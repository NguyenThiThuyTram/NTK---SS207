<?php
// 1. Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Gọi file kết nối Database (dùng __DIR__ để đường dẫn luôn đúng dù đứng ở trang nào)
require_once __DIR__ . '/../config/database.php';

// 3. Đếm số lượng sản phẩm trong giỏ hàng (Cộng dồn số lượng 'quantity')
$cart_count = 0;
try {
    if (isset($_SESSION['user_id'])) {
        // Đếm cho khách ĐÃ đăng nhập (dựa vào user_id)
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM Cart WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $row['total_items'] ? $row['total_items'] : 0;
    } else {
        // Đếm cho khách CHƯA đăng nhập (dựa vào session_id)
        $session_id = session_id();
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM Cart WHERE session_id = :session_id AND user_id IS NULL");
        $stmt->execute(['session_id' => $session_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $row['total_items'] ? $row['total_items'] : 0;
    }
} catch (PDOException $e) {
    $cart_count = 0; // Nếu có lỗi thì cho về 0
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* ============================================================
           USER DARK MODE
        ============================================================ */
        body.dark-mode {
            background-color: #111 !important;
            color: #ddd !important;
        }
        body.dark-mode .main-header {
            background: #1a1a1a !important;
            border-bottom-color: #2a2a2a !important;
        }
        body.dark-mode .main-header a,
        body.dark-mode .navbar a,
        body.dark-mode .header-icons a {
            color: #ccc !important;
        }
        body.dark-mode .navbar a:hover,
        body.dark-mode .navbar a.active {
            color: #fff !important;
        }
        body.dark-mode .main-content,
        body.dark-mode main {
            background-color: #111 !important;
        }
        body.dark-mode footer,
        body.dark-mode .footer {
            background: #1a1a1a !important;
            color: #aaa !important;
        }
        body.dark-mode footer a,
        body.dark-mode .footer a {
            color: #888 !important;
        }
        body.dark-mode footer a:hover {
            color: #ddd !important;
        }
        /* Product cards */
        body.dark-mode .product-card,
        body.dark-mode .card {
            background: #1e1e1e !important;
            border-color: #2a2a2a !important;
        }
        body.dark-mode .product-card h3,
        body.dark-mode .product-card .name,
        body.dark-mode .product-card p {
            color: #ccc !important;
        }
        /* Inputs & Forms */
        body.dark-mode input,
        body.dark-mode select,
        body.dark-mode textarea {
            background: #1e1e1e !important;
            border-color: #333 !important;
            color: #ddd !important;
        }
        body.dark-mode input::placeholder,
        body.dark-mode textarea::placeholder {
            color: #666 !important;
        }
        /* Search bar */
        body.dark-mode .search-bar-container {
            background: #1a1a1a !important;
            border-color: #2a2a2a !important;
        }
        /* Cart, checkout */
        body.dark-mode .cart-table,
        body.dark-mode .checkout-right {
            background: #1e1e1e !important;
            border-color: #2a2a2a !important;
        }
        body.dark-mode .checkout-right {
            background-color: #1a1a1a !important;
            border-color: #2a2a2a !important;
        }
        body.dark-mode .sum-total-row,
        body.dark-mode .sum-row {
            color: #ccc !important;
        }
        body.dark-mode .sum-total-row { border-top-color: #2a2a2a !important; }
        body.dark-mode .sum-wallet-box {
            border-color: #2a2a2a !important;
        }
        body.dark-mode .method-box {
            border-color: #2a2a2a !important;
            background: #1e1e1e !important;
            color: #ddd !important;
        }
        body.dark-mode .method-name { color: #eee !important; }
        body.dark-mode .method-desc,
        body.dark-mode .sum-note { color: #888 !important; }
        body.dark-mode .checkout-stepper {
            border-bottom-color: #2a2a2a !important;
        }
        body.dark-mode .step-indicator.active { color: #eee !important; }
        body.dark-mode .step-line { background-color: #333 !important; }
        body.dark-mode .btn-back {
            background: #1e1e1e !important;
            border-color: #333 !important;
            color: #ccc !important;
        }

        /* Dark mode toggle button in header */
        .dm-user-toggle {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 17px;
            color: inherit;
            display: inline-flex;
            align-items: center;
            padding: 0 4px;
            transition: color 0.2s;
        }
        .dm-user-toggle:hover { opacity: 0.75; }
    </style>

    <script>
        /* Áp dụng dark mode NGAY trước khi render để tránh flash */
        (function(){
            if (localStorage.getItem('ntk_dark') === '1') {
                document.documentElement.classList.add('dm-pre');
            }
        })();
    </script>

</head>

<body>
<script>
    /* Chuyển class từ <html> sang <body> sau khi body tồn tại */
    if (document.documentElement.classList.contains('dm-pre')) {
        document.body.classList.add('dark-mode');
        document.documentElement.classList.remove('dm-pre');
    }
</script>

    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/logo-ntk.png" alt="NTK Logo">
                </a>
            </div>

            <?php
            // Lấy tên file hiện tại (ví dụ: product.php)
            $current_page = basename($_SERVER['PHP_SELF']);
            ?>

            <nav class="navbar">
                <ul>
                    <li>
                        <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Trang
                            chủ</a>
                    </li>
                    <li>
                        <a href="product.php"
                            class="<?php echo ($current_page == 'product.php') ? 'active' : ''; ?>">Shop</a>
                    </li>
                    <li>
                        <a href="wishlist.php"
                            class="<?php echo ($current_page == 'wishlist.php') ? 'active' : ''; ?>">Yêu thích</a>
                    </li>
                    <li>
                        <a href="promotion.php"
                            class="<?php echo ($current_page == 'promotion.php') ? 'active' : ''; ?>">Promotion</a>
                    </li>
                </ul>
            </nav>

            <div class="search-bar-container" id="searchBar" style="display: none;">
                <form action="search.php" method="GET" class="search-form">
                    <button type="submit" class="submit-search"><i class="fa-solid fa-magnifying-glass"></i></button>

                    <input type="text" name="q" placeholder="Bạn đang tìm kiếm gì?..." required>

                    <button type="button" class="close-search" onclick="toggleSearch()"><i
                            class="fa-solid fa-xmark"></i></button>
                </form>
            </div>

            <div class="header-icons">
                <a href="javascript:void(0)" onclick="toggleSearch()"><i class="fa-solid fa-magnifying-glass"></i></a>
                <a href="wishlist.php"><i class="fa-regular fa-heart"></i></a>



                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (($_SESSION['role'] ?? 0) == 1): ?>
                        <!-- Admin → vào Admin Dashboard -->
                        <a href="admin/dashboard.php" title="Quản trị Admin">
                            <i class="fa-solid fa-user-gear"></i>
                        </a>
                    <?php else: ?>
                        <!-- User thường → vào User Dashboard -->
                        <a href="views/user/dashboard.php" title="Tài khoản của tôi">
                            <i class="fa-solid fa-user"></i>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="views/login.php" title="Đăng nhập"><i class="fa-regular fa-user"></i></a>
                <?php endif; ?>

                <a href="cart.php" class="cart-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Dark mode toggle -->
                <button class="dm-user-toggle" id="dmUserToggle" onclick="toggleUserDark()" title="Bật/tắt chế độ tối">
                    <i class="fa-regular fa-moon" id="dmUserIcon"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="main-content">

        <script>
            function toggleSearch() {
                var searchBar = document.getElementById("searchBar");
                if (searchBar.style.display === "none") {
                    searchBar.style.display = "block";
                    searchBar.querySelector("input").focus();
                } else {
                    searchBar.style.display = "none";
                }
            }

            // ── USER DARK MODE ──────────────────────────────────────
            function toggleUserDark() {
                const isDark = document.body.classList.toggle('dark-mode');
                localStorage.setItem('ntk_dark', isDark ? '1' : '0');
                updateDmUserIcon(isDark);
            }
            function updateDmUserIcon(isDark) {
                const icon = document.getElementById('dmUserIcon');
                const btn  = document.getElementById('dmUserToggle');
                if (!icon) return;
                icon.className = isDark ? 'fa-solid fa-sun' : 'fa-regular fa-moon';
                btn.title = isDark ? 'Tắt chế độ tối' : 'Bật chế độ tối';
            }
            // Sync icon on load
            document.addEventListener('DOMContentLoaded', function(){
                updateDmUserIcon(document.body.classList.contains('dark-mode'));
            });
        </script>