<?php
/**
 * admin_order_action.php
 * Xử lý tất cả hành động Admin đối với đơn hàng:
 * - confirm_payment   : Xác nhận thanh toán Online → status=1
 * - prepare_shipping  : Chuẩn bị hàng → status=2 (Đang giao hàng)
 * - delivery_failed   : Giao hàng thất bại → status=9
 * - return_to_warehouse: Nhận hàng hoàn về kho → status=10 hoặc 4
 * - approve_return    : Duyệt yêu cầu trả hàng → status=6
 * - reject_return     : Từ chối trả hàng → status=3 hoặc 2
 * - confirm_refund    : Xác nhận hoàn tiền → status=7, hoàn tiền vào ví
 * - approve_cancel    : Đồng ý hủy đơn (case B) → status=4
 * - reject_cancel     : Từ chối hủy (case B) → giữ status hiện tại
 */
session_start();
require_once __DIR__ . '/../config/database.php';

// Kiểm tra admin session
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 0) != 1) {
    header("Location: ../views/login.php");
    exit;
}

$order_id   = $_POST['order_id'] ?? $_GET['id'] ?? '';
$action     = $_POST['action'] ?? $_GET['action'] ?? '';
$admin_note = trim($_POST['admin_note'] ?? '');
$tracking   = trim($_POST['tracking_number'] ?? '');

if (!$order_id || !$action) {
    echo "<script>alert('Thiếu thông tin!'); window.history.back();</script>";
    exit;
}

// ─── Hàm tạo thông báo cho user ────────────────────────────────
function createUserNotification($conn, $user_id, $type, $title, $message, $order_id) {
    try {
        $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_id) VALUES (:uid, :type, :title, :msg, :oid)")
             ->execute(['uid' => $user_id, 'type' => $type, 'title' => $title, 'msg' => $message, 'oid' => $order_id]);
    } catch (PDOException $e) {
        // Không throw để không ảnh hưởng đến hành động chính
    }
}

