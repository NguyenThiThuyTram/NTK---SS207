<?php
// Bật session lên để biết đang thằng nào đang đăng nhập
session_start();

// Xóa toàn bộ các biến trong session (như user_id, username...)
session_unset();

// Hủy diệt hoàn toàn phiên làm việc
session_destroy();

// Đá văng về trang đăng nhập (hoặc trang chủ index.php tùy đại ca)
header("Location: login.php");
exit();
?>