<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

header("Location: https://admin.ntkfashion.me/login.php");
exit();
?>
