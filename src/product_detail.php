<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 1. Kết nối Database
require_once 'config/database.php';

// 2. Lấy ID sản phẩm từ URL
$product_id = $_GET['id'] ?? '';

$product = null;
$variants = [];
$related_products = [];
$root_reviews = [];
$first_v = null;

$user_id_session = $_SESSION['user_id'] ?? null;
$user_role_session = isset($_SESSION['role']) ? intval($_SESSION['role']) : 0; // 1: Admin, 0: Khách

if ($product_id) {
    // 3. Lấy thông tin sản phẩm chính
    $sql_prod = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 JOIN categories c ON p.category_id = c.category_id 
                 WHERE p.product_id = :id";
    $stmt = $conn->prepare($sql_prod);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Log to recently viewed list (limit to top 20 rows)
        try {
            $stmt_rv = $conn->prepare("INSERT INTO recent_views (product_id, viewed_at) VALUES (:pid, NOW()) ON DUPLICATE KEY UPDATE viewed_at = NOW()");
            $stmt_rv->execute(['pid' => $product_id]);

            $conn->exec("DELETE FROM recent_views WHERE product_id NOT IN (
                SELECT product_id FROM (
                    SELECT product_id FROM recent_views ORDER BY viewed_at DESC LIMIT 20
                ) as tmp
            )");
        } catch (PDOException $e) {
            // Fail silently
        }

        // 4. Lấy Biến thể (Màu, Size, Giá, Tồn kho)
        $sql_variants = "SELECT pv.*, 
                            (SELECT fs.flash_sale_price FROM flash_sales fs WHERE fs.variant_id = pv.variant_id AND fs.status = 1 AND fs.sale_date = CURRENT_DATE() LIMIT 1) as flash_sale_price
                         FROM product_variants pv 
                         WHERE pv.product_id = :id AND pv.is_active = 1";
        $stmt_v = $conn->prepare($sql_variants);
        $stmt_v->bindParam(':id', $product_id);
        $stmt_v->execute();
        $variants = $stmt_v->fetchAll(PDO::FETCH_ASSOC);
        $first_v = $variants[0] ?? null;

        // 5. Lấy Sản phẩm liên quan (Cùng Category, trừ món hiện tại)
        $sql_related = "SELECT p.*, v.original_price, v.sale_price 
                        FROM products p 
                        LEFT JOIN product_variants v ON p.product_id = v.product_id 
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

        // 6. TẢI DỮ LIỆU ĐÁNH GIÁ GỐC (parent_id IS NULL)
        $sql_reviews = "SELECT r.*, u.fullname, u.role,
                               (SELECT COUNT(*) FROM review_likes WHERE review_id = r.review_id) as total_likes
                        FROM reviews r 
                        LEFT JOIN users u ON r.user_id = u.user_id 
                        WHERE r.product_id = :prod_id AND r.parent_id IS NULL 
                        ORDER BY r.created_at DESC";
        $stmt_rev = $conn->prepare($sql_reviews);
        $stmt_rev->bindParam(':prod_id', $product_id);
        $stmt_rev->execute();
        $root_reviews = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);
    }
}

// 7. KIỂM TRA QUYỀN ĐƯỢC VIẾT ĐÁNH GIÁ GỐC (Đã mua và Hoàn thành = 3)
$can_user_review = false;
$has_reviewed = false;
if ($user_id_session && $product) {
    $sql_verify_buy = "SELECT COUNT(*) FROM orders o 
                       JOIN order_details od ON o.order_id = od.order_id 
                       WHERE o.user_id = :uid 
                         AND od.variant_id IN (SELECT variant_id FROM product_variants WHERE product_id = :pid) 
                         AND o.order_status = 3";
    $stmt_v_buy = $conn->prepare($sql_verify_buy);
    $stmt_v_buy->execute(['uid' => $user_id_session, 'pid' => $product_id]);
    if (intval($stmt_v_buy->fetchColumn()) > 0) {
        $can_user_review = true;
    }
    
    if ($can_user_review) {
        $sql_check_reviewed = "SELECT COUNT(*) FROM reviews WHERE user_id = :uid AND product_id = :pid AND parent_id IS NULL";
        $stmt_checked = $conn->prepare($sql_check_reviewed);
        $stmt_checked->execute(['uid' => $user_id_session, 'pid' => $product_id]);
        if (intval($stmt_checked->fetchColumn()) > 0) {
            $has_reviewed = true;
        }
    }
}

// 7.5 KIỂM TRA SẢN PHẨM ĐÃ CÓ TRONG DANH SÁCH YÊU THÍCH CHƯA
$is_in_wishlist = false;
if ($user_id_session && $product) {
    $stmt_wl = $conn->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = :uid AND product_id = :pid");
    $stmt_wl->execute(['uid' => $user_id_session, 'pid' => $product_id]);
    $is_in_wishlist = (intval($stmt_wl->fetchColumn()) > 0);
}

