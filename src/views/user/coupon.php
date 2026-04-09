<?php
// Ensure this runs as a child of dashboard.php
$user_id = $_SESSION['user_id'] ?? null;
global $conn;

$saved_coupons = [];

if (isset($conn) && $conn !== null && $user_id) {
    try {
        // Query kết nối bảng Coupons và bảng UserCoupons để lấy mã KHÁCH HÀNG ĐÃ LƯU
        // (Lưu ý: Đại ca cần có bảng UserCoupons trong database, đệ có để code SQL tạo bảng ở dưới nếu chưa có)
        $sql = "SELECT c.coupon_id, c.code, c.discount_type, c.discount_value, c.start_date, c.end_date, c.min_order_value, c.max_discount_amount 
                FROM Coupons c
                INNER JOIN UserCoupons uc ON c.coupon_id = uc.coupon_id
                WHERE uc.user_id = :user_id AND c.status = 1 AND c.end_date >= CURDATE()
                ORDER BY c.end_date ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $saved_coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Nếu lỗi do chưa có bảng UserCoupons, tạm thời gán mảng rỗng
        $saved_coupons = [];
    }
}

// Hàm format tiền tệ
if (!function_exists('formatMoney')) {
    function formatMoney($amount) {
        return number_format($amount, 0, ',', '.') . 'đ';
    }
}
?>

<style>
    /* Tổng thể Tone Màu Sang Trọng & Font Helvetica Neue */
    .coupon-wrapper {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #ffffff;
        color: #111111;
    }
    
    .coupon-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 1px solid #e5e5e5;
        padding-bottom: 15px;
    }
    
    .coupon-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #111111;
    }

    /* Nút Khám Phá Voucher (Nâu đậm) */
    .btn-discover {
        background-color: #2f1c00;
        color: #ffffff;
        border: none;
        padding: 8px 18px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: opacity 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-discover:hover {
        opacity: 0.85;
        color: #ffffff;
    }

    /* Lưới Voucher */
    .coupon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }

    /* Thẻ Voucher (Nền Beige, viền xám nhẹ) */
    .coupon-card {
        display: flex;
        background-color: #f5f1eb;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .coupon-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    /* Cột trái Voucher (Nền Nâu Đậm) */
    .coupon-left {
        background-color: #2f1c00;
        color: #f5f1eb;
        padding: 15px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 90px;
        text-align: center;
        border-right: 2px dashed #ffffff;
    }
    .coupon-left .tag {
        font-size: 14px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Cột phải Voucher */
    .coupon-right {
        padding: 15px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .c-title {
        font-weight: 600;
        font-size: 15px;
        margin-bottom: 6px;
        color: #111111;
    }
    .c-desc {
        font-size: 13px;
        color: #555555;
        margin-bottom: 4px;
    }
    
    /* Footer của thẻ Voucher */
    .c-action {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px;
        border-top: 1px solid #e5e5e5;
        padding-top: 12px;
    }
    .c-code {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        background: #ffffff;
        padding: 4px 8px;
        border: 1px solid #e5e5e5;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        color: #2f1c00;
    }
    .c-btn-use {
        background-color: transparent;
        color: #2f1c00;
        border: 1px solid #2f1c00;
        padding: 6px 14px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .c-btn-use:hover {
        background-color: #2f1c00;
        color: #ffffff;
    }

    /* Trạng thái trống (Empty State) */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        background: #f5f1eb;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
    }
    .empty-state p {
        color: #555555;
        margin-bottom: 20px;
        font-size: 15px;
    }
</style>

<div class="coupon-wrapper">
    <div class="coupon-header">
        <h2>Kho Voucher của bạn</h2>
        <a href="dashboard.php?view=all_coupons" class="btn-discover">Khám phá voucher</a>
    </div>

    <?php if (empty($saved_coupons)): ?>
        <div class="empty-state">
            <p>Bạn chưa lưu voucher nào trong ví.</p>
            <a href="dashboard.php?view=all_coupons" class="btn-discover">Khám phá voucher ngay</a>
        </div>
    <?php else: ?>
        <div class="coupon-grid">
            <?php foreach ($saved_coupons as $cp): ?>
                <div class="coupon-card">
                    <div class="coupon-left">
                        <span class="tag">
                            <?= $cp['discount_type'] == 0 ? 'Giảm %' : 'Giảm Tiền' ?>
                        </span>
                    </div>
                    <div class="coupon-right">
                        <div>
                            <div class="c-title">
                                <?php 
                                    if ($cp['discount_type'] == 0) { // 0: Phần trăm (Dựa theo schema NTK.sql)
                                        echo "Giảm " . $cp['discount_value'] . "%";
                                        if ($cp['max_discount_amount'] > 0) {
                                            echo " (Tối đa " . formatMoney($cp['max_discount_amount']) . ")";
                                        }
                                    } else { // 1: Số tiền cố định
                                        echo "Giảm " . formatMoney($cp['discount_value']);
                                    }
                                ?>
                            </div>
                            <div class="c-desc">Đơn Tối Thiểu <?= formatMoney($cp['min_order_value']) ?></div>
                            <div class="c-desc">HSD: <?= date('d/m/Y', strtotime($cp['end_date'])) ?></div>
                        </div>
                        <div class="c-action">
                            <span class="c-code"><?= htmlspecialchars($cp['code']) ?></span>
                            <button class="c-btn-use" onclick="alert('Đang áp dụng mã <?= $cp['code'] ?>!')">Dùng Ngay</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>