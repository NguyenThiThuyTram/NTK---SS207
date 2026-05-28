<?php
/**
 * NTK Fashion - Tự động sửa quyền ghi cho thư mục config/
 * Bạn chỉ cần upload file này lên web online và truy cập: https://[ten-mien]/src/fix_config_permission.php
 */
echo "<h3>🔧 Đang kiểm tra và tự động sửa quyền ghi...</h3>";

$configDir = __DIR__ . '/config';

if (!is_dir($configDir)) {
    // Nếu thư mục config chưa có, tiến hành tạo mới với quyền 0777
    if (@mkdir($configDir, 0777, true)) {
        echo "✅ Đã tự động tạo mới thư mục config/ với quyền ghi thành công!<br>";
    } else {
        echo "❌ Thư mục config/ chưa tồn tại và PHP không đủ quyền tự tạo mới.<br>";
    }
}

if (is_dir($configDir)) {
    // Thử dùng hàm chmod của PHP để cấp quyền 0777
    if (@chmod($configDir, 0777)) {
        echo "✅ <b>Thành công!</b> Đã cấp quyền ghi (chmod 0777) cho thư mục: <code>src/config/</code><br>";
        
        // Thử viết thử 1 file test xem có ghi được thật không
        $testFile = $configDir . '/permission_test.txt';
        if (@file_put_contents($testFile, 'OK') !== false) {
            echo "✅ <b>Kiểm tra thực tế:</b> Thư mục đã ghi được file thành công!<br>";
            @unlink($testFile);
        } else {
            echo "⚠️ <b>Cảnh báo:</b> Đã chạy lệnh chmod nhưng web server vẫn báo chưa ghi được file.<br>";
        }
    } else {
        echo "❌ Không thể tự động thay đổi quyền qua PHP.<br>";
        echo "💡 <b>Cách xử lý thủ công:</b><br>";
        echo "- <b>Cách 1:</b> Mở terminal SSH trên server của bạn, gõ lệnh: <code>sudo chmod -R 777 src/config</code><br>";
        echo "- <b>Cách 2:</b> Dùng FTP/cPanel File Manager, click chuột phải vào thư mục <code>src/config/</code> -> Chọn <b>Permissions/Chỉnh sửa quyền</b> -> Nhập <code>777</code> hoặc tích chọn hết các ô Write.<br>";
    }
}
?>
