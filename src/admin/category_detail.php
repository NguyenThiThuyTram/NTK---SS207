<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header("Location: categories.php");
    exit;
}

// Fetch category
$stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    echo "Không tìm thấy danh mục.";
    exit;
}

// Fetch products in category
$stmt_prod = $conn->prepare("
    SELECT p.*, COALESCE(SUM(pv.stock), 0) as total_stock 
    FROM products p 
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id 
    WHERE p.category_id = ? 
    GROUP BY p.product_id
");
$stmt_prod->execute([$id]);
$products = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

$admin_current_page = 'categories.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    .page-title {
        font-size: 21px;
        font-weight: 700;
        color: #111;
        margin-bottom: 4px;
    }
    .page-subtitle {
        font-size: 13px;
        color: #888;
    }
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #555;
        text-decoration: none;
        background: #fff;
        border: 1px solid #ccc;
        padding: 8px 16px;
        border-radius: 6px;
        transition: all 0.2s;
    }
    .btn-back:hover { background: #f5f5f5; color: #111; }

    /* Layout */
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 992px) {
        .detail-grid { grid-template-columns: 1fr; }
    }

    .panel {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .panel-title {
        font-size: 15px;
        font-weight: 700;
        color: #111;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Info Card */
    .info-card {
        text-align: center;
    }
    .info-image {
        width: 120px;
        height: 120px;
        border-radius: 12px;
        object-fit: cover;
        background: #f5f1eb;
        margin: 0 auto 16px;
        border: 1px solid #e5e5e5;
    }
    .info-title {
        font-size: 18px;
        font-weight: 700;
        color: #111;
        margin-bottom: 8px;
    }
    .info-id {
        display: inline-block;
        background: #f5f1eb;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        margin-bottom: 16px;
    }
    .info-list {
        text-align: left;
        margin-top: 24px;
        border-top: 1px solid #f5f1eb;
        padding-top: 16px;
    }
    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 14px;
    }
    .info-label { color: #888; font-weight: 500; }
    .info-value { color: #111; font-weight: 600; }

    /* Table */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead th {
        padding: 14px 20px;
        font-size: 11.5px;
        font-weight: 700;
        color: #999;
        text-transform: uppercase;
        text-align: left;
        background: #fafaf8;
        border-bottom: 1px solid #e5e5e5;
    }
    .data-table tbody td {
        padding: 14px 20px;
        font-size: 14px;
        color: #111;
        border-bottom: 1px solid #f5f1eb;
        vertical-align: middle;
    }
    .data-table tbody tr:hover { background: #fafaf8; }
    
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-active { background: #eafaf1; color: #27ae60; }
    .status-inactive { background: #fdf0ef; color: #c0392b; }
</style>

<div class="page-header">
    <div>
        <div class="page-title">Chi tiết danh mục</div>
        <div class="page-subtitle">Thông tin chi tiết và danh sách sản phẩm thuộc danh mục</div>
    </div>
    <a href="categories.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Trở về</a>
</div>

<div class="detail-grid">
    
    <!-- Cột trái: Thông tin danh mục -->
    <div class="panel info-card">
        <?php if(!empty($category['image_url'])): ?>
            <?php $img_src = (strpos($category['image_url'], 'http') === 0) ? $category['image_url'] : '../' . $category['image_url']; ?>
            <img src="<?= htmlspecialchars($img_src) ?>" class="info-image" alt="Category" onerror="this.outerHTML='<div class=\'info-image\' style=\'display:flex;align-items:center;justify-content:center;font-size:36px;color:#ccc;\'><i class=\'fa-regular fa-image\'></i></div>';">
        <?php else: ?>
            <div class="info-image" style="display:flex;align-items:center;justify-content:center;font-size:36px;color:#ccc;"><i class="fa-regular fa-image"></i></div>
        <?php endif; ?>
        
        <div class="info-title"><?= htmlspecialchars($category['name']) ?></div>
        <div class="info-id">ID: <?= htmlspecialchars($category['category_id']) ?></div>
        
        <div style="margin-top: 12px;">
            <a href="add_category.php?id=<?= urlencode($category['category_id']) ?>" class="btn-back" style="background: #2f1c00; color: #fff; border:none; width:100%; justify-content:center;">
                <i class="fa-solid fa-pen"></i> Chỉnh sửa danh mục
            </a>
        </div>

        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Slug:</span>
                <span class="info-value"><?= htmlspecialchars($category['slug']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Mức ưu tiên:</span>
                <span class="info-value"><?= (int)$category['priority'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Hiển thị Home:</span>
                <span class="info-value">
                    <?php if($category['is_show_home']): ?>
                        <span style="color:#27ae60;">Có hiển thị</span>
                    <?php else: ?>
                        <span style="color:#c0392b;">Đang ẩn</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-item" style="flex-direction: column; align-items:flex-start;">
                <span class="info-label" style="margin-bottom: 8px;">Mô tả:</span>
                <span class="info-value" style="font-weight: 400; line-height:1.5; color:#555; text-align:left;">
                    <?= !empty($category['description']) ? nl2br(htmlspecialchars($category['description'])) : '<i>Chưa có mô tả</i>' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Cột phải: Danh sách sản phẩm -->
    <div class="panel">
        <div class="panel-title">Sản phẩm trong danh mục (<?= count($products) ?>)</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Tồn kho</th>
                    <th>Đã bán</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($products)): ?>
                    <tr><td colspan="4" style="text-align:center; padding: 40px; color:#888;">Không có sản phẩm nào thuộc danh mục này.</td></tr>
                <?php else: ?>
                    <?php foreach($products as $p): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <?php if(!empty($p['image'])): ?>
                                        <?php $p_img_src = (strpos($p['image'], 'http') === 0) ? $p['image'] : '../' . $p['image']; ?>
                                        <img src="<?= htmlspecialchars($p_img_src) ?>" style="width:40px; height:40px; border-radius:6px; object-fit:cover;" onerror="this.outerHTML='<div style=\'width:40px; height:40px; border-radius:6px; background:#f5f1eb; display:flex; align-items:center; justify-content:center; color:#ccc;\'><i class=\'fa-solid fa-box\'></i></div>';">
                                    <?php else: ?>
                                        <div style="width:40px; height:40px; border-radius:6px; background:#f5f1eb; display:flex; align-items:center; justify-content:center; color:#ccc;"><i class="fa-solid fa-box"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <a href="product_detail.php?id=<?= urlencode($p['product_id']) ?>" style="font-weight:500; color:#111; text-decoration:none; transition: color 0.2s;" onmouseover="this.style.color='#2f1c00'; this.style.textDecoration='underline';" onmouseout="this.style.color='#111'; this.style.textDecoration='none';">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </a>
                                        <div style="font-size:12px; color:#888; margin-top:2px;"><?= htmlspecialchars($p['product_id']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:500;"><?= (int)$p['total_stock'] ?></td>
                            <td><?= number_format($p['sold_count'] ?? 0) ?></td>
                            <td>
                                <?php if($p['status'] == 1 && $p['total_stock'] > 0): ?>
                                    <span class="status-badge status-active">Còn hàng</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Ngừng / Hết hàng</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
</main>
</body>
</html>
