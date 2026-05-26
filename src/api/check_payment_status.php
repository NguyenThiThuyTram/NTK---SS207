<?php
require_once '../config/database.php';

$order_id = $_GET['id'] ?? '';

if (!$order_id) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT payment_status, payos_order_code FROM orders WHERE order_id = :oid");
$stmt->execute(['oid' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if ($order && $order['payment_status'] == 1) {
    echo json_encode(['success' => true, 'paid' => true]);
    exit;
}

// Nếu chưa thanh toán trong DB, kiểm tra PayOS API trực tiếp phòng trường hợp webhook bị trễ/không chạy do test local
if ($order && !empty($order['payos_order_code'])) {
    $PAYOS_CLIENT_ID = "d9c795f0-0eea-438e-9f92-3a2902c7c99c";
    $PAYOS_API_KEY = "610ff3aa-21e6-4713-ba23-d9b74e545129";
    
    $ch = curl_init("https://api-merchant.payos.vn/v2/payment-requests/" . $order['payos_order_code']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "x-client-id: $PAYOS_CLIENT_ID",
            "x-api-key: $PAYOS_API_KEY"
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($res['data']['status']) && $res['data']['status'] === 'PAID') {
        $conn->prepare("UPDATE orders SET payment_status = 1, order_status = 1 WHERE order_id = :oid")->execute(['oid' => $order_id]);
        
        // Gửi thông báo cho admin (nếu có thể)
        try {
            $stmt_admin = $conn->prepare("SELECT user_id FROM users WHERE role = 1 LIMIT 1");
            $stmt_admin->execute();
            $admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
            if ($admin) {
                $check = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE related_order_id = :oid AND type = 'payment_success'");
                $check->execute(['oid' => $order_id]);
                if ($check->fetchColumn() == 0) {
                    $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_id) VALUES (:uid, 'payment_success', :title, :msg, :oid)")
                         ->execute([
                             'uid'   => $admin['user_id'],
                             'title' => 'Khách đã thanh toán: #' . $order_id,
                             'msg'   => "Đơn hàng #{$order_id} đã được thanh toán thành công qua PayOS.",
                             'oid'   => $order_id
                         ]);
                }
            }
        } catch (Exception $e) {}

        echo json_encode(['success' => true, 'paid' => true]);
        exit;
    }
}

echo json_encode(['success' => true, 'paid' => false]);
?>
