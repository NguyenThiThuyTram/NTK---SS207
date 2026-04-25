<?php
// ── Kiểm tra session & quyền admin ──────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Uncomment để bật bảo vệ admin:
// if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 0) != 1) {
//     header('Location: ../views/login.php');
//     exit;
// }

require_once __DIR__ . '/../config/database.php';

// ── Lấy số liệu thống kê từ database ────────────────────────────────

// 1. Tổng đơn hàng
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 2. Doanh thu (tổng final_price của các đơn đã hoàn thành/đang giao)
$stmt = $conn->query("SELECT SUM(final_price) as revenue FROM orders WHERE order_status IN (1, 2, 3)");
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

// 3. Tổng sản phẩm đang bán
$stmt = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 1");
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 4. Tổng khách hàng
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 0");
$total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// ── Tính % Tăng trưởng (7 ngày qua vs 7 ngày trước đó) ────────────
function getGrowthRate($conn, $table, $dateColumn, $valueColumn = '1', $condition = "1=1") {
    $curSql = "SELECT SUM($valueColumn) as val FROM $table WHERE $condition AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $prevSql = "SELECT SUM($valueColumn) as val FROM $table WHERE $condition AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND $dateColumn < DATE_SUB(NOW(), INTERVAL 7 DAY)";
    
    $cur = $conn->query($curSql)->fetch(PDO::FETCH_ASSOC)['val'] ?? 0;
    $prev = $conn->query($prevSql)->fetch(PDO::FETCH_ASSOC)['val'] ?? 0;
    
    if ($prev == 0) return $cur > 0 ? 100 : 0;
    return (($cur - $prev) / $prev) * 100;
}

$growth_orders = getGrowthRate($conn, 'orders', 'order_date');
$growth_revenue = getGrowthRate($conn, 'orders', 'order_date', 'final_price', 'order_status IN (1, 2, 3)');
$growth_customers = getGrowthRate($conn, 'users', 'created_at', '1', 'role = 0');

function renderGrowthBadge($percent) {
    if ($percent > 0) {
        return '<span class="stat-change">+' . number_format($percent, 1) . '%</span>';
    } elseif ($percent < 0) {
        return '<span class="stat-change negative">' . number_format($percent, 1) . '%</span>';
    } else {
        return '<span class="stat-change neutral">0%</span>';
    }
}

