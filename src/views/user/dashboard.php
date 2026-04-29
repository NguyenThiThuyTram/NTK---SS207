<?php
ob_start(); // Fix lỗi headers already sent
// ────────────────────────────────────────────────
// dashboard.php – User Dashboard
// ────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

// Redirect nếu chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Lấy thông tin user
try {
    $stmt = $conn->prepare("SELECT * FROM Users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$current_user) {
        session_destroy();
        header('Location: ../login.php');
        exit;
    }
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
    exit;
}

// View hiện tại
$view = isset($_GET['view']) ? $_GET['view'] : 'hoso';

// Đếm giỏ hàng cho header
$cart_count = 0;
try {
    $stmt_c = $conn->prepare("SELECT SUM(quantity) as total FROM Cart WHERE user_id = :uid");
    $stmt_c->execute(['uid' => $_SESSION['user_id']]);
    $row_c = $stmt_c->fetch(PDO::FETCH_ASSOC);
    $cart_count = $row_c['total'] ? (int)$row_c['total'] : 0;
} catch (PDOException $e) {
    $cart_count = 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản - NTK Fashion</title>

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main site CSS – đường dẫn tương đối từ views/user/ -->
    <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
    /* ══════════════════════════════════════════════
       DASHBOARD – CSS riêng
    ══════════════════════════════════════════════ */
    :root {
        --primary:      #2f1c00;
        --bg:           #ffffff;
        --beige:        #f5f1eb;
        --border:       #e5e5e5;
        --text:         #111111;
        --muted:        #555555;
    }

    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background: var(--bg); color: var(--text); }

    /* ── Header (dùng lại CSS từ style.css + bổ sung search drop) ── */
    .main-header { position: sticky; top: 0; z-index: 1000; }

    /* Search drop-box */
    .search-bar-container {
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        width: 100% !important;
        background: #fff;
        padding: 20px 5%;
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        border-top: 1px solid var(--border);
        z-index: 999;
        animation: slideDown 0.25s ease-out forwards;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .search-form {
        display: flex !important;
        align-items: center !important;
        max-width: 680px !important;
        margin: 0 auto !important;
        border-bottom: 2px solid var(--primary) !important;
        padding-bottom: 8px !important;
        background: transparent !important;
        border-radius: 0 !important;
        border-top: none !important;
        border-left: none !important;
        border-right: none !important;
    }
    .submit-search, .close-search {
        background: none !important;
        border: none !important;
        cursor: pointer; outline: none;
    }
    .submit-search i { color: var(--primary); font-size: 18px; margin-right: 12px; }
    .search-form input {
        flex: 1; border: none !important; outline: none !important;
        font-size: 15px; padding: 8px 0;
        background: transparent !important; color: #333;
    }
    .search-form input::placeholder { color: #bbb; }
    .close-search i { font-size: 20px; color: #999; transition: color 0.2s; }
    .close-search:hover i { color: var(--primary); }

    /* ── Dashboard layout ── */
    .dashboard-wrap {
        display: flex;
        max-width: 1240px;
        margin: 40px auto 60px;
        padding: 0 24px;
        gap: 0;
        min-height: 600px;
    }

    /* ── Sidebar ── */
    .dashboard-sidebar {
        width: 240px;
        flex-shrink: 0;
        padding-right: 24px;
        border-right: 1px solid var(--border);
    }
    .user-card {
        display: flex;
        align-items: center;
        margin-bottom: 28px;
        padding-bottom: 20px;
        border-bottom: 1px dashed var(--border);
    }
    .user-avatar {
        width: 48px; height: 48px;
        border-radius: 50%;
        background: var(--beige);
        border: 1.5px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; font-weight: 700;
        color: var(--primary);
        margin-right: 14px;
        flex-shrink: 0;
    }
    .user-name { font-weight: 600; font-size: 14.5px; line-height: 1.3; }
    .user-edit { font-size: 12px; color: var(--muted); text-decoration: none; }
    .user-edit:hover { color: var(--primary); }

    .menu-section-title {
        font-size: 10.5px; font-weight: 700;
        color: #bbb; letter-spacing: 0.9px;
        text-transform: uppercase;
        margin: 22px 0 7px 14px;
    }
    .sidebar-menu { list-style: none; padding: 0; margin: 0; }
    .sidebar-menu a {
        display: flex; align-items: center;
        padding: 10px 14px;
        border-radius: 7px;
        font-size: 13.5px;
        color: var(--text);
        text-decoration: none;
        margin-bottom: 2px;
        transition: background 0.18s, color 0.18s;
    }
    .sidebar-menu a i {
        width: 18px; margin-right: 11px;
        color: var(--muted);
        font-size: 13px;
        transition: color 0.18s;
    }
    .sidebar-menu a:hover { background: var(--beige); color: var(--primary); }
    .sidebar-menu a:hover i { color: var(--primary); }
    .sidebar-menu a.active { background: var(--primary); color: #fff; font-weight: 600; }
    .sidebar-menu a.active i { color: #fff; }
    .sidebar-menu a.logout { color: #c0392b; }
    .sidebar-menu a.logout i { color: #c0392b; }
    .sidebar-menu a.logout:hover { background: #fff5f5; }

    /* ── Main content area ── */
    .dashboard-content {
        flex: 1;
        padding-left: 40px;
        min-width: 0;
    }

    /* ── Override: tắt main-content từ header nếu có ── */
    .site-main-placeholder { display: none !important; }

    /* ── Responsive ── */
    @media (max-width: 820px) {
        .dashboard-wrap { flex-direction: column; }
        .dashboard-sidebar {
            width: 100%; border-right: none;
            border-bottom: 1px solid var(--border);
            padding-right: 0; padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .dashboard-content { padding-left: 0; }
        .sidebar-menu a { font-size: 14px; }
    }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════
     HEADER – tự render để kiểm soát path
     (Không dùng include header.php vì path
      assets/css sẽ bị sai từ views/user/)
══════════════════════════════════════ -->
<header class="main-header">
    <div class="header-container">
        <!-- Logo -->
        <div class="logo">
            <a href="../../index.php">
                <img src="../../assets/images/logo-ntk.png" alt="NTK Logo">
            </a>
        </div>

        <!-- Nav -->
        <nav class="navbar">
            <ul>
                <li><a href="../../index.php">Trang chủ</a></li>
                <li><a href="../../product.php">Shop</a></li>
                <li><a href="../../wishlist.php">Yêu thích</a></li>
                <li><a href="../../promotion.php">Promotion</a></li>
            </ul>
        </nav>

        <!-- Search box (dropdown) -->
        <div class="search-bar-container" id="searchBar" style="display:none;">
            <form action="../../search.php" method="GET" class="search-form">
                <button type="submit" class="submit-search"><i class="fa-solid fa-magnifying-glass"></i></button>
                <input type="text" name="q" placeholder="Bạn đang tìm kiếm gì?..." required>
                <button type="button" class="close-search" onclick="toggleSearch()"><i class="fa-solid fa-xmark"></i></button>
            </form>
        </div>

        <!-- Icons -->
        <div class="header-icons">
            <a href="javascript:void(0)" onclick="toggleSearch()" title="Tìm kiếm">
                <i class="fa-solid fa-magnifying-glass"></i>
            </a>
            <a href="../../wishlist.php" title="Yêu thích">
                <i class="fa-regular fa-heart"></i>
            </a>
            <a href="dashboard.php" title="Tài khoản">
                <i class="fa-solid fa-user"></i>
            </a>
            <a href="../../cart.php" class="cart-icon" title="Giỏ hàng">
                <i class="fa-solid fa-bag-shopping"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-count"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</header>

<!-- ══════════════════════════════════════
     DASHBOARD BODY
══════════════════════════════════════ -->
<div class="dashboard-wrap">

    <!-- ── SIDEBAR ── -->
    <aside class="dashboard-sidebar">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper(substr($current_user['username'], 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($current_user['fullname'] ?: $current_user['username']) ?></div>
                <a href="dashboard.php?view=hoso" class="user-edit">✏️ Sửa hồ sơ</a>
            </div>
        </div>

        <div class="menu-section-title">Tài khoản</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php?view=hoso"       class="<?= $view=='hoso'      ?'active':'' ?>"><i class="fa-regular fa-user"></i>Hồ sơ</a></li>
            
            <li><a href="dashboard.php?view=diachi"     class="<?= $view=='diachi'    ?'active':'' ?>"><i class="fa-solid fa-location-dot"></i>Địa chỉ</a></li>
            <li><a href="dashboard.php?view=doimatkhau" class="<?= $view=='doimatkhau'?'active':'' ?>"><i class="fa-solid fa-lock"></i>Đổi mật khẩu</a></li>
            <li><a href="dashboard.php?view=caidat"     class="<?= $view=='caidat'    ?'active':'' ?>"><i class="fa-solid fa-gear"></i>Cài đặt thông báo</a></li>
        </ul>

        <div class="menu-section-title">Mua sắm</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php?view=donmua"   class="<?= $view=='donmua'  ?'active':'' ?>"><i class="fa-solid fa-file-invoice-dollar"></i>Đơn mua</a></li>
            <li><a href="dashboard.php?view=thongbao" class="<?= $view=='thongbao'?'active':'' ?>"><i class="fa-regular fa-bell"></i>Thông báo</a></li>
        </ul>

        <div class="menu-section-title">Tiện ích</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php?view=vihoantien" class="<?= $view=='vihoantien'?'active':'' ?>"><i class="fa-solid fa-wallet"></i>Ví hoàn tiền</a></li>
            <li><a href="dashboard.php?view=khovoucher" class="<?= ($view=='khovoucher'||$view=='all_coupons')?'active':'' ?>"><i class="fa-solid fa-ticket"></i>Kho voucher</a></li>
        </ul>

        <div style="margin-top: 24px;">
            <ul class="sidebar-menu">
                <li><a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i>Đăng xuất</a></li>
            </ul>
        </div>
    </aside>

    <!-- ── CONTENT ── -->
    <main class="dashboard-content">
        <?php
            switch ($view) {
                case 'hoso':        include 'profile_form.php';          break;
                case 'nganhang':    include 'bank_form.php';              break;
                case 'thongbao':    include 'notifications.php';          break;
                case 'diachi':      include 'address_list.php';           break;
                case 'doimatkhau':  include 'change_password.php';        break;
                case 'caidat':      include 'notification_settings.php';  break;
                case 'donmua':      include 'orders.php';                 break;
                case 'chitietdonhang': include 'order_detail.php';        break;
                case 'vihoantien':  include 'wallet.php';                 break;
                case 'khovoucher':  include 'coupon.php';                 break;
                case 'all_coupons': include 'all_coupons.php';            break;
                default:            include 'profile_form.php';           break;
            }
        ?>
    </main>

</div><!-- /.dashboard-wrap -->

<script>
function toggleSearch() {
    var bar = document.getElementById('searchBar');
    if (bar.style.display === 'none' || bar.style.display === '') {
        bar.style.display = 'block';
        bar.querySelector('input').focus();
    } else {
        bar.style.display = 'none';
    }
}
document.addEventListener('click', function(e) {
    var bar = document.getElementById('searchBar');
    var btn = document.querySelector('[onclick="toggleSearch()"]');
    if (bar && !bar.contains(e.target) && btn && !btn.contains(e.target)) {
        bar.style.display = 'none';
    }
});
</script>

</body>
</html>