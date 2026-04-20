<?php
require_once '../config/database.php';

$order_id = $_GET['id'] ?? '';

if (!$order_id) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT payment_status FROM Orders WHERE order_id = :oid");
$stmt->execute(['oid' => $order_id]);
$status = $stmt->fetchColumn();

if ($status == 1) {
    echo json_encode(['success' => true, 'paid' => true]);
} else {
    echo json_encode(['success' => true, 'paid' => false]);
}
?>
