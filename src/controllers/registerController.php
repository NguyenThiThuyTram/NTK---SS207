<?php
// Gọi đường ống kết nối vào
require_once '../config/database.php';

// Kiểm tra xem người dùng có bấm nút ĐĂNG KÝ (gửi dạng POST) hay không
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Hứng dữ liệu từ form gửi sang
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    // 2. Xử lý các trường bắt buộc của Database
    // Tạo user_id ngẫu nhiên 5 ký tự, ví dụ: U8291
    $user_id = 'U' . rand(1000, 9999); 
    $username = $email; // Lấy email làm tài khoản đăng nhập luôn
    
    // (Lưu ý: Thực tế ngta sẽ băm mật khẩu ra cho an toàn, nhưng DB của bạn đang set password là VARCHAR(50), ta dùng md5 để mã hóa cơ bản cho vừa khít 50 ký tự nhé)
    $hashed_password = md5($password); 

    try {
        // 3. Chuẩn bị câu lệnh SQL đưa vào bảng Users
        $sql = "INSERT INTO Users (user_id, username, password, fullname, email, phonenumber) 
                VALUES (:user_id, :username, :password, :fullname, :email, :phone)";
        
        $stmt = $conn->prepare($sql);
        
        // 4. Nhét dữ liệu thực tế vào câu lệnh
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':fullname', $fullname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        
        // 5. Bấm nút khai hỏa!
        $stmt->execute();
        
        // Thành công thì hiện thông báo và đá về trang đăng nhập
        echo "<script>
                alert('Đăng ký tài khoản thành công! Xin mời đăng nhập.');
                window.location.href = '../views/login.php';
              </script>";
              
    } catch(PDOException $e) {
        // Nếu email hoặc số điện thoại bị trùng, SQL sẽ chửi, mình bắt lỗi ở đây
        echo "<script>
                alert('Lỗi: Email hoặc số điện thoại này đã được sử dụng!');
                window.history.back(); // Quay lại trang trước
              </script>";
    }
}
?>
