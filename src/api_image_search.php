<?php
header('Content-Type: application/json');

// ────── VALIDATE REQUEST ──────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Không nhận được hình ảnh hoặc tệp lỗi']);
    exit;
}
if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Tệp ảnh quá lớn (tối đa 5MB)']);
    exit;
}
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$imageType    = $_FILES['image']['type'];
if (!in_array($imageType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Định dạng hình ảnh không được hỗ trợ']);
    exit;
}

// ────── CACHE ──────
$tmpPath   = $_FILES['image']['tmp_name'];
$imageHash = md5_file($tmpPath);
$cacheDir  = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/' . $imageHash . '.json';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
    header('X-Cache: HIT');
    echo file_get_contents($cacheFile);
    exit;
}

// ══════════════════════════════════════════════════════════════════
// BẢNG MAPPING: AWS Label → category_id của NTK (bắt buộc 10 danh mục)
//
// Logic: Với mỗi label AWS trả về, tra bảng này để biết nó thuộc
// category nào. Mỗi label có thể map sang 1 category. Sau đó cộng
// dồn Confidence score để bầu chọn category cuối cùng.
// ══════════════════════════════════════════════════════════════════
$LABEL_TO_CATEGORY = [
    // ─── CAT01 – Áo thun ───────────────────────────────────────────
    'T-Shirt'        => 'CAT01',
    'Tshirt'         => 'CAT01',
    'Tee'            => 'CAT01',
    'Crop Top'       => 'CAT01',
    'Tank Top'       => 'CAT01',
    'Sleeveless'     => 'CAT01',
    'Short Sleeve'   => 'CAT01',
    'Babytee'        => 'CAT01',
    'Graphic Tee'    => 'CAT01',
    'Long Sleeve'    => 'CAT01',  // áo thun tay dài vẫn là CAT01

    // ─── CAT02 – Áo khoác ──────────────────────────────────────────
    'Jacket'         => 'CAT02',
    'Coat'           => 'CAT02',
    'Windbreaker'    => 'CAT02',
    'Parka'          => 'CAT02',
    'Outerwear'      => 'CAT02',
    'Bomber Jacket'  => 'CAT02',
    'Denim Jacket'   => 'CAT02',
    'Leather Jacket' => 'CAT02',
    'Vest'           => 'CAT02',
    'Gilet'          => 'CAT02',
    'Puffer'         => 'CAT02',
    'Anorak'         => 'CAT02',

    // ─── CAT03 – Hoodie & Sweater ──────────────────────────────────
    'Hoodie'         => 'CAT03',
    'Sweatshirt'     => 'CAT03',
    'Sweater'        => 'CAT03',
    'Pullover'       => 'CAT03',
    'Zip Up'         => 'CAT03',
    'Fleece'         => 'CAT03',
    'Crewneck'       => 'CAT03',
    'Zip Hoodie'     => 'CAT03',

    // ─── CAT04 – Quần (kaki / vải dù / parachute…) ────────────────
    'Pants'          => 'CAT04',
    'Trousers'       => 'CAT04',
    'Cargo Pants'    => 'CAT04',
    'Cargo'          => 'CAT04',
    'Wide Leg'       => 'CAT04',
    'Joggers'        => 'CAT04',
    'Sweatpants'     => 'CAT04',
    'Chinos'         => 'CAT04',
    'Slacks'         => 'CAT04',
    'Palazzo'        => 'CAT04',
    'Parachute Pants'=> 'CAT04',

    // ─── CAT05 – Áo sơ mi ──────────────────────────────────────────
    'Shirt'          => 'CAT05',
    'Blouse'         => 'CAT05',
    'Button Down'    => 'CAT05',
    'Oxford Shirt'   => 'CAT05',
    'Flannel Shirt'  => 'CAT05',
    'Dress Shirt'    => 'CAT05',
    'Button Up'      => 'CAT05',

    // ─── CAT06 – Quần đùi / short ──────────────────────────────────
    'Shorts'         => 'CAT06',
    'Short'          => 'CAT06',
    'Board Shorts'   => 'CAT06',
    'Bermuda'        => 'CAT06',
    'Hot Pants'      => 'CAT06',

    // ─── CAT07 – Áo polo ───────────────────────────────────────────
    'Polo'           => 'CAT07',
    'Polo Shirt'     => 'CAT07',
    'Golf Shirt'     => 'CAT07',

    // ─── CAT08 – Quần jeans ────────────────────────────────────────
    'Jeans'          => 'CAT08',
    'Denim'          => 'CAT08',
    'Jean'           => 'CAT08',
    'Denim Pants'    => 'CAT08',
    'Skinny Jeans'   => 'CAT08',
    'Bootcut'        => 'CAT08',

    // ─── CAT09 – Chân váy ──────────────────────────────────────────
    'Skirt'          => 'CAT09',
    'Mini Skirt'     => 'CAT09',
    'Midi Skirt'     => 'CAT09',
    'Maxi Skirt'     => 'CAT09',
    'Pleated Skirt'  => 'CAT09',
    'A-Line Skirt'   => 'CAT09',
    'Pencil Skirt'   => 'CAT09',
    'Wrap Skirt'     => 'CAT09',

    // ─── CAT10 – Áo len & cardigan ─────────────────────────────────
    'Cardigan'       => 'CAT10',
    'Knitwear'       => 'CAT10',
    'Knit'           => 'CAT10',
    'Knitted'        => 'CAT10',
    'Turtleneck'     => 'CAT10',
    'Ribbed'         => 'CAT10',
    'Woolen'         => 'CAT10',
    'Wool'           => 'CAT10',
    'Merino'         => 'CAT10',
    'Fuzzy'          => 'CAT10',
];

