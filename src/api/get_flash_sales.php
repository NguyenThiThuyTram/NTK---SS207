<?php
/**
 * API: Lấy danh sách sản phẩm Flash Sale hôm nay (JSON)
 * Dùng cho real-time update trang khuyến mãi khi admin thêm flash sale
 */
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "SELECT p.product_id, p.name, p.image, p.sold_count,
                   v.original_price, fs.flash_sale_price as sale_price
            FROM products p 
            JOIN product_variants v ON p.product_id = v.product_id 
            JOIN flash_sales fs ON v.variant_id = fs.variant_id
            WHERE fs.status = 1 AND fs.sale_date = CURRENT_DATE() AND p.status = 1
            GROUP BY p.product_id 
            ORDER BY (v.original_price - fs.flash_sale_price) DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($products),
        'products' => $products
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống',
        'products' => []
    ]);
}
