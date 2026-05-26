<?php
// order_detail.php - Included inside dashboard.php
$order_id = $_GET['id'] ?? null;
if (!$order_id) {
    echo "<h3>Không tìm thấy đơn hàng.</h3>";
    return;
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :oid AND user_id = :uid");
$stmt->execute(['oid' => $order_id, 'uid' => $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<h3>Đơn hàng không tồn tại hoặc bạn không có quyền xem.</h3>";
    return;
}

// Lấy chi tiết sản phẩm
$stmt_items = $conn->prepare("
    SELECT od.*, p.product_id AS product_id, p.image AS product_image, v.image AS variant_image, v.color, v.size 
    FROM order_details od
    LEFT JOIN product_variants v ON od.variant_id = v.variant_id
    LEFT JOIN products p ON v.product_id = p.product_id
    WHERE od.order_id = :oid
");
$stmt_items->execute(['oid' => $order_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Xác định trạng thái để hiển thị Progress
// 0: Chờ thanh toán (nếu online chưa pass / COD: pending_payment nếu có), 1: Đang xử lý, 2: Đang giao, 3: Hoàn thành, 4: Đã hủy
$status = (int)$order['order_status']; 
$os = $status; // alias dùng cho các nút hành động bên dưới
$date_placed = date('Y-m-d', strtotime($order['order_date']));
?>

<style>
.od-container {
    background: #fff;
    padding: 20px;
}
.od-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
    margin-bottom: 20px;
}
.od-header a {
    text-decoration: none;
    color: #444;
    font-weight: 500;
}
.od-header a:hover {
    color: var(--primary);
}
.od-status-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    position: relative;
    padding: 0 40px;
}
.od-status-bar::before {
    content: '';
    position: absolute;
    top: 24px;
    left: 80px;
    right: 80px;
    height: 3px;
    background: #e0e0e0;
    z-index: 1;
}
.od-step {
    text-align: center;
    position: relative;
    z-index: 2;
    flex: 1;
}
.od-step-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #fff;
    border: 3px solid #e0e0e0;
    color: #aaa;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 10px;
    background: #f9f9f9;
}
.od-step.active .od-step-icon {
    border-color: #4cd137;
    color: #4cd137;
}
.od-step-label {
    font-size: 13px;
    color: #555;
    font-weight: 500;
}
.od-step-date {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.od-info-cards {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}
.od-info-card {
    flex: 1;
    background: #fafafa;
    border: 1px solid #eee;
    padding: 20px;
    border-radius: 4px;
}
.od-info-card h4 {
    margin-top: 0;
    font-size: 14px;
    color: #333;
    text-transform: uppercase;
    border-bottom: 1px solid #ebebeb;
    padding-bottom: 10px;
    margin-bottom: 15px;
}
.od-info-row {
    margin-bottom: 8px;
    font-size: 14px;
    color: #555;
}
.od-info-row strong {
    color: #333;
}

.od-products {
    background: #fff;
    border: 1px solid #eee;
    padding: 20px;
    border-radius: 4px;
}
.od-products h4 {
    margin-top: 0;
    font-size: 14px;
    color: #333;
    text-transform: uppercase;
    border-bottom: 1px solid #ebebeb;
    padding-bottom: 10px;
    margin-bottom: 15px;
}
.od-item {
    display: flex;
    border-bottom: 1px solid #f5f5f5;
    padding: 15px 0;
}
.od-item:last-child {
    border-bottom: none;
}
.od-item-img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    border: 1px solid #eee;
    margin-right: 15px;
}
.od-item-details {
    flex: 1;
}
.od-item-name {
    font-weight: 600;
    color: #333;
    font-size: 14px;
    margin-bottom: 5px;
}
.od-item-variant {
    font-size: 13px;
    color: #777;
    margin-bottom: 8px;
}
.od-item-price-qty {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}
.od-item-price {
    font-weight: 600;
    color: #333;
}

.od-summary {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px dashed #ccc;
    font-size: 15px;
}
.od-summary-inner {
    width: 300px;
}
.od-summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    color: #555;
}
.od-summary-row.total {
    font-size: 18px;
    font-weight: bold;
    color: var(--primary);
    border-top: 1px solid #eee;
    padding-top: 10px;
    margin-top: 5px;
}

/* ════════════════════════════════════════════════════════
   DARK MODE STYLING FOR ORDER DETAIL
   ════════════════════════════════════════════════════════ */
body.dark-mode .od-container {
    background-color: #181818 !important;
    color: #eeeeee !important;
}
body.dark-mode .od-header {
    border-bottom-color: #2a2a2a !important;
    color: #eeeeee !important;
}
body.dark-mode .od-header a {
    color: #cccccc !important;
}
body.dark-mode .od-header a:hover {
    color: #e5c199 !important;
}
body.dark-mode .od-status-bar::before {
    background: #2a2a2a !important;
}
body.dark-mode .od-step-icon {
    background: #252525 !important;
    border-color: #3a3a3a !important;
    color: #888888 !important;
}
body.dark-mode .od-step.active .od-step-icon {
    border-color: #4cd137 !important;
    color: #4cd137 !important;
}
body.dark-mode .od-step-label {
    color: #cccccc !important;
}
body.dark-mode .od-step-date {
    color: #777777 !important;
}
body.dark-mode .od-info-card {
    background-color: #1e1e1e !important;
    border-color: #2a2a2a !important;
    color: #eeeeee !important;
}
body.dark-mode .od-info-card h4 {
    color: #ffffff !important;
    border-bottom-color: #2a2a2a !important;
}
body.dark-mode .od-info-row {
    color: #cccccc !important;
}
body.dark-mode .od-info-row strong {
    color: #ffffff !important;
}
body.dark-mode .od-products {
    background-color: #181818 !important;
    border-color: #2a2a2a !important;
}
body.dark-mode .od-products h4 {
    color: #ffffff !important;
    border-bottom-color: #2a2a2a !important;
}
body.dark-mode .od-item {
    border-bottom-color: #2a2a2a !important;
}
body.dark-mode .od-item-img {
    border-color: #333333 !important;
    background-color: #252525 !important;
}
body.dark-mode .od-item-name {
    color: #ffffff !important;
}
body.dark-mode .od-item-variant {
    color: #aaaaaa !important;
}
body.dark-mode .od-summary {
    border-top-color: #2a2a2a !important;
    color: #cccccc !important;
}
body.dark-mode .od-summary-row {
    color: #cccccc !important;
}
body.dark-mode .od-summary-row.total {
    color: #e5c199 !important;
    border-top-color: #2a2a2a !important;
}
body.dark-mode .od-action-btn {
    background-color: #252525 !important;
    border-color: #555555 !important;
    color: #dddddd !important;
}
body.dark-mode .od-action-btn:hover {
    background-color: #333333 !important;
    border-color: #ffffff !important;
    color: #ffffff !important;
}
body.dark-mode .od-btn-primary {
    background-color: #a6825c !important;
    color: #121212 !important;
    border-color: #a6825c !important;
}
body.dark-mode .od-btn-primary:hover {
    background-color: #c9a47e !important;
    border-color: #c9a47e !important;
}
body.dark-mode .od-btn-teal {
    background-color: #16a085 !important;
    color: #ffffff !important;
    border-color: #16a085 !important;
}
body.dark-mode .od-btn-teal:hover {
    background-color: #1abc9c !important;
    border-color: #1abc9c !important;
}
body.dark-mode .od-btn-danger {
    background-color: #c0392b !important;
    color: #ffffff !important;
    border-color: #c0392b !important;
}
body.dark-mode .od-btn-danger:hover {
    background-color: #e74c3c !important;
    border-color: #e74c3c !important;
}
body.dark-mode #review-modal-detail,
body.dark-mode #return-modal-detail {
    background: rgba(0, 0, 0, 0.75) !important;
}
body.dark-mode #review-modal-detail > div,
body.dark-mode #return-modal-detail > div {
    background-color: #1e1e1e !important;
    color: #eeeeee !important;
}
body.dark-mode #review-modal-detail h3,
body.dark-mode #return-modal-detail h3,
body.dark-mode #review-modal-detail label,
body.dark-mode #return-modal-detail label {
    color: #ffffff !important;
}
body.dark-mode #review-modal-detail p,
body.dark-mode #return-modal-detail p {
    color: #cccccc !important;
}
body.dark-mode #review-modal-detail input,
body.dark-mode #review-modal-detail select,
body.dark-mode #review-modal-detail textarea,
body.dark-mode #return-modal-detail input,
body.dark-mode #return-modal-detail select,
body.dark-mode #return-modal-detail textarea {
    background-color: #252525 !important;
    border-color: #333333 !important;
    color: #ffffff !important;
}
body.dark-mode #review-modal-detail input::placeholder,
body.dark-mode #review-modal-detail textarea::placeholder,
body.dark-mode #return-modal-detail input::placeholder,
body.dark-mode #return-modal-detail textarea::placeholder {
    color: #888888 !important;
}
</style>

