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

// 1. Lấy thông báo từ bảng notifications (liên quan đến đơn hàng)
try {
    $stmt_noti = $conn->query("SELECT noti_id, type, title, message, related_order_id, created_at, is_read FROM notifications ORDER BY created_at DESC LIMIT 50");
    while ($row = $stmt_noti->fetch()) {
        $icon = 'fa-bell';
        $color = '#555';
        $link = 'order_detail.php?id=' . $row['related_order_id'];
        
        switch ($row['type']) {
            case 'new_order':
                $icon = 'fa-cart-plus'; $color = '#27ae60'; // Xanh lá
                break;
            case 'order_cancelled':
            case 'cancel_request':
                $icon = 'fa-ban'; $color = '#e74c3c'; // Đỏ
                break;
            case 'return_request':
                $icon = 'fa-rotate-left'; $color = '#e67e22'; // Cam
                break;
            case 'payment_success':
                $icon = 'fa-circle-check'; $color = '#27ae60';
                break;
            default:
                if (strpos($row['title'], 'hủy') !== false) {
                    $icon = 'fa-ban'; $color = '#e74c3c';
                }
                break;
        }

        $read_link = '../controllers/read_notification.php?id=' . $row['noti_id'] . '&redirect=' . urlencode($link);

        $notifications[] = [
            'id' => $row['noti_id'],
            'time' => strtotime($row['created_at']),
            'icon' => $icon, 'color' => $color,
            'label' => $row['title'],
            'link' => $read_link,
            'time_str' => date('H:i d/m', strtotime($row['created_at'])),
            'is_unread' => ($row['is_read'] == 0)
        ];
    }
} catch (PDOException $e) {}

// 2. Sắp hết hàng (stock <= 10) - Động
$stmt = $conn->query("SELECT pv.variant_id, p.name FROM product_variants pv JOIN products p ON pv.product_id = p.product_id WHERE pv.stock > 0 AND pv.stock <= 10");
while ($row = $stmt->fetch()) {
    $notifications[] = [
        'time' => time(), 
        'icon' => 'fa-box-open', 'color' => '#d48806',
        'label' => 'Sắp hết hàng: ' . $row['name'],
        'link' => 'inventory.php?search=' . urlencode($row['name']),
        'time_str' => 'Tồn kho thấp',
        'is_unread' => true
    ];
}

// 3. Hết hàng - Động
$stmt = $conn->query("SELECT pv.variant_id, p.name FROM product_variants pv JOIN products p ON pv.product_id = p.product_id WHERE pv.stock = 0");
while ($row = $stmt->fetch()) {
    $notifications[] = [
        'time' => time() - 3600,
        'icon' => 'fa-triangle-exclamation', 'color' => '#e74c3c',
        'label' => 'Hết hàng: ' . $row['name'],
        'link' => 'inventory.php?search=' . urlencode($row['name']),
        'time_str' => 'Kho rỗng',
        'is_unread' => true
    ];
}

// 4. Voucher sắp hết hạn - Động
$stmt = $conn->query("SELECT coupon_id, code, end_date FROM coupons WHERE end_date BETWEEN NOW() AND NOW() + INTERVAL 3 DAY AND status = 1");
while ($row = $stmt->fetch()) {
    $notifications[] = [
        'time' => strtotime($row['end_date']),
        'icon' => 'fa-ticket', 'color' => '#9b59b6',
        'label' => 'Voucher sắp hết hạn: ' . $row['code'],
        'link' => 'view_coupon.php?id=' . $row['coupon_id'],
        'time_str' => 'Hết hạn: ' . date('d/m', strtotime($row['end_date'])),
        'is_unread' => true
    ];
}

// 8. Đếm thống kê chính xác (Từ Array)
$count_new_orders = count(array_filter($notifications, function($n) { return strpos($n['icon'], 'cart') !== false; }));
$count_warnings   = count(array_filter($notifications, function($n) { return strpos($n['icon'], 'exclamation') !== false || strpos($n['icon'], 'ban') !== false || strpos($n['icon'], 'rotate-left') !== false; }));
$count_products   = count(array_filter($notifications, function($n) { return strpos($n['icon'], 'ticket') !== false || strpos($n['icon'], 'box') !== false; }));

