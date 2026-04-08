CREATE DATABASE NTK;
USE NTK;

-- 1. Bảng Danh mục
CREATE TABLE Categories (
    category_id CHAR(5) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(255),
    image_url VARCHAR(255),
    is_show_home INT DEFAULT 1,
    priority INT DEFAULT 0,
    description VARCHAR(500)
);

-- 2. Bảng Người dùng
CREATE TABLE Users (
    user_id CHAR(5) PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,
    fullname VARCHAR(100),
    email VARCHAR(100),
    phonenumber VARCHAR(10),
    address VARCHAR(500),
    verification_code VARCHAR(6),
    verification_code_expires_at DATETIME,
    is_verified INT DEFAULT 0,
    role INT DEFAULT 0,
    status INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_orders INT DEFAULT 0,
    total_spend DECIMAL(15,2) DEFAULT 0
) ;

-- 3. Bảng Mã giảm giá
CREATE TABLE Coupons (
    coupon_id CHAR(5) PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    discount_type INT, -- 0: Phần trăm, 1: Số tiền cố định
    discount_value DECIMAL(15,2),
    min_order_value DECIMAL(15,2),
    start_date DATETIME,
    end_date DATETIME,
    max_discount_amount DECIMAL(15,2),
    quantity INT,
    used_count INT DEFAULT 0,
    status INT DEFAULT 1
) ;

-- 4. Bảng Phương thức vận chuyển
CREATE TABLE Shipping_Methods (
    shipping_method_id CHAR(5) PRIMARY KEY,
    name VARCHAR(100),
    cost DECIMAL(15,2),
    estimated_delivery VARCHAR(50)
) ;

-- 5. Bảng Sản phẩm (FK: category_id)
CREATE TABLE Products (
    product_id CHAR(5) PRIMARY KEY,
    category_id CHAR(5),
    name VARCHAR(200) NOT NULL,
    description VARCHAR(500),
    image VARCHAR(255),
    rating DECIMAL(2,1),
    sold_count INT DEFAULT 0,
    status INT DEFAULT 1,
    avg_rating DECIMAL(2,1),
    total_reviews INT DEFAULT 0,
    seo_title VARCHAR(150),
    seo_description VARCHAR(300),
    CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES Categories(category_id)
) ;

-- 6. Bảng Biến thể sản phẩm (FK: product_id)
CREATE TABLE Product_Variants (
    variant_id CHAR(5) PRIMARY KEY,
    product_id CHAR(5),
    sku VARCHAR(50),
    color VARCHAR(50),
    size VARCHAR(50),
    stock INT DEFAULT 0,
    original_price DECIMAL(15,2),
    sale_price DECIMAL(15,2),
    image VARCHAR(255),
    is_featured INT DEFAULT 0,
    is_active INT DEFAULT 1,
    weight INT, length INT, width INT, height INT,
    CONSTRAINT fk_variant_prod FOREIGN KEY (product_id) REFERENCES Products(product_id)
) ;

-- 7. Bảng Đơn hàng (FK: user_id, coupon_id, shipping_method_id)
CREATE TABLE Orders (
    order_id CHAR(5) PRIMARY KEY,
    user_id CHAR(5),
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    fullname VARCHAR(100),
    phone VARCHAR(10),
    address VARCHAR(500),
    total_price DECIMAL(15,2),
    shipping_fee DECIMAL(15,2),
	shipping_method_id CHAR(5),
    discount_value DECIMAL(15,2),
    order_status INT,
    final_price DECIMAL(15,2),
    payment_status INT DEFAULT 0,
    payment_method INT,
    coupon_id CHAR(5),
    tracking_number VARCHAR(50),
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES Users(user_id),
    CONSTRAINT fk_order_coupon FOREIGN KEY (coupon_id) REFERENCES Coupons(coupon_id),
    CONSTRAINT fk_order_ship FOREIGN KEY (shipping_method_id) REFERENCES Shipping_Methods(shipping_method_id)
) ;


-- 8. Bảng Chi tiết đơn hàng (FK: order_id, variant_id)
CREATE TABLE Order_Details (
    detail_id CHAR(5) PRIMARY KEY,
    order_id CHAR(5),
    variant_id CHAR(5),
    quantity INT,
    price DECIMAL(15,2),
    feedback VARCHAR(500),
    is_reviewed INT DEFAULT 0,
    CONSTRAINT fk_detail_order FOREIGN KEY (order_id) REFERENCES Orders(order_id),
    CONSTRAINT fk_detail_variant FOREIGN KEY (variant_id) REFERENCES Product_Variants(variant_id)
) ;

-- 9. Bảng Giỏ hàng (FK: user_id, variant_id)
CREATE TABLE Cart (
    cart_id CHAR(5) PRIMARY KEY,
    user_id CHAR(5),
    variant_id CHAR(5),
    quantity INT,
    session_id VARCHAR(255),
    is_selected INT DEFAULT 1,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES Users(user_id),
    CONSTRAINT fk_cart_variant FOREIGN KEY (variant_id) REFERENCES Product_Variants(variant_id)
) ;

-- 10. Bảng Yêu thích (FK: user_id, product_id)
CREATE TABLE Wishlist (
    wishlist_id CHAR(5) PRIMARY KEY,
    user_id CHAR(5),
    product_id CHAR(5),
    added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wish_user FOREIGN KEY (user_id) REFERENCES Users(user_id),
    CONSTRAINT fk_wish_prod FOREIGN KEY (product_id) REFERENCES Products(product_id)
) ;

-- 11. Bảng Đánh giá (FK: user_id, product_id)
CREATE TABLE Reviews (
    review_id CHAR(5) PRIMARY KEY,
    user_id CHAR(5),
    product_id CHAR(5),
    rating FLOAT CHECK (rating >= 1 AND rating <= 5),
    comment VARCHAR(500),
    image VARCHAR(255),
    reply VARCHAR(500),
    status INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rev_user FOREIGN KEY (user_id) REFERENCES Users(user_id),
    CONSTRAINT fk_rev_prod FOREIGN KEY (product_id) REFERENCES Products(product_id)
) ;

-- Bảng Categories
INSERT INTO Categories (category_id, name, slug, image_url, is_show_home, priority, description)
VALUES 
('CAT01', 'Áo thun', 'ao-thun', 'https://down-vn.img.susercontent.com/file/vn-', 1, 1, 'Áo thun basic dễ mặc, phù hợp mọi phong cách'),
('CAT02', 'Áo khoác', 'ao-khoac', 'https://down-vn.img.susercontent.com/file/vn-', 0, 2, 'Áo khoác thời trang, giữ ấm và chống nắng'),
('CAT03', 'Hoodie&Sweater', 'hoodie-sweater', 'https://down-vn.img.susercontent.com/file/vn-', 1, 3, 'Hoodie và sweater trẻ trung, năng động'),
('CAT04', 'Quần', 'quan', 'https://down-vn.img.susercontent.com/file/vn-', 0, 4, 'Quần thời trang, sành điệu'),
('CAT05', 'Áo sơ mi', 'ao-so-mi', 'https://down-vn.img.susercontent.com/file/vn-11', 0, 5, 'Áo sơ mi lịch sự, phù hợp đi làm và đi chơi'),
('CAT06', 'Quần đùi', 'quan-dui', 'https://down-vn.img.susercontent.com/file/vn-11', 0, 6, 'Quần đùi thoải mái cho hoạt động hàng ngày'),
('CAT07', 'Áo polo', 'ao-polo', 'https://down-vn.img.susercontent.com/file/vn-11', 0, 7, 'Áo polo thanh lịch, dễ phối đồ'),
('CAT08', 'Quần jeans', 'quan-jeans', 'https://down-vn.img.susercontent.com/file/vn-11', 1, 8, 'Quần jeans bền đẹp, phong cách cá tính'),
('CAT09', 'Chân váy', 'chan-vay', 'https://down-vn.img.susercontent.com/file/vn-11', 0, 9, 'Chân váy nữ tính, đa dạng kiểu dáng'),
('CAT10', 'Áo len & cardigan', 'ao-len-cardigan', 'https://down-vn.img.susercontent.com/file/vn-11', 0, 10, 'Áo len và cardigan giữ ấm, thời trang');

-- Bảng dữ liệu Users
INSERT INTO Users (
    user_id, username, password, fullname, email, 
    phonenumber, address, is_verified, role, status, 
    created_at, total_orders, total_spend
) VALUES 
('U01', 'admin', 'admin123', N'Quản Trị Viên', 'admin@ntk.vn', '334275834', N'Kho tổng HCM', 1, 1, 1, '2024-01-01', 0, 0),
('U02', 'nguyenvana', 'pass123', N'Nguyễn Văn A', 'ana@gmail.com', '375788987', N'123 Lê Lợi, Q1, HCM', 0, 1, 1, '2024-01-15', 5, 2500000),
('U03', 'tranthib', 'pass123', N'Trần Thị B', 'bib@gmail.com', '964326512', N'45 Cầu Giấy, Hà Nội', 0, 0, 1, '2024-02-10', 2, 850000),
('U04', 'lethic', 'pass123', N'Lê Thị C', 'cic@gmail.com', '901239876', N'10 Nguyễn Trãi, Q5', 0, 1, 0, '2024-03-05', 0, 0),
('U05', 'hoanglong', 'pass123', N'Hoàng Long', 'longh@gmail.com', '987654321', N'15 Lê Duẩn, Đà Nẵng', 0, 1, 1, '2024-03-20', 12, 15000000),
('U06', 'thanhthuy', 'pass123', N'Nguyễn Thanh Thúy', 'thuynt@gmail.com', '912345678', N'88 Nguyễn Huệ, Q1, HCM', 0, 0, 1, '2024-04-12', 1, 450000),
('U07', 'minhquan', 'pass123', N'Phạm Minh Quân', 'quanpm@gmail.com', '905112233', N'12 Trần Phú, Hải Phòng', 0, 1, 1, '2024-05-01', 8, 6200000),
('U08', 'kieuoanh', 'pass123', N'Võ Kiều Oanh', 'oanhvk@gmail.com', '934556677', N'200 Phan Chu Trinh, Huế', 0, 1, 0, '2024-05-18', 0, 0),
('U09', 'ducanh', 'pass123', N'Đỗ Đức Anh', 'anhdd@gmail.com', '977889900', N'45 Láng Hạ, Đống Đa, Hà Nội', 0, 1, 1, '2024-06-02', 3, 1200000),
('U10', 'thuytien', 'pass123', N'Bùi Thủy Tiên', 'tienbt@gmail.com', '966554433', N'77 Cách Mạng Tháng 8, Cần Thơ', 0, 0, 1, '2024-06-25', 15, 22000000),
('U11', 'xuanbach', 'pass123', N'Ngô Xuân Bách', 'bachnx@gmail.com', '944332211', N'102 Quang Trung, Gò Vấp, HCM', 0, 1, 1, '2024-07-10', 4, 3100000),
('U12', 'thuha', 'pass123', N'Nguyễn Thu Hà', 'hant@gmail.com', '922110099', N'56 Kim Mã, Ba Đình, Hà Nội', 0, 1, 1, '2024-07-30', 7, 5400000),
('U13', 'giahuy', 'pass123', N'Trần Gia Huy', 'huytg@gmail.com', '909123456', N'32 Hùng Vương, Nha Trang', 0, 0, 1, '2024-08-14', 0, 0),
('U14', 'mylinh', 'pass123', N'Đặng Mỹ Linh', 'linhdm@gmail.com', '988776655', N'120 Võ Văn Kiệt, Q5, HCM', 0, 1, 1, '2024-09-05', 2, 980000),
('U15', 'quocbao', 'pass123', N'Phan Quốc Bảo', 'baopq@gmail.com', '911223344', N'15 Hòa Bình, Biên Hòa', 0, 1, 0, '2024-09-21', 0, 0),
('U16', 'camtu', 'pass123', N'Lý Cẩm Tú', 'tulc@gmail.com', '933445566', N'09 Lê Lợi, TP Vinh', 0, 1, 1, '2024-10-08', 6, 4200000),
('U17', 'nhatminh', 'pass123', N'Vũ Nhật Minh', 'minhvn@gmail.com', '955667788', N'22 Điện Biên Phủ, Đà Nẵng', 0, 1, 1, '2024-10-25', 10, 8900000),
('U18', 'phuongthao', 'pass123', N'Chu Phương Thảo', 'thaocp@gmail.com', '977112233', N'412 Trường Chinh, Tân Bình, HCM', 0, 1, 1, '2024-11-12', 3, 1150000),
('U19', 'huynhanh', 'pass123', N'Lê Huỳnh Anh', 'anhlh@gmail.com', '900223344', N'89 Nguyễn Trãi, Thanh Xuân, HN', 0, 1, 1, '2024-11-30', 5, 2750000),
('U20', 'lamminh', 'pass123', N'Lâm Khải Minh', 'lminh@gmail.com', '335378609', N'Trần Đại Nghĩa, Dĩ An, Bình Dương', 1, 0, 0, '2024-11-30', 3, 400000);

