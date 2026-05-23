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

// Prompt system instruction to output JSON
$promptText = "Bạn là trợ lý AI chuyên nghiệp phân tích thời trang cho shop NTK Fashion. 
Hãy phân tích hình ảnh này và cho biết loại sản phẩm thời trang gì (ví dụ: áo thun, áo sơ mi, đầm hoa, quần tây, chân váy...). 
Hãy chọn ra 1 đến 2 từ khóa tìm kiếm (bằng tiếng Việt) tốt nhất để tìm kiếm sản phẩm này hoặc các sản phẩm tương tự trong kho hàng của cửa hàng (ví dụ: 'áo thun trắng', 'quần tây đen', 'đầm hoa'). 

BẮT BUỘC TRẢ VỀ ĐÚNG ĐỊNH DẠNG JSON SAU (Tuyệt đối không kèm ký tự ngoài JSON):
{
  \"keyword\": \"từ khóa chính để tìm kiếm bằng tiếng Việt, viết thường không dấu hoặc có dấu\",
  \"description\": \"mô tả ngắn gọn 1 câu về kiểu dáng/màu sắc/đặc điểm nhận diện của sản phẩm trong ảnh\"
}";

$payload = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => $promptText
                ],
                [
                    "inlineData" => [
                        "mimeType" => $imageType,
                        "data" => $imageData
                    ]
                ]
            ]
        ]
    ],
    "generationConfig" => [
        "responseMimeType" => "application/json"
    ]
];

// cURL Request to Gemini
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối AI: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$result = json_decode($response, true);

if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    file_put_contents(__DIR__ . '/gemini_error_log.txt', date('Y-m-d H:i:s') . ' - Image Search Error: ' . $response . PHP_EOL, FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'AI không phản hồi. Lỗi: ' . ($result['error']['message'] ?? 'Phản hồi không hợp lệ')]);
    exit;
}

$jsonString = $result['candidates'][0]['content']['parts'][0]['text'];
$aiData = json_decode($jsonString, true);

if (!$aiData) {
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý định dạng AI']);
    exit;
}

$keyword = trim($aiData['keyword'] ?? '');
$description = trim($aiData['description'] ?? '');

// Connect Database and query
require_once __DIR__ . '/config/database.php';

$products = [];
$is_fallback = false;

if (!empty($keyword)) {
    try {
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

        // Fallback 1: Truncate keyword to see if we can find broader matches
        if (empty($products)) {
            $words = explode(' ', $keyword);
            $shorterKeyword = count($words) > 1 ? $words[0] . ' ' . $words[1] : $keyword;
            
            $stmt = $conn->prepare("
                SELECT p.product_id, p.name, p.image, MIN(v.sale_price) as sale_price, MIN(v.original_price) as original_price
                FROM products p
                LEFT JOIN product_variants v ON p.product_id = v.product_id
                WHERE p.status = 1 AND (p.name LIKE :keyword OR p.description LIKE :keyword)
                GROUP BY p.product_id
                LIMIT 4
            ");
            $stmt->execute(['keyword' => '%' . $shorterKeyword . '%']);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Fallback 2: Show best-selling products as recommendations
        if (empty($products)) {
            $is_fallback = true;
            $stmt = $conn->prepare("
                SELECT p.product_id, p.name, p.image, MIN(v.sale_price) as sale_price, MIN(v.original_price) as original_price
                FROM products p
                LEFT JOIN product_variants v ON p.product_id = v.product_id
                WHERE p.status = 1
                GROUP BY p.product_id
                ORDER BY p.sold_count DESC
                LIMIT 4
            ");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()]);
        exit;
    }
}

// Return JSON output
$output = [
    'success' => true,
    'keyword' => $keyword,
    'description' => $description,
    'is_fallback' => $is_fallback,
    'products' => $products
];

if (is_dir($cacheDir) && is_writable($cacheDir)) {
    @file_put_contents($cacheFile, json_encode($output));
}

echo json_encode($output);
?>
