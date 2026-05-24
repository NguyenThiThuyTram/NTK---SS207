<?php
require 'src/config/database.php';
$stmt = $conn->query("SELECT order_id, order_date, order_status FROM orders WHERE order_id LIKE '%O%' ORDER BY order_id DESC LIMIT 5");
print_r($stmt->fetchAll());
?>
