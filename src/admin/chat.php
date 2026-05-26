<?php
session_start();
require_once '../config/database.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../index.php");
    exit;
}

// Lấy danh sách user đã nhắn tin
$stmt = $conn->query("
    SELECT u.user_id, u.full_name, MAX(c.created_at) as last_msg
    FROM chat_messages c
    JOIN users u ON (c.sender_id = u.user_id OR c.receiver_id = u.user_id)
    WHERE u.role = 0
    GROUP BY u.user_id
    ORDER BY last_msg DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_user = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$messages = [];
if ($current_user > 0) {
    // Đánh dấu đã đọc
    $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = :uid AND receiver_id = 0")->execute(['uid' => $current_user]);
    
    // Lấy tin nhắn
    $stmt_msg = $conn->prepare("SELECT * FROM chat_messages WHERE (sender_id = :uid AND receiver_id = 0) OR (sender_id = :admin AND receiver_id = :uid) ORDER BY id ASC");
    $stmt_msg->execute(['uid' => $current_user, 'admin' => $_SESSION['user_id']]);
    $messages = $stmt_msg->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Live Chat</title>
    <style>
        body { font-family: sans-serif; display: flex; height: 100vh; margin: 0; }
        .sidebar { width: 250px; background: #f4f4f4; border-right: 1px solid #ddd; overflow-y: auto; }
        .sidebar a { display: block; padding: 15px; text-decoration: none; color: #333; border-bottom: 1px solid #ddd; }
        .sidebar a:hover, .sidebar a.active { background: #e0e0e0; }
        .chat-area { flex: 1; display: flex; flex-direction: column; background: #fff; }
        .chat-header { padding: 15px; background: #2f1c00; color: white; }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; background: #fafafa; }
        .msg { max-width: 70%; padding: 10px 15px; margin-bottom: 10px; border-radius: 15px; line-height: 1.4; }
        .msg-admin { background: #2f1c00; color: white; align-self: flex-end; margin-left: auto; border-bottom-right-radius: 0; }
        .msg-user { background: #e5e5e5; color: black; align-self: flex-start; border-bottom-left-radius: 0; }
        .chat-input { padding: 15px; background: #fff; border-top: 1px solid #ddd; display: flex; }
        .chat-input input { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .chat-input button { padding: 10px 20px; background: #2f1c00; color: white; border: none; margin-left: 10px; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 style="padding: 15px; margin: 0; background: #222; color: #fff;">Khách hàng</h3>
        <?php foreach ($users as $u): ?>
            <a href="?uid=<?= $u['user_id'] ?>" class="<?= $current_user == $u['user_id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($u['full_name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <div class="chat-area">
        <?php if ($current_user > 0): ?>
            <div class="chat-header">Đang chat với khách hàng #<?= $current_user ?></div>
            <div class="chat-messages" id="chat-messages">
                <?php foreach ($messages as $m): ?>
                    <div class="msg <?= $m['sender_id'] == $_SESSION['user_id'] ? 'msg-admin' : 'msg-user' ?>">
                        <?= htmlspecialchars($m['message']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="chat-input">
                <input type="text" id="msg-input" placeholder="Nhập tin nhắn trả lời..." onkeypress="if(event.key === 'Enter') sendAdminMsg()">
                <button onclick="sendAdminMsg()">Gửi</button>
            </div>
            
            <script>
                const chatBox = document.getElementById('chat-messages');
                chatBox.scrollTop = chatBox.scrollHeight;
                
                async function sendAdminMsg() {
                    const input = document.getElementById('msg-input');
                    const msg = input.value.trim();
                    if (!msg) return;
                    
                    chatBox.innerHTML += `<div class="msg msg-admin">${msg}</div>`;
                    input.value = '';
                    chatBox.scrollTop = chatBox.scrollHeight;
                    
                    const fd = new FormData();
                    fd.append('message', msg);
                    fd.append('receiver_id', <?= $current_user ?>);
                    
                    await fetch('../api/chat_send.php', { method: 'POST', body: fd });
                }

                // Nhận real-time SSE
                const sseUrl = new URL('../api/sse_stream.php', window.location.origin);
                sseUrl.searchParams.set('last_chat_id', <?= !empty($messages) ? end($messages)['id'] : 0 ?>);
                const sse = new EventSource(sseUrl);
                sse.addEventListener('message', function(e) {
                    const data = JSON.parse(e.data);
                    if (data.chat_messages) {
                        data.chat_messages.forEach(m => {
                            if (m.sender_id == <?= $current_user ?>) {
                                chatBox.innerHTML += `<div class="msg msg-user">${m.message}</div>`;
                                chatBox.scrollTop = chatBox.scrollHeight;
                            }
                        });
                        const last = data.chat_messages[data.chat_messages.length-1];
                        sseUrl.searchParams.set('last_chat_id', last.id);
                    }
                });
            </script>
        <?php else: ?>
            <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#888;">
                Vui lòng chọn khách hàng để chat
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
