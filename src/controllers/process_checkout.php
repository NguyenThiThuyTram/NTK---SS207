<?php
session_start();
require_once 'config/database.php'; // Đảm bảo đường dẫn này đúng với project của bạn

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: views/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Nhận dữ liệu từ form (Lấy các thông tin giao hàng cơ bản)
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cod';
    
    // ĐÂY LÀ PHẦN QUAN TRỌNG: Lấy số tiền ví mà khách muốn dùng
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

        // Lưu thông tin đơn hàng, có cột wallet_used_amount bạn vừa tạo
        $sql_order = "INSERT INTO Orders (order_id, user_id, fullname, phone, address, note, payment_method, wallet_used_amount) 
                      VALUES (:oid, :uid, :fname, :phone, :addr, :note, :pay, :wallet_used)";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->execute([
            'oid' => $order_id,
            'uid' => $user_id,
            'fname' => $first_name . ' ' . $last_name,
            'phone' => $phone,
            'addr' => $address . ', ' . $city,
            'note' => $notes,
            'pay' => $payment_method,
            'wallet_used' => $wallet_used
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
        // BƯỚC 3: DỌN DẸP
        // -------------------------------------------------------------
        // Chuyển sản phẩm từ Cart sang Order_Details (Code chi tiết đơn hàng của bạn)
        // ... (Bạn tự viết phần INSERT INTO Order_Details SELECT từ Cart ở đây) ...

        // Xóa giỏ hàng đã chọn
        $stmt_clear_cart = $conn->prepare("DELETE FROM Cart WHERE user_id = :uid AND is_selected = 1");
        $stmt_clear_cart->execute(['uid' => $user_id]);

        // CHỐT GIAO DỊCH (Lưu mọi thứ vĩnh viễn vào DB)
        $conn->commit();

        // Thành công -> Báo cáo và chuyển hướng
        echo "<script>
                alert('Đặt hàng thành công! Mã đơn của bạn là: $order_id');
                window.location.href = 'index.php'; // Hoặc chuyển sang trang cảm ơn/quản lý đơn hàng
              </script>";

    } catch (Exception $e) {
        // NẾU CÓ LỖI (Ví dụ: Mạng rớt, hack số dư, lỗi SQL) -> HOÀN TÁC TOÀN BỘ! Khách không bị trừ tiền.
        $conn->rollBack();
        
        $error_msg = $e->getMessage();
        echo "<script>
                alert('Có lỗi xảy ra: $error_msg');
                window.history.back();
              </script>";
    }
} else {
    header("Location: index.php");
    exit;
}
?>