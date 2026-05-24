<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/database.php';

require dirname(__DIR__) . '/includes/PHPMailer/Exception.php';
require dirname(__DIR__) . '/includes/PHPMailer/PHPMailer.php';
require dirname(__DIR__) . '/includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Định dạng email không hợp lệ!";
        $message_type = "error";
    } else {
        // Prevent enumeration attack: always show a positive styled message
        $message = "Nếu email chính xác, một liên kết đặt lại mật khẩu đã được gửi đến hộp thư của bạn. Vui lòng kiểm tra email!";
        $message_type = "success";

        try {
            // Check if the user exists
            $stmt = $conn->prepare("SELECT user_id, password, fullname FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $user_id = $user['user_id'];
                
                // Generate a stateless token containing user_id, expiry, and signature
                $expiry = time() + 1800; // 30 minutes
                $secret_key = "NTK_FASHION_SECRET_KEY_2026";
                $signature = hash_hmac('sha256', $user_id . '|' . $expiry, $secret_key . $user['password']);
                $token = base64_encode($user_id . '|' . $expiry . '|' . $signature);

                // Construct dynamic URL (enforcing HTTPS on production)
                $protocol = 'http';
                if ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) ||
                    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                    (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'ntkfashion.me') !== false)) {
                    $protocol = 'https';
                }
                $host = $_SERVER['HTTP_HOST'] ?? 'ntkfashion.me';
                $reset_link = "$protocol://$host/src/views/reset_password.php?token=" . urlencode($token);

                // Dispatch Email using PHPMailer SMTP Gmail setup
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->SMTPDebug  = 2;
                    ob_start();

                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'tpkhai108@gmail.com'; 
                    $mail->Password   = 'nswcoznxscfxclae'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom('tpkhai108@gmail.com', 'Hệ Thống NTK');
                    $mail->addAddress($email, $user['fullname']);

                    $mail->isHTML(true);
                    $mail->Subject = 'Yêu cầu đặt lại mật khẩu NTK Fashion';
                    $mail->Body    = "
                        <h3>Chào {$user['fullname']},</h3>
                        <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
                        <p>Vui lòng click vào đường dẫn dưới đây để tiến hành đặt lại mật khẩu mới (Liên kết có giá trị trong vòng 30 phút):</p>
                        <p><a href='$reset_link' style='color:#2f1c00; font-weight:bold;'>$reset_link</a></p>
                        <p>Nếu bạn không gửi yêu cầu này, vui lòng bỏ qua email này.</p>
                        <p>Trân trọng,<br>Đội ngũ NTK Fashion</p>
                    ";

                    $mail->send();
                    ob_end_clean();
                } catch (Exception $e) {
                    $smtp_log = ob_get_clean();
                    // TẠM THỜI IN THẲNG LỖI RA MÀN HÌNH ĐỂ BẮT BỆNH
                    die("<div style='background:#fff; padding:20px; color:red; text-align:left;'>
                            <h3>LỖI GỬI MAIL:</h3>
                            <b>Lỗi hệ thống:</b> " . $e->getMessage() . "<br>
                            <b>Chi tiết PHPMailer:</b> " . $mail->ErrorInfo . "<br>
                            <b>Log máy chủ trả về:</b> <pre>" . htmlspecialchars($smtp_log) . "</pre>
                         </div>");
                }
            }
        } catch (PDOException $e) {
            die("Lỗi Database: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu - NTK</title>
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

        /* Nút X ở góc */
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            text-decoration: none;
            color: #000;
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
        body.dark-mode .close-btn {
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
            <a href="login.php" class="close-btn">&times;</a>
            <h2 class="title">QUÊN MẬT KHẨU</h2>

            <?php if (!empty($message)): ?>
                <div style="color: <?php echo $message_type === 'success' ? '#15803d' : '#dc2626'; ?>; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: 500; border: 1px solid <?php echo $message_type === 'success' ? '#86efac' : '#f87171'; ?>; background-color: <?php echo $message_type === 'success' ? '#f0fdf4' : '#fee2e2'; ?>;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST">
                
                <div class="input-group">
                    <label>EMAIL ĐĂNG KÝ</label>
                    <input type="email" name="email" placeholder="Nhập email của bạn..." required>
                </div>

                <button type="submit" class="btn-login">GỬI YÊU CẦU ĐẶT LẠI MẬT KHẨU</button>
                
            </form>

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
    </script>
</body>
</html>
