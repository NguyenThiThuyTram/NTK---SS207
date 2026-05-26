<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || (int)$_SESSION['role'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin_uid = $_SESSION['user_id'];

// Nhận dữ liệu JSON từ request body
$input = json_decode(file_get_contents('php://input'), true);
$event_ids = isset($input['event_ids']) ? $input['event_ids'] : [];

if (empty($event_ids)) {
    echo json_encode(['success' => true, 'message' => 'No notifications to mark as read']);
    exit;
}

try {
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("INSERT IGNORE INTO admin_read_logs (user_id, event_id) VALUES (:uid, :eid)");
    foreach ($event_ids as $eid) {
        if (!empty($eid)) {
            $stmt->execute(['uid' => $admin_uid, 'eid' => $eid]);
        }
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
