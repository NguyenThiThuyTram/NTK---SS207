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

        $stmt_info = $conn->prepare("SELECT * FROM Orders WHERE order_id = :oid AND user_id = :uid FOR UPDATE");
        $stmt_info->execute(['oid' => $order_id, 'uid' => $user_id]);
        $order = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Không tìm thấy đơn hàng");
        }
        if ($order['order_status'] != 3) {
            throw new Exception("Đơn hàng chưa hoàn thành, không thể trả hàng.");
        }

        // Tính tiền cần hoàn lại (Cộng tiền đã trả nếu trả online + tiền ví đã trừ)
        // Nếu COD nhưng order_status = 3 (Hoàn thành) thì nghĩa là đã thanh toán bằng tiền mặt
        // Tuy nhiên hệ thống chỉ hoàn trả vào ví điện tử 
        $refund_amount = 0;
        
        // Nếu đơn hàng COD hoàn thành, tức là đã giao và thu tiền -> Hoàn tiền vào ví
        // Nếu chuyển khoản (payment_status = 1) -> Hoàn tiền vào ví
        $refund_amount += floatval($order['final_price']); // Trả lại toàn bộ tiền khách đã thanh toán cuối cùng
        $refund_amount += floatval($order['wallet_used_amount']); // Trả lại số xu đã dùng

        $msg = '';
        if ($refund_amount > 0) {
            $conn->prepare("UPDATE Users SET wallet_balance = wallet_balance + :amt WHERE user_id = :uid")
                 ->execute(['amt' => $refund_amount, 'uid' => $user_id]);
            $conn->prepare("INSERT INTO Wallet_Transactions (user_id, amount, transaction_type, description, related_order_id) VALUES (:uid, :amt, 1, 'Hoàn tiền do trả hàng (Refund)', :oid)")
                 ->execute(['uid' => $user_id, 'amt' => $refund_amount, 'oid' => $order_id]);
        }
        
        // Đổi trạng thái đơn thành "Trả hàng/Hoàn tiền" (Status = 5)
        $conn->prepare("UPDATE Orders SET order_status = 5 WHERE order_id = :oid")->execute(['oid' => $order_id]);
        $msg = 'Đã yêu cầu trả hàng và hoàn lại ' . number_format($refund_amount, 0, ',', '.') . ' VNĐ vào ví!';

        $conn->commit();

        echo "<script>
                alert('$msg');
                window.location.href = '../views/user/dashboard.php?view=donmua&tab=5';
              </script>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>
                alert('Lỗi: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
    }
} else {
    echo "<script>
            alert('Không tìm thấy mã đơn hàng!');
            window.history.back();
          </script>";
}
