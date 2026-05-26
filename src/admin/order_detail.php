<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header("Location: orders.php");
    exit;
}

// Handle action - chuyển hướng sang admin_order_action.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Chuyển hướng sang controller riêng để xử lý
    header("Location: admin_order_action.php?id=" . urlencode($id));
    exit;
}

// Hiển thị thông báo thành công (nếu có)
$success_msg = $_GET['success'] ?? '';

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

// Payment method mapping (1=COD, 2=Online/PayOS)
$paymentMethodMap = [
    1 => 'Thanh toán khi nhận hàng (COD)',
    2 => 'Chuyển khoản / PayOS (Online)',
];
$pm = $paymentMethodMap[$order['payment_method']] ?? 'Chưa xác định';

// Status mapping đầy đủ theo luồng nghiệp vụ
// step: số bước đã hoàn thành trong timeline chuẩn (0=đặt hàng, 1=chờ lấy hàng, 2=đang giao, 3=hoàn thành)
// step=-1 nghĩa là nhánh đặc biệt (hủy/trả/thất bại) → dùng timeline riêng
$statusMap = [
    0  => ['class' => 'badge-warning', 'text' => 'Chờ thanh toán',         'step' => 0],
    1  => ['class' => 'badge-info',    'text' => 'Chờ lấy hàng',           'step' => 1],
    2  => ['class' => 'badge-primary', 'text' => 'Đang giao hàng',         'step' => 2],
    3  => ['class' => 'badge-success', 'text' => 'Hoàn thành',             'step' => 3],
    4  => ['class' => 'badge-danger',  'text' => 'Đã hủy',                 'step' => -1],
    5  => ['class' => 'badge-danger',  'text' => 'Yêu cầu trả hàng',      'step' => -2],
    6  => ['class' => 'badge-warning', 'text' => 'Đang hoàn trả hàng',    'step' => -2],
    7  => ['class' => 'badge-success', 'text' => 'Đã hoàn tiền',          'step' => -2],
    8  => ['class' => 'badge-warning', 'text' => 'Chờ duyệt hủy',         'step' => -1],
    9  => ['class' => 'badge-danger',  'text' => 'Giao hàng thất bại',    'step' => -3],
    10 => ['class' => 'badge-warning', 'text' => 'Đang hoàn về kho',      'step' => -3],
];
$statusInfo = $statusMap[$order['order_status']] ?? $statusMap[0];
$step = $statusInfo['step'];

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

<?php if (!empty($success_msg)): ?>
<div style="background:#eafaf1; border:1px solid #27ae60; color:#1e8449; padding:12px 16px; border-radius:6px; margin-bottom:20px; font-size:14px;">
    <i class="fa-solid fa-circle-check" style="margin-right:8px;"></i><?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
