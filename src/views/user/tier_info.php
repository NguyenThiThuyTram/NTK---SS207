<?php
// Lấy tổng chi tiêu
$stmt = $conn->prepare("SELECT IFNULL(SUM(final_price), 0) FROM orders WHERE user_id = :uid AND order_status = 3");
$stmt->execute(['uid' => $_SESSION['user_id']]);
$total_spent = (float)$stmt->fetchColumn();

$accumulated_points = (int)$current_user['accumulated_points'];
$current_points = (int)$current_user['current_points'];
$current_tier = $current_user['tier'] ?? 'Member';

$tiers = [
    'Member' => ['points' => 0, 'spend' => 0, 'discount' => 0, 'name' => 'Thành viên Đồng', 'color' => '#8b7355', 'icon' => 'fa-user'],
    'Silver' => ['points' => 500, 'spend' => 5000000, 'discount' => 2, 'name' => 'Thành viên Bạc', 'color' => '#bdc3c7', 'icon' => 'fa-medal'],
    'Gold' => ['points' => 1500, 'spend' => 15000000, 'discount' => 5, 'name' => 'Thành viên Vàng', 'color' => '#f1c40f', 'icon' => 'fa-trophy'],
    'Diamond' => ['points' => 5000, 'spend' => 50000000, 'discount' => 10, 'name' => 'Thành viên Kim Cương', 'color' => '#9b59b6', 'icon' => 'fa-gem']
];

$next_tier = null;
$next_points_needed = 0;
$next_spend_needed = 0;

if ($current_tier === 'Member') {
    $next_tier = 'Silver';
} elseif ($current_tier === 'Silver') {
    $next_tier = 'Gold';
} elseif ($current_tier === 'Gold') {
    $next_tier = 'Diamond';
}

if ($next_tier) {
    $next_points_needed = max(0, $tiers[$next_tier]['points'] - $accumulated_points);
    $next_spend_needed = max(0, $tiers[$next_tier]['spend'] - $total_spent);
    
    // Tính phần trăm progress (dựa trên điều kiện nào gần đạt được hơn)
    $pct_points = min(100, ($accumulated_points / $tiers[$next_tier]['points']) * 100);
    $pct_spend = min(100, ($total_spent / $tiers[$next_tier]['spend']) * 100);
    $progress_pct = max($pct_points, $pct_spend);
} else {
    $progress_pct = 100;
}

$tier_info = $tiers[$current_tier] ?? $tiers['Member'];
?>

