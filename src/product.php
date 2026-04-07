<?php
require_once 'config/database.php';
include 'includes/header.php';

// 1. Lấy danh mục
$sql_cate = "SELECT * FROM Categories ORDER BY priority ASC";
$stmt_cate = $conn->prepare($sql_cate);
$stmt_cate->execute();
$categories = $stmt_cate->fetchAll(PDO::FETCH_ASSOC);

// 2. Lấy sản phẩm kèm giá
$cat_id = isset($_GET['cat']) ? $_GET['cat'] : null;
$sql = "SELECT p.*, v.original_price, v.sale_price 
        FROM Products p 
        LEFT JOIN Product_Variants v ON p.product_id = v.product_id ";
if ($cat_id) {
    $sql .= " WHERE p.category_id = :cat_id AND p.status = 1 ";
} else {
    $sql .= " WHERE p.status = 1 ";
}
$sql .= " GROUP BY p.product_id";

$stmt = $conn->prepare($sql);
if ($cat_id) $stmt->bindParam(':cat_id', $cat_id);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container">
    <nav class="breadcrumb">Trang chủ > <b>Shop</b></nav>

    <div class="category-filter">
        <a href="product.php" class="btn-cat <?php echo !$cat_id ? 'active' : ''; ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="product.php?cat=<?php echo $cat['category_id']; ?>" 
               class="btn-cat <?php echo $cat_id == $cat['category_id'] ? 'active' : ''; ?>">
                <?php echo $cat['name']; ?>
            </a>
        <?php endforeach; ?>
    </div>


<div class="product-grid">
    <?php foreach ($products as $p): ?>
        <div class="product-card">
            <a href="product_detail.php?id=<?php echo $p['product_id']; ?>">
                <div class="img-wrapper">
                    <img src="<?php echo $p['image']; ?>" alt="">
                    
                    <?php /* if ($p['sale_price'] < $p['original_price']): ?>
                        <span class="badge-sale">SALE</span>
                    <?php endif; 
                    */ ?>
                </div>
                
                <div class="product-info">
                    <h3 class="product-name"><?php echo $p['name']; ?></h3>
                    
                    <div class="product-meta">
                        <span class="product-stars">★★★★★</span>
                        <span class="product-sold">| Đã bán <?php echo $p['sold_count']; ?></span>
                    </div>

                    <div class="price-container">
                        <?php if ($p['sale_price'] < $p['original_price']): ?>
                            <span class="current-price"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>đ</span>
                            <span class="old-price-strike"><?php echo number_format($p['original_price'], 0, ',', '.'); ?>đ</span>
                        <?php else: ?>
                            <span class="current-price"><?php echo number_format($p['original_price'], 0, ',', '.'); ?>đ</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
</main>

<?php include 'includes/footer.php'; ?>