<div style="background:#fdf0ef; border:1px solid #e74c3c; color:#c0392b; padding:12px 16px; border-radius:6px; margin-bottom:20px; font-size:14px;">
    <i class="fa-solid fa-circle-exclamation" style="margin-right:8px;"></i><?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<div class="page-header">
    <div class="page-title">
        Đơn hàng #<?= htmlspecialchars($order['order_id']) ?>
        <span class="badge <?= $statusInfo['class'] ?>"><?= $statusInfo['text'] ?></span>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items:center;">
        <?php
        $os = (int)$order['order_status'];

        // Nút theo từng trạng thái
        if ($os === 0):
        ?>
            <!-- Chờ thanh toán: Admin xác nhận thanh toán -->
            <form action="admin_order_action.php" method="POST" style="margin:0;">
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="confirm_payment">
                <button type="submit" class="btn-outline" style="color:#27ae60; border-color:#27ae60;" onclick="return confirm('Xác nhận thanh toán cho đơn này?')">
                    <i class="fa-solid fa-circle-check"></i> Xác nhận đã thanh toán
                </button>
            </form>

        <?php elseif ($os === 1): ?>
            <!-- Chờ lấy hàng: Chuẩn bị hàng + nhập mã vận đơn -->
            <button type="button" class="btn-outline" style="color:#2980b9; border-color:#2980b9;"
                onclick="document.getElementById('shipping-form').style.display='block'; this.style.display='none'">
                <i class="fa-solid fa-truck"></i> Bàn giao ĐVVC
            </button>
            <div id="shipping-form" style="display:none;">
                <form action="admin_order_action.php" method="POST" style="display:flex; gap:8px; align-items:center;">
                    <input type="hidden" name="order_id" value="<?= $id ?>">
                    <input type="hidden" name="action" value="prepare_shipping">
                    <input type="text" name="tracking_number" placeholder="Mã vận đơn (tùy chọn)"
                           style="padding:8px 12px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
                    <button type="submit" class="btn-outline" style="color:#2980b9; border-color:#2980b9;" onclick="return confirm('Xác nhận bàn giao hàng cho ĐVVC?')">
                        Xác nhận
                    </button>
                </form>
            </div>

        <?php elseif ($os === 2): ?>
            <!-- Đang giao: Giao thất bại -->
            <form action="admin_order_action.php" method="POST" style="margin:0;" onsubmit="return confirm('Xác nhận giao hàng thất bại?')">
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="delivery_failed">
                <input type="hidden" name="admin_note" value="Shipper không liên lạc được với khách">
                <button type="submit" class="btn-danger-outline">
                    <i class="fa-solid fa-truck-arrow-right"></i> Giao thất bại
                </button>
            </form>

        <?php elseif ($os === 5): ?>
            <!-- Đang yêu cầu trả hàng: Duyệt / Từ chối -->
            <form action="admin_order_action.php" method="POST" style="margin:0;" onsubmit="return confirm('Duyệt yêu cầu trả hàng?')">
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="approve_return">
                <button type="submit" class="btn-approve" style="padding:8px 16px; border-radius:4px; font-size:14px; border:none; cursor:pointer; background:#27ae60; color:#fff;">
                    <i class="fa-solid fa-thumbs-up"></i> Duyệt trả hàng
                </button>
            </form>
            <form action="admin_order_action.php" method="POST" style="margin:0;"
                  onsubmit="var n=prompt('Nhập lý do từ chối:'); if(!n){return false;} this.querySelector('[name=admin_note]').value=n; return true;">
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="reject_return">
                <input type="hidden" name="admin_note" value="">
                <button type="submit" class="btn-danger-outline">
                    <i class="fa-solid fa-ban"></i> Từ chối
                </button>
            </form>

        <?php elseif ($os === 6): ?>
            <!-- Đang hoàn trả hàng: Xác nhận nhận hàng + hoàn tiền -->
            <form action="admin_order_action.php" method="POST" style="margin:0;" onsubmit="return confirm('Xác nhận đã nhận hàng hoàn trả và hoàn tiền cho khách?')">
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="confirm_refund">
                <button type="submit" style="padding:8px 16px; border-radius:4px; font-size:14px; border:none; cursor:pointer; background:#27ae60; color:#fff;">
                    <i class="fa-solid fa-money-bill-wave"></i> Xác nhận hoàn tiền
                </button>
            </form>

        <?php elseif ($os === 8): ?>
            <!-- Chờ duyệt hủy: Đồng ý / Từ chối -->
            <form action="admin_order_action.php" method="POST" style="margin:0;" onsubmit="return confirm('Đồng ý cho hủy đơn này?')">
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="approve_cancel">
                <button type="submit" class="btn-danger-outline">
                    <i class="fa-solid fa-check"></i> Đồng ý hủy
                </button>
            </form>
            <form action="admin_order_action.php" method="POST" style="margin:0;"
                  onsubmit="var n=prompt('Nhập lý do từ chối hủy:'); if(!n){return false;} this.querySelector('[name=admin_note]').value=n; return true;">
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="reject_cancel">
                <input type="hidden" name="admin_note" value="">
                <button type="submit" class="btn-outline">
                    <i class="fa-solid fa-ban"></i> Từ chối hủy
                </button>
            </form>

        <?php elseif ($os === 9): ?>
            <!-- Giao thất bại: Xác nhận nhận lại hàng -->
            <form action="admin_order_action.php" method="POST" style="margin:0;" onsubmit="return confirm('Xác nhận đã nhận lại hàng về kho?')">
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="return_to_warehouse">
                <button type="submit" class="btn-outline" style="color:#7f8c8d; border-color:#7f8c8d;">
                    <i class="fa-solid fa-warehouse"></i> Đã nhận hàng hoàn về kho
                </button>
            </form>
        <?php endif; ?>

        <?php if (in_array($os, [0, 1, 2])): ?>
            <!-- Admin chủ động hủy đơn -->
            <form action="admin_order_action.php" method="POST" style="margin:0;"
                  onsubmit="var n=prompt('Nhập lý do hủy đơn:'); if(!n){return false;} this.querySelector('[name=admin_note]').value=n; return true;">
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="admin_cancel">
                <input type="hidden" name="admin_note" value="">
                <button type="submit" class="btn-danger-outline">
                    <i class="fa-solid fa-trash-can"></i> Hủy đơn
                </button>
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

        <!-- Yêu cầu trả hàng -->
        <?php if ($returnReq || in_array($order['order_status'], [5, 6, 7])): ?>
        <div class="return-box">
            <div class="return-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>Yêu cầu trả hàng / Hoàn tiền</span>
                </div>
                <?php
                    $os_now = (int)$order['order_status'];
                    if ($os_now === 7)     $retStatusTxt = '✅ Đã hoàn tiền';
                    elseif ($os_now === 6) $retStatusTxt = '📦 Đang hoàn trả hàng';
                    elseif ($returnReq && $returnReq['status'] == 1) $retStatusTxt = '✅ Đã duyệt';
                    elseif ($returnReq && $returnReq['status'] == 2) $retStatusTxt = '❌ Đã từ chối';
                    else $retStatusTxt = '⏳ Chờ xử lý';
                ?>
                <span class="badge" style="background:#fdf5e6; color:#f39c12; font-size:12px;"><?= $retStatusTxt ?></span>
            </div>

            <?php if ($returnReq): ?>
            <div style="font-size:14px; margin-bottom:12px;">
                <div style="color:#888; margin-bottom:4px;">Lý do trả hàng:</div>
                <div style="color:#111; font-weight:500;"><?= htmlspecialchars($returnReq['reason']) ?></div>
            </div>
            <?php if (!empty($returnReq['image_proof'])): ?>
            <div style="font-size:14px; margin-bottom:16px;">
                <div style="color:#888; margin-bottom:4px;">Hình ảnh minh chứng:</div>
                <img src="<?= htmlspecialchars((strpos($returnReq['image_proof'], 'http')===0) ? $returnReq['image_proof'] : '../'.$returnReq['image_proof']) ?>" style="width:100px; height:100px; border-radius:6px; object-fit:cover; border:1px solid #e5e5e5;">
            </div>
            <?php endif; ?>
            <div style="font-size:14px; margin-bottom:16px;">
                <div style="color:#888; margin-bottom:4px;">Ngày yêu cầu:</div>
                <div style="color:#111; font-weight:500;"><?= date('d/m/Y H:i', strtotime($returnReq['created_at'])) ?></div>
            </div>
            <?php if (!empty($returnReq['admin_note'])): ?>
            <div style="font-size:14px; margin-bottom:16px; padding:10px; background:#f8f8f8; border-radius:4px;">
                <div style="color:#888; margin-bottom:4px;">Ghi chú Admin:</div>
                <div style="color:#111;"><?= htmlspecialchars($returnReq['admin_note']) ?></div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($os_now === 5 && (!$returnReq || $returnReq['status'] == 0)): ?>
            <div style="display:flex; gap:12px;">
                <form action="admin_order_action.php" method="POST" style="flex:1; margin:0;" onsubmit="return confirm('Duyệt yêu cầu trả hàng?')">
                    <input type="hidden" name="order_id" value="<?= $id ?>">
                    <input type="hidden" name="action" value="approve_return">
                    <button type="submit" class="btn-return-action btn-approve" style="width:100%;">✅ Duyệt trả hàng</button>
                </form>
                <form action="admin_order_action.php" method="POST" style="flex:1; margin:0;"
                      onsubmit="var n=prompt('Nhập lý do từ chối:'); if(!n){return false;} this.querySelector('[name=admin_note]').value=n; return true;">
                    <input type="hidden" name="order_id" value="<?= $id ?>">
                    <input type="hidden" name="action" value="reject_return">
                    <input type="hidden" name="admin_note" value="">
                    <button type="submit" class="btn-return-action btn-reject" style="width:100%;">❌ Từ chối</button>
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

                <?php
                $os_tl  = (int)$order['order_status'];
                $is_cod = ((int)$order['payment_method'] === 1);
                ?>

                <!-- Bước 1: Đặt hàng (luôn active) -->
                <div class="timeline-item active">
                    <div class="timeline-dot"></div>
                    <div class="timeline-title">Đơn hàng đã đặt</div>
                    <div class="timeline-desc">Khách hàng đã đặt đơn hàng<br><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></div>
                </div>

                <?php if ($os_tl === 4 || $os_tl === 8): ?>
                    <!-- Hủy / Chờ duyệt hủy -->
                    <div class="timeline-item active" style="margin-bottom:0;">
                        <div class="timeline-dot" style="background:#c0392b;"></div>
                        <div class="timeline-title" style="color:#c0392b;">
                            <?= $os_tl === 8 ? 'Đang chờ Admin duyệt hủy' : 'Đơn hàng đã bị hủy' ?>
                        </div>
                        <div class="timeline-desc">
                            <?php if (!empty($order['cancel_reason'])): ?>
                                Lý do: <?= htmlspecialchars($order['cancel_reason']) ?>
                            <?php else: ?>
                                <?= $os_tl === 8 ? 'Yêu cầu hủy đang được xem xét' : 'Đơn hàng không còn hiệu lực' ?>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($os_tl === 9 || $os_tl === 10): ?>
                    <!-- Giao thất bại -->
                    <div class="timeline-item active">
                        <div class="timeline-dot" style="background:#2980b9;"></div>
                        <div class="timeline-title">Chờ lấy hàng</div>
                        <div class="timeline-desc">Đơn hàng đã được chuẩn bị</div>
                    </div>
                    <div class="timeline-item active">
                        <div class="timeline-dot" style="background:#8e44ad;"></div>
                        <div class="timeline-title">Đang giao hàng</div>
                        <div class="timeline-desc">Đơn hàng đã được bàn giao ĐVVC</div>
                    </div>
                    <div class="timeline-item active" style="margin-bottom:0;">
                        <div class="timeline-dot" style="background:#c0392b;"></div>
                        <div class="timeline-title" style="color:#c0392b;">Giao hàng thất bại</div>
                        <div class="timeline-desc">
                            <?= !empty($order['admin_note']) ? htmlspecialchars($order['admin_note']) : 'Không liên lạc được với khách hàng' ?>
                            <?php if (!empty($order['delivery_failed_at'])): ?>
                                <br><?= date('d/m/Y H:i', strtotime($order['delivery_failed_at'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($os_tl === 10): ?>
                    <div class="timeline-item active" style="margin-bottom:0;">
                        <div class="timeline-dot" style="background:#7f8c8d;"></div>
                        <div class="timeline-title" style="color:#7f8c8d;">Hàng đã hoàn về kho</div>
                        <div class="timeline-desc">Đơn hàng đã được thu hồi về kho</div>
                    </div>
                    <?php endif; ?>

                <?php elseif (in_array($os_tl, [5, 6, 7])): ?>
                    <!-- Trả hàng / Hoàn tiền -->
                    <div class="timeline-item active">
                        <div class="timeline-dot"></div>
                        <div class="timeline-title">Chờ lấy hàng → Đang giao → Đã nhận</div>
                        <div class="timeline-desc">Các bước giao hàng đã hoàn tất trước khi trả hàng</div>
                    </div>
                    <div class="timeline-item active">
                        <div class="timeline-dot" style="background:#c0392b;"></div>
                        <div class="timeline-title" style="color:#c0392b;">Yêu cầu trả hàng</div>
                        <div class="timeline-desc">
                            <?php if (!empty($order['return_reason'])): ?>
                                Lý do: <?= htmlspecialchars($order['return_reason']) ?>
                            <?php endif; ?>
                            <?php if (!empty($order['return_requested_at'])): ?>
                                <br><?= date('d/m/Y H:i', strtotime($order['return_requested_at'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($os_tl >= 6): ?>
                    <div class="timeline-item active">
                        <div class="timeline-dot" style="background:#d35400;"></div>
                        <div class="timeline-title">Đã duyệt – Đang hoàn trả hàng</div>
                        <div class="timeline-desc">Admin đã duyệt, khách đang gửi hàng về</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($os_tl === 7): ?>
                    <div class="timeline-item active" style="margin-bottom:0;">
                        <div class="timeline-dot" style="background:#1abc9c;"></div>
                        <div class="timeline-title" style="color:#1abc9c;">Đã hoàn tiền</div>
                        <div class="timeline-desc">Tiền đã được hoàn về ví khách hàng</div>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Luồng chuẩn: 0 → 1 → 2 → 3 -->

                    <!-- Bước 2: Chờ lấy hàng (status >= 1) -->
                    <div class="timeline-item <?= $os_tl >= 1 ? 'active' : '' ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-title">Chờ lấy hàng</div>
                        <div class="timeline-desc">
                            <?php if ($os_tl >= 1): ?>
                                <?= $is_cod ? 'Đơn COD đã xác nhận, chuẩn bị giao' : 'Thanh toán xác nhận, chuẩn bị giao' ?>
                            <?php else: ?>
                                Chờ xác nhận thanh toán
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Bước 3: Đang giao hàng (status >= 2) -->
                    <div class="timeline-item <?= $os_tl >= 2 ? 'active' : '' ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-title">Đang giao hàng</div>
                        <div class="timeline-desc">
                            <?= $os_tl >= 2 ? 'Đơn hàng đã bàn giao cho đơn vị vận chuyển' : 'Chờ bàn giao ĐVVC' ?>
                            <?php if (!empty($order['tracking_number'])): ?>
                                <br>Mã vận đơn: <strong><?= htmlspecialchars($order['tracking_number']) ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Bước 4: Hoàn thành (status = 3) -->
                    <div class="timeline-item <?= $os_tl >= 3 ? 'active' : '' ?>" style="margin-bottom:0;">
                        <div class="timeline-dot"></div>
                        <div class="timeline-title">Hoàn thành</div>
                        <div class="timeline-desc">
                            <?php if ($os_tl >= 3): ?>
                                Khách hàng đã xác nhận nhận hàng
                                <?= $is_cod ? '· Thanh toán COD thu thành công' : '' ?>
                            <?php else: ?>
                                Chờ khách xác nhận nhận hàng
                            <?php endif; ?>
                        </div>
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

<script>
    const sseUrl = new URL('../api/sse_stream.php', window.location.origin);
    const eventSource = new EventSource(sseUrl.toString());

    eventSource.addEventListener('message', function(e) {
        const data = JSON.parse(e.data);
        if (data.order_update && data.order_update.length > 0) {
            // Hiển thị toast hoặc alert nhỏ rồi reload trang
            alert('Trạng thái đơn hàng vừa được cập nhật, hệ thống sẽ tự động làm mới!');
            setTimeout(() => window.location.reload(), 1000);
        }
    });
</script>
</body>
</html>
