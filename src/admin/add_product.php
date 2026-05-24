<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';
$isEdit = !empty($id);
$prod = null;

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$prod) {
        $isEdit = false;
        $id = '';
    }
}

$variants = [];
if ($isEdit) {
    $stmt_var = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_id ASC");
    $stmt_var->execute([$id]);
    $variants = $stmt_var->fetchAll(PDO::FETCH_ASSOC);
}


$error = '';
$success = '';

// Processing form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $status = (int)($_POST['status'] ?? 0);
    
    $seo_title = $_POST['seo_title'] ?? '';
    $seo_description = $_POST['seo_description'] ?? '';
    
    // Handle image upload
    $image_url = $isEdit ? $prod['image'] : '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/images/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_url = 'assets/images/products/' . $fileName;
        }
    }
    
    try {
        $conn->beginTransaction();
        
        if (!$isEdit) {
            // Auto generate product_id
            $stmt = $conn->query("SELECT product_id FROM products ORDER BY product_id DESC LIMIT 1");
            $lastProd = $stmt->fetch();
            if ($lastProd) {
                $num = (int)substr($lastProd['product_id'], 1) + 1;
                $newId = 'C' . str_pad($num, 2, '0', STR_PAD_LEFT);
            } else {
                $newId = 'C01';
            }
            $idToUse = $newId;
            $stmt = $conn->prepare("INSERT INTO products (product_id, name, description, category_id, status, image, seo_title, seo_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$newId, $name, $description, $category_id, $status, $image_url, $seo_title, $seo_description]);
        } else {
            $idToUse = $id;
            $stmt = $conn->prepare("UPDATE products SET name=?, description=?, category_id=?, status=?, image=?, seo_title=?, seo_description=? WHERE product_id=?");
            $stmt->execute([$name, $description, $category_id, $status, $image_url, $seo_title, $seo_description, $id]);
        }

        // Process Variants
        $v_id = $_POST['v_id'] ?? [];
        $v_sku = $_POST['v_sku'] ?? [];
        $v_color = $_POST['v_color'] ?? [];
        $v_size = $_POST['v_size'] ?? [];
        $v_price = $_POST['v_price'] ?? [];
        $v_stock = $_POST['v_stock'] ?? [];

        $maxNumStmt = $conn->query("SELECT MAX(CAST(SUBSTRING(variant_id, 2) AS UNSIGNED)) as max_id FROM product_variants");
        $maxIdRow = $maxNumStmt->fetch();
        $currentMaxVar = (int)$maxIdRow['max_id'];

        for ($i = 0; $i < count($v_color); $i++) {
            $cv_id = $v_id[$i] ?? '';
            $cv_sku = $v_sku[$i] ?? '';
            $cv_color = $v_color[$i] ?? '';
            $cv_size = $v_size[$i] ?? '';
            $cv_price = (float)($v_price[$i] ?? 0);
            $cv_stock = (int)($v_stock[$i] ?? 0);
            
            if (trim($cv_color) === '') continue;

            if ($cv_id) {
                $ustmt = $conn->prepare("UPDATE product_variants SET sku=?, color=?, size=?, stock=?, original_price=?, sale_price=? WHERE variant_id=? AND product_id=?");
                $ustmt->execute([$cv_sku, $cv_color, $cv_size, $cv_stock, $cv_price, $cv_price, $cv_id, $idToUse]);
            } else {
                $currentMaxVar++;
                $newVarId = 'V' . str_pad($currentMaxVar, 3, '0', STR_PAD_LEFT);
                $istmt = $conn->prepare("INSERT INTO product_variants (variant_id, product_id, sku, color, size, stock, original_price, sale_price, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                $istmt->execute([$newVarId, $idToUse, $cv_sku, $cv_color, $cv_size, $cv_stock, $cv_price, $cv_price]);
            }
        }
        
        $conn->commit();
        
        if ($isEdit) {
            $success = "Cập nhật sản phẩm thành công!";
            $prod = ['name'=>$name, 'description'=>$description, 'category_id'=>$category_id, 'status'=>$status, 'image'=>$image_url, 'product_id'=>$id, 'seo_title'=>$seo_title, 'seo_description'=>$seo_description];
            // Reload variants
            $stmt_var = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_id ASC");
            $stmt_var->execute([$id]);
            $variants = $stmt_var->fetchAll(PDO::FETCH_ASSOC);
        } else {
            header("Location: products.php");
            exit;
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Lỗi hệ thống: " . $e->getMessage();
    }
}

// Lấy danh mục để hiển thị trong select
$stmt_cats = $conn->prepare("SELECT category_id, name FROM categories ORDER BY priority ASC");
$stmt_cats->execute();
$categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

$admin_current_page = 'products.php'; // Highlight mục Sản phẩm
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    
    .page-header { margin-bottom: 24px; }
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

    /* Layout 2 cột */
    .layout-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 992px) {
        .layout-grid { grid-template-columns: 1fr; }
    }

    /* Panels */
    .panel {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .panel-title {
        font-size: 15px;
        font-weight: 600;
        color: #111;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Forms */
    .form-group { margin-bottom: 20px; }
    .form-group:last-child { margin-bottom: 0; }
    .form-row {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
    }
    .form-col { flex: 1; }
    
    .form-label {
        display: block;
        font-size: 13px;
        color: #333;
        font-weight: 500;
        margin-bottom: 8px;
    }
    .form-label.required::after {
        content: " *";
        color: #c0392b;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        border: 1px solid #e5e5e5;
        border-radius: 6px;
        outline: none;
        transition: border-color 0.2s;
        color: #333;
    }
    .form-control:focus { border-color: #2f1c00; }
    
    /* Upload Box */
    .upload-box {
        border: 1px dashed #ccc;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        background: #fafaf8;
        cursor: pointer;
        transition: background 0.2s;
    }
    .upload-box:hover { background: #f5f1eb; }
    .upload-icon {
        font-size: 32px;
        color: #aaa;
        margin-bottom: 12px;
    }
    .upload-text { font-size: 14px; color: #333; }
    .upload-text span { font-weight: 600; color: #2f1c00; }
    .upload-hint { font-size: 12px; color: #888; margin-top: 8px; }

    /* Biến thể table */
    .variant-table { width: 100%; border-collapse: collapse; }
    .variant-table th {
        font-size: 12px;
        color: #888;
        font-weight: 500;
        text-align: left;
        padding-bottom: 8px;
    }
    .variant-table td { padding-bottom: 12px; padding-right: 12px; }
    .variant-table td:last-child { padding-right: 0; }
    
    .btn-add-variant {
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 6px;
        cursor: pointer;
    }
    .btn-add-variant:hover { background: #f5f5f5; color: #111; }

    /* Preview Sidebar */
    .preview-box {
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 16px;
    }
    .preview-img-area {
        width: 100%;
        aspect-ratio: 1/1;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #aaa;
        font-size: 48px;
    }
    .preview-title {
        font-size: 14px;
        font-weight: 500;
        color: #111;
        margin-top: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e5e5;
    }

    /* Actions */
    .btn {
        width: 100%;
        padding: 12px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s;
        margin-bottom: 12px;
        display: block;
    }
    .btn-primary { background: #2f1c00; color: #fff; }
    .btn-primary:hover { background: #1a0f00; }
    
    .btn-secondary { background: #fff; color: #555; border-color: #ccc; }
    .btn-secondary:hover { background: #f5f5f5; color: #111; }
    
    .btn-link {
        background: none;
        border: none;
        color: #888;
        font-size: 13px;
        text-decoration: none;
    }
    .btn-link:hover { color: #111; }
</style>

<div class="page-header">
    <div class="page-title"><?= $isEdit ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' ?></div>
    <div class="page-subtitle"><?= $isEdit ? 'Cập nhật thông tin sản phẩm' : 'Tạo sản phẩm mới cho cửa hàng' ?></div>
</div>

<?php if ($success): ?>
<div style="background: #eafaf1; color: #27ae60; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 500;">
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<form action="" method="POST" enctype="multipart/form-data">
    <div class="layout-grid">
        
        <!-- Cột Trái (Nội dung chính) -->
        <div class="main-col">
            
            <!-- Thông tin sản phẩm -->
            <div class="panel">
                <div class="panel-title">Thông tin sản phẩm</div>
                
                <div class="form-group">
                    <label class="form-label required">Tên sản phẩm</label>
                    <input type="text" class="form-control" name="name" id="prod-name" placeholder="Nhập tên sản phẩm" value="<?= htmlspecialchars($prod['name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mô tả sản phẩm</label>
                    <textarea class="form-control" name="description" rows="5" placeholder="Nhập mô tả chi tiết sản phẩm"><?= htmlspecialchars($prod['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label required">Danh mục</label>
                        <select class="form-control" name="category_id" required>
                            <option value="">Chọn danh mục</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c['category_id'] ?>" <?= (isset($prod['category_id']) && $prod['category_id'] == $c['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label">Thương hiệu</label>
                        <input type="text" class="form-control" name="brand" placeholder="Nhập tên thương hiệu">
                    </div>
                </div>
            </div>

            <!-- Hình ảnh sản phẩm -->
            <div class="panel">
                <div class="panel-title">Hình ảnh sản phẩm</div>
                <?php if($isEdit && !empty($prod['image'])): ?>
                    <?php $img_src = (strpos($prod['image'], 'http') === 0) ? $prod['image'] : '../' . $prod['image']; ?>
                    <div style="margin-bottom: 12px;">
                        <img src="<?= htmlspecialchars($img_src) ?>" style="height:80px; border-radius:6px; object-fit:cover;" onerror="this.outerHTML='<div style=\'height:80px; width:80px; border-radius:6px; background:#f5f1eb; display:flex; align-items:center; justify-content:center; color:#ccc;\'><i class=\'fa-solid fa-image\'></i></div>';">
                    </div>
                <?php endif; ?>
                <div class="upload-box" onclick="document.getElementById('file-upload').click();">
                    <div class="upload-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></div>
                    <div class="upload-text">Kéo thả ảnh hoặc <span>chọn tệp</span></div>
                    <div class="upload-hint">PNG, JPG (tối đa 5MB)</div>
                    <input type="file" name="image" style="display: none;" id="file-upload" accept="image/png, image/jpeg">
                </div>
                <!-- Phân loại sản phẩm -->
            <div class="panel">
                <div class="panel-title">
                    Phân loại sản phẩm
                    <button type="button" class="btn-add-variant" onclick="addVariantRow()"><i class="fa-solid fa-plus"></i> Thêm biến thể</button>
                </div>
                <table class="variant-table" id="variantTable">
                    <thead>
                        <tr>
                            <th>Màu sắc</th>
                            <th>Size</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>SKU</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($variants)): ?>
                            <tr>
                                <td><input type="hidden" name="v_id[]" value=""><input type="text" name="v_color[]" class="form-control" placeholder="Mặc định" required></td>
                                <td><input type="text" name="v_size[]" class="form-control" placeholder="Freesize" required></td>
                                <td><input type="text" name="v_price[]" class="form-control" placeholder="150000" required></td>
                                <td><input type="number" name="v_stock[]" class="form-control" placeholder="100" required></td>
                                <td><input type="text" name="v_sku[]" class="form-control" placeholder="SKU-01"></td>
                                <td><button type="button" class="btn-link" onclick="this.closest('tr').remove()" style="color:red;"><i class="fa-solid fa-trash"></i></button></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($variants as $v): ?>
                            <tr>
                                <td><input type="hidden" name="v_id[]" value="<?= htmlspecialchars($v['variant_id']) ?>"><input type="text" name="v_color[]" class="form-control" value="<?= htmlspecialchars($v['color']) ?>" required></td>
                                <td><input type="text" name="v_size[]" class="form-control" value="<?= htmlspecialchars($v['size']) ?>" required></td>
                                <td><input type="text" name="v_price[]" class="form-control" value="<?= (int)$v['original_price'] ?>" required></td>
                                <td><input type="number" name="v_stock[]" class="form-control" value="<?= (int)$v['stock'] ?>" required></td>
                                <td><input type="text" name="v_sku[]" class="form-control" value="<?= htmlspecialchars($v['sku'] ?? '') ?>"></td>
                                <td><button type="button" class="btn-link" onclick="this.closest('tr').remove()" style="color:red;"><i class="fa-solid fa-trash"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- SEO -->
            <div class="panel">
                <div class="panel-title">SEO</div>
                <div class="form-group">
                    <label class="form-label">Meta Title</label>
                    <input type="text" class="form-control" name="seo_title" placeholder="Nhập tiêu đề SEO" value="<?= htmlspecialchars($prod['seo_title'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Meta Description</label>
                    <textarea class="form-control" name="seo_description" rows="3" placeholder="Nhập mô tả SEO"><?= htmlspecialchars($prod['seo_description'] ?? '') ?></textarea>
                </div>
            </div>

        </div>



        </div>

        <!-- Cột Phải (Sidebar) -->
        <div class="sidebar-col">
            
            <div class="panel" style="position: sticky; top: 80px;">
                <div class="panel-title" style="margin-bottom: 16px;">Preview</div>
                <div class="preview-box">
                    <div class="preview-img-area">
                        <i class="fa-regular fa-image"></i>
                    </div>
                </div>
                <div class="preview-title" id="prev-title"><?= htmlspecialchars($prod['name'] ?? 'Tên sản phẩm') ?></div>
                
                <div class="form-group" style="margin-top: 24px;">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-control" name="status">
                        <option value="0" <?= (isset($prod['status']) && $prod['status'] == 0) ? 'selected' : '' ?>>Nháp</option>
                        <option value="1" <?= (!isset($prod['status']) || $prod['status'] == 1) ? 'selected' : '' ?>>Công khai</option>
                    </select>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Cập nhật sản phẩm' : 'Đăng sản phẩm' ?></button>
                    <button type="button" class="btn btn-secondary" onclick="window.location='products.php'">Lưu nháp</button>
                    <div style="text-align: center; margin-top: 16px;">
                        <a href="products.php" class="btn-link">Hủy</a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</form>

<script>
    // JS Preview Tên sản phẩm
    const nameInput = document.getElementById('prod-name');
    const prevTitle = document.getElementById('prev-title');

    nameInput.addEventListener('input', function() {
        prevTitle.textContent = this.value || 'Tên sản phẩm';
    });
    
    function addVariantRow() {
        const tbody = document.querySelector('#variantTable tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="hidden" name="v_id[]" value=""><input type="text" name="v_color[]" class="form-control" placeholder="Màu sắc" required></td>
            <td><input type="text" name="v_size[]" class="form-control" placeholder="Size" required></td>
            <td><input type="text" name="v_price[]" class="form-control" placeholder="Giá" required></td>
            <td><input type="number" name="v_stock[]" class="form-control" placeholder="Tồn kho" required></td>
            <td><input type="text" name="v_sku[]" class="form-control" placeholder="SKU"></td>
            <td><button type="button" class="btn-link" onclick="this.closest('tr').remove()" style="color:red;"><i class="fa-solid fa-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
    }
</script>

</div><!-- /.admin-content -->
</main>
</body>
</html>
