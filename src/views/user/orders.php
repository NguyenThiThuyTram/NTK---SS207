<?php
// orders.php - Danh sách đơn hàng User (included trong dashboard.php)
$user_id = $_SESSION['user_id'];
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// ─── Mapping trạng thái theo luồng nghiệp vụ ──────────────────────────────
// 0  = Chờ thanh toán
// 1  = Chờ lấy hàng     (COD đặt xong / Online đã TT)
// 2  = Đang giao hàng
// 3  = Hoàn thành
// 4  = Đã hủy
// 5  = Đang yêu cầu trả hàng
// 6  = Đang hoàn trả hàng
// 7  = Đã hoàn tiền
// 8  = Chờ duyệt hủy
// 9  = Giao hàng thất bại
// 10 = Đang hoàn về kho
// ──────────────────────────────────────────────────────────────────────────

function getOrderStatusInfo($code) {
    $map = [
        0  => ['text' => 'CHỜ THANH TOÁN',          'color' => '#e67e22', 'bg' => '#fef9f0'],
        1  => ['text' => 'CHỜ LẤY HÀNG',            'color' => '#2980b9', 'bg' => '#eaf4fd'],
        2  => ['text' => 'ĐANG GIAO HÀNG',           'color' => '#8e44ad', 'bg' => '#f5eef8'],
        3  => ['text' => 'HOÀN THÀNH',               'color' => '#27ae60', 'bg' => '#eafaf1'],
        4  => ['text' => 'ĐÃ HỦY',                   'color' => '#888888', 'bg' => '#f5f5f5'],
        5  => ['text' => 'ĐANG YÊU CẦU TRẢ HÀNG',   'color' => '#c0392b', 'bg' => '#fdf0ef'],
        6  => ['text' => 'ĐANG HOÀN TRẢ HÀNG',       'color' => '#d35400', 'bg' => '#fef5ec'],
        7  => ['text' => 'ĐÃ HOÀN TIỀN',             'color' => '#1abc9c', 'bg' => '#e8faf8'],
        8  => ['text' => 'CHỜ DUYỆT HỦY',            'color' => '#f39c12', 'bg' => '#fef9e7'],
        9  => ['text' => 'GIAO HÀNG THẤT BẠI',       'color' => '#c0392b', 'bg' => '#fdf0ef'],
        10 => ['text' => 'ĐANG HOÀN VỀ KHO',         'color' => '#7f8c8d', 'bg' => '#f2f3f4'],
    ];
    return $map[$code] ?? ['text' => 'KHÔNG RÕ', 'color' => '#333', 'bg' => '#fff'];
}

// ─── Build câu SQL theo tab ────────────────────────────────────────────────
$params = [':user_id' => $user_id];
$status_condition = '';

switch ($current_tab) {
    case '0':  $status_condition = "AND o.order_status = 0";  break;
    case '1':  $status_condition = "AND o.order_status = 1";  break;
    case '2':  $status_condition = "AND o.order_status = 2";  break;
    case '3':  $status_condition = "AND o.order_status = 3";  break;
    case '4':  $status_condition = "AND o.order_status = 4";  break;
    case 'return': $status_condition = "AND o.order_status IN (5,6,7)"; break;
    case '8':  $status_condition = "AND o.order_status = 8";  break;
    case '9':  $status_condition = "AND o.order_status IN (9,10)"; break;
    default:   // all - tất cả trừ đã hủy
        $status_condition = "AND o.order_status NOT IN (4)";
}

