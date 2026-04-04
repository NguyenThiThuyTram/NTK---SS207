<?php
// =====================================================================
// PHẦN 1: KẾT NỐI VÀ LẤY DỮ LIỆU TỪ BẢNG "Users"
// =====================================================================
require_once '../../config/database.php'; 

// Mặc định là file dashboard.php bên ngoài đã chạy session_start() rồi nên cứ thế mà gọi $_SESSION thôi
$user_id = $_SESSION['user_id'] ?? 1; // Tạm để 1 để test nếu chưa có đăng nhập thật

try {
    // Chỉ lấy 3 cột cần thiết từ bảng Users
    $sql = "SELECT fullname, phonenumber, address FROM Users WHERE user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    
    // Lấy ra 1 dòng dữ liệu duy nhất của user này
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Lỗi kết nối database: " . $e->getMessage();
    $user_data = null;
}
?>

<style>
    .address-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px; }
    .btn-update-address { background-color: var(--primary); color: var(--bg-white); border: none; padding: 10px 20px; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 8px; }
    .address-item { border: 1px solid var(--border-color); padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: flex-start; }
    .address-left { display: flex; flex-direction: column; gap: 8px; }
    .address-name-phone { display: flex; align-items: center; gap: 10px; font-size: 15px; }
    .address-name { font-weight: 600; color: var(--text-main); }
    .text-divider { color: #ccc; }
    .address-phone { color: var(--text-muted); }
    .address-detail { font-size: 14px; color: var(--text-muted); margin-top: 5px; }
    .badge-default { border: 1px solid #ee4d2d; color: #ee4d2d; font-size: 12px; padding: 2px 5px; margin-left: 10px; border-radius: 2px; }
</style>

<div class="address-header">
    <h2 class="section-title" style="margin: 0; padding: 0; border: none;">Địa chỉ nhận hàng</h2>
    
    <button class="btn-update-address" onclick="alert('Mở form cho user nhập lại địa chỉ')">
        <i class="fa-solid fa-pen"></i> Cập nhật địa chỉ
    </button>
</div>

<div class="address-list">
    <?php if ($user_data && !empty($user_data['address'])): ?>
        <div class="address-item">
            <div class="address-left">
                <div class="address-name-phone">
                    <span class="address-name"><?php echo htmlspecialchars($user_data['fullname']); ?></span>
                    <span class="text-divider">|</span>
                    <span class="address-phone"><?php echo htmlspecialchars($user_data['phonenumber']); ?></span>
                    <span class="badge-default">Địa chỉ duy nhất</span>
                </div>
                
                <div class="address-detail">
                    <?php echo htmlspecialchars($user_data['address']); ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--text-muted); border: 1px dashed #ccc;">
            <i class="fa-solid fa-location-dot" style="font-size: 40px; margin-bottom: 10px;"></i>
            <p>Bé chưa cập nhật địa chỉ nào. Cập nhật ngay để mua hàng nhé!</p>
        </div>
    <?php endif; ?>
</div>