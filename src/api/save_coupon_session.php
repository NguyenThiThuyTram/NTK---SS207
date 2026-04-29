<?php
/**
 * API: Lưu coupon đã áp dụng vào session
 * POST params:
 *   code            - mã coupon
 *   discount_amount - số tiền giảm
 *   coupon_id       - ID coupon
 * Gọi khi user áp dụng mã thành công ở giỏ hàng
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $code            = strtoupper(trim($_POST['code'] ?? ''));
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    $coupon_id       = trim($_POST['coupon_id'] ?? '');

    if ($code && $discount_amount > 0) {
        $_SESSION['cart_coupon'] = [
            'code'            => $code,
            'discount_amount' => $discount_amount,
            'coupon_id'       => $coupon_id,
        ];
        echo json_encode(['success' => true]);
    } else {
        // Xóa coupon session
        unset($_SESSION['cart_coupon']);
        echo json_encode(['success' => true, 'cleared' => true]);
    }
} elseif ($method === 'GET' && isset($_GET['clear'])) {
    unset($_SESSION['cart_coupon']);
    echo json_encode(['success' => true, 'cleared' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
