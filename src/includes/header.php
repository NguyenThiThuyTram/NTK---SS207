<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$_is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
             (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$_protocol = $_is_https ? 'https' : 'http';
$_host = $_SERVER['HTTP_HOST'];
$_src_dir = str_replace('\\', '/', realpath(__DIR__ . '/../'));
$_doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$_src_path = str_replace($_doc_root, '', $_src_dir);
$_BASE = $_protocol . '://' . $_host . $_src_path;

$cart_count = 0;
try {
    if (isset($_SESSION['user_id']) && isset($conn)) {
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $row['total_items'] ?: 0;
    }
} catch (\Throwable $e) {
    error_log($e->getMessage());
}

$unread_noti_count = 0;
$max_notif_id = 0;
$max_chat_id = 0;
try {
    if (isset($_SESSION['user_id']) && isset($conn)) {
        $stmt_n = $conn->prepare("SELECT COUNT(*) as unread, MAX(noti_id) as max_id FROM notifications WHERE user_id = :user_id");
        $stmt_n->execute(['user_id' => $_SESSION['user_id']]);
        $row_n = $stmt_n->fetch(PDO::FETCH_ASSOC);
        
        $stmt_unread = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = :user_id AND is_read = 0");
        $stmt_unread->execute(['user_id' => $_SESSION['user_id']]);
        
        $unread_noti_count = $stmt_unread->fetchColumn() ?: 0;
        $max_notif_id = $row_n['max_id'] ?: 0;
        
        // Max chat id
        try {
            $stmt_c = $conn->prepare("SELECT MAX(id) FROM chat_messages WHERE receiver_id = :user_id");
            $stmt_c->execute(['user_id' => $_SESSION['user_id']]);
            $max_chat_id = $stmt_c->fetchColumn() ?: 0;
        } catch (\Throwable $e) {
            error_log($e->getMessage());
        }
    }
} catch (\Throwable $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NTK Fashion</title>
    <link rel="icon" type="image/png" href="<?= $_BASE ?>/assets/images/logo-ntk.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $_BASE ?>/assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* === COMPREHENSIVE DARK MODE SYNC === */
        body.dark-mode {
            --bg-color: #121212 !important;
            --beige-color: #1a1a1a !important;
            --border-color: #333333 !important;
            --text-color: #f5f5f5 !important;
            background-color: #121212 !important;
            color: #f5f5f5 !important;
        }

        body.dark-mode p, body.dark-mode span, body.dark-mode li, body.dark-mode td, body.dark-mode th, body.dark-mode label, body.dark-mode legend, body.dark-mode input, body.dark-mode select, body.dark-mode textarea { color: #e0e0e0 !important; }
        body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, body.dark-mode h4, body.dark-mode h5, body.dark-mode h6, body.dark-mode strong, body.dark-mode b { color: #ffffff !important; }
        body.dark-mode a { color: #ffffff !important; }
        body.dark-mode a:hover { color: #f1c40f !important; }
        body.dark-mode header, body.dark-mode .main-header, body.dark-mode .search-bar-container { background: #1a1a1a !important; background-color: #1a1a1a !important; border-bottom-color: #333333 !important; }
        body.dark-mode .logo img, body.dark-mode #mainLogo { filter: brightness(0) invert(1) !important; }
        body.dark-mode footer, body.dark-mode .main-footer { background: #1a1a1a !important; background-color: #1a1a1a !important; border-top-color: #333333 !important; }
        body.dark-mode .section, body.dark-mode section, body.dark-mode .policy-page { background: #121212 !important; background-color: #121212 !important; }

        body.dark-mode .product-card, body.dark-mode .cart-left, body.dark-mode .order-summary-box, body.dark-mode .process-step, body.dark-mode .payment-card, body.dark-mode .contact-box, body.dark-mode .checkout-box, body.dark-mode .billing-box, body.dark-mode .cart-item, body.dark-mode .address-card, body.dark-mode .coupon-card, body.dark-mode .notification-card, body.dark-mode .profile-card, body.dark-mode .wallet-card, body.dark-mode .review-card, body.dark-mode .review-item, body.dark-mode .detail-box, body.dark-mode .order-box { background: #1e1e1e !important; background-color: #1e1e1e !important; border-color: #333333 !important; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5) !important; color: #ffffff !important; }
        body.dark-mode input[type="text"], body.dark-mode input[type="email"], body.dark-mode input[type="tel"], body.dark-mode input[type="password"], body.dark-mode input[type="number"], body.dark-mode select, body.dark-mode textarea { background-color: #252525 !important; border-color: #333333 !important; color: #ffffff !important; }
        body.dark-mode th { background-color: #1a1a1a !important; color: #a6825c !important; border-color: #333333 !important; }
        body.dark-mode td { background-color: #121212 !important; color: #eeeeee !important; border-color: #333333 !important; }
        body.dark-mode .navbar a, body.dark-mode .header-icons a { color: #f5f5f5 !important; }
        body.dark-mode .navbar a:hover, body.dark-mode .navbar a.active { color: #f1c40f !important; }
        body.dark-mode .navbar a.active { border-bottom-color: #f1c40f !important; }

        .dm-user-toggle { background: none; border: none; cursor: pointer; font-size: 18px; color: inherit; display: inline-flex; align-items: center; padding: 0 8px; }
        .search-bar-container { display: none; background: #ffffff; border-bottom: 1px solid #eee; padding: 15px 0; position: absolute; width: 100%; left: 0; top: 70px; z-index: 999; }
        body.dark-mode .search-bar-container { background: #1a1a1a !important; border-bottom: 1px solid #333 !important; }
        
        .image-search-btn, .search-btn { background: none !important; border: none !important; cursor: pointer; outline: none; padding: 0 10px; color: #2f1c00; font-size: 18px; transition: color 0.3s, transform 0.2s; display: inline-flex; align-items: center; justify-content: center; }
        .image-search-btn:hover, .search-btn:hover { color: #8a6d51; transform: scale(1.1); }
        body.dark-mode .image-search-btn, body.dark-mode .search-btn { color: #f5f5f5 !important; }
        body.dark-mode .image-search-btn:hover, body.dark-mode .search-btn:hover { color: #f1c40f !important; }
        
        .image-search-modal { position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; }
        .image-search-modal-content { background-color: #ffffff; padding: 30px; border-radius: 15px; text-align: center; max-width: 400px; width: 90%; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); }
        body.dark-mode .image-search-modal-content { background-color: #1a1a1a; color: #ffffff; border: 1px solid #333; }
        .loader-spinner { border: 4px solid rgba(0, 0, 0, 0.1); width: 50px; height: 50px; border-radius: 50%; border-left-color: #2f1c00; animation: spin 1s linear infinite; margin: 0 auto 20px auto; }
        body.dark-mode .loader-spinner { border: 4px solid rgba(255, 255, 255, 0.1); border-left-color: #f1c40f; }
        #imageSearchStatus { font-size: 16px; font-weight: 500; margin-bottom: 15px; color: #2f1c00; }
        body.dark-mode #imageSearchStatus { color: #ffffff; }
        .image-preview-container { margin-top: 15px; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; max-height: 200px; display: flex; justify-content: center; align-items: center; background: #f9f9f9; }
        body.dark-mode .image-preview-container { border-color: #333; background: #121212; }
        #imageSearchPreview { max-width: 100%; max-height: 200px; object-fit: contain; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="<?= $_BASE ?>/index.php"><img src="<?= $_BASE ?>/assets/images/logo-ntk.png" alt="NTK Logo" id="mainLogo"></a>
            </div>

            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

            <nav class="navbar">
                <ul>
                    <li><a href="<?= $_BASE ?>/index.php" class="<?= ($current_page == 'index.php') ? 'active' : ''; ?>">Trang chủ</a></li>
                    <li><a href="<?= $_BASE ?>/product.php" class="<?= ($current_page == 'product.php') ? 'active' : ''; ?>">Cửa hàng</a></li>
                    <li><a href="<?= $_BASE ?>/wishlist.php" class="<?= ($current_page == 'wishlist.php') ? 'active' : ''; ?>">Yêu thích</a></li>
                    <li><a href="<?= $_BASE ?>/promotion.php" class="<?= ($current_page == 'promotion.php') ? 'active' : ''; ?>">Khuyến mãi</a></li>
                </ul>
            </nav>

            <div class="header-icons">
                <a href="javascript:void(0)" onclick="toggleSearch()"><i class="fa-solid fa-magnifying-glass"></i></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= $_BASE ?>/views/user/dashboard.php?view=thongbao" title="Thông báo" style="position:relative;">
                        <i class="fa-regular fa-bell"></i>
                        <span id="badge-notif" class="cart-count" style="position:absolute; top:-6px; right:-8px; background:#e63946; color:#fff; font-size:12px; padding:2px 6px; border-radius:12px; <?= $unread_noti_count > 0 ? 'display:inline-block;' : 'display:none;' ?>">
                            <?= intval($unread_noti_count) ?>
                        </span>
                    </a>
                    <a href="<?= (isset($_SESSION['role']) && $_SESSION['role'] == 1) ? $_BASE . '/admin/dashboard.php' : $_BASE . '/views/user/dashboard.php'; ?>" title="Tài khoản"><i class="fa-solid fa-user"></i></a>
                <?php else: ?>
                    <a href="<?= $_BASE ?>/views/login.php" title="Đăng nhập"><i class="fa-regular fa-user"></i></a>
                <?php endif; ?>
                <a href="<?= $_BASE ?>/cart.php" style="position:relative;">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span class="cart-count" style="position:absolute; top:-6px; right:-8px; background:#e63946; color:#fff; font-size:12px; padding:2px 6px; border-radius:12px; display:inline-block;"><?= intval($cart_count) ?></span>
                </a>
                <button class="dm-user-toggle" onclick="toggleUserDark()"><i class="fa-regular fa-moon" id="dmUserIcon"></i></button>
            </div>
        </div>

        <div id="searchBar" class="search-bar-container" style="display:none;">
            <form action="<?= $_BASE ?>/search.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Tìm kiếm..." class="search-input" required>
                <button type="button" class="image-search-btn" onclick="triggerImageSearch()" title="Tìm kiếm bằng hình ảnh">
                    <i class="fa-solid fa-camera"></i>
                </button>
                <button type="submit" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <input type="file" id="imageSearchInput" accept="image/*" style="display: none;" onchange="handleImageSearch(this)">
        </div>

        <div id="imageSearchModal" class="image-search-modal" style="display: none;">
            <div class="image-search-modal-content">
                <div class="loader-spinner"></div>
                <p id="imageSearchStatus">Đang gửi ảnh lên hệ thống...</p>
                <div class="image-preview-container" style="display: none;">
                    <img id="imageSearchPreview" src="" alt="Preview">
                </div>
            </div>
        </div>
    </header>

    <script>
        function toggleUserDark() {
            const isDark = document.body.classList.toggle('dark-mode');
            localStorage.setItem('ntk_dark', isDark ? '1' : '0');
            document.getElementById('dmUserIcon').className = isDark ? 'fa-solid fa-sun' : 'fa-regular fa-moon';
        }
        function toggleSearch() {
            const sb = document.getElementById("searchBar");
            sb.style.display = (sb.style.display === "none") ? "block" : "none";
        }
        if (localStorage.getItem('ntk_dark') === '1') {
            document.body.classList.add('dark-mode');
            document.addEventListener('DOMContentLoaded', function() {
                const icon = document.getElementById('dmUserIcon');
                if (icon) icon.className = 'fa-solid fa-sun';
            });
        }

        window.addEventListener('storage', function(e) {
            if (e.key === 'ntk_dark') {
                const isDark = e.newValue === '1';
                document.body.classList.toggle('dark-mode', isDark);
                const icon = document.getElementById('dmUserIcon');
                if (icon) icon.className = isDark ? 'fa-solid fa-sun' : 'fa-regular fa-moon';
            }
        });

        function triggerImageSearch() {
            document.getElementById('imageSearchInput').click();
        }

        function handleImageSearch(input) {
            if (!input.files || !input.files[0]) return;
            
            const file = input.files[0];
            const modal = document.getElementById('imageSearchModal');
            const statusText = document.getElementById('imageSearchStatus');
            const previewContainer = document.querySelector('.image-preview-container');
            const previewImg = document.getElementById('imageSearchPreview');
            
            statusText.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Đang đọc hình ảnh...';
            previewContainer.style.display = 'none';
            modal.style.display = 'flex';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.style.display = 'flex';
            };
            reader.readAsDataURL(file);
            
            const formData = new FormData();
            formData.append('image', file);
            statusText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> AI đang phân tích hình ảnh...';
            
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 15000);
            
            fetch('<?= $_BASE ?>/api_image_search.php', {
                method: 'POST',
                body: formData,
                signal: controller.signal
            })
            .then(response => response.json())
            .then(data => {
                clearTimeout(timeoutId);
                if (data.success) {
                    statusText.style.color = '#27ae60';
                    
                    // SỬA TẠI ĐÂY: Nếu API chạy ở chế độ fallback sản phẩm, đổi luôn từ khóa redirect thành 'áo thun'
                    const query = data.is_fallback ? 'áo thun' : (data.keyword ? data.keyword : 'áo thun');
                    const desc = data.is_fallback ? 'Hệ thống tự động gợi ý các mẫu Áo thun mới nhất do không tìm thấy sản phẩm trùng khớp.' : (data.description || '');
                    
                    statusText.innerHTML = '<i class="fa-solid fa-circle-check"></i> Phân tích xong! Từ khóa: "<strong>' + query + '</strong>".<br><small style="color: #666;">Đang tìm sản phẩm...</small>';
                    
                    setTimeout(() => {
                        const searchUrl = new URL('<?= $_BASE ?>/search.php', window.location.origin);
                        searchUrl.searchParams.set('q', query);
                        searchUrl.searchParams.set('image_search', '1');
                        searchUrl.searchParams.set('image_desc', desc);
                        if(data.is_fallback) searchUrl.searchParams.set('fallback', '1');
                        window.location.href = searchUrl.toString();
                    }, 1200);
                } else {
                    showImageSearchError(data.message || 'Có lỗi xảy ra khi phân tích ảnh.');
                }
            })
            .catch(error => {
                clearTimeout(timeoutId);
                if (error.name === 'AbortError') {
                    statusText.style.color = '#f39c12';
                    statusText.innerHTML = '<i class="fa-solid fa-hourglass-end"></i> Phân tích quá lâu, đang tìm sản phẩm tương tự...';
                    
                    setTimeout(() => {
                        const searchUrl = new URL('<?= $_BASE ?>/search.php', window.location.origin);
                        searchUrl.searchParams.set('q', 'áo thun'); // SỬA TẠI ĐÂY: Quá hạn ép thẳng về 'áo thun' thay vì 'thời trang'
                        searchUrl.searchParams.set('image_search', '1');
                        searchUrl.searchParams.set('fallback', '1');
                        searchUrl.searchParams.set('image_desc', 'Hệ thống tự động gợi ý danh mục áo thun do phản hồi từ AI quá hạn.');
                        window.location.href = searchUrl.toString();
                    }, 1500);
                } else {
                    showImageSearchError('Không thể kết nối tới máy chủ.');
                }
            });
        }

        function showImageSearchError(message) {
            const statusText = document.getElementById('imageSearchStatus');
            statusText.style.color = '#c0392b';
            statusText.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Lỗi: ' + message + '<br><button type="button" class="btn-cat" style="margin-top: 15px; background: #c0392b; color: white; border: none; padding: 5px 15px; cursor: pointer;" onclick="closeImageSearchModal()">Đóng</button>';
        }

        function closeImageSearchModal() {
            document.getElementById('imageSearchModal').style.display = 'none';
            document.getElementById('imageSearchInput').value = '';
        }
    </script>
    
    <!-- REAL-TIME SSE LOGIC -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <style>
        .ntk-toast-container { position: fixed; top: 80px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
        .ntk-toast { background: #fff; border-left: 4px solid #f39c12; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 15px 20px; border-radius: 4px; min-width: 300px; display: flex; align-items: flex-start; gap: 15px; animation: slideInRight 0.3s ease-out forwards; transition: opacity 0.3s; }
        body.dark-mode .ntk-toast { background: #1e1e1e; color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.5); }
        .ntk-toast i { font-size: 20px; color: #f39c12; margin-top: 2px; }
        .ntk-toast-content { flex: 1; }
        .ntk-toast-title { font-weight: bold; font-size: 14px; margin-bottom: 5px; color: #333; }
        body.dark-mode .ntk-toast-title { color: #f5f5f5; }
        .ntk-toast-msg { font-size: 13px; color: #666; }
        body.dark-mode .ntk-toast-msg { color: #ccc; }
        .ntk-toast-close { cursor: pointer; color: #aaa; font-size: 16px; border: none; background: none; }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
    <div class="ntk-toast-container" id="ntk-toast-container"></div>
    <script>
        let lastNotifId = <?= $max_notif_id ?>;
        let lastChatId = <?= $max_chat_id ?>;
        const sseUrl = new URL('<?= $_BASE ?>/api/sse_stream.php', window.location.origin);
        sseUrl.searchParams.set('last_notif_id', lastNotifId);
        sseUrl.searchParams.set('last_chat_id', lastChatId);
        // Note: polling_order_id can be added dynamically on order pages

        const eventSource = new EventSource(sseUrl.toString());

        eventSource.addEventListener('message', function(e) {
            const data = JSON.parse(e.data);
            
            // 1. Nhận thông báo mới
            if (data.notifications && data.notifications.length > 0) {
                let badge = document.getElementById('badge-notif');
                if (badge) {
                    let currentCount = parseInt(badge.innerText || '0');
                    currentCount += data.notifications.length;
                    badge.innerText = currentCount;
                    badge.style.display = 'inline-block';
                }

                const notiList = document.querySelector('.noti-list');
                if (notiList) {
                    const emptyState = notiList.querySelector('.noti-empty');
                    if (emptyState) emptyState.remove();
                }

                data.notifications.forEach(notif => {
                    showToast(notif.title, notif.message);
                    
                    if (notiList) {
                        const item = document.createElement('div');
                        item.className = 'noti-item unread';
                        item.setAttribute('data-id', notif.noti_id);
                        
                        let iconClass = 'fa-solid fa-bell';
                        let colorClass = 'icon-blue';
                        const map = {
                            'order_placed': { icon: 'fa-solid fa-bag-shopping', color: 'icon-blue' },
                            'order_pending_payment': { icon: 'fa-solid fa-clock', color: 'icon-orange' },
                            'order_confirmed': { icon: 'fa-solid fa-circle-check', color: 'icon-green' },
                            'order_shipping': { icon: 'fa-solid fa-truck-fast', color: 'icon-orange' },
                            'order_completed': { icon: 'fa-solid fa-star', color: 'icon-green' },
                            'order_cancelled': { icon: 'fa-regular fa-circle-xmark', color: 'icon-red' },
                            'return_request': { icon: 'fa-solid fa-rotate-left', color: 'icon-orange' },
                            'return_approved': { icon: 'fa-solid fa-thumbs-up', color: 'icon-green' },
                            'return_rejected': { icon: 'fa-solid fa-ban', color: 'icon-red' },
                            'refund_done': { icon: 'fa-solid fa-wallet', color: 'icon-green' },
                            'delivery_failed': { icon: 'fa-solid fa-triangle-exclamation', color: 'icon-red' },
                            'cancel_approved': { icon: 'fa-solid fa-circle-xmark', color: 'icon-red' },
                            'cancel_rejected': { icon: 'fa-solid fa-ban', color: 'icon-red' },
                            'order_update': { icon: 'fa-solid fa-bell', color: 'icon-blue' }
                        };
                        
                        if (map[notif.type]) {
                            iconClass = map[notif.type].icon;
                            colorClass = map[notif.type].color;
                        }
                        
                        let orderLinkHtml = '';
                        if (notif.related_order_id) {
                            orderLinkHtml = `
                                <a href="dashboard.php?view=chitietdonhang&id=${notif.related_order_id}" class="noti-order-link">
                                    Xem đơn hàng #${notif.related_order_id} →
                                </a>
                            `;
                        }

                        const createdDate = new Date();
                        const timeStr = createdDate.getHours().toString().padStart(2, '0') + ':' + createdDate.getMinutes().toString().padStart(2, '0') + ' ' + createdDate.getDate().toString().padStart(2, '0') + '/' + (createdDate.getMonth() + 1).toString().padStart(2, '0') + '/' + createdDate.getFullYear();

                        item.innerHTML = `
                            <div class="noti-icon ${colorClass}">
                                <i class="${iconClass}"></i>
                            </div>
                            <div class="noti-body">
                                <div class="noti-title" style="font-weight: 600;">${notif.title}</div>
                                <div class="noti-msg">${notif.message}</div>
                                ${orderLinkHtml}
                                <div class="noti-time">
                                    <i class="fa-regular fa-clock" style="margin-right:4px;"></i>
                                    Vừa xong
                                    <span style="color:#ddd; margin:0 6px;">·</span>
                                    ${timeStr}
                                </div>
                            </div>
                            <div class="unread-dot" title="Chưa đọc"></div>
                        `;
                        
                        notiList.insertBefore(item, notiList.firstChild);
                        
                        const titleCount = document.querySelector('.noti-page-header span');
                        if (titleCount) {
                            const currentTotal = parseInt(titleCount.innerText) || 0;
                            titleCount.innerText = (currentTotal + 1) + ' thông báo';
                        }
                    }

                    lastNotifId = Math.max(lastNotifId, notif.noti_id);
                });
                
                // Cập nhật lại URL kết nối SSE với ID mới nhất để tránh gửi lại
                sseUrl.searchParams.set('last_notif_id', lastNotifId);
            }

            // 2. Nhận tin nhắn chat mới
            if (data.chat_messages && data.chat_messages.length > 0) {
                const last = data.chat_messages[data.chat_messages.length - 1];
                lastChatId = Math.max(lastChatId, parseInt(last.id));
                sseUrl.searchParams.set('last_chat_id', lastChatId);

                const hasNewFromStaff = data.chat_messages.some(msg => msg.sender_id !== <?= json_encode(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '') ?>);
                
                if (hasNewFromStaff) {
                    const chatbox = document.getElementById('ntk-chatbox');
                    const chatMode = document.getElementById('chat-mode');
                    const isChatboxOpenAndHuman = chatbox && chatbox.style.display === 'flex' && chatMode && chatMode.value === 'human';

                    if (isChatboxOpenAndHuman) {
                        if (typeof window.handleNewChatMessage === 'function') {
                            window.handleNewChatMessage(data.chat_messages);
                        }
                    } else {
                        // Lấy nội dung tin nhắn cuối cùng để hiển thị trên toast
                        const lastMsgContent = last.message;
                        showChatToast('Tin nhắn mới từ nhân viên', lastMsgContent);
                    }
                }
            }

            // 3. Trạng thái đơn hàng (sẽ gọi hook nếu đang ở trang chi tiết)
            if (data.order_update) {
                if (typeof window.handleOrderUpdate === 'function') {
                    window.handleOrderUpdate(data.order_update);
                }
            }

            // 4. Đơn hàng MỚI được đặt (chỉ admin nhận)
            if (data.new_order && data.new_order.length > 0) {
                data.new_order.forEach(function(o) {
                    var price = parseInt(o.final_price || 0).toLocaleString('vi-VN');
                    showToast(
                        '🛒 Đơn hàng mới #' + o.order_id,
                        (o.fullname || 'Khách') + ' vừa đặt hàng • ' + price + '₫'
                    );
                    // Cập nhật badge "Đơn hàng mới" trong admin sidebar nếu có
                    var badge = document.querySelector('[data-admin-badge="new_orders"]');
                    if (badge) {
                        badge.textContent = (parseInt(badge.textContent) || 0) + 1;
                        badge.style.display = 'inline-block';
                    }
                });
                if (typeof window.handleNewOrder === 'function') {
                    window.handleNewOrder(data.new_order);
                }
            }
        });

        function showToast(title, message) {
            const container = document.getElementById('ntk-toast-container');
            const toast = document.createElement('div');
            toast.className = 'ntk-toast';
            toast.innerHTML = `
                <i class="fa-solid fa-bell"></i>
                <div class="ntk-toast-content">
                    <div class="ntk-toast-title">${title}</div>
                    <div class="ntk-toast-msg">${message}</div>
                </div>
                <button class="ntk-toast-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        function showChatToast(title, message) {
            const container = document.getElementById('ntk-toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = 'ntk-toast';
            toast.style.cursor = 'pointer';
            toast.innerHTML = `
                <i class="fa-solid fa-comments" style="color: #f39c12; margin-top: 2px;"></i>
                <div class="ntk-toast-content">
                    <div class="ntk-toast-title">${title}</div>
                    <div class="ntk-toast-msg">${message}</div>
                </div>
                <button class="ntk-toast-close" onclick="event.stopPropagation(); this.parentElement.remove()">&times;</button>
            `;
            toast.onclick = function() {
                if (typeof window.openUserChat === 'function') {
                    window.openUserChat();
                }
                toast.remove();
            };
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 6000);
        }
    </script>
    <?php endif; ?>
    <main class="main-content">