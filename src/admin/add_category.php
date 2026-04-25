<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';
$isEdit = !empty($id);
$cat = null;

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$id]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cat) {
        $isEdit = false;
        $id = '';
    }
}

$error = '';
$success = '';

// Processing form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $priority = (int)($_POST['priority'] ?? 0);
    $is_show_home = isset($_POST['is_show_home']) ? 1 : 0;
    $description = $_POST['description'] ?? '';
    
    // Handle image upload
    $image_url = $isEdit ? $cat['image_url'] : '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/images/categories/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // Adjust relative path for DB
            $image_url = 'assets/images/categories/' . $fileName;
        }
    }
    
    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, priority=?, is_show_home=?, description=?, image_url=? WHERE category_id=?");
        $stmt->execute([$name, $slug, $priority, $is_show_home, $description, $image_url, $id]);
        $success = "Cập nhật danh mục thành công!";
        // Update local $cat for view
        $cat = ['name'=>$name, 'slug'=>$slug, 'priority'=>$priority, 'is_show_home'=>$is_show_home, 'description'=>$description, 'image_url'=>$image_url, 'category_id'=>$id];
    } else {
        // Auto generate category_id
        $stmt = $conn->query("SELECT category_id FROM categories ORDER BY category_id DESC LIMIT 1");
        $lastCat = $stmt->fetch();
        if ($lastCat) {
            $num = (int)substr($lastCat['category_id'], 3) + 1;
            $newId = 'CAT' . str_pad($num, 2, '0', STR_PAD_LEFT);
        } else {
            $newId = 'CAT01';
        }
        $stmt = $conn->prepare("INSERT INTO categories (category_id, name, slug, priority, is_show_home, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$newId, $name, $slug, $priority, $is_show_home, $description, $image_url]);
        header("Location: categories.php");
        exit;
    }
}

