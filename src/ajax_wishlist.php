<?php
require_once 'config/database.php';
session_start();

// Giả sử Bee đã lưu thông tin user vào session sau khi đăng nhập
$user_id = $_SESSION['user_id'] ?? null;
$product_id = $_POST['product_id'] ?? null;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập để yêu thích!']);
    exit;
}

if ($product_id) {
    // Kiểm tra xem sản phẩm đã có trong wishlist chưa
    $check_sql = "SELECT * FROM Wishlist WHERE user_id = :u AND product_id = :p";
    $stmt = $conn->prepare($check_sql);
    $stmt->execute(['u' => $user_id, 'p' => $product_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'info', 'message' => 'Sản phẩm đã có trong danh sách yêu thích!']);
    } else {
        // Tạo wishlist_id tự động (Ví dụ: W + số ngẫu nhiên)
        $wish_id = 'W' . rand(100, 999);
        $insert_sql = "INSERT INTO Wishlist (wishlist_id, user_id, product_id) VALUES (:id, :u, :p)";
        $stmt_ins = $conn->prepare($insert_sql);
        $stmt_ins->execute(['id' => $wish_id, 'u' => $user_id, 'p' => $product_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Đã thêm vào yêu thích!']);
    }
}
?>