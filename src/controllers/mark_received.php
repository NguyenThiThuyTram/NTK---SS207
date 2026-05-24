<?php
/**
 * mark_received.php
 * User bấm "Đã nhận được hàng"
 * Chỉ cho phép khi status = 2 (Đang giao hàng)
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit;
}

$order_id = $_GET['id'] ?? '';
$user_id  = $_SESSION['user_id'];

if (!$order_id) {
    echo "<script>alert('Không tìm thấy mã đơn hàng!'); window.history.back();</script>";
    exit;
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :oid AND user_id = :uid FOR UPDATE");
    $stmt->execute(['oid' => $order_id, 'uid' => $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) throw new Exception("Không tìm thấy đơn hàng.");

    $status = (int)$order['order_status'];

    // Chỉ cho phép khi đang giao hàng (status = 2)
    if ($status !== 2) {
        if ($status === 3) {
            throw new Exception("Đơn hàng này đã được xác nhận nhận hàng trước đó.");
        } elseif ($status === 1) {
            throw new Exception("Đơn hàng chưa được giao, không thể xác nhận nhận hàng.");
        } else {
            throw new Exception("Trạng thái đơn hàng không hợp lệ để xác nhận nhận hàng (hiện tại: {$status}).");
        }
    }

    // Chuyển trạng thái → Hoàn thành (3)
    $conn->prepare("UPDATE orders SET order_status = 3, payment_status = 1 WHERE order_id = :oid")
         ->execute(['oid' => $order_id]);

    // Thưởng điểm Loyalty
    require_once '../includes/loyalty_utils.php';
    $points_earned = floor((float)$order['final_price'] / 10000);
    if ($points_earned > 0) {
        addLoyaltyPoints($conn, $user_id, $points_earned, "hoàn thành đơn hàng #{$order_id}");
    }

    // Tạo thông báo cho User
    try {
        $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_id) VALUES (:uid, 'order_completed', :title, :msg, :oid)")
             ->execute([
                 'uid'   => $user_id,
                 'title' => 'Đơn hàng hoàn thành!',
                 'msg'   => "Đơn hàng #{$order_id} đã hoàn thành. Cảm ơn bạn đã mua sắm tại NTK Fashion! Hãy đánh giá sản phẩm để nhận xu thưởng nhé!",
                 'oid'   => $order_id
             ]);
    } catch (PDOException $e) {}

    $conn->commit();

    echo "<script>alert('Xác nhận đã nhận được hàng thành công!\\nCảm ơn bạn đã mua sắm tại NTK Fashion!'); window.location.href = '../views/user/dashboard.php?view=donmua&tab=3';</script>";

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
}
?>
