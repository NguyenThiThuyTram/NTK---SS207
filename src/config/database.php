<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

$host     = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbname   = getenv('DB_NAME') ?: 'ntk';

// 1. Phát hiện môi trường: Nếu chạy local XAMPP (localhost / 127.0.0.1)
$is_local = in_array($host, ['localhost', '127.0.0.1', '::1']);

// 2. MẸO THÔNG MINH CHO BEE: 
// Nếu chạy dưới máy local của ní, ép thông số kết nối về mặc định của XAMPP 
// để tránh lỗi "MySQL server has gone away" mà không sợ bóp team khi push code!
if ($is_local) {
    $username = 'root';
    $password = ''; 
    $dbname   = 'ntk'; // << Sửa lại đúng tên Database trong phpMyAdmin của ní (nếu có lệch)
}

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT         => false,
];

// Chỉ bật SSL khi kết nối cloud thực tế để bảo mật
if (!$is_local) {
    $pdo_options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    $pdo_options[PDO::MYSQL_ATTR_SSL_CA]                 = false;
}

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        $pdo_options
    );
    // Set MySQL timezone
    $conn->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die("Kết nối Database thất bại: " . $e->getMessage());
}
?>