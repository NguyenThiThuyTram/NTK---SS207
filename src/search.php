<?php
require_once 'includes/header.php';
require_once 'config/database.php'; 

// ==========================================
// 1. LẤY DANH MỤC TỪ DATABASE (Bảng Categories)
// ==========================================
$categories = [];
try {
    // Lấy category_id (VD: CAT01) và name (VD: Áo thun)
    $stmt_cat = $conn->query("SELECT category_id, name FROM Categories");
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Lỗi lấy danh mục: " . $e->getMessage();
}

// ==========================================
// 2. BẮT CÁC THAM SỐ LỌC TỪ URL VÀ XỬ LÝ SQL
// ==========================================
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$price_filter = isset($_GET['price']) ? $_GET['price'] : '';
$sort_filter = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$cat_filter = isset($_GET['category']) ? $_GET['category'] : []; 

$products = [];

// SQL Gốc: Kết nối Products và Product_Variants
$sql = "
    SELECT 
        p.product_id, p.name, p.image, p.rating, p.sold_count,
        MIN(pv.original_price) as original_price,
        MIN(pv.sale_price) as sale_price
    FROM Products p
    LEFT JOIN Product_Variants pv ON p.product_id = pv.product_id
    WHERE p.status = 1
";

$params = [];

// Lọc: Từ khóa
if ($keyword !== '') {
    $sql .= " AND p.name LIKE :keyword ";
    $params['keyword'] = '%' . $keyword . '%';
}

// Lọc: Danh mục (Checkbox nhiều lựa chọn)
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

// Lọc: Khoảng Giá (Dưới 200, 200-500, Trên 500)
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

// Lọc: Sắp xếp
if ($sort_filter === 'asc') {
    $sql .= " ORDER BY $actual_price ASC ";
} elseif ($sort_filter === 'desc') {
    $sql .= " ORDER BY $actual_price DESC ";
}

// Thực thi truy vấn
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Lỗi truy vấn: " . $e->getMessage();
}

$count = count($products);
?>

<style>
    .search-page-container {
        width: 90%;
        max-width: 1400px;
        margin: 40px auto;
    }
    .search-header h2 { font-size: 24px; font-weight: normal; margin-bottom: 5px; }
    .search-header p { color: #666; font-size: 14px; margin-bottom: 30px; }
    
    /* Layout 2 Cột */
    .search-layout {
        display: flex;
        gap: 40px;
        align-items: flex-start;
    }
    
    /* Cột Trái: Bộ Lọc */
    .sidebar-filter { width: 250px; flex-shrink: 0; }
    .filter-group { margin-bottom: 30px; }
    .filter-group h3 { font-size: 14px; margin-bottom: 15px; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 10px;}
    .filter-group label { display: block; margin-bottom: 12px; font-size: 14px; cursor: pointer; color: #444; }
    .filter-group input[type="checkbox"], .filter-group input[type="radio"] { margin-right: 8px; }
    .sort-select { width: 100%; padding: 10px; border: 1px solid #e5e5e5; outline: none; background: #fff; cursor: pointer;}
    
    /* Cột Phải: Lưới Sản Phẩm */
    .product-grid {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 25px;
    }
    .product-card {
        position: relative; cursor: pointer; transition: transform 0.3s;
        border: 1px solid #e5e5e5; padding-bottom: 15px; background: #fff;
    }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-color: #ccc; }
    .product-card img { width: 100%; height: 300px; object-fit: cover; background-color: #f9f9f9; }
    
    .product-info { padding: 0 15px; margin-top: 15px; }
    .product-name { font-size: 14px; font-weight: normal; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #333;}
    .product-meta { font-size: 12px; color: #888; margin-bottom: 8px; }
    .product-meta .rating { color: #f39c12; margin-right: 10px; }
    
    .product-price { font-size: 14px; }
    .current-price { color: #111; font-weight: bold; margin-right: 10px; }
    .old-price { color: #999; text-decoration: line-through; font-size: 13px; }
    
    .sale-badge {
        position: absolute; top: 10px; left: 10px;
        background-color: #d32f2f; color: white;
        font-size: 12px; padding: 3px 8px; font-weight: bold; z-index: 2;
    }
</style>

<div class="search-page-container">
    <div class="search-header">
        <h2><?php echo $keyword !== '' ? 'Kết quả tìm kiếm: "' . htmlspecialchars($keyword) . '"' : 'Tất cả sản phẩm'; ?></h2>
        <p>Tìm thấy <?php echo $count; ?> sản phẩm phù hợp</p>
    </div>

    <div class="search-layout">
        
        <aside class="sidebar-filter">
            <form action="search.php" method="GET" id="filterForm">
                
                <?php if($keyword !== ''): ?>
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($keyword); ?>">
                <?php endif; ?>

                <div class="filter-group">
                    <h3>DANH MỤC</h3>
                    <?php if(empty($categories)): ?>
                        <p style="font-size: 13px; color: #888;">Chưa có danh mục</p>
                    <?php else: ?>
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
                    <label>
                        <input type="radio" name="price" value="under_200" onchange="this.form.submit()" <?php if($price_filter=='under_200') echo 'checked'; ?>> 
                        Dưới 200.000đ
                    </label>
                    <label>
                        <input type="radio" name="price" value="200_500" onchange="this.form.submit()" <?php if($price_filter=='200_500') echo 'checked'; ?>> 
                        200.000đ - 500.000đ
                    </label>
                    <label>
                        <input type="radio" name="price" value="over_500" onchange="this.form.submit()" <?php if($price_filter=='over_500') echo 'checked'; ?>> 
                        Trên 500.000đ
                    </label>
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
            <?php if ($count > 0): ?>
                <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <a href="product_detail.php?id=<?php echo $p['product_id']; ?>" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                        <?php 
                            $gia_goc = isset($p['original_price']) ? $p['original_price'] : 0; 
                            $gia_sale = isset($p['sale_price']) ? $p['sale_price'] : 0;
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
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: #f9f9f9; border: 1px dashed #ccc;">
                    <p style="font-size: 16px; color: #666;">Không tìm thấy sản phẩm nào phù hợp với bộ lọc của bạn.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>