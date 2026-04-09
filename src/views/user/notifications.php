<?php
// Ensure this runs as a child of dashboard.php, meaning $conn and $_SESSION['user_id'] exist
$user_id = $_SESSION['user_id'];

// Lấy thông báo từ bảng Orders của user
try {
    $sql = "SELECT order_id as id_don_hang, order_status as trang_thai, order_date as thoi_gian 
            FROM Orders 
            WHERE user_id = :user_id 
            ORDER BY order_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $danh_sach_thong_bao = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Lỗi lấy thông báo: " . $e->getMessage();
    $danh_sach_thong_bao = [];
}
?>

<style>
    .notification-list { display: flex; flex-direction: column; margin-top: 10px; }
    .noti-item { display: flex; align-items: center; padding: 20px 0; border-bottom: 1px solid var(--border-color); gap: 20px; }
    .noti-item:last-child { border-bottom: none; }
    .noti-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px; flex-shrink: 0; }
    
    .icon-orange { background-color: #fff1e6; color: #f76b1c; }
    .icon-green { background-color: #e6f9ed; color: #21b559; }
    .icon-red { background-color: #fee2e2; color: #dc2626; }
    .icon-blue { background-color: #e0f2fe; color: #0284c7; }
    
    .noti-content { display: flex; flex-direction: column; gap: 4px; }
    .noti-title { font-size: 15px; color: var(--text-main); font-weight: 400; }
    .noti-time { font-size: 13px; color: var(--text-muted); }
</style>

<div class="content-header">
    <h2 class="section-title" style="margin: 0; padding: 0; border: none; margin-bottom: 20px;">Thông báo</h2>
</div>

<div class="notification-list">
    <?php if (!empty($danh_sach_thong_bao)): ?>
        
        <?php foreach ($danh_sach_thong_bao as $noti): ?>
            
            <?php 
                $icon_class = 'fa-solid fa-bell'; 
                $color_class = 'icon-blue';
                $tieu_de = 'Có cập nhật về đơn hàng #' . htmlspecialchars($noti['id_don_hang']);

                // Chuyển đổi order_status INT sang logic UI
                $status = (int)$noti['trang_thai'];
                
                if ($status === 1) { // Đang giao
                    $icon_class = 'fa-solid fa-truck-fast';
                    $color_class = 'icon-orange';
                    $tieu_de = 'Đơn hàng #' . htmlspecialchars($noti['id_don_hang']) . ' đang được giao đến bạn';
                } elseif ($status === 2 || $status === 3) { // Thành công
                    $icon_class = 'fa-regular fa-circle-check';
                    $color_class = 'icon-green';
                    $tieu_de = 'Đơn hàng #' . htmlspecialchars($noti['id_don_hang']) . ' đã được giao thành công';
                } elseif ($status === 4) { // Đã hủy
                    $icon_class = 'fa-regular fa-circle-xmark';
                    $color_class = 'icon-red';
                    $tieu_de = 'Đơn hàng #' . htmlspecialchars($noti['id_don_hang']) . ' đã bị hủy';
                } else { // Chờ xác nhận (0) hoặc mặc định
                    $icon_class = 'fa-solid fa-box-open';
                    $color_class = 'icon-blue';
                    $tieu_de = 'Đơn hàng #' . htmlspecialchars($noti['id_don_hang']) . ' đang chờ xác nhận';
                }
            ?>

            <div class="noti-item">
                <div class="noti-icon <?php echo $color_class; ?>">
                    <i class="<?php echo $icon_class; ?>"></i>
                </div>
                <div class="noti-content">
                    <div class="noti-title"><?php echo $tieu_de; ?></div>
                    <!-- Định dạng thời gian -->
                    <div class="noti-time"><?php echo date('d/m/Y H:i', strtotime($noti['thoi_gian'])); ?></div>
                </div>
            </div>

        <?php endforeach; ?>

    <?php else: ?>
        <div class="noti-item" style="justify-content: center; color: var(--text-muted); border: 1px dashed #ccc; padding: 40px; border-radius: 8px;">
            Bạn chưa có thông báo nào.
        </div>
    <?php endif; ?>
</div>