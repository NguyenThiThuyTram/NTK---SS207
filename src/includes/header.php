<?php
// Bắt đầu session nếu chưa có (Dùng cho phần Auth và Giỏ hàng)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giả lập số lượng giỏ hàng (bạn có thể thay bằng logic count từ CSDL/Session sau)
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 3; // Tạm để số 3 giống thiết kế
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NTK Fashion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/logo-ntk.png" alt="NTK Logo">
                </a>
            </div>
            
            <nav class="navbar">
                <ul>
                    <li><a href="index.php" class="active">Trang chủ</a></li>
                    <li><a href="product.php">Shop</a></li>
                    <li><a href="#">Yêu thích</a></li>
                    <li><a href="#">Promotion</a></li>
                </ul>
            </nav>

            <div class="search-bar-container" id="searchBar" style="display: none;">
                <form action="search.php" method="GET" class="search-form">
                    <button type="submit" class="submit-search"><i class="fa-solid fa-magnifying-glass"></i></button>
                    
                    <input type="text" name="q" placeholder="Bạn đang tìm kiếm gì?..." required>
                    
                    <button type="button" class="close-search" onclick="toggleSearch()"><i class="fa-solid fa-xmark"></i></button>
                </form>
            </div>
            
            <div class="header-icons">
                <a href="javascript:void(0)" onclick="toggleSearch()"><i class="fa-solid fa-magnifying-glass"></i></a>
                <a href="#"><i class="fa-regular fa-heart"></i></a>
                
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="views/user/dashboard.php" title="Tài khoản của tôi"><i class="fa-solid fa-user"></i></a>
                <?php else: ?>
                    <a href="views/login.php" title="Đăng nhập"><i class="fa-regular fa-user"></i></a>
                <?php endif; ?>

                <a href="cart.php" class="cart-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <?php if($cart_count > 0): ?>
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