<div class="od-container">
    <div class="od-header">
        <a href="dashboard.php?view=donmua"><i class="fa-solid fa-arrow-left"></i> TRỞ LẠI</a>
        <div style="font-size: 14px; color: #555;">MÃ ĐƠN HÀNG: <strong><?= $order['order_id'] ?></strong></div>
    </div>

    <!-- Thanh trạng thái (giả lập dựa trên order_status) -->
    <div class="od-status-bar">
        <?php
            $os       = (int)$order['order_status'];
            $is_cod   = ($order['payment_method'] == 1);
            $is_paid  = ($order['payment_status'] == 1);

            // ── Các bước progress bar theo luồng ─────────────────────────────
            // Đặt hàng: luôn active
            $step_placed   = true;

            // Chờ lấy hàng: active khi status >= 1 (bao gồm các nhánh đặc biệt >= 1)
            $step_waiting  = ($os >= 1 && $os !== 4);

            // Đang giao: active khi status >= 2
            $step_shipping = in_array($os, [2, 3, 5, 6, 7, 9, 10]);

            // Đã nhận + Xác nhận TT:
            // - Online: sáng khi đã thanh toán (payment_status=1) hoặc hoàn thành
            // - COD: chỉ sáng khi status=3 (nhận hàng), vì COD thu tiền khi giao
            $step_received = ($os === 3 || in_array($os, [5, 6, 7]));
            $step_paid_visual = $step_received; // Thanh toán xác nhận cùng lúc nhận hàng (COD) hoặc sau TT online
            if (!$is_cod && $is_paid) $step_paid_visual = ($os >= 1 && $os !== 4); // Online: sáng khi đã TT

            // Hoàn thành
            $step_done = ($os === 3);

            // Nhánh đặc biệt
            $is_cancelled      = ($os === 4);
            $is_return_pending = ($os === 5);
            $is_returning      = ($os === 6);
            $is_refunded       = ($os === 7);
            $is_delivery_failed= in_array($os, [9, 10]);
        ?>

        <?php if ($is_cancelled): ?>
            <!-- Đã hủy: hiển thị nhánh hủy -->
            <div class="od-step active">
                <div class="od-step-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="od-step-label">Đơn hàng đã đặt</div>
                <div class="od-step-date"><?= $date_placed ?></div>
            </div>
            <div class="od-step active" style="--step-color:#c0392b;">
                <div class="od-step-icon" style="border-color:#c0392b; color:#c0392b;"><i class="fa-solid fa-circle-xmark"></i></div>
                <div class="od-step-label" style="color:#c0392b;">Đơn hàng đã hủy</div>
                <div class="od-step-date" style="color:#c0392b;">
                    <?= !empty($order['cancel_reason']) ? htmlspecialchars($order['cancel_reason']) : 'Đã hủy' ?>
                </div>
            </div>

        <?php elseif ($is_return_pending || $is_returning || $is_refunded): ?>
            <!-- Trả hàng / Hoàn tiền -->
            <div class="od-step active">
                <div class="od-step-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="od-step-label">Đơn hàng đã đặt</div>
                <div class="od-step-date"><?= $date_placed ?></div>
            </div>
            <div class="od-step active">
                <div class="od-step-icon"><i class="fa-solid fa-box-open"></i></div>
                <div class="od-step-label">Đã nhận hàng</div>
                <div class="od-step-date">Hoàn thành trước đó</div>
            </div>
            <div class="od-step active" style="color:#c0392b;">
                <div class="od-step-icon" style="border-color:#c0392b; color:#c0392b;"><i class="fa-solid fa-rotate-left"></i></div>
                <div class="od-step-label" style="color:#c0392b;">
                    <?= $is_refunded ? 'Đã hoàn tiền' : ($is_returning ? 'Đang hoàn trả hàng' : 'Đang yêu cầu trả hàng') ?>
                </div>
                <div class="od-step-date" style="color:<?= $is_refunded ? '#1abc9c' : '#c0392b' ?>;">
                    <?= $is_refunded ? 'Tiền đã vào ví' : 'Chờ xử lý' ?>
                </div>
            </div>

        <?php elseif ($is_delivery_failed): ?>
            <!-- Giao thất bại -->
            <div class="od-step active">
                <div class="od-step-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="od-step-label">Đơn hàng đã đặt</div>
                <div class="od-step-date"><?= $date_placed ?></div>
            </div>
            <div class="od-step active">
                <div class="od-step-icon"><i class="fa-solid fa-truck"></i></div>
                <div class="od-step-label">Đang giao hàng</div>
                <div class="od-step-date">Đã bàn giao ĐVVC</div>
            </div>
            <div class="od-step active">
                <div class="od-step-icon" style="border-color:#c0392b; color:#c0392b;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="od-step-label" style="color:#c0392b;">Giao hàng thất bại</div>
                <div class="od-step-date" style="color:#c0392b;">Đang hoàn về kho</div>
            </div>

        <?php else: ?>
            <!-- Luồng chuẩn -->

            <!-- Bước 1: Đặt hàng (luôn active) -->
            <div class="od-step active">
                <div class="od-step-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="od-step-label">Đơn hàng đã đặt</div>
                <div class="od-step-date"><?= $date_placed ?></div>
            </div>

            <?php if (!$is_cod): ?>
            <!-- Bước 1.5: Đã thanh toán (chỉ Online) -->
            <div class="od-step <?= $is_paid ? 'active' : '' ?>">
                <div class="od-step-icon"><i class="fa-solid fa-credit-card"></i></div>
                <div class="od-step-label">Đã thanh toán</div>
                <div class="od-step-date"><?= $is_paid ? 'Thanh toán thành công' : 'Chờ thanh toán' ?></div>
            </div>
            <?php endif; ?>

            <!-- Bước 2: Chờ lấy hàng (status >= 1) -->
            <div class="od-step <?= $step_waiting ? 'active' : '' ?>">
                <div class="od-step-icon"><i class="fa-solid fa-box"></i></div>
                <div class="od-step-label">Chờ lấy hàng</div>
                <div class="od-step-date"><?= $step_waiting ? 'Người bán đang chuẩn bị' : '-' ?></div>
            </div>

            <!-- Bước 3: Đang giao (status >= 2) -->
            <div class="od-step <?= $step_shipping ? 'active' : '' ?>">
                <div class="od-step-icon"><i class="fa-solid fa-truck"></i></div>
                <div class="od-step-label">Đang giao hàng</div>
                <div class="od-step-date"><?= $step_shipping ? 'Đã bàn giao ĐVVC' : '-' ?></div>
            </div>

            <!-- Bước 4: Nhận hàng -->
            <div class="od-step <?= $step_received ? 'active' : '' ?>">
                <div class="od-step-icon"><i class="fa-solid fa-box-open"></i></div>
                <div class="od-step-label">Đã nhận được hàng<?= $is_cod ? '<br>& Thu tiền COD' : '' ?></div>
                <div class="od-step-date">
                    <?php if ($step_received): ?>
                        <?= $is_cod ? 'Thanh toán & Nhận hàng' : 'Đã xác nhận nhận hàng' ?>
                    <?php else: ?>-<?php endif; ?>
                </div>
            </div>

            <!-- Bước 5: Hoàn thành (status = 3) -->
            <div class="od-step <?= $step_done ? 'active' : '' ?>">
                <div class="od-step-icon"><i class="fa-solid fa-star"></i></div>
                <div class="od-step-label">Hoàn thành</div>
                <div class="od-step-date"><?= $step_done ? 'Cảm ơn bạn!' : '-' ?></div>
            </div>

        <?php endif; ?>
    </div>

    <!-- Thông báo trạng thái đặc biệt -->
    <?php if ($os === 5): ?>
    <div style="background:#fff8f0; border:1px solid #f0a500; padding:14px 20px; border-radius:6px; margin-bottom:24px; font-size:14px; color:#7a5000;">
        <i class="fa-solid fa-clock-rotate-left" style="margin-right:8px;"></i>
        <strong>Yêu cầu trả hàng đang được xem xét.</strong> Admin sẽ phản hồi trong vòng 24 giờ.
    </div>
    <?php elseif ($os === 6): ?>
    <div style="background:#eaf4fd; border:1px solid #2980b9; padding:14px 20px; border-radius:6px; margin-bottom:24px; font-size:14px; color:#1a5276;">
        <i class="fa-solid fa-truck-ramp-box" style="margin-right:8px;"></i>
        <strong>Yêu cầu trả hàng đã được duyệt.</strong> Vui lòng đóng gói và gửi hàng về trong vòng 3 ngày.
    </div>
    <?php elseif ($os === 7): ?>
    <div style="background:#eafaf1; border:1px solid #27ae60; padding:14px 20px; border-radius:6px; margin-bottom:24px; font-size:14px; color:#1e8449;">
        <i class="fa-solid fa-circle-check" style="margin-right:8px;"></i>
        <strong>Hoàn tiền thành công!</strong> Tiền đã được hoàn vào ví của bạn.
    </div>
    <?php elseif ($os === 9 || $os === 10): ?>
    <div style="background:#fdf0ef; border:1px solid #e74c3c; padding:14px 20px; border-radius:6px; margin-bottom:24px; font-size:14px; color:#7b241c;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-right:8px;"></i>
        <strong>Giao hàng không thành công.</strong>
        <?= !empty($order['admin_note']) ? htmlspecialchars($order['admin_note']) . '.' : '' ?>
        Đơn hàng đang được hoàn về kho. Chúng tôi sẽ liên hệ lại với bạn.
    </div>
    <?php endif; ?>

    <div class="od-info-cards">
        <div class="od-info-card">
            <h4>ĐỊA CHỈ NHẬN HÀNG</h4>
            <div class="od-info-row"><strong><?= htmlspecialchars($order['fullname'] ?? '') ?></strong></div>
            <div class="od-info-row"><?= htmlspecialchars($order['phone'] ?? '') ?></div>
            <div class="od-info-row" style="margin-top: 10px; line-height: 1.5;">
                <?= htmlspecialchars($order['address'] ?? '') ?>
            </div>
        </div>
        
        <div class="od-info-card">
            <h4>THÔNG TIN VẬN CHUYỂN</h4>
            <div class="od-info-row" style="margin-bottom:15px; display:flex; justify-content:space-between;">
                <div>
                    <div style="color:#888; font-size:13px; margin-bottom:5px;">Đơn vị vận chuyển</div>
                    <strong>Nhanh (COD)</strong>
                </div>
                <!-- <div>
                    <div style="color:#888; font-size:13px; margin-bottom:5px;">Mã vận đơn</div>
                    <strong>SPXVN...</strong>
                </div> -->
            </div>
            
            <?php if (!empty($order['note'])): ?>
            <div class="od-info-row" style="margin-top:15px; border-top:1px solid #eee; padding-top:15px;">
                <div style="color:#888; font-size:13px; margin-bottom:5px;">Ghi chú của bạn:</div>
                <div><?= htmlspecialchars($order['note']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="od-products">
        <h4>SẢN PHẨM (<?= count($items) ?>)</h4>
        
        <?php foreach ($items as $item): 
            $imgSelected = !empty($item['variant_image']) ? $item['variant_image'] : $item['product_image'];
            $img = !empty($imgSelected) ? $imgSelected : "../../assets/images/default-avatar.png";
            $variant_arr = [];
            if(!empty($item['color'])) $variant_arr[] = $item['color'];
            if(!empty($item['size'])) $variant_arr[] = $item['size'];
            $variant_str = !empty($variant_arr) ? "Phân loại: " . implode(', ', $variant_arr) : "";
        ?>
        <div class="od-item">
            <img src="<?= $img ?>" alt="" class="od-item-img">
            <div class="od-item-details">
                <div class="od-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="od-item-variant"><?= htmlspecialchars($variant_str) ?></div>
                <div class="od-item-price-qty">
                    <span style="color:#888;">Số lượng: x<?= $item['quantity'] ?></span>
                    <span class="od-item-price"><?= number_format($item['price'], 0, ',', '.') ?> đ</span>
                </div>

                <?php if ($os === 3 && !empty($item['product_id'])): ?>
                    <div style="margin-top:8px; display:flex; gap:8px; justify-content:flex-end;">
                        <button type="button" class="od-action-btn od-btn-outline" style="padding:8px 12px;" data-product-id="<?= htmlspecialchars($item['product_id']) ?>" data-product-name="<?= htmlspecialchars($item['product_name']) ?>" onclick="openReviewModal(this)">Đánh giá</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="od-summary">
            <div class="od-summary-inner">
                <div class="od-summary-row">
                    <span>Tổng tiền hàng:</span>
                    <span><?= number_format($order['total_price'] - $order['shipping_fee'], 0, ',', '.') ?> đ</span>
                </div>
                <div class="od-summary-row">
                    <span>Phí vận chuyển:</span>
                    <span><?= number_format($order['shipping_fee'], 0, ',', '.') ?> đ</span>
                </div>
                <?php if (!empty($order['discount_value']) && floatval($order['discount_value']) > 0): ?>
                <div class="od-summary-row" style="color:#2e7d32;">
                    <span>Giảm giá (voucher):</span>
                    <span>-<?= number_format($order['discount_value'], 0, ',', '.') ?> đ</span>
                </div>
                <?php endif; ?>
                <?php if ($order['wallet_used_amount'] > 0): ?>
                <div class="od-summary-row">
                    <span>Sử dụng ví:</span>
                    <span style="color:#e74c3c;">-<?= number_format($order['wallet_used_amount'], 0, ',', '.') ?> đ</span>
                </div>
                <?php endif; ?>
                <div class="od-summary-row total">
                    <span>Tổng số tiền:</span>
                    <span><?= number_format($order['final_price'], 0, ',', '.') ?> đ</span>
                </div>
            </div>
        </div>

        <?php
        // ── Thông báo từ chối trả hàng (status = 3 sau khi bị reject, có admin_note) ──
        // Khi admin reject_return → order_status = 3, admin_note = lý do từ chối
        $return_rejected_info = null;
        if ($os === 3 && !empty($order['admin_note'])) {
            // Kiểm tra trong order_returns xem có record bị từ chối không
            try {
                $stmt_ret = $conn->prepare("SELECT * FROM order_returns WHERE order_id = :oid ORDER BY created_at DESC LIMIT 1");
                $stmt_ret->execute(['oid' => $order_id]);
                $ret_record = $stmt_ret->fetch(PDO::FETCH_ASSOC);
                if ($ret_record && (int)$ret_record['status'] === 2) {
                    $return_rejected_info = $ret_record;
                }
            } catch (PDOException $e) {}
        }
        ?>

        <?php if ($return_rejected_info): ?>
        <div style="background:#fdf0ef; border:1px solid #e74c3c; padding:16px 20px; border-radius:8px; margin-top:20px; font-size:14px; color:#7b241c;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <i class="fa-solid fa-circle-xmark" style="font-size:18px; color:#e74c3c;"></i>
                <strong>Yêu cầu trả hàng bị từ chối</strong>
            </div>
            <div><strong>Lý do từ chối:</strong> <?= htmlspecialchars($order['admin_note']) ?></div>
            <?php if (!empty($return_rejected_info['reason'])): ?>
            <div style="margin-top:6px; color:#555; font-size:13px;">Lý do bạn yêu cầu trả: <?= htmlspecialchars($return_rejected_info['reason']) ?></div>
            <?php endif; ?>
            <div style="margin-top:10px; font-size:12px; color:#999;">Nếu có thắc mắc, vui lòng liên hệ bộ phận hỗ trợ của NTK Fashion.</div>
        </div>
        <?php endif; ?>

        <!-- ── NÚT HÀNH ĐỘNG ĐƠN HÀNG ── -->
        <div style="margin-top:24px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; align-items:center;">

            <?php if ($os === 2): ?>
                <!-- Đang giao: Xác nhận đã nhận hàng -->
                <a href="../../controllers/mark_received.php?id=<?= $order['order_id'] ?>"
                   class="od-action-btn od-btn-teal"
                   onclick="return confirm('Bạn xác nhận đã nhận được hàng và hàng không có vấn đề gì chứ?')">
                    <i class="fa-solid fa-check"></i> Đã nhận được hàng
                </a>
            <?php endif; ?>

            <?php if ($os === 3): ?>
                <!-- Hoàn thành: Trả hàng + Mua lại -->
                <button class="od-action-btn od-btn-outline"
                    onclick="document.getElementById('return-modal-detail').style.display='flex'">
                    <i class="fa-solid fa-rotate-left"></i> Trả hàng
                </button>
                <a href="../../controllers/buy_again.php?id=<?= htmlspecialchars($order['order_id']) ?>" class="od-action-btn od-btn-primary">
                    Mua lại
                </a>
            <?php elseif ($os === 4): ?>
                <!-- Đã hủy: Mua lại -->
                <a href="../../controllers/buy_again.php?id=<?= htmlspecialchars($order['order_id']) ?>" class="od-action-btn od-btn-primary">
                    Mua lại
                </a>
            <?php endif; ?>

            <?php if ($os === 0 || $os === 1): ?>
                <?php if ($os === 0 && !$is_paid): ?>
                <!-- Chờ thanh toán: Thanh toán ngay -->
                <a href="../../order_success.php?id=<?= htmlspecialchars($order['order_id']) ?>&method=online" class="od-action-btn od-btn-primary">
                    <i class="fa-solid fa-credit-card" style="margin-right:5px;"></i> Thanh toán ngay
                </a>
                <?php endif; ?>
                <!-- Chờ/Đang xử lý: Hủy đơn -->
                <a href="#"
                   onclick="var r = prompt('Vui lòng nhập lý do hủy đơn hàng:'); if(r){ window.location.href='../../controllers/cancel_order.php?id=<?= $order['order_id'] ?>&reason=' + encodeURIComponent(r); } return false;"
                   class="od-action-btn od-btn-danger">
                    Hủy đơn
                </a>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal đánh giá sản phẩm -->
<div id="review-modal-detail" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; border-radius:10px; width:100%; max-width:520px; padding:26px; box-shadow:0 16px 40px rgba(0,0,0,0.18); position:relative;">
        <button type="button" onclick="closeReviewModalDetail()" style="position:absolute; top:12px; right:12px; border:none; background:transparent; font-size:18px; color:#555; cursor:pointer;">&times;</button>
        <h3 style="margin:0 0 16px; font-size:20px; color:#222;">Đánh giá sản phẩm</h3>
        <p id="review-modal-product-detail" style="margin:0 0 18px; color:#555; font-size:14px;"></p>

        <form id="review-form-detail" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_comment">
            <input type="hidden" name="product_id" id="review-product-id-detail" value="">
            <input type="hidden" name="parent_id" value="">

            <label for="review-rating-detail" style="display:block; font-weight:600; margin-bottom:8px; color:#333;">Số sao</label>
            <select id="review-rating-detail" name="rating" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; margin-bottom:16px; font-size:14px;">
                <option value="">Chọn đánh giá</option>
                <option value="1">1 sao</option>
                <option value="2">2 sao</option>
                <option value="3">3 sao</option>
                <option value="4">4 sao</option>
                <option value="5">5 sao</option>
            </select>

            <label for="review-comment-detail" style="display:block; font-weight:600; margin-bottom:8px; color:#333;">Nhận xét</label>
            <textarea id="review-comment-detail" name="comment" rows="4" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; resize:vertical; font-size:14px; margin-bottom:16px;"></textarea>

            <label for="review-image-detail" style="display:block; font-weight:600; margin-bottom:8px; color:#333;">Hình ảnh (tùy chọn)</label>
            <input type="file" id="review-image-detail" name="review_image" accept="image/*" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; margin-bottom:10px;">
            <div id="review-image-preview-detail" style="display:none; margin-bottom:16px;">
                <img src="" alt="Preview" style="max-width:100%; border-radius:8px; border:1px solid #eee;">
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;">
                <button type="button" onclick="closeReviewModalDetail()" style="flex:1; min-width:120px; padding:12px 16px; border:1px solid #ccc; background:#fff; border-radius:6px; color:#333; cursor:pointer;">Hủy</button>
                <button type="submit" class="od-action-btn od-btn-primary" style="flex:1; min-width:120px;">Gửi đánh giá</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Trả hàng (chỉ cho status=3) -->
<?php if ($os === 3): ?>
<div id="return-modal-detail"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:30px; max-width:480px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
        <h3 style="margin:0 0 20px; font-size:18px; color:#333;">Yêu cầu Trả hàng / Hoàn tiền</h3>
        <p style="font-size:14px; color:#666; margin-bottom:20px;">Đơn hàng: <strong>#<?= $order['order_id'] ?></strong></p>

        <form action="../../controllers/return_order.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">

            <label style="display:block; font-size:14px; font-weight:600; margin-bottom:8px; color:#333;">Lý do trả hàng <span style="color:#e74c3c;">*</span></label>
            <select name="reason" required
                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; margin-bottom:16px;">
                <option value="">-- Chọn lý do --</option>
                <option value="Hàng bị lỗi / hư hỏng">Hàng bị lỗi / hư hỏng</option>
                <option value="Sai sản phẩm / sai màu / sai size">Sai sản phẩm / sai màu / sai size</option>
                <option value="Hàng không đúng mô tả">Hàng không đúng mô tả</option>
                <option value="Hàng bị thiếu, còn thiếu phụ kiện">Hàng bị thiếu, còn thiếu phụ kiện</option>
                <option value="Đổi ý, không muốn mua nữa">Đổi ý, không muốn mua nữa</option>
            </select>

            <label style="display:block; font-size:14px; font-weight:600; margin-bottom:8px; color:#333;">Ảnh / Video bằng chứng</label>
            <input type="file" name="return_image" accept="image/*,video/*"
                   style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; font-size:13px; margin-bottom:16px;">
            <p style="font-size:12px; color:#999; margin-top:-12px; margin-bottom:16px;">Hỗ trợ: JPG, PNG, GIF, MP4 (tối đa 10MB)</p>

            <div style="display:flex; gap:12px; margin-top:8px;">
                <button type="button"
                        onclick="document.getElementById('return-modal-detail').style.display='none'"
                        style="flex:1; padding:11px; border:1px solid #ccc; background:#fff; border-radius:4px; font-size:14px; cursor:pointer;">
                    Hủy bỏ
                </button>
                <button type="submit"
                        style="flex:1; padding:11px; background:#ee4d2d; color:#fff; border:none; border-radius:4px; font-size:14px; font-weight:600; cursor:pointer;">
                    Gửi yêu cầu
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    function openReviewModal(button) {
        var productId = button.getAttribute('data-product-id');
        var productName = button.getAttribute('data-product-name');
        document.getElementById('review-product-id-detail').value = productId;
        document.getElementById('review-modal-product-detail').innerText = 'Sản phẩm: ' + productName;
        document.getElementById('review-modal-detail').style.display = 'flex';
        document.getElementById('review-form-detail').reset();
        document.getElementById('review-image-preview-detail').style.display = 'none';
    }

    function closeReviewModalDetail() {
        document.getElementById('review-modal-detail').style.display = 'none';
    }

    document.getElementById('review-image-detail').addEventListener('change', function () {
        var file = this.files[0];
        var preview = document.querySelector('#review-image-preview-detail img');
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                document.getElementById('review-image-preview-detail').style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('review-image-preview-detail').style.display = 'none';
            preview.src = '';
        }
    });

    document.getElementById('review-form-detail').addEventListener('submit', function (e) {
        e.preventDefault();
        var form = e.currentTarget;
        var formData = new FormData(form);

        fetch('../../ajax_review.php', {
            method: 'POST',
            body: formData
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                alert('Cảm ơn bạn! Đánh giá đã được gửi.');
                if (formData.get('product_id')) {
                    window.location.href = '../../product_detail.php?id=' + encodeURIComponent(formData.get('product_id')) + '&open_review=0';
                } else {
                    closeReviewModalDetail();
                }
            } else {
                alert(data.message || 'Không thể gửi đánh giá. Vui lòng thử lại.');
            }
        })
        .catch(function () {
            alert('Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại.');
        });
    });
</script>

<style>
.od-action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 20px; border-radius: 4px; font-size: 14px;
    font-weight: 500; cursor: pointer; text-decoration: none;
    border: 1px solid transparent; transition: 0.2s;
    white-space: nowrap;
}
.od-btn-teal   { background: #26aa99; color: #fff; border-color: #26aa99; }
.od-btn-teal:hover { background: #1f8c7d; }
.od-btn-outline { background: #fff; color: #555; border-color: #ccc; }
.od-btn-outline:hover { border-color: #888; color: #333; }
.od-btn-primary { background: #ee4d2d; color: #fff; border-color: #ee4d2d; font-weight: 700; }
.od-btn-primary:hover { background: #d73211; }
.od-btn-danger  { background: #fff; color: #e74c3c; border-color: #e74c3c; }
.od-btn-danger:hover { background: #fdf0ef; }
</style>

