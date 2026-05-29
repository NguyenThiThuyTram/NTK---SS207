<?php
require_once 'auth_check.php';
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

$stmt_dash_calc = $conn->prepare("
    SELECT 
        p.product_id, 
        p.name, 
        p.sold_count, 
        MIN(COALESCE(NULLIF(pv.sale_price, 0), pv.original_price)) as min_price
    FROM products p
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE p.status = 1
    GROUP BY p.product_id, p.name, p.sold_count
");
$stmt_dash_calc->execute();
$dash_prods = $stmt_dash_calc->fetchAll(PDO::FETCH_ASSOC);

$total_items_sold = 0;
$total_global_revenue = 0;
$highest_revenue_prod = 'Chưa có dữ liệu';
$max_prod_revenue = -1;

foreach ($dash_prods as $prod) {
    $sold = intval($prod['sold_count']);
    $price = floatval($prod['min_price']);
    $prod_revenue = $sold * $price;

    $total_items_sold += $sold;
    $total_global_revenue += $prod_revenue;

    if ($prod_revenue > $max_prod_revenue && $sold > 0) {
        $max_prod_revenue = $prod_revenue;
        $highest_revenue_prod = $prod['name'];
    }
}
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

// Mapping trạng thái đơn hàng phù hợp hệ thống
function getOrderStatusLabel(int $status): array {
    $map = [
        0 => ['label' => 'Chờ thanh toán', 'class' => 'status-pending'],
        1 => ['label' => 'Chờ lấy hàng',   'class' => 'status-shipping'],
        2 => ['label' => 'Đang giao hàng', 'class' => 'status-shipping'],
        3 => ['label' => 'Hoàn thành',    'class' => 'status-delivered'],
        4 => ['label' => 'Đã hủy',        'class' => 'status-cancelled'],
        5 => ['label' => 'Yêu cầu trả hàng','class' => 'status-returned'],
        6 => ['label' => 'Đang hoàn trả',  'class' => 'status-returned'],
        7 => ['label' => 'Đã hoàn tiền',   'class' => 'status-delivered'],
        8 => ['label' => 'Chờ duyệt hủy',  'class' => 'status-pending'],
        9 => ['label' => 'Giao thất bại',  'class' => 'status-cancelled'],
        10 => ['label' => 'Đang hoàn về kho','class' => 'status-pending'],
    ];
    return $map[$status] ?? ['label' => 'Không rõ', 'class' => 'status-pending'];
}

// ── CUSTOMER CHURN RISK PREDICTION QUERY (FROM BEST_SELLERS.PHP) ──
$stmt_churn = $conn->prepare("
    SELECT 
        u.user_id,
        COALESCE(NULLIF(u.fullname, ''), u.username) as customer_name,
        u.email,
        COALESCE(SUM(CASE WHEN o.order_status IN (1, 2, 3) THEN o.final_price ELSE 0 END), 0) AS TotalSpent,
        COUNT(o.order_id) AS TotalOrders,
        MAX(o.order_date) AS LastOrderDate,
        DATEDIFF(NOW(), MAX(o.order_date)) AS DaysSinceLastOrder
    FROM users u
    INNER JOIN orders o ON u.user_id = o.user_id
    GROUP BY u.user_id, u.fullname, u.username, u.email
    HAVING DaysSinceLastOrder > 30
    ORDER BY DaysSinceLastOrder DESC
    LIMIT 10
");
$stmt_churn->execute();
$churn_customers = $stmt_churn->fetchAll(PDO::FETCH_ASSOC);

// ── PRODUCT PERFORMANCE QUERY (NO LIMIT) (FROM BEST_SELLERS.PHP) ──
$stmt_table = $conn->prepare("
    SELECT 
        p.product_id, 
        p.name, 
        p.image, 
        p.sold_count, 
        c.name as category_name,
        COALESCE(SUM(pv.stock), 0) as total_stock,
        MIN(COALESCE(NULLIF(pv.sale_price, 0), pv.original_price)) as price
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_active = 1
    WHERE p.status = 1
    GROUP BY p.product_id
    ORDER BY p.sold_count DESC
");
$stmt_table->execute();
$products = $stmt_table->fetchAll(PDO::FETCH_ASSOC);

// Calculate global metrics from the product list for Product Performance table contribution
$total_global_revenue_bs = 0.0;
foreach ($products as $prod) {
    $sold = (int)$prod['sold_count'];
    $price = (float)($prod['price'] ?? 0);
    $total_global_revenue_bs += $sold * $price;
}

// ── VIP CUSTOMERS QUERY (FROM BEST_SELLERS.PHP) ──
$stmt_vip = $conn->prepare("
    SELECT 
        u.user_id,
        COALESCE(NULLIF(u.fullname, ''), u.username) as customer_name,
        u.email,
        COALESCE(SUM(CASE WHEN o.order_status IN (1, 2, 3) THEN o.final_price ELSE 0 END), 0) AS TotalSpent,
        COUNT(o.order_id) AS TotalOrders,
        MAX(o.order_date) AS LastOrderDate,
        DATEDIFF(NOW(), MAX(o.order_date)) AS DaysSinceLastOrder
    FROM users u
    INNER JOIN orders o ON u.user_id = o.user_id
    GROUP BY u.user_id, u.fullname, u.username, u.email
    HAVING TotalOrders >= 3 AND TotalSpent >= 2000000
    ORDER BY TotalSpent DESC
    LIMIT 10
");
$stmt_vip->execute();
$vip_customers = $stmt_vip->fetchAll(PDO::FETCH_ASSOC);

// ── Include sidebar (cũng gồm cả topbar + DOCTYPE) ───────────────────
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    /* ── Font & Base ─────────────────────────────────────── */
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }

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
    
    .status-pending   { background: #3d2d18; color: #f39c12; }
    .status-shipping  { background: #182d3d; color: #3498db; }
    .status-delivered { background: #183d25; color: #2ecc71; }
    .status-done      { background: #ffffff; color: #121212; }
    .status-done::before { background: #121212; }
    .status-cancelled { background: #3d1a1a; color: #ff4d4d; }
    .status-returned  { background: #331a3d; color: #9b59b6; }

    /* Action link */
    .action-link {
        font-size: 13px;
        font-weight: 500;
        color: #2f1c00;
        text-decoration: none;
        padding: 5px 0;
        border-bottom: 1.5px solid transparent;
        transition: border-color 0.15s;
    }
    .action-link:hover { border-color: #2f1c00; }


</style>

<div class="page-header">
    <div class="page-title">Trang Chủ</div>
    <div class="page-subtitle">Tổng quan hoạt động kinh doanh</div>
</div>

<!-- <div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <?= renderGrowthBadge($growth_orders) ?>
        </div>
        <div class="stat-value"><?= number_format($total_orders) ?></div>
        <div class="stat-label">Tổng đơn hàng</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <?= renderGrowthBadge($growth_revenue) ?>
        </div>
        <div class="stat-value"><?= number_format($total_revenue, 0, ',', '.') ?>₫</div>
        <div class="stat-label">Doanh thu</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
            <span class="stat-change neutral">—</span>
        </div>
        <div class="stat-value"><?= number_format($total_products) ?></div>
        <div class="stat-label">Sản phẩm</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <?= renderGrowthBadge($growth_customers) ?>
        </div>
        <div class="stat-value"><?= number_format($total_customers) ?></div>
        <div class="stat-label">Khách hàng</div>
    </div>
</div> -->

<div class="stats-grid">
    <a href="orders.php" class="stat-card" style="text-decoration: none; color: inherit; display: block;">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <?= renderGrowthBadge($growth_orders) ?>
        </div>
        <div class="stat-value"><?= number_format($total_orders) ?></div>
        <div class="stat-label">Tổng đơn hàng</div>
    </a>

    <a href="#financial-performance" class="stat-card" style="text-decoration: none; color: inherit; display: block;" onmouseover="this.querySelector('.stat-label').style.textDecoration='underline'" onmouseout="this.querySelector('.stat-label').style.textDecoration='none'">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <?= renderGrowthBadge($growth_revenue) ?>
        </div>
        <div class="stat-value"><?= number_format($total_global_revenue, 0, ',', '.') ?>₫</div>
        <div class="stat-label">Doanh thu hệ thống</div>
    </a>

    <a href="products.php" class="stat-card" style="text-decoration: none; color: inherit; display: block;">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
            <span class="stat-change neutral" style="color: var(--accent-color); font-weight: 600; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;" title="Bán chạy nhất: <?= htmlspecialchars($highest_revenue_prod) ?>">
                ⭐ Top: <?= htmlspecialchars($highest_revenue_prod) ?>
            </span>
        </div>
        <div class="stat-value"><?= number_format($total_items_sold) ?></div>
        <div class="stat-label">Sản phẩm đã bán</div>
    </a>

    <a href="accounts.php" class="stat-card" style="text-decoration: none; color: inherit; display: block;">
        <div class="stat-card-top">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <?= renderGrowthBadge($growth_customers) ?>
        </div>
        <div class="stat-value"><?= number_format($total_customers) ?></div>
        <div class="stat-label">Khách hàng</div>
    </a>
</div>

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
</div>

<div class="section-card">
    <div class="section-header">
        <div class="section-title">Khách hàng tiềm năng</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Khách Hàng</th>
                    <th style="text-align: right; width: 130px;">Số Đơn Đã Mua</th>
                    <th style="text-align: right; width: 150px;">Tổng Chi Tiêu</th>
                    <th style="text-align: center; width: 180px;">Ngày Đặt Đơn Cuối</th>
                    <th style="text-align: right; width: 140px;">Thời Gian Kể Từ Đơn Cuối</th>
                    <th style="padding-left: 30px; width: 180px;">Hạng Dự Kiến</th>
                    <th style="padding-left: 20px;">Hành Động Khuyến Nghị</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vip_customers)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #888888;">
                        Chưa tìm thấy khách hàng nào đạt tiêu chí VIP (Mua >= 3 đơn và Chi tiêu >= 2M).
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($vip_customers as $vip): 
                        $v_days = (int)$vip['DaysSinceLastOrder'];
                        $v_spent = (float)$vip['TotalSpent'];
                        $v_orders = (int)$vip['TotalOrders'];

                        if ($v_spent >= 10000000) {
                            $v_badge = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; background-color: #fdf0ef; color: #c0392b;"><i class="fa-solid fa-crown"></i> Đối tác Kim Cương</span>';
                            $v_recommend = '<span style="font-weight: 600; font-size: 13.5px; color: #c0392b;">Tặng Quà Tri Ân Đặc Biệt</span>';
                        } elseif ($v_spent >= 5000000) {
                            $v_badge = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; background-color: #fdf6ec; color: #e6a23c;"><i class="fa-solid fa-star"></i> Khách Hàng Vàng</span>';
                            $v_recommend = '<span style="font-weight: 600; font-size: 13.5px; color: #e6a23c;">Mới Vào Nhóm Trải Nghiệm Sớm</span>';
                        } else {
                            $v_badge = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; background-color: #eafaf1; color: #27ae60;"><i class="fa-solid fa-user-check"></i> Thành Viên Bạc</span>';
                            $v_recommend = '<span style="font-weight: 600; font-size: 13.5px; color: #27ae60;">Gửi Khuyến Mãi Độc Quyền</span>';
                        }
                    ?>
                    <tr>
                        <td>
                            <a href="account_detail.php?id=<?= urlencode($vip['user_id']) ?>" style="text-decoration: none; color: inherit; display: block;" onmouseover="this.querySelector('.vip-name-title').style.textDecoration='underline', this.querySelector('.vip-name-title').style.color='#a6825c'" onmouseout="this.querySelector('.vip-name-title').style.textDecoration='none', this.querySelector('.vip-name-title').style.color='#111111'">
                                <div class="vip-name-title" style="font-weight: 600; color: #111111; transition: color 0.2s;"><?= htmlspecialchars($vip['customer_name']) ?></div>
                                <div style="font-size: 12px; color: #888888; margin-top: 2px;"><?= htmlspecialchars($vip['email'] ?? 'Không có email') ?></div>
                            </a>
                        </td>
                        <td style="text-align: right; font-weight: 600;"><?= number_format($v_orders) ?></td>
                        <td style="text-align: right; font-weight: 600; color: #2e7d32;"><?= number_format($v_spent, 0, ',', '.') ?>đ</td>
                        <td style="text-align: center; color: #888888;"><?= date('d/m/Y', strtotime($vip['LastOrderDate'])) ?></td>
                        <td style="text-align: right; font-weight: 600; color: #888888;"><?= number_format($v_days) ?> ngày trước</td>
                        <td style="padding-left: 30px;"><?= $v_badge ?></td>
                        <td style="padding-left: 20px;"><?= $v_recommend ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section-card">
    <div class="section-header">
        <div class="section-title">Khách hàng có nguy cơ rời bỏ</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Khách Hàng</th>
                    <th style="text-align: right; width: 130px;">Số Đơn Đã Mua</th>
                    <th style="text-align: right; width: 150px;">Tổng Chi Tiêu</th>
                    <th style="text-align: center; width: 180px;">Ngày Đặt Đơn Cuối</th>
                    <th style="text-align: right; width: 140px;">Thời Gian Kể Từ Đơn Cuối</th>
                    <th style="padding-left: 30px; width: 180px;">Trạng Thái Dự Báo</th>
                    <th style="padding-left: 20px;">Hành Động Khuyến Nghị</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($churn_customers)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #888888;">
                        Không phát hiện khách hàng có nguy cơ rời bỏ (DaysSinceLastOrder > 30)
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($churn_customers as $cust): 
                        $days = (int)$cust['DaysSinceLastOrder'];
                        $spent = (float)$cust['TotalSpent'];
                        $orders_count = (int)$cust['TotalOrders'];

                        if ($days >= 31 && $days <= 90 && ($spent >= 2000000 || $orders_count >= 2)) {
                            $status_badge = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; background-color: #fdf6ec; color: #e6a23c;"><i class="fa-solid fa-triangle-exclamation"></i> Nguy cơ rời bỏ</span>';
                            $recommendation = '<span style="font-weight: 600; font-size: 13.5px; color: #e6a23c;">Gửi Mail Tặng Voucher 15%</span>';
                        } elseif ($days > 90) {
                            $status_badge = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; background-color: #fdf0ef; color: #c0392b;"><i class="fa-solid fa-snowflake"></i> Đã rời bỏ</span>';
                            $recommendation = '<span style="font-weight: 600; font-size: 13.5px; color: #c0392b;">Chiến dịch Re-marketing</span>';
                        } else {
                            $status_badge = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; background-color: #f4f4f5; color: #909399;"><i class="fa-solid fa-circle-check"></i> Vận hành ổn định</span>';
                            $recommendation = '<span style="font-weight: 600; font-size: 13.5px; color: #27ae60;">Chăm sóc &amp; Duy trì liên hệ</span>';
                        }
                    ?>
                    <tr>
                        <td>
                            <a href="account_detail.php?id=<?= urlencode($cust['user_id']) ?>" style="text-decoration: none; color: inherit; display: block;" onmouseover="this.querySelector('.cust-name-title').style.textDecoration='underline', this.querySelector('.cust-name-title').style.color='#a6825c'" onmouseout="this.querySelector('.cust-name-title').style.textDecoration='none', this.querySelector('.cust-name-title').style.color='#111111'">
                                <div class="cust-name-title" style="font-weight: 600; color: #111111; transition: color 0.2s;"><?= htmlspecialchars($cust['customer_name']) ?></div>
                                <div style="font-size: 12px; color: #888888; margin-top: 2px;"><?= htmlspecialchars($cust['email'] ?? 'Không có email') ?></div>
                            </a>
                        </td>
                        <td style="text-align: right; font-weight: 600;"><?= number_format($orders_count) ?></td>
                        <td style="text-align: right; font-weight: 600; color: #2e7d32;"><?= number_format($spent, 0, ',', '.') ?>đ</td>
                        <td style="text-align: center; color: #888888;"><?= date('d/m/Y', strtotime($cust['LastOrderDate'])) ?></td>
                        <td style="text-align: right; font-weight: 600; color: #c0392b;"><?= number_format($days) ?> ngày</td>
                        <td style="padding-left: 30px;"><?= $status_badge ?></td>
                        <td style="padding-left: 20px;"><?= $recommendation ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section-card" id="financial-performance">
    <div class="section-header">
        <div class="section-title">Hiệu suất tài chính sản phẩm</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">Hạng</th>
                    <th>Sản phẩm</th>
                    <th style="text-align: right; width: 110px;">Đã bán</th>
                    <th style="text-align: right; width: 110px;">Tồn kho</th>
                    <th style="text-align: right; width: 120px;">Giá bán</th>
                    <th style="text-align: right; width: 140px;">Doanh thu</th>
                    <th style="text-align: right; width: 160px;">Tỷ trọng doanh thu</th>
                    <th style="padding-left: 30px;">Nhận định &amp; Khuyến nghị</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #888888;">
                        Không có sản phẩm nào đang hoạt động
                    </td>
                </tr>
                <?php else: ?>
                    <?php 
                    $rank = 1;
                    foreach ($products as $prod): 
                        $sold = (int)$prod['sold_count'];
                        $price = (float)$prod['price'];
                        $stock = (int)$prod['total_stock'];
                        
                        $prod_revenue = $sold * $price;
                        $revenue_contribution = $total_global_revenue_bs > 0 ? ($prod_revenue / $total_global_revenue_bs) * 100 : 0;
                        
                        $badges = [];
                        if ($rank <= 3) {
                            $badges[] = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; background-color: #eafaf1; color: #27ae60;"><i class="fa-solid fa-star"></i> Best Seller</span>';
                        }
                        if ($revenue_contribution > 8) {
                            $badges[] = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; background-color: #fdf6ec; color: #e6a23c;"><i class="fa-solid fa-money-bill-wave"></i> Tỷ trọng doanh thu cao</span>';
                        }
                        if ($revenue_contribution < 1.5 && $stock > 300) {
                            $badges[] = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; background-color: #fdf0ef; color: #c0392b;"><i class="fa-solid fa-chart-line-down"></i> Cần Kích Cầu</span>';
                        }
                        
                        if (empty($badges)) {
                            $badges[] = '<span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; background-color: #f4f4f5; color: #909399;"><i class="fa-solid fa-check"></i> Vận Hành Ổn Định</span>';
                        }
                    ?>
                    <tr>
                        <td style="text-align: center;"><span style="font-weight: 700; font-size: 15px; color: #a6825c; text-align: center; display: inline-block; width: 24px;">#<?= $rank ?></span></td>
                        <td>
                            <a href="product_detail.php?id=<?= urlencode($prod['product_id']) ?>" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px;" onmouseover="this.querySelector('.prod-name-title').style.textDecoration='underline', this.querySelector('.prod-name-title').style.color='#a6825c'" onmouseout="this.querySelector('.prod-name-title').style.textDecoration='none', this.querySelector('.prod-name-title').style.color='#111111'">
                                <?php if(!empty($prod['image'])): ?>
                                    <?php $img_src = (strpos($prod['image'], 'http') === 0) ? $prod['image'] : '../' . $prod['image']; ?>
                                    <img src="<?= htmlspecialchars($img_src) ?>" style="width: 44px; height: 44px; border-radius: 6px; object-fit: cover; background: #f5f1eb; border: 1px solid #e5e5e5; flex-shrink: 0;" alt="Product Image" onerror="this.outerHTML='<div style=\'width: 44px; height: 44px; border-radius: 6px; border: 1px solid #e5e5e5; display:flex;align-items:center;justify-content:center;color:#aaa;\'><i class=\'fa-solid fa-image\'></i></div>';">
                                <?php else: ?>
                                    <div style="width: 44px; height: 44px; border-radius: 6px; border: 1px solid #e5e5e5; display:flex;align-items:center;justify-content:center;color:#aaa;"><i class="fa-solid fa-image"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div class="prod-name-title" style="font-weight: 600; color: #111111; line-height: 1.4; transition: color 0.2s;"><?= htmlspecialchars($prod['name']) ?></div>
                                    <div style="font-size: 11.5px; color: #888888; margin-top: 2px; font-weight: 500;"><?= htmlspecialchars($prod['category_name'] ?? 'Không có danh mục') ?></div>
                                </div>
                            </a>
                        </td>
                        <td style="text-align: right; font-weight: 600;"><?= number_format($sold) ?></td>
                        <td style="text-align: right; font-weight: 600; color: #a6825c;"><?= number_format($stock) ?></td>
                        <td style="text-align: right; color: #888888;"><?= number_format($price, 0, ',', '.') ?>đ</td>
                        <td style="text-align: right; font-weight: 600;"><?= number_format($prod_revenue, 0, ',', '.') ?>đ</td>
                        <td style="text-align: right; font-weight: 600; color: #2e7d32;"><?= number_format($revenue_contribution, 2) ?>%</td>
                        <td style="padding-left: 30px;">
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                <?= implode(' ', $badges) ?>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
</body>
</html>