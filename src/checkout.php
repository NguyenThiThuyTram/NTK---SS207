<?php
require_once 'config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: views/login.php?redirect=../checkout.php");
    exit;
}

// 1. Lấy sản phẩm đang ĐƯỢC CHỌN trong giỏ hàng
$sql = "SELECT c.cart_id, c.quantity, 
               v.variant_id, v.color, v.size, v.original_price, v.sale_price,
               p.product_id, p.name AS product_name, p.image
        FROM Cart c
        JOIN Product_Variants v ON c.variant_id = v.variant_id
        JOIN Products p ON v.product_id = p.product_id
        WHERE c.user_id = :uid AND c.is_selected = 1";
$stmt = $conn->prepare($sql);
$stmt->execute(['uid' => $user_id]);
$checkout_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($checkout_items) === 0) {
    echo "<script>alert('Bạn chưa chọn sản phẩm nào để thanh toán!'); window.location.href='cart.php';</script>";
    exit;
}

// Lấy thông tin user để điền sẵn vào form
$stmt_user = $conn->prepare("SELECT * FROM Users WHERE user_id = :uid");
$stmt_user->execute(['uid' => $user_id]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

// 2. Tính toán cơ bản
$subtotal = 0;
foreach ($checkout_items as $item) {
    $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['original_price'];
    $subtotal += $price * $item['quantity'];
}

$shipping_fee = 35000; // Cố định 35k như yêu cầu

// 3. LẤY SỐ DƯ VÍ THẬT TỪ DATABASE
$stmt_wallet = $conn->prepare("SELECT wallet_balance FROM Users WHERE user_id = :uid");
$stmt_wallet->execute(['uid' => $user_id]);
$user_data = $stmt_wallet->fetch(PDO::FETCH_ASSOC);

// Gán số dư ví thật (nếu không có thì mặc định là 0)
$wallet_balance = $user_data['wallet_balance'] ?? 0;

// 4. Đọc coupon đã áp dụng từ giỏ hàng (nếu có)
$cart_coupon = $_SESSION['cart_coupon'] ?? null;
// Xóa session sau khi đọc (sẽ được lưu lại khi user xác nhận trong checkout)
// Giữ session để user có thể navigate back và coupon vẫn còn

// 5. Lấy danh sách voucher còn hiệu lực để đề xuất
$available_coupons = [];
try {
    $stmt_coupons = $conn->prepare("
        SELECT coupon_id, code, discount_type, discount_value,
               min_order_value, max_discount_amount, end_date, quantity, used_count
        FROM Coupons
        WHERE status = 1
          AND (start_date IS NULL OR start_date <= NOW())
          AND (end_date IS NULL OR end_date >= NOW())
          AND (quantity IS NULL OR used_count < quantity)
        ORDER BY discount_value DESC
    ");
    $stmt_coupons->execute();
    $raw_coupons = $stmt_coupons->fetchAll(PDO::FETCH_ASSOC);

    // Tính toán discount thực tế cho từng coupon dựa trên subtotal hiện tại
    foreach ($raw_coupons as $cp) {
        // Kiểm tra điều kiện đơn tối thiểu
        if ($subtotal < floatval($cp['min_order_value'])) continue;

        if ($cp['discount_type'] == 0) {
            $calc = $subtotal * (floatval($cp['discount_value']) / 100);
            if (!empty($cp['max_discount_amount']) && $cp['max_discount_amount'] > 0) {
                $calc = min($calc, floatval($cp['max_discount_amount']));
            }
        } else {
            $calc = floatval($cp['discount_value']);
        }
        $cp['calc_discount'] = min(round($calc), $subtotal);
        $available_coupons[] = $cp;
    }

    // Sắp xếp: voucher giảm nhiều tiền nhất đầu tiên
    usort($available_coupons, fn($a, $b) => $b['calc_discount'] - $a['calc_discount']);

} catch (PDOException $e) {
    $available_coupons = [];
}

// 6. Lấy danh sách địa chỉ đã lưu của user
$saved_addresses = [];
try {
    $st_addr = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = :uid ORDER BY is_default DESC, created_at DESC");
    $st_addr->execute(['uid' => $user_id]);
    $saved_addresses = $st_addr->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $saved_addresses = [];
}

include 'includes/header.php';
?>

<style>
    
    body { background-color: #fff; }
    .checkout-page { 
        max-width: 1200px; 
        margin: 40px auto; 
        padding: 0 20px; 
        /* ĐÃ SỬA: Đổi sang font Helvetica Neue */
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; 
    }
    
    /* Layout 2 cột */
    .checkout-layout { display: flex; gap: 40px; align-items: flex-start; }
    .checkout-left { flex: 1; }
    .checkout-right { width: 420px; background-color: #faf9f5; padding: 25px; border-radius: 8px; position: sticky; top: 20px; border: 1px solid #f0eee9; }

    /* Tiêu đề & Form */
    .step-title { font-size: 16px; font-weight: bold; color: #333; text-transform: uppercase; letter-spacing: 1px;  margin-bottom: 25px; }
    .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
    .form-col { flex: 1; }
    .form-group { margin-bottom: 20px; }
    .form-group label, .form-col label { 
        display: block; 
        font-size: 13px; 
        color: #777; 
        text-transform: uppercase; 
        margin-bottom: 12px; 
        letter-spacing: 0.5px;
    }
    .form-group input, .form-group select, .form-col input { 
        width: 100%; 
        padding: 14px 15px; /* Tăng khoảng trống bên trong ô */
        border: 1px solid #e0e0e0; /* Màu viền nhạt và tinh tế hơn */
        border-radius: 2px; /* Bo góc cực nhẹ, tạo cảm giác vuông vức */
        font-size: 15px; 
        color: #333;
        outline: none; 
        box-sizing: border-box; 
        transition: border-color 0.3s ease;
    }
    .form-group input:focus, .form-col input:focus { 
        border-color: #999; /* Đổi màu viền khi click chuột vào */
    }

    /* Phương thức Ship & Thanh toán */
    .method-box { border: 1px solid #ddd; border-radius: 4px; padding: 15px 20px; margin-bottom: 15px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: 0.3s; }
    .method-box.active { border-color: #4caf50; background-color: #f1f8e9; }
    .method-box input[type="radio"] { accent-color: #4caf50; transform: scale(1.2); margin-right: 15px; }
    .method-info { flex: 1; }
    .method-name { font-weight: bold; color: #333; margin-bottom: 5px; }
    .method-desc { font-size: 13px; color: #777; }
    .method-price { font-weight: bold; color: #333; }

    /* Chi tiết chuyển khoản QR */
    .qr-payment-info { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-top: 15px; display: none; }
    .qr-payment-info.show { display: block; }
    .bank-details { background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    .bank-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 10px; color: #555; }
    .bank-row strong { color: #333; }
    .qr-img-box { text-align: center; }
    .qr-img-box img { width: 200px; height: 200px; object-fit: cover; border: 1px solid #eee; border-radius: 8px; padding: 5px; }

    /* Nút điều hướng */
    .step-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }
    .btn-back { background: #fff; border: 1px solid #ddd; padding: 12px 25px; border-radius: 4px; cursor: pointer; color: #333; font-weight: bold; transition: 0.2s; }
    .btn-back:hover { background: #f5f5f5; }
    .btn-next { background: #2f1c00; border: 1px solid #2f1c00; padding: 12px 35px; border-radius: 4px; cursor: pointer; color: #fff; font-weight: bold; transition: 0.2s; flex: 1; margin-left: 20px; text-align: center; }
    .btn-next:hover { background: #1a0f00; }

    /* Các bước ẩn/hiện */
    .checkout-step { display: none; animation: fadeIn 0.4s; }
    .checkout-step.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* Tóm tắt đơn hàng (Cột phải) */
    .summary-title { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 20px; color: #333; }
    .sum-item { display: flex; gap: 15px; margin-bottom: 20px; }
    .sum-item img { width: 65px; height: 80px; object-fit: cover; border-radius: 4px; }
    .sum-item-info { flex: 1; }
    .sum-item-name { font-size: 14px; font-weight: bold; color: #333; margin-bottom: 5px; }
    .sum-item-variant { font-size: 12px; color: #777; margin-bottom: 3px; }
    .sum-item-price { font-size: 14px; color: #333; font-weight: bold; text-align: right; }

    .summary-items {
        max-height: 300px;
        overflow-y: auto;
        padding-right: 10px;
    }
    /* Tùy chỉnh thanh cuộn cho Tóm tắt đơn hàng */
    .summary-items::-webkit-scrollbar { width: 6px; }
    .summary-items::-webkit-scrollbar-track { background: transparent; }
    .summary-items::-webkit-scrollbar-thumb { background: #bbb; border-radius: 10px; }
    .summary-items::-webkit-scrollbar-thumb:hover { background: #999; }

    .sum-wallet-box { padding: 15px 0; border-top: 1px dashed #ddd; border-bottom: 1px dashed #ddd; margin: 20px 0; }
    .wallet-title { font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 10px; }
    .wallet-balance { font-size: 14px; font-weight: bold; color: #333; margin-bottom: 10px; }
    .wallet-checkbox { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #333; cursor: pointer; }
    .wallet-checkbox input { accent-color: #2f1c00; width: 16px; height: 16px; }

    /* Coupon box */
    .coupon-box { padding: 15px 0; border-bottom: 1px dashed #ddd; margin-bottom: 15px; }
    .coupon-title { font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px; }
    .coupon-input-row { display: flex; gap: 8px; }
    .coupon-input-row input { flex: 1; padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 2px; font-size: 14px; outline: none; text-transform: uppercase; transition: border-color 0.3s; }
    .coupon-input-row input:focus { border-color: #999; }
    .coupon-input-row button { background: #2f1c00; color: #fff; border: none; padding: 10px 14px; border-radius: 2px; cursor: pointer; font-size: 13px; font-weight: bold; white-space: nowrap; transition: opacity 0.2s; }
    .coupon-input-row button:hover { opacity: 0.85; }
    .coupon-msg { font-size: 12px; margin-top: 6px; }
    .coupon-msg.success { color: #2e7d32; }
    .coupon-msg.error { color: #c0392b; }
    .coupon-applied-tag { display: flex; align-items: center; justify-content: space-between; background: #f1f8e9; border: 1px solid #c8e6c9; border-radius: 4px; padding: 6px 10px; margin-top: 8px; font-size: 13px; color: #2e7d32; }
    .coupon-applied-tag span { font-weight: bold; }
    .coupon-remove { background: none; border: none; color: #c0392b; cursor: pointer; font-size: 16px; line-height: 1; padding: 0 2px; }

    .sum-row { display: flex; justify-content: space-between; font-size: 14px; color: #555; margin-bottom: 12px; }
    .sum-total-row { display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; color: #333; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
    .sum-note { font-size: 11px; color: #999; margin-top: 5px; }
    /* Coupon Suggestions Panel */
    .coupon-suggestions { margin-bottom: 14px; }
    .coupon-suggest-title {
        font-size: 11px; color: #888; text-transform: uppercase;
        letter-spacing: 0.6px; margin-bottom: 8px;
        display: flex; align-items: center; gap: 6px;
    }
    .coupon-suggest-list { display: flex; flex-direction: column; gap: 7px; }
    .coupon-suggest-item {
        display: flex; align-items: center; justify-content: space-between;
        border: 1px dashed #c8b89a; border-radius: 6px;
        padding: 8px 10px; background: #fdfaf6; cursor: pointer;
        transition: all 0.2s; gap: 8px;
    }
    .coupon-suggest-item:hover { background: #f5ede0; border-color: #2f1c00; }
    .coupon-suggest-item.best-pick { border-color: #2f1c00; background: #f9f4ed; }
    .csi-left { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; }
    .csi-badge {
        background: #2f1c00; color: #fff; font-size: 10px; font-weight: 700;
        padding: 2px 6px; border-radius: 4px; white-space: nowrap; flex-shrink: 0;
    }
    .csi-badge.best { background: #b7860b; }
    .csi-info { min-width: 0; }
    .csi-code { font-size: 12px; font-weight: 700; color: #2f1c00; font-family: monospace; }
    .csi-desc { font-size: 11px; color: #888; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .csi-discount { font-size: 12px; font-weight: 700; color: #2e7d32; white-space: nowrap; flex-shrink: 0; }
    .csi-detail-btn {
        font-size: 10px; color: #888; background: none; border: none;
        cursor: pointer; padding: 2px 4px; text-decoration: underline; flex-shrink: 0;
        transition: color 0.15s;
    }
    .csi-detail-btn:hover { color: #2f1c00; }
    .coupon-suggest-more {
        font-size: 11.5px; color: #888; text-align: center; margin-top: 6px;
        cursor: pointer; transition: color 0.2s;
    }
    .coupon-suggest-more:hover { color: #2f1c00; }

    /* Modal chi tiết voucher */
    .voucher-modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.45);
        z-index: 9999; display: flex; align-items: center; justify-content: center;
        opacity: 0; pointer-events: none; transition: opacity 0.25s;
    }
    .voucher-modal-overlay.open { opacity: 1; pointer-events: auto; }
    .voucher-modal {
        background: #fff; border-radius: 12px; width: 380px; max-width: 95vw;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2); transform: scale(0.92);
        transition: transform 0.25s; overflow: hidden;
    }
    .voucher-modal-overlay.open .voucher-modal { transform: scale(1); }
    .vm-header {
        background: #2f1c00; color: #fff; padding: 18px 20px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .vm-header h3 { margin: 0; font-size: 16px; font-weight: 700; }
    .vm-close { background: none; border: none; color: rgba(255,255,255,0.7); font-size: 20px; cursor: pointer; line-height: 1; }
    .vm-close:hover { color: #fff; }
    .vm-body { padding: 20px; }
    .vm-big-discount {
        text-align: center; padding: 16px; background: #faf6f0;
        border-radius: 8px; margin-bottom: 16px;
    }
    .vm-discount-val { font-size: 28px; font-weight: 800; color: #2f1c00; }
    .vm-discount-type { font-size: 12px; color: #888; margin-top: 2px; }
    .vm-code-box {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        background: #f5f1eb; border: 1.5px dashed #c0a878;
        border-radius: 6px; padding: 10px 14px; margin-bottom: 16px;
    }
    .vm-code { font-family: monospace; font-size: 18px; font-weight: 800; color: #2f1c00; letter-spacing: 2px; }
    .vm-copy-btn {
        background: #2f1c00; color: #fff; border: none; padding: 5px 12px;
        border-radius: 4px; font-size: 12px; cursor: pointer; white-space: nowrap;
        transition: opacity 0.2s;
    }
    .vm-copy-btn:hover { opacity: 0.85; }
    .vm-detail-list { list-style: none; padding: 0; margin: 0; }
    .vm-detail-list li {
        display: flex; justify-content: space-between; align-items: flex-start;
        padding: 9px 0; border-bottom: 1px solid #f0ede8; font-size: 13.5px;
    }
    .vm-detail-list li:last-child { border-bottom: none; }
    .vm-detail-list .vdl-label { color: #888; }
    .vm-detail-list .vdl-val { font-weight: 600; color: #333; text-align: right; max-width: 55%; }
    .vm-apply-btn {
        width: 100%; background: #2f1c00; color: #fff; border: none;
        padding: 13px; border-radius: 6px; font-size: 14px; font-weight: 700;
        cursor: pointer; margin-top: 14px; transition: opacity 0.2s;
    }
    .vm-apply-btn:hover { opacity: 0.87; }

    /* Thanh Tiến Trình (Stepper) */
    .checkout-stepper { 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        margin-bottom: 40px; 
        padding-bottom: 20px;
        border-bottom: 1px solid #f0eee9;
    }
    .step-indicator { 
        font-size: 13px; 
        color: #b5b5b5; /* Màu xám nhạt cho bước chưa tới */
        text-transform: uppercase; 
        letter-spacing: 1px; 
        transition: 0.3s;
        font-weight: 500;
    }
    .step-indicator.active { 
        color: #333; /* Màu đậm cho bước hiện tại */
        font-weight: bold; 
    }
    .step-line { 
        height: 1px; 
        background-color: #e0e0e0; 
        width: 60px; 
        margin: 0 20px; 
    }
</style>

<div class="checkout-page">
    <div class="checkout-page">
    
    <div class="checkout-stepper">
        <div class="step-indicator active" id="indicator-1">1. Thông tin giao hàng</div>
        <div class="step-line"></div>
        <div class="step-indicator" id="indicator-2">2. Vận chuyển</div>
        <div class="step-line"></div>
        <div class="step-indicator" id="indicator-3">3. Thanh toán</div>
    </div>
    <div class="checkout-layout">
        
        <div class="checkout-left">
            <form id="checkoutForm" action="controllers/process_checkout.php" method="POST">
                
                <div class="checkout-step active" id="step-1">
                    <h2 class="step-title">Thông tin giao hàng</h2>
                    <div class="form-group">
                        <label>Quốc gia</label>
                        <select name="country"><option value="VN">Vietnam</option></select>
                    </div>

                    <?php if (!empty($saved_addresses)): ?>

                    <!-- ► CHỌN ĐỊ CHỈ ĐÃ LƯU -->
                    <div id="saved-addr-section">
                        <div style="font-size:13px;font-weight:700;color:#777;text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px;">Giao tới</div>

                        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px;" id="addr-radio-list">
                        <?php foreach ($saved_addresses as $idx => $sa):
                            $full_addr = implode(', ', array_filter([
                                $sa['street'], $sa['ward'], $sa['district'], $sa['province']
                            ]));
                        ?>
                        <label id="addr-label-<?= $sa['address_id'] ?>" style="
                            display:flex;align-items:flex-start;gap:12px;
                            border:1.5px solid <?= $sa['is_default'] ? '#2f1c00' : '#e0e0e0' ?>;
                            border-radius:8px;padding:14px 16px;cursor:pointer;
                            background:<?= $sa['is_default'] ? '#fdfaf6' : '#fff' ?>;
                            transition:all .2s;
                        " onclick="selectSavedAddr(<?= $sa['address_id'] ?>)">
                            <input type="radio" name="addr_choice" value="<?= $sa['address_id'] ?>"
                                   id="radAddr<?= $sa['address_id'] ?>"
                                   <?= $sa['is_default'] ? 'checked' : '' ?>
                                   style="margin-top:3px;accent-color:#2f1c00;">
                            <div>
                                <div style="font-weight:700;font-size:14px;margin-bottom:3px;">
                                    <?= htmlspecialchars($sa['recipient_name']) ?>
                                    <span style="font-weight:400;color:#777;margin-left:8px;"><?= htmlspecialchars($sa['phone']) ?></span>
                                    <?php if ($sa['is_default']): ?>
                                    <span style="font-size:10.5px;background:#2f1c00;color:#fff;padding:2px 7px;border-radius:20px;margin-left:6px;vertical-align:middle;">Mặc định</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:13px;color:#555;"><?= htmlspecialchars($full_addr) ?></div>
                                <?php if (!empty($sa['note'])): ?>
                                <div style="font-size:12px;color:#aaa;margin-top:3px;"><?= htmlspecialchars($sa['note']) ?></div>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>

                        <!-- Tuỳ chọn: nhập tay -->
                        <label id="addr-label-manual" style="
                            display:flex;align-items:center;gap:12px;
                            border:1.5px solid #e0e0e0;border-radius:8px;
                            padding:12px 16px;cursor:pointer;background:#fff;transition:all .2s;
                        " onclick="selectSavedAddr('manual')">
                            <input type="radio" name="addr_choice" value="manual" id="radAddrManual" style="accent-color:#2f1c00;">
                            <span style="font-size:14px;color:#555;"><i class="fa-solid fa-pen" style="margin-right:6px;color:#bbb;"></i>Nhập địa chỉ khác</span>
                        </label>
                        </div>

                        <!-- Hidden inputs gửi lên server khi dùng địa chỉ đã lưu -->
                        <input type="hidden" id="co_recipient" name="recipient_name" value="<?= htmlspecialchars($saved_addresses[0]['recipient_name'] ?? '') ?>">
                        <input type="hidden" id="co_phone_addr" name="addr_phone"    value="<?= htmlspecialchars($saved_addresses[0]['phone']          ?? '') ?>">
                        <input type="hidden" id="co_street"     name="address"       value="<?= htmlspecialchars(implode(', ', array_filter([$saved_addresses[0]['street']??'', $saved_addresses[0]['ward']??'', $saved_addresses[0]['district']??'']))) ?>">
                        <input type="hidden" id="co_city"        name="city"          value="<?= htmlspecialchars($saved_addresses[0]['province'] ?? '') ?>">

                        <!-- form nhập tay - ẩn theo mặc định nếu có địa chỉ lưu -->
                        <div id="manual-addr-form" style="display:none;padding:16px;background:#fafafa;border:1.5px solid #e0e0e0;border-radius:8px;">

                    <?php else: ?>
                    <!-- Không có địa chỉ lưu — hiện form thủ công -->
                    <div id="manual-addr-form">
                    <?php endif; ?>

                    <?php
                        $name_parts = explode(' ', $user_info['fullname'] ?? '');
                        $first_name = array_shift($name_parts);
                        $last_name  = implode(' ', $name_parts);
                    ?>
                    <div class="form-row">
                        <div class="form-col"><label>Họ *</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($first_name) ?>" required>
                        </div>
                        <div class="form-col"><label>Tên *</label>
                            <input type="text" name="last_name"  value="<?= htmlspecialchars($last_name) ?>" required>
                        </div>
                    </div>
                    <div class="form-group"><label>Email *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user_info['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group"><label>Số điện thoại *</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user_info['phonenumber'] ?? '') ?>" required>
                    </div>
                    <div class="form-group"><label>Địa chỉ cụ thể (Số nhà, tên đường...)</label>
                        <input type="text" name="address" value="<?= htmlspecialchars($user_info['address'] ?? '') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-col"><label>Tỉnh/Thành phố *</label>
                            <select name="province" id="api_province" style="width:100%;padding:14px 15px;border:1px solid #e0e0e0;border-radius:2px;outline:none;">
                                <option value="">-- Chọn Tỉnh/Thành phố --</option>
                            </select>
                            <div class="field-error" id="err_province" style="color:#c0392b;font-size:12px;margin-top:4px;display:none;">Vui lòng chọn Tỉnh/Thành phố</div>
                        </div>
                        <div class="form-col"><label>Quận/Huyện *</label>
                            <select name="district" id="api_district" style="width:100%;padding:14px 15px;border:1px solid #e0e0e0;border-radius:2px;outline:none;" disabled>
                                <option value="">-- Chọn Quận/Huyện --</option>
                            </select>
                            <div class="field-error" id="err_district" style="color:#c0392b;font-size:12px;margin-top:4px;display:none;">Vui lòng chọn Quận/Huyện</div>
                        </div>
                    </div>
                    <div class="form-group"><label>Phường/Xã *</label>
                        <select name="ward" id="api_ward" style="width:100%;padding:14px 15px;border:1px solid #e0e0e0;border-radius:2px;outline:none;" disabled>
                            <option value="">-- Chọn Phường/Xã --</option>
                        </select>
                        <div class="field-error" id="err_ward" style="color:#c0392b;font-size:12px;margin-top:4px;display:none;">Vui lòng chọn Phường/Xã</div>
                    </div>

                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:#333;margin-top:14px;">
                        <input type="checkbox" name="save_as_default" value="1" style="accent-color:#2f1c00;width:16px;height:16px;">
                        <span style="font-weight:600;">Lưu thủ công và đặt làm địa chỉ mặc định</span>
                    </label>

                    <?php if (!empty($saved_addresses)): ?>
                        </div> <!-- đóng manual-addr-form -->
                    </div> <!-- đóng saved-addr-section -->
                    <?php else: ?>
                        </div> <!-- đóng manual-addr-form (khi không có địa chỉ lưu) -->
                    <?php endif; ?>

                    <div class="form-group"><label>Ghi chú thêm (Không bắt buộc)</label>
                        <input type="text" name="notes" placeholder="Ghi chú thêm..." id="inp_notes">
                    </div>
                    <div class="step-actions" style="justify-content: flex-end;">
                        <button type="button" class="btn-next" onclick="validateStep1()" style="flex: none; width: 200px;">Tiếp tục</button>
                    </div>
                </div>

                <div class="checkout-step" id="step-2">
                    <h2 class="step-title">Phí Ship</h2>
                    
                    <label class="method-box active">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="shipping_method" value="standard" checked>
                            <div class="method-info">
                                <div class="method-name">Tiêu chuẩn</div>
                                <div class="method-desc">Giao hàng trong 3-5 ngày</div>
                            </div>
                        </div>
                        <div class="method-price"><?php echo number_format($shipping_fee, 0, ',', '.'); ?> VNĐ</div>
                    </label>

                    <div class="step-actions">
                        <button type="button" class="btn-back" onclick="goToStep(1)">< Quay lại</button>
                        <button type="button" class="btn-next" onclick="goToStep(3)">Tiếp tục thanh toán</button>
                    </div>
                </div>

                <div class="checkout-step" id="step-3">
                    <h2 class="step-title">Phương thức thanh toán</h2>
                    
                    <label class="method-box" onclick="toggleQR(false)">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <div class="method-info">
                                <div class="method-name">Thanh toán khi nhận hàng (COD)</div>
                                <div class="method-desc">Quý khách sẽ thanh toán khi nhận hàng từ đơn vị vận chuyển.</div>
                            </div>
                        </div>
                    </label>

                    <label class="method-box" onclick="toggleQR(true)">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="online">
                            <div class="method-info">
                                <div class="method-name">Thanh toán online (Ngân hàng / Momo / VNPAY)</div>
                            </div>
                        </div>
                    </label>

                    <div class="qr-payment-info" id="qr-section">
                        <div style="text-align:center; padding: 25px 15px;">
                            <i class="fa-solid fa-shield-halved" style="font-size:40px; color:#4CAF50; margin-bottom:15px;"></i>
                            <h4 style="margin-bottom:10px; color:#333; font-size:16px;">Thanh toán An Toàn qua PayOS</h4>
                            <p style="font-size:14px; color:#666; line-height:1.6;">
                                Hệ thống tự động tạo mã QR chính xác với số tiền và nội dung chuyển khoản.<br>
                                Bạn sẽ được chuyển hướng tới cổng thanh toán an toàn ngay sau khi xác nhận đặt hàng.
                            </p>
                        </div>
                    </div>

                    <input type="hidden" name="wallet_used" id="input_wallet_used" value="0">

                    <div class="step-actions">
                        <button type="button" class="btn-back" onclick="goToStep(2)">< Quay lại</button>
                        <button type="submit" class="btn-next" onclick="return validateSubmit()">XÁC NHẬN ĐẶT HÀNG</button>
                    </div>
                </div>

            </form>
        </div>

        <div class="checkout-right">
            <h3 class="summary-title">Tóm tắt đơn hàng</h3>
            
            <div class="summary-items" style="max-height: 300px; overflow-y: auto; padding-right: 10px;">
                <?php foreach ($checkout_items as $item): 
                    $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['original_price'];
                ?>
                <div class="sum-item">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                    <div class="sum-item-info">
                        <div style="display: flex; justify-content: space-between;">
                            <div class="sum-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="sum-item-price"><?php echo number_format($price, 0, ',', '.'); ?> VNĐ</div>
                        </div>
                        <div class="sum-item-variant">Phân loại: <?php echo htmlspecialchars($item['color']); ?></div>
                        <div class="sum-item-variant">Kích cỡ: <?php echo htmlspecialchars($item['size']); ?></div>
                        <div class="sum-item-variant">Số lượng: x<?php echo $item['quantity']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="sum-wallet-box">
                <div class="wallet-title">Ví nội bộ của bạn</div>
                <div class="wallet-balance">Số dư: <?php echo number_format($wallet_balance, 0, ',', '.'); ?> VNĐ</div>
                
                <label class="wallet-checkbox">
                    <input type="checkbox" id="use_wallet_cb" onchange="calculateTotal()" <?php echo ($wallet_balance <= 0) ? 'disabled' : ''; ?>>
                    Sử dụng ví nội bộ cho đơn hàng này
                </label>
            </div>

            <!-- MÃ GIẢM GIÁ -->
            <div class="coupon-box">
                <!-- PANEL ĐỀ XUẤT VOUCHER -->
                <?php if (!empty($available_coupons)): ?>
                <div class="coupon-suggestions" id="coupon_suggestions">
                    <div class="coupon-suggest-title">
                        <i class="fa-solid fa-bolt" style="color:#b7860b;"></i>
                        Voucher dành cho bạn
                    </div>
                    <div class="coupon-suggest-list" id="suggest_list">
                    <?php foreach (array_slice($available_coupons, 0, 3) as $idx => $sc):
                        $is_percent = ($sc['discount_type'] == 0);
                        $desc = $is_percent
                            ? 'Giảm ' . intval($sc['discount_value']) . '%' . (!empty($sc['max_discount_amount']) ? ' (tối đa ' . number_format($sc['max_discount_amount'],0,',','.') . 'đ)' : '')
                            : 'Giảm cố định';
                        $end_str = empty($sc['end_date']) ? 'Vô hạn' : 'HSD: ' . date('d/m/Y', strtotime($sc['end_date']));
                    ?>
                    <div class="coupon-suggest-item <?= $idx === 0 ? 'best-pick' : '' ?>"
                         onclick="selectSuggestedCoupon('<?= htmlspecialchars($sc['code']) ?>')"
                         data-code="<?= htmlspecialchars($sc['code']) ?>"
                         data-id="<?= $sc['coupon_id'] ?>"
                         data-discount="<?= $sc['calc_discount'] ?>"
                         data-type="<?= $sc['discount_type'] ?>"
                         data-value="<?= $sc['discount_value'] ?>"
                         data-min="<?= $sc['min_order_value'] ?>"
                         data-max="<?= $sc['max_discount_amount'] ?? 0 ?>"
                         data-end="<?= $sc['end_date'] ?? '' ?>"
                         data-qty="<?= $sc['quantity'] ?? '' ?>"
                         data-used="<?= $sc['used_count'] ?? 0 ?>">
                        <div class="csi-left">
                            <?php if ($idx === 0): ?>
                                <span class="csi-badge best">Tốt nhất</span>
                            <?php else: ?>
                                <span class="csi-badge"><i class="fa-solid fa-tag"></i></span>
                            <?php endif; ?>
                            <div class="csi-info">
                                <div class="csi-code"><?= htmlspecialchars($sc['code']) ?></div>
                                <div class="csi-desc"><?= $desc ?> &bull; <?= $end_str ?></div>
                            </div>
                        </div>
                        <span class="csi-discount">-<?= number_format($sc['calc_discount'],0,',','.') ?>đ</span>
                        <button class="csi-detail-btn" type="button"
                                onclick="event.stopPropagation(); openVoucherModal(this.closest('.coupon-suggest-item'))">
                            Chi tiết
                        </button>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <?php if (count($available_coupons) > 3): ?>
                    <div class="coupon-suggest-more" onclick="toggleMoreCoupons()">
                        <i class="fa-solid fa-chevron-down" id="suggest_chevron"></i>
                        Xem thêm <?= count($available_coupons) - 3 ?> voucher khác
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="coupon-title">Nhập mã thủ công</div>
                <div class="coupon-input-row">
                    <input type="text" id="coupon_code_input" placeholder="Nhập mã..." maxlength="50">
                    <button type="button" onclick="applyCoupon()">Áp dụng</button>
                </div>
                <div class="coupon-msg" id="coupon_msg"></div>
                <div id="coupon_applied_tag" style="display:none;" class="coupon-applied-tag">
                    <span id="coupon_tag_text"></span>
                    <button class="coupon-remove" onclick="removeCoupon()" title="Xóa mã">✕</button>
                </div>
                <!-- Hidden inputs để submit form -->
                <input type="hidden" name="coupon_code" id="input_coupon_code" value="">
                <input type="hidden" name="coupon_discount" id="input_coupon_discount" value="0">
                <input type="hidden" name="coupon_id" id="input_coupon_id" value="">
            </div>

            <div class="sum-row">
                <span>Giá tạm tính:</span>
                <span id="ui_subtotal" data-val="<?php echo $subtotal; ?>"><?php echo number_format($subtotal, 0, ',', '.'); ?> VNĐ</span>
            </div>
            <div class="sum-row">
                <span>Phí vận chuyển:</span>
                <span id="ui_shipping" data-val="<?php echo $shipping_fee; ?>"><?php echo number_format($shipping_fee, 0, ',', '.'); ?> VNĐ</span>
            </div>

            <div class="sum-row" id="coupon_discount_row" style="display: none; color: #2e7d32;">
                <span>Giảm giá (mã):</span>
                <span id="ui_coupon_discount">-0 VNĐ</span>
            </div>
            
            <div class="sum-row" id="wallet_discount_row" style="display: none; color: #d32f2f;">
                <span>Dùng ví hoàn tiền:</span>
                <span id="ui_wallet_used">-0 VNĐ</span>
            </div>

            <div class="sum-total-row">
                <span>TỔNG TIỀN</span>
                <span id="ui_total"><?php echo number_format($subtotal + $shipping_fee, 0, ',', '.'); ?> VNĐ</span>
            </div>
            <div class="sum-note">Chưa bao gồm thuế (nếu có)</div>
        </div>

    </div>
</div>

<script>
    // ── CHỌN ĐỊA CHỈ ĐÃ LƯU ────────────────────────────────
    // Map address_id → data (từ PHP)
    const savedAddrMap = <?php
        $map = [];
        foreach ($saved_addresses as $sa) {
            $full = implode(', ', array_filter([$sa['street'], $sa['ward'], $sa['district']]));
            $map[$sa['address_id']] = [
                'recipient' => $sa['recipient_name'],
                'phone'     => $sa['phone'],
                'address'   => $full,
                'city'      => $sa['province'],
            ];
        }
        echo json_encode($map, JSON_UNESCAPED_UNICODE);
    ?>;

    function selectSavedAddr(id) {
        // Cập nhật radio checked
        const radios = document.querySelectorAll('[name="addr_choice"]');
        radios.forEach(r => r.checked = (r.value == id));

        // Style các label
        document.querySelectorAll('#addr-radio-list label, #addr-label-manual').forEach(lbl => {
            lbl.style.borderColor = '#e0e0e0';
            lbl.style.background  = '#fff';
        });
        const activeLabel = document.getElementById('addr-label-' + id);
        if (activeLabel) {
            activeLabel.style.borderColor = '#2f1c00';
            activeLabel.style.background  = '#fdfaf6';
        }

        const manualForm = document.getElementById('manual-addr-form');
        const manualInputs = manualForm ? manualForm.querySelectorAll('input:not([type="checkbox"]), select') : [];

        if (id === 'manual') {
            // Hiện form nhập tay, bật required cho tất cả các field nhập tay, tắt required cho biến ẩn (nếu có)
            if (manualForm) manualForm.style.display = 'block';
            manualInputs.forEach(el => el.setAttribute('required', 'required'));
            ['co_recipient','co_phone_addr','co_street','co_city'].forEach(fid => {
                const el = document.getElementById(fid);
                if (el) el.removeAttribute('required');
            });
        } else {
            // Ẩn form nhập tay, xóa toàn bộ required trên DOM ẩn
            if (manualForm) manualForm.style.display = 'none';
            manualInputs.forEach(el => el.removeAttribute('required'));
            // Điền hidden inputs
            const d = savedAddrMap[id];
            if (d) {
                document.getElementById('co_recipient').value  = d.recipient;
                document.getElementById('co_phone_addr').value = d.phone;
                document.getElementById('co_street').value     = d.address;
                document.getElementById('co_city').value       = d.city;
            }
        }
    }

    // Init: apply style cho địa chỉ mặc định đang được chọn
    document.addEventListener('DOMContentLoaded', function() {
        const checkedRadio = document.querySelector('[name="addr_choice"]:checked');
        if (checkedRadio) selectSavedAddr(checkedRadio.value === 'manual' ? 'manual' : parseInt(checkedRadio.value));
    });

    // JS CHUYỂN BƯỚC VÀ CẬP NHẬT THANH TIẾN TRÌNH
    function goToStep(step) {
        document.querySelectorAll('.checkout-step').forEach(el => el.classList.remove('active'));
        document.getElementById('step-' + step).classList.add('active');
        document.querySelectorAll('.step-indicator').forEach(el => el.classList.remove('active'));
        document.getElementById('indicator-' + step).classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ── VALIDATION STEP 1 ───────────────────────────────────────
    function showFieldError(inputEl, msgElId, msg) {
        if (inputEl) {
            inputEl.style.borderColor = '#c0392b';
            inputEl.addEventListener('input', function() {
                inputEl.style.borderColor = '#e0e0e0';
                const e = document.getElementById(msgElId);
                if (e) e.style.display = 'none';
            }, { once: true });
        }
        const errEl = document.getElementById(msgElId);
        if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
    }
    function clearFieldError(inputEl, msgElId) {
        if (inputEl) inputEl.style.borderColor = '#e0e0e0';
        const e = document.getElementById(msgElId); if (e) e.style.display = 'none';
    }

    function validateStep1() {
        const addrChoice = document.querySelector('[name="addr_choice"]:checked');
        const usingManual = !addrChoice || addrChoice.value === 'manual';

        if (!usingManual) { goToStep(2); return; }

        let valid = true;

        function checkSelect(id, errId) {
            const el  = document.getElementById(id);
            const err = document.getElementById(errId);
            if (el && !el.value) {
                el.style.borderColor = '#c0392b';
                if (err) err.style.display = 'block';
                el.addEventListener('change', () => {
                    el.style.borderColor = '#e0e0e0';
                    if (err) err.style.display = 'none';
                }, { once: true });
                return false;
            }
            if (el) el.style.borderColor = '#e0e0e0';
            if (err) err.style.display = 'none';
            return true;
        }

        // 1. Tinh/Thanh pho BAT BUOC
        if (!checkSelect('api_province', 'err_province')) valid = false;

        // 2. Quan/Huyen BAT BUOC (chi check khi da bat)
        const distEl = document.getElementById('api_district');
        if (distEl && !distEl.disabled) {
            if (!checkSelect('api_district', 'err_district')) valid = false;
        } else if (distEl && distEl.disabled) {
            // Quan dang disabled vi chua chon Tinh -> bao loi Tinh la du
        }

        // 3. Phuong/Xa BAT BUOC (chi check khi da bat)
        const wardEl = document.getElementById('api_ward');
        if (wardEl && !wardEl.disabled) {
            if (!checkSelect('api_ward', 'err_ward')) valid = false;
        }

        if (!valid) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }
        goToStep(2);
    }

    // ── VALIDATE TRƯỚC KHI SUBMIT ────────────────────────────────
    function validateSubmit() {
        // Xoá required khỏi tất cả field bị ẩn để browser validation không block
        document.querySelectorAll('.checkout-step:not(.active) [required]').forEach(el => {
            el.dataset.wasRequired = '1';
            el.removeAttribute('required');
        });
        return true; // Cho phép form submit
    }

    // JS HIỂN THỊ MÃ QR KHI CHỌN ONLINE
    function toggleQR(show) {
        const qrSection = document.getElementById('qr-section');
        const boxes = document.querySelectorAll('#step-3 .method-box');
        boxes.forEach(box => box.classList.remove('active'));
        if(show) { qrSection.classList.add('show'); boxes[1].classList.add('active'); }
        else      { qrSection.classList.remove('show'); boxes[0].classList.add('active'); }
    }

    // JS TÍNH TOÁN VÍ HOÀN TIỀN & MÃ GIẢM GIÁ
    const walletBalance = <?php echo $wallet_balance; ?>;

    // State mã giảm giá
    let activeCoupon = null; // { coupon_id, code, discount_amount }

    function applyCoupon() {
        const code = document.getElementById('coupon_code_input').value.trim().toUpperCase();
        const msgEl = document.getElementById('coupon_msg');
        const tagEl = document.getElementById('coupon_applied_tag');
        const tagText = document.getElementById('coupon_tag_text');

        if (!code) {
            msgEl.textContent = 'Vui lòng nhập mã giảm giá.';
            msgEl.className = 'coupon-msg error';
            return;
        }

        const subtotal = parseInt(document.getElementById('ui_subtotal').getAttribute('data-val'));
        const shipping = parseInt(document.getElementById('ui_shipping').getAttribute('data-val'));
        const orderTotal = subtotal; // Dùng subtotal (không cộng ship) cho nhất quán với giỏ hàng

        // Gọi AJAX kiểm tra mã qua POST để tránh lỗi ký tự đặc biệt trong URL (vd: 30/4)
        fetch('api/check_coupon.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'code=' + encodeURIComponent(code) + '&order_total=' + orderTotal
        })
            .then(r => r.json())
            .then(res => {
                if (res.valid) {
                    activeCoupon = {
                        coupon_id: res.coupon_id,
                        code: res.code,
                        discount_amount: res.discount_amount
                    };
                    document.getElementById('input_coupon_code').value = res.code;
                    document.getElementById('input_coupon_discount').value = res.discount_amount;
                    document.getElementById('input_coupon_id').value = res.coupon_id;

                    tagText.textContent = res.code + ' – Giảm ' + res.discount_amount.toLocaleString('vi-VN') + ' VNĐ';
                    tagEl.style.display = 'flex';
                    msgEl.textContent = '';
                    msgEl.className = 'coupon-msg';
                    calculateTotal();
                } else {
                    activeCoupon = null;
                    tagEl.style.display = 'none';
                    document.getElementById('input_coupon_code').value = '';
                    document.getElementById('input_coupon_discount').value = '0';
                    document.getElementById('input_coupon_id').value = '';
                    msgEl.textContent = res.message || 'Mã giảm giá không hợp lệ.';
                    msgEl.className = 'coupon-msg error';
                    calculateTotal();
                }
            })
            .catch(() => {
                msgEl.textContent = 'Lỗi kết nối, thử lại sau.';
                msgEl.className = 'coupon-msg error';
            });
    }

    function removeCoupon() {
        activeCoupon = null;
        document.getElementById('coupon_code_input').value = '';
        document.getElementById('input_coupon_code').value = '';
        document.getElementById('input_coupon_discount').value = '0';
        document.getElementById('input_coupon_id').value = '';
        document.getElementById('coupon_applied_tag').style.display = 'none';
        document.getElementById('coupon_msg').textContent = '';
        document.getElementById('coupon_msg').className = 'coupon-msg';
        calculateTotal();
    }

    function calculateTotal() {
        const subtotal = parseInt(document.getElementById('ui_subtotal').getAttribute('data-val'));
        const shipping = parseInt(document.getElementById('ui_shipping').getAttribute('data-val'));
        const useWallet = document.getElementById('use_wallet_cb').checked;
        const walletRow = document.getElementById('wallet_discount_row');
        const uiWalletUsed = document.getElementById('ui_wallet_used');
        const uiTotal = document.getElementById('ui_total');
        const qrTotal = document.getElementById('qr-total-amount');
        const inputWalletUsed = document.getElementById('input_wallet_used');

        // Giảm giá coupon
        const couponDiscount = activeCoupon ? activeCoupon.discount_amount : 0;
        const couponRow = document.getElementById('coupon_discount_row');
        const uiCouponDiscount = document.getElementById('ui_coupon_discount');
        if (couponDiscount > 0) {
            couponRow.style.display = 'flex';
            uiCouponDiscount.innerText = '-' + couponDiscount.toLocaleString('vi-VN') + ' VNĐ';
        } else {
            couponRow.style.display = 'none';
        }

        let totalAfterCoupon = Math.max(0, subtotal + shipping - couponDiscount);

        let walletUsedAmount = 0;
        if (useWallet) {
            walletUsedAmount = Math.min(walletBalance, totalAfterCoupon);
            walletRow.style.display = 'flex';
            uiWalletUsed.innerText = '-' + walletUsedAmount.toLocaleString('vi-VN') + ' VNĐ';
        } else { walletRow.style.display = 'none'; }

        let finalTotal = totalAfterCoupon - walletUsedAmount;
        uiTotal.innerText = finalTotal.toLocaleString('vi-VN') + ' VNĐ';
        if(qrTotal) qrTotal.innerText = finalTotal.toLocaleString('vi-VN');
        inputWalletUsed.value = walletUsedAmount;
    }

    // ── GỌI API ĐỊA CHỈ (esgoo.net) ────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        const selPr = document.getElementById('api_province');
        const selDi = document.getElementById('api_district');
        const selWa = document.getElementById('api_ward');

        if (!selPr) return;

        // Bỏ qua giá trị ID trên server, ta lưu value là NAME
        fetch('https://esgoo.net/api-tinhthanh/1/0.htm')
            .then(res => res.json())
            .then(data => {
                if(data.error === 0) {
                    data.data.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.name;
                        opt.dataset.id = p.id;
                        opt.textContent = p.name;
                        selPr.appendChild(opt);
                    });
                }
            });

        selPr.addEventListener('change', function() {
            selDi.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
            selWa.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            selWa.disabled = true;
            selDi.disabled = true;

            const selectedOpt = selPr.options[selPr.selectedIndex];
            const pid = selectedOpt?.dataset?.id;
            
            if(pid) {
                fetch(`https://esgoo.net/api-tinhthanh/2/${pid}.htm`)
                    .then(res => res.json())
                    .then(data => {
                        if(data.error === 0) {
                            data.data.forEach(d => {
                                const opt = document.createElement('option');
                                opt.value = d.name;
                                opt.dataset.id = d.id;
                                opt.textContent = d.name;
                                selDi.appendChild(opt);
                            });
                            selDi.disabled = false;
                        }
                    });
            }
        });

        selDi.addEventListener('change', function() {
            selWa.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            selWa.disabled = true;

            const selectedOpt = selDi.options[selDi.selectedIndex];
            const did = selectedOpt?.dataset?.id;

            if(did) {
                fetch(`https://esgoo.net/api-tinhthanh/3/${did}.htm`)
                    .then(res => res.json())
                    .then(data => {
                        if(data.error === 0) {
                            data.data.forEach(w => {
                                const opt = document.createElement('option');
                                opt.value = w.name;
                                opt.textContent = w.name;
                                selWa.appendChild(opt);
                            });
                            selWa.disabled = false;
                        }
                    });
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>