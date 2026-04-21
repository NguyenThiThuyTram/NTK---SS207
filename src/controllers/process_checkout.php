<?php
session_start();
require_once '../config/database.php'; // Đảm bảo đường dẫn này đúng với project của bạn

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: views/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. NHẬN DỮ LIỆU TỪ FORM ────────────────────────────────
    $addr_choice = $_POST['addr_choice'] ?? 'manual';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';

    // Tách phương thức
    if ($addr_choice !== 'manual') {
        // Dùng địa chỉ đã lưu
        $fullname = trim($_POST['recipient_name'] ?? '');
        $phone = preg_replace('/\s+/', '', $_POST['addr_phone'] ?? '');
        // $address là street, $city là province
        $final_address = trim(($_POST['address'] ?? '') . ', ' . ($_POST['city'] ?? ''), ', ');
    } else {
        // Nhập tay
        $fullname = trim($first_name . ' ' . $last_name);
        $phone = preg_replace('/\s+/', '', $_POST['phone'] ?? '');

        $st = trim($_POST['address'] ?? '');
        $wa = trim($_POST['ward'] ?? '');
        $di = trim($_POST['district'] ?? '');
        $pr = trim($_POST['province'] ?? '');
        $final_address = implode(', ', array_filter([$st, $wa, $di, $pr]));

        // Lưu địa chỉ mặc định?
        if (!empty($_POST['save_as_default'])) {
            $conn->prepare("UPDATE user_addresses SET is_default=0 WHERE user_id=:uid")->execute([':uid' => $user_id]);
            $ins = $conn->prepare("INSERT INTO user_addresses (user_id, recipient_name, phone, street, ward, district, province, is_default) VALUES (:uid, :rn, :ph, :st, :wa, :di, :pr, 1)");
            $ins->execute([
                ':uid' => $user_id,
                ':rn' => $fullname,
                ':ph' => $phone,
                ':st' => $st,
                ':wa' => $wa,
                ':di' => $di,
                ':pr' => $pr
            ]);
        }
    }

    $notes = $_POST['notes'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $wallet_used = isset($_POST['wallet_used']) ? floatval($_POST['wallet_used']) : 0;


    try {
        // BẮT ĐẦU GIAO DỊCH (Transaction) - Khóa an toàn
        $conn->beginTransaction();

        // -------------------------------------------------------------
        // BƯỚC 1: LƯU ĐƠN HÀNG VÀO BẢNG Orders
        // -------------------------------------------------------------
        // (Giả sử bạn tự sinh order_id là O0001, O0002...)
        $stmt_max = $conn->prepare("SELECT MAX(CAST(SUBSTRING(order_id, 2) AS UNSIGNED)) FROM Orders");
        $stmt_max->execute();
        $max_num = intval($stmt_max->fetchColumn()) + 1;
        $order_id = 'O' . str_pad($max_num, 4, '0', STR_PAD_LEFT);

        // Lấy các sản phẩm đã chọn từ giỏ hàng (dùng Product_Variants để lấy đúng giá)
        $stmt_cart = $conn->prepare("
            SELECT c.cart_id, c.quantity, c.variant_id,
                   v.original_price, v.sale_price,
                   p.product_id, p.name AS product_name
            FROM Cart c
            JOIN Product_Variants v ON c.variant_id = v.variant_id
            JOIN Products p ON v.product_id = p.product_id
            WHERE c.user_id = :uid AND c.is_selected = 1
        ");
        $stmt_cart->execute(['uid' => $user_id]);
        $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

        // Tính tổng
        $subtotal = 0;
        foreach ($cart_items as $ci) {
            $price = ($ci['sale_price'] > 0) ? $ci['sale_price'] : $ci['original_price'];
            $subtotal += $price * $ci['quantity'];
        }
        $shipping_fee = 35000;
        $total_price  = $subtotal + $shipping_fee;
        $final_price  = max(0, $total_price - $wallet_used);

        // Sinh mã payos_order_code (INT)
        $payos_order_code = intval(date('ymd') . rand(1000, 9999));

        // Xác định trạng thái ban đầu
        // order_status: 0=chờ thanh toán (online), 1=đang xử lý (COD)
        // payment_status: 0=chưa TT, 1=đã TT
        $pay_method_int         = ($payment_method === 'online') ? 2 : 1;
        $initial_payment_status = ($wallet_used >= $total_price) ? 1 : 0; // COD và Online đều là 0 ban đầu trừ khi ví trả hết
        $initial_order_status   = ($payment_method === 'cod')    ? 1 : 0; // 0=pending, 1=processing

        // Lưu đơn hàng
        $sql_order = "INSERT INTO Orders (order_id, user_id, fullname, phone, address, note, payment_method, wallet_used_amount, total_price, final_price, shipping_fee, payos_order_code, payment_status, order_status) 
                      VALUES (:oid, :uid, :fname, :phone, :addr, :note, :pay, :wallet_used, :tp, :fp, :sf, :poc, :ps, :os)";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->execute([
            'oid'         => $order_id,
            'uid'         => $user_id,
            'fname'       => $fullname,
            'phone'       => $phone,
            'addr'        => $final_address,
            'note'        => $notes,
            'pay'         => $pay_method_int,
            'wallet_used' => $wallet_used,
            'tp'          => $total_price,
            'fp'          => $final_price,
            'sf'          => $shipping_fee,
            'poc'         => $payos_order_code,
            'ps'          => $initial_payment_status,
            'os'          => $initial_order_status
        ]);

        // -------------------------------------------------------------
        // BƯỚC 2: XỬ LÝ VÍ ĐIỆN TỬ (Nếu khách có dùng)
        // -------------------------------------------------------------
        if ($wallet_used > 0) {
            // 2.1 Kiểm tra lại xem số dư thật sự có đủ không (chống hack qua F12 sửa code HTML)
            $stmt_check_wallet = $conn->prepare("SELECT wallet_balance FROM Users WHERE user_id = :uid FOR UPDATE");
            $stmt_check_wallet->execute(['uid' => $user_id]);
            $current_balance = floatval($stmt_check_wallet->fetchColumn());

            if ($current_balance < $wallet_used) {
                throw new Exception("Số dư ví không đủ để thực hiện giao dịch này!");
            }

            // 2.2 Trừ tiền trong bảng Users
            $sql_deduct = "UPDATE Users SET wallet_balance = wallet_balance - :amount WHERE user_id = :uid";
            $stmt_deduct = $conn->prepare($sql_deduct);
            $stmt_deduct->execute([
                'amount' => $wallet_used,
                'uid' => $user_id
            ]);

            // 2.3 Lưu lại lịch sử giao dịch vào bảng Wallet_Transactions bạn vừa tạo
            // Giả sử transaction_type: 1 là Nạp/Hoàn tiền, 2 là Trừ tiền mua sắm
            $sql_trans = "INSERT INTO Wallet_Transactions (user_id, amount, transaction_type, description, related_order_id) 
                          VALUES (:uid, :amount, 2, :desc, :oid)";
            $stmt_trans = $conn->prepare($sql_trans);
            $stmt_trans->execute([
                'uid' => $user_id,
                'amount' => $wallet_used,
                'desc' => "Sử dụng ví thanh toán đơn hàng " . $order_id,
                'oid' => $order_id
            ]);
        }

        // -------------------------------------------------------------
        // BƯỚC 3: LƯU CHI TIẾT ĐƠN HÀNG (Order_Details)
        // -------------------------------------------------------------
        // Sinh detail_id kiểu D0001, D0002...
        $stmt_max_d = $conn->prepare("SELECT MAX(CAST(SUBSTRING(detail_id, 2) AS UNSIGNED)) FROM Order_Details");
        $stmt_max_d->execute();
        $max_d = intval($stmt_max_d->fetchColumn());

        $sql_detail = "INSERT INTO Order_Details (detail_id, order_id, variant_id, product_name, quantity, price) 
                       VALUES (:did, :oid, :vid, :pname, :qty, :price)";
        $stmt_detail = $conn->prepare($sql_detail);
        foreach ($cart_items as $ci) {
            $max_d++;
            $detail_id  = 'D' . str_pad($max_d, 4, '0', STR_PAD_LEFT);
            $unit_price = ($ci['sale_price'] > 0) ? $ci['sale_price'] : $ci['original_price'];
            $stmt_detail->execute([
                'did'   => $detail_id,
                'oid'   => $order_id,
                'vid'   => $ci['variant_id'],
                'pname' => $ci['product_name'],
                'qty'   => $ci['quantity'],
                'price' => $unit_price
            ]);
        }

        // -------------------------------------------------------------
        // BƯỚC 4: XÓA GIỎ HÀNG
        // -------------------------------------------------------------
        $stmt_clear_cart = $conn->prepare("DELETE FROM Cart WHERE user_id = :uid AND is_selected = 1");
        $stmt_clear_cart->execute(['uid' => $user_id]);

        // CHỐT GIAO DỊCH
        $conn->commit();

        // ===============================
        // ✅ PAYOS PAYMENT (FINAL FIX)
        // ===============================
        if ($payment_method === 'online' && $final_price > 0) {

            $PAYOS_CLIENT_ID = "d9c795f0-0eea-438e-9f92-3a2902c7c99c";
            $PAYOS_API_KEY = "610ff3aa-21e6-4713-ba23-d9b74e545129";
            $PAYOS_CHECKSUM_KEY = "b7b836b8064139b2906a3431c5bad44ad104ade4777d83cf7059eb316623ebfe";

            // 🔥 Tự động lấy base URL động để chạy tốt trên mọi thư mục/máy tính
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $src_path = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
            $base_url = rtrim($protocol . $_SERVER['HTTP_HOST'] . $src_path, '/');

            $return_url = $base_url . "/order_success.php?id=" . $order_id . "&method=online";
            $cancel_url = $base_url . "/checkout.php";

            $payos_data = [
                "orderCode" => $payos_order_code,
                "amount" => intval($final_price),
                "description" => "NTK " . $order_id,
                "returnUrl" => $return_url,
                "cancelUrl" => $cancel_url
            ];

            // ===============================
            // ✅ FIX SIGNATURE (KHÔNG dng http_build_query)
            // ===============================
            ksort($payos_data);

            $rawData = "";
            foreach ($payos_data as $key => $value) {
                $rawData .= $key . "=" . trim($value) . "&";
            }
            $rawData = rtrim($rawData, "&");

            $signature = hash_hmac("sha256", $rawData, $PAYOS_CHECKSUM_KEY);
            $payos_data["signature"] = $signature;

            // ===============================
            // CALL API
            // ===============================
            $ch = curl_init("https://api-merchant.payos.vn/v2/payment-requests");

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payos_data),
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "x-client-id: $PAYOS_CLIENT_ID",
                    "x-api-key: $PAYOS_API_KEY"
                ],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false  // Fix lỗi cURL SSL (60) trên XAMPP
            ]);

            $response = curl_exec($ch);

            // ❗ Nếu call lỗi thật (network lỗi)
            if ($response === false) {
                $err = curl_error($ch);
                curl_close($ch);
                echo "<script>alert('Lỗi CURL: $err'); window.location.href = '../checkout.php';</script>";
                exit;
            }

            curl_close($ch);

            $resData = json_decode($response, true);

            // 📝 LOG để debug
            file_put_contents(__DIR__ . "/payos_debug.txt", print_r($resData, true));

            // ===============================
            // ✅ SUCCESS
            // ===============================
            if (isset($resData['code']) && $resData['code'] === '00' && isset($resData['data'])) {

                $qr_code = $resData['data']['qrCode'] ?? '';
                $checkout_url = $resData['data']['checkoutUrl'] ?? '';

                $upd = $conn->prepare("
                    UPDATE Orders
                    SET payos_qr_code = :qr, payos_checkout_url = :url
                    WHERE order_id = :oid
                ");
                $upd->execute([
                    'qr' => $qr_code,
                    'url' => $checkout_url,
                    'oid' => $order_id
                ]);

                header("Location: ../order_success.php?id=$order_id&method=online");
                exit;

            } else {
                // ❗ FIX: show full lỗi thật
                $err = $resData['desc'] ?? json_encode($resData);
                echo "<script>alert('PayOS lỗi: " . addslashes($err) . "'); window.location.href = '../checkout.php';</script>";
                exit;
            }
        }

        // COD hoặc ví đủ trả hoàn toàn → Chuyển trang thành công
        header("Location: ../order_success.php?id=" . $order_id . "&method=cod");
        exit;

    } catch (Exception $e) {
        // NẾU CÓ LỖI (Ví dụ: Mạng rớt, hack số dư, lỗi SQL) -> HOÀN TÁC TOÀN BỘ! Khách không bị trừ tiền.
        $conn->rollBack();

        $error_msg = $e->getMessage();
        echo "<script>
                alert('Có lỗi xảy ra: " . addslashes($error_msg) . "');
                window.history.back();
              </script>";
    }
} else {
    header("Location: index.php");
    exit;
}
?>