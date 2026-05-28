<?php
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

// Check uploaded file
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Không nhận được hình ảnh hoặc tệp lỗi']);
    exit;
}

// Check file size (max 5MB)
if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Tệp ảnh quá lớn (tối đa 5MB)']);
    exit;
}

// Check mime type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$imageType = $_FILES['image']['type'];
if (!in_array($imageType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Định dạng hình ảnh không được hỗ trợ (chỉ nhận JPG, PNG, GIF, WEBP)']);
    exit;
}

// Read image and encode to base64
$tmpPath = $_FILES['image']['tmp_name'];

// Result Caching by MD5 of image
$imageHash = md5_file($tmpPath);
$cacheDir = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/' . $imageHash . '.json';

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
    header('X-Cache: HIT');
    echo file_get_contents($cacheFile);
    exit;
}

$imageData = base64_encode(file_get_contents($tmpPath));

// API Key (load from api_key.php)
require_once __DIR__ . '/api_key.php';
$apiKey = $GEMINI_API_KEY;
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

// Primary Prompt - detailed analysis
$promptText = "Bạn là trợ lý AI chuyên nghiệp phân tích thời trang cho shop NTK Fashion. 
Hãy phân tích hình ảnh này và cho biết loại sản phẩm thời trang gì (ví dụ: áo thun, áo sơ mi, đầm hoa, quần tây, chân váy...). 
Hãy chọn ra 1 đến 2 từ khóa tìm kiếm (bằng tiếng Việt) tốt nhất để tìm kiếm sản phẩm này hoặc các sản phẩm tương tự trong kho hàng của cửa hàng (ví dụ: 'áo thun trắng', 'quần tây đen', 'đầm hoa'). 

BẮT BUỘC TRẢ VỀ ĐÚNG ĐỊNH DẠNG JSON SAU (Tuyệt đối không kèm ký tự ngoài JSON):
{
  \"keyword\": \"từ khóa chính để tìm kiếm bằng tiếng Việt, viết thường không dấu hoặc có dấu\",
  \"description\": \"mô tả ngắn gọn 1 câu về kiểu dáng/màu sắc/đặc điểm nhận diện của sản phẩm trong ảnh\"
}";

// Fallback Prompt - simple category detection
$fallbackPrompt = "Hãy xác định loại sản phẩm thời trang trong ảnh này bằng 1 đến 2 từ khóa đơn giản (ví dụ: áo thun, quần, đầm, v.v.). 

Trả về JSON:
{
  \"keyword\": \"tên loại sản phẩm bằng tiếng Việt\"
}";

// Function to call Gemini API
function callGeminiAPI($url, $payload, $timeout = 10) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $errorCode = curl_errno($ch);
    $errorMsg = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'error_code' => $errorCode,
        'error_msg' => $errorMsg
    ];
}

// ────── CONFIGURATION ──────
$PRIMARY_TIMEOUT = 5;    // Phân tích chi tiết (5 giây)
$FALLBACK_TIMEOUT = 3;   // Phân tích nhanh (3 giây)
$DEFAULT_FALLBACK_KEYWORD = 'áo thun'; // Ép cứng danh mục mặc định ở đây

// ────── TRY PRIMARY ANALYSIS (5 seconds) ──────
$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $promptText],
                ["inlineData" => [
                    "mimeType" => $imageType,
                    "data" => $imageData
                ]]
            ]
        ]
    ],
    "generationConfig" => ["responseMimeType" => "application/json"]
];

$result1 = callGeminiAPI($url, $payload, $PRIMARY_TIMEOUT);
$aiData = ['keyword' => '', 'description' => ''];
$fallback_used = false;
$ai_success = false;

