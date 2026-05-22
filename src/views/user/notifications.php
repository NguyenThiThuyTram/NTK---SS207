<?php
// notifications.php - Included trong dashboard.php
$user_id = $_SESSION['user_id'];

// Đánh dấu tất cả là đã đọc khi mở trang
try {
    $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0")
         ->execute(['uid' => $user_id]);
} catch (PDOException $e) {}

// Lấy danh sách thông báo từ bảng notifications (nếu có)
$notifications = [];
try {
    $sql = "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['uid' => $user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Bỏ qua nếu bảng chưa tồn tại
}

// Bổ sung các thông báo động từ trạng thái đơn hàng hiện tại
try {
    $stmt2 = $conn->prepare("SELECT order_id, order_status, order_date FROM orders WHERE user_id = :uid ORDER BY order_date DESC");
    $stmt2->execute(['uid' => $user_id]);
    $raw = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($raw as $r) {
        $statusTexts = [
            0 => "đang chờ thanh toán",
            1 => "đang ở trạng thái Chờ lấy hàng",
            2 => "đang được giao",
            3 => "đã hoàn thành",
            4 => "đã bị hủy",
            5 => "có yêu cầu trả hàng đang chờ",
            6 => "đang hoàn trả hàng",
            7 => "đã hoàn tiền",
            8 => "đang chờ duyệt hủy",
            9 => "giao hàng thất bại",
            10 => "hàng đang hoàn về kho",
        ];
        $text = $statusTexts[$r['order_status']] ?? "có cập nhật";
        
        // Kiểm tra xem đơn hàng này đã có thông báo cập nhật trạng thái trong mảng chưa để tránh trùng lặp
        $exists = false;
        foreach ($notifications as $n) {
            if ($n['related_order_id'] === $r['order_id'] && $n['type'] === 'order_update') {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            $notifications[] = [
                'noti_id'          => null,
                'type'             => 'order_update',
                'title'            => "Cập nhật đơn hàng #{$r['order_id']}",
                'message'          => "Đơn hàng #{$r['order_id']} {$text}.",
                'related_order_id' => $r['order_id'],
                'is_read'          => 1,
                'created_at'       => $r['order_date'],
            ];
        }
    }
} catch (PDOException $e2) {}

// Sắp xếp lại danh sách thông báo theo thời gian mới nhất
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});

// Mapping icon và màu theo loại thông báo
function getNotiStyle($type) {
    $map = [
        'order_placed'            => ['icon' => 'fa-solid fa-bag-shopping',     'color' => 'icon-blue'],
        'order_pending_payment'   => ['icon' => 'fa-solid fa-clock',            'color' => 'icon-orange'],
        'order_confirmed'         => ['icon' => 'fa-solid fa-circle-check',     'color' => 'icon-green'],
        'order_shipping'          => ['icon' => 'fa-solid fa-truck-fast',        'color' => 'icon-orange'],
        'order_completed'         => ['icon' => 'fa-solid fa-star',             'color' => 'icon-green'],
        'order_cancelled'         => ['icon' => 'fa-regular fa-circle-xmark',   'color' => 'icon-red'],
        'return_request'          => ['icon' => 'fa-solid fa-rotate-left',       'color' => 'icon-orange'],
        'return_approved'         => ['icon' => 'fa-solid fa-thumbs-up',         'color' => 'icon-green'],
        'return_rejected'         => ['icon' => 'fa-solid fa-ban',              'color' => 'icon-red'],
        'refund_done'             => ['icon' => 'fa-solid fa-wallet',           'color' => 'icon-green'],
        'delivery_failed'         => ['icon' => 'fa-solid fa-triangle-exclamation', 'color' => 'icon-red'],
        'cancel_approved'         => ['icon' => 'fa-solid fa-circle-xmark',     'color' => 'icon-red'],
        'cancel_rejected'         => ['icon' => 'fa-solid fa-ban',              'color' => 'icon-red'],
        'order_update'            => ['icon' => 'fa-solid fa-bell',             'color' => 'icon-blue'],
    ];
    return $map[$type] ?? ['icon' => 'fa-solid fa-bell', 'color' => 'icon-blue'];
}

