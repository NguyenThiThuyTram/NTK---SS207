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
        $stmt_info = $conn->prepare("SELECT * FROM Orders WHERE order_id = :oid AND user_id = :uid");
        $stmt_info->execute(['oid' => $order_id, 'uid' => $user_id]);
        $order = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Không tìm thấy đơn hàng");
        }

        if ($order['order_status'] != 1 && $order['order_status'] != 2) {
            throw new Exception("Trạng thái đơn hàng không hợp lệ để xác nhận nhận hàng.");
        }

        $stmt_update = $conn->prepare("UPDATE Orders SET order_status = 3 WHERE order_id = :oid");
        $stmt_update->execute(['oid' => $order_id]);

        echo "<script>
                alert('Xác nhận đã nhận được hàng thành công!');
                window.location.href = '../views/user/dashboard.php?view=donmua&tab=3';
              </script>";
    } catch (Exception $e) {
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
