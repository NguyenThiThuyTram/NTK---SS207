<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || (int)$_SESSION['role'] !== 1) {
    header("Location: ../index.php");
    exit;
}

$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : '';
$redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '../admin/dashboard.php';
$admin_uid = $_SESSION['user_id'];

if (!empty($event_id)) {
    try {
        $stmt = $conn->prepare("INSERT IGNORE INTO admin_read_logs (user_id, event_id) VALUES (:uid, :eid)");
        $stmt->execute(['uid' => $admin_uid, 'eid' => $event_id]);
    } catch (PDOException $e) {
        // Bỏ qua lỗi nếu có
    }
}

// Redirect đến link tương ứng
header("Location: " . $redirect_url);
exit;
?>
