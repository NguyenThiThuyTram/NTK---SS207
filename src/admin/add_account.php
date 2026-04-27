<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Lấy dữ liệu từ Form (Khớp với các cột ní yêu cầu)
    $username    = trim($_POST['username']);
    $fullname    = trim($_POST['fullname']);
    $email       = trim($_POST['email']);
    $phonenumber = trim($_POST['phonenumber']);
    $address     = trim($_POST['address']);
    $password    = $_POST['password'];
    $role        = $_POST['role'];

    // 2. Tự sinh ID: Dùng tiền tố US + chuỗi thời gian duy nhất
    $new_user_id = "US" . date('His') . rand(10, 99); 

    // 3. Mã hóa mật khẩu bảo mật
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 4. Câu lệnh SQL khớp hoàn toàn với cấu trúc ảnh database ní gửi
        $sql = "INSERT INTO users (user_id, username, fullname, email, phonenumber, address, password, role, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $new_user_id, 
            $username, 
            $fullname, 
            $email, 
            $phonenumber, 
            $address, 
            $hashed_password, 
            $role
        ]);
        
        header("Location: accounts.php?msg=added"); 
        exit;

    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $error_msg = "Lỗi: Username, Email hoặc Số điện thoại này đã tồn tại.";
        } else {
            $error_msg = "Lỗi Database: " . $e->getMessage();
        }
    }
}

$admin_current_page = 'accounts.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    .add-user-wrapper { padding: 30px; background: #fdfdfb; min-height: 100vh; display: flex; justify-content: center; }
    .form-card { width: 100%; max-width: 750px; background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
    .page-title { font-size: 22px; font-weight: 700; text-transform: uppercase; margin-bottom: 30px; color: #111; text-align: center; letter-spacing: 1px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 11px; font-weight: 700; color: #999; text-transform: uppercase; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; transition: 0.2; }
    .form-control:focus { border-color: #2f1c00; outline: none; box-shadow: 0 0 0 3px rgba(47,28,0,0.1); }
    .btn-submit { background: #2f1c00; color: #fff; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 10px; text-transform: uppercase; }
    .error-alert { background: #fdf0ef; color: #c0392b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; border-left: 4px solid #c0392b; }
</style>

<div class="add-user-wrapper">
    <div class="form-card">
        <h2 class="page-title">Thêm tài khoản hệ thống</h2>

        <?php if ($error_msg): ?>
            <div class="error-alert"><i class="fa-solid fa-circle-exclamation"></i> <?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Username (Tên đăng nhập)</label>
                    <input type="text" name="username" class="form-control" placeholder="Ví dụ: tuenghi06" required>
                </div>
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" name="fullname" class="form-control" placeholder="Nhập tên đầy đủ..." required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Địa chỉ Email</label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com" required>
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phonenumber" class="form-control" placeholder="Nhập số điện thoại..." required>
                </div>
            </div>

            <div class="form-group">
                <label>Địa chỉ thường trú</label>
                <input type="text" name="address" class="form-control" placeholder="Số nhà, tên đường, quận/huyện..." required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Phân quyền</label>
                    <select name="role" class="form-control">
                        <option value="0">Khách hàng (Customer)</option>
                        <option value="1">Quản trị viên (Admin)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-submit">Xác nhận tạo tài khoản</button>
            <a href="accounts.php" style="display: block; text-align: center; margin-top: 20px; color: #999; text-decoration: none; font-size: 13px;">Hủy và quay lại</a>
        </form>
    </div>
</div>