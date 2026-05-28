<?php
require_once 'auth_check.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ── TASK 2 SECTION 1: METRICS FETCHING ──────────────────────────────────

// 1. Top Seller
$stmt_top = $conn->query("SELECT name, sold_count FROM products WHERE status = 1 ORDER BY sold_count DESC LIMIT 1");
$top_seller = $stmt_top->fetch(PDO::FETCH_ASSOC);

// 2. Most Viewed
$stmt_viewed = $conn->query("SELECT name, view_count FROM products WHERE status = 1 ORDER BY view_count DESC LIMIT 1");
$most_viewed = $stmt_viewed->fetch(PDO::FETCH_ASSOC);

// 3. Global Store Conversion Rate
$stmt_global = $conn->query("SELECT SUM(sold_count) as total_sold, SUM(view_count) as total_views FROM products WHERE status = 1");
$global_stats = $stmt_global->fetch(PDO::FETCH_ASSOC);

$total_sold = (float)($global_stats['total_sold'] ?? 0);
$total_views = (float)($global_stats['total_views'] ?? 0);
$global_conversion_rate = $total_views > 0 ? ($total_sold / $total_views) * 100 : 0;

// ── TASK 2 SECTION 2: TABLE FETCHING ─────────────────────────────────────

$stmt_table = $conn->prepare("
    SELECT 
        p.product_id, 
        p.name, 
        p.image, 
        p.sold_count, 
        p.view_count, 
        c.name as category_name,
        COALESCE(SUM(pv.stock), 0) as total_stock
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE p.status = 1
    GROUP BY p.product_id
    ORDER BY p.sold_count DESC, p.view_count DESC
    LIMIT 10
");
$stmt_table->execute();
$products = $stmt_table->fetchAll(PDO::FETCH_ASSOC);

// Include sidebar
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    /* ── Custom Premium Style for Analytics Dashboard ── */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 26px;
    }
    .page-title {
        font-size: 21px;
        font-weight: 700;
        color: #111111;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .page-subtitle {
        font-size: 13px;
        color: #999;
    }

    /* Metrics Grid */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: #ffffff;
        border: 1px solid #e5e5e5;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: transform 0.2s, box-shadow 0.2s;
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
        color: #111111;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        margin-bottom: 4px;
    }
    .stat-label {
        font-size: 12px;
        color: #888888;
        font-weight: 500;
    }

    /* Section Card */
    .section-card {
        background: #ffffff;
        border: 1px solid #e5e5e5;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        overflow: hidden;
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #111111;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Table */
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    .data-table thead th {
        padding: 14px 20px;
        font-size: 11.5px;
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
        padding: 14px 20px;
        font-size: 14px;
        color: #111111;
        border-bottom: 1px solid #f5f1eb;
        vertical-align: middle;
    }
    .data-table tbody tr:last-child td {
        border-bottom: none;
    }
    .data-table tbody tr:hover {
        background: #fafaf8;
    }

    /* Custom Elements */
    .prod-rank {
        font-weight: 700;
        font-size: 15px;
        color: #a6825c;
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
        background: #f5f1eb;
        border: 1px solid #e5e5e5;
        flex-shrink: 0;
    }
    .prod-name {
        font-weight: 600;
        color: #111111;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .prod-cat {
        font-size: 11.5px;
        color: #888;
        margin-top: 2px;
        font-weight: 500;
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
    }
    .badge-success { background: #eafaf1; color: #27ae60; }
    .badge-warning { background: #fdf6ec; color: #e6a23c; }
    .badge-danger { background: #fef0f0; color: #f56c6c; }
    .badge-ok { background: #f4f4f5; color: #909399; }

    /* Dark Mode Overrides */
    body.dark-mode .stat-card {
        background: #1e1e1e;
        border-color: #2a2a2a;
    }
    body.dark-mode .stat-value {
        color: #ffffff;
    }
    body.dark-mode .section-card {
        background: #1e1e1e;
        border-color: #2a2a2a;
    }
    body.dark-mode .section-title {
        color: #ffffff;
    }
    body.dark-mode .data-table thead th {
        background: #1a1a1a;
        border-bottom-color: #2a2a2a;
    }
    body.dark-mode .data-table tbody td {
        color: #cccccc;
        border-bottom-color: #252525;
    }
    body.dark-mode .data-table tbody tr:hover {
        background: #252525;
    }
    body.dark-mode .prod-name {
        color: #ffffff;
    }
    body.dark-mode .badge-success { background: rgba(39, 174, 96, 0.12); color: #2ecc71; }
    body.dark-mode .badge-warning { background: rgba(230, 162, 60, 0.12); color: #e6a23c; }
    body.dark-mode .badge-danger { background: rgba(245, 108, 108, 0.12); color: #f56c6c; }
    body.dark-mode .badge-ok { background: rgba(144, 147, 153, 0.12); color: #a8abb2; }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div class="page-title">Phân tích Bán chạy</div>
        <div class="page-subtitle">Hiệu suất kinh doanh và hành vi xem sản phẩm của khách hàng</div>
    </div>
</div>

<!-- Section 1: Metric Cards -->
<div class="metrics-grid">
    <!-- Card 1: Top Seller -->
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(166, 130, 92, 0.1); color: #a6825c;">
            <i class="fa-solid fa-crown"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value" title="<?= htmlspecialchars($top_seller['name'] ?? 'N/A') ?>">
                <?= htmlspecialchars($top_seller['name'] ?? 'N/A') ?>
            </span>
            <span class="stat-label">Bán chạy nhất (<?= number_format($top_seller['sold_count'] ?? 0) ?> sản phẩm)</span>
        </div>
    </div>

    <!-- Card 2: Most Viewed -->
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
            <i class="fa-solid fa-eye"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value" title="<?= htmlspecialchars($most_viewed['name'] ?? 'N/A') ?>">
                <?= htmlspecialchars($most_viewed['name'] ?? 'N/A') ?>
            </span>
            <span class="stat-label">Xem nhiều nhất (<?= number_format($most_viewed['view_count'] ?? 0) ?> lượt xem)</span>
        </div>
    </div>

    <!-- Card 3: Global Conversion Rate -->
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
            <i class="fa-solid fa-percent"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value" style="font-size: 20px;">
                <?= number_format($global_conversion_rate, 2) ?>%
            </span>
            <span class="stat-label">Tỷ lệ chuyển đổi toàn cửa hàng</span>
        </div>
    </div>
</div>

<!-- Section 2 & 3: Smart Analysis Table & Actionable Insights -->
<div class="section-card">
    <h2 class="section-title">Danh sách 10 sản phẩm hàng đầu</h2>
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">Hạng</th>
                    <th>Sản phẩm</th>
                    <th style="text-align: right; width: 110px;">Đã bán</th>
                    <th style="text-align: right; width: 110px;">Lượt xem</th>
                    <th style="text-align: right; width: 140px;">Tỷ lệ chuyển đổi</th>
                    <th style="padding-left: 30px;">Nhận định & Khuyến nghị</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #aaa;">
                        Không có sản phẩm nào
                    </td>
                </tr>
                <?php else: ?>
                    <?php 
                    $rank = 1;
                    foreach ($products as $prod): 
                        $sold = (int)$prod['sold_count'];
                        $views = (int)$prod['view_count'];
                        $stock = (int)$prod['total_stock'];
                        
                        $conversion = $views > 0 ? ($sold / $views) * 100 : 0;
                        
                        // Actionable Insight Badges Logic
                        $badges = [];
                        if ($rank <= 3) {
                            $badges[] = '<span class="badge badge-success"><i class="fa-solid fa-star"></i> Best Seller ⭐</span>';
                        }
                        if ($views > 40 && $conversion < 5) {
                            $badges[] = '<span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> Tối ưu hình ảnh/Giá ⚠️</span>';
                        }
                        if ($stock < 5 && $sold > 10) {
                            $badges[] = '<span class="badge badge-danger"><i class="fa-solid fa-triangle-exclamation"></i> Hết hàng 🚨</span>';
                        }
                        
                        if (empty($badges)) {
                            $badges[] = '<span class="badge badge-ok"><i class="fa-solid fa-check"></i> Vận hành ổn định ✅</span>';
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
                        <td style="text-align: right; color: #666;"><?= number_format($views) ?></td>
                        <td style="text-align: right; font-weight: 600; color: #2e7d32;"><?= number_format($conversion, 2) ?>%</td>
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
