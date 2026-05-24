<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 0) != 1) {
    header("Location: ../views/login.php");
    exit();
}
?>
