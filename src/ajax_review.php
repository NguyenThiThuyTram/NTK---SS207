<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 0; // 1: Admin, 0: Khách hàng

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập để thực hiện hành động này!']);
    exit;
}

$action = $_POST['action'] ?? '';

// ===================== XỬ LÝ GỬI BÌNH LUẬN & PHẢN HỒI =====================
if ($action === 'submit_comment') {
    $product_id = $_POST['product_id'] ?? '';
    $parent_id  = $_POST['parent_id'] ?? null; 
    $comment    = trim($_POST['comment'] ?? '');
    $rating     = isset($_POST['rating']) ? floatval($_POST['rating']) : 5;

    if (empty($comment)) {
        echo json_encode(['status' => 'error', 'message' => 'Nội dung không được để trống!']);
        exit;
    }

    // PHÂN NHÁNH KIỂM TRA QUYỀN
    if (empty($parent_id)) {
        // TRƯỜNG HỢP 1: Đánh giá gốc -> Ép buộc phải mua hàng và đơn phải Hoàn thành (status = 3)
        $sql_check = "SELECT COUNT(*) FROM orders o 
                      JOIN order_details od ON o.order_id = od.order_id 
                      WHERE o.user_id = :uid AND od.variant_id IN (SELECT variant_id FROM product_variants WHERE product_id = :pid) AND o.order_status = 3";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute(['uid' => $user_id, 'pid' => $product_id]);
        
        if (intval($stmt_check->fetchColumn()) === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn phải mua và nhận sản phẩm này thành công mới có quyền để lại đánh giá!']);
            exit;
        }
    } else {
        // TRƯỜNG HỢP 2: Viết phản hồi con -> Chỉ duy nhất ADMIN (role = 1) mới có quyền
        if (intval($user_role) !== 1) {
            echo json_encode(['status' => 'error', 'message' => 'Chỉ có Quản trị viên mới có quyền phản hồi đánh giá này!']);
            exit;
        }
        $rating = null; // Phản hồi không cần số sao
    }

    try {
        $sql = "INSERT INTO reviews (user_id, product_id, parent_id, rating, comment, created_at) 
                VALUES (:uid, :pid, :parent, :rate, :text, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'uid'    => $user_id,
            'pid'    => $product_id,
            'parent' => !empty($parent_id) ? $parent_id : null,
            'rate'   => $rating,
            'text'   => $comment
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Gửi dữ liệu thành công!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit;
}

// ===================== XỬ LÝ LIKE / UNLIKE BẤT ĐỒNG BỘ =====================
if ($action === 'toggle_like') {
    $review_id = intval($_POST['review_id'] ?? 0);

    // Kiểm tra xem user này đã thích bình luận này chưa
    $st_check = $conn->prepare("SELECT COUNT(*) FROM review_likes WHERE review_id = :rid AND user_id = :uid");
    $st_check->execute(['rid' => $review_id, 'uid' => $user_id]);
    $has_liked = (intval($st_check->fetchColumn()) > 0);

    try {
        if ($has_liked) {
            // Đã thích -> Thực hiện UNLIKE (Xóa vết khỏi bảng)
            $del = $conn->prepare("DELETE FROM review_likes WHERE review_id = :rid AND user_id = :uid");
            $del->execute(['rid' => $review_id, 'uid' => $user_id]);
            $status = 'unliked';
        } else {
            // Chưa thích -> Thực hiện LIKE (Thêm vết vào bảng)
            $ins = $conn->prepare("INSERT INTO review_likes (review_id, user_id) VALUES (:rid, :uid)");
            $ins->execute(['rid' => $review_id, 'uid' => $user_id]);
            $status = 'liked';
        }

        // Đếm lại tổng số lượt thích hiện tại của bình luận này để trả về giao diện
        $st_count = $conn->prepare("SELECT COUNT(*) FROM review_likes WHERE review_id = :rid");
        $st_count->execute(['rid' => $review_id]);
        $total_likes = intval($st_count->fetchColumn());

        echo json_encode(['status' => 'success', 'like_status' => $status, 'total_likes' => $total_likes]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi tương tác: ' . $e->getMessage()]);
    }
    exit;
}
?>