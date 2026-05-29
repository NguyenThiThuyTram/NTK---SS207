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

// ────── CACHE (MD5 of file) ──────
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

// ────── HARD-CODED SHOP CATEGORIES (NTK Fashion) ──────
// ONLY keywords that match what the shop actually sells.
// AWS labels are mapped into this list; anything outside is ignored.
$SHOP_CATEGORIES = [
    // Tops
    'T-Shirt'    => 'áo thun',
    'Shirt'      => 'áo sơ mi',
    'Blouse'     => 'áo kiểu',
    'Sweater'    => 'áo len',
    'Jacket'     => 'áo khoác',
    'Coat'       => 'áo khoác',
    'Hoodie'     => 'áo hoodie',
    'Top'        => 'áo thun',
    // Bottoms
    'Pants'      => 'quần',
    'Trousers'   => 'quần tây',
    'Jeans'      => 'quần jean',
    'Shorts'     => 'quần short',
    'Skirt'      => 'chân váy',
    // One-piece
    'Dress'      => 'đầm',
    'Gown'       => 'đầm dạ hội',
    'Jumpsuit'   => 'jumpsuit',
    // Accessories
    'Hat'        => 'mũ',
    'Handbag'    => 'túi xách',
    'Bag'        => 'túi xách',
    'Shoes'      => 'giày',
    'Glasses'    => 'kính',
    'Sunglasses' => 'kính mát',
    // Suit
    'Suit'       => 'vest',
    'Blazer'     => 'vest',
];

// ────── COLOR MAP ──────
$COLOR_MAP = [
    'Red'       => 'đỏ',
    'Blue'      => 'xanh dương',
    'Navy Blue' => 'xanh navy',
    'Black'     => 'đen',
    'White'     => 'trắng',
    'Green'     => 'xanh lá',
    'Yellow'    => 'vàng',
    'Pink'      => 'hồng',
    'Purple'    => 'tím',
    'Brown'     => 'nâu',
    'Orange'    => 'cam',
    'Gray'      => 'xám',
    'Grey'      => 'xám',
    'Beige'     => 'be',
    'Cream'     => 'kem',
    'Maroon'    => 'đỏ đô',
];

// ────── DEFAULT FALLBACK ──────
$DEFAULT_FALLBACK_KEYWORD = 'áo thun';

// ────── ANALYZE WITH AWS REKOGNITION (sole engine) ──────
require_once __DIR__ . '/includes/aws_rekognition.php';

$foundCategory = ''; // matched shop category keyword (VN)
$foundColor    = ''; // matched color keyword (VN)
$ai_success    = false;
$log_file      = __DIR__ . '/image_search_log.txt';

$rekognition = new AwsRekognition();

if ($rekognition->isConfigured()) {
    $rawBytes = file_get_contents($tmpPath);
    $awsResult = $rekognition->detectLabels($rawBytes, 30, 50.0);

    if (!isset($awsResult['error']) && isset($awsResult['Labels'])) {
        foreach ($awsResult['Labels'] as $label) {
            $name = $label['Name'];

            // Map to hard-coded shop category (first match wins)
            if (empty($foundCategory) && isset($SHOP_CATEGORIES[$name])) {
                $foundCategory = $SHOP_CATEGORIES[$name];
            }

            // Map to color (first match wins)
            if (empty($foundColor) && isset($COLOR_MAP[$name])) {
                $foundColor = $COLOR_MAP[$name];
            }

            // Stop scanning once both are found
            if (!empty($foundCategory) && !empty($foundColor)) {
                break;
            }
        }

        if (!empty($foundCategory)) {
            $ai_success = true;
            file_put_contents($log_file,
                date('Y-m-d H:i:s') . ' - AWS OK | Category: ' . $foundCategory . ' | Color: ' . $foundColor . PHP_EOL,
                FILE_APPEND
            );
        } else {
            file_put_contents($log_file,
                date('Y-m-d H:i:s') . ' - AWS: No matching shop category found in labels.' . PHP_EOL,
                FILE_APPEND
            );
        }
    } else {
        $errDetail = $awsResult['error'] ?? 'Unknown AWS error';
        file_put_contents($log_file,
            date('Y-m-d H:i:s') . ' - AWS Error: ' . $errDetail . PHP_EOL,
            FILE_APPEND
        );
    }
} else {
    file_put_contents($log_file,
        date('Y-m-d H:i:s') . ' - AWS Rekognition not configured.' . PHP_EOL,
        FILE_APPEND
    );
}

