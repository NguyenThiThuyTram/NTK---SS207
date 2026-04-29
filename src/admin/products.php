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

// Lấy danh sách sản phẩm kèm tổng tồn kho từ các biến thể và tên danh mục
$stmt = $conn->prepare("
    SELECT 
        p.product_id, 
        p.name, 
        p.image, 
        p.sold_count, 
        p.status,
        c.name as category_name,
        COALESCE(SUM(pv.stock), 0) as total_stock
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    GROUP BY p.product_id
    ORDER BY p.product_id ASC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tổng số sản phẩm
$total_products = count($products);

// Include sidebar
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    /* ── Font & Base ─────────────────────────────────────── */
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 26px;
    }
    .page-header-left {}
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
    .btn-add {
        background: #2f1c00;
        color: #ffffff;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s;
        border: none;
        cursor: pointer;
    }
    .btn-add:hover { background: #1a0f00; }

    /* ── Table Container ─────────────────────────────────── */
    .section-card {
        background: #ffffff;
        border-radius: 10px;
        border: 1px solid #e5e5e5;
        overflow: hidden;
    }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead th {
        padding: 14px 24px;
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
        padding: 14px 24px;
        font-size: 14px;
        color: #111111;
        border-bottom: 1px solid #f5f1eb;
        vertical-align: middle;
    }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table tbody tr:hover { background: #fafaf8; }

    /* Custom Elements */
    .link-detail {
        color: inherit;
        text-decoration: none;
        transition: color 0.2s;
    }
    .link-detail:hover {
        color: #2f1c00;
        text-decoration: underline;
    }
    .prod-id {
        font-weight: 500;
        font-size: 13px;
        color: #555;
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
        font-weight: 500;
        color: #111111;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.4;
    }
    .prod-cat {
        font-size: 12px;
        color: #888;
        margin-top: 2px;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
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
        margin-right: 6px;
    }
    /* Các trạng thái theo yêu cầu */
    .status-instock { background: #eafaf1; color: #27ae60; }
    .status-outstock { background: #fdf0ef; color: #c0392b; }
    .status-stopped { background: #f5f1eb; color: #888; }

    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 8px;
    }
    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        text-decoration: none;
        transition: all 0.2s;
        background: #f5f1eb;
        color: #555;
    }
    .btn-icon.edit:hover { background: #2f1c00; color: #fff; }
    .btn-icon.delete:hover { background: #c0392b; color: #fff; }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div class="page-title">Quản lý sản phẩm</div>
        <div class="page-subtitle"><?= $total_products ?> sản phẩm</div>
    </div>
    <a href="add_product.php" class="btn-add"><i class="fa-solid fa-plus"></i> Thêm sản phẩm</a>
</div>

<div class="section-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Sản phẩm</th>
                <th>Tồn kho</th>
                <th>Đã bán</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:40px; color:#aaa;">
                    Chưa có sản phẩm nào
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($products as $prod): 
                    // Xử lý logic trạng thái
                    $total_stock = (int)$prod['total_stock'];
                    $status_val = (int)$prod['status'];
                    
                    if ($status_val === 0) {
                        $status_class = 'status-stopped';
                        $status_text = 'Ngừng kinh doanh';
                    } elseif ($total_stock > 0) {
                        $status_class = 'status-instock';
                        $status_text = 'Còn hàng';
                    } else {
                        $status_class = 'status-outstock';
                        $status_text = 'Hết hàng';
                    }
                ?>
                <tr>
                    <td><span class="prod-id"><?= htmlspecialchars($prod['product_id']) ?></span></td>
                    <td>
                        <div class="prod-info">
                            <?php if(!empty($prod['image'])): ?>
                                <?php $img_src = (strpos($prod['image'], 'http') === 0) ? $prod['image'] : '../' . $prod['image']; ?>
                                <img src="<?= htmlspecialchars($img_src) ?>" class="prod-image" alt="Product Image" onerror="this.outerHTML='<div class=\'prod-image\' style=\'display:flex;align-items:center;justify-content:center;color:#aaa;\'><i class=\'fa-solid fa-image\'></i></div>';">
                            <?php else: ?>
                                <div class="prod-image" style="display:flex;align-items:center;justify-content:center;color:#aaa;"><i class="fa-solid fa-image"></i></div>
                            <?php endif; ?>
                            <div>
                                <div class="prod-name">
                                    <a href="product_detail.php?id=<?= urlencode($prod['product_id']) ?>" class="link-detail">
                                        <?= htmlspecialchars($prod['name']) ?>
                                    </a>
                                </div>
                                <div class="prod-cat"><?= htmlspecialchars($prod['category_name'] ?? 'Không có danh mục') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span style="color: #111; font-weight: 500;"><?= $total_stock ?></span></td>
                    <td><?= number_format($prod['sold_count'] ?? 0) ?></td>
                    <td>
                        <span class="status-badge <?= $status_class ?>">
                            <?= $status_text ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="add_product.php?id=<?= urlencode($prod['product_id']) ?>" class="btn-icon edit" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></a>
                            <a href="#" class="btn-icon delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');"><i class="fa-solid fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div><!-- /.admin-content -->
</main>
</body>
</html>
