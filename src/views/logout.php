<?php
// Bật session lên để biết đang thằng nào đang đăng nhập
session_start();

// Xóa toàn bộ các biến trong session (như user_id, username...)
session_unset();

// Hủy diệt hoàn toàn phiên làm việc
session_destroy();

// Đưa về trang chủ dành cho khách
header("Location: ../index.php");
exit();
?>