// Sort notifications by time descending
usort($notifications, function($a, $b) {
    return $b['time'] <=> $a['time'];
});

// Tính tổng số lượng chưa đọc
$total_unread = count(array_filter($notifications, function($n) { return !empty($n['is_unread']); }));

// Giới hạn hiển thị top 20 thông báo mới nhất trong dropdown
$notifications = array_slice($notifications, 0, 20);
$notif_count = $total_unread > 0 ? $total_unread : count($notifications);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin NTK Fashion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            background-color: #121212 !important;
            color: #eeeeee !important;
        }
        body.dark-mode .admin-sidebar {
            background: #1e1e1e;
            border-right-color: #2a2a2a;
        }
        body.dark-mode .sidebar-logo {
            border-bottom-color: #2a2a2a;
        }
        body.dark-mode .nav-item {
            color: #bbbbbb;
        }
        body.dark-mode .nav-item:hover {
            background: #252525;
            color: #ffffff;
        }
        body.dark-mode .nav-item.active {
            background: #a6825c;
            color: #121212;
        }
        body.dark-mode .sidebar-footer {
            border-top-color: #2a2a2a;
        }
        body.dark-mode .admin-info-name { color: #ffffff; }
        body.dark-mode .admin-info-email { color: #888888; }
        body.dark-mode .btn-logout {
            background: #252525;
            color: #cccccc;
        }
        body.dark-mode .btn-logout:hover {
            background: #3a1a1a;
            color: #e74c3c;
        }
        body.dark-mode .admin-topbar {
            background: #1e1e1e;
            border-bottom-color: #2a2a2a;
        }
        body.dark-mode .topbar-icon-btn {
            color: #aaaaaa;
        }
        body.dark-mode .topbar-icon-btn:hover {
            background: #252525;
            color: #ffffff;
        }
        body.dark-mode .topbar-divider { background: #2a2a2a; }
        body.dark-mode .topbar-avatar {
            background: #252525;
            color: #ffffff;
        }
        body.dark-mode .admin-main,
        body.dark-mode .admin-content { background: #121212 !important; }

        /* Notification dropdown - Removed in favor of premium Fullscreen overlay */

        /* Cards, Panels & Forms (Dùng trên tất cả các trang admin) */
        body.dark-mode .section-card,
        body.dark-mode .user-table-card,
        body.dark-mode .panel,
        body.dark-mode .review-admin-card,
        body.dark-mode .table-container,
        body.dark-mode .stat-card,
        body.dark-mode .form-card,
        body.dark-mode .admin-box,
        body.dark-mode .detail-grid,
        body.dark-mode .detail-block,
        body.dark-mode .billing-summary,
        body.dark-mode .order-timeline-card,
        body.dark-mode .info-card,
        body.dark-mode .order-box,
        body.dark-mode .customer-box,
        body.dark-mode .shipping-box,
        body.dark-mode .payment-box,
        body.dark-mode .coupon-box,
        body.dark-mode .timeline-card,
        body.dark-mode .timeline-body,
        body.dark-mode .timeline-header,
        body.dark-mode .card,
        body.dark-mode .card-body,
        body.dark-mode .card-header,
        body.dark-mode .address-card,
        body.dark-mode .coupon-card,
        body.dark-mode .notification-card,
        body.dark-mode .profile-card,
        body.dark-mode .wallet-card,
        body.dark-mode .review-card,
        body.dark-mode .review-item,
        body.dark-mode .detail-box {
            background: #1e1e1e !important;
            border-color: #2a2a2a !important;
            color: #ffffff !important;
        }
        body.dark-mode .stat-value {
            color: #ffffff !important;
        }
        body.dark-mode .stat-label {
            color: #888888 !important;
        }
        body.dark-mode .stat-change.neutral {
            background: #252525 !important;
            color: #aaaaaa !important;
        }
        body.dark-mode .page-title,
        body.dark-mode .section-title,
        body.dark-mode .page-header .page-title {
            color: #ffffff !important;
        }
        body.dark-mode .panel-title {
            color: #ffffff !important;
            border-bottom-color: #2a2a2a !important;
        }
        body.dark-mode .form-label {
            color: #dddddd !important;
        }
        body.dark-mode .form-text {
            color: #888888 !important;
        }

        /* Form Controls */
        body.dark-mode .form-control,
        body.dark-mode .input-edit,
        body.dark-mode input,
        body.dark-mode select,
        body.dark-mode textarea {
            background-color: #252525 !important;
            border-color: #333333 !important;
            color: #ffffff !important;
        }
        body.dark-mode .form-control:focus,
        body.dark-mode .input-edit:focus {
            border-color: #a6825c !important;
        }
        body.dark-mode .form-control[readonly] {
            background-color: #1a1a1a !important;
            color: #666666 !important;
        }

        /* Upload & Preview boxes */
        body.dark-mode .upload-box {
            background-color: #1e1e1e !important;
            border-color: #333333 !important;
        }
        body.dark-mode .upload-box:hover {
            background-color: #252525 !important;
        }
        body.dark-mode .upload-icon {
            color: #888888 !important;
        }
        body.dark-mode .upload-text {
            color: #dddddd !important;
        }
        body.dark-mode .upload-text span {
            color: #e5c199 !important;
        }
        body.dark-mode .upload-hint {
            color: #777777 !important;
        }
        body.dark-mode .preview-box {
            background-color: #1e1e1e !important;
            border-color: #333333 !important;
        }
        body.dark-mode .preview-img {
            background-color: #252525 !important;
            color: #888888 !important;
        }
        body.dark-mode .preview-info .name {
            color: #ffffff !important;
        }
        body.dark-mode .preview-info .desc {
            color: #888888 !important;
        }

        /* Tables & Table Cells */
        body.dark-mode .data-table thead th,
        body.dark-mode .user-table th,
        body.dark-mode .prod-table th {
            background: #1a1a1a !important;
            color: #888888 !important;
            border-bottom-color: #2a2a2a !important;
        }
        body.dark-mode .data-table tbody td,
        body.dark-mode .user-table td,
        body.dark-mode .prod-table td {
            color: #cccccc !important;
            border-bottom-color: #252525 !important;
        }
        body.dark-mode .data-table tbody tr:hover,
        body.dark-mode .user-table tbody tr:hover,
        body.dark-mode .prod-table tbody tr:hover { 
            background: #252525 !important; 
        }

        /* Typography & Custom elements */
        body.dark-mode .page-title { color: #ffffff !important; }
        body.dark-mode .page-subtitle { color: #888888 !important; }
        body.dark-mode .cat-id,
        body.dark-mode .prod-id,
        body.dark-mode .info-label {
            color: #aaaaaa !important;
        }
        body.dark-mode .cat-image,
        body.dark-mode .prod-image,
        body.dark-mode .info-image {
            background-color: #252525 !important;
            border-color: #333333 !important;
        }
        body.dark-mode .prod-name,
        body.dark-mode .link-detail {
            color: #ffffff !important;
        }
        body.dark-mode .link-detail:hover {
            color: #e5c199 !important;
        }
        body.dark-mode .sku-badge {
            background-color: #2a2a2a !important;
            color: #cccccc !important;
        }
        body.dark-mode .variant-info {
            color: #dddddd !important;
        }
        body.dark-mode .variant-info .color {
            color: #aaaaaa !important;
        }
        body.dark-mode .info-val,
        body.dark-mode .info-value {
            color: #ffffff !important;
        }
        body.dark-mode .info-row {
            color: #eeeeee !important;
        }
        body.dark-mode .info-icon {
            color: #888888 !important;
        }
        body.dark-mode .info-list {
            border-top-color: #2a2a2a !important;
        }

        /* Intercept inline styled colors on pages to prevent dark text on dark bg */
        body.dark-mode td span[style*="color: #111"],
        body.dark-mode td span[style*="color:#111"],
        body.dark-mode span[style*="color:#555"],
        body.dark-mode span[style*="color: #555"],
        body.dark-mode span[style*="color:#111"],
        body.dark-mode span[style*="color: #111"],
        body.dark-mode div[style*="color:#555"],
        body.dark-mode div[style*="color: #555"],
        body.dark-mode div[style*="color:#111"],
        body.dark-mode div[style*="color: #111"],
        body.dark-mode [style*="color:#333"],
        body.dark-mode [style*="color: #333"],
        body.dark-mode [style*="color:#2f1c00"],
        body.dark-mode [style*="color: #2f1c00"],
        body.dark-mode [style*="color:#222"],
        body.dark-mode [style*="color: #222"],
        body.dark-mode [style*="color:#444"],
        body.dark-mode [style*="color: #444"],
        body.dark-mode td[style*="color:#2f1c00"],
        body.dark-mode td[style*="color: #2f1c00"] {
            color: #eeeeee !important;
        }
        body.dark-mode span[style*="color:#888"],
        body.dark-mode span[style*="color: #888"],
        body.dark-mode div[style*="color:#888"],
        body.dark-mode div[style*="color: #888"] {
            color: #aaaaaa !important;
        }
        
        /* Glassmorphic border and background for status badges in dark mode */
        body.dark-mode .order-status,
        body.dark-mode .status-badge {
            background-color: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid currentColor !important;
        }

        /* Stat row & item overrides for high contrast and unreadability fix */
        body.dark-mode .stat-row {
            background-color: #252525 !important;
            border: 1px solid #333 !important;
        }
        body.dark-mode .stat-item {
            border-right-color: #333 !important;
        }
        body.dark-mode .stat-val {
            color: #ffffff !important;
        }
        body.dark-mode .address-item {
            border-color: #333 !important;
            background-color: #1e1e1e !important;
        }
        body.dark-mode .address-name {
            color: #ffffff !important;
        }
        body.dark-mode .address-txt {
            color: #bbbbbb !important;
        }
        body.dark-mode .address-default {
            background-color: #a6825c !important;
            color: #121212 !important;
        }

        /* Search input & Filters */
        body.dark-mode .search-input,
        body.dark-mode .filter-select {
            background-color: #252525 !important;
            border-color: #333333 !important;
            color: #ffffff !important;
        }
        body.dark-mode .search-input:focus,
        body.dark-mode .filter-select:focus { 
            border-color: #555555 !important; 
        }

        /* Toggle switch */
        body.dark-mode .switch .slider {
            background-color: #444444 !important;
        }
        body.dark-mode .switch input:checked + .slider {
            background-color: #a6825c !important;
        }

        /* Buttons */
        body.dark-mode .btn-primary,
        body.dark-mode .btn-add,
        body.dark-mode .btn-save {
            background-color: #a6825c !important;
            color: #121212 !important;
        }
        body.dark-mode .btn-primary:hover,
        body.dark-mode .btn-add:hover,
        body.dark-mode .btn-save:hover {
            background-color: #c9a47e !important;
        }
        body.dark-mode .btn-secondary,
        body.dark-mode .btn-back,
        body.dark-mode .btn-back-link,
        body.dark-mode .btn-outline {
            background-color: #252525 !important;
            color: #dddddd !important;
            border-color: #555555 !important;
        }
        body.dark-mode .btn-secondary:hover,
        body.dark-mode .btn-back:hover,
        body.dark-mode .btn-back-link:hover,
        body.dark-mode .btn-outline:hover {
            background-color: #333333 !important;
            color: #ffffff !important;
            border-color: #ffffff !important;
        }
        body.dark-mode .btn-danger-outline {
            background-color: #252525 !important;
            color: #ff4d4d !important;
            border-color: #c0392b !important;
        }
        body.dark-mode .btn-danger-outline:hover {
            background-color: #3d1a1a !important;
            color: #ffffff !important;
        }
        body.dark-mode .btn-icon {
            background-color: #252525 !important;
            color: #aaaaaa !important;
        }
        body.dark-mode .btn-icon.edit:hover {
            background-color: #a6825c !important;
            color: #121212 !important;
        }
        body.dark-mode .btn-icon.delete:hover {
            background-color: #c0392b !important;
            color: #ffffff !important;
        }

        /* Badges */
        body.dark-mode .id-badge { background: #252525 !important; color: #aaaaaa !important; }
        body.dark-mode .status-active,
        body.dark-mode .status-instock,
        body.dark-mode .badge-ok {
            background-color: #1c3d27 !important;
            color: #2ecc71 !important;
        }
        body.dark-mode .status-inactive,
        body.dark-mode .status-stopped,
        body.dark-mode .badge-warning,
        body.dark-mode .badge-warn {
            background-color: #3d2d18 !important;
            color: #f39c12 !important;
        }
        body.dark-mode .status-outstock,
        body.dark-mode .badge-danger,
        body.dark-mode .badge-danger {
            background-color: #3d1a1a !important;
            color: #ff4d4d !important;
        }
        body.dark-mode .badge-success {
            background-color: #1c3d27 !important;
            color: #2ecc71 !important;
        }
        body.dark-mode .badge-info {
            background-color: #182d3d !important;
            color: #3498db !important;
        }
        body.dark-mode .badge-primary {
            background-color: #1c2d3d !important;
            color: #3498db !important;
        }

        /* Order Details Specific */
        body.dark-mode .timeline::before {
            background-color: #333333 !important;
        }
        body.dark-mode .timeline-dot {
            border-color: #1e1e1e !important;
            background-color: #333333 !important;
        }
        body.dark-mode .timeline-item.active .timeline-dot {
            background-color: #2ecc71 !important;
        }
        body.dark-mode .timeline-title {
            color: #ffffff !important;
        }
        body.dark-mode .timeline-desc {
            color: #aaaaaa !important;
        }
        body.dark-mode .summary-total {
            color: #ffffff !important;
            border-top-color: #333333 !important;
        }
        body.dark-mode .summary-total span[style*="color:#2f1c00"],
        body.dark-mode .summary-total span[style*="color: #2f1c00"] {
            color: #e5c199 !important;
        }
        body.dark-mode .return-box {
            background-color: #1e1e1e !important;
            border-color: #f39c12 !important;
        }
        body.dark-mode .return-header {
            border-bottom-color: #3a2a1a !important;
        }

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
           NOTIFICATION FULLSCREEN OVERLAY
         ============================================================ */
        .notif-fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            z-index: 10000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: all 0.45s cubic-bezier(0.16, 1, 0.3, 1);
            padding: 30px;
            font-family: 'Inter', "Helvetica Neue", Helvetica, Arial, sans-serif !important;
        }
        .notif-fullscreen *:not(i):not([class*="fa-"]) {
            font-family: 'Inter', "Helvetica Neue", Helvetica, Arial, sans-serif !important;
        }
        body.dark-mode .notif-fullscreen {
            background: rgba(12, 12, 12, 0.93);
        }
        .notif-fullscreen.open {
            opacity: 1;
            pointer-events: auto;
        }
        .notif-fs-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 1;
        }
        .notif-fs-container {
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 28px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 2;
            transform: translateY(40px) scale(0.95);
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        body.dark-mode .notif-fs-container {
            background: rgba(25, 25, 25, 0.8);
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.35);
        }
        .notif-fullscreen.open .notif-fs-container {
            transform: translateY(0) scale(1);
        }
        
        /* Header */
        .notif-fs-header {
            padding: 28px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: rgba(255, 255, 255, 0.25);
        }
        body.dark-mode .notif-fs-header {
            border-bottom-color: rgba(255, 255, 255, 0.05);
            background: rgba(0, 0, 0, 0.1);
        }
        .notif-fs-title-area {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .notif-fs-title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #a6825c, #d4af37);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .notif-fs-title i {
            color: #a6825c;
            -webkit-text-fill-color: #a6825c;
        }
        .notif-fs-subtitle {
            font-size: 13.5px;
            color: #666;
            font-weight: 500;
        }
        body.dark-mode .notif-fs-subtitle {
            color: #aaa;
        }
        .highlight-count {
            color: #e74c3c;
            font-weight: 700;
            background: rgba(231, 76, 60, 0.1);
            padding: 2px 8px;
            border-radius: 6px;
            margin: 0 2px;
        }
        
        /* Close Button */
        .notif-fs-close {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, 0.06);
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
        }
        body.dark-mode .notif-fs-close {
            background: rgba(30, 30, 30, 0.9);
            color: #eee;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .notif-fs-close:hover {
            transform: rotate(90deg) scale(1.1);
            border-color: #a6825c;
            color: #a6825c;
            box-shadow: 0 6px 15px rgba(166, 130, 92, 0.2);
        }
        
        /* Stats Banner */
        .notif-fs-stats {
            padding: 20px 36px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            background: rgba(255, 255, 255, 0.15);
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        }
        body.dark-mode .notif-fs-stats {
            background: rgba(0, 0, 0, 0.05);
            border-bottom-color: rgba(255, 255, 255, 0.03);
        }
        .notif-stat-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0, 0, 0, 0.04);
            border-radius: 16px;
            transition: all 0.3s;
        }
        body.dark-mode .notif-stat-card {
            background: rgba(40, 40, 40, 0.4);
            border-color: rgba(255, 255, 255, 0.04);
        }
        .notif-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
        }
        .notif-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .notif-stat-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .notif-stat-val {
            font-size: 18px;
            font-weight: 800;
            color: #111;
        }
        body.dark-mode .notif-stat-val {
            color: #fff;
        }
        .notif-stat-lbl {
            font-size: 12px;
            color: #777;
            font-weight: 600;
        }
        body.dark-mode .notif-stat-lbl {
            color: #999;
        }
        
        /* Body (Scrollable List) */
        .notif-fs-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px 36px;
        }
        .notif-fs-body::-webkit-scrollbar {
            width: 6px;
        }
        .notif-fs-body::-webkit-scrollbar-track {
            background: transparent;
        }
        .notif-fs-body::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        body.dark-mode .notif-fs-body::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
        }
        .notif-fs-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        
        /* List Item */
        .notif-fs-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 18px 24px;
            background: rgba(255, 255, 255, 0.4);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 18px;
            text-decoration: none;
            color: #111;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        body.dark-mode .notif-fs-item {
            background: rgba(35, 35, 35, 0.3);
            border-color: rgba(255, 255, 255, 0.05);
            color: #eee;
        }
        .notif-fs-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: transparent;
            transition: background 0.3s;
        }
        .notif-fs-item:hover {
            background: rgba(255, 255, 255, 0.85);
            transform: translateY(-3px);
            border-color: rgba(166, 130, 92, 0.3);
            box-shadow: 0 10px 25px rgba(166, 130, 92, 0.06);
        }
        body.dark-mode .notif-fs-item:hover {
            background: rgba(45, 45, 45, 0.6);
            border-color: rgba(166, 130, 92, 0.4);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .notif-fs-item:hover::before {
            background: #a6825c;
        }
        
        .notif-fs-icon-wrap {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            transition: all 0.3s;
        }
        .notif-fs-item:hover .notif-fs-icon-wrap {
            transform: scale(1.1);
        }
        .notif-fs-body-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .notif-fs-label {
            font-size: 14.5px;
            font-weight: 600;
            line-height: 1.4;
            color: #111;
            transition: color 0.2s;
        }
        body.dark-mode .notif-fs-label {
            color: #eee;
        }
        .notif-fs-item:hover .notif-fs-label {
            color: #a6825c;
        }
        .notif-fs-time {
            font-size: 12px;
            color: #888;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        body.dark-mode .notif-fs-time {
            color: #aaa;
        }
        
        /* Action Arrow */
        .notif-fs-action {
            display: flex;
            align-items: center;
            font-size: 13px;
            font-weight: 700;
            color: #a6825c;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            gap: 6px;
        }
        .notif-fs-item:hover .notif-fs-action {
            opacity: 1;
            transform: translateX(0);
        }
        
        /* Empty State */
        .notif-fs-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
        }
        .empty-icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(166, 130, 92, 0.1);
            color: #a6825c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 20px;
            animation: pulse-ring 2s infinite;
        }
        .notif-fs-empty h3 {
            font-size: 18px;
            font-weight: 700;
            color: #111;
            margin-bottom: 8px;
        }
        body.dark-mode .notif-fs-empty h3 {
            color: #fff;
        }
        .notif-fs-empty p {
            font-size: 14px;
            color: #777;
            max-width: 300px;
            line-height: 1.5;
        }
        
        /* Footer Close button */
        .notif-fs-footer {
            padding: 24px 36px;
            display: flex;
            justify-content: center;
            background: rgba(255, 255, 255, 0.25);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        body.dark-mode .notif-fs-footer {
            background: rgba(0, 0, 0, 0.1);
            border-top-color: rgba(255, 255, 255, 0.05);
        }
        .btn-fs-close {
            padding: 12px 32px;
            border-radius: 14px;
            border: none;
            background: #a6825c;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(166, 130, 92, 0.2);
        }
        .btn-fs-close:hover {
            background: #bfa07e;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(166, 130, 92, 0.3);
        }
        
        /* Cascade/Stagger Entry Animations */
        @keyframes fs-item-fade {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .notif-fs-item.animate-in {
            opacity: 0;
            animation: fs-item-fade 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            animation-delay: var(--delay, 0s);
        }
        
        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(166, 130, 92, 0.4);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 15px rgba(166, 130, 92, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(166, 130, 92, 0);
            }
        }0;
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

        /* ============================================================
           ADMIN RESPONSIVE MOBILE STYLES
        ============================================================ */
        @media (max-width: 1024px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .admin-sidebar.open {
                transform: translateX(0);
            }
            .admin-topbar {
                left: 0 !important;
                padding: 0 15px !important;
            }
            .admin-main {
                margin-left: 0 !important;
            }
            .admin-content {
                padding: 15px !important;
            }
            .admin-sidebar-toggle {
                display: flex !important;
                align-items: center;
            }
            /* Table wrap for admin tables to prevent overflow */
            .section-card, .data-table-wrapper, .admin-table-container, .table-responsive {
                overflow-x: auto !important;
                width: 100% !important;
            }
            .data-table {
                min-width: 750px !important;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
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
        <a href="reviews.php" class="nav-item <?= ($admin_current_page === 'reviews.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-star"></i> Quản lý đánh giá
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
        <a href="logout.php" class="btn-logout">
            <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
        </a>
    </div>

</aside>

<!-- ===== TOPBAR — Chuông + Tài khoản góc phải ===== -->
<div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" onclick="toggleAdminSidebar()" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.4); z-index: 998;"></div>

<header class="admin-topbar">
    <!-- Nút Toggle Sidebar di động -->
    <button class="admin-sidebar-toggle" onclick="toggleAdminSidebar()" style="display: none; background: none; border: none; font-size: 20px; color: inherit; cursor: pointer; padding: 0 10px; margin-right: auto;" title="Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <!-- Notification bell and Fullscreen Overlay -->
    <div class="notif-wrap" id="notifWrap">
        <a href="#" class="topbar-icon-btn" title="Thông báo" onclick="toggleNotif(event)">
            <i class="fa-regular fa-bell"></i>
            <?php if ($notif_count > 0): ?>
            <span class="topbar-badge"><?= min($notif_count, 9) ?><?= $notif_count > 9 ? '+' : '' ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="notif-fullscreen" id="notifFullscreen">
        <div class="notif-fs-backdrop" onclick="toggleNotif(event)"></div>
        <div class="notif-fs-container">
            <!-- Header -->
            <div class="notif-fs-header">
                <div class="notif-fs-title-area">
                    <h2 class="notif-fs-title"><i class="fa-solid fa-bell"></i> THÔNG BÁO HỆ THỐNG</h2>
                    <p class="notif-fs-subtitle">Bạn đang có <span class="highlight-count"><?= $notif_count ?></span> thông báo cần lưu ý</p>
                </div>
                <button class="notif-fs-close" onclick="toggleNotif(event)" title="Đóng toàn màn hình">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Quick Stats Banner -->
            <div class="notif-fs-stats">
                <div class="notif-stat-card">
                    <div class="notif-stat-icon" style="background: rgba(39, 174, 96, 0.12); color: #27ae60;">
                        <i class="fa-solid fa-cart-plus"></i>
                    </div>
                    <div class="notif-stat-info">
                        <span class="notif-stat-val"><?= $count_new_orders ?></span>
                        <span class="notif-stat-lbl">Đơn hàng mới</span>
                    </div>
                </div>
                <div class="notif-stat-card">
                    <div class="notif-stat-icon" style="background: rgba(231, 76, 60, 0.12); color: #e74c3c;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="notif-stat-info">
                        <span class="notif-stat-val"><?= $count_warnings ?></span>
                        <span class="notif-stat-lbl">Cảnh báo / Hủy đơn</span>
                    </div>
                </div>
                <div class="notif-stat-card">
                    <div class="notif-stat-icon" style="background: rgba(166, 130, 92, 0.12); color: #a6825c;">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div class="notif-stat-info">
                        <span class="notif-stat-val"><?= $count_products ?></span>
                        <span class="notif-stat-lbl">Sản phẩm & Coupon</span>
                    </div>
                </div>
            </div>

            <!-- Notification List -->
            <div class="notif-fs-body">
                <div class="notif-fs-list">
                    <?php if (empty($notifications)): ?>
                    <div class="notif-fs-empty">
                        <div class="empty-icon-wrap">
                            <i class="fa-regular fa-bell-slash"></i>
                        </div>
                        <h3>Hộp thư sạch sẽ!</h3>
                        <p>Hiện tại bạn không có thông báo mới nào từ hệ thống.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($notifications as $index => $n): ?>
                    <a href="<?= htmlspecialchars($n['link']) ?>" class="notif-fs-item animate-in <?= !empty($n['is_unread']) ? 'unread-noti' : '' ?>" style="--delay: <?= $index * 0.05 ?>s; <?= !empty($n['is_unread']) ? 'background: rgba(166,130,92,0.05); border-left: 3px solid #e74c3c;' : 'opacity: 0.8;' ?>">
                        <div class="notif-fs-icon-wrap" style="background:<?= $n['color'] ?>18; color:<?= $n['color'] ?>; box-shadow: 0 0 15px <?= $n['color'] ?>15;">
                            <i class="fa-solid <?= $n['icon'] ?>"></i>
                        </div>
                        <div class="notif-fs-body-content" style="position: relative;">
                            <div class="notif-fs-label" <?= !empty($n['is_unread']) ? 'style="font-weight: 800; color: #000;"' : 'style="font-weight: 400; color: #555;"' ?>>
                                <?= htmlspecialchars($n['label']) ?>
                                <?php if (!empty($n['is_unread'])): ?>
                                    <span style="display:inline-block; width:8px; height:8px; background:#e74c3c; border-radius:50%; margin-left:5px; vertical-align:middle;"></span>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($n['time_str'])): ?>
                            <div class="notif-fs-time" <?= !empty($n['is_unread']) ? 'style="font-weight: 600; color: #333;"' : '' ?>><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($n['time_str']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="notif-fs-action">
                            <span>Xử lý <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="notif-fs-footer">
                <button class="btn-fs-close" onclick="toggleNotif(event)">Quay lại Bảng Điều Khiển</button>
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
    if (localStorage.getItem('ntk_dark') === '1') {
        document.body.classList.add('dark-mode');
    }
})();

function toggleDarkMode() {
    const body = document.body;
    const isDark = body.classList.toggle('dark-mode');
    localStorage.setItem('ntk_dark', isDark ? '1' : '0');
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

// Đồng bộ tức thời khi đổi dark mode ở tab khác (user view <=> admin view)
window.addEventListener('storage', function(e) {
    if (e.key === 'ntk_dark') {
        const isDark = e.newValue === '1';
        if (isDark) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
        updateDmIcon(isDark);
    }
});

// ── NOTIFICATION ────────────────────────────────────────────
function toggleNotif(e) {
    if (e) e.preventDefault();
    const modal = document.getElementById('notifFullscreen');
    if (!modal) return;
    
    const isOpen = modal.classList.toggle('open');
    
    // Ngăn chặn cuộn trang body khi đang hiển thị overlay
    if (isOpen) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

// Bổ sung phím tắt Escape để tắt thông báo nhanh
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('notifFullscreen');
        if (modal && modal.classList.contains('open')) {
            toggleNotif();
        }
    }
});

function toggleAdminSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const backdrop = document.getElementById('adminSidebarBackdrop');
    if (sidebar) {
        const isOpen = sidebar.classList.toggle('open');
        if (backdrop) backdrop.style.display = isOpen ? 'block' : 'none';
    }
}
</script>

<!-- Main content starts here -->
<main class="admin-main">
<div class="admin-content">