try {
    $conn->beginTransaction();

    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("SELECT o.*, u.user_id as uid FROM orders o LEFT JOIN users u ON o.user_id = u.user_id WHERE o.order_id = :oid FOR UPDATE");
    $stmt->execute(['oid' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) throw new Exception("Không tìm thấy đơn hàng #{$order_id}");

    $status  = (int)$order['order_status'];
    $user_id = $order['uid'];
    $success_msg = '';

    switch ($action) {

        // ────────────────────────────────────────────────────────
        // Admin xác nhận thanh toán online (webhook PayOS đã nhận)
        // status: 0 → 1 (Chờ lấy hàng)
        // ────────────────────────────────────────────────────────
        case 'confirm_payment':
            if ($status !== 0) throw new Exception("Đơn hàng không ở trạng thái chờ thanh toán.");
            $conn->prepare("UPDATE orders SET order_status = 1, payment_status = 1 WHERE order_id = :oid")
                 ->execute(['oid' => $order_id]);
            createUserNotification($conn, $user_id, 'order_confirmed',
                'Thanh toán xác nhận thành công',
                "Đơn hàng #{$order_id} của bạn đã được xác nhận thanh toán. Chúng tôi sẽ chuẩn bị và giao hàng sớm nhất!",
                $order_id);
            $success_msg = "Đã xác nhận thanh toán cho đơn #{$order_id}";
            break;

        // ────────────────────────────────────────────────────────
        // Admin chuẩn bị hàng và bàn giao ĐVVC
        // status: 1 → 2 (Đang giao hàng)
        // ────────────────────────────────────────────────────────
        case 'prepare_shipping':
            if ($status !== 1) throw new Exception("Đơn hàng phải ở trạng thái Chờ lấy hàng.");
            $update_sql = "UPDATE orders SET order_status = 2";
            $update_params = ['oid' => $order_id];
            if (!empty($tracking)) {
                $update_sql .= ", tracking_number = :track";
                $update_params['track'] = $tracking;
            }
            $update_sql .= " WHERE order_id = :oid";
            $conn->prepare($update_sql)->execute($update_params);

            $track_info = !empty($tracking) ? " Mã vận đơn: {$tracking}." : "";
            createUserNotification($conn, $user_id, 'order_shipping',
                'Đơn hàng đang được giao',
                "Đơn hàng #{$order_id} đã được bàn giao cho đơn vị vận chuyển.{$track_info} Bạn sẽ nhận hàng trong 1-3 ngày tới.",
                $order_id);
            $success_msg = "Đã cập nhật đơn #{$order_id} sang trạng thái Đang giao hàng";
            break;

        // ────────────────────────────────────────────────────────
        // Giao hàng thất bại (shipper gọi 3 lần không được)
        // status: 2 → 9 (Giao hàng thất bại)
        // ────────────────────────────────────────────────────────
        case 'delivery_failed':
            if ($status !== 2) throw new Exception("Đơn hàng phải đang ở trạng thái Đang giao hàng.");
            $conn->prepare("UPDATE orders SET order_status = 9, delivery_failed_at = NOW(), admin_note = :note WHERE order_id = :oid")
                 ->execute(['note' => $admin_note ?: 'Không liên lạc được với khách hàng', 'oid' => $order_id]);
            createUserNotification($conn, $user_id, 'delivery_failed',
                'Giao hàng không thành công',
                "Đơn hàng #{$order_id} giao không thành công do " . ($admin_note ?: 'không liên lạc được') . ". Đơn hàng đang được hoàn về kho.",
                $order_id);
            $success_msg = "Đã đánh dấu giao thất bại cho đơn #{$order_id}";
            break;

        // ────────────────────────────────────────────────────────
        // Admin nhận hàng hoàn về kho (sau giao thất bại)
        // status: 9 → 10 (Đang hoàn về kho) → Admin xác nhận → Kết thúc
        // ────────────────────────────────────────────────────────
        case 'return_to_warehouse':
            if ($status !== 9 && $status !== 10) throw new Exception("Đơn hàng phải ở trạng thái Giao thất bại hoặc Đang hoàn về kho.");
            // Admin xác nhận đã nhận lại hàng
            $conn->prepare("UPDATE orders SET order_status = 10 WHERE order_id = :oid")
                 ->execute(['oid' => $order_id]);
            // COD → không hoàn tiền. Online → hoàn tiền sản phẩm (trừ phí ship)
            if ($order['payment_status'] == 1 && $order['payment_method'] == 2) {
                // Online đã trả → hoàn tiền sản phẩm (final_price - shipping_fee)
                $refund = floatval($order['final_price']) - floatval($order['shipping_fee']);
                if ($refund > 0) {
                    $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + :amt WHERE user_id = :uid")
                         ->execute(['amt' => $refund, 'uid' => $user_id]);
                    $conn->prepare("INSERT INTO wallet_transactions (user_id, amount, transaction_type, description, related_order_id) VALUES (:uid, :amt, 1, :desc, :oid)")
                         ->execute(['uid' => $user_id, 'amt' => $refund, 'desc' => "Hoàn tiền giao hàng thất bại đơn #{$order_id} (trừ phí ship)", 'oid' => $order_id]);
                    createUserNotification($conn, $user_id, 'refund_done',
                        'Hoàn tiền giao hàng thất bại',
                        "Đơn hàng #{$order_id} giao không thành công. Số tiền " . number_format($refund, 0, ',', '.') . " VNĐ (đã trừ phí ship) đã được hoàn vào ví của bạn.",
                        $order_id);
                }
            }
            $success_msg = "Đã xác nhận nhận hàng hoàn về kho cho đơn #{$order_id}";
            break;

        // ────────────────────────────────────────────────────────
        // Admin duyệt yêu cầu trả hàng
        // status: 5 → 6 (Đang hoàn trả hàng)
        // ────────────────────────────────────────────────────────
        case 'approve_return':
            if ($status !== 5) throw new Exception("Đơn hàng không có yêu cầu trả hàng đang chờ.");
            $conn->prepare("UPDATE orders SET order_status = 6, admin_note = :note WHERE order_id = :oid")
                 ->execute(['note' => $admin_note, 'oid' => $order_id]);
            $conn->prepare("UPDATE order_returns SET status = 1, admin_note = :note WHERE order_id = :oid ORDER BY created_at DESC LIMIT 1")
                 ->execute(['note' => $admin_note, 'oid' => $order_id]);
            createUserNotification($conn, $user_id, 'return_approved',
                'Yêu cầu trả hàng được duyệt',
                "Yêu cầu trả hàng cho đơn #{$order_id} đã được chấp nhận. Vui lòng đóng gói và gửi hàng về cho chúng tôi trong vòng 3 ngày. Tiền sẽ được hoàn sau khi chúng tôi nhận và kiểm tra hàng.",
                $order_id);
            $success_msg = "Đã duyệt yêu cầu trả hàng cho đơn #{$order_id}";
            break;

        // ────────────────────────────────────────────────────────
        // Admin từ chối yêu cầu trả hàng
        // status: 5 → 3 (Hoàn thành) hoặc 2 (Đang giao tùy context)
        // ────────────────────────────────────────────────────────
        case 'reject_return':
            if ($status !== 5) throw new Exception("Đơn hàng không có yêu cầu trả hàng đang chờ.");
            if (empty($admin_note)) throw new Exception("Vui lòng nhập lý do từ chối.");
            // Hoàn về trạng thái trước (Hoàn thành = 3)
            $conn->prepare("UPDATE orders SET order_status = 3, admin_note = :note WHERE order_id = :oid")
                 ->execute(['note' => $admin_note, 'oid' => $order_id]);
            $conn->prepare("UPDATE order_returns SET status = 2, admin_note = :note WHERE order_id = :oid ORDER BY created_at DESC LIMIT 1")
                 ->execute(['note' => $admin_note, 'oid' => $order_id]);
            createUserNotification($conn, $user_id, 'return_rejected',
                'Yêu cầu trả hàng bị từ chối',
                "Yêu cầu trả hàng cho đơn #{$order_id} bị từ chối. Lý do: {$admin_note}",
                $order_id);
            $success_msg = "Đã từ chối yêu cầu trả hàng cho đơn #{$order_id}";
            break;

        // ────────────────────────────────────────────────────────
        // Admin xác nhận nhận hàng hoàn trả & hoàn tiền
        // status: 6 → 7 (Đã hoàn tiền)
        // ────────────────────────────────────────────────────────
        case 'confirm_refund':
            if ($status !== 6) throw new Exception("Đơn hàng phải ở trạng thái Đang hoàn trả hàng.");
            $conn->prepare("UPDATE orders SET order_status = 7 WHERE order_id = :oid")
                 ->execute(['oid' => $order_id]);

            // Hoàn tiền vào ví user
            $refund_amount = floatval($order['final_price']);
            if ($refund_amount > 0) {
                $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + :amt WHERE user_id = :uid")
                     ->execute(['amt' => $refund_amount, 'uid' => $user_id]);
                $conn->prepare("INSERT INTO wallet_transactions (user_id, amount, transaction_type, description, related_order_id) VALUES (:uid, :amt, 1, :desc, :oid)")
                     ->execute(['uid' => $user_id, 'amt' => $refund_amount, 'desc' => "Hoàn tiền trả hàng đơn #{$order_id}", 'oid' => $order_id]);
            }
            createUserNotification($conn, $user_id, 'refund_done',
                'Hoàn tiền thành công!',
                "Hoàn tiền thành công! Số tiền " . number_format($refund_amount, 0, ',', '.') . " VNĐ từ đơn hàng #{$order_id} đã được hoàn về ví của bạn.",
                $order_id);
            $success_msg = "Đã xác nhận hoàn tiền " . number_format($refund_amount, 0, ',', '.') . " VNĐ cho đơn #{$order_id}";
            break;

        // ────────────────────────────────────────────────────────
        // Admin đồng ý hủy đơn (case B - đã chuẩn bị hàng)
        // status: 8 → 4 (Đã hủy)
        // ────────────────────────────────────────────────────────
        case 'approve_cancel':
            if ($status !== 8) throw new Exception("Không có yêu cầu hủy đơn nào đang chờ.");
            // Hoàn tiền nếu đã thanh toán
            $refund = 0;
            if ($order['payment_status'] == 1) $refund = floatval($order['final_price']);
            if ($refund > 0) {
                $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + :amt WHERE user_id = :uid")
                     ->execute(['amt' => $refund, 'uid' => $user_id]);
                $conn->prepare("INSERT INTO wallet_transactions (user_id, amount, transaction_type, description, related_order_id) VALUES (:uid, :amt, 1, :desc, :oid)")
                     ->execute(['uid' => $user_id, 'amt' => $refund, 'desc' => "Hoàn tiền hủy đơn #{$order_id}", 'oid' => $order_id]);
            }
            $conn->prepare("UPDATE orders SET order_status = 4 WHERE order_id = :oid")->execute(['oid' => $order_id]);
            $msg_cancel = "Yêu cầu hủy đơn hàng #{$order_id} đã được chấp nhận. Đơn hàng đã được hủy.";
            if ($refund > 0) $msg_cancel .= " Số tiền " . number_format($refund, 0, ',', '.') . " VNĐ sẽ hoàn vào ví trong 1-3 ngày.";
            createUserNotification($conn, $user_id, 'cancel_approved', 'Yêu cầu hủy đơn được duyệt', $msg_cancel, $order_id);
            $success_msg = "Đã đồng ý hủy đơn #{$order_id}";
            break;

        // ────────────────────────────────────────────────────────
        // Admin từ chối hủy (case B - hàng đã giao cho ĐVVC)
        // status: 8 → 2 (Đang giao hàng)
        // ────────────────────────────────────────────────────────
        case 'reject_cancel':
            if ($status !== 8) throw new Exception("Không có yêu cầu hủy đơn nào đang chờ.");
            if (empty($admin_note)) throw new Exception("Vui lòng nhập lý do từ chối.");
            $conn->prepare("UPDATE orders SET order_status = 2, admin_note = :note WHERE order_id = :oid")
                 ->execute(['note' => $admin_note, 'oid' => $order_id]);
            createUserNotification($conn, $user_id, 'cancel_rejected',
                'Yêu cầu hủy đơn bị từ chối',
                "Yêu cầu hủy đơn hàng #{$order_id} bị từ chối. Lý do: {$admin_note}. Đơn hàng vẫn đang được giao đến bạn.",
                $order_id);
            $success_msg = "Đã từ chối hủy đơn #{$order_id}, chuyển sang Đang giao hàng";
            break;

        // ────────────────────────────────────────────────────────
        // Admin chủ động hủy đơn
        // status: 0, 1, 2, 8 → 4 (Đã hủy)
        // ────────────────────────────────────────────────────────
        case 'admin_cancel':
            if (in_array($status, [3, 4, 7])) throw new Exception("Không thể hủy đơn hàng ở trạng thái này.");
            // Hoàn tiền nếu đã thanh toán
            $refund = 0;
            if ($order['payment_status'] == 1) $refund = floatval($order['final_price']);
            if ($refund > 0) {
                $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + :amt WHERE user_id = :uid")
                     ->execute(['amt' => $refund, 'uid' => $user_id]);
                $conn->prepare("INSERT INTO wallet_transactions (user_id, amount, transaction_type, description, related_order_id) VALUES (:uid, :amt, 1, :desc, :oid)")
                     ->execute(['uid' => $user_id, 'amt' => $refund, 'desc' => "Hoàn tiền do Admin hủy đơn #{$order_id}", 'oid' => $order_id]);
            }
            $conn->prepare("UPDATE orders SET order_status = 4, cancel_reason = :reason WHERE order_id = :oid")
                 ->execute(['reason' => $admin_note ?: 'Quản trị viên hủy', 'oid' => $order_id]);
            $msg_cancel = "Đơn hàng #{$order_id} đã bị hủy bởi quản trị viên. Lý do: " . ($admin_note ?: 'Không có');
            if ($refund > 0) $msg_cancel .= ". Số tiền " . number_format($refund, 0, ',', '.') . " VNĐ sẽ hoàn vào ví.";
            createUserNotification($conn, $user_id, 'order_cancelled', 'Đơn hàng bị hủy', $msg_cancel, $order_id);
            $success_msg = "Đã hủy đơn #{$order_id}";
            break;

        default:
            throw new Exception("Hành động không hợp lệ: {$action}");
    }

    $conn->commit();

    // Redirect về trang chi tiết đơn hàng
    $redirect = "order_detail.php?id=" . urlencode($order_id) . "&success=" . urlencode($success_msg);
    header("Location: $redirect");
    exit;

} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $err_msg = $e->getMessage();
    $redirect = "order_detail.php?id=" . urlencode($order_id) . "&error=" . urlencode($err_msg);
    header("Location: $redirect");
    exit;
}
?>
