<?php
session_start();
include '../includes/config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = 'ID nhân viên không hợp lệ!';
    header('Location: manage_employees.php');
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$employee) {
    $_SESSION['error'] = 'Nhân viên không tồn tại!';
    header('Location: manage_employees.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if (!$name || !$email || !$role || !$status) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $stmt = $conn->prepare("UPDATE employees SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
        if ($stmt === false) {
            $error = 'Lỗi server khi cập nhật nhân viên!';
            error_log("Prepare failed: " . $conn->error);
        } else {
            $stmt->bind_param("sssss", $name, $email, $role, $status, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Cập nhật nhân viên thành công!';
                header('Location: manage_employees.php');
                exit;
            } else {
                $error = 'Lỗi khi cập nhật nhân viên!';
                error_log("Update failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa nhân viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Sửa nhân viên</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Tên nhân viên</label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Vai trò</label>
                <select class="form-control" name="role" required>
                    <option value="cashier" <?php echo $employee['role'] == 'cashier' ? 'selected' : ''; ?>>Thu ngân</option>
                    <option value="baker" <?php echo $employee['role'] == 'baker' ? 'selected' : ''; ?>>Làm bánh</option>
                    <option value="manager" <?php echo $employee['role'] == 'manager' ? 'selected' : ''; ?>>Quản lý</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Trạng thái</label>
                <select class="form-control" name="status" required>
                    <option value="active" <?php echo $employee['status'] == 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                    <option value="inactive" <?php echo $employee['status'] == 'inactive' ? 'selected' : ''; ?>>Ngưng hoạt động</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>