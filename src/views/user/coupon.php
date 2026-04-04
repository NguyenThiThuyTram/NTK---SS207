<?php
// Sử dụng kết nối PDO từ dashboard.php
global $conn;

$coupons = [];

// Kiểm tra xem biến $conn có tồn tại không để chặn lỗi "Call to a member function prepare() on null"
if (isset($conn) && $conn !== null) {
    try {
        // Lấy mã giảm giá từ database của đại ca (còn hạn và đang active)
        $sql = "SELECT coupon_id, code, discount_type, discount_value, start_date, end_date, min_order_value, max_discount_amount 
                FROM Coupons 
                WHERE status = 1 AND end_date >= CURDATE() 
                ORDER BY end_date ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "<p style='color:red;'>Lỗi tải Voucher: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red; background: #ffe6e6; padding: 10px; border-radius: 4px; border: 1px solid red;'>
            <b>Lỗi kết nối:</b> Không tìm thấy biến \$conn. Đại ca kiểm tra lại đường dẫn file <code>database.php</code> trong <code>dashboard.php</code> xem đã chuẩn chưa nhé!
          </p>";
}

// Hàm format tiền tệ (Check xem hàm đã tồn tại chưa để khỏi bị lỗi redeclare)
if (!function_exists('formatMoney')) {
    function formatMoney($amount) {
        return number_format($amount, 0, ',', '.') . 'đ';
    }
}
?>

<style>
    /* Giao diện khi trống */
    .empty-coupon { text-align: center; padding: 60px 0; }
    .empty-coupon svg { margin-bottom: 15px; opacity: 0.3; }
    .empty-coupon p { color: var(--text-muted, #757575); font-size: 16px; margin-bottom: 20px; }
    .btn-explore { padding: 10px 30px; border: 1px solid var(--text-main, #111); background: transparent; color: var(--text-main, #111); font-size: 14px; cursor: pointer; transition: 0.2s; border-radius: 2px;}
    .btn-explore:hover { background: var(--border-color, #f8f8f8); }

    /* Giao diện khi có Voucher */
    .coupon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px; }
    .coupon-card { display: flex; border: 1px solid var(--border-color, #e8e8e8); border-radius: 4px; overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .coupon-left { width: 100px; background: #ee4d2d; color: #fff; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 15px; border-right: 2px dashed #fff; }
    .coupon-left i { font-size: 24px; margin-bottom: 5px; }
    .coupon-right { padding: 15px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
    .c-title { font-size: 16px; font-weight: bold; margin-bottom: 5px; color: var(--text-main, #333); }
    .c-min { font-size: 13px; color: var(--text-muted, #666); margin-bottom: 5px; }
    .c-date { font-size: 12px; color: #ee4d2d; }
    .c-action { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
    .c-code { background: var(--bg-body, #f5f5f5); padding: 4px 8px; border-radius: 2px; font-size: 12px; font-family: monospace; border: 1px dashed #ccc;}
    .c-btn { color: #ee4d2d; border: none; background: none; font-weight: 500; cursor: pointer; font-size: 14px; padding: 0;}
    .c-btn:hover { text-decoration: underline; }
</style>

<h2 class="section-title">Kho voucher</h2>

<?php if (empty($coupons) && (isset($conn) && $conn !== null)): ?>
    <div class="empty-coupon">
        <svg width="80" height="80" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M25 35H75C80.5228 35 85 39.4772 85 45V55C85 60.5228 80.5228 65 75 65H25C19.4772 65 15 60.5228 15 55V45C15 39.4772 19.4772 35 25 35Z" stroke="#333" stroke-width="6" stroke-linejoin="round"/>
            <path d="M30 35V65" stroke="#333" stroke-width="4" stroke-dasharray="4 4"/>
            <path d="M70 35V65" stroke="#333" stroke-width="4" stroke-dasharray="4 4"/>
            <circle cx="15" cy="50" r="8" fill="#f5f5f5" stroke="#333" stroke-width="6"/>
            <circle cx="85" cy="50" r="8" fill="#f5f5f5" stroke="#333" stroke-width="6"/>
        </svg>
        <p>Bạn chưa có voucher nào</p>
        <button class="btn-explore">Khám phá voucher</button>
    </div>
<?php elseif (!empty($coupons)): ?>
    <div class="coupon-grid">
        <?php foreach ($coupons as $cp): ?>
            <div class="coupon-card">
                <div class="coupon-left">
                    <i class="fa-solid fa-ticket"></i>
                    <span style="font-size: 13px; text-align: center;">
                        <?= $cp['discount_type'] == 'percent' ? 'Giảm %' : 'Giảm Tiền' ?>
                    </span>
                </div>
                <div class="coupon-right">
                    <div>
                        <div class="c-title">
                            <?php 
                                if ($cp['discount_type'] == 'percent') {
                                    echo "Giảm " . $cp['discount_value'] . "%";
                                    if ($cp['max_discount_amount'] > 0) {
                                        echo " (Tối đa " . formatMoney($cp['max_discount_amount']) . ")";
                                    }
                                } else {
                                    echo "Giảm " . formatMoney($cp['discount_value']);
                                }
                            ?>
                        </div>
                        <div class="c-min">Đơn Tối Thiểu <?= formatMoney($cp['min_order_value']) ?></div>
                        <div class="c-date">HSD: <?= date('d/m/Y', strtotime($cp['end_date'])) ?></div>
                    </div>
                    <div class="c-action">
                        <span class="c-code"><?= htmlspecialchars($cp['code']) ?></span>
                        <button class="c-btn" onclick="alert('Đã lưu mã <?= $cp['code'] ?>!')">Lưu</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>