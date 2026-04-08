<?php
require_once 'config/database.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'] ?? null;

// Nếu chưa đăng nhập
if (!$user_id) {
    echo "<div class='container' style='text-align:center; padding: 100px 0;'>
            <i class='fa-solid fa-bag-shopping' style='font-size:64px; color:#ddd; margin-bottom:20px;'></i>
            <h2 style='color:#555; margin-bottom:10px;'>Bạn chưa đăng nhập</h2>
            <p style='color:#999; margin-bottom:30px;'>Vui lòng đăng nhập để xem giỏ hàng của bạn</p>
            <a href='views/login.php' class='btn-buy-now' style='display:inline-block; width:200px; margin-top:10px;'>Đăng nhập ngay</a>
          </div>";
    include 'includes/footer.php';
    exit;
}

// Lấy danh sách sản phẩm trong giỏ hàng
$sql = "SELECT c.cart_id, c.quantity, c.is_selected,
               v.variant_id, v.color, v.size, v.original_price, v.sale_price, v.stock,
               p.product_id, p.name AS product_name, p.image
        FROM Cart c
        JOIN Product_Variants v ON c.variant_id = v.variant_id
        JOIN Products p ON v.product_id = p.product_id
        WHERE c.user_id = :uid
        ORDER BY c.cart_id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute(['uid' => $user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_items = count($cart_items);
?>

<div class="cart-page">
    <div class="cart-wrapper">
        <!-- Breadcrumb -->
        <nav class="cart-breadcrumb">
            <a href="index.php">Trang chủ</a>
            <span class="bc-sep"><i class="fa-solid fa-chevron-right"></i></span>
            <span>Giỏ hàng</span>
        </nav>

        <h1 class="cart-title">Giỏ hàng của bạn</h1>

        <div class="cart-layout">
            <!-- Cột trái: Danh sách sản phẩm -->
            <div class="cart-left">
                <?php if ($total_items > 0): ?>

                    <!-- Thanh chọn tất cả -->
                    <div class="cart-select-all-bar">
                        <label class="cart-checkbox-label" id="select-all-label">
                            <input type="checkbox" id="select-all-checkbox" onchange="toggleSelectAll(this)">
                            <span class="cart-custom-checkbox"></span>
                            <span class="select-all-text">Chọn tất cả (<span
                                    id="selected-count">0</span>/<?php echo $total_items; ?>)</span>
                        </label>
                        <span class="cart-no-select-notice" id="no-select-notice">Chưa chọn sản phẩm nào</span>
                    </div>

                    <!-- Header cột -->
                    <div class="cart-table-header">
                        <span class="col-product">SẢN PHẨM</span>
                        <span class="col-price">GIÁ</span>
                        <span class="col-qty">SỐ LƯỢNG</span>
                        <span class="col-total">TỔNG</span>
                    </div>

                    <!-- Danh sách sản phẩm -->
                    <div id="cart-items-list">
                        <?php foreach ($cart_items as $item):
                            $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['original_price'];
                            $line_total = $price * $item['quantity'];
                            $is_checked = $item['is_selected'] == 1 ? 'checked' : '';
                            ?>
                            <div class="cart-item" id="cart-item-<?php echo $item['cart_id']; ?>"
                                data-cart-id="<?php echo $item['cart_id']; ?>" data-price="<?php echo $price; ?>"
                                data-qty="<?php echo $item['quantity']; ?>" data-selected="<?php echo $item['is_selected']; ?>">

                                <div class="cart-item-check">
                                    <label class="cart-checkbox-label">
                                        <input type="checkbox" class="item-checkbox" <?php echo $is_checked; ?>
                                            onchange="toggleItemSelect(this, '<?php echo $item['cart_id']; ?>')">
                                        <span class="cart-custom-checkbox"></span>
                                    </label>
                                </div>

                                <div class="cart-item-img">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                        alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                </div>

                                <div class="cart-item-info">
                                    <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" class="cart-item-name">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </a>
                                    <p class="cart-item-variant">Màu: <?php echo htmlspecialchars($item['color']); ?></p>
                                    <p class="cart-item-variant">Size: <?php echo htmlspecialchars($item['size']); ?></p>
                                    <button class="cart-item-remove"
                                        onclick="removeCartItem('<?php echo $item['cart_id']; ?>')">
                                        <i class="fa-solid fa-trash-can"></i> Xóa
                                    </button>
                                </div>

                                <div class="cart-item-price">
                                    <?php echo number_format($price, 0, ',', '.'); ?>đ
                                </div>

                                <div class="cart-item-qty">
                                    <div class="qty-control">
                                        <button class="qty-btn"
                                            onclick="changeQty('<?php echo $item['cart_id']; ?>', -1)">−</button>
                                        <input type="number" class="qty-input" id="qty-<?php echo $item['cart_id']; ?>"
                                            value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>"
                                            onchange="updateQtyInput('<?php echo $item['cart_id']; ?>', this.value)">
                                        <button class="qty-btn"
                                            onclick="changeQty('<?php echo $item['cart_id']; ?>', 1)">+</button>
                                    </div>
                                </div>

                                <div class="cart-item-total" id="total-<?php echo $item['cart_id']; ?>">
                                    <?php echo number_format($line_total, 0, ',', '.'); ?>đ
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Nút tiếp tục mua sắm -->
                    <div class="cart-continue">
                        <a href="product.php" class="btn-continue">
                            <i class="fa-solid fa-arrow-left"></i> Tiếp tục mua sắm
                        </a>
                    </div>

                <?php else: ?>
                    <!-- Giỏ hàng trống -->
                    <div class="cart-empty">
                        <i class="fa-solid fa-bag-shopping cart-empty-icon"></i>
                        <h3>Giỏ hàng của bạn đang trống</h3>
                        <p>Hãy thêm sản phẩm vào giỏ hàng để tiến hành mua sắm!</p>
                        <a href="product.php" class="btn-buy-now"
                            style="display:inline-block; width:220px; text-align:center; margin-top:20px;">
                            Khám phá sản phẩm
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cột phải: Tổng đơn hàng -->
            <div class="cart-right">
                <div class="order-summary-box">
                    <h2 class="order-summary-title">Tổng đơn hàng</h2>

                    <!-- Cảnh báo chưa chọn -->
                    <div class="order-warning" id="order-warning">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        Vui lòng chọn sản phẩm để thanh toán
                    </div>

                    <!-- Chi tiết giá -->
                    <div class="order-detail-row">
                        <span class="od-label">Giá tạm tính (<span id="summary-count">0</span> sản phẩm)</span>
                        <span class="od-value" id="summary-subtotal">0đ</span>
                    </div>
                    <div class="order-detail-row">
                        <span class="od-label">Phí vận chuyển</span>
                        <span class="od-value" id="summary-shipping">0đ</span>
                    </div>

                    <div class="order-divider"></div>

                    <div class="order-total-row">
                        <span class="ot-label">Tổng tiền</span>
                        <span class="ot-value" id="summary-total">0đ</span>
                    </div>

                    <!-- Nhập mã giảm giá -->
                    <div class="coupon-section">
                        <label class="coupon-label">
                            <i class="fa-solid fa-tag"></i> Mã giảm giá
                        </label>
                        <div class="coupon-input-group">
                            <input type="text" id="coupon-code" placeholder="Nhập mã giảm giá" class="coupon-input">
                            <button class="btn-apply-coupon" onclick="applyCoupon()">Áp dụng</button>
                        </div>
                        <p class="coupon-msg" id="coupon-msg"></p>
                    </div>

                    <!-- Nút thanh toán -->
                    <button class="btn-checkout" id="btn-checkout" onclick="goCheckout()" disabled>
                        Tiến hành thanh toán
                    </button>
                    <p class="checkout-note">
                        <i class="fa-solid fa-rotate-left"></i> Miễn phí đổi trả trong 30 ngày
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Dữ liệu giỏ hàng từ PHP
    var cartData = {};
    <?php foreach ($cart_items as $item):
        $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['original_price'];
        ?>
        cartData['<?php echo $item['cart_id']; ?>'] = {
            price: <?php echo $price; ?>,
            qty: <?php echo $item['quantity']; ?>,
            stock: <?php echo $item['stock']; ?>,
            selected: <?php echo $item['is_selected']; ?>
        };
    <?php endforeach; ?>

    var discountAmount = 0;
    var SHIPPING_FEE = 35000;

    // ===================== HỖ TRỢ =====================
    function formatVND(num) {
        return num.toLocaleString('vi-VN') + 'đ';
    }

    // ===================== TÍNH TỔNG =====================
    function updateSummary() {
        var selectedItems = 0;
        var subtotal = 0;

        for (var id in cartData) {
            var item = cartData[id];
            if (item.selected) {
                selectedItems++;
                subtotal += item.price * item.qty;
            }
        }

        document.getElementById('selected-count').textContent = selectedItems;
        document.getElementById('summary-count').textContent = selectedItems;
        document.getElementById('summary-subtotal').textContent = formatVND(subtotal);

        var shipping = (selectedItems > 0) ? SHIPPING_FEE : 0;
        document.getElementById('summary-shipping').textContent = formatVND(shipping);

        var total = Math.max(0, subtotal + shipping - discountAmount);
        document.getElementById('summary-total').textContent = formatVND(total);

        // Cập nhật UI
        var warning = document.getElementById('order-warning');
        var noNotice = document.getElementById('no-select-notice');
        var btnCheckout = document.getElementById('btn-checkout');

        if (selectedItems > 0) {
            warning.style.display = 'none';
            noNotice.style.display = 'none';
            btnCheckout.disabled = false;
            btnCheckout.classList.add('active');
        } else {
            warning.style.display = 'flex';
            noNotice.style.display = 'block';
            btnCheckout.disabled = true;
            btnCheckout.classList.remove('active');
        }

        // Cập nhật checkbox select-all
        updateSelectAllState();
    }

    function updateSelectAllState() {
        var all = document.querySelectorAll('.item-checkbox');
        var checked = document.querySelectorAll('.item-checkbox:checked');
        var selectAll = document.getElementById('select-all-checkbox');
        if (selectAll) {
            selectAll.checked = all.length > 0 && checked.length === all.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
        }
    }

    // ===================== CHỌN TẤT CẢ =====================
    function toggleSelectAll(cb) {
        var checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(function (box) {
            box.checked = cb.checked;
            var cartId = box.closest('.cart-item').dataset.cartId;
            cartData[cartId].selected = cb.checked ? 1 : 0;
            // Cập nhật DB
            updateSelectDB(cartId, cb.checked ? 1 : 0);
        });
        updateSummary();
    }

    // ===================== CHỌN TỪNG SẢN PHẨM =====================
    function toggleItemSelect(cb, cartId) {
        cartData[cartId].selected = cb.checked ? 1 : 0;
        updateSelectDB(cartId, cb.checked ? 1 : 0);
        updateSummary();
    }

    function updateSelectDB(cartId, isSelected) {
        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: { action: 'select', cart_id: cartId, is_selected: isSelected },
            error: function () { console.log('Lỗi cập nhật lựa chọn'); }
        });
    }

    // ===================== THAY ĐỔI SỐ LƯỢNG =====================
    function changeQty(cartId, delta) {
        var current = cartData[cartId].qty;
        var newQty = current + delta;
        var maxStock = cartData[cartId].stock;

        if (newQty < 1) return;
        if (newQty > maxStock) {
            showToast('Số lượng không được vượt quá tồn kho (' + maxStock + ')!', 'warning');
            return;
        }

        cartData[cartId].qty = newQty;
        document.getElementById('qty-' + cartId).value = newQty;

        // Cập nhật tổng dòng
        var lineTotal = cartData[cartId].price * newQty;
        document.getElementById('total-' + cartId).textContent = formatVND(lineTotal);

        updateSummary();

        // Gửi lên server
        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: { action: 'update_qty', cart_id: cartId, quantity: newQty },
            error: function () { showToast('Lỗi cập nhật số lượng!', 'error'); }
        });
    }

    function updateQtyInput(cartId, val) {
        var newQty = parseInt(val);
        var maxStock = cartData[cartId].stock;
        if (isNaN(newQty) || newQty < 1) newQty = 1;
        if (newQty > maxStock) newQty = maxStock;

        cartData[cartId].qty = newQty;
        document.getElementById('qty-' + cartId).value = newQty;
        var lineTotal = cartData[cartId].price * newQty;
        document.getElementById('total-' + cartId).textContent = formatVND(lineTotal);
        updateSummary();

        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: { action: 'update_qty', cart_id: cartId, quantity: newQty },
            error: function () { showToast('Lỗi cập nhật số lượng!', 'error'); }
        });
    }

    // ===================== XÓA SẢN PHẨM =====================
    function removeCartItem(cartId) {
        if (!confirm('Bạn muốn xóa sản phẩm này khỏi giỏ hàng?')) return;

        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: { action: 'remove', cart_id: cartId },
            success: function (res) {
                if (res.trim() === 'success') {
                    $('#cart-item-' + cartId).fadeOut(400, function () {
                        $(this).remove();
                        delete cartData[cartId];
                        updateSummary();

                        // Kiểm tra nếu giỏ hàng trống
                        if (Object.keys(cartData).length === 0) {
                            location.reload();
                        }
                    });
                    showToast('Đã xóa sản phẩm khỏi giỏ hàng', 'success');
                    updateCartCount();
                } else {
                    showToast('Lỗi: Không thể xóa!', 'error');
                }
            },
            error: function () { showToast('Không thể kết nối với máy chủ!', 'error'); }
        });
    }

    // ===================== MÃ GIẢM GIÁ =====================
    function applyCoupon() {
        var code = document.getElementById('coupon-code').value.trim();
        var msg = document.getElementById('coupon-msg');

        if (!code) {
            msg.textContent = 'Vui lòng nhập mã giảm giá!';
            msg.className = 'coupon-msg error';
            return;
        }

        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: { action: 'apply_coupon', code: code },
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    discountAmount = res.discount;
                    msg.textContent = '✓ Áp dụng thành công! Giảm ' + formatVND(res.discount);
                    msg.className = 'coupon-msg success';
                    updateSummary();
                } else {
                    discountAmount = 0;
                    msg.textContent = '✗ ' + res.message;
                    msg.className = 'coupon-msg error';
                    updateSummary();
                }
            },
            error: function () {
                msg.textContent = 'Lỗi kết nối, thử lại sau!';
                msg.className = 'coupon-msg error';
            }
        });
    }

    // ===================== THANH TOÁN =====================
    function goCheckout() {
        var selected = [];
        for (var id in cartData) {
            if (cartData[id].selected) selected.push(id);
        }
        if (selected.length === 0) {
            showToast('Vui lòng chọn ít nhất một sản phẩm!', 'warning');
            return;
        }
        // Chuyển hướng sang trang thanh toán (sẽ tạo sau)
        var couponCode = document.getElementById('coupon-code').value.trim();
        window.location.href = 'checkout.php?coupon=' + encodeURIComponent(couponCode);
    }

    // ===================== TOAST NOTIFICATION =====================
    function showToast(message, type) {
        var existing = document.querySelector('.cart-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.className = 'cart-toast cart-toast-' + type;
        toast.innerHTML = '<i class="fa-solid ' +
            (type === 'success' ? 'fa-circle-check' : type === 'error' ? 'fa-circle-xmark' : 'fa-circle-exclamation') +
            '"></i> ' + message;
        document.body.appendChild(toast);

        setTimeout(function () { toast.classList.add('show'); }, 10);
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    // ===================== KHỞI TẠO =====================
    $(document).ready(function () {
        updateSummary();
    });
    function updateCartCount() {
        $.get('ajax_cart.php', { action: 'get_count' }, function (res) {
            var count = parseInt(res.trim());
            if (!isNaN(count)) {
                var badge = document.querySelector('.cart-count');
                if (badge) badge.textContent = count;
            }
        });
    }

    // ===================== KHỞI TẠO =====================
    $(document).ready(function () {
        updateSummary();
    });
</script>

<?php include 'includes/footer.php'; ?>