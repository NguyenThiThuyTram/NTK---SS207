<?php
require_once 'src/config/database.php';
try {
    $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS freeship_coupon_id CHAR(5) DEFAULT NULL");
    echo "[OK] Added freeship_coupon_id to orders table\n";
} catch (PDOException $e) {
    echo "[ERR] " . $e->getMessage() . "\n";
}

try {
    $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS freeship_discount_value DECIMAL(15,2) DEFAULT 0.00");
    echo "[OK] Added freeship_discount_value to orders table\n";
} catch (PDOException $e) {
    echo "[ERR] " . $e->getMessage() . "\n";
}
