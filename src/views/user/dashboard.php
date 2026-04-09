<?php
session_start();
include '../../config/database.php';

// Cấm người chưa đăng nhập vô dashboard
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Lấy thông tin user hiện tại
try {
    $sql = "SELECT * FROM Users WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$current_user) {
        session_destroy();
        header('Location: ../login.php');
        exit;
    }
} catch (PDOException $e) {
    echo "Lỗi kết nối hoặc truy vấn: " . $e->getMessage();
    exit;
}

// Xác định view hiện tại
$view = isset($_GET['view']) ? $_GET['view'] : 'hoso';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NTK Fashion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* TONE MÀU CHUẨN LOCAL BRAND ĐẠI CA YÊU CẦU */
        :root {
            --primary: #2f1c00;      
            --bg-white: #ffffff;     
            --beige: #f5f1eb;        
            --border-color: #e5e5e5; 
            --text-main: #111111;    
            --text-muted: #555555;   
        }

        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-white);
            color: var(--text-main);
        }

        /* ================= FIXED HEADER ================= */
        .site-header {
            background-color: var(--bg-white);
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative; /* Quan trọng để thả hộp tìm kiếm xuống đúng chỗ */
        }

        /* Logo NTK - Tĩnh */
        .logo-static {
            font-size: 30px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: 3px;
            font-family: Georgia, serif; 
            cursor: default;
            user-select: none;
        }

        /* Menu điều hướng */
        .main-nav {
            display: flex;
            gap: 35px;
        }

        .main-nav a {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.3s ease;
            position: relative;
        }

        .main-nav a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 1px;
            bottom: -5px;
            left: 0;
            background-color: var(--primary);
            transition: width 0.3s ease;
        }

        .main-nav a:hover {
            color: var(--primary);
        }

        .main-nav a:hover::after {
            width: 100%;
        }

        /* 4 ICONS BÊN PHẢI */
        .header-icons {
            display: flex;
            gap: 22px;
            align-items: center;
        }

        .header-icons a {
            color: var(--text-main);
            font-size: 20px; /* Cho to rõ nét như hình */
            text-decoration: none;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .header-icons a:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        /* ================= HỘP TÌM KIẾM BẬT/TẮT ================= */
        .search-drop-box {
            position: absolute;
            top: calc(100% + 20px); /* Nằm ngay dưới mép header */
            right: 20px; /* Căn sát mép phải icon */
            width: 320px;
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
            padding: 10px 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 101;
        }

        .search-drop-box.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .search-drop-box form {
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--text-main);
            padding-bottom: 5px;
        }

        .search-drop-box input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 14px;
            padding: 8px 0;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: var(--text-main);
        }

        .search-drop-box button {
            background: none;
            border: none;
            font-size: 16px;
            color: var(--text-main);
            cursor: pointer;
        }

        .search-drop-box button:hover {
            color: var(--primary);
        }

        /* ================= DASHBOARD CONTENT ================= */
        .dashboard-container {
            display: flex;
            max-width: 1200px;
            margin: 40px auto;
            background: var(--bg-white);
            min-height: 600px;
            padding: 0 20px;
        }

        .sidebar { width: 250px; padding-right: 20px; border-right: 1px solid var(--border-color); flex-shrink: 0; }
        .user-info { display: flex; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px dashed var(--border-color); }
        .avatar { width: 50px; height: 50px; border-radius: 50%; background: var(--beige); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; color: var(--primary); margin-right: 15px; border: 1px solid var(--border-color); }
        .menu-list { list-style: none; padding: 0; margin: 0; }
        .menu-title { font-size: 12px; font-weight: 700; color: #999; margin: 25px 0 10px 0; text-transform: uppercase; }
        .menu-list a { display: flex; align-items: center; padding: 10px 15px; color: var(--text-main); text-decoration: none; border-radius: 4px; font-size: 14px; margin-bottom: 2px; transition: all 0.2s ease; }
        .menu-list a i { margin-right: 12px; width: 18px; color: var(--text-muted); transition: color 0.2s ease; }
        .menu-list a:hover { background-color: var(--beige); color: var(--primary); }
        .menu-list a:hover i { color: var(--primary); }
        .menu-list a.active { background-color: var(--primary); color: var(--bg-white); font-weight: 500; }
        .menu-list a.active i { color: var(--bg-white); }
        .main-content { flex: 1; padding-left: 40px; }
    </style>
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <div class="logo-static">NTK</div>
            
            <nav class="main-nav">
                <a href="http://localhost:8080/NTK---SS207/src/index.php">Trang chủ</a>
                <a href="http://localhost:8080/NTK---SS207/src/product.php">Shop</a>
                <a href="http://localhost:8080/NTK---SS207/src/wishlist.php">Yêu thích</a>
                <a href="http://localhost:8080/NTK---SS207/src/promotion.php">Promotion</a>
            </nav>
            
            <div class="header-icons">
                <a href="#" id="toggleSearchBtn"><i class="fa-solid fa-magnifying-glass"></i></a>
                <a href="http://localhost:8080/NTK---SS207/src/wishlist.php"><i class="fa-regular fa-heart"></i></a>
                <a href="http://localhost:8080/NTK---SS207/src/views/user/dashboard.php"><i class="fa-regular fa-user"></i></a>
                <a href="http://localhost:8080/NTK---SS207/src/cart.php"><i class="fa-solid fa-bag-shopping"></i></a>
            </div>

            <div class="search-drop-box" id="searchDropBox">
                <form action="http://localhost:8080/NTK---SS207/src/search.php" method="GET">
                    <input type="text" name="q" placeholder="Bạn tìm gì...">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="user-info">
                <div class="avatar"><?= strtoupper(substr($current_user['username'], 0, 1)) ?></div>
                <div>
                    <div style="font-weight:600; font-size: 16px; color: var(--text-main);"><?= htmlspecialchars($current_user['fullname'] ?: $current_user['username']) ?></div>
                    <a href="dashboard.php?view=hoso" style="font-size:12px; color:var(--text-muted); text-decoration:none;">Sửa Hồ Sơ</a>
                </div>
            </div>

            <div class="menu-title">Tài khoản của tôi</div>
            <ul class="menu-list">
                <li><a href="dashboard.php?view=hoso" class="<?= ($view=='hoso')?'active':'' ?>"><i class="fa-regular fa-user"></i> Hồ sơ</a></li>
                <li><a href="dashboard.php?view=nganhang" class="<?= ($view=='nganhang')?'active':'' ?>"><i class="fa-solid fa-building-columns"></i> Ngân hàng</a></li>
                <li><a href="dashboard.php?view=diachi" class="<?= ($view=='diachi')?'active':'' ?>"><i class="fa-solid fa-location-dot"></i> Địa chỉ</a></li>
                <li><a href="dashboard.php?view=doimatkhau" class="<?= ($view=='doimatkhau')?'active':'' ?>"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</a></li>
                <li><a href="dashboard.php?view=caidat" class="<?= ($view=='caidat')?'active':'' ?>"><i class="fa-solid fa-gear"></i> Cài đặt thông báo</a></li>
            </ul>

            <div class="menu-title">Mua sắm</div>
            <ul class="menu-list">
                <li><a href="dashboard.php?view=donmua" class="<?= ($view=='donmua')?'active':'' ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Đơn Mua</a></li>
                <li><a href="dashboard.php?view=thongbao" class="<?= ($view=='thongbao')?'active':'' ?>"><i class="fa-regular fa-bell"></i> Thông báo</a></li>
            </ul>

            <div class="menu-title">Tiện ích</div>
            <ul class="menu-list">
                <li><a href="dashboard.php?view=vihoantien" class="<?= ($view=='vihoantien')?'active':'' ?>"><i class="fa-solid fa-wallet"></i> Ví hoàn tiền</a></li>
                <li><a href="dashboard.php?view=khovoucher" class="<?= ($view=='khovoucher'||$view=='all_coupons')?'active':'' ?>"><i class="fa-solid fa-ticket"></i> Kho voucher</a></li>
            </ul>

            <div class="menu-title" style="margin-top: 30px;">
                <ul class="menu-list">
                    <li><a href="../logout.php" style="color: #d9534f;"><i class="fa-solid fa-arrow-right-from-bracket" style="color: #d9534f;"></i> Đăng xuất</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content">
            <?php 
                switch ($view) {
                    case 'hoso': include 'profile_form.php'; break;
                    case 'nganhang': include 'bank_form.php'; break;
                    case 'thongbao': include 'notifications.php'; break;
                    case 'diachi': include 'address_list.php'; break;
                    case 'doimatkhau': include 'change_password.php'; break;
                    case 'caidat': include 'notification_settings.php'; break;
                    case 'donmua': include 'orders.php'; break;
                    case 'vihoantien': include 'wallet.php'; break;
                    case 'khovoucher': include 'coupon.php'; break;
                    case 'all_coupons': include 'all_coupons.php'; break;
                    default: include 'profile_form.php'; break;
                }
            ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleSearchBtn = document.getElementById('toggleSearchBtn');
            const searchDropBox = document.getElementById('searchDropBox');

            // Khi bấm vào icon kính lúp
            toggleSearchBtn.addEventListener('click', function(e) {
                e.preventDefault(); // Tránh bị nhảy trang
                searchDropBox.classList.toggle('active');
            });

            // Ẩn hộp tìm kiếm nếu click ra ngoài khoảng trống
            document.addEventListener('click', function(e) {
                if (!toggleSearchBtn.contains(e.target) && !searchDropBox.contains(e.target)) {
                    searchDropBox.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>