// Hàm tính thời gian tương đối
function timeAgo($datetime) {
    $now  = new DateTime();
    $time = new DateTime($datetime);
    $diff = $now->diff($time);
    if ($diff->d === 0 && $diff->h === 0) {
        return $diff->i <= 1 ? 'Vừa xong' : $diff->i . ' phút trước';
    } elseif ($diff->d === 0) {
        return $diff->h . ' giờ trước';
    } elseif ($diff->d === 1) {
        return 'Hôm qua';
    } elseif ($diff->d < 7) {
        return $diff->d . ' ngày trước';
    } else {
        return date('d/m/Y', strtotime($datetime));
    }
}
?>

<style>
    .noti-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #eee;
    }
    .noti-page-title { font-size: 20px; font-weight: 700; color: #2f1c00; margin: 0; }
    .noti-list { display: flex; flex-direction: column; gap: 2px; }
    .noti-item {
        display: flex;
        align-items: flex-start;
        padding: 16px 18px;
        border-radius: 8px;
        gap: 16px;
        transition: background 0.15s;
        cursor: default;
        border: 1px solid transparent;
    }
    .noti-item:hover { background: #fafaf8; border-color: #f0ede8; }
    .noti-item.unread { background: #fffbf5; border-color: #f5ede0; }

    .noti-icon {
        width: 44px; height: 44px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 17px; flex-shrink: 0; margin-top: 2px;
    }
    .icon-orange { background: #fff1e6; color: #f76b1c; }
    .icon-green  { background: #e6f9ed; color: #21b559; }
    .icon-red    { background: #fee2e2; color: #dc2626; }
    .icon-blue   { background: #e0f2fe; color: #0284c7; }
    .icon-purple { background: #ede9fe; color: #7c3aed; }

    .noti-body { flex: 1; min-width: 0; }
    .noti-title {
        font-size: 14.5px;
        font-weight: 600;
        color: #222;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .noti-msg { font-size: 13.5px; color: #555; line-height: 1.5; }
    .noti-time { font-size: 12px; color: #aaa; margin-top: 6px; }
    .noti-order-link {
        display: inline-block;
        margin-top: 6px;
        font-size: 12.5px;
        color: #2f1c00;
        text-decoration: none;
        font-weight: 500;
        border-bottom: 1px solid #ccc;
    }
    .noti-order-link:hover { color: #ee4d2d; border-color: #ee4d2d; }

    .unread-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: #ee4d2d; flex-shrink: 0; margin-top: 6px;
    }

    .noti-empty {
        text-align: center;
        padding: 60px 20px;
        color: #bbb;
        border: 1px dashed #e0e0e0;
        border-radius: 8px;
    }
    .noti-empty i { font-size: 48px; display: block; margin-bottom: 16px; }
</style>

<div class="noti-page-header">
    <h2 class="noti-page-title">🔔 Thông báo</h2>
    <span style="font-size:13px; color:#999;"><?= count($notifications) ?> thông báo</span>
</div>

<div class="noti-list">
    <?php if (empty($notifications)): ?>
        <div class="noti-empty">
            <i class="fa-regular fa-bell-slash"></i>
            <p>Bạn chưa có thông báo nào.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $noti): ?>
            <?php
            $style     = getNotiStyle($noti['type']);
            $is_unread = !$noti['is_read'];
            ?>
            <div class="noti-item <?= $is_unread ? 'unread' : '' ?>">
                <div class="noti-icon <?= $style['color'] ?>">
                    <i class="<?= $style['icon'] ?>"></i>
                </div>
                <div class="noti-body">
                    <div class="noti-title"><?= htmlspecialchars($noti['title']) ?></div>
                    <div class="noti-msg"><?= htmlspecialchars($noti['message']) ?></div>
                    <?php if (!empty($noti['related_order_id'])): ?>
                        <a href="dashboard.php?view=chitietdonhang&id=<?= htmlspecialchars($noti['related_order_id']) ?>"
                           class="noti-order-link">
                            Xem đơn hàng #<?= htmlspecialchars($noti['related_order_id']) ?> →
                        </a>
                    <?php endif; ?>
                    <div class="noti-time">
                        <i class="fa-regular fa-clock" style="margin-right:4px;"></i>
                        <?= timeAgo($noti['created_at']) ?>
                        <span style="color:#ddd; margin:0 6px;">·</span>
                        <?= date('d/m/Y H:i', strtotime($noti['created_at'])) ?>
                    </div>
                </div>
                <?php if ($is_unread): ?>
                    <div class="unread-dot" title="Chưa đọc"></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>