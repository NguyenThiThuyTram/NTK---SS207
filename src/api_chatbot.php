<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Anh/chị muốn hỏi gì ạ?']);
    exit;
}

require_once __DIR__ . '/api_key.php'; 
$apiKey = $GEMINI_API_KEY; 

$systemPrompt = "Bạn là 'Nhân viên tư vấn AI' chuyên nghiệp của shop thời trang NTK.
QUY TẮC GIAO TIẾP:
- Luôn xưng là 'em' và gọi khách hàng là 'anh/chị'. Lịch sự, tận tâm, chuyên nghiệp, duyên dáng.
- Nhiệm vụ: Tư vấn size, báo giá, kiểm tra hàng và gợi ý sản phẩm.

BẮT BUỘC TRẢ VỀ ĐÚNG ĐỊNH DẠNG JSON SAU (Tuyệt đối không có ký tự ngoài JSON):
{
    \"action\": \"chat\" hoặc \"search\" hoặc \"suggest\" hoặc \"policy\",
    \"keyword\": \"tên sản phẩm nếu có (để trống nếu không)\",
    \"size\": \"S, M, L, XL... nếu có (để trống nếu không)\",
    \"price_max\": số_tiền_tối_đa_nếu_khách_hỏi_giá (mặc định 0),
    \"reply_text\": \"Câu trả lời thân thiện của em\"
}

QUY TẮC CHỌN 'action' VÀ CÁCH NÓI CHUYỆN (RẤT QUAN TRỌNG):
1. KHÁCH CHO CHIỀU CAO, CÂN NẶNG: Tự suy luận size chuẩn (S, M, L, XL), chốt action là \"search\", điền size vào biến \"size\". ĐẶC BIỆT phần 'reply_text' phải có câu dẫn dắt mời khách xem đồ, ví dụ: 'Dạ với vóc dáng của mình, anh/chị mặc size M là vừa đẹp và tôn dáng luôn ạ. Em gửi anh/chị xem thử một số mẫu size M đang sẵn hàng cực xinh bên em nhé:'. (Tuyệt đối KHÔNG hỏi lại khách muốn mua gì nữa vì hàng sẽ được hiển thị ngay bên dưới).
2. TÌM ĐỒ CỤ THỂ HOẶC THEO TIÊU CHÍ (Tìm tên, tìm giá): action: \"search\". 'reply_text' dẫn dắt: 'Dạ em tìm được một số mẫu ưng ý theo yêu cầu của anh/chị đây ạ, mình xem thử nha:'.
3. HỎI CHUNG CHUNG / GỢI Ý: action: \"suggest\". 'reply_text': 'Dạ, shop đang có nhiều mẫu xinh lắm, em gửi anh/chị xem thử vài mẫu đang BÁN CHẠY NHẤT bên em nha:'.
4. CHÀO HỎI / TRÒ CHUYỆN: Khách chào, cảm ơn -> action: \"chat\". 'reply_text' nói chuyện bình thường.
5. HỎI CHÍNH SÁCH: Khách hỏi ship, đổi trả -> action: \"policy\".";

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
// Thay đổi dòng URL thành phiên bản 1.5 flash chuẩn xác nhất
//$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key=' . $apiKey;

