<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$error_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Lấy dữ liệu từ Form
    $code = strtoupper(trim($_POST['code']));
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'];
    $min_order = $_POST['min_order_value'];
    $quantity = $_POST['quantity'];
    $end_date = $_POST['end_date'];

    // 2. TỰ SINH ID: Dùng tiền tố CP + chuỗi thời gian duy nhất
    $new_id = "CP" . date('His') . rand(10, 99); 

    try {
        // 3. Chèn vào database
        $sql = "INSERT INTO coupons (coupon_id, code, discount_type, discount_value, min_order_value, quantity, end_date, used_count) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$new_id, $code, $discount_type, $discount_value, $min_order, $quantity, $end_date]);
        
        header("Location: coupons.php?msg=added"); exit;
    } catch (PDOException $e) {
        // Bẫy lỗi trùng mã CODE (tên voucher)
        if ($e->errorInfo[1] == 1062) {
            $error_msg = "Lỗi: Mã Voucher '$code' đã tồn tại. Bee hãy đặt tên mã khác nhé!";
        } else {
            $error_msg = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}

$admin_current_page = 'coupons.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    .add-wrapper { padding: 30px; background: #fdfdfb; min-height: 100vh; }
    .form-card { max-width: 700px; background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
    .page-title { font-size: 21px; font-weight: 700; text-transform: uppercase; margin-bottom: 25px; color: #111; letter-spacing: 0.5px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 11px; font-weight: 700; color: #999; text-transform: uppercase; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
    .btn-submit { background: #2f1c00; color: #fff; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 10px; }
    .error-alert { background: #fdf0ef; color: #c0392b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border-left: 4px solid #c0392b; }
</style>

<div class="add-wrapper">
    <div class="form-card">
        <h2 class="page-title">Tạo Voucher Mới</h2>

        <?php if ($error_msg): ?>
            <div class="error-alert"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Mã Voucher (Ví dụ: HELLOWORLD)</label>
                <input type="text" name="code" class="form-control" placeholder="Nhập mã ưu đãi..." required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Loại giảm giá</label>
                    <select name="discount_type" class="form-control">
                        <option value="0">Phần trăm (%)</option>
                        <option value="1">Số tiền mặt (₫)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Giá trị giảm</label>
                    <input type="number" name="discount_value" class="form-control" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Đơn tối thiểu (₫)</label>
                    <input type="number" name="min_order_value" class="form-control" value="0">
                </div>
                <div class="form-group">
                    <label>Số lượng phát hành</label>
                    <input type="number" name="quantity" class="form-control" value="100">
                </div>
            </div>

            <div class="form-group">
                <label>Ngày hết hạn</label>
                <input type="date" name="end_date" class="form-control" required>
            </div>

            <button type="submit" class="btn-submit">XÁC NHẬN TẠO VOUCHER</button>
            <a href="coupons.php" style="display: block; text-align: center; margin-top: 20px; color: #999; text-decoration: none; font-size: 13px;">Hủy bỏ và quay lại</a>
        </form>
    </div>
</div>