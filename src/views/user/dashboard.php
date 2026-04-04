<?php
session_start();
include '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NTK Fashion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* CSS CHUẨN THEO THIẾT KẾ CỦA MÀY (ĐẠI CA) */
        :root {
            var(--primary): #2f1c00;      
            --bg-white: #ffffff;     
            --beige: #f5f1eb;        
            --border-color: #e5e5e5; 
            --text-main: #111111;    
            --text-muted: #757575;   
            --bg-body: #f9f9f9;      
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: var(--bg-body); color: var(--text-main); line-height: 1.5; }

        header { display: flex; justify-content: space-between; align-items: center; padding: 20px 5%; background-color: var(--bg-white); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 24px; font-weight: bold; color: var(--primary); }
        .nav-links { display: flex; gap: 40px; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-main); font-weight: 500; font-size: 15px;}
        .icons { display: flex; gap: 20px; font-size: 18px; color: var(--text-main); }

        .dashboard-container { display: flex; max-width: 1200px; margin: 40px auto; gap: 30px; padding: 0 20px; }
        
        .sidebar { width: 250px; flex-shrink: 0; background-color: var(--bg-white); padding: 20px 0; border-radius: 4px; }
        .user-brief { display: flex; align-items: center; gap: 15px; padding: 0 20px 20px 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 20px; }
        .avatar-mini { width: 45px; height: 45px; background-color: var(--border-color); border-radius: 50%; display: flex; justify-content: center; align-items: center; color: #999; }
        .name { font-weight: 600; font-size: 15px; }
        .edit-profile { font-size: 13px; color: var(--text-muted); text-decoration: none; display: flex; gap: 5px; align-items: center; margin-top: 4px;}
        
        .sidebar-menu { list-style: none; }
        .sidebar-menu > li { margin-bottom: 15px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-main); padding: 8px 20px; font-size: 15px; transition: color 0.2s; }
        .sidebar-menu a i { width: 20px; text-align: center; color: var(--text-muted); }
        .sidebar-menu a:hover, .sidebar-menu a.active { color: var(--primary); font-weight: 600; }
        
        .sub-menu { list-style: none; margin-top: 5px; }
        .sub-menu a { padding: 8px 20px 8px 52px; font-size: 14px; color: var(--text-muted); font-weight: normal; }
        .sub-menu a.active { color: var(--primary); background-color: var(--beige); font-weight: bold; }

        .main-content { flex: 1; background-color: var(--bg-white); padding: 30px; border-radius: 4px; min-height: 500px; }

        /* ĐỆ CHỈ THÊM ĐÚNG ĐOẠN NÀY ĐỂ IN ĐẬM TIÊU ĐỀ NÈ ĐẠI CA */
        .section-title {
            font-size: 20px;
            font-weight: bold; /* Lệnh in đậm */
            color: var(--text-main);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">NTK</div>
        <ul class="nav-links">
            <li><a href="#">Trang chủ</a></li>
            <li><a href="#">Shop</a></li>
            <li><a href="#">Yêu thích</a></li>
            <li><a href="#">Promotion</a></li>
        </ul>
        <div class="icons">
            <i class="fa-solid fa-magnifying-glass"></i>
            <i class="fa-regular fa-heart"></i>
            <i class="fa-regular fa-user"></i>
            <i class="fa-solid fa-bag-shopping"></i>
        </div>
    </header>

    <?php 
        // Lấy view hiện tại, mặc định là hoso
        $view = isset($_GET['view']) ? $_GET['view'] : 'hoso'; 
    ?>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="user-brief">
                <div class="avatar-mini"><i class="fa-solid fa-user"></i></div>
                <div class="info">
                    <div class="name">Nguyễn Văn A</div>
                    <a href="#" class="edit-profile"><i class="fa-solid fa-pen"></i> Sửa Hồ Sơ</a>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li><a href="dashboard.php?view=thongbao" class="<?php echo ($view == 'thongbao') ? 'active' : ''; ?>"><i class="fa-regular fa-bell"></i> Thông báo</a></li>
                <li>
                    <a href="#"><i class="fa-regular fa-user"></i> Tài khoản của tôi</a>
                    <ul class="sub-menu">
                        <li><a href="dashboard.php?view=hoso" class="<?php echo ($view == 'hoso') ? 'active' : ''; ?>">Hồ sơ</a></li>
                        <li><a href="dashboard.php?view=nganhang" class="<?php echo ($view == 'nganhang') ? 'active' : ''; ?>">Ngân hàng</a></li>
                        <li><a href="dashboard.php?view=diachi" class="<?php echo ($view == 'diachi') ? 'active' : ''; ?>">Địa chỉ</a></li>
                        <li><a href="dashboard.php?view=doimatkhau" class="<?php echo ($view == 'doimatkhau') ? 'active' : ''; ?>">Đổi mật khẩu</a></li>
                        <li><a href="dashboard.php?view=caidat" class="<?php echo ($view == 'caidat') ? 'active' : ''; ?>">Cài đặt thông báo</a></li>
                    </ul>
                </li>
                <li><a href="dashboard.php?view=donmua" class="<?php echo ($view == 'donmua') ? 'active' : ''; ?>"><i class="fa-solid fa-box"></i> Đơn mua</a></li>
                <li><a href="dashboard.php?view=vihoantien" class="<?php echo ($view == 'vihoantien') ? 'active' : ''; ?>"><i class="fa-solid fa-wallet"></i> Ví hoàn tiền</a></li>
                <li><a href="dashboard.php?view=khovoucher" class="<?php echo ($view == 'khovoucher') ? 'active' : ''; ?>"><i class="fa-solid fa-ticket"></i> Kho voucher</a></li>
            </ul>
        </aside>

        <main class="main-content" style="<?php echo ($view == 'donmua') ? 'background-color: transparent; padding: 0;' : ''; ?>">
            <?php 
                // GỌI CÁC FILE CON VÀO ĐÂY DỰA THEO MENU
                if ($view == 'hoso') { 
                    include 'profile_form.php'; 
                } 
                elseif ($view == 'nganhang') { 
                    include 'bank_form.php'; 
                } 
                elseif ($view == 'thongbao') { 
                    include 'notifications.php'; 
                } 
                elseif ($view == 'diachi') { 
                    include 'address_list.php'; 
                } 
                elseif ($view == 'doimatkhau') { 
                    include 'change_password.php'; 
                } 
                elseif ($view == 'caidat') { 
                    include 'notification_settings.php'; 
                } 
                elseif ($view == 'donmua') { 
                    include 'orders.php'; 
                } 
                elseif ($view == 'vihoantien') { 
                    include 'wallet.php';
                }
                elseif ($view == 'khovoucher') { 
                    include 'coupon.php'; 
                } 
                else {
                    include 'profile_form.php';
                }
                
            ?>
        </main>
    </div>
</body>
</html>