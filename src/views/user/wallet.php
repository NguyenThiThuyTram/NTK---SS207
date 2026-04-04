<?php
// ==========================================
// DUMMY DATA - Dữ liệu giả lập cho Ví hoàn tiền
// (Sau này đại ca xóa phần này đi và dùng $conn -> prepare() để kéo từ DB ra nhé)
// ==========================================

$current_balance = 150000; // Số dư hiện tại (150k)

// Lịch sử giao dịch giả lập
$transactions = [
    [
        'id' => 'TX001',
        'title' => 'Hoàn tiền đơn DH002',
        'date' => '2026-02-26 14:30:00',
        'amount' => 150000,
        'type' => 'plus' // Cộng tiền
    ],
    [
        'id' => 'TX002',
        'title' => 'Sử dụng ví thanh toán đơn DH005',
        'date' => '2026-03-01 09:15:00',
        'amount' => 50000,
        'type' => 'minus' // Trừ tiền
    ],
    [
        'id' => 'TX003',
        'title' => 'Hoàn tiền đánh giá sản phẩm áo thun',
        'date' => '2026-03-10 20:00:00',
        'amount' => 20000,
        'type' => 'plus'
    ]
];

// Hàm format tiền tệ
if (!function_exists('formatVND')) {
    function formatVND($amount) {
        return number_format($amount, 0, ',', '.') . ' đ';
    }
}
?>

<style>
    .wallet-header {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
        color: var(--text-main, #111);
    }

    /* Thẻ số dư */
    .balance-card {
        background: linear-gradient(135deg, #2f1c00 0%, #4a2c00 100%);
        color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(47, 28, 0, 0.15);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    /* Hiệu ứng trang trí góc thẻ */
    .balance-card::after {
        content: '';
        position: absolute;
        right: -30px;
        top: -30px;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .balance-label {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 10px;
    }

    .balance-amount {
        font-size: 36px;
        font-weight: bold;
        margin-bottom: 15px;
    }

    .balance-desc {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 15px;
    }

    /* Lịch sử giao dịch */
    .tx-section-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .tx-list {
        display: flex;
        flex-direction: column;
        border: 1px solid var(--border-color, #e5e5e5);
        border-radius: 8px;
        background: #fff;
    }

    .tx-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color, #e5e5e5);
    }

    .tx-item:last-child {
        border-bottom: none;
    }

    .tx-info-left {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .tx-title {
        font-size: 15px;
        font-weight: 500;
        color: var(--text-main, #333);
    }

    .tx-date {
        font-size: 13px;
        color: var(--text-muted, #757575);
    }

    .tx-amount {
        font-size: 15px;
        font-weight: 600;
    }

    .tx-amount.plus { color: #28a745; } /* Xanh lá */
    .tx-amount.minus { color: #dc3545; } /* Đỏ */
</style>

<div class="wallet-container">
    <div class="wallet-header">Ví hoàn tiền</div>

    <div class="balance-card">
        <div class="balance-label">Số dư hiện tại</div>
        <div class="balance-amount"><?= formatVND($current_balance) ?></div>
        <div class="balance-desc">Số dư từ hoàn tiền đơn hàng có thể sử dụng để thanh toán trực tiếp khi mua sắm.</div>
    </div>

    <div class="tx-section-title">Lịch sử giao dịch</div>
    
    <?php if (empty($transactions)): ?>
        <div style="text-align: center; padding: 40px; color: #757575; border: 1px dashed #ccc; border-radius: 8px;">
            Chưa có giao dịch nào.
        </div>
    <?php else: ?>
        <div class="tx-list">
            <?php foreach ($transactions as $tx): ?>
                <div class="tx-item">
                    <div class="tx-info-left">
                        <span class="tx-title"><?= htmlspecialchars($tx['title']) ?></span>
                        <span class="tx-date"><?= date('d/m/Y - H:i', strtotime($tx['date'])) ?></span>
                    </div>
                    <div class="tx-amount <?= $tx['type'] ?>">
                        <?= ($tx['type'] == 'plus' ? '+' : '-') . formatVND($tx['amount']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>