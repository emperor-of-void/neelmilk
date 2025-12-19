<?php
// Thông tin kết nối
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "neelmilk";

// Kết nối MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ĐÚNG CHỮ HOA - CHỮ THƯỜNG 100%
$projectFolder = "KOAL_BAKERYYY";

define("BASE_URL", "/" . $projectFolder . "/");
?>