// 8. HÀM ĐỆ QUY HIỂN THỊ LUỒNG TƯƠNG TÁC CON
function renderReviewReplies($conn, $parent_id, $product_id, $user_id_session, $user_role_session) {
    $sql = "SELECT r.*, u.fullname, u.role,
                   (SELECT COUNT(*) FROM review_likes WHERE review_id = r.review_id) as total_likes
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.user_id 
            WHERE r.product_id = :pid AND r.parent_id = :parent 
            ORDER BY r.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['pid' => $product_id, 'parent' => $parent_id]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($replies)) return;

    echo '<div class="review-replies-list" style="margin-left: 45px; border-left: 2px solid #f5f1eb; padding-left: 15px; margin-top: 10px;">';
    foreach ($replies as $reply) {
        $isAdmin = ($reply['role'] == 1);
        $bg_color = $isAdmin ? '#f9f5f0' : '#fcfcfc';
        $border_left = $isAdmin ? '3px solid #a6825c' : '1px solid #eee';
        $label = $isAdmin ? '<span style="font-size:10px; background:#2f1c00; color:#fff; padding:1px 5px; border-radius:3px; margin-left:5px;">Quản trị viên</span>' : '';

        // Kiểm tra xem tài khoản hiện tại đã Like chưa
        $is_liked = false;
        if ($user_id_session) {
            $st_l = $conn->prepare("SELECT COUNT(*) FROM review_likes WHERE review_id = :rid AND user_id = :uid");
            $st_l->execute(['rid' => $reply['review_id'], 'uid' => $user_id_session]);
            $is_liked = (intval($st_l->fetchColumn()) > 0);
        }

        echo '<div class="review-item reply-item" style="background:'.$bg_color.'; border-left:'.$border_left.'; padding:12px; margin-bottom:10px; border-radius:4px;">';
        echo '  <div style="display:flex; justify-content:space-between; font-size:13px;">';
        echo '      <b>' . htmlspecialchars($reply['fullname'] ?? 'Khách hàng') . $label . '</b>';
        echo '      <small style="color:#aaa;">' . date('d/m/Y H:i', strtotime($reply['created_at'])) . '</small>';
        echo '  </div>';
        echo '  <p style="font-size:13.5px; color:#444; margin:5px 0;">' . htmlspecialchars($reply['comment']) . '</p>';
        
        echo '  <div style="display:flex; gap:15px; align-items:center; margin-top:5px;">';
        echo '      <button type="button" onclick="toggleLike('.$reply['review_id'].')" id="like-btn-'.$reply['review_id'].'" style="background:none; border:none; color:'.($is_liked ? '#e63946':'#888').'; font-size:12px; cursor:pointer; padding:0; display:flex; align-items:center; gap:4px;"><i class="'.($is_liked ? 'fa-solid':'fa-regular').' fa-thumbs-up"></i> Thích (<span class="like-count">'.$reply['total_likes'].'</span>)</button>';
        
        // CHỈ ADMIN MỚI HIỆN NÚT PHẢN HỒI
        if ($user_role_session === 1) {
            echo '  <button type="button" class="reply-trigger-btn" onclick="openReplyForm('.$reply['review_id'].')" style="background:none; border:none; color:#a6825c; font-size:12px; cursor:pointer; padding:0; font-weight:600;"><i class="fa-regular fa-comment-dots"></i> Phản hồi</button>';
        }
        echo '  </div>';
        echo '  <div class="reply-form-container" id="reply-form-'.$reply['review_id'].'" style="display:none; margin-top:10px;"></div>';
        
        renderReviewReplies($conn, $reply['review_id'], $product_id, $user_id_session, $user_role_session);
        echo '</div>';
    }
    echo '</div>';
}

// Gọi header
include 'includes/header.php';
?>

