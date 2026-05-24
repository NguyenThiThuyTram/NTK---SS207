<?php
require_once '../config/database.php';

// 🔐 KEY THẬT
define('PAYOS_CHECKSUM_KEY', 'b7b836b8064139b2906a3431c5bad44ad104ade4777d83cf7059eb316623ebfe');

// 📥 Nhận payload
$payload = file_get_contents("php://input");
$requestBody = json_decode($payload, true);

// 📝 Log payload
file_put_contents(__DIR__ . "/log.txt", date('[Y-m-d H:i:s] ') . $payload . "\n", FILE_APPEND);

// ❌ Validate
if (!$requestBody || !isset($requestBody['data']) || !isset($requestBody['signature'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

// ===============================
// ✅ VERIFY SIGNATURE (CHUẨN 100%)
// ===============================

$receivedSignature = $requestBody['signature'];

$signData = $requestBody['data'];

// sort
ksort($signData);

// build raw string
$rawData = "";
foreach ($signData as $key => $value) {
    // Không bỏ qua giá trị rỗng/null, PayOS hash nguyên văn
    // Nếu value là array, trong webhook thật thường ko bị lồng sâu, nhưng cẩn thận:
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value);
    }
    $rawData .= $key . "=" . $value . "&";
}
$rawData = rtrim($rawData, "&");

// tạo signature
$expectedSignature = hash_hmac("sha256", $rawData, PAYOS_CHECKSUM_KEY);

// 📝 Log debug
file_put_contents(
    __DIR__ . "/log.txt",
    "RAW: $rawData\nEXPECTED: $expectedSignature\nRECEIVED: $receivedSignature\n",
    FILE_APPEND
);

// ❌ Sai signature
if ($expectedSignature !== $receivedSignature) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

// ===============================
// ✅ XỬ LÝ THANH TOÁN
// ===============================

if (
    isset($requestBody['code']) && $requestBody['code'] === '00' &&
    isset($requestBody['success']) && $requestBody['success'] === true
) {
    $data = $requestBody['data'];
    $payos_order_code = $data['orderCode'] ?? null;

    if ($payos_order_code) {

        $stmt = $conn->prepare("SELECT order_id, payment_status FROM orders WHERE payos_order_code = :poc");
        $stmt->execute(['poc' => $payos_order_code]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {

            if ($order['payment_status'] == 0) {
                $order_id = $order['order_id'];

                $upd = $conn->prepare("
                    UPDATE orders 
                    SET payment_status = 1, order_status = 1 
                    WHERE order_id = :oid
                ");
                $upd->execute(['oid' => $order_id]);

                // Tạo thông báo cho admin
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
                } catch (PDOException $e) {}

                file_put_contents(
                    __DIR__ . "/log.txt",
                    date('[Y-m-d H:i:s] ') . "✅ Updated order {$order['order_id']} to PAID\n",
                    FILE_APPEND
                );

            } else {
                file_put_contents(
                    __DIR__ . "/log.txt",
                    date('[Y-m-d H:i:s] ') . "⚠ Already paid: {$order['order_id']}\n",
                    FILE_APPEND
                );
            }

        } else {
            file_put_contents(
                __DIR__ . "/log.txt",
                date('[Y-m-d H:i:s] ') . "❌ Order not found: $payos_order_code\n",
                FILE_APPEND
            );
        }
    }
}

http_response_code(200);
echo json_encode(['success' => true]);