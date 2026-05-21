<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lấy tên trang hiện tại để highlight menu active
$admin_current_page = basename($_SERVER['PHP_SELF']);
// Map tên file → tiêu đề trang
$page_titles = [
    'dashboard.php'  => 'Trang Chủ',
    'categories.php' => 'Danh mục',
    'products.php'   => 'Sản phẩm',
    'orders.php'     => 'Đơn hàng',
    'inventory.php'  => 'Tồn kho',
    'coupons.php'    => 'Coupon',
    'accounts.php'   => 'Tài khoản',
];
$current_page_title = $page_titles[$admin_current_page] ?? '';
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
    <title>Admin NTK Fashion</title>
    <link rel="icon" type="image/png" href="data:image/png;base64,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">
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
        <img src="data:image/png;base64,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" alt="NTK Fashion Logo">
    </a>

    <!-- Navigation (không có section title) -->
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= ($admin_current_page === 'dashboard.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-table-columns"></i> Trang Chủ
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
