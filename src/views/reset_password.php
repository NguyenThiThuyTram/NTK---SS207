<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$message = '';
$message_type = '';
$show_form = false;

if (empty($token)) {
    $message = "Liên kết này không hợp lệ hoặc đã hết hạn.";
    $message_type = "error";
} else {
    try {
        // Verify token and expiry
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE reset_token = :token AND reset_token_expiry > :now");
        $stmt->execute([
            'token' => $token,
            'now' => date('Y-m-d H:i:s')
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $message = "Liên kết này không hợp lệ hoặc đã hết hạn.";
            $message_type = "error";
        } else {
            $show_form = true;
        }
    } catch (PDOException $e) {
        $message = "Lỗi hệ thống: " . $e->getMessage();
        $message_type = "error";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $show_form) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $message = "Mật khẩu mới phải từ 6 ký tự trở lên!";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Xác nhận mật khẩu không trùng khớp!";
        $message_type = "error";
    } else {
        try {
            // Hash password using BCRYPT
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Update database and clear token details to prevent replay attacks
            $stmt = $conn->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = :token");
            $stmt->execute([
                'password' => $hashed_password,
                'token' => $token
            ]);

            $_SESSION['login_success_msg'] = "Đặt lại mật khẩu thành công! Vui lòng đăng nhập.";
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $message = "Lỗi hệ thống: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lại Mật Khẩu - NTK</title>
    <link rel="icon" type="image/png" href="../assets/images/logo-ntk.png">

    <style>
        /* Reset cơ bản */
        * { box-sizing: border-box; font-family: sans-serif; }

        /* Làm nền xám phủ kín màn hình */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.4);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0; 
        }

        /* Cái hộp trắng */
        .login-box {
            background: #fff;
            width: 450px;
            padding: 40px;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .title { text-transform: uppercase; font-size: 22px; font-weight: normal; margin-bottom: 30px; }

        /* Định dạng các ô nhập liệu */
        .input-group { margin-bottom: 25px; }
        .input-group label {
            display: block; font-size: 12px; color: #666; margin-bottom: 8px; text-transform: uppercase;
        }
        .input-group input {
            width: 100%; border: none; border-bottom: 1px solid #ccc; padding: 10px 0; font-size: 14px; outline: none;
        }
        .input-group input:focus { border-bottom: 1px solid #000; }

        /* Icon mắt */
        .password-wrapper { relative; }
        .eye-icon { 
            position: absolute; 
            right: 0; 
            top: 10px; 
            cursor: pointer; 
            color: #999; 
            user-select: none;
        }

        /* Nút submit */
        .btn-login {
            width: 100%; background: #1a1a1a; color: #fff; padding: 15px; border: none; font-size: 14px; font-weight: bold; cursor: pointer;
        }

        /* Link dưới cùng */
        .footer-link { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
        .footer-link a { color: #5b3e31; font-weight: bold; text-decoration: none; }

        /* DARK MODE OVERRIDES */
        body.dark-mode {
            background-color: #121212 !important;
            color: #ffffff !important;
        }
        body.dark-mode .login-box {
            background: #1e1e1e !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5) !important;
            color: #ffffff !important;
        }
        body.dark-mode .input-group label {
            color: #aaaaaa !important;
        }
        body.dark-mode .input-group input {
            background: transparent !important;
            color: #ffffff !important;
            border-bottom-color: #555555 !important;
        }
        body.dark-mode .input-group input:focus {
            border-bottom-color: #a6825c !important;
        }
        body.dark-mode .btn-login {
            background: #a6825c !important;
            color: #121212 !important;
        }
        body.dark-mode .btn-login:hover {
            background: #c9a47e !important;
        }
        body.dark-mode .footer-link {
            color: #bbbbbb !important;
        }
        body.dark-mode .footer-link a {
            color: #e5c199 !important;
        }
    </style>
</head>
<body style="margin: 0;"> 
    <div id="modal-overlay" class="modal-overlay">
        <div class="login-box">
            <h2 class="title">ĐẶT LẠI MẬT KHẨU</h2>

            <?php if (!empty($message)): ?>
                <div style="color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: 500; border: 1px solid #f87171; background-color: #fee2e2;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_form): ?>
                <form action="reset_password.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="input-group">
                        <label>MẬT KHẨU MỚI</label>
                        <div class="password-wrapper" style="position: relative;">
                            <input type="password" name="new_password" id="password" placeholder="Nhập mật khẩu mới..." required minlength="6">
                            <span class="eye-icon" id="togglePassword">👁️</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>XÁC NHẬN MẬT KHẨU</label>
                        <div class="password-wrapper" style="position: relative;">
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Nhập lại mật khẩu..." required minlength="6">
                            <span class="eye-icon" id="togglePasswordConfirm">👁️</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">CẬP NHẬT MẬT KHẨU</button>
                    
                </form>
            <?php endif; ?>

            <div class="footer-link">
                Quay lại <a href="login.php">Đăng nhập</a>
            </div>
        </div>
    </div>

    <script>
        // --- 0. Áp dụng chế độ tối từ localStorage ---
        if (localStorage.getItem('ntk_dark') === '1') {
            document.body.classList.add('dark-mode');
        }

        // --- 1. Xử lý tắt/bật con mắt mật khẩu ---
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        if (togglePassword && password) {
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.textContent = (type === 'text') ? '🙈' : '👁️';
            });
        }

        const togglePasswordConfirm = document.querySelector('#togglePasswordConfirm');
        const confirmPassword = document.querySelector('#confirm_password');
        if (togglePasswordConfirm && confirmPassword) {
            togglePasswordConfirm.addEventListener('click', function () {
                const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPassword.setAttribute('type', type);
                this.textContent = (type === 'text') ? '🙈' : '👁️';
            });
        }
    </script>
</body>
</html>
