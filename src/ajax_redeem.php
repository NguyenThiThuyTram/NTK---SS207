<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$points_to_redeem = isset($_POST['points']) ? intval($_POST['points']) : 0;
if ($points_to_redeem <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Số điểm không hợp lệ!']);
    exit;
}

try {
    $conn->beginTransaction();

    // Lấy số điểm hiện tại
    $stmt = $conn->prepare("SELECT current_points, wallet_balance FROM users WHERE user_id = :uid FOR UPDATE");
    $stmt->execute(['uid' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Không tìm thấy người dùng.");
    }

    if ($user['current_points'] < $points_to_redeem) {
        throw new Exception("Bạn không có đủ điểm Loyalty.");
    }

    // Tỷ lệ đổi: 1 điểm = 100 VNĐ
    $vnd_amount = $points_to_redeem * 100;

    // Trừ điểm và cộng tiền ví
    $stmt_update = $conn->prepare("UPDATE users SET current_points = current_points - :pts, wallet_balance = wallet_balance + :amt WHERE user_id = :uid");
    $stmt_update->execute([
        'pts' => $points_to_redeem,
        'amt' => $vnd_amount,
        'uid' => $user_id
    ]);

    // Thêm giao dịch vào wallet_transactions
    $stmt_tx = $conn->prepare("INSERT INTO wallet_transactions (user_id, amount, transaction_type, description, created_at) VALUES (:uid, :amt, 1, :desc, NOW())");
    $stmt_tx->execute([
        'uid' => $user_id,
        'amt' => $vnd_amount,
        'desc' => "Đổi $points_to_redeem điểm Loyalty thành tiền"
    ]);

    // Thêm thông báo
    $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (:uid, :msg, 0, NOW())");
    $stmt_notif->execute([
        'uid' => $user_id,
        'msg' => "Bạn đã đổi $points_to_redeem điểm Loyalty thành " . number_format($vnd_amount, 0, ',', '.') . "đ thành công!"
    ]);

    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => "Đổi điểm thành công! Nhận " . number_format($vnd_amount, 0, ',', '.') . "đ."]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
