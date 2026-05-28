<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_POST['submit_reply'])) {
    $parent_id = intval($_POST['parent_id']);
    $product_id = $_POST['product_id'];
    $reply_comment = trim($_POST['reply_comment']);
    $user_id_of_review = $_POST['user_id_of_review'];
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $give_voucher = isset($_POST['give_voucher']) ? 1 : 0;
    $admin_id = $_SESSION['user_id'] ?? null;

    if (!empty($reply_comment) && $admin_id) {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, parent_id, comment, created_at) VALUES (:uid, :pid, :parent, :text, NOW())");
        $stmt->execute(['uid' => $admin_id, 'pid' => $product_id, 'parent' => $parent_id, 'text' => $reply_comment]);
        
        $coupon_id = null;
        if ($give_voucher && $user_id_of_review) {
            $coupon_id = 'V' . substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
            $coupon_code = 'REWARD30K_' . time();
            $stmt_cp = $conn->prepare("INSERT INTO coupons (coupon_id, code, discount_value, discount_type, min_order_value, start_date, end_date, quantity, status, coupon_type) 
                VALUES (:cid, :code, 30000, 0, 0, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1, 1, 0)");
            $stmt_cp->execute([
                'cid' => $coupon_id,
                'code' => $coupon_code
            ]);
            
            $msg = "Người bán đã phản hồi đánh giá của bạn và tặng bạn một Voucher giảm 30K (Mã: $coupon_code) cho đơn hàng tiếp theo.";
            $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'system', 'Quà tặng từ Shop', :msg)");
            $stmt_notif->execute(['uid' => $user_id_of_review, 'msg' => $msg]);
        }

        $stmt_upd = $conn->prepare("UPDATE reviews SET is_pinned = :pin, reward_coupon_id = :cid WHERE review_id = :rid");
        $stmt_upd->execute(['pin' => $is_pinned, 'cid' => $coupon_id, 'rid' => $parent_id]);

        echo "<script>alert('Đã gửi phản hồi và cập nhật đánh giá thành công!'); window.location.href='reviews.php';</script>";
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
                
                <?php if (!empty($r['image'])): ?>
                    <div style="margin: 10px 0;">
                        <img src="../<?= htmlspecialchars($r['image']) ?>" alt="Hình đánh giá" style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 1px solid #eee; object-fit: cover; cursor:pointer;" onclick="window.open(this.src,'_blank')">
                    </div>
                <?php endif; ?>
                <?php if (!empty($r['video'])): ?>
                    <div style="margin: 10px 0;">
                        <video controls style="max-width: 280px; max-height: 200px; border-radius: 8px; border: 1px solid #eee;">
                            <source src="../<?= htmlspecialchars($r['video']) ?>" type="video/mp4">
                            <source src="../<?= htmlspecialchars($r['video']) ?>">
                            Trình duyệt không hỗ trợ phát video.
                        </video>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST" class="reply-form-admin">
                    <input type="hidden" name="parent_id" value="<?= $r['review_id'] ?>">
                    <input type="hidden" name="product_id" value="<?= $r['product_id'] ?>">
                    <input type="hidden" name="user_id_of_review" value="<?= htmlspecialchars($r['user_id']) ?>">
                    
                    <div style="margin-bottom: 10px; display: flex; gap: 15px; font-size: 13px;">
                        <label><input type="checkbox" name="is_pinned" value="1"> <i class="fa-solid fa-thumbtack" style="color:#e74c3c"></i> Ghim lên đầu</label>
                        <label><input type="checkbox" name="give_voucher" value="1"> <i class="fa-solid fa-gift" style="color:#27ae60"></i> Tặng Voucher 30K</label>
                    </div>

                    <textarea name="reply_comment" class="reply-textarea-admin" placeholder="Nhập câu trả lời của Quản trị viên tại đây..." required></textarea>
                    <div style="text-align: right;">
                        <button type="submit" name="submit_reply" class="btn-submit-reply-admin">Gửi câu trả lời & Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div></main>
</body>
</html>