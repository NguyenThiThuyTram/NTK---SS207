<?php
require_once '../src/config/database.php';

echo "=== COUPONS SCHEMA ===\n";
try {
    $stmt = $conn->query("DESCRIBE coupons");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
