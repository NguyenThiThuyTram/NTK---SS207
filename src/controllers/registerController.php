<?php
session_start();
require_once '../config/database.php';

require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = md5($_POST['password']);
    
    $user_id = 'U' . rand(1000, 9999); 
    $username = $email;
    
    // Tạo OTP 4 SỐ
    $verification_code = sprintf("%04d", mt_rand(1, 9999));
    
    try {
        $sql = "INSERT INTO Users (user_id, username, password, fullname, email, phonenumber, verification_code) 
                VALUES (:user_id, :username, :password, :fullname, :email, :phone, :code)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':fullname', $fullname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':code', $verification_code);
        
        $stmt->execute();
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            
            // ⚠️ CHÚ Ý: NHỚ ĐIỀN LẠI EMAIL VÀ PASS 16 KÝ TỰ CỦA KHẢI VÀO 2 DÒNG NÀY NHA ⚠️
            $mail->Username   = 'tpkhai108@gmail.com'; 
            $mail->Password   = 'nswc oznx scfx clae
'; 
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('tpkhai108@gmail.com', 'Hệ Thống NTK');
            $mail->addAddress($email, $fullname);

            $mail->isHTML(true);
            $mail->Subject = 'Mã xác nhận đăng ký tài khoản NTK';
            $mail->Body    = "
                <h3>Chào $fullname,</h3>
                <p>Cảm ơn bạn đã đăng ký tài khoản tại hệ thống NTK.</p>
                <p>Mã xác nhận (OTP) của bạn là: <b style='color:red; font-size:24px;'>$verification_code</b></p>
                <p>Trân trọng,<br>Đội ngũ NTK</p>
            ";

            $mail->send();
            
            // THÀNH CÔNG -> ĐÁ SANG TRANG OTP
            echo "<script>
                    alert('Đăng ký thành công! Hệ thống đã gửi mã OTP 4 số vào Email của bạn.');
                    window.location.href = '../views/verify_otp.php?email=$email';
                  </script>";
        } catch (Exception $e) {
            // LỖI GỬI MAIL CŨNG ĐÁ SANG TRANG OTP (ĐỂ BIẾT MÀ SỬA LỖI)
            echo "<script>
                    alert('Lỗi gửi mail: Kiểm tra lại Mật khẩu ứng dụng 16 ký tự nhé! Lỗi chi tiết: {$mail->ErrorInfo}');
                    window.location.href = '../views/verify_otp.php?email=$email';
                  </script>";
        }
        
    } catch(PDOException $e) {
        echo "<script>
                alert('Lỗi: Email hoặc số điện thoại này đã tồn tại trong hệ thống!');
                window.history.back();
              </script>";
    }
}
?>
