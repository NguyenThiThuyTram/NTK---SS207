<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Exclude login.php to prevent infinite redirect loops
if (basename($_SERVER['PHP_SELF']) === 'login.php') {
    return;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 0) != 1 || !isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}
?>
