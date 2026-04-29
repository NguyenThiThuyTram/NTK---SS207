<?php
require_once 'config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===================== ĐẾM GIỎ HÀNG (cho phép cả lúc chưa đăng nhập) =====================
if ($action === 'get_count') {
    $count = 0;
    try {
        if ($user_id) {
            $st = $conn->prepare("SELECT SUM(quantity) FROM Cart WHERE user_id = :uid");
            $st->execute(['uid' => $user_id]);
            $count = intval($st->fetchColumn());
        } else {
            // Đếm theo session_id nếu chưa đăng nhập
            $st = $conn->prepare("SELECT SUM(quantity) FROM Cart WHERE session_id = :sid AND user_id IS NULL");
            $st->execute(['sid' => session_id()]);
            $count = intval($st->fetchColumn());
        }
    } catch (PDOException $e) {
        $count = 0;
    }
    echo $count;
    exit;
}

// ===================== THÊM VÀO GIỎ HÀNG (Sửa lại logic phân nhánh) =====================
if ($action === 'add_to_cart') {
    // 1. Nếu muốn ép đăng nhập mới cho thêm vào giỏ, hãy bật đoạn này:
    if (!$user_id) {
        echo 'not_logged_in';
        exit;
    }

    $variant_id = trim($_POST['variant_id'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    if ($quantity < 1)
        $quantity = 1;

    // Kiểm tra biến thể & tồn kho
    $st = $conn->prepare("SELECT variant_id, stock FROM Product_Variants WHERE variant_id = :vid AND is_active = 1");
    $st->execute(['vid' => $variant_id]);
    $variant = $st->fetch(PDO::FETCH_ASSOC);

    if (!$variant) {
        echo 'not_found';
        exit;
    }
    if ($variant['stock'] < 1) {
        echo 'out_of_stock';
        exit;
    }
    if ($quantity > $variant['stock']) {
        $quantity = $variant['stock'];
    }

    // Kiểm tra sản phẩm đã có trong giỏ của người này chưa
    $st2 = $conn->prepare("SELECT cart_id, quantity FROM Cart WHERE user_id = :uid AND variant_id = :vid");
    $st2->execute(['uid' => $user_id, 'vid' => $variant_id]);
    $existing = $st2->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Đã có → cộng dồn số lượng
        $new_qty = min($existing['quantity'] + $quantity, $variant['stock']);
        $upd = $conn->prepare("UPDATE Cart SET quantity = :qty WHERE cart_id = :cid");
        $upd->execute(['qty' => $new_qty, 'cid' => $existing['cart_id']]);
        echo 'updated';
    } else {
        // Chưa có → Sinh ID mới (Tự động tạo mã C0001, C0002...)
        // Lấy số lớn nhất hiện tại
        $stMax = $conn->prepare("SELECT MAX(CAST(SUBSTRING(cart_id, 2) AS UNSIGNED)) FROM Cart");
        $stMax->execute();
        $maxNum = intval($stMax->fetchColumn()) + 1;
        $cart_id = 'C' . str_pad($maxNum, 4, '0', STR_PAD_LEFT);

        // Thêm mới
        $ins = $conn->prepare("INSERT INTO Cart (cart_id, user_id, variant_id, quantity, is_selected) VALUES (:cid, :uid, :vid, :qty, 1)");
        $ins->execute([
            'cid' => $cart_id,
            'uid' => $user_id,
            'vid' => $variant_id,
            'qty' => $quantity
        ]);
        echo 'success';
    }
    exit;
}

// Bắt buộc đăng nhập cho các thao tác phía dưới (Cập nhật, Xóa, Dùng mã)
if (!$user_id) {
    echo 'not_logged_in';
    exit;
}

// ===================== CẬP NHẬT SỐ LƯỢNG =====================
if ($action === 'update_qty') {
    $cart_id = $_POST['cart_id'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($quantity < 1)
        $quantity = 1;

    $sql = "SELECT v.stock FROM Cart c JOIN Product_Variants v ON c.variant_id = v.variant_id
            WHERE c.cart_id = :cid AND c.user_id = :uid";
    $st = $conn->prepare($sql);
    $st->execute(['cid' => $cart_id, 'uid' => $user_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo 'not_found';
        exit;
    }
    if ($quantity > $row['stock']) {
        $quantity = $row['stock'];
    }

    $upd = $conn->prepare("UPDATE Cart SET quantity = :qty WHERE cart_id = :cid AND user_id = :uid");
    $upd->execute(['qty' => $quantity, 'cid' => $cart_id, 'uid' => $user_id]);
    echo 'success';
    exit;
}

// ===================== XÓA SẢN PHẨM =====================
if ($action === 'remove') {
    $cart_id = $_POST['cart_id'] ?? '';
    $del = $conn->prepare("DELETE FROM Cart WHERE cart_id = :cid AND user_id = :uid");
    $del->execute(['cid' => $cart_id, 'uid' => $user_id]);
    echo 'success';
    exit;
}

// ===================== CẬP NHẬT TRẠNG THÁI CHỌN (Checkbox) =====================
if ($action === 'select') {
    $cart_id = $_POST['cart_id'] ?? '';
    $is_selected = intval($_POST['is_selected'] ?? 0);

    $upd = $conn->prepare("UPDATE Cart SET is_selected = :sel WHERE cart_id = :cid AND user_id = :uid");
    $upd->execute(['sel' => $is_selected, 'cid' => $cart_id, 'uid' => $user_id]);
    echo 'success';
    exit;
}

// ===================== ÁP DỤNG MÃ GIẢM GIÁ =====================
if ($action === 'apply_coupon') {
    header('Content-Type: application/json');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $now = date('Y-m-d H:i:s');

    $sql = "SELECT * FROM coupons
            WHERE code = :code
              AND status = 1
              AND (start_date IS NULL OR start_date <= :now1)
              AND (end_date IS NULL OR end_date >= :now2)
              AND (quantity IS NULL OR used_count < quantity)";
    $st = $conn->prepare($sql);
    $st->execute(['code' => $code, 'now1' => $now, 'now2' => $now]);
    $coupon = $st->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        echo json_encode(['status' => 'error', 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn!']);
        exit;
    }

    // Tính tổng tiền các sản phẩm ĐÃ CHỌN trong giỏ
    $sql2 = "SELECT SUM(c.quantity * CASE WHEN v.sale_price > 0 THEN v.sale_price ELSE v.original_price END
                    ) AS subtotal
             FROM Cart c
             JOIN Product_Variants v ON c.variant_id = v.variant_id
             WHERE c.user_id = :uid AND c.is_selected = 1";
    $st2 = $conn->prepare($sql2);
    $st2->execute(['uid' => $user_id]);
    $row2 = $st2->fetch(PDO::FETCH_ASSOC);
    $subtotal = floatval($row2['subtotal'] ?? 0);

    if ($subtotal < $coupon['min_order_value']) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Đơn hàng tối thiểu ' . number_format($coupon['min_order_value'], 0, ',', '.') . 'đ để áp dụng mã này!'
        ]);
        exit;
    }

    $discount = 0;
    if ($coupon['discount_type'] == 0) {
        $discount = $subtotal * ($coupon['discount_value'] / 100);
        if ($coupon['max_discount_amount'] && $discount > $coupon['max_discount_amount']) {
            $discount = $coupon['max_discount_amount'];
        }
    } else {
        $discount = $coupon['discount_value'];
    }

    echo json_encode([
        'status' => 'success',
        'coupon_id' => $coupon['coupon_id'],
        'discount' => $discount,
        'message' => 'Áp dụng mã giảm giá thành công!'
    ]);
    exit;
}

echo 'invalid_action';
?>