<?php
// 1. Kết nối Database
require_once 'config/database.php';

// 2. Lấy ID sản phẩm từ URL
$product_id = $_GET['id'] ?? '';

// Khởi tạo biến để tránh lỗi
$product = null;
$variants = [];
$related_products = [];
$reviews = [];
$first_v = null;

if ($product_id) {
    // 3. Lấy thông tin sản phẩm chính
    $sql_prod = "SELECT p.*, c.name as category_name 
                 FROM Products p 
                 JOIN Categories c ON p.category_id = c.category_id 
                 WHERE p.product_id = :id";
    $stmt = $conn->prepare($sql_prod);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // 4. Lấy Biến thể (Màu, Size, Giá, Tồn kho)
        $sql_variants = "SELECT * FROM Product_Variants WHERE product_id = :id AND is_active = 1";
        $stmt_v = $conn->prepare($sql_variants);
        $stmt_v->bindParam(':id', $product_id);
        $stmt_v->execute();
        $variants = $stmt_v->fetchAll(PDO::FETCH_ASSOC);
        $first_v = $variants[0] ?? null;

        // 5. Lấy Sản phẩm liên quan (Cùng Category, trừ món hiện tại)
        $sql_related = "SELECT p.*, v.original_price, v.sale_price 
                        FROM Products p 
                        LEFT JOIN Product_Variants v ON p.product_id = v.product_id 
                        WHERE p.category_id = :cat_id 
                        AND p.product_id != :prod_id 
                        AND p.status = 1
                        GROUP BY p.product_id
                        LIMIT 4";
        $stmt_related = $conn->prepare($sql_related);
        $stmt_related->bindParam(':cat_id', $product['category_id']);
        $stmt_related->bindParam(':prod_id', $product_id);
        $stmt_related->execute();
        $related_products = $stmt_related->fetchAll(PDO::FETCH_ASSOC);

        // 6. Lấy Feedback (Lấy fullname từ bảng users)
        $sql_reviews = "SELECT r.*, u.fullname 
                        FROM reviews r 
                        LEFT JOIN users u ON r.user_id = u.user_id 
                        WHERE r.product_id = :prod_id 
                        ORDER BY r.created_at DESC";
        $stmt_rev = $conn->prepare($sql_reviews);
        $stmt_rev->bindParam(':prod_id', $product_id);
        $stmt_rev->execute();
        $reviews = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Gọi header
include 'includes/header.php';
?>

<main class="container">
    <?php if ($product): ?>
        <nav class="breadcrumb">Trang chủ / Shop / <b><?php echo $product['name']; ?></b></nav>

        <div class="product-detail-container">
            <div class="product-image-section">
                <img src="<?php echo $product['image']; ?>" alt="" class="main-detail-img">
            </div>

            <div class="product-info-section">
                <h1 class="detail-title"><?php echo $product['name']; ?></h1>

                <div class="detail-meta">
                    <span class="stars">
                        <?php
                        $rating = $product['avg_rating'] ?: 5;
                        for ($i = 1; $i <= 5; $i++)
                            echo ($i <= $rating) ? '★' : '☆';
                        ?>
                    </span>
                    <span class="rating-text"><?php echo $rating; ?> (<?php echo count($reviews); ?> đánh giá)</span> |
                    <span class="sold-count">Đã bán <?php echo $product['sold_count']; ?></span>
                </div>

                <div class="detail-price-box">
                    <span class="detail-sale-price" id="detail-sale-price">
                        <?php if ($first_v):
                            echo number_format($first_v['sale_price'] > 0 ? $first_v['sale_price'] : $first_v['original_price'], 0, ',', '.') . 'đ';
                        endif; ?>
                    </span>
                    <?php if ($first_v && $first_v['sale_price'] < $first_v['original_price'] && $first_v['sale_price'] > 0): ?>
                        <span class="detail-old-price"
                            id="detail-old-price"><?php echo number_format($first_v['original_price'], 0, ',', '.'); ?>đ</span>
                        <span class="discount-tag"
                            id="detail-discount">-<?php echo round((($first_v['original_price'] - $first_v['sale_price']) / $first_v['original_price']) * 100); ?>%</span>
                    <?php else: ?>
                        <span class="detail-old-price" id="detail-old-price" style="display:none;"></span>
                        <span class="discount-tag" id="detail-discount" style="display:none;"></span>
                    <?php endif; ?>
                </div>

                <!-- CHỌN MÀU SẮC -->
                <div class="detail-options">
                    <p class="option-label">Màu sắc: <strong
                            id="selected-color"><?php echo $first_v['color'] ?? ''; ?></strong></p>
                    <div class="option-list" id="color-list">
                        <?php
                        $unique_colors = array_unique(array_column($variants, 'color'));
                        $first_color = $first_v['color'] ?? '';
                        foreach ($unique_colors as $color):
                            $active = ($color === $first_color) ? 'active' : '';
                            ?>
                            <button class="btn-option <?php echo $active; ?>"
                                data-color="<?php echo htmlspecialchars($color); ?>"
                                onclick="selectColor('<?php echo htmlspecialchars($color); ?>')">
                                <?php echo $color; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CHỌN KÍCH CỠ -->
                <div class="detail-options">
                    <p class="option-label">Kích cỡ: <strong
                            id="selected-size"><?php echo $first_v['size'] ?? ''; ?></strong></p>
                    <div class="option-list" id="size-list">
                        <?php
                        $first_size = $first_v['size'] ?? '';
                        // Chỉ lấy size của màu đang chọn
                        $sizes_for_first_color = [];
                        foreach ($variants as $v) {
                            if ($v['color'] === $first_color)
                                $sizes_for_first_color[] = $v['size'];
                        }
                        foreach (array_unique($sizes_for_first_color) as $size):
                            $active = ($size === $first_size) ? 'active' : '';
                            ?>
                            <button class="btn-option <?php echo $active; ?>" data-size="<?php echo htmlspecialchars($size); ?>"
                                onclick="selectSize('<?php echo htmlspecialchars($size); ?>')">
                                <?php echo $size; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SỐ LƯỢNG -->
                <div class="detail-options">
                    <p class="option-label">Số lượng:</p>
                    <div class="quantity-wrapper">
                        <div class="quantity-selector">
                            <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                            <input type="text" id="quantity" value="1" readonly>
                            <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                        </div>
                        <span class="stock-info"><?php echo $first_v['stock'] ?? 0; ?> sản phẩm có sẵn</span>
                    </div>
                </div>

                <!-- THÔNG BÁO INLINE -->
                <div id="cart-inline-msg"
                    style="display:none; margin: 10px 0; padding: 10px 14px; border-radius: 8px; font-size: 13px;"></div>

                <!-- NÚT HÀNH ĐỘNG -->
                <div class="detail-actions">
                    <button class="btn-add-to-cart" id="btn-add-cart" onclick="addToCart(false)">
                        <i class="fa fa-shopping-bag"></i> Thêm vào giỏ
                    </button>
                    <button class="btn-buy-now" id="btn-buy-now" onclick="addToCart(true)">
                        Mua ngay
                    </button>
                    <button class="btn-wishlist" id="add-to-wishlist" data-id="<?php echo $product['product_id']; ?>">
                        <i class="fa fa-heart-o"></i>
                    </button>
                </div>

                <div class="detail-description">
                    <p class="option-label">Mô tả sản phẩm:</p>
                    <p class="desc-text"><?php echo nl2br($product['description']); ?></p>
                </div>

                <div class="product-feedback" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                    <h3 style="color: #a6825c; margin-bottom: 20px;">Đánh giá từ khách hàng</h3>
                    <?php if (count($reviews) > 0): ?>
                        <div class="review-list">
                            <?php foreach ($reviews as $rev): ?>
                                <div class="review-item"
                                    style="margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between;">
                                        <b><?php echo $rev['fullname'] ?: 'Khách hàng ẩn danh'; ?></b>
                                        <span style="color: #ffc107;">
                                            <?php for ($i = 1; $i <= 5; $i++)
                                                echo ($i <= $rev['rating']) ? '★' : '☆'; ?>
                                        </span>
                                    </div>
                                    <p style="font-size: 14px; color: #555; margin: 5px 0;"><?php echo $rev['comment']; ?></p>
                                    <small style="color: #999;"><?php echo date('d/m/Y', strtotime($rev['created_at'])); ?></small>

                                    <?php if (!empty($rev['reply'])): ?>
                                        <div
                                            style="background: #f9f5f0; padding: 10px; border-radius: 4px; font-size: 13px; margin-top: 8px;">
                                            <b>NTK phản hồi:</b> <?php echo $rev['reply']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #999;">Chưa có đánh giá nào cho sản phẩm này.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <section class="section related-products" style="margin-top: 50px;">
            <h2 class="section-title">Sản phẩm liên quan</h2>
            <div class="product-grid">
                <?php foreach ($related_products as $item): ?>
                    <div class="product-card">
                        <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                            <div class="img-wrapper">
                                <img src="<?php echo $item['image']; ?>" alt="">
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo $item['name']; ?></h3>
                                <div class="product-meta">
                                    <span class="stars">★★★★★</span>
                                    <span class="product-sold">| <?php echo $item['avg_rating'] ?: '5.0'; ?></span>
                                </div>
                                <p class="price"><?php echo number_format($item['original_price'], 0, ',', '.'); ?>đ</p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php else: ?>
        <p style="padding: 100px; text-align: center;">Sản phẩm không tồn tại.</p>
    <?php endif; ?>
</main>

<!-- Dữ liệu biến thể dạng JSON để JS xử lý -->
<script>
    var allVariants = <?php echo json_encode($variants); ?>;
    var selectedColor = "<?php echo addslashes($first_v['color'] ?? ''); ?>";
    var selectedSize = "<?php echo addslashes($first_v['size'] ?? ''); ?>";

    // ===================== TÌM BIẾN THỂ =====================
    function findVariant(color, size) {
        return allVariants.find(function (v) {
            return v.color === color && v.size === size;
        }) || null;
    }

    // ===================== CẬP NHẬT GIÁ + TỒN KHO =====================
    function updatePriceAndStock() {
        var v = findVariant(selectedColor, selectedSize);
        if (!v) {
            document.getElementById('stock-info').textContent = 'Không có sẵn';
            document.getElementById('btn-add-cart').disabled = true;
            document.getElementById('btn-buy-now').disabled = true;
            return;
        }

        var salePrice = parseFloat(v.sale_price) || 0;
        var originalPrice = parseFloat(v.original_price) || 0;
        var displayPrice = salePrice > 0 ? salePrice : originalPrice;

        document.getElementById('detail-sale-price').textContent = displayPrice.toLocaleString('vi-VN') + 'đ';

        var oldPriceEl = document.getElementById('detail-old-price');
        var discountEl = document.getElementById('detail-discount');

        if (salePrice > 0 && salePrice < originalPrice) {
            oldPriceEl.textContent = originalPrice.toLocaleString('vi-VN') + 'đ';
            oldPriceEl.style.display = '';
            var pct = Math.round((originalPrice - salePrice) / originalPrice * 100);
            discountEl.textContent = '-' + pct + '%';
            discountEl.style.display = '';
        } else {
            oldPriceEl.style.display = 'none';
            discountEl.style.display = 'none';
        }

        document.getElementById('stock-info').textContent = v.stock + ' sản phẩm có sẵn';
        document.getElementById('quantity').value = 1;

        var disabled = (v.stock < 1);
        document.getElementById('btn-add-cart').disabled = disabled;
        document.getElementById('btn-buy-now').disabled = disabled;
    }

    // ===================== CHỌN MÀU =====================
    function selectColor(color) {
        selectedColor = color;
        document.getElementById('selected-color').textContent = color;

        // Highlight nút màu
        document.querySelectorAll('#color-list .btn-option').forEach(function (b) {
            b.classList.toggle('active', b.dataset.color === color);
        });

        // Cập nhật danh sách Size theo màu vừa chọn
        var sizesForColor = allVariants
            .filter(function (v) { return v.color === color; })
            .map(function (v) { return v.size; });
        var uniqueSizes = sizesForColor.filter(function (s, i) { return sizesForColor.indexOf(s) === i; });

        var sizeList = document.getElementById('size-list');
        sizeList.innerHTML = '';
        uniqueSizes.forEach(function (size) {
            var btn = document.createElement('button');
            btn.className = 'btn-option';
            btn.dataset.size = size;
            btn.textContent = size;
            btn.onclick = function () { selectSize(size); };
            sizeList.appendChild(btn);
        });

        // Tự chọn size đầu tiên
        if (uniqueSizes.length > 0) selectSize(uniqueSizes[0]);
    }

    // ===================== CHỌN SIZE =====================
    function selectSize(size) {
        selectedSize = size;
        document.getElementById('selected-size').textContent = size;

        document.querySelectorAll('#size-list .btn-option').forEach(function (b) {
            b.classList.toggle('active', b.dataset.size === size);
        });

        updatePriceAndStock();
    }

    // ===================== THAY ĐỔI SỐ LƯỢNG =====================
    function changeQty(amt) {
        var v = findVariant(selectedColor, selectedSize);
        var max = v ? parseInt(v.stock) : 1;
        var inp = document.getElementById('quantity');
        var newVal = parseInt(inp.value) + amt;
        if (newVal < 1) newVal = 1;
        if (newVal > max) newVal = max;
        inp.value = newVal;
    }

    // ===================== THÊM VÀO GIỎ =====================
    function addToCart(buyNow) {
        var v = findVariant(selectedColor, selectedSize);
        if (!v) {
            showInlineMsg('Vui lòng chọn màu sắc và kích cỡ!', 'warning');
            return;
        }

        var qty = parseInt(document.getElementById('quantity').value);
        if (qty < 1 || qty > v.stock) {
            showInlineMsg('Số lượng không hợp lệ!', 'warning');
            return;
        }

        var btn = buyNow ? document.getElementById('btn-buy-now') : document.getElementById('btn-add-cart');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: {
                action: 'add_to_cart',
                variant_id: v.variant_id,
                quantity: qty
            },
            success: function (res) {
                btn.disabled = false;
                if (buyNow) {
                    btn.innerHTML = 'Mua ngay';
                } else {
                    btn.innerHTML = '<i class="fa fa-shopping-bag"></i> Thêm vào giỏ';
                }

                var result = res.trim();
                if (result === 'success' || result === 'updated') {
                    if (buyNow) {
                        window.location.href = 'cart.php';
                    } else {
                        showInlineMsg('✓ Đã thêm vào giỏ hàng!', 'success');
                        // Cập nhật số trên icon giỏ hàng header
                        updateCartCount();
                    }
                } else if (result === 'not_logged_in') {
                    showInlineMsg('Vui lòng đăng nhập để thêm vào giỏ hàng!', 'error');
                    setTimeout(function () { window.location.href = 'views/login.php'; }, 1500);
                } else if (result === 'out_of_stock') {
                    showInlineMsg('Sản phẩm đã hết hàng!', 'error');
                } else {
                    showInlineMsg('Có lỗi xảy ra, thử lại sau!', 'error');
                }
            },
            error: function () {
                btn.disabled = false;
                btn.innerHTML = buyNow ? 'Mua ngay' : '<i class="fa fa-shopping-bag"></i> Thêm vào giỏ';
                showInlineMsg('Không thể kết nối với máy chủ!', 'error');
            }
        });
    }

    // ===================== CẬP NHẬT ICON GIỎ HÀNG =====================
    function updateCartCount() {
        $.get('ajax_cart.php', { action: 'get_count' }, function (res) {
            var count = parseInt(res.trim());
            if (!isNaN(count)) {
                var badge = document.querySelector('.cart-count');
                if (badge) badge.textContent = count;
            }
        });
    }

    // ===================== THÔNG BÁO INLINE =====================
    function showInlineMsg(msg, type) {
        var el = document.getElementById('cart-inline-msg');
        el.style.display = 'block';
        el.textContent = msg;
        el.style.background = type === 'success' ? '#eafbf0' : type === 'warning' ? '#fffbf0' : '#fff0f0';
        el.style.color = type === 'success' ? '#1e8449' : type === 'warning' ? '#b8860b' : '#c0392b';
        el.style.border = '1px solid ' + (type === 'success' ? '#a9dfbf' : type === 'warning' ? '#f5e6b2' : '#f5c6cb');

        clearTimeout(el._timer);
        el._timer = setTimeout(function () { el.style.display = 'none'; }, 4000);
    }

    // ===================== WISHLIST =====================
    $(document).ready(function () {
        $('#add-to-wishlist').click(function (e) {
            e.preventDefault();
            var productId = $(this).data('id');
            var btn = $(this);

            $.ajax({
                url: 'ajax_wishlist.php',
                method: 'POST',
                data: { product_id: productId },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        btn.find('i').removeClass('fa-heart-o').addClass('fa-heart').css('color', 'red');
                        showInlineMsg('Đã thêm vào danh sách yêu thích!', 'success');
                    } else {
                        showInlineMsg(response.message, 'error');
                    }
                }
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>