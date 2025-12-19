<?php
session_start();
include 'includes/config.php';

$product_id = 0;
$qty = 1;

// Nhận dữ liệu từ POST (từ trang chi tiết)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);
    header('Content-Type: application/json');
} 
// Nhận từ GET (từ nút thêm giỏ ở danh sách)
else {
    $product_id = filter_var($_GET['product_id'] ?? 0, FILTER_VALIDATE_INT);
    $qty = filter_var($_GET['qty'] ?? 1, FILTER_VALIDATE_INT);
}

// Validate qty
if ($qty < 1) $qty = 1;

if (!$product_id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ!']);
    } else {
        $_SESSION['error'] = 'ID sản phẩm không hợp lệ!';
        header('Location: index.php');
    }
    exit;
}

// Kiểm tra kho
$stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stock = $stmt->get_result()->fetch_assoc()['stock'] ?? 0;
$stmt->close();

if ($stock < $qty) {
    echo json_encode(['success' => false, 'message' => 'Không đủ hàng trong kho!']);
    exit;
}

// Khởi tạo giỏ
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Cộng số lượng đúng
$_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $qty;

// Response cho AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm vào giỏ!',
        'cart_count' => array_sum($_SESSION['cart'])
    ]);
} else {
    $_SESSION['success'] = 'Thêm vào giỏ thành công!';
    header('Location: cart.php');
}
exit;
?>
