<?php
require_once '../config/database.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../views/login.php');
    exit;
}

$order_id = $_GET['id'] ?? '';
if (!$order_id) {
    header('Location: ../index.php');
    exit;
}

try {
    // Check if the order belongs to this user
    $stmt_check = $conn->prepare("SELECT user_id FROM orders WHERE order_id = :oid AND user_id = :uid");
    $stmt_check->execute(['oid' => $order_id, 'uid' => $user_id]);
    if (!$stmt_check->fetch()) {
        header('Location: ../index.php');
        exit;
    }

    // Lấy các sản phẩm của đơn hàng
    $stmt = $conn->prepare("SELECT variant_id, quantity FROM order_details WHERE order_id = :oid");
    $stmt->execute(['oid' => $order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        echo "<script>alert('Không tìm thấy sản phẩm nào trong đơn hàng này.'); window.location.href='../views/user/dashboard.php?view=donmua';</script>";
        exit;
    }

    // Bỏ chọn tất cả sản phẩm trong giỏ hàng hiện tại để chỉ mua những sản phẩm này
    $conn->prepare("UPDATE cart SET is_selected = 0 WHERE user_id = :uid")->execute(['uid' => $user_id]);

    $added_count = 0;

    foreach ($items as $item) {
        $variant_id = $item['variant_id'];
        $quantity = $item['quantity'];
        
        // Kiểm tra biến thể & tồn kho
        $st_variant = $conn->prepare("SELECT stock FROM product_variants WHERE variant_id = :vid AND is_active = 1");
        $st_variant->execute(['vid' => $variant_id]);
        $variant = $st_variant->fetch(PDO::FETCH_ASSOC);

        if (!$variant || $variant['stock'] < 1) {
            continue; // Hết hàng hoặc không tồn tại, bỏ qua
        }

        if ($quantity > $variant['stock']) {
            $quantity = $variant['stock'];
        }

        // Kiểm tra xem đã có trong giỏ hàng chưa
        $st_cart = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = :uid AND variant_id = :vid");
        $st_cart->execute(['uid' => $user_id, 'vid' => $variant_id]);
        $existing = $st_cart->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Cập nhật số lượng và chọn lại
            $upd = $conn->prepare("UPDATE cart SET quantity = :qty, is_selected = 1 WHERE cart_id = :cid");
            $upd->execute(['qty' => $quantity, 'cid' => $existing['cart_id']]);
            $added_count++;
        } else {
            // Thêm mới
            // Lấy số lớn nhất hiện tại để sinh cart_id (tương tự như ajax_cart.php)
            $stMax = $conn->prepare("SELECT MAX(CAST(SUBSTRING(cart_id, 2) AS UNSIGNED)) FROM cart");
            $stMax->execute();
            $maxNum = intval($stMax->fetchColumn()) + 1;
            $cart_id = 'C' . str_pad($maxNum, 4, '0', STR_PAD_LEFT);

            $ins = $conn->prepare("INSERT INTO cart (cart_id, user_id, variant_id, quantity, is_selected) VALUES (:cid, :uid, :vid, :qty, 1)");
            $ins->execute([
                'cid' => $cart_id,
                'uid' => $user_id,
                'vid' => $variant_id,
                'qty' => $quantity
            ]);
            $added_count++;
        }
    }

    if ($added_count > 0) {
        // Chuyển hướng tới trang thanh toán
        header('Location: ../checkout.php');
    } else {
        echo "<script>alert('Các sản phẩm trong đơn hàng này hiện đã hết hàng hoặc ngừng kinh doanh.'); window.location.href='../views/user/dashboard.php?view=donmua';</script>";
    }
    exit;

} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
    exit;
}
?>
