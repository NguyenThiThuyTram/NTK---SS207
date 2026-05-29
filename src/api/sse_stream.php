<?php
// Để tránh khóa session quá lâu, script này sẽ chạy cực nhanh và thoát.
session_start();
require_once '../config/database.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['role'] ?? 0;

if (!$user_id) {
    echo "event: error\ndata: {\"msg\": \"Unauthorized\"}\n\n";
    exit;
}

// Báo cho EventSource client tự động gọi lại sau 5 giây
echo "retry: 5000\n";

$last_event_id = $_SERVER['HTTP_LAST_EVENT_ID'] ?? '';
if (!empty($last_event_id) && strpos($last_event_id, '_') !== false) {
    list($last_chat_id_from_id, $last_notif_id_from_id) = explode('_', $last_event_id);
    $last_chat_id = (int)$last_chat_id_from_id;
    $last_notif_id = (int)$last_notif_id_from_id;
} else {
    $last_notif_id = isset($_GET['last_notif_id']) ? (int)$_GET['last_notif_id'] : ($_SESSION['sse_last_notif_id_' . $user_id] ?? 0);
    $last_chat_id  = isset($_GET['last_chat_id'])  ? (int)$_GET['last_chat_id']  : ($_SESSION['sse_last_chat_id_'  . $user_id] ?? 0);
}

$events = [];
$new_notifs = [];
$new_chats  = [];

// ── 1. Kiểm tra THÔNG BÁO MỚI ───────────────────────────────────────────────
try {
    if (isset($conn)) {
        $stmt_notif = $conn->prepare("SELECT * FROM notifications WHERE user_id = :uid AND noti_id > :last_id ORDER BY noti_id ASC");
        $stmt_notif->execute(['uid' => $user_id, 'last_id' => $last_notif_id]);
        $new_notifs = $stmt_notif->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($new_notifs)) {
            $events['notifications'] = $new_notifs;
            $_SESSION['sse_last_notif_id_' . $user_id] = max($last_notif_id, (int)end($new_notifs)['noti_id']);
        }
    }
} catch (\Throwable $e) {
    error_log("SSE notifications error for user $user_id: " . $e->getMessage());
}

// ── 2. Kiểm tra CHAT MỚI ────────────────────────────────────────────────────
try {
    if (isset($conn)) {
        $chat_cond = "receiver_id = :uid";
        if ($role == 1) {
            $chat_cond = "(receiver_id = :uid OR receiver_id IS NULL OR receiver_id = '0')";
        }

        $stmt_chat = $conn->prepare("
            SELECT c.*, u.fullname as sender_name 
            FROM chat_messages c
            LEFT JOIN users u ON c.sender_id = u.user_id
            WHERE $chat_cond AND c.id > :last_id 
            ORDER BY c.id ASC
        ");
        $stmt_chat->execute(['uid' => $user_id, 'last_id' => $last_chat_id]);
        $new_chats = $stmt_chat->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($new_chats)) {
            $events['chat_messages'] = $new_chats;
            $_SESSION['sse_last_chat_id_' . $user_id] = max($last_chat_id, (int)end($new_chats)['id']);
        }
    }
} catch (\Throwable $e) {
    error_log("SSE chat error for user $user_id: " . $e->getMessage());
}

// ── 3. Kiểm tra TRẠNG THÁI ĐƠN HÀNG ───────────────────────────────────────────────
// KEY phân tách theo user_id để tránh ghi đè state giữa các tài khoản
$state_key     = 'sse_orders_state_' . $user_id;
$current_orders = $_SESSION[$state_key] ?? [];
$is_first_load  = empty($current_orders);  // Lần đầu: chưa có state, KHÔNG phát event
$new_state      = [];

