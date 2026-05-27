<?php
// 1. Phải kết nối Database đầu tiên để có biến $conn
require_once 'config/database.php';

// 2. Gọi header
require_once 'includes/header.php';

// --- PHẦN LOGIC LẤY DỮ LIỆU ---

// Lấy 4 sản phẩm mới nhất (New Arrivals)
$sql_new = "SELECT p.*, v.original_price, v.sale_price 
            FROM products p 
            LEFT JOIN product_variants v ON p.product_id = v.product_id 
            WHERE p.status = 1
            GROUP BY p.product_id
            ORDER BY p.product_id DESC 
            LIMIT 4";
$stmt_new = $conn->prepare($sql_new);
$stmt_new->execute();
$new_arrivals = $stmt_new->fetchAll(PDO::FETCH_ASSOC);

// Lấy 4 sản phẩm bán chạy nhất (Best Sellers)
$sql_best = "SELECT p.*, v.original_price, v.sale_price 
             FROM products p 
             LEFT JOIN product_variants v ON p.product_id = v.product_id 
             WHERE p.status = 1
             GROUP BY p.product_id
             ORDER BY p.sold_count DESC 
             LIMIT 4";
$stmt_best = $conn->prepare($sql_best);
$stmt_best->execute();
$best_sellers = $stmt_best->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="banner">
    <div class="banner-content" style="text-align: center; padding: 100px 0;">
        </div>
</div>

<div class="section main-section-card">
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

<div class="section bg-be main-section-card">
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

<div class="section main-section-card">
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

<div class="section bg-be main-section-card" id="recent-views-section" style="display: none;">
    <div class="section-header">
        <p>XEM GẦN ĐÂY</p>
        <h2>Recently Viewed</h2>
    </div>
    <div class="product-grid" id="recent-views-grid">
        <!-- Will be populated dynamically via AJAX -->
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const recentSection = document.getElementById("recent-views-section");
        const recentGrid = document.getElementById("recent-views-grid");
        let lastProductIds = ""; // Store last rendered product IDs to prevent blinking

        function fetchRecentViews() {
            fetch("views/get_recent_views.php")
                .then(response => response.json())
                .then(result => {
                    if (result.status === "success" && result.data && result.data.length > 0) {
                        // Check if the products list has actually changed
                        const currentProductIds = result.data.map(item => item.product_id).join(',');
                        if (currentProductIds === lastProductIds) {
                            return; // No change, do not re-render to avoid visual blinking
                        }
                        lastProductIds = currentProductIds;

                        let html = "";
                        result.data.forEach((item, index) => {
                            const priceFormatted = new Intl.NumberFormat('vi-VN').format(item.original_price) + 'đ';
                            // Apply col-md-3 and fade-in-up with a staggered transition delay
                            html += `
                                <div class="product-card col-md-3 fade-in-up" style="transition-delay: ${index * 50}ms;">
                                    <a href="product_detail.php?id=${item.product_id}">
                                        <div class="img-wrapper">
                                            <img src="${item.image}" alt="${item.name}">
                                        </div>
                                        <div class="product-info">
                                            <h3 class="product-name">${item.name}</h3>
                                            <div class="product-meta">
                                                <span class="product-stars">★★★★★</span>
                                                <span class="product-sold">| Đã bán ${item.sold_count}</span>
                                            </div>
                                            <p class="price">${priceFormatted}</p>
                                        </div>
                                    </a>
                                </div>
                            `;
                        });
                        
                        // Render elements in inactive state
                        recentGrid.innerHTML = html;
                        recentSection.style.display = "block";
                        
                        // Trigger transition with slight delay so browser applies styles and animations render smoothly
                        setTimeout(() => {
                            const cards = recentGrid.querySelectorAll('.product-card');
                            cards.forEach(card => card.classList.add('active'));
                        }, 50);
                    } else {
                        recentSection.style.display = "none";
                        lastProductIds = "";
                    }
                })
                .catch(error => {
                    console.error("Error fetching recently viewed products:", error);
                });
        }

        // Fetch immediately on load
        fetchRecentViews();

        // Poll every 5 seconds (5000ms)
        setInterval(fetchRecentViews, 5000);
    });
</script>

<style>
    /* Smooth slide-up fade animation for live product cards */
    .fade-in-up {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .fade-in-up.active {
        opacity: 1;
        transform: translateY(0);
    }

    /* Concept layout compatible with bootstrap col-md-3 grids */
    #recent-views-grid.product-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 30px !important;
    }

    @media (max-width: 768px) {
        #recent-views-grid.product-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 15px !important;
        }
    }

    /* ============================================================
        CSS FIX ĐỒNG BỘ DARKMODE TRANG CHỦ NTK FASHION
    ============================================================ */
    .banner {
        background-color: #f4f4f4; /* Nền mặc định Lightmode */
        transition: background-color 0.3s ease;
    }

    /* Ép đồng bộ nền tối sâu tuyệt đối khi bật Dark Mode */
    body.dark-mode, 
    body.dark-mode .main-content, 
    body.dark-mode .banner, 
    body.dark-mode .main-section-card {
        background-color: #121212 !important;
    }

    body.dark-mode .bg-be {
        background-color: #1a1a1a !important; /* Biến vùng Best Seller thành xám đen sang trọng */
    }

    body.dark-mode .section-header h2, 
    body.dark-mode .section-header p {
        color: #ffffff !important;
    }

    /* Chatbox được hiển thị toàn cục từ footer.php */

<?php
require_once 'includes/footer.php';
?>
