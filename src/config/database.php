<?php
$host     = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbname   = getenv('DB_NAME') ?: 'ntk';

// Phát hiện môi trường: local XAMPP không cần SSL
// Cloud/Production (PlanetScale, Railway...) mới cần SSL
$is_local = in_array($host, ['localhost', '127.0.0.1', '::1']);

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT         => false,
];

// Chỉ bật SSL khi kết nối cloud (tránh lỗi "MySQL server has gone away" trên XAMPP)
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
} catch (PDOException $e) {
    die("Kết nối Database thất bại: " . $e->getMessage());
}
?>