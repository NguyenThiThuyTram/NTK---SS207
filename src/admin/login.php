<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = md5($_POST['password'] ?? '');

    try {
        // Strict role == 1 check
        $sql = "SELECT * FROM users WHERE email = :email AND role = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = null;
        if ($stmt->rowCount() > 0) {
            $potential_user = $stmt->fetch(PDO::FETCH_ASSOC);
            $plain_password = $_POST['password'] ?? '';
            $authenticated = false;

            if (strlen($potential_user['password']) === 32) {
                // Legacy MD5 Check
                if ($potential_user['password'] === md5($plain_password)) {
                    $authenticated = true;
                }
            } else {
                // Modern BCRYPT Check
                if (password_verify($plain_password, $potential_user['password'])) {
                    $authenticated = true;
                }
            }

            if ($authenticated) {
                $user = $potential_user;
            }
        }

        if ($user) {

            // Check OTP verification
            if ($user['is_verified'] == 0) {
                $login_error = "Tài khoản chưa xác thực OTP. Vui lòng kiểm tra Email!";
            } else {
                // Set Admin Sessions
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = 1;
                $_SESSION['admin_logged_in'] = true;

                header("Location: dashboard.php");
                exit();
            }
        } else {
            $login_error = "Sai thông tin đăng nhập hoặc bạn không có quyền truy cập";
        }
    } catch(PDOException $e) {
        $login_error = "Lỗi hệ thống: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị - NTK</title>
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
        .password-wrapper { position: relative; }
        .eye-icon { 
            position: absolute; 
            right: 0; 
            top: 10px; 
            cursor: pointer; 
            color: #999; 
            user-select: none; /* Tránh bị bôi đen khi click nhanh */
        }

        /* Căn ngang Checkbox */
        .row-flex { display: flex; justify-content: space-between; align-items: center; font-size: 14px; margin-bottom: 25px; }

        /* Nút Đăng nhập */
        .btn-login {
            width: 100%; background: #1a1a1a; color: #fff; padding: 15px; border: none; font-size: 14px; font-weight: bold; cursor: pointer;
        }

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
        body.dark-mode .remember-me {
            color: #bbbbbb !important;
        }
        body.dark-mode .btn-login {
            background: #a6825c !important;
            color: #121212 !important;
        }
        body.dark-mode .btn-login:hover {
            background: #c9a47e !important;
        }
    </style>
</head>
<body style="margin: 0;"> 
    <div id="modal-overlay" class="modal-overlay">
        <div class="login-box">
            <h2 class="title">ĐĂNG NHẬP QUẢN TRỊ</h2>

            <?php if (!empty($login_error)): ?>
                <div style="background-color: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: 500; border: 1px solid #f87171;">
                    <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                
                <div class="input-group">
                    <label>EMAIL HỆ THỐNG</label>
                    <input type="text" name="email" placeholder="Nhập email admin..." required>
                </div>

                <div class="input-group">
                    <label>MẬT KHẨU</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="........" required>
                        <span class="eye-icon" id="togglePassword">👁️</span>
                    </div>
                </div>

                <div class="row-flex">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Ghi nhớ đăng nhập
                    </label>
                </div>

                <button type="submit" class="btn-login">ĐĂNG NHẬP</button>
                
            </form>
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

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            if (type === 'text') {
                this.textContent = '🙈'; 
            } else {
                this.textContent = '👁️'; 
            }
        });
    </script>
</body>
</html>
