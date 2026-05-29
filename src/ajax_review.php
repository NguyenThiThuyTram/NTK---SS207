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

    // KIỂM DUYỆT VĂN BẢN (Text Moderation)
    $bad_words = [
        // Chửi thề, tục tĩu chung
        'địt', 'đụ', 'lồn', 'cặc', 'buồi', 'đĩ', 'điếm', 'phò', 'đéo', 'vcl', 'vl', 'vãi lồn', 'đm', 'đmm', 'đkm', 'đcm', 'chó đẻ', 'thằng chó', 'con chó', 'súc vật', 'cặn bã', 'rác rưởi', 'óc chó', 'ngu học', 'khốn nạn', 'mất dạy', 'vô giáo dục', 'bitch', 'fuck', 'shit', 'cunt', 'slut', 'nứng',
        
        // Chửi cha chửi mẹ, gia đình
        'địt mẹ', 'đụ má', 'địt cha', 'tổ tông', 'địt cụ', 'con mẹ mày', 'thằng cha mày', 'ông nội mày', 'bà nội mày', 'mồ mả', 'bàn thờ', 'chết cha', 'chết mẹ', 'đĩ mẹ', 'tạp chủng', 'đjt mẹ', 'duma',
        
        // Ma túy, chất kích thích, tệ nạn
        'ma túy', 'hê rô in', 'heroin', 'đập đá', 'chơi đá', 'hút cần', 'cần sa', 'cỏ mỹ', 'thuốc lắc', 'kẹo ke', 'xì ke', 'nghiện ngập', 'phê cần', 'bay phòng', 'đánh bài', 'cờ bạc', 'cá độ', 'tài xỉu', 'lô đề',
        
        // Chết chóc, tự tử, bạo lực
        'tự tử', 'chết đi', 'đi chết đi', 'tự sát', 'cắt cổ', 'giết người', 'đâm chém', 'chém giết', 'đổ máu', 'thắt cổ', 'nhảy lầu', 'thuốc độc',
        
        // Từ lóng, viết tắt
        'cave', 'gái gọi', 'nulo', 'vkl', 'đjt', 'dklm'
    ];
    $comment_lower = mb_strtolower($comment, 'UTF-8');
    foreach ($bad_words as $word) {
        // Kiểm tra từ khóa có xuất hiện trong nội dung không (sử dụng regex để kiểm tra từ đứng độc lập hoặc có dấu câu)
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/iu', $comment_lower) || strpos($comment_lower, $word) !== false) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Nội dung đánh giá vi phạm tiêu chuẩn cộng đồng (chứa từ ngữ không phù hợp). Vui lòng chỉnh sửa lại!']);
            exit;
        }
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
            
            // AWS Rekognition Content Moderation
            require_once __DIR__ . '/includes/aws_rekognition.php';
            $rekognition = new AwsRekognition();
            if ($rekognition->isConfigured()) {
                $imgData = file_get_contents($destination);
                $modResult = $rekognition->detectModerationLabels($imgData);
                
                if (isset($modResult['ModerationLabels']) && count($modResult['ModerationLabels']) > 0) {
                    // Xóa file ảnh vi phạm
                    unlink($destination);
                    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Hình ảnh vi phạm tiêu chuẩn cộng đồng (chứa nội dung không phù hợp). Vui lòng chọn ảnh khác.']);
                    exit;
                }
            }
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

        // Gửi thông báo cho người viết đánh giá gốc nếu Admin phản hồi
        if (!empty($parent_id) && intval($user_role) === 1) {
            $stmt_get_owner = $conn->prepare("SELECT user_id FROM reviews WHERE review_id = :rid");
            $stmt_get_owner->execute(['rid' => $parent_id]);
            $owner_id = $stmt_get_owner->fetchColumn();
            
            if ($owner_id && $owner_id != $user_id) {
                $msg = "Quản trị viên đã phản hồi đánh giá của bạn.";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'system', 'Phản hồi từ Shop', :msg)");
                $stmt_notif->execute(['uid' => $owner_id, 'msg' => $msg]);
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

// ===================== XỬ LÝ XÓA BÌNH LUẬN =====================
if ($action === 'delete_review') {
    $review_id = intval($_POST['review_id'] ?? 0);

    if ($review_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID đánh giá không hợp lệ!']);
        exit;
    }

    try {
        // Kiểm tra xem review này có tồn tại và thuộc về user đang đăng nhập (hoặc user là admin)
        $stmt_check = $conn->prepare("SELECT user_id, image, video FROM reviews WHERE review_id = :rid");
        $stmt_check->execute(['rid' => $review_id]);
        $review = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$review) {
            echo json_encode(['status' => 'error', 'message' => 'Đánh giá không tồn tại!']);
            exit;
        }

        // Kiểm tra quyền: Chỉ admin (role = 1) hoặc chủ sở hữu đánh giá mới được xóa
        if (intval($user_role) !== 1 && intval($review['user_id']) !== intval($user_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền xóa đánh giá này!']);
            exit;
        }

        // Tìm tất cả các phản hồi con để xóa file media nếu có
        $stmt_children = $conn->prepare("SELECT image, video FROM reviews WHERE parent_id = :rid");
        $stmt_children->execute(['rid' => $review_id]);
        $children = $stmt_children->fetchAll(PDO::FETCH_ASSOC);

        // Xóa file media của các phản hồi con (nếu có)
        foreach ($children as $child) {
            if (!empty($child['image']) && file_exists(__DIR__ . '/../' . $child['image'])) {
                unlink(__DIR__ . '/../' . $child['image']);
            }
            if (!empty($child['video']) && file_exists(__DIR__ . '/../' . $child['video'])) {
                unlink(__DIR__ . '/../' . $child['video']);
            }
        }

        // Xóa các phản hồi con trong database
        $stmt_del_children = $conn->prepare("DELETE FROM reviews WHERE parent_id = :rid");
        $stmt_del_children->execute(['rid' => $review_id]);

        // Xóa file media của đánh giá gốc
        if (!empty($review['image']) && file_exists(__DIR__ . '/../' . $review['image'])) {
            unlink(__DIR__ . '/../' . $review['image']);
        }
        if (!empty($review['video']) && file_exists(__DIR__ . '/../' . $review['video'])) {
            unlink(__DIR__ . '/../' . $review['video']);
        }

        // Xóa các likes liên quan
        $stmt_del_likes = $conn->prepare("DELETE FROM review_likes WHERE review_id = :rid");
        $stmt_del_likes->execute(['rid' => $review_id]);

        // Cuối cùng xóa đánh giá gốc
        $stmt_del = $conn->prepare("DELETE FROM reviews WHERE review_id = :rid");
        $stmt_del->execute(['rid' => $review_id]);

        echo json_encode(['status' => 'success', 'message' => 'Đã xóa đánh giá thành công!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    exit;
}
?>