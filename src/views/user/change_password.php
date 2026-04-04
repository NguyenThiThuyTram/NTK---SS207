<?php
// Bắt buộc nhúng file database vào để nói chuyện với SQL
require_once '../../config/database.php';

$user_id = $_SESSION['user_id'] ?? 1; // ID user (tạm lấy 1 để test)
$thong_bao = ''; // Biến lưu câu thông báo lỗi/thành công
$loai_thong_bao = ''; // 'success' hoặc 'error' để tô màu cho thông báo

// Kiểm tra xem người dùng có bấm nút "Xác nhận" (submit form) chưa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_doi_mat_khau'])) {
    $mat_khau_cu = $_POST['current_password'];
    $mat_khau_moi = $_POST['new_password'];
    $xac_nhan_mk = $_POST['confirm_password'];

    // 1. Kiểm tra nhập thiếu
    if (empty($mat_khau_cu) || empty($mat_khau_moi) || empty($xac_nhan_mk)) {
        $thong_bao = "Vui lòng nhập đầy đủ thông tin!";
        $loai_thong_bao = "error";
    } 
    // 2. Kiểm tra mật khẩu mới và xác nhận có khớp không
    elseif ($mat_khau_moi !== $xac_nhan_mk) {
        $thong_bao = "Mật khẩu mới và xác nhận không khớp nhau!";
        $loai_thong_bao = "error";
    } 
    // 3. Nếu mọi thứ ok thì vào database check mật khẩu cũ
    else {
        try {
            // LƯU Ý: Anh giả sử cột mật khẩu trong bảng Users của mày tên là 'password'
            $sql = "SELECT password FROM Users WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $pass_trong_db = $user['password'];
                   
                if ($mat_khau_cu === $pass_trong_db) {
                    
                    // Cập nhật mật khẩu mới vào database
                    // (Nếu dùng mã hóa thì chỗ này phải xài password_hash() nhé)
                    $sql_update = "UPDATE Users SET password = :new_password WHERE user_id = :user_id";
                    $stmt_update = $conn->prepare($sql_update);
                    
                    if ($stmt_update->execute(['new_password' => $mat_khau_moi, 'user_id' => $user_id])) {
                        $thong_bao = "Đổi mật khẩu thành công!";
                        $loai_thong_bao = "success";
                    } else {
                        $thong_bao = "Có lỗi xảy ra khi cập nhật, vui lòng thử lại!";
                        $loai_thong_bao = "error";
                    }
                } else {
                    $thong_bao = "Mật khẩu hiện tại không đúng!";
                    $loai_thong_bao = "error";
                }
            }
        } catch (PDOException $e) {
            $thong_bao = "Lỗi database: " . $e->getMessage();
            $loai_thong_bao = "error";
        }
    }
}
?>

<style>
    /* CSS CSS CSS - Bê y xì thiết kế của mày vào */
    .password-desc { font-size: 14px; color: var(--text-muted); margin-bottom: 30px; margin-top: -10px;}
    
    .password-form { max-width: 500px; }
    .form-group { display: flex; align-items: center; margin-bottom: 20px; }
    .form-group label { width: 150px; font-size: 14px; color: var(--text-main); text-align: right; margin-right: 20px; flex-shrink: 0; }
    .form-group input { flex-grow: 1; padding: 10px 15px; border: 1px solid var(--border-color); outline: none; font-size: 14px; transition: 0.2s; }
    .form-group input:focus { border-color: var(--primary); }
    
    .btn-submit { background-color: #331f00; color: #fff; border: none; padding: 10px 30px; font-size: 14px; cursor: pointer; margin-left: 170px; transition: 0.2s; }
    .btn-submit:hover { background-color: #5c3800; }

    /* CSS cho thông báo */
    .alert { padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 14px; margin-left: 170px; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<h2 class="section-title" style="margin-bottom: 5px; border-bottom: none; padding-bottom: 0;">Đổi mật khẩu</h2>
<p class="password-desc" style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">Để bảo mật tài khoản, vui lòng không chia sẻ mật khẩu cho người khác</p>

<div class="password-content">
    <form action="" method="POST" class="password-form">
        
        <?php if (!empty($thong_bao)): ?>
            <div class="alert alert-<?php echo $loai_thong_bao; ?>">
                <?php echo $thong_bao; ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="current_password">Mật khẩu hiện tại</label>
            <input type="password" id="current_password" name="current_password" placeholder="Nhập mật khẩu hiện tại" required>
        </div>

        <div class="form-group">
            <label for="new_password">Mật khẩu mới</label>
            <input type="password" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới" required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Xác nhận mật khẩu</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required>
        </div>

        <button type="submit" name="btn_doi_mat_khau" class="btn-submit">Xác nhận</button>
    </form>
</div>