try {
    if (isset($conn)) {
        if ($role == 1) {
            // Admin: lấy thêm thông tin đơn hàng để hiển thị toast
            $stmt_ord = $conn->prepare("
                SELECT o.order_id, o.order_status, o.payment_status,
                       o.order_date, o.final_price, o.fullname
                FROM orders o
                ORDER BY o.order_id DESC LIMIT 100
            ");
            $stmt_ord->execute();
        } else {
            $stmt_ord = $conn->prepare("SELECT order_id, order_status, payment_status, order_date, final_price, fullname FROM orders WHERE user_id = :uid ORDER BY order_id DESC LIMIT 50");
            $stmt_ord->execute(['uid' => $user_id]);
        }

        while ($ord = $stmt_ord->fetch(PDO::FETCH_ASSOC)) {
            $oid    = $ord['order_id'];
            $new_os = (int)$ord['order_status'];
            $new_ps = (int)$ord['payment_status'];
            $new_state[$oid] = ['os' => $new_os, 'ps' => $new_ps];

            if (!$is_first_load) {
                if (!isset($current_orders[$oid])) {
                    // ĐƠN HÀNG MỚI: order_id chưa từng có trong state
                    if (!isset($events['new_order'])) $events['new_order'] = [];
                    $events['new_order'][] = [
                        'order_id'      => $oid,
                        'order_status'  => $new_os,
                        'payment_status'=> $new_ps,
                        'final_price'   => $ord['final_price'] ?? 0,
                        'fullname'      => $ord['fullname'] ?? '',
                        'order_date'    => $ord['order_date'] ?? ''
                    ];
                } elseif ($new_os !== $current_orders[$oid]['os'] || $new_ps !== $current_orders[$oid]['ps']) {
                    // THAY ĐỔI TRẠNG THÁI: order cũ có sự thay đổi
                    if (!isset($events['order_update'])) $events['order_update'] = [];
                    $events['order_update'][] = [
                        'order_id'       => $oid,
                        'order_status'   => $new_os,
                        'payment_status' => $new_ps
                    ];
                }
            }
        }
    }
} catch (\Throwable $e) {
    error_log("SSE orders error for user $user_id: " . $e->getMessage());
}

// ── 4. Kiểm tra VOUCHER MỚI ───────────────────────────────────────────────
$coupon_state_key = 'sse_coupon_count_' . $user_id;
$prev_coupon_count = $_SESSION[$coupon_state_key] ?? -1; // -1 = lần đầu
$is_first_coupon_load = ($prev_coupon_count === -1);

try {
    if (isset($conn)) {
        $stmt_cp = $conn->prepare("SELECT COUNT(*) as cnt FROM coupons WHERE status = 1 AND (end_date IS NULL OR end_date >= NOW())");
        $stmt_cp->execute();
        $current_coupon_count = (int)$stmt_cp->fetchColumn();

        $_SESSION[$coupon_state_key] = $current_coupon_count;

        // Chỉ phát event khi KHÔNG phải lần đầu load VÀ có thay đổi số lượng coupon
        if (!$is_first_coupon_load && $current_coupon_count !== $prev_coupon_count) {
            $events['new_coupon'] = [
                'count' => $current_coupon_count,
                'prev_count' => $prev_coupon_count
            ];
        }
    }
} catch (\Throwable $e) {
    error_log("SSE coupon error for user $user_id: " . $e->getMessage());
}
// ── 5. Kiểm tra FLASH SALE MỚI ────────────────────────────────────────────
$fs_state_key = 'sse_flash_sale_count_' . $user_id;
$prev_fs_count = $_SESSION[$fs_state_key] ?? -1;
$is_first_fs_load = ($prev_fs_count === -1);

try {
    if (isset($conn)) {
        $stmt_fs = $conn->prepare("SELECT COUNT(*) FROM flash_sales WHERE status = 1 AND sale_date = CURRENT_DATE()");
        $stmt_fs->execute();
        $current_fs_count = (int)$stmt_fs->fetchColumn();

        $_SESSION[$fs_state_key] = $current_fs_count;

        if (!$is_first_fs_load && $current_fs_count !== $prev_fs_count) {
            $events['new_flash_sale'] = [
                'count' => $current_fs_count,
                'prev_count' => $prev_fs_count
            ];
        }
    }
} catch (\Throwable $e) {
    error_log("SSE flash_sale error for user $user_id: " . $e->getMessage());
}

// Lưu state phân tách theo user_id và đóng Session ngay lập tức
$_SESSION[$state_key] = $new_state;
session_write_close();

// Nếu có sự kiện mới, ĐẨY DỮ LIỆU
if (!empty($events)) {
    $max_chat_sent  = !empty($new_chats)  ? (int)end($new_chats)['id']        : $last_chat_id;
    $max_notif_sent = !empty($new_notifs) ? (int)end($new_notifs)['noti_id']  : $last_notif_id;
    $eventId = $max_chat_sent . '_' . $max_notif_sent;

    echo "id: $eventId\n";
    echo "event: message\n";
    echo "data: " . json_encode($events) . "\n\n";
} else {
    echo ": no-events\n\n";
}

exit;
?>

