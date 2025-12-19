<?php
session_start();
include '../includes/config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = 'ID nhân viên không hợp lệ!';
    header('Location: manage_employees.php');
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
if ($stmt === false) {
    $_SESSION['error'] = 'Lỗi server khi xóa nhân viên!';
    error_log("Prepare failed: " . $conn->error);
    header('Location: manage_employees.php');
    exit;
}

$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    $_SESSION['success'] = 'Xóa nhân viên thành công!';
} else {
    $_SESSION['error'] = 'Lỗi khi xóa nhân viên!';
    error_log("Delete failed: " . $stmt->error);
}
$stmt->close();

header('Location: manage_employees.php');
exit;
?>