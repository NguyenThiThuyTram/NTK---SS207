<?php
// 1. Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Gọi file kết nối Database
require_once __DIR__ . '/../config/database.php';

// Tính BASE URL (tuyệt đối) để dùng cho link/image trong header
// Luôn trỏ về thư mục src/
$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host = $_SERVER['HTTP_HOST'];
// Lấy đường dẫn của thư mục src/ từ document root
$_src_dir = str_replace('\\', '/', realpath(__DIR__ . '/../'));
$_doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$_src_path = str_replace($_doc_root, '', $_src_dir);
$_BASE = $_protocol . '://' . $_host . $_src_path;

// 3. Đếm số lượng sản phẩm trong giỏ hàng
$cart_count = 0;
try {
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $row['total_items'] ?: 0;
    } else {
        $session_id = session_id();
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE session_id = :session_id AND user_id IS NULL");
        $stmt->execute(['session_id' => $session_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $row['total_items'] ?: 0;
    }
} catch (PDOException $e) {
    $cart_count = 0;
}

// Đếm số lượng thông báo chưa đọc
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
        /* ============================================================
           USER DARK MODE - PHIÊN BẢN NỔI BẬT CHỮ
        ============================================================ */
        body.dark-mode {
            background-color: #121212 !important;
            color: #f5f5f5 !important;
        }

        body.dark-mode p, body.dark-mode span, body.dark-mode li, 
        body.dark-mode td, body.dark-mode th, body.dark-mode label {
            color: #e0e0e0 !important;
        }

        body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, 
        body.dark-mode h4, body.dark-mode h5, body.dark-mode h6,
        body.dark-mode strong, body.dark-mode b {
            color: #ffffff !important;
            text-shadow: 0 0 1px rgba(255,255,255,0.1);
        }

        body.dark-mode .main-header {
            background: #1a1a1a !important;
            border-bottom: 1px solid #333 !important;
        }
        body.dark-mode .navbar a, body.dark-mode .header-icons a {
            color: #f5f5f5 !important;
        }
        body.dark-mode .navbar a:hover, body.dark-mode .navbar a.active {
            color: #f1c40f !important;
        }

        body.dark-mode .product-card, body.dark-mode .card, 
        body.dark-mode .voucher-item, body.dark-mode .coupon-suggest-item {
            background: #1e1e1e !important;
            border-color: #444 !important;
        }

        body.dark-mode input, body.dark-mode select, body.dark-mode textarea {
            background: #252525 !important;
            border-color: #444 !important;
            color: #fff !important;
        }

        body.dark-mode #dmUserIcon {
            color: #f1c40f !important;
        }

        .dm-user-toggle {
            background: none; border: none; cursor: pointer;
            font-size: 18px; color: inherit; display: inline-flex;
            align-items: center; padding: 0 8px; transition: 0.3s;
        }
        .dm-user-toggle:hover { transform: scale(1.1); }

        /* CSS THUẦN CHO THANH TÌM KIẾM CỦA NTK FASHION */
        .search-bar-container {
            display: none;
            background: #ffffff;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            position: absolute;
            width: 100%;
            left: 0;
            top: 70px; /* Căn chỉnh theo chiều cao header của ní */
            z-index: 999;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        body.dark-mode .search-bar-container {
            background: #1a1a1a !important;
            border-bottom: 1px solid #333 !important;
        }
        .search-form {
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            outline: none;
            font-size: 14px;
        }
        .search-btn {
            padding: 10px 20px;
            background: #222;
            color: #fff;
            border: 1px solid #222;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        body.dark-mode .search-btn {
            background: #f1c40f !important;
            color: #222 !important;
            border-color: #f1c40f !important;
        }
    </style>

    <script>
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
                <a href="<?= $_BASE ?>/index.php">
                    <img src="<?= $_BASE ?>/assets/images/logo-ntk.png" alt="NTK Logo" id="mainLogo">
                </a>
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
                    <?php if ($_SESSION['role'] != 1): ?>
                        <a href="<?= $_BASE ?>/views/user/dashboard.php?view=thongbao" class="cart-icon" title="Thông báo">
                            <i class="fa-regular fa-bell"></i>
                            <?php if ($unread_noti_count > 0): ?><span class="cart-count" style="background:#ee4d2d;"><?= $unread_noti_count; ?></span><?php endif; ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?= ($_SESSION['role'] == 1) ? $_BASE . '/admin/dashboard.php' : $_BASE . '/views/user/dashboard.php'; ?>" title="Tài khoản">
                        <i class="<?= ($_SESSION['role'] == 1) ? 'fa-solid fa-user-gear' : 'fa-solid fa-user'; ?>"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= $_BASE ?>/views/login.php" title="Đăng nhập"><i class="fa-regular fa-user"></i></a>
                <?php endif; ?>

                <a href="<?= $_BASE ?>/cart.php" class="cart-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <?php if ($cart_count > 0): ?><span class="cart-count"><?= $cart_count; ?></span><?php endif; ?>
                </a>

                <button class="dm-user-toggle" id="dmUserToggle" onclick="toggleUserDark()">
                    <i class="fa-regular fa-moon" id="dmUserIcon"></i>
                </button>
            </div>
        </div>

        <div id="searchBar" class="search-bar-container" style="display:none;">
            <form action="<?= $_BASE ?>/search.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Tìm kiếm sản phẩm tại NTK Fashion..." class="search-input" required>
                <button type="submit" class="search-btn">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
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
                icon.className = 'fa-solid fa-sun';
                if(logo) logo.style.filter = 'brightness(0) invert(1)';
            } else {
                icon.className = 'fa-regular fa-moon';
                if(logo) logo.style.filter = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateDmUserIcon(document.body.classList.contains('dark-mode'));
        });

        // HÀM TOGGLE ĐÃ CHẠY PHÀ PHÀ VÌ ĐÃ CÓ THẺ SEARCHBAR
        function toggleSearch() {
            const sb = document.getElementById("searchBar");
            if(sb) {
                if (sb.style.display === "none" || sb.style.display === "") {
                    sb.style.display = "block";
                } else {
                    sb.style.display = "none";
                }
            }
        }
    </script>