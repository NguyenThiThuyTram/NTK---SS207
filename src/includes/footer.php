</main> 

<style>
    /* CSS bổ trợ để fix triệt để hiển thị Footer trong Dark Mode */
    body.dark-mode .main-footer {
        background-color: #1a1a1a !important;
        border-top: 1px solid #333;
        color: #ddd !important;
    }
    /* Ép màu cho các tiêu đề H3 mà Bee đang dùng */
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
    /* Icon mạng xã hội */
    body.dark-mode .social-icons a i {
        color: #f1c40f !important;
    }
</style>

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-col">
            <img src="assets/images/logo-ntk.png" alt="NTK Logo" class="footer-logo" id="footerLogo">
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

        <div class="footer-col">
            <h3>KẾT NỐI VỚI CHÚNG TÔI</h3>
            <div class="social-icons">
                <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-telegram"></i></a>
                <a href="#"><i class="fa-solid fa-bag-shopping"></i></a>
            </div>
            <p class="social-note">Follow để cập nhật xu hướng mới nhất và nhận các deal hot từ NTK!</p>
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
    // Thêm logic nhỏ để đổi màu logo footer khi sang Dark Mode nếu cần
    document.addEventListener('DOMContentLoaded', function() {
        const isDark = document.body.classList.contains('dark-mode');
        const fLogo = document.getElementById('footerLogo');
        if (isDark && fLogo) {
            fLogo.style.filter = 'brightness(0) invert(1)';
        }
    });
</script>

<script src="assets/js/main.js"></script>
</body>
</html>