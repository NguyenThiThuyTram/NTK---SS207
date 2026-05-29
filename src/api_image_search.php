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
$imageType = $_FILES['image']['type'];
if (!in_array($imageType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Định dạng hình ảnh không được hỗ trợ (chỉ nhận JPG, PNG, GIF, WEBP)']);
    exit;
}

// ────── CACHE ──────
$tmpPath   = $_FILES['image']['tmp_name'];
$imageHash = md5_file($tmpPath);
$cacheDir  = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/' . $imageHash . '.json';

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
    header('X-Cache: HIT');
    echo file_get_contents($cacheFile);
    exit;
}

// ──────────────────────────────────────────────────────────────────
// HARD-CODED SHOP CATEGORIES – mapping AWS label → category_id của NTK
// Chỉ các danh mục shop thực sự bán mới được phép trả về.
// ──────────────────────────────────────────────────────────────────
$CATEGORY_MAP = [
    // CAT01 – Áo thun
    'T-Shirt'      => 'CAT01',
    'Tee'          => 'CAT01',
    'Crop Top'     => 'CAT01',
    // CAT02 – Áo khoác
    'Jacket'       => 'CAT02',
    'Coat'         => 'CAT02',
    'Windbreaker'  => 'CAT02',
    'Parka'        => 'CAT02',
    'Outerwear'    => 'CAT02',
    // CAT03 – Hoodie & Sweater
    'Hoodie'       => 'CAT03',
    'Sweatshirt'   => 'CAT03',
    'Sweater'      => 'CAT03',
    'Pullover'     => 'CAT03',
    // CAT04 – Quần (kaki, vải dù, parachute…)
    'Pants'        => 'CAT04',
    'Trousers'     => 'CAT04',
    'Cargo'        => 'CAT04',
    // CAT05 – Áo sơ mi
    'Shirt'        => 'CAT05',
    'Blouse'       => 'CAT05',
    'Button Down'  => 'CAT05',
    // CAT06 – Quần đùi / short
    'Shorts'       => 'CAT06',
    'Short'        => 'CAT06',
    // CAT07 – Áo polo
    'Polo'         => 'CAT07',
    'Polo Shirt'   => 'CAT07',
    // CAT08 – Quần jeans
    'Jeans'        => 'CAT08',
    'Denim'        => 'CAT08',
    // CAT09 – Chân váy
    'Skirt'        => 'CAT09',
    'Mini Skirt'   => 'CAT09',
    // CAT10 – Áo len & cardigan
    'Cardigan'     => 'CAT10',
    'Knitwear'     => 'CAT10',
    'Knit'         => 'CAT10',
];

// Tên hiển thị của từng category (dùng cho description + keyword trả về frontend)
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

// Danh mục mặc định khi AWS không nhận ra được gì
$DEFAULT_CATEGORY_ID   = 'CAT01';
$DEFAULT_CATEGORY_NAME = 'áo thun';

// ──────────────────────────────────────────────────────────────────
// COLOR MAP: AWS label → màu tiếng Việt (khớp với product_variants.color)
// Các màu được lấy trực tiếp từ dữ liệu product_variants trong database:
// Trắng, Đen, Xanh, Xanh Navy, Xanh Nhạt, Hồng, Vàng, Nâu, Ghi, Kem,
// Tím, Đỏ, Xám, Sắc Bé (Beige), Đỏ Đô (Maroon), v.v.
// ──────────────────────────────────────────────────────────────────
$COLOR_MAP = [
    // Trắng
    'White'         => 'Trắng',
    // Đen
    'Black'         => 'Đen',
    // Xanh (dương / lá – AWS thường dùng Blue/Green)
    'Blue'          => 'Xanh',
    'Green'         => 'Xanh',
    'Navy Blue'     => 'Xanh Navy',
    'Navy'          => 'Xanh Navy',
    'Light Blue'    => 'Xanh Nhạt',
    'Cyan'          => 'Xanh',
    'Teal'          => 'Xanh',
    // Hồng
    'Pink'          => 'Hồng',
    'Rose'          => 'Hồng',
    'Salmon'        => 'Hồng',
    // Vàng
    'Yellow'        => 'Vàng',
    'Gold'          => 'Vàng',
    // Nâu
    'Brown'         => 'Nâu',
    'Tan'           => 'Nâu',
    'Camel'         => 'Nâu',
    // Xám / Ghi
    'Gray'          => 'Ghi',
    'Grey'          => 'Ghi',
    'Silver'        => 'Ghi',
    // Kem / Be
    'Beige'         => 'Kem',
    'Cream'         => 'Kem',
    'Off White'     => 'Kem',
    'Ivory'         => 'Kem',
    // Tím
    'Purple'        => 'Tím',
    'Violet'        => 'Tím',
    'Lavender'      => 'Tím',
    // Đỏ
    'Red'           => 'Đỏ',
    'Maroon'        => 'Đỏ',
    'Burgundy'      => 'Đỏ',
    // Cam
    'Orange'        => 'Cam',
    // Đỏ Đô
    'Dark Red'      => 'Đỏ',
];

