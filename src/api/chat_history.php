<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để xem lịch sử chat.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    // Lấy tin nhắn giữa user này và admin
    $stmt = $conn->prepare("
        SELECT * FROM chat_messages 
        WHERE (sender_id = :uid AND receiver_id = 0) 
           OR (receiver_id = :uid AND sender_id IN (SELECT user_id FROM users WHERE role = 1)) 
        ORDER BY id ASC
    ");
    $stmt->execute(['uid' => $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Đánh dấu tin nhắn admin gửi cho user này là đã đọc khi họ xem chat
    $upd = $conn->prepare("
        UPDATE chat_messages 
        SET is_read = 1 
        WHERE receiver_id = :uid AND sender_id IN (SELECT user_id FROM users WHERE role = 1) AND is_read = 0
    ");
    $upd->execute(['uid' => $user_id]);

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