// Tên tiếng Việt của từng category (hiển thị ra frontend)
$CATEGORY_NAMES = [
    'CAT01' => 'áo thun',
    'CAT02' => 'áo khoác',
    'CAT03' => 'hoodie & sweater',
    'CAT04' => 'quần',
    'CAT05' => 'áo sơ mi',
    'CAT06' => 'quần đùi',
    'CAT07' => 'áo polo',
    'CAT08' => 'quần jeans',
    'CAT09' => 'chân váy',
    'CAT10' => 'áo len & cardigan',
];

// Danh mục mặc định (khi AWS không nhận ra bất cứ nhãn thời trang nào)
$DEFAULT_CATEGORY_ID   = 'CAT01';
$DEFAULT_CATEGORY_NAME = 'áo thun';

// ══════════════════════════════════════════════════════════════════
// COLOR MAP: AWS label → màu tiếng Việt (khớp với product_variants.color)
// ══════════════════════════════════════════════════════════════════
$COLOR_MAP = [
    'White'       => 'Trắng',  'Off White'    => 'Trắng',  'Ivory'    => 'Kem',
    'Black'       => 'Đen',
    'Blue'        => 'Xanh',   'Navy Blue'    => 'Xanh Navy', 'Navy' => 'Xanh Navy',
    'Light Blue'  => 'Xanh Nhạt', 'Cyan'     => 'Xanh',    'Teal'    => 'Xanh',
    'Green'       => 'Xanh',
    'Pink'        => 'Hồng',   'Rose'         => 'Hồng',   'Salmon'  => 'Hồng',
    'Yellow'      => 'Vàng',   'Gold'         => 'Vàng',
    'Brown'       => 'Nâu',    'Tan'          => 'Nâu',    'Camel'   => 'Nâu',
    'Gray'        => 'Ghi',    'Grey'         => 'Ghi',    'Silver'  => 'Ghi',
    'Beige'       => 'Kem',    'Cream'        => 'Kem',
    'Purple'      => 'Tím',    'Violet'       => 'Tím',    'Lavender'=> 'Tím',
    'Red'         => 'Đỏ',     'Maroon'       => 'Đỏ',     'Burgundy'=> 'Đỏ',
    'Orange'      => 'Cam',
];

$log_file = __DIR__ . '/image_search_log.txt';

// ══════════════════════════════════════════════════════════════════
// PHÂN TÍCH ẢNH VỚI AWS REKOGNITION
// Chiến lược: Cộng dồn Confidence score cho từng category,
// rồi chọn category có tổng điểm cao nhất.
// ══════════════════════════════════════════════════════════════════
require_once __DIR__ . '/includes/aws_rekognition.php';

$categoryScores = [];   // ['CAT01' => 95.3, 'CAT03' => 72.1, ...]
$foundColor     = '';
$ai_success     = false;
$matchedLabels  = [];   // để log debug

$rekognition = new AwsRekognition();

if ($rekognition->isConfigured()) {
    $rawBytes  = file_get_contents($tmpPath);
    $awsResult = $rekognition->detectLabels($rawBytes, 50, 40.0); // lấy nhiều nhãn hơn, threshold thấp hơn

    if (!isset($awsResult['error']) && isset($awsResult['Labels'])) {
        foreach ($awsResult['Labels'] as $label) {
            $name       = $label['Name'];
            $confidence = (float)($label['Confidence'] ?? 0);

            // Cộng dồn điểm confidence vào category tương ứng
            if (isset($LABEL_TO_CATEGORY[$name])) {
                $catId = $LABEL_TO_CATEGORY[$name];
                $categoryScores[$catId] = ($categoryScores[$catId] ?? 0) + $confidence;
                $matchedLabels[]        = "$name({$catId}:{$confidence})";
            }

            // Lấy màu (first match wins, dùng màu tự nhiên nhất)
            if (empty($foundColor) && isset($COLOR_MAP[$name])) {
                $foundColor = $COLOR_MAP[$name];
            }
        }

        if (!empty($categoryScores)) {
            // Chọn category có tổng confidence cao nhất
            arsort($categoryScores);
            $winnerCatId = array_key_first($categoryScores);
            $ai_success  = true;

            file_put_contents($log_file,
                date('Y-m-d H:i:s') . " - AWS OK | Winner: $winnerCatId ({$CATEGORY_NAMES[$winnerCatId]}) "
                . "Score: {$categoryScores[$winnerCatId]} | Color: $foundColor | "
                . "Labels: " . implode(', ', $matchedLabels) . "\n",
                FILE_APPEND
            );
        } else {
            file_put_contents($log_file,
                date('Y-m-d H:i:s') . " - AWS: No fashion labels matched any NTK category.\n",
                FILE_APPEND
            );
        }
    } else {
        $errDetail = $awsResult['error'] ?? 'Unknown';
        file_put_contents($log_file,
            date('Y-m-d H:i:s') . " - AWS Error: $errDetail\n",
            FILE_APPEND
        );
    }
} else {
    file_put_contents($log_file,
        date('Y-m-d H:i:s') . " - AWS not configured.\n",
        FILE_APPEND
    );
}

