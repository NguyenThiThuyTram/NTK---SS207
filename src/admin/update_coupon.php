<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';
if (empty($id)) { header("Location: coupons.php"); exit; }

// 1. Lấy dữ liệu cũ để hiển thị lên Form
$stmt = $conn->prepare("SELECT * FROM coupons WHERE coupon_id = ?");
$stmt->execute([$id]);
$cp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cp) { header("Location: coupons.php"); exit; }

// 2. Xử lý khi Bee bấm "LƯU THAY ĐỔI"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'];
    $min_order = $_POST['min_order_value']; // Mới thêm
    $quantity = $_POST['quantity'];         // Mới thêm
    $end_date = $_POST['end_date'];

    // Cập nhật câu SQL để lưu đầy đủ các cột
    $sql = "UPDATE coupons SET 
            code=?, 
            discount_type=?, 
            discount_value=?, 
            min_order_value=?, 
            quantity=?, 
            end_date=? 
            WHERE coupon_id=?";
    
    $conn->prepare($sql)->execute([$code, $discount_type, $discount_value, $min_order, $quantity, $end_date, $id]);
    
    header("Location: coupons.php?msg=updated"); exit;
}

$admin_current_page = 'coupons.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    .update-container { padding: 30px; background: #fdfdfb; min-height: 100vh; }
    .form-card { 
        max-width: 700px; 
        background: #fff; 
        padding: 40px; 
        border-radius: 12px; 
        border: 1px solid #e5e5e5;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    }
    .page-title { font-size: 21px; font-weight: 700; text-transform: uppercase; margin-bottom: 25px; color: #2f1c00; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 11px; font-weight: 700; color: #999; text-transform: uppercase; margin-bottom: 8px; }
    .form-control { 
        width: 100%; 
        padding: 12px 15px; 
        border: 1px solid #ddd; 
        border-radius: 8px; 
        font-size: 14px; 
        transition: 0.2s;
    }
    .form-control:focus { border-color: #2f1c00; outline: none; box-shadow: 0 0 0 3px rgba(47,28,0,0.1); }
    .btn-save { 
        background: #2f1c00; 
        color: #fff; 
        border: none; 
        padding: 15px 30px; 
        border-radius: 8px; 
        font-weight: 700; 
        width: 100%; 
        cursor: pointer;
        margin-top: 10px;
    }
    .btn-save:hover { background: #1a0f00; }
</style>

<div class="update-container">
    <div class="form-card">
        <h2 class="page-title">Chỉnh sửa Voucher: <?= $cp['code'] ?></h2>
        
        <form method="POST">
            <div class="form-group">
                <label>Mã Voucher (Code)</label>
                <input type="text" name="code" value="<?= htmlspecialchars($cp['code']) ?>" class="form-control" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Loại giảm giá</label>
                    <select name="discount_type" class="form-control">
                        <option value="0" <?= $cp['discount_type'] == 0 ? 'selected' : '' ?>>Phần trăm (%)</option>
                        <option value="1" <?= $cp['discount_type'] == 1 ? 'selected' : '' ?>>Số tiền mặt (₫)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Giá trị giảm</label>
                    <input type="number" name="discount_value" value="<?= $cp['discount_value'] ?>" class="form-control" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Đơn tối thiểu (₫)</label>
                    <input type="number" name="min_order_value" value="<?= $cp['min_order_value'] ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Số lượng mã</label>
                    <input type="number" name="quantity" value="<?= $cp['quantity'] ?>" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label>Ngày hết hạn</label>
                <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime($cp['end_date'])) ?>" class="form-control" required>
            </div>

            <button type="submit" class="btn-save">LƯU THAY ĐỔI</button>
            <a href="coupons.php" style="display: block; text-align: center; margin-top: 20px; color: #999; text-decoration: none; font-size: 13px;">Hủy và quay lại</a>
        </form>
    </div>
</div>