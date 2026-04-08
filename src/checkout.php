<?php
require_once 'config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: views/login.php?redirect=../checkout.php");
    exit;
}

// 1. Lấy sản phẩm đang ĐƯỢC CHỌN trong giỏ hàng
$sql = "SELECT c.cart_id, c.quantity, 
               v.variant_id, v.color, v.size, v.original_price, v.sale_price,
               p.product_id, p.name AS product_name, p.image
        FROM Cart c
        JOIN Product_Variants v ON c.variant_id = v.variant_id
        JOIN Products p ON v.product_id = p.product_id
        WHERE c.user_id = :uid AND c.is_selected = 1";
$stmt = $conn->prepare($sql);
$stmt->execute(['uid' => $user_id]);
$checkout_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($checkout_items) === 0) {
    echo "<script>alert('Bạn chưa chọn sản phẩm nào để thanh toán!'); window.location.href='cart.php';</script>";
    exit;
}

// Lấy thông tin user để điền sẵn vào form
$stmt_user = $conn->prepare("SELECT * FROM Users WHERE user_id = :uid");
$stmt_user->execute(['uid' => $user_id]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

// 2. Tính toán cơ bản
$subtotal = 0;
foreach ($checkout_items as $item) {
    $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['original_price'];
    $subtotal += $price * $item['quantity'];
}

$shipping_fee = 35000; // Cố định 35k như yêu cầu

// 3. LẤY SỐ DƯ VÍ THẬT TỪ DATABASE
$stmt_wallet = $conn->prepare("SELECT wallet_balance FROM Users WHERE user_id = :uid");
$stmt_wallet->execute(['uid' => $user_id]);
$user_data = $stmt_wallet->fetch(PDO::FETCH_ASSOC);

// Gán số dư ví thật (nếu không có thì mặc định là 0)
$wallet_balance = $user_data['wallet_balance'] ?? 0;

include 'includes/header.php';
?>

<style>
    
    body { background-color: #fff; }
    .checkout-page { 
        max-width: 1200px; 
        margin: 40px auto; 
        padding: 0 20px; 
        /* ĐÃ SỬA: Đổi sang font Helvetica Neue */
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; 
    }
    
    /* Layout 2 cột */
    .checkout-layout { display: flex; gap: 40px; align-items: flex-start; }
    .checkout-left { flex: 1; }
    .checkout-right { width: 420px; background-color: #faf9f5; padding: 25px; border-radius: 8px; position: sticky; top: 20px; border: 1px solid #f0eee9; }

    /* Tiêu đề & Form */
    .step-title { font-size: 16px; color: #333; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 25px; }
    .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
    .form-col { flex: 1; }
    .form-group { margin-bottom: 20px; }
    .form-group label, .form-col label { 
        display: block; 
        font-size: 13px; 
        color: #777; 
        text-transform: uppercase; 
        margin-bottom: 12px; 
        letter-spacing: 0.5px;
    }
    .form-group input, .form-group select, .form-col input { 
        width: 100%; 
        padding: 14px 15px; /* Tăng khoảng trống bên trong ô */
        border: 1px solid #e0e0e0; /* Màu viền nhạt và tinh tế hơn */
        border-radius: 2px; /* Bo góc cực nhẹ, tạo cảm giác vuông vức */
        font-size: 15px; 
        color: #333;
        outline: none; 
        box-sizing: border-box; 
        transition: border-color 0.3s ease;
    }
    .form-group input:focus, .form-col input:focus { 
        border-color: #999; /* Đổi màu viền khi click chuột vào */
    }

    /* Phương thức Ship & Thanh toán */
    .method-box { border: 1px solid #ddd; border-radius: 4px; padding: 15px 20px; margin-bottom: 15px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: 0.3s; }
    .method-box.active { border-color: #4caf50; background-color: #f1f8e9; }
    .method-box input[type="radio"] { accent-color: #4caf50; transform: scale(1.2); margin-right: 15px; }
    .method-info { flex: 1; }
    .method-name { font-weight: bold; color: #333; margin-bottom: 5px; }
    .method-desc { font-size: 13px; color: #777; }
    .method-price { font-weight: bold; color: #333; }

    /* Chi tiết chuyển khoản QR */
    .qr-payment-info { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-top: 15px; display: none; }
    .qr-payment-info.show { display: block; }
    .bank-details { background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    .bank-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 10px; color: #555; }
    .bank-row strong { color: #333; }
    .qr-img-box { text-align: center; }
    .qr-img-box img { width: 200px; height: 200px; object-fit: cover; border: 1px solid #eee; border-radius: 8px; padding: 5px; }

    /* Nút điều hướng */
    .step-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }
    .btn-back { background: #fff; border: 1px solid #ddd; padding: 12px 25px; border-radius: 4px; cursor: pointer; color: #333; font-weight: bold; transition: 0.2s; }
    .btn-back:hover { background: #f5f5f5; }
    .btn-next { background: #2f1c00; border: 1px solid #2f1c00; padding: 12px 35px; border-radius: 4px; cursor: pointer; color: #fff; font-weight: bold; transition: 0.2s; flex: 1; margin-left: 20px; text-align: center; }
    .btn-next:hover { background: #1a0f00; }

    /* Các bước ẩn/hiện */
    .checkout-step { display: none; animation: fadeIn 0.4s; }
    .checkout-step.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* Tóm tắt đơn hàng (Cột phải) */
    .summary-title { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 20px; color: #333; }
    .sum-item { display: flex; gap: 15px; margin-bottom: 20px; }
    .sum-item img { width: 65px; height: 80px; object-fit: cover; border-radius: 4px; }
    .sum-item-info { flex: 1; }
    .sum-item-name { font-size: 14px; font-weight: bold; color: #333; margin-bottom: 5px; }
    .sum-item-variant { font-size: 12px; color: #777; margin-bottom: 3px; }
    .sum-item-price { font-size: 14px; color: #333; font-weight: bold; text-align: right; }

    .sum-wallet-box { padding: 15px 0; border-top: 1px dashed #ddd; border-bottom: 1px dashed #ddd; margin: 20px 0; }
    .wallet-title { font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 10px; }
    .wallet-balance { font-size: 14px; font-weight: bold; color: #333; margin-bottom: 10px; }
    .wallet-checkbox { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #333; cursor: pointer; }
    .wallet-checkbox input { accent-color: #2f1c00; width: 16px; height: 16px; }

    .sum-row { display: flex; justify-content: space-between; font-size: 14px; color: #555; margin-bottom: 12px; }
    .sum-total-row { display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; color: #333; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
    .sum-note { font-size: 11px; color: #999; margin-top: 5px; }
    /* Thanh Tiến Trình (Stepper) */
    .checkout-stepper { 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        margin-bottom: 40px; 
        padding-bottom: 20px;
        border-bottom: 1px solid #f0eee9;
    }
    .step-indicator { 
        font-size: 13px; 
        color: #b5b5b5; /* Màu xám nhạt cho bước chưa tới */
        text-transform: uppercase; 
        letter-spacing: 1px; 
        transition: 0.3s;
        font-weight: 500;
    }
    .step-indicator.active { 
        color: #333; /* Màu đậm cho bước hiện tại */
        font-weight: bold; 
    }
    .step-line { 
        height: 1px; 
        background-color: #e0e0e0; 
        width: 60px; 
        margin: 0 20px; 
    }
</style>

<div class="checkout-page">
    <div class="checkout-page">
    
    <div class="checkout-stepper">
        <div class="step-indicator active" id="indicator-1">1. Thông tin giao hàng</div>
        <div class="step-line"></div>
        <div class="step-indicator" id="indicator-2">2. Vận chuyển</div>
        <div class="step-line"></div>
        <div class="step-indicator" id="indicator-3">3. Thanh toán</div>
    </div>
    <div class="checkout-layout">
        
        <div class="checkout-left">
            <form id="checkoutForm" action="process_checkout.php" method="POST">
                
                <div class="checkout-step active" id="step-1">
                    <h2 class="step-title">Thông tin giao hàng</h2>
                    <div class="form-group">
                        <label>Quốc gia</label>
                        <select name="country"><option value="VN">Vietnam</option></select>
                    </div>
                    
                    <?php 
                        // Tách họ và tên từ fullname (giả lập đơn giản)
                        $name_parts = explode(' ', $user_info['fullname'] ?? '');
                        $first_name = array_shift($name_parts);
                        $last_name = implode(' ', $name_parts);
                    ?>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Họ *</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="form-col">
                            <label>Tên *</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại *</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_info['phonenumber'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ *</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user_info['address'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tỉnh/Thành phố *</label>
                        <input type="text" name="city" placeholder="VD: Tp. Hồ Chí Minh" required>
                    </div>
                    <div class="form-group">
                        <label>Ghi chú thêm (Không bắt buộc)</label>
                        <input type="text" name="notes" placeholder="Ghi chú thêm...">
                    </div>
                    <div class="step-actions" style="justify-content: flex-end;">
                        <button type="button" class="btn-next" onclick="goToStep(2)" style="flex: none; width: 200px;">Tiếp tục</button>
                    </div>
                </div>

                <div class="checkout-step" id="step-2">
                    <h2 class="step-title">Phí Ship</h2>
                    
                    <label class="method-box active">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="shipping_method" value="standard" checked>
                            <div class="method-info">
                                <div class="method-name">Tiêu chuẩn</div>
                                <div class="method-desc">Giao hàng trong 3-5 ngày</div>
                            </div>
                        </div>
                        <div class="method-price"><?php echo number_format($shipping_fee, 0, ',', '.'); ?> VNĐ</div>
                    </label>

                    <div class="step-actions">
                        <button type="button" class="btn-back" onclick="goToStep(1)">< Quay lại</button>
                        <button type="button" class="btn-next" onclick="goToStep(3)">Tiếp tục thanh toán</button>
                    </div>
                </div>

                <div class="checkout-step" id="step-3">
                    <h2 class="step-title">Phương thức thanh toán</h2>
                    
                    <label class="method-box" onclick="toggleQR(false)">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <div class="method-info">
                                <div class="method-name">Thanh toán khi nhận hàng (COD)</div>
                                <div class="method-desc">Quý khách sẽ thanh toán khi nhận hàng từ đơn vị vận chuyển.</div>
                            </div>
                        </div>
                    </label>

                    <label class="method-box" onclick="toggleQR(true)">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="online">
                            <div class="method-info">
                                <div class="method-name">Thanh toán online (Ngân hàng / Momo / VNPAY)</div>
                            </div>
                        </div>
                    </label>

                    <div class="qr-payment-info" id="qr-section">
                        <div class="bank-details">
                            <div style="font-size: 12px; color: #666; margin-bottom: 15px; text-transform: uppercase;">Thông tin chuyển khoản</div>
                            <div class="bank-row"><span>Tên tài khoản:</span> <strong>NGUYEN VAN A</strong></div>
                            <div class="bank-row"><span>Số tài khoản:</span> <strong>0963258746</strong></div>
                            <div class="bank-row"><span>Ngân hàng:</span> <strong>MB Bank</strong></div>
                            <div class="bank-row"><span>Nội dung chuyển khoản:</span> <strong style="color: #d32f2f;">DH_<?php echo $user_id; ?>_<?php echo time(); ?></strong></div>
                        </div>
                        <div class="qr-img-box">
                            <img src="https://img.vietqr.io/image/MB-0963258746-compact.png?amount=<?php echo $subtotal + $shipping_fee; ?>&addInfo=DH_<?php echo $user_id; ?>_<?php echo time(); ?>" alt="QR Code">
                            <p style="font-size: 13px; color: #666; margin-top: 10px;">Quét mã để thanh toán</p>
                            <p style="font-size: 14px; font-weight: bold; margin-top: 5px;">Số tiền: <span id="qr-total-amount"><?php echo number_format($subtotal + $shipping_fee, 0, ',', '.'); ?></span> VNĐ</p>
                        </div>
                    </div>

                    <input type="hidden" name="wallet_used" id="input_wallet_used" value="0">

                    <div class="step-actions">
                        <button type="button" class="btn-back" onclick="goToStep(2)">< Quay lại</button>
                        <button type="submit" class="btn-next">XÁC NHẬN ĐẶT HÀNG</button>
                    </div>
                </div>

            </form>
        </div>

        <div class="checkout-right">
            <h3 class="summary-title">Tóm tắt đơn hàng</h3>
            
            <div class="summary-items" style="max-height: 300px; overflow-y: auto; padding-right: 10px;">
                <?php foreach ($checkout_items as $item): 
                    $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['original_price'];
                ?>
                <div class="sum-item">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                    <div class="sum-item-info">
                        <div style="display: flex; justify-content: space-between;">
                            <div class="sum-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="sum-item-price"><?php echo number_format($price, 0, ',', '.'); ?> VNĐ</div>
                        </div>
                        <div class="sum-item-variant">Phân loại: <?php echo htmlspecialchars($item['color']); ?></div>
                        <div class="sum-item-variant">Kích cỡ: <?php echo htmlspecialchars($item['size']); ?></div>
                        <div class="sum-item-variant">Số lượng: x<?php echo $item['quantity']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="sum-wallet-box">
                <div class="wallet-title">Ví nội bộ của bạn</div>
                <div class="wallet-balance">Số dư: <?php echo number_format($wallet_balance, 0, ',', '.'); ?> VNĐ</div>
                
                <label class="wallet-checkbox">
                    <input type="checkbox" id="use_wallet_cb" onchange="calculateTotal()" <?php echo ($wallet_balance <= 0) ? 'disabled' : ''; ?>>
                    Sử dụng ví nội bộ cho đơn hàng này
                </label>
            </div>

            <div class="sum-row">
                <span>Giá tạm tính:</span>
                <span id="ui_subtotal" data-val="<?php echo $subtotal; ?>"><?php echo number_format($subtotal, 0, ',', '.'); ?> VNĐ</span>
            </div>
            <div class="sum-row">
                <span>Phí vận chuyển:</span>
                <span id="ui_shipping" data-val="<?php echo $shipping_fee; ?>"><?php echo number_format($shipping_fee, 0, ',', '.'); ?> VNĐ</span>
            </div>
            
            <div class="sum-row" id="wallet_discount_row" style="display: none; color: #d32f2f;">
                <span>Dùng ví hoàn tiền:</span>
                <span id="ui_wallet_used">-0 VNĐ</span>
            </div>

            <div class="sum-total-row">
                <span>TỔNG TIỀN</span>
                <span id="ui_total"><?php echo number_format($subtotal + $shipping_fee, 0, ',', '.'); ?> VNĐ</span>
            </div>
            <div class="sum-note">Chưa bao gồm thuế (nếu có)</div>
        </div>

    </div>
</div>

<script>
    // JS CHUYỂN BƯỚC VÀ CẬP NHẬT THANH TIẾN TRÌNH
    function goToStep(step) {
        // 1. Chuyển form
        document.querySelectorAll('.checkout-step').forEach(el => {
            el.classList.remove('active');
        });
        document.getElementById('step-' + step).classList.add('active');

        // 2. Chuyển màu thanh tiến trình
        document.querySelectorAll('.step-indicator').forEach(el => {
            el.classList.remove('active');
        });
        document.getElementById('indicator-' + step).classList.add('active');

        // 3. Cuộn lên đầu
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // JS HIỂN THỊ MÃ QR KHI CHỌN ONLINE
    function toggleQR(show) {
        const qrSection = document.getElementById('qr-section');
        const boxes = document.querySelectorAll('#step-3 .method-box');
        
        boxes.forEach(box => box.classList.remove('active'));
        
        if(show) {
            qrSection.classList.add('show');
            boxes[1].classList.add('active'); // Đánh dấu box online
        } else {
            qrSection.classList.remove('show');
            boxes[0].classList.add('active'); // Đánh dấu box cod
        }
    }

    // JS TÍNH TOÁN VÍ HOÀN TIỀN
    const walletBalance = <?php echo $wallet_balance; ?>;
    
    function calculateTotal() {
        const subtotal = parseInt(document.getElementById('ui_subtotal').getAttribute('data-val'));
        const shipping = parseInt(document.getElementById('ui_shipping').getAttribute('data-val'));
        const useWallet = document.getElementById('use_wallet_cb').checked;
        const walletRow = document.getElementById('wallet_discount_row');
        const uiWalletUsed = document.getElementById('ui_wallet_used');
        const uiTotal = document.getElementById('ui_total');
        const qrTotal = document.getElementById('qr-total-amount'); // Cập nhật số tiền trên mã QR
        const inputWalletUsed = document.getElementById('input_wallet_used');

        let totalBeforeWallet = subtotal + shipping;
        let walletUsedAmount = 0;

        if (useWallet) {
            // Dùng tối đa số tiền trong ví, nhưng không vượt quá tổng đơn
            walletUsedAmount = Math.min(walletBalance, totalBeforeWallet);
            
            walletRow.style.display = 'flex';
            uiWalletUsed.innerText = '-' + walletUsedAmount.toLocaleString('vi-VN') + ' VNĐ';
        } else {
            walletRow.style.display = 'none';
        }

        let finalTotal = totalBeforeWallet - walletUsedAmount;
        
        // Cập nhật hiển thị
        uiTotal.innerText = finalTotal.toLocaleString('vi-VN') + ' VNĐ';
        if(qrTotal) qrTotal.innerText = finalTotal.toLocaleString('vi-VN');
        
        // Lưu giá trị vào input ẩn để đẩy lên server
        inputWalletUsed.value = walletUsedAmount;
    }
</script>

<?php include 'includes/footer.php'; ?>