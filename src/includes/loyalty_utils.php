<?php
// src/includes/loyalty_utils.php

function addLoyaltyPoints($conn, $user_id, $points, $reason) {
    if ($points <= 0) return;

    // Lấy thông tin hiện tại
    $stmt = $conn->prepare("SELECT current_points, accumulated_points, tier FROM users WHERE user_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) return;

    $new_current = (int)$user['current_points'] + $points;
    $new_accumulated = (int)$user['accumulated_points'] + $points;
    $current_tier = $user['tier'];

    // Cập nhật điểm
    $stmt = $conn->prepare("UPDATE users SET current_points = :cp, accumulated_points = :ap WHERE user_id = :uid");
    $stmt->execute(['cp' => $new_current, 'ap' => $new_accumulated, 'uid' => $user_id]);

    // Gửi thông báo nhận điểm
    $msg = "Tuyệt vời! Bạn nhận được $points điểm từ việc $reason.";
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'system', 'Nhận điểm thưởng', :msg)");
    $stmt->execute(['uid' => $user_id, 'msg' => $msg]);

    // Kiểm tra thăng hạng
    checkTierUpgrade($conn, $user_id, $new_accumulated, $current_tier);
}

function checkTierUpgrade($conn, $user_id, $accumulated_points, $current_tier) {
    $new_tier = 'Member';
    if ($accumulated_points >= 5000) {
        $new_tier = 'Diamond';
    } elseif ($accumulated_points >= 1500) {
        $new_tier = 'Gold';
    } elseif ($accumulated_points >= 500) {
        $new_tier = 'Silver';
    }

    if ($new_tier !== $current_tier && getTierLevel($new_tier) > getTierLevel($current_tier)) {
        $stmt = $conn->prepare("UPDATE users SET tier = :tier WHERE user_id = :uid");
        $stmt->execute(['tier' => $new_tier, 'uid' => $user_id]);

        $discount = getTierDiscount($new_tier);

        if ($discount > 0) {
            $msg = "🎉 Chúc mừng! Bạn đã thăng hạng lên Thành viên $new_tier. Mọi đơn hàng từ nay sẽ được giảm tự động $discount%!";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'system', 'Thăng hạng thành viên', :msg)");
            $stmt->execute(['uid' => $user_id, 'msg' => $msg]);
        }
    }
}

function getTierLevel($tier) {
    switch ($tier) {
        case 'Diamond': return 4;
        case 'Gold': return 3;
        case 'Silver': return 2;
        default: return 1;
    }
}

function getTierDiscount($tier) {
    switch ($tier) {
        case 'Diamond': return 10;
        case 'Gold': return 5;
        case 'Silver': return 2;
        default: return 0;
    }
}
?>
