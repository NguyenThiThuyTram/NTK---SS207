<?php
// Ensure this runs as a child of dashboard.php, meaning $conn and $_SESSION['user_id'] exist
$user_id = $_SESSION['user_id'];

// Xử lý POST (Lưu Địa Chỉ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
    $address = trim($_POST['address']);
    
    try {
        $stmt = $conn->prepare("UPDATE Users SET address = :address WHERE user_id = :user_id");
        $stmt->execute(['address' => $address, 'user_id' => $user_id]);
        echo "<script>alert('Lưu địa chỉ thành công!'); window.location.href='dashboard.php?view=diachi';</script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
}

// Lấy thông tin user hiện tại
$stmt = $conn->prepare("SELECT address FROM Users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$user_address = $user_data['address'] ?? '';
?>

<style>
    /* Tổng thể: Font chữ và màu nền trắng, chữ đen */
    .address-wrapper {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        background-color: #ffffff; /* Background */
        color: #111111; /* Text */
    }
    
    /* Phần Header */
    .address-wrapper .content-header {
        margin-bottom: 20px;
    }
    
    .address-wrapper .section-title {
        margin: 0 0 5px 0;
        padding: 0;
        font-size: 18px;
        font-weight: 600;
        color: #111111; /* Text */
    }
    
    .address-wrapper .section-subtitle {
        margin: 0;
        font-size: 14px;
        color: #111111; /* Text */
        opacity: 0.7; /* Làm dịu màu chữ phụ một chút */
    }

    /* Khối chứa Form */
    .address-list {
        max-width: 600px;
        padding: 20px;
        background-color: #f5f1eb; /* Beige (Tạo điểm nhấn nhẹ cho form) */
        border: 1px solid #e5e5e5; /* Border */
        border-radius: 6px;
    }

    /* Ô nhập liệu */
    .address-wrapper textarea {
        width: 100%;
        padding: 12px;
        background-color: #ffffff; /* Background ô text */
        border: 1px solid #e5e5e5; /* Border */
        border-radius: 4px;
        outline: none;
        resize: vertical;
        box-sizing: border-box;
        margin-bottom: 15px;
        font-size: 14px;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: #111111; /* Text */
        transition: border 0.2s ease;
    }
    
    .address-wrapper textarea:focus {
        border-color: #2f1c00; /* Khi nhấn vào viền sẽ có màu Nâu Đậm */
    }

    /* Nút bấm */
    .address-wrapper button {
        background-color: #2f1c00; /* Primary (Nâu đậm) */
        color: #ffffff; /* Chữ trắng cho nổi bật trên nền nâu */
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        transition: opacity 0.2s ease;
    }
    
    .address-wrapper button:hover {
        opacity: 0.85; /* Khi rê chuột vào sẽ sáng lên một chút xíu */
    }
</style>

<div class="address-wrapper">
    <div class="content-header">
        <h2 class="section-title">Địa chỉ nhận hàng</h2>
        <p class="section-subtitle">Quản lý địa chỉ giao hàng của bạn</p>
    </div>

    <div class="address-list">
        <form method="POST">
            <textarea name="address" rows="4" placeholder="Nhập địa chỉ giao hàng của bạn..."><?= htmlspecialchars($user_address) ?></textarea>
            
            <button type="submit" name="save_address">Lưu Địa Chỉ</button>
        </form>
    </div>
</div>