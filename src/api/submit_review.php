<?php
/**
 * API: submit_review.php
 * ─────────────────────────────────────────────────────────────────
 * Xử lý backend nhận đánh giá sản phẩm từ người dùng.
 * - Yêu cầu đăng nhập + đã mua hàng hoàn thành (status = 3).
 * - Upload ảnh (review_image) & video (review_video) vào assets/uploads/reviews/.
 * - Lưu vào bảng `reviews`.
 * ─────────────────────────────────────────────────────────────────
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/loyalty_utils.php';

header('Content-Type: application/json; charset=utf-8');

// ── 1. Kiểm tra đăng nhập ──────────────────────────────────────────────────
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Vui lòng đăng nhập để gửi đánh giá!']);
    exit;
}

// ── 2. Lấy dữ liệu từ form ─────────────────────────────────────────────────
$product_id = $_POST['product_id'] ?? '';
$rating     = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment    = trim($_POST['comment'] ?? '');
$detail_id  = $_POST['detail_id'] ?? '';

// Validate cơ bản
if (empty($product_id)) {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Thiếu mã sản phẩm!']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Vui lòng chọn số sao từ 1 đến 5!']);
    exit;
}
if (empty($comment)) {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Nội dung nhận xét không được để trống!']);
    exit;
}

// ── 3. Kiểm tra quyền: Phải mua hàng + đơn Hoàn thành (status = 3) ────────
$sql_check = "SELECT COUNT(*) FROM orders o 
              JOIN order_details od ON o.order_id = od.order_id 
              WHERE o.user_id = :uid 
                AND od.variant_id IN (SELECT variant_id FROM product_variants WHERE product_id = :pid) 
                AND o.order_status = 3";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->execute(['uid' => $user_id, 'pid' => $product_id]);

if (intval($stmt_check->fetchColumn()) === 0) {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Bạn phải mua và nhận sản phẩm này thành công mới có quyền để lại đánh giá!']);
    exit;
}

// ── 5. Xử lý upload ảnh (review_image) ─────────────────────────────────────
$upload_dir = __DIR__ . '/../assets/uploads/reviews/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$review_image = null;
if (!empty($_FILES['review_image']['name']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
    $allowed_img_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_ext = strtolower(pathinfo($_FILES['review_image']['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_img_ext, true)) {
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Chỉ cho phép tập tin ảnh JPG, PNG, GIF, WEBP.']);
        exit;
    }
    if ($_FILES['review_image']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Kích thước ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.']);
        exit;
    }

    // Kiểm tra MIME type thật sự (an toàn)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['review_image']['tmp_name']);
    finfo_close($finfo);
    $allowed_mimes_img = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed_mimes_img, true)) {
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Tập tin ảnh không hợp lệ!']);
        exit;
    }

    $unique_name = 'review_img_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $file_ext;
    $destination = $upload_dir . $unique_name;

    if (move_uploaded_file($_FILES['review_image']['tmp_name'], $destination)) {
        $review_image = 'assets/uploads/reviews/' . $unique_name;
    }
}

// ── 6. Xử lý upload video (review_video) ───────────────────────────────────
$review_video = null;
if (!empty($_FILES['review_video']['name']) && $_FILES['review_video']['error'] === UPLOAD_ERR_OK) {
    $allowed_vid_ext = ['mp4', 'mov', 'avi', 'mkv', 'webm', '3gp'];
    $file_ext = strtolower(pathinfo($_FILES['review_video']['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_vid_ext, true)) {
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Chỉ cho phép tập tin video MP4, MOV, AVI, MKV, WEBM, 3GP.']);
        exit;
    }
    if ($_FILES['review_video']['size'] > 15 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Kích thước video quá lớn! Vui lòng chọn video dưới 15MB.']);
        exit;
    }

    // Kiểm tra MIME type thật sự (an toàn)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['review_video']['tmp_name']);
    finfo_close($finfo);
    $allowed_mimes_vid = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm', 'video/3gpp'];
    if (!in_array($mime, $allowed_mimes_vid, true)) {
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Tập tin video không hợp lệ!']);
        exit;
    }

    $unique_name = 'review_vid_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $file_ext;
    $destination = $upload_dir . $unique_name;

    if (move_uploaded_file($_FILES['review_video']['tmp_name'], $destination)) {
        $review_video = 'assets/uploads/reviews/' . $unique_name;
    }
}

// ── 7. INSERT vào bảng reviews ──────────────────────────────────────────────
try {
    $sql = "INSERT INTO reviews (user_id, product_id, parent_id, rating, comment, image, video, created_at) 
            VALUES (:uid, :pid, NULL, :rate, :text, :img, :vid, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'uid'  => $user_id,
        'pid'  => $product_id,
        'rate' => $rating,
        'text' => $comment,
        'img'  => $review_image,
        'vid'  => $review_video,
    ]);

    // ── 8. Cập nhật trạng thái is_reviewed cho order_details ────────────────
    if (!empty($detail_id)) {
        $stmt_up = $conn->prepare("UPDATE order_details SET is_reviewed = 1 WHERE detail_id = :did");
        $stmt_up->execute(['did' => $detail_id]);
    } else {
        // Dự phòng: Tìm order_detail chưa đánh giá
        $stmt_find = $conn->prepare("
            SELECT od.detail_id FROM order_details od
            JOIN orders o ON od.order_id = o.order_id
            JOIN product_variants v ON od.variant_id = v.variant_id
            WHERE o.user_id = :uid AND v.product_id = :pid AND o.order_status = 3 AND od.is_reviewed = 0
            ORDER BY o.order_date DESC LIMIT 1
        ");
        $stmt_find->execute(['uid' => $user_id, 'pid' => $product_id]);
        $found_detail_id = $stmt_find->fetchColumn();
        if ($found_detail_id) {
            $stmt_up = $conn->prepare("UPDATE order_details SET is_reviewed = 1 WHERE detail_id = :did");
            $stmt_up->execute(['did' => $found_detail_id]);
        }
    }

    // ── 9. Cộng điểm thưởng Loyalty ────────────────────────────────────────
    $reward_points = 0;
    if (!empty($comment)) $reward_points += 50;
    if (!empty($review_image) || !empty($review_video)) $reward_points += 50;

    if ($reward_points > 0) {
        addLoyaltyPoints($conn, $user_id, $reward_points, "đánh giá sản phẩm");
    }

    echo json_encode([
        'status'        => 'success',
        'success'       => true,
        'points_earned' => $reward_points,
        'message'       => 'Gửi đánh giá thành công! Cảm ơn bạn đã chia sẻ trải nghiệm.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
