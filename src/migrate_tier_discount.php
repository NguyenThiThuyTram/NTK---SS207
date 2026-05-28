<?php
require 'config/database.php';
try {
    // Kiểm tra xem cột đã tồn tại chưa
    $check = $conn->query("SHOW COLUMNS FROM orders LIKE 'tier_discount_value'");
    if ($check->rowCount() === 0) {
        $conn->exec('ALTER TABLE orders ADD COLUMN tier_discount_value DECIMAL(12,2) DEFAULT 0 AFTER discount_value');
        echo 'OK: Đã thêm cột tier_discount_value vào bảng orders';
    } else {
        echo 'OK: Cột tier_discount_value đã tồn tại';
    }
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
