<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lấy tên trang hiện tại để highlight menu active
$admin_current_page = basename($_SERVER['PHP_SELF']);
// Map tên file → tiêu đề trang
$page_titles = [
    'dashboard.php'  => 'Dashboard',
    'categories.php' => 'Danh mục',
    'products.php'   => 'Sản phẩm',
    'orders.php'     => 'Đơn hàng',
    'inventory.php'  => 'Tồn kho',
    'coupons.php'    => 'Coupon',
    'accounts.php'   => 'Tài khoản',
];
// ── THÔNG BÁO ADMIN ────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
$notifications = [];

// 1. Đơn hàng mới (order_status = 0, trong 24h)
$stmt = $conn->query("SELECT order_id, order_date FROM orders WHERE order_status = 0 AND order_date >= NOW() - INTERVAL 24 HOUR ORDER BY order_date DESC LIMIT 5");
while ($row = $stmt->fetch()) {
    $notifications[] = [
        'time' => strtotime($row['order_date']),
        'icon' => 'fa-cart-plus', 'color' => '#2f6fdd',
        'label' => 'Đơn hàng mới: #' . $row['order_id'],
        'link' => 'order_detail.php?id=' . $row['order_id'],
        'time_str' => date('H:i d/m', strtotime($row['order_date']))
    ];
}

// 2. Yêu cầu hủy đơn (order_status = 5)
$stmt = $conn->query("SELECT order_id, order_date FROM orders WHERE order_status = 5 ORDER BY order_date DESC LIMIT 5");
while ($row = $stmt->fetch()) {
    $notifications[] = [
        'time' => strtotime($row['order_date']),
        'icon' => 'fa-ban', 'color' => '#e74c3c',
        'label' => 'Yêu cầu hủy đơn: #' . $row['order_id'],
        'link' => 'order_detail.php?id=' . $row['order_id'],
        'time_str' => date('H:i d/m', strtotime($row['order_date']))
    ];
}

// 3. Thanh toán thành công (payment_status = 1, trong 24h)
$stmt = $conn->query("SELECT order_id, order_date FROM orders WHERE payment_status = 1 AND order_date >= NOW() - INTERVAL 24 HOUR ORDER BY order_date DESC LIMIT 5");
while ($row = $stmt->fetch()) {
    $notifications[] = [
        'time' => strtotime($row['order_date']),
        'icon' => 'fa-circle-check', 'color' => '#27ae60',
        'label' => 'Đã thanh toán: #' . $row['order_id'],
        'link' => 'order_detail.php?id=' . $row['order_id'],
        'time_str' => date('H:i d/m', strtotime($row['order_date']))
    ];
}

// 4. Sắp hết hàng (stock <= 10)
$stmt = $conn->query("SELECT pv.variant_id, p.name FROM product_variants pv JOIN products p ON pv.product_id = p.product_id WHERE pv.stock > 0 AND pv.stock <= 10 LIMIT 5");
while ($row = $stmt->fetch()) {
    $notifications[] = [
        'time' => time(), 
        'icon' => 'fa-box-open', 'color' => '#d48806',
        'label' => 'Sắp hết hàng: ' . $row['name'],
        'link' => 'inventory.php?search=' . urlencode($row['name']),
        'time_str' => 'Tồn kho thấp'
    ];
}

// 5. Hết hàng
$stmt = $conn->query("SELECT pv.variant_id, p.name FROM product_variants pv JOIN products p ON pv.product_id = p.product_id WHERE pv.stock = 0 LIMIT 5");
while ($row = $stmt->fetch()) {
    $notifications[] = [
        'time' => time() - 3600, // Đẩy xuống một chút
        'icon' => 'fa-triangle-exclamation', 'color' => '#e74c3c',
        'label' => 'Hết hàng: ' . $row['name'],
        'link' => 'inventory.php?search=' . urlencode($row['name']),
        'time_str' => 'Kho rỗng'
    ];
}