-- Bảng Coupons
INSERT INTO Coupons (
    coupon_id, 
    code, 
    discount_type, 
    discount_value, 
    min_order_value, 
    start_date, 
    end_date, 
    max_discount_amount, 
    quantity, 
    used_count, 
    status
)
VALUES 
('CP01', 'WELCOME', 0, '10', 250000, '2024-01-01', '2025-01-01', 30000, 1000, 25, 1),
('CP02', 'FREESHIP', 1, '20000', 200000, '2024-01-01', '2024-06-30', NULL, 500, 33, 1),
('CP03', 'SALE', 0, '10', 500000, '2024-06-01', '2024-06-07', 50000, 100, 56, 0),
('CP04', 'TET', 1, '50000', 1000000, '2025-01-01', '2025-02-01', NULL, 50, 78, 0),
('CP05', 'NTK', 0, '10', 2000000, '2024-11-11', '2024-12-11', 200000, 20, 23, 1);

-- Bảng Shipping_Methods
INSERT INTO Shipping_Methods (shipping_method_id, name, cost, estimated_delivery)
VALUES 
('S01', 'SPX', 35000, '2-3 ngày'),
('S02', 'GHN', 40000, '2-4 ngày'),
('S03', 'GHTK', 25000, '3-5 ngày'),
('S04', 'J&T', 30000, '1-2 ngày');