if (!$result1['error_code']) {
    $result = json_decode($result1['response'], true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $jsonString = $result['candidates'][0]['content']['parts'][0]['text'];
        $aiData = json_decode($jsonString, true);
        if ($aiData && !empty($aiData['keyword'])) {
            $ai_success = true;
            file_put_contents(__DIR__ . '/gemini_error_log.txt', date('Y-m-d H:i:s') . ' - Primary Analysis Success: ' . $aiData['keyword'] . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/gemini_error_log.txt', date('Y-m-d H:i:s') . ' - Primary Analysis Empty: ' . $result1['response'] . PHP_EOL, FILE_APPEND);
            $fallback_used = true;
        }
    } else {
        file_put_contents(__DIR__ . '/gemini_error_log.txt', date('Y-m-d H:i:s') . ' - Primary Parse Failed: ' . $result1['response'] . PHP_EOL, FILE_APPEND);
        $fallback_used = true;
    }
} else {
    file_put_contents(__DIR__ . '/gemini_error_log.txt', date('Y-m-d H:i:s') . ' - Primary Timeout/Error (Code ' . $result1['error_code'] . '): ' . $result1['error_msg'] . ' - Triggering fallback' . PHP_EOL, FILE_APPEND);
    $fallback_used = true;
}

// ────── IF PRIMARY FAILED, TRY AWS REKOGNITION (Smart Fallback) ──────
if ($fallback_used && !$ai_success) {
    require_once __DIR__ . '/includes/aws_rekognition.php';
    $rekognition = new AwsRekognition();
    
    if ($rekognition->isConfigured()) {
        $rawBytes = base64_decode($imageData);
        $awsResult = $rekognition->detectLabels($rawBytes, 10, 70.0);
        
        if (!isset($awsResult['error']) && isset($awsResult['Labels'])) {
            $awsToVn = [
                'T-Shirt' => 'áo thun', 'Shirt' => 'áo', 'Dress' => 'đầm',
                'Pants' => 'quần', 'Skirt' => 'chân váy', 'Jacket' => 'áo khoác',
                'Sweater' => 'áo len', 'Shorts' => 'quần short', 'Suit' => 'vest',
                'Hat' => 'mũ', 'Shoes' => 'giày'
            ];
            
            $colorToVn = [
                'Red' => 'đỏ', 'Blue' => 'xanh dương', 'Black' => 'đen',
                'White' => 'trắng', 'Green' => 'xanh lá', 'Yellow' => 'vàng',
                'Pink' => 'hồng', 'Purple' => 'tím', 'Brown' => 'nâu'
            ];
            
            $foundItem = '';
            $foundColor = '';
            
            foreach ($awsResult['Labels'] as $label) {
                $name = $label['Name'];
                if (empty($foundItem) && isset($awsToVn[$name])) {
                    $foundItem = $awsToVn[$name];
                }
                if (empty($foundColor) && isset($colorToVn[$name])) {
                    $foundColor = $colorToVn[$name];
                }
            }
            
            if (!empty($foundItem)) {
                $fallbackKeyword = $foundItem . ($foundColor ? ' ' . $foundColor : '');
                $aiData = [
                    'keyword' => $fallbackKeyword,
                    'description' => 'AWS AI nhận diện: ' . ucfirst($fallbackKeyword)
                ];
                $ai_success = true;
                file_put_contents(__DIR__ . '/gemini_error_log.txt', date('Y-m-d H:i:s') . ' - AWS Analysis Success: ' . $fallbackKeyword . PHP_EOL, FILE_APPEND);
            }
        }
    }
    
    // Nếu AWS cũng thất bại hoặc không cấu hình, dùng mặc định
    if (!$ai_success) {
        file_put_contents(__DIR__ . '/gemini_error_log.txt', date('Y-m-d H:i:s') . ' - AWS Fallback Failed, using DEFAULT: ' . $DEFAULT_FALLBACK_KEYWORD . PHP_EOL, FILE_APPEND);
        $aiData = ['keyword' => $DEFAULT_FALLBACK_KEYWORD, 'description' => 'Sử dụng danh mục mặc định do AI không nhận diện được'];
    }
}

$keyword = trim($aiData['keyword'] ?? '');
$description = trim($aiData['description'] ?? '');

// Connect Database and query
require_once __DIR__ . '/config/database.php';

$products = [];
$is_fallback = false;

try {
    // 1. TÌM KIẾM THEO TỪ KHÓA AI TRẢ VỀ
    if (!empty($keyword)) {
        $stmt = $conn->prepare("
            SELECT p.product_id, p.name, p.image, MIN(v.sale_price) as sale_price, MIN(v.original_price) as original_price
            FROM products p
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            WHERE p.status = 1 AND (p.name LIKE :keyword OR p.description LIKE :keyword)
            GROUP BY p.product_id
            LIMIT 4
        ");
        $stmt->execute(['keyword' => '%' . $keyword . '%']);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Thử tìm kiếm thu gọn từ khóa nếu từ khóa dài không ra kết quả
        if (empty($products)) {
            $words = preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY);
            if (count($words) > 1) {
                $shorterKeyword = $words[0] . ' ' . $words[1];
                $stmt->execute(['keyword' => '%' . $shorterKeyword . '%']);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }

    // 2. PHƯƠNG ÁN FALLBACK ÉP BUỘC: Nếu không tìm thấy sản phẩm nào, tự động lấy danh mục Áo Thun
    if (empty($products)) {
        $is_fallback = true;
        $keyword = $DEFAULT_FALLBACK_KEYWORD; // Gán lại từ khóa hiển thị ra giao diện là "áo thun"
        $description = 'Hệ thống tự động gợi ý các mẫu Áo thun mới nhất cho bạn.';
        
        $stmt = $conn->prepare("
            SELECT p.product_id, p.name, p.image, MIN(v.sale_price) as sale_price, MIN(v.original_price) as original_price
            FROM products p
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            WHERE p.status = 1 AND (p.name LIKE :fallback_keyword OR p.description LIKE :fallback_keyword)
            GROUP BY p.product_id
            ORDER BY p.product_id DESC
            LIMIT 4
        ");
        $stmt->execute(['fallback_keyword' => '%' . $DEFAULT_FALLBACK_KEYWORD . '%']);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Trường hợp bất khả kháng nếu trong DB của bạn không có chữ "áo thun" nào, lấy đại 4 sản phẩm mới nhất
        if (empty($products)) {
            $stmt_backup = $conn->prepare("
                SELECT p.product_id, p.name, p.image, MIN(v.sale_price) as sale_price, MIN(v.original_price) as original_price
                FROM products p
                LEFT JOIN product_variants v ON p.product_id = v.product_id
                WHERE p.status = 1
                GROUP BY p.product_id
                ORDER BY p.product_id DESC
                LIMIT 4
            ");
            $stmt_backup->execute();
            $products = $stmt_backup->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    $is_fallback = true;
    $products = [];
}

// Return JSON output
$output = [
    'success' => true,
    'keyword' => $keyword,
    'description' => $description,
    'ai_used_fallback' => $fallback_used, 
    'is_fallback' => $is_fallback,         
    'products' => $products
];

// Chỉ lưu Cache nếu AI nhận diện thành công (Không lưu nếu bị ép dùng Fallback mặc định do lỗi)
if (!$is_fallback && is_dir($cacheDir) && is_writable($cacheDir)) {
    @file_put_contents($cacheFile, json_encode($output));
}

echo json_encode($output);
?>