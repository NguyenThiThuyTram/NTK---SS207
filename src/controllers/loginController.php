<?php
session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = md5($_POST['password']); 

    // Nhận cái link "trí nhớ" từ thẻ input ẩn lúc nãy
    $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '../index.php';

    try {
        $sql = "SELECT * FROM Users WHERE email = :email AND password = :password";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // KIỂM TRA OTP
            if ($user['is_verified'] == 0) {
                $_SESSION['login_error'] = "Tài khoản chưa xác thực OTP. Vui lòng kiểm tra Email!";
                header("Location: ../views/login.php");
                exit();
            }

            // LƯU SESSION
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            
            // LUỒNG LOGIC: Báo thành công và văng về ĐÚNG TRANG CŨ
            echo "<script>
                    alert('Đăng nhập thành công!');
                    window.location.href = '" . $redirect_to . "';
                  </script>";
            exit();

        } else {
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