$payload = [
    "system_instruction" => ["parts" => [["text" => $systemPrompt]]],
    "generationConfig" => ["responseMimeType" => "application/json"], 
    "contents" => [["role" => "user", "parts" => [["text" => $userMessage]]]]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 

$response = curl_exec($ch);
if(curl_errno($ch)) { 
    echo json_encode(['reply' => 'Lỗi kết nối máy chủ cURL: ' . curl_error($ch)]); 
    curl_close($ch); 
    exit; 
}
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $jsonString = $result['candidates'][0]['content']['parts'][0]['text'];
    $aiData = json_decode($jsonString, true);
    
    if (!$aiData) {
         echo json_encode(['reply' => 'Dạ AI đang xử lý bị nhầm lẫn chút, đại ca thử hỏi lại nhé!']);
         exit;
    }

    $action = strtolower(trim($aiData['action'] ?? 'chat'));
    $botReply = $aiData['reply_text'] ?? 'Dạ em nghe ạ.';
    $keyword = $aiData['keyword'] ?? '';

    // =================================================================
    // ⚙️ BẪY KÉP CHỐNG AI LƯỜI (Rất Quan Trọng)
    // Nếu AI gán nhầm action, ta sẽ tự ép lại dựa vào câu nói của nó
    // =================================================================
    if (strpos(mb_strtoupper($botReply, 'UTF-8'), 'BÁN CHẠY NHẤT') !== false) {
        $action = 'suggest';
    }

    // GỌI KẾT NỐI DATABASE
    $keyword = trim($aiData['keyword'] ?? '');
    $size = strtoupper(trim($aiData['size'] ?? ''));
    $price_max = $aiData['price_max'] ?? 0;

    require_once __DIR__ . '/config/database.php';

    if ($action === 'search' || $action === 'suggest') {
        try {
            // --- CẤU TRÚC TRUY VẤN CHÍNH ---
            $sql_base = "SELECT p.*, v.original_price, v.sale_price,
                           GROUP_CONCAT(DISTINCT v.size SEPARATOR ', ') as available_sizes,
                           SUM(v.stock) as total_stock
                    FROM products p 
                    LEFT JOIN product_variants v ON p.product_id = v.product_id 
                    WHERE p.status = 1";
            
            $where = "";
            $params = [];

            if ($action === 'search') {
                if (!empty($keyword)) {
                    $where .= " AND p.name LIKE :keyword";
                    $params['keyword'] = '%' . $keyword . '%';
                }
                if (!empty($size)) {
                    // Lọc cực kỳ nghiêm ngặt: Chỉ lấy sản phẩm mà chính cái size đó còn hàng
                    $where .= " AND p.product_id IN (SELECT product_id FROM product_variants WHERE size = :size AND stock > 0)";
                    $params['size'] = $size;
                }
                if ($price_max > 0) {
                    $where .= " AND v.original_price <= :price_max";
                    $params['price_max'] = $price_max;
                }
            }

            $sql = $sql_base . $where . " GROUP BY p.product_id";
            if ($action === 'suggest') $sql .= " ORDER BY p.sold_count DESC";
            $sql .= " LIMIT 3";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // --- LOGIC XỬ LÝ KHI KHÔNG CÓ KẾT QUẢ ĐÚNG YÊU CẦU ---
            // --- FALLBACK: Luôn trả về Quần (CAT04) nếu không tìm thấy sản phẩm ---
            if (count($products) == 0) {
                
                // FALLBACK MECHANISM: Bất cứ khách hỏi gì cũng trả về danh mục Quần
                $botReply = "Dạ, cảm ơn anh/chị đã yêu thích shop em! Hiện tại em muốn gợi ý cho anh/chị một số mẫu quần cực xinh đang hot nhất bên em. Anh/chị xem thử nhé:";

                // Truy vấn: Lấy sản phẩm Quần (CAT04) - bán chạy nhất
                $sql_fallback = "SELECT p.*, v.original_price, v.sale_price,
                                GROUP_CONCAT(DISTINCT v.size SEPARATOR ', ') as available_sizes,
                                SUM(v.stock) as total_stock
                        FROM products p 
                        LEFT JOIN product_variants v ON p.product_id = v.product_id 
                        WHERE p.status = 1 AND p.category_id = 'CAT04'
                        GROUP BY p.product_id 
                        ORDER BY p.sold_count DESC 
                        LIMIT 3";
                $stmt_fallback = $conn->prepare($sql_fallback);
                $stmt_fallback->execute();
                $products = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
            }

            // --- HIỂN THỊ KẾT QUẢ ---
            if (count($products) > 0) {
                $botReply .= "<div style='display:flex; flex-direction:column; gap:12px; margin-top:10px;'>";
                foreach ($products as $row) {
                    $price = number_format($row['original_price'], 0, ',', '.') . 'đ';
                    $sizes = !empty($row['available_sizes']) ? $row['available_sizes'] : 'Freesize';
                    
                    $stockStatus = ($row['total_stock'] > 0) 
                        ? "<span style='color:#27ae60;'>● Còn hàng</span>" 
                        : "<span style='color:#e74c3c;'>○ Tạm hết hàng</span>";

                    $botReply .= "
                    <a href='product_detail.php?id={$row['product_id']}' style='display:flex; text-decoration:none; color:#333; background:#fff; padding:10px; border-radius:10px; border:1px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
                        <img src='{$row['image']}' style='width:70px; height:70px; object-fit:cover; border-radius:6px; margin-right:12px;'>
                        <div style='flex:1;'>
                            <div style='font-size:14px; font-weight:bold; margin-bottom:4px;'>{$row['name']}</div>
                            <div style='color:#d35400; font-size:13px; font-weight:bold;'>Giá: $price</div>
                            <div style='color:#666; font-size:12px; margin: 2px 0;'>Size: $sizes</div>
                            <div style='font-size:11px; font-weight:500;'>$stockStatus</div>
                        </div>
                    </a>";
                }
                $botReply .= "</div>";
            }
        } catch (PDOException $e) {
            $botReply .= "<br><i>Lỗi hệ thống, anh/chị vui lòng thử lại sau ạ.</i>";
        }
    }

    echo json_encode(['reply' => $botReply]);

} else {
    // Ép in ra toàn bộ nội dung thật sự mà Google gửi về để xem lỗi gì
    $chi_tiet_loi = json_encode($result, JSON_UNESCAPED_UNICODE);
    echo json_encode(['reply' => 'Lỗi từ Google: ' . $chi_tiet_loi]);
}
?>