$sql = "
    SELECT 
        o.order_id, o.order_status, o.final_price, o.order_date,
        o.payment_method, o.payment_status, o.tracking_number,
        od.quantity, od.price, p.product_id,
        p.name AS product_name, p.image AS product_image,
        v.color, v.size, v.image AS variant_image
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    JOIN product_variants v ON od.variant_id = v.variant_id
    JOIN products p ON v.product_id = p.product_id
    WHERE o.user_id = :user_id $status_condition
    ORDER BY o.order_date DESC
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gom nhóm theo đơn hàng
    $orders = [];
    foreach ($results as $row) {
        $oid = $row['order_id'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'order_id'       => $oid,
                'status'         => (int)$row['order_status'],
                'total_amount'   => $row['final_price'],
                'order_date'     => $row['order_date'],
                'payment_method' => (int)$row['payment_method'],
                'payment_status' => (int)$row['payment_status'],
                'tracking_number'=> $row['tracking_number'],
                'items'          => []
            ];
        }
        $variant_parts = [];
        if (!empty($row['color'])) $variant_parts[] = $row['color'];
        if (!empty($row['size']))  $variant_parts[] = $row['size'];
        $row['variant_name'] = implode(', ', $variant_parts);
        $row['image_url']    = !empty($row['variant_image']) ? $row['variant_image'] : $row['product_image'];
        $orders[$oid]['items'][] = $row;
    }
} catch (PDOException $e) {
    echo "<div style='padding:20px; background:#fff3cd; color:#856404;'>Lỗi SQL: " . $e->getMessage() . "</div>";
    $orders = [];
}
?>

