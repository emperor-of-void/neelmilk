<?php
session_start();
session_destroy();
$_SESSION['success'] = 'Đã đăng xuất thành công!';
header('Location: index.php');
exit;
?>