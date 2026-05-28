<?php
/**
 * run_migration.php - Chạy migration DB từ command line
 */
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'ntk';

$is_local = in_array($host, ['localhost', '127.0.0.1', '::1']);
$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
];
if (!$is_local) {
    $pdo_options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    $pdo_options[PDO::MYSQL_ATTR_SSL_CA]                 = false;
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, $pdo_options);
} catch (PDOException $e) {
    echo "Loi ket noi: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$sqls = [
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancel_reason VARCHAR(500) DEFAULT NULL" => "cancel_reason",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancel_requested_at DATETIME DEFAULT NULL" => "cancel_requested_at",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS return_reason VARCHAR(500) DEFAULT NULL" => "return_reason",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS return_image VARCHAR(500) DEFAULT NULL" => "return_image",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS return_requested_at DATETIME DEFAULT NULL" => "return_requested_at",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_failed_at DATETIME DEFAULT NULL" => "delivery_failed_at",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS admin_note VARCHAR(500) DEFAULT NULL" => "orders.admin_note",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(100) DEFAULT NULL" => "orders.tracking_number",
    "ALTER TABLE order_returns ADD COLUMN IF NOT EXISTS admin_note VARCHAR(500) DEFAULT NULL" => "order_returns.admin_note",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS parent_id INT(11) DEFAULT NULL" => "reviews.parent_id",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS video VARCHAR(255) DEFAULT NULL" => "reviews.video",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS tier_discount_value DECIMAL(12,2) DEFAULT 0 AFTER discount_value" => "orders.tier_discount_value",
    "CREATE TABLE IF NOT EXISTS notifications (
        noti_id INT(11) NOT NULL AUTO_INCREMENT,
        user_id CHAR(5) DEFAULT NULL,
        type VARCHAR(50) DEFAULT 'system',
        title VARCHAR(200) NOT NULL,
        message VARCHAR(500) NOT NULL,
        related_order_id CHAR(5) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (noti_id),
        KEY idx_noti_user (user_id),
        KEY idx_noti_order (related_order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci" => "CREATE TABLE notifications",
    "CREATE TABLE IF NOT EXISTS review_likes (
        like_id INT(11) NOT NULL AUTO_INCREMENT,
        review_id VARCHAR(11) NOT NULL,
        user_id CHAR(5) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (like_id),
        UNIQUE KEY unique_user_review_like (user_id, review_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci" => "CREATE TABLE review_likes",
    "CREATE TABLE IF NOT EXISTS recent_views (
        product_id CHAR(5) NOT NULL,
        viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci" => "CREATE TABLE recent_views"
];

foreach ($sqls as $sql => $desc) {
    try {
        $conn->exec($sql);
        echo "[OK] " . $desc . PHP_EOL;
    } catch (PDOException $e) {
        echo "[SKIP/ERR] " . $desc . " => " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "=== Migration hoan thanh ===" . PHP_EOL;
?>
