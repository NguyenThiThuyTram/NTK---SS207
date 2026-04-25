<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// Handle filter
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

$whereClause = "1=1";
$params = [];

if ($tab === '0') {
    $whereClause .= " AND o.order_status = 0"; // Chờ xác nhận
} elseif ($tab === 'wait_pay') {
    $whereClause .= " AND o.payment_status = 0 AND o.order_status NOT IN (3, 4)"; // Chờ thanh toán
} elseif ($tab === '1') {
    $whereClause .= " AND o.order_status = 1"; // Đang xử lý
} elseif ($tab === '2') {
    $whereClause .= " AND o.order_status = 2"; // Đang giao
} elseif ($tab === '3') {
    $whereClause .= " AND o.order_status = 3"; // Hoàn thành
} elseif ($tab === '4') {
    $whereClause .= " AND o.order_status = 4"; // Đã hủy
} elseif ($tab === '5') {
    $whereClause .= " AND o.order_status = 5"; // Trả hàng
}

// Search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $whereClause .= " AND (o.order_id LIKE ? OR o.fullname LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Fetch orders
$query = "
    SELECT o.*, 
           (SELECT pv.image FROM order_details od JOIN product_variants pv ON od.variant_id = pv.variant_id WHERE od.order_id = o.order_id LIMIT 1) as variant_img,
           (SELECT p.image FROM order_details od JOIN product_variants pv ON od.variant_id = pv.variant_id JOIN products p ON pv.product_id = p.product_id WHERE od.order_id = o.order_id LIMIT 1) as product_img,
           (SELECT p.name FROM order_details od JOIN product_variants pv ON od.variant_id = pv.variant_id JOIN products p ON pv.product_id = p.product_id WHERE od.order_id = o.order_id LIMIT 1) as first_product_name
    FROM orders o
    WHERE $whereClause
    ORDER BY o.order_date DESC
";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts for tabs
$counts = [
    'all' => 0, '0' => 0, 'wait_pay' => 0, '1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0
];
$countStmt = $conn->query("SELECT order_status, payment_status FROM orders");
while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
    $counts['all']++;
    if ($row['order_status'] == 0) $counts['0']++;
    if ($row['payment_status'] == 0 && !in_array($row['order_status'], [3,4])) $counts['wait_pay']++;
    if ($row['order_status'] == 1) $counts['1']++;
    if ($row['order_status'] == 2) $counts['2']++;
    if ($row['order_status'] == 3) $counts['3']++;
    if ($row['order_status'] == 4) $counts['4']++;
    if ($row['order_status'] == 5) $counts['5']++;
}

$admin_current_page = 'orders.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    
    .page-header { margin-bottom: 24px; }
    .page-title { font-size: 24px; font-weight: 600; color: #111; margin-bottom: 4px; }
    .page-subtitle { font-size: 14px; color: #888; }

    /* Tabs */
    .tabs-container {
        display: flex;
        gap: 32px;
        border-bottom: 2px solid #e5e5e5;
        margin-bottom: 20px;
        overflow-x: auto;
    }
    .tab-item {
        padding: 12px 0;
        font-size: 14px;
        font-weight: 500;
        color: #555;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }
    .tab-item:hover { color: #111; }
    .tab-item.active {
        color: #2f1c00;
        border-bottom-color: #2f1c00;
        font-weight: 600;
    }
    .badge {
        background: #f5f1eb;
        color: #2f1c00;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    /* Toolbar */
    .toolbar {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
    }
    .search-input {
        flex: 1;
        padding: 10px 16px;
        padding-left: 40px;
        border: 1px solid #e5e5e5;
        border-radius: 6px;
        font-size: 14px;
        outline: none;
        background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%23888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>') no-repeat 12px center;
    }
    .filter-select {
        padding: 10px 16px;
        border: 1px solid #e5e5e5;
        border-radius: 6px;
        font-size: 14px;
        outline: none;
        background: #fff;
        color: #333;
    }

    /* Table */
    .table-container {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        overflow: hidden;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    .data-table th {
        background: #fafaf8;
        padding: 14px 20px;
        font-size: 11px;
        font-weight: 700;
        color: #888;
        text-transform: uppercase;
        text-align: left;
        border-bottom: 1px solid #e5e5e5;
    }
    .data-table td {
        padding: 16px 20px;
        font-size: 14px;
        color: #333;
        border-bottom: 1px solid #f5f1eb;
        vertical-align: middle;
    }
    .data-table tr:hover { background: #fafaf8; }
    
    /* Product Cell */
    .product-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .product-img {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        object-fit: cover;
    }

    /* Status Badges */
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        white-space: nowrap;
    }
    .status-pay-done { background: #2f1c00; color: #fff; }
    .status-pay-wait { background: #f5f5f5; color: #888; }
    .status-pay-cod { background: #fdf0ef; color: #c0392b; }

    .status-order-wait { background: #fdf5e6; color: #f39c12; }
    .status-order-process { background: #eaf2fd; color: #3498db; }
    .status-order-shipping { background: #fcf3cf; color: #d35400; }
    .status-order-done { background: #eafaf1; color: #27ae60; }
    .status-order-cancel { background: #fdf0ef; color: #c0392b; }

    /* Actions */
    .action-btn {
        background: none;
        border: none;
        color: #888;
        cursor: pointer;
        font-size: 16px;
        padding: 4px;
        transition: color 0.2s;
        text-decoration: none;
    }
    .action-btn:hover { color: #111; }
</style>

<div class="page-header">
    <div class="page-title">Quản lý đơn hàng</div>
    <div class="page-subtitle"><?= $counts['all'] ?> đơn hàng</div>
</div>

<div class="tabs-container">
    <a href="?tab=all" class="tab-item <?= $tab == 'all' ? 'active' : '' ?>">Tất cả <span class="badge"><?= $counts['all'] ?></span></a>
    <a href="?tab=0" class="tab-item <?= $tab == '0' ? 'active' : '' ?>">Chờ xác nhận <span class="badge"><?= $counts['0'] ?></span></a>
    <a href="?tab=wait_pay" class="tab-item <?= $tab == 'wait_pay' ? 'active' : '' ?>">Chờ thanh toán <span class="badge"><?= $counts['wait_pay'] ?></span></a>
    <a href="?tab=1" class="tab-item <?= $tab == '1' ? 'active' : '' ?>">Đang xử lý <span class="badge"><?= $counts['1'] ?></span></a>
    <a href="?tab=2" class="tab-item <?= $tab == '2' ? 'active' : '' ?>">Đang giao <span class="badge"><?= $counts['2'] ?></span></a>
    <a href="?tab=3" class="tab-item <?= $tab == '3' ? 'active' : '' ?>">Hoàn thành <span class="badge"><?= $counts['3'] ?></span></a>
    <a href="?tab=4" class="tab-item <?= $tab == '4' ? 'active' : '' ?>">Đã hủy <span class="badge"><?= $counts['4'] ?></span></a>
    <a href="?tab=5" class="tab-item <?= $tab == '5' ? 'active' : '' ?>">Trả hàng <span class="badge"><?= $counts['5'] ?></span></a>
</div>

<div class="toolbar">
    <form action="" method="GET" style="display:flex; flex:1; gap:16px;">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <input type="text" name="search" class="search-input" placeholder="Tìm theo mã đơn, tên khách hàng..." value="<?= htmlspecialchars($search) ?>">
        <select class="filter-select">
            <option value="">Trạng thái thanh toán</option>
        </select>
        <select class="filter-select">
            <option value="">Đơn vị vận chuyển</option>
        </select>
        <button type="submit" style="display:none;"></button>
    </form>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px;"><input type="checkbox"></th>
                <th>Mã Đơn</th>
                <th>Khách Hàng</th>
                <th>Sản Phẩm</th>
                <th>Tổng Tiền</th>
                <th>Thanh Toán</th>
                <th>Trạng Thái</th>
                <th>Ngày Đặt</th>
                <th>Hành Động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="9" style="text-align:center; padding: 40px; color:#888;">Không tìm thấy đơn hàng nào.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><input type="checkbox"></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($o['order_id']) ?></td>
                        <td><?= htmlspecialchars($o['fullname']) ?></td>
                        <td>
                            <div class="product-cell">
                                <?php 
                                    $img = $o['variant_img'] ?: $o['product_img'];
                                    if ($img): 
                                        $img_src = (strpos($img, 'http') === 0) ? $img : '../' . $img;
                                ?>
                                    <img src="<?= htmlspecialchars($img_src) ?>" class="product-img" onerror="this.outerHTML='<div class=\'product-img\' style=\'background:#f5f1eb;display:flex;align-items:center;justify-content:center;color:#ccc;\'><i class=\'fa-solid fa-box\'></i></div>'">
                                <?php else: ?>
                                    <div class="product-img" style="background:#f5f1eb;display:flex;align-items:center;justify-content:center;color:#ccc;"><i class="fa-solid fa-box"></i></div>
                                <?php endif; ?>
                                <span style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($o['first_product_name']) ?>">
                                    <?= htmlspecialchars($o['first_product_name'] ?? 'Không rõ') ?>
                                </span>
                            </div>
                        </td>
                        <td style="font-weight: 500;"><?= number_format($o['final_price'] ?? 0, 0, ',', '.') ?> đ</td>
                        <td>
                            <?php if ($o['payment_status'] == 1): ?>
                                <span class="status-badge status-pay-done">Đã thanh toán</span>
                            <?php else: ?>
                                <?php if ($o['payment_method'] == 2): // COD ?>
                                    <span class="status-badge status-pay-cod">COD</span>
                                <?php else: ?>
                                    <span class="status-badge status-pay-wait">Chờ thanh toán</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $statusMap = [
                                    0 => ['class' => 'status-order-wait', 'text' => 'Chờ xác nhận'],
                                    1 => ['class' => 'status-order-process', 'text' => 'Đang xử lý'],
                                    2 => ['class' => 'status-order-shipping', 'text' => 'Đang giao'],
                                    3 => ['class' => 'status-order-done', 'text' => 'Hoàn thành'],
                                    4 => ['class' => 'status-order-cancel', 'text' => 'Đã hủy'],
                                    5 => ['class' => 'status-order-cancel', 'text' => 'Trả hàng']
                                ];
                                $s = $statusMap[$o['order_status']] ?? ['class' => 'status-order-wait', 'text' => 'Chờ xác nhận'];
                            ?>
                            <span class="status-badge <?= $s['class'] ?>"><?= $s['text'] ?></span>
                        </td>
                        <td><?= date('Y-m-d', strtotime($o['order_date'])) ?></td>
                        <td>
                            <a href="order_detail.php?id=<?= urlencode($o['order_id']) ?>" class="action-btn" title="Xem chi tiết"><i class="fa-regular fa-eye"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
</main>
</body>
</html>
