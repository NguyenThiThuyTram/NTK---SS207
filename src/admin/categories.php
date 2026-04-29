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

// Lấy danh sách danh mục
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY priority ASC, name ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tổng số danh mục
$total_categories = count($categories);

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
        color: #111111;
        text-decoration: none;
        transition: color 0.2s;
    }
    .link-detail:hover {
        color: #2f1c00;
        text-decoration: underline;
    }
    .cat-id {
        font-weight: 500;
        font-size: 13px;
        color: #555;
    }
    .cat-image {
        width: 48px;
        height: 48px;
        border-radius: 6px;
        object-fit: cover;
        background: #f5f1eb;
        border: 1px solid #e5e5e5;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-active { background: #eafaf1; color: #27ae60; }
    .status-inactive { background: #f5f1eb; color: #888; }

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
        <div class="page-title">Quản lý danh mục</div>
        <div class="page-subtitle"><?= $total_categories ?> danh mục</div>
    </div>
    <a href="add_category.php" class="btn-add"><i class="fa-solid fa-plus"></i> Thêm danh mục</a>
</div>

<div class="section-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Hình ảnh</th>
                <th>Tên danh mục</th>
                <th>Mức ưu tiên</th>
                <th>Hiển thị Home</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:40px; color:#aaa;">
                    Chưa có danh mục nào
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><span class="cat-id"><?= htmlspecialchars($cat['category_id']) ?></span></td>
                    <td>
                        <?php if(!empty($cat['image_url'])): ?>
                            <?php $img_src = (strpos($cat['image_url'], 'http') === 0) ? $cat['image_url'] : '../' . $cat['image_url']; ?>
                            <img src="<?= htmlspecialchars($img_src) ?>" class="cat-image" alt="Cat Image" onerror="this.outerHTML='<div class=\'cat-image\' style=\'display:flex;align-items:center;justify-content:center;color:#aaa;\'><i class=\'fa-solid fa-image\'></i></div>';">
                        <?php else: ?>
                            <div class="cat-image" style="display:flex;align-items:center;justify-content:center;color:#aaa;"><i class="fa-solid fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 500;">
                        <a href="category_detail.php?id=<?= urlencode($cat['category_id']) ?>" class="link-detail">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    </td>
                    <td><?= (int)$cat['priority'] ?></td>
                    <td>
                        <?php if($cat['is_show_home'] == 1): ?>
                            <span class="status-badge status-active">Hiển thị</span>
                        <?php else: ?>
                            <span class="status-badge status-inactive">Ẩn</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="add_category.php?id=<?= urlencode($cat['category_id']) ?>" class="btn-icon edit" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></a>
                            <a href="#" class="btn-icon delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');"><i class="fa-solid fa-trash"></i></a>
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
