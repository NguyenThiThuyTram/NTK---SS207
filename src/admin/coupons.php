<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// 1. Lấy danh sách Voucher từ Database
$coupons = $conn->query("SELECT * FROM coupons ORDER BY coupon_id DESC")->fetchAll(PDO::FETCH_ASSOC);

$admin_current_page = 'coupons.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    /* RESET & BASE — Font: Helvetica Neue | Color: NTK Brand */
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }

    .coupon-wrapper { padding: 30px; background: #fdfdfb; min-height: 100vh; }
    
    /* Tiêu đề: Viết hoa, đậm, chuẩn style NTK */
    .page-title { 
        font-size: 21px; 
        font-weight: 700; 
        color: #111111; 
        text-transform: uppercase; 
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .page-subtitle { font-size: 13px; color: #888; }

    /* Button thêm voucher */
    .btn-add-ntk {
        background: #2f1c00;
        color: #ffffff;
        padding: 10px 22px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: 0.2s;
        border: none;
    }
    .btn-add-ntk:hover { background: #1a0f00; transform: translateY(-1px); }

    /* Bảng dữ liệu đồng bộ */
    .section-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e5e5e5;
        overflow: hidden;
        margin-top: 25px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
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
    .data-table tbody td {
        padding: 18px 24px;
        font-size: 14px;
        color: #111111;
        border-bottom: 1px solid #f5f1eb;
        vertical-align: middle;
    }
    .data-table tbody tr:hover { background: #fafaf8; }

    /* Voucher Tags */
    .code-badge { 
        background: #f4f1ee; 
        color: #2f1c00; 
        padding: 6px 12px; 
        border-radius: 4px; 
        font-family: 'Courier New', Courier, monospace; 
        font-weight: 700; 
        border: 1px dashed #2f1c00; 
    }
    .status-tag { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .tag-active { background: #eafaf1; color: #27ae60; }
    .tag-expired { background: #fdf0ef; color: #c0392b; }

    /* Icons Action */
    .action-btns { display: flex; gap: 15px; justify-content: flex-end; }
    .btn-icon { color: #888; transition: 0.2s; text-decoration: none; font-size: 16px; }
    .btn-icon:hover { color: #2f1c00; }
    .btn-delete:hover { color: #c0392b; }
</style>

<div class="coupon-wrapper">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h2 class="page-title">QUẢN LÝ VOUCHER</h2>
            <div class="page-subtitle">Quản lý các mã giảm giá và chương trình ưu đãi</div>
        </div>
        <a href="add_coupon.php" class="btn-add-ntk">
            <i class="fa-solid fa-plus"></i> THÊM VOUCHER
        </a>
    </div>

    <div class="section-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>MÃ CODE</th>
                    <th>GIẢM GIÁ</th>
                    <th>ĐƠN TỐI THIỂU</th>
                    <th>NGÀY HẾT HẠN</th>
                    <th>ĐÃ SỬ DỤNG</th>
                    <th>TRẠNG THÁI</th>
                    <th style="text-align: right;">HÀNH ĐỘNG</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($coupons)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 40px; color:#999;">Chưa có dữ liệu voucher.</td></tr>
                <?php else: ?>
                    <?php foreach ($coupons as $cp): 
                        $is_expired = strtotime($cp['end_date']) < time();
                    ?>
                    <tr>
                        <td><span class="code-badge"><?= htmlspecialchars($cp['code']) ?></span></td>
                        <td style="font-weight: 700; color: #c0392b;">
                            <?= $cp['discount_type'] == 0 ? (int)$cp['discount_value'].'%' : number_format($cp['discount_value']).'₫' ?>
                        </td>
                        <td><?= number_format($cp['min_order_value']) ?>₫</td>
                        <td style="color: #555;"><?= date('d/m/Y', strtotime($cp['end_date'])) ?></td>
                        <td>
                            <strong style="font-size: 15px;"><?= $cp['used_count'] ?></strong> / <?= $cp['quantity'] ?>
                            <div style="width: 80px; height: 3px; background: #eee; border-radius: 10px; margin-top: 6px;">
                                <div style="width: <?= ($cp['quantity'] > 0) ? ($cp['used_count']/$cp['quantity'])*100 : 0 ?>%; height: 100%; background: #2f1c00;"></div>
                            </div>
                        </td>
                        <td>
                            <span class="status-tag <?= $is_expired ? 'tag-expired' : 'tag-active' ?>">
                                <?= $is_expired ? 'Hết hạn' : 'Hoạt động' ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="view_coupon.php?id=<?= $cp['coupon_id'] ?>" class="btn-icon" title="Xem chi tiết">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <a href="update_coupon.php?id=<?= $cp['coupon_id'] ?>" class="btn-icon" title="Chỉnh sửa">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                                <a href="delete_coupon.php?id=<?= $cp['coupon_id'] ?>" class="btn-icon btn-delete" title="Xóa" onclick="return confirm('Bee có chắc chắn muốn xóa mã <?= $cp['code'] ?> chứ?')">
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

</main>
</body>
</html>