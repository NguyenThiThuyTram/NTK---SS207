<?php
// BẮT BUỘC: Giả lập dữ liệu từ Database (Trong thực tế, đoạn này lấy từ $_SESSION hoặc truy vấn DB)
// Nếu mày đã có session, đổi thành: $user = $_SESSION['user'];
$user = [
    'username' => 'demo_ntk',
    'fullname' => 'Nguyễn Văn A',
    'email' => 'demo@ntk.vn',
    'phone' => '0912345678',
    'gender' => 'nam', // 'nam', 'nu', hoặc 'khac'
    'dob' => '1995-05-20' // Định dạng YYYY-MM-DD từ DB
];
?>

<style>
    /* CSS RIÊNG CHO PHẦN HỒ SƠ - ĐẢM BẢO GIỐNG 100% DESIGN */
    .profile-container {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-top: 20px;
    }

    /* Cột Form bên trái */
    .form-area {
        flex: 1;
        padding-right: 40px;
        border-right: 1px solid var(--border-color);
    }

    .form-group {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .form-group label {
        width: 120px;
        text-align: right;
        margin-right: 25px;
        font-size: 14px;
        color: var(--text-muted);
    }

    .form-control {
        flex: 1;
        max-width: 450px;
        padding: 10px 15px;
        border: 1px solid var(--border-color);
        border-radius: 2px;
        font-size: 14px;
        color: var(--text-main);
        outline: none;
    }

    .form-control:focus {
        border-color: var(--text-muted);
    }

    .form-control:disabled {
        background-color: var(--bg-body);
        color: var(--text-muted);
        cursor: not-allowed;
    }

    /* Căn chỉnh Radio Giới tính */
    .radio-group {
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .radio-group label {
        width: auto;
        margin: 0;
        text-align: left;
        color: var(--text-main);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .radio-group input[type="radio"] {
        accent-color: var(--primary); /* Đổi màu nút tick thành màu nâu */
        cursor: pointer;
    }

    /* Nút Lưu */
    .btn-save {
        background-color: var(--primary);
        color: var(--bg-white);
        border: none;
        padding: 10px 40px;
        border-radius: 2px;
        cursor: pointer;
        font-size: 14px;
        margin-left: 145px; /* Đẩy vào bằng với label */
        margin-top: 10px;
    }
    .btn-save:hover { opacity: 0.9; }

    /* Cột Avatar bên phải */
    .avatar-area {
        width: 280px;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-left: 20px;
    }

    .avatar-preview {
        width: 100px;
        height: 100px;
        background-color: #eef0f4;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 45px;
        color: #c5cbd5;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .btn-upload {
        background-color: var(--bg-white);
        color: var(--text-main);
        border: 1px solid var(--border-color);
        padding: 8px 20px;
        border-radius: 2px;
        cursor: pointer;
        font-size: 14px;
        margin-bottom: 15px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .btn-upload:hover { background-color: #fafafa; }

    .avatar-hint {
        font-size: 13px;
        color: var(--text-muted);
        text-align: center;
        line-height: 1.6;
    }
</style>

<div class="content-header">
    <h2>Hồ Sơ Của Tôi</h2>
    <p>Quản lý thông tin hồ sơ để bảo mật tài khoản</p>
</div>

<div class="profile-container">
    <div class="form-area">
        <form action="../../controllers/UserController.php?action=update_profile" method="POST">
            
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>

            <div class="form-group">
                <label>Tên</label>
                <input type="text" name="ho_ten" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>

            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="sdt" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>

            <div class="form-group">
                <label>Giới tính</label>
                <div class="radio-group">
                    <label><input type="radio" name="gioi_tinh" value="nam" <?php echo ($user['gender'] == 'nam') ? 'checked' : ''; ?>> Nam</label>
                    <label><input type="radio" name="gioi_tinh" value="nu" <?php echo ($user['gender'] == 'nu') ? 'checked' : ''; ?>> Nữ</label>
                    <label><input type="radio" name="gioi_tinh" value="khac" <?php echo ($user['gender'] == 'khac') ? 'checked' : ''; ?>> Khác</label>
                </div>
            </div>

            <div class="form-group">
                <label>Ngày sinh</label>
                <input type="date" name="ngay_sinh" class="form-control" value="<?php echo htmlspecialchars($user['dob']); ?>">
            </div>

            <button type="submit" class="btn-save">Lưu</button>
        </form>
    </div>

    <div class="avatar-area">
        <div class="avatar-preview">
            <i class="fa-solid fa-user"></i>
            </div>
        <button type="button" class="btn-upload">Chọn ảnh</button>
        <div class="avatar-hint">
            Dung lượng file tối đa 1 MB<br>Định dạng: .JPEG, .PNG
        </div>
    </div>
</div>