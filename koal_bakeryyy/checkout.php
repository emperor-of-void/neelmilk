<?php
session_start();
include 'includes/config.php';

// --- 1. KIỂM TRA ĐIỀU KIỆN ---
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để thanh toán!';
    header('Location: login_customer.php');
    exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error'] = 'Giỏ hàng của bạn trống!';
    header('Location: index.php');
    exit;
}

// Tạo CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- 2. LẤY THÔNG TIN GIỎ HÀNG ---
$cart_items = [];
$total = 0;
$product_ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));

$stmt = $conn->prepare("SELECT id, name, price, stock, image FROM products WHERE id IN ($placeholders)");
if ($stmt === false) {
    $_SESSION['error'] = 'Lỗi truy vấn: ' . $conn->error;
    header('Location: cart.php');
    exit;
}
$stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
$stmt->execute();
$result = $stmt->get_result();

while ($product = $result->fetch_assoc()) {
    $product_id = $product['id'];
    $quantity = isset($_SESSION['cart'][$product_id]) ? (int)$_SESSION['cart'][$product_id] : 0;
    if ($quantity > 0) {
        if ($quantity > $product['stock']) {
            $_SESSION['error'] = 'Sản phẩm ' . htmlspecialchars($product['name']) . ' chỉ còn ' . $product['stock'];
            header('Location: cart.php');
            exit;
        }
        $cart_items[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'subtotal' => $product['price'] * $quantity,
            'image' => $product['image']
        ];
        $total += $product['price'] * $quantity;
    }
}
$stmt->close();

$error = null;

