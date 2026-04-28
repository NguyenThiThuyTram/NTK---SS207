<?php
session_start();

// Set header trả về dạng JSON
header('Content-Type: application/json');

// Gọi file kết nối database
require_once __DIR__ . '/config/database.php';

// Nhận câu hỏi từ Frontend gửi lên
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';
if (empty($userMessage)) {
    echo json_encode(['reply' => 'Anh/chị muốn hỏi gì ạ?']);
    exit;
}

// ==========================================
// ĐẠI CA DÁN API KEY CỦA GEMINI VÀO ĐÂY NHÉ:
$apiKey = 'AIzaSyBXwQjYyVqnzidwLHyOHA27xcjbtB6hWxI'; 
// ==========================================

// Cấu hình gửi lên server Google Gemini
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

// KỊCH BẢN YÊU CẦU CỦA ĐẠI CA
$systemPrompt = "Bạn là Nhân viên AI chính thức của shop thời trang NTK.
Phong cách: Chuyên nghiệp, lịch sự, tận tâm và ngắn gọn.
Xưng hô: Luôn tự xưng là 'em', gọi khách hàng là 'anh/chị'. 
Tuyệt đối KHÔNG xưng tôi-bạn, KHÔNG nhận mình là người thật (nếu khách hỏi, hãy nói rõ mình là Nhân viên AI của hệ thống).

KIẾN THỨC NỀN TẢNG CỦA SHOP NTK:
1. Tư vấn Size (Không cần gọi lệnh, tự tính toán và trả lời khách dựa trên bảng này):
- Size S: Dưới 1m60, Cân nặng 40kg - 48kg.
- Size M: Từ 1m60 - 1m68, Cân nặng 49kg - 58kg.
- Size L: Từ 1m68 - 1m75, Cân nặng 59kg - 68kg.
- Size XL: Trên 1m75, Cân nặng 69kg - 85kg.
(Nếu số đo của khách nằm giữa 2 size, hãy khuyên khách lấy size lớn hơn để mặc cho thoải mái).

QUY TẮC SỬ DỤNG LỆNH ẨN (RẤT QUAN TRỌNG):
Khi khách hàng có các yêu cầu dưới đây, bạn KHÔNG ĐƯỢC tự bịa ra thông tin mà CHỈ ĐƯỢC PHÉP trả lời bằng đúng 1 dòng chứa cú pháp [LỆNH] tương ứng để hệ thống xử lý:

1. TÌM SẢN PHẨM: Khi khách muốn tìm áo, quần, váy... 
-> Trả lời: [SEARCH: <từ_khóa>] (VD: [SEARCH: áo baby tee])

2. HỎI MÃ GIẢM GIÁ / KHUYẾN MÃI: Khi khách hỏi 'có mã giảm giá không', 'shop có sale không'...
-> Trả lời: [COUPON]