// 5. Đơn hàng trong 7 ngày gần đây, xếp theo thứ tự đặt hàng (cũ → mới)
$stmt = $conn->prepare("
    SELECT o.order_id, o.fullname, o.order_date, o.final_price, o.order_status
    FROM orders o
    WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY o.order_date ASC
");
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapping trạng thái đơn hàng
function getOrderStatusLabel(int $status): array {
    $map = [
        0 => ['label' => 'Chờ xử lý',  'class' => 'status-pending'],
        1 => ['label' => 'Đang giao',   'class' => 'status-shipping'],
        2 => ['label' => 'Đã giao',     'class' => 'status-delivered'],
        3 => ['label' => 'Hoàn thành',  'class' => 'status-done'],
        4 => ['label' => 'Đã hủy',      'class' => 'status-cancelled'],
        5 => ['label' => 'Trả hàng',    'class' => 'status-returned'],
    ];
    return $map[$status] ?? ['label' => 'Không rõ', 'class' => 'status-pending'];
}

// ── Include sidebar (cũng gồm cả topbar + DOCTYPE) ───────────────────
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<!-- ============================================================
     DASHBOARD CONTENT
============================================================ -->
<style>
    /* ── Font & Base ─────────────────────────────────────── */
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }

    /* ── Page header ─────────────────────────────────────── */
    .page-header { margin-bottom: 28px; }
    .page-title {
        font-size: 21px;
        font-weight: 700;
        color: #111111;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .page-subtitle { font-size: 13px; color: #999; margin-top: 4px; }

    /* ── Stat cards ──────────────────────────────────────── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 26px;
    }
    .stat-card {
        background: #ffffff;
        border-radius: 10px;
        padding: 22px 22px 18px;
        border: 1px solid #e5e5e5;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .stat-card:hover {
        box-shadow: 0 4px 20px rgba(47,28,0,0.08);
        transform: translateY(-2px);
    }
    .stat-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    .stat-icon {
        width: 44px;
        height: 44px;
        background: #2f1c00;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 18px;
        flex-shrink: 0;
    }
    .stat-change {
        font-size: 12px;
        font-weight: 600;
        color: #27ae60;
        background: #eafaf1;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .stat-change.negative { color: #c0392b; background: #fdf0ef; }
    .stat-change.neutral { color: #888; background: #f5f1eb; }

    .stat-value {
        font-size: 26px;
        font-weight: 500;
        color: #111111;
        line-height: 1;
        margin-bottom: 6px;
    }
    .stat-label {
        font-size: 11px;
        font-weight: 500;
        color: #aaa;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    /* ── Recent Orders Table ─────────────────────────────── */
    .section-card {
        background: #ffffff;
        border-radius: 10px;
        border: 1px solid #e5e5e5;
        overflow: hidden;
        margin-bottom: 26px;
    }
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        border-bottom: 1px solid #e5e5e5;
    }
    .section-title {
        font-size: 13px;
        font-weight: 700;
        color: #111111;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    .btn-view-all {
        font-size: 12px;
        font-weight: 600;
        color: #2f1c00;
        text-decoration: none;
        background: #f5f1eb;
        padding: 6px 14px;
        border-radius: 6px;
        transition: background 0.2s;
    }
    .btn-view-all:hover { background: #ece8e1; }

    /* Table */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead th {
        padding: 12px 24px;
        font-size: 11px;
        font-weight: 700;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        text-align: left;
        background: #fafaf8;
        border-bottom: 1px solid #e5e5e5;
        white-space: nowrap;
    }
    .data-table tbody td {
        padding: 14px 24px;
        font-size: 13.5px;
        color: #333;
        border-bottom: 1px solid #f8f8f8;
        vertical-align: middle;
    }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table tbody tr:hover { background: #fafafa; }

    /* Order ID */
    .order-id {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        font-size: 13px;
        color: #444;
    }

    /* Status badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 11px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    .status-badge::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        opacity: 0.7;
    }
    .status-pending   { background: #fff8e6; color: #d48806; }
    .status-shipping  { background: #fff3e0; color: #e67e22; }
    .status-delivered { background: #e8f5e9; color: #27ae60; }
    .status-done      { background: #1a1a2e; color: #ffffff; }
    .status-done::before { background: #fff; }
    .status-cancelled { background: #fdf0ef; color: #e74c3c; }
    .status-returned  { background: #f3f0ff; color: #8e44ad; }

    /* Action link */
    .action-link {
        font-size: 13px;
        font-weight: 500;
        color: #1a1a2e;
        text-decoration: none;
        padding: 5px 0;
        border-bottom: 1.5px solid transparent;
        transition: border-color 0.15s;
    }
    .action-link:hover { border-color: #1a1a2e; }
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="page-title">Dashboard</div>
    <div class="page-subtitle">Tổng quan hoạt động kinh doanh</div>
</div>

<!-- Stat Cards -->
<div class="stats-grid">
    <!-- Tổng đơn hàng -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <?= renderGrowthBadge($growth_orders) ?>
        </div>
        <div class="stat-value"><?= number_format($total_orders) ?></div>
        <div class="stat-label">Tổng đơn hàng</div>
    </div>

    <!-- Doanh thu -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <?= renderGrowthBadge($growth_revenue) ?>
        </div>
        <div class="stat-value"><?= number_format($total_revenue, 0, ',', '.') ?>₫</div>
        <div class="stat-label">Doanh thu</div>
    </div>

    <!-- Sản phẩm -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
            <span class="stat-change neutral">—</span>
        </div>
        <div class="stat-value"><?= number_format($total_products) ?></div>
        <div class="stat-label">Sản phẩm</div>
    </div>

    <!-- Khách hàng -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <?= renderGrowthBadge($growth_customers) ?>
        </div>
        <div class="stat-value"><?= number_format($total_customers) ?></div>
        <div class="stat-label">Khách hàng</div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="section-card">
    <div class="section-header">
        <div class="section-title">Đơn hàng gần đây</div>
        <a href="orders.php" class="btn-view-all">Xem tất cả →</a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Ngày đặt</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_orders)): ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:30px; color:#aaa;">
                    Chưa có đơn hàng nào
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($recent_orders as $order):
                $statusInfo = getOrderStatusLabel((int)$order['order_status']);
            ?>
            <tr>
                <td><span class="order-id"><?= htmlspecialchars($order['order_id']) ?></span></td>
                <td><?= htmlspecialchars($order['fullname'] ?? '—') ?></td>
                <td><?= date('Y-m-d', strtotime($order['order_date'])) ?></td>
                <td><?= number_format($order['final_price'], 0, ',', '.') ?>₫</td>
                <td>
                    <span class="status-badge <?= $statusInfo['class'] ?>">
                        <?= $statusInfo['label'] ?>
                    </span>
                </td>
                <td>
                    <a href="order_detail.php?id=<?= urlencode($order['order_id']) ?>" class="action-link">
                        Xem chi tiết
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div><!-- /.admin-content -->
</main>
</body>
</html>
