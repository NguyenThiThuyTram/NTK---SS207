<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit;
}

$order_id = $_GET['id'] ?? '';
$user_id = $_SESSION['user_id'];

if ($order_id) {
    try {
        $conn->beginTransaction();

        // 1. Lấy thông tin đơn hàng
        $stmt_info = $conn->prepare("SELECT * FROM Orders WHERE order_id = :oid AND user_id = :uid FOR UPDATE");
        $stmt_info->execute(['oid' => $order_id, 'uid' => $user_id]);
        $order = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Không tìm thấy đơn hàng");
        }
        if ($order['order_status'] >= 2 && $order['order_status'] != 4) {
            throw new Exception("Đơn hàng đã được giao hoặc hoàn thành, không thể hủy.");
        }
        if ($order['order_status'] == 4) {
            throw new Exception("Đơn hàng này đã bị hủy từ trước.");
        }

        // 2. Tính tiền cần hoàn lại (Cộng tiền đã trả nếu trả online + tiền ví đã trừ)
        $refund_amount = 0;
        if ($order['payment_status'] == 1) { 
            $refund_amount += floatval($order['final_price']);
        }
        $refund_amount += floatval($order['wallet_used_amount']);

        $msg = '';
        if ($refund_amount > 0 || $order['order_status'] == 1) {
            // Đã trả tiền, thanh toán cọc, hoặc đang xử lý -> Chuyển thành Đã hủy (status = 4)
            if ($refund_amount > 0) {
                $conn->prepare("UPDATE Users SET wallet_balance = wallet_balance + :amt WHERE user_id = :uid")
                     ->execute(['amt' => $refund_amount, 'uid' => $user_id]);
                $conn->prepare("INSERT INTO Wallet_Transactions (user_id, amount, transaction_type, description, related_order_id) VALUES (:uid, :amt, 1, 'Hoàn tiền do hủy đơn hàng', :oid)")
                     ->execute(['uid' => $user_id, 'amt' => $refund_amount, 'oid' => $order_id]);
            }
            $conn->prepare("UPDATE Orders SET order_status = 4 WHERE order_id = :oid")->execute(['oid' => $order_id]);
            $msg = 'Đã hủy đơn hàng' . ($refund_amount > 0 ? " và hoàn lại " . number_format($refund_amount,0,',','.') . " VNĐ vào ví" : "") . "!";
        } else {
            // Chưa thanh toán đồng nào và Chờ TT -> Xóa hẳn
            $conn->prepare("DELETE FROM Order_Details WHERE order_id = :oid")->execute(['oid' => $order_id]);
            $conn->prepare("DELETE FROM Orders WHERE order_id = :oid AND user_id = :uid")->execute(['oid' => $order_id, 'uid' => $user_id]);
            $msg = 'Đã hủy và xóa đơn hàng thành công!';
        }

        $conn->commit();

        echo "<script>
                alert('$msg');
                window.location.href = '../views/user/dashboard.php?view=donmua&tab=all';
              </script>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>
                alert('Lỗi khi hủy đơn hàng: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
    }
} else {
    echo "<script>
            alert('Không tìm thấy mã đơn hàng!');
            window.history.back();
          </script>";
}
