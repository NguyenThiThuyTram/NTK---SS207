<?php
// Gọi Header
require_once 'includes/header.php';
?>

<div class="policy-page">
    <div class="policy-container">
        
        <div style="font-size: 13px; color: #666; margin-bottom: 20px;">
            <a href="index.php">Trang chủ</a> > <span>Chính sách thanh toán</span>
        </div>

        <div class="policy-header">
            <h1 class="text-primary">Chính Sách Thanh Toán</h1>
            <p>An toàn, minh bạch và đa dạng phương thức thanh toán cho bạn.</p>
        </div>

        <div class="policy-content">
            
            <section class="policy-section">
                <h2>1. Phương Thức Thanh Toán</h2>
                <div class="payment-grid">
                    <div class="payment-card bg-beige">
                        <i class="fa-solid fa-money-bill-wave text-primary"></i>
                        <h3>Thanh toán khi nhận hàng (COD)</h3>
                        <p>Thanh toán bằng tiền mặt trực tiếp cho nhân viên giao hàng.</p>
                        <ul>
                            <li>Áp dụng toàn quốc.</li>
                            <li><strong>Phí COD 0đ:</strong> Đơn hàng trên 300.000đ.</li>
                            <li><strong>Phí COD 15.000đ:</strong> Đơn hàng dưới 300.000đ.</li>
                        </ul>
                    </div>

                    <div class="payment-card bg-beige">
                        <i class="fa-solid fa-building-columns text-primary"></i>
                        <h3>Chuyển khoản ngân hàng</h3>
                        <p>Xác nhận và giao hàng ngay sau khi nhận được tiền.</p>
                        <ul style="list-style: none; margin-left: 0;">
                            <li><strong>NH:</strong> Vietcombank - CN TP.HCM</li>
                            <li><strong>STK:</strong> 0123456789</li>
                            <li><strong>Tên:</strong> CÔNG TY TNHH NTK FASHION</li>
                            <li><em>ND: [Mã ĐH] - [SĐT]</em></li>
                        </ul>
                    </div>

                    <div class="payment-card bg-beige">
                        <i class="fa-solid fa-wallet text-primary"></i>
                        <h3>Thanh toán qua ví điện tử</h3>
                        <p>Hỗ trợ qua MoMo, ZaloPay, VNPay.</p>
                        <ul>
                            <li>Thanh toán nhanh chóng, an toàn.</li>
                            <li>Nhận ưu đãi từ các ví điện tử.</li>
                            <li>Hoàn tiền tự động nếu hủy đơn.</li>
                        </ul>
                    </div>

                    <div class="payment-card bg-beige">
                        <i class="fa-solid fa-hand-holding-dollar text-primary"></i>
                        <h3>Ví hoàn tiền NTK</h3>
                        <p>Sử dụng số dư trong Ví hoàn tiền để thanh toán.</p>
                        <ul>
                            <li>Áp dụng cho khách đã có tài khoản.</li>
                            <li>Số dư tích lũy từ đơn hàng trước.</li>
                            <li>Kết hợp được với phương thức khác.</li>
                        </ul>
                    </div>
                </div>
            </section>

            <div class="policy-row">
                <section class="policy-section half-width">
                    <h2>2. Bảo Mật Thông Tin</h2>
                    <p>NTK cam kết bảo mật tuyệt đối thông tin thanh toán:</p>
                    <ul>
                        <li>Mã hóa SSL 256-bit cho mọi giao dịch.</li>
                        <li>Không lưu trữ thông tin thẻ tín dụng/ngân hàng.</li>
                        <li>Tuân thủ tiêu chuẩn bảo mật PCI-DSS.</li>
                        <li>Xác thực 2 lớp (OTP) cho mọi giao dịch.</li>
                    </ul>
                </section>

                <section class="policy-section half-width">
                    <h2>3. Quy Định Hoàn Tiền</h2>
                    <p>Thời gian hoàn tiền tùy theo phương thức ban đầu:</p>
                    <ul>
                        <li><strong>COD:</strong> Không áp dụng hoàn tiền (chỉ đổi hàng).</li>
                        <li><strong>Chuyển khoản:</strong> 3-5 ngày làm việc.</li>
                        <li><strong>Ví điện tử:</strong> 1-3 ngày làm việc.</li>
                        <li><strong>Ví hoàn tiền NTK:</strong> Hoàn ngay lập tức.</li>
                    </ul>
                    <p style="font-style: italic; color: red;">* Lưu ý: Phí giao dịch (nếu có) sẽ không được hoàn lại.</p>
                </section>
            </div>

            <section class="policy-section">
                <h2>4. Ưu Đãi Thanh Toán</h2>
                <ul>
                    <li><strong>Giảm giá chuyển khoản:</strong> Giảm thêm 50.000đ cho đơn hàng trên 1.000.000đ khi thanh toán chuyển khoản.</li>
                    <li><strong>Hoàn tiền ví điện tử:</strong> Hoàn 5% (tối đa 100.000đ) khi thanh toán qua MoMo, ZaloPay.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h2>5. Câu Hỏi Thường Gặp (FAQ)</h2>
                <div class="faq-list">
                    <div class="faq-item">
                        <h4>Q: Tôi có thể thanh toán bằng thẻ tín dụng quốc tế không?</h4>
                        <p>A: Hiện tại NTK chưa hỗ trợ thanh toán bằng thẻ tín dụng. Vui lòng sử dụng các phương thức thanh toán khác.</p>
                    </div>
                    <div class="faq-item">
                        <h4>Q: Làm sao để kiểm tra trạng thái thanh toán?</h4>
                        <p>A: Bạn có thể kiểm tra trong mục "Đơn hàng của tôi" trên tài khoản hoặc liên hệ hotline 0373546444.</p>
                    </div>
                    <div class="faq-item">
                        <h4>Q: Tôi chuyển khoản nhầm số tiền thì làm sao?</h4>
                        <p>A: Vui lòng liên hệ ngay với bộ phận CSKH để được hỗ trợ hoàn tiền hoặc điều chỉnh đơn hàng.</p>
                    </div>
                </div>
            </section>

            <section class="policy-contact">
                <h2>Hỗ Trợ Thanh Toán</h2>
                <div class="contact-box">
                    <p><i class="fa-solid fa-phone"></i> <strong>Hotline:</strong> 0373546444 (9:00 - 21:00)</p>
                    <p><i class="fa-solid fa-envelope"></i> <strong>Email:</strong> payment@ntkfashion.com</p>
                    <p><i class="fa-solid fa-headset"></i> <strong>Chat trực tuyến:</strong> Góc phải màn hình</p>
                </div>
            </section>

        </div>
    </div>
</div>

<?php
// Gọi Footer
require_once 'includes/footer.php';
?>