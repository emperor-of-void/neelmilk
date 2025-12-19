<?php
session_start();
include '../includes/config.php';

// --- LOGIC PHP GIỮ NGUYÊN ---
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Xử lý thông báo
$success = isset($_SESSION['success']) ? $_SESSION['success'] : null;
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['success'], $_SESSION['error']);

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $image = $_FILES['image']['name'];
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    
    if (!$name || !$price || $price <= 0 || !$stock || $stock < 0) {
        $error = 'Vui lòng nhập đầy đủ và hợp lệ các thông tin!';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if ($_FILES['image']['size'] > $max_size || !in_array($_FILES['image']['type'], $allowed_types)) {
            $error = 'Hình ảnh không hợp lệ! Chỉ chấp nhận JPEG, PNG, GIF và kích thước dưới 2MB.';
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, price, description, image, stock, search_count) VALUES (?, ?, ?, ?, ?, 0)");
            if ($stmt === false) {
                $error = 'Lỗi server khi thêm sản phẩm!';
                error_log("Prepare failed: " . $conn->error);
            } else {
                $stmt->bind_param("sdssi", $name, $price, $description, $image, $stock);
                if ($stmt->execute()) {
                    move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $image);
                    $_SESSION['success'] = 'Thêm sản phẩm thành công!'; // Dùng session để reload không mất thông báo
                    header("Location: manage_products.php");
                    exit;
                } else {
                    $error = 'Lỗi khi thêm sản phẩm!';
                    error_log("Insert failed: " . $stmt->error);
                }
                $stmt->close();
            }
        }
    }
}

// Phân trang
$limit = 5;
$page = isset($_GET['page']) && filter_var($_GET['page'], FILTER_VALIDATE_INT) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT COUNT(*) as total FROM products";
$result = $conn->query($sql);
$total_products = $result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

