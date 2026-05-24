<?php
/**
 * return_order.php
 * User gửi yêu cầu trả hàng. KHÔNG hoàn tiền ngay.
 * Luồng: User gửi yêu cầu → status=5 → Admin duyệt → status=6 → User gửi hàng
 *        → Admin xác nhận nhận hàng → status=7 (Đã hoàn tiền) → Hoàn tiền vào ví
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit;
}

$order_id = $_POST['order_id'] ?? ($_GET['id'] ?? '');
$user_id  = $_SESSION['user_id'];
$reason   = trim($_POST['reason'] ?? '');
$return_image = ''; // Xử lý upload nếu có

if (!$order_id) {
    echo "<script>alert('Không tìm thấy mã đơn hàng!'); window.history.back();</script>";
    exit;
}

if (empty($reason)) {
    echo "<script>alert('Vui lòng nhập lý do trả hàng!'); window.history.back();</script>";
    exit;
}

// Xử lý upload ảnh bằng chứng (nếu có)
if (!empty($_FILES['return_image']['name'])) {
    $upload_dir = __DIR__ . '/../assets/uploads/returns/';
    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
    
    $ext = strtolower(pathinfo($_FILES['return_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4'];
    
    if (in_array($ext, $allowed) && $_FILES['return_image']['size'] <= 10 * 1024 * 1024) {
        $new_name = 'return_' . $order_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['return_image']['tmp_name'], $upload_dir . $new_name)) {
            $return_image = 'assets/uploads/returns/' . $new_name;
        }
    }
}

try {
    $conn->beginTransaction();

    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :oid AND user_id = :uid FOR UPDATE");
    $stmt->execute(['oid' => $order_id, 'uid' => $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) throw new Exception("Không tìm thấy đơn hàng.");

    $status = (int)$order['order_status'];

    // Chỉ cho phép trả hàng khi đơn đang giao (2) hoặc đã hoàn thành (3)
    // Và chưa có yêu cầu trả hàng đang chờ (5)
    if (!in_array($status, [2, 3])) {
        throw new Exception("Chỉ có thể yêu cầu trả hàng khi đơn đang giao hoặc đã hoàn thành.");
    }

    // Kiểm tra xem đã có yêu cầu trả hàng chưa
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM order_returns WHERE order_id = :oid AND status IN (0, 1)");
    $stmt_check->execute(['oid' => $order_id]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("Đơn hàng này đã có yêu cầu trả hàng đang được xử lý.");
    }

    // Tạo yêu cầu trả hàng trong bảng order_returns
    $conn->prepare("INSERT INTO order_returns (order_id, reason, image_proof, status, created_at) VALUES (:oid, :reason, :img, 0, NOW())")
         ->execute(['oid' => $order_id, 'reason' => $reason, 'img' => $return_image]);

    // Cập nhật đơn hàng: status=5 (Đang yêu cầu trả hàng)
    // Đóng băng tiền (chưa chuyển cho seller) - chỉ cập nhật trạng thái
    $conn->prepare("UPDATE orders SET order_status = 5, return_reason = :reason, return_image = :img, return_requested_at = NOW() WHERE order_id = :oid")
         ->execute(['reason' => $reason, 'img' => $return_image, 'oid' => $order_id]);

    // Tạo thông báo cho USER
    try {
        $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_id) VALUES (:uid, 'return_request', :title, :msg, :oid)")
             ->execute([
                 'uid'   => $user_id,
                 'title' => 'Yêu cầu trả hàng đã được gửi',
                 'msg'   => "Yêu cầu trả hàng cho đơn hàng #{$order_id} đã được gửi thành công. Admin sẽ xem xét và phản hồi trong vòng 24 giờ.",
                 'oid'   => $order_id
             ]);
             
        // Tạo thông báo cho ADMIN
        $stmt_admin = $conn->prepare("SELECT user_id FROM users WHERE role = 1 LIMIT 1");
        $stmt_admin->execute();
        $admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_order_id) VALUES (:uid, 'return_request', :title, :msg, :oid)")
                 ->execute([
                     'uid'   => $admin['user_id'],
                     'title' => 'Yêu cầu trả hàng: #' . $order_id,
                     'msg'   => "Khách hàng yêu cầu trả hàng cho đơn #{$order_id}. Lý do: {$reason}",
                     'oid'   => $order_id
                 ]);
        }
    } catch (PDOException $e) {}

    $conn->commit();

    echo "<script>alert('Yêu cầu trả hàng cho đơn hàng #{$order_id} đã được gửi thành công!\\nAdmin sẽ xem xét và phản hồi trong vòng 24 giờ.'); window.location.href = '../views/user/dashboard.php?view=donmua&tab=5';</script>";

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
}
?>
