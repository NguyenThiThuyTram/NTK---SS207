<?php
ob_start();
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
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản - NTK Fashion</title>
    <link rel="icon" type="image/png" href="../../assets/images/logo-ntk.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
    :root {
        --primary:  #2f1c00;
        --bg:       #ffffff;
        --beige:    #f5f1eb;
        --border:   #e5e5e5;
        --text:     #111111;
        --muted:    #555555;
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background: var(--bg); color: var(--text); }

    /* Header sticky */
    .main-header { position: sticky; top: 0; z-index: 1000; }

    /* Search drop */
    .search-bar-container {
        position: absolute !important; top: 100% !important; left: 0 !important;
        width: 100% !important; background: #fff;
        padding: 20px 5%; box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        border-top: 1px solid var(--border); z-index: 999;
    }
    body.dark-mode .search-bar-container { background: #1a1a1a !important; border-top-color: #333 !important; }

    /* Dashboard layout */
    .dashboard-wrap {
        display: flex; max-width: 1240px;
        margin: 40px auto 60px; padding: 0 24px; gap: 0; min-height: 600px;
    }

    /* Sidebar */
    .dashboard-sidebar { width: 240px; flex-shrink: 0; padding-right: 24px; border-right: 1px solid var(--border); }
    .user-card { display: flex; align-items: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 1px dashed var(--border); }
    .user-avatar {
        width: 48px; height: 48px; border-radius: 50%;
        background: var(--beige); border: 1.5px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; font-weight: 700; color: var(--primary);
        margin-right: 14px; flex-shrink: 0;
    }
    .user-name { font-weight: 600; font-size: 14.5px; line-height: 1.3; }
    .user-edit { font-size: 12px; color: var(--muted); text-decoration: none; }
    .user-edit:hover { color: var(--primary); }

    .menu-section-title {
        font-size: 10.5px; font-weight: 700; color: #bbb;
        letter-spacing: 0.9px; text-transform: uppercase; margin: 22px 0 7px 14px;
    }
    .sidebar-menu { list-style: none; padding: 0; margin: 0; }
    .sidebar-menu a {
        display: flex; align-items: center; padding: 10px 14px;
        border-radius: 7px; font-size: 13.5px; color: var(--text);
        text-decoration: none; margin-bottom: 2px; transition: background 0.18s, color 0.18s;
    }
    .sidebar-menu a i { width: 18px; margin-right: 11px; color: var(--muted); font-size: 13px; transition: color 0.18s; }
    .sidebar-menu a:hover { background: var(--beige); color: var(--primary); }
    .sidebar-menu a:hover i { color: var(--primary); }
    .sidebar-menu a.active { background: var(--primary); color: #fff; font-weight: 600; }
    .sidebar-menu a.active i { color: #fff; }
    .sidebar-menu a.logout { color: #c0392b; }
    .sidebar-menu a.logout i { color: #c0392b; }
    .sidebar-menu a.logout:hover { background: #fff5f5; }

    /* Content area */
    .dashboard-content { flex: 1; padding-left: 40px; min-width: 0; }

    /* Responsive */
    @media (max-width: 820px) {
        .dashboard-wrap { flex-direction: column; }
        .dashboard-sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--border); padding-right: 0; padding-bottom: 20px; margin-bottom: 24px; }
        .dashboard-content { padding-left: 0; }
    }

    /* ============================================================
       HỆ THỐNG ĐỒNG BỘ DARK MODE TOÀN DIỆN CHO USER DASHBOARD
       ============================================================ */
    body.dark-mode {
        background-color: #121212 !important;
        color: #eeeeee !important;
    }
    body.dark-mode .dashboard-sidebar {
        border-right-color: #2a2a2a !important;
    }
    body.dark-mode .user-card {
        border-bottom-color: #2a2a2a !important;
    }
    body.dark-mode .sidebar-menu a {
        color: #cccccc !important;
    }
    body.dark-mode .sidebar-menu a:hover {
        background: #252525 !important;
        color: #e5c199 !important;
    }
    body.dark-mode .sidebar-menu a:hover i {
        color: #e5c199 !important;
    }
    body.dark-mode .sidebar-menu a.active {
        background: #a6825c !important;
        color: #121212 !important;
    }
    body.dark-mode .sidebar-menu a.active i {
        color: #121212 !important;
    }
    body.dark-mode .menu-section-title {
        color: #666666 !important;
    }
    body.dark-mode .dashboard-wrap {
        background-color: #121212 !important;
    }
    body.dark-mode .dashboard-content {
        background-color: #121212 !important;
        color: #eeeeee !important;
    }

    /* Headings in dashboard */
    body.dark-mode .dashboard-content h1,
    body.dark-mode .dashboard-content h2,
    body.dark-mode .dashboard-content h3,
    body.dark-mode .dashboard-content h4,
    body.dark-mode .dashboard-content h5,
    body.dark-mode .dashboard-content h6 {
        color: #ffffff !important;
    }

    /* Subpage Containers */
    body.dark-mode .profile-container,
    body.dark-mode .wallet-wrapper,
    body.dark-mode .order-container,
    body.dark-mode .coupon-container,
    body.dark-mode .coupon-card,
    body.dark-mode .address-item,
    body.dark-mode .address-card,
    body.dark-mode .bank-card,
    body.dark-mode .order-card,
    body.dark-mode .empty-order,
    body.dark-mode .tx-empty,
    body.dark-mode .balance-card {
        background-color: #1e1e1e !important;
        border-color: #2a2a2a !important;
        color: #eeeeee !important;
    }

    /* Form labels & descriptions */
    body.dark-mode .form-group label,
    body.dark-mode .toggle-info .title,
    body.dark-mode .tx-title,
    body.dark-mode .balance-label {
        color: #dddddd !important;
    }
    body.dark-mode .form-text,
    body.dark-mode .tx-date,
    body.dark-mode .balance-desc {
        color: #888888 !important;
    }

    /* Form input fields & textareas */
    body.dark-mode .form-control,
    body.dark-mode .input-edit,
    body.dark-mode select,
    body.dark-mode textarea {
        background-color: #252525 !important;
        border-color: #333333 !important;
        color: #ffffff !important;
    }
    body.dark-mode .form-control:focus,
    body.dark-mode select:focus,
    body.dark-mode textarea:focus {
        border-color: #a6825c !important;
    }
    body.dark-mode .form-control:disabled {
        background-color: #1a1a1a !important;
        color: #666666 !important;
    }

    /* Tables & Order Details */
    body.dark-mode .order-tabs {
        background-color: #1e1e1e !important;
        border-bottom-color: #2a2a2a !important;
    }
    body.dark-mode .order-tabs a {
        color: #aaaaaa !important;
    }
    body.dark-mode .order-tabs a:hover {
        color: #e5c199 !important;
    }
    body.dark-mode .order-tabs a.active {
        color: #e5c199 !important;
        border-bottom-color: #e5c199 !important;
    }
    body.dark-mode .order-search {
        background-color: #252525 !important;
    }
    body.dark-mode .order-search input {
        color: #ffffff !important;
    }
    body.dark-mode .order-header {
        border-bottom-color: #2a2a2a !important;
    }
    body.dark-mode .order-item {
        border-bottom-color: #2a2a2a !important;
    }
    body.dark-mode .item-name {
        color: #ffffff !important;
    }
    body.dark-mode .item-variant {
        color: #aaaaaa !important;
    }
    body.dark-mode .item-qty {
        color: #888888 !important;
    }
    body.dark-mode .item-price,
    body.dark-mode .total-price {
        color: #e5c199 !important;
    }
    body.dark-mode .order-footer {
        background-color: #1a1a1a !important;
    }
    body.dark-mode .order-total {
        color: #dddddd !important;
    }
    body.dark-mode .item-img {
        border-color: #333333 !important;
        background-color: #252525 !important;
    }

    /* Modal dialogs inside orders (Return order modal) */
    body.dark-mode div[style*="background:#fff"],
    body.dark-mode div[style*="background: #fff"],
    body.dark-mode div[style*="background-color:#fff"],
    body.dark-mode div[style*="background-color: #fff"] {
        background-color: #1e1e1e !important;
        color: #eeeeee !important;
        border-color: #2a2a2a !important;
    }
    body.dark-mode h3[style*="color:#333"],
    body.dark-mode h3[style*="color: #333"],
    body.dark-mode label[style*="color:#333"],
    body.dark-mode label[style*="color: #333"],
    body.dark-mode p[style*="color:#333"],
    body.dark-mode p[style*="color: #333"] {
        color: #ffffff !important;
    }
    body.dark-mode button[style*="background:#fff"],
    body.dark-mode button[style*="background: #fff"],
    body.dark-mode button[style*="background-color:#fff"] {
        background-color: #252525 !important;
        color: #dddddd !important;
        border-color: #555555 !important;
    }

    /* Tx details and wallet */
    body.dark-mode .balance-amount {
        color: #e5c199 !important;
    }
    body.dark-mode .tx-section-title {
        color: #ffffff !important;
        border-bottom-color: #2a2a2a !important;
    }
    body.dark-mode .tx-item {
        border-bottom-color: #252525 !important;
    }
    body.dark-mode .tx-amount.minus {
        color: #dddddd !important;
    }
    body.dark-mode .tx-empty {
        border-color: #333333 !important;
    }

    /* Address list specifics */
    body.dark-mode .address-name {
        color: #ffffff !important;
    }
    body.dark-mode .address-phone {
        color: #aaaaaa !important;
    }
    body.dark-mode .address-detail {
        color: #cccccc !important;
    }
    body.dark-mode .badge-default {
        background-color: #1c3d27 !important;
        color: #2ecc71 !important;
    }

    /* Buttons */
    body.dark-mode .btn-save,
    body.dark-mode .btn-primary,
    body.dark-mode .btn-teal {
        background: #a6825c !important;
        color: #121212 !important;
        border-color: #a6825c !important;
    }
    body.dark-mode .btn-save:hover,
    body.dark-mode .btn-primary:hover,
    body.dark-mode .btn-teal:hover {
        background: #c9a47e !important;
        border-color: #c9a47e !important;
    }
    body.dark-mode .btn-outline,
    body.dark-mode .btn-back {
        background-color: #252525 !important;
        color: #dddddd !important;
        border-color: #555555 !important;
    }
    body.dark-mode .btn-outline:hover,
    body.dark-mode .btn-back:hover {
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

    /* Intercept inline styled colors in user views */
    body.dark-mode span[style*="color:#333"],
    body.dark-mode span[style*="color: #333"],
    body.dark-mode span[style*="color:#555"],
    body.dark-mode span[style*="color: #555"],
    body.dark-mode strong[style*="color:#333"],
    body.dark-mode strong[style*="color: #333"],
    body.dark-mode div[style*="color:#333"],
    body.dark-mode div[style*="color: #333"] {
        color: #eeeeee !important;
    }

    /* Info boxes inside user orders */
    body.dark-mode .info-box-orange {
        background-color: #3d2d18 !important;
        border-color: #f39c12 !important;
        color: #f39c12 !important;
    }
    body.dark-mode .info-box-blue {
        background-color: #182d3d !important;
        border-color: #3498db !important;
        color: #3498db !important;
    }
    body.dark-mode .info-box-red {
        background-color: #3d1a1a !important;
        border-color: #ff4d4d !important;
        color: #ff4d4d !important;
    }
    body.dark-mode .info-box-green {
        background-color: #1c3d27 !important;
        border-color: #2ecc71 !important;
        color: #2ecc71 !important;
    }

    /* Coupon page dark mode overrides */
    body.dark-mode .coupon-wrapper,
    body.dark-mode .discover-wrapper {
        background-color: transparent !important;
        color: #eeeeee !important;
    }
    body.dark-mode .coupon-header,
    body.dark-mode .discover-header {
        border-bottom-color: #2a2a2a !important;
    }
    body.dark-mode .coupon-header h2,
    body.dark-mode .discover-header h2 {
        color: #ffffff !important;
    }
    body.dark-mode .coupon-count {
        background-color: #252525 !important;
        color: #cccccc !important;
    }
    body.dark-mode .coupon-left {
        background-color: #2a2a2a !important;
        color: #e5c199 !important;
        border-right-color: rgba(255, 255, 255, 0.1) !important;
    }
    body.dark-mode .coupon-left .c-pct,
    body.dark-mode .coupon-left .tag {
        color: #e5c199 !important;
    }
    body.dark-mode .c-title {
        color: #ffffff !important;
    }
    body.dark-mode .c-desc,
    body.dark-mode .c-desc-text {
        color: #bbbbbb !important;
    }
    body.dark-mode .c-meta span {
        background-color: #252525 !important;
        color: #cccccc !important;
    }
    body.dark-mode .c-expire {
        background-color: #3d2d18 !important;
        color: #f39c12 !important;
    }
    body.dark-mode .c-footer {
        border-top-color: #2a2a2a !important;
    }
    body.dark-mode .c-code-tag,
    body.dark-mode .c-code {
        background-color: #252525 !important;
        color: #e5c199 !important;
        border-color: #a6825c !important;
    }
    body.dark-mode .c-code-tag:hover {
        background-color: #333333 !important;
    }
    body.dark-mode .c-copy-btn,
    body.dark-mode .c-btn-save {
        background-color: #a6825c !important;
        color: #121212 !important;
    }
    body.dark-mode .c-copy-btn:hover,
    body.dark-mode .c-btn-save:hover {
        background-color: #c9a47e !important;
    }
    body.dark-mode .c-copy-btn.copied {
        background-color: #2ecc71 !important;
        color: #ffffff !important;
    }
    body.dark-mode .c-btn-saved {
        background-color: #333333 !important;
        color: #666666 !important;
    }
    body.dark-mode .btn-back-wallet {
        color: #e5c199 !important;
        border-color: #e5c199 !important;
    }
    body.dark-mode .btn-back-wallet:hover {
        background-color: #e5c199 !important;
        color: #121212 !important;
    }
    body.dark-mode .coupon-empty,
    body.dark-mode .empty-state {
        background-color: #1e1e1e !important;
        border-color: #333333 !important;
        color: #aaaaaa !important;
    }

    /* Dark mode logo fix – handled by header.php JS */
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

<?php
// ── Include header dùng chung ──────────────────────────────────────────────
// header.php dùng $_BASE (URL tuyệt đối) nên include từ bất kỳ đâu đều đúng
require realpath(__DIR__ . '/../../includes/header.php');
// ── End header include ─────────────────────────────────────────────────────
?>
</main><!-- đóng <main class="main-content"> mà header.php mở ra -->
<style>.main-content { display: none !important; }</style>

<!-- ══════════════════════════════════════════════
     DASHBOARD BODY
══════════════════════════════════════════════ -->
<div class="dashboard-wrap">

    <!-- SIDEBAR -->
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
            <li><a href="dashboard.php?view=hoso"       class="<?= $view=='hoso'       ? 'active' : '' ?>"><i class="fa-regular fa-user"></i>Hồ sơ</a></li>
            <li><a href="dashboard.php?view=hangthanhvien" class="<?= $view=='hangthanhvien' ? 'active' : '' ?>"><i class="fa-solid fa-crown"></i>Hạng thành viên</a></li>
            <li><a href="dashboard.php?view=diachi"     class="<?= $view=='diachi'     ? 'active' : '' ?>"><i class="fa-solid fa-location-dot"></i>Địa chỉ</a></li>
            <li><a href="dashboard.php?view=doimatkhau" class="<?= $view=='doimatkhau' ? 'active' : '' ?>"><i class="fa-solid fa-lock"></i>Đổi mật khẩu</a></li>
            <li><a href="dashboard.php?view=caidat"     class="<?= $view=='caidat'     ? 'active' : '' ?>"><i class="fa-solid fa-gear"></i>Cài đặt thông báo</a></li>
        </ul>

        <div class="menu-section-title">Mua sắm</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php?view=donmua"   class="<?= $view=='donmua'   ? 'active' : '' ?>"><i class="fa-solid fa-file-invoice-dollar"></i>Đơn mua</a></li>
            <li><a href="dashboard.php?view=thongbao" class="<?= $view=='thongbao' ? 'active' : '' ?>"><i class="fa-regular fa-bell"></i>Thông báo</a></li>
        </ul>

        <div class="menu-section-title">Tiện ích</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php?view=vihoantien" class="<?= $view=='vihoantien' ? 'active' : '' ?>"><i class="fa-solid fa-wallet"></i>Ví hoàn tiền</a></li>
            <li><a href="dashboard.php?view=khovoucher" class="<?= ($view=='khovoucher'||$view=='all_coupons') ? 'active' : '' ?>"><i class="fa-solid fa-ticket"></i>Kho voucher</a></li>
        </ul>

        <div style="margin-top: 24px;">
            <ul class="sidebar-menu">
                <li><a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i>Đăng xuất</a></li>
            </ul>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="dashboard-content">
        <?php
            switch ($view) {
                case 'hoso':           include 'profile_form.php';         break;
                case 'hangthanhvien':  include 'tier_info.php';            break;
                case 'nganhang':       include 'bank_form.php';            break;
                case 'thongbao':       include 'notifications.php';        break;
                case 'diachi':         include 'address_list.php';         break;
                case 'doimatkhau':     include 'change_password.php';      break;
                case 'caidat':         include 'notification_settings.php';break;
                case 'donmua':         include 'orders.php';               break;
                case 'chitietdonhang': include 'order_detail.php';         break;
                case 'vihoantien':     include 'wallet.php';               break;
                case 'khovoucher':     include 'coupon.php';               break;
                case 'all_coupons':    include 'all_coupons.php';          break;
                default:               include 'profile_form.php';         break;
            }
        ?>
    </main>

</div><!-- /.dashboard-wrap -->

<script>
function toggleSearch() {
    var bar = document.getElementById('searchBar');
    if (!bar) return;
    if (bar.style.display === 'none' || bar.style.display === '') {
        bar.style.display = 'block';
        var inp = bar.querySelector('input');
        if (inp) inp.focus();
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