<style>
    .breadcrumb { font-size: 14px; color: #888; margin-bottom: 25px; }
    .breadcrumb a { color: #666; text-decoration: none; transition: color 0.2s ease; font-weight: 500; }
    .breadcrumb a:hover { color: #111; text-decoration: underline; }
    .option-list { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
    .btn-option { padding: 10px 24px; border: 1px solid #ddd !important; background-color: #ffffff !important; color: #333333 !important; font-size: 14px; font-weight: 500; cursor: pointer; border-radius: 4px; transition: all 0.2s ease; outline: none; }
    .btn-option:hover { border-color: #888888 !important; }
    .btn-option.active { background-color: #ffffff !important; color: #000000 !important; border: 2px solid #000000 !important; font-weight: 700 !important; padding: 9px 23px; }
    .btn-wishlist { background-color: #ffffff !important; border: 1px solid #2f1c00 !important; color: #2f1c00 !important; width: 50px; height: 50px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; cursor: pointer; font-size: 20px; transition: all 0.2s ease; outline: none; }
    .btn-wishlist:hover { background-color: #fdfaf6 !important; transform: scale(1.05); }
    .btn-wishlist.liked { border-color: #e63946 !important; }
    .btn-wishlist.liked i { color: #e63946 !important; }
    
    /* ===== DARK MODE - ĐỒNG BỘ ĐEN HẾT ===== */
    body.dark-mode {
        background-color: #0a0a0a !important;
        color: #ffffff !important;
    }
    
    body.dark-mode main,
    body.dark-mode .container {
        background-color: #0a0a0a !important;
        color: #ffffff !important;
    }
    
    body.dark-mode .breadcrumb { color: #aaa !important; }
    body.dark-mode .breadcrumb a { color: #aaa !important; }
    body.dark-mode .breadcrumb a:hover { color: #fff !important; }
    body.dark-mode .breadcrumb b { color: #ffffff !important; }
    
    /* Title & Meta */
    body.dark-mode .detail-title { color: #ffffff !important; }
    body.dark-mode .detail-meta { color: #dddddd !important; }
    body.dark-mode .rating-text { color: #dddddd !important; }
    body.dark-mode .sold-count { color: #dddddd !important; }
    
    /* Price Box */
    body.dark-mode .detail-price-box {
        background-color: #1a1a1a !important;
        border: 1px solid #333333 !important;
    }
    body.dark-mode .detail-sale-price { color: #ffffff !important; }
    body.dark-mode .detail-old-price { color: #999 !important; }
    body.dark-mode .discount-tag { background-color: #e63946 !important; color: #ffffff !important; }
    
    /* Options */
    body.dark-mode .option-label { color: #ffffff !important; }
    body.dark-mode .btn-option { background-color: #1a1a1a !important; border-color: #333333 !important; color: #ffffff !important; }
    body.dark-mode .btn-option:hover { border-color: #666 !important; }
    body.dark-mode .btn-option.active { background-color: #1a1a1a !important; color: #ffffff !important; border: 2px solid #ffffff !important; }
    
    /* Stock Info */
    body.dark-mode .stock-info { color: #aaa !important; }
    
    /* Quantity Selector */
    body.dark-mode .quantity-selector {
        background-color: #1a1a1a !important;
        border-color: #333333 !important;
    }
    body.dark-mode .quantity-selector input { 
        background-color: #1a1a1a !important; 
        color: #ffffff !important; 
        border-color: #333333 !important; 
    }
    body.dark-mode .qty-btn {
        background-color: transparent !important;
        color: #ffffff !important;
        border-color: #333333 !important;
    }
    
    /* Buttons */
    body.dark-mode .btn-add-to-cart { background-color: #2f1c00 !important; color: #ffffff !important; }
    body.dark-mode .btn-buy-now { background-color: #444444 !important; color: #ffffff !important; }
    body.dark-mode .btn-wishlist { background-color: #1a1a1a !important; border-color: #333333 !important; color: #ffffff !important; }
    
    /* Description */
    body.dark-mode .desc-text { color: #dddddd !important; }
    
    /* Feedback Section */
    body.dark-mode .product-feedback { 
        border-top-color: #333333 !important;
        background-color: #0a0a0a !important;
    }
    body.dark-mode .product-feedback h3 { color: #f1c40f !important; }
    
    /* Review Form */
    body.dark-mode .main-review-form {
        background-color: #1a1a1a !important;
        border: 1px solid #333333 !important;
    }
    body.dark-mode .main-review-form p { color: #ffffff !important; }
    body.dark-mode .main-review-form label { color: #dddddd !important; }
    body.dark-mode .main-review-form textarea {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border-color: #333333 !important;
    }
    body.dark-mode .main-review-form textarea::placeholder { color: #666 !important; }
    body.dark-mode .main-review-form select {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border-color: #333333 !important;
    }
    body.dark-mode .main-review-form select option {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
    }
    body.dark-mode .main-review-form input[type="file"] {
        background-color: #1a1a1a !important;
        color: #aaa !important;
        border-color: #333333 !important;
    }
    body.dark-mode .main-review-form button {
        background-color: #2f1c00 !important;
        color: #ffffff !important;
    }
    
    /* No Review Info Box */
    body.dark-mode div[style*="background: #fffbf0"] {
        background-color: #1a1a1a !important;
        border-color: #333333 !important;
        color: #ffeb7f !important;
    }
    
    /* Review List */
    body.dark-mode .review-list { color: #ffffff !important; }
    body.dark-mode .review-list p { color: #dddddd !important; }
    
    /* Review Item */
    body.dark-mode .review-item {
        background-color: #1a1a1a !important;
        border-color: #333333 !important;
    }
    body.dark-mode .review-item b { color: #ffffff !important; }
    body.dark-mode .review-item small { color: #999 !important; }
    body.dark-mode .review-item p { color: #dddddd !important; }
    body.dark-mode .review-item span { color: #ffc107 !important; }
    body.dark-mode .review-item img { border-color: #333333 !important; }
    
    /* Reply Items */
    body.dark-mode .reply-item {
        background-color: #1a1a1a !important;
        border-left-color: #a6825c !important;
        border-color: #333333 !important;
    }
    body.dark-mode .review-replies-list { 
        border-left-color: #333333 !important; 
    }
    
    /* Reply Form */
    body.dark-mode .reply-form-container {
        background-color: #1a1a1a !important;
        border: 1px solid #333333 !important;
    }
    body.dark-mode .reply-form-container textarea {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border-color: #333333 !important;
    }
    body.dark-mode .reply-form-container textarea::placeholder { color: #666 !important; }
    body.dark-mode .reply-form-container button {
        background-color: #2f1c00 !important;
        color: #ffffff !important;
    }
    body.dark-mode .reply-form-container div[style*="background"] {
        background-color: #1a1a1a !important;
        border-color: #333333 !important;
    }
    
    /* Like/Reply Buttons */
    body.dark-mode .reply-trigger-btn { color: #f1c40f !important; }
    
    /* Related Products */
    body.dark-mode .section-title { color: #ffffff !important; }
    body.dark-mode .product-card { background-color: #1a1a1a !important; }
    body.dark-mode .product-info { color: #ffffff !important; }
    body.dark-mode .product-name { color: #ffffff !important; }
    body.dark-mode .product-meta { color: #dddddd !important; }
    body.dark-mode .price { color: #ffc107 !important; }
    
    /* Inline Message */
    body.dark-mode #cart-inline-msg {
        background-color: #1a1a1a !important;
        border-color: #333333 !important;
        color: #ffffff !important;
    }
    
    /* All text elements in dark mode */
    body.dark-mode h1 { color: #ffffff !important; }
    body.dark-mode h2 { color: #ffffff !important; }
    body.dark-mode h3 { color: #ffffff !important; }
    body.dark-mode h4 { color: #ffffff !important; }
    body.dark-mode p { color: #dddddd !important; }
    
    /* Override ALL inline background styles in dark mode */
    body.dark-mode div[style*="background: #fffbf0"],
    body.dark-mode div[style*="background:#fcfcfc"],
    body.dark-mode div[style*="background:#fafafa"],
    body.dark-mode div[style*="background:#f9f5f0"],
    body.dark-mode div[style*="background:#fcfcfc"] {
        background-color: #1a1a1a !important;
        border-color: #333333 !important;
        color: #ffffff !important;
    }
    
    body.dark-mode .review-info-box {
        background-color: #1a1a1a !important;
        border-color: #333333 !important;
        color: #ffeb7f !important;
    }
    
    body.dark-mode .reply-form-box,
    body.dark-mode textarea {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border-color: #333333 !important;
    }
    
    body.dark-mode textarea::placeholder {
        color: #666 !important;
    }
    
    body.dark-mode .admin-label {
        background-color: #2f1c00 !important;
        color: #ffffff !important;
    }
</style>

<main class="container">
    <?php if ($product): ?>
        
        <nav class="breadcrumb">
            <a href="index.php">Trang chủ</a> / 
            <a href="product.php">Cửa hàng</a> / 
            <b><?php echo htmlspecialchars($product['name']); ?></b>
        </nav>

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
                        for ($i = 1; $i <= 5; $i++)
                            echo ($i <= $rating) ? '★' : '☆';
                        ?>
                    </span>
                    <span class="rating-text"><?php echo $rating; ?> (<?php echo count($root_reviews); ?> đánh giá)</span> |
                    <span class="sold-count">Đã bán <?php echo $product['sold_count']; ?></span>
                </div>

                <div class="detail-price-box">
                    <span class="detail-sale-price" id="detail-sale-price">
                        <?php if ($first_v):
                            $active_price = ($first_v['flash_sale_price'] !== null) ? $first_v['flash_sale_price'] : ($first_v['sale_price'] > 0 ? $first_v['sale_price'] : $first_v['original_price']);
                            echo number_format($active_price, 0, ',', '.') . 'đ';
                        endif; ?>
                    </span>
                    <?php 
                    $show_discount = false;
                    $sale_p = 0;
                    if ($first_v) {
                        if ($first_v['flash_sale_price'] !== null) {
                            $show_discount = true;
                            $sale_p = $first_v['flash_sale_price'];
                        } elseif ($first_v['sale_price'] < $first_v['original_price'] && $first_v['sale_price'] > 0) {
                            $show_discount = true;
                            $sale_p = $first_v['sale_price'];
                        }
                    }
                    if ($show_discount): ?>
                        <span class="detail-old-price" id="detail-old-price"><?php echo number_format($first_v['original_price'], 0, ',', '.'); ?>đ</span>
                        <span class="discount-tag" id="detail-discount">-<?php echo round((($first_v['original_price'] - $sale_p) / $first_v['original_price']) * 100); ?>%</span>
                    <?php else: ?>
                        <span class="detail-old-price" id="detail-old-price" style="display:none;"></span>
                        <span class="discount-tag" id="detail-discount" style="display:none;"></span>
                    <?php endif; ?>
                </div>

                <div class="detail-options">
                    <p class="option-label">Màu sắc: <strong id="selected-color"><?php echo $first_v['color'] ?? ''; ?></strong></p>
                    <div class="option-list" id="color-list">
                        <?php
                        $unique_colors = array_unique(array_column($variants, 'color'));
                        $first_color = $first_v['color'] ?? '';
                        foreach ($unique_colors as $color):
                            $active = ($color === $first_color) ? 'active' : '';
                            ?>
                            <button class="btn-option <?php echo $active; ?>"
                                data-color="<?php echo htmlspecialchars($color); ?>"
                                onclick="selectColor('<?php echo htmlspecialchars($color); ?>')">
                                <?php echo $color; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="detail-options">
                    <p class="option-label">Kích cỡ: <strong id="selected-size"><?php echo $first_v['size'] ?? ''; ?></strong></p>
                    <div class="option-list" id="size-list">
                        <?php
                        $first_size = $first_v['size'] ?? '';
                        $sizes_for_first_color = [];
                        foreach ($variants as $v) {
                            if ($v['color'] === $first_color)
                                $sizes_for_first_color[] = $v['size'];
                        }
                        foreach (array_unique($sizes_for_first_color) as $size):
                            $active = ($size === $first_size) ? 'active' : '';
                            ?>
                            <button class="btn-option <?php echo $active; ?>" data-size="<?php echo htmlspecialchars($size); ?>"
                                onclick="selectSize('<?php echo htmlspecialchars($size); ?>')">
                                <?php echo $size; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="detail-options">
                    <p class="option-label">Số lượng:</p>
                    <div class="quantity-wrapper">
                        <div class="quantity-selector">
                            <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                            <input type="text" id="quantity" value="1" readonly>
                            <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                        </div>
                        <span class="stock-info" id="stock-info"><?php echo $first_v['stock'] ?? 0; ?> sản phẩm có sẵn</span>
                    </div>
                </div>

                <div id="cart-inline-msg" style="display:none; margin: 10px 0; padding: 10px 14px; border-radius: 8px; font-size: 13px;"></div>

                <div class="detail-actions">
                    <button class="btn-add-to-cart" id="btn-add-cart" onclick="addToCart(false)">
                        <i class="fa fa-shopping-bag"></i> Thêm vào giỏ
                    </button>
                    <button class="btn-buy-now" id="btn-buy-now" onclick="addToCart(true)">
                        Mua ngay
                    </button>
                    <button class="btn-wishlist <?php echo $is_in_wishlist ? 'liked' : ''; ?>" id="add-to-wishlist" data-id="<?php echo $product['product_id']; ?>">
                        <i class="<?php echo $is_in_wishlist ? 'fa-solid fa-heart' : 'fa-regular fa-heart'; ?>"></i>
                    </button>
                </div>

                <div class="detail-description">
                    <p class="option-label">Mô tả sản phẩm:</p>
                    <p class="desc-text"><?php echo nl2br($product['description']); ?></p>
                </div>

                <div class="product-feedback" style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px;">
                    <h3 style="color: #a6825c; margin-bottom: 25px;">Đánh giá và tương tác từ khách hàng</h3>
                    
                    <?php if ($can_user_review && !$has_reviewed): ?>
                        <div class="main-review-form" style="margin-bottom: 30px; background: #fafafa; padding: 15px; border-radius: 6px; border: 1px solid #eee;">
                            <p style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Viết đánh giá của bạn:</p>
                            <textarea id="main_comment_text" style="width:100%; height:70px; padding:10px; border:1px solid #ddd; outline:none; resize:none;" placeholder="Chia sẻ cảm nhận về sản phẩm..."></textarea>
                            <div style="display:flex; gap:14px; flex-wrap:wrap; margin-top:10px;">
                                <div style="flex:1; min-width:180px;">
                                    <label style="font-size:13px; color:#666;">Số sao: </label>
                                    <select id="main_rating_val" style="padding:8px 10px; border:1px solid #ddd; border-radius:4px; width:100%;">
                                        <option value="5">5 ★</option>
                                        <option value="4">4 ★</option>
                                        <option value="3">3 ★</option>
                                        <option value="2">2 ★</option>
                                        <option value="1">1 ★</option>
                                    </select>
                                </div>
                                <div style="flex:1; min-width:260px;">
                                    <label style="font-size:13px; color:#666; display:block; margin-bottom:4px;">Hình ảnh (tùy chọn):</label>
                                    <input id="main_review_image" type="file" accept="image/*" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:13px;">
                                    <div id="main_review_image_preview" style="display:none; margin-top:10px;"><img src="" alt="Preview" style="max-width:100%; border-radius:6px; border:1px solid #eee;"></div>
                                </div>
                                <button type="button" onclick="submitReview(0)" style="background:#2f1c00; color:#fff; border:none; padding:12px 24px; border-radius:4px; cursor:pointer; font-weight:600; font-size:13px; white-space:nowrap;">Gửi Đánh Giá</button>
                            </div>
                        </div>
                    <?php elseif ($has_reviewed): ?>
                        <div style="background: #e8f5e9; color: #2e7d32; padding: 12px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 25px; border: 1px solid #c8e6c9;">
                            <i class="fa-solid fa-circle-check"></i> Cảm ơn bạn đã để lại đánh giá cho sản phẩm này!
                        </div>
                    <?php else: ?>
                        <div style="background: #fffbf0; color: #b8860b; padding: 12px 16px; border-radius: 6px; font-size: 13.5px; margin-bottom: 25px; border: 1px solid #f5e6b2;">
                            <i class="fa-solid fa-circle-info"></i> Chỉ những khách hàng đã mua và nhận sản phẩm thành công tại NTK Fashion mới có quyền để lại đánh giá.
                        </div>
                    <?php endif; ?>

                    <div class="review-list">
                        <?php if (empty($root_reviews)): ?>
                            <p style="color: #999; font-size:14px;">Chưa có đánh giá nào cho sản phẩm này.</p>
                        <?php else: ?>
                            <?php foreach ($root_reviews as $rev): 
                                $is_root_liked = false;
                                if ($user_id_session) {
                                    $st_rl = $conn->prepare("SELECT COUNT(*) FROM review_likes WHERE review_id = :rid AND user_id = :uid");
                                    $st_rl->execute(['rid' => $rev['review_id'], 'uid' => $user_id_session]);
                                    $is_root_liked = (intval($st_rl->fetchColumn()) > 0);
                                }
                            ?>
                                <div class="review-item root-item" style="margin-bottom: 20px; border-bottom: 1px solid #f5f1eb; padding-bottom: 15px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <b><?= htmlspecialchars($rev['fullname'] ?: 'Khách hàng ẩn danh') ?></b>
                                        <span style="color: #ffc107; font-size:13px;">
                                            <?php for ($i = 1; $i <= 5; $i++) echo ($i <= $rev['rating']) ? '★' : '☆'; ?>
                                            <small style="color: #999; margin-left:10px; font-family:sans-serif;"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></small>
                                        </span>
                                    </div>
                                    <p style="font-size: 14px; color: #333; margin: 8px 0;"><?= htmlspecialchars($rev['comment']) ?></p>
                                    <?php if (!empty($rev['image'])): ?>
                                        <div style="margin: 10px 0;">
                                            <img src="<?= htmlspecialchars($rev['image']) ?>" alt="Hình đánh giá" style="max-width: 190px; max-height: 190px; border-radius: 10px; border: 1px solid #eee; object-fit: cover;">
                                        </div>
                                    <?php endif; ?>
                                    <div style="display:flex; gap:15px; align-items:center; margin-top:5px;">
                                        <button type="button" onclick="toggleLike(<?= $rev['review_id'] ?>)" id="like-btn-<?= $rev['review_id'] ?>" style="background:none; border:none; color:<?= $is_root_liked ? '#e63946' : '#888' ?>; font-size:13px; cursor:pointer; padding:0; display:flex; align-items:center; gap:4px;"><i class="<?= $is_root_liked ? 'fa-solid' : 'fa-regular' ?> fa-thumbs-up"></i> Thích (<span class="like-count"><?= $rev['total_likes'] ?></span>)</button>
                                        
                                        <?php if ($user_role_session === 1): ?>
                                            <button type="button" class="reply-trigger-btn" onclick="openReplyForm(<?= $rev['review_id'] ?>)" style="background:none; border:none; color:#a6825c; font-size:13px; cursor:pointer; padding:0; font-weight:600;"><i class="fa-regular fa-comment-dots"></i> Phản hồi</button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="reply-form-container" id="reply-form-<?= $rev['review_id'] ?>" style="display:none; margin-top:10px;"></div>

                                    <?php renderReviewReplies($conn, $rev['review_id'], $product_id, $user_id_session, $user_role_session); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
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

<script>
    var allVariants = <?php echo json_encode($variants); ?>;
    var selectedColor = "<?php echo addslashes($first_v['color'] ?? ''); ?>";
    var selectedSize = "<?php echo addslashes($first_v['size'] ?? ''); ?>";
    var openReview = <?php echo isset($_GET['open_review']) ? 'true' : 'false'; ?>;

    document.addEventListener("DOMContentLoaded", function() {
        var stockSpan = document.querySelector('.stock-info');
        if(stockSpan && !stockSpan.id) {
            stockSpan.id = 'stock-info';
        }

        var mainImageInput = document.getElementById('main_review_image');
        if (mainImageInput) {
            mainImageInput.addEventListener('change', function () {
                var file = this.files[0];
                var preview = document.querySelector('#main_review_image_preview img');
                if (file && preview) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        preview.src = e.target.result;
                        document.getElementById('main_review_image_preview').style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else if (preview) {
                    preview.src = '';
                    document.getElementById('main_review_image_preview').style.display = 'none';
                }
            });
        }

        // Nếu được gọi từ trang đơn hàng với open_review=1 thì cuộn xuống form đánh giá
        if (openReview) {
            var reviewForm = document.querySelector('.main-review-form');
            if (reviewForm) {
                reviewForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                var ta = document.getElementById('main_comment_text');
                if (ta) ta.focus();
            } else {
                // Nếu không có form (không đủ quyền), cuộn tới khu vực feedback để hiển thị thông báo
                var fb = document.querySelector('.product-feedback');
                if (fb) fb.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        <?php if (isset($_SESSION['cart_success_msg'])): ?>
        showInlineMsg("<?php echo htmlspecialchars($_SESSION['cart_success_msg'], ENT_QUOTES, 'UTF-8'); ?>", "success");
        <?php unset($_SESSION['cart_success_msg']); ?>
        <?php endif; ?>
    });

    function findVariant(color, size) {
        return allVariants.find(function (v) {
            return v.color === color && v.size === size;
        }) || null;
    }

    function updatePriceAndStock() {
        var v = findVariant(selectedColor, selectedSize);
        var stockInfoEl = document.getElementById('stock-info');
        
        if (!v) {
            if(stockInfoEl) stockInfoEl.textContent = 'Không có sẵn';
            document.getElementById('btn-add-cart').disabled = true;
            document.getElementById('btn-buy-now').disabled = true;
            return;
        }

        var flashSalePrice = parseFloat(v.flash_sale_price) || 0;
        var salePrice = flashSalePrice > 0 ? flashSalePrice : (parseFloat(v.sale_price) || 0);
        var originalPrice = parseFloat(v.original_price) || 0;
        var displayPrice = salePrice > 0 ? salePrice : originalPrice;

        document.getElementById('detail-sale-price').textContent = displayPrice.toLocaleString('vi-VN') + 'đ';

        var oldPriceEl = document.getElementById('detail-old-price');
        var discountEl = document.getElementById('detail-discount');

        if (salePrice > 0 && salePrice < originalPrice) {
            oldPriceEl.textContent = originalPrice.toLocaleString('vi-VN') + 'đ';
            oldPriceEl.style.display = '';
            var pct = Math.round((originalPrice - salePrice) / originalPrice * 100);
            discountEl.textContent = '-' + pct + '%';
            discountEl.style.display = '';
        } else {
            oldPriceEl.style.display = 'none';
            discountEl.style.display = 'none';
        }

        if(stockInfoEl) stockInfoEl.textContent = v.stock + ' sản phẩm có sẵn';
        document.getElementById('quantity').value = 1;

        var disabled = (v.stock < 1);
        document.getElementById('btn-add-cart').disabled = disabled;
        document.getElementById('btn-buy-now').disabled = disabled;
    }

    function selectColor(color) {
        selectedColor = color;
        document.getElementById('selected-color').textContent = color;

        document.querySelectorAll('#color-list .btn-option').forEach(function (b) {
            b.classList.toggle('active', b.dataset.color === color);
        });

        var sizesForColor = allVariants
            .filter(function (v) { return v.color === color; })
            .map(function (v) { return v.size; });
        var uniqueSizes = sizesForColor.filter(function (s, i) { return sizesForColor.indexOf(s) === i; });

        var sizeList = document.getElementById('size-list');
        sizeList.innerHTML = '';
        uniqueSizes.forEach(function (size) {
            var btn = document.createElement('button');
            btn.className = 'btn-option';
            btn.dataset.size = size;
            btn.textContent = size;
            btn.onclick = function () { selectSize(size); };
            sizeList.appendChild(btn);
        });

        if (uniqueSizes.length > 0) selectSize(uniqueSizes[0]);
    }

    function selectSize(size) {
        selectedSize = size;
        document.getElementById('selected-size').textContent = size;

        document.querySelectorAll('#size-list .btn-option').forEach(function (b) {
            b.classList.toggle('active', b.dataset.size === size);
        });

        updatePriceAndStock();
    }

    function changeQty(amt) {
        var v = findVariant(selectedColor, selectedSize);
        var max = v ? parseInt(v.stock) : 1;
        var inp = document.getElementById('quantity');
        var newVal = parseInt(inp.value) + amt;
        if (newVal < 1) newVal = 1;
        if (newVal > max) newVal = max;
        inp.value = newVal;
    }

    function addToCart(buyNow) {
        var v = findVariant(selectedColor, selectedSize);
        if (!v) {
            showInlineMsg('Vui lòng chọn màu sắc và kích cỡ!', 'warning');
            return;
        }

        var qty = parseInt(document.getElementById('quantity').value);
        if (qty < 1 || qty > v.stock) {
            showInlineMsg('Số lượng không hợp lệ!', 'warning');
            return;
        }

        var btn = buyNow ? document.getElementById('btn-buy-now') : document.getElementById('btn-add-cart');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: {
                action: 'add_to_cart',
                variant_id: v.variant_id,
                quantity: qty
            },
            success: function (res) {
                btn.disabled = false;
                if (buyNow) {
                    btn.innerHTML = 'Mua ngay';
                } else {
                    btn.innerHTML = '<i class="fa fa-shopping-bag"></i> Thêm vào giỏ';
                }

                var result = res.trim();
                if (result === 'success' || result === 'updated') {
                    if (buyNow) {
                        window.location.href = 'cart.php';
                    } else {
                        showInlineMsg('✓ Đã thêm vào giỏ hàng!', 'success');
                        updateCartCount();
                    }
                } else if (result === 'not_logged_in') {
                    window.location.href = 'views/login.php';
                } else if (result === 'out_of_stock') {
                    showInlineMsg('Sản phẩm đã hết hàng!', 'error');
                } else {
                    showInlineMsg('Có lỗi xảy ra, thử lại sau!', 'error');
                }
            },
            error: function () {
                btn.disabled = false;
                btn.innerHTML = buyNow ? 'Mua ngay' : '<i class="fa fa-shopping-bag"></i> Thêm vào giỏ';
                showInlineMsg('Không thể kết nối với máy chủ!', 'error');
            }
        });
    }

    function updateCartCount() {
        $.get('ajax_cart.php', { action: 'get_count' }, function (res) {
            var count = parseInt(res.trim());
            if (!isNaN(count)) {
                var badge = document.querySelector('.cart-count');
                if (badge) badge.textContent = count;
            }
        });
    }

    function showInlineMsg(msg, type) {
        var el = document.getElementById('cart-inline-msg');
        el.style.display = 'block';
        el.textContent = msg;
        el.style.background = type === 'success' ? '#eafbf0' : type === 'warning' ? '#fffbf0' : '#fff0f0';
        el.style.color = type === 'success' ? '#1e8449' : type === 'warning' ? '#b8860b' : '#c0392b';
        el.style.border = '1px solid ' + (type === 'success' ? '#a9dfbf' : type === 'warning' ? '#f5e6b2' : '#f5c6cb');

        clearTimeout(el._timer);
        el._timer = setTimeout(function () { el.style.display = 'none'; }, 4000);
    }

    $(document).ready(function () {
        var wishBtn = $('#add-to-wishlist');
        var productId = wishBtn.data('id');
        
        wishBtn.click(function (e) {
            e.preventDefault();
            var btn = $(this);

            $.ajax({
                url: 'ajax_wishlist.php',
                method: 'POST',
                data: { product_id: productId },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        btn.addClass('liked');
                        btn.find('i').removeClass('fa-regular').addClass('fa-solid');
                        showInlineMsg('Đã thêm vào danh sách yêu thích!', 'success');
                    } else if (response.status === 'info') {
                        btn.addClass('liked');
                        btn.find('i').removeClass('fa-regular').addClass('fa-solid');
                        showInlineMsg(response.message, 'success');
                    } else if (response.status === 'not_logged_in') {
                        window.location.href = response.redirect_url;
                    } else {
                        showInlineMsg(response.message, 'error');
                    }
                }
            });
        });
    });

    // ============================================================
    // LOGIC ĐIỀU KHIỂN CHUYỂN ĐỔI FORM VÀ XỬ LÝ LIKE, PHẢN HỒI
    // ============================================================
    function openReplyForm(reviewId) {
        const container = document.getElementById('reply-form-' + reviewId);
        if (container.style.display === 'block') {
            container.style.display = 'none'; container.innerHTML = ''; return;
        }
        document.querySelectorAll('.reply-form-container').forEach(el => { el.style.display = 'none'; el.innerHTML = ''; });
        
        container.innerHTML = `
            <div style="background:#fcfcfc; padding:10px; border:1.5px solid #e5e5e5; border-radius:4px; margin-top: 10px;">
                <textarea id="reply_text_\${reviewId}" style="width:100%; height:60px; padding:8px; border:1px solid #ddd; outline:none; resize:none; font-size:13.5px;" placeholder="Quản trị viên nhập phản hồi hệ thống..."></textarea>
                <div style="text-align:right; margin-top:8px;">
                    <button type="button" onclick="submitReview(\${reviewId})" style="background:#2f1c00; color:#fff; border:none; padding:6px 16px; border-radius:3px; cursor:pointer; font-size:12px; font-weight:600;">Gửi phản hồi</button>
                </div>
            </div>
        `;
        container.style.display = 'block';
    }

    function submitReview(parentId) {
        const productId = "<?= $product_id ?>";
        let comment = '';
        let rating = 5;
        let reviewImage = null;

        if (parentId === 0) {
            comment = document.getElementById('main_comment_text').value.trim();
            rating = document.getElementById('main_rating_val').value;
            reviewImage = document.getElementById('main_review_image').files[0] || null;
        } else {
            comment = document.getElementById('reply_text_' + parentId).value.trim();
        }

        if (!comment) { alert('Vui lòng nhập nội dung!'); return; }

        const formData = new FormData();
        formData.append('action', 'submit_comment');
        formData.append('product_id', productId);
        formData.append('parent_id', parentId > 0 ? parentId : '');
        formData.append('comment', comment);
        formData.append('rating', rating);
        if (reviewImage) {
            formData.append('review_image', reviewImage);
        }

        fetch('ajax_review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                if (res.points_earned && res.points_earned > 0) {
                    alert('Gửi đánh giá thành công! Bạn được cộng ' + res.points_earned + ' điểm Loyalty.');
                } else {
                    alert('Gửi đánh giá thành công!');
                }
                window.location.reload();
            } else {
                alert(res.message || 'Không thể gửi đánh giá. Vui lòng thử lại.');
            }
        })
        .catch(() => {
            alert('Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại.');
        });
    }

    function toggleLike(reviewId) {
        $.ajax({
            url: 'ajax_review.php',
            method: 'POST',
            data: { action: 'toggle_like', review_id: reviewId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    const btn = document.getElementById('like-btn-' + reviewId);
                    const countSpan = btn.querySelector('.like-count');
                    const icon = btn.querySelector('i');
                    
                    countSpan.textContent = res.total_likes;
                    if (res.like_status === 'liked') {
                        btn.style.color = '#e63946';
                        icon.className = 'fa-solid fa-thumbs-up';
                    } else {
                        btn.style.color = '#888';
                        icon.className = 'fa-regular fa-thumbs-up';
                    }
                } else {
                    alert(res.message);
                }
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>