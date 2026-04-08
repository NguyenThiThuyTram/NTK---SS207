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

</head>

<body>

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
                    <a href="views/user/dashboard.php" title="Tài khoản của tôi"><i class="fa-solid fa-user"></i></a>
                <?php else: ?>
                    <a href="views/login.php" title="Đăng nhập"><i class="fa-regular fa-user"></i></a>
                <?php endif; ?>

                <a href="cart.php" class="cart-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">

        <script>
            function toggleSearch() {
                var searchBar = document.getElementById("searchBar");
                if (searchBar.style.display === "none") {
                    searchBar.style.display = "block";
                    // Tự động focus con trỏ chuột vào ô nhập liệu
                    searchBar.querySelector("input").focus();
                } else {
                    searchBar.style.display = "none";
                }
            }
        </script>