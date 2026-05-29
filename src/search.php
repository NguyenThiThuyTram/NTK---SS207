<?php
require_once 'includes/header.php';
require_once 'config/database.php'; 

// ==========================================
// 1. LẤY DANH MỤC TỪ DATABASE (Bảng Categories)
// ==========================================
$categories = [];
try {
    $stmt_cat = $conn->query("SELECT category_id, name FROM categories");
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Lỗi lấy danh mục: " . $e->getMessage();
}

// ==========================================
// 2. BẮT CÁC THAM SỐ LỌC TỪ URL VÀ XỬ LÝ SQL
// ==========================================
$keyword          = isset($_GET['q']) ? trim($_GET['q']) : '';
$image_search     = isset($_GET['image_search']) && $_GET['image_search'] === '1';
$image_description= isset($_GET['image_desc'])  ? trim($_GET['image_desc'])  : '';
$image_cat        = isset($_GET['image_cat'])   ? trim($_GET['image_cat'])   : '';  // category_id từ nhận diện ảnh
$image_color      = isset($_GET['image_color']) ? trim($_GET['image_color']) : '';  // màu từ nhận diện ảnh
$fallback_mode    = isset($_GET['fallback'])    && $_GET['fallback'] === '1';
$price_filter     = isset($_GET['price'])       ? $_GET['price']             : '';
$sort_filter      = isset($_GET['sort'])        ? $_GET['sort']              : 'default';
$cat_filter       = isset($_GET['category'])    ? (array)$_GET['category']  : [];

// Nếu tìm kiếm ảnh và sidebar chưa có category nào được tích nhưng image_cat có → tự động pre-fill
if ($image_search && !empty($image_cat) && empty($cat_filter)) {
    $cat_filter = [$image_cat];
}


$products = [];
$fallback_products = [];

$sql = "
    SELECT 
        p.product_id, p.name, p.image, p.rating, p.sold_count,
        MIN(pv.original_price) as original_price,
        MIN(pv.sale_price) as sale_price
    FROM products p
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE p.status = 1
";

$params = [];

if ($keyword !== '' && !($image_search && !empty($cat_filter))) {
    $sql .= " AND (p.name LIKE :keyword OR p.description LIKE :keyword) ";
    $params['keyword'] = '%' . $keyword . '%';
}

if (!empty($cat_filter)) {
    $cat_placeholders = [];
    foreach($cat_filter as $index => $cat_id) {
        $param_name = 'cat_' . $index;
        $cat_placeholders[] = ':' . $param_name;
        $params[$param_name] = $cat_id;
    }
    $sql .= " AND p.category_id IN (" . implode(',', $cat_placeholders) . ") ";
}

$sql .= " GROUP BY p.product_id ";

$actual_price = "MIN(COALESCE(NULLIF(pv.sale_price, 0), pv.original_price))";
$having_clauses = [];

if ($price_filter === 'under_200') {
    $having_clauses[] = "$actual_price < 200000";
} elseif ($price_filter === '200_500') {
    $having_clauses[] = "$actual_price BETWEEN 200000 AND 500000";
} elseif ($price_filter === 'over_500') {
    $having_clauses[] = "$actual_price > 500000";
}

if (!empty($having_clauses)) {
    $sql .= " HAVING " . implode(' AND ', $having_clauses);
}

if ($sort_filter === 'asc') {
    $sql .= " ORDER BY $actual_price ASC ";
} elseif ($sort_filter === 'desc') {
    $sql .= " ORDER BY $actual_price DESC ";
}

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("search.php query: " . $e->getMessage());
}

