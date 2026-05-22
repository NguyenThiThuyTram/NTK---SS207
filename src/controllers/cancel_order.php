<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit;
}

$order_id = $_GET['id'] ?? '';
$user_id  = $_SESSION['user_id'];
$reason   = $_POST['reason'] ?? ($_GET['reason'] ?? 'Khách hàng tự hủy');

if (!$order_id) {
    echo "<script>alert('Không tìm thấy mã đơn hàng!'); window.history.back();</script>";
    exit;
}

try {
    $conn->beginTransaction();

    // Lấy thông tin đơn hàng (khóa hàng để tránh race condition)
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :oid AND user_id = :uid FOR UPDATE");
    $stmt->execute(['oid' => $order_id, 'uid' => $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) throw new Exception("Không tìm thấy đơn hàng.");

    $status = (int)$order['order_status'];

    // ─── PHÂN LOẠI THEO TRẠNG THÁI ───────────────────────────────────────────
    //
    // Trường hợp A: status = 0 (Chờ thanh toán) hoặc 1 (Chờ lấy hàng)
    //   → Hủy NGAY LẬP TỨC, hoàn tiền nếu đã trả
    //
    // Trường hợp B: status = 8 (Đã được tạo yêu cầu hủy, chờ Admin duyệt)
    //   → Chỉ Admin mới xử lý, user không tự hủy thêm
    //
    // Trường hợp C: status = 2 (Đang giao hàng) hoặc cao hơn
    //   → Không cho hủy
    // ─────────────────────────────────────────────────────────────────────────

    if ($status === 4) {
        throw new Exception("Đơn hàng này đã được hủy trước đó.");
    }

    if ($status >= 2 && $status !== 8) {
        throw new Exception("Đơn hàng đang giao hoặc đã hoàn thành, không thể hủy. Nếu hàng đã giao, vui lòng dùng chức năng Trả hàng.");
    }

    if ($status === 8) {
        throw new Exception("Yêu cầu hủy đơn của bạn đang được Admin xem xét. Vui lòng đợi phản hồi.");
    }

    // ── Trường hợp A: Hủy ngay lập tức ───────────────────────────────────────
    if ($status === 0 || $status === 1) {

        // Hoàn tiền vào ví nếu user đã thanh toán online hoặc dùng ví
        $refund_amount = 0;
        if ($order['payment_status'] == 1) {
            // Đã thanh toán (online hoặc đủ điều kiện)
            $refund_amount += floatval($order['final_price']);
        }
        // Hoàn lại phần ví đã dùng (nếu có, và chưa tính trong final_price)
        if (floatval($order['wallet_used_amount']) > 0 && $order['payment_status'] != 1) {
            $refund_amount += floatval($order['wallet_used_amount']);
        }

        // Hoàn tiền vào ví
        if ($refund_amount > 0) {
            $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + :amt WHERE user_id = :uid")
                 ->execute(['amt' => $refund_amount, 'uid' => $user_id]);
            $conn->prepare("INSERT INTO wallet_transactions (user_id, amount, transaction_type, description, related_order_id) VALUES (:uid, :amt, 1, :desc, :oid)")
                 ->execute(['uid' => $user_id, 'amt' => $refund_amount, 'desc' => "Hoàn tiền do hủy đơn hàng #{$order_id}", 'oid' => $order_id]);
        }

        // Cập nhật trạng thái đơn → Đã hủy (4)
        $conn->prepare("UPDATE orders SET order_status = 4, cancel_reason = :reason WHERE order_id = :oid")
             ->execute(['reason' => $reason, 'oid' => $order_id]);

        // Tạo thông báo cho user
        $msg_noti = "Đơn hàng #{$order_id} đã được hủy thành công.";
        if ($refund_amount > 0) {
            $msg_noti .= " Số tiền " . number_format($refund_amount, 0, ',', '.') . " VNĐ sẽ được hoàn vào ví của bạn trong 1-3 ngày làm việc.";
        }
        try {
            $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_id) VALUES (:uid, 'order_cancelled', :title, :msg, :oid)")
                 ->execute(['uid' => $user_id, 'title' => 'Đơn hàng đã hủy', 'msg' => $msg_noti, 'oid' => $order_id]);
        } catch (PDOException $e) {}

        $conn->commit();

        $alert_msg = "Đơn hàng #{$order_id} đã được hủy thành công!";
        if ($refund_amount > 0) {
            $alert_msg .= "\nHoàn " . number_format($refund_amount, 0, ',', '.') . " VNĐ vào ví.";
        }
        echo "<script>alert(" . json_encode($alert_msg) . "); window.location.href = '../views/user/dashboard.php?view=donmua&tab=4';</script>";
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
}
?>
