<?php
require_once '../config/database.php';
include '../includes/header.php';

// Giả sử lấy wishlist của User đang đăng nhập (U01)
$user_id = 'U01'; 
$sql = "SELECT p.* FROM Products p 
        JOIN Wishlist w ON p.product_id = w.product_id 
        WHERE w.user_id = :user_id";
$stmt = $conn->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$fav_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container">
    <h2 class="section-title">Sản phẩm bạn yêu thích</h2>
    <div class="product-grid">
        <?php if (empty($fav_products)): ?>
            <p>Chưa có sản phẩm nào trong danh sách yêu thích.</p>
        <?php else: ?>
            <?php foreach ($fav_products as $p): ?>
                <div class="product-card">
                    <a href="product_detail.php?id=<?php echo $p['product_id']; ?>">
                        <img src="../assets/images/<?php echo $p['image']; ?>" alt="">
                        <h3><?php echo $p['name']; ?></h3>
                        <p class="price"><?php echo number_format($p['price']); ?>đ</p>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>