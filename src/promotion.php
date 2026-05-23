<?php
require_once 'config/database.php';
include 'includes/header.php';

// Lấy danh sách sản phẩm đang giảm giá (sale_price < original_price)
$sql_promo = "SELECT p.*, v.original_price, v.sale_price 
              FROM products p 
              JOIN product_variants v ON p.product_id = v.product_id 
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

<style>
/* Cải thiện hiển thị cho Dark Mode */
body.dark-mode {
    background-color: #121212 !important;
}

body.dark-mode .promo-banner {
    background-color: #1a1a1a !important; /* Nền banner đậm hơn */
    border-bottom: 1px solid #333;
}

body.dark-mode h1, body.dark-mode h2 {
    color: #ffffff !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5); /* Tạo độ sâu cho chữ */
}

body.dark-mode .promo-sub {
    color: #f1c40f !important; /* Màu vàng Gold cho chữ SPECIAL OFFER */
    font-weight: bold;
}

body.dark-mode .time-box {
    background: #252525 !important;
    color: #fff !important;
    border: 1px solid #444;
}

body.dark-mode .product-card {
    background: #1e1e1e !important;
    border: 1px solid #333 !important;
}

body.dark-mode .product-name {
    color: #ffffff !important;
}

body.dark-mode .current-price {
    color: #f1c40f !important; /* Giá nổi bật hơn */
    font-weight: bold;
}

body.dark-mode .old-price-strike {
    color: #888 !important;
}

/* Hiệu ứng hover cho thẻ sản phẩm */
body.dark-mode .product-card:hover {
    border-color: #f1c40f !important;
    box-shadow: 0 4px 12px rgba(241, 196, 15, 0.2);
}
</style>

<?php include 'includes/footer.php'; ?>