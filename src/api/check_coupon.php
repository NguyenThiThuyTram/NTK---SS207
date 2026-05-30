<?php
/**
 * API: Kiểm tra mã giảm giá
 * GET params:
 * code        - mã coupon cần kiểm tra
 * order_total - tổng đơn hàng (subtotal + shipping) trước khi giảm
 */
session_start(); // Phải gọi session để biết ai đang đăng nhập
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$code        = strtoupper(trim($_POST['code'] ?? $_GET['code'] ?? ''));
$order_total = floatval($_POST['order_total'] ?? $_GET['order_total'] ?? 0);
$current_user_id = $_SESSION['user_id'] ?? null; // Lấy ID của khách đang thao tác trên web

if (!$code) {
    echo json_encode(['valid' => false, 'message' => 'Vui lòng nhập mã giảm giá.']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT *
        FROM coupons
        WHERE code = :code
          AND status = 1
          AND (start_date IS NULL OR start_date <= NOW())
          AND (end_date IS NULL OR end_date >= NOW())
    ");
    $stmt->execute(['code' => $code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        echo json_encode(['valid' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết hạn.']);
        exit;
    }

    // ── 🛡️ KIỂM TRA ĐẶC QUYỀN USER (ĐOẠN MỚI THÊM) ──────────────────
    // Nếu mã này có gắn user_id (mã tặng riêng), mà ID khách đang đăng nhập không khớp -> CẤM
    if (!empty($coupon['user_id']) && $coupon['user_id'] !== $current_user_id) {
        echo json_encode(['valid' => false, 'message' => 'Rất tiếc, mã giảm giá này là đặc quyền dành riêng cho khách hàng khác!']);
        exit;
    }
    // ─────────────────────────────────────────────────────────────────

    // Kiểm tra số lượng
    if ($coupon['quantity'] !== null && $coupon['used_count'] >= $coupon['quantity']) {
        echo json_encode(['valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.']);
        exit;
    }

    // Kiểm tra giá trị đơn tối thiểu (áp dụng cho giá sản phẩm - order_total ở đây đại diện cho subtotal)
    if ($order_total < floatval($coupon['min_order_value'])) {
        $min_fmt = number_format($coupon['min_order_value'], 0, ',', '.');
        echo json_encode(['valid' => false, 'message' => "Đơn hàng tối thiểu {$min_fmt} VNĐ để dùng mã này."]);
        exit;
    }

    // Tính số tiền giảm
    $discount_amount = 0;
    if ($coupon['coupon_type'] == 1) {
        // Voucher Freeship
        $shipping_fee = floatval($_POST['shipping_fee'] ?? $_GET['shipping_fee'] ?? 35000);
        if ($coupon['discount_type'] == 0) {
            // Giảm % phí ship
            $discount_amount = $shipping_fee * (floatval($coupon['discount_value']) / 100);
        } else {
            // Giảm tiền ship cố định
            $discount_amount = floatval($coupon['discount_value']);
        }
        $discount_amount = min($discount_amount, $shipping_fee);
    } else {
        // Voucher Giảm giá đơn hàng
        if ($coupon['discount_type'] == 0) {
            // Giảm theo %
            $discount_amount = $order_total * (floatval($coupon['discount_value']) / 100);
            // Áp dụng giảm tối đa (nếu có)
            if (!empty($coupon['max_discount_amount']) && $coupon['max_discount_amount'] > 0) {
                $discount_amount = min($discount_amount, floatval($coupon['max_discount_amount']));
            }
        } else {
            // Giảm số tiền cố định
            $discount_amount = floatval($coupon['discount_value']);
        }
        $discount_amount = min($discount_amount, $order_total);
    }
    
    $discount_amount = round($discount_amount);

    echo json_encode([
        'valid'               => true,
        'coupon_id'           => $coupon['coupon_id'],
        'code'                => $coupon['code'],
        'discount_amount'     => $discount_amount,
        'discount_type'       => $coupon['discount_type'],
        'discount_value'      => $coupon['discount_value'],
        'max_discount_amount' => $coupon['max_discount_amount'],
        'coupon_type'         => $coupon['coupon_type'],
        'message'             => 'Áp dụng mã thành công!'
    ]);

} catch (PDOException $e) {
    echo json_encode(['valid' => false, 'message' => 'Lỗi hệ thống, vui lòng thử lại.']);
}