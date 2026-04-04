<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập NTK</title>

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

        /* Nút X ở góc */
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
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

        /* Căn ngang Checkbox và Quên mật khẩu */
        .row-flex { display: flex; justify-content: space-between; align-items: center; font-size: 14px; margin-bottom: 25px; }
        .forgot-pass { color: #666; text-decoration: none; }

        /* Nút Đăng nhập */
        .btn-login {
            width: 100%; background: #1a1a1a; color: #fff; padding: 15px; border: none; font-size: 14px; font-weight: bold; cursor: pointer;
        }

        /* Link dưới cùng */
        .footer-link { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
        .footer-link a { color: #5b3e31; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body> 
    <div class="modal-overlay">
        <div class="login-box">
            <button class="close-btn">&times;</button>
            <h2 class="title">ĐĂNG NHẬP</h2>

            <?php if (isset($_SESSION['login_error'])): ?>
                <div style="background-color: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: 500; border: 1px solid #f87171;">
                    <?php 
                        echo $_SESSION['login_error']; 
                        unset($_SESSION['login_error']); // Xóa lỗi sau khi hiện để lần sau không bị dính
                    ?>
                </div>
            <?php endif; ?>

            <form action="../controllers/loginController.php" method="POST">
                
                <div class="input-group">
                    <label>EMAIL HOẶC SỐ ĐIỆN THOẠI</label>
                    <input type="text" name="email" placeholder="Nhập email của bạn..." required>
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
                    <a href="#" class="forgot-pass">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-login">ĐĂNG NHẬP</button>
                
            </form>

            <div class="footer-link">
                Chưa có tài khoản? <a href="register.php">Đăng ký</a>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            // Kiểm tra xem input đang ẩn (password) hay hiện (text)
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            
            // Đổi thuộc tính type của ô input
            password.setAttribute('type', type);
            
            // Đổi icon: Nếu đang hiện chữ (text) thì nhắm mắt lại, ngược lại thì mở mắt ra
            if (type === 'text') {
                this.textContent = '🙈'; // Icon nhắm mắt
            } else {
                this.textContent = '👁️'; // Icon mở mắt
            }
        });
    </script>
</body>
</html>