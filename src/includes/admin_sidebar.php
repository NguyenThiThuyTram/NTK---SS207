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
$current_page_title = $page_titles[$admin_current_page] ?? 'Admin';
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
            background-color: #f5f1eb;
            color: #111111;
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
           MAIN CONTENT WRAPPER
        ============================================================ */
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
    <a href="#" class="topbar-icon-btn" title="Thông báo">
        <i class="fa-regular fa-bell"></i>
        <span class="topbar-badge">3</span>
    </a>
    <div class="topbar-divider"></div>
    <div class="topbar-avatar" title="<?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin') ?>">
        <?= strtoupper(substr($_SESSION['fullname'] ?? 'A', 0, 1)) ?>
    </div>
</header>

<!-- Main content starts here -->
<main class="admin-main">
<div class="admin-content">
