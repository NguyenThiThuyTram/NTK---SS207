<?php
/**
 * API: Lấy danh sách voucher đang hoạt động (JSON)
 * Dùng cho real-time update phía user khi admin tạo voucher mới
 */
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "SELECT coupon_id, code, discount_type, discount_value,
                   min_order_value, max_discount_amount, start_date, end_date,
                   quantity, used_count, status, coupon_type
            FROM coupons
            WHERE status = 1
              AND (end_date IS NULL OR end_date >= NOW())
            ORDER BY end_date IS NULL DESC, end_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($coupons),
        'coupons' => $coupons
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống',
        'coupons' => []
    ]);
}
