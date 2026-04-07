<?php
require_once 'config/database.php';
include 'includes/header.php';

// Lấy danh sách sản phẩm đang giảm giá (sale_price < original_price)
$sql_promo = "SELECT p.*, v.original_price, v.sale_price 
              FROM Products p 
              JOIN Product_Variants v ON p.product_id = v.product_id 
              WHERE v.sale_price < v.original_price AND p.status = 1
              GROUP BY p.product_id 
              ORDER BY (v.original_price - v.sale_price) DESC";

$stmt = $conn->prepare($sql_promo);
$stmt->execute();
$promo_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container">
    <div class="promo-banner">
        <div class="promo-content">
            <p class="promo-sub">SPECIAL OFFER</p>
            <h1>FLASH SALE</h1>
            <div id="countdown">
                <div class="time-box"><span id="days">00</span><p>Ngày</p></div>
                <div class="time-box"><span id="hours">00</span><p>Giờ</p></div>
                <div class="time-box"><span id="minutes">00</span><p>Phút</p></div>
                <div class="time-box"><span id="seconds">00</span><p>Giây</p></div>
            </div>
            <a href="#sale-list" class="btn-shop-now">SĂN DEAL NGAY</a>
        </div>
    </div>

    <div id="sale-list" class="section-header" style="text-align: center; margin-top: 50px;">
        <p>DANH SÁCH GIẢM GIÁ</p>
        <h2>HOT DEALS FOR YOU</h2>
    </div>

    <div class="product-grid">
        <?php foreach ($promo_products as $p): ?>
            <div class="product-card">
    <a href="product_detail.php?id=<?php echo $p['product_id']; ?>">
        <div class="img-wrapper">
            <img src="<?php echo $p['image']; ?>" alt="">
            
            <span class="badge-sale">SALE</span>
        </div>
        
        <div class="product-info">
            <h3 class="product-name"><?php echo $p['name']; ?></h3>
            <div class="product-meta">
                <span class="product-stars">★★★★★</span>
                <span class="product-sold">| Đã bán <?php echo $p['sold_count']; ?></span>
            </div>

            <div class="price-container">
                <span class="current-price"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>đ</span>
                <span class="old-price-strike"><?php echo number_format($p['original_price'], 0, ',', '.'); ?>đ</span>
            </div>
        </div>
    </a>
</div>
        <?php endforeach; ?>
    </div>
</main>

<script>
// JavaScript làm đồng hồ đếm ngược (Kết thúc vào cuối ngày hôm nay)
function updateCountdown() {
    const now = new Date();
    const endOfDay = new Date();
    endOfDay.setHours(23, 59, 59, 999);

    const diff = endOfDay - now;

    const h = Math.floor((diff / (1000 * 60 * 60)) % 24);
    const m = Math.floor((diff / 1000 / 60) % 60);
    const s = Math.floor((diff / 1000) % 60);

    document.getElementById('hours').innerText = h < 10 ? '0'+h : h;
    document.getElementById('minutes').innerText = m < 10 ? '0'+m : m;
    document.getElementById('seconds').innerText = s < 10 ? '0'+s : s;
}
setInterval(updateCountdown, 1000);
updateCountdown();
</script>

<?php include 'includes/footer.php'; ?>