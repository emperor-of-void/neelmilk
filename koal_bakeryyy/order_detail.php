<?php
session_start();
include 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để xem chi tiết đơn hàng!';
    header('Location: login_customer.php');
    exit;
}

if (!isset($_GET['order_id']) || !filter_var($_GET['order_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = 'Mã đơn hàng không hợp lệ!';
    header('Location: order_history.php');
    exit;
}

$order_id = $_GET['order_id'];
$customer_id = $_SESSION['customer_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['error'] = 'Đơn hàng không tồn tại hoặc không thuộc về bạn!';
    header('Location: order_history.php');
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("SELECT od.*, p.name FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Chi tiết đơn hàng #<?php echo $order_id; ?></h2>
        <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount']); ?> VNĐ</p>
        <p><strong>Phương thức thanh toán:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
        <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
        <p><strong>Ngày đặt:</strong> <?php echo $order['created_at']; ?></p>
        <h4>Danh sách sản phẩm</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th>Tổng</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_details as $detail): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($detail['name']); ?></td>
                        <td><?php echo number_format($detail['price']); ?> VNĐ</td>
                        <td><?php echo $detail['quantity']; ?></td>
                        <td><?php echo number_format($detail['price'] * $detail['quantity']); ?> VNĐ</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="order_history.php" class="btn btn-secondary">Quay lại lịch sử đơn hàng</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>