// --- 3. XỬ LÝ THANH TOÁN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Token không hợp lệ! Vui lòng thử lại.';
    } else {
        $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
        if (!in_array($payment_method, ['cod', 'bank_transfer', 'digital_wallet'])) {
            $error = 'Phương thức thanh toán không hợp lệ!';
        } else {
            $customer_id = $_SESSION['customer_id'];
            
            // Bắt đầu Transaction
            $conn->autocommit(FALSE);
            
            try {
                // Insert Đơn Hàng
                $stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, payment_method, status) VALUES (?, ?, ?, 'pending')");
                if ($stmt === false) throw new Exception($conn->error);
                
                $stmt->bind_param("ids", $customer_id, $total, $payment_method);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                $order_id = $conn->insert_id;
                $stmt->close();

                // Insert Chi Tiết Đơn Hàng & Trừ Tồn Kho
                foreach ($cart_items as $item) {
                    // Insert chi tiết
                    $stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
                    if (!$stmt->execute()) throw new Exception($stmt->error);
                    $stmt->close();

                    // Trừ kho
                    $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->bind_param("ii", $item['quantity'], $item['id']);
                    if (!$stmt->execute()) throw new Exception($stmt->error);
                    $stmt->close();
                }

                // Hoàn tất Transaction
                $conn->commit();
                unset($_SESSION['cart']); // Xóa giỏ hàng

                // --- ĐIỀU HƯỚNG DỰA TRÊN PHƯƠNG THỨC THANH TOÁN ---
                if ($payment_method == 'cod') {
                    // Thanh toán khi nhận hàng -> Xong luôn
                    $_SESSION['success'] = 'Đặt hàng thành công! Mã đơn: #' . $order_id;
                    header('Location: order_history.php');
                } else {
                    // Chuyển khoản -> Qua trang quét QR
                    $_SESSION['pending_order_id'] = $order_id;
                    header('Location: payment.php');
                }
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Lỗi xử lý đơn hàng: ' . $e->getMessage();
            }
            
            $conn->autocommit(TRUE);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - NeelMilk Premium</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts: Playfair Display (Luxury) & Quicksand (Soft) -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --brand-green: #2e7d5e;
            --brand-light: #e8f5e9;
            --soft-bg: #f9fcfb;
            --text-dark: #2d2d2d;
            --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
        }

        body {
            background-color: var(--soft-bg);
            font-family: "Quicksand", sans-serif;
            color: var(--text-dark);
        }

        h2, h4, h5 { font-family: "Playfair Display", serif; font-weight: 700; color: var(--brand-green); }

        /* Custom Radio Buttons for Payment */
        .payment-option {
            border: 2px solid #eee;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
            position: relative;
        }
        
        .payment-option:hover { transform: translateY(-3px); box-shadow: var(--shadow-soft); }
        
        /* Ẩn input radio nhưng giữ layout */
        .payment-option input[type="radio"] {
            position: absolute; opacity: 0; width: 100%; height: 100%; top: 0; left: 0; cursor: pointer; z-index: 2;
        }

        /* Trạng thái Active */
        .payment-option.active {
            border-color: var(--brand-green);
            background-color: #f1f8f5;
        }
        
        .payment-icon { font-size: 1.5rem; margin-right: 15px; color: #888; transition: 0.3s; }
        .payment-option.active .payment-icon { color: var(--brand-green); }

        /* Details Box */
        .payment-details {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #fff;
            border-radius: 15px;
            font-size: 0.9rem;
            border-left: 4px solid var(--brand-green);
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .payment-details.show { display: block; }

        /* Summary Card */
        .summary-card {
            background: #fff;
            border-radius: 25px;
            padding: 30px;
            box-shadow: var(--shadow-soft);
            position: sticky; top: 100px;
        }

        .cart-item-row {
            display: flex; align-items: center; margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 15px;
        }
        .cart-img-sm { width: 60px; height: 60px; object-fit: cover; border-radius: 12px; margin-right: 15px; }
        .item-name { font-weight: 600; font-size: 0.95rem; margin-bottom: 2px; }
        .item-qty { font-size: 0.85rem; color: #888; }
        .item-price { font-weight: 700; color: var(--brand-green); margin-left: auto; }

        /* Buttons */
        .btn-pay {
            background: linear-gradient(135deg, var(--brand-green), #43a07a);
            color: #fff; border: none;
            border-radius: 50px; padding: 15px;
            width: 100%; font-weight: 700; font-size: 1.1rem;
            box-shadow: 0 10px 20px rgba(46, 125, 94, 0.2);
            transition: 0.3s;
        }
        .btn-pay:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(46, 125, 94, 0.3); color: #fff; }
        
        .btn-back { text-decoration: none; color: #888; font-weight: 600; display: block; text-align: center; margin-top: 20px; transition: 0.2s; }
        .btn-back:hover { color: var(--brand-green); }

        /* Alert */
        .alert-soft { border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <h2 class="text-center mb-5">Xác Nhận Thanh Toán</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-soft d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div class="ms-2"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" id="checkout-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row g-5">
                <!-- Cột Trái: Phương thức thanh toán -->
                <div class="col-lg-7">
                    <h4 class="mb-4">Chọn phương thức thanh toán</h4>
                    
                    <!-- Option 1: COD -->
                    <div class="payment-option active" id="opt-cod">
                        <input type="radio" name="payment_method" id="cod" value="cod" checked>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-money-bill-wave payment-icon"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Thanh toán khi nhận hàng (COD)</h6>
                                <small class="text-muted">Thanh toán tiền mặt cho shipper</small>
                            </div>
                        </div>
                    </div>
                    <div id="cod_details" class="payment-details show">
                        <p class="mb-0"><i class="fas fa-info-circle me-2 text-success"></i>Bạn chỉ cần thanh toán khi nhân viên giao hàng đến tận nơi.</p>
                    </div>

                    <!-- Option 2: Bank Transfer -->
                    <div class="payment-option" id="opt-bank">
                        <input type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-university payment-icon"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Chuyển khoản ngân hàng</h6>
                                <small class="text-muted">Quét mã VietQR tự động</small>
                            </div>
                        </div>
                    </div>
                    <div id="bank_transfer_details" class="payment-details">
                        <p class="mb-0 text-muted">
                            <i class="fas fa-qrcode me-2 text-primary"></i>
                            Hệ thống sẽ tạo mã QR tự động ở bước tiếp theo. Bạn chỉ cần quét mã bằng App ngân hàng.
                        </p>
                    </div>

                    <!-- Option 3: E-Wallet -->
                    <div class="payment-option" id="opt-wallet">
                        <input type="radio" name="payment_method" id="digital_wallet" value="digital_wallet">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-qrcode payment-icon"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Ví điện tử</h6>
                                <small class="text-muted">MoMo, ZaloPay</small>
                            </div>
                        </div>
                    </div>
                    <div id="digital_wallet_details" class="payment-details">
                        <p class="mb-0 text-muted">
                            <i class="fas fa-mobile-alt me-2 text-danger"></i>
                            Sử dụng App MoMo hoặc ZaloPay để quét mã thanh toán ở bước tiếp theo.
                        </p>
                    </div>

                </div>

                <!-- Cột Phải: Tóm tắt đơn hàng -->
                <div class="col-lg-5">
                    <div class="summary-card">
                        <h4 class="mb-4">Đơn hàng của bạn</h4>
                        
                        <div class="cart-list mb-4">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item-row">
                                    <img src="images/<?php echo htmlspecialchars($item['image']); ?>" class="cart-img-sm" alt="Product">
                                    <div class="flex-grow-1">
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="item-qty">x <?php echo $item['quantity']; ?></div>
                                    </div>
                                    <div class="item-price"><?php echo number_format($item['subtotal']); ?> đ</div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tạm tính</span>
                            <span class="fw-bold"><?php echo number_format($total); ?> VNĐ</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-muted">Phí vận chuyển</span>
                            <span class="text-success fw-bold">Miễn phí</span>
                        </div>

                        <div class="d-flex justify-content-between border-top pt-3 mb-4">
                            <span class="h5 fw-bold">Tổng cộng</span>
                            <span class="h4 fw-bold text-danger"><?php echo number_format($total); ?> VNĐ</span>
                        </div>

                        <button type="submit" class="btn btn-pay" id="confirm-btn">
                            <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                            <span>Hoàn tất đơn hàng</span>
                        </button>

                        <a href="cart.php" class="btn-back">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại giỏ hàng
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Xử lý UI khi chọn phương thức thanh toán
        const options = document.querySelectorAll('.payment-option');
        const details = document.querySelectorAll('.payment-details');

        options.forEach(opt => {
            opt.addEventListener('click', function() {
                // Reset active state
                options.forEach(o => o.classList.remove('active'));
                details.forEach(d => d.classList.remove('show'));
                
                // Set active current
                this.classList.add('active');
                
                // Show detail
                const input = this.querySelector('input[type="radio"]');
                input.checked = true;
                const detailId = input.id + '_details';
                const detailEl = document.getElementById(detailId);
                if(detailEl) detailEl.classList.add('show');
            });
        });

        // Hiệu ứng loading khi submit
        document.getElementById('checkout-form').addEventListener('submit', function() {
            const btn = document.getElementById('confirm-btn');
            const spinner = btn.querySelector('.spinner-border');
            const text = btn.querySelector('span:last-child');
            
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
            spinner.classList.remove('d-none');
            text.textContent = 'Đang xử lý...';
        });
    </script>
</body>
</html>