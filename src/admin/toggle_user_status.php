<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$user_id = $_POST['user_id'] ?? '';
$action  = $_POST['action'] ?? ''; // 'lock' or 'unlock'

if (!$user_id || !in_array($action, ['lock', 'unlock'])) {
    header('Location: accounts.php');
    exit;
}

$new_status = ($action === 'lock') ? 0 : 1;
$stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
$stmt->execute([$new_status, $user_id]);

header('Location: accounts.php?msg=' . ($action === 'lock' ? 'locked' : 'unlocked'));
exit;
