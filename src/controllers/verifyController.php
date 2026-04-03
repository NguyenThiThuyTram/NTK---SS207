<?php
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    // Gộp 4 số OTP rời rạc thành 1 chuỗi (VD: "1", "2", "3", "4" thành "1234")
    $otp_array = $_POST['otp'];
    $entered_otp = implode('', $otp_array);

    try {
        // 1. Kiểm tra xem Email và Mã OTP có khớp nhau trong database không?
        $sql = "SELECT * FROM Users WHERE email = :email AND verification_code = :code";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':code', $entered_otp);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // 2. Nếu ĐÚNG: Cập nhật is_verified = 1 và xóa mã OTP đi (để không dùng lại được)
            $update_sql = "UPDATE Users SET is_verified = 1, verification_code = NULL WHERE email = :email";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':email', $email);
            $update_stmt->execute();

            echo "<script>
                    alert('Xác thực tài khoản thành công! Bây giờ bạn có thể đăng nhập.');
                    window.location.href = '../views/login.php';
                  </script>";
        } else {
            // 3. Nếu SAI: Báo lỗi và bắt nhập lại
            echo "<script>
                    alert('Mã xác nhận không chính xác! Vui lòng kiểm tra lại Email.');
                    window.history.back();
                  </script>";
        }
    } catch(PDOException $e) {
        echo "Lỗi truy vấn: " . $e->getMessage();
    }
}
?>