<?php
session_start();
include 'includes/config.php';

// --- LOGIC PHP GIỮ NGUYÊN ---
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $conn->prepare("SELECT id, name, price, description, image, stock FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($product = $result->fetch_assoc()) {
        $product_id = $product['id'];
        $quantity = $_SESSION['cart'][$product_id] ?? 0;
        if ($quantity > 0) {
            $subtotal = $product['price'] * $quantity;
            if ($quantity > $product['stock']) {
                $_SESSION['error'] = 'Số lượng ' . htmlspecialchars($product['name']) . ' vượt tồn kho (' . $product['stock'] . ')!';
            }
            $cart_items[] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'description' => $product['description'],
                'image' => $product['image'],
                'stock' => $product['stock'],
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
            $total += $subtotal;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - NeelMilk Premium</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts: Playfair Display (Luxury) & Quicksand (Soft) -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --brand-green: #2e7d5e;      /* Màu xanh thương hiệu */
            --brand-gold: #c5a059;       /* Màu vàng sang trọng */
            --soft-bg: #f9fcfb;          /* Nền xanh kem nhạt */
            --text-dark: #2d2d2d;
        }

        body {
            background-color: var(--soft-bg);
            font-family: "Quicksand", sans-serif;
            color: var(--text-dark);
        }

        h2 { font-family: "Playfair Display", serif; font-weight: 700; color: var(--brand-green); }

        /* Card Style cho Table */
        .cart-container {
            background: #fff;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            padding: 30px;
            border: none;
        }

        /* Table Customization */
        .table-custom {
            border-collapse: separate;
            border-spacing: 0 15px; /* Khoảng cách giữa các dòng */
        }
        .table-custom thead th {
            border: none;
            font-family: "Quicksand", sans-serif;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: #888;
            font-weight: 700;
            padding-bottom: 15px;
        }
        .table-custom tbody tr {
            background-color: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }
        .table-custom tbody tr:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }
        .table-custom td {
            border: none;
            vertical-align: middle;
            padding: 20px;
            background: #fff;
        }
        /* Bo tròn dòng đầu và cuối của tr */
        .table-custom td:first-child { border-top-left-radius: 15px; border-bottom-left-radius: 15px; }
        .table-custom td:last-child { border-top-right-radius: 15px; border-bottom-right-radius: 15px; }

        /* Product Details */
        .cart-image {
            width: 70px; height: 70px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .product-name {
            font-family: "Playfair Display", serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 0;
        }
        .product-desc {
            font-size: 0.85rem;
            color: #999;
            max-width: 200px;
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
        }

        /* Quantity Input - Pill Shape */
        .quantity-input {
            width: 70px;
            text-align: center;
            border-radius: 50px;
            border: 1px solid #eee;
            padding: 5px;
            font-weight: 600;
            color: var(--brand-green);
        }
        .quantity-input:focus {
            border-color: var(--brand-green);
            box-shadow: 0 0 0 3px rgba(46, 125, 94, 0.1);
        }

        /* Summary Card (Bên phải) */
        .summary-card {
            background: #fff;
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            position: sticky;
            top: 100px;
        }
        .summary-title {
            font-family: "Playfair Display", serif;
            font-size: 1.5rem;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .summary-row {
            display: flex; justify-content: space-between;
            margin-bottom: 15px; font-size: 0.95rem; color: #666;
        }
        .summary-total {
            display: flex; justify-content: space-between;
            margin-top: 20px; pt-3;
            border-top: 2px dashed #eee;
            font-size: 1.3rem; font-weight: 700; color: var(--brand-green);
            font-family: "Playfair Display", serif;
        }

        /* Buttons */
        .btn-remove {
            color: #ccc; background: transparent; border: none; font-size: 1.2rem; transition: 0.3s;
        }
        .btn-remove:hover { color: #ff6b6b; transform: scale(1.1); background: transparent !important; }

        .btn-checkout {
            background: linear-gradient(135deg, var(--brand-green), #43a07a);
            color: #fff; border: none;
            border-radius: 50px; padding: 15px;
            width: 100%; font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 10px 20px rgba(46, 125, 94, 0.2);
            transition: 0.3s;
        }
        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(46, 125, 94, 0.3);
            color: #fff;
        }

        .btn-continue {
            color: #888; text-decoration: none; font-weight: 600; font-size: 0.9rem;
            transition: 0.2s; display: inline-block; margin-top: 20px; text-align: center; width: 100%;
        }
        .btn-continue:hover { color: var(--brand-green); }

        /* Warning Text */
        .stock-warning { color: #ff9800; font-weight: 700; font-size: 0.8rem; display: block; margin-top: 5px; }

        /* Empty Cart */
        .empty-cart-box {
            text-align: center; padding: 60px; background: #fff; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }
        .empty-icon { font-size: 4rem; color: #eee; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5 mb-5">
        
        <?php if (isset($_SESSION['error'])) : ?>
            <div class="alert alert-warning rounded-pill px-4 shadow-sm border-0 mb-4 d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)) : ?>
            <div class="empty-cart-box">
                <i class="fas fa-shopping-basket empty-icon"></i>
                <h3 style="font-family: 'Playfair Display'">Giỏ hàng của bạn đang trống</h3>
                <p class="text-muted mb-4">Hãy thêm những sản phẩm sữa tươi ngon nhất vào đây nhé.</p>
                <a href="index.php" class="btn btn-checkout" style="max-width: 250px;">
                    Tiếp tục mua sắm
                </a>
            </div>
        <?php else : ?>
            
            <div class="row g-5">
                <!-- Cột Trái: Danh sách sản phẩm -->
                <div class="col-lg-8">
                    <h2 class="mb-4">Giỏ hàng của bạn <span class="fs-5 text-muted ms-2 fw-light">(<?php echo count($cart_items); ?> sản phẩm)</span></h2>
                    
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 45%">Sản phẩm</th>
                                    <th scope="col" style="width: 15%">Giá</th>
                                    <th scope="col" style="width: 15%">Số lượng</th>
                                    <th scope="col" style="width: 15%">Thành tiền</th>
                                    <th scope="col" style="width: 10%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item) : ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="images/<?php echo htmlspecialchars($item['image']); ?>" class="cart-image me-3" alt="Product">
                                                <div>
                                                    <p class="product-name"><?php echo htmlspecialchars($item['name']); ?></p>
                                                    <p class="product-desc mb-0"><?php echo htmlspecialchars($item['description'] ?? 'Sữa tươi thanh trùng'); ?></p>
                                                    <?php if ($item['quantity'] > $item['stock']) : ?>
                                                        <span class="stock-warning"><i class="fas fa-exclamation-triangle me-1"></i>Hết hàng</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-muted"><?php echo number_format($item['price']); ?></td>
                                        <td>
                                            <!-- Giữ nguyên hàm onchange để logic JS hoạt động -->
                                            <input type="number" class="form-control quantity-input" 
                                                   min="1" max="<?php echo $item['stock']; ?>" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   onchange="updateCart(<?php echo $item['id']; ?>, this.value)">
                                            <small class="d-block text-muted mt-1" style="font-size: 0.7rem">Kho: <?php echo $item['stock']; ?></small>
                                        </td>
                                        <td class="fw-bold" style="color: var(--brand-green);">
                                            <?php echo number_format($item['subtotal']); ?>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn-remove" onclick="removeFromCart(<?php echo $item['id']; ?>)" title="Xóa sản phẩm">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cột Phải: Tổng kết đơn hàng -->
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h4 class="summary-title">Tổng đơn hàng</h4>
                        
                        <div class="summary-row">
                            <span>Tạm tính:</span>
                            <span class="fw-bold"><?php echo number_format($total); ?> VNĐ</span>
                        </div>
                        <div class="summary-row">
                            <span>Phí vận chuyển:</span>
                            <span class="text-success">Miễn phí</span>
                        </div>
                        <div class="summary-row">
                            <span>Thuế (VAT):</span>
                            <span>Đã bao gồm</span>
                        </div>

                        <div class="summary-total">
                            <span>Tổng cộng:</span>
                            <span><?php echo number_format($total); ?> VNĐ</span>
                        </div>

                        <a href="checkout.php" class="btn btn-checkout mt-4">
                            Thanh Toán Ngay <i class="fas fa-arrow-right ms-2"></i>
                        </a>

                        <a href="index.php" class="btn-continue">
                            <i class="fas fa-arrow-left me-1"></i> Tiếp tục mua sắm
                        </a>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>