$sql = "SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm - NeelMilk Admin</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts Luxury -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --admin-bg: #fffbf0;
            --brand-green: #2e7d5e;
            --brand-gold: #c5a059;
            --text-dark: #333;
            --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
        }

        body {
            background-color: var(--admin-bg);
            font-family: "Quicksand", sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Header Page */
        .page-header {
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(197, 160, 89, 0.2);
            padding-bottom: 15px;
        }
        .page-title {
            font-family: "Playfair Display", serif;
            font-size: 2rem;
            color: var(--brand-green);
        }

        /* Admin Card (Container chung) */
        .admin-card {
            background: #fff;
            border-radius: 25px;
            box-shadow: var(--shadow-soft);
            padding: 30px;
            border: none;
            margin-bottom: 30px;
        }

        /* Form Thêm Sản Phẩm */
        .form-label { font-weight: 600; color: #666; font-size: 0.9rem; }
        .form-control {
            border-radius: 15px;
            border: 1px solid #eee;
            padding: 10px 15px;
            background: #fcfcfc;
            transition: 0.3s;
        }
        .form-control:focus {
            background: #fff;
            border-color: var(--brand-gold);
            box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.1);
        }
        
        .btn-luxury {
            background: linear-gradient(135deg, var(--brand-green), #43a07a);
            color: #fff; border: none;
            border-radius: 50px; padding: 10px 25px;
            font-weight: 600; transition: 0.3s;
            box-shadow: 0 5px 15px rgba(46, 125, 94, 0.2);
        }
        .btn-luxury:hover { transform: translateY(-2px); color: #fff; box-shadow: 0 8px 20px rgba(46, 125, 94, 0.3); }

        .btn-toggle-form {
            background: #fff; border: 1px solid var(--brand-green);
            color: var(--brand-green); border-radius: 50px;
            padding: 8px 20px; font-weight: 600;
        }
        .btn-toggle-form:hover { background: var(--brand-green); color: #fff; }

        /* Table Styling */
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .table-custom thead th {
            border: none; text-transform: uppercase;
            font-size: 0.8rem; font-weight: 700; color: #888; padding: 15px;
        }
        .table-custom tbody tr {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }
        .table-custom tbody tr:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .table-custom td {
            border: none; padding: 15px; vertical-align: middle;
            border-top: 1px solid #f9f9f9; border-bottom: 1px solid #f9f9f9;
        }
        .table-custom td:first-child { border-top-left-radius: 15px; border-bottom-left-radius: 15px; border-left: 1px solid #f9f9f9; }
        .table-custom td:last-child { border-top-right-radius: 15px; border-bottom-right-radius: 15px; border-right: 1px solid #f9f9f9; }

        .prod-thumb {
            width: 50px; height: 50px; object-fit: cover; border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Badges */
        .badge-stock {
            padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 600;
        }
        .badge-ok { background: #e8f5e9; color: var(--brand-green); }
        .badge-low { background: #ffebee; color: #c62828; }

        /* Action Buttons */
        .action-btn {
            width: 32px; height: 32px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            color: #555; background: #f5f5f5; margin-right: 5px;
            transition: 0.2s; text-decoration: none;
        }
        .action-btn:hover { background: var(--brand-gold); color: #fff; }
        .action-btn.delete:hover { background: #ff6b6b; }

        /* Alert */
        .alert-soft { border-radius: 15px; border: none; font-size: 0.9rem; }
        
        /* Pagination */
        .pagination .page-link {
            border: none; margin: 0 5px; border-radius: 50% !important;
            width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;
            color: #555; font-weight: 600;
        }
        .pagination .page-item.active .page-link {
            background: var(--brand-green); color: #fff;
            box-shadow: 0 4px 10px rgba(46, 125, 94, 0.3);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        
        <!-- Header -->
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title">Quản Lý Sản Phẩm</h2>
                <p class="text-muted mb-0">Kho hàng sữa tươi & thực phẩm.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-toggle-form" type="button" data-bs-toggle="collapse" data-bs-target="#addProductForm">
                    <i class="fas fa-plus me-2"></i>Thêm Mới
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>

        <!-- Thông báo -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-soft d-flex align-items-center mb-4">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-soft d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Form Thêm Sản Phẩm (Collapse) -->
        <div class="collapse mb-4" id="addProductForm">
            <div class="admin-card bg-white">
                <h4 class="mb-4" style="font-family: 'Playfair Display'; color: var(--brand-green);">Thêm Sản Phẩm Mới</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_product" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tên sản phẩm</label>
                            <input type="text" class="form-control" name="name" required placeholder="VD: Sữa tươi thanh trùng...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Giá bán (VNĐ)</label>
                            <input type="number" class="form-control" name="price" step="1000" required placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tồn kho</label>
                            <input type="number" class="form-control" name="stock" value="50" min="0" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Mô tả sản phẩm</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control" name="image" accept="image/*" required>
                            <div class="form-text">Kích thước tối đa 2MB. Định dạng JPG, PNG.</div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-luxury">
                                <i class="fas fa-save me-2"></i>Lưu Sản Phẩm
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="admin-card">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th width="5%">#ID</th>
                            <th width="10%">Ảnh</th>
                            <th width="25%">Tên sản phẩm</th>
                            <th width="15%">Giá bán</th>
                            <th width="15%">Tồn kho</th>
                            <th width="15%">Lượt tìm</th>
                            <th width="15%" class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="text-muted fw-bold">#<?php echo $row['id']; ?></td>
                                <td>
                                    <img src="../images/<?php echo htmlspecialchars($row['image']); ?>" class="prod-thumb" alt="img">
                                </td>
                                <td class="fw-bold" style="color: var(--brand-green);">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </td>
                                <td class="fw-bold text-dark">
                                    <?php echo number_format($row['price']); ?> đ
                                </td>
                                <td>
                                    <?php if ($row['stock'] < 10): ?>
                                        <span class="badge-stock badge-low">
                                            <i class="fas fa-exclamation-circle me-1"></i>Sắp hết (<?php echo $row['stock']; ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-stock badge-ok">
                                            <?php echo $row['stock']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted">
                                    <i class="far fa-eye me-1"></i><?php echo $row['search_count']; ?>
                                </td>
                                <td class="text-end">
                                    <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="action-btn" title="Sửa">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="action-btn delete" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?')" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav>
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $stmt->close(); ?>