// 6. Voucher sắp hết hạn
$stmt = $conn->query("SELECT coupon_id, code, end_date FROM coupons WHERE end_date BETWEEN NOW() AND NOW() + INTERVAL 3 DAY AND status = 1 LIMIT 5");
while ($row = $stmt->fetch()) {
    $notifications[] = [
        'time' => strtotime($row['end_date']),
        'icon' => 'fa-ticket', 'color' => '#9b59b6',
        'label' => 'Voucher sắp hết hạn: ' . $row['code'],
        'link' => 'view_coupon.php?id=' . $row['coupon_id'],
        'time_str' => 'Hết hạn: ' . date('d/m', strtotime($row['end_date']))
    ];
}

// 7. User mới
$stmt = $conn->query("SELECT user_id, fullname, created_at FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY AND role = 0 ORDER BY created_at DESC LIMIT 5");
while ($row = $stmt->fetch()) {
    $notifications[] = [
        'time' => strtotime($row['created_at']),
        'icon' => 'fa-user-plus', 'color' => '#2980b9',
        'label' => 'Thành viên mới: ' . $row['fullname'],
        'link' => 'account_detail.php?id=' . $row['user_id'],
        'time_str' => date('H:i d/m', strtotime($row['created_at']))
    ];
}

// Sort notifications by time descending
usort($notifications, function($a, $b) {
    return $b['time'] <=> $a['time'];
});

