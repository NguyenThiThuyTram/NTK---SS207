<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra quyền Admin (Bỏ comment để kích hoạt bảo mật)
// if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) { header("Location: ../views/login.php"); exit; }

require_once __DIR__ . '/../config/database.php';

// 2. Xử lý cập nhật tồn kho nhanh
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $variant_id = $_POST['variant_id'];
    $new_stock = (int)$_POST['stock'];
    $sku = $_POST['sku'];
    
    $stmt_update = $conn->prepare("UPDATE product_variants SET stock = ? WHERE variant_id = ?");
    if ($stmt_update->execute([$new_stock, $variant_id])) {
        $message = "Đã cập nhật mã <strong>$sku</strong> thành <strong>$new_stock</strong> sản phẩm.";
    }
}

// 3. Xử lý Bộ lọc & Tìm kiếm
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? 'all';

$whereClauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(p.name LIKE ? OR pv.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_status === 'out') {
    $whereClauses[] = "pv.stock <= 0";
} elseif ($filter_status === 'low') {
    $whereClauses[] = "pv.stock > 0 AND pv.stock <= 10";
}

$whereSql = implode(" AND ", $whereClauses);

// 4. Truy vấn dữ liệu JOIN products và product_variants
$query = "
    SELECT 
        pv.variant_id, pv.sku, pv.size, pv.color, pv.stock, pv.image AS variant_img,
        p.product_id, p.name AS product_name, p.image AS product_img
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.product_id
    WHERE $whereSql
    ORDER BY p.name ASC, pv.color ASC
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$inventory_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admin_current_page = 'inventory.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    .inventory-wrapper { padding: 25px; background: #fdfdfb; min-height: 100vh; }
    .filter-bar { display: flex; gap: 15px; margin-bottom: 25px; align-items: flex-end; flex-wrap: wrap; background:#fff; padding: 20px; border-radius:12px; border:1px solid #eee; }
    
    .table-container { background: #fff; border: 1px solid #eee; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .inv-table { width: 100%; border-collapse: collapse; }
    .inv-table th { background: #fafafa; padding: 15px; text-align: left; font-size: 11px; text-transform: uppercase; color: #999; letter-spacing: 0.5px; border-bottom: 1px solid #eee; }
    .inv-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; }
    
    /* Hiệu ứng link sản phẩm */
    .prod-link { color: #111; text-decoration: none; font-weight: 600; transition: 0.2s; display: flex; align-items: center; gap: 12px; }
    .prod-link:hover { color: #2f1c00; }
    .prod-link:hover span { text-decoration: underline; }
    
    .img-preview { width: 50px; height: 65px; object-fit: cover; border-radius: 6px; background: #f5f5f5; border: 1px solid #eee; }
    
    .stock-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .badge-ok { background: #e6f7ed; color: #2ecc71; }
    .badge-warn { background: #fff7e6; color: #f39c12; }
    .badge-danger { background: #fef0f0; color: #e74c3c; }

    .input-edit { width: 75px; padding: 8px; border: 1px solid #ddd; border-radius: 6px; text-align: center; outline: none; }
    .input-edit:focus { border-color: #2f1c00; }
    .btn-update-stock { background: #2f1c00; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: 0.2s; }
    .btn-update-stock:hover { background: #1a0f00; }
</style>

<div class="inventory-wrapper">
    <div class="page-header" style="margin-bottom: 30px;">
        <h1 class="page-title" style="margin:0; font-size: 24px;">Quản lý tồn kho</h1> <br>
        <p style="color: #666; font-size: 14px;">Theo dõi chi tiết biến thể và cập nhật số lượng hàng hóa.</p>
    </div>

    <form method="GET" class="filter-bar">
        <div style="flex: 1; min-width: 250px;">
            <label style="font-size: 12px; font-weight: 600; display:block; margin-bottom: 5px;">Tìm kiếm sản phẩm</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tên SP hoặc mã SKU..." class="form-control" style="width: 100%;">
        </div>
        <div>
            <label style="font-size: 12px; font-weight: 600; display:block; margin-bottom: 5px;">Trạng thái kho</label>
            <select name="status" class="form-control" style="width: 160px;">
                <option value="all">Tất cả sản phẩm</option>
                <option value="low" <?= $filter_status == 'low' ? 'selected' : '' ?>>Sắp hết hàng (≤10)</option>
                <option value="out" <?= $filter_status == 'out' ? 'selected' : '' ?>>Đã hết hàng (0)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height: 42px; background: #ffffff; border: none;"><i class="fa-solid fa-filter"></i> Lọc dữ liệu</button>
        <?php if(!empty($search) || $filter_status != 'all'): ?>
            <a href="inventory.php" style="margin-left: 10px; font-size: 13px; color: #c0392b; text-decoration: none;">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <?php if ($message): ?>
        <div style="background: #e6f7ed; color: #1e8449; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #2ecc71;">
            <i class="fa-solid fa-check-circle"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Sản phẩm & Hình ảnh</th>
                    <th>Mã SKU</th>
                    <th>Phân loại (Màu/Size)</th>
                    <th style="text-align: center;">Tồn thực tế</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventory_list)): ?>
                    <tr><td colspan="6" style="text-align:center; padding: 50px; color: #aaa;">Không tìm thấy dữ liệu tồn kho nào phù hợp.</td></tr>
                <?php else: ?>
                    <?php foreach ($inventory_list as $item): 
                        $stk = (int)$item['stock'];
                        // Ưu tiên lấy ảnh biến thể, nếu không có mới lấy ảnh sản phẩm chính
                        $img = !empty($item['variant_img']) ? $item['variant_img'] : $item['product_img'];
                        $img_path = (strpos($img, 'http') === 0) ? $img : '../' . $img;
                    ?>
                    <tr>
                        <td>
                            <a href="product_detail.php?id=<?= urlencode($item['product_id']) ?>" class="prod-link">
                                <img src="<?= $img_path ?>" class="img-preview" onerror="this.src='../assets/images/no-image.png'">
                                <span><?= htmlspecialchars($item['product_name']) ?></span>
                            </a>
                        </td>
                        <td style="font-family: monospace; font-size: 13px; color: #666;"><?= $item['sku'] ?></td>
                        <td>
                            <span style="color:#888;"><?= $item['color'] ?></span> / <strong><?= $item['size'] ?></strong>
                        </td>
                        <td style="text-align: center;">
                            <form method="POST" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <input type="hidden" name="variant_id" value="<?= $item['variant_id'] ?>">
                                <input type="hidden" name="sku" value="<?= $item['sku'] ?>">
                                <input type="number" name="stock" value="<?= $stk ?>" class="input-edit" min="0">
                                <button type="submit" name="update_stock" class="btn-update-stock" title="Lưu số lượng"><i class="fa-solid fa-floppy-disk"></i></button>
                            </form>
                        </td>
                        <td>
                            <?php if($stk <= 0): ?>
                                <span class="stock-badge badge-danger">Hết hàng</span>
                            <?php elseif($stk <= 10): ?>
                                <span class="stock-badge badge-warn">Sắp hết (<?= $stk ?>)</span>
                            <?php else: ?>
                                <span class="stock-badge badge-ok">Còn hàng</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="add_product.php?id=<?= urlencode($item['product_id']) ?>" class="btn-icon edit" title="Chỉnh sửa sản phẩm"><i class="fa-solid fa-pen"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>