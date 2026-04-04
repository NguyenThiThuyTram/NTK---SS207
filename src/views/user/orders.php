<style>
    /* CSS chuẩn bài E-commerce */
    .order-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: #333;
        background-color: #f5f5f5;
    }
    
    /* Tabs Menu */
    .order-tabs {
        display: flex;
        background: #fff;
        margin-bottom: 15px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        overflow-x: auto;
    }
    .order-tabs a {
        flex: 1;
        text-align: center;
        padding: 15px 10px;
        text-decoration: none;
        color: #555;
        font-size: 15px;
        border-bottom: 2px solid transparent;
        white-space: nowrap;
        transition: 0.2s;
    }
    .order-tabs a:hover {
        color: #ee4d2d;
    }
    .order-tabs a.active {
        color: #ee4d2d;
        border-bottom: 2px solid #ee4d2d;
    }

    /* Thanh tìm kiếm */
    .order-search {
        background: #eaeaea;
        padding: 12px 15px;
        border-radius: 4px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        color: #888;
    }
    .order-search input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        margin-left: 10px;
        font-size: 14px;
    }

    /* Thẻ Đơn Hàng (Order Card) */
    .order-card {
        background: #fff;
        border-radius: 4px;
        margin-bottom: 15px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .order-header {
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .shop-name { font-weight: 600; font-size: 14px; }
    .order-status { color: #ee4d2d; font-weight: 500; text-transform: uppercase; font-size: 14px;}
    .status-success { color: #26aa99; }

    /* Sản phẩm bên trong đơn hàng */
    .order-item {
        display: flex;
        padding: 15px 20px;
        border-bottom: 1px solid #fafafa;
    }
    .order-item:last-child { border-bottom: none; }
    .item-img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border: 1px solid #e1e1e1;
        margin-right: 15px;
    }
    .item-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .item-name { font-size: 15px; margin: 0 0 5px 0; font-weight: normal; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .item-variant { font-size: 13px; color: #888; margin-bottom: 5px; }
    .item-qty { font-size: 13px; color: #333; }
    .item-price {
        color: #ee4d2d;
        font-size: 14px;
        text-align: right;
        min-width: 100px;
    }
    .item-price-old { color: #888; text-decoration: line-through; margin-right: 5px; font-size: 13px;}

    /* Footer thẻ đơn hàng (Thành tiền & Nút bấm) */
    .order-footer {
        padding: 20px;
        background: #fffcfb;
        border-top: 1px dashed #f0f0f0;
    }
    .order-total {
        text-align: right;
        font-size: 15px;
        margin-bottom: 15px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
    }
    .total-price { font-size: 20px; color: #ee4d2d; font-weight: 500; }
    
    .order-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .btn {
        padding: 8px 20px;
        font-size: 14px;
        cursor: pointer;
        border-radius: 2px;
        transition: 0.2s;
    }
    .btn-outline {
        background: #fff;
        border: 1px solid #ccc;
        color: #555;
    }
    .btn-outline:hover { background: #f8f8f8; }
    .btn-primary {
        background: #ee4d2d;
        border: 1px solid #ee4d2d;
        color: #fff;
    }
    .btn-primary:hover { opacity: 0.9; }
</style>

<div class="order-container">
    
    <div class="order-tabs">
        <a href="#" class="active">Tất cả</a>
        <a href="#">Chờ thanh toán</a>
        <a href="#">Vận chuyển</a>
        <a href="#">Chờ giao hàng</a>
        <a href="#">Hoàn thành</a>
        <a href="#">Đã hủy</a>
        <a href="#">Trả hàng/Hoàn tiền</a>
    </div>

    <div class="order-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Bạn có thể tìm kiếm theo ID đơn hàng hoặc Tên sản phẩm">
    </div>

    <div class="order-card">
        <div class="order-header">
            <span class="shop-name">Mã đơn hàng: #202401</span>
            <span class="order-status status-success"><i class="fa-solid fa-truck"></i> GIAO HÀNG THÀNH CÔNG</span>
        </div>
        
        <div class="order-item">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-7qukw-ljk90z7w9x6zc5" alt="Áo thun" class="item-img">
            <div class="item-info">
                <h4 class="item-name">Áo Thun Nam Nữ Form Rộng Cổ Tròn Unisex Tay Lỡ Phông Trơn Local Brand</h4>
                <div class="item-variant">Phân loại hàng: Đen, L</div>
                <div class="item-qty">x1</div>
            </div>
            <div class="item-price">
                <span class="item-price-old">₫150.000</span> ₫75.000
            </div>
        </div>

        <div class="order-footer">
            <div class="order-total">
                Thành tiền: <span class="total-price">₫75.000</span>
            </div>
            <div class="order-actions">
                <button class="btn btn-primary">Mua lại</button>
                <button class="btn btn-outline">Đánh giá</button>
                <button class="btn btn-outline">Liên hệ người bán</button>
            </div>
        </div>
    </div>

    <div class="order-card">
        <div class="order-header">
            <span class="shop-name">Mã đơn hàng: #202402</span>
            <span class="order-status">ĐANG VẬN CHUYỂN</span>
        </div>
        
        <div class="order-item">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lsthxjbxv7xjd9" alt="Quần Jean" class="item-img">
            <div class="item-info">
                <h4 class="item-name">Quần Jean Nam Ống Rộng Ống Suông Trơn Cao Cấp Dáng Chuẩn</h4>
                <div class="item-variant">Phân loại hàng: Xanh nhạt, Size 30</div>
                <div class="item-qty">x2</div>
            </div>
            <div class="item-price">₫190.000</div>
        </div>

        <div class="order-item">
            <img src="https://down-vn.img.susercontent.com/file/sg-11134201-22120-gngw5y2b1zkvd7" alt="Áo sơ mi" class="item-img">
            <div class="item-info">
                <h4 class="item-name">Áo Sơ Mi Nam Ngắn Tay Cổ Tàu Vải Đũi Thái Không Nhăn Mát Mẻ</h4>
                <div class="item-variant">Phân loại hàng: Trắng, XL</div>
                <div class="item-qty">x1</div>
            </div>
            <div class="item-price">₫120.000</div>
        </div>

        <div class="order-footer">
            <div class="order-total">
                Thành tiền: <span class="total-price">₫500.000</span>
            </div>
            <div class="order-actions">
                <button class="btn btn-outline">Đã nhận được hàng</button>
                <button class="btn btn-outline">Xem chi tiết đơn</button>
            </div>
        </div>
    </div>

    <div class="order-card">
        <div class="order-header">
            <span class="shop-name">Mã đơn hàng: #202403</span>
            <span class="order-status" style="color: #888;">ĐÃ HỦY</span>
        </div>
        
        <div class="order-item">
            <img src="https://down-vn.img.susercontent.com/file/983995eb83fcb40ea89b1b702ec94d80" alt="Mũ Lưỡi Trai" class="item-img" style="filter: grayscale(100%); opacity: 0.7;">
            <div class="item-info">
                <h4 class="item-name" style="color: #888;">Mũ Lưỡi Trai Nón Kết Thêu Chữ Trơn Nam Nữ Thời Trang</h4>
                <div class="item-variant">Phân loại hàng: Đen</div>
                <div class="item-qty">x1</div>
            </div>
            <div class="item-price" style="color: #888;">₫45.000</div>
        </div>

        <div class="order-footer">
            <div class="order-total">
                Thành tiền: <span class="total-price" style="color: #333;">₫45.000</span>
            </div>
            <div class="order-actions">
                <button class="btn btn-primary">Mua lại</button>
                <button class="btn btn-outline">Xem chi tiết hủy đơn</button>
            </div>
        </div>
    </div>

</div>