<?php
require_once 'auth_check.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ── CUSTOMER CHURN RISK PREDICTION QUERY ──────────────────────────────
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

// ── PRODUCT PERFORMANCE QUERY (NO LIMIT) ──────────────────────────────────
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

// Calculate global metrics from the product list
$total_global_revenue = 0.0;
$total_items_sold = 0;
$highest_revenue = 0.0;
$highest_revenue_product_name = 'N/A';

foreach ($products as $prod) {
    $sold = (int)$prod['sold_count'];
    $price = (float)($prod['price'] ?? 0);
    $prod_revenue = $sold * $price;
    
    $total_global_revenue += $prod_revenue;
    $total_items_sold += $sold;
    
    if ($prod_revenue > $highest_revenue) {
        $highest_revenue = $prod_revenue;
        $highest_revenue_product_name = $prod['name'];
    }
}

// Include sidebar
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    /* Theme color system based on CSS variables */
    :root {
        --bg-primary: #ffffff;
        --bg-secondary: #fafaf8;
        --bg-tertiary: #f5f1eb;
        --border-color: #e5e5e5;
        --text-primary: #111111;
        --text-secondary: #888888;
        --accent-color: #a6825c;
        
        --success-bg: #eafaf1;
        --success-color: #27ae60;
        --warning-bg: #fdf6ec;
        --warning-color: #e6a23c;
        --danger-bg: #fef0f0;
        --danger-color: #f56c6c;
        --info-bg: #eaf2fd;
        --info-color: #3498db;
        --ok-bg: #f4f4f5;
        --ok-color: #909399;
    }

    body.dark-mode {
        --bg-primary: #1e1e1e;
        --bg-secondary: #121212;
        --bg-tertiary: #252525;
        --border-color: #2a2a2a;
        --text-primary: #ffffff;
        --text-secondary: #aaaaaa;
        
        --success-bg: rgba(39, 174, 96, 0.12);
        --success-color: #2ecc71;
        --warning-bg: rgba(230, 162, 60, 0.12);
        --warning-color: #e6a23c;
        --danger-bg: rgba(245, 108, 108, 0.12);
        --danger-color: #f56c6c;
        --ok-bg: rgba(144, 147, 153, 0.12);
        --ok-color: #a8abb2;
    }

    /* Base Styling Override for Analytics */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 26px;
    }
    .page-title {
        font-size: 21px;
        font-weight: 700;
        color: var(--text-primary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .page-subtitle {
        font-size: 13px;
        color: var(--text-secondary);
    }

    /* Metrics Grid */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: transform 0.2s, box-shadow 0.2s, background-color 0.3s, border-color 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .stat-details {
        flex: 1;
        min-width: 0;
    }
    .stat-value {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        margin-bottom: 4px;
    }
    .stat-label {
        font-size: 12px;
        color: var(--text-secondary);
        font-weight: 500;
    }

    /* Section Card */
    .section-card {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        margin-bottom: 30px;
        transition: background-color 0.3s, border-color 0.3s;
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Table Responsive Wrapper */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Table Design */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }
    .data-table thead th {
        padding: 14px 20px;
        font-size: 11.5px;
        font-weight: 700;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        text-align: left;
        background: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }
    .data-table tbody td {
        padding: 14px 20px;
        font-size: 14px;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
        transition: color 0.3s, border-color 0.3s;
    }
    .data-table tbody tr:last-child td {
        border-bottom: none;
    }
    .data-table tbody tr:hover {
        background: var(--bg-secondary);
    }

    /* Custom Elements */
    .prod-rank {
        font-weight: 700;
        font-size: 15px;
        color: var(--accent-color);
        text-align: center;
        display: inline-block;
        width: 24px;
    }
    .prod-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .prod-image {
        width: 44px;
        height: 44px;
        border-radius: 6px;
        object-fit: cover;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        flex-shrink: 0;
    }
    .prod-name {
        font-weight: 600;
        color: var(--text-primary);
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .prod-cat {
        font-size: 11.5px;
        color: var(--text-secondary);
        margin-top: 2px;
        font-weight: 500;
    }

    /* Customer block */
    .customer-meta {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 2px;
    }

    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        transition: background-color 0.3s, color 0.3s;
    }
    .badge-success { background: var(--success-bg); color: var(--success-color); }
    .badge-warning { background: var(--warning-bg); color: var(--warning-color); }
    .badge-danger { background: var(--danger-bg); color: var(--danger-color); }
    .badge-info { background: var(--info-bg); color: var(--info-color); }
    .badge-ok { background: var(--ok-bg); color: var(--ok-color); }

    .recommendation-text {
        font-weight: 600;
        font-size: 13.5px;
    }
    .text-warning { color: var(--warning-color); }
    .text-danger { color: var(--danger-color); }
    .text-success { color: var(--success-color); }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div class="page-title">Phân tích dữ liệu</div>
        <div class="page-subtitle">Hệ thống phân tích rủi ro khách hàng rời bỏ và hiệu suất tài chính sản phẩm</div>
    </div>
</div>

<!-- Section 1: Metric Cards -->
<div class="metrics-grid">
    <!-- Card 1: Top Revenue Product -->
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(166, 130, 92, 0.1); color: #a6825c;">
            <i class="fa-solid fa-crown"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value" title="<?= htmlspecialchars($highest_revenue_product_name) ?>">
                <?= htmlspecialchars($highest_revenue_product_name) ?>
            </span>
            <span class="stat-label">Sản phẩm mang lại doanh thu cao nhất (<?= number_format($highest_revenue, 0, ',', '.') ?>đ)</span>
        </div>
    </div>

    <!-- Card 2: Total Items Sold -->
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
            <i class="fa-solid fa-cart-shopping"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value" style="font-size: 20px;">
                <?= number_format($total_items_sold) ?> sản phẩm
            </span>
            <span class="stat-label">Tổng số lượng đã bán toàn shop</span>
        </div>
    </div>

    <!-- Card 3: Total Global Revenue -->
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
            <i class="fa-solid fa-money-bill-trend-up"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value" style="font-size: 20px;">
                <?= number_format($total_global_revenue, 0, ',', '.') ?>đ
            </span>
            <span class="stat-label">Tổng doanh thu toàn hệ thống</span>
        </div>
    </div>
</div>

<!-- Section 2: Customer Churn Risk Prediction (TOP SECTION) -->
<div class="section-card">
    <h2 class="section-title">
        <i class="fa-solid fa-user-minus" style="color: var(--danger-color);"></i> Top 10 Khách Hàng Có Nguy Cơ Rời Bỏ
    </h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Khách Hàng</th>
                    <th style="text-align: right; width: 130px;">Số Đơn Đã Mua</th>
                    <th style="text-align: right; width: 150px;">Tổng Chi Tiêu</th>
                    <th style="text-align: center; width: 180px;">Đơn Cuối Vào Ngày</th>
                    <th style="text-align: right; width: 140px;">Số Ngày Xa Cách</th>
                    <th style="padding-left: 30px; width: 180px;">Trạng Thái Dự Báo</th>
                    <th style="padding-left: 20px;">Hành Động Khuyến Nghị</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($churn_customers)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        Không phát hiện khách hàng có nguy cơ rời bỏ (DaysSinceLastOrder > 30)
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($churn_customers as $cust): 
                        $days = (int)$cust['DaysSinceLastOrder'];
                        $spent = (float)$cust['TotalSpent'];
                        $orders_count = (int)$cust['TotalOrders'];

                        $status_badge = '';
                        $recommendation = '';

                        if ($days >= 31 && $days <= 90 && ($spent >= 2000000 || $orders_count >= 2)) {
                            $status_badge = '<span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> Nguy cơ rời bỏ </span>';
                            $recommendation = '<span class="recommendation-text text-warning">Gửi Mail Tặng Voucher 15% </span>';
                        } elseif ($days > 90) {
                            $status_badge = '<span class="badge badge-danger"><i class="fa-solid fa-snowflake"></i> Đã rời bỏ </span>';
                            $recommendation = '<span class="recommendation-text text-danger">Chiến dịch Re-marketing </span>';
                        } else {
                            $status_badge = '<span class="badge badge-ok"><i class="fa-solid fa-circle-check"></i> Vận hành ổn định </span>';
                            $recommendation = '<span class="recommendation-text text-success">Chăm sóc &amp; Duy trì liên hệ</span>';
                        }
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($cust['customer_name']) ?></div>
                            <div class="customer-meta"><?= htmlspecialchars($cust['email'] ?? 'Không có email') ?></div>
                        </td>
                        <td style="text-align: right; font-weight: 600;"><?= number_format($orders_count) ?></td>
                        <td style="text-align: right; font-weight: 600; color: #2e7d32;"><?= number_format($spent, 0, ',', '.') ?>đ</td>
                        <td style="text-align: center; color: var(--text-secondary);"><?= date('d/m/Y', strtotime($cust['LastOrderDate'])) ?></td>
                        <td style="text-align: right; font-weight: 600; color: var(--danger-color);"><?= number_format($days) ?> ngày</td>
                        <td style="padding-left: 30px;"><?= $status_badge ?></td>
                        <td style="padding-left: 20px;"><?= $recommendation ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Section 3: Product Financial Performance (BOTTOM SECTION) -->
<div class="section-card">
    <h2 class="section-title">
        <i class="fa-solid fa-chart-pie" style="color: var(--accent-color);"></i> Danh sách hiệu suất tài chính sản phẩm toàn hệ thống
    </h2>
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
                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-secondary);">
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
                        $revenue_contribution = $total_global_revenue > 0 ? ($prod_revenue / $total_global_revenue) * 100 : 0;
                        
                        // Actionable Insight Badges Logic
                        $badges = [];
                        if ($rank <= 3) {
                            $badges[] = '<span class="badge badge-success"><i class="fa-solid fa-star"></i> Best Seller </span>';
                        }
                        if ($revenue_contribution > 8) {
                            $badges[] = '<span class="badge badge-warning"><i class="fa-solid fa-money-bill-wave"></i> Tỷ trọng doanh thu cao </span>';
                        }
                        if ($revenue_contribution < 1.5  && $stock > 300) {
                            $badges[] = '<span class="badge badge-danger"><i class="fa-solid fa-chart-line-down"></i> Cần Kích Cầu </span>';
                        }
                        
                        if (empty($badges)) {
                            $badges[] = '<span class="badge badge-ok"><i class="fa-solid fa-check"></i> Vận Hành Ổn Định </span>';
                        }
                    ?>
                    <tr>
                        <td style="text-align: center;"><span class="prod-rank">#<?= $rank ?></span></td>
                        <td>
                            <div class="prod-info">
                                <?php if(!empty($prod['image'])): ?>
                                    <?php $img_src = (strpos($prod['image'], 'http') === 0) ? $prod['image'] : '../' . $prod['image']; ?>
                                    <img src="<?= htmlspecialchars($img_src) ?>" class="prod-image" alt="Product Image" onerror="this.outerHTML='<div class=\'prod-image\' style=\'display:flex;align-items:center;justify-content:center;color:#aaa;\'><i class=\'fa-solid fa-image\'></i></div>';">
                                <?php else: ?>
                                    <div class="prod-image" style="display:flex;align-items:center;justify-content:center;color:#aaa;"><i class="fa-solid fa-image"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div class="prod-name"><?= htmlspecialchars($prod['name']) ?></div>
                                    <div class="prod-cat"><?= htmlspecialchars($prod['category_name'] ?? 'Không có danh mục') ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: right; font-weight: 600;"><?= number_format($sold) ?></td>
                        <td style="text-align: right; font-weight: 600; color: var(--accent-color);"><?= number_format($stock) ?></td>
                        <td style="text-align: right; color: var(--text-secondary);"><?= number_format($price, 0, ',', '.') ?>đ</td>
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

</div><!-- /.admin-content -->
</main>
</body>
</html>
