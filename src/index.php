<?php
// Bắt buộc gọi header đầu tiên
require_once 'includes/header.php';
?>

<div class="banner">
    <div>
        </div>
</div>

<div class="section">
    <div class="section-header">
        <p>MỚI RA MẮT</p>
        <h2>New Arrivals</h2>
    </div>
    <div class="arrival-grid" id="newArrivals">
        </div>
</div>

<div class="section bg-be">
    <div class="section-header">
        <p>ĐƯỢC YÊU THÍCH NHẤT</p>
        <h2>Best Sellers</h2>
    </div>
    <div class="grid-slider" id="bestSeller">
        </div>
    <div class="dots-container" id="bestSellerDots">
        <span class="dot active" onclick="scrollToProduct(0)"></span>
        <span class="dot" onclick="scrollToProduct(1)"></span>
    </div>
</div>

<div class="section">
    <div class="section-header">
        <p>DANH MỤC</p>
        <h2>Shop by Category</h2>
    </div>
    <div class="category-grid">
        <div class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7ne96vcjmiu46@resize_w900_nl.webp" alt="Áo thun">
            <div class="category-overlay">Áo thun</div>
        </div>
        <div class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mitmevxbal1j0b@resize_w900_nl.webp" alt="Áo khoác">
            <div class="category-overlay">Áo khoác</div>
        </div>
        <div class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg6a54vwzvv002@resize_w900_nl.webp" alt="Hoodie">
            <div class="category-overlay">Hoodie</div>
        </div>
        <div class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfxc3xhxoop9ed@resize_w900_nl.webp" alt="Quần">
            <div class="category-overlay">Quần</div>
        </div>
        <div class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-me8igxycndhj04@resize_w900_nl.webp" alt="Áo sơ mi">
            <div class="category-overlay">Áo sơ mi</div>
        </div>
    </div>
</div>

<?php
// Bắt buộc gọi footer cuối cùng
require_once 'includes/footer.php';
?>