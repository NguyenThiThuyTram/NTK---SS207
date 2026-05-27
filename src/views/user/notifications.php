<?php
// notifications.php - Included trong dashboard.php
$user_id = $_SESSION['user_id'];

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
    .noti-item .noti-title {
        font-size: 14.5px;
        font-weight: 500;
        color: #555;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .noti-item.unread .noti-title {
        font-weight: bold;
        color: #111;
    }
    .noti-item .noti-msg { 
        font-size: 13.5px; 
        color: #666; 
        line-height: 1.5; 
        font-weight: normal;
    }
    .noti-item.unread .noti-msg {
        font-weight: 600;
        color: #222;
    }
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

<div class="noti-page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 class="noti-page-title">🔔 Thông báo</h2>
        <span style="font-size:13px; color:#999;" id="user-noti-count"><?= count($notifications) ?> thông báo</span>
    </div>
    <?php
    $unread_count = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
    if ($unread_count > 0):
    ?>
    <button class="btn-user-mark-all-read" onclick="userMarkAllRead(event)" style="background: #fdf5ea; color: #a6825c; border: 1px solid #a6825c; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 12.5px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
        <i class="fa-solid fa-check-double"></i> Đánh dấu đã đọc tất cả
    </button>
    <?php endif; ?>
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
            <div class="noti-item <?= $is_unread ? 'unread' : '' ?>" data-id="<?= htmlspecialchars($noti['noti_id']) ?>">
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

<script>
function userMarkAllRead(e) {
    if (e) e.preventDefault();
    
    fetch('<?= $_BASE ?>/api/user_mark_all_read.php', {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const unreadItems = document.querySelectorAll('.noti-item.unread');
            unreadItems.forEach(item => {
                item.classList.remove('unread');
                const dot = item.querySelector('.unread-dot');
                if (dot) dot.remove();
            });
            
            const badge = document.getElementById('badge-notif');
            if (badge) {
                badge.innerText = '0';
                badge.style.display = 'none';
            }
            
            const btn = document.querySelector('.btn-user-mark-all-read');
            if (btn) btn.remove();
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || 'Không thể đánh dấu đã đọc.'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi kết nối mạng, vui lòng thử lại.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const list = document.querySelector('.noti-list');
    if (list) {
        list.addEventListener('click', function(e) {
            const item = e.target.closest('.noti-item.unread');
            if (!item) return;
            
            const notiId = item.getAttribute('data-id');
            if (!notiId || notiId === 'null') return;
            
            const formData = new FormData();
            formData.append('noti_id', notiId);
            
            fetch('<?= $_BASE ?>/api/user_mark_read.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    item.classList.remove('unread');
                    const dot = item.querySelector('.unread-dot');
                    if (dot) dot.remove();
                    
                    const badge = document.getElementById('badge-notif');
                    if (badge) {
                        let currentCount = parseInt(badge.innerText || '0');
                        if (currentCount > 1) {
                            badge.innerText = currentCount - 1;
                        } else {
                            badge.innerText = '0';
                            badge.style.display = 'none';
                        }
                    }
                    
                    const remainingUnread = document.querySelectorAll('.noti-item.unread').length;
                    if (remainingUnread === 0) {
                        const btn = document.querySelector('.btn-user-mark-all-read');
                        if (btn) btn.remove();
                    }
                }
            })
            .catch(err => console.error(err));
        });
    }
});
</script>