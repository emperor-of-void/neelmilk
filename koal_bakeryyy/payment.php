<?php
session_start();
include 'includes/config.php';

if (!isset($_SESSION['pending_order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = $_SESSION['pending_order_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Không tìm thấy đơn hàng.");
}

// CẤU HÌNH TÀI KHOẢN NHẬN TIỀN CỦA BẠN (SỬA TẠI ĐÂY)
$MY_BANK_ID = 'VCB'; // Mã ngân hàng (VCB, TCB, MB, ACB...)
$MY_ACCOUNT_NO = '1234567890'; // Số tài khoản của bạn
$MY_ACCOUNT_NAME = 'NGUYEN VAN A'; // Tên chủ tài khoản (Viết hoa không dấu)

// Tạo link QR VietQR (Tự động điền số tiền và nội dung)
// Cấu trúc: https://img.vietqr.io/image/[BANK]-[ACC_NO]-[TEMPLATE].png?amount=[TIEN]&addInfo=[NOI_DUNG]
$amount = $order['total_amount'];
$content = "THANH TOAN DON " . $order_id; // Nội dung chuyển khoản ngắn gọn
$vietqr_url = "https://img.vietqr.io/image/{$MY_BANK_ID}-{$MY_ACCOUNT_NO}-compact2.png?amount={$amount}&addInfo={$content}&accountName={$MY_ACCOUNT_NAME}";

// Link QR MoMo (Dùng link tạo QR cá nhân của bạn hoặc ảnh tĩnh)
// Mẹo: Bạn vào MoMo > Nhận tiền > Lưu ảnh QR của bạn > Đặt tên là momo_qr.jpg và bỏ vào thư mục images
$momo_qr = "images/momo_qr.jpg"; 

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán đơn hàng #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Quicksand:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #fffbf0; font-family: 'Quicksand', sans-serif; }
        .payment-card {
            background: #fff; border-radius: 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.05);
            padding: 40px; max-width: 500px; margin: 50px auto;
            text-align: center;
        }
        .qr-img {
            width: 100%; max-width: 300px;
            border-radius: 15px;
            border: 2px solid #eee;
            margin-bottom: 20px;
        }
        .amount-text {
            font-family: 'Playfair Display', serif;
            font-size: 2rem; color: #2e7d5e; font-weight: 700;
        }
        .btn-confirm {
            background: linear-gradient(135deg, #2e7d5e, #43a07a);
            color: #fff; border-radius: 50px; padding: 12px 30px; width: 100%;
            font-weight: 600; border: none; margin-top: 20px;
        }
        .btn-confirm:hover { color: #fff; opacity: 0.9; }
        .note-box {
            background: #fff3cd; color: #856404;
            padding: 15px; border-radius: 15px; font-size: 0.9rem; margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="payment-card">
            <h3 style="font-family: 'Playfair Display'; color: #2e7d5e;">Thanh Toán Ngay</h3>
            <p class="text-muted">Mã đơn hàng: <strong>#<?= $order_id ?></strong></p>
            
            <div class="amount-text mb-3"><?= number_format($amount) ?> VNĐ</div>

            <?php if ($order['payment_method'] == 'bank_transfer'): ?>
                <!-- Hiển thị QR Ngân hàng -->
                <img src="<?= $vietqr_url ?>" class="qr-img" alt="Mã QR VietQR">
                <p class="mb-1">Ngân hàng: <strong><?= $MY_BANK_ID ?></strong></p>
                <p class="mb-1">Số tài khoản: <strong><?= $MY_ACCOUNT_NO ?></strong></p>
                <p class="mb-1">Chủ tài khoản: <strong><?= $MY_ACCOUNT_NAME ?></strong></p>
                <p class="mb-0">Nội dung: <strong><?= $content ?></strong></p>

            <?php elseif ($order['payment_method'] == 'digital_wallet'): ?>
                <!-- Hiển thị QR MoMo -->
                <img src="<?= $momo_qr ?>" class="qr-img" alt="Mã QR MoMo">
                <p>Vui lòng quét mã MoMo trên và nhập số tiền: <strong><?= number_format($amount) ?> đ</strong></p>
                <p>Nội dung chuyển khoản: <strong><?= $content ?></strong></p>
            <?php endif; ?>

            <div class="note-box">
                <i class="fas fa-info-circle me-1"></i>
                <strong>Lưu ý:</strong> Sau khi chuyển khoản thành công, vui lòng ấn nút "Tôi đã thanh toán" bên dưới. Admin sẽ kiểm tra và xác nhận đơn hàng của bạn.
            </div>

            <a href="order_history.php" class="btn btn-confirm">
                <i class="fas fa-check-circle me-2"></i> Tôi đã thanh toán
            </a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>