<?php
/**
 * MIGRATION SCRIPT - NTK Fashion
 * Chạy 1 lần để cập nhật database với các trạng thái và bảng mới
 * Tất cả đều dùng IF NOT EXISTS / IF EXISTS để an toàn
 */
session_start();
require_once '../config/database.php';

// Bảo vệ: chỉ admin mới chạy được
// (Có thể bỏ comment dưới khi deploy thực tế)
// if (!isset($_SESSION['admin_id'])) { die('Không có quyền!'); }

$results = [];

function runSQL($conn, $sql, $desc) {
    global $results;
    try {
        $conn->exec($sql);
        $results[] = ['ok' => true, 'desc' => $desc];
    } catch (PDOException $e) {
        $results[] = ['ok' => false, 'desc' => $desc, 'error' => $e->getMessage()];
    }
}

// ─────────────────────────────────────────────────────
// 1. THÊM CỘT VÀO BẢNG orders (IF NOT EXISTS)
// ─────────────────────────────────────────────────────

// cancel_reason: lý do hủy đơn
runSQL($conn,
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancel_reason VARCHAR(500) DEFAULT NULL AFTER note",
    "Thêm cột cancel_reason vào orders"
);

// cancel_requested_at: thời điểm user gửi yêu cầu hủy (cho trường hợp B)
runSQL($conn,
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancel_requested_at DATETIME DEFAULT NULL AFTER cancel_reason",
    "Thêm cột cancel_requested_at vào orders"
);

// return_reason: lý do trả hàng (lưu thêm ở orders để tiện query)
runSQL($conn,
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS return_reason VARCHAR(500) DEFAULT NULL AFTER cancel_requested_at",
    "Thêm cột return_reason vào orders"
);

// return_image: ảnh bằng chứng trả hàng
runSQL($conn,
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS return_image VARCHAR(500) DEFAULT NULL AFTER return_reason",
    "Thêm cột return_image vào orders"
);

// return_requested_at: thời điểm user gửi yêu cầu trả hàng
runSQL($conn,
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS return_requested_at DATETIME DEFAULT NULL AFTER return_image",
    "Thêm cột return_requested_at vào orders"
);

// delivery_failed_at: thời điểm giao hàng thất bại
runSQL($conn,
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_failed_at DATETIME DEFAULT NULL AFTER return_requested_at",
    "Thêm cột delivery_failed_at vào orders"
);

// admin_note: ghi chú từ admin (lý do từ chối, v.v.)
runSQL($conn,
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS admin_note VARCHAR(500) DEFAULT NULL AFTER delivery_failed_at",
    "Thêm cột admin_note vào orders"
);

// ─────────────────────────────────────────────────────
// 2. TẠO BẢNG notifications (IF NOT EXISTS)
// ─────────────────────────────────────────────────────
// Mapping loại thông báo:
//   order_placed      = Đã đặt hàng thành công
//   order_confirmed   = Đã xác nhận thanh toán
//   order_shipping    = Đang giao hàng
//   order_completed   = Đã hoàn thành
//   order_cancelled   = Đã hủy
//   return_request    = Yêu cầu trả hàng (gửi cho admin)
//   return_approved   = Yêu cầu trả hàng được duyệt
//   return_rejected   = Yêu cầu trả hàng bị từ chối
//   return_received   = Đã nhận hàng hoàn trả
//   refund_done       = Đã hoàn tiền
//   delivery_failed   = Giao hàng thất bại
//   cancel_approved   = Admin duyệt hủy
//   cancel_rejected   = Admin từ chối hủy

runSQL($conn,
    "CREATE TABLE IF NOT EXISTS `notifications` (
        `noti_id`         INT(11) NOT NULL AUTO_INCREMENT,
        `user_id`         CHAR(5) DEFAULT NULL,
        `type`            VARCHAR(50) DEFAULT 'system',
        `title`           VARCHAR(200) NOT NULL,
        `message`         VARCHAR(500) NOT NULL,
        `related_order_id` CHAR(5) DEFAULT NULL,
        `is_read`         TINYINT(1) DEFAULT 0,
        `created_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`noti_id`),
        KEY `idx_noti_user` (`user_id`),
        KEY `idx_noti_order` (`related_order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    "Tạo bảng notifications"
);

// ─────────────────────────────────────────────────────
// 3. CẬP NHẬT order_returns - thêm cột admin_note nếu chưa có
// ─────────────────────────────────────────────────────
runSQL($conn,
    "ALTER TABLE order_returns ADD COLUMN IF NOT EXISTS admin_note VARCHAR(500) DEFAULT NULL AFTER status",
    "Thêm cột admin_note vào order_returns"
);

// ─────────────────────────────────────────────────────
// 4. CẬP NHẬT DỮ LIỆU HIỆN CÓ - Đồng nhất hóa order_status
// ─────────────────────────────────────────────────────
// Luồng cũ: 1 = Đang xử lý (COD), 2 = Đang giao
// Luồng mới: 1 = Chờ lấy hàng, 2 = Đang giao hàng
// → Không cần đổi số, chỉ đổi tên hiển thị trong code PHP

// Hiện tại status=0 có 2 nghĩa: online chưa TT. Giữ nguyên.
// Status=1 cũ = "Đang xử lý" → đổi tên thành "Chờ lấy hàng" (không đổi số)
// → OK, không cần UPDATE database

$results[] = ['ok' => true, 'desc' => 'Không cần đổi số trạng thái - chỉ đổi nhãn hiển thị'];

// ─────────────────────────────────────────────────────
// OUTPUT
// ─────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Database Migration - NTK Fashion</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #2f1c00; }
        .ok   { background: #eafaf1; border-left: 4px solid #27ae60; padding: 10px 15px; margin: 8px 0; border-radius: 4px; }
        .fail { background: #fdf0ef; border-left: 4px solid #c0392b; padding: 10px 15px; margin: 8px 0; border-radius: 4px; }
        .done { background: #2f1c00; color: #fff; padding: 15px 20px; border-radius: 8px; margin-top: 20px; font-size: 18px; text-align: center; }
    </style>
</head>
<body>
<h1>🔧 NTK Fashion - Database Migration</h1>
<p style="color:#888;">Chạy lúc: <?= date('Y-m-d H:i:s') ?></p>

<?php foreach ($results as $r): ?>
    <div class="<?= $r['ok'] ? 'ok' : 'fail' ?>">
        <?= $r['ok'] ? '✅' : '❌' ?> <strong><?= htmlspecialchars($r['desc']) ?></strong>
        <?php if (!$r['ok'] && isset($r['error'])): ?>
            <br><small style="color:#c0392b;"><?= htmlspecialchars($r['error']) ?></small>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php
$all_ok = !in_array(false, array_column($results, 'ok'));
?>
<div class="done">
    <?php if ($all_ok): ?>
        ✅ Migration hoàn thành! Tất cả thay đổi đã được áp dụng.
    <?php else: ?>
        ⚠️ Migration hoàn thành với một số lỗi (xem chi tiết bên trên).
    <?php endif; ?>
</div>

<p style="color:#888; margin-top:20px; font-size:13px;">
    ⚠️ Sau khi chạy xong, bạn có thể xóa hoặc bảo vệ file này.
</p>
</body>
</html>
