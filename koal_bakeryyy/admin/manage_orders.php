<?php
session_start();
include '../includes/config.php';

// --- LOGIC PHP GIỮ NGUYÊN ---
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Xử lý update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    if ($order_id && in_array($status, ['pending', 'completed', 'cancelled'])) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Cập nhật trạng thái đơn #' . $order_id . ' thành công!';
        } else {
            $_SESSION['error'] = 'Lỗi cập nhật!';
        }
        $stmt->close();
    }
    header('Location: manage_orders.php');
    exit;
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Lấy orders
$stmt = $conn->prepare("
    SELECT o.*, c.name AS customer_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    ORDER BY o.created_at DESC LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Total pages
$total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders");
$total_stmt->execute();
$total_orders = $total_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);
$total_stmt->close();

// Messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn Hàng - NeelMilk Admin</title>
    
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

        /* Page Header */
        .page-header {
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(197, 160, 89, 0.2);
        }
        .page-title {
            font-family: "Playfair Display", serif;
            font-size: 2rem;
            color: var(--brand-green);
            margin-bottom: 5px;
        }

        /* Admin Card Container */
        .admin-card {
            background: #fff;
            border-radius: 25px;
            box-shadow: var(--shadow-soft);
            padding: 30px;
            border: none;
        }

        /* Table Styling */
        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px; /* Khoảng cách giữa các hàng */
        }
        
        .table-custom thead th {
            border: none;
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: 700;
            color: #888;
            padding: 15px;
            background: transparent;
        }

        .table-custom tbody tr {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .table-custom tbody tr:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            z-index: 1;
            position: relative;
        }

        .table-custom td {
            border: none;
            padding: 20px 15px;
            vertical-align: middle;
            border-top: 1px solid #f9f9f9;
            border-bottom: 1px solid #f9f9f9;
        }
        /* Bo tròn đầu đuôi */
        .table-custom td:first-child { border-top-left-radius: 15px; border-bottom-left-radius: 15px; border-left: 1px solid #f9f9f9; }
        .table-custom td:last-child { border-top-right-radius: 15px; border-bottom-right-radius: 15px; border-right: 1px solid #f9f9f9; }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
            min-width: 90px;
            text-align: center;
        }
        .status-pending { background: #fff8e1; color: #f57c00; border: 1px solid #ffe0b2; }
        .status-completed { background: #e8f5e9; color: #2e7d5e; border: 1px solid #c8e6c9; }
        .status-cancelled { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

        /* Action Form */
        .form-select-custom {
            border-radius: 20px;
            border: 1px solid #eee;
            font-size: 0.9rem;
            padding: 5px 30px 5px 15px;
            cursor: pointer;
            background-color: #f8f9fa;
        }
        .form-select-custom:focus {
            border-color: var(--brand-gold);
            box-shadow: none;
        }

        .btn-update {
            border: none;
            background: var(--brand-green);
            color: white;
            width: 32px; height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center; justify-content: center;
            transition: 0.2s;
            margin-left: 5px;
        }
        .btn-update:hover { background: var(--brand-gold); transform: rotate(15deg); }

        /* Pagination */
        .pagination .page-link {
            border: none; margin: 0 5px;
            border-radius: 50% !important;
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            color: #555; font-weight: 600;
        }
        .pagination .page-item.active .page-link {
            background: var(--brand-green); color: #fff;
            box-shadow: 0 4px 10px rgba(46, 125, 94, 0.3);
        }

        /* Alert */
        .alert-soft { border-radius: 15px; border: none; font-size: 0.95rem; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container py-5">
        <!-- Header -->
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title">Quản Lý Đơn Hàng</h2>
                <p class="text-muted mb-0">Theo dõi và cập nhật trạng thái đơn hàng sữa.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Dashboard
            </a>
        </div>

        <!-- Thông báo -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-soft d-flex align-items-center mb-4">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-soft d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Main Card -->
        <div class="admin-card">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th width="5%">#ID</th>
                            <th width="20%">Khách hàng</th>
                            <th width="15%">Tổng tiền</th>
                            <th width="15%">Ngày đặt</th>
                            <th width="15%">Trạng thái hiện tại</th>
                            <th width="30%">Cập nhật trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?php echo $order['id']; ?></td>
                                
                                <td class="fw-bold" style="color: var(--brand-green);">
                                    <i class="far fa-user-circle me-2"></i>
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </td>
                                
                                <td class="fw-bold"><?php echo number_format($order['total_amount']); ?> VNĐ</td>
                                
                                <td class="text-muted small">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </td>

                                <td>
                                    <?php 
                                        $badge_class = 'status-pending';
                                        $status_text = 'Chờ xử lý';
                                        
                                        if ($order['status'] == 'completed') {
                                            $badge_class = 'status-completed';
                                            $status_text = 'Hoàn thành';
                                        } elseif ($order['status'] == 'cancelled') {
                                            $badge_class = 'status-cancelled';
                                            $status_text = 'Đã hủy';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $badge_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>

                                <td>
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        
                                        <select name="status" class="form-select form-select-custom me-2 w-auto">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Hủy đơn</option>
                                        </select>
                                        
                                        <button type="submit" name="update_status" class="btn-update" title="Lưu thay đổi">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if(empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                                    <p>Chưa có đơn hàng nào.</p>
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