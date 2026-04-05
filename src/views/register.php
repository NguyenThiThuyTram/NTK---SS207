<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký NTK</title>

    <style>
        * { box-sizing: border-box; font-family: sans-serif; }
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.4);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .login-box {
            background: #fff;
            width: 450px;
            padding: 40px;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .close-btn {
            position: absolute; top: 20px; right: 20px;
            background: none; border: none; font-size: 20px; cursor: pointer;
        }
        .title { text-transform: uppercase; font-size: 22px; font-weight: normal; margin-bottom: 30px; }
        
        .input-group { margin-bottom: 20px; } 
        .input-group label {
            display: block; font-size: 12px; color: #666; margin-bottom: 8px; text-transform: uppercase;
        }
        .input-group input {
            width: 100%; border: none; border-bottom: 1px solid #ccc; padding: 10px 0; font-size: 14px; outline: none;
        }
        .input-group input:focus { border-bottom: 1px solid #000; }
        
        /* Icon mắt */
        .password-wrapper { position: relative; }
        .eye-icon { position: absolute; right: 0; top: 10px; cursor: pointer; color: #999; user-select: none; }
        
        .btn-login {
            width: 100%; background: #1a1a1a; color: #fff; padding: 15px; border: none; font-size: 14px; font-weight: bold; cursor: pointer; margin-top: 10px;
        }
        .footer-link { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
        .footer-link a { color: #5b3e31; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body style="margin: 0;">

    <div id="modal-overlay" class="modal-overlay">
        <div class="login-box">
            <button class="close-btn" onclick="goToHome()">&times;</button>
            <h2 class="title">ĐĂNG KÝ</h2>

            <?php if (isset($_SESSION['register_error'])): ?>
                <div style="background-color: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: 500; border: 1px solid #f87171;">
                    <?php 
                        echo $_SESSION['register_error']; 
                        unset($_SESSION['register_error']); 
                    ?>
                </div>
            <?php endif; ?>

            <form action="../controllers/registerController.php" method="POST">
                
                <div class="input-group">
                    <label>HỌ VÀ TÊN</label>
                    <input type="text" name="fullname" placeholder="Nhập họ và tên" required>
                </div>

                <div class="input-group">
                    <label>EMAIL</label>
                    <input type="email" name="email" placeholder="Nhập email của bạn" required>
                </div>

                <div class="input-group">
                    <label>SỐ ĐIỆN THOẠI</label>
                    <input type="tel" name="phone" placeholder="0912345678" required>
                </div>

                <div class="input-group">
                    <label>MẬT KHẨU</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="........" required>
                        <span class="eye-icon" id="togglePassword">👁️</span>
                    </div>
                </div>

                <button type="submit" class="btn-login">ĐĂNG KÝ</button>
                
            </form>

            <div class="footer-link">
                Đã có tài khoản? <a href="javascript:void(0)" onclick="goToLogin()">Đăng nhập</a>
            </div>
        </div>
    </div>

    <script>
        // --- 1. Xử lý click ra ngoài nền xám để về trang chủ ---
        document.getElementById('modal-overlay').addEventListener('click', function(event) {
            if (event.target === this) {
                goToHome();
            }
        });

        function goToHome() {
            window.location.replace('../index.php');
        }

        function goToLogin() {
            window.location.replace('login.php');
        }

        // --- 2. Xử lý tắt/bật con mắt mật khẩu ---
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