// Sau khi fetch: nếu tìm kiếm ảnh có màu + sort mặc định
// → đưa sản phẩm có variant màu khớp lên trước
if ($image_search && !empty($image_color) && !empty($products) && $sort_filter === 'default') {
    try {
        $pidList = array_column($products, 'product_id');
        $pidPlaceholders = implode(',', array_fill(0, count($pidList), '?'));
        $colorStmt = $conn->prepare(
            "SELECT DISTINCT product_id FROM product_variants
             WHERE product_id IN ($pidPlaceholders) AND color LIKE ? AND is_active = 1"
        );
        $colorStmt->execute(array_merge($pidList, ['%' . $image_color . '%']));
        $colorMatchIds = array_flip($colorStmt->fetchAll(PDO::FETCH_COLUMN));
        // Sắp xếp: màu khớp (0) lên trước, không khớp (1) xuống sau
        usort($products, function($a, $b) use ($colorMatchIds) {
            $aM = isset($colorMatchIds[$a['product_id']]) ? 0 : 1;
            $bM = isset($colorMatchIds[$b['product_id']]) ? 0 : 1;
            return $aM - $bM;
        });
    } catch (PDOException $e) { /* giữ nguyên thứ tự nếu lỗi */ }
}

$count = count($products);

// Fallback: chỉ khi image_search VÀ không có category filter hoặc bị lọc giá quá khắt
// (Nếu đã có cat_filter từ image search → để trống là ổn, không cần fallback cũ)
if ($count === 0 && $image_search && empty($cat_filter)) {
    try {
        $stmt = $conn->prepare(
            "SELECT p.product_id, p.name, p.image, p.rating, p.sold_count,
                    MIN(pv.original_price) as original_price, MIN(pv.sale_price) as sale_price
             FROM products p LEFT JOIN product_variants pv ON p.product_id = pv.product_id
             WHERE p.status = 1
             GROUP BY p.product_id ORDER BY p.sold_count DESC LIMIT 8"
        );
        $stmt->execute();
        $fallback_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fallback_mode = true;
    } catch (PDOException $e) {
        $fallback_products = [];
    }
}

?>

