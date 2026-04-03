<?php
session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = md5($_POST['password']); // Mã hóa pass để so sánh với DB

    try {
        // Tìm tài khoản theo Email và Password
        $sql = "SELECT * FROM Users WHERE email = :email AND password = :password";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // KIỂM TRA XEM ĐÃ XÁC THỰC OTP CHƯA?
            if ($user['is_verified'] == 0) {
                // Lưu lỗi vào Session và đá về lại trang login
                $_SESSION['login_error'] = "Tài khoản chưa xác thực OTP. Vui lòng kiểm tra Email!";
                header("Location: ../views/login.php");
                exit();
            }

            // NẾU THÀNH CÔNG VÀ ĐÃ XÁC THỰC
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            
            // Đá về trang chủ (Khải nhớ sửa lại đường dẫn trang chủ cho đúng với dự án của Khải nha)
            echo "<script>
                    alert('Đăng nhập thành công!');
                    window.location.href = '../../index.php';
                  </script>";
            exit();

        } else {
            // NẾU SAI EMAIL HOẶC MẬT KHẨU
            $_SESSION['login_error'] = "Email hoặc mật khẩu không chính xác!";
            header("Location: ../views/login.php");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['login_error'] = "Lỗi hệ thống: " . $e->getMessage();
        header("Location: ../views/login.php");
        exit();
    }
}
?>