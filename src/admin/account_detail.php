<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$user_id = $_GET['id'] ?? '';
if (!$user_id) {
    header('Location: accounts.php');
    exit;
}

// Lấy thông tin user + thống kê
$stmt = $conn->prepare("
    SELECT u.*, 
           COUNT(o.order_id) as total_orders, 
           IFNULL(SUM(o.final_price), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id AND o.payment_status = 1
    WHERE u.user_id = ?
    GROUP BY u.user_id
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: accounts.php');
    exit;
}

// Lấy địa chỉ
$stmt_addr = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt_addr->execute([$user_id]);
$addresses = $stmt_addr->fetchAll(PDO::FETCH_ASSOC);

// Lấy lịch sử đơn hàng
$stmt_orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 10");
$stmt_orders->execute([$user_id]);
$orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

$admin_current_page = 'accounts.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    .page-title { font-size: 21px; font-weight: 700; color: #111111; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .page-subtitle { font-size: 13px; color: #999; margin-bottom: 24px; }
    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-back { background: #f5f1eb; color: #333; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s; }
    .btn-back:hover { background: #e5e0da; }
    .btn-action { padding: 10px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-lock { background: #fdf0ef; color: #e74c3c; }
    .btn-unlock { background: #eafaf1; color: #27ae60; }
    
    .detail-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start; }
    
    .section-card { background: #fff; border-radius: 10px; border: 1px solid #e5e5e5; padding: 24px; margin-bottom: 24px; }
    .section-title { font-size: 15px; font-weight: 700; color: #111; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; }
    .section-title i { color: #888; }
    
    .info-group { margin-bottom: 16px; }
    .info-label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; font-weight: 600; }
    .info-value { font-size: 14px; color: #111; font-weight: 500; }
    .info-value.bold { font-weight: 700; color: #2f1c00; font-size: 16px; }
    
    .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: 0.75; }
    .status-active { background: #eafaf1; color: #27ae60; }
    .status-locked { background: #fdf0ef; color: #e74c3c; }
    
    .stat-row { display: flex; gap: 16px; background: #fafaf8; padding: 16px; border-radius: 8px; margin-top: 20px; }
    .stat-item { flex: 1; text-align: center; border-right: 1px solid #e5e5e5; }
    .stat-item:last-child { border-right: none; }
    .stat-val { font-size: 18px; font-weight: 700; color: #111; margin-bottom: 4px; }
    .stat-lbl { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }

    .address-item { padding: 16px; border: 1px solid #e5e5e5; border-radius: 8px; margin-bottom: 12px; }
    .address-item:last-child { margin-bottom: 0; }
    .address-name { font-weight: 700; color: #111; font-size: 14px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
    .address-default { background: #2f1c00; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; font-weight: 600; }
    .address-txt { font-size: 13px; color: #555; line-height: 1.5; }

    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { padding: 12px 16px; font-size: 11px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.8px; text-align: left; background: #fafaf8; border-bottom: 1px solid #e5e5e5; }
    .data-table td { padding: 14px 16px; font-size: 13.5px; color: #333; border-bottom: 1px solid #f5f1eb; }
    .order-status { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
</style>

<div class="toolbar">
    <div>
        <div class="page-title">Chi tiết tài khoản</div>
        <p class="page-subtitle">ID: <?= htmlspecialchars($user['user_id']) ?></p>
    </div>
    <div style="display:flex; gap:12px;">
        <a href="accounts.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
        <?php if ($user['status'] == 1): ?>
            <form method="POST" action="toggle_user_status.php" onsubmit="return confirm('Bạn có chắc muốn khóa tài khoản này?')">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']) ?>">
                <input type="hidden" name="action" value="lock">
                <button type="submit" class="btn-action btn-lock"><i class="fa-solid fa-lock"></i> Khóa tài khoản</button>
            </form>
        <?php else: ?>
            <form method="POST" action="toggle_user_status.php" onsubmit="return confirm('Mở khóa tài khoản này?')">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']) ?>">
                <input type="hidden" name="action" value="unlock">
                <button type="submit" class="btn-action btn-unlock"><i class="fa-solid fa-lock-open"></i> Mở khóa tài khoản</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="detail-grid">
    <!-- Cột trái: Thông tin -->
    <div>
        <div class="section-card">
            <div class="section-title"><i class="fa-solid fa-address-card"></i> Thông tin cơ bản</div>
            <div class="info-group">
                <div class="info-label">Trạng thái</div>
                <?php if ($user['status'] == 1): ?>
                    <span class="status-badge status-active">Hoạt động</span>
                <?php else: ?>
                    <span class="status-badge status-locked">Đã khóa</span>
                <?php endif; ?>
            </div>
            <div class="info-group">
                <div class="info-label">Họ tên</div>
                <div class="info-value bold"><?= htmlspecialchars($user['fullname']) ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Email</div>
                <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Số điện thoại</div>
                <div class="info-value"><?= htmlspecialchars($user['phonenumber']) ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Ngày tham gia</div>
                <div class="info-value"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Vai trò</div>
                <div class="info-value"><?= $user['role'] == 1 ? 'Quản trị viên (Admin)' : 'Khách hàng' ?></div>
            </div>

            <div class="stat-row">
                <div class="stat-item">
                    <div class="stat-val"><?= $user['total_orders'] ?></div>
                    <div class="stat-lbl">Đơn hàng</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val"><?= number_format($user['total_spent'], 0, ',', '.') ?>đ</div>
                    <div class="stat-lbl">Chi tiêu</div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-title"><i class="fa-solid fa-wallet"></i> Ví NTK Pay</div>
            <div class="info-group" style="margin-bottom:0;">
                <div class="info-label">Số dư hiện tại</div>
                <div class="info-value bold" style="font-size:24px; color:#27ae60;"><?= number_format($user['wallet_balance'], 0, ',', '.') ?>đ</div>
            </div>
        </div>
    </div>

    <!-- Cột phải: Địa chỉ & Đơn hàng -->
    <div>
        <div class="section-card">
            <div class="section-title"><i class="fa-solid fa-map-location-dot"></i> Địa chỉ giao hàng</div>
            <?php if (empty($addresses)): ?>
                <div style="color:#aaa; font-size:13.5px;">Chưa có địa chỉ nào được lưu.</div>
            <?php else: ?>
                <?php foreach ($addresses as $addr): ?>
                <div class="address-item">
                    <div class="address-name">
                        <?= htmlspecialchars($addr['recipient_name']) ?> - <?= htmlspecialchars($addr['phone']) ?>
                        <?php if ($addr['is_default']): ?><span class="address-default">Mặc định</span><?php endif; ?>
                    </div>
                    <div class="address-txt">
                        <?= htmlspecialchars($addr['street']) ?>, <?= htmlspecialchars($addr['ward']) ?>, <?= htmlspecialchars($addr['district']) ?>, <?= htmlspecialchars($addr['province']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="section-card">
            <div class="section-title"><i class="fa-solid fa-boxes-packing"></i> Đơn hàng gần đây</div>
            <?php if (empty($orders)): ?>
                <div style="color:#aaa; font-size:13.5px;">Chưa có đơn hàng nào.</div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td style="font-weight:600;">#<?= htmlspecialchars($o['order_id']) ?></td>
                                <td><?= date('d/m/Y', strtotime($o['order_date'])) ?></td>
                                <td style="font-weight:600;"><?= number_format($o['final_price'], 0, ',', '.') ?>đ</td>
                                <td>
                                    <?php
                                    $statuses = [
                                        0 => ['Chờ xác nhận', '#f39c12', '#fdf2e9'],
                                        1 => ['Đã xác nhận', '#3498db', '#ebf5fb'],
                                        2 => ['Đang giao hàng', '#9b59b6', '#f5eef8'],
                                        3 => ['Đã giao', '#27ae60', '#eafaf1'],
                                        4 => ['Đã hủy', '#e74c3c', '#fdf0ef'],
                                        5 => ['Yêu cầu hủy', '#e67e22', '#fef5e7']
                                    ];
                                    $s = $statuses[$o['order_status']] ?? ['Không rõ', '#999', '#eee'];
                                    ?>
                                    <span class="order-status" style="color:<?= $s[1] ?>; background:<?= $s[2] ?>;"><?= $s[0] ?></span>
                                </td>
                                <td>
                                    <a href="order_detail.php?id=<?= $o['order_id'] ?>" style="color:#2980b9; text-decoration:none; font-size:13px; font-weight:600;">Chi tiết</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</div><!-- /.admin-content -->
</main>
</body>
</html>
