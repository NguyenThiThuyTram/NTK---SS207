<?php
require_once 'src/config/database.php';
$stmt = $conn->query('SELECT * FROM coupons');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $conn->query('SELECT * FROM shipping_methods');
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
