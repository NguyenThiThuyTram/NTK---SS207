<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để chat với nhân viên.']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');
$receiver_id = isset($_POST['receiver_id']) ? trim($_POST['receiver_id']) : '0'; // '0' = Admin

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Tin nhắn rỗng']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message, created_at) VALUES (:sid, :rid, :msg, NOW())");
    $stmt->execute([
        'sid' => $sender_id,
        'rid' => $receiver_id,
        'msg' => $message
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
