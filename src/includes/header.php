<?php
// 1. Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Gọi file kết nối Database
require_once __DIR__ . '/../config/database.php';

// 3. Đếm số lượng sản phẩm trong giỏ hàng
$cart_count = 0;
try {
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM Cart WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $row['total_items'] ?: 0;
    } else {
        $session_id = session_id();
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM Cart WHERE session_id = :session_id AND user_id IS NULL");
        $stmt->execute(['session_id' => $session_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $row['total_items'] ?: 0;
    }
} catch (PDOException $e) {
    $cart_count = 0;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NTK Fashion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* ============================================================
           USER DARK MODE - PHIÊN BẢN NỔI BẬT CHỮ
        ============================================================ */
        body.dark-mode {
            background-color: #121212 !important; /* Nền tối sâu */
            color: #f5f5f5 !important; /* Chữ trắng sữa dễ đọc */
        }

        /* Làm nổi bật toàn bộ văn bản */
        body.dark-mode p, body.dark-mode span, body.dark-mode li, 
        body.dark-mode td, body.dark-mode th, body.dark-mode label {
            color: #e0e0e0 !important;
        }

        /* Tiêu đề trắng tinh khôi */
        body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, 
        body.dark-mode h4, body.dark-mode h5, body.dark-mode h6,
        body.dark-mode strong, body.dark-mode b {
            color: #ffffff !important;
            text-shadow: 0 0 1px rgba(255,255,255,0.1);
        }

        /* Header & Navbar */
        body.dark-mode .main-header {
            background: #1a1a1a !important;
            border-bottom: 1px solid #333 !important;
        }
        body.dark-mode .navbar a, body.dark-mode .header-icons a {
            color: #f5f5f5 !important;
        }
        body.dark-mode .navbar a:hover, body.dark-mode .navbar a.active {
            color: #f1c40f !important; /* Màu vàng Gold đặc trưng */
        }

        /* Thẻ sản phẩm & Voucher */
        body.dark-mode .product-card, body.dark-mode .card, 
        body.dark-mode .voucher-item, body.dark-mode .coupon-suggest-item {
            background: #1e1e1e !important;
            border-color: #444 !important;
        }

        /* Input & Form */
        body.dark-mode input, body.dark-mode select, body.dark-mode textarea {
            background: #252525 !important;
            border-color: #444 !important;
            color: #fff !important;
        }

        /* Icon Dark mode khi bật */
        body.dark-mode #dmUserIcon {
            color: #f1c40f !important; /* Màu vàng cho mặt trời */
        }

        .dm-user-toggle {
            background: none; border: none; cursor: pointer;
            font-size: 18px; color: inherit; display: inline-flex;
            align-items: center; padding: 0 8px; transition: 0.3s;
        }
        .dm-user-toggle:hover { transform: scale(1.1); }
    </style>

    <script>
        // Kiểm tra dark mode ngay lập tức
        if (localStorage.getItem('ntk_dark') === '1') {
            document.documentElement.classList.add('dark-mode-init');
        }
    </script>
</head>

<body>
<script>
    if (document.documentElement.classList.contains('dark-mode-init')) {
        document.body.classList.add('dark-mode');
        document.documentElement.classList.remove('dark-mode-init');
    }
</script>

    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/logo-ntk.png" alt="NTK Logo" id="mainLogo">
                </a>
            </div>

            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

            <nav class="navbar">
                <ul>
                    <li><a href="index.php" class="<?= ($current_page == 'index.php') ? 'active' : ''; ?>">Trang chủ</a></li>
                    <li><a href="product.php" class="<?= ($current_page == 'product.php') ? 'active' : ''; ?>">Cửa hàng</a></li>
                    <li><a href="wishlist.php" class="<?= ($current_page == 'wishlist.php') ? 'active' : ''; ?>">Yêu thích</a></li>
                    <li><a href="promotion.php" class="<?= ($current_page == 'promotion.php') ? 'active' : ''; ?>">Khuyến mãi</a></li>
                </ul>
            </nav>

            <div class="header-icons">
                <a href="javascript:void(0)" onclick="toggleSearch()"><i class="fa-solid fa-magnifying-glass"></i></a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= ($_SESSION['role'] == 1) ? 'admin/dashboard.php' : 'views/user/dashboard.php'; ?>" title="Tài khoản">
                        <i class="<?= ($_SESSION['role'] == 1) ? 'fa-solid fa-user-gear' : 'fa-solid fa-user'; ?>"></i>
                    </a>
                <?php else: ?>
                    <a href="views/login.php" title="Đăng nhập"><i class="fa-regular fa-user"></i></a>
                <?php endif; ?>

                <a href="cart.php" class="cart-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <?php if ($cart_count > 0): ?><span class="cart-count"><?= $cart_count; ?></span><?php endif; ?>
                </a>

                <button class="dm-user-toggle" id="dmUserToggle" onclick="toggleUserDark()">
                    <i class="fa-regular fa-moon" id="dmUserIcon"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="main-content">

    <script>
        function toggleUserDark() {
            const isDark = document.body.classList.toggle('dark-mode');
            localStorage.setItem('ntk_dark', isDark ? '1' : '0');
            updateDmUserIcon(isDark);
        }

        function updateDmUserIcon(isDark) {
            const icon = document.getElementById('dmUserIcon');
            const logo = document.getElementById('mainLogo');
            if (!icon) return;

            if (isDark) {
                icon.className = 'fa-solid fa-sun'; // Mặt trời vàng
                if(logo) logo.style.filter = 'brightness(0) invert(1)'; // Đổi logo sang trắng nếu cần
            } else {
                icon.className = 'fa-regular fa-moon'; // Mặt trăng tối
                if(logo) logo.style.filter = 'none';
            }
        }

        // Chạy ngay khi load trang để đồng bộ icon
        document.addEventListener('DOMContentLoaded', function() {
            updateDmUserIcon(document.body.classList.contains('dark-mode'));
        });

        function toggleSearch() {
            // Giữ nguyên logic search của Bee
            const sb = document.getElementById("searchBar");
            if(sb) sb.style.display = (sb.style.display === "none") ? "block" : "none";
        }
    </script>