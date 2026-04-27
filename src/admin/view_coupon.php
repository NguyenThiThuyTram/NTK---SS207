<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';
if (empty($id)) { header("Location: coupons.php"); exit; }

// Truy vấn thông tin chi tiết voucher
$stmt = $conn->prepare("SELECT * FROM coupons WHERE coupon_id = ?");
$stmt->execute([$id]);
$cp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cp) { echo "Không tìm thấy voucher."; exit; }

$admin_current_page = 'coupons.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    .view-container { padding: 30px; background: #fdfdfb; min-height: 100vh; font-family: "Helvetica Neue", Arial; }
    .header-back { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #888; font-size: 14px; margin-bottom: 20px; }
    .header-back:hover { color: #111; }
    
    .coupon-detail-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e5e5;
        max-width: 800px;
        overflow: hidden;
    }
    .detail-header {
        background: #fafaf8;
        padding: 30px;
        border-bottom: 1px solid #e5e5e5;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .detail-body { padding: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
    
    .info-group { margin-bottom: 20px; }
    .info-label { font-size: 11px; font-weight: 700; color: #aaa; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block; }
    .info-value { font-size: 16px; color: #111; font-weight: 500; }
    
    .promo-box {
        background: #2f1c00;
        color: #fff;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    .promo-code { font-family: monospace; font-size: 24px; font-weight: 700; letter-spacing: 2px; }
</style>

<div class="view-container">
    <a href="coupons.php" class="header-back"><i class="fa-solid fa-arrow-left"></i> QUAY LẠI DANH SÁCH</a>
    
    <div class="coupon-detail-card">
        <div class="detail-header">
            <div>
                <h2 style="margin:0; font-size: 20px; font-weight: 700;">CHI TIẾT VOUCHER</h2>
                <span style="color: #27ae60; font-size: 13px; font-weight: 600;">● Đang trong thời gian áp dụng</span>
            </div>
            <a href="update_coupon.php?id=<?= $cp['coupon_id'] ?>" style="color: #2f1c00; font-weight: 600; text-decoration: none; border-bottom: 1px solid;">Chỉnh sửa</a>
        </div>

        <div class="detail-body">
            <div>
                <div class="info-group">
                    <span class="info-label">Mã định danh (ID)</span>
                    <span class="info-value">#<?= $cp['coupon_id'] ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Loại ưu đãi</span>
                    <span class="info-value"><?= $cp['discount_type'] == 0 ? 'Giảm theo phần trăm (%)' : 'Giảm số tiền cố định (₫)' ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Giá trị giảm</span>
                    <span class="info-value" style="color: #c0392b; font-weight: 700; font-size: 20px;">
                        <?= $cp['discount_type'] == 0 ? (int)$cp['discount_value'].'%' : number_format($cp['discount_value']).'₫' ?>
                    </span>
                </div>
                <div class="info-group">
                    <span class="info-label">Điều kiện đơn tối thiểu</span>
                    <span class="info-value"><?= number_format($cp['min_order_value']) ?>₫</span>
                </div>
            </div>

            <div>
                <div class="promo-box">
                    <span class="info-label" style="color: rgba(255,255,255,0.6);">MÃ KHUYẾN MÃI</span>
                    <div class="promo-code"><?= $cp['code'] ?></div>
                </div>
                
                <div class="info-group" style="margin-top: 25px;">
                    <span class="info-label">Thời hạn sử dụng</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($cp['end_date'])) ?></span>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Tình trạng số lượng</span>
                    <span class="info-value">Đã dùng <?= $cp['used_count'] ?> / Tổng <?= $cp['quantity'] ?> lượt</span>
                </div>
            </div>
        </div>
    </div>
</div>