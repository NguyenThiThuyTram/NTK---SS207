<?php
session_start();
require_once '../config/database.php';

// Cấu hình Header cho SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Tắt buffering của Nginx (nếu có dùng)

// Không giới hạn thời gian thực thi của script
set_time_limit(0);

// Giải phóng session để tránh khóa session (Session Blocking)
// cho phép user mở các tab/trang khác đồng thời
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 0;
session_write_close();

if (!$user_id) {
    echo "event: error\ndata: {\"msg\": \"Unauthorized\"}\n\n";
    exit;
}

// Xóa tất cả output buffer hiện tại
while (ob_get_level() > 0) ob_end_flush();
flush();

// Nhận ID cuối cùng từ Client truyền lên để chỉ lấy dữ liệu MỚI
$last_notif_id = isset($_GET['last_notif_id']) ? (int)$_GET['last_notif_id'] : 0;
$last_chat_id = isset($_GET['last_chat_id']) ? (int)$_GET['last_chat_id'] : 0;

// Lấy danh sách trạng thái hiện tại của tất cả đơn hàng (đang chờ/đang xử lý) của user này
$current_orders = [];
try {
    $stmt = $conn->prepare("SELECT order_id, order_status, payment_status FROM orders WHERE user_id = :uid AND (order_status IN (0, 1) OR payment_status = 0)");
    $stmt->execute(['uid' => $user_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $current_orders[$row['order_id']] = [
            'os' => (int)$row['order_status'],
            'ps' => (int)$row['payment_status']
        ];
    }
} catch (Exception $e) {}

// Lặp vô hạn để đẩy dữ liệu
while (true) {
    // Nếu client ngắt kết nối
    if (connection_aborted()) {
        break;
    }

    $events = [];

    // 1. Kiểm tra THÔNG BÁO MỚI
    try {
        $stmt_notif = $conn->prepare("SELECT * FROM notifications WHERE user_id = :uid AND notification_id > :last_id ORDER BY notification_id ASC");
        $stmt_notif->execute(['uid' => $user_id, 'last_id' => $last_notif_id]);
        $new_notifs = $stmt_notif->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($new_notifs)) {
            $events['notifications'] = $new_notifs;
            // Cập nhật last_id
            $last_notif_id = end($new_notifs)['notification_id'];
        }
    } catch (PDOException $e) {}

    // 2. Kiểm tra CHAT MỚI (nếu bảng chat_messages tồn tại)
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
            $last_chat_id = end($new_chats)['id'];
        }
    } catch (PDOException $e) {}

    // 3. Kiểm tra TRẠNG THÁI ĐƠN HÀNG của user
    try {
        $stmt_ord = $conn->prepare("SELECT order_id, order_status, payment_status FROM orders WHERE user_id = :uid AND (order_status IN (0, 1) OR payment_status = 0)");
        $stmt_ord->execute(['uid' => $user_id]);
        
        while ($ord = $stmt_ord->fetch(PDO::FETCH_ASSOC)) {
            $oid = $ord['order_id'];
            $new_os = (int)$ord['order_status'];
            $new_ps = (int)$ord['payment_status'];

            // Nếu đơn hàng đã có trong mảng theo dõi và trạng thái thay đổi
            if (isset($current_orders[$oid])) {
                if ($new_os !== $current_orders[$oid]['os'] || $new_ps !== $current_orders[$oid]['ps']) {
                    if (!isset($events['order_update'])) $events['order_update'] = [];
                    $events['order_update'][] = [
                        'order_id' => $oid,
                        'order_status' => $new_os,
                        'payment_status' => $new_ps
                    ];
                    // Cập nhật lại state cục bộ
                    $current_orders[$oid] = ['os' => $new_os, 'ps' => $new_ps];
                }
            } else {
                // Đơn hàng mới xuất hiện
                $current_orders[$oid] = ['os' => $new_os, 'ps' => $new_ps];
            }
        }
    } catch (PDOException $e) {}

    // Nếu có sự kiện mới, ĐẨY DỮ LIỆU về trình duyệt
    if (!empty($events)) {
        echo "event: message\n";
        echo "data: " . json_encode($events) . "\n\n";
        
        // Bắt buộc flush buffer
        @ob_flush();
        flush();
    }

    // Ngủ 2 giây trước khi check lại để không gây quá tải CSDL
    sleep(2);
}
?>
