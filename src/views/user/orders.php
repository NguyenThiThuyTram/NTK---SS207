<?php
// Đảm bảo chạy trong dashboard.php
$user_id = $_SESSION['user_id'];

// 1. LẤY TRẠNG THÁI HIỆN TẠI TỪ URL (Mặc định là 'all')
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// 2. CHUẨN BỊ CÂU LỆNH SQL VÀ MẢNG THAM SỐ
$params = [':user_id' => $user_id];
$status_condition = "";

if ($current_tab !== 'all') {
    // Đã sửa thành order_status theo đúng DB
    $status_condition = "AND o.order_status = :status";
    $params[':status'] = $current_tab;
}

/* * 3. TRUY VẤN JOIN CHUẨN THEO NTK.SQL:
 * Đi từ Orders -> Order_Details -> Product_Variants -> Products
 */
$sql = "
    SELECT 
        o.order_id, o.order_status, o.final_price, o.order_date,
        od.quantity, od.price,
        p.name AS product_name, p.image AS product_image,
        v.color, v.size, v.image AS variant_image
    FROM Orders o
    JOIN Order_Details od ON o.order_id = od.order_id
    JOIN Product_Variants v ON od.variant_id = v.variant_id
    JOIN Products p ON v.product_id = p.product_id
    WHERE o.user_id = :user_id $status_condition
    ORDER BY o.order_date DESC
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gom nhóm các sản phẩm theo chung 1 Đơn hàng
    $orders = [];
    foreach ($results as $row) {
        $oid = $row['order_id'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'order_id' => $oid,
                'status' => $row['order_status'],
                'total_amount' => $row['final_price'],
                'items' => []
            ];
        }
        
        // Gộp Color và Size thành tên Phân loại
        $variant_parts = [];
        if (!empty($row['color'])) $variant_parts[] = $row['color'];
        if (!empty($row['size'])) $variant_parts[] = $row['size'];
        $row['variant_name'] = implode(', ', $variant_parts);
        
        // Ưu tiên lấy ảnh phân loại, nếu không có lấy ảnh sản phẩm gốc
        $row['image_url'] = !empty($row['variant_image']) ? $row['variant_image'] : $row['product_image'];
        
        $orders[$oid]['items'][] = $row;
    }
} catch (PDOException $e) {
    echo "<div style='padding:20px; background:#fff3cd; color:#856404; border:1px solid #ffeeba;'>Lỗi SQL: " . $e->getMessage() . "</div>";
}

// Hàm dịch trạng thái
function getOrderStatus($status_code) {
    switch ($status_code) {
        case 0: return ['text' => 'CHỜ THANH TOÁN', 'color' => '#ee4d2d'];
        case 1: return ['text' => 'VẬN CHUYỂN', 'color' => '#26aa99'];
        case 2: return ['text' => 'CHỜ GIAO HÀNG', 'color' => '#26aa99'];
        case 3: return ['text' => 'HOÀN THÀNH', 'color' => '#26aa99'];
        case 4: return ['text' => 'ĐÃ HỦY', 'color' => '#888888'];
        case 5: return ['text' => 'TRẢ HÀNG/HOÀN TIỀN', 'color' => '#ee4d2d'];
        default: return ['text' => 'KHÔNG RÕ', 'color' => '#333'];
    }
}
?>