-- Bảng Products
INSERT INTO Products (product_id, category_id, name, description, image, rating, sold_count, status, avg_rating, total_reviews, seo_title, seo_description)
VALUES 
-- NHÓM T: ÁO THUN (CAT01)
('T01', 'CAT01', 'Áo thun babytee thể thao', 'Áo Thun Babytee Thể Thao Jersey Soccer Hack Dáng Đường Phố Cho Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7ne96vcjmiu46.webp', 4.2, 433, 1, 4.2, 130, 'Mua Áo thun babytee thể thao giá tốt tại NTK Fashion', 'Sản phẩm Áo thun babytee thể thao chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T02', 'CAT01', 'Áo thun babytee cổ ôm', 'Áo Babytee Y2K Cổ Ôm 100% Cotton Phong Cách Streetwear 2025', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-med1e440xbev37.webp', 4.7, 180, 1, 4.7, 54, 'Mua Áo thun babytee cổ ôm giá tốt tại NTK Fashion', 'Sản phẩm Áo thun babytee cổ ôm chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T03', 'CAT01', 'Áo thun babytee basic', 'Áo Thun Baby Tee Basic 100% Cotton HOT TREND dễ phối đồ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mee3cwm3s4cgfd.webp', 4.4, 239, 1, 4.4, 72, 'Mua Áo thun babytee basic giá tốt tại NTK Fashion', 'Sản phẩm Áo thun babytee basic chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T04', 'CAT01', 'Áo thun kiểu trễ vai', 'Áo Thun Sọc Trễ Vai Áo Trễ Vai Phối Dây Buộc Nơ Nữ Tính Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-meb5j0sgmvba74.webp', 4.6, 446, 1, 4.6, 134, 'Mua Áo thun kiểu trễ vai giá tốt tại NTK Fashion', 'Sản phẩm Áo thun kiểu trễ vai chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T05', 'CAT01', 'Áo thun tay dài', 'Áo Thun Kẻ Long Sleeves Cotton Kẻ Dày Dặn Logo Thêu Unisex Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mgomv4ifjpqiab.webp', 4.9, 491, 1, 4.9, 147, 'Mua Áo thun tay dài giá tốt tại NTK Fashion', 'Sản phẩm Áo thun tay dài chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T06', 'CAT01', 'Áo thun form rộng', 'Áo Thun Kẻ 100% Cotton Stripes Tee Form Rộng Oversized Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mdy3txnrb8qoab@resize_w900_nl.webp', 4.3, 314, 1, 4.3, 94, 'Mua Áo thun form rộng giá tốt tại NTK Fashion', 'Sản phẩm Áo thun form rộng chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T07', 'CAT01', 'Áo babytee chấm bi', 'Áo Babytee Phối Hoạ Tiết Chấm Bi Áo tay Raglan Nữ Tính Trendy', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mjtii2dx62o585.webp', 4.5, 437, 1, 4.5, 131, 'Mua Áo babytee chấm bi giá tốt tại NTK Fashion', 'Sản phẩm Áo babytee chấm bi chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T08', 'CAT01', 'Áo Babytee Lucky Horse', 'Áo Babytee Lucky Horse Form Basic Chào Năm Mới May Mắn 2026', 'https://down-vn.img.susercontent.com/file/vn-11134207-81ztc-mkdpz011e29xc7.webp', 4.4, 301, 1, 4.4, 90, 'Mua Áo Babytee Lucky Horse giá tốt tại NTK Fashion', 'Sản phẩm Áo Babytee Lucky Horse chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T09', 'CAT01', 'Áo babytee đứng form', 'Áo Thun Babytee 3-Star Form Fit Regular Cotton 2 Chiều Đứng Form', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfaxfknhsxze9d.webp', 4.5, 101, 1, 4.5, 30, 'Mua Áo babytee đứng form giá tốt tại NTK Fashion', 'Sản phẩm Áo babytee đứng form chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T10', 'CAT01', 'Áo Baby Tee "I Love Cat"', 'Áo Baby Tee "I Love Cat" 100% Cotton', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lza1i2khf9zh61.webp', 4.3, 302, 1, 4.3, 91, 'Mua Áo Baby Tee "I Love Cat" giá tốt tại NTK Fashion', 'Sản phẩm Áo Baby Tee "I Love Cat" chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),

-- NHÓM J: ÁO KHOÁC (CAT02)
('J01', 'CAT02', 'Áo khoác da', 'Áo Khoác Da Tay Dài Kèm Túi Trong Da Cao Cấp Phong Cách Retro Cổ Điển', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mh71v1j7gb9nc8.webp', 4.8, 311, 1, 4.8, 93, 'Mua Áo khoác da giá tốt tại NTK Fashion', 'Sản phẩm Áo khoác da chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('J02', 'CAT02', 'Áo khoác dù', 'Áo Khoác Dù Chắn Gió Nhiều Màu Mũ Dây Rút Chống Nắng Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mf3y04daxxxm7b.webp', 4.5, 229, 1, 4.5, 69, 'Mua Áo khoác dù giá tốt tại NTK Fashion', 'Sản phẩm Áo khoác dù chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('J03', 'CAT02', 'Áo khoác Canvas', 'Áo Khoác Canvas Dáng Ngắn Áo Khoác Phối Cổ Nhung Tăm Basic Unisex Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg7d8s7jrvnvb7.webp', 4.1, 214, 1, 4.1, 64, 'Mua Áo khoác Canvas giá tốt tại NTK Fashion', 'Sản phẩm Áo khoác Canvas chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('J04', 'CAT02', 'Áo khoác Phao', 'Áo Khoác Phao Phồng Siêu Nhẹ Siêu Ấm Áo Phao Béo Dáng Lửng Mùa Đông', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m5ca38ruq4ae89.webp', 4.7, 474, 1, 4.7, 142, 'Mua Áo khoác Phao giá tốt tại NTK Fashion', 'Sản phẩm Áo khoác Phao chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('J05', 'CAT02', 'Áo khoác Bomber', 'Áo Khoác Bomber Pilot Oversized Chần Bông Thêu Chữ Thời Trang Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m33curbuutamf5.webp', 4.3, 244, 1, 4.3, 73, 'Mua Áo khoác Bomber giá tốt tại NTK Fashion', 'Sản phẩm Áo khoác Bomber chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),

-- NHÓM H: HOODIE (CAT03)
('H01', 'CAT03', 'Áo Hoodie Zip basic', 'Áo Hoodie Zip Basic Vải Nỉ 2 Da Chống Nắng Tốt Form Rộng Nam Nữ Unisex', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m1djz3jqsva0d1.webp', 4.6, 305, 1, 4.6, 92, 'Mua Áo Hoodie Zip basic giá tốt tại NTK Fashion', 'Sản phẩm Áo Hoodie Zip basic chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H02', 'CAT03', 'Áo Hoodie Zip phối Caro', 'Áo Hoodie Zip Phối Caro Nỉ 2 Da Thêu 77 Foreveryoung Form Rộng Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg6a54w1k8az55.webp', 4.8, 422, 1, 4.8, 127, 'Mua Áo Hoodie Zip phối Caro giá tốt tại NTK Fashion', 'Sản phẩm Áo Hoodie Zip phối Caro chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H03', 'CAT03', 'Áo hoodie cờ Mỹ', 'Áo Hoodie in lụa cờ Mỹ Form Rộng Phong Cách Âu Mỹ Unisex Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg253sm8k6x758.webp', 4.5, 59, 1, 4.5, 18, 'Mua Áo hoodie cờ Mỹ giá tốt tại NTK Fashion', 'Sản phẩm Áo hoodie cờ Mỹ chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H04', 'CAT03', 'Áo Hoodie Zip Nỉ Bông Form Boxy', 'Áo Hoodie Zip Nỉ Bông Basic Form Boxy Urban Khoá Kéo BYC Streetwear Unisex Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mk0do4ap7xtz53.webp', 4.4, 334, 1, 4.4, 100, 'Mua Áo Hoodie Zip Nỉ Bông Form Boxy giá tốt tại NTK Fashion', 'Sản phẩm Áo Hoodie Zip Nỉ Bông Form Boxy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H05', 'CAT03', 'Áo Hoodie Zip Nỉ Bông Khoá Kéo 2 Đầu', 'Áo Hoodie Zip Nỉ Bông Khoá Kéo 2 Đầu WITHLOVE Form Boxy Basic Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mdzue2ay0tmrba.webp', 4.8, 490, 1, 4.8, 147, 'Mua Áo Hoodie Zip Nỉ Bông Khoá Kéo 2 Đầu giá tốt tại NTK Fashion', 'Sản phẩm Áo Hoodie Zip Nỉ Bông Khoá Kéo 2 Đầu chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H06', 'CAT03', 'Áo Hoodie Zip ORIGINALS', 'Áo Hoodie Zip ORIGINALS Nỉ 2 Da Không Xù Chữ Thêu', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mdwm103kinlud9.webp', 4.1, 387, 1, 4.1, 116, 'Mua Áo Hoodie Zip ORIGINALS giá tốt tại NTK Fashion', 'Sản phẩm Áo Hoodie Zip ORIGINALS chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H07', 'CAT03', 'Áo Khoác Hoodie Zip Nỉ Chân Cua', 'Áo Khoác Hoodie Zip Nỉ Chân Cua Dày Dặn Áo Hoodie Form Boxy Unisex Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mjibi5ytlb0l94.webp', 4.2, 237, 1, 4.2, 71, 'Mua Áo Khoác Hoodie Zip Nỉ Chân Cua giá tốt tại NTK Fashion', 'Sản phẩm Áo Khoác Hoodie Zip Nỉ Chân Cua chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),

-- NHÓM P: QUẦN (CAT04)
('P01', 'CAT04', 'Quần Dài Kẻ Sọc Kaki', 'Quần Dài Kẻ Sọc Kaki Ống Rộng Phối Dây Belt', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7q889omjzub28.webp', 4.9, 170, 1, 4.9, 51, 'Mua Quần Dài Kẻ Sọc Kaki giá tốt tại NTK Fashion', 'Sản phẩm Quần Dài Kẻ Sọc Kaki chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('P02', 'CAT04', 'Quần Kaki BALLOON', 'Quần Kaki BALLOON Ống Rộng Dáng Cong Pants Hack Eo Phong Cách', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mc8wdg5whi6qb8.webp', 4.0, 313, 1, 4.0, 94, 'Mua Quần Kaki BALLOON giá tốt tại NTK Fashion', 'Sản phẩm Quần Kaki BALLOON chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('P03', 'CAT04', 'Quần Nỉ Form Rộng ORIGINALS', 'Quần Nỉ Form Rộng ORIGINALS Không Xù Phong Cách Đơn Giản Thoải Mái', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-meksw027d340f0.webp', 4.5, 99, 1, 4.5, 30, 'Mua Quần Nỉ Form Rộng ORIGINALS giá tốt tại NTK Fashion', 'Sản phẩm Quần Nỉ Form Rộng ORIGINALS chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('P04', 'CAT04', 'Quần Vải Dù Xếp Ly Ống Thụng', 'Quần Vải Dù Xếp Ly Ống Thụng Form Wide Leg Phong Cách Đường Phố Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mgrnaj06x2bw5f.webp', 4.7, 36, 1, 4.7, 11, 'Mua Quần Vải Dù Xếp Ly Ống Thụng giá tốt tại NTK Fashion', 'Sản phẩm Quần Vải Dù Xếp Ly Ống Thụng chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('P05', 'CAT04', 'Quần Parachute Harem', 'Quần Parachute Harem Dáng Thụng Vintage Quần Dài Dễ Vận Động Nhật Bản', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mdk2lm3j25s5f1.webp', 4.6, 247, 1, 4.6, 74, 'Mua Quần Parachute Harem giá tốt tại NTK Fashion', 'Sản phẩm Quần Parachute Harem chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('P06', 'CAT04', 'Quần Jean Ống Rộng', 'Quần Jean Ống Rộng Tôn Dáng Quần Bò Wash Màu Unisex Thời Trang Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfzb05oeal8odb.webp', 4.8, 400, 1, 4.8, 120, 'Mua Quần Jean Ống Rộng giá tốt tại NTK Fashion', 'Sản phẩm Quần Jean Ống Rộng chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('P07', 'CAT04', 'Quần Vải Dù Túi Hộp', 'Quần Vải Dù Túi Hộp Form Thụng Phối Dây Rút Nam Nữ Cargo Pants Streetwear', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lzjxjlys0s695c.webp', 4.4, 471, 1, 4.4, 141, 'Mua Quần Vải Dù Túi Hộp giá tốt tại NTK Fashion', 'Sản phẩm Quần Vải Dù Túi Hộp chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('P08', 'CAT04', 'Quần Dài Vải Đũi Cạp Chun', 'Quần Dài Vải Đũi Cạp Chun Mềm Mại Thông Thoáng Đa Năng Mùa Thu Mùa Đông', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcm9c1erguct08.webp', 4.3, 123, 1, 4.3, 37, 'Mua Quần Dài Vải Đũi Cạp Chun giá tốt tại NTK Fashion', 'Sản phẩm Quần Dài Vải Đũi Cạp Chun chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('P09', 'CAT04', 'Quần Kaki Ống Rộng Ống Suông', 'Quần Kaki Ống Rộng Ống Suông Phong Cách Trẻ Trung Năng Động Dễ Phối Đồ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-md9vnutr17doc8@resize_w900_nl.webp', 5.0, 297, 1, 5.0, 89, 'Mua Quần Kaki Ống Rộng Ống Suông giá tốt tại NTK Fashion', 'Sản phẩm Quần Kaki Ống Rộng Ống Suông chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('P10', 'CAT04', 'Quần Vải Dù Ống Rộng PARACHUTE', 'Quần Vải Dù Ống Rộng PARACHUTE Màu Trơn', 'https://down-vn.img.susercontent.com/file/vn-11134207-7qukw-lj6mxj354wzwd3.webp', 4.9, 206, 1, 4.9, 62, 'Mua Quần Vải Dù Ống Rộng PARACHUTE giá tốt tại NTK Fashion', 'Sản phẩm Quần Vải Dù Ống Rộng PARACHUTE chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),

-- NHÓM S: SƠ MI (CAT05)
('S01', 'CAT05', 'Áo Sơ Mi Basic', 'Áo Sơ Mi Basic Nhiều Màu Dáng Rộng Họa Tiết Kẻ Sọc Thời Trang Đường Phố', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-llqdtiaj374v5c.webp', 4.7, 450, 1, 4.7, 135, 'Mua Áo Sơ Mi Basic giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Basic chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S02', 'CAT05', 'Áo Sơ Mi Chiết Eo', 'Áo Sơ Mi Chiết Eo Buộc Nơ Có Túi Ngực Dành Cho Nữ Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-md2x7l0cran0fb@resize_w900_nl.webp', 4.1, 484, 1, 4.1, 145, 'Mua Áo Sơ Mi Chiết Eo giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Chiết Eo chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S03', 'CAT05', 'Áo Sơ Mi Kẻ CỘC TAY', 'Áo Sơ Mi Kẻ CỘC TAY Vải Oxford Phối Cổ Trắng Dáng Rộng Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-ltdmmaiv6t9548@resize_w900_nl.webp', 4.3, 180, 1, 4.3, 54, 'Mua Áo Sơ Mi Kẻ CỘC TAY giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Kẻ CỘC TAY chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S04', 'CAT05', 'Áo Sơ Mi Form Fit', 'Áo Sơ Mi Bycamcam Form Fit Trơn Nhiều Màu Thoáng Khí Đứng Form', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lzrhde113o9t1f.webp', 4.6, 253, 1, 4.6, 76, 'Mua Áo Sơ Mi Form Fit giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Form Fit chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S05', 'CAT05', 'Áo Sơ Mi Cộc Tay Form Rộng', 'Áo Sơ Mi Cộc Tay Form Rộng Trẻ Trung Hoạ Tiết Kẻ Khoá Trái Tim', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mbgh9gzxgbc0ed.webp', 4.2, 430, 1, 4.2, 129, 'Mua Áo Sơ Mi Cộc Tay Form Rộng giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Cộc Tay Form Rộng chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S06', 'CAT05', 'Áo Sơ Mi Kẻ Sọc Tay Dài Mỏng Mát', 'Áo Sơ Mi Kẻ Sọc Tay Dài Mỏng Mát Form Rộng Vạt Tôm Thời Trang Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lxunl9nhukt783@resize_w900_nl.webp', 4.8, 357, 1, 4.8, 107, 'Mua Áo Sơ Mi Kẻ Sọc Tay Dài Mỏng Mát giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Kẻ Sọc Tay Dài Mỏng Mát chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S07', 'CAT05', 'Áo Sơ Mi Kẻ Sọc Cổ Nhọn', 'Áo Sơ Mi Kẻ Sọc Cổ Nhọn Striped Shirt Dáng Lửng Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lvzhuqgyr1ej37@resize_w900_nl.webp', 4.5, 82, 1, 4.5, 25, 'Mua Áo Sơ Mi Kẻ Sọc Cổ Nhọn giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Kẻ Sọc Cổ Nhọn chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),

-- NHÓM SH: SHORT (CAT06)
('SH01', 'CAT06', 'Quần Short Kaki Túi Hộp', 'Quần Short Kaki Túi Hộp Dáng Ngắn Phong Cách Retro', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcg2d2ixd4fw7b@resize_w900_nl.webp', 4.7, 396, 1, 4.7, 119, 'Mua Quần Short Kaki Túi Hộp giá tốt tại NTK Fashion', 'Sản phẩm Quần Short Kaki Túi Hộp chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SH02', 'CAT06', 'Quần Short Dù Thể Thao', 'Quần Short Dù Thể Thao Sọc Vải Dù Phong Cách Sporty', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-md5i7uvw67nj7a@resize_w900_nl.webp', 4.1, 138, 1, 4.1, 41, 'Mua Quần Short Dù Thể Thao giá tốt tại NTK Fashion', 'Sản phẩm Quần Short Dù Thể Thao chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SH03', 'CAT06', 'Quần Jeans Short Lửng', 'Quần Jeans Short Lửng Cạp Đính Cúc Vải Denim Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfxj5ibz0u8c2f@resize_w900_nl.webp', 4.7, 194, 1, 4.7, 58, 'Mua Quần Jeans Short Lửng giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans Short Lửng chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),

-- NHÓM PL: POLO (CAT07)
('PL01', 'CAT07', 'Áo Thun Polo Phối Cổ', 'Áo Thun Polo Phối Cổ Basic Năng Động Cho Nữ Xuân Hè 2025', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcrbsixysl9pbc@resize_w900_nl.webp', 4.7, 413, 1, 4.7, 124, 'Mua Áo Thun Polo Phối Cổ giá tốt tại NTK Fashion', 'Sản phẩm Áo Thun Polo Phối Cổ chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('PL02', 'CAT07', 'Áo Polo Kẻ Sọc BabyTee', 'Áo Polo Kẻ Sọc BabyTee Họa Tiết Thêu Thiết Kế Tôn Dáng Cho Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m9o595a72b1qf8@resize_w900_nl.webp', 4.2, 359, 1, 4.2, 108, 'Mua Áo Polo Kẻ Sọc BabyTee giá tốt tại NTK Fashion', 'Sản phẩm Áo Polo Kẻ Sọc BabyTee chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('PL03', 'CAT07', 'Áo Polo Chiết Eo Tay Bồng', 'Áo Polo Chiết Eo Tay Bồng Form Ôm Vừa Tôn Dáng Cho Nữ Xuân Hè 2025', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m9k2akpj4mqi7b@resize_w900_nl.webp', 4.7, 499, 1, 4.7, 150, 'Mua Áo Polo Chiết Eo Tay Bồng giá tốt tại NTK Fashion', 'Sản phẩm Áo Polo Chiết Eo Tay Bồng chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('PL04', 'CAT07', 'Áo Thun Dài Tay Polo Kẻ Ngang', 'Áo Thun Dài Tay Polo Kẻ Ngang Sọc Lớn Hàn Quốc Thu Đông Logo Thêu Trendy', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mh5pidvewdu7e2@resize_w900_nl.webp', 4.7, 175, 1, 4.7, 53, 'Mua Áo Thun Dài Tay Polo Kẻ Ngang giá tốt tại NTK Fashion', 'Sản phẩm Áo Thun Dài Tay Polo Kẻ Ngang chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('PL05', 'CAT07', 'Áo Polo Basic Babytee', 'Áo Polo Basic Babytee Cho Nữ Vải Cá Sấu Cotton Logo Thêu Túi Ngực', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m0mh43fd5ocvc4@resize_w900_nl.webp', 4.7, 163, 1, 4.7, 49, 'Mua Áo Polo Basic Babytee giá tốt tại NTK Fashion', 'Sản phẩm Áo Polo Basic Babytee chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('PL06', 'CAT07', 'Áo Len Dài Tay Cổ Polo', 'Áo Len Dài Tay Cổ Polo Áo Len Vặn Thừng Basic Chất Mịn Dày Dặn Premium', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mjl2uiqymznrd3@resize_w900_nl.webp', 4.3, 436, 1, 4.3, 131, 'Mua Áo Len Dài Tay Cổ Polo giá tốt tại NTK Fashion', 'Sản phẩm Áo Len Dài Tay Cổ Polo chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),

-- NHÓM JE: JEANS (CAT08)
('JE01', 'CAT08', 'Quần Jean Dáng Bí', 'Quần Jean Dáng Bí Cat Washing Denim Retro Unisex Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfcb1buk8sumb0@resize_w900_nl.webp', 4.8, 353, 1, 4.8, 100, 'Mua  Quần Jeans  giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('JE02', 'CAT08', 'Quần Jeans Mềm Dáng Dài', 'Quần Jeans Mềm Dáng Dài Gập Gấu Quần Dài Form Rộng Chất Denim Mềm Đứng Form Unisex', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mftf1gdhowll3e@resize_w900_nl.webp', 4.3, 123, 1, 4.8, 100, 'Mua  Quần Jeans  giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('JE03', 'CAT08', 'Quần Jean Dáng Lửng Dài Demi', 'Quần Jean Dáng Lửng Dài Demi Jean Short Năng Động Denim Wash', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mbc529lr4zbsf1@resize_w900_nl.webp', 4.8, 127, 1, 4.8, 100, 'Mua  Quần Jeans  giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('JE04', 'CAT08', 'Quần Jeans Wash', 'Quần Jeans Wash New Cạp Cao Quần Bò Ống Rộng Tôn Dáng Basic', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfxc467plp8te5@resize_w900_nl.webp', 4.0, 305, 1, 4.8, 100, 'Mua  Quần Jeans  giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('JE05', 'CAT08', 'Quần Bò Wash Màu', 'Quần Jean Ống Rộng Tôn Dáng Quần Bò Wash Màu Unisex Thời Trang Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfzb05oeal8odb@resize_w900_nl.webp', 4.4, 422, 1, 4.8, 100, 'Mua  Quần Jeans  giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),

-- NHÓM SK: SKIRT (CAT09)
('SK01', 'CAT09', 'Chân Váy Ngắn Y2K', 'Chân Váy Ngắn Y2K Caro Lưng Thấp Kèm Quần Bảo Hộ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lzbdjua7vpe59e@resize_w900_nl.webp', 4.4, 349, 1, 4.8, 100, 'Mua Váy giá tốt tại NTK Fashion', 'Sản phẩm Váy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SK02', 'CAT09', 'Quần Váy Ngắn Nỉ Ép', 'Quần Váy Ngắn Nỉ Ép Basic Tôn Dáng Dành Cho Nữ Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcr9179vpcx98a@resize_w900_nl.webp', 4.8, 58, 1, 4.8, 100, 'Mua Váy giá tốt tại NTK Fashion', 'Sản phẩm Váy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SK03', 'CAT09', 'Chân Váy Dài Xếp Ly', 'Chân Váy Dài Xếp Ly Lưng Cao Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mczvn82i30rx20@resize_w900_nl.webp', 4.2, 98, 1, 4.8, 100, 'Mua Váy giá tốt tại NTK Fashion', 'Sản phẩm Váy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SK04', 'CAT09', 'Quần Váy Cách Điệu', 'Quần Váy Cách Điệu Hàn Quốc Vải Chéo Hàn Đính Logo Nữ Tính', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcpv6fnksgnw5f@resize_w900_nl.webp', 4.2, 203, 1, 4.8, 100, 'Mua Váy giá tốt tại NTK Fashion', 'Sản phẩm Váy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SK05', 'CAT09', 'Quần Váy Ngắn Dáng Xoè', 'Quần Váy Ngắn Dáng Xoè Cạp Cao Phong Cách Âu Mỹ Cá Tính', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-md2zgopg67qlc2@resize_w900_nl.webp', 4.4, 122, 1, 4.8, 100, 'Mua Váy giá tốt tại NTK Fashion', 'Sản phẩm Váy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SK06', 'CAT09', 'Quần Váy Ngắn Dáng Bí', 'Quần Váy Ngắn Dáng Bí Nữ Siêu Phồng Chất Dù Form Nhỏ Dáng Ngắn Hack Dáng Cho Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7q1ympl44vl12@resize_w900_nl.webp', 4.0, 331, 1, 4.8, 100, 'Mua Váy giá tốt tại NTK Fashion', 'Sản phẩm Váy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SK07', 'CAT09', 'Quần Váy Nỉ Ép Chấm Bi', 'Quần Váy Nỉ Ép Hoạ Tiết Chấm Bi Basic Trendy Năng Động Dành Cho Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mgdi1fbogft48d@resize_w900_nl.webp', 4.7, 325, 1, 4.8, 100, 'Mua Váy giá tốt tại NTK Fashion', 'Sản phẩm Váy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SK08', 'CAT09', 'Quần Váy Dáng Chữ A', 'Quần Váy Dáng Chữ A Mei Skirt Pants Kẻ Sọc Phối Đai Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-madkfvd9lj7w09@resize_w900_nl.webp', 4.7, 420, 1, 4.8, 100, 'Mua Váy giá tốt tại NTK Fashion', 'Sản phẩm Váy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SK09', 'CAT09', 'Chân Váy Ngắn Swan Skirt', 'Chân Váy Ngắn Swan Skirt Xếp Tầng Có Dây Rút Dáng Xoè Cho Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mbd9fec9s4w10b@resize_w900_nl.webp', 4.5, 118, 1, 4.8, 100, 'Mua Váy giá tốt tại NTK Fashion', 'Sản phẩm Váy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SK10', 'CAT09', 'Chân Váy Form Bí Chấm Bi', 'Chân Váy Ngắn Form Bí Chấm Bi Dây Buộc Nơ Dễ Thương Kèm Quần Bảo Hộ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcbxwnm5wrid6f@resize_w900_nl.webp', 4.9, 112, 1, 4.8, 100, 'Mua Váy giá tốt tại NTK Fashion', 'Sản phẩm Váy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),

-- NHÓM C: CARDIGAN&ÁO LEN (CAT10)
('C01', 'CAT10', 'Áo Khoác Cardigan Len', 'Áo Khoác Cardigan Len Hàn Quốc Dày Dặn Nhiều Màu Thêu Logo', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m4hdzd36m4q8c9@resize_w900_nl.webp', 4.8, 58, 1, 4.8, 100, 'Mua Áo Len giá tốt tại NTK Fashion', 'Sản phẩm Áo Len chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('C02', 'CAT10', 'Áo Len Cổ Tròn Lông Thỏ', 'Áo Len Cổ Tròn Lông Thỏ Mềm Mịn Áo Sweater Sợi Dệt Dày Dặn Ấm Áp Mùa Đông', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mji6wkvavqx1fa@resize_w900_nl.webp', 4.4, 167, 1, 4.8, 100, 'Mua Áo Len giá tốt tại NTK Fashion', 'Sản phẩm Áo Len chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('C03', 'CAT10', 'Áo Len Kẻ Sọc Thu Đông', 'Áo Len Dài Tay Thu Đông Kẻ Sọc Croptop Phong Cách Hàn Quốc Basic Năng Động', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mhmvya34ka2p92@resize_w900_nl.webp', 4.7, 378, 1, 4.8, 100, 'Mua Áo Len giá tốt tại NTK Fashion', 'Sản phẩm Áo Len chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('C04', 'CAT10', 'Áo Lông Thỏ Dài Tay', 'Áo Lông Thỏ Dài Tay Mềm Mịn Áo Len Kẻ Sọc Sleeves Form Rộng Basic Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mji9pf2xw1dzb9@resize_w900_nl.webp', 4.4, 365, 1, 4.8, 100, 'Mua Áo Len giá tốt tại NTK Fashion', 'Sản phẩm Áo Len chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('C05', 'CAT10', 'Áo Len Mỏng Cộc Tay', 'Áo Len Mỏng Mùa Thu Cộc Tay Phối Màu Áo Len Có Cổ Thoáng Khí Dễ Phối Đồ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mdbj2ec8tetb7a@resize_w900_nl.webp', 4.1, 242, 1, 4.8, 100, 'Mua Áo Len giá tốt tại NTK Fashion', 'Sản phẩm Áo Len chất lượng cao, thiết kế chuẩn, giao hàng nhanh.');

-- Bảng dữ liệu product_variants
INSERT INTO product_variants (
    variant_id, product_id, sku, color, size, stock, 
    original_price, sale_price, image, is_featured, is_active, 
    weight, length, width, height
) VALUES 
('V001', 'T01', 'T01-Trắng-S', N'Trắng', 'S', 150, 330000, 280500, NULL, 0, 1, 200, 25, 20, 2),
('V002', 'T01', 'T01-Trắng-M', N'Trắng', 'M', 120, 230000, 195500, NULL, 0, 1, 200, 25, 20, 2),
('V003', 'T01', 'T01-Xanh Navy-S', N'Xanh Navy', 'S', 200, 180000, 153000, NULL, 0, 1, 200, 25, 20, 2),
('V004', 'T01', 'T01-Xanh Navy-M', N'Xanh Navy', 'M', 215, 230000, 195500, NULL, 0, 1, 200, 25, 20, 2),
('V005', 'T02', 'T02-Đen-S', N'Đen', 'S', 170, 250000, 212500, NULL, 0, 1, 200, 25, 20, 2),
('V006', 'T02', 'T02-Đen-M', N'Đen', 'M', 180, 280000, 238000, NULL, 0, 1, 200, 25, 20, 2),
('V007', 'T02', 'T02-Đen-L', N'Đen', 'L', 210, 250000, 212500, NULL, 0, 1, 200, 25, 20, 2),
('V008', 'T02', 'T02-Ghi-S', N'Ghi', 'S', 200, 190000, 161500, NULL, 0, 1, 200, 25, 20, 2),
('V009', 'T02', 'T02-Ghi-M', N'Ghi', 'M', 100, 310000, 263500, NULL, 0, 1, 200, 25, 20, 2),
('V010', 'T02', 'T02-Ghi-L', N'Ghi', 'L', 110, 250000, 212500, NULL, 0, 1, 200, 25, 20, 2),
('V011', 'T03', 'T03-Hồng-S', N'Hồng', 'S', 110, 420000, 357000, NULL, 1, 1, 200, 25, 20, 2),
('V012', 'T03', 'T03-Hồng-M', N'Hồng', 'M', 110, 180000, 153000, NULL, 0, 1, 200, 25, 20, 2),
('V013', 'T04', 'T04-Sọc Trắng-S', N'Sọc Trắng', 'S', 220, 350000, 297500, NULL, 0, 1, 200, 25, 20, 2),
('V014', 'T04', 'T04-Sọc Trắng-M', N'Sọc Trắng', 'M', 200, 350000, 297500, NULL, 0, 1, 200, 25, 20, 2),
('V015', 'T04', 'T04-Nâu-S', N'Nâu', 'S', 210, 320000, 272000, NULL, 0, 1, 200, 25, 20, 2),
('V016', 'T04', 'T04-Nâu-M', N'Nâu', 'M', 130, 220000, 187000, NULL, 0, 1, 200, 25, 20, 2),
('V017', 'T05', 'T05-Xanh-S', N'Xanh', 'S', 120, 350000, 297500, NULL, 0, 1, 200, 25, 20, 2),
('V018', 'T05', 'T05-Xanh-M', N'Xanh', 'M', 300, 240000, 204000, NULL, 0, 1, 200, 25, 20, 2),
('V019', 'T05', 'T05-Đen-S', N'Đen', 'S', 300, 200000, 170000, NULL, 0, 1, 200, 25, 20, 2),
('V020', 'T05', 'T05-Đen-M', N'Đen', 'M', 120, 330000, 280500, NULL, 0, 1, 200, 25, 20, 2),
('V021', 'T06', 'T06-Trắng-S', N'Trắng', 'S', 170, 310000, 263500, NULL, 0, 1, 200, 25, 20, 2),
('V022', 'T06', 'T06-Trắng-M', N'Trắng', 'M', 200, 330000, 280500, NULL, 1, 1, 200, 25, 20, 2),
('V023', 'T06', 'T06-Kem-S', N'Kem', 'S', 150, 440000, 374000, NULL, 0, 1, 200, 25, 20, 2),
('V024', 'T06', 'T06-Kem-M', N'Kem', 'M', 120, 180000, 153000, NULL, 0, 1, 200, 25, 20, 2),
('V025', 'T07', 'T07-Trắng-S', N'Trắng', 'S', 220, 400000, 340000, NULL, 0, 1, 200, 25, 20, 2),
('V026', 'T07', 'T07-Trắng-M', N'Trắng', 'M', 200, 260000, 221000, NULL, 0, 1, 200, 25, 20, 2),
('V027', 'T07', 'T07-Xanh-S', N'Xanh', 'S', 210, 310000, 263500, NULL, 0, 1, 200, 25, 20, 2),
('V028', 'T07', 'T07-Xanh-M', N'Xanh', 'M', 130, 270000, 229500, NULL, 0, 1, 200, 25, 20, 2),
('V029', 'T08', 'T08-Đen-S', N'Đen', 'S', 120, 420000, 357000, NULL, 0, 1, 200, 25, 20, 2),
('V030', 'T08', 'T08-Đen-M', N'Đen', 'M', 300, 400000, 340000, NULL, 0, 1, 200, 25, 20, 2),
('V031', 'T08', 'T08-Trắng-S', N'Trắng', 'S', 300, 240000, 204000, NULL, 0, 1, 200, 25, 20, 2),
('V032', 'T08', 'T08-Trắng-M', N'Trắng', 'M', 120, 270000, 229500, NULL, 0, 1, 200, 25, 20, 2),
('V033', 'T09', 'T09-Đen-S', N'Đen', 'S', 150, 440000, 374000, NULL, 1, 1, 200, 25, 20, 2),
('V034', 'T09', 'T09-Đen-M', N'Đen', 'M', 120, 220000, 187000, NULL, 0, 1, 200, 25, 20, 2),
('V035', 'T09', 'T09-Đen-L', N'Đen', 'L', 200, 290000, 246500, NULL, 0, 1, 200, 25, 20, 2),
('V036', 'T10', 'T10-Xanh-S', N'Xanh', 'S', 215, 290000, 246500, NULL, 0, 1, 200, 25, 20, 2),
('V037', 'T10', 'T10-Xanh-M', N'Xanh', 'M', 170, 310000, 263500, NULL, 0, 1, 200, 25, 20, 2),
('V038', 'T10', 'T10-Xanh-L', N'Xanh', 'L', 180, 200000, 170000, NULL, 0, 1, 200, 25, 20, 2),
('V039', 'J01', 'J01-Xanh-S', N'Xanh', 'S', 210, 350000, 297500, NULL, 0, 1, 300, 30, 20, 2),
('V040', 'J01', 'J01-Xanh-M', N'Xanh', 'M', 200, 280000, 238000, NULL, 0, 1, 300, 30, 20, 2),
('V041', 'J01', 'J01-Xanh-L', N'Xanh', 'L', 100, 280000, 238000, NULL, 0, 1, 300, 30, 20, 2),
('V042', 'J02', 'J02-Đen-S', N'Đen', 'S', 110, 420000, 357000, NULL, 0, 1, 300, 30, 20, 2),
('V043', 'J02', 'J02-Đen-M', N'Đen', 'M', 110, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V044', 'J02', 'J02-Đen-L', N'Đen', 'L', 110, 290000, 246500, NULL, 1, 1, 300, 30, 20, 2),
('V045', 'J03', 'J03-Ghi-S', N'Ghi', 'S', 220, 220000, 187000, NULL, 0, 1, 300, 30, 20, 2),
('V046', 'J03', 'J03-Ghi-M', N'Ghi', 'M', 200, 280000, 238000, NULL, 0, 1, 300, 30, 20, 2),
('V047', 'J03', 'J03-Ghi-L', N'Ghi', 'L', 210, 300000, 255000, NULL, 0, 1, 300, 30, 20, 2),
('V048', 'J04', 'J04-Xanh-S', N'Xanh', 'S', 130, 320000, 272000, NULL, 0, 1, 300, 30, 20, 2),
('V049', 'J04', 'J04-Xanh-M', N'Xanh', 'M', 120, 420000, 357000, NULL, 0, 1, 300, 30, 20, 2),
('V050', 'J04', 'J04-Xanh-L', N'Xanh', 'L', 300, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V051', 'J05', 'J05-Xanh Nhạt-S', N'Xanh Nhạt', 'S', 300, 230000, 195500, NULL, 0, 1, 300, 30, 20, 2),
('V052', 'J05', 'J05-Xanh Nhạt-M', N'Xanh Nhạt', 'M', 120, 330000, 280500, NULL, 0, 1, 300, 30, 20, 2),
('V053', 'J05', 'J05-Xanh Nhạt-L', N'Xanh Nhạt', 'L', 170, 230000, 195500, NULL, 0, 1, 300, 30, 20, 2),
('V069', 'H01', 'H01-Đen-S', N'Đen', 'S', 200, 450000, 382500, NULL, 0, 1, 300, 30, 20, 2),
('V070', 'H01', 'H01-Đen-M', N'Đen', 'M', 215, 220000, 187000, NULL, 0, 1, 300, 30, 20, 2),
('V071', 'H01', 'H01-Đen-L', N'Đen', 'L', 170, 270000, 229500, NULL, 0, 1, 300, 30, 20, 2),
('V072', 'H02', 'H02-Ghi-S', N'Ghi', 'S', 180, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V073', 'H02', 'H02-Ghi-M', N'Ghi', 'M', 210, 440000, 374000, NULL, 0, 1, 300, 30, 20, 2),
('V074', 'H02', 'H02-Ghi-L', N'Ghi', 'L', 200, 280000, 238000, NULL, 0, 1, 300, 30, 20, 2),
('V075', 'H03', 'H03-Xanh-S', N'Xanh', 'S', 100, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V076', 'H03', 'H03-Xanh-M', N'Xanh', 'M', 110, 410000, 348500, NULL, 0, 1, 300, 30, 20, 2),
('V077', 'H03', 'H03-Xanh-L', N'Xanh', 'L', 110, 200000, 170000, NULL, 1, 1, 300, 30, 20, 2),
('V078', 'H04', 'H04-Nâu-S', N'Nâu', 'S', 110, 400000, 340000, NULL, 0, 1, 300, 30, 20, 2),
('V079', 'H04', 'H04-Nâu-M', N'Nâu', 'M', 220, 300000, 255000, NULL, 0, 1, 300, 30, 20, 2),
('V080', 'H04', 'H04-Nâu-L', N'Nâu', 'L', 200, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V081', 'H05', 'H05-Đỏ-S', N'Đỏ', 'S', 210, 180000, 153000, NULL, 0, 1, 300, 30, 20, 2),
('V082', 'H05', 'H05-Đỏ-M', N'Đỏ', 'M', 130, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V083', 'H05', 'H05-Đỏ-L', N'Đỏ', 'L', 120, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V084', 'H06', 'H06-Trắng-S', N'Trắng', 'S', 300, 400000, 340000, NULL, 0, 1, 300, 30, 20, 2),
('V085', 'H06', 'H06-Trắng-M', N'Trắng', 'M', 300, 300000, 255000, NULL, 0, 1, 300, 30, 20, 2),
('V086', 'H06', 'H06-Trắng-L', N'Trắng', 'L', 120, 180000, 153000, NULL, 0, 1, 300, 30, 20, 2),
('V087', 'H07', 'H07-Vàng-S', N'Vàng', 'S', 170, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V088', 'H07', 'H07-Vàng-M', N'Vàng', 'M', 200, 410000, 348500, NULL, 1, 1, 300, 30, 20, 2),
('V089', 'H07', 'H07-Vàng-L', N'Vàng', 'L', 150, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V099', 'S01', 'S01-Trắng-S', N'Trắng', 'S', 150, 430000, 365500, NULL, 1, 1, 300, 30, 20, 2),
('V100', 'S01', 'S01-Trắng-M', N'Trắng', 'M', 120, 410000, 348500, NULL, 0, 1, 300, 30, 20, 2),
('V101', 'S01', 'S01-Trắng-L', N'Trắng', 'L', 200, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V102', 'S02', 'S02-Xanh-S', N'Xanh', 'S', 215, 400000, 340000, NULL, 0, 1, 300, 30, 20, 2),
('V103', 'S02', 'S02-Xanh-M', N'Xanh', 'M', 170, 300000, 255000, NULL, 0, 1, 300, 30, 20, 2),
('V104', 'S02', 'S02-Xanh-L', N'Xanh', 'L', 180, 180000, 153000, NULL, 0, 1, 300, 30, 20, 2),
('V105', 'S03', 'S03-Vàng-S', N'Vàng', 'S', 210, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V106', 'S03', 'S03-Vàng-M', N'Vàng', 'M', 200, 410000, 348500, NULL, 0, 1, 300, 30, 20, 2),
('V107', 'S03', 'S03-Vàng-L', N'Vàng', 'L', 100, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V108', 'S04', 'S04-Đen-S', N'Đen', 'S', 110, 400000, 340000, NULL, 0, 1, 300, 30, 20, 2),
('V109', 'S04', 'S04-Đen-M', N'Đen', 'M', 110, 300000, 255000, NULL, 0, 1, 300, 30, 20, 2),
('V110', 'S04', 'S04-Đen-L', N'Đen', 'L', 110, 180000, 153000, NULL, 1, 1, 300, 30, 20, 2),
('V111', 'S05', 'S05-Đỏ-S', N'Đỏ', 'S', 220, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V112', 'S05', 'S05-Đỏ-M', N'Đỏ', 'M', 200, 410000, 348500, NULL, 0, 1, 300, 30, 20, 2),
('V113', 'S05', 'S05-Đỏ-L', N'Đỏ', 'L', 210, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V114', 'S06', 'S06-Hồng-S', N'Hồng', 'S', 130, 400000, 340000, NULL, 0, 1, 300, 30, 20, 2),
('V115', 'S06', 'S06-Hồng-M', N'Hồng', 'M', 120, 300000, 255000, NULL, 0, 1, 300, 30, 20, 2),
('V116', 'S06', 'S06-Hồng-L', N'Hồng', 'L', 300, 180000, 153000, NULL, 0, 1, 300, 30, 20, 2),
('V117', 'S07', 'S07-Tím-S', N'Tím', 'S', 300, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V118', 'S07', 'S07-Tím-M', N'Tím', 'M', 120, 410000, 348500, NULL, 0, 1, 300, 30, 20, 2),
('V119', 'S07', 'S07-Tím-L', N'Tím', 'L', 170, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V129', 'SK01', 'SK01-Đen-Freesize', N'Đen', 'Freesize', 300, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V130', 'SK01', 'SK01-Kem-Freesize', N'Kem', 'Freesize', 120, 410000, 348500, NULL, 0, 1, 300, 30, 20, 2),
('V131', 'SK01', 'SK01-Nâu-Freesize', N'Nâu', 'Freesize', 170, 200000, 170000, NULL, 1, 1, 300, 30, 20, 2),
('V132', 'SK02', 'SK02-Đen-Freesize', N'Đen', 'Freesize', 200, 400000, 340000, NULL, 0, 1, 300, 30, 20, 2),
('V133', 'SK02', 'SK02-Xám-Freesize', N'Xám', 'Freesize', 150, 300000, 255000, NULL, 0, 1, 300, 30, 20, 2),
('V134', 'SK03', 'SK03-Đen-Freesize', N'Đen', 'Freesize', 120, 180000, 153000, NULL, 0, 1, 300, 30, 20, 2),
('V135', 'SK03', 'SK03-Trắng-Freesize', N'Trắng', 'Freesize', 220, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V136', 'SK03', 'SK03-Xám-Freesize', N'Xám', 'Freesize', 200, 410000, 348500, NULL, 0, 1, 300, 30, 20, 2),
('V137', 'SK04', 'SK04-Nâu-Freesize', N'Nâu', 'Freesize', 210, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V138', 'SK04', 'SK04-Đen-Freesize', N'Đen', 'Freesize', 130, 400000, 340000, NULL, 0, 1, 300, 30, 20, 2),
('V139', 'SK04', 'SK04-Ghi-Freesize', N'Ghi', 'Freesize', 120, 300000, 255000, NULL, 0, 1, 300, 30, 20, 2),
('V140', 'SK05', 'SK05-Đen-Freesize', N'Đen', 'Freesize', 300, 180000, 153000, NULL, 0, 1, 300, 30, 20, 2),
('V141', 'SK05', 'SK05-Xanh Navy-Freesize', N'Xanh Navy', 'Freesize', 300, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V142', 'SK05', 'SK05-Trắng-Freesize', N'Trắng', 'Freesize', 120, 410000, 348500, NULL, 1, 1, 300, 30, 20, 2),
('V143', 'SK06', 'SK06-Đen-Freesize', N'Đen', 'Freesize', 170, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V144', 'SK06', 'SK06-Trắng-Freesize', N'Trắng', 'Freesize', 200, 400000, 340000, NULL, 0, 1, 300, 30, 20, 2),
('V145', 'SK07', 'SK07-Hồng-Freesize', N'Hồng', 'Freesize', 150, 300000, 255000, NULL, 0, 1, 300, 30, 20, 2),
('V146', 'SK07', 'SK07-Trắng-Freesize', N'Trắng', 'Freesize', 120, 180000, 153000, NULL, 0, 1, 300, 30, 20, 2),
('V147', 'SK08', 'SK08-Xanh-Freesize', N'Xanh', 'Freesize', 220, 430000, 365500, NULL, 0, 1, 300, 30, 20, 2),
('V148', 'SK08', 'SK08-Trắng Lung Linh-Freesize', N'Trắng Lung Linh', 'Freesize', 200, 410000, 348500, NULL, 0, 1, 300, 30, 20, 2),
('V149', 'SK09', 'SK09-Đen-Freesize', N'Đen', 'Freesize', 210, 200000, 170000, NULL, 0, 1, 300, 30, 20, 2),
('V150', 'SK09', 'SK09-Nâu-Freesize', N'Nâu', 'Freesize', 130, 400000, 340000, NULL, 0, 1, 300, 30, 20, 2),
('V151', 'SK10', 'SK10-Đen-1', N'Đen', '1', 120, 300000, 255000, NULL, 0, 1, 300, 30, 20, 2),
('V152', 'SK10', 'SK10-Đen-2', N'Đen', '2', 300, 180000, 153000, NULL, 0, 1, 300, 30, 20, 2),
('V153', 'SK10', 'SK10-Nâu-1', N'Nâu', '1', 300, 430000, 365500, NULL, 1, 1, 300, 30, 20, 2),
('V154', 'SK10', 'SK10-Nâu-2', N'Nâu', '2', 120, 410000, 348500, NULL, 0, 1, 300, 30, 20, 2),
('V155', 'SK06', 'SK06-Trắng-Freesize', N'Trắng', 'Freesize', 150, 400000, 340000, NULL, 0, 1, 300, 30, 20, 2),
('V156', 'SK07', 'SK07-Trắng-Freesize', N'Trắng', 'Freesize', 120, 290000, 246500, NULL, 0, 1, 300, 30, 20, 2),
('V157', 'SK08', 'SK08-Trắng Lung Linh-Freesize', N'Trắng Lung Linh', 'Freesize', 250, 450000, 382500, NULL, 1, 1, 300, 30, 20, 2),
('V158', 'SK09', 'SK09-Đen-Freesize', N'Đen', 'Freesize', 220, 220000, 187000, NULL, 0, 1, 300, 30, 20, 2),
('V159', 'SK10', 'SK10-Nâu-1', N'Nâu', '1', 200, 210000, 178500, NULL, 0, 1, 300, 30, 20, 2),
('V160', 'SK10', 'SK10-Nâu-2', N'Nâu', '2', 300, 320000, 272000, NULL, 0, 1, 300, 30, 20, 2),
('V161', 'SK10', 'SK10-Đen-1', N'Đen', '1', 300, 420000, 357000, NULL, 0, 1, 300, 30, 20, 2),
('V162', 'SK10', 'SK10-Đen-2', N'Đen', '2', 120, 330000, 280500, NULL, 1, 1, 300, 30, 20, 2),
('V163', 'C01', 'C01-Đỏ-Freesize', N'Đỏ', 'Freesize', 170, 430000, 365500, NULL, 0, 1, 300, 30, 18, 5),
('V164', 'C01', 'C01-Sọc Bé-Freesize', N'Sọc Bé', 'Freesize', 200, 410000, 348500, NULL, 1, 1, 300, 30, 18, 5),
('V165', 'C02', 'C02-Đỏ-Freesize', N'Đỏ', 'Freesize', 150, 200000, 170000, NULL, 0, 1, 300, 30, 18, 5),
('V166', 'C02', 'C02-Trắng-Freesize', N'Trắng', 'Freesize', 120, 400000, 340000, NULL, 0, 1, 300, 30, 18, 5),
('V167', 'C03', 'C03-Sọc Đỏ-Freesize', N'Sọc Đỏ', 'Freesize', 220, 300000, 255000, NULL, 0, 1, 300, 30, 18, 5),
('V168', 'C04', 'C04-Sọc Đen-Freesize', N'Sọc Đen', 'Freesize', 150, 200000, 170000, NULL, 0, 1, 300, 30, 18, 5),
('V169', 'C05', 'C05-Trắng-1', N'Trắng', '1', 120, 180000, 153000, NULL, 0, 1, 300, 30, 18, 5);

-- Bảng Orders
INSERT INTO Orders (
    order_id, user_id, order_date, fullname, phone, address, 
    total_price, shipping_fee, shipping_method_id, discount_value, 
    order_status, final_price, payment_status, payment_method, coupon_id, tracking_number
) VALUES  
('ORD01', 'U02', '2025-01-10', N'Nguyễn Văn A', '0375788987', N'123 Lê Lợi, Q1, HCM', 450000, 30000, 'S01', 30000, 0, 450000, 0, 0, 'CP01', 'ORD01-U02-TN'),
('ORD02', 'U03', '2025-01-15', N'Trần Thị B', '0964326512', N'45 Cầu Giấy, Hà Nội', 300000, 30000, 'S02', 30000, 1, 300000, 0, 0, 'CP02', 'ORD02-U03-TN'),
('ORD03', 'U05', '2025-02-01', N'Hoàng Long', '0987654321', N'15 Lê Duẩn, Đà Nẵng', 800000, 30000, 'S03', 30000, 2, 800000, 0, 1, 'CP03', 'ORD03-U05-TN'),
('ORD04', 'U06', '2025-02-05', N'Nguyễn Thanh Thủy', '0912345678', N'88 Nguyễn Huệ, Q1, HCM', 250000, 30000, 'S04', 30000, 3, 250000, 1, 2, NULL, 'ORD04-U06-TN'),
('ORD05', 'U08', '2025-02-10', N'Võ Kiều Oanh', '0934556677', N'200 Phan Chu Trinh, Huế', 1200000, 30000, 'S01', 30000, 2, 1200000, 0, 1, 'CP04', 'ORD05-U08-TN'),
('ORD06', 'U09', '2025-02-12', N'Đỗ Đức Anh', '0977889900', N'45 Láng Hạ, Đống Đa, Hà Nội', 500000, 30000, 'S02', 30000, 1, 500000, 0, 2, 'CP05', 'ORD06-U09-TN'),
('ORD07', 'U11', '2025-02-14', N'Ngô Xuân Bách', '0944332211', N'102 Quang Trung, Gò Vấp, HCM', 190000, 30000, 'S03', 28500, 3, 191500, 0, 1, NULL, 'ORD07-U11-TN'),
('ORD08', 'U13', '2025-02-18', N'Trần Gia Huy', '0909123456', N'32 Hùng Vương, Nha Trang', 600000, 30000, 'S04', 30000, 4, 600000, 0, 0, 'CP01', 'ORD08-U13-TN'),
('ORD09', 'U15', '2025-02-20', N'Phan Quốc Bảo', '0911223344', N'15 Hòa Bình, Biên Hòa', 350000, 30000, 'S01', 30000, 1, 350000, 0, 1, 'CP02', 'ORD09-U15-TN'),
('ORD10', 'U18', '2025-02-25', N'Chu Phương Thảo', '0977112233', N'412 Trường Chinh, Tân Bình, HCM', 420000, 30000, 'S03', 30000, 1, 420000, 0, 2, NULL, 'ORD10-U18-TN');

INSERT INTO Order_Details (
    detail_id, 
    order_id, 
    variant_id, 
    quantity, 
    price, 
    feedback, 
    is_reviewed
)
VALUES 
('DT001', 'ORD01', 'V001', 1, 159000, 'Áo rất đẹp, chất vải co giãn tốt!', 0),
('DT002', 'ORD01', 'V051', 1, 289000, 'Vải dày dặn, ấm áp.', 0),
('DT003', 'ORD02', 'V072', 1, 189000, 'Mặc rất tôn dáng.', 0),
('DT004', 'ORD02', 'V005', 1, 149000, 'Giao hàng nhanh.', 0),
('DT005', 'ORD03', 'V037', 2, 349000, 'Mọi người nên mua nhé!', 0),
('DT006', 'ORD03', 'V003', 1, 159000, 'Tuyệt vời, phải ủng hộ thương xuyên.', 1),
('DT007', 'ORD04', 'V104', 1, 189000, 'Hàng đẹp mà giá lại phải chăng.', 0),
('DT008', 'ORD05', 'V045', 2, 399000, 'Nhân viên tư vấn nhiệt tình, giao hàng nhanh, mình', 0),
('DT009', 'ORD05', 'V037', 1, 349000, 'Shop không bao giờ làm mình thất vọng.', 0),
('DT010', 'ORD06', 'V142', 2, 219000, 'Đóng gói chuyên nghiệp, chất vải xịn xò.', 0),
('DT011', 'ORD07', 'V088', 1, 189000, 'Vải bến đẹp, đáng tiến.', 0),
('DT012', 'ORD08', 'V051', 2, 289000, 'Sẽ mua lại, rất đáng tiền.', 0);

-- Bảng Cart
INSERT INTO Cart (
    cart_id, 
    user_id, 
    variant_id, 
    quantity, 
    session_id, 
    is_selected
)
VALUES 
('C0001', 'U04', 'V001', 2, NULL, 0),
('C0002', 'U04', 'V051', 1, NULL, 0),
('C0003', 'U07', 'V072', 1, NULL, 0),
('C0004', 'U11', 'V023', 1, NULL, 1),
('C0005', NULL, 'V015', 3, 'sess_998877abc', 0),
('C0006', NULL, 'V041', 1, 'sess_998877abc', 0),
('C0007', 'U14', 'V011', 1, NULL, 0),
('C0008', 'U17', 'V003', 2, NULL, 1);

-- Bảng dữ liệu reviews
INSERT INTO reviews (review_id, user_id, product_id, rating, comment, image, reply, status, created_at)
VALUES 
('R01', 'U01', 'T01', 4.2, N'Áo rất đẹp, chất vải co giãn tốt!', NULL, N'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2025-01-01'),
('R02', 'U02', 'T02', 4.1, N'Chất vải dày dặn, ấm áp.', NULL, N'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2024-06-30'),
('R03', 'U03', 'T03', 4.2, N'Mặc rất tôn dáng.', NULL, N'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2024-06-07'),
('R04', 'U04', 'J01', 4.9, N'Hàng như ảnh, giao hàng nhanh.', NULL, N'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2025-02-01'),
('R05', 'U05', 'J02', 4.1, N'Mọi người nên mua nhé!', NULL, N'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2024-11-11'),
('R06', 'U06', 'J03', 4.1, N'Tuyệt vời, phải ủng hộ thường xuyên.', NULL, N'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2025-12-06'),
('R07', 'U07', 'H01', 4.7, N'Hàng đẹp mà giá lại phải chăng.', NULL, N'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2025-01-01'),
('R08', 'U08', 'H02', 4.9, N'Nhân viên tư vấn nhiệt tình, giao hàng nhanh, mìn', NULL, N'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2024-06-30'),
('R09', 'U09', 'H03', 4.9, N'Shop không bao giờ làm mình thất vọng.', NULL, N'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2024-06-07'),
('R10', 'U10', 'H04', 5.0, N'Đóng gói chuyên nghiệp, chất vải xịn xò.', NULL, N'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 1, '2025-02-01');

-- Bảng Wishlist
INSERT INTO Wishlist (wishlist_id, user_id, product_id, added_date)
VALUES 
('W01', 'U01', 'T01', '2025-01-01'),
('W02', 'U02', 'T02', '2024-06-30'),
('W03', 'U03', 'T03', '2024-06-07'),
('W04', 'U04', 'T04', '2025-02-01'),
('W05', 'U05', 'T05', '2024-11-11'),
('W06', 'U06', 'T06', '2025-12-06');


-- Lệnh cập nhật cấu trúc bảng Users
ALTER TABLE Users ADD bank_name nvarchar(100);
ALTER TABLE Users ADD bank_account_number varchar(20);
ALTER TABLE Users ADD bank_account_name nvarchar(100);

-- Cập nhật dữ liệu ngân hàng cho 20 user
UPDATE Users SET bank_name = 'Vietcombank', bank_account_number = '1012233445', bank_account_name = 'QUAN TRI VIEN' WHERE user_id = 'U01';
UPDATE Users SET bank_name = 'MB Bank', bank_account_number = '987654321', bank_account_name = 'NGUYEN VAN A' WHERE user_id = 'U02';
UPDATE Users SET bank_name = 'Techcombank', bank_account_number = '19033445566', bank_account_name = 'TRAN THI B' WHERE user_id = 'U03';
UPDATE Users SET bank_name = 'VietinBank', bank_account_number = '1028877665', bank_account_name = 'LE THI C' WHERE user_id = 'U04';
UPDATE Users SET bank_name = 'BIDV', bank_account_number = '21510001234', bank_account_name = 'HOANG LONG' WHERE user_id = 'U05';
UPDATE Users SET bank_name = 'ACB', bank_account_number = '77889955', bank_account_name = 'NGUYEN THANH THUY' WHERE user_id = 'U06';
UPDATE Users SET bank_name = 'TPBank', bank_account_number = '4455667701', bank_account_name = 'PHAM MINH QUAN' WHERE user_id = 'U07';
UPDATE Users SET bank_name = 'Sacombank', bank_account_number = '601223344', bank_account_name = 'VO KIEU OANH' WHERE user_id = 'U08';
UPDATE Users SET bank_name = 'Agribank', bank_account_number = '15002051234', bank_account_name = 'DO DUC ANH' WHERE user_id = 'U09';
UPDATE Users SET bank_name = 'VPBank', bank_account_number = '155667788', bank_account_name = 'BUI THUY TIEN' WHERE user_id = 'U10';
UPDATE Users SET bank_name = 'HDBank', bank_account_number = '6870407123', bank_account_name = 'NGO XUAN BACH' WHERE user_id = 'U11';
UPDATE Users SET bank_name = 'VIB', bank_account_number = '257040655', bank_account_name = 'NGUYEN THU HA' WHERE user_id = 'U12';
UPDATE Users SET bank_name = 'SHB', bank_account_number = '1011223344', bank_account_name = 'TRAN GIA HUY' WHERE user_id = 'U13';
UPDATE Users SET bank_name = 'VietCapitalBank', bank_account_number = '8007041234', bank_account_name = 'DANG MY LINH' WHERE user_id = 'U14';
UPDATE Users SET bank_name = 'MSB', bank_account_number = '3501017788', bank_account_name = 'PHAN QUOC BAO' WHERE user_id = 'U15';
UPDATE Users SET bank_name = 'SeABank', bank_account_number = '123456', bank_account_name = 'LY CAM TU' WHERE user_id = 'U16';
UPDATE Users SET bank_name = 'OCB', bank_account_number = '41000123', bank_account_name = 'VU NHAT MINH' WHERE user_id = 'U17';
UPDATE Users SET bank_name = 'LienVietPostBank', bank_account_number = '223344556', bank_account_name = 'CHU PHUONG THAO' WHERE user_id = 'U18';
UPDATE Users SET bank_name = 'Nam A Bank', bank_account_number = '3010223344', bank_account_name = 'LE HUYNH ANH' WHERE user_id = 'U19';
UPDATE Users SET bank_name = 'Eximbank', bank_account_number = '20001484123', bank_account_name = 'LAM KHAI MINH' WHERE user_id = 'U20';

-- 1. Thêm cột số dư ví nội bộ vào bảng Người dùng, mặc định ban đầu là 0
ALTER TABLE Users ADD wallet_balance DECIMAL(15,2) DEFAULT 0;
GO

-- 2. Tạo bảng Lịch sử giao dịch ví nội bộ (Đã dùng IDENTITY thay cho AUTO_INCREMENT)
CREATE TABLE Wallet_Transactions (
    transaction_id INT IDENTITY(1,1) PRIMARY KEY, -- ID tự tăng cho dễ quản lý
    user_id CHAR(5),                               -- Liên kết với người dùng
    amount DECIMAL(15,2) NOT NULL,                 -- Số tiền biến động (Ví dụ: 150000 hoặc 50000)
    transaction_type INT NOT NULL,                 -- 1: Cộng tiền (Hoàn trả, thưởng), 2: Trừ tiền (Mua hàng)
    description VARCHAR(255),                      -- Nội dung: "Hoàn tiền đơn DH002", "Hoàn tiền đánh giá..."
    related_order_id CHAR(5) NULL,                 -- Liên kết với đơn hàng liên quan (nếu có)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, -- Thời gian giao dịch
    
    -- Ràng buộc khóa ngoại
    CONSTRAINT fk_wt_user FOREIGN KEY (user_id) REFERENCES Users(user_id),
    CONSTRAINT fk_wt_order FOREIGN KEY (related_order_id) REFERENCES Orders(order_id)
);
GO

-- 3. Thêm cột ghi nhận số tiền từ ví nội bộ đã dùng để thanh toán cho đơn hàng này
ALTER TABLE Orders ADD wallet_used_amount DECIMAL(15,2) DEFAULT 0;
GO
--Cập nhật số dư ví cho 20 user
UPDATE Users SET wallet_balance = 150000 WHERE user_id = 'U01';
UPDATE Users SET wallet_balance = 50000 WHERE user_id = 'U02';
UPDATE Users SET wallet_balance = 0 WHERE user_id = 'U03';
UPDATE Users SET wallet_balance = 250000 WHERE user_id = 'U04';
UPDATE Users SET wallet_balance = 1000000 WHERE user_id = 'U05';
UPDATE Users SET wallet_balance = 0 WHERE user_id = 'U06';
UPDATE Users SET wallet_balance = 120000 WHERE user_id = 'U07';
UPDATE Users SET wallet_balance = 0 WHERE user_id = 'U08';
UPDATE Users SET wallet_balance = 30000 WHERE user_id = 'U09';
UPDATE Users SET wallet_balance = 500000 WHERE user_id = 'U10';
UPDATE Users SET wallet_balance = 0 WHERE user_id = 'U11';
UPDATE Users SET wallet_balance = 85000 WHERE user_id = 'U12';
UPDATE Users SET wallet_balance = 0 WHERE user_id = 'U13';
UPDATE Users SET wallet_balance = 200000 WHERE user_id = 'U14';
UPDATE Users SET wallet_balance = 0 WHERE user_id = 'U15';
UPDATE Users SET wallet_balance = 450000 WHERE user_id = 'U16';
UPDATE Users SET wallet_balance = 15000 WHERE user_id = 'U17';
UPDATE Users SET wallet_balance = 0 WHERE user_id = 'U18';
UPDATE Users SET wallet_balance = 75000 WHERE user_id = 'U19';
UPDATE Users SET wallet_balance = 0 WHERE user_id = 'U20';



-- THÊM DỮ LIỆU LỊCH SỬ GIAO DỊCH VÍ CHO TOÀN BỘ 20 USER
INSERT INTO Wallet_Transactions (user_id, amount, transaction_type, description, related_order_id, created_at)
VALUES 
-- User 01 (Quản trị viên / Test)
('U01', 150000, 1, N'Hoàn tiền đơn DH002', 'ORD02', '2026-02-26 14:30:00'),
('U01', 50000, 2, N'Sử dụng ví thanh toán đơn DH005', 'ORD05', '2026-03-01 09:15:00'),
('U01', 50000, 1, N'Thưởng hạng thành viên Vàng', NULL, '2026-03-10 20:00:00'),

-- User 02
('U02', 50000, 1, N'Hoàn tiền do lỗi vận chuyển', 'ORD01', '2026-03-15 10:20:00'),

-- User 03 (Có cộng vào và trừ ra hết sạch)
('U03', 100000, 1, N'Tặng tiền đăng ký tài khoản mới', NULL, '2026-01-05 08:00:00'),
('U03', 100000, 2, N'Sử dụng ví thanh toán đơn ORD02', 'ORD02', '2026-01-15 09:00:00'),

-- User 04
('U04', 250000, 1, N'Hoàn tiền đơn hàng khách trả lại', NULL, '2026-03-10 14:00:00'),

-- User 05 (VIP)
('U05', 1000000, 1, N'Thưởng khách hàng mua sỉ tháng 3', NULL, '2026-04-01 08:00:00'),

-- User 06
('U06', 50000, 1, N'Hoàn tiền đánh giá sản phẩm', NULL, '2026-02-10 11:00:00'),
('U06', 50000, 2, N'Thanh toán một phần đơn ORD04', 'ORD04', '2026-02-15 15:30:00'),

-- User 07
('U07', 120000, 1, N'Hoàn tiền chênh lệch phí ship', NULL, '2026-03-20 16:45:00'),

-- User 08
('U08', 30000, 1, N'Quà tặng sinh nhật tháng 2', NULL, '2026-02-05 07:00:00'),
('U08', 30000, 2, N'Thanh toán phí ship đơn ORD05', 'ORD05', '2026-02-10 09:30:00'),

-- User 09
('U09', 30000, 1, N'Hoàn tiền đánh giá 5 sao có tâm', NULL, '2026-03-01 19:20:00'),

-- User 10
('U10', 500000, 1, N'Hoàn tiền bồi thường sản phẩm lỗi', NULL, '2026-03-25 10:15:00'),

-- User 11
('U11', 20000, 1, N'Thưởng tham gia Minigame Facebook', NULL, '2026-02-10 21:00:00'),
('U11', 20000, 2, N'Sử dụng ví thanh toán đơn ORD07', 'ORD07', '2026-02-14 10:00:00'),

-- User 12
('U12', 85000, 1, N'Hoàn tiền do khách hủy đơn hàng', NULL, '2026-04-05 13:40:00'),

-- User 13
('U13', 100000, 1, N'Quà tặng khách hàng mới', NULL, '2026-01-20 09:00:00'),
('U13', 100000, 2, N'Sử dụng ví thanh toán đơn ORD08', 'ORD08', '2026-02-18 14:20:00'),

-- User 14
('U14', 200000, 1, N'Hoàn tiền chương trình Flash Sale', NULL, '2026-03-30 22:00:00'),

-- User 15
('U15', 50000, 1, N'Hoàn tiền phí vận chuyển', NULL, '2026-02-15 16:10:00'),
('U15', 50000, 2, N'Sử dụng ví thanh toán đơn ORD09', 'ORD09', '2026-02-20 11:45:00'),

-- User 16
('U16', 450000, 1, N'Hoàn tiền đổi trả do nhầm size', NULL, '2026-04-02 08:30:00'),

-- User 17
('U17', 15000, 1, N'Hoàn tiền đánh giá có kèm hình ảnh', NULL, '2026-03-12 20:15:00'),

-- User 18
('U18', 40000, 1, N'Quy đổi voucher thành tiền mặt', NULL, '2026-02-20 09:00:00'),
('U18', 40000, 2, N'Sử dụng ví thanh toán đơn ORD10', 'ORD10', '2026-02-25 15:00:00'),

-- User 19
('U19', 75000, 1, N'Hoàn tiền xin lỗi do giao hàng trễ', NULL, '2026-03-28 17:30:00'),

-- User 20
('U20', 25000, 1, N'Thưởng hoa hồng giới thiệu bạn bè', NULL, '2026-04-01 10:00:00'),
('U20', 25000, 2, N'Rút tiền về thẻ ngân hàng', NULL, '2026-04-05 18:00:00');


-- 2. Cập nhật số tiền ví đã dùng cho các đơn có sử dụng ví (Khớp với bảng Wallet_Transactions vừa nạp)
UPDATE Orders SET wallet_used_amount = 100000 WHERE order_id = 'ORD02'; -- Của user U03
UPDATE Orders SET wallet_used_amount = 50000  WHERE order_id = 'ORD04'; -- Của user U06
UPDATE Orders SET wallet_used_amount = 30000  WHERE order_id = 'ORD05'; -- Của user U08
UPDATE Orders SET wallet_used_amount = 20000  WHERE order_id = 'ORD07'; -- Của user U11
UPDATE Orders SET wallet_used_amount = 100000 WHERE order_id = 'ORD08'; -- Của user U13
UPDATE Orders SET wallet_used_amount = 50000  WHERE order_id = 'ORD09'; -- Của user U15
UPDATE Orders SET wallet_used_amount = 40000  WHERE order_id = 'ORD10'; -- Của user U18