// Giới hạn hiển thị top 10 thông báo mới nhất trong dropdown
$notifications = array_slice($notifications, 0, 10);
$notif_count = count($notifications);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($current_page_title) ?> — Admin NTK Fashion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ============================================================
           RESET & BASE — Font: Helvetica Neue | Color: NTK Brand
        ============================================================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            background-color: #ffffff;
            color: #111111;
            transition: background-color 0.3s, color 0.3s;
        }

        /* ============================================================
           DARK MODE — Toggle bằng class .dark-mode trên <body>
        ============================================================ */
        body.dark-mode {
            background-color: #0f0f0f;
            color: #e0e0e0;
        }
        body.dark-mode .admin-sidebar {
            background: #161616;
            border-right-color: #2a2a2a;
        }
        body.dark-mode .sidebar-logo {
            border-bottom-color: #2a2a2a;
        }
        body.dark-mode .nav-item {
            color: #bbb;
        }
        body.dark-mode .nav-item:hover {
            background: #222;
            color: #fff;
        }
        body.dark-mode .nav-item.active {
            background: #2a2a2a;
            color: #fff;
        }
        body.dark-mode .sidebar-footer {
            border-top-color: #2a2a2a;
        }
        body.dark-mode .admin-info-name { color: #eee; }
        body.dark-mode .admin-info-email { color: #888; }
        body.dark-mode .btn-logout {
            background: #1e1e1e;
            color: #ccc;
        }
        body.dark-mode .btn-logout:hover {
            background: #2a1010;
            color: #e74c3c;
        }
        body.dark-mode .admin-topbar {
            background: #161616;
            border-bottom-color: #2a2a2a;
        }
        body.dark-mode .topbar-icon-btn {
            color: #aaa;
        }
        body.dark-mode .topbar-icon-btn:hover {
            background: #2a2a2a;
            color: #fff;
        }
        body.dark-mode .topbar-divider { background: #2a2a2a; }
        body.dark-mode .topbar-avatar {
            background: #2a2a2a;
            color: #eee;
        }
        body.dark-mode .admin-main,
        body.dark-mode .admin-content { background: #0f0f0f; }
        /* Notification dropdown */
        body.dark-mode .notif-dropdown {
            background: #1a1a1a;
            border-color: #2a2a2a;
            box-shadow: 0 8px 30px rgba(0,0,0,0.5);
        }
        body.dark-mode .notif-header {
            color: #ddd;
            border-bottom-color: #2a2a2a;
        }
        body.dark-mode .notif-item {
            color: #ddd;
            border-bottom-color: #222;
        }
        body.dark-mode .notif-item:hover { background: #222; }
        body.dark-mode .notif-label { color: #eee; }
        body.dark-mode .notif-empty { color: #666; }
        /* Cards & Tables (dùng trên các trang admin) */
        body.dark-mode .section-card,
        body.dark-mode .user-table-card {
            background: #161616 !important;
            border-color: #2a2a2a !important;
        }
        body.dark-mode .data-table thead th,
        body.dark-mode .user-table th {
            background: #1e1e1e !important;
            color: #777 !important;
            border-bottom-color: #2a2a2a !important;
        }
        body.dark-mode .data-table tbody td,
        body.dark-mode .user-table td {
            color: #ccc !important;
            border-bottom-color: #1e1e1e !important;
        }
        body.dark-mode .data-table tbody tr:hover,
        body.dark-mode .user-table tbody tr:hover { background: #1e1e1e !important; }
        body.dark-mode .search-input,
        body.dark-mode .filter-select {
            background: #1e1e1e !important;
            border-color: #333 !important;
            color: #ddd !important;
        }
        body.dark-mode .search-input:focus,
        body.dark-mode .filter-select:focus { border-color: #555 !important; }
        body.dark-mode .id-badge { background: #252525 !important; color: #aaa !important; }
        body.dark-mode .page-title { color: #eee !important; }
        body.dark-mode .page-subtitle { color: #666 !important; }

        /* Dark mode toggle button */
        .dm-toggle {
            position: relative;
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #555; cursor: pointer; font-size: 17px;
            background: none; border: none;
            transition: background 0.2s, color 0.2s;
        }
        .dm-toggle:hover { background: #f5f1eb; color: #2f1c00; }
        body.dark-mode .dm-toggle { color: #aaa; }
        body.dark-mode .dm-toggle:hover { background: #2a2a2a; color: #fff; }

        /* Nút về trang chủ */
        .btn-home-exit {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 14px; margin: 0 10px 10px;
            border-radius: 8px; font-size: 13px; font-weight: 500;
            color: #555; text-decoration: none;
            border: 1px dashed #ddd;
            transition: all 0.2s;
        }
        .btn-home-exit:hover {
            border-color: #2f1c00; color: #2f1c00; background: #f5f1eb;
        }
        body.dark-mode .btn-home-exit {
            color: #888; border-color: #333;
        }
        body.dark-mode .btn-home-exit:hover {
            background: #222; color: #fff; border-color: #555;
        }

        /* ============================================================
           SIDEBAR — Cột trái cố định
        ============================================================ */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 210px;
            height: 100vh;
            background: #ffffff;
            border-right: 1px solid #e5e5e5;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        /* Logo */
        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 58px;
            border-bottom: 1px solid #e5e5e5;
            text-decoration: none;
        }
        .sidebar-logo img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        /* Navigation */
        .sidebar-nav {
            flex: 1;
            padding: 14px 10px;
            overflow-y: auto;
        }
        .sidebar-nav::-webkit-scrollbar { width: 0; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 7px;
            color: #555555;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.15s ease;
            margin-bottom: 2px;
        }
        .nav-item i {
            width: 17px;
            text-align: center;
            font-size: 13px;
            flex-shrink: 0;
            opacity: 0.6;
        }
        .nav-item:hover {
            background: #f5f1eb;
            color: #2f1c00;
        }
        .nav-item:hover i { opacity: 1; }
        .nav-item.active {
            background: #2f1c00;
            color: #ffffff;
            font-weight: 500;
        }
        .nav-item.active i { opacity: 1; }

        /* Footer */
        .sidebar-footer {
            padding: 14px 16px;
            border-top: 1px solid #e5e5e5;
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .admin-info-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f5f1eb;
            border: 1px solid #e5e5e5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #2f1c00;
            flex-shrink: 0;
        }
        .admin-info-name {
            font-size: 13px;
            font-weight: 600;
            color: #111111;
        }
        .admin-info-email {
            font-size: 11px;
            color: #aaa;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 130px;
        }
        .btn-logout {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 12px;
            border-radius: 7px;
            color: #c0392b;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.15s;
        }
        .btn-logout:hover { background: #fdf0ef; }
        .btn-logout i { font-size: 13px; }

        /* ============================================================
           TOPBAR — Thanh ngang cố định phía trên nội dung chính
        ============================================================ */
        .admin-topbar {
            position: fixed;
            top: 0;
            left: 210px;
            right: 0;
            height: 58px;
            background: #ffffff;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 28px;
            gap: 10px;
            z-index: 999;
        }
        .topbar-icon-btn {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #555;
            cursor: pointer;
            font-size: 18px;
            transition: background 0.2s;
            text-decoration: none;
        }
        .topbar-icon-btn:hover { background: #f5f1eb; color: #2f1c00; }
        .topbar-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            background: #c0392b;
            border-radius: 50%;
            font-size: 9px;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .topbar-divider {
            width: 1px;
            height: 22px;
            background: #e5e5e5;
            margin: 0 4px;
        }
        .topbar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #f5f1eb;
            color: #2f1c00;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }

        /* ============================================================
           NOTIFICATION DROPDOWN
        ============================================================ */
        .notif-wrap { position: relative; }
        .notif-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 320px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            z-index: 2000;
            overflow: hidden;
        }
        .notif-wrap:hover .notif-dropdown,
        .notif-wrap.open .notif-dropdown { display: block; }
        .notif-header {
            padding: 14px 18px 10px;
            font-size: 13px;
            font-weight: 700;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #f0f0f0;
        }
        .notif-list { max-height: 340px; overflow-y: auto; }
        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 18px;
            border-bottom: 1px solid #f9f9f9;
            text-decoration: none;
            color: #111;
            transition: background 0.15s;
        }
        .notif-item:hover { background: #fafaf8; }
        .notif-item:last-child { border-bottom: none; }
        .notif-icon-wrap {
            width: 34px; height: 34px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .notif-body { flex: 1; }
        .notif-label { font-size: 13px; color: #111; line-height: 1.4; }
        .notif-time  { font-size: 11px; color: #aaa; margin-top: 2px; }
        .notif-empty { padding: 24px 18px; text-align: center; color: #aaa; font-size: 13px; }

        .admin-main {
            margin-left: 210px;
            padding-top: 58px;
            min-height: 100vh;
            background: #ffffff;
        }
        .admin-content {
            padding: 30px;
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="admin-sidebar">

    <!-- Logo -->
    <a href="dashboard.php" class="sidebar-logo">
        <img src="../assets/images/logo-ntk.png" alt="NTK Fashion Logo">
    </a>

    <!-- Navigation (không có section title) -->
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= ($admin_current_page === 'dashboard.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-table-columns"></i> Dashboard
        </a>
        <a href="categories.php" class="nav-item <?= ($admin_current_page === 'categories.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i> Danh mục
        </a>
        <a href="products.php" class="nav-item <?= ($admin_current_page === 'products.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-shirt"></i> Sản phẩm
        </a>
        <a href="orders.php" class="nav-item <?= ($admin_current_page === 'orders.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-cart-shopping"></i> Đơn hàng
        </a>
        <a href="inventory.php" class="nav-item <?= ($admin_current_page === 'inventory.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i> Tồn kho
        </a>
        <a href="coupons.php" class="nav-item <?= ($admin_current_page === 'coupons.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-ticket"></i> Coupon
        </a>
        <a href="accounts.php" class="nav-item <?= ($admin_current_page === 'accounts.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> Tài khoản
        </a>
    </nav>

    <!-- Thoát về trang khách -->
    <a href="../index.php" class="btn-home-exit" title="Xem trang web như khách hàng" target="_blank">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem trang khách
    </a>

    <!-- Footer: Admin info + Logout -->
    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-info-avatar">
                <?= strtoupper(substr($_SESSION['fullname'] ?? 'A', 0, 1)) ?>
            </div>
            <div>
                <div class="admin-info-name"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin NTK') ?></div>
                <div class="admin-info-email"><?= htmlspecialchars($_SESSION['username'] ?? 'admin@ntk.vn') ?></div>
            </div>
        </div>
        <a href="../views/logout.php" class="btn-logout">
            <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
        </a>
    </div>

</aside>

<!-- ===== TOPBAR — Chuông + Tài khoản góc phải ===== -->
<header class="admin-topbar">
    <!-- Notification bell with dropdown -->
    <div class="notif-wrap" id="notifWrap">
        <a href="#" class="topbar-icon-btn" title="Thông báo" onclick="toggleNotif(event)">
            <i class="fa-regular fa-bell"></i>
            <?php if ($notif_count > 0): ?>
            <span class="topbar-badge"><?= min($notif_count, 9) ?><?= $notif_count > 9 ? '+' : '' ?></span>
            <?php endif; ?>
        </a>
        <div class="notif-dropdown" id="notifDropdown">
            <div class="notif-header">Thông báo</div>
            <div class="notif-list">
                <?php if (empty($notifications)): ?>
                <div class="notif-empty"><i class="fa-regular fa-bell-slash" style="margin-right:6px;"></i>Không có thông báo mới</div>
                <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                <a href="<?= htmlspecialchars($n['link']) ?>" class="notif-item">
                    <div class="notif-icon-wrap" style="background:<?= $n['color'] ?>22; color:<?= $n['color'] ?>;">
                        <i class="fa-solid <?= $n['icon'] ?>"></i>
                    </div>
                    <div class="notif-body">
                        <div class="notif-label"><?= htmlspecialchars($n['label']) ?></div>
                        <?php if ($n['time']): ?>
                        <div class="notif-time"><?= htmlspecialchars($n['time']) ?></div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="topbar-divider"></div>
    <!-- Dark mode toggle -->
    <button class="dm-toggle" id="dmToggle" onclick="toggleDarkMode()" title="Bật/tắt chế độ tối">
        <i class="fa-regular fa-moon" id="dmIcon"></i>
    </button>
    <div class="topbar-divider"></div>
    <div class="topbar-avatar" title="<?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin') ?>">
        <?= strtoupper(substr($_SESSION['fullname'] ?? 'A', 0, 1)) ?>
    </div>
</header>

<script>
// ── DARK MODE ──────────────────────────────────────────────
(function(){
    // Áp dụng ngay khi load để tránh flash
    if (localStorage.getItem('ntk_admin_dark') === '1') {
        document.body.classList.add('dark-mode');
    }
})();

function toggleDarkMode() {
    const body = document.body;
    const isDark = body.classList.toggle('dark-mode');
    localStorage.setItem('ntk_admin_dark', isDark ? '1' : '0');
    updateDmIcon(isDark);
}

function updateDmIcon(isDark) {
    const icon = document.getElementById('dmIcon');
    if (!icon) return;
    icon.className = isDark ? 'fa-solid fa-sun' : 'fa-regular fa-moon';
    document.getElementById('dmToggle').title = isDark ? 'Tắt chế độ tối' : 'Bật chế độ tối';
}

// Đồng bộ icon ngay sau khi DOM sẵn sàng
document.addEventListener('DOMContentLoaded', function(){
    const isDark = document.body.classList.contains('dark-mode');
    updateDmIcon(isDark);
});

// ── NOTIFICATION ────────────────────────────────────────────
function toggleNotif(e) {
    e.preventDefault();
    const wrap = document.getElementById('notifWrap');
    wrap.classList.toggle('open');
    document.addEventListener('click', function closeNotif(ev) {
        if (!wrap.contains(ev.target)) {
            wrap.classList.remove('open');
            document.removeEventListener('click', closeNotif);
        }
    });
}
</script>

<!-- Main content starts here -->
<main class="admin-main">
<div class="admin-content">
