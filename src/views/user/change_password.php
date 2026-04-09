<?php
$user_id = $_SESSION['user_id'];
$thong_bao = ''; 
$loai_thong_bao = ''; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_doi_mat_khau'])) {
    $mat_khau_cu = $_POST['current_password'];
    $mat_khau_moi = $_POST['new_password'];
    $xac_nhan_mk = $_POST['confirm_password'];

    if (empty($mat_khau_cu) || empty($mat_khau_moi) || empty($xac_nhan_mk)) {
        $thong_bao = "Vui lòng nhập đầy đủ thông tin!";
        $loai_thong_bao = "error";
    } 
    elseif ($mat_khau_moi !== $xac_nhan_mk) {
        $thong_bao = "Mật khẩu mới và xác nhận không khớp nhau!";
        $loai_thong_bao = "error";
    } 
    else {
        try {
            $sql = "SELECT password FROM Users WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $pass_trong_db = $user['password'];
                
                // User may have md5 (legacy) or password_hash
                // Support both during transition, but the user explicitly requested password_verify so we will prioritize it.
                // If it fails password_verify, fallback to md5 matching for legacy accounts to not lock them out, THEN hash the new password.
                $is_valid = password_verify($mat_khau_cu, $pass_trong_db);
                if (!$is_valid && md5($mat_khau_cu) === $pass_trong_db) {
                    $is_valid = true;
                }

                if ($is_valid) {
                    // Hash mật khẩu mới bằng thuật toán bcrypt an toàn
                    $hashed_password = password_hash($mat_khau_moi, PASSWORD_DEFAULT);
                    
                    $sql_update = "UPDATE Users SET password = :new_password WHERE user_id = :user_id";
                    $stmt_update = $conn->prepare($sql_update);
                    
                    if ($stmt_update->execute(['new_password' => $hashed_password, 'user_id' => $user_id])) {
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
    .password-desc { font-size: 14px; color: var(--text-muted); margin-bottom: 30px; margin-top: -10px;}
    .password-form { max-width: 500px; padding: 20px; border: 1px solid var(--border-color); background: var(--bg-white);}
    .form-group { display: flex; flex-direction: column; margin-bottom: 20px; }
    .form-group label { font-size: 14px; font-weight: bold; margin-bottom: 5px; color: var(--text-main); }
    .form-group input { padding: 10px 15px; border: 1px solid var(--border-color); outline: none; font-size: 14px; transition: 0.2s; }
    .form-group input:focus { border-color: var(--primary); }
    
    .btn-save { background-color: var(--primary); color: #fff; border: none; padding: 10px 30px; font-size: 14px; cursor: pointer; transition: 0.2s; width: 100%;}
    .btn-save:hover { opacity: 0.9; }

    .alert { padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 14px; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<div class="content-header" style="margin-bottom: 20px; border-bottom: none; padding-bottom: 0;">
    <h2 class="section-title" style="margin: 0 0 10px 0; padding: 0; border-bottom: none;">Đổi mật khẩu</h2>
    
    <p class="password-desc" style="margin: 5px 0 0 0; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; color: var(--text-muted);">
        Để bảo mật tài khoản, vui lòng không chia sẻ mật khẩu
    </p>
</div>

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

        <div style="margin-top: 15px;">
            <button type="submit" name="btn_doi_mat_khau" style="padding: 10px 20px; background-color: #000; color: #fff; border: none; cursor: pointer; width: 100%;">Lưu thay đổi</button>
        </div>
    </form>
</div>