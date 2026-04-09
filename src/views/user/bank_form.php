<?php
// Ensure this runs as a child of dashboard.php
$user_id = $_SESSION['user_id'];

// Xử lý POST (Thêm/Sửa/Xóa Ngân Hàng)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'save_bank') {
            $bank_name = trim($_POST['bank_name']);
            $bank_account_number = trim($_POST['bank_account_number']);
            $bank_account_name = trim($_POST['bank_account_name']); 
            
            try {
                $stmt = $conn->prepare("UPDATE Users SET bank_name = :bank_name, bank_account_number = :bank_account_number, bank_account_name = :bank_account_name WHERE user_id = :user_id");
                $stmt->execute([
                    'bank_name' => $bank_name, 
                    'bank_account_number' => $bank_account_number, 
                    'bank_account_name' => mb_strtoupper($bank_account_name, 'UTF-8'),
                    'user_id' => $user_id
                ]);
                echo "<script>alert('Lưu/Cập nhật ngân hàng thành công!'); window.location.href='dashboard.php?view=nganhang';</script>";
                exit;
            } catch (PDOException $e) {
                echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
            }
        } elseif ($action === 'delete_bank') {
            try {
                $stmt = $conn->prepare("UPDATE Users SET bank_name = NULL, bank_account_number = NULL, bank_account_name = NULL WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $user_id]);
                echo "<script>alert('Xóa ngân hàng thành công!'); window.location.href='dashboard.php?view=nganhang';</script>";
                exit;
            } catch (PDOException $e) {
                echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
            }
        }
    }
}

// Lấy thông tin NH hiện tại
$stmt = $conn->prepare("SELECT bank_name, bank_account_number, bank_account_name FROM Users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$bank_data = $stmt->fetch(PDO::FETCH_ASSOC);

$has_bank = ($bank_data && !empty($bank_data['bank_name']) && !empty($bank_data['bank_account_number']));
?>

<style>
    /* Giao diện Đơn giản - Sạch sẽ - Rõ ràng */
    .bank-wrapper {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        background: transparent; 
        color: #333; /* Chữ xám đen cực kỳ dễ đọc */
    }
    
    /* Header */
    .bank-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee; /* Viền nhạt tinh tế */
        margin-bottom: 20px;
    }
    .bank-title {
        font-size: 18px;
        font-weight: 500;
        color: #333;
        margin: 0;
    }
    
    /* Các Nút Bấm - Tone Xám Đậm */
    .btn-add-bank, .btn-save {
        background: #555; /* Xám đậm nhã nhặn */
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: 0.2s;
    }
    .btn-add-bank:hover, .btn-save:hover {
        background: #333; /* Rê chuột vào tối hơn chút */
    }

    .btn-delete {
        background: #fff;
        color: #555;
        border: 1px solid #ccc; /* Viền xám nhạt */
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: 0.2s;
    }
    .btn-delete:hover {
        background: #f5f5f5;
        border-color: #999;
    }

    /* Form Container */
    .bank-form-container {
        max-width: 650px;
        padding: 10px 0;
    }
    .form-group {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    .form-group label {
        width: 150px;
        font-size: 14px;
        color: #555;
    }
    .form-group input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        outline: none;
        color: #333;
        transition: border 0.2s;
    }
    .form-group input:focus {
        border-color: #888;
    }
    
    /* Thẻ hiển thị ngân hàng đã lưu */
    .saved-bank-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px;
        border: 1px solid #eee;
        border-radius: 6px;
        margin-top: 10px;
        background: #fafafa; /* Nền xám cực lợt cho thẻ nổi lên 1 chút */
    }
    .bank-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .bank-icon {
        width: 45px;
        height: 45px;
        background: #f0f0f0;
        color: #555;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .bank-details h4 {
        margin: 0 0 5px 0;
        color: #333;
        font-size: 16px;
    }
    .bank-details p {
        margin: 0;
        color: #666;
        font-size: 14px;
        text-transform: uppercase;
    }
</style>

<div class="bank-wrapper">
    <div class="bank-header">
        <h2 class="bank-title">Tài khoản ngân hàng của tôi</h2>
        <?php if (!$has_bank): ?>
            <button class="btn-add-bank" id="btnAddBankForm"><i class="fa-solid fa-plus"></i> Thêm Tài khoản</button>
        <?php endif; ?>
    </div>
    
    <?php if (!$has_bank): ?>
        <div class="bank-form-container" id="newBankFormContainer" style="display: none;">
            <form method="POST">
                <div class="form-group">
                    <label>Tên Ngân Hàng</label>
                    <input type="text" name="bank_name" placeholder="VD: Vietcombank, MBBank..." required>
                </div>
                <div class="form-group">
                    <label>Tên Chủ Thẻ</label>
                    <input type="text" name="bank_account_name" placeholder="VD: NGUYEN VAN A" required>
                </div>
                <div class="form-group">
                    <label>Số Tài Khoản</label>
                    <input type="text" name="bank_account_number" placeholder="Nhập số tài khoản..." required>
                </div>
                <div style="padding-left: 150px; margin-top: 20px;">
                    <button type="submit" name="action" value="save_bank" class="btn-save">Lưu Tài Khoản</button>
                </div>
            </form>
        </div>

    <?php else: ?>
        <div class="saved-bank-card">
            <div class="bank-info">
                <div class="bank-icon"><i class="fa-solid fa-building-columns"></i></div>
                <div class="bank-details">
                    <h4><?= htmlspecialchars($bank_data['bank_name']) ?></h4>
                    <p><?= htmlspecialchars($bank_data['bank_account_name']) ?> • **********<?= substr(htmlspecialchars($bank_data['bank_account_number']), -4) ?></p>
                </div>
            </div>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="action" value="delete_bank" class="btn-delete" onclick="return confirm('Bạn có chắc muốn xóa tài khoản ngân hàng này?');">Xóa</button>
            </form>
        </div>
        
        <h3 style="font-size: 14px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; color: #666; font-weight: normal;">Cập nhật thông tin ngân hàng</h3>
        <div class="bank-form-container" style="padding-top: 10px;">
            <form method="POST">
                <div class="form-group">
                    <label>Tên Ngân Hàng</label>
                    <input type="text" name="bank_name" value="<?= htmlspecialchars($bank_data['bank_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Tên Chủ Thẻ</label>
                    <input type="text" name="bank_account_name" value="<?= htmlspecialchars($bank_data['bank_account_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Số Tài Khoản</label>
                    <input type="text" name="bank_account_number" value="<?= htmlspecialchars($bank_data['bank_account_number']) ?>" required>
                </div>
                <div style="padding-left: 150px; margin-top: 20px;">
                    <button type="submit" name="action" value="save_bank" class="btn-save">Cập Nhật Thông Tin</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnAddBank = document.getElementById('btnAddBankForm');
    const formContainer = document.getElementById('newBankFormContainer');
    
    if (btnAddBank && formContainer) {
        btnAddBank.addEventListener('click', function() {
            if (formContainer.style.display === 'none' || formContainer.style.display === '') {
                formContainer.style.display = 'block';
                this.innerHTML = '<i class="fa-solid fa-xmark"></i> Hủy';
            } else {
                formContainer.style.display = 'none';
                this.innerHTML = '<i class="fa-solid fa-plus"></i> Thêm Tài khoản';
            }
        });
    }
});
</script>