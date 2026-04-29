<?php
// Ensure this runs as a child of dashboard.php
$user_id = $_SESSION['user_id'] ?? null;
global $conn;

$all_coupons = [];

if (isset($conn) && $conn !== null) {
    try {
        // Lấy tất cả Coupons đang hoạt động và còn hạn
        $sql = "SELECT *
                FROM Coupons
                WHERE status = 1 AND end_date >= CURDATE()
                ORDER BY end_date ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $all_coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "<script>console.error('Lỗi DB: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Hàm format tiền tệ
if (!function_exists('formatMoney')) {
    function formatMoney($amount) {
        return number_format((float)$amount, 0, ',', '.') . 'đ';
    }
}
?>

<style>
    /* Vẫn giữ tone màu Sang Trọng & Font Helvetica Neue */
    .discover-wrapper {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #ffffff;
        color: #111111;
    }
    
    .discover-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 1px solid #e5e5e5;
        padding-bottom: 15px;
    }
    
    .discover-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #111111;
    }

    .btn-back-wallet {
        background-color: transparent;
        color: #2f1c00;
        border: 1px solid #2f1c00;
        padding: 8px 18px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .btn-back-wallet:hover {
        background-color: #2f1c00;
        color: #ffffff;
    }

    .coupon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }

    .coupon-card {
        display: flex;
        background-color: #f5f1eb; /* Beige */
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .coupon-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    /* Đổi màu cột trái sang tone xám/đen để phân biệt với voucher đã lưu */
    .coupon-left {
        background-color: #111111;
        color: #ffffff;
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
    }

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
        border: 1px dashed #ccc;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        color: #666;
    }
    
    /* Nút Lưu Voucher */
    .c-btn-save {
        background-color: #2f1c00;
        color: #ffffff;
        border: none;
        padding: 6px 14px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s ease;
    }
    .c-btn-save:hover {
        opacity: 0.85;
    }
    
    /* Nút khi đã lưu rồi */
    .c-btn-saved {
        background-color: #e5e5e5;
        color: #888888;
        border: none;
        padding: 6px 14px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: not-allowed;
    }
    
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        background: #f5f1eb;
        border: 1px dashed #ccc;
        border-radius: 8px;
        color: #555555;
    }
</style>

<div class="discover-wrapper">
    <div class="discover-header">
        <h2>Khám phá siêu Voucher</h2>
        <a href="dashboard.php?view=coupon" class="btn-back-wallet">Quay lại ví của tôi</a>
    </div>

    <?php if (empty($all_coupons)): ?>
        <div class="empty-state">
            <p>Hiện tại không có chương trình khuyến mãi nào. Vui lòng quay lại sau nhé!</p>
        </div>
    <?php else: ?>
        <div class="coupon-grid">
            <?php foreach ($all_coupons as $cp): ?>
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
                                    if ($cp['discount_type'] == 0) {
                                        echo "Giảm " . $cp['discount_value'] . "%";
                                        if ($cp['max_discount_amount'] > 0) {
                                            echo " (Tối đa " . formatMoney($cp['max_discount_amount']) . ")";
                                        }
                                    } else {
                                        echo "Giảm " . formatMoney($cp['discount_value']);
                                    }
                                ?>
                            </div>
                            <div class="c-desc">Đơn Tối Thiểu <?= formatMoney($cp['min_order_value']) ?></div>
                            <div class="c-desc">HSD: <?= date('d/m/Y', strtotime($cp['end_date'])) ?></div>
                        </div>
                        <div class="c-action">
                            <span class="c-code" id="code-<?= $cp['coupon_id'] ?>"><?= htmlspecialchars($cp['code']) ?></span>
                            
                            <button class="c-btn-save" onclick="copyCoupon('<?= htmlspecialchars($cp['code']) ?>', this)">
                                Sao chép mã
                            </button>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function copyCoupon(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
        const orig = btn.textContent;
        btn.textContent = '✓ Đã sao chép!';
        btn.style.background = '#2e7d32';
        setTimeout(() => {
            btn.textContent = orig;
            btn.style.background = '';
        }, 2000);
    }).catch(() => {
        // Fallback cho trình duyệt cũ
        const el = document.createElement('textarea');
        el.value = code;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        const orig = btn.textContent;
        btn.textContent = '✓ Đã sao chép!';
        setTimeout(() => { btn.textContent = orig; }, 2000);
    });
}
</script>