<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

// 1. AJAX: Lấy danh sách biến thể của sản phẩm
if (isset($_GET['action']) && $_GET['action'] === 'get_variants') {
    header('Content-Type: application/json; charset=utf-8');
    $product_id = $_GET['product_id'] ?? '';
    
    $stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = :pid AND is_active = 1 ORDER BY variant_id ASC");
    $stmt->execute(['pid' => $product_id]);
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($variants);
    exit;
}

// 2. Xử lý xóa flash sale
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $fs_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM flash_sales WHERE flash_sale_id = :id");
    $stmt->execute(['id' => $fs_id]);
    header("Location: flash_sales.php?msg=deleted");
    exit;
}

// 3. Xử lý bật/tắt flash sale
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $fs_id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE flash_sales SET status = 1 - status WHERE flash_sale_id = :id");
    $stmt->execute(['id' => $fs_id]);
    header("Location: flash_sales.php?msg=toggled");
    exit;
}

// 4. Xử lý thêm/cập nhật flash sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_flash_sale'])) {
    $sale_date = $_POST['sale_date'] ?? date('Y-m-d');
    $prices = $_POST['flash_sale_price'] ?? []; // array of variant_id => price
    $selected = $_POST['selected_variants'] ?? []; // array of variant_ids
    
    if (!empty($selected)) {
        foreach ($selected as $vid) {
            $price = floatval($prices[$vid] ?? 0);
            if ($price > 0) {
                // Kiểm tra xem đã tồn tại flash sale cho variant này trong ngày chưa
                $check = $conn->prepare("SELECT flash_sale_id FROM flash_sales WHERE variant_id = :vid AND sale_date = :sdate");
                $check->execute(['vid' => $vid, 'sdate' => $sale_date]);
                $existing_id = $check->fetchColumn();
                
                if ($existing_id) {
                    $upd = $conn->prepare("UPDATE flash_sales SET flash_sale_price = :price, status = 1 WHERE flash_sale_id = :id");
                    $upd->execute(['price' => $price, 'id' => $existing_id]);
                } else {
                    $ins = $conn->prepare("INSERT INTO flash_sales (variant_id, sale_date, flash_sale_price, status) VALUES (:vid, :sdate, :price, 1)");
                    $ins->execute(['vid' => $vid, 'sdate' => $sale_date, 'price' => $price]);
                }
            }
        }
        header("Location: flash_sales.php?msg=added");
        exit;
    }
}

