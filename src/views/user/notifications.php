<?php
// =====================================================================
// PHẦN 1: KẾT NỐI DATABASE VÀ LẤY DỮ LIỆU (Mày ghép vào source của mày)
// =====================================================================
/* // [CÁCH LÀM THẬT VỚI DATABASE]
// 1. Lấy ID user đang đăng nhập:
$user_id = $_SESSION['user_info']['id']; 

// 2. Viết câu query lấy thông báo (hoặc lấy từ bảng đơn hàng):
$sql = "SELECT id_don_hang, trang_thai, thoi_gian_cap_nhat 
        FROM don_hang 
        WHERE user_id = '$user_id' 
        ORDER BY thoi_gian_cap_nhat DESC";

// 3. Thực thi query và gán vào biến $danh_sach_thong_bao
// Ví dụ dùng PDO: 
// $stmt = $conn->prepare($sql);
// $stmt->execute();
// $danh_sach_thong_bao = $stmt->fetchAll(PDO::FETCH_ASSOC);
*/

// [DỮ LIỆU MẪU ĐỂ TEST GIAO DIỆN - Khi code thật thì xóa đoạn giả lập này đi]
$danh_sach_thong_bao = [
    [
        'id_don_hang' => 'DH001',
        'trang_thai' => 'dang_giao', // Trạng thái: đang giao
        'thoi_gian' => 'Hôm nay 09:30'
    ],
    [
        'id_don_hang' => 'DH002',
        'trang_thai' => 'thanh_cong', // Trạng thái: thành công
        'thoi_gian' => 'Hôm qua 14:20'
    ]
];
// =====================================================================
?>

<style>
    /* CSS GIỮ NGUYÊN Y CHANG BẢN TRƯỚC ĐỂ ĐẢM BẢO ĐẸP */
    .notification-list { display: flex; flex-direction: column; margin-top: 10px; }
    .noti-item { display: flex; align-items: center; padding: 20px 0; border-bottom: 1px solid var(--border-color); gap: 20px; }
    .noti-item:last-child { border-bottom: none; }
    .noti-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px; flex-shrink: 0; }
    
    /* Các class màu sắc tự động */
    .icon-orange { background-color: #fff1e6; color: #f76b1c; }
    .icon-green { background-color: #e6f9ed; color: #21b559; }
    
    .noti-content { display: flex; flex-direction: column; gap: 4px; }
    .noti-title { font-size: 15px; color: var(--text-main); font-weight: 400; }
    .noti-time { font-size: 13px; color: var(--text-muted); }
</style>

<div class="content-header">
    <h2>Thông báo</h2>
</div>

<div class="notification-list">
    <?php if (!empty($danh_sach_thong_bao)): ?>
        
        <?php foreach ($danh_sach_thong_bao as $noti): ?>
            
            <?php 
                // Cài đặt mặc định
                $icon_class = 'fa-solid fa-bell'; 
                $color_class = '';
                $tieu_de = '';

                // Logic tự động set icon và màu dựa trên trạng thái đơn hàng từ DB
                if ($noti['trang_thai'] == 'dang_giao') {
                    $icon_class = 'fa-solid fa-box';
                    $color_class = 'icon-orange';
                    $tieu_de = 'Đơn hàng #' . htmlspecialchars($noti['id_don_hang']) . ' đang được giao đến bạn';
                } elseif ($noti['trang_thai'] == 'thanh_cong') {
                    $icon_class = 'fa-regular fa-circle-check';
                    $color_class = 'icon-green';
                    $tieu_de = 'Đơn hàng #' . htmlspecialchars($noti['id_don_hang']) . ' đã được giao thành công';
                }
                // Mày có thể thêm logic elseif ($noti['trang_thai'] == 'da_huy') ở đây nữa...
            ?>

            <div class="noti-item">
                <div class="noti-icon <?php echo $color_class; ?>">
                    <i class="<?php echo $icon_class; ?>"></i>
                </div>
                <div class="noti-content">
                    <div class="noti-title"><?php echo $tieu_de; ?></div>
                    <div class="noti-time"><?php echo htmlspecialchars($noti['thoi_gian']); ?></div>
                </div>
            </div>

        <?php endforeach; ?>

    <?php else: ?>
        <div class="noti-item" style="justify-content: center; color: var(--text-muted);">
            Bạn chưa có thông báo nào.
        </div>
    <?php endif; ?>
</div>