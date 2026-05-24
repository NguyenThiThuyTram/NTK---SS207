<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("Location: ../index.php");
    exit;
}

$noti_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '../admin/dashboard.php';

if ($noti_id > 0) {
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE noti_id = :nid");
        $stmt->execute(['nid' => $noti_id]);
    } catch (PDOException $e) {
        // Bỏ qua lỗi nếu có
    }
}

// Redirect đến link tương ứng
header("Location: " . $redirect_url);
exit;
?>