<div class="profile-container" style="background:#fff; border-radius:12px; padding:30px; box-shadow:0 2px 10px rgba(0,0,0,0.03);">
    <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 5px; color: #111;">Hạng Thành Viên</h2>
    <p style="color: #666; font-size: 14px; margin-bottom: 25px;">Theo dõi cấp bậc và quyền lợi của bạn</p>

    <!-- Thẻ hạng hiện tại -->
    <div style="background: linear-gradient(135deg, #2c3e50, #1a252f); border-radius: 16px; padding: 30px; color: #fff; margin-bottom: 30px; position: relative; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
        <!-- Decor -->
        <i class="fa-solid <?= $tier_info['icon'] ?>" style="position: absolute; right: -20px; bottom: -20px; font-size: 150px; opacity: 0.1; transform: rotate(-15deg);"></i>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 1;">
            <div>
                <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #cbd5e1; margin-bottom: 5px;">Hạng hiện tại</div>
                <div style="font-size: 32px; font-weight: 800; color: <?= $tier_info['color'] ?>; text-shadow: 0 2px 4px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 12px;">
                    <i class="fa-solid <?= $tier_info['icon'] ?>"></i> <?= $tier_info['name'] ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 14px; color: #cbd5e1; margin-bottom: 2px;">Điểm khả dụng</div>
                <div style="font-size: 24px; font-weight: 700; color: #e5c199;"><?= number_format($current_points, 0, ',', '.') ?> <i class="fa-solid fa-coins" style="font-size:16px;"></i></div>
            </div>
        </div>

        <div style="margin-top: 30px; position: relative; z-index: 1;">
            <?php if ($next_tier): ?>
                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                    <span style="color: #cbd5e1;">Tiến trình lên hạng <strong><?= $tiers[$next_tier]['name'] ?></strong></span>
                    <span style="font-weight: 600; color: #fff;"><?= round($progress_pct) ?>%</span>
                </div>
                <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; width: <?= $progress_pct ?>%; background: <?= $tiers[$next_tier]['color'] ?>; border-radius: 4px; box-shadow: 0 0 10px <?= $tiers[$next_tier]['color'] ?>;"></div>
                </div>
                <div style="margin-top: 12px; font-size: 13px; color: #94a3b8; line-height: 1.5;">
                    Cần thêm <strong style="color:#fff;"><?= number_format($next_points_needed, 0, ',', '.') ?> điểm</strong> HOẶC chi tiêu thêm <strong style="color:#fff;"><?= number_format($next_spend_needed, 0, ',', '.') ?>đ</strong> để thăng hạng.<br>
                    <span style="font-size: 12px;">(Bạn đang có <?= number_format($accumulated_points, 0, ',', '.') ?> điểm tích luỹ, Tổng chi tiêu: <?= number_format($total_spent, 0, ',', '.') ?>đ)</span>
                </div>
            <?php else: ?>
                <div style="font-size: 15px; color: #e5c199; font-weight: 600;"><i class="fa-solid fa-crown"></i> Bạn đã đạt cấp bậc cao nhất!</div>
                <div style="margin-top: 8px; font-size: 13px; color: #94a3b8;">
                    Tổng điểm tích luỹ: <?= number_format($accumulated_points, 0, ',', '.') ?> | Tổng chi tiêu: <?= number_format($total_spent, 0, ',', '.') ?>đ
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quyền lợi hạng -->
    <h3 style="font-size: 18px; font-weight: 600; margin: 30px 0 15px; color: #111;"><i class="fa-solid fa-gift" style="color: #e74c3c; margin-right: 8px;"></i> Quyền Lợi Của Bạn</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
        <!-- Quyền lợi chiết khấu -->
        <div style="border: 1px solid #e5e5e5; border-radius: 12px; padding: 20px; background: #fafaf8;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(39, 174, 96, 0.1); color: #27ae60; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 15px;">
                <i class="fa-solid fa-percent"></i>
            </div>
            <h4 style="font-size: 15px; font-weight: 600; margin-bottom: 8px; color: #111;">Chiết khấu đơn hàng</h4>
            <p style="font-size: 13.5px; color: #555; line-height: 1.5; margin: 0;">
                <?php if ($tier_info['discount'] > 0): ?>
                    Bạn được giảm trực tiếp <strong><?= $tier_info['discount'] ?>%</strong> trên tổng giá trị của mọi đơn hàng, tự động áp dụng khi thanh toán.
                <?php else: ?>
                    Hãy thăng hạng Bạc để nhận ưu đãi giảm 2% trên mọi đơn hàng nhé!
                <?php endif; ?>
            </p>
        </div>

        <!-- Quyền lợi voucher sinh nhật/lễ -->
        <div style="border: 1px solid #e5e5e5; border-radius: 12px; padding: 20px; background: #fafaf8;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(155, 89, 182, 0.1); color: #9b59b6; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 15px;">
                <i class="fa-solid fa-cake-candles"></i>
            </div>
            <h4 style="font-size: 15px; font-weight: 600; margin-bottom: 8px; color: #111;">Voucher đặc biệt</h4>
            <p style="font-size: 13.5px; color: #555; line-height: 1.5; margin: 0;">
                Nhận voucher quà tặng đặc biệt vào dịp sinh nhật và các ngày lễ lớn trong năm. Hạng càng cao, quà càng khủng!
            </p>
        </div>

        <!-- Quyền lợi điểm thưởng -->
        <div style="border: 1px solid #e5e5e5; border-radius: 12px; padding: 20px; background: #fafaf8;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(241, 196, 15, 0.1); color: #f39c12; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 15px;">
                <i class="fa-solid fa-coins"></i>
            </div>
            <h4 style="font-size: 15px; font-weight: 600; margin-bottom: 8px; color: #111;">Tích lũy điểm vô hạn</h4>
            <p style="font-size: 13.5px; color: #555; line-height: 1.5; margin: 0;">
                Hoàn thành đơn hàng được cộng 20 điểm. Đánh giá sản phẩm nhận 50-100 điểm. Dùng điểm đổi voucher mua sắm!
            </p>
        </div>
    </div>

</div>

<style>
/* Hỗ trợ Dark Mode */
body.dark-mode .profile-container { background: #1e1e1e !important; border-color: #333 !important; }
body.dark-mode h2, body.dark-mode h3, body.dark-mode h4 { color: #fff !important; }
body.dark-mode p { color: #bbb !important; }
body.dark-mode .profile-container > div > div { border-color: #333 !important; background: #252525 !important; }
</style>