// 5. Lấy danh sách sản phẩm phục vụ form thêm mới
$products = $conn->query("SELECT product_id, name FROM products WHERE status = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 6. Lấy danh sách flash sales hiện tại
$sql_list = "SELECT fs.*, pv.color, pv.size, pv.sku, pv.original_price, pv.sale_price as reg_sale_price, p.name as product_name
             FROM flash_sales fs
             JOIN product_variants pv ON fs.variant_id = pv.variant_id
             JOIN products p ON pv.product_id = p.product_id
             ORDER BY fs.sale_date DESC, fs.flash_sale_id DESC";
$flash_sales = $conn->query($sql_list)->fetchAll(PDO::FETCH_ASSOC);

$admin_current_page = 'flash_sales.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    
    .page-title { 
        font-size: 21px; 
        font-weight: 700; 
        color: #111111; 
        text-transform: uppercase; 
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .page-subtitle { font-size: 13px; color: #888; }
    
    /* Layout */
    .fs-layout {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 25px;
        margin-top: 25px;
    }
    
    @media (max-width: 1024px) {
        .fs-layout {
            grid-template-columns: 1fr;
        }
    }
    
    /* Cards */
    .fs-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e5e5e5;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        height: fit-content;
    }
    
    body.dark-mode .fs-card {
        background: #1e1e1e !important;
        border-color: #333 !important;
    }
    
    /* Form elements */
    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 11px; font-weight: 700; color: #999; text-transform: uppercase; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background: #fff; color: #333; }
    body.dark-mode .form-control { background: #252525 !important; border-color: #333 !important; color: #fff !important; }
    
    .btn-action-ntk {
        background: #2f1c00;
        color: #ffffff;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        width: 100%;
        border: none;
        cursor: pointer;
        transition: 0.2s;
        text-align: center;
    }
    .btn-action-ntk:hover { background: #1a0f00; }
    
    /* Table styles */
    .section-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e5e5e5;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    body.dark-mode .section-card {
        background: #1e1e1e !important;
        border-color: #333 !important;
    }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead th {
        padding: 16px 24px;
        font-size: 11px;
        font-weight: 700;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-align: left;
        background: #fafaf8;
        border-bottom: 1px solid #e5e5e5;
    }
    body.dark-mode .data-table thead th {
        background: #252525 !important;
        border-bottom-color: #333 !important;
    }
    
    .data-table tbody td {
        padding: 16px 24px;
        font-size: 14px;
        color: #111111;
        border-bottom: 1px solid #f5f1eb;
        vertical-align: middle;
    }
    body.dark-mode .data-table tbody td {
        color: #ddd !important;
        border-bottom-color: #333 !important;
    }
    
    .data-table tbody tr:hover { background: #fafaf8; }
    body.dark-mode .data-table tbody tr:hover { background: #252525 !important; }
    
    /* Tags */
    .status-tag { padding: 4px 10px; border-radius: 20px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; display: inline-block; }
    .tag-active { background: #eafaf1; color: #27ae60; }
    .tag-inactive { background: #fdf0ef; color: #c0392b; }
    
    /* Action buttons */
    .action-btns { display: flex; gap: 15px; }
    .btn-icon { color: #888; transition: 0.2s; text-decoration: none; font-size: 16px; background: none; border: none; cursor: pointer; }
    .btn-icon:hover { color: #2f1c00; }
    body.dark-mode .btn-icon:hover { color: #f1c40f !important; }
    .btn-delete:hover { color: #c0392b !important; }
    
    /* Dynamic variant section */
    .variant-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: 8px;
        margin-bottom: 10px;
        font-size: 13px;
    }
    body.dark-mode .variant-row {
        border-color: #333 !important;
    }
    .variant-row input[type="checkbox"] {
        accent-color: #2f1c00;
        width: 16px;
        height: 16px;
    }
    .variant-info-lbl {
        flex: 1;
    }
    .variant-price-input {
        width: 100px;
        padding: 6px 8px;
        font-size: 13px;
        border: 1px solid #ddd;
        border-radius: 6px;
    }
</style>

<div class="page-header" style="margin-bottom: 26px;">
    <div class="page-title">QUẢN LÝ FLASH SALE</div>
    <div class="page-subtitle">Thiết lập chương trình giảm giá chớp nhoáng cho từng sản phẩm</div>
</div>

<div class="fs-layout">
    <!-- Left Column: Add Flash Sale Form -->
    <div class="fs-card">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 16px; font-weight: 700; color: #2f1c00; text-transform: uppercase;">
            Thêm sản phẩm sale
        </h3>
        
        <form method="POST">
            <div class="form-group">
                <label>Ngày diễn ra</label>
                <input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Chọn sản phẩm</label>
                <select id="select-product" class="form-control" onchange="loadVariants(this.value)">
                    <option value="">-- Chọn sản phẩm --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" id="variants-section" style="display: none;">
                <label>Chọn các phân loại & Giá Sale</label>
                <div id="variants-list" style="max-height: 350px; overflow-y: auto; padding-right: 5px;">
                    <!-- Sẽ được điền bằng AJAX -->
                </div>
            </div>
            
            <button type="submit" name="add_flash_sale" class="btn-action-ntk" id="submit-btn" disabled>
                CẬP NHẬT FLASH SALE
            </button>
        </form>
    </div>
    
    <!-- Right Column: Current Flash Sales List -->
    <div>
        <div class="section-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Phân loại</th>
                        <th>Giá Gốc</th>
                        <th>Giá Thường</th>
                        <th>Giá Flash Sale</th>
                        <th>Ngày Sale</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($flash_sales)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 40px; color: #999;">Chưa có sản phẩm nào tham gia Flash Sale.</td></tr>
                    <?php else: ?>
                        <?php foreach ($flash_sales as $fs): ?>
                            <tr>
                                <td>
                                    <strong style="color: #2f1c00;"><?= htmlspecialchars($fs['product_name']) ?></strong>
                                    <div style="font-size: 11px; color: #888;">SKU: <?= htmlspecialchars($fs['sku']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($fs['color']) ?> - Size <?= htmlspecialchars($fs['size']) ?></td>
                                <td><?= number_format($fs['original_price'], 0, ',', '.') ?>đ</td>
                                <td><?= number_format($fs['reg_sale_price'], 0, ',', '.') ?>đ</td>
                                <td style="font-weight: 700; color: #e74c3c;">
                                    <?= number_format($fs['flash_sale_price'], 0, ',', '.') ?>đ
                                </td>
                                <td><?= date('d/m/Y', strtotime($fs['sale_date'])) ?></td>
                                <td>
                                    <span class="status-tag <?= $fs['status'] == 1 ? 'tag-active' : 'tag-inactive' ?>">
                                        <?= $fs['status'] == 1 ? 'Kích hoạt' : 'Tắt' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="flash_sales.php?action=toggle&id=<?= $fs['flash_sale_id'] ?>" class="btn-icon" title="Bật/Tắt trạng thái">
                                            <i class="fa-solid <?= $fs['status'] == 1 ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                        </a>
                                        <a href="flash_sales.php?action=delete&id=<?= $fs['flash_sale_id'] ?>" class="btn-icon btn-delete" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này khỏi Flash Sale?')">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function loadVariants(productId) {
        const listDiv = document.getElementById('variants-list');
        const section = document.getElementById('variants-section');
        const submitBtn = document.getElementById('submit-btn');
        
        if (!productId) {
            section.style.display = 'none';
            listDiv.innerHTML = '';
            submitBtn.disabled = true;
            return;
        }
        
        listDiv.innerHTML = '<div style="text-align:center; padding:20px; color:#999;"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải phân loại...</div>';
        section.style.display = 'block';
        
        fetch('flash_sales.php?action=get_variants&product_id=' + encodeURIComponent(productId))
            .then(res => res.json())
            .then(variants => {
                listDiv.innerHTML = '';
                
                if (variants.length === 0) {
                    listDiv.innerHTML = '<div style="padding:10px; color:#e74c3c;">Sản phẩm không có biến thể hoạt động nào!</div>';
                    submitBtn.disabled = true;
                    return;
                }
                
                variants.forEach(v => {
                    const regSale = parseFloat(v.sale_price) || 0;
                    const origPrice = parseFloat(v.original_price) || 0;
                    const basePrice = regSale > 0 ? regSale : origPrice;
                    
                    const row = document.createElement('div');
                    row.className = 'variant-row';
                    row.innerHTML = `
                        <input type="checkbox" name="selected_variants[]" value="${v.variant_id}" id="chk_${v.variant_id}" onchange="toggleVariantInput(this, '${v.variant_id}')">
                        <div class="variant-info-lbl">
                            <label for="chk_${v.variant_id}"><strong>${v.color} - Size ${v.size}</strong></label>
                            <div style="font-size:11px; color:#888;">Gốc: ${origPrice.toLocaleString('vi-VN')}đ | Tồn: ${v.stock}</div>
                        </div>
                        <input type="number" name="flash_sale_price[${v.variant_id}]" id="price_${v.variant_id}" class="variant-price-input" placeholder="Giá Sale..." disabled required min="1000">
                    `;
                    listDiv.appendChild(row);
                });
                
                // Keep submit button disabled until at least one is checked
                submitBtn.disabled = true;
            })
            .catch(err => {
                console.error(err);
                listDiv.innerHTML = '<div style="padding:10px; color:#e74c3c;">Lỗi tải dữ liệu biến thể!</div>';
                submitBtn.disabled = true;
            });
    }
    
    function toggleVariantInput(checkbox, variantId) {
        const priceInput = document.getElementById('price_' + variantId);
        priceInput.disabled = !checkbox.checked;
        
        // Cập nhật trạng thái nút Submit
        const checkedCount = document.querySelectorAll('input[name="selected_variants[]"]:checked').length;
        document.getElementById('submit-btn').disabled = (checkedCount === 0);
    }
</script>

</main>
</div>
</body>
</html>
