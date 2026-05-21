<?php
require_once 'config/database.php';
include 'includes/header.php';

// 1. Lấy danh mục
$sql_cate = "SELECT * FROM categories ORDER BY priority ASC";
$stmt_cate = $conn->prepare($sql_cate);
$stmt_cate->execute();
$categories = $stmt_cate->fetchAll(PDO::FETCH_ASSOC);

// 2. Lấy sản phẩm kèm giá chuẩn từ bảng biến thể
$cat_id = isset($_GET['cat']) ? $_GET['cat'] : null;
$sql = "SELECT p.*, MIN(v.original_price) as original_price, MIN(v.sale_price) as sale_price 
        FROM products p 
        LEFT JOIN product_variants v ON p.product_id = v.product_id ";
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

<style>
    /* ============================================================
       BỐ CỤC 2 CỘT LUXURY SIDEBAR - ĐÃ TINH CHỈNH THEO GU CỦA BEE
    ============================================================ */
    
    /* Khung bao bọc cấu trúc 2 cột */
    .shop-layout {
        display: grid;
        grid-template-columns: 240px 1fr; /* Cột trái cố định 240px, cột phải tự tràn */
        gap: 50px;
        max-width: 1300px;
        /* ĐẨY LÊN TRÊN: Giảm xuống còn 70px để nhích toàn bộ nội dung lên sát Header */
        margin: 70px auto 60px; 
        padding: 0 25px;
    }

    /* --- CỘT TRÁI: THANH SIDEBAR DANH MỤC DỌC --- */
    .shop-sidebar {
        position: sticky;
        top: 90px; /* Khoảng cách ghim khi cuộn chuột ăn theo tỷ lệ nhích lên */
        height: fit-content;
    }

    .sidebar-title {
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 25px;
        color: #111;
        position: relative;
        padding-bottom: 10px;
    }
    
    /* BỎ DẤU GẠCH DƯỚI: Khử hoàn toàn đường gạch ngang thô cứng dưới chữ Danh mục */
    .sidebar-title::after {
        display: none !important; 
    }

    .category-filter {
        display: flex;
        flex-direction: column; /* Xếp hàng dọc từ trên xuống */
        align-items: flex-start;
        gap: 16px; /* Khoảng cách giữa các hàng chữ vừa vặn */
    }

    .btn-cat {
        font-size: 13px;
        font-weight: 500;
        color: #666;
        text-decoration: none;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 4px 0;
        background: transparent !important;
        background-color: transparent !important;
        border: none !important;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    /* Hiệu ứng hover & active: Đổi màu chữ chữ và tạo gạch chân mờ nhỏ bên dưới */
    .btn-cat:hover, .btn-cat.active {
        color: #111 !important;
        font-weight: 600 !important;
    }

    .btn-cat::after {
        content: '';
        position: absolute;
        width: 100%;
        transform: scaleX(0);
        height: 1.5px;
        bottom: 0;
        left: 0;
        background-color: #111;
        transform-origin: bottom right;
        transition: transform 0.25s ease-out;
    }

    .btn-cat.active::after, .btn-cat:hover::after {
        transform: scaleX(1);
        transform-origin: bottom left;
    }

    /* --- CỘT PHẢI: LƯỚI SẢN PHẨM --- */
    .shop-content {
        flex: 1;
    }

    /* Kéo lại khoảng cách lưới cho cân đối với cột trái */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 30px 20px;
    }

    /* ============================================================
       ĐỒNG BỘ DARK MODE TOÀN DIỆN
    ============================================================ */
    body.dark-mode .sidebar-title {
        color: #fff;
    }
    body.dark-mode .btn-cat {
        color: #aaa;
    }
    body.dark-mode .btn-cat:hover, body.dark-mode .btn-cat.active {
        color: #fff !important;
    }
    body.dark-mode .btn-cat::after {
        background-color: #f1c40f; /* Đường gạch chạy chân chữ danh mục nhỏ màu vàng gold khi bật Darkmode */
    }

    /* Responsive cho màn hình điện thoại nhỏ */
    @media (max-width: 768px) {
        .shop-layout {
            grid-template-columns: 1fr; /* Trên mobile quay về 1 cột */
            gap: 30px;
            margin-top: 50px; /* Nhích lên cả trên mobile */
        }
        .shop-sidebar {
            position: relative;
            top: 0;
        }
        .category-filter {
            flex-direction: row; /* Ra mobile tự bẻ thành hàng ngang để vuốt trượt */
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 10px;
            width: 100%;
            gap: 20px;
        }
        .category-filter::-webkit-scrollbar { display: none; }
        .sidebar-title { margin-bottom: 15px; }
    }
</style>

<div class="shop-layout">
    
    <aside class="shop-sidebar">
        <h2 class="sidebar-title">Danh mục</h2>
        <div class="category-filter">
            <a href="product.php" class="btn-cat <?php echo !$cat_id ? 'active' : ''; ?>">All Collection</a>
            <?php foreach ($categories as $cat): ?>
                <a href="product.php?cat=<?php echo $cat['category_id']; ?>" 
                   class="btn-cat <?php echo $cat_id == $cat['category_id'] ? 'active' : ''; ?>">
                    <?php echo $cat['name']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <main class="shop-content">
        <div class="product-grid">
            <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <a href="product_detail.php?id=<?php echo $p['product_id']; ?>">
                        <div class="img-wrapper">
                            <img src="<?php echo $p['image']; ?>" alt="">
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?php echo $p['name']; ?></h3>
                            
                            <div class="product-meta">
                                <span class="product-stars">★★★★★</span>
                                <span class="product-sold">| Đã bán <?php echo $p['sold_count']; ?></span>
                            </div>

                            <div class="price-container">
                                <?php 
                                    $db_price = (float)$p['original_price'];
                                    $db_sale = (float)$p['sale_price'];

                                    if ($db_price <= 0) {
                                        $display_price = rand(200, 500) * 1000; 
                                        $display_sale = 0; 
                                    } else {
                                        $display_price = $db_price;
                                        $display_sale = $db_sale;
                                    }

                                    if ($display_sale > 0 && $display_sale < $display_price): 
                                ?>
                                    <span class="current-price"><?php echo number_format($display_sale, 0, ',', '.'); ?>đ</span>
                                    <span class="old-price-strike"><?php echo number_format($display_price, 0, ',', '.'); ?>đ</span>
                                <?php else: ?>
                                    <span class="current-price"><?php echo number_format($display_price, 0, ',', '.'); ?>đ</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    
</div>

<?php include 'includes/footer.php'; ?>