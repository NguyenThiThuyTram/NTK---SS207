<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Xử lý khi Admin bấm nút Gửi câu trả lời từ giao diện quản trị này
if (isset($_POST['submit_reply'])) {
    $parent_id = intval($_POST['parent_id']);
    $product_id = $_POST['product_id'];
    $reply_comment = trim($_POST['reply_comment']);
    $admin_id = $_SESSION['user_id'] ?? null;

    if (!empty($reply_comment) && $admin_id) {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, parent_id, comment, created_at) VALUES (:uid, :pid, :parent, :text, NOW())");
        $stmt->execute(['uid' => $admin_id, 'pid' => $product_id, 'parent' => $parent_id, 'text' => $reply_comment]);
        echo "<script>alert('Đã gửi phản hồi của Quản trị viên thành công!'); window.location.href='reviews.php';</script>";
        exit;
    }
}

// Lấy danh sách các ĐÁNH GIÁ GỐC chưa được Admin trả lời để ưu tiên xử lý trước
$query = "SELECT r.*, u.fullname, p.name as product_name 
          FROM reviews r
          LEFT JOIN users u ON r.user_id = u.user_id
          LEFT JOIN products p ON r.product_id = p.product_id
          WHERE r.parent_id IS NULL 
            AND r.review_id NOT IN (SELECT DISTINCT parent_id FROM reviews WHERE parent_id IS NOT NULL)
          ORDER BY r.created_at DESC";
$all_reviews = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

$admin_current_page = 'reviews.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    .page-header { margin-bottom: 24px; }
    .page-title { font-size: 21px; font-weight: 700; color: #111111; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .page-subtitle { font-size: 13px; color: #999; }

    .review-box-container { display: flex; flex-direction: column; gap: 20px; margin-top: 20px; }
    .review-admin-card { background: #ffffff; border: 1px solid #e5e5e5; border-radius: 8px; padding: 20px 24px; }
    .rac-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f5f1eb; padding-bottom: 10px; margin-bottom: 12px; }
    .rac-user { font-weight: 700; font-size: 14px; color: #111; }
    .rac-prod { font-size: 13px; color: #a6825c; font-weight: 500; }
    .rac-stars { color: #ffc107; font-size: 12px; }
    .rac-text { font-size: 14.5px; color: #333; margin-bottom: 15px; line-height: 1.5; }
    
    .reply-form-admin { background: #fafaf8; padding: 15px; border-radius: 6px; border: 1px solid #e5e5e5; }
    .reply-textarea-admin { width: 100%; height: 55px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: none; font-size: 13.5px; outline: none; }
    .btn-submit-reply-admin { background: #2f1c00; color: #fff; border: none; padding: 7px 18px; border-radius: 4px; font-size: 12.5px; font-weight: 600; cursor: pointer; margin-top: 8px; }


</style>

<div class="page-header">
    <div class="page-title">Quản lý và phản hồi đánh giá</div>
    <div class="page-subtitle">Danh sách đánh giá gốc từ khách hàng cần phản hồi</div>
</div>

<div class="review-box-container">
    <?php if (empty($all_reviews)): ?>
        <div style="background:#fff; border:1px solid #e5e5e5; padding:4px; text-align:center; padding: 50px; color:#888; border-radius:8px;" class="review-admin-card">
            <i class="fa-regular fa-comment-dots" style="font-size:36px; margin-bottom:10px; display:block; opacity:0.5;"></i>
            Tuyệt vời! Bạn đã phản hồi hết toàn bộ đánh giá của khách hàng.
        </div>
    <?php else: ?>
        <?php foreach ($all_reviews as $r): ?>
            <div class="review-admin-card">
                <div class="rac-header">
                    <div>
                        <span class="rac-user"><?= htmlspecialchars($r['fullname'] ?: 'Khách ẩn danh') ?></span>
                        <span class="rac-stars">
                            <?php for ($i = 1; $i <= 5; $i++) echo ($i <= $r['rating']) ? '★' : '☆'; ?>
                        </span>
                    </div>
                    <div class="rac-prod">Sản phẩm: <?= htmlspecialchars($r['product_name']) ?></div>
                </div>
                <div class="rac-text">“ <?= htmlspecialchars($r['comment']) ?> ”</div>
                
                <form action="" method="POST" class="reply-form-admin">
                    <input type="hidden" name="parent_id" value="<?= $r['review_id'] ?>">
                    <input type="hidden" name="product_id" value="<?= $r['product_id'] ?>">
                    <textarea name="reply_comment" class="reply-textarea-admin" placeholder="Nhập câu trả lời của Quản trị viên tại đây..." required></textarea>
                    <div style="text-align: right;">
                        <button type="submit" name="submit_reply" class="btn-submit-reply-admin">Gửi câu trả lời</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div></main>
</body>
</html>