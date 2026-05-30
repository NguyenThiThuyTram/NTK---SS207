<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

// 1. Nhận ID và Tên (Dạng chuỗi như U873, tuyệt đối không dùng ép kiểu (int) nữa)
$target_user_id = $_POST['form_user_id'] ?? $_GET['user_id'] ?? null;
$customer_name = $_GET['name'] ?? $_POST['form_customer_name'] ?? 'Khách hàng';

// Nếu không nhận được dữ liệu, báo lỗi
if (!$target_user_id) {
    die("<div style='padding: 50px; text-align: center; font-family: sans-serif;'>
            <h1 style='color: red;'>🚨 LỖI KHÔNG NHẬN ĐƯỢC ID KHÁCH HÀNG!</h1>
            <p>Vui lòng quay lại trang Dashboard và thử lại.</p>
            <a href='dashboard.php' style='display:inline-block; margin-top:10px; padding:10px 20px; background:#2f1c00; color:#fff; text-decoration:none; border-radius:5px;'>Quay lại Dashboard</a>
         </div>");
}

$error_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount_type = (int)$_POST['discount_type'];
    $discount_value = (float)$_POST['discount_value'];
    $min_order = (float)$_POST['min_order_value'];
    $end_date = $_POST['end_date'];
    $quantity = 1; 

    // Tự sinh ID cho bảng coupon
    do {
        $new_id = "CP" . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); 
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM coupons WHERE coupon_id = ?");
        $stmt_check->execute([$new_id]);
        $id_exists = $stmt_check->fetchColumn();
    } while ($id_exists > 0);

    try {
        // CHÈN VÀO DATABASE (Bơm thẳng chuỗi mã khách $target_user_id)
        $sql = "INSERT INTO coupons (coupon_id, code, discount_type, discount_value, min_order_value, quantity, end_date, used_count, user_id, status, coupon_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, 1, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$new_id, $code, $discount_type, $discount_value, $min_order, $quantity, $end_date, $target_user_id]);
        
        // Lưu thành công -> Tự động chuyển hướng thẳng về trang danh sách voucher
        header("Location: coupons.php?msg=assigned"); 
        exit;

    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $error_msg = "Lỗi: Mã Voucher '$code' đã tồn tại. Hãy đặt mã khác nhé!";
        } else {
            $error_msg = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}

// Tự động gợi ý mã voucher dựa trên tên khách
$suggested_code = "VIP" . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $customer_name), 0, 5)) . rand(100, 999);
$admin_current_page = 'coupons.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    .add-wrapper { padding: 30px; background: #fdfdfb; min-height: 100vh; }
    .form-card { max-width: 700px; background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
    .page-title { font-size: 21px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; color: #111; }
    .page-subtitle { font-size: 14px; color: #666; margin-bottom: 25px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 11px; font-weight: 700; color: #999; text-transform: uppercase; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
    .btn-submit { background: #2f1c00; color: #fff; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 10px; }
    .error-alert { background: #fdf0ef; color: #c0392b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
</style>

<div class="add-wrapper">
    <div class="form-card">
        <h2 class="page-title">Tặng Voucher Đặc Quyền</h2>
        <div class="page-subtitle">Khách hàng: <strong><?= htmlspecialchars($customer_name) ?></strong> (ID: <?= htmlspecialchars($target_user_id) ?>)</div>

        <?php if ($error_msg): ?>
            <div class="error-alert"><?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST" action="give_coupon.php">
            <input type="hidden" name="form_user_id" value="<?= htmlspecialchars($target_user_id) ?>">
            <input type="hidden" name="form_customer_name" value="<?= htmlspecialchars($customer_name) ?>">

            <div class="form-group">
                <label>Mã Voucher Độc Quyền</label>
                <input type="text" name="code" class="form-control" value="<?= $suggested_code ?>" required>
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
                    <label>Ngày hết hạn</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">XÁC NHẬN TẶNG VOUCHER</button>
            <a href="dashboard.php" style="display: block; text-align: center; margin-top: 20px; color: #999; text-decoration: none; font-size: 13px;">Hủy bỏ và quay lại Dashboard</a>
        </form>
    </div>
</div>