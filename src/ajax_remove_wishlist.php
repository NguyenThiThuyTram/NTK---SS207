<?php
require_once 'config/database.php';
session_start(); // CỰC KỲ QUAN TRỌNG: Phải có dòng này thì PHP mới lấy được $user_id

$user_id = $_SESSION['user_id'] ?? null;
$product_id = $_POST['product_id'] ?? null;

if ($user_id && $product_id) {
    // Thực hiện xóa trong DB
    $sql = "DELETE FROM Wishlist WHERE user_id = :u AND product_id = :p";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute(['u' => $user_id, 'p' => $product_id]);
    
    if ($result) {
        echo "success"; // Chỉ xuất duy nhất chữ này, không dấu cách, không HTML
    } else {
        echo "error_db";
    }
} else {
    echo "error_auth";
}
exit; // Ngắt kết nối luôn để không dư thừa dữ liệu rác