$admin_current_page = 'categories.php'; // Highlight mục Danh mục trong sidebar
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

    .form-container {
        max-width: 800px;
    }

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
    }

    .form-group {
        margin-bottom: 20px;
    }
    .form-group:last-child {
        margin-bottom: 0;
    }
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
    .form-control[readonly] {
        background: #f8f9fa;
        color: #888;
        cursor: not-allowed;
    }
    .form-text {
        font-size: 11px;
        color: #888;
        margin-top: 6px;
        display: block;
    }

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
    .upload-text {
        font-size: 14px;
        color: #333;
    }
    .upload-text span {
        font-weight: 600;
        color: #2f1c00;
    }
    .upload-hint {
        font-size: 12px;
        color: #888;
        margin-top: 8px;
    }

    /* Toggle Switch */
    .toggle-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .toggle-info .title { font-size: 14px; font-weight: 500; color: #111; }
    .toggle-info .desc { font-size: 12px; color: #888; margin-top: 4px; }
    
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider { background-color: #2f1c00; }
    input:checked + .slider:before { transform: translateX(20px); }

    /* Preview Box */
    .preview-box {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        background: #fafaf8;
    }
    .preview-img {
        width: 60px;
        height: 60px;
        background: #e5e5e5;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #aaa;
        font-size: 20px;
    }
    .preview-info .name { font-size: 14px; font-weight: 600; color: #111; margin-bottom: 4px; }
    .preview-info .desc { font-size: 12px; color: #888; }

    /* Action Buttons */
    .action-panel {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }
    .btn {
        flex: 1;
        padding: 12px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    .btn-primary {
        background: #2f1c00;
        color: #fff;
    }
    .btn-primary:hover { background: #1a0f00; }
    .btn-secondary {
        background: #fff;
        color: #555;
        border-color: #ccc;
    }
    .btn-secondary:hover { background: #f5f5f5; color: #111; }
</style>

<div class="page-header">
    <div class="page-title"><?= $isEdit ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' ?></div>
    <div class="page-subtitle"><?= $isEdit ? 'Cập nhật thông tin danh mục' : 'Tạo danh mục sản phẩm mới' ?></div>
</div>

<?php if ($success): ?>
<div style="background: #eafaf1; color: #27ae60; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 500;">
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<div class="form-container">
    <form action="" method="POST" enctype="multipart/form-data">
        
        <!-- Thông tin danh mục -->
        <div class="panel">
            <div class="panel-title">Thông tin danh mục</div>
            
            <div class="form-group">
                <label class="form-label required">Tên danh mục</label>
                <input type="text" class="form-control" name="name" id="cat-name" placeholder="Nhập tên danh mục (VD: Áo thun, Quần jeans...)" value="<?= htmlspecialchars($cat['name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Slug</label>
                <input type="text" class="form-control" name="slug" id="cat-slug" placeholder="auto-generate-from-name" value="<?= htmlspecialchars($cat['slug'] ?? '') ?>" readonly>
                <span class="form-text">URL: /category/<span id="slug-preview"><?= htmlspecialchars($cat['slug'] ?? 'auto-generate') ?></span></span>
            </div>
            
            <div class="form-group">
                <label class="form-label">Danh mục cha</label>
                <select class="form-control" name="parent_id">
                    <option value="">Không có (Danh mục gốc)</option>
                </select>
            </div>
        </div>

        <!-- Hình ảnh đại diện -->
        <div class="panel">
            <div class="panel-title">Hình ảnh đại diện</div>
            <?php if($isEdit && !empty($cat['image_url'])): ?>
                <?php $img_src = (strpos($cat['image_url'], 'http') === 0) ? $cat['image_url'] : '../' . $cat['image_url']; ?>
                <div style="margin-bottom: 12px;">
                    <img src="<?= htmlspecialchars($img_src) ?>" style="height:60px; border-radius:6px; object-fit:cover;" onerror="this.outerHTML='<div style=\'height:60px; width:60px; border-radius:6px; background:#f5f1eb; display:flex; align-items:center; justify-content:center; color:#ccc;\'><i class=\'fa-solid fa-image\'></i></div>';">
                </div>
            <?php endif; ?>
            <div class="upload-box">
                <div class="upload-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></div>
                <div class="upload-text">Kéo thả ảnh hoặc <span>chọn tệp</span></div>
                <div class="upload-hint">PNG, JPG (tối đa 2MB)</div>
                <input type="file" name="image" style="display: none;" id="file-upload" accept="image/png, image/jpeg">
            </div>
        </div>

        <!-- Mô tả danh mục -->
        <div class="panel">
            <div class="panel-title">Mô tả danh mục</div>
            <div class="form-group">
                <textarea class="form-control" name="description" rows="5" placeholder="Nhập mô tả chi tiết về danh mục này..."><?= htmlspecialchars($cat['description'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Cài đặt hiển thị -->
        <div class="panel">
            <div class="panel-title">Cài đặt hiển thị</div>
            
            <div class="form-group toggle-container">
                <div class="toggle-info">
                    <div class="title">Hiển thị trên trang chủ</div>
                    <div class="desc">Danh mục sẽ xuất hiện trong phần danh mục nổi bật</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="is_show_home" value="1" <?= (!isset($cat) || $cat['is_show_home'] == 1) ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="form-group" style="margin-top: 24px;">
                <label class="form-label">Thứ tự hiển thị</label>
                <input type="number" class="form-control" name="priority" value="<?= htmlspecialchars($cat['priority'] ?? 0) ?>">
                <span class="form-text">Số thứ tự càng nhỏ sẽ hiển thị trước (0 là đầu tiên)</span>
            </div>
        </div>

        <!-- Preview -->
        <div class="panel">
            <div class="panel-title">Preview</div>
            <div class="preview-box">
                <div class="preview-img">
                    <i class="fa-regular fa-image"></i>
                </div>
                <div class="preview-info">
                    <div class="name" id="prev-name">Tên danh mục</div>
                    <div class="desc">Chưa có mô tả</div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="action-panel">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Cập nhật danh mục' : 'Lưu danh mục' ?></button>
            <a href="categories.php" class="btn btn-secondary" style="text-decoration:none; display:block;">Hủy</a>
        </div>

    </form>
</div>

<script>
    // JS tạo slug tự động (demo)
    const nameInput = document.getElementById('cat-name');
    const slugInput = document.getElementById('cat-slug');
    const slugPreview = document.getElementById('slug-preview');
    const prevName = document.getElementById('prev-name');

    nameInput.addEventListener('input', function() {
        let val = this.value;
        prevName.textContent = val || 'Tên danh mục';
        
        let slug = val.toLowerCase()
            .replace(/[áàảãạăắằẳẵặâấầẩẫậ]/g, 'a')
            .replace(/[éèẻẽẹêếềểễệ]/g, 'e')
            .replace(/[íìỉĩị]/g, 'i')
            .replace(/[óòỏõọôốồổỗộơớờởỡợ]/g, 'o')
            .replace(/[úùủũụưứừửữự]/g, 'u')
            .replace(/[ýỳỷỹỵ]/g, 'y')
            .replace(/đ/g, 'd')
            .replace(/[^a-z0-9 -]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
            
        slugInput.value = slug;
        slugPreview.textContent = slug || 'auto-generate';
    });

    // Mở file input khi bấm vào vùng upload
    document.querySelector('.upload-box').addEventListener('click', () => {
        document.getElementById('file-upload').click();
    });
</script>

</div><!-- /.admin-content -->
</main>
</body>
</html>
