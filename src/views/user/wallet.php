<?php
// Đảm bảo chạy trong dashboard.php
$user_id = $_SESSION['user_id'] ?? null;
global $conn;

$current_balance = 0;
$transactions = [];

if (isset($conn) && $conn !== null && $user_id) {
    try {
        // 1. Tính tổng số dư hiện tại từ bảng Wallet_Transactions
        // (Quy ước theo SQL của đại ca: transaction_type = 1 là cộng tiền hoàn, 2 là trừ tiền thanh toán)
        $stmt_balance = $conn->prepare("
            SELECT SUM(CASE WHEN transaction_type = 1 THEN amount ELSE -amount END) as balance 
            FROM Wallet_Transactions 
            WHERE user_id = :user_id
        ");
        $stmt_balance->execute(['user_id' => $user_id]);
        $balance_result = $stmt_balance->fetch(PDO::FETCH_ASSOC);
        $current_balance = $balance_result['balance'] ?? 0;

        // 2. Lấy lịch sử giao dịch (JOIN 2 bảng Users và Wallet_Transactions theo đúng lệnh đại ca)
        $stmt_tx = $conn->prepare("
            SELECT wt.amount, wt.transaction_type, wt.description, wt.created_at 
            FROM Wallet_Transactions wt
            JOIN Users u ON wt.user_id = u.user_id
            WHERE u.user_id = :user_id
            ORDER BY wt.created_at DESC
        ");
        $stmt_tx->execute(['user_id' => $user_id]);
        $transactions = $stmt_tx->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Bắt lỗi nếu lỡ tên cột trong DB có khác biệt
        echo "<script>console.error('Lỗi DB: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Hàm format tiền tệ
if (!function_exists('formatVND')) {
    function formatVND($amount) {
        return number_format((float)$amount, 0, ',', '.') . 'đ';
    }
}
?>

<style>
    /* Giao diện tổng thể - Giữ chuẩn Font Helvetica Neue & Tone màu đã chốt */
    .wallet-wrapper {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #ffffff;
        color: #111111;
        max-width: 800px;
    }
    
    .wallet-header {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #111111;
    }

    /* Thẻ Số Dư (Nền Beige cực mượt) */
    .balance-card {
        background-color: #f5f1eb; 
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 30px;
    }
    .balance-label {
        font-size: 15px;
        color: #555555;
        margin-bottom: 5px;
    }
    .balance-amount {
        font-size: 32px;
        font-weight: 700;
        color: #2f1c00; /* Nâu đậm */
        margin-bottom: 10px;
    }
    .balance-desc {
        font-size: 14px;
        color: #666666;
        line-height: 1.5;
    }

    /* Phần Lịch sử giao dịch */
    .tx-section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #111111;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e5e5;
    }

    .tx-list {
        display: flex;
        flex-direction: column;
    }

    .tx-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #f0f0f0;
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
        color: #111111;
    }
    .tx-date {
        font-size: 13px;
        color: #888888;
    }

    .tx-amount {
        font-size: 16px;
        font-weight: 600;
    }
    .tx-amount.plus {
        color: #28a745; /* Xanh lá cho giao dịch hoàn tiền */
    }
    .tx-amount.minus {
        color: #111111; /* Đen cho giao dịch xài tiền */
    }

    /* Trạng thái khi Ví rỗng */
    .tx-empty {
        text-align: center;
        padding: 40px 20px;
        color: #757575;
        background-color: #fafafa;
        border: 1px dashed #e5e5e5;
        border-radius: 8px;
        font-size: 15px;
    }
</style>

<div class="wallet-wrapper">
    <div class="wallet-header">Ví hoàn tiền</div>

    <div class="balance-card">
        <div class="balance-label">Số dư hiện tại</div>
        <div class="balance-amount"><?= formatVND($current_balance) ?></div>
        <div class="balance-desc">Số dư từ hoàn tiền đơn hàng có thể sử dụng để thanh toán trực tiếp khi mua sắm.</div>
    </div>

    <div class="tx-section-title">Lịch sử giao dịch</div>
    
    <?php if (empty($transactions)): ?>
        <div class="tx-empty">
            Chưa có giao dịch nào.
        </div>
    <?php else: ?>
        <div class="tx-list">
            <?php foreach ($transactions as $tx): ?>
                <div class="tx-item">
                    <div class="tx-info-left">
                        <span class="tx-title"><?= htmlspecialchars($tx['description'] ?? 'Giao dịch') ?></span>
                        <span class="tx-date"><?= date('d/m/Y - H:i', strtotime($tx['created_at'])) ?></span>
                    </div>
                    
                    <?php if ($tx['transaction_type'] == 1): ?>
                        <div class="tx-amount plus">+<?= formatVND($tx['amount']) ?></div>
                    <?php else: ?>
                        <div class="tx-amount minus">-<?= formatVND($tx['amount']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>