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

    $coupon_id       = !empty($_POST['coupon_id'])       ? trim($_POST['coupon_id'])       : null;
    $coupon_discount = isset($_POST['coupon_discount'])  ? floatval($_POST['coupon_discount']) : 0;
    $coupon_code     = !empty($_POST['coupon_code'])     ? strtoupper(trim($_POST['coupon_code'])) : null;

    $freeship_coupon_id       = !empty($_POST['freeship_coupon_id'])       ? trim($_POST['freeship_coupon_id'])       : null;
    $freeship_coupon_discount = isset($_POST['freeship_coupon_discount'])  ? floatval($_POST['freeship_coupon_discount']) : 0;
    $freeship_coupon_code     = !empty($_POST['freeship_coupon_code'])     ? strtoupper(trim($_POST['freeship_coupon_code'])) : null;

    $shipping_method_id = !empty($_POST['shipping_method_id']) ? trim($_POST['shipping_method_id']) : 'S01';
    
    $points_discount = isset($_POST['points_discount']) ? floatval($_POST['points_discount']) : 0;


    try {
        // BẮT ĐẦU GIAO DỊCH (Transaction) - Khóa an toàn
        $conn->beginTransaction();

        // -------------------------------------------------------------
        // BƯỚC 1: LƯU ĐƠN HÀNG VÀO BẢNG Orders
        // -------------------------------------------------------------
        $stmt_max = $conn->prepare("SELECT MAX(CAST(SUBSTRING(order_id, 2) AS UNSIGNED)) FROM orders");
        $stmt_max->execute();
        $max_num = intval($stmt_max->fetchColumn()) + 1;
        $order_id = 'O' . str_pad($max_num, 4, '0', STR_PAD_LEFT);

        // Lấy các sản phẩm đã chọn từ giỏ hàng
        $stmt_cart = $conn->prepare("
            SELECT c.cart_id, c.quantity, c.variant_id,
                   v.original_price, v.sale_price,
                   (SELECT fs.flash_sale_price FROM flash_sales fs WHERE fs.variant_id = v.variant_id AND fs.status = 1 AND fs.sale_date = CURRENT_DATE() LIMIT 1) as flash_sale_price,
                   p.product_id, p.name AS product_name
            FROM cart c
            JOIN product_variants v ON c.variant_id = v.variant_id
            JOIN products p ON v.product_id = p.product_id
            WHERE c.user_id = :uid AND c.is_selected = 1
        ");
        $stmt_cart->execute(['uid' => $user_id]);
        $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

        // Tính tổng
        $subtotal = 0;
        foreach ($cart_items as $ci) {
            $price = ($ci['flash_sale_price'] !== null) ? $ci['flash_sale_price'] : (($ci['sale_price'] > 0) ? $ci['sale_price'] : $ci['original_price']);
            $subtotal += $price * $ci['quantity'];
        }

        // Lấy thông tin phí ship của Đơn vị vận chuyển từ database
        $stmt_sm = $conn->prepare("SELECT cost FROM shipping_methods WHERE shipping_method_id = :smid");
        $stmt_sm->execute(['smid' => $shipping_method_id]);
        $shipping_fee = floatval($stmt_sm->fetchColumn() ?: 35000);
        $total_price  = $subtotal + $shipping_fee;

        // ── Xác nhận lại coupon giảm giá đơn hàng phía server ──────────────────
        $verified_coupon_discount = 0;
        $verified_coupon_id = null;
        if ($coupon_id) {
            $stmt_cp = $conn->prepare("SELECT * FROM coupons WHERE coupon_id = :cid AND status = 1 AND (coupon_type = 0 OR coupon_type IS NULL) AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW())");
            $stmt_cp->execute(['cid' => $coupon_id]);
            $cp = $stmt_cp->fetch(PDO::FETCH_ASSOC);

            if ($cp) {
                $qty_ok = ($cp['quantity'] === null || $cp['used_count'] < $cp['quantity']);
                $min_ok = ($subtotal >= floatval($cp['min_order_value']));
                if ($qty_ok && $min_ok) {
                    if ($cp['discount_type'] == 0) {
                        $calc = $subtotal * (floatval($cp['discount_value']) / 100);
                        if (!empty($cp['max_discount_amount']) && $cp['max_discount_amount'] > 0) {
                            $calc = min($calc, floatval($cp['max_discount_amount']));
                        }
                    } else {
                        $calc = floatval($cp['discount_value']);
                    }
                    $verified_coupon_discount = min(round($calc), $subtotal);
                    $verified_coupon_id = $cp['coupon_id'];
                }
            }
        }

        // ── Xác nhận lại coupon freeship phía server ──────────────────
        $verified_freeship_discount = 0;
        $verified_freeship_id = null;
        if ($freeship_coupon_id) {
            $stmt_fcp = $conn->prepare("SELECT * FROM coupons WHERE coupon_id = :cid AND status = 1 AND coupon_type = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW())");
            $stmt_fcp->execute(['cid' => $freeship_coupon_id]);
            $fcp = $stmt_fcp->fetch(PDO::FETCH_ASSOC);

            if ($fcp) {
                $qty_ok = ($fcp['quantity'] === null || $fcp['used_count'] < $fcp['quantity']);
                $min_ok = ($subtotal >= floatval($fcp['min_order_value']));
                if ($qty_ok && $min_ok) {
                    if ($fcp['discount_type'] == 0) {
                        $calc = $shipping_fee * (floatval($fcp['discount_value']) / 100);
                    } else {
                        $calc = floatval($fcp['discount_value']);
                    }
                    $verified_freeship_discount = min(round($calc), $shipping_fee);
                    $verified_freeship_id = $fcp['coupon_id'];
                }
            }
        }

        require_once '../includes/loyalty_utils.php';
        
        $stmt_user_loyalty = $conn->prepare("SELECT current_points, tier FROM users WHERE user_id = :uid FOR UPDATE");
        $stmt_user_loyalty->execute(['uid' => $user_id]);
        $u_data = $stmt_user_loyalty->fetch(PDO::FETCH_ASSOC);
        $current_points = $u_data['current_points'] ?? 0;
        $tier = $u_data['tier'] ?? 'Member';

        // Xác thực điểm Loyalty
        if ($points_discount > $current_points * 100) {
            $points_discount = $current_points * 100;
        }

        // Tính chiết khấu hạng
        $tier_discount_percent = getTierDiscount($tier);
        $tier_discount = round($subtotal * ($tier_discount_percent / 100));

        $final_price = max(0, $total_price - $verified_coupon_discount - $verified_freeship_discount - $tier_discount - $points_discount - $wallet_used);
        $payos_order_code = intval(date('ymd') . rand(1000, 9999));

        // Logic trạng thái: COD=1 (pay_method=1), Online=2 (pay_method=2)
        // payment_status: 1=Đã TT, 0=Chưa TT
        // order_status: 1=Processing, 0=Pending
        $pay_method_int = ($payment_method === 'online') ? 2 : 1;
        $initial_payment_status = ($wallet_used >= $total_price && $payment_method !== 'online') ? 1 : 0;
        $initial_order_status = ($payment_method === 'online') ? 0 : 1;

        $sql_order = "INSERT INTO orders (order_id, user_id, fullname, phone, address, note, payment_method, wallet_used_amount, total_price, final_price, shipping_fee, payos_order_code, payment_status, order_status, coupon_id, discount_value, shipping_method_id, freeship_coupon_id, freeship_discount_value) 
                      VALUES (:oid, :uid, :fname, :phone, :addr, :note, :pay, :wallet_used, :tp, :fp, :sf, :poc, :ps, :os, :cpid, :dv, :smid, :fcsid, :fcsval)";
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
            'os'          => $initial_order_status,
            'cpid'        => $verified_coupon_id,
            'dv'          => $verified_coupon_discount,
            'smid'        => $shipping_method_id,
            'fcsid'       => $verified_freeship_id,
            'fcsval'      => $verified_freeship_discount
        ]);

        // -------------------------------------------------------------
        // BƯỚC 2: XỬ LÝ VÍ ĐIỆN TỬ
        // -------------------------------------------------------------
        if ($wallet_used > 0) {
            $stmt_check_wallet = $conn->prepare("SELECT wallet_balance FROM users WHERE user_id = :uid FOR UPDATE");
            $stmt_check_wallet->execute(['uid' => $user_id]);
            $current_balance = floatval($stmt_check_wallet->fetchColumn());
            if ($current_balance < $wallet_used) throw new Exception("Số dư ví không đủ!");
            
            $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - :amount WHERE user_id = :uid")->execute(['amount' => $wallet_used, 'uid' => $user_id]);
            $conn->prepare("INSERT INTO wallet_transactions (user_id, amount, transaction_type, description, related_order_id) VALUES (:uid, :amount, 2, :desc, :oid)")
                 ->execute(['uid' => $user_id, 'amount' => $wallet_used, 'desc' => "Sử dụng ví thanh toán đơn hàng " . $order_id, 'oid' => $order_id]);
        }

        // -------------------------------------------------------------
        // BƯỚC 2b: XỬ LÝ ĐIỂM LOYALTY
        // -------------------------------------------------------------
        if ($points_discount > 0) {
            $points_to_deduct = ceil($points_discount / 100);
            $conn->prepare("UPDATE users SET current_points = current_points - :pts WHERE user_id = :uid")
                 ->execute(['pts' => $points_to_deduct, 'uid' => $user_id]);
        }

        // -------------------------------------------------------------
        // BƯỚC 3 & 4 & 5: CHI TIẾT, XÓA GIỎ, COUPON
        // -------------------------------------------------------------
        $stmt_max_d = $conn->prepare("SELECT MAX(CAST(SUBSTRING(detail_id, 2) AS UNSIGNED)) FROM order_details");
        $stmt_max_d->execute();
        $max_d = intval($stmt_max_d->fetchColumn());
        $stmt_detail = $conn->prepare("INSERT INTO order_details (detail_id, order_id, variant_id, product_name, quantity, price) VALUES (:did, :oid, :vid, :pname, :qty, :price)");
        foreach ($cart_items as $ci) {
            $max_d++;
            $price = ($ci['flash_sale_price'] !== null) ? $ci['flash_sale_price'] : ($ci['sale_price'] > 0 ? $ci['sale_price'] : $ci['original_price']);
            $stmt_detail->execute(['did' => 'D' . str_pad($max_d, 4, '0', STR_PAD_LEFT), 'oid' => $order_id, 'vid' => $ci['variant_id'], 'pname' => $ci['product_name'], 'qty' => $ci['quantity'], 'price' => $price]);
        }
        $conn->prepare("DELETE FROM cart WHERE user_id = :uid AND is_selected = 1")->execute(['uid' => $user_id]);
        if ($verified_coupon_id) $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE coupon_id = :cid")->execute(['cid' => $verified_coupon_id]);
        if ($verified_freeship_id) $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE coupon_id = :cid")->execute(['cid' => $verified_freeship_id]);

        // ── Thông báo cho Khách hàng & Admin ───────────────────────────
        try {
            // Thông báo cho Khách hàng
            $order_total_fmt = number_format($final_price, 0, ',', '.');
            $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_id) VALUES (:uid, 'order_placed', :title, :msg, :oid)")
                 ->execute([
                     'uid'   => $user_id,
                     'title' => 'Đặt hàng thành công #' . $order_id,
                     'msg'   => "Đơn hàng #{$order_id} của bạn đã được ghi nhận. Tổng thanh toán là {$order_total_fmt} VNĐ.",
                     'oid'   => $order_id
                 ]);

            // Lấy user_id của admin (role = 1)
            $stmt_admin = $conn->prepare("SELECT user_id FROM users WHERE role = 1 LIMIT 1");
            $stmt_admin->execute();
            $admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
            if ($admin) {
                $admin_uid = $admin['user_id'];
                $order_total_fmt = number_format($final_price, 0, ',', '.');
                $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_id) VALUES (:uid, 'new_order', :title, :msg, :oid)")
                     ->execute([
                         'uid'   => $admin_uid,
                         'title' => 'Đơn hàng mới #' . $order_id,
                         'msg'   => "Có đơn hàng mới #{$order_id} từ khách hàng, tổng tiền {$order_total_fmt} VNĐ. Vui lòng xử lý.",
                         'oid'   => $order_id
                     ]);
            }
        } catch (PDOException $e) { /* Không throw, không ảnh hưởng luồng chính */ }

        $conn->commit();

        // -------------------------------------------------------------
        // PAYOS
        // -------------------------------------------------------------
        if ($payment_method === 'online' && $final_price > 0) {
            $PAYOS_CLIENT_ID = "d9c795f0-0eea-438e-9f92-3a2902c7c99c";
            $PAYOS_API_KEY = "610ff3aa-21e6-4713-ba23-d9b74e545129";
            $PAYOS_CHECKSUM_KEY = "b7b836b8064139b2906a3431c5bad44ad104ade4777d83cf7059eb316623ebfe";
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
            $payos_data = ["orderCode" => $payos_order_code, "amount" => intval($final_price), "description" => "Don hang $order_id", "returnUrl" => "$base_url/order_success.php?order_id=$order_id&method=online", "cancelUrl" => "$base_url/checkout.php"];
            ksort($payos_data);
            $rawData = ""; foreach ($payos_data as $key => $value) $rawData .= "$key=" . trim($value) . "&";
            $payos_data["signature"] = hash_hmac("sha256", rtrim($rawData, "&"), $PAYOS_CHECKSUM_KEY);
            $ch = curl_init("https://api-merchant.payos.vn/v2/payment-requests");
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payos_data), CURLOPT_HTTPHEADER => ["Content-Type: application/json", "x-client-id: $PAYOS_CLIENT_ID", "x-api-key: $PAYOS_API_KEY"], CURLOPT_SSL_VERIFYPEER => false]);
            $response = json_decode(curl_exec($ch), true); curl_close($ch);
            if (isset($response['code']) && $response['code'] === '00') {
                $conn->prepare("UPDATE orders SET payos_qr_code = :qr, payos_checkout_url = :url WHERE order_id = :oid")->execute(['qr' => $response['data']['qrCode'], 'url' => $response['data']['checkoutUrl'], 'oid' => $order_id]);
                header("Location: " . $response['data']['checkoutUrl']); exit;
            } else {
                echo "<script>alert('Lỗi thanh toán: " . ($response['desc'] ?? 'Unknown') . "'); window.location.href='../checkout.php';</script>"; exit;
            }
        }
        header("Location: ../order_success.php?id=$order_id&method=cod"); exit;
    } catch (Exception $e) {
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