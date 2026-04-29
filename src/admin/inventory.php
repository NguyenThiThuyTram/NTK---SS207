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
    /* ── Page header ──────────────────────────────── */
    .page-title { font-size: 21px; font-weight: 700; color: #111111; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .page-subtitle { font-size: 13px; color: #999; margin-top: 4px; }

    /* ── Toolbar ──────────────────────────────────── */
    .toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    .search-wrap {
        position: relative;
        flex: 1;
    }
    .search-wrap i {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
        font-size: 13px;
        pointer-events: none;
    }
    .search-input {
        width: 100%;
        padding: 10px 14px 10px 38px;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        font-size: 13.5px;
        outline: none;
        color: #111;
        background: #fff;
        transition: border-color 0.2s;
    }
    .search-input:focus { border-color: #2f1c00; }

    .filter-select {
        padding: 10px 36px 10px 14px;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        font-size: 13.5px;
        color: #333;
        background: #fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'><polyline points='6 9 12 15 18 9'/></svg>") no-repeat right 12px center;
        -webkit-appearance: none;
        appearance: none;
        outline: none;
        cursor: pointer;
        transition: border-color 0.2s;
        min-width: 180px;
    }
    .filter-select:focus { border-color: #2f1c00; }

    .btn-reset {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        font-size: 13px;
        color: #c0392b;
        background: #fff;
        text-decoration: none;
        white-space: nowrap;
        transition: background 0.2s, border-color 0.2s;
    }
    .btn-reset:hover { background: #fdf0ef; border-color: #e74c3c; }

    /* ── Table ────────────────────────────────────── */
    .section-card {
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e5e5e5;
        overflow: hidden;
        margin-bottom: 26px;
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

    /* Product cell */
    .prod-info { display: flex; align-items: center; gap: 12px; }
    .prod-image {
        width: 44px; height: 44px;
        border-radius: 6px;
        object-fit: cover;
        background: #f5f1eb;
        border: 1px solid #e5e5e5;
        flex-shrink: 0;
    }
    .prod-name {
        font-weight: 400;
        color: #111111;
        font-size: 13.5px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.4;
        text-decoration: none;
    }
    .prod-name:hover { color: #2f1c00; text-decoration: underline; }
    .prod-id {
        font-weight: 500;
        font-size: 13px;
        color: #555;
    }
    .sku-badge {
        font-family: 'Courier New', monospace;
        font-size: 12.5px;
        color: #555;
        background: #f5f1eb;
        padding: 3px 8px;
        border-radius: 4px;
    }
    .variant-info { font-size: 13px; color: #444; white-space: nowrap; }
    .variant-info .color { color: #888; }

    /* Stock cell */
    .stock-cell { display: flex; align-items: center; gap: 10px; }
    .stock-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 11px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    .stock-badge::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: currentColor;
        margin-right: 5px;
        opacity: 0.75;
    }
    .badge-ok     { background: #eafaf1; color: #27ae60; }
    .badge-warn   { background: #fff8e6; color: #d48806; }
    .badge-danger { background: #fdf0ef; color: #e74c3c; }

    /* Inline stock edit */
    .stock-edit-form { display: flex; align-items: center; gap: 6px; }
    .input-edit {
        width: 72px;
        padding: 7px 10px;
        border: 1px solid #e5e5e5;
        border-radius: 6px;
        text-align: center;
        font-size: 13.5px;
        outline: none;
        transition: border-color 0.2s;
    }
    .input-edit:focus { border-color: #2f1c00; }
    .btn-save {
        width: 32px; height: 32px;
        background: #2f1c00;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        transition: background 0.2s;
        flex-shrink: 0;
    }
    .btn-save:hover { background: #1a0f00; }

    /* Action */
    .btn-icon {
        width: 32px; height: 32px;
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

    /* Success message */
    .alert-success {
        background: #eafaf1;
        color: #1e8449;
        padding: 14px 18px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #27ae60;
        font-size: 13.5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>

<!-- Page Header -->
<div class="page-header" style="margin-bottom: 26px;">
    <div class="page-title">Quản lý tồn kho</div>
    <p class="page-subtitle">Theo dõi chi tiết biến thể và cập nhật số lượng hàng hóa.</p>
</div>

<!-- Toolbar: Search + Filter (auto-submit) -->
<form method="GET" id="filterForm">
    <div class="toolbar">
        <div class="search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Tìm theo tên sản phẩm hoặc mã SKU..."
                value="<?= htmlspecialchars($search) ?>"
                oninput="clearTimeout(window._st); window._st = setTimeout(() => document.getElementById('filterForm').submit(), 500)"
            >
        </div>

        <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
            <option value="low"  <?= $filter_status === 'low'  ? 'selected' : '' ?>>Sắp hết hàng (≤ 10)</option>
            <option value="out"  <?= $filter_status === 'out'  ? 'selected' : '' ?>>Đã hết hàng</option>
        </select>

        <?php if (!empty($search) || $filter_status !== 'all'): ?>
        <a href="inventory.php" class="btn-reset">
            <i class="fa-solid fa-xmark"></i> Xóa lọc
        </a>
        <?php endif; ?>
    </div>
</form>

<!-- Success message -->
<?php if ($message): ?>
<div class="alert-success">
    <i class="fa-solid fa-circle-check"></i> <?= $message ?>
</div>
<?php endif; ?>

<!-- Table -->
<div class="section-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Sản phẩm</th>
                <th>Mã SKU</th>
                <th>Màu / Size</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($inventory_list)): ?>
            <tr>
                <td colspan="7" style="text-align:center; padding: 50px; color: #aaa;">
                    Không tìm thấy dữ liệu tồn kho phù hợp.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($inventory_list as $item):
                $stk = (int)$item['stock'];
                $img = !empty($item['variant_img']) ? $item['variant_img'] : $item['product_img'];
                $img_path = !empty($img) ? ((strpos($img, 'http') === 0) ? $img : '../' . $img) : '';

                if ($stk <= 0)  { $badge_class = 'badge-danger'; $badge_text = 'Hết hàng'; }
                elseif ($stk <= 10) { $badge_class = 'badge-warn';   $badge_text = 'Sắp hết'; }
                else                { $badge_class = 'badge-ok';     $badge_text = 'Còn hàng'; }
            ?>
            <tr>
                <!-- ID -->
                <td><span class="prod-id"><?= htmlspecialchars($item['product_id']) ?></span></td>

                <!-- Sản phẩm -->
                <td>
                    <div class="prod-info">
                        <?php if ($img_path): ?>
                            <img src="<?= htmlspecialchars($img_path) ?>" class="prod-image"
                                 onerror="this.outerHTML='<div class=\'prod-image\' style=\'display:flex;align-items:center;justify-content:center;color:#aaa;\'><i class=\'fa-solid fa-image\'></i></div>'">
                        <?php else: ?>
                            <div class="prod-image" style="display:flex;align-items:center;justify-content:center;color:#aaa;">
                                <i class="fa-solid fa-image"></i>
                            </div>
                        <?php endif; ?>
                        <a href="product_detail.php?id=<?= urlencode($item['product_id']) ?>" class="prod-name">
                            <?= htmlspecialchars($item['product_name']) ?>
                        </a>
                    </div>
                </td>

                <!-- SKU -->
                <td><span class="sku-badge"><?= htmlspecialchars($item['sku']) ?></span></td>

                <!-- Màu / Size -->
                <td>
                    <span class="variant-info">
                        <span class="color"><?= htmlspecialchars($item['color']) ?></span>
                        &nbsp;/&nbsp;
                        <strong><?= htmlspecialchars($item['size']) ?></strong>
                    </span>
                </td>

                <!-- Tồn kho (inline edit) -->
                <td>
                    <form method="POST" class="stock-edit-form">
                        <input type="hidden" name="variant_id" value="<?= $item['variant_id'] ?>">
                        <input type="hidden" name="sku" value="<?= htmlspecialchars($item['sku']) ?>">
                        <input type="number" name="stock" value="<?= $stk ?>" class="input-edit" min="0">
                        <button type="submit" name="update_stock" class="btn-save" title="Lưu">
                            <i class="fa-solid fa-floppy-disk"></i>
                        </button>
                    </form>
                </td>

                <!-- Trạng thái -->
                <td>
                    <span class="stock-badge <?= $badge_class ?>"><?= $badge_text ?></span>
                </td>

                <!-- Hành động -->
                <td>
                    <a href="add_product.php?id=<?= urlencode($item['product_id']) ?>" class="btn-icon edit" title="Chỉnh sửa sản phẩm">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
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