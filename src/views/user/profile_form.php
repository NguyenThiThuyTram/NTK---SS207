<?php
// Đảm bảo chạy trong dashboard.php (đã có $conn và $_SESSION['user_id'])
$user_id = $_SESSION['user_id'];

// Xử lý khi người dùng bấm nút "Cập Nhật Thông Tin"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phonenumber = trim($_POST['phonenumber']);

    try {
        // Cập nhật đúng 3 cột trong bảng Users: fullname, email, phonenumber
        $stmt = $conn->prepare("UPDATE Users SET fullname = :fullname, email = :email, phonenumber = :phonenumber WHERE user_id = :user_id");
        $stmt->execute([
            'fullname' => $fullname,
            'email' => $email,
            'phonenumber' => $phonenumber,
            'user_id' => $user_id
        ]);
        
        // Nếu đại ca có lưu tên vào session để hiện ở menu, thì cập nhật luôn
        $_SESSION['fullname'] = $fullname;

        echo "<script>alert('Cập nhật thông tin hồ sơ thành công!'); window.location.href='dashboard.php?view=hoso';</script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
}

// Lấy thông tin mới nhất từ Database để nhét vào các ô input
$stmt = $conn->prepare("SELECT username, fullname, email, phonenumber FROM Users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="content-header">
    <h2>Hồ Sơ Của Tôi</h2>
    <p>Quản lý thông tin hồ sơ để bảo mật tài khoản</p>
</div>

<style>
    /* Chỉnh lại giao diện: Căn giữa, bỏ cột avatar, form nhìn sang trọng hơn */
    .profile-container {
        max-width: 650px; 
        padding: 30px;
        background: var(--bg-white, #fff);
        border: 1px solid var(--border-color, #e0e0e0);
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .form-group {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .form-group label {
        width: 150px;
        text-align: right;
        margin-right: 25px;
        font-size: 14px;
        font-weight: bold;
        color: #333;
    }

    .form-control {
        flex: 1;
        padding: 12px 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 15px;
        color: #333;
        outline: none;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary, #000);
    }

    .form-control:disabled {
        background-color: #f5f5f5;
        color: #888;
        cursor: not-allowed;
    }

    .btn-save {
        background: var(--primary, #000);
        color: #fff;
        padding: 12px 30px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 15px;
        font-weight: bold;
        margin-left: 175px; /* Đẩy cái nút qua phải cho thẳng hàng với ô input */
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<div class="profile-container">
    <form method="POST" action="">
        <input type="hidden" name="action" value="update_profile">
        
        <div class="form-group">
            <label>Tên đăng nhập</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
        </div>

        <div class="form-group">
            <label>Họ và Tên</label>
            <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" placeholder="Nhập họ và tên..." required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Nhập email..." required>
        </div>

        <div class="form-group">
            <label>Số điện thoại</label>
            <input type="text" name="phonenumber" class="form-control" value="<?= htmlspecialchars($user['phonenumber'] ?? '') ?>" placeholder="Nhập số điện thoại..." required>
        </div>

        <button type="submit" class="btn-save">
            <i class="fa-solid fa-floppy-disk"></i> Cập Nhật Thông Tin
        </button>
    </form>
</div>