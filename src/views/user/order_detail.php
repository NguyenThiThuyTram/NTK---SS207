<?php
// order_detail.php - Included inside dashboard.php
$order_id = $_GET['id'] ?? null;
if (!$order_id) {
    echo "<h3>Không tìm thấy đơn hàng.</h3>";
    return;
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM Orders WHERE order_id = :oid AND user_id = :uid");
$stmt->execute(['oid' => $order_id, 'uid' => $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<h3>Đơn hàng không tồn tại hoặc bạn không có quyền xem.</h3>";
    return;
}

// Lấy chi tiết sản phẩm
$stmt_items = $conn->prepare("
    SELECT od.*, p.image AS product_image, v.image AS variant_image, v.color, v.size 
    FROM Order_Details od
    LEFT JOIN Product_Variants v ON od.variant_id = v.variant_id
    LEFT JOIN Products p ON v.product_id = p.product_id
    WHERE od.order_id = :oid
");
$stmt_items->execute(['oid' => $order_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Xác định trạng thái để hiển thị Progress
// 0: Chờ thanh toán (nếu online chưa pass / COD: pending_payment nếu có), 1: Đang xử lý, 2: Đang giao, 3: Hoàn thành, 4: Đã hủy
$status = (int)$order['order_status']; 
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
</style>

<div class="od-container">
    <div class="od-header">
        <a href="dashboard.php?view=donmua"><i class="fa-solid fa-arrow-left"></i> TRỞ LẠI</a>
        <div style="font-size: 14px; color: #555;">MÃ ĐƠN HÀNG: <strong><?= $order['order_id'] ?></strong></div>
    </div>

    <!-- Thanh trạng thái (giả lập dựa trên order_status) -->
    <div class="od-status-bar">
        <?php 
            $is_cod = ($order['payment_method'] == 1);
            $is_paid = ($order['payment_status'] == 1);
            
            $is_shipping = ($status >= 2);
            $is_completed = ($status == 3);

            // Nếu COD -> Lúc nào cũng "Đã đặt". Nếu PayOS -> "Đã đặt" chỉ sáng nếu đã trả tiền
            $is_placed = $is_cod || $is_paid;
            
            // Visual for "Đã xác nhận thanh toán" - COD sẽ sáng lên khi hoàn thành (nhận hàng)
            $is_paid_visual = $is_paid || $is_completed;
        ?>
        <div class="od-step <?= $is_placed ? 'active' : '' ?>">
            <div class="od-step-icon"><i class="fa-solid fa-receipt"></i></div>
            <div class="od-step-label">Đơn hàng đã đặt</div>
            <div class="od-step-date"><?= $is_placed ? $date_placed : 'Chờ chuyển khoản' ?></div>
        </div>

        <div class="od-step <?= $is_paid_visual ? 'active' : '' ?>">
            <div class="od-step-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            <div class="od-step-label">Đã xác nhận thanh toán</div>
            <div class="od-step-date"><?= $is_paid_visual ? $date_placed : '-' ?></div>
        </div>

        <div class="od-step <?= $is_shipping ? 'active' : '' ?>">
            <div class="od-step-icon"><i class="fa-solid fa-truck"></i></div>
            <div class="od-step-label">Đã giao đơn vị VC</div>
            <div class="od-step-date"><?= $is_shipping ? '-' : '-' ?></div>
        </div>

        <div class="od-step <?= $is_completed ? 'active' : '' ?>">
            <div class="od-step-icon"><i class="fa-solid fa-box-open"></i></div>
            <div class="od-step-label">Đã nhận được hàng</div>
            <div class="od-step-date"><?= $is_completed ? '-' : '-' ?></div>
        </div>

        <div class="od-step <?= $is_completed ? 'active' : '' ?>">
            <div class="od-step-icon"><i class="fa-solid fa-star"></i></div>
            <div class="od-step-label">Đơn hàng hoàn thành</div>
            <div class="od-step-date"><?= $is_completed ? '-' : '-' ?></div>
        </div>
    </div>

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

        <?php if ($status == 0 || $status == 1): ?>
        <div style="text-align:right; margin-top:20px;">
            <a href="../../controllers/cancel_order.php?id=<?= $order['order_id'] ?>" 
               onclick="return confirm('Bạn có chắc chắn muốn hủy (và hoàn tiền nếu có) đơn hàng này không?')" 
               style="padding:10px 25px; background:#fff; border:1px solid #ee4d2d; color:#ee4d2d; text-decoration:none; border-radius:2px; display:inline-block; font-size:14px;">
               Hủy đơn
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
