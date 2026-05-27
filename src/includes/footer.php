<?php
// Tính BASE URL cho footer (giống header.php)
$_f_is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$_f_protocol = $_f_is_https ? 'https' : 'http';
$_f_host = $_SERVER['HTTP_HOST'];
$_f_src_dir = str_replace('\\', '/', realpath(__DIR__ . '/../'));
$_f_doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$_f_src_path = str_replace($_f_doc_root, '', $_f_src_dir);
$_FBASE = $_f_protocol . '://' . $_f_host . $_f_src_path;
?>
</main>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* ============================================================
       CSS CHUẨN ĐỒNG BỘ DARK MODE & FIX CĂN GIỮA ICON
    ============================================================ */
    
    /* ÉP CĂN GIỮA MẠNG XÃ HỘI CHUẨN HIGH-FASHION */
    .social-icons {
        display: flex !important;
        justify-content: center !important; /* Căn giữa theo chiều ngang */
        align-items: center !important;     /* Căn giữa theo chiều dọc */
        gap: 15px;                          /* Khoảng cách giữa các vòng tròn icon */
        margin: 15px auto 20px;             /* Tạo khoảng cách thông thoáng trên dưới */
        width: 100%;
    }

    /* Đảm bảo vòng tròn bọc icon cũng chuẩn tâm */
    .social-icons a {
        display: inline-flex !important;
        justify-content: center !important;
        align-items: center !important;
        width: 40px;
        height: 40px;
        border: 1px solid #ddd;
        border-radius: 50%;
        text-decoration: none;
        transition: all 0.3s ease;
        color: #333333 !important; /* Đặt màu mặc định cho icon */
    }
    
    .social-icons a:hover {
        border-color: #222 !important;
        background-color: #222 !important;
    }
    .social-icons a:hover i {
        color: #fff !important;
    }

    /* CSS bổ trợ để fix triệt để hiển thị Footer trong Dark Mode */
    body.dark-mode .main-footer {
        background-color: #1a1a1a !important;
        border-top: 1px solid #333;
        color: #ddd !important;
    }

    body.dark-mode .footer-logo {
        filter: brightness(0) invert(1) !important;
        border: 2px solid #ffffff !important;
        border-radius: 50%;
    }
    
    /* Ép màu cho các tiêu đề H3 */
    body.dark-mode .footer-col h3 {
        color: #ffffff !important;
        text-shadow: 0 0 2px rgba(255,255,255,0.2);
        margin-bottom: 20px;
        font-weight: bold;
    }
    
    /* Làm nổi bật các dòng text thông thường và link */
    body.dark-mode .footer-col p, 
    body.dark-mode .footer-col li,
    body.dark-mode .footer-col a {
        color: #bbbbbb !important;
    }
    body.dark-mode .footer-col a:hover {
        color: #f1c40f !important; /* Di chuột vào hiện màu vàng NTK */
    }
    
    /* Phần bản quyền phía dưới */
    body.dark-mode .footer-bottom {
        background-color: #111 !important;
        border-top: 1px solid #222;
        color: #888 !important;
    }
    
    /* Icon mạng xã hội khi ở Dark Mode */
    body.dark-mode .social-icons a {
        border-color: #444 !important;
        color: #f1c40f !important; /* Icon màu vàng khi ở chế độ tối */
    }
    body.dark-mode .social-icons a i {
        color: #f1c40f !important;
    }
    body.dark-mode .social-icons a:hover {
        background-color: #f1c40f !important;
        border-color: #f1c40f !important;
    }
    body.dark-mode .social-icons a:hover i {
        color: #111 !important;
    }