// ────── LOG FILE ──────
$log_file = __DIR__ . '/image_search_log.txt';

// ────── AWS REKOGNITION ANALYSIS ──────
require_once __DIR__ . '/includes/aws_rekognition.php';

$foundCategoryId   = '';
$foundCategoryName = '';
$foundColor        = '';   // màu tiếng Việt khớp với product_variants.color
$ai_success        = false;

$rekognition = new AwsRekognition();

if ($rekognition->isConfigured()) {
    $rawBytes  = file_get_contents($tmpPath);
    $awsResult = $rekognition->detectLabels($rawBytes, 30, 50.0);

    if (!isset($awsResult['error']) && isset($awsResult['Labels'])) {
        foreach ($awsResult['Labels'] as $label) {
            $name = $label['Name'];

            // Map sang category_id của shop (first match wins)
            if (empty($foundCategoryId) && isset($CATEGORY_MAP[$name])) {
                $foundCategoryId   = $CATEGORY_MAP[$name];
                $foundCategoryName = $CATEGORY_NAMES[$foundCategoryId];
            }

            // Map sang màu tiếng Việt (first match wins)
            if (empty($foundColor) && isset($COLOR_MAP[$name])) {
                $foundColor = $COLOR_MAP[$name];
            }

            // Dừng scan khi đã có cả hai
            if (!empty($foundCategoryId) && !empty($foundColor)) {
                break;
            }
        }

        if (!empty($foundCategoryId)) {
            $ai_success = true;
            file_put_contents($log_file,
                date('Y-m-d H:i:s') . " - AWS OK | CategoryID: $foundCategoryId ($foundCategoryName) | Color: $foundColor\n",
                FILE_APPEND
            );
        } else {
            file_put_contents($log_file,
                date('Y-m-d H:i:s') . " - AWS: No matching shop category in labels.\n",
                FILE_APPEND
            );
        }
    } else {
        $errDetail = $awsResult['error'] ?? 'Unknown AWS error';
        file_put_contents($log_file,
            date('Y-m-d H:i:s') . " - AWS Error: $errDetail\n",
            FILE_APPEND
        );
    }
} else {
    file_put_contents($log_file,
        date('Y-m-d H:i:s') . " - AWS Rekognition not configured.\n",
        FILE_APPEND
    );
}

// Gán fallback nếu AWS không nhận ra
if (!$ai_success) {
    $foundCategoryId   = $DEFAULT_CATEGORY_ID;
    $foundCategoryName = $DEFAULT_CATEGORY_NAME;
    file_put_contents($log_file,
        date('Y-m-d H:i:s') . " - Using DEFAULT category: $DEFAULT_CATEGORY_ID\n",
        FILE_APPEND
    );
}

$keyword     = $foundCategoryName;
$description = 'AWS AI nhận diện: ' . ucfirst($foundCategoryName) . ($foundColor ? ' màu ' . $foundColor : '');

// ──────────────────────────────────────────────────────────────────
// DATABASE QUERY – 3 cấp ưu tiên
// Ưu tiên 1: Đúng category_id VÀ có variant màu phù hợp → sản phẩm giống ảnh nhất
// Ưu tiên 2: Đúng category_id (bất kỳ màu)
// Ưu tiên 3: 4 sản phẩm mới nhất (tránh giao diện trống)
// ──────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/database.php';

$products    = [];
$is_fallback = false;
$search_mode = '';

try {
    // ── PRIORITY 1: category_id + màu sắc khớp ──
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
              AND p.category_id = :cat_id
              AND v.color LIKE :color
              AND v.is_active = 1
            GROUP BY p.product_id
            ORDER BY p.product_id DESC
            LIMIT 4
        ");
        $stmt->execute([
            'cat_id' => $foundCategoryId,
            'color'  => '%' . $foundColor . '%',
        ]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($products)) {
            $search_mode = 'category_and_color';
        }
    }

    // ── PRIORITY 2: category_id (bất kỳ màu) ──
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
        $stmt->execute(['cat_id' => $foundCategoryId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($products)) {
            $search_mode = 'category_only';
        }
    }

    // ── PRIORITY 3: 4 sản phẩm mới nhất (tránh trang trống) ──
    if (empty($products)) {
        $is_fallback = true;
        $keyword     = $DEFAULT_CATEGORY_NAME;
        $description = 'Hệ thống tự động gợi ý các sản phẩm mới nhất cho bạn.';
        $search_mode = 'newest_fallback';

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
    'category_id'   => $foundCategoryId,
    'color_matched' => $foundColor,
    'search_mode'   => $search_mode,   // category_and_color | category_only | newest_fallback | db_error
    'is_fallback'   => $is_fallback,
    'products'      => $products,
];

// Cache chỉ khi tìm thấy kết quả thật (không phải fallback)
if (!$is_fallback && $ai_success && is_dir($cacheDir) && is_writable($cacheDir)) {
    @file_put_contents($cacheFile, json_encode($output));
}

echo json_encode($output);
?>