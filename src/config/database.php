<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "NTK"; // Tên database chuẩn theo file sql của bạn

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Kết nối Database thất bại: " . $e->getMessage());
}
?>
