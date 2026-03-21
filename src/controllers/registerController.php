<?php
// Bật session để lưu thông tin nếu cần
session_start();

// Gọi file kết nối Database
require_once '../config/database.php';

// Gọi anh bưu tá PHPMailer vào làm việc
require '../includes/PHPMailer/Exception.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Hứng dữ liệu từ form
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = md5($_POST['password']); // Mã hóa mật khẩu
    
    // Tạo user_id và username
    $user_id = 'U' . rand(1000, 9999); 
    $username = $email;
    
    // TẠO MÃ OTP 6 SỐ NGẪU NHIÊN
    $verification_code = sprintf("%06d", mt_rand(1, 999999));
    
    try {
        // LƯU VÀO DATABASE BẢNG USERS
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
        
        // --- BẮT ĐẦU QUÁ TRÌNH GỬI MAIL OTP ---
        $mail = new PHPMailer(true);
        try {
            // Cấu hình Bưu điện Google
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            
            // ⚠️ KHẢI ĐIỀN THÔNG TIN CỦA KHẢI VÀO 2 DÒNG NÀY ⚠️
            $mail->Username   = 'email_cua_khai_muon_gui@gmail.com'; // Ví dụ: ntk.shop@gmail.com
            $mail->Password   = 'ĐIỀN_MẬT_KHẨU_16_KÝ_TỰ_VÀO_ĐÂY'; // Dán 16 chữ cái nãy lấy được vào đây
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Người gửi và người nhận
            $mail->setFrom('email_cua_khai_muon_gui@gmail.com', 'Hệ Thống NTK');
            $mail->addAddress($email, $fullname); // Gửi tới email khách hàng điền trong form

            // Nội dung thư
            $mail->isHTML(true);
            $mail->Subject = 'Mã xác nhận đăng ký tài khoản NTK';
            $mail->Body    = "
                <h3>Chào $fullname,</h3>
                <p>Cảm ơn bạn đã đăng ký tài khoản tại hệ thống NTK.</p>
                <p>Mã xác nhận (OTP) của bạn là: <b style='color:red; font-size:24px;'>$verification_code</b></p>
                <p>Trân trọng,<br>Đội ngũ NTK</p>
            ";

            // Bấm gửi!
            $mail->send();
            
            // Xong xuôi thì báo thành công và đá về trang Login
            echo "<script>
                    alert('Đăng ký thành công! Hệ thống đã gửi mã OTP vào Email của bạn.');
                    window.location.href = '../views/login.php';
                  </script>";
        } catch (Exception $e) {
            // Lưu DB thành công nhưng gửi mail lỗi (thường do sai mật khẩu 16 ký tự)
            echo "<script>
                    alert('Đăng ký thành công nhưng không thể gửi Email OTP. Lỗi: {$mail->ErrorInfo}');
                    window.location.href = '../views/login.php';
                  </script>";
        }
        
    } catch(PDOException $e) {
        // Lỗi do trùng email hoặc trùng SĐT
        echo "<script>
                alert('Lỗi: Email hoặc số điện thoại này đã tồn tại trong hệ thống!');
                window.history.back();
              </script>";
    }
}
?>
