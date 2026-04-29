<?php
// Đây là sub-view được include bởi dashboard.php
$user_id = $_SESSION['user_id'] ?? null;
global $conn;

$coupons = [];

if (isset($conn) && $conn !== null) {
    try {
        // Lấy tất cả voucher còn hiệu lực: status=1 và end_date chưa hết (hoặc NULL = không giới hạn)
        $sql = "SELECT coupon_id, code, discount_type, discount_value,
                       min_order_value, max_discount_amount, start_date, end_date,
                       quantity, used_count, status
                FROM coupons
                WHERE status = 1
                  AND (end_date IS NULL OR end_date >= NOW())
                ORDER BY end_date IS NULL DESC, end_date ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $coupons = [];
        // Debug: bỏ comment dòng dưới nếu muốn xem lỗi
        // error_log('Coupon query error: ' . $e->getMessage());
    }
}

// Format tiền
if (!function_exists('fmtMoney')) {
    function fmtMoney($amount) {
        return number_format((float)$amount, 0, ',', '.') . 'đ';
    }
}
?>

<style>
    .coupon-wrapper {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: #111;
    }

    .coupon-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e5e5;
    }
    .coupon-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #111;
        letter-spacing: 0.3px;
    }
    .coupon-count {
        font-size: 13px;
        color: #888;
        background: #f5f1eb;
        padding: 4px 12px;
        border-radius: 20px;
    }

    /* Grid */
    .coupon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 18px;
    }

    /* Card */
    .coupon-card {
        display: flex;
        background: #fff;
        border: 1px solid #e8e8e8;
        border-radius: 10px;
        overflow: hidden;
        transition: box-shadow 0.2s, transform 0.2s;
        position: relative;
    }
    .coupon-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    /* Cột trái */
    .coupon-left {
        background: #2f1c00;
        color: #f5f1eb;
        width: 88px;
        min-width: 88px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 14px 10px;
        border-right: 2px dashed rgba(255,255,255,0.3);
        gap: 4px;
    }
    .coupon-left .c-icon {
        font-size: 22px;
        margin-bottom: 4px;
        opacity: 0.9;
    }
    .coupon-left .c-pct {
        font-size: 20px;
        font-weight: 800;
        line-height: 1;
    }
    .coupon-left .c-type {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        opacity: 0.8;
    }

    /* Cột phải */
    .coupon-right {
        flex: 1;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 8px;
    }

    .c-title {
        font-size: 15px;
        font-weight: 700;
        color: #111;
        margin: 0 0 2px 0;
    }
    .c-desc-text {
        font-size: 12.5px;
        color: #777;
        margin-bottom: 2px;
    }
    .c-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        font-size: 12px;
        color: #666;
    }
    .c-meta span {
        background: #f5f1eb;
        padding: 2px 8px;
        border-radius: 12px;
    }
    .c-expire {
        background: #fff4e5 !important;
        color: #b05000 !important;
    }

    /* Footer row */
    .c-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px dashed #eee;
        margin-top: 4px;
    }
    .c-code-tag {
        font-family: monospace;
        font-size: 14px;
        font-weight: 700;
        color: #2f1c00;
        letter-spacing: 1px;
        background: #f5f1eb;
        padding: 4px 10px;
        border-radius: 4px;
        border: 1px dashed #c0a878;
        cursor: pointer;
        user-select: none;
        transition: background 0.15s;
    }
    .c-code-tag:hover { background: #ede5d5; }

    .c-copy-btn {
        background: #2f1c00;
        color: #fff;
        border: none;
        padding: 6px 14px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: opacity 0.2s;
    }
    .c-copy-btn:hover { opacity: 0.85; }
    .c-copy-btn.copied {
        background: #27ae60;
    }

    /* Badge "Không giới hạn" */
    .badge-unlimited {
        position: absolute;
        top: 8px;
        right: 8px;
        font-size: 10px;
        background: #111;
        color: #fff;
        padding: 2px 7px;
        border-radius: 10px;
        letter-spacing: 0.3px;
    }

    /* Empty state */
    .coupon-empty {
        text-align: center;
        padding: 60px 20px;
        background: #f5f1eb;
        border-radius: 10px;
        border: 1px dashed #ddd;
        color: #888;
        font-size: 15px;
    }
    .coupon-empty i {
        font-size: 36px;
        margin-bottom: 12px;
        display: block;
        opacity: 0.4;
    }

    /* Toast */
    #coupon-toast {
        position: fixed;
        bottom: 28px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        background: #2f1c00;
        color: #fff;
        padding: 10px 22px;
        border-radius: 24px;
        font-size: 13.5px;
        font-weight: 500;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s, transform 0.3s;
        z-index: 9999;
    }
    #coupon-toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
