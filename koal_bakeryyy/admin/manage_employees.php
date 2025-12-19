<?php
session_start();
// 1. SỬA LỖI: Include file cấu hình để có biến $conn
include '../includes/config.php';

// Kiểm tra đăng nhập Admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Xử lý thông báo
$success = isset($_SESSION['success']) ? $_SESSION['success'] : null;
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['success'], $_SESSION['error']);

// 2. XỬ LÝ THÊM NHÂN VIÊN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_employee'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING);
    
    // Giả sử nhân viên cũng cần đăng nhập, ta tạo username/pass mặc định hoặc bỏ qua nếu chỉ quản lý thông tin
    // Ở đây tôi làm ví dụ quản lý thông tin cơ bản
    
    if (!$name || !$email) {
        $error = 'Vui lòng nhập tên và email!';
    } else {
        // Kiểm tra email trùng
        $check = $conn->prepare("SELECT id FROM employees WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email nhân viên đã tồn tại!';
        } else {
            $stmt = $conn->prepare("INSERT INTO employees (name, email, phone, position, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("ssss", $name, $email, $phone, $position);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Thêm nhân viên thành công!';
                    header("Location: manage_employees.php");
                    exit;
                } else {
                    $error = 'Lỗi khi thêm: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Lỗi kết nối CSDL!';
            }
        }
        $check->close();
    }
}

// 3. XỬ LÝ XÓA NHÂN VIÊN
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Đã xóa nhân viên!';
        header("Location: manage_employees.php");
        exit;
    } else {
        $error = 'Lỗi khi xóa!';
    }
}

// 4. PHÂN TRANG & LẤY DỮ LIỆU
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng
$total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM employees");
$total_stmt->execute();
$total_res = $total_stmt->get_result()->fetch_assoc();
$total_employees = $total_res['total']; // Fix lỗi nếu fetch_assoc trả về null
$total_pages = ceil($total_employees / $limit);
$total_stmt->close();

// Lấy danh sách
$stmt = $conn->prepare("SELECT * FROM employees ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Nhân Viên - NeelMilk Admin</title>
    
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

        /* Header */
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

        /* Card Container */
        .admin-card {
            background: #fff;
            border-radius: 25px;
            box-shadow: var(--shadow-soft);
            padding: 30px;
            border: none;
            margin-bottom: 30px;
        }

        /* Form Styles */
        .form-label { font-weight: 600; color: #666; font-size: 0.9rem; }
        .form-control, .form-select {
            border-radius: 15px;
            border: 1px solid #eee;
            padding: 10px 15px;
            background: #fcfcfc;
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

        /* Table Styles (Floating Rows) */
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

        /* Avatar Circle */
        .avatar-circle {
            width: 40px; height: 40px; background: #e0f2f1; color: var(--brand-green);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: bold; margin-right: 10px;
        }

        /* Action Button */
        .btn-delete {
            width: 32px; height: 32px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            color: #ff6b6b; background: #ffebee;
            transition: 0.2s; text-decoration: none;
        }
        .btn-delete:hover { background: #ff6b6b; color: #fff; }

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

        /* Alert */
        .alert-soft { border-radius: 15px; border: none; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container py-5">
        
        <!-- Header -->
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title">Quản Lý Nhân Sự</h2>
                <p class="text-muted mb-0">Danh sách nhân viên và phân quyền.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-toggle-form" type="button" data-bs-toggle="collapse" data-bs-target="#addEmployeeForm">
                    <i class="fas fa-user-plus me-2"></i>Thêm Mới
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

        <!-- Form Thêm Nhân Viên (Collapse) -->
        <div class="collapse mb-4" id="addEmployeeForm">
            <div class="admin-card bg-white">
                <h4 class="mb-4" style="font-family: 'Playfair Display'; color: var(--brand-green);">Thêm Hồ Sơ Nhân Viên</h4>
                <form method="POST">
                    <input type="hidden" name="add_employee" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" name="name" required placeholder="Nguyễn Văn A">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required placeholder="email@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control" name="phone" placeholder="09xxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Chức vụ / Vị trí</label>
                            <select class="form-select" name="position">
                                <option value="Nhân viên bán hàng">Nhân viên bán hàng</option>
                                <option value="Quản lý kho">Quản lý kho</option>
                                <option value="Giao hàng">Giao hàng</option>
                                <option value="Quản trị viên">Quản trị viên</option>
                            </select>
                        </div>
                        <div class="col-12 text-end mt-4">
                            <button type="submit" class="btn btn-luxury">
                                <i class="fas fa-save me-2"></i>Lưu Thông Tin
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách nhân viên -->
        <div class="admin-card">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="25%">Họ tên</th>
                            <th width="20%">Thông tin liên hệ</th>
                            <th width="20%">Chức vụ</th>
                            <th width="15%">Ngày tham gia</th>
                            <th width="15%" class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($employees)): ?>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td class="text-muted fw-bold">#<?php echo $emp['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle">
                                                <?php echo strtoupper(substr($emp['name'], 0, 1)); ?>
                                            </div>
                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($emp['name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-dark mb-1"><i class="far fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($emp['email']); ?></span>
                                            <span class="text-muted small"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($emp['phone'] ?? 'N/A'); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-pill px-3 py-2 fw-normal">
                                            <?php echo htmlspecialchars($emp['position'] ?? 'Nhân viên'); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($emp['created_at'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?delete_id=<?php echo $emp['id']; ?>" class="btn-delete" 
                                           onclick="return confirm('Bạn có chắc muốn xóa nhân viên này?');" title="Xóa">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fa-3x mb-3 opacity-25"></i>
                                    <p>Chưa có nhân viên nào trong danh sách.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav>
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>