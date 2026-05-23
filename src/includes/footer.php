<?php
// Tính BASE URL cho footer (giống header.php)
$_f_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_f_host = $_SERVER['HTTP_HOST'];
$_f_src_dir = str_replace('\\', '/', realpath(__DIR__ . '/../'));
$_f_doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$_f_src_path = str_replace($_f_doc_root, '', $_f_src_dir);
$_FBASE = $_f_protocol . '://' . $_f_host . $_f_src_path;
?>
</main>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* ============================================================
       CSS CHUẨN ĐỒNG BỘ DARK MODE & FIX CĂN GIỮA ICON
    ============================================================ */
    
    /* ÉP CĂN GIỮA MẠNG XÃ HỘI CHUẨN HIGH-FASHION */
    .social-icons {
        display: flex !important;
        justify-content: center !important; /* Căn giữa theo chiều ngang */
        align-items: center !important;     /* Căn giữa theo chiều dọc */
        gap: 15px;                          /* Khoảng cách giữa các vòng tròn icon */
        margin: 15px auto 20px;             /* Tạo khoảng cách thông thoáng trên dưới */
        width: 100%;
    }

    /* Đảm bảo vòng tròn bọc icon cũng chuẩn tâm */
    .social-icons a {
        display: inline-flex !important;
        justify-content: center !important;
        align-items: center !important;
        width: 40px;
        height: 40px;
        border: 1px solid #ddd;
        border-radius: 50%;
        text-decoration: none;
        transition: all 0.3s ease;
        color: #333333 !important; /* Đặt màu mặc định cho icon */
    }
    
    .social-icons a:hover {
        border-color: #222 !important;
        background-color: #222 !important;
    }
    .social-icons a:hover i {
        color: #fff !important;
    }

    /* CSS bổ trợ để fix triệt để hiển thị Footer trong Dark Mode */
    body.dark-mode .main-footer {
        background-color: #1a1a1a !important;
        border-top: 1px solid #333;
        color: #ddd !important;
    }
    
    /* Ép màu cho các tiêu đề H3 */
    body.dark-mode .footer-col h3 {
        color: #ffffff !important;
        text-shadow: 0 0 2px rgba(255,255,255,0.2);
        margin-bottom: 20px;
        font-weight: bold;
    }
    
    /* Làm nổi bật các dòng text thông thường và link */
    body.dark-mode .footer-col p, 
    body.dark-mode .footer-col li,
    body.dark-mode .footer-col a {
        color: #bbbbbb !important;
    }
    body.dark-mode .footer-col a:hover {
        color: #f1c40f !important; /* Di chuột vào hiện màu vàng NTK */
    }
    
    /* Phần bản quyền phía dưới */
    body.dark-mode .footer-bottom {
        background-color: #111 !important;
        border-top: 1px solid #222;
        color: #888 !important;
    }
    
    /* Icon mạng xã hội khi ở Dark Mode */
    body.dark-mode .social-icons a {
        border-color: #444 !important;
        color: #f1c40f !important; /* Icon màu vàng khi ở chế độ tối */
    }
    body.dark-mode .social-icons a i {
        color: #f1c40f !important;
    }
    body.dark-mode .social-icons a:hover {
        background-color: #f1c40f !important;
        border-color: #f1c40f !important;
    }
    body.dark-mode .social-icons a:hover i {
        color: #111 !important;
    }
</style>

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-col">
            <div class="footer-logo-box" style="margin-bottom: 15px;">
                <img src="<?= $_FBASE ?>/assets/images/logo-ntk.png" alt="NTK Logo" class="footer-logo" id="footerLogo">
            </div>
            <p>Khám phá những thiết kế giúp bạn tự tin mỗi ngày.</p>
            <p>Hotline: <strong>0373546444</strong></p>
            <p>Giờ hoạt động: 9:00 - 21:00</p>
        </div>

        <div class="footer-col">
            <h3>HỖ TRỢ KHÁCH HÀNG</h3>
            <ul>
                <li><a href="return-policy.php">Chính sách đổi trả</a></li>
                <li><a href="payment-policy.php">Chính sách thanh toán</a></li>
                <li><a href="shipping-policy.php">Chính sách vận chuyển</a></li>
                <li><a href="guide.php">Hướng dẫn mua hàng</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3>DANH MỤC NỔI BẬT</h3>
            <ul class="footer-links">
                <li><a href="product.php?cat=CAT01">Áo thun</a></li>
                <li><a href="product.php?cat=CAT02">Áo khoác</a></li>
                <li><a href="product.php?cat=CAT03">Hoodie & Sweater</a></li>
                <li><a href="product.php?cat=CAT04">Quần</a></li>
                <li><a href="product.php?cat=CAT05">Áo sơ mi</a></li>
            </ul>
        </div>

        <div class="footer-col" style="text-align: center;"> 
            <h3>KẾT NỐI VỚI CHÚNG TÔI</h3>
            <div class="social-icons">
                <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-telegram"></i></a>
                <a href="#"><i class="fa-solid fa-bag-shopping"></i></a>
            </div>
            <p class="social-note" style="margin-top: 10px;">Follow để cập nhật xu hướng mới nhất và nhận các deal hot từ NTK!</p>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="bottom-container">
            <p>© <?php echo date("Y"); ?> NTK Fashion. All rights reserved.</p>
            <p>GPKD: 0123456789 | Cấp ngày: 01/01/2026</p>
        </div>
    </div>
</footer>

<script>
    // Đồng bộ màu sắc logo tự động khi load trang
    document.addEventListener('DOMContentLoaded', function() {
        const isDark = document.body.classList.contains('dark-mode');
        const fLogo = document.getElementById('footerLogo');
        if (isDark && fLogo) {
            fLogo.style.filter = 'brightness(0) invert(1)';
        }
    });
</script>

<script src="<?= $_FBASE ?>/assets/js/main.js"></script>
</body>
</html>