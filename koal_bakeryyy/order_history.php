<?php
session_start();
include 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để xem lịch sử đơn hàng!';
    header('Location: login_customer.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Hàm hỗ trợ hiển thị trạng thái đẹp
function getStatusBadge($status) {
    switch ($status) {
        case 'pending': return '<span class="badge-soft bg-soft-warning"><i class="fas fa-clock me-1"></i>Chờ xử lý</span>';
        case 'completed': return '<span class="badge-soft bg-soft-success"><i class="fas fa-check-circle me-1"></i>Hoàn thành</span>';
        case 'cancelled': return '<span class="badge-soft bg-soft-danger"><i class="fas fa-times-circle me-1"></i>Đã hủy</span>';
        default: return '<span class="badge-soft bg-light text-dark">' . htmlspecialchars($status) . '</span>';
    }
}

// Hàm hiển thị phương thức thanh toán đẹp
function getPaymentMethod($method) {
    switch ($method) {
        case 'cod': return '<i class="fas fa-money-bill-wave text-success me-1"></i> Tiền mặt (COD)';
        case 'bank_transfer': return '<i class="fas fa-university text-primary me-1"></i> Chuyển khoản';
        case 'digital_wallet': return '<i class="fas fa-qrcode text-danger me-1"></i> Ví điện tử';
        default: return htmlspecialchars($method);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng - NeelMilk</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts Luxury -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --brand-green: #2e7d5e;
            --brand-gold: #c5a059;
            --soft-bg: #fffbf0;
            --text-dark: #333;
        }

        body {
            background-color: var(--soft-bg);
            font-family: "Quicksand", sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            margin-top: 20px;
        }
        .page-title {
            font-family: "Playfair Display", serif;
            font-size: 2.5rem;
            color: var(--brand-green);
            position: relative;
            display: inline-block;
        }
        .page-title::after {
            content: ''; display: block; width: 50px; height: 3px;
            background: var(--brand-gold); margin: 10px auto 0; border-radius: 5px;
        }

        /* Card Container */
        .history-container {
            max-width: 1000px;
            margin: 0 auto;
            padding-bottom: 60px;
        }

        /* Table Customization (Floating Rows) */
        .table-custom {
            border-collapse: separate;
            border-spacing: 0 15px; /* Tạo khoảng cách giữa các dòng */
        }
        
        .table-custom thead th {
            border: none;
            font-family: "Playfair Display", serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: #666;
            padding: 0 15px 10px 15px;
        }

        .table-custom tbody tr {
            background-color: #fff;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .table-custom tbody tr:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(46, 125, 94, 0.1);
        }

        .table-custom td {
            border: none;
            padding: 20px 15px;
            vertical-align: middle;
        }
        /* Bo tròn đầu và cuối dòng */
        .table-custom td:first-child { border-top-left-radius: 20px; border-bottom-left-radius: 20px; }
        .table-custom td:last-child { border-top-right-radius: 20px; border-bottom-right-radius: 20px; }

        /* Badges & Text Styles */
        .order-id { font-weight: 700; color: var(--text-dark); }
        .order-date { font-size: 0.9rem; color: #888; }
        .order-total { font-weight: 700; color: var(--brand-green); font-size: 1.1rem; }
        
        .badge-soft {
            padding: 8px 12px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; display: inline-block;
        }
        .bg-soft-warning { background: #fff8e1; color: #f57c00; }
        .bg-soft-success { background: #e8f5e9; color: #2e7d5e; }
        .bg-soft-danger { background: #ffebee; color: #c62828; }

        .btn-detail {
            width: 40px; height: 40px;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            background: #f5f5f5; color: #555;
            transition: 0.3s; text-decoration: none;
        }
        .btn-detail:hover {
            background: var(--brand-gold); color: #fff;
            transform: rotate(45deg);
        }

        /* Empty State */
        .empty-state {
            text-align: center; padding: 50px;
            background: #fff; border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }
        .btn-shop-now {
            background: linear-gradient(135deg, var(--brand-green), #43a07a);
            color: #fff; padding: 10px 30px; border-radius: 50px;
            text-decoration: none; font-weight: 600; display: inline-block; margin-top: 20px;
            box-shadow: 0 5px 15px rgba(46, 125, 94, 0.2);
        }
        .btn-shop-now:hover { transform: translateY(-2px); color: #fff; box-shadow: 0 8px 20px rgba(46, 125, 94, 0.3); }
        
        /* Back Button */
        .btn-back {
            color: #888; text-decoration: none; font-weight: 600;
            display: inline-block; margin-top: 20px; transition: 0.3s;
        }
        .btn-back:hover { color: var(--brand-green); transform: translateX(-5px); }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container history-container mt-5">
        
        <div class="page-header">
            <h2 class="page-title">Hành Trình Mua Sắm</h2>
            <p class="text-muted mt-2">Lịch sử các đơn hàng sữa tươi bạn đã đặt.</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success rounded-pill text-center border-0 shadow-sm mb-4">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list fa-4x mb-3 text-muted opacity-25"></i>
                <h4 style="font-family: 'Playfair Display'">Bạn chưa có đơn hàng nào</h4>
                <p class="text-muted">Hãy khám phá các sản phẩm sữa tươi ngon ngay hôm nay.</p>
                <a href="index.php" class="btn-shop-now">Mua Sắm Ngay</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th width="15%">Mã đơn</th>
                            <th width="20%">Ngày đặt</th>
                            <th width="20%">Thanh toán</th>
                            <th width="15%">Tổng tiền</th>
                            <th width="15%">Trạng thái</th>
                            <th width="10%" class="text-center">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <span class="order-id">#<?php echo $order['id']; ?></span>
                                </td>
                                <td>
                                    <span class="order-date">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.9rem;">
                                    <?php echo getPaymentMethod($order['payment_method']); ?>
                                </td>
                                <td>
                                    <span class="order-total"><?php echo number_format($order['total_amount']); ?> <small>đ</small></span>
                                </td>
                                <td>
                                    <?php echo getStatusBadge($order['status']); ?>
                                </td>
                                <td class="text-center">
                                    <a href="order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn-detail" title="Xem chi tiết">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="text-center">
            <a href="index.php" class="btn-back">
                <i class="fas fa-long-arrow-alt-left me-2"></i>Quay lại trang chủ
            </a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>