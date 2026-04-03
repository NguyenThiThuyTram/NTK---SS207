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
                        <input type="password" id="myPassword" name="password" placeholder="........" required>
                        <span class="eye-icon" id="toggleEye">👁️</span>
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
        // --- 1. CODE XỬ LÝ CON MẮT HIỂN THỊ MẬT KHẨU (Đã có sẵn của bạn) ---
        const toggleEye = document.getElementById('toggleEye');
        const passwordInput = document.getElementById('myPassword');

        toggleEye.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.style.opacity = type === 'password' ? '1' : '0.5';
        });

        // --- 2. CODE XỬ LÝ CLICK VÙNG XÁM & CHUYỂN TRANG MỚI THÊM ---
        
        // Bắt sự kiện click ra nền xám
        document.getElementById('modal-overlay').addEventListener('click', function(event) {
            if (event.target === this) {
                goToHome();
            }
        });

        // Hàm thoát về trang chủ
        function goToHome() {
            window.location.replace('../index.php');
        }

        // Hàm chuyển sang trang Đăng nhập
        function goToLogin() {
            window.location.replace('login.php');
        }
    </script>

</body>
</html>