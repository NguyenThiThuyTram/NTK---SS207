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
            margin: 0; /* Xóa khoảng trắng viền mặc định của body */
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
        .eye-icon { position: absolute; right: 0; top: 10px; cursor: pointer; color: #999; }

        /* Căn ngang Checkbox và Quên mật khẩu */
        .row-flex { display: flex; justify-content: space-between; align-items: center; font-size: 14px; margin-bottom: 25px; }
        .forgot-pass { color: #666; text-decoration: none; }

        /* Hộp vàng Demo */
        .demo-box { background-color: #fdfaf0; padding: 15px; font-size: 14px; color: #555; margin-bottom: 30px; line-height: 1.6;}
        .demo-box p { margin: 0; }

        /* Nút Đăng nhập */
        .btn-login {
            width: 100%; background: #1a1a1a; color: #fff; padding: 15px; border: none; font-size: 14px; font-weight: bold; cursor: pointer;
        }

        /* Link dưới cùng */
        .footer-link { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
        .footer-link a { color: #5b3e31; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body style="margin: 0;"> 
    <div id="modal-overlay" class="modal-overlay">
        <div class="login-box">
            <button class="close-btn" onclick="goToHome()">&times;</button>
            <h2 class="title">ĐĂNG NHẬP</h2>

            <form action="" method="POST">
                
                <div class="input-group">
                    <label>EMAIL HOẶC SỐ ĐIỆN THOẠI</label>
                    <input type="text" name="email" placeholder="demo@ntk.vn hoặc admin@ntk.vn" required>
                </div>

                <div class="input-group">
                    <label>MẬT KHẨU</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" placeholder="........" required>
                        <span class="eye-icon">👁️</span>
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
                Chưa có tài khoản? <a href="javascript:void(0)" onclick="goToRegister()">Đăng ký</a>
            </div>
        </div>
    </div>

    <script>
        // Xử lý click ra ngoài nền xám để về trang chủ
        document.getElementById('modal-overlay').addEventListener('click', function(event) {
            // Đảm bảo chỉ thoát khi click đúng nền xám, không bị thoát khi click vào form trắng
            if (event.target === this) {
                goToHome();
            }
        });

        // Hàm quay về trang chủ (Dùng replace để không lưu lịch sử, thoát 1 lần là xong)
        function goToHome() {
            window.location.replace('../index.php');
        }

        // Hàm chuyển sang trang Đăng ký (Dùng replace để không dồn lịch sử bấm Back)
        function goToRegister() {
            window.location.replace('register.php');
        }
    </script>
</body>
</html>