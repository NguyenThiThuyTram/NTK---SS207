<?php
require_once __DIR__ . '/../config/database.php';
$id = $_GET['id'] ?? '';

if (!empty($id)) {
    // Tạm thời tắt kiểm tra khóa ngoại
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    $stmt = $conn->prepare("DELETE FROM coupons WHERE coupon_id = ?");
    $stmt->execute([$id]);
    
    // Bật lại kiểm tra khóa ngoại sau khi xóa xong
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
}

header("Location: coupons.php?msg=deleted");
exit;