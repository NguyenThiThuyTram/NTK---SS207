<?php
require_once 'auth_check.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$admin_user_id = $_SESSION['user_id'];

// Lấy danh sách user đã nhắn tin
$stmt = $conn->prepare("
    SELECT 
        u.user_id, 
        u.fullname, 
        MAX(c.created_at) as last_msg_time,
        (SELECT message FROM chat_messages 
         WHERE (sender_id = u.user_id AND receiver_id = '0') 
            OR (sender_id IN (SELECT user_id FROM users WHERE role = 1) AND receiver_id = u.user_id) 
         ORDER BY id DESC LIMIT 1) as last_msg_content,
        (SELECT COUNT(*) FROM chat_messages 
         WHERE sender_id = u.user_id AND receiver_id = '0' AND is_read = 0) as unread_count
    FROM chat_messages c
    JOIN users u ON (c.sender_id = u.user_id OR c.receiver_id = u.user_id)
    WHERE u.role = 0
    GROUP BY u.user_id
    ORDER BY last_msg_time DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_user = isset($_GET['uid']) ? trim($_GET['uid']) : '';
$messages = [];
$current_customer_name = "";

if (!empty($current_user)) {
    // Lấy thông tin khách hàng đang chọn
    $stmt_cust = $conn->prepare("SELECT fullname FROM users WHERE user_id = :uid AND role = 0");
    $stmt_cust->execute(['uid' => $current_user]);
    $cust = $stmt_cust->fetch();
    if ($cust) {
        $current_customer_name = $cust['fullname'];
    }

    // Đánh dấu đã đọc tất cả tin nhắn từ khách hàng này
    $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = :uid AND receiver_id = '0' AND is_read = 0")->execute(['uid' => $current_user]);
    
    // Lấy tin nhắn giữa khách hàng này và admin
    $stmt_msg = $conn->prepare("
        SELECT * FROM chat_messages 
        WHERE (sender_id = :uid AND receiver_id = '0') 
           OR (sender_id IN (SELECT user_id FROM users WHERE role = 1) AND receiver_id = :uid) 
        ORDER BY id ASC
    ");
    $stmt_msg->execute(['uid' => $current_user]);
    $messages = $stmt_msg->fetchAll(PDO::FETCH_ASSOC);
}

// Tìm max_id tuyệt đối để làm mốc SSE
$stmt_max = $conn->query("SELECT MAX(id) FROM chat_messages");
$max_chat_id = (int)$stmt_max->fetchColumn() ?: 0;

$admin_current_page = 'chat.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    /* ============================================================
       PREMIUM DUAL LAYOUT CHATBOARD
    ============================================================ */
    .chat-dashboard {
        display: flex;
        height: calc(100vh - 160px);
        background: #ffffff;
        border: 1px solid #e5e5e5;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        margin-top: 15px;
    }
    body.dark-mode .chat-dashboard {
        background: #181818 !important;
        border-color: #2a2a2a !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    /* ── Sidebar (Left Column) ── */
    .chat-sidebar {
        width: 320px;
        border-right: 1px solid #e5e5e5;
        display: flex;
        flex-direction: column;
        background: #fafaf9;
        flex-shrink: 0;
    }
    body.dark-mode .chat-sidebar {
        background: #1e1e1e !important;
        border-right-color: #2a2a2a !important;
    }

    .chat-search-container {
        padding: 16px;
        border-bottom: 1px solid #e5e5e5;
        background: #ffffff;
        position: relative;
    }
    body.dark-mode .chat-search-container {
        background: #1e1e1e !important;
        border-bottom-color: #2a2a2a !important;
    }
    .chat-search-container i {
        position: absolute;
        left: 28px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
        font-size: 13.5px;
    }
    .chat-search-input {
        width: 100%;
        padding: 10px 14px 10px 38px;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        font-size: 13.5px;
        outline: none;
        background: #f9f9fa;
        color: #111;
        transition: all 0.2s;
    }
    body.dark-mode .chat-search-input {
        background: #252525 !important;
        border-color: #333333 !important;
        color: #fff !important;
    }
    .chat-search-input:focus {
        border-color: #2f1c00;
        background: #fff;
    }
    body.dark-mode .chat-search-input:focus {
        border-color: #a6825c !important;
    }

    .chat-user-list {
        flex: 1;
        overflow-y: auto;
    }

    .user-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        border-bottom: 1px solid #f5f1eb;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        color: inherit;
        position: relative;
    }
    body.dark-mode .user-item {
        border-bottom-color: #252525 !important;
    }
    .user-item:hover {
        background: #f5f1eb;
    }
    body.dark-mode .user-item:hover {
        background: #252525 !important;
    }
    .user-item.active {
        background: #f0e9df;
        border-left: 4px solid #2f1c00;
    }
    body.dark-mode .user-item.active {
        background: #2c2217 !important;
        border-left-color: #a6825c !important;
    }

    .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #2f1c00;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13.5px;
        flex-shrink: 0;
    }
    body.dark-mode .user-avatar {
        background: #a6825c !important;
        color: #121212 !important;
    }

    .user-info {
        flex: 1;
        min-width: 0;
    }
    .user-name-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 4px;
    }
    .user-fullname {
        font-weight: 600;
        font-size: 13.5px;
        color: #111;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    body.dark-mode .user-fullname {
        color: #fff !important;
    }
    .msg-time {
        font-size: 11px;
        color: #999;
    }
    .msg-preview {
        font-size: 12px;
        color: #777;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    body.dark-mode .msg-preview {
        color: #aaa !important;
    }

    .unread-badge {
        background: #c0392b;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 6px;
        display: inline-block;
    }

    /* ── Chat Window (Right Column) ── */
    .chat-window {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #ffffff;
    }
    body.dark-mode .chat-window {
        background: #181818 !important;
    }

    .chat-header {
        padding: 16px 24px;
        border-bottom: 1px solid #e5e5e5;
        display: flex;
        align-items: center;
        gap: 12px;
        background: #ffffff;
    }
    body.dark-mode .chat-header {
        background: #1e1e1e !important;
        border-bottom-color: #2a2a2a !important;
    }
    .active-user-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #e5e5e5;
        color: #333;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
    }
    body.dark-mode .active-user-avatar {
        background: #252525 !important;
        color: #fff !important;
    }
    .active-user-name {
        font-weight: 700;
        font-size: 15px;
        color: #111;
    }
    body.dark-mode .active-user-name {
        color: #fff !important;
    }
    .active-status {
        font-size: 11px;
        color: #27ae60;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 2px;
    }
    .active-status::before {
        content: '';
        width: 6px;
        height: 6px;
        background: #27ae60;
        border-radius: 50%;
    }

    .chat-messages {
        flex: 1;
        padding: 24px;
        overflow-y: auto;
        background: #f9f9fa;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    body.dark-mode .chat-messages {
        background: #121212 !important;
    }

    /* Message Bubbles */
    .msg-group {
        display: flex;
        flex-direction: column;
        max-width: 65%;
    }
    .msg-group.admin {
        align-self: flex-end;
        align-items: flex-end;
    }
    .msg-group.user {
        align-self: flex-start;
        align-items: flex-start;
    }

    .msg-bubble {
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 13px;
        line-height: 1.45;
        word-wrap: break-word;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .msg-group.admin .msg-bubble {
        background: linear-gradient(135deg, #2f1c00 0%, #4a2c00 100%);
        color: #ffffff;
        border-bottom-right-radius: 2px;
    }
    body.dark-mode .msg-group.admin .msg-bubble {
        background: #a6825c !important;
        color: #121212 !important;
    }
    .msg-group.user .msg-bubble {
        background: #ffffff;
        color: #111111;
        border: 1px solid #e5e5e5;
        border-bottom-left-radius: 2px;
    }
    body.dark-mode .msg-group.user .msg-bubble {
        background: #222222 !important;
        color: #eeeeee !important;
        border-color: #2a2a2a !important;
    }

    .msg-time-stamp {
        font-size: 10px;
        color: #999;
        margin-top: 4px;
    }

    /* Chat Input */
    .chat-input-area {
        padding: 16px 24px;
        border-top: 1px solid #e5e5e5;
        background: #ffffff;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    body.dark-mode .chat-input-area {
        background: #1e1e1e !important;
        border-top-color: #2a2a2a !important;
    }
    .chat-input-field {
        flex: 1;
        padding: 10px 16px;
        border: 1px solid #e5e5e5;
        border-radius: 20px;
        font-size: 13px;
        outline: none;
        background: #f9f9fa;
        color: #111;
        transition: all 0.2s;
    }
    body.dark-mode .chat-input-field {
        background: #252525 !important;
        border-color: #333333 !important;
        color: #fff !important;
    }
    .chat-input-field:focus {
        border-color: #2f1c00;
        background: #fff;
    }
    body.dark-mode .chat-input-field:focus {
        border-color: #a6825c !important;
    }

    .btn-send {
        background: #2f1c00;
        color: #ffffff;
        border: none;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: all 0.2s;
    }
    body.dark-mode .btn-send {
        background: #a6825c !important;
        color: #121212 !important;
    }
    .btn-send:hover {
        transform: scale(1.05);
    }

    .no-chat-selected {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #aaa;
        background: #f9f9fa;
        padding: 40px;
        text-align: center;
    }
    body.dark-mode .no-chat-selected {
        background: #121212 !important;
        color: #666 !important;
    }
    .no-chat-icon {
        font-size: 48px;
        margin-bottom: 16px;
        color: #ddd;
    }
    body.dark-mode .no-chat-icon {
        color: #2a2a2a !important;
    }
</style>

<div class="page-title">Hệ thống Live Chat</div>
<p class="page-subtitle">Hỗ trợ và tư vấn trực tiếp cho khách hàng NTK Fashion</p>

<div class="chat-dashboard">
    <!-- Cột danh sách khách hàng -->
    <div class="chat-sidebar">
        <div class="chat-search-container">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="chat-search-input" id="search-users" placeholder="Tìm kiếm khách hàng..." onkeyup="filterUserList()">
        </div>
        <div class="chat-user-list" id="user-list-items">
            <?php if (empty($users)): ?>
                <div style="text-align:center; padding:30px; color:#aaa; font-size:13px;">Chưa có cuộc trò chuyện nào</div>
            <?php else: ?>
                <?php foreach ($users as $u): 
                    $is_active = ($current_user === $u['user_id']);
                    $initials = strtoupper(substr($u['fullname'], 0, 1));
                    
                    // Xử lý hiển thị tin nhắn cuối
                    $last_msg = htmlspecialchars($u['last_msg_content'] ?? '');
                    if (strlen($last_msg) > 30) {
                        $last_msg = mb_substr($last_msg, 0, 30, 'UTF-8') . '...';
                    }
                    
                    // Format thời gian tin nhắn cuối
                    $time_str = '';
                    if ($u['last_msg_time']) {
                        $time_str = date('H:i', strtotime($u['last_msg_time']));
                    }
                ?>
                    <a href="?uid=<?= $u['user_id'] ?>" class="user-item <?= $is_active ? 'active' : '' ?>" data-id="<?= $u['user_id'] ?>" data-name="<?= htmlspecialchars(strtolower($u['fullname'])) ?>">
                        <div class="user-avatar"><?= $initials ?></div>
                        <div class="user-info">
                            <div class="user-name-row">
                                <span class="user-fullname"><?= htmlspecialchars($u['fullname']) ?></span>
                                <span class="msg-time" id="time-<?= $u['user_id'] ?>"><?= $time_str ?></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span class="msg-preview" id="preview-<?= $u['user_id'] ?>"><?= $last_msg ?></span>
                                <span class="unread-badge" id="unread-<?= $u['user_id'] ?>" style="<?= ($u['unread_count'] > 0) ? '' : 'display:none;' ?>"><?= $u['unread_count'] ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cột nội dung tin nhắn -->
    <div class="chat-window">
        <?php if (!empty($current_user)): ?>
            <div class="chat-header">
                <div class="active-user-avatar"><?= strtoupper(substr($current_customer_name, 0, 1)) ?></div>
                <div>
                    <div class="active-user-name"><?= htmlspecialchars($current_customer_name) ?></div>
                    <div class="active-status">Đang trực tuyến</div>
                </div>
            </div>
            
            <div class="chat-messages" id="chat-messages-container">
                <?php if (empty($messages)): ?>
                    <div style="text-align:center; padding:40px; color:#aaa; font-size:13px;" id="no-msg-hint">Chưa có tin nhắn nào. Gửi tin nhắn đầu tiên phía dưới!</div>
                <?php else: ?>
                    <?php foreach ($messages as $m): 
                        // Quyết định tin nhắn của admin hay user
                        // sender_id trùng với $_SESSION['user_id'] HOẶC sender_id là admin
                        $is_admin = ($m['sender_id'] == $_SESSION['user_id'] || $m['receiver_id'] == $current_user);
                        
                        $group_class = $is_admin ? 'admin' : 'user';
                        $time_label = date('H:i d/m/Y', strtotime($m['created_at']));
                    ?>
                        <div class="msg-group <?= $group_class ?>">
                            <div class="msg-bubble">
                                <?= htmlspecialchars($m['message']) ?>
                            </div>
                            <span class="msg-time-stamp"><?= $time_label ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="chat-input-area">
                <input type="text" class="chat-input-field" id="msg-input" placeholder="Nhập câu trả lời..." onkeypress="if(event.key === 'Enter') sendAdminMsg()">
                <button class="btn-send" onclick="sendAdminMsg()" title="Gửi"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        <?php else: ?>
            <div class="no-chat-selected">
                <div class="no-chat-icon"><i class="fa-solid fa-comments"></i></div>
                <h3 style="font-weight:700; color:#444; font-size:16px; margin-bottom:8px;">Bắt đầu tư vấn</h3>
                <p style="font-size:13px; color:#888; max-width:300px; line-height:1.45;">Vui lòng chọn một khách hàng từ danh sách bên trái để bắt đầu trò chuyện hỗ trợ.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Cuộn xuống cuối khung chat
    const chatContainer = document.getElementById('chat-messages-container');
    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // Lọc danh sách khách hàng tại sidebar
    function filterUserList() {
        const query = document.getElementById('search-users').value.toLowerCase().trim();
        const items = document.querySelectorAll('.user-item');
        items.forEach(item => {
            const name = item.getAttribute('data-name');
            if (name.includes(query)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Gửi tin nhắn từ Admin
    async function sendAdminMsg() {
        const input = document.getElementById('msg-input');
        if (!input) return;
        const msg = input.value.trim();
        if (!msg) return;
        
        // Append tin nhắn tạm thời lên UI
        const hintNode = document.getElementById('no-msg-hint');
        if (hintNode) hintNode.remove();
        
        const now = new Date();
        const timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0') + ' ' + now.getDate().toString().padStart(2, '0') + '/' + (now.getMonth()+1).toString().padStart(2, '0') + '/' + now.getFullYear();
        
        chatContainer.innerHTML += `
            <div class="msg-group admin">
                <div class="msg-bubble">${escapeHTML(msg)}</div>
                <span class="msg-time-stamp">${timeStr}</span>
            </div>
        `;
        input.value = '';
        chatContainer.scrollTop = chatContainer.scrollHeight;
        
        // Gửi AJAX
        try {
            const fd = new FormData();
            fd.append('message', msg);
            fd.append('receiver_id', <?= json_encode($current_user) ?>);
            
            const response = await fetch('../api/chat_send.php', { method: 'POST', body: fd });
            const data = await response.json();
            if (!data.success) {
                console.error("Gửi tin thất bại: ", data.message);
            } else {
                // Cập nhật preview của sidebar
                const previewEl = document.getElementById('preview-' + <?= json_encode($current_user) ?>);
                if (previewEl) {
                    previewEl.innerText = msg.length > 30 ? msg.substring(0,30) + '...' : msg;
                }
                const timeEl = document.getElementById('time-' + <?= json_encode($current_user) ?>);
                if (timeEl) {
                    timeEl.innerText = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
                }
                
                // Đưa user này lên top
                moveUserToTop(<?= json_encode($current_user) ?>);
            }
        } catch (e) {
            console.error("Lỗi kết nối gửi tin: ", e);
        }
    }

    // Di chuyển user lên đầu danh sách
    function moveUserToTop(uid) {
        const list = document.getElementById('user-list-items');
        const item = document.querySelector(`.user-item[data-id="${uid}"]`);
        if (list && item) {
            list.insertBefore(item, list.firstChild);
        }
    }

    // Tránh XSS
    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }

    // ── XỬ LÝ CHAT SSE REAL-TIME ────────────────────────────
    let lastChatId = <?= $max_chat_id ?>;
    
    const sseUrl = new URL('../api/sse_stream.php', window.location.href);
    sseUrl.searchParams.set('last_chat_id', lastChatId);
    
    const sse = new EventSource(sseUrl);
    sse.addEventListener('message', function(e) {
        const data = JSON.parse(e.data);
        if (data.chat_messages) {
            let receivedActiveUserMsg = false;
            
            data.chat_messages.forEach(m => {
                const senderId = m.sender_id;
                const activeId = <?= json_encode($current_user) ?>;
                
                // Nếu khách hàng gửi tin nhắn chưa có trong sidebar thì tự động thêm mới vào DOM tức thì
                if (m.receiver_id == '0') {
                    let itemEl = document.querySelector(`.user-item[data-id="${senderId}"]`);
                    if (!itemEl) {
                        const initials = m.sender_name ? m.sender_name.charAt(0).toUpperCase() : 'U';
                        const name = m.sender_name || 'Khách hàng';
                        const listItems = document.getElementById('user-list-items');
                        if (listItems) {
                            const newHtml = `
                                <a href="?uid=${senderId}" class="user-item ${senderId === activeId ? 'active' : ''}" data-id="${senderId}" data-name="${name.toLowerCase()}">
                                    <div class="user-avatar">${initials}</div>
                                    <div class="user-info">
                                        <div class="user-name-row">
                                            <span class="user-fullname">${name}</span>
                                            <span class="msg-time" id="time-${senderId}"></span>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <span class="msg-preview" id="preview-${senderId}"></span>
                                            <span class="unread-badge" id="unread-${senderId}" style="display:none;">0</span>
                                        </div>
                                    </div>
                                </a>
                            `;
                            // Prepend vào danh sách
                            listItems.insertAdjacentHTML('afterbegin', newHtml);
                        }
                    }
                }

                // Nếu tin nhắn là từ khách hàng ta đang chat
                if (senderId === activeId && m.receiver_id == '0') {
                    receivedActiveUserMsg = true;
                    
                    const hintNode = document.getElementById('no-msg-hint');
                    if (hintNode) hintNode.remove();
                    
                    const dateObj = new Date(m.created_at);
                    const timeStr = dateObj.getHours().toString().padStart(2, '0') + ':' + dateObj.getMinutes().toString().padStart(2, '0') + ' ' + dateObj.getDate().toString().padStart(2, '0') + '/' + (dateObj.getMonth()+1).toString().padStart(2, '0') + '/' + dateObj.getFullYear();
                    
                    chatContainer.innerHTML += `
                        <div class="msg-group user">
                            <div class="msg-bubble">${escapeHTML(m.message)}</div>
                            <span class="msg-time-stamp">${timeStr}</span>
                        </div>
                    `;
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                    
                    // Cập nhật preview ở sidebar
                    const previewEl = document.getElementById('preview-' + activeId);
                    if (previewEl) {
                        previewEl.innerText = m.message.length > 30 ? m.message.substring(0,30) + '...' : m.message;
                    }
                    const timeEl = document.getElementById('time-' + activeId);
                    if (timeEl) {
                        timeEl.innerText = dateObj.getHours().toString().padStart(2, '0') + ':' + dateObj.getMinutes().toString().padStart(2, '0');
                    }
                    
                    // Đưa user lên top
                    moveUserToTop(activeId);
                } else if (m.receiver_id == '0') {
                    // Tin nhắn từ khách hàng khác
                    // Cập nhật badge chưa đọc ở sidebar
                    const badgeEl = document.getElementById('unread-' + senderId);
                    if (badgeEl) {
                        let count = parseInt(badgeEl.innerText) || 0;
                        count += 1;
                        badgeEl.innerText = count;
                        badgeEl.style.display = 'inline-block';
                    }
                    
                    // Cập nhật preview ở sidebar
                    const previewEl = document.getElementById('preview-' + senderId);
                    if (previewEl) {
                        previewEl.innerText = m.message.length > 30 ? m.message.substring(0,30) + '...' : m.message;
                    }
                    const timeEl = document.getElementById('time-' + senderId);
                    if (timeEl) {
                        const dateObj = new Date(m.created_at);
                        timeEl.innerText = dateObj.getHours().toString().padStart(2, '0') + ':' + dateObj.getMinutes().toString().padStart(2, '0');
                    }
                    
                    // Đưa user lên top
                    moveUserToTop(senderId);
                    
                    // Hiện Toast Notification đẹp đẽ
                    if (typeof showNtkToast === 'function') {
                        showNtkToast(
                            "Tin nhắn mới", 
                            `Bạn nhận được tin nhắn mới từ khách hàng.`,
                            "fa-comments"
                        );
                    }
                }
            });
            
            // Nếu có tin nhắn của khách hàng đang chat, ta gọi API để đánh dấu đã đọc luôn
            if (receivedActiveUserMsg) {
                const fd = new FormData();
                fd.append('user_id', <?= json_encode($current_user) ?>);
                fetch('../api/chat_mark_read.php', { method: 'POST', body: fd });
            }
            
            // Cập nhật mốc ID cuối
            const last = data.chat_messages[data.chat_messages.length - 1];
            lastChatId = Math.max(lastChatId, parseInt(last.id));
            sseUrl.searchParams.set('last_chat_id', lastChatId);
        }
    });

    // Hàm tạo Toast đẹp đẽ nếu chưa được khai báo
    function showNtkToast(title, msg, iconClass = "fa-bell") {
        const container = document.getElementById('ntk-toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = 'ntk-toast';
        toast.innerHTML = `
            <i class="fa-solid ${iconClass}"></i>
            <div class="ntk-toast-content">
                <div class="ntk-toast-title">${title}</div>
                <div class="ntk-toast-msg">${msg}</div>
            </div>
            <button class="ntk-toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        container.appendChild(toast);
        
        // Tự biến mất sau 5s
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
</script>

</div><!-- /.admin-content -->
</main>
</body>
</html>