<style>
    .search-page-container { width: 90%; max-width: 1400px; margin: 40px auto; }
    .search-header h2 { font-size: 24px; font-weight: normal; margin-bottom: 5px; }
    .search-header p { color: #666; font-size: 14px; margin-bottom: 30px; }
    .search-layout { display: flex; gap: 40px; align-items: flex-start; }
    .sidebar-filter { width: 250px; flex-shrink: 0; }
    .filter-group { margin-bottom: 30px; }
    .filter-group h3 { font-size: 14px; margin-bottom: 15px; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 10px;}
    .filter-group label { display: block; margin-bottom: 12px; font-size: 14px; cursor: pointer; color: #444; }
    .filter-group input[type="checkbox"], .filter-group input[type="radio"] { margin-right: 8px; }
    .sort-select { width: 100%; padding: 10px; border: 1px solid #e5e5e5; outline: none; background: #fff; cursor: pointer;}
    .product-grid { flex: 1; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; }
    .product-card { position: relative; cursor: pointer; transition: transform 0.3s; border: 1px solid #e5e5e5; padding-bottom: 15px; background: #fff; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-color: #ccc; }
    .product-card img { width: 100%; height: 300px; object-fit: cover; background-color: #f9f9f9; }
    .product-info { padding: 0 15px; margin-top: 15px; }
    .product-name { font-size: 14px; font-weight: normal; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #333;}
    .product-meta { font-size: 12px; color: #888; margin-bottom: 8px; }
    .product-meta .rating { color: #f39c12; margin-right: 10px; }
    .product-price { font-size: 14px; }
    .current-price { color: #111; font-weight: bold; margin-right: 10px; }
    .old-price { color: #999; text-decoration: line-through; font-size: 13px; }
    .sale-badge { position: absolute; top: 10px; left: 10px; background-color: #d32f2f; color: white; font-size: 12px; padding: 3px 8px; font-weight: bold; z-index: 2; }

    /* DARK MODE STYLING */
    body.dark-mode .search-page-container { color: #eeeeee; }
    body.dark-mode .search-header h2 { color: #ffffff; }
    body.dark-mode .search-header p { color: #cccccc; }
    body.dark-mode .sidebar-filter { background-color: #1e1e1e; border-radius: 8px; padding: 20px; }
    body.dark-mode .filter-group h3 { color: #ffffff; border-bottom-color: #2a2a2a; }
    body.dark-mode .filter-group label { color: #cccccc; }
    body.dark-mode .filter-group input[type="radio"], body.dark-mode .filter-group input[type="checkbox"] { accent-color: #a6825c; }
    body.dark-mode .sort-select { background-color: #252525 !important; border-color: #333333 !important; color: #ffffff !important; }
    body.dark-mode .product-card { background-color: #1e1e1e; border-color: #2a2a2a; }
    body.dark-mode .product-name { color: #ffffff; }
    body.dark-mode .current-price { color: #e5c199; }
    body.dark-mode .image-search-summary { background-color: #2a2a1f !important; border-color: #4a4a30 !important; color: #cccccc !important; }
    body.dark-mode .image-search-summary h3 { color: #ffffff !important; }
</style>

<div class="search-page-container">
    <div class="search-header">
        <?php
            // Tiêu đề trang
            if ($image_search && !empty($image_cat)) {
                $catNameMap = [
                    'CAT01'=>'Áo thun','CAT02'=>'Áo khoác','CAT03'=>'Hoodie & Sweater',
                    'CAT04'=>'Quần','CAT05'=>'Áo sơ mi','CAT06'=>'Quần đùi',
                    'CAT07'=>'Áo polo','CAT08'=>'Quần jeans','CAT09'=>'Chân váy',
                    'CAT10'=>'Áo len & cardigan'
                ];
                $pageTitle = 'Gợi ý ảnh: ' . ($catNameMap[$image_cat] ?? $image_cat);
                if (!empty($image_color)) $pageTitle .= ' màu ' . htmlspecialchars($image_color);
            } elseif ($keyword !== '') {
                $pageTitle = 'Kết quả tìm kiếm: &ldquo;' . htmlspecialchars($keyword) . '&rdquo;';
            } else {
                $pageTitle = 'Tất cả sản phẩm';
            }
        ?>
        <h2><?= $pageTitle ?></h2>
        <p>
            <?php if ($image_search): ?>
                Tìm thấy <strong><?= $count ?></strong> sản phẩm<?= (!empty($image_color) ? ' — màu <strong>' . htmlspecialchars($image_color) . '</strong> ưu tiên lên đầu' : '') ?>.
            <?php else: ?>
                Tìm thấy <?php echo $count; ?> sản phẩm phù hợp.
            <?php endif; ?>
        </p>
    </div>

    <div class="search-layout">
        <aside class="sidebar-filter">
            <form action="search.php" method="GET" id="filterForm">
                <?php if($keyword !== '' && !($image_search && !empty($cat_filter))): ?>
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($keyword); ?>">
                <?php endif; ?>
                <?php if ($image_search): ?>
                    <input type="hidden" name="image_search" value="1">
                    <input type="hidden" name="image_desc"  value="<?php echo htmlspecialchars($image_description); ?>">
                    <input type="hidden" name="image_cat"   value="<?php echo htmlspecialchars($image_cat); ?>">
                    <?php if (!empty($image_color)): ?>
                        <input type="hidden" name="image_color" value="<?php echo htmlspecialchars($image_color); ?>">
                    <?php endif; ?>
                <?php endif; ?>

                <div class="filter-group">
                    <h3>DANH MỤC</h3>
                    <?php if(!empty($categories)): ?>
                        <?php foreach($categories as $cat): ?>
                            <label>
                                <input type="checkbox" name="category[]" value="<?php echo $cat['category_id']; ?>" onchange="this.form.submit()" <?php if(in_array($cat['category_id'], $cat_filter)) echo 'checked'; ?>> 
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="filter-group">
                    <h3>KHOẢNG GIÁ</h3>
                    <label><input type="radio" name="price" value="under_200" onchange="this.form.submit()" <?php if($price_filter=='under_200') echo 'checked'; ?>> Dưới 200.000đ</label>
                    <label><input type="radio" name="price" value="200_500" onchange="this.form.submit()" <?php if($price_filter=='200_500') echo 'checked'; ?>> 200.000đ - 500.000đ</label>
                    <label><input type="radio" name="price" value="over_500" onchange="this.form.submit()" <?php if($price_filter=='over_500') echo 'checked'; ?>> Trên 500.000đ</label>
                </div>

                <div class="filter-group">
                    <h3>SẮP XẾP</h3>
                    <select class="sort-select" name="sort" onchange="this.form.submit()">
                        <option value="default" <?php if($sort_filter=='default') echo 'selected'; ?>>Mặc định</option>
                        <option value="asc" <?php if($sort_filter=='asc') echo 'selected'; ?>>Giá từ thấp đến cao</option>
                        <option value="desc" <?php if($sort_filter=='desc') echo 'selected'; ?>>Giá từ cao đến thấp</option>
                    </select>
                </div>
            </form>
        </aside>

        <main class="product-grid">
            <?php if ($image_search): ?>
                <div class="image-search-summary" style="grid-column:1 / -1; background:#fff8e9; border:1px solid #f2dcb4; border-radius:8px; padding:18px; margin-bottom:20px;">
                    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
                        <div>
                            <h3 style="margin:0 0 6px; font-size:17px; color:#333;">Gợi ý từ ảnh</h3>
                            <p style="margin:0; color:#5b4a20; font-size:14px; line-height:1.5;">
                                <?php echo $image_description ? htmlspecialchars($image_description) : 'Hệ thống đã phân tích ảnh và đưa ra những sản phẩm tương tự.'; ?>
                            </p>
                        </div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                            <?php if (!empty($image_cat)): ?>
                                <?php
                                    $catNames = [
                                        'CAT01'=>'Áo thun','CAT02'=>'Áo khoác','CAT03'=>'Hoodie &amp; Sweater',
                                        'CAT04'=>'Quần','CAT05'=>'Áo sơ mi','CAT06'=>'Quần đùi',
                                        'CAT07'=>'Áo polo','CAT08'=>'Quần jeans','CAT09'=>'Chân váy',
                                        'CAT10'=>'Áo len &amp; cardigan'
                                    ];
                                    $catLabel = $catNames[$image_cat] ?? $image_cat;
                                ?>
                                <span style="background:#fff3cd; color:#7a5000; padding:7px 14px; border-radius:20px; font-weight:600; font-size:13px;">
                                    🛈 Danh mục: <?= $catLabel ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($image_color)): ?>
                                <span style="background:#fce4ec; color:#880e4f; padding:7px 14px; border-radius:20px; font-weight:600; font-size:13px;">
                                    🎨 Màu: <?= htmlspecialchars($image_color) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <?php 
            $renderList = $count > 0 ? $products : $fallback_products; 
            if (!empty($renderList)):
                foreach ($renderList as $p): 
            ?>

                <div class="product-card">
                    <a href="product_detail.php?id=<?php echo $p['product_id']; ?>" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                    <?php 
                        $gia_goc = $p['original_price'] ?? 0; 
                        $gia_sale = $p['sale_price'] ?? 0;
                    ?>
                    <?php if($gia_sale > 0 && $gia_sale < $gia_goc): ?>
                        <div class="sale-badge">SALE</div>
                    <?php endif; ?>
                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    <div class="product-info">
                        <h4 class="product-name"><?php echo htmlspecialchars($p['name']); ?></h4>
                        <div class="product-meta">
                            <span class="rating">⭐ <?php echo !empty($p['rating']) ? $p['rating'] : '5.0'; ?></span>
                            <span class="sold">Đã bán <?php echo !empty($p['sold_count']) ? $p['sold_count'] : '0'; ?></span>
                        </div>
                        <div class="product-price">
                            <?php if($gia_sale > 0 && $gia_sale < $gia_goc): ?>
                                <span class="current-price"><?php echo number_format($gia_sale, 0, ',', '.'); ?> VNĐ</span>
                                <span class="old-price"><?php echo number_format($gia_goc, 0, ',', '.'); ?> VNĐ</span>
                            <?php else: ?>
                                <span class="current-price"><?php echo number_format($gia_goc, 0, ',', '.'); ?> VNĐ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    </a>
                </div>
            <?php 
                endforeach; 
            else: 
            ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: #f9f9f9; border: 1px dashed #ccc;">
                    <p style="font-size: 16px; color: #666;">Không tìm thấy sản phẩm nào phù hợp với bộ lọc của bạn.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>