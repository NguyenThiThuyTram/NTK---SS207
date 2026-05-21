<?php
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'ntk';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            // Chỉ cần giữ lại chế độ báo lỗi exception là đủ chuẩn rồi Bee
            PDO::ATTR_ERRMODE                      => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE           => PDO::FETCH_ASSOC,
            // Fix lỗi: MySQL yêu cầu SSL (require_secure_transport=ON)
            // Cho phép kết nối SSL nhưng không verify CA certificate (dùng self-signed)
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
} catch(PDOException $e) {
    die("Kết nối Database thất bại: " . $e->getMessage());
}
?>