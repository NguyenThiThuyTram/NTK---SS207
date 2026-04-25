<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header("Location: products.php");
    exit;
}

// Fetch product & category name
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.product_id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "Không tìm thấy sản phẩm.";
    exit;
}

// Fetch variants
$stmt_var = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_id ASC");
$stmt_var->execute([$id]);
$variants = $stmt_var->fetchAll(PDO::FETCH_ASSOC);

// Calculate total stock
$total_stock = array_reduce($variants, function($carry, $item) {
    return $carry + (int)$item['stock'];
}, 0);

$admin_current_page = 'products.php';
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
        width: 140px;
        height: 140px;
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
        line-height: 1.4;
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
        <div class="page-title">Chi tiết sản phẩm</div>
        <div class="page-subtitle">Thông tin và biến thể của sản phẩm</div>
    </div>
    <a href="products.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Trở về</a>
</div>

<div class="detail-grid">
    
    <!-- Cột trái: Thông tin sản phẩm -->
    <div class="panel info-card">
        <?php if(!empty($product['image'])): ?>
            <?php $img_src = (strpos($product['image'], 'http') === 0) ? $product['image'] : '../' . $product['image']; ?>
            <img src="<?= htmlspecialchars($img_src) ?>" class="info-image" alt="Product" onerror="this.outerHTML='<div class=\'info-image\' style=\'display:flex;align-items:center;justify-content:center;font-size:36px;color:#ccc;\'><i class=\'fa-solid fa-image\'></i></div>';">
        <?php else: ?>
            <div class="info-image" style="display:flex;align-items:center;justify-content:center;font-size:36px;color:#ccc;"><i class="fa-solid fa-image"></i></div>
        <?php endif; ?>
        
        <div class="info-title"><?= htmlspecialchars($product['name']) ?></div>
        <div class="info-id">ID: <?= htmlspecialchars($product['product_id']) ?></div>
        
        <div style="margin-top: 12px;">
            <a href="add_product.php?id=<?= urlencode($product['product_id']) ?>" class="btn-back" style="background: #2f1c00; color: #fff; border:none; width:100%; justify-content:center;">
                <i class="fa-solid fa-pen"></i> Chỉnh sửa sản phẩm
            </a>
        </div>

        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Danh mục:</span>
                <span class="info-value"><?= htmlspecialchars($product['category_name'] ?? 'Không có') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Trạng thái:</span>
                <span class="info-value">
                    <?php if($product['status'] == 1 && $total_stock > 0): ?>
                        <span style="color:#27ae60;">Công khai (Còn hàng)</span>
                    <?php elseif($product['status'] == 0): ?>
                        <span style="color:#888;">Bản nháp</span>
                    <?php else: ?>
                        <span style="color:#c0392b;">Hết hàng</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Tổng tồn kho:</span>
                <span class="info-value"><?= $total_stock ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Đã bán:</span>
                <span class="info-value"><?= number_format($product['sold_count'] ?? 0) ?></span>
            </div>
            <div class="info-item" style="flex-direction: column; align-items:flex-start; margin-top:16px;">
                <span class="info-label" style="margin-bottom: 8px;">Mô tả sản phẩm:</span>
                <span class="info-value" style="font-weight: 400; line-height:1.5; color:#555; text-align:left;">
                    <?= !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : '<i>Chưa có mô tả</i>' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Cột phải: Danh sách biến thể -->
    <div class="panel">
        <div class="panel-title">Biến thể sản phẩm (<?= count($variants) ?>)</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Màu sắc</th>
                    <th>Kích cỡ (Size)</th>
                    <th>Giá bán</th>
                    <th>Tồn kho</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($variants)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 40px; color:#888;">Sản phẩm chưa có biến thể nào. Vui lòng chỉnh sửa để thêm biến thể.</td></tr>
                <?php else: ?>
                    <?php foreach($variants as $v): ?>
                        <tr>
                            <td style="color:#555; font-family: monospace;"><?= htmlspecialchars($v['sku'] ?? 'N/A') ?></td>
                            <td style="font-weight:500;"><?= htmlspecialchars($v['color']) ?></td>
                            <td><?= htmlspecialchars($v['size']) ?></td>
                            <td style="color:#c0392b; font-weight:600;"><?= number_format($v['sale_price'] ?? $v['original_price'] ?? 0, 0, ',', '.') ?>đ</td>
                            <td>
                                <?php if($v['stock'] > 0): ?>
                                    <span style="font-weight:600;"><?= (int)$v['stock'] ?></span>
                                <?php else: ?>
                                    <span style="color:#c0392b; font-weight:600;">Hết</span>
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