</style>

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-col">
            <div class="footer-logo-box" style="margin-bottom: 15px;">
                <img src="<?= $_FBASE ?>/assets/images/logo-ntk.png" alt="NTK Logo" class="footer-logo" id="footerLogo">
            </div>
            <p>Khám phá những thiết kế giúp bạn tự tin mỗi ngày.</p>
            <p>Hotline: <strong>0373546444</strong></p>
            <p>Giờ hoạt động: 9:00 - 21:00</p>
        </div>

        <div class="footer-col">
            <h3>HỖ TRỢ KHÁCH HÀNG</h3>
            <ul>
                <li><a href="return-policy.php">Chính sách đổi trả</a></li>
                <li><a href="payment-policy.php">Chính sách thanh toán</a></li>
                <li><a href="shipping-policy.php">Chính sách vận chuyển</a></li>
                <li><a href="guide.php">Hướng dẫn mua hàng</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3>DANH MỤC NỔI BẬT</h3>
            <ul class="footer-links">
                <li><a href="product.php?cat=CAT01">Áo thun</a></li>
                <li><a href="product.php?cat=CAT02">Áo khoác</a></li>
                <li><a href="product.php?cat=CAT03">Hoodie & Sweater</a></li>
                <li><a href="product.php?cat=CAT04">Quần</a></li>
                <li><a href="product.php?cat=CAT05">Áo sơ mi</a></li>
            </ul>
        </div>

        <div class="footer-col" style="text-align: center;"> 
            <h3>KẾT NỐI VỚI CHÚNG TÔI</h3>
            <div class="social-icons">
                <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-telegram"></i></a>
                <a href="#"><i class="fa-solid fa-bag-shopping"></i></a>
            </div>
            <p class="social-note" style="margin-top: 10px;">Follow để cập nhật xu hướng mới nhất và nhận các deal hot từ NTK!</p>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="bottom-container">
            <p>© <?php echo date("Y"); ?> NTK Fashion. All rights reserved.</p>
            <p>GPKD: 0123456789 | Cấp ngày: 01/01/2026</p>
        </div>
    </div>
</footer>

<script>
    // Đồng bộ màu sắc logo tự động khi load trang
    document.addEventListener('DOMContentLoaded', function() {
        const isDark = document.body.classList.contains('dark-mode');
        const fLogo = document.getElementById('footerLogo');
        if (isDark && fLogo) {
            fLogo.style.filter = 'brightness(0) invert(1)';
        }
    });
</script>

<style>
    /* ============================================================
       CSS CHATBOX HUman & AI Bot NTK Fashion
    ============================================================ */
    #ntk-chat-toggle { 
        position: fixed; bottom: 20px; right: 20px; 
        background: linear-gradient(135deg, #2f1c00 0%, #4a2c00 100%); 
        color: #ffffff; 
        border: none; border-radius: 50%; 
        width: 60px; height: 60px; 
        font-size: 24px; cursor: pointer; 
        box-shadow: 0 8px 24px rgba(47,28,0,0.25); 
        z-index: 9998; 
        display: flex; align-items: center; justify-content: center;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    #ntk-chat-toggle:hover {
        transform: scale(1.1) translateY(-2px);
        box-shadow: 0 12px 28px rgba(47,28,0,0.35);
    }
    
    #ntk-chatbox { 
        position: fixed; bottom: 90px; right: 20px; 
        width: 340px; height: 480px;
        background: #ffffff; 
        border: 1px solid #e5e5e5; 
        border-radius: 16px; 
        box-shadow: 0 12px 36px rgba(0,0,0,0.1); 
        display: none; flex-direction: column; 
        z-index: 9999; overflow: hidden; 
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; 
        color: #111111; 
        transition: all 0.3s ease;
    }
    
    body.dark-mode #ntk-chatbox {
        background: #181818 !important;
        border-color: #2a2a2a !important;
        color: #fff !important;
    }
    
    #ntk-chat-header { 
        background: linear-gradient(135deg, #2f1c00 0%, #4a2c00 100%); 
        color: #ffffff; 
        padding: 14px 18px; 
        font-weight: bold;
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    #ntk-chat-header select {
        background: rgba(255,255,255,0.15);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 13px;
        font-weight: 600;
        outline: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    #ntk-chat-header select:hover {
        background: rgba(255,255,255,0.25);
    }
    #ntk-chat-header select option {
        color: #000;
        background: #fff;
    }
    
    #ntk-chat-messages { 
        flex: 1; overflow-y: auto; 
        padding: 16px; 
        background: #f9f9fa; 
        display: flex; flex-direction: column; gap: 12px; 
    }
    body.dark-mode #ntk-chat-messages {
        background: #121212 !important;
    }
    
    #ntk-chat-input-area { 
        display: flex; align-items: center;
        border-top: 1px solid #e5e5e5; 
        padding: 12px 16px; 
        background: #ffffff; 
    }
    body.dark-mode #ntk-chat-input-area {
        background: #181818 !important;
        border-top-color: #2a2a2a !important;
    }
    
    #ntk-chat-input { 
        flex: 1; padding: 10px 16px; 
        border: 1px solid #e5e5e5; 
        border-radius: 20px; outline: none; 
        font-size: 13px;
        background-color: #f9f9fa;
        color: #111111;
        transition: all 0.3s;
    }
    #ntk-chat-input:focus {
        border-color: #2f1c00;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(47,28,0,0.1);
    }
    body.dark-mode #ntk-chat-input {
        background-color: #252525 !important;
        border-color: #333333 !important;
        color: #ffffff !important;
    }
    body.dark-mode #ntk-chat-input:focus {
        border-color: #a6825c !important;
        box-shadow: 0 0 0 3px rgba(166,130,92,0.2);
    }
    
    #ntk-send-btn { 
        background: #2f1c00; 
        color: #ffffff; border: none; 
        padding: 10px 18px; margin-left: 8px; 
        border-radius: 20px; cursor: pointer; 
        font-weight: bold; font-size: 13px;
        transition: all 0.2s;
    }
    #ntk-send-btn:hover {
        background: #1a0f00;
    }
    body.dark-mode #ntk-send-btn {
        background: #a6825c !important;
        color: #121212 !important;
    }
    body.dark-mode #ntk-send-btn:hover {
        background: #c9a47e !important;
    }
    
    .msg-user { 
        background: #2f1c00; 
        color: #ffffff; 
        padding: 10px 14px; 
        border-radius: 15px 15px 0 15px; 
        align-self: flex-end; max-width: 80%; 
        font-size: 13px; line-height: 1.45;
        box-shadow: 0 2px 6px rgba(47,28,0,0.15);
        word-wrap: break-word;
        animation: msgSlideUp 0.25s ease;
    }
    body.dark-mode .msg-user {
        background: #a6825c !important;
        color: #121212 !important;
        box-shadow: 0 2px 6px rgba(166,130,92,0.2);
    }
    
    .msg-bot { 
        background: #ffffff; 
        border: 1px solid #e5e5e5; 
        color: #111111; 
        padding: 10px 14px; 
        border-radius: 15px 15px 15px 0; 
        align-self: flex-start; max-width: 80%; 
        font-size: 13px; line-height: 1.45;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        word-wrap: break-word;
        animation: msgSlideUp 0.25s ease;
    }
    body.dark-mode .msg-bot {
        background: #222222 !important;
        border-color: #2a2a2a !important;
        color: #eeeeee !important;
    }
    @keyframes msgSlideUp {
        from { transform: translateY(8px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<button id="ntk-chat-toggle" onclick="toggleChat()">
    <i class="fa-solid fa-comments"></i>
</button>

<div id="ntk-chatbox">
    <div id="ntk-chat-header">
        <select id="chat-mode">
            <option value="bot">🤖 Trợ lý AI</option>
            <option value="human">👩‍💼 Nhân viên NTK</option>
        </select>
        <span onclick="toggleChat()" style="cursor:pointer; font-size:16px;">✖</span>
    </div>
    <div id="ntk-chat-messages">
        <div class="msg-bot">Dạ chào anh/chị, em là trợ lý AI của NTK Fashion. Em có thể giúp gì cho mình ạ?</div>
    </div>
    <div id="ntk-chat-input-area">
        <input type="text" id="ntk-chat-input" placeholder="Nhập tin nhắn..." onkeypress="if(event.key==='Enter') sendMessage()">
        <button id="ntk-send-btn" onclick="sendMessage()">Gửi</button>
    </div>
</div>

<script>
    function toggleChat() {
        const chatbox = document.getElementById('ntk-chatbox');
        const isOpening = (chatbox.style.display !== 'flex');
        chatbox.style.display = isOpening ? 'flex' : 'none';
        
        if (isOpening) {
            const chatMode = document.getElementById('chat-mode').value;
            if (chatMode === 'human') {
                loadChatHistory();
            }
            document.getElementById('ntk-chat-input').focus();
        }
    }

    document.getElementById('chat-mode').addEventListener('change', function() {
        const messagesDiv = document.getElementById('ntk-chat-messages');
        if (this.value === 'human') {
            loadChatHistory();
        } else {
            messagesDiv.innerHTML = '<div class="msg-bot">Dạ chào anh/chị, em là trợ lý AI của NTK Fashion. Em có thể giúp gì cho mình ạ?</div>';
        }
    });

    async function loadChatHistory(isSilent = false) {
        const messagesDiv = document.getElementById('ntk-chat-messages');
        if (!isSilent) {
            messagesDiv.innerHTML = '<div style="text-align:center; padding:20px; color:#aaa;"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải hội thoại...</div>';
        }
        
        try {
            const response = await fetch('<?= $_FBASE ?>/api/chat_history.php');
            const data = await response.json();
            
            if (data.success) {
                messagesDiv.innerHTML = '';
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(m => {
                        const isUser = (m.sender_id === <?= json_encode(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '') ?>);
                        const msgClass = isUser ? 'msg-user' : 'msg-bot';
                        const label = isUser ? '' : '<b>Nhân viên:</b><br>';
                        messagesDiv.innerHTML += `<div class="${msgClass}">${label}${m.message}</div>`;
                    });
                    
                    const lastMsg = data.messages[data.messages.length - 1];
                    if (lastMsg && typeof sseUrl !== 'undefined') {
                        lastChatId = Math.max(lastChatId, parseInt(lastMsg.id));
                        sseUrl.searchParams.set('last_chat_id', lastChatId);
                    }
                } else {
                    messagesDiv.innerHTML = '<div style="text-align:center; padding:20px; color:#aaa; font-size:12.5px;">Chưa có tin nhắn nào. Bạn hãy nhập câu hỏi phía dưới để trò chuyện với Nhân viên nhé!</div>';
                }
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            } else {
                if (!isSilent) {
                    messagesDiv.innerHTML = `<div style="text-align:center; padding:20px; color:#e74c3c; font-size:12.5px;"><i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>${data.message}</div>`;
                }
            }
        } catch (e) {
            if (!isSilent) {
                messagesDiv.innerHTML = '<div style="text-align:center; padding:20px; color:#e74c3c; font-size:12.5px;">Không thể tải lịch sử chat. Vui lòng kiểm tra kết nối!</div>';
            }
        }
    }

    async function sendMessage() {
        const input = document.getElementById('ntk-chat-input');
        const msgText = input.value.trim();
        if (!msgText) return;

        const messagesDiv = document.getElementById('ntk-chat-messages');
        const chatMode = document.getElementById('chat-mode').value;
        
        messagesDiv.innerHTML += `<div class="msg-user">${msgText}</div>`;
        input.value = '';
        messagesDiv.scrollTop = messagesDiv.scrollHeight;

        if (chatMode === 'bot') {
            const typingId = "typing-" + Date.now();
            messagesDiv.innerHTML += `<div class="msg-bot" id="${typingId}"><i class="fa-solid fa-ellipsis fa-bounce"></i> Trợ lý AI đang xử lý...</div>`;
            messagesDiv.scrollTop = messagesDiv.scrollHeight;

            try {
                const response = await fetch('<?= $_FBASE ?>/api_chatbot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: msgText })
                });
                const data = await response.json();
                
                const typNode = document.getElementById(typingId);
                if (typNode) typNode.remove();
                
                messagesDiv.innerHTML += `<div class="msg-bot">${data.reply.replace(/\n/g, '<br>')}</div>`;
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            } catch (error) {
                const typNode = document.getElementById(typingId);
                if (typNode) typNode.innerHTML = "Đã xảy ra lỗi kết nối AI!";
            }
        } else {
            // Live chat với nhân viên
            try {
                const formData = new FormData();
                formData.append('message', msgText);
                formData.append('receiver_id', 0); // 0 = Admin
                
                const response = await fetch('<?= $_FBASE ?>/api/chat_send.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (!data.success) {
                    messagesDiv.innerHTML += `<div class="msg-bot" style="color:#c0392b; font-size:12px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i>Hệ thống: ${data.message}</div>`;
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                }
            } catch (error) {
                messagesDiv.innerHTML += `<div class="msg-bot" style="color:#c0392b; font-size:12px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i>Hệ thống: Lỗi kết nối gửi tin.</div>`;
            }
        }
    }

    // Hook nhận tin nhắn từ SSE
    if (typeof window.handleNewChatMessage === 'undefined' || window.handleNewChatMessage.name !== 'globalHandleNewChatMessage') {
        window.handleNewChatMessage = function globalHandleNewChatMessage(messages) {
            const chatbox = document.getElementById('ntk-chatbox');
            const selectMode = document.getElementById('chat-mode');
            
            let hasNewFromStaff = false;
            messages.forEach(msg => {
                if (msg.sender_id !== <?= json_encode(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '') ?>) {
                    hasNewFromStaff = true;
                }
            });

            if (hasNewFromStaff) {
                // Tự động mở hộp chat nếu chưa mở
                if (chatbox.style.display !== 'flex') {
                    chatbox.style.display = 'flex';
                }
                // Tự chuyển sang tab chat nhân viên và load lại
                if (selectMode && selectMode.value !== 'human') {
                    selectMode.value = 'human';
                }
                loadChatHistory(true);
            }
        };
    }
</script>

<script src="<?= $_FBASE ?>/assets/js/main.js"></script>
</body>
</html>