// ────── BUILD DISPLAY KEYWORD & DESCRIPTION ──────
if ($ai_success) {
    $keyword     = $foundCategory;
    $description = 'AWS AI nhận diện: ' . ucfirst($foundCategory) . ($foundColor ? ' màu ' . $foundColor : '');
} else {
    $keyword     = $DEFAULT_FALLBACK_KEYWORD;
    $description = 'Hệ thống tự động gợi ý các mẫu mới nhất cho bạn.';
    file_put_contents($log_file,
        date('Y-m-d H:i:s') . ' - Using DEFAULT fallback: ' . $DEFAULT_FALLBACK_KEYWORD . PHP_EOL,
        FILE_APPEND
    );
}

// ────── DATABASE QUERY (3-level priority) ──────
require_once __DIR__ . '/config/database.php';

$products    = [];
$is_fallback = false;
$search_mode = '';

try {
    // ── PRIORITY 1: Category AND Color (most similar to uploaded image) ──
    if ($ai_success && !empty($foundColor)) {
        $stmt = $conn->prepare("
            SELECT p.product_id, p.name, p.image,
                   MIN(v.sale_price) as sale_price,
                   MIN(v.original_price) as original_price
            FROM products p
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            WHERE p.status = 1
              AND (p.name LIKE :cat OR p.description LIKE :cat)
              AND (p.name LIKE :col OR p.description LIKE :col)
            GROUP BY p.product_id
            ORDER BY p.product_id DESC
            LIMIT 4
        ");
        $stmt->execute([
            'cat' => '%' . $foundCategory . '%',
            'col' => '%' . $foundColor . '%',
        ]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($products)) {
            $search_mode = 'category_and_color';
        }
    }

    // ── PRIORITY 2: Category only ──
    if (empty($products) && !empty($keyword)) {
        $stmt = $conn->prepare("
            SELECT p.product_id, p.name, p.image,
                   MIN(v.sale_price) as sale_price,
                   MIN(v.original_price) as original_price
            FROM products p
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            WHERE p.status = 1
              AND (p.name LIKE :keyword OR p.description LIKE :keyword)
            GROUP BY p.product_id
            ORDER BY p.product_id DESC
            LIMIT 4
        ");
        $stmt->execute(['keyword' => '%' . $keyword . '%']);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($products)) {
            $search_mode = 'category_only';
        }
    }

    // ── PRIORITY 3: Newest 4 products (prevent empty UI) ──
    if (empty($products)) {
        $is_fallback = true;
        $keyword     = $DEFAULT_FALLBACK_KEYWORD;
        $description = 'Hệ thống tự động gợi ý các sản phẩm mới nhất cho bạn.';
        $search_mode = 'newest_fallback';

        $stmt = $conn->prepare("
            SELECT p.product_id, p.name, p.image,
                   MIN(v.sale_price) as sale_price,
                   MIN(v.original_price) as original_price
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
        date('Y-m-d H:i:s') . ' - DB Error: ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );
}

// ────── RESPONSE ──────
$output = [
    'success'     => true,
    'keyword'     => $keyword,
    'description' => $description,
    'search_mode' => $search_mode, // diagnostic: category_and_color | category_only | newest_fallback | db_error
    'is_fallback' => $is_fallback,
    'products'    => $products,
];

// Cache only when AWS found a real match (not fallback)
if (!$is_fallback && $ai_success && is_dir($cacheDir) && is_writable($cacheDir)) {
    @file_put_contents($cacheFile, json_encode($output));
}

echo json_encode($output);
?>