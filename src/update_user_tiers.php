<?php
require 'config/database.php';
require 'includes/loyalty_utils.php';

try {
    // Lấy tất cả user
    $stmt = $conn->query("SELECT user_id, accumulated_points, tier FROM users WHERE role = 0");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $upgraded_count = 0;

    foreach ($users as $user) {
        $user_id = $user['user_id'];
        $accumulated_points = (int)$user['accumulated_points'];
        $current_tier = $user['tier'] ?: 'Member';

        // Lấy tổng chi tiêu
        $stmt_spent = $conn->prepare("SELECT IFNULL(SUM(final_price), 0) FROM orders WHERE user_id = :uid AND order_status = 3");
        $stmt_spent->execute(['uid' => $user_id]);
        $total_spent = (float)$stmt_spent->fetchColumn();

        $new_tier = 'Member';
        if ($accumulated_points >= 5000 || $total_spent >= 50000000) {
            $new_tier = 'Diamond';
        } elseif ($accumulated_points >= 1500 || $total_spent >= 15000000) {
            $new_tier = 'Gold';
        } elseif ($accumulated_points >= 500 || $total_spent >= 5000000) {
            $new_tier = 'Silver';
        }

        if ($new_tier !== $current_tier) {
            // Update the tier
            $update_stmt = $conn->prepare("UPDATE users SET tier = :tier WHERE user_id = :uid");
            $update_stmt->execute(['tier' => $new_tier, 'uid' => $user_id]);
            $upgraded_count++;

            // We do NOT send notifications or coupons here to avoid spamming existing users during this migration,
            // or we could, but let's just quietly update their tiers.
            echo "Updated $user_id from $current_tier to $new_tier (Points: $accumulated_points, Spent: $total_spent)<br>\n";
        }
    }

    echo "OK: Đã truy xét và cập nhật hạng cho $upgraded_count user.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
