<?php
require_once 'src/config/database.php';
$stmt1 = $conn->query('DESCRIBE flash_sales');
print_r($stmt1->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $conn->query('DESCRIBE shipping_methods');
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

$stmt3 = $conn->query('DESCRIBE orders');
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