3. TRA CỨU ĐƠN HÀNG: Khi khách muốn kiểm tra đơn hàng, khách cung cấp mã đơn (VD: kiểm tra cho anh đơn #12345).
-> Trả lời: [TRACK_ORDER: <mã_đơn>] (VD: [TRACK_ORDER: 12345])

4. CHÍNH SÁCH ĐỔI TRẢ: Khi khách hỏi về việc đổi trả hàng.
-> Trả lời: [POLICY_RETURN]

5. GẶP NHÂN VIÊN THẬT: Khi khách cáu gắt, hoặc có yêu cầu phức tạp mà AI không xử lý được.
-> Trả lời: [CONTACT_HUMAN]";

// 2. LƯU LỊCH SỬ TRÒ CHUYỆN ĐỂ AI CÓ TRÍ NHỚ (SESSION)
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Đưa câu hỏi mới của khách vào lịch sử
$_SESSION['chat_history'][] = [
    "role" => "user",
    "parts" => [
        ["text" => $userMessage]
    ]
];

// Giới hạn lịch sử để gọi API không bị quá tải (lưu 20 tin nhắn gần nhất)
if (count($_SESSION['chat_history']) > 20) {
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
}

// 3. GÓI DỮ LIỆU GỬI ĐI BAO GỒM LỊCH SỬ
$payload = [
    "system_instruction" => [   
        "parts" => [
            ["text" => $systemPrompt]
        ]
    ],
    "contents" => array_values($_SESSION['chat_history'])
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
// Bỏ qua check SSL trên localhost để không bị lỗi XAMPP
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $botReply = $result['candidates'][0]['content']['parts'][0]['text'];

    // Lưu lại câu trả lời gốc của con AI (dạng tag) vào lịch sử
    $_SESSION['chat_history'][] = [
        "role" => "model",
        "parts" => [
            ["text" => $botReply]
        ]
    ];

    // CHUỖI XỬ LÝ LỆNH ẨN TỪ AI THEO YÊU CẦU CỦA ĐẠI CA (REGEX & DATABASE)
    
    // 1. TÌM SẢN PHẨM: [SEARCH: <từ_khóa>]
    if (preg_match('/\[SEARCH:\s*(.*?)\]/i', $botReply, $matches)) {
        $keyword = trim($matches[1]); 
        
        $stmt = $conn->prepare("
            SELECT p.product_id, p.name, p.image, MIN(v.sale_price) as min_price
            FROM products p
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            WHERE p.name LIKE :keyword OR p.description LIKE :keyword
            GROUP BY p.product_id
            LIMIT 5
        ");
        $stmt->execute(['keyword' => "%$keyword%"]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($products) > 0) {
            $botReply = "Dạ, em đã tìm thấy một số mẫu <b>" . htmlspecialchars($keyword) . "</b> cho anh/chị nè:<br><br>";
            $botReply .= "<div class='product-list-chat' style='display: flex; flex-direction: column; gap: 10px;'>";
            foreach ($products as $row) {
                $priceFormat = number_format($row['min_price'] ?? 0, 0, ',', '.') . "đ";
                $imgUrl = !empty($row['image']) ? htmlspecialchars($row['image']) : 'assets/img/default.jpg';
                $botReply .= "<div class='product-item' style='display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 8px;'>";
                $botReply .= "<img src='" . $imgUrl . "' style='width: 60px; height: 60px; object-fit: cover; border-radius: 4px;'>";
                $botReply .= "<div>";
                $botReply .= "<a href='product_detail.php?id=" . htmlspecialchars($row['product_id']) . "' style='font-weight: bold; text-decoration: none; color: #333;'>" . htmlspecialchars($row['name']) . "</a><br>";
                $botReply .= "<span style='color:red; font-weight: bold;'>" . $priceFormat . "</span>";
                $botReply .= "</div></div>";
            }
            $botReply .= "</div>";
        } else {
            $botReply = "Dạ hiện tại bên em không tìm thấy mẫu <b>" . htmlspecialchars($keyword) . "</b> nào ạ. Anh/chị thử tìm kiếm với từ khóa khác giúp em nha!";
        }
    } 
    // 2. HỎI MÃ GIẢM GIÁ: [COUPON]
    elseif (preg_match('/\[COUPON\]/i', $botReply)) {
        $stmt = $conn->prepare("SELECT code, discount_type, discount_value, min_order_value FROM coupons WHERE status = 1 AND end_date > NOW() LIMIT 5");
        $stmt->execute();
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($coupons) > 0) {
            $botReply = "Dạ hiện shop đang có mã giảm giá cho anh/chị đây ạ:<br><br>";
            $botReply .= "<ul style='padding-left: 20px;'>";
            foreach ($coupons as $coupon) {
                // discount_type: 1 là tiền mặt, 0 là phần trăm
                $discount = $coupon['discount_type'] == 1 
                            ? number_format($coupon['discount_value'], 0, ',', '.') . '₫' 
                            : $coupon['discount_value'] . '%';
                $minOrder = number_format($coupon['min_order_value'], 0, ',', '.');
                $botReply .= "<li>Mã <b>{$coupon['code']}</b>: Giảm {$discount} (Áp dụng cho đơn từ {$minOrder}₫)</li>";
            }
            $botReply .= "</ul>";
        } else {
            $botReply = "Dạ hiện tại shop em đang tạm hết mã giảm giá rồi ạ. Anh/chị theo dõi website để chờ đợt ưu đãi tới nha!";
        }
    }
    // 3. TRA CỨU ĐƠN HÀNG: [TRACK_ORDER: <mã>]
    elseif (preg_match('/\[TRACK_ORDER:\s*(.*?)\]/i', $botReply, $matches)) {
        $orderCode = trim($matches[1]);
        $stmt = $conn->prepare("SELECT order_status, total_price, order_date FROM orders WHERE order_id = :order_id OR payos_order_code = :order_id");
        $stmt->execute(['order_id' => $orderCode]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $statuses = [
                0 => 'Chờ xác nhận',
                1 => 'Đã xác nhận',
                2 => 'Đang giao hàng',
                3 => 'Đã giao tới',
                4 => 'Đã hủy',
                5 => 'Hoàn trả'
            ];
            $statusText = $statuses[$order['order_status']] ?? 'Không xác định';
            $total = number_format($order['total_price'], 0, ',', '.');
            $date = date('d/m/Y H:i', strtotime($order['order_date']));
            
            $botReply = "Dạ em tìm thấy đơn hàng <b>" . htmlspecialchars($orderCode) . "</b> rồi ạ:<br><br>";
            $botReply .= "- Trạng thái: <b><span style='color: green;'>{$statusText}</span></b><br>";
            $botReply .= "- Ngày đặt: {$date}<br>";
            $botReply .= "- Tổng giá trị: {$total}₫<br>";
        } else {
            $botReply = "Dạ em không tìm thấy đơn hàng nào có mã <b>" . htmlspecialchars($orderCode) . "</b> ạ. Anh/chị kiểm tra lại mã giúp em với nhé!";
        }
    }
    // 4. CHÍNH SÁCH ĐỔI TRẢ: [POLICY_RETURN]
    elseif (preg_match('/\[POLICY_RETURN\]/i', $botReply)) {
        $botReply = "Dạ về chính sách đổi trả của bên em ạ:<br><br>";
        $botReply .= "1. Điều kiện: Sản phẩm quần áo còn mới, đầy đủ tem mác, hóa đơn và chưa qua sử dụng/giặt ủi.<br>";
        $botReply .= "2. Thời gian: Trong vòng 7 ngày kể từ khi nhận được hàng.<br>";
        $botReply .= "Anh/chị có thể xem chi tiết hơn tại <a href='return-policy.php' target='_blank' style='color: blue;'>Trang Chính sách Đổi Trả</a> của bọn em nha!";
    }
    // 5. GẶP NHÂN VIÊN THẬT: [CONTACT_HUMAN]
    elseif (preg_match('/\[CONTACT_HUMAN\]/i', $botReply)) {
        $botReply = "Dạ anh/chị đợi một tẹo nhé, em đang xin phép chuyển lời qua các anh chị admin cửa hàng để được hỗ trợ trực tiếp ạ! Hoặc anh/chị có thể gọi hotline: <b>0123-456-789</b> nếu cần gấp ạ.";
    }

    echo json_encode(['reply' => $botReply]);

} else {
    // Ghi log lỗi vào file mảng
    $error_msg = json_encode(['curl_error' => curl_error($ch), 'api_response' => $result]);
    file_put_contents(__DIR__ . '/gemini_error_log.txt', date('Y-m-d H:i:s') . ' - ' . $error_msg . PHP_EOL, FILE_APPEND);
    
    echo json_encode(['reply' => 'Dạ nhân viên AI của NTK đang bận chút xíu, anh/chị đợi xíu nhắn lại nha!']);
}
?>