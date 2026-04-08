<?php
// 1. Phải kết nối Database đầu tiên để có biến $conn
require_once 'config/database.php';

// 2. Gọi header
require_once 'includes/header.php';

// --- PHẦN LOGIC LẤY DỮ LIỆU ---

// Lấy 4 sản phẩm mới nhất (New Arrivals)
// Sắp xếp theo product_id giảm dần (món mới tạo sẽ có ID lớn)
$sql_new = "SELECT p.*, v.original_price, v.sale_price 
            FROM Products p 
            LEFT JOIN Product_Variants v ON p.product_id = v.product_id 
            WHERE p.status = 1
            GROUP BY p.product_id
            ORDER BY p.product_id DESC 
            LIMIT 4";
$stmt_new = $conn->prepare($sql_new);
$stmt_new->execute();
$new_arrivals = $stmt_new->fetchAll(PDO::FETCH_ASSOC);

// Lấy 4 sản phẩm bán chạy nhất (Best Sellers)
// Sắp xếp theo cột sold_count (lượt bán) từ cao đến thấp
$sql_best = "SELECT p.*, v.original_price, v.sale_price 
             FROM Products p 
             LEFT JOIN Product_Variants v ON p.product_id = v.product_id 
             WHERE p.status = 1
             GROUP BY p.product_id
             ORDER BY p.sold_count DESC 
             LIMIT 4";
$stmt_best = $conn->prepare($sql_best);
$stmt_best->execute();
$best_sellers = $stmt_best->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="banner">
    <div class="banner-content" style="text-align: center; padding: 100px 0; background: #f4f4f4;">

    </div>
</div>

<div class="section">
    <div class="section-header">
        <p>MỚI RA MẮT</p>
        <h2>New Arrivals</h2>
    </div>
    <div class="product-grid">
        <?php foreach ($new_arrivals as $item): ?>
            <div class="product-card">
                <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                    <div class="img-wrapper">
                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo $item['name']; ?></h3>
                        <div class="product-meta">
                            <span class="product-stars">★★★★★</span>
                            <span class="product-sold">| Đã bán <?php echo $item['sold_count']; ?></span>
                        </div>
                        <p class="price"><?php echo number_format($item['original_price'], 0, ',', '.'); ?>đ</p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="section bg-be">
    <div class="section-header">
        <p>ĐƯỢC YÊU THÍCH NHẤT</p>
        <h2>Best Sellers</h2>
    </div>
    <div class="product-grid">
        <?php foreach ($best_sellers as $item): ?>
            <div class="product-card">
                <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                    <div class="img-wrapper">
                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                        <span class="badge-hot" style="position:absolute; top:10px; right:10px; background: #a6825c; color:#fff; padding:2px 10px; font-size:12px;">HOT</span>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo $item['name']; ?></h3>
                        <div class="product-meta">
                            <span class="product-stars">★★★★★</span>
                            <span class="product-sold">| Đã bán <?php echo $item['sold_count']; ?></span>
                        </div>
                        <p class="price"><?php echo number_format($item['original_price'], 0, ',', '.'); ?>đ</p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="section">
    <div class="section-header">
        <p>DANH MỤC</p>
        <h2>Shop by Category</h2>
    </div>
    <div class="category-grid">
        <a href="product.php?cat=CAT01" class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7ne96vcjmiu46" alt="Áo thun">
            <div class="category-overlay">Áo thun</div>
        </a>
        <a href="product.php?cat=CAT02" class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mitmevxbal1j0b" alt="Áo khoác">
            <div class="category-overlay">Áo khoác</div>
        </a>
        <a href="product.php?cat=CAT03" class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg6a54vwzvv002" alt="Hoodie">
            <div class="category-overlay">Hoodie</div>
        </a>
        <a href="product.php?cat=CAT04" class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfxc3xhxoop9ed" alt="Quần">
            <div class="category-overlay">Quần</div>
        </a>
        <a href="product.php?cat=CAT05" class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-me8igxycndhj04" alt="Áo sơ mi">
            <div class="category-overlay">Áo sơ mi</div>
        </a>
    </div>
</div>



<?php
require_once 'includes/footer.php';
?>