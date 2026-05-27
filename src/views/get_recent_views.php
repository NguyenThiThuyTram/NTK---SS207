<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';

try {
    // Fetch top 8 recently viewed active products
    $sql = "SELECT p.product_id, p.name, p.image, p.sold_count, v.original_price, v.sale_price
            FROM recent_views rv
            JOIN products p ON rv.product_id = p.product_id
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            WHERE p.status = 1
            GROUP BY p.product_id
            ORDER BY rv.viewed_at DESC
            LIMIT 8";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $results
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
