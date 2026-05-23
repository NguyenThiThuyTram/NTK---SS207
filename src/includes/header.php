<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host = $_SERVER['HTTP_HOST'];
$_src_dir = str_replace('\\', '/', realpath(__DIR__ . '/../'));
$_doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$_src_path = str_replace($_doc_root, '', $_src_dir);
$_BASE = $_protocol . '://' . $_host . $_src_path;

$cart_count = 0;
try {
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $row['total_items'] ?: 0;
    }
} catch (PDOException $e) {}

$unread_noti_count = 0;
try {
    if (isset($_SESSION['user_id'])) {
        $stmt_n = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = :user_id AND is_read = 0");
        $stmt_n->execute(['user_id' => $_SESSION['user_id']]);
        $row_n = $stmt_n->fetch(PDO::FETCH_ASSOC);
        $unread_noti_count = $row_n['unread'] ?: 0;
    }
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NTK Fashion</title>
    <link rel="icon" type="image/png" href="<?= $_BASE ?>/assets/images/logo-ntk.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $_BASE ?>/assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* === COMPREHENSIVE DARK MODE SYNC === */
        body.dark-mode {
            --bg-color: #121212 !important;
            --beige-color: #1a1a1a !important;
            --border-color: #333333 !important;
            --text-color: #f5f5f5 !important;
            background-color: #121212 !important;
            color: #f5f5f5 !important;
        }

        /* Elements and Texts */
        body.dark-mode p, 
        body.dark-mode span, 
        body.dark-mode li, 
        body.dark-mode td, 
        body.dark-mode th, 
        body.dark-mode label,
        body.dark-mode legend,
        body.dark-mode input,
        body.dark-mode select,
        body.dark-mode textarea { 
            color: #e0e0e0 !important; 
        }
        
        body.dark-mode h1, 
        body.dark-mode h2, 
        body.dark-mode h3, 
        body.dark-mode h4,
        body.dark-mode h5,
        body.dark-mode h6,
        body.dark-mode strong,
        body.dark-mode b { 
            color: #ffffff !important; 
        }

        body.dark-mode a {
            color: #ffffff !important;
        }
        body.dark-mode a:hover {
            color: #f1c40f !important;
        }

        /* Header, Footer, and Backgrounds */
        body.dark-mode header,
        body.dark-mode .main-header,
        body.dark-mode .search-bar-container { 
            background: #1a1a1a !important; 
            background-color: #1a1a1a !important; 
            border-bottom-color: #333333 !important; 
        }
        
        body.dark-mode footer, 
        body.dark-mode .main-footer { 
            background: #1a1a1a !important; 
            background-color: #1a1a1a !important; 
            border-top-color: #333333 !important; 
        }

        body.dark-mode .section, 
        body.dark-mode section,
        body.dark-mode .policy-page { 
            background: #121212 !important; 
            background-color: #121212 !important; 
        }

        /* Light Cards and Boxes */
        body.dark-mode .product-card,
        body.dark-mode .cart-left,
        body.dark-mode .order-summary-box,
        body.dark-mode .process-step,
        body.dark-mode .payment-card,
        body.dark-mode .contact-box,
        body.dark-mode .checkout-box,
        body.dark-mode .billing-box,
        body.dark-mode .cart-item,
        body.dark-mode .address-card,
        body.dark-mode .coupon-card,
        body.dark-mode .notification-card,
        body.dark-mode .profile-card,
        body.dark-mode .wallet-card,
        body.dark-mode .review-card,
        body.dark-mode .review-item,
        body.dark-mode .detail-box,
        body.dark-mode .order-box {
            background: #1e1e1e !important;
            background-color: #1e1e1e !important;
            border-color: #333333 !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5) !important;
            color: #ffffff !important;
        }

        /* Special Cart and Forms */
        body.dark-mode .cart-select-all-bar,
        body.dark-mode .cart-table-header {
            background: #1a1a1a !important;
            border-bottom-color: #333333 !important;
        }
        body.dark-mode .cart-custom-checkbox {
            background: #252525 !important;
            border-color: #555555 !important;
        }
        body.dark-mode .cart-checkbox-label input[type="checkbox"]:checked+.cart-custom-checkbox {
            background: #a6825c !important;
            border-color: #a6825c !important;
        }
        body.dark-mode .order-warning {
            background: #2a2211 !important;
            border-color: #554422 !important;
            color: #f1c40f !important;
        }
        body.dark-mode .order-warning i {
            color: #f1c40f !important;
        }

        /* Form Controls */
        body.dark-mode input[type="text"],
        body.dark-mode input[type="email"],
        body.dark-mode input[type="tel"],
        body.dark-mode input[type="password"],
        body.dark-mode input[type="number"],
        body.dark-mode select,
        body.dark-mode textarea {
            background-color: #252525 !important;
            border-color: #333333 !important;
            color: #ffffff !important;
        }
        body.dark-mode input::placeholder,
        body.dark-mode textarea::placeholder {
            color: #888888 !important;
        }
        
        /* Tables */
        body.dark-mode table,
        body.dark-mode .shipping-table {
            background-color: #121212 !important;
            border-color: #333333 !important;
        }
        body.dark-mode th {
            background-color: #1a1a1a !important;
            color: #a6825c !important;
            border-color: #333333 !important;
        }
        body.dark-mode td {
            background-color: #121212 !important;
            color: #eeeeee !important;
            border-color: #333333 !important;
        }
        body.dark-mode tr:hover,
        body.dark-mode tbody tr:hover {
            background-color: #1a1a1a !important;
        }

        /* Buttons & Badges */
        body.dark-mode .btn-cat,
        body.dark-mode .btn-secondary {
            background: #252525 !important;
            border-color: #555555 !important;
            color: #ffffff !important;
        }
        body.dark-mode .btn-cat:hover,
        body.dark-mode .btn-cat.active,
        body.dark-mode .btn-secondary:hover {
            background: #a6825c !important;
            color: #121212 !important;
            border-color: #a6825c !important;
        }
        body.dark-mode .btn-primary,
        body.dark-mode .submit-btn,
        body.dark-mode .checkout-btn {
            background: #a6825c !important;
            color: #121212 !important;
        }
        body.dark-mode .btn-primary:hover,
        body.dark-mode .submit-btn:hover,
        body.dark-mode .checkout-btn:hover {
            background: #c9a47e !important;
        }

        /* Navigation specifics */
        body.dark-mode .navbar a, 
        body.dark-mode .header-icons a { 
            color: #f5f5f5 !important; 
        }
        body.dark-mode .navbar a:hover, 
        body.dark-mode .navbar a.active { 
            color: #f1c40f !important; 
        }
        body.dark-mode .navbar a.active {
            border-bottom-color: #f1c40f !important;
        }
        body.dark-mode .submenu {
            background-color: #1a1a1a !important;
            border-color: #333333 !important;
        }
        body.dark-mode .submenu li a:hover {
            background-color: #252525 !important;
            color: #f1c40f !important;
        }

        /* Social Icons */
        body.dark-mode .social-icons i {
            border-color: #333333 !important;
            color: #ffffff !important;
        }
        body.dark-mode .social-icons i:hover {
            background-color: #a6825c !important;
            color: #121212 !important;
            border-color: #a6825c !important;
        }

        .dm-user-toggle { background: none; border: none; cursor: pointer; font-size: 18px; color: inherit; display: inline-flex; align-items: center; padding: 0 8px; }
        .search-bar-container { display: none; background: #ffffff; border-bottom: 1px solid #eee; padding: 15px 0; position: absolute; width: 100%; left: 0; top: 70px; z-index: 999; }
        body.dark-mode .search-bar-container { background: #1a1a1a !important; border-bottom: 1px solid #333 !important; }
        
        /* Styles for Image Search button and loading modal */
        .image-search-btn, .search-btn {
            background: none !important;
            border: none !important;
            cursor: pointer;
            outline: none;
            padding: 0 10px;
            color: #2f1c00;
            font-size: 18px;
            transition: color 0.3s, transform 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .image-search-btn:hover, .search-btn:hover {
            color: #8a6d51;
            transform: scale(1.1);
        }
        body.dark-mode .image-search-btn, body.dark-mode .search-btn {
            color: #f5f5f5 !important;
        }
        body.dark-mode .image-search-btn:hover, body.dark-mode .search-btn:hover {
            color: #f1c40f !important;
        }
        body.dark-mode .search-form {
            border-bottom-color: #f5f5f5 !important;
        }
        body.dark-mode .search-form input {
            color: #ffffff !important;
        }
        
        .image-search-modal {
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }
        .image-search-modal-content {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: scaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        body.dark-mode .image-search-modal-content {
            background-color: #1a1a1a;
            color: #ffffff;
            border: 1px solid #333;
        }
        .loader-spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border-left-color: #2f1c00;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px auto;
        }
        body.dark-mode .loader-spinner {
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-left-color: #f1c40f;
        }
        #imageSearchStatus {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 15px;
            color: #2f1c00;
        }
        body.dark-mode #imageSearchStatus {
            color: #ffffff;
        }
        .image-preview-container {
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #ddd;
            max-height: 200px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f9f9f9;
        }
        body.dark-mode .image-preview-container {
            border-color: #333;
            background: #121212;
        }
        #imageSearchPreview {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes scaleUp {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="<?= $_BASE ?>/index.php"><img src="<?= $_BASE ?>/assets/images/logo-ntk.png" alt="NTK Logo" id="mainLogo"></a>
            </div>

            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

            <nav class="navbar">
                <ul>
                    <li><a href="<?= $_BASE ?>/index.php" class="<?= ($current_page == 'index.php') ? 'active' : ''; ?>">Trang chủ</a></li>
                    <li><a href="<?= $_BASE ?>/product.php" class="<?= ($current_page == 'product.php') ? 'active' : ''; ?>">Cửa hàng</a></li>
                    <li><a href="<?= $_BASE ?>/wishlist.php" class="<?= ($current_page == 'wishlist.php') ? 'active' : ''; ?>">Yêu thích</a></li>
                    <li><a href="<?= $_BASE ?>/promotion.php" class="<?= ($current_page == 'promotion.php') ? 'active' : ''; ?>">Khuyến mãi</a></li>
                </ul>
            </nav>

            <div class="header-icons">
                <a href="javascript:void(0)" onclick="toggleSearch()"><i class="fa-solid fa-magnifying-glass"></i></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= $_BASE ?>/views/user/dashboard.php?view=thongbao" title="Thông báo"><i class="fa-regular fa-bell"></i></a>
                    <a href="<?= ($_SESSION['role'] == 1) ? $_BASE . '/admin/dashboard.php' : $_BASE . '/views/user/dashboard.php'; ?>" title="Tài khoản"><i class="fa-solid fa-user"></i></a>
                <?php else: ?>
                    <a href="<?= $_BASE ?>/views/login.php" title="Đăng nhập"><i class="fa-regular fa-user"></i></a>
                <?php endif; ?>
                <a href="<?= $_BASE ?>/cart.php"><i class="fa-solid fa-bag-shopping"></i></a>
                <button class="dm-user-toggle" onclick="toggleUserDark()"><i class="fa-regular fa-moon" id="dmUserIcon"></i></button>
            </div>
        </div>

        <div id="searchBar" class="search-bar-container" style="display:none;">
            <form action="<?= $_BASE ?>/search.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Tìm kiếm..." class="search-input" required>
                <!-- Nút tìm kiếm bằng hình ảnh -->
                <button type="button" class="image-search-btn" onclick="triggerImageSearch()" title="Tìm kiếm bằng hình ảnh">
                    <i class="fa-solid fa-camera"></i>
                </button>
                <button type="submit" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <input type="file" id="imageSearchInput" accept="image/*" style="display: none;" onchange="handleImageSearch(this)">
        </div>

        <!-- Modal Loading Tìm kiếm hình ảnh -->
        <div id="imageSearchModal" class="image-search-modal" style="display: none;">
            <div class="image-search-modal-content">
                <div class="loader-spinner"></div>
                <p id="imageSearchStatus">Đang gửi ảnh lên hệ thống...</p>
                <div class="image-preview-container" style="display: none;">
                    <img id="imageSearchPreview" src="" alt="Preview">
                </div>
            </div>
        </div>
    </header>

    <script>
        function toggleUserDark() {
            const isDark = document.body.classList.toggle('dark-mode');
            localStorage.setItem('ntk_dark', isDark ? '1' : '0');
            document.getElementById('dmUserIcon').className = isDark ? 'fa-solid fa-sun' : 'fa-regular fa-moon';
        }
        function toggleSearch() {
            const sb = document.getElementById("searchBar");
            sb.style.display = (sb.style.display === "none") ? "block" : "none";
        }
        if (localStorage.getItem('ntk_dark') === '1') {
            document.body.classList.add('dark-mode');
            document.addEventListener('DOMContentLoaded', function() {
                const icon = document.getElementById('dmUserIcon');
                if (icon) icon.className = 'fa-solid fa-sun';
            });
        }

        // Đồng bộ tức thời khi đổi dark mode ở tab khác
        window.addEventListener('storage', function(e) {
            if (e.key === 'ntk_dark') {
                const isDark = e.newValue === '1';
                document.body.classList.toggle('dark-mode', isDark);
                const icon = document.getElementById('dmUserIcon');
                if (icon) icon.className = isDark ? 'fa-solid fa-sun' : 'fa-regular fa-moon';
            }
        });

        function triggerImageSearch() {
            document.getElementById('imageSearchInput').click();
        }

        function handleImageSearch(input) {
            if (!input.files || !input.files[0]) return;
            
            const file = input.files[0];
            const modal = document.getElementById('imageSearchModal');
            const statusText = document.getElementById('imageSearchStatus');
            const previewContainer = document.querySelector('.image-preview-container');
            const previewImg = document.getElementById('imageSearchPreview');
            
            // Reset modal state
            statusText.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Đang đọc hình ảnh...';
            statusText.style.color = '';
            previewContainer.style.display = 'none';
            previewImg.src = '';
            
            // Show modal
            modal.style.display = 'flex';
            
            // Show preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.style.display = 'flex';
            };
            reader.readAsDataURL(file);
            
            // Prepare form data
            const formData = new FormData();
            formData.append('image', file);
            
            // Change status
            statusText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> AI đang phân tích hình ảnh...';
            
            // Fetch API
            fetch('<?= $_BASE ?>/api_image_search.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusText.style.color = '#27ae60';
                    statusText.innerHTML = '<i class="fa-solid fa-circle-check"></i> Phân tích thành công! Từ khóa: "<strong>' + data.keyword + '</strong>".<br><small style="color: #666;">Đang tìm sản phẩm tương tự...</small>';
                    
                    // Redirect after 1.5 seconds
                    setTimeout(() => {
                        window.location.href = '<?= $_BASE ?>/search.php?q=' + encodeURIComponent(data.keyword);
                    }, 1500);
                } else {
                    showImageSearchError(data.message || 'Có lỗi xảy ra khi phân tích ảnh.');
                }
            })
            .catch(error => {
                console.error('Error searching image:', error);
                showImageSearchError('Không thể kết nối tới máy chủ. Vui lòng thử lại.');
            });
        }

        function showImageSearchError(message) {
            const statusText = document.getElementById('imageSearchStatus');
            statusText.style.color = '#c0392b';
            statusText.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Lỗi: ' + message + '<br><button type="button" class="btn-cat" style="margin-top: 15px; background: #c0392b; color: white; border: none; padding: 5px 15px; cursor: pointer;" onclick="closeImageSearchModal()">Đóng</button>';
        }

        function closeImageSearchModal() {
            document.getElementById('imageSearchModal').style.display = 'none';
            document.getElementById('imageSearchInput').value = ''; // Reset file input
        }
    </script>
    <main class="main-content">