// ══════════════════════════════════════════════════════════════════
// ÉP KẾT QUẢ VỀ ĐÚNG DANH MỤC NTK – TUYỆT ĐỐI KHÔNG NGOẠI LỆ
// Nếu AWS không nhận ra nhãn thời trang nào → fallback về CAT01
// (vẫn là 1 trong 10 danh mục của shop, không bao giờ ra ngoài)
// ══════════════════════════════════════════════════════════════════
if ($ai_success) {
    $finalCatId   = $winnerCatId;
    $finalCatName = $CATEGORY_NAMES[$finalCatId];
} else {
    // Fallback: CAT01 – vẫn là danh mục của shop, không ngoại lệ
    $finalCatId   = $DEFAULT_CATEGORY_ID;
    $finalCatName = $DEFAULT_CATEGORY_NAME;
    file_put_contents($log_file,
        date('Y-m-d H:i:s') . " - Fallback to default: $finalCatId\n",
        FILE_APPEND
    );
}

$keyword     = $finalCatName;
$description = 'AI nhận diện: ' . ucfirst($finalCatName) . ($foundColor ? ' màu ' . $foundColor : '');

// ══════════════════════════════════════════════════════════════════
// DATABASE – 3 cấp ưu tiên
// P1: Đúng category_id + variant có màu khớp → giống ảnh nhất, lên đầu
// P2: Đúng category_id (bất kỳ màu)
// P3: 4 sản phẩm mới nhất (tránh giao diện trống)
// ══════════════════════════════════════════════════════════════════
require_once __DIR__ . '/config/database.php';

$products    = [];
$is_fallback = false;
$search_mode = '';

try {
    // ── PRIORITY 1: category_id + màu variant khớp ──────────────
    if (!empty($foundColor)) {
        $stmt = $conn->prepare("
            SELECT
                p.product_id,
                p.name,
                p.image,
                MIN(v.sale_price)     AS sale_price,
                MIN(v.original_price) AS original_price
            FROM products p
            INNER JOIN product_variants v ON p.product_id = v.product_id
            WHERE p.status = 1
              AND p.category_id  = :cat_id
              AND v.color        LIKE :color
              AND v.is_active    = 1
            GROUP BY p.product_id
            ORDER BY p.product_id DESC
            LIMIT 4
        ");
        $stmt->execute([
            'cat_id' => $finalCatId,
            'color'  => '%' . $foundColor . '%',
        ]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($products)) $search_mode = 'category_and_color';
    }

    // ── PRIORITY 2: category_id (bất kỳ màu) ─────────────────────
    if (empty($products)) {
        $stmt = $conn->prepare("
            SELECT
                p.product_id,
                p.name,
                p.image,
                MIN(v.sale_price)     AS sale_price,
                MIN(v.original_price) AS original_price
            FROM products p
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            WHERE p.status = 1
              AND p.category_id = :cat_id
            GROUP BY p.product_id
            ORDER BY p.product_id DESC
            LIMIT 4
        ");
        $stmt->execute(['cat_id' => $finalCatId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($products)) $search_mode = 'category_only';
    }

    // ── PRIORITY 3: Mới nhất toàn shop (backup tuyệt đối) ────────
    if (empty($products)) {
        $is_fallback = true;
        $search_mode = 'newest_fallback';
        $description = 'Hệ thống tự động gợi ý các sản phẩm mới nhất cho bạn.';

        $stmt = $conn->prepare("
            SELECT
                p.product_id,
                p.name,
                p.image,
                MIN(v.sale_price)     AS sale_price,
                MIN(v.original_price) AS original_price
            FROM products p
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            WHERE p.status = 1
            GROUP BY p.product_id
            ORDER BY p.product_id DESC
            LIMIT 4
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $is_fallback = true;
    $search_mode = 'db_error';
    $products    = [];
    file_put_contents($log_file,
        date('Y-m-d H:i:s') . ' - DB Error: ' . $e->getMessage() . "\n",
        FILE_APPEND
    );
}

// ────── RESPONSE ──────
$output = [
    'success'       => true,
    'keyword'       => $keyword,
    'description'   => $description,
    'category_id'   => $finalCatId,
    'color_matched' => $foundColor,
    'search_mode'   => $search_mode,
    'is_fallback'   => $is_fallback,
    'products'      => $products,
];

if (!$is_fallback && $ai_success && is_dir($cacheDir) && is_writable($cacheDir)) {
    @file_put_contents($cacheFile, json_encode($output));
}

echo json_encode($output);
?>