<style>
    .order-container { font-family: "Helvetica Neue", Arial, sans-serif; color: #333; }

    /* TABS */
    .order-tabs { display: flex; background: #fff; border-bottom: 2px solid #f0f0f0; margin-bottom: 15px; overflow-x: auto; gap: 0; }
    .order-tabs a { padding: 14px 16px; text-decoration: none; color: #666; font-size: 13.5px; border-bottom: 2px solid transparent; white-space: nowrap; transition: 0.2s; margin-bottom: -2px; }
    .order-tabs a:hover { color: #ee4d2d; }
    .order-tabs a.active { color: #ee4d2d; border-bottom: 2px solid #ee4d2d; font-weight: 600; }

    /* SEARCH */
    .order-search { background: #eaeaea; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px; display: flex; align-items: center; }
    .order-search input { border: none; background: transparent; outline: none; width: 100%; margin-left: 10px; font-size: 14px; }

    /* CARD */
    .order-card { background: #fff; margin-bottom: 15px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #f0f0f0; }
    .order-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1px solid #f5f5f5; font-size: 13.5px; }
    .order-item { display: flex; padding: 14px 16px; border-bottom: 1px solid #f5f5f5; gap: 14px; }
    .item-img { width: 72px; height: 72px; object-fit: cover; border: 1px solid #e1e1e1; border-radius: 4px; flex-shrink: 0; }
    .item-info { flex: 1; min-width: 0; }
    .item-name { font-size: 15px; margin: 0 0 4px; color: #333; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .item-variant { font-size: 13px; color: #888; margin-bottom: 4px; }
    .item-qty { font-size: 13px; color: #555; }
    .item-price { font-size: 14px; color: #ee4d2d; font-weight: 600; min-width: 90px; text-align: right; white-space: nowrap; }

    /* FOOTER */
    .order-footer { padding: 14px 16px; background: #fdfcfb; }
    .order-total { font-size: 14px; color: #333; margin-bottom: 12px; }
    .total-price { font-size: 20px; color: #ee4d2d; font-weight: bold; margin-left: 8px; }
    .order-actions { display: flex; justify-content: flex-end; gap: 10px; align-items: center; flex-wrap: wrap; }
    .tracking-info { font-size: 13px; color: #555; margin-bottom: 10px; }
    .tracking-info strong { color: #2980b9; }

    /* BUTTONS */
    .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0 15px; border-radius: 3px; font-size: 13.5px; cursor: pointer; text-decoration: none; min-width: 90px; height: 36px; box-sizing: border-box; border: 1px solid transparent; font-family: inherit; transition: 0.2s; margin: 0; line-height: 1; white-space: nowrap; }
    .btn-primary { background: #ee4d2d; color: #fff; border-color: #ee4d2d; }
    .btn-primary:hover { background: #d73211; }
    .btn-teal { background: #26aa99; color: #fff; border-color: #26aa99; }
    .btn-teal:hover { background: #1f8c7d; }
    .btn-outline { background: #fff; color: #555; border-color: #ccc; }
    .btn-outline:hover { border-color: #888; color: #333; }
    .btn-warning { background: #f39c12; color: #fff; border-color: #f39c12; }
    .btn-danger-outline { background: #fff; color: #e74c3c; border-color: #e74c3c; }
    .btn-danger-outline:hover { background: #fdf0ef; }

    /* STATUS CHIP */
    .status-chip { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }

    /* INFO BOX */
    .info-box { font-size: 13px; padding: 10px 14px; border-radius: 4px; margin-bottom: 10px; }
    .info-box-orange { background: #fff8f0; border: 1px solid #f0a500; color: #7a5000; }
    .info-box-blue   { background: #eaf4fd; border: 1px solid #2980b9; color: #1a5276; }
    .info-box-red    { background: #fdf0ef; border: 1px solid #e74c3c; color: #7b241c; }
    .info-box-green  { background: #eafaf1; border: 1px solid #27ae60; color: #1e8449; }

    /* EMPTY */
    .empty-order { text-align: center; padding: 60px 20px; background: #fff; color: #aaa; border-radius: 6px; }
    .empty-order i { font-size: 48px; display: block; margin-bottom: 16px; }
</style>

<div class="order-container">

    <!-- TABS -->
    <div class="order-tabs">
        <a href="?view=donmua&tab=all"    class="<?= $current_tab === 'all'    ? 'active' : '' ?>">Tất cả</a>
        <a href="?view=donmua&tab=0"      class="<?= $current_tab === '0'      ? 'active' : '' ?>">Chờ thanh toán</a>
        <a href="?view=donmua&tab=1"      class="<?= $current_tab === '1'      ? 'active' : '' ?>">Chờ lấy hàng</a>
        <a href="?view=donmua&tab=2"      class="<?= $current_tab === '2'      ? 'active' : '' ?>">Đang giao</a>
        <a href="?view=donmua&tab=3"      class="<?= $current_tab === '3'      ? 'active' : '' ?>">Hoàn thành</a>
        <a href="?view=donmua&tab=4"      class="<?= $current_tab === '4'      ? 'active' : '' ?>">Đã hủy</a>
        <a href="?view=donmua&tab=return" class="<?= $current_tab === 'return' ? 'active' : '' ?>">Trả hàng/Hoàn tiền</a>
    </div>

    <div class="order-search">
        <i class="fa-solid fa-magnifying-glass" style="color:#888;"></i>
        <input type="text" placeholder="Tìm kiếm theo mã đơn hàng, tên sản phẩm...">
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-order">
            <i class="fa-solid fa-box-open"></i>
            <p>Chưa có đơn hàng nào trong mục này</p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
            $st    = $order['status'];
            $stInfo = getOrderStatusInfo($st);
            $is_cancelled = ($st === 4);
            ?>
            <div class="order-card">
                <!-- Header -->
                <div class="order-header">
                    <span style="font-weight:500; color:#555;">
                        <i class="fa-solid fa-store" style="margin-right:6px; color:#888;"></i>
                        Mã đơn hàng: <strong style="color:#333;">#<?= htmlspecialchars($order['order_id']) ?></strong>
                        <span style="color:#bbb; margin: 0 8px;">|</span>
                        <span style="font-size:12px; color:#888;"><?= date('d/m/Y', strtotime($order['order_date'])) ?></span>
                    </span>
                    <span class="status-chip" style="color:<?= $stInfo['color'] ?>; background:<?= $stInfo['bg'] ?>;">
                        <?= $stInfo['text'] ?>
                    </span>
                </div>

                <!-- Sản phẩm -->
                <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <img src="<?= htmlspecialchars($item['image_url'] ?? '') ?>"
                             alt="Product" class="item-img"
                             style="<?= $is_cancelled ? 'filter:grayscale(100%); opacity:0.6;' : '' ?>"
                             onerror="this.src='../../assets/images/logo-ntk.png'">
                        <div class="item-info">
                            <h4 class="item-name" style="<?= $is_cancelled ? 'color:#aaa;' : '' ?>">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </h4>
                            <?php if (!empty($item['variant_name'])): ?>
                                <div class="item-variant">Phân loại: <?= htmlspecialchars($item['variant_name']) ?></div>
                            <?php endif; ?>
                            <div class="item-qty">x<?= (int)$item['quantity'] ?></div>
                        </div>
                        <div class="item-price" style="<?= $is_cancelled ? 'color:#bbb;' : '' ?>">
                            ₫<?= number_format($item['price'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <?php if ($st === 3): ?>
                        <div style="padding:0 16px 12px 16px; display:flex; gap:8px; justify-content:flex-end;">
                            <button type="button" class="btn btn-outline" style="min-width:120px;" data-product-id="<?= htmlspecialchars($item['product_id']) ?>" data-product-name="<?= htmlspecialchars($item['product_name']) ?>" onclick="openReviewModal(this)">Đánh giá</button>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Footer -->
                <div class="order-footer">

                    <!-- Mã vận đơn nếu đang giao -->
                    <?php if (!empty($order['tracking_number']) && in_array($st, [2, 3])): ?>
                        <div class="tracking-info">
                            <i class="fa-solid fa-truck"></i> Mã vận đơn: <strong><?= htmlspecialchars($order['tracking_number']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <!-- Thông báo trạng thái đặc biệt -->
                    <?php if ($st === 5): ?>
                        <div class="info-box info-box-orange">
                            <i class="fa-solid fa-clock-rotate-left"></i> Yêu cầu trả hàng đang được Admin xem xét. Chúng tôi sẽ phản hồi trong vòng 24 giờ.
                        </div>
                    <?php elseif ($st === 6): ?>
                        <div class="info-box info-box-blue">
                            <i class="fa-solid fa-truck-ramp-box"></i> Yêu cầu trả hàng đã được duyệt. Vui lòng gửi hàng về cho chúng tôi trong vòng 3 ngày.
                        </div>
                    <?php elseif ($st === 7): ?>
                        <div class="info-box info-box-green">
                            <i class="fa-solid fa-circle-check"></i> Hoàn tiền thành công! Tiền đã được hoàn vào ví của bạn.
                        </div>
                    <?php elseif ($st === 8): ?>
                        <div class="info-box info-box-orange">
                            <i class="fa-solid fa-hourglass-half"></i> Yêu cầu hủy đơn đang chờ Admin phê duyệt.
                        </div>
                    <?php elseif ($st === 9): ?>
                        <div class="info-box info-box-red">
                            <i class="fa-solid fa-circle-exclamation"></i> Giao hàng không thành công. Đơn hàng đang được hoàn về kho.
                        </div>
                    <?php elseif ($st === 10): ?>
                        <div class="info-box info-box-red">
                            <i class="fa-solid fa-warehouse"></i> Hàng đã được hoàn về kho sau khi giao không thành công.
                        </div>
                    <?php endif; ?>

                    <!-- Tổng tiền -->
                    <div class="order-total">
                        Thành tiền: <span class="total-price">₫<?= number_format($order['total_amount'], 0, ',', '.') ?></span>
                    </div>

                    <!-- Nút hành động -->
                    <div class="order-actions">
                        <!-- Xem chi tiết (luôn hiện) -->
                        <a href="?view=chitietdonhang&id=<?= htmlspecialchars($order['order_id']) ?>" class="btn btn-outline">
                            <i class="fa-regular fa-file-lines" style="margin-right:5px;"></i> Xem chi tiết
                        </a>

                        <?php if ($st === 0): ?>
                            <!-- Chờ thanh toán: Thanh toán ngay + Hủy -->
                            <a href="../../order_success.php?id=<?= htmlspecialchars($order['order_id']) ?>&method=online" class="btn btn-primary">
                                <i class="fa-solid fa-credit-card" style="margin-right:5px;"></i> Thanh toán ngay
                            </a>
                            <a href="../../controllers/cancel_order.php?id=<?= htmlspecialchars($order['order_id']) ?>"
                               class="btn btn-danger-outline"
                               onclick="return confirm('Bạn chắc chắn muốn hủy đơn hàng này không?')">
                                Hủy đơn
                            </a>

                        <?php elseif ($st === 1): ?>
                            <!-- Chờ lấy hàng: Chỉ hủy (chưa giao) -->
                            <a href="../../controllers/cancel_order.php?id=<?= htmlspecialchars($order['order_id']) ?>"
                               class="btn btn-danger-outline"
                               onclick="return confirm('Bạn chắc chắn muốn hủy đơn hàng này không?')">
                                Hủy đơn
                            </a>

                        <?php elseif ($st === 2): ?>
                            <!-- Đang giao: Đã nhận hàng (ẩn nút hủy) -->
                            <a href="../../controllers/mark_received.php?id=<?= htmlspecialchars($order['order_id']) ?>"
                               class="btn btn-teal"
                               onclick="return confirm('Bạn xác nhận đã nhận được hàng và hàng không có vấn đề gì chứ?')">
                                <i class="fa-solid fa-check" style="margin-right:5px;"></i> Đã nhận được hàng
                            </a>

                        <?php elseif ($st === 3): ?>
                            <!-- Hoàn thành: Trả hàng + Mua lại – cùng hàng với Xem chi tiết -->
                            <button class="btn btn-outline"
                                onclick="document.getElementById('return-modal-<?= $order['order_id'] ?>').style.display='flex'">
                                <i class="fa-solid fa-rotate-left" style="margin-right:5px;"></i> Trả hàng
                            </button>
                            <a href="../../controllers/buy_again.php?id=<?= htmlspecialchars($order['order_id']) ?>" class="btn btn-primary">MUA LẠI</a>

                        <?php elseif ($st === 4): ?>
                            <!-- Đã hủy: Mua lại -->
                            <a href="../../controllers/buy_again.php?id=<?= htmlspecialchars($order['order_id']) ?>" class="btn btn-primary">MUA LẠI</a>

                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Modal Trả hàng (chỉ hiện khi status=3) -->
            <?php if ($st === 3): ?>
            <div id="return-modal-<?= $order['order_id'] ?>"
                 style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:8px; padding:30px; max-width:480px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
                    <h3 style="margin:0 0 20px; font-size:18px; color:#333;">Yêu cầu Trả hàng / Hoàn tiền</h3>
                    <p style="font-size:14px; color:#666; margin-bottom:20px;">Đơn hàng: <strong>#<?= $order['order_id'] ?></strong></p>

                    <form action="../../controllers/return_order.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">

                        <label style="display:block; font-size:14px; font-weight:600; margin-bottom:8px; color:#333;">Lý do trả hàng <span style="color:#e74c3c;">*</span></label>
                        <select name="reason" required
                                style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font-size:14px; margin-bottom:16px;">
                            <option value="">-- Chọn lý do --</option>
                            <option value="Hàng bị lỗi / hư hỏng">Hàng bị lỗi / hư hỏng</option>
                            <option value="Sai sản phẩm / sai màu / sai size">Sai sản phẩm / sai màu / sai size</option>
                            <option value="Hàng không đúng mô tả">Hàng không đúng mô tả</option>
                            <option value="Hàng bị thiếu, còn thiếu phụ kiện">Hàng bị thiếu, còn thiếu phụ kiện</option>
                            <option value="Đổi ý, không muốn mua nữa">Đổi ý, không muốn mua nữa</option>
                        </select>

                        <label style="display:block; font-size:14px; font-weight:600; margin-bottom:8px; color:#333;">Ảnh / Video bằng chứng</label>
                        <input type="file" name="return_image" accept="image/*,video/*"
                               style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; font-size:13px; margin-bottom:16px;">
                        <p style="font-size:12px; color:#999; margin-top:-12px; margin-bottom:16px;">Hỗ trợ: JPG, PNG, GIF, MP4 (tối đa 10MB)</p>

                        <div style="display:flex; gap:12px; margin-top:8px;">
                            <button type="button"
                                    onclick="document.getElementById('return-modal-<?= $order['order_id'] ?>').style.display='none'"
                                    style="flex:1; padding:11px; border:1px solid #ccc; background:#fff; border-radius:4px; font-size:14px; cursor:pointer;">
                                Hủy bỏ
                            </button>
                            <button type="submit"
                                    style="flex:1; padding:11px; background:#ee4d2d; color:#fff; border:none; border-radius:4px; font-size:14px; font-weight:600; cursor:pointer;">
                                Gửi yêu cầu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Review modal chung cho trang đơn hàng -->
    <div id="review-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#fff; border-radius:10px; width:100%; max-width:520px; padding:26px; box-shadow:0 16px 40px rgba(0,0,0,0.18); position:relative;">
            <button type="button" onclick="closeReviewModal()" style="position:absolute; top:12px; right:12px; border:none; background:transparent; font-size:18px; color:#555; cursor:pointer;">&times;</button>
            <h3 style="margin:0 0 16px; font-size:20px; color:#222;">Đánh giá sản phẩm</h3>
            <p id="review-modal-product" style="margin:0 0 18px; color:#555; font-size:14px;"></p>

            <form id="review-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_comment">
                <input type="hidden" name="product_id" id="review-product-id" value="">
                <input type="hidden" name="parent_id" value="">

                <label for="review-rating" style="display:block; font-weight:600; margin-bottom:8px; color:#333;">Số sao</label>
                <select id="review-rating" name="rating" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; margin-bottom:16px; font-size:14px;">
                    <option value="">Chọn đánh giá</option>
                    <option value="1">1 sao</option>
                    <option value="2">2 sao</option>
                    <option value="3">3 sao</option>
                    <option value="4">4 sao</option>
                    <option value="5">5 sao</option>
                </select>

                <label for="review-comment" style="display:block; font-weight:600; margin-bottom:8px; color:#333;">Nhận xét</label>
                <textarea id="review-comment" name="comment" rows="4" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; resize:vertical; font-size:14px; margin-bottom:16px;"></textarea>

                <label for="review-image" style="display:block; font-weight:600; margin-bottom:8px; color:#333;">Hình ảnh (tùy chọn)</label>
                <input type="file" id="review-image" name="review_image" accept="image/*" style="width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:6px; margin-bottom:10px;">
                <div id="review-image-preview" style="display:none; margin-bottom:16px;">
                    <img src="" alt="Preview" style="max-width:100%; border-radius:8px; border:1px solid #eee;">
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;">
                    <button type="button" onclick="closeReviewModal()" style="flex:1; min-width:120px; padding:12px 16px; border:1px solid #ccc; background:#fff; border-radius:6px; color:#333; cursor:pointer;">Hủy</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; min-width:120px;">Gửi đánh giá</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReviewModal(button) {
            var productId = button.getAttribute('data-product-id');
            var productName = button.getAttribute('data-product-name');
            document.getElementById('review-product-id').value = productId;
            document.getElementById('review-modal-product').innerText = 'Sản phẩm: ' + productName;
            document.getElementById('review-modal').style.display = 'flex';
            document.getElementById('review-form').reset();
            document.getElementById('review-image-preview').style.display = 'none';
        }

        function closeReviewModal() {
            document.getElementById('review-modal').style.display = 'none';
        }

        document.getElementById('review-image').addEventListener('change', function () {
            var file = this.files[0];
            var preview = document.querySelector('#review-image-preview img');
            if (file) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    document.getElementById('review-image-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('review-image-preview').style.display = 'none';
                preview.src = '';
            }
        });

        document.getElementById('review-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var form = e.currentTarget;
            var formData = new FormData(form);

            fetch('../../ajax_review.php', {
                method: 'POST',
                body: formData
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    alert('Cảm ơn bạn! Đánh giá đã được gửi.');
                    if (formData.get('product_id')) {
                        window.location.href = '../../product_detail.php?id=' + encodeURIComponent(formData.get('product_id')) + '&open_review=0';
                    } else {
                        closeReviewModal();
                    }
                } else {
                    alert(data.message || 'Không thể gửi đánh giá. Vui lòng thử lại.');
                }
            })
            .catch(function () {
                alert('Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại.');
            });
        });

        // Hook vào SSE chung từ header.php
        if (typeof window.handleOrderUpdate === 'undefined') {
            window.handleOrderUpdate = function(updates) {
                // updates là mảng các order có thay đổi
                if (updates && updates.length > 0) {
                    showToast('Đơn hàng cập nhật', 'Trạng thái đơn hàng của bạn vừa thay đổi!');
                    setTimeout(() => window.location.reload(), 2000);
                }
            };
        }
    </script>

</div>