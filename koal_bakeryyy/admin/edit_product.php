<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = 'ID sản phẩm không hợp lệ!';
    header('Location: manage_products.php');
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['error'] = 'Sản phẩm không tồn tại!';
    header('Location: manage_products.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $image = $_FILES['image']['name'] ? $_FILES['image']['name'] : $product['image'];
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $search_count = filter_input(INPUT_POST, 'search_count', FILTER_VALIDATE_INT);

    if (!$name || !$price || $price <= 0 || !$stock || $stock < 0) {
        $error = 'Vui lòng nhập đầy đủ và hợp lệ các thông tin!';
    } else {
        if ($_FILES['image']['name']) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            if ($_FILES['image']['size'] > $max_size || !in_array($_FILES['image']['type'], $allowed_types)) {
                $error = 'Hình ảnh không hợp lệ! Chỉ chấp nhận JPEG, PNG, GIF và kích thước dưới 2MB.';
            }
        }
        if (!isset($error)) {
            $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ?, image = ?, stock = ?, search_count = ? WHERE id = ?");
            if ($stmt === false) {
                $error = 'Lỗi server khi chuẩn bị truy vấn!';
                error_log("Prepare failed: " . $conn->error);
            } else {
                $stmt->bind_param("sdssiii", $name, $price, $description, $image, $stock, $search_count, $id);
                if ($stmt->execute()) {
                    if ($_FILES['image']['name']) {
                        move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $image);
                    }
                    $_SESSION['success'] = 'Cập nhật sản phẩm thành công!';
                    header('Location: manage_products.php');
                    exit;
                } else {
                    $error = 'Lỗi khi cập nhật sản phẩm!';
                    error_log("Update failed: " . $stmt->error);
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Sửa sản phẩm</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Tên sản phẩm</label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Giá</label>
                <input type="number" class="form-control" name="price" value="<?php echo $product['price']; ?>" step="0.01" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <textarea class="form-control" name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Hình ảnh</label>
                <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/gif">
                <img src="../images/<?php echo htmlspecialchars($product['image']); ?>" width="100" class="mt-2">
            </div>
            <div class="mb-3">
                <label class="form-label">Số lượng tồn kho</label>
                <input type="number" class="form-control" name="stock" value="<?php echo $product['stock']; ?>" min="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Số lần tìm kiếm</label>
                <input type="number" class="form-control" name="search_count" value="<?php echo $product['search_count']; ?>" min="0" required>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>