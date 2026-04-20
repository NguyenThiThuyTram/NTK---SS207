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

        $stmt = $conn->prepare("SELECT order_id, payment_status FROM Orders WHERE payos_order_code = :poc");
        $stmt->execute(['poc' => $payos_order_code]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {

            if ($order['payment_status'] == 0) {

                $upd = $conn->prepare("
                    UPDATE Orders 
                    SET payment_status = 1, order_status = 1 
                    WHERE order_id = :oid
                ");
                $upd->execute(['oid' => $order['order_id']]);

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