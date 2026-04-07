<?php
require_once 'config/database.php';
include 'includes/header.php';

$product_id = isset($_GET['id']) ? $_GET['id'] : '';

if ($product_id) {
    // 1. Lấy thông tin sản phẩm (bao gồm cả rating và lượt bán từ bảng Products)
    $sql_prod = "SELECT p.*, c.name as category_name 
                 FROM Products p 
                 JOIN Categories c ON p.category_id = c.category_id 
                 WHERE p.product_id = :id";
    $stmt = $conn->prepare($sql_prod);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Lấy biến thể (Màu, Size, Giá, Tồn kho)
    $sql_variants = "SELECT * FROM Product_Variants WHERE product_id = :id AND is_active = 1";
    $stmt_v = $conn->prepare($sql_variants);
    $stmt_v->bindParam(':id', $product_id);
    $stmt_v->execute();
    $variants = $stmt_v->fetchAll(PDO::FETCH_ASSOC);

    $first_v = $variants[0] ?? null;
}
?>

<main class="container">
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
                    $rating = $product['avg_rating']; // Lấy từ cột avg_rating
                    for($i=1; $i<=5; $i++) echo ($i <= $rating) ? '★' : '☆';
                    ?>
                </span> 
                <span class="rating-text"><?php echo $product['avg_rating']; ?> (<?php echo $product['total_reviews']; ?> đánh giá)</span> | 
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
                    <span class="stock-info"><?php echo $first_v['stock']; ?> sản phẩm có sẵn</span>
                </div>
            </div>

            <div class="detail-actions">
                <button class="btn-add-cart"><i class="fa fa-shopping-bag"></i> Thêm vào giỏ</button>
                <button class="btn-buy-now">Mua ngay</button>
                <button class="btn-wishlist" id="add-to-wishlist" data-id="<?php echo $product['product_id']; ?>">
        <i class="fa fa-heart-o"></i>
            </div>
            
            <div class="detail-description">
                <p class="option-label">Mô tả sản phẩm:</p>
                <p class="desc-text"><?php echo nl2br($product['description']); ?></p>
            </div>
        </div>
    </div>
</main>

<script>
function changeQty(amt) {
    let qty = document.getElementById('quantity');
    let newVal = parseInt(qty.value) + amt;
    if (newVal >= 1) qty.value = newVal;
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#add-to-wishlist').click(function(e) {
        e.preventDefault();
        let productId = $(this).data('id');
        let btn = $(this);

        $.ajax({
            url: 'ajax_wishlist.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    // Đổi màu trái tim thành đỏ khi thành công
                    btn.find('i').removeClass('fa-heart-o').addClass('fa-heart').css('color', 'red');
                    alert(response.message);
                } else if(response.status === 'error') {
                    alert(response.message);
                    // Có thể chuyển hướng đến trang login nếu chưa đăng nhập
                    // window.location.href = 'login.php';
                } else {
                    alert(response.message);
                }
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>