<style>
    /* CSS chuẩn bài E-commerce */
    .order-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #333; background-color: #f5f5f5; }
    .order-tabs { display: flex; background: #fff; margin-bottom: 15px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow-x: auto; }
    .order-tabs a { flex: 1; text-align: center; padding: 15px 10px; text-decoration: none; color: #555; font-size: 15px; border-bottom: 2px solid transparent; white-space: nowrap; transition: 0.2s; }
    .order-tabs a:hover { color: #ee4d2d; }
    .order-tabs a.active { color: #ee4d2d; border-bottom: 2px solid #ee4d2d; }
    .order-search { background: #eaeaea; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px; display: flex; align-items: center;}
    .order-search input { border: none; background: transparent; outline: none; width: 100%; margin-left: 10px; font-size: 14px;}
    .order-card { background: #fff; margin-bottom: 15px; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .order-header { display: flex; justify-content: space-between; padding: 15px; border-bottom: 1px solid #f0f0f0; font-size: 14px; font-weight: 500; }
    .order-item { display: flex; padding: 15px; border-bottom: 1px solid #f0f0f0; }
    .item-img { width: 80px; height: 80px; object-fit: cover; border: 1px solid #e1e1e1; margin-right: 15px; }
    .item-info { flex: 1; }
    .item-name { font-size: 16px; margin: 0 0 5px; color: #333; font-weight: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .item-variant { font-size: 14px; color: #888; margin-bottom: 5px; }
    .item-qty { font-size: 14px; color: #333; }
    .item-price { font-size: 14px; color: #ee4d2d; font-weight: 500; min-width: 80px; text-align: right; }
    .order-footer { padding: 15px; text-align: right; background: #fffcfb; }
    .order-total { font-size: 14px; color: #333; margin-bottom: 15px; }
    .total-price { font-size: 20px; color: #ee4d2d; font-weight: bold; margin-left: 10px; }
    .order-actions { display: flex; justify-content: flex-end; gap: 10px; }
    .btn { padding: 8px 15px; border-radius: 2px; font-size: 14px; cursor: pointer; text-decoration: none; }
    .btn-primary { background: #ee4d2d; color: #fff; border: 1px solid #ee4d2d; }
    .btn-outline { background: #fff; color: #555; border: 1px solid #ccc; }
    .empty-order { text-align: center; padding: 50px 20px; background: #fff; color: #888; }
</style>

<div class="order-container">
    
    <div class="order-tabs">
        <a href="?view=donmua&tab=all" class="<?= $current_tab === 'all' ? 'active' : '' ?>">Tất cả</a>
        <a href="?view=donmua&tab=0" class="<?= $current_tab === '0' ? 'active' : '' ?>">Chờ thanh toán</a>
        <a href="?view=donmua&tab=1" class="<?= $current_tab === '1' ? 'active' : '' ?>">Vận chuyển</a>
        <a href="?view=donmua&tab=2" class="<?= $current_tab === '2' ? 'active' : '' ?>">Chờ giao hàng</a>
        <a href="?view=donmua&tab=3" class="<?= $current_tab === '3' ? 'active' : '' ?>">Hoàn thành</a>
        <a href="?view=donmua&tab=4" class="<?= $current_tab === '4' ? 'active' : '' ?>">Đã hủy</a>
        <a href="?view=donmua&tab=5" class="<?= $current_tab === '5' ? 'active' : '' ?>">Trả hàng/Hoàn tiền</a>
    </div>

    <div class="order-search">
        <i class="fa-solid fa-magnifying-glass" style="color: #888;"></i>
        <input type="text" placeholder="Bạn có thể tìm kiếm theo tên Shop, ID đơn hàng hoặc Tên Sản phẩm">
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-order">
            <i class="fa-solid fa-box-open" style="font-size: 50px; margin-bottom: 15px; color: #ccc;"></i>
            <p>Chưa có đơn hàng nào trong trạng thái này</p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php $status_info = getOrderStatus($order['status']); ?>
            <div class="order-card">
                <div class="order-header">
                    <span class="shop-name">Mã đơn hàng: #<?= htmlspecialchars($order['order_id']) ?></span>
                    <span class="order-status" style="color: <?= $status_info['color'] ?>; font-weight:bold;">
                        <?= $status_info['text'] ?>
                    </span>
                </div>
                
                <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <?php $img_style = ($order['status'] == 4) ? "filter: grayscale(100%); opacity: 0.7;" : ""; ?>
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="Product" class="item-img" style="<?= $img_style ?>">
                        
                        <div class="item-info">
                            <h4 class="item-name" <?= ($order['status'] == 4) ? 'style="color:#888;"' : '' ?>>
                                <?= htmlspecialchars($item['product_name']) ?>
                            </h4>
                            <?php if (!empty($item['variant_name'])): ?>
                                <div class="item-variant">Phân loại hàng: <?= htmlspecialchars($item['variant_name']) ?></div>
                            <?php endif; ?>
                            <div class="item-qty">x<?= htmlspecialchars($item['quantity']) ?></div>
                        </div>
                        <div class="item-price" <?= ($order['status'] == 4) ? 'style="color:#888;"' : '' ?>>
                            ₫<?= number_format($item['price'], 0, ',', '.') ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="order-footer">
                    <div class="order-total">
                        Thành tiền: <span class="total-price">₫<?= number_format($order['total_amount'], 0, ',', '.') ?></span>
                    </div>
                    <div class="order-actions">
                        <?php if ($order['status'] == 3): ?>
                            <button class="btn btn-primary">Mua lại</button>
                            <button class="btn btn-outline">Đánh giá</button>
                        <?php elseif ($order['status'] == 0): ?>
                            <button class="btn btn-primary">Thanh toán ngay</button>
                            <button class="btn btn-outline">Hủy đơn</button>
                        <?php elseif ($order['status'] == 1 || $order['status'] == 2): ?>
                            <button class="btn btn-primary" style="background:#26aa99; border-color:#26aa99;">Đã nhận được hàng</button>
                        <?php else: ?>
                            <button class="btn btn-outline">Xem chi tiết đơn</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>