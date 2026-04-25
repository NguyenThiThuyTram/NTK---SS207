<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header("Location: orders.php");
    exit;
}

// Handle action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'update_status') {
            $new_status = (int)$_POST['new_status'];
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
            $stmt->execute([$new_status, $id]);
        } elseif ($action === 'cancel_order') {
            $stmt = $conn->prepare("UPDATE orders SET order_status = 4 WHERE order_id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'approve_return') {
            // Update order status to 4 (Cancel/Refund) and return status to 1 (Approved)
            $stmt = $conn->prepare("UPDATE order_returns SET status = 1 WHERE order_id = ?");
            $stmt->execute([$id]);
            $stmt = $conn->prepare("UPDATE orders SET order_status = 4 WHERE order_id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'reject_return') {
            // Reject return request
            $stmt = $conn->prepare("UPDATE order_returns SET status = 2 WHERE order_id = ?");
            $stmt->execute([$id]);
            // Revert order status to 3 (Completed) since they can only return after delivery
            $stmt = $conn->prepare("UPDATE orders SET order_status = 3 WHERE order_id = ?");
            $stmt->execute([$id]);
        }
    }
    // Redirect to prevent form resubmission
    header("Location: order_detail.php?id=" . urlencode($id));
    exit;
}

// Fetch order
$stmt = $conn->prepare("
    SELECT o.*, u.email, sm.name as shipping_method_name 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.shipping_method_id
    WHERE o.order_id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Không tìm thấy đơn hàng.";
    exit;
}

// Fetch order details
$stmt_det = $conn->prepare("
    SELECT od.*, pv.image as variant_img, pv.color, pv.size, p.image as product_img, p.name as p_name
    FROM order_details od
    LEFT JOIN product_variants pv ON od.variant_id = pv.variant_id
    LEFT JOIN products p ON pv.product_id = p.product_id
    WHERE od.order_id = ?
");
$stmt_det->execute([$id]);
$details = $stmt_det->fetchAll(PDO::FETCH_ASSOC);

// Fetch return request if any
$stmt_ret = $conn->prepare("SELECT * FROM order_returns WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt_ret->execute([$id]);
$returnReq = $stmt_ret->fetch(PDO::FETCH_ASSOC);

// Subtotal calculation
$subtotal = 0;
foreach ($details as $d) {
    $subtotal += ($d['unit_price'] * $d['quantity']);
}

// Payment method mapping
$paymentMethodMap = [
    1 => 'Thẻ tín dụng / Ghi nợ',
    2 => 'Thanh toán khi nhận hàng (COD)',
    3 => 'Chuyển khoản ngân hàng (PayOS)',
    4 => 'Ví điện tử'
];
$pm = $paymentMethodMap[$order['payment_method']] ?? 'Chưa xác định';

// Status mapping
$statusMap = [
    0 => ['class' => 'badge-warning', 'text' => 'Chờ xác nhận', 'step' => 1],
    1 => ['class' => 'badge-info', 'text' => 'Đang xử lý', 'step' => 2],
    2 => ['class' => 'badge-primary', 'text' => 'Đang giao', 'step' => 3],
    3 => ['class' => 'badge-success', 'text' => 'Hoàn thành', 'step' => 4],
    4 => ['class' => 'badge-danger', 'text' => 'Đã hủy', 'step' => 0],
    5 => ['class' => 'badge-danger', 'text' => 'Trả hàng', 'step' => 4]
];
$statusInfo = $statusMap[$order['order_status']] ?? $statusMap[0];

$admin_current_page = 'orders.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e5e5;
    }
    .page-title {
        font-size: 24px;
        font-weight: 600;
        color: #111;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .btn-back-link {
        font-size: 14px;
        color: #555;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }
    .btn-back-link:hover { color: #111; }

    /* Layout */
    .detail-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 992px) {
        .detail-grid { grid-template-columns: 1fr; }
    }

    /* Panels */
    .panel {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .panel-title {
        font-size: 16px;
        font-weight: 600;
        color: #111;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f5f1eb;
    }

    /* Info Rows */
    .info-row {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        font-size: 14px;
    }
    .info-icon {
        width: 24px;
        color: #888;
        font-size: 14px;
    }
    .info-label {
        width: 160px;
        color: #555;
    }
    .info-val {
        color: #111;
        font-weight: 500;
    }

    /* Table */
    .prod-table {
        width: 100%;
        border-collapse: collapse;
    }
    .prod-table th {
        font-size: 12px;
        color: #888;
        font-weight: 600;
        text-transform: uppercase;
        text-align: left;
        padding-bottom: 12px;
        border-bottom: 1px solid #e5e5e5;
    }
    .prod-table td {
        padding: 16px 0;
        border-bottom: 1px solid #f5f1eb;
        vertical-align: middle;
        font-size: 14px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
        color: #555;
    }
    .summary-total {
        font-size: 18px;
        font-weight: 700;
        color: #111;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e5e5;
    }

    /* Timeline */
    .timeline {
        position: relative;
        padding-left: 20px;
        margin-top: 16px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 4px;
        top: 8px;
        bottom: 8px;
        width: 2px;
        background: #e5e5e5;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 24px;
    }
    .timeline-item:last-child { margin-bottom: 0; }
    .timeline-dot {
        position: absolute;
        left: -20px;
        top: 4px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #e5e5e5;
        border: 2px solid #fff;
    }
    .timeline-item.active .timeline-dot { background: #27ae60; }
    .timeline-title {
        font-size: 14px;
        font-weight: 600;
        color: #111;
        margin-bottom: 4px;
    }
    .timeline-desc { font-size: 13px; color: #888; }

    /* Badges */
    .badge {
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 600;
    }
    .badge-warning { background: #fdf5e6; color: #f39c12; }
    .badge-info { background: #eaf2fd; color: #3498db; }
    .badge-primary { background: #e5f0ff; color: #0066cc; }
    .badge-success { background: #eafaf1; color: #27ae60; }
    .badge-danger { background: #fdf0ef; color: #c0392b; }

    /* Return Box */
    .return-box {
        border: 1px solid #f39c12;
        border-radius: 8px;
        padding: 24px;
        background: #fff;
    }
    .return-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #d35400;
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #fdf5e6;
    }
    .btn-return-action {
        padding: 10px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        flex: 1;
        text-align: center;
        border: 1px solid transparent;
        transition: 0.2s;
    }
    .btn-approve { background: #27ae60; color: #fff; }
    .btn-approve:hover { background: #219653; }
    .btn-reject { background: #fff; color: #c0392b; border-color: #c0392b; }
    .btn-reject:hover { background: #fdf0ef; }

    /* Buttons */
    .btn-outline {
        padding: 8px 16px;
        background: #fff;
        border: 1px solid #555;
        color: #555;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-outline:hover { background: #f5f5f5; color: #111; border-color: #111; }
    .btn-danger-outline {
        padding: 8px 16px;
        background: #fff;
        border: 1px solid #c0392b;
        color: #c0392b;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-danger-outline:hover { background: #fdf0ef; }
</style>

<a href="orders.php" class="btn-back-link"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách đơn hàng</a>

<div class="page-header">
    <div class="page-title">
        Đơn hàng #<?= htmlspecialchars($order['order_id']) ?>
        <span class="badge <?= $statusInfo['class'] ?>"><?= $statusInfo['text'] ?></span>
    </div>
    <div style="display: flex; gap: 12px;">
        <form action="" method="POST" style="margin:0;">
            <input type="hidden" name="action" value="update_status">
            <select name="new_status" class="btn-outline" style="appearance: auto; outline:none;" onchange="this.form.submit()">
                <option value="">Cập nhật trạng thái</option>
                <option value="0">Chờ xác nhận</option>
                <option value="1">Đang xử lý</option>
                <option value="2">Đang giao</option>
                <option value="3">Hoàn thành</option>
            </select>
        </form>
        <?php if ($order['order_status'] != 4 && $order['order_status'] != 3): ?>
        <form action="" method="POST" style="margin:0;" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');">
            <input type="hidden" name="action" value="cancel_order">
            <button type="submit" class="btn-danger-outline">Hủy đơn</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="detail-grid">
    <!-- Cột Trái -->
    <div>
        <!-- Thông tin khách hàng -->
        <div class="panel">
            <div class="panel-title">Thông tin khách hàng</div>
            <div class="info-row">
                <i class="fa-regular fa-user info-icon"></i>
                <span class="info-label">Tên:</span>
                <span class="info-val"><?= htmlspecialchars($order['fullname']) ?></span>
            </div>
            <div class="info-row">
                <i class="fa-solid fa-phone info-icon"></i>
                <span class="info-label">Số điện thoại:</span>
                <span class="info-val"><?= htmlspecialchars($order['phone']) ?></span>
            </div>
            <div class="info-row">
                <i class="fa-regular fa-envelope info-icon"></i>
                <span class="info-label">Email:</span>
                <span class="info-val"><?= htmlspecialchars($order['email'] ?? 'Không có') ?></span>
            </div>
            <div class="info-row" style="align-items: flex-start;">
                <i class="fa-solid fa-location-dot info-icon" style="margin-top: 4px;"></i>
                <span class="info-label">Địa chỉ giao hàng:</span>
                <span class="info-val" style="flex:1; line-height: 1.4;"><?= htmlspecialchars($order['address']) ?></span>
            </div>
        </div>

        <!-- Thông tin đơn hàng -->
        <div class="panel">
            <div class="panel-title">Thông tin đơn hàng</div>
            <div class="info-row">
                <i class="fa-regular fa-calendar info-icon"></i>
                <span class="info-label">Ngày đặt:</span>
                <span class="info-val"><?= date('Y-m-d H:i', strtotime($order['order_date'])) ?></span>
            </div>
            <div class="info-row">
                <i class="fa-regular fa-credit-card info-icon"></i>
                <span class="info-label">Phương thức thanh toán:</span>
                <span class="info-val"><?= $pm ?></span>
            </div>
            <div class="info-row">
                <i class="fa-solid fa-dollar-sign info-icon"></i>
                <span class="info-label">Trạng thái thanh toán:</span>
                <span class="info-val" style="color: <?= $order['payment_status'] == 1 ? '#27ae60' : '#888' ?>;">
                    <?= $order['payment_status'] == 1 ? 'Đã thanh toán' : 'Chưa thanh toán' ?>
                </span>
            </div>
            <?php if (!empty($order['note'])): ?>
            <div class="info-row" style="align-items: flex-start; margin-top:16px;">
                <i class="fa-regular fa-comment info-icon" style="margin-top: 4px;"></i>
                <span class="info-label">Ghi chú:</span>
                <span class="info-val" style="flex:1; font-style: italic; color:#555;"><?= htmlspecialchars($order['note']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="panel">
            <div class="panel-title">Danh sách sản phẩm</div>
            <table class="prod-table">
                <thead>
                    <tr>
                        <th>Sản Phẩm</th>
                        <th style="text-align: center;">Size</th>
                        <th style="text-align: center;">Số Lượng</th>
                        <th style="text-align: right;">Giá</th>
                        <th style="text-align: right;">Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $d): ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <?php 
                                    $img = $d['variant_img'] ?: $d['product_img'];
                                    if ($img): 
                                        $img_src = (strpos($img, 'http') === 0) ? $img : '../' . $img;
                                ?>
                                    <img src="<?= htmlspecialchars($img_src) ?>" style="width:48px; height:48px; border-radius:6px; object-fit:cover;" onerror="this.outerHTML='<div style=\'width:48px;height:48px;background:#f5f1eb;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#ccc;\'><i class=\'fa-solid fa-box\'></i></div>'">
                                <?php else: ?>
                                    <div style="width:48px; height:48px; border-radius:6px; background:#f5f1eb; display:flex; align-items:center; justify-content:center; color:#ccc;"><i class="fa-solid fa-box"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 500; color:#111; margin-bottom:4px;"><?= htmlspecialchars($d['p_name'] ?? $d['product_name']) ?></div>
                                    <?php if(!empty($d['color'])): ?>
                                    <div style="font-size:12px; color:#888;">Màu: <?= htmlspecialchars($d['color']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: center; color:#555;"><?= htmlspecialchars($d['size'] ?? '-') ?></td>
                        <td style="text-align: center; font-weight:500;"><?= (int)$d['quantity'] ?></td>
                        <td style="text-align: right; color:#555;"><?= number_format($d['unit_price'], 0, ',', '.') ?> đ</td>
                        <td style="text-align: right; font-weight:600; color:#111;"><?= number_format($d['unit_price'] * $d['quantity'], 0, ',', '.') ?> đ</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top: 24px; max-width: 300px; margin-left: auto;">
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <span style="font-weight:500; color:#111;"><?= number_format($subtotal, 0, ',', '.') ?> đ</span>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển:</span>
                    <span style="font-weight:500; color:#111;"><?= number_format($order['shipping_fee'] ?? 0, 0, ',', '.') ?> đ</span>
                </div>
                <?php if ($order['discount_value'] > 0): ?>
                <div class="summary-row">
                    <span>Giảm giá:</span>
                    <span style="font-weight:500; color:#c0392b;">-<?= number_format($order['discount_value'], 0, ',', '.') ?> đ</span>
                </div>
                <?php endif; ?>
                <?php if ($order['wallet_used_amount'] > 0): ?>
                <div class="summary-row">
                    <span>Thanh toán từ ví:</span>
                    <span style="font-weight:500; color:#c0392b;">-<?= number_format($order['wallet_used_amount'], 0, ',', '.') ?> đ</span>
                </div>
                <?php endif; ?>
                <div class="summary-row summary-total">
                    <span>Tổng tiền:</span>
                    <span style="color:#2f1c00;"><?= number_format($order['final_price'], 0, ',', '.') ?> đ</span>
                </div>
            </div>
        </div>

        <!-- Yêu cầu trả hàng (If exists or if order_status == 5) -->
        <?php if ($returnReq || $order['order_status'] == 5): ?>
        <div class="return-box">
            <div class="return-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>Yêu cầu trả hàng</span>
                </div>
                <?php 
                    $retStatusTxt = "Chờ xử lý";
                    if ($returnReq && $returnReq['status'] == 1) $retStatusTxt = "Đã duyệt";
                    if ($returnReq && $returnReq['status'] == 2) $retStatusTxt = "Đã từ chối";
                ?>
                <span class="badge" style="background:#fdf5e6; color:#f39c12; font-size:12px;"><?= $retStatusTxt ?></span>
            </div>
            
            <div style="font-size:14px; margin-bottom:12px;">
                <div style="color:#888; margin-bottom:4px;">Lý do trả hàng:</div>
                <div style="color:#111; font-weight:500;"><?= htmlspecialchars($returnReq['reason'] ?? 'Khách hàng yêu cầu trả hàng.') ?></div>
            </div>

            <?php if (!empty($returnReq['image_proof'])): ?>
            <div style="font-size:14px; margin-bottom:16px;">
                <div style="color:#888; margin-bottom:4px;">Hình ảnh minh chứng:</div>
                <img src="<?= htmlspecialchars((strpos($returnReq['image_proof'], 'http')===0) ? $returnReq['image_proof'] : '../'.$returnReq['image_proof']) ?>" style="width:100px; height:100px; border-radius:6px; object-fit:cover; border:1px solid #e5e5e5;">
            </div>
            <?php endif; ?>

            <div style="font-size:14px; margin-bottom:24px;">
                <div style="color:#888; margin-bottom:4px;">Ngày yêu cầu:</div>
                <div style="color:#111; font-weight:500;"><?= date('Y-m-d H:i', strtotime($returnReq['created_at'] ?? $order['order_date'])) ?></div>
            </div>

            <?php if (!$returnReq || $returnReq['status'] == 0): ?>
            <div style="display:flex; gap:16px;">
                <form action="" method="POST" style="flex:1; margin:0;">
                    <input type="hidden" name="action" value="approve_return">
                    <button type="submit" class="btn-return-action btn-approve" style="width:100%;">Duyệt trả hàng</button>
                </form>
                <form action="" method="POST" style="flex:1; margin:0;" onsubmit="return confirm('Bạn có chắc chắn từ chối yêu cầu này?');">
                    <input type="hidden" name="action" value="reject_return">
                    <button type="submit" class="btn-return-action btn-reject" style="width:100%;">Từ chối</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Cột Phải -->
    <div>
        <!-- Timeline -->
        <div class="panel">
            <div class="panel-title">Timeline trạng thái</div>
            <div class="timeline">
                
                <div class="timeline-item active">
                    <div class="timeline-dot"></div>
                    <div class="timeline-title">Đơn hàng đã đặt</div>
                    <div class="timeline-desc">Khách hàng đã đặt đơn hàng<br><?= date('Y-m-d H:i', strtotime($order['order_date'])) ?></div>
                </div>

                <?php if ($order['order_status'] != 4): // Not cancelled ?>
                    <div class="timeline-item <?= $statusInfo['step'] >= 1 ? 'active' : '' ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-title">Đơn hàng đã được xác nhận</div>
                        <div class="timeline-desc">Admin đã xác nhận đơn hàng</div>
                    </div>

                    <div class="timeline-item <?= $statusInfo['step'] >= 2 ? 'active' : '' ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-title">Đơn hàng đã được đóng gói</div>
                        <div class="timeline-desc">Đơn hàng đã được đóng gói và sẵn sàng giao</div>
                    </div>

                    <div class="timeline-item <?= $statusInfo['step'] >= 3 ? 'active' : '' ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-title">Đang vận chuyển</div>
                        <div class="timeline-desc">Đơn hàng đang được giao đến khách hàng</div>
                    </div>

                    <div class="timeline-item <?= $statusInfo['step'] >= 4 ? 'active' : '' ?>" style="margin-bottom:0;">
                        <div class="timeline-dot"></div>
                        <div class="timeline-title">Hoàn thành</div>
                        <div class="timeline-desc">Đơn hàng đã giao thành công</div>
                    </div>
                <?php else: ?>
                    <div class="timeline-item active" style="margin-bottom:0;">
                        <div class="timeline-dot" style="background:#c0392b;"></div>
                        <div class="timeline-title" style="color:#c0392b;">Đơn hàng đã bị hủy</div>
                        <div class="timeline-desc">Đơn hàng này không còn hiệu lực</div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
        
        <!-- Lịch sử hoạt động -->
        <div class="panel">
            <div class="panel-title">Lịch sử hoạt động</div>
            <div style="font-size:13px; color:#555; line-height:1.6;">
                <?php if($order['order_status'] == 4): ?>
                    <div style="margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #f5f1eb;">
                        <strong style="color:#111;">Đã hủy đơn hàng</strong><br>
                        <?= date('Y-m-d H:i') ?>
                    </div>
                <?php endif; ?>
                <div style="margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #f5f1eb;">
                    <strong style="color:#111;">Khách hàng đặt hàng</strong><br>
                    <?= date('Y-m-d H:i', strtotime($order['order_date'])) ?>
                </div>
            </div>
        </div>

    </div>
</div>

</div>
</main>
</body>
</html>
