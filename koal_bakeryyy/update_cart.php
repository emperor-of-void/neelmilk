<?php
session_start();
include 'includes/config.php';

header('Content-Type: application/json');

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$remove = isset($_POST['remove']) && $_POST['remove'] == '1';

if (!$product_id || !isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
    exit;
}

// Lấy info sản phẩm từ DB
$stmt = $conn->prepare("SELECT name, price, stock FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại!']);
    exit;
}

if ($remove) {
    // Xóa khỏi cart
    unset($_SESSION['cart'][$product_id]);
    echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công!']);
    exit;
}

// Cập nhật quantity
if ($quantity > $product['stock']) {
    echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho (' . $product['stock'] . ')!']);
    exit;
}

$_SESSION['cart'][$product_id] = $quantity;

echo json_encode([
    'success' => true, 
    'message' => 'Cập nhật số lượng thành công! Tạm tính: ' . number_format($product['price'] * $quantity) . ' VNĐ',
    'subtotal' => $product['price'] * $quantity
]);
exit;
?>