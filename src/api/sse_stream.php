<?php
// Để tránh khóa session quá lâu, script này sẽ chạy cực nhanh và thoát.
session_start();
require_once '../config/database.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 0;

if (!$user_id) {
    echo "event: error\ndata: {\"msg\": \"Unauthorized\"}\n\n";
    exit;
}

// Báo cho EventSource client tự động gọi lại sau 3 giây
echo "retry: 3000\n";

$last_notif_id = $_SESSION['sse_last_notif_id'] ?? 0;
$last_chat_id = $_SESSION['sse_last_chat_id'] ?? 0;

$events = [];

// 1. Kiểm tra THÔNG BÁO MỚI
try {
    $stmt_notif = $conn->prepare("SELECT * FROM notifications WHERE user_id = :uid AND notification_id > :last_id ORDER BY notification_id ASC");
    $stmt_notif->execute(['uid' => $user_id, 'last_id' => $last_notif_id]);
    $new_notifs = $stmt_notif->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($new_notifs)) {
        $events['notifications'] = $new_notifs;
        $_SESSION['sse_last_notif_id'] = end($new_notifs)['notification_id'];
    }
} catch (PDOException $e) {}

// 2. Kiểm tra CHAT MỚI
try {
    $chat_cond = "receiver_id = :uid";
    if ($role == 1) {
        $chat_cond = "(receiver_id = :uid OR receiver_id IS NULL OR receiver_id = 0)"; 
    }
    
    $stmt_chat = $conn->prepare("SELECT * FROM chat_messages WHERE $chat_cond AND id > :last_id ORDER BY id ASC");
    $stmt_chat->execute(['uid' => $user_id, 'last_id' => $last_chat_id]);
    $new_chats = $stmt_chat->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($new_chats)) {
        $events['chat_messages'] = $new_chats;
        $_SESSION['sse_last_chat_id'] = end($new_chats)['id'];
    }
} catch (PDOException $e) {}

// 3. Kiểm tra TRẠNG THÁI ĐƠN HÀNG (Sử dụng Session để lưu state)
$current_orders = $_SESSION['sse_orders_state'] ?? [];
$new_state = [];
$is_first_load = empty($current_orders);

try {
    if ($role == 1) {
        $stmt_ord = $conn->prepare("SELECT order_id, order_status, payment_status FROM orders ORDER BY order_id DESC LIMIT 100");
        $stmt_ord->execute();
    } else {
        $stmt_ord = $conn->prepare("SELECT order_id, order_status, payment_status FROM orders WHERE user_id = :uid ORDER BY order_id DESC LIMIT 50");
        $stmt_ord->execute(['uid' => $user_id]);
    }
    
    while ($ord = $stmt_ord->fetch(PDO::FETCH_ASSOC)) {
        $oid = $ord['order_id'];
        $new_os = (int)$ord['order_status'];
        $new_ps = (int)$ord['payment_status'];
        $new_state[$oid] = ['os' => $new_os, 'ps' => $new_ps];

        if (isset($current_orders[$oid])) {
            if ($new_os !== $current_orders[$oid]['os'] || $new_ps !== $current_orders[$oid]['ps']) {
                if (!isset($events['order_update'])) $events['order_update'] = [];
                $events['order_update'][] = [
                    'order_id' => $oid,
                    'order_status' => $new_os,
                    'payment_status' => $new_ps
                ];
            }
        }
    }
} catch (PDOException $e) {}

// Lưu trạng thái mới vào Session và đóng Session ngay lập tức
$_SESSION['sse_orders_state'] = $new_state;
session_write_close();

// Nếu có sự kiện mới, ĐẨY DỮ LIỆU
if (!empty($events)) {
    echo "event: message\n";
    echo "data: " . json_encode($events) . "\n\n";
} else {
    echo ": no-events\n\n";
}

exit;
?>
