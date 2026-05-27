<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Quyền truy cập bị từ chối.']);
    exit;
}

$uid = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
if (!empty($uid)) {
    try {
        $stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = :uid AND receiver_id = '0' AND is_read = 0");
        $stmt->execute(['uid' => $uid]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Mã khách hàng không hợp lệ.']);
}
?>
