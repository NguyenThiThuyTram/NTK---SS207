<?php
session_start();
require_once 'config/database.php';

$order_id = $_GET['id'] ?? '';
$method   = $_GET['method'] ?? 'cod'; // 'online' hoặc 'cod'

if (!$order_id) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM Orders WHERE order_id = :oid");
$stmt->execute(['oid' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Không tìm thấy đơn hàng.";
    exit;
}

// Lấy chi tiết sản phẩm trong đơn hàng
$stmt_items = $conn->prepare("
    SELECT od.order_id, od.variant_id, od.product_name, od.quantity, od.price,
           v.color, v.size FROM Order_Details od
    LEFT JOIN Product_Variants v ON od.variant_id = v.variant_id
    WHERE od.order_id = :oid
");
$stmt_items->execute(['oid' => $order_id]);
$order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

$is_online  = ($order['payment_method'] == 2);
$is_paid    = ($order['payment_status'] == 1);
$has_qr     = (!empty($order['payos_qr_code']));
$checkout_url = $order['payos_checkout_url'] ?? '';

$page_title = "Đặt Hàng Thành Công";
include 'includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    .success-page {
        max-width: 720px;
        margin: 50px auto;
        padding: 0 20px 80px;
        font-family: 'Inter', sans-serif;
    }

    /* Breadcrumb */
    .success-breadcrumb {
        text-align: center;
        font-size: 12px;
        color: #aaa;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-bottom: 40px;
    }

    /* Card chính */
    .success-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 30px rgba(0,0,0,0.07);
        overflow: hidden;
    }

    /* Header card */
    .success-card-header {
        background: linear-gradient(135deg, #2f1c00 0%, #5a3500 100%);
        padding: 35px 40px;
        text-align: center;
        color: #fff;
    }
    .success-card-header .icon-wrap {
        width: 70px; height: 70px;
        background: rgba(255,255,255,0.15);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 18px;
        backdrop-filter: blur(4px);
    }
    .success-card-header .icon-wrap i { font-size: 32px; }
    .success-card-header h1 { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
    .success-card-header p { font-size: 14px; opacity: 0.8; }

    /* Body card */
    .success-card-body { padding: 35px 40px; }

    /* Order number badge */
    .order-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: #fdf6ec; border: 1px solid #f0d9b5;
        border-radius: 8px; padding: 10px 20px;
        font-size: 15px; color: #5a3500; font-weight: 600;
        margin-bottom: 28px;
    }

    /* Section title */
    .section-label {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #aaa;
        margin-bottom: 14px;
    }

    /* Thông tin đơn hàng */
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 28px; }
    .info-item { background: #faf9f6; border-radius: 10px; padding: 14px 18px; }
    .info-item .label { font-size: 11px; color: #aaa; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
    .info-item .value { font-size: 14px; color: #333; font-weight: 500; }

    /* Sản phẩm */
    .product-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }
    .product-row { display: flex; align-items: center; gap: 14px; padding: 12px; background: #faf9f6; border-radius: 10px; }
    .product-row .prod-name { flex: 1; font-size: 14px; font-weight: 500; color: #333; }
    .product-row .prod-variant { font-size: 12px; color: #999; }
    .product-row .prod-price { font-size: 14px; font-weight: 600; color: #2f1c00; }

    /* Tổng tiền */
    .price-breakdown { border-top: 1px solid #f0eee9; padding-top: 18px; margin-bottom: 28px; }
    .price-row { display: flex; justify-content: space-between; font-size: 14px; color: #666; margin-bottom: 10px; }
    .price-row.total { font-size: 17px; font-weight: 700; color: #2f1c00; border-top: 1px solid #f0eee9; padding-top: 14px; margin-top: 4px; }

    /* === QR BOX === */
    .qr-box {
        border: 2px dashed #e0d5c8;
        border-radius: 16px;
        padding: 30px 24px;
        text-align: center;
        margin-bottom: 28px;
        background: #fdfaf6;
        position: relative;
    }
    .qr-box h3 { font-size: 16px; font-weight: 600; color: #2f1c00; margin-bottom: 6px; }
    .qr-box p  { font-size: 13px; color: #888; margin-bottom: 20px; line-height: 1.6; }
    .qr-image  {
        width: 220px; height: 220px;
        border-radius: 12px;
        border: 6px solid #fff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        margin: 0 auto 18px;
        display: block;
    }
    .qr-amount {
        display: inline-block;
        background: #2f1c00; color: #fff;
        padding: 10px 24px; border-radius: 30px;
        font-size: 18px; font-weight: 700;
        margin-bottom: 14px;
    }
    .qr-status-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 18px; border-radius: 20px;
        background: #fff3e0; color: #e65100;
        font-size: 13px; font-weight: 500;
    }
    .qr-status-badge.paid {
        background: #e8f5e9; color: #2e7d32;
    }
    .open-payos-btn {
        display: inline-flex; align-items: center; gap: 8px;
        margin-top: 16px;
        padding: 11px 24px; border-radius: 8px;
        background: #fff; border: 1.5px solid #2f1c00;
        color: #2f1c00; font-size: 13px; font-weight: 600;
        text-decoration: none; transition: 0.2s;
    }
    .open-payos-btn:hover { background: #2f1c00; color: #fff; }

    /* === SUCCESS PAID BOX === */
    .paid-box {
        padding: 28px;
        border-radius: 14px;
        background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
        text-align: center;
        margin-bottom: 28px;
        border: 1px solid #c8e6c9;
    }
    .paid-box i { font-size: 44px; color: #4CAF50; margin-bottom: 12px; }
    .paid-box h3 { font-size: 18px; font-weight: 700; color: #1b5e20; margin-bottom: 6px; }
    .paid-box p  { font-size: 14px; color: #388e3c; }

    /* === COD BOX === */
    .cod-box {
        padding: 24px;
        border-radius: 14px;
        background: #fdf6ec;
        margin-bottom: 28px;
        border: 1px solid #f0d9b5;
        display: flex; align-items: flex-start; gap: 14px;
    }
    .cod-box i { font-size: 26px; color: #f59e0b; margin-top: 2px; }
    .cod-box h4 { font-size: 15px; font-weight: 600; color: #78350f; margin-bottom: 5px; }
    .cod-box p  { font-size: 13px; color: #92400e; line-height: 1.5; }

    /* CTA buttons */
    .cta-buttons { display: flex; gap: 14px; justify-content: center; margin-top: 8px; }
    .cta-btn {
        padding: 13px 28px; border-radius: 10px;
        font-size: 14px; font-weight: 600; text-decoration: none;
        display: flex; align-items: center; gap: 8px; transition: 0.2s;
    }
    .cta-btn.primary { background: #2f1c00; color: #fff; box-shadow: 0 4px 14px rgba(47,28,0,0.25); }
    .cta-btn.primary:hover { background: #1a0f00; transform: translateY(-1px); }
    .cta-btn.secondary { background: #fff; color: #333; border: 1.5px solid #ddd; }
    .cta-btn.secondary:hover { background: #f5f5f5; }

    /* Divider */
    .divider { border: none; border-top: 1px solid #f0eee9; margin: 22px 0; }

    /* Animated paid overlay */
    #success-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center; justify-content: center;
    }
    #success-overlay.show { display: flex; }
    .overlay-card {
        background: #fff; border-radius: 20px; padding: 50px 40px;
        text-align: center; max-width: 360px; width: 90%;
        animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes popIn {
        from { transform: scale(0.7); opacity: 0; }
        to   { transform: scale(1);   opacity: 1; }
    }
    .overlay-card i { font-size: 60px; color: #4CAF50; margin-bottom: 16px; }
    .overlay-card h2 { font-size: 22px; font-weight: 700; color: #1b5e20; margin-bottom: 8px; }
    .overlay-card p  { color: #555; font-size: 14px; margin-bottom: 24px; }
    .overlay-close-btn {
        display: inline-block; padding: 12px 30px; background: #2f1c00;
        color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600;
        cursor: pointer; border: none; font-size: 14px;
    }
</style>

<!-- Overlay thành công (cho online payment) -->
<div id="success-overlay">
    <div class="overlay-card">
        <i class="fa-solid fa-circle-check"></i>
        <h2>Thanh toán thành công!</h2>
        <p>Hệ thống đã ghi nhận khoản thanh toán của bạn. Đơn hàng đang được xử lý.</p>
        <button class="overlay-close-btn" onclick="closeOverlay()">Xem chi tiết đơn hàng</button>
    </div>
</div>

<div class="success-page">
    <div class="success-breadcrumb">NTK Fashion &nbsp;/&nbsp; Đặt hàng thành công</div>

    <div class="success-card">
        <!-- Header -->
        <div class="success-card-header">
            <div class="icon-wrap">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <h1>Cảm ơn bạn đã đặt hàng!</h1>
            <p>Đơn hàng của bạn đã được ghi nhận và đang được xử lý.</p>
        </div>

        <!-- Body -->
        <div class="success-card-body">

            <!-- Mã đơn hàng -->
            <div style="text-align:center; margin-bottom: 28px;">
                <div class="order-badge">
                    <i class="fa-solid fa-hashtag" style="font-size:13px;"></i>
                    Mã đơn hàng: <?= htmlspecialchars($order_id) ?>
                </div>
            </div>

            <!-- Thông tin giao hàng -->
            <div class="section-label">Thông tin giao hàng</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Người nhận</div>
                    <div class="value"><?= htmlspecialchars($order['fullname'] ?? '') ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Số điện thoại</div>
                    <div class="value"><?= htmlspecialchars($order['phone'] ?? '') ?></div>
                </div>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <div class="label">Địa chỉ</div>
                    <div class="value"><?= htmlspecialchars($order['address'] ?? '') ?></div>
                </div>
            </div>

            <!-- Sản phẩm -->
            <?php if (!empty($order_items)): ?>
            <div class="section-label">Sản phẩm đã đặt</div>
            <div class="product-list">
                <?php foreach ($order_items as $item): ?>
                <div class="product-row">
                    <div>
                        <div class="prod-name"><?= htmlspecialchars($item['product_name'] ?? '') ?></div>
                        <div class="prod-variant">
                            <?php
                                $variant_info = [];
                                if (!empty($item['color'])) $variant_info[] = $item['color'];
                                if (!empty($item['size']))  $variant_info[] = $item['size'];
                                echo htmlspecialchars(implode(' · ', $variant_info));
                            ?>
                            &nbsp;&times; <?= intval($item['quantity']) ?>
                        </div>
                    </div>
                    <div class="prod-price">
                        <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> VNĐ
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Tổng tiền -->
            <div class="price-breakdown">
                <div class="price-row">
                    <span>Giá tạm tính</span>
                    <span><?= number_format($order['total_price'] - $order['shipping_fee'], 0, ',', '.') ?> VNĐ</span>
                </div>
                <div class="price-row">
                    <span>Phí vận chuyển</span>
                    <span><?= number_format($order['shipping_fee'], 0, ',', '.') ?> VNĐ</span>
                </div>
                <?php if ($order['wallet_used_amount'] > 0): ?>
                <div class="price-row" style="color:#d32f2f;">
                    <span>Dùng ví nội bộ</span>
                    <span>-<?= number_format($order['wallet_used_amount'], 0, ',', '.') ?> VNĐ</span>
                </div>
                <?php endif; ?>
                <div class="price-row total">
                    <span>TỔNG THANH TOÁN</span>
                    <span><?= number_format($order['final_price'], 0, ',', '.') ?> VNĐ</span>
                </div>
            </div>

            <hr class="divider">

            <!-- ===== PHẦN THANH TOÁN ===== -->

            <?php if ($is_online && !$is_paid && $has_qr): ?>
                <!-- ONLINE: Chưa thanh toán — hiện QR -->
                <div class="qr-box" id="qr-section">
                    <h3><i class="fa-solid fa-qrcode" style="margin-right:6px;"></i>Quét mã QR để thanh toán</h3>
                    <p>Mở ứng dụng ngân hàng và quét mã QR bên dưới.<br>
                       Hệ thống sẽ tự động cập nhật sau khi nhận được tiền.</p>

                    <?php
                        // PayOS trả về payos_qr_code là CHUỖI dữ liệu QR (không phải ảnh)
                        // Dùng API qrserver.com để render thành ảnh PNG
                        $qr_data = $order['payos_qr_code'];
                    ?>
                    <img class="qr-image"
                         src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=15&data=<?= urlencode($qr_data) ?>"
                         alt="PayOS QR Code">

                    <div class="qr-amount"><?= number_format($order['final_price'], 0, ',', '.') ?> VNĐ</div>

                    <div>
                        <div class="qr-status-badge" id="status-badge">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                            Đang chờ thanh toán...
                        </div>
                    </div>

                    <?php if (!empty($checkout_url)): ?>
                    <div>
                        <a href="<?= htmlspecialchars($checkout_url) ?>" target="_blank" class="open-payos-btn">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            Mở trang thanh toán PayOS
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <script>
                const orderId = <?= json_encode($order_id) ?>;
                let pollCount = 0;
                const maxPolls = 120; // Poll tối đa 6 phút (120 x 3s)

                const intervalId = setInterval(function() {
                    pollCount++;
                    if (pollCount > maxPolls) {
                        clearInterval(intervalId);
                        return;
                    }

                    fetch('api/check_payment_status.php?id=' + orderId)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.paid) {
                                clearInterval(intervalId);
                                // Hiện overlay thành công
                                document.getElementById('success-overlay').classList.add('show');
                                // Cập nhật badge
                                const badge = document.getElementById('status-badge');
                                badge.className = 'qr-status-badge paid';
                                badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Đã thanh toán!';
                            }
                        })
                        .catch(e => console.error('Poll error:', e));
                }, 3000);
                </script>

            <?php elseif ($is_online && $is_paid): ?>
                <!-- ONLINE: Đã thanh toán xong -->
                <div class="paid-box">
                    <div><i class="fa-solid fa-circle-check"></i></div>
                    <h3>Thanh toán thành công!</h3>
                    <p>Khoản thanh toán đã được xác nhận. Đơn hàng đang được chuẩn bị giao đến bạn.</p>
                </div>

            <?php else: ?>
                <!-- COD hoặc ví thanh toán đủ -->
                <div class="cod-box">
                    <i class="fa-solid fa-truck"></i>
                    <div>
                        <h4>Đặt hàng thành công!</h4>
                        <p>
                            <?php if ($order['payment_method'] == 1): ?>
                                Bạn sẽ thanh toán khi nhận hàng (COD). Đơn hàng sẽ được giao trong 3–5 ngày làm việc.
                            <?php else: ?>
                                Đơn hàng đã được thanh toán bằng ví nội bộ. Vui lòng chờ nhận hàng.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

            <?php endif; ?>

            <!-- CTA buttons -->
            <div class="cta-buttons">
                <a href="index.php" class="cta-btn secondary">
                    <i class="fa-solid fa-arrow-left" style="font-size:12px;"></i>
                    Tiếp tục mua sắm
                </a>
                <a href="views/user/dashboard.php?view=chitietdonhang&id=<?= $order_id ?>" class="cta-btn primary">
                    <i class="fa-solid fa-box"></i>
                    Theo dõi đơn hàng
                </a>
            </div>

        </div><!-- end card-body -->
    </div><!-- end success-card -->
</div>

<script>
function closeOverlay() {
    document.getElementById('success-overlay').classList.remove('show');
    // Reload để cập nhật trạng thái đã thanh toán
    location.reload();
}
</script>

<?php include 'includes/footer.php'; ?>
