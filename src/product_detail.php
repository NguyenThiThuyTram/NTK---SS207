<?php
// 1. Kết nối Database
require_once 'config/database.php';

// 2. Lấy ID sản phẩm từ URL
$product_id = $_GET['id'] ?? '';

// Khởi tạo biến để tránh lỗi
$product = null;
$variants = [];
$related_products = [];
$reviews = [];
$first_v = null;

if ($product_id) {
    // 3. Lấy thông tin sản phẩm chính
    $sql_prod = "SELECT p.*, c.name as category_name 
                 FROM Products p 
                 JOIN Categories c ON p.category_id = c.category_id 
                 WHERE p.product_id = :id";
    $stmt = $conn->prepare($sql_prod);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // 4. Lấy Biến thể (Màu, Size, Giá, Tồn kho)
        $sql_variants = "SELECT * FROM Product_Variants WHERE product_id = :id AND is_active = 1";
        $stmt_v = $conn->prepare($sql_variants);
        $stmt_v->bindParam(':id', $product_id);
        $stmt_v->execute();
        $variants = $stmt_v->fetchAll(PDO::FETCH_ASSOC);
        $first_v = $variants[0] ?? null;

        // 5. Lấy Sản phẩm liên quan (Cùng Category, trừ món hiện tại)
        $sql_related = "SELECT p.*, v.original_price, v.sale_price 
                        FROM Products p 
                        LEFT JOIN Product_Variants v ON p.product_id = v.product_id 
                        WHERE p.category_id = :cat_id 
                        AND p.product_id != :prod_id 
                        AND p.status = 1
                        GROUP BY p.product_id
                        LIMIT 4";
        $stmt_related = $conn->prepare($sql_related);
        $stmt_related->bindParam(':cat_id', $product['category_id']);
        $stmt_related->bindParam(':prod_id', $product_id);
        $stmt_related->execute();
        $related_products = $stmt_related->fetchAll(PDO::FETCH_ASSOC);

        // 6. Lấy Feedback (Lấy fullname từ bảng users)
        $sql_reviews = "SELECT r.*, u.fullname 
                        FROM reviews r 
                        LEFT JOIN users u ON r.user_id = u.user_id 
                        WHERE r.product_id = :prod_id 
                        ORDER BY r.created_at DESC";
        $stmt_rev = $conn->prepare($sql_reviews);
        $stmt_rev->bindParam(':prod_id', $product_id);
        $stmt_rev->execute();
        $reviews = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Gọi header
include 'includes/header.php';
?>

<main class="container">
    <?php if ($product): ?>
    <nav class="breadcrumb">Trang chủ / Shop / <b><?php echo $product['name']; ?></b></nav>

    <div class="product-detail-container">
        <div class="product-image-section">
            <img src="<?php echo $product['image']; ?>" alt="" class="main-detail-img">
        </div>

        <div class="product-info-section">
            <h1 class="detail-title"><?php echo $product['name']; ?></h1>
            
            <div class="detail-meta">
                <span class="stars">
                    <?php 
                    $rating = $product['avg_rating'] ?: 5;
                    for($i=1; $i<=5; $i++) echo ($i <= $rating) ? '★' : '☆';
                    ?>
                </span> 
                <span class="rating-text"><?php echo $rating; ?> (<?php echo count($reviews); ?> đánh giá)</span> | 
                <span class="sold-count">Đã bán <?php echo $product['sold_count']; ?></span>
            </div>

            <div class="detail-price-box">
                <?php if ($first_v): ?>
                    <span class="detail-sale-price"><?php echo number_format($first_v['sale_price'], 0, ',', '.'); ?>đ</span>
                    <?php if ($first_v['sale_price'] < $first_v['original_price']): ?>
                        <span class="detail-old-price"><?php echo number_format($first_v['original_price'], 0, ',', '.'); ?>đ</span>
                        <span class="discount-tag">-<?php echo round((($first_v['original_price'] - $first_v['sale_price'])/$first_v['original_price'])*100); ?>%</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="detail-options">
                <p class="option-label">Màu sắc:</p>
                <div class="option-list">
                    <?php foreach(array_unique(array_column($variants, 'color')) as $color): ?>
                        <button class="btn-option"><?php echo $color; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="detail-options">
                <p class="option-label">Kích cỡ:</p>
                <div class="option-list">
                    <?php foreach(array_unique(array_column($variants, 'size')) as $size): ?>
                        <button class="btn-option"><?php echo $size; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="detail-options">
                <p class="option-label">Số lượng:</p>
                <div class="quantity-wrapper">
                    <div class="quantity-selector">
                        <button type="button" class="qty-btn" onclick="changeQty(-1)">-</button>
                        <input type="text" id="quantity" value="1" readonly>
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                    <span class="stock-info"><?php echo $first_v['stock'] ?? 0; ?> sản phẩm có sẵn</span>
                </div>
            </div>

            <div class="detail-actions">
                <button class="btn-add-cart"><i class="fa fa-shopping-bag"></i> Thêm vào giỏ</button>
                <button class="btn-buy-now">Mua ngay</button>
                <button class="btn-wishlist" id="add-to-wishlist" data-id="<?php echo $product['product_id']; ?>">
                    <i class="fa fa-heart-o"></i>
                </button>
            </div>
            
            <div class="detail-description">
                <p class="option-label">Mô tả sản phẩm:</p>
                <p class="desc-text"><?php echo nl2br($product['description']); ?></p>
            </div>

            <div class="product-feedback" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                <h3 style="color: #a6825c; margin-bottom: 20px;">Đánh giá từ khách hàng</h3>
                <?php if (count($reviews) > 0): ?>
                    <div class="review-list">
                        <?php foreach ($reviews as $rev): ?>
                            <div class="review-item" style="margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <b><?php echo $rev['fullname'] ?: 'Khách hàng ẩn danh'; ?></b>
                                    <span style="color: #ffc107;">
                                        <?php for($i=1; $i<=5; $i++) echo ($i <= $rev['rating']) ? '★' : '☆'; ?>
                                    </span>
                                </div>
                                <p style="font-size: 14px; color: #555; margin: 5px 0;"><?php echo $rev['comment']; ?></p>
                                <small style="color: #999;"><?php echo date('d/m/Y', strtotime($rev['created_at'])); ?></small>
                                
                                <?php if (!empty($rev['reply'])): ?>
                                    <div style="background: #f9f5f0; padding: 10px; border-radius: 4px; font-size: 13px; margin-top: 8px;">
                                        <b>NTK phản hồi:</b> <?php echo $rev['reply']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #999;">Chưa có đánh giá nào cho sản phẩm này.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="section related-products" style="margin-top: 50px;">
        <h2 class="section-title">Sản phẩm liên quan</h2>
        <div class="product-grid">
            <?php foreach ($related_products as $item): ?>
                <div class="product-card">
                    <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                        <div class="img-wrapper">
                            <img src="<?php echo $item['image']; ?>" alt="">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo $item['name']; ?></h3>
                            <div class="product-meta">
                                <span class="stars">★★★★★</span>
                                <span class="product-sold">| <?php echo $item['avg_rating'] ?: '5.0'; ?></span>
                            </div>
                            <p class="price"><?php echo number_format($item['original_price'], 0, ',', '.'); ?>đ</p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php else: ?>
        <p style="padding: 100px; text-align: center;">Sản phẩm không tồn tại.</p>
    <?php endif; ?>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function changeQty(amt) {
    let qty = document.getElementById('quantity');
    let newVal = parseInt(qty.value) + amt;
    if (newVal >= 1) qty.value = newVal;
}

$(document).ready(function() {
    $('#add-to-wishlist').click(function(e) {
        e.preventDefault();
        let productId = $(this).data('id');
        let btn = $(this);
        $.post('ajax_wishlist.php', { product_id: productId }, function(response) {
            if(response.status === 'success') {
                btn.find('i').removeClass('fa-heart-o').addClass('fa-heart').css('color', 'red');
            }
            alert(response.message);
        }, 'json');
    });
});
</script>

<?php include 'includes/footer.php'; ?>