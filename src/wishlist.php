<?php
require_once 'config/database.php';
include 'includes/header.php';

// Giả sử Bee đã có session user_id
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "<div class='container' style='text-align:center; padding: 100px 0;'>
            <h2>Đăng nhập để xem danh sách yêu thích!</h2>
            <a href='login.php' class='btn-buy-now' style='display:inline-block; width:200px; margin-top:20px;'>Đăng nhập ngay</a>
          </div>";
    include 'includes/footer.php';
    exit;
}

// Truy vấn lấy danh sách sản phẩm từ bảng Wishlist
$sql = "SELECT p.*, v.original_price, v.sale_price 
        FROM Wishlist w
        JOIN Products p ON w.product_id = p.product_id
        LEFT JOIN Product_Variants v ON p.product_id = v.product_id
        WHERE w.user_id = :u
        GROUP BY p.product_id";

$stmt = $conn->prepare($sql);
$stmt->execute(['u' => $user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container">
    <div class="section-header" style="text-align: center; margin-top: 40px;">
        <p>DANH SÁCH SẢN PHẨM ĐƯỢC YÊU THÍCH</p>
        <h2>WISHLIST</h2>
    </div>

    <?php if (count($wishlist_items) > 0): ?>
        <div class="product-grid">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="product-card" id="wish-item-<?php echo $item['product_id']; ?>">
                    <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                        <div class="img-wrapper">
                            <img src="<?php echo $item['image']; ?>" alt="">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo $item['name']; ?></h3>
                            <p class="price"><?php echo number_format($item['original_price'], 0, ',', '.'); ?>đ</p>
                        </div>
                    </a>
                    <button class="btn-remove-wish" onclick="removeWishlist('<?php echo $item['product_id']; ?>')">
                        <i class="fa fa-trash-o"></i> Xóa món này
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 50px;">
            <p>Chưa có món nào được chọn</p>
            <a href="product.php" style="color: #a6825c; text-decoration: underline;">Đi shopping ngay!</a>
        </div>
    <?php endif; ?>
</main>

<script>
function removeWishlist(prodId) {
    if(confirm('Bạn muốn bỏ yêu thích món này?')) {
        $.ajax({
            url: 'ajax_remove_wishlist.php',
            method: 'POST',
            data: { product_id: prodId },
            success: function(res) {
                console.log("Server trả về:", res); // Nhấn F12 chọn tab Console để xem cái này
                
                // Dùng .trim() để loại bỏ mọi khoảng trắng thừa nếu có
                if (res.trim() === 'success') {
                    $('#wish-item-' + prodId).fadeOut(500, function() {
                        $(this).remove(); // Xóa hẳn phần tử khỏi cây HTML sau khi ẩn
                    });
                } else {
                    alert('Lỗi: ' + res);
                }
            },
            error: function(xhr, status, error) {
                console.error("Lỗi AJAX:", error);
                alert('Không thể kết nối với máy chủ!');
            }
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