</style>

<div class="coupon-wrapper">
    <div class="coupon-header">
        <h2><i class="fa-solid fa-ticket" style="margin-right:8px;color:#2f1c00;"></i>Kho Voucher của bạn</h2>
        <?php if (!empty($coupons)): ?>
            <span class="coupon-count"><?= count($coupons) ?> mã khả dụng</span>
        <?php endif; ?>
    </div>

    <?php if (empty($coupons)): ?>
        <div class="coupon-empty">
            <i class="fa-solid fa-ticket-simple"></i>
            Hiện tại chưa có voucher nào đang hoạt động.
        </div>

    <?php else: ?>
        <div class="coupon-grid">
            <?php foreach ($coupons as $cp):
                $isPercent    = ($cp['discount_type'] == 0);
                $val          = $cp['discount_value'];
                $noExpiry     = empty($cp['end_date']);
                $expireStr    = $noExpiry ? 'Không giới hạn' : date('d/m/Y', strtotime($cp['end_date']));

                if ($isPercent) {
                    $title = 'Giảm ' . intval($val) . '%';
                    if (!empty($cp['max_discount_amount']) && $cp['max_discount_amount'] > 0) {
                        $title .= ' (Tối đa ' . fmtMoney($cp['max_discount_amount']) . ')';
                    }
                } else {
                    $title = 'Giảm ' . fmtMoney($val);
                }
            ?>
            <div class="coupon-card">
                <?php if ($noExpiry): ?>
                    <span class="badge-unlimited">Không giới hạn</span>
                <?php endif; ?>

                <div class="coupon-left">
                    <i class="fa-solid fa-tag c-icon"></i>
                    <?php if ($isPercent): ?>
                        <div class="c-pct"><?= intval($val) ?>%</div>
                        <div class="c-type">Giảm giá</div>
                    <?php else: ?>
                        <div class="c-pct" style="font-size:13px;"><?= fmtMoney($val) ?></div>
                        <div class="c-type">Cố định</div>
                    <?php endif; ?>
                </div>

                <div class="coupon-right">
                    <div>
                        <div class="c-title"><?= htmlspecialchars($title) ?></div>
                        <div class="c-meta">
                            <span>Đơn tối thiểu: <?= fmtMoney($cp['min_order_value'] ?? 0) ?></span>
                            <?php if (!$noExpiry): ?>
                                <span class="c-expire"><i class="fa-regular fa-clock" style="margin-right:3px;"></i>HSD: <?= $expireStr ?></span>
                            <?php endif; ?>
                            <?php if (!empty($cp['quantity'])): ?>
                                <span>Còn: <?= $cp['quantity'] - ($cp['used_count'] ?? 0) ?> lượt</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="c-footer">
                        <span class="c-code-tag" onclick="copyCoupon('<?= htmlspecialchars($cp['code']) ?>', this)">
                            <?= htmlspecialchars($cp['code']) ?>
                        </span>
                        <button class="c-copy-btn" onclick="copyCoupon('<?= htmlspecialchars($cp['code']) ?>', this)" id="btn-<?= $cp['coupon_id'] ?>">
                            <i class="fa-regular fa-copy"></i> Sao chép
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Toast notification -->
<div id="coupon-toast">✓ Đã sao chép mã!</div>

<script>
function copyCoupon(code, el) {
    navigator.clipboard.writeText(code).then(function() {
        // Show toast
        const toast = document.getElementById('coupon-toast');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2200);

        // Highlight button
        const btn = document.getElementById('btn-' + code) || el.closest('.c-footer')?.querySelector('.c-copy-btn');
        if (btn) {
            btn.classList.add('copied');
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Đã chép!';
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.innerHTML = '<i class="fa-regular fa-copy"></i> Sao chép';
            }, 2000);
        }
    }).catch(function() {
        // Fallback cho trình duyệt cũ
        const tmp = document.createElement('input');
        tmp.value = code;
        document.body.appendChild(tmp);
        tmp.select();
        document.execCommand('copy');
        document.body.removeChild(tmp);
        alert('Đã sao chép mã: ' + code);
    });
}
</script>