<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'includes/loyalty_utils.php';

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
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Bạn phải mua và nhận sản phẩm này thành công mới có quyền để lại đánh giá!']);
            exit;
        }
    } else {
        // TRƯỜNG HỢP 2: Viết phản hồi con -> Chỉ duy nhất ADMIN (role = 1) mới có quyền
        if (intval($user_role) !== 1) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Chỉ có Quản trị viên mới có quyền phản hồi đánh giá này!']);
            exit;
        }
        $rating = null; // Phản hồi không cần số sao
    }

    $review_image = null;
    if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['review_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Lỗi tải ảnh (Mã lỗi: ' . $_FILES['review_image']['error'] . '). Có thể file vượt quá dung lượng cho phép của máy chủ.']);
            exit;
        }

        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['review_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext, true)) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Chỉ cho phép tập tin ảnh JPG, PNG, GIF, WEBP.']);
            exit;
        }

        // Giới hạn kích thước ảnh tối đa 5MB
        if ($_FILES['review_image']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Kích thước ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.']);
            exit;
        }

        $upload_dir = __DIR__ . '/assets/uploads/reviews/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $unique_name = 'review_img_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $file_ext;
        $destination = $upload_dir . $unique_name;

        if (move_uploaded_file($_FILES['review_image']['tmp_name'], $destination)) {
            $review_image = 'assets/uploads/reviews/' . $unique_name;
        } else {
            $err = error_get_last();
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Không thể lưu ảnh trên server. Lỗi hệ thống: ' . ($err['message'] ?? 'Unknown error') . ' | Destination: ' . $destination]);
            exit;
        }
    }

    $review_video = null;
    if (isset($_FILES['review_video']) && $_FILES['review_video']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['review_video']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Lỗi tải video (Mã lỗi: ' . $_FILES['review_video']['error'] . '). Có thể file vượt quá dung lượng cho phép của máy chủ.']);
            exit;
        }

        $allowed_video_ext = ['mp4', 'mov', 'avi', 'mkv', 'webm', '3gp'];
        $file_ext = strtolower(pathinfo($_FILES['review_video']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_video_ext, true)) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Chỉ cho phép tập tin video MP4, MOV, AVI, MKV, WEBM.']);
            exit;
        }

        // Giới hạn kích thước video tối đa 15MB
        if ($_FILES['review_video']['size'] > 15 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Kích thước video quá lớn! Vui lòng chọn video dưới 15MB.']);
            exit;
        }

        $upload_dir = __DIR__ . '/assets/uploads/reviews/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $unique_name = 'review_vid_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $file_ext;
        $destination = $upload_dir . $unique_name;

        if (move_uploaded_file($_FILES['review_video']['tmp_name'], $destination)) {
            $review_video = 'assets/uploads/reviews/' . $unique_name;
        } else {
            $err = error_get_last();
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Không thể lưu video trên server. Lỗi hệ thống: ' . ($err['message'] ?? 'Unknown error') . ' | Destination: ' . $destination]);
            exit;
        }
    }

    try {
        $sql = "INSERT INTO reviews (user_id, product_id, parent_id, rating, comment, image, video, created_at) 
                VALUES (:uid, :pid, :parent, :rate, :text, :img, :vid, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'uid'    => $user_id,
            'pid'    => $product_id,
            'parent' => !empty($parent_id) ? $parent_id : null,
            'rate'   => $rating,
            'text'   => $comment,
            'img'    => $review_image,
            'vid'    => $review_video,
        ]);

        // Cộng điểm thưởng nếu là đánh giá gốc
        $reward_points = 0;
        if (empty($parent_id)) {
            if (!empty($comment)) $reward_points += 50;          // +50 điểm cho nội dung text
            if (!empty($review_image)) $reward_points += 50;     // +50 điểm khi đính kèm hình ảnh
            if (!empty($review_video)) $reward_points += 50;     // +50 điểm khi đính kèm video
            
            if ($reward_points > 0) {
                addLoyaltyPoints($conn, $user_id, $reward_points, "đánh giá sản phẩm");
            }
        }

        echo json_encode(['status' => 'success', 'success' => true, 'points_earned' => $reward_points, 'message' => 'Gửi dữ liệu thành công!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
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