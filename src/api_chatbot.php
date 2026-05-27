<?php
header('Content-Type: application/json');

// Đọc dữ liệu gửi lên từ giao diện chat
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? $_POST['message'] ?? $_GET['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Anh/chị muốn hỏi gì ạ?']);
    exit;
}

// GỌI KẾT NỐI DATABASE ĐỂ LẤY SẢN PHẨM TRỰC TIẾP
require_once __DIR__ . '/config/database.php';

try {
    // 1. Lời thoại chào đón lịch sự mặc định giống như Shopee
    $botReply = "Dạ chào anh/chị! Em là nhân viên tư vấn của shop NTK Fashion ạ. Hiện tại hệ thống tư vấn tự động đang được bảo trì nâng cấp để phục vụ mình tốt hơn. 
    
    Sau đây, em xin phép gợi ý cho mình một số mẫu **Áo phong cách dễ thương với mức giá ưu đãi dưới 400.000đ** đang Sẵn Hàng và được săn đón nhiều nhất tại shop, anh/chị xem thử có ưng ý không nha:";

    // 2. Câu truy vấn SQL quét nghiêm ngặt: Lấy các loại Áo (áo thun, sơ mi, babytee, áo kiểu...) dưới 400k và KHÔNG LẤY MẪU 0 ĐỒNG
    $sql_fallback = "SELECT p.*, MIN(v.original_price) as original_price, MIN(v.sale_price) as sale_price,
                            GROUP_CONCAT(DISTINCT v.size SEPARATOR ', ') as available_sizes,
                            SUM(v.stock) as total_stock
                    FROM products p 
                    LEFT JOIN product_variants v ON p.product_id = v.product_id 
                    WHERE p.status = 1 
                      AND (p.name LIKE '%áo%' OR p.description LIKE '%áo%' OR p.name LIKE '%babytee%')
                      AND (v.original_price > 0) -- BẮT BUỘC: Loại bỏ hoàn toàn biến thể có giá gốc bằng 0đ
                      AND (COALESCE(NULLIF(v.sale_price, 0), v.original_price) < 400000) -- Giá bán thực tế dưới 400k
                    GROUP BY p.product_id 
                    ORDER BY p.sold_count DESC 
                    LIMIT 3";
                    
    $stmt_fallback = $conn->prepare($sql_fallback);
    $stmt_fallback->execute();
    $products = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);

    // 3. Đổ danh sách sản phẩm thành khung thẻ HTML gửi về bong bóng chat
    if (count($products) > 0) {
        $botReply .= "<div style='display:flex; flex-direction:column; gap:12px; margin-top:10px;'>";
        foreach ($products as $row) {
            // Lấy giá bán thực tế để hiển thị (Ưu tiên lấy giá sale nếu có)
            $real_price = ($row['sale_price'] > 0 && $row['sale_price'] < $row['original_price']) ? $row['sale_price'] : $row['original_price'];
            $price_text = number_format($real_price, 0, ',', '.') . 'đ';
            $sizes = !empty($row['available_sizes']) ? $row['available_sizes'] : 'Freesize';
            
            $stockStatus = ($row['total_stock'] > 0) 
                ? "<span style='color:#27ae60;'>● Còn hàng</span>" 
                : "<span style='color:#e74c3c;'>○ Tạm hết hàng</span>";

            $botReply .= "
            <a href='product_detail.php?id={$row['product_id']}' style='display:flex; text-decoration:none; color:#333; background:#fff; padding:10px; border-radius:10px; border:1px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
                <img src='{$row['image']}' style='width:70px; height:70px; object-fit:cover; border-radius:6px; margin-right:12px;'>
                <div style='flex:1;'>
                    <div style='font-size:14px; font-weight:bold; margin-bottom:4px; color:#111;'>{$row['name']}</div>
                    <div style='color:#d35400; font-size:13px; font-weight:bold;'>Giá: $price_text</div>
                    <div style='color:#666; font-size:12px; margin: 2px 0;'>Size: $sizes</div>
                    <div style='font-size:11px; font-weight:500;'>$stockStatus</div>
                </div>
            </a>";
        }
        $botReply .= "</div>";
    } else {
        // Trường hợp dự phòng khẩn cấp: Lấy 3 sản phẩm bất kỳ bán chạy nhưng cũng phải chặn giá 0đ
        $stmt_backup = $conn->query("SELECT p.*, MIN(v.original_price) as original_price, MIN(v.sale_price) as sale_price, GROUP_CONCAT(DISTINCT v.size SEPARATOR ', ') as available_sizes, SUM(v.stock) as total_stock FROM products p LEFT JOIN product_variants v ON p.product_id = v.product_id WHERE p.status = 1 AND v.original_price > 0 GROUP BY p.product_id ORDER BY p.sold_count DESC LIMIT 3");
        $backup_products = $stmt_backup->fetchAll(PDO::FETCH_ASSOC);
        
        $botReply .= "<div style='display:flex; flex-direction:column; gap:12px; margin-top:10px;'>";
        foreach ($backup_products as $row) {
            $real_price = ($row['sale_price'] > 0 && $row['sale_price'] < $row['original_price']) ? $row['sale_price'] : $row['original_price'];
            $price_text = number_format($real_price, 0, ',', '.') . 'đ';
            $botReply .= "
            <a href='product_detail.php?id={$row['product_id']}' style='display:flex; text-decoration:none; color:#333; background:#fff; padding:10px; border-radius:10px; border:1px solid #eee;'>
                <img src='{$row['image']}' style='width:70px; height:70px; object-fit:cover; border-radius:6px; margin-right:12px;'>
                <div style='flex:1;'>
                    <div style='font-size:14px; font-weight:bold; margin-bottom:4px; color:#111;'>{$row['name']}</div>
                    <div style='color:#d35400; font-size:13px; font-weight:bold;'>Giá: $price_text</div>
                </div>
            </a>";
        }
        $botReply .= "</div>";
    }

} catch (PDOException $e) {
    $botReply = "Dạ em chào anh/chị, hiện tại hệ thống phản hồi đang bận, anh/chị vui lòng thử lại sau giây lát ạ!";
}

// Trả kết quả JSON về ngay lập tức cho giao diện hiển thị hiển thị
echo json_encode(['reply' => $botReply]);
?>