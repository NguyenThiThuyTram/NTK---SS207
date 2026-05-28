<?php
$dirs = [
    __DIR__ . '/src/assets/uploads',
    __DIR__ . '/src/assets/uploads/reviews',
    __DIR__ . '/src/assets/uploads/returns',
    __DIR__ . '/src/assets/images/products'
];

echo "<h3>Đang cấp quyền ghi (CHMOD 777) cho các thư mục upload...</h3>";
echo "<ul>";
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0777, true)) {
            echo "<li>Đã TẠO và cấp quyền: $dir</li>";
        } else {
            echo "<li style='color:red;'>KHÔNG THỂ tạo thư mục: $dir (Bạn cần tạo thủ công)</li>";
        }
    } else {
        if (@chmod($dir, 0777)) {
            echo "<li>Đã CẤP QUYỀN 777: $dir</li>";
        } else {
            echo "<li style='color:orange;'>Không thể CHMOD tự động: $dir (Hãy cấp quyền ghi thủ công)</li>";
        }
    }
}
echo "</ul>";
echo "<p style='color:green; font-weight:bold;'>Xong! Bây giờ bạn có thể quay lại upload ảnh bình thường.</p>";
echo "<p><em>Ghi chú: Sau khi upload thành công, bạn có thể xóa file này khỏi server để bảo mật.</em></p>";
