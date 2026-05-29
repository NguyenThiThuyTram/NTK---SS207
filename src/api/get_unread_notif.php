<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread' => 0]);
    exit;
}

try {
    $stmt_unread = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = :user_id AND is_read = 0");
    $stmt_unread->execute(['user_id' => $_SESSION['user_id']]);
    $unread_noti_count = $stmt_unread->fetchColumn() ?: 0;
    echo json_encode(['unread' => intval($unread_noti_count)]);
} catch (\Throwable $e) {
    echo json_encode(['unread' => 0]);
}
?>
