-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2026 at 03:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30
SET FOREIGN_KEY_CHECKS = 0;

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ntk`
--
CREATE DATABASE IF NOT EXISTS `ntk` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ntk`;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `cart_id` char(5) NOT NULL,
  `user_id` char(5) DEFAULT NULL,
  `variant_id` char(5) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `is_selected` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `variant_id`, `quantity`, `session_id`, `is_selected`) VALUES
('C0001', 'U04', 'V001', 2, NULL, 0),
('C0002', 'U04', 'V051', 1, NULL, 0),
('C0003', 'U07', 'V072', 1, NULL, 0),
('C0004', 'U11', 'V023', 1, NULL, 1),
('C0005', NULL, 'V015', 3, 'sess_998877abc', 0),
('C0006', NULL, 'V041', 1, 'sess_998877abc', 0),
('C0007', 'U14', 'V011', 1, NULL, 0),
('C0008', 'U17', 'V003', 2, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` char(5) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_show_home` int(11) DEFAULT 1,
  `priority` int(11) DEFAULT 0,
  `description` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `slug`, `image_url`, `is_show_home`, `priority`, `description`) VALUES
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

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `coupon_id` char(5) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `discount_type` int(11) DEFAULT NULL,
  `discount_value` decimal(15,2) DEFAULT NULL,
  `min_order_value` decimal(15,2) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `max_discount_amount` decimal(15,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`coupon_id`, `code`, `discount_type`, `discount_value`, `min_order_value`, `start_date`, `end_date`, `max_discount_amount`, `quantity`, `used_count`, `status`) VALUES
('CP01', 'WELCOME', 0, 10.00, 250000.00, '2024-01-01 00:00:00', '2025-01-01 00:00:00', 30000.00, 1000, 25, 1),
('CP02', 'FREESHIP', 1, 20000.00, 200000.00, '2024-01-01 00:00:00', '2024-06-30 00:00:00', NULL, 500, 33, 1),
('CP03', 'SALE', 0, 10.00, 500000.00, '2024-06-01 00:00:00', '2024-06-07 00:00:00', 50000.00, 100, 56, 0),
('CP04', 'TET', 1, 50000.00, 1000000.00, '2025-01-01 00:00:00', '2025-02-01 00:00:00', NULL, 50, 78, 0),
('CP05', 'NTK', 0, 10.00, 2000000.00, '2024-11-11 00:00:00', '2024-12-11 00:00:00', 200000.00, 20, 23, 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `order_id` char(5) NOT NULL,
  `payos_order_code` bigint(20) DEFAULT NULL,
  `payos_qr_code` text DEFAULT NULL,
  `user_id` char(5) DEFAULT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `fullname` varchar(100) DEFAULT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `total_price` decimal(15,2) DEFAULT NULL,
  `shipping_fee` decimal(15,2) DEFAULT NULL,
  `shipping_method_id` char(5) DEFAULT NULL,
  `discount_value` decimal(15,2) DEFAULT NULL,
  `order_status` int(11) DEFAULT NULL,
  `final_price` decimal(15,2) DEFAULT NULL,
  `payment_status` int(11) DEFAULT 0,
  `payment_method` int(11) DEFAULT NULL,
  `coupon_id` char(5) DEFAULT NULL,
  `tracking_number` varchar(50) DEFAULT NULL,
  `wallet_used_amount` decimal(15,2) DEFAULT 0.00,
  `note` varchar(500) DEFAULT NULL,
  `payos_checkout_url` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `payos_order_code`, `payos_qr_code`, `user_id`, `order_date`, `fullname`, `phone`, `address`, `total_price`, `shipping_fee`, `shipping_method_id`, `discount_value`, `order_status`, `final_price`, `payment_status`, `payment_method`, `coupon_id`, `tracking_number`, `wallet_used_amount`, `note`, `payos_checkout_url`) VALUES
('O0002', 2604207261, NULL, 'U3237', '2026-04-20 20:13:59', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 205000.00, 35000.00, NULL, NULL, 0, 205000.00, 0, 2, NULL, NULL, 0.00, '', NULL),
('O0004', 2604202477, '00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454063750005802VN62250821CSPZKHEL6A2 NTK O000463044729', 'U3237', '2026-04-20 20:50:31', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 375000.00, 35000.00, NULL, NULL, 0, 375000.00, 0, 2, NULL, NULL, 0.00, '', 'https://pay.payos.vn/web/a3c93636f3844eb899f5ad1cee488633'),
('O0005', 2604207573, '00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454061880005802VN62250821CS3GXCGU662 NTK O00056304C3F0', 'U3237', '2026-04-20 20:53:55', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 188000.00, 35000.00, NULL, NULL, 0, 188000.00, 0, 2, NULL, NULL, 0.00, '', 'https://pay.payos.vn/web/939a438485c54bc29f5ab23b52676dad'),
('O0006', 2604209391, NULL, 'U3237', '2026-04-20 20:55:07', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 290000.00, 35000.00, NULL, NULL, 1, 290000.00, 1, 1, NULL, NULL, 0.00, '', NULL),
('O0007', 2604201143, NULL, 'U3237', '2026-04-20 20:56:55', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 188000.00, 35000.00, NULL, NULL, 1, 188000.00, 1, 1, NULL, NULL, 0.00, '', NULL),
('O0008', 2604209254, '00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454062050005802VN62250821CSKH5XTLOH8 NTK O000863042EFB', 'U3237', '2026-04-20 21:00:28', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 205000.00, 35000.00, NULL, NULL, 1, 205000.00, 1, 2, NULL, NULL, 0.00, '', 'https://pay.payos.vn/web/1d35d523e4974b91ad50f5d49fa3d328'),
('O0009', 2604201275, NULL, 'U3237', '2026-04-20 21:21:19', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 290000.00, 35000.00, NULL, NULL, 1, 290000.00, 1, 1, NULL, NULL, 0.00, '', NULL),
('O0010', 2604207687, NULL, 'U3237', '2026-04-20 21:47:35', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 375000.00, 35000.00, NULL, NULL, 1, 375000.00, 0, 1, NULL, NULL, 0.00, '', NULL),
('O0011', 2604205924, NULL, 'U3237', '2026-04-20 21:47:53', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 205000.00, 35000.00, NULL, NULL, 5, 205000.00, 0, 1, NULL, NULL, 0.00, '', NULL),
('O0013', 2604201477, NULL, 'U3237', '2026-04-20 21:49:21', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 205000.00, 35000.00, NULL, NULL, 5, 205000.00, 0, 1, NULL, NULL, 0.00, '', NULL),
('O0015', 2604204885, '00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454062050005802VN62250821CS8TZRFQK86 NTK O00156304F2EA', 'U3237', '2026-04-20 21:50:09', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 205000.00, 35000.00, NULL, NULL, 5, 205000.00, 1, 2, NULL, NULL, 0.00, '', 'https://pay.payos.vn/web/6a3d5ff0f80e48f58b4aea3293b9d897'),
('O0016', 2604204002, '00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454062050005802VN62250821CSUO0FD6OD1 NTK O00166304DFA1', 'U3237', '2026-04-20 21:56:42', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 205000.00, 35000.00, NULL, NULL, 4, 205000.00, 1, 2, NULL, NULL, 0.00, '', 'https://pay.payos.vn/web/70757ae94f3b4662aaff59c5886414ed'),
('O0017', 2604206761, '00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454062050005802VN62250821CS4XA6E4IC7 NTK O00176304A22A', 'U3237', '2026-04-20 23:16:22', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 205000.00, 35000.00, NULL, NULL, 1, 205000.00, 1, 2, NULL, NULL, 0.00, '', 'https://pay.payos.vn/web/f6e1212dfce64199b0a3857c29e6ca6d'),
('O0019', 2604204756, '00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA53037045405350005802VN62250821CSOXH6HVWS2 NTK O001963042306', 'U3237', '2026-04-20 23:18:38', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 35000.00, 35000.00, NULL, NULL, 1, 35000.00, 1, 2, NULL, NULL, 0.00, '', 'https://pay.payos.vn/web/8a09a86005bf40f7a77d0dd1119d0be3'),
('O0020', 2604212219, NULL, 'U3237', '2026-04-21 12:25:30', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 375000.00, 35000.00, NULL, NULL, 3, 375000.00, 0, 1, NULL, NULL, 0.00, '', NULL),
('O0022', 2604225406, NULL, 'U3237', '2026-04-22 08:36:00', 'Tram Nguyen', '0373546431', 'Hồ Chí Minh', 290000.00, 35000.00, NULL, NULL, 4, 0.00, 1, 2, NULL, NULL, 290000.00, '', NULL),
('ORD01', NULL, NULL, 'U02', '2025-01-10 00:00:00', 'Nguyễn Văn A', '0375788987', '123 Lê Lợi, Q1, HCM', 450000.00, 30000.00, 'S01', 30000.00, 0, 450000.00, 0, 0, 'CP01', 'ORD01-U02-TN', 0.00, NULL, NULL),
('ORD02', NULL, NULL, 'U03', '2025-01-15 00:00:00', 'Trần Thị B', '0964326512', '45 Cầu Giấy, Hà Nội', 300000.00, 30000.00, 'S02', 30000.00, 1, 300000.00, 0, 0, 'CP02', 'ORD02-U03-TN', 100000.00, NULL, NULL),
('ORD03', NULL, NULL, 'U05', '2025-02-01 00:00:00', 'Hoàng Long', '0987654321', '15 Lê Duẩn, Đà Nẵng', 800000.00, 30000.00, 'S03', 30000.00, 2, 800000.00, 0, 1, 'CP03', 'ORD03-U05-TN', 0.00, NULL, NULL),
('ORD04', NULL, NULL, 'U06', '2025-02-05 00:00:00', 'Nguyễn Thanh Thủy', '0912345678', '88 Nguyễn Huệ, Q1, HCM', 250000.00, 30000.00, 'S04', 30000.00, 3, 250000.00, 1, 2, NULL, 'ORD04-U06-TN', 50000.00, NULL, NULL),
('ORD05', NULL, NULL, 'U08', '2025-02-10 00:00:00', 'Võ Kiều Oanh', '0934556677', '200 Phan Chu Trinh, Huế', 1200000.00, 30000.00, 'S01', 30000.00, 2, 1200000.00, 0, 1, 'CP04', 'ORD05-U08-TN', 30000.00, NULL, NULL),
('ORD06', NULL, NULL, 'U09', '2025-02-12 00:00:00', 'Đỗ Đức Anh', '0977889900', '45 Láng Hạ, Đống Đa, Hà Nội', 500000.00, 30000.00, 'S02', 30000.00, 1, 500000.00, 0, 2, 'CP05', 'ORD06-U09-TN', 0.00, NULL, NULL),
('ORD07', NULL, NULL, 'U11', '2025-02-14 00:00:00', 'Ngô Xuân Bách', '0944332211', '102 Quang Trung, Gò Vấp, HCM', 190000.00, 30000.00, 'S03', 28500.00, 3, 191500.00, 0, 1, NULL, 'ORD07-U11-TN', 20000.00, NULL, NULL),
('ORD08', NULL, NULL, 'U13', '2025-02-18 00:00:00', 'Trần Gia Huy', '0909123456', '32 Hùng Vương, Nha Trang', 600000.00, 30000.00, 'S04', 30000.00, 4, 600000.00, 0, 0, 'CP01', 'ORD08-U13-TN', 100000.00, NULL, NULL),
('ORD09', NULL, NULL, 'U15', '2025-02-20 00:00:00', 'Phan Quốc Bảo', '0911223344', '15 Hòa Bình, Biên Hòa', 350000.00, 30000.00, 'S01', 30000.00, 1, 350000.00, 0, 1, 'CP02', 'ORD09-U15-TN', 50000.00, NULL, NULL),
('ORD10', NULL, NULL, 'U18', '2025-02-25 00:00:00', 'Chu Phương Thảo', '0977112233', '412 Trường Chinh, Tân Bình, HCM', 420000.00, 30000.00, 'S03', 30000.00, 1, 420000.00, 0, 2, NULL, 'ORD10-U18-TN', 40000.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

DROP TABLE IF EXISTS `order_details`;
CREATE TABLE `order_details` (
  `detail_id` char(5) NOT NULL,
  `order_id` char(5) DEFAULT NULL,
  `variant_id` char(5) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `feedback` varchar(500) DEFAULT NULL,
  `is_reviewed` int(11) DEFAULT 0,
  `product_name` varchar(200) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`detail_id`, `order_id`, `variant_id`, `quantity`, `price`, `feedback`, `is_reviewed`, `product_name`, `unit_price`) VALUES
('D0006', 'O0002', 'V165', 1, 170000.00, NULL, 0, 'Áo Len Cổ Tròn Lông Thỏ', NULL),
('D0008', 'O0004', 'V165', 2, 170000.00, NULL, 0, 'Áo Len Cổ Tròn Lông Thỏ', NULL),
('D0009', 'O0005', 'V140', 1, 153000.00, NULL, 0, 'Quần Váy Ngắn Dáng Xoè', NULL),
('D0010', 'O0006', 'V167', 1, 255000.00, NULL, 0, 'Áo Len Kẻ Sọc Thu Đông', NULL),
('D0011', 'O0007', 'V169', 1, 153000.00, NULL, 0, 'Áo Len Mỏng Cộc Tay', NULL),
('D0012', 'O0008', 'V168', 1, 170000.00, NULL, 0, 'Áo Lông Thỏ Dài Tay', NULL),
('D0013', 'O0009', 'V167', 1, 255000.00, NULL, 0, 'Áo Len Kẻ Sọc Thu Đông', NULL),
('D0014', 'O0010', 'V165', 2, 170000.00, NULL, 0, 'Áo Len Cổ Tròn Lông Thỏ', NULL),
('D0015', 'O0011', 'V165', 1, 170000.00, NULL, 0, 'Áo Len Cổ Tròn Lông Thỏ', NULL),
('D0017', 'O0013', 'V165', 1, 170000.00, NULL, 0, 'Áo Len Cổ Tròn Lông Thỏ', NULL),
('D0019', 'O0015', 'V165', 1, 170000.00, NULL, 0, 'Áo Len Cổ Tròn Lông Thỏ', NULL),
('D0020', 'O0016', 'V165', 1, 170000.00, NULL, 0, 'Áo Len Cổ Tròn Lông Thỏ', NULL),
('D0021', 'O0017', 'V165', 1, 170000.00, NULL, 0, 'Áo Len Cổ Tròn Lông Thỏ', NULL),
('D0023', 'O0020', 'V165', 2, 170000.00, NULL, 0, 'Áo Len Cổ Tròn Lông Thỏ', NULL),
('D0025', 'O0022', 'V167', 1, 255000.00, NULL, 0, 'Áo Len Kẻ Sọc Thu Đông', NULL),
('DT001', 'ORD01', 'V001', 1, 159000.00, 'Áo rất đẹp, chất vải co giãn tốt!', 0, NULL, NULL),
('DT002', 'ORD01', 'V051', 1, 289000.00, 'Vải dày dặn, ấm áp.', 0, NULL, NULL),
('DT003', 'ORD02', 'V072', 1, 189000.00, 'Mặc rất tôn dáng.', 0, NULL, NULL),
('DT004', 'ORD02', 'V005', 1, 149000.00, 'Giao hàng nhanh.', 0, NULL, NULL),
('DT005', 'ORD03', 'V037', 2, 349000.00, 'Mọi người nên mua nhé!', 0, NULL, NULL),
('DT006', 'ORD03', 'V003', 1, 159000.00, 'Tuyệt vời, phải ủng hộ thương xuyên.', 1, NULL, NULL),
('DT007', 'ORD04', 'V104', 1, 189000.00, 'Hàng đẹp mà giá lại phải chăng.', 0, NULL, NULL),
('DT008', 'ORD05', 'V045', 2, 399000.00, 'Nhân viên tư vấn nhiệt tình, giao hàng nhanh, mình', 0, NULL, NULL),
('DT009', 'ORD05', 'V037', 1, 349000.00, 'Shop không bao giờ làm mình thất vọng.', 0, NULL, NULL),
('DT010', 'ORD06', 'V142', 2, 219000.00, 'Đóng gói chuyên nghiệp, chất vải xịn xò.', 0, NULL, NULL),
('DT011', 'ORD07', 'V088', 1, 189000.00, 'Vải bến đẹp, đáng tiến.', 0, NULL, NULL),
('DT012', 'ORD08', 'V051', 2, 289000.00, 'Sẽ mua lại, rất đáng tiền.', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` char(5) NOT NULL,
  `category_id` char(5) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `sold_count` int(11) DEFAULT 0,
  `status` int(11) DEFAULT 1,
  `avg_rating` decimal(2,1) DEFAULT NULL,
  `total_reviews` int(11) DEFAULT 0,
  `seo_title` varchar(150) DEFAULT NULL,
  `seo_description` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `name`, `description`, `image`, `rating`, `sold_count`, `status`, `avg_rating`, `total_reviews`, `seo_title`, `seo_description`) VALUES
('C01', 'CAT10', 'Áo Khoác Cardigan Len', 'Áo Khoác Cardigan Len Hàn Quốc Dày Dặn Nhiều Màu Thêu Logo', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m4hdzd36m4q8c9@resize_w900_nl.webp', 4.8, 58, 1, 4.8, 100, 'Mua Áo Len giá tốt tại NTK Fashion', 'Sản phẩm Áo Len chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('C02', 'CAT10', 'Áo Len Cổ Tròn Lông Thỏ', 'Áo Len Cổ Tròn Lông Thỏ Mềm Mịn Áo Sweater Sợi Dệt Dày Dặn Ấm Áp Mùa Đông', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mji6wkvavqx1fa@resize_w900_nl.webp', 4.4, 167, 1, 4.8, 100, 'Mua Áo Len giá tốt tại NTK Fashion', 'Sản phẩm Áo Len chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('C03', 'CAT10', 'Áo Len Kẻ Sọc Thu Đông', 'Áo Len Dài Tay Thu Đông Kẻ Sọc Croptop Phong Cách Hàn Quốc Basic Năng Động', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mhmvya34ka2p92@resize_w900_nl.webp', 4.7, 378, 1, 4.8, 100, 'Mua Áo Len giá tốt tại NTK Fashion', 'Sản phẩm Áo Len chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('C04', 'CAT10', 'Áo Lông Thỏ Dài Tay', 'Áo Lông Thỏ Dài Tay Mềm Mịn Áo Len Kẻ Sọc Sleeves Form Rộng Basic Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mji9pf2xw1dzb9@resize_w900_nl.webp', 4.4, 365, 1, 4.8, 100, 'Mua Áo Len giá tốt tại NTK Fashion', 'Sản phẩm Áo Len chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('C05', 'CAT10', 'Áo Len Mỏng Cộc Tay', 'Áo Len Mỏng Mùa Thu Cộc Tay Phối Màu Áo Len Có Cổ Thoáng Khí Dễ Phối Đồ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mdbj2ec8tetb7a@resize_w900_nl.webp', 4.1, 242, 1, 4.8, 100, 'Mua Áo Len giá tốt tại NTK Fashion', 'Sản phẩm Áo Len chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H01', 'CAT03', 'Áo Hoodie Zip basic', 'Áo Hoodie Zip Basic Vải Nỉ 2 Da Chống Nắng Tốt Form Rộng Nam Nữ Unisex', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m1djz3jqsva0d1.webp', 4.6, 305, 1, 4.6, 92, 'Mua Áo Hoodie Zip basic giá tốt tại NTK Fashion', 'Sản phẩm Áo Hoodie Zip basic chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H02', 'CAT03', 'Áo Hoodie Zip phối Caro', 'Áo Hoodie Zip Phối Caro Nỉ 2 Da Thêu 77 Foreveryoung Form Rộng Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg6a54w1k8az55.webp', 4.8, 422, 1, 4.8, 127, 'Mua Áo Hoodie Zip phối Caro giá tốt tại NTK Fashion', 'Sản phẩm Áo Hoodie Zip phối Caro chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H03', 'CAT03', 'Áo hoodie cờ Mỹ', 'Áo Hoodie in lụa cờ Mỹ Form Rộng Phong Cách Âu Mỹ Unisex Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg253sm8k6x758.webp', 4.5, 59, 1, 4.5, 18, 'Mua Áo hoodie cờ Mỹ giá tốt tại NTK Fashion', 'Sản phẩm Áo hoodie cờ Mỹ chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H04', 'CAT03', 'Áo Hoodie Zip Nỉ Bông Form Boxy', 'Áo Hoodie Zip Nỉ Bông Basic Form Boxy Urban Khoá Kéo BYC Streetwear Unisex Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mk0do4ap7xtz53.webp', 4.4, 334, 1, 4.4, 100, 'Mua Áo Hoodie Zip Nỉ Bông Form Boxy giá tốt tại NTK Fashion', 'Sản phẩm Áo Hoodie Zip Nỉ Bông Form Boxy chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H05', 'CAT03', 'Áo Hoodie Zip Nỉ Bông Khoá Kéo 2 Đầu', 'Áo Hoodie Zip Nỉ Bông Khoá Kéo 2 Đầu WITHLOVE Form Boxy Basic Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mdzue2ay0tmrba.webp', 4.8, 490, 1, 4.8, 147, 'Mua Áo Hoodie Zip Nỉ Bông Khoá Kéo 2 Đầu giá tốt tại NTK Fashion', 'Sản phẩm Áo Hoodie Zip Nỉ Bông Khoá Kéo 2 Đầu chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H06', 'CAT03', 'Áo Hoodie Zip ORIGINALS', 'Áo Hoodie Zip ORIGINALS Nỉ 2 Da Không Xù Chữ Thêu', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mdwm103kinlud9.webp', 4.1, 387, 1, 4.1, 116, 'Mua Áo Hoodie Zip ORIGINALS giá tốt tại NTK Fashion', 'Sản phẩm Áo Hoodie Zip ORIGINALS chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('H07', 'CAT03', 'Áo Khoác Hoodie Zip Nỉ Chân Cua', 'Áo Khoác Hoodie Zip Nỉ Chân Cua Dày Dặn Áo Hoodie Form Boxy Unisex Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mjibi5ytlb0l94.webp', 4.2, 237, 1, 4.2, 71, 'Mua Áo Khoác Hoodie Zip Nỉ Chân Cua giá tốt tại NTK Fashion', 'Sản phẩm Áo Khoác Hoodie Zip Nỉ Chân Cua chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('J01', 'CAT02', 'Áo khoác da', 'Áo Khoác Da Tay Dài Kèm Túi Trong Da Cao Cấp Phong Cách Retro Cổ Điển', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mh71v1j7gb9nc8.webp', 4.8, 311, 1, 4.8, 93, 'Mua Áo khoác da giá tốt tại NTK Fashion', 'Sản phẩm Áo khoác da chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('J02', 'CAT02', 'Áo khoác dù', 'Áo Khoác Dù Chắn Gió Nhiều Màu Mũ Dây Rút Chống Nắng Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mf3y04daxxxm7b.webp', 4.5, 229, 1, 4.5, 69, 'Mua Áo khoác dù giá tốt tại NTK Fashion', 'Sản phẩm Áo khoác dù chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('J03', 'CAT02', 'Áo khoác Canvas', 'Áo Khoác Canvas Dáng Ngắn Áo Khoác Phối Cổ Nhung Tăm Basic Unisex Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg7d8s7jrvnvb7.webp', 4.1, 214, 1, 4.1, 64, 'Mua Áo khoác Canvas giá tốt tại NTK Fashion', 'Sản phẩm Áo khoác Canvas chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('J04', 'CAT02', 'Áo khoác Phao', 'Áo Khoác Phao Phồng Siêu Nhẹ Siêu Ấm Áo Phao Béo Dáng Lửng Mùa Đông', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m5ca38ruq4ae89.webp', 4.7, 474, 1, 4.7, 142, 'Mua Áo khoác Phao giá tốt tại NTK Fashion', 'Sản phẩm Áo khoác Phao chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('J05', 'CAT02', 'Áo khoác Bomber', 'Áo Khoác Bomber Pilot Oversized Chần Bông Thêu Chữ Thời Trang Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m33curbuutamf5.webp', 4.3, 244, 1, 4.3, 73, 'Mua Áo khoác Bomber giá tốt tại NTK Fashion', 'Sản phẩm Áo khoác Bomber chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('JE01', 'CAT08', 'Quần Jean Dáng Bí', 'Quần Jean Dáng Bí Cat Washing Denim Retro Unisex Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfcb1buk8sumb0@resize_w900_nl.webp', 4.8, 353, 1, 4.8, 100, 'Mua  Quần Jeans  giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('JE02', 'CAT08', 'Quần Jeans Mềm Dáng Dài', 'Quần Jeans Mềm Dáng Dài Gập Gấu Quần Dài Form Rộng Chất Denim Mềm Đứng Form Unisex', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mftf1gdhowll3e@resize_w900_nl.webp', 4.3, 123, 1, 4.8, 100, 'Mua  Quần Jeans  giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('JE03', 'CAT08', 'Quần Jean Dáng Lửng Dài Demi', 'Quần Jean Dáng Lửng Dài Demi Jean Short Năng Động Denim Wash', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mbc529lr4zbsf1@resize_w900_nl.webp', 4.8, 127, 1, 4.8, 100, 'Mua  Quần Jeans  giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('JE04', 'CAT08', 'Quần Jeans Wash', 'Quần Jeans Wash New Cạp Cao Quần Bò Ống Rộng Tôn Dáng Basic', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfxc467plp8te5@resize_w900_nl.webp', 4.0, 305, 1, 4.8, 100, 'Mua  Quần Jeans  giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('JE05', 'CAT08', 'Quần Bò Wash Màu', 'Quần Jean Ống Rộng Tôn Dáng Quần Bò Wash Màu Unisex Thời Trang Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfzb05oeal8odb@resize_w900_nl.webp', 4.4, 422, 1, 4.8, 100, 'Mua  Quần Jeans  giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
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
('PL01', 'CAT07', 'Áo Thun Polo Phối Cổ', 'Áo Thun Polo Phối Cổ Basic Năng Động Cho Nữ Xuân Hè 2025', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcrbsixysl9pbc@resize_w900_nl.webp', 4.7, 413, 1, 4.7, 124, 'Mua Áo Thun Polo Phối Cổ giá tốt tại NTK Fashion', 'Sản phẩm Áo Thun Polo Phối Cổ chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('PL02', 'CAT07', 'Áo Polo Kẻ Sọc BabyTee', 'Áo Polo Kẻ Sọc BabyTee Họa Tiết Thêu Thiết Kế Tôn Dáng Cho Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m9o595a72b1qf8@resize_w900_nl.webp', 4.2, 359, 1, 4.2, 108, 'Mua Áo Polo Kẻ Sọc BabyTee giá tốt tại NTK Fashion', 'Sản phẩm Áo Polo Kẻ Sọc BabyTee chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('PL03', 'CAT07', 'Áo Polo Chiết Eo Tay Bồng', 'Áo Polo Chiết Eo Tay Bồng Form Ôm Vừa Tôn Dáng Cho Nữ Xuân Hè 2025', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m9k2akpj4mqi7b@resize_w900_nl.webp', 4.7, 499, 1, 4.7, 150, 'Mua Áo Polo Chiết Eo Tay Bồng giá tốt tại NTK Fashion', 'Sản phẩm Áo Polo Chiết Eo Tay Bồng chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('PL04', 'CAT07', 'Áo Thun Dài Tay Polo Kẻ Ngang', 'Áo Thun Dài Tay Polo Kẻ Ngang Sọc Lớn Hàn Quốc Thu Đông Logo Thêu Trendy', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mh5pidvewdu7e2@resize_w900_nl.webp', 4.7, 175, 1, 4.7, 53, 'Mua Áo Thun Dài Tay Polo Kẻ Ngang giá tốt tại NTK Fashion', 'Sản phẩm Áo Thun Dài Tay Polo Kẻ Ngang chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('PL05', 'CAT07', 'Áo Polo Basic Babytee', 'Áo Polo Basic Babytee Cho Nữ Vải Cá Sấu Cotton Logo Thêu Túi Ngực', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m0mh43fd5ocvc4@resize_w900_nl.webp', 4.7, 163, 1, 4.7, 49, 'Mua Áo Polo Basic Babytee giá tốt tại NTK Fashion', 'Sản phẩm Áo Polo Basic Babytee chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('PL06', 'CAT07', 'Áo Len Dài Tay Cổ Polo', 'Áo Len Dài Tay Cổ Polo Áo Len Vặn Thừng Basic Chất Mịn Dày Dặn Premium', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mjl2uiqymznrd3@resize_w900_nl.webp', 4.3, 436, 1, 4.3, 131, 'Mua Áo Len Dài Tay Cổ Polo giá tốt tại NTK Fashion', 'Sản phẩm Áo Len Dài Tay Cổ Polo chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S01', 'CAT05', 'Áo Sơ Mi Basic', 'Áo Sơ Mi Basic Nhiều Màu Dáng Rộng Họa Tiết Kẻ Sọc Thời Trang Đường Phố', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-llqdtiaj374v5c.webp', 4.7, 450, 1, 4.7, 135, 'Mua Áo Sơ Mi Basic giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Basic chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S02', 'CAT05', 'Áo Sơ Mi Chiết Eo', 'Áo Sơ Mi Chiết Eo Buộc Nơ Có Túi Ngực Dành Cho Nữ Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-md2x7l0cran0fb@resize_w900_nl.webp', 4.1, 484, 1, 4.1, 145, 'Mua Áo Sơ Mi Chiết Eo giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Chiết Eo chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S03', 'CAT05', 'Áo Sơ Mi Kẻ CỘC TAY', 'Áo Sơ Mi Kẻ CỘC TAY Vải Oxford Phối Cổ Trắng Dáng Rộng Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-ltdmmaiv6t9548@resize_w900_nl.webp', 4.3, 180, 1, 4.3, 54, 'Mua Áo Sơ Mi Kẻ CỘC TAY giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Kẻ CỘC TAY chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S04', 'CAT05', 'Áo Sơ Mi Form Fit', 'Áo Sơ Mi Bycamcam Form Fit Trơn Nhiều Màu Thoáng Khí Đứng Form', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lzrhde113o9t1f.webp', 4.6, 253, 1, 4.6, 76, 'Mua Áo Sơ Mi Form Fit giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Form Fit chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S05', 'CAT05', 'Áo Sơ Mi Cộc Tay Form Rộng', 'Áo Sơ Mi Cộc Tay Form Rộng Trẻ Trung Hoạ Tiết Kẻ Khoá Trái Tim', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mbgh9gzxgbc0ed.webp', 4.2, 430, 1, 4.2, 129, 'Mua Áo Sơ Mi Cộc Tay Form Rộng giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Cộc Tay Form Rộng chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S06', 'CAT05', 'Áo Sơ Mi Kẻ Sọc Tay Dài Mỏng Mát', 'Áo Sơ Mi Kẻ Sọc Tay Dài Mỏng Mát Form Rộng Vạt Tôm Thời Trang Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lxunl9nhukt783@resize_w900_nl.webp', 4.8, 357, 1, 4.8, 107, 'Mua Áo Sơ Mi Kẻ Sọc Tay Dài Mỏng Mát giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Kẻ Sọc Tay Dài Mỏng Mát chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('S07', 'CAT05', 'Áo Sơ Mi Kẻ Sọc Cổ Nhọn', 'Áo Sơ Mi Kẻ Sọc Cổ Nhọn Striped Shirt Dáng Lửng Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lvzhuqgyr1ej37@resize_w900_nl.webp', 4.5, 82, 1, 4.5, 25, 'Mua Áo Sơ Mi Kẻ Sọc Cổ Nhọn giá tốt tại NTK Fashion', 'Sản phẩm Áo Sơ Mi Kẻ Sọc Cổ Nhọn chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SH01', 'CAT06', 'Quần Short Kaki Túi Hộp', 'Quần Short Kaki Túi Hộp Dáng Ngắn Phong Cách Retro', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcg2d2ixd4fw7b@resize_w900_nl.webp', 4.7, 396, 1, 4.7, 119, 'Mua Quần Short Kaki Túi Hộp giá tốt tại NTK Fashion', 'Sản phẩm Quần Short Kaki Túi Hộp chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SH02', 'CAT06', 'Quần Short Dù Thể Thao', 'Quần Short Dù Thể Thao Sọc Vải Dù Phong Cách Sporty', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-md5i7uvw67nj7a@resize_w900_nl.webp', 4.1, 138, 1, 4.1, 41, 'Mua Quần Short Dù Thể Thao giá tốt tại NTK Fashion', 'Sản phẩm Quần Short Dù Thể Thao chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('SH03', 'CAT06', 'Quần Jeans Short Lửng', 'Quần Jeans Short Lửng Cạp Đính Cúc Vải Denim Phong Cách Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfxj5ibz0u8c2f@resize_w900_nl.webp', 4.7, 194, 1, 4.7, 58, 'Mua Quần Jeans Short Lửng giá tốt tại NTK Fashion', 'Sản phẩm Quần Jeans Short Lửng chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
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
('T01', 'CAT01', 'Áo thun babytee thể thao', 'Áo Thun Babytee Thể Thao Jersey Soccer Hack Dáng Đường Phố Cho Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7ne96vcjmiu46.webp', 4.2, 433, 1, 4.2, 130, 'Mua Áo thun babytee thể thao giá tốt tại NTK Fashion', 'Sản phẩm Áo thun babytee thể thao chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T02', 'CAT01', 'Áo thun babytee cổ ôm', 'Áo Babytee Y2K Cổ Ôm 100% Cotton Phong Cách Streetwear 2025', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-med1e440xbev37.webp', 4.7, 180, 1, 4.7, 54, 'Mua Áo thun babytee cổ ôm giá tốt tại NTK Fashion', 'Sản phẩm Áo thun babytee cổ ôm chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T03', 'CAT01', 'Áo thun babytee basic', 'Áo Thun Baby Tee Basic 100% Cotton HOT TREND dễ phối đồ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mee3cwm3s4cgfd.webp', 4.4, 239, 1, 4.4, 72, 'Mua Áo thun babytee basic giá tốt tại NTK Fashion', 'Sản phẩm Áo thun babytee basic chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T04', 'CAT01', 'Áo thun kiểu trễ vai', 'Áo Thun Sọc Trễ Vai Áo Trễ Vai Phối Dây Buộc Nơ Nữ Tính Hàn Quốc', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-meb5j0sgmvba74.webp', 4.6, 446, 1, 4.6, 134, 'Mua Áo thun kiểu trễ vai giá tốt tại NTK Fashion', 'Sản phẩm Áo thun kiểu trễ vai chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T05', 'CAT01', 'Áo thun tay dài', 'Áo Thun Kẻ Long Sleeves Cotton Kẻ Dày Dặn Logo Thêu Unisex Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mgomv4ifjpqiab.webp', 4.9, 491, 1, 4.9, 147, 'Mua Áo thun tay dài giá tốt tại NTK Fashion', 'Sản phẩm Áo thun tay dài chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T06', 'CAT01', 'Áo thun form rộng', 'Áo Thun Kẻ 100% Cotton Stripes Tee Form Rộng Oversized Nam Nữ', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mdy3txnrb8qoab@resize_w900_nl.webp', 4.3, 314, 1, 4.3, 94, 'Mua Áo thun form rộng giá tốt tại NTK Fashion', 'Sản phẩm Áo thun form rộng chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T07', 'CAT01', 'Áo babytee chấm bi', 'Áo Babytee Phối Hoạ Tiết Chấm Bi Áo tay Raglan Nữ Tính Trendy', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mjtii2dx62o585.webp', 4.5, 437, 1, 4.5, 131, 'Mua Áo babytee chấm bi giá tốt tại NTK Fashion', 'Sản phẩm Áo babytee chấm bi chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T08', 'CAT01', 'Áo Babytee Lucky Horse', 'Áo Babytee Lucky Horse Form Basic Chào Năm Mới May Mắn 2026', 'https://down-vn.img.susercontent.com/file/vn-11134207-81ztc-mkdpz011e29xc7.webp', 4.4, 301, 1, 4.4, 90, 'Mua Áo Babytee Lucky Horse giá tốt tại NTK Fashion', 'Sản phẩm Áo Babytee Lucky Horse chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T09', 'CAT01', 'Áo babytee đứng form', 'Áo Thun Babytee 3-Star Form Fit Regular Cotton 2 Chiều Đứng Form', 'https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfaxfknhsxze9d.webp', 4.5, 101, 1, 4.5, 30, 'Mua Áo babytee đứng form giá tốt tại NTK Fashion', 'Sản phẩm Áo babytee đứng form chất lượng cao, thiết kế chuẩn, giao hàng nhanh.'),
('T10', 'CAT01', 'Áo Baby Tee \"I Love Cat\"', 'Áo Baby Tee \"I Love Cat\" 100% Cotton', 'https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lza1i2khf9zh61.webp', 4.3, 302, 1, 4.3, 91, 'Mua Áo Baby Tee \"I Love Cat\" giá tốt tại NTK Fashion', 'Sản phẩm Áo Baby Tee \"I Love Cat\" chất lượng cao, thiết kế chuẩn, giao hàng nhanh.');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `variant_id` char(5) NOT NULL,
  `product_id` char(5) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `original_price` decimal(15,2) DEFAULT NULL,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_featured` int(11) DEFAULT 0,
  `is_active` int(11) DEFAULT 1,
  `weight` int(11) DEFAULT NULL,
  `length` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`variant_id`, `product_id`, `sku`, `color`, `size`, `stock`, `original_price`, `sale_price`, `image`, `is_featured`, `is_active`, `weight`, `length`, `width`, `height`) VALUES
('V001', 'T01', 'T01-Trắng-S', 'Trắng', 'S', 150, 330000.00, 280500.00, NULL, 0, 1, 200, 25, 20, 2),
('V002', 'T01', 'T01-Trắng-M', 'Trắng', 'M', 120, 230000.00, 195500.00, NULL, 0, 1, 200, 25, 20, 2),
('V003', 'T01', 'T01-Xanh Navy-S', 'Xanh Navy', 'S', 200, 180000.00, 153000.00, NULL, 0, 1, 200, 25, 20, 2),
('V004', 'T01', 'T01-Xanh Navy-M', 'Xanh Navy', 'M', 215, 230000.00, 195500.00, NULL, 0, 1, 200, 25, 20, 2),
('V005', 'T02', 'T02-Đen-S', 'Đen', 'S', 170, 250000.00, 212500.00, NULL, 0, 1, 200, 25, 20, 2),
('V006', 'T02', 'T02-Đen-M', 'Đen', 'M', 180, 280000.00, 238000.00, NULL, 0, 1, 200, 25, 20, 2),
('V007', 'T02', 'T02-Đen-L', 'Đen', 'L', 210, 250000.00, 212500.00, NULL, 0, 1, 200, 25, 20, 2),
('V008', 'T02', 'T02-Ghi-S', 'Ghi', 'S', 200, 190000.00, 161500.00, NULL, 0, 1, 200, 25, 20, 2),
('V009', 'T02', 'T02-Ghi-M', 'Ghi', 'M', 100, 310000.00, 263500.00, NULL, 0, 1, 200, 25, 20, 2),
('V010', 'T02', 'T02-Ghi-L', 'Ghi', 'L', 110, 250000.00, 212500.00, NULL, 0, 1, 200, 25, 20, 2),
('V011', 'T03', 'T03-Hồng-S', 'Hồng', 'S', 110, 420000.00, 357000.00, NULL, 1, 1, 200, 25, 20, 2),
('V012', 'T03', 'T03-Hồng-M', 'Hồng', 'M', 110, 180000.00, 153000.00, NULL, 0, 1, 200, 25, 20, 2),
('V013', 'T04', 'T04-Sọc Trắng-S', 'Sọc Trắng', 'S', 220, 350000.00, 297500.00, NULL, 0, 1, 200, 25, 20, 2),
('V014', 'T04', 'T04-Sọc Trắng-M', 'Sọc Trắng', 'M', 200, 350000.00, 297500.00, NULL, 0, 1, 200, 25, 20, 2),
('V015', 'T04', 'T04-Nâu-S', 'Nâu', 'S', 210, 320000.00, 272000.00, NULL, 0, 1, 200, 25, 20, 2),
('V016', 'T04', 'T04-Nâu-M', 'Nâu', 'M', 130, 220000.00, 187000.00, NULL, 0, 1, 200, 25, 20, 2),
('V017', 'T05', 'T05-Xanh-S', 'Xanh', 'S', 120, 350000.00, 297500.00, NULL, 0, 1, 200, 25, 20, 2),
('V018', 'T05', 'T05-Xanh-M', 'Xanh', 'M', 300, 240000.00, 204000.00, NULL, 0, 1, 200, 25, 20, 2),
('V019', 'T05', 'T05-Đen-S', 'Đen', 'S', 300, 200000.00, 170000.00, NULL, 0, 1, 200, 25, 20, 2),
('V020', 'T05', 'T05-Đen-M', 'Đen', 'M', 120, 330000.00, 280500.00, NULL, 0, 1, 200, 25, 20, 2),
('V021', 'T06', 'T06-Trắng-S', 'Trắng', 'S', 170, 310000.00, 263500.00, NULL, 0, 1, 200, 25, 20, 2),
('V022', 'T06', 'T06-Trắng-M', 'Trắng', 'M', 200, 330000.00, 280500.00, NULL, 1, 1, 200, 25, 20, 2),
('V023', 'T06', 'T06-Kem-S', 'Kem', 'S', 150, 440000.00, 374000.00, NULL, 0, 1, 200, 25, 20, 2),
('V024', 'T06', 'T06-Kem-M', 'Kem', 'M', 120, 180000.00, 153000.00, NULL, 0, 1, 200, 25, 20, 2),
('V025', 'T07', 'T07-Trắng-S', 'Trắng', 'S', 220, 400000.00, 340000.00, NULL, 0, 1, 200, 25, 20, 2),
('V026', 'T07', 'T07-Trắng-M', 'Trắng', 'M', 200, 260000.00, 221000.00, NULL, 0, 1, 200, 25, 20, 2),
('V027', 'T07', 'T07-Xanh-S', 'Xanh', 'S', 210, 310000.00, 263500.00, NULL, 0, 1, 200, 25, 20, 2),
('V028', 'T07', 'T07-Xanh-M', 'Xanh', 'M', 130, 270000.00, 229500.00, NULL, 0, 1, 200, 25, 20, 2),
('V029', 'T08', 'T08-Đen-S', 'Đen', 'S', 120, 420000.00, 357000.00, NULL, 0, 1, 200, 25, 20, 2),
('V030', 'T08', 'T08-Đen-M', 'Đen', 'M', 300, 400000.00, 340000.00, NULL, 0, 1, 200, 25, 20, 2),
('V031', 'T08', 'T08-Trắng-S', 'Trắng', 'S', 300, 240000.00, 204000.00, NULL, 0, 1, 200, 25, 20, 2),
('V032', 'T08', 'T08-Trắng-M', 'Trắng', 'M', 120, 270000.00, 229500.00, NULL, 0, 1, 200, 25, 20, 2),
('V033', 'T09', 'T09-Đen-S', 'Đen', 'S', 150, 440000.00, 374000.00, NULL, 1, 1, 200, 25, 20, 2),
('V034', 'T09', 'T09-Đen-M', 'Đen', 'M', 120, 220000.00, 187000.00, NULL, 0, 1, 200, 25, 20, 2),
('V035', 'T09', 'T09-Đen-L', 'Đen', 'L', 200, 290000.00, 246500.00, NULL, 0, 1, 200, 25, 20, 2),
('V036', 'T10', 'T10-Xanh-S', 'Xanh', 'S', 215, 290000.00, 246500.00, NULL, 0, 1, 200, 25, 20, 2),
('V037', 'T10', 'T10-Xanh-M', 'Xanh', 'M', 170, 310000.00, 263500.00, NULL, 0, 1, 200, 25, 20, 2),
('V038', 'T10', 'T10-Xanh-L', 'Xanh', 'L', 180, 200000.00, 170000.00, NULL, 0, 1, 200, 25, 20, 2),
('V039', 'J01', 'J01-Xanh-S', 'Xanh', 'S', 210, 350000.00, 297500.00, NULL, 0, 1, 300, 30, 20, 2),
('V040', 'J01', 'J01-Xanh-M', 'Xanh', 'M', 200, 280000.00, 238000.00, NULL, 0, 1, 300, 30, 20, 2),
('V041', 'J01', 'J01-Xanh-L', 'Xanh', 'L', 100, 280000.00, 238000.00, NULL, 0, 1, 300, 30, 20, 2),
('V042', 'J02', 'J02-Đen-S', 'Đen', 'S', 110, 420000.00, 357000.00, NULL, 0, 1, 300, 30, 20, 2),
('V043', 'J02', 'J02-Đen-M', 'Đen', 'M', 110, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V044', 'J02', 'J02-Đen-L', 'Đen', 'L', 110, 290000.00, 246500.00, NULL, 1, 1, 300, 30, 20, 2),
('V045', 'J03', 'J03-Ghi-S', 'Ghi', 'S', 220, 220000.00, 187000.00, NULL, 0, 1, 300, 30, 20, 2),
('V046', 'J03', 'J03-Ghi-M', 'Ghi', 'M', 200, 280000.00, 238000.00, NULL, 0, 1, 300, 30, 20, 2),
('V047', 'J03', 'J03-Ghi-L', 'Ghi', 'L', 210, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 20, 2),
('V048', 'J04', 'J04-Xanh-S', 'Xanh', 'S', 130, 320000.00, 272000.00, NULL, 0, 1, 300, 30, 20, 2),
('V049', 'J04', 'J04-Xanh-M', 'Xanh', 'M', 120, 420000.00, 357000.00, NULL, 0, 1, 300, 30, 20, 2),
('V050', 'J04', 'J04-Xanh-L', 'Xanh', 'L', 300, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V051', 'J05', 'J05-Xanh Nhạt-S', 'Xanh Nhạt', 'S', 300, 230000.00, 195500.00, NULL, 0, 1, 300, 30, 20, 2),
('V052', 'J05', 'J05-Xanh Nhạt-M', 'Xanh Nhạt', 'M', 120, 330000.00, 280500.00, NULL, 0, 1, 300, 30, 20, 2),
('V053', 'J05', 'J05-Xanh Nhạt-L', 'Xanh Nhạt', 'L', 170, 230000.00, 195500.00, NULL, 0, 1, 300, 30, 20, 2),
('V069', 'H01', 'H01-Đen-S', 'Đen', 'S', 200, 450000.00, 382500.00, NULL, 0, 1, 300, 30, 20, 2),
('V070', 'H01', 'H01-Đen-M', 'Đen', 'M', 215, 220000.00, 187000.00, NULL, 0, 1, 300, 30, 20, 2),
('V071', 'H01', 'H01-Đen-L', 'Đen', 'L', 170, 270000.00, 229500.00, NULL, 0, 1, 300, 30, 20, 2),
('V072', 'H02', 'H02-Ghi-S', 'Ghi', 'S', 180, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V073', 'H02', 'H02-Ghi-M', 'Ghi', 'M', 210, 440000.00, 374000.00, NULL, 0, 1, 300, 30, 20, 2),
('V074', 'H02', 'H02-Ghi-L', 'Ghi', 'L', 200, 280000.00, 238000.00, NULL, 0, 1, 300, 30, 20, 2),
('V075', 'H03', 'H03-Xanh-S', 'Xanh', 'S', 100, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V076', 'H03', 'H03-Xanh-M', 'Xanh', 'M', 110, 410000.00, 348500.00, NULL, 0, 1, 300, 30, 20, 2),
('V077', 'H03', 'H03-Xanh-L', 'Xanh', 'L', 110, 200000.00, 170000.00, NULL, 1, 1, 300, 30, 20, 2),
('V078', 'H04', 'H04-Nâu-S', 'Nâu', 'S', 110, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 20, 2),
('V079', 'H04', 'H04-Nâu-M', 'Nâu', 'M', 220, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 20, 2),
('V080', 'H04', 'H04-Nâu-L', 'Nâu', 'L', 200, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V081', 'H05', 'H05-Đỏ-S', 'Đỏ', 'S', 210, 180000.00, 153000.00, NULL, 0, 1, 300, 30, 20, 2),
('V082', 'H05', 'H05-Đỏ-M', 'Đỏ', 'M', 130, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V083', 'H05', 'H05-Đỏ-L', 'Đỏ', 'L', 120, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V084', 'H06', 'H06-Trắng-S', 'Trắng', 'S', 300, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 20, 2),
('V085', 'H06', 'H06-Trắng-M', 'Trắng', 'M', 300, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 20, 2),
('V086', 'H06', 'H06-Trắng-L', 'Trắng', 'L', 120, 180000.00, 153000.00, NULL, 0, 1, 300, 30, 20, 2),
('V087', 'H07', 'H07-Vàng-S', 'Vàng', 'S', 170, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V088', 'H07', 'H07-Vàng-M', 'Vàng', 'M', 200, 410000.00, 348500.00, NULL, 1, 1, 300, 30, 20, 2),
('V089', 'H07', 'H07-Vàng-L', 'Vàng', 'L', 150, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V099', 'S01', 'S01-Trắng-S', 'Trắng', 'S', 150, 430000.00, 365500.00, NULL, 1, 1, 300, 30, 20, 2),
('V100', 'S01', 'S01-Trắng-M', 'Trắng', 'M', 120, 410000.00, 348500.00, NULL, 0, 1, 300, 30, 20, 2),
('V101', 'S01', 'S01-Trắng-L', 'Trắng', 'L', 200, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V102', 'S02', 'S02-Xanh-S', 'Xanh', 'S', 215, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 20, 2),
('V103', 'S02', 'S02-Xanh-M', 'Xanh', 'M', 170, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 20, 2),
('V104', 'S02', 'S02-Xanh-L', 'Xanh', 'L', 180, 180000.00, 153000.00, NULL, 0, 1, 300, 30, 20, 2),
('V105', 'S03', 'S03-Vàng-S', 'Vàng', 'S', 210, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V106', 'S03', 'S03-Vàng-M', 'Vàng', 'M', 200, 410000.00, 348500.00, NULL, 0, 1, 300, 30, 20, 2),
('V107', 'S03', 'S03-Vàng-L', 'Vàng', 'L', 100, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V108', 'S04', 'S04-Đen-S', 'Đen', 'S', 110, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 20, 2),
('V109', 'S04', 'S04-Đen-M', 'Đen', 'M', 110, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 20, 2),
('V110', 'S04', 'S04-Đen-L', 'Đen', 'L', 110, 180000.00, 153000.00, NULL, 1, 1, 300, 30, 20, 2),
('V111', 'S05', 'S05-Đỏ-S', 'Đỏ', 'S', 220, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V112', 'S05', 'S05-Đỏ-M', 'Đỏ', 'M', 200, 410000.00, 348500.00, NULL, 0, 1, 300, 30, 20, 2),
('V113', 'S05', 'S05-Đỏ-L', 'Đỏ', 'L', 210, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V114', 'S06', 'S06-Hồng-S', 'Hồng', 'S', 130, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 20, 2),
('V115', 'S06', 'S06-Hồng-M', 'Hồng', 'M', 120, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 20, 2),
('V116', 'S06', 'S06-Hồng-L', 'Hồng', 'L', 300, 180000.00, 153000.00, NULL, 0, 1, 300, 30, 20, 2),
('V117', 'S07', 'S07-Tím-S', 'Tím', 'S', 300, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V118', 'S07', 'S07-Tím-M', 'Tím', 'M', 120, 410000.00, 348500.00, NULL, 0, 1, 300, 30, 20, 2),
('V119', 'S07', 'S07-Tím-L', 'Tím', 'L', 170, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V129', 'SK01', 'SK01-Đen-Freesize', 'Đen', 'Freesize', 300, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V130', 'SK01', 'SK01-Kem-Freesize', 'Kem', 'Freesize', 120, 410000.00, 348500.00, NULL, 0, 1, 300, 30, 20, 2),
('V131', 'SK01', 'SK01-Nâu-Freesize', 'Nâu', 'Freesize', 170, 200000.00, 170000.00, NULL, 1, 1, 300, 30, 20, 2),
('V132', 'SK02', 'SK02-Đen-Freesize', 'Đen', 'Freesize', 200, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 20, 2),
('V133', 'SK02', 'SK02-Xám-Freesize', 'Xám', 'Freesize', 150, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 20, 2),
('V134', 'SK03', 'SK03-Đen-Freesize', 'Đen', 'Freesize', 120, 180000.00, 153000.00, NULL, 0, 1, 300, 30, 20, 2),
('V135', 'SK03', 'SK03-Trắng-Freesize', 'Trắng', 'Freesize', 220, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V136', 'SK03', 'SK03-Xám-Freesize', 'Xám', 'Freesize', 200, 410000.00, 348500.00, NULL, 0, 1, 300, 30, 20, 2),
('V137', 'SK04', 'SK04-Nâu-Freesize', 'Nâu', 'Freesize', 210, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V138', 'SK04', 'SK04-Đen-Freesize', 'Đen', 'Freesize', 130, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 20, 2),
('V139', 'SK04', 'SK04-Ghi-Freesize', 'Ghi', 'Freesize', 120, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 20, 2),
('V140', 'SK05', 'SK05-Đen-Freesize', 'Đen', 'Freesize', 300, 180000.00, 153000.00, NULL, 0, 1, 300, 30, 20, 2),
('V141', 'SK05', 'SK05-Xanh Navy-Freesize', 'Xanh Navy', 'Freesize', 300, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V142', 'SK05', 'SK05-Trắng-Freesize', 'Trắng', 'Freesize', 120, 410000.00, 348500.00, NULL, 1, 1, 300, 30, 20, 2),
('V143', 'SK06', 'SK06-Đen-Freesize', 'Đen', 'Freesize', 170, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V144', 'SK06', 'SK06-Trắng-Freesize', 'Trắng', 'Freesize', 200, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 20, 2),
('V145', 'SK07', 'SK07-Hồng-Freesize', 'Hồng', 'Freesize', 150, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 20, 2),
('V146', 'SK07', 'SK07-Trắng-Freesize', 'Trắng', 'Freesize', 120, 180000.00, 153000.00, NULL, 0, 1, 300, 30, 20, 2),
('V147', 'SK08', 'SK08-Xanh-Freesize', 'Xanh', 'Freesize', 220, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 20, 2),
('V148', 'SK08', 'SK08-Trắng Lung Linh-Freesize', 'Trắng Lung Linh', 'Freesize', 200, 410000.00, 348500.00, NULL, 0, 1, 300, 30, 20, 2),
('V149', 'SK09', 'SK09-Đen-Freesize', 'Đen', 'Freesize', 210, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 20, 2),
('V150', 'SK09', 'SK09-Nâu-Freesize', 'Nâu', 'Freesize', 130, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 20, 2),
('V151', 'SK10', 'SK10-Đen-1', 'Đen', '1', 120, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 20, 2),
('V152', 'SK10', 'SK10-Đen-2', 'Đen', '2', 300, 180000.00, 153000.00, NULL, 0, 1, 300, 30, 20, 2),
('V153', 'SK10', 'SK10-Nâu-1', 'Nâu', '1', 300, 430000.00, 365500.00, NULL, 1, 1, 300, 30, 20, 2),
('V154', 'SK10', 'SK10-Nâu-2', 'Nâu', '2', 120, 410000.00, 348500.00, NULL, 0, 1, 300, 30, 20, 2),
('V155', 'SK06', 'SK06-Trắng-Freesize', 'Trắng', 'Freesize', 150, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 20, 2),
('V156', 'SK07', 'SK07-Trắng-Freesize', 'Trắng', 'Freesize', 120, 290000.00, 246500.00, NULL, 0, 1, 300, 30, 20, 2),
('V157', 'SK08', 'SK08-Trắng Lung Linh-Freesize', 'Trắng Lung Linh', 'Freesize', 250, 450000.00, 382500.00, NULL, 1, 1, 300, 30, 20, 2),
('V158', 'SK09', 'SK09-Đen-Freesize', 'Đen', 'Freesize', 220, 220000.00, 187000.00, NULL, 0, 1, 300, 30, 20, 2),
('V159', 'SK10', 'SK10-Nâu-1', 'Nâu', '1', 200, 210000.00, 178500.00, NULL, 0, 1, 300, 30, 20, 2),
('V160', 'SK10', 'SK10-Nâu-2', 'Nâu', '2', 300, 320000.00, 272000.00, NULL, 0, 1, 300, 30, 20, 2),
('V161', 'SK10', 'SK10-Đen-1', 'Đen', '1', 300, 420000.00, 357000.00, NULL, 0, 1, 300, 30, 20, 2),
('V162', 'SK10', 'SK10-Đen-2', 'Đen', '2', 120, 330000.00, 280500.00, NULL, 1, 1, 300, 30, 20, 2),
('V163', 'C01', 'C01-Đỏ-Freesize', 'Đỏ', 'Freesize', 170, 430000.00, 365500.00, NULL, 0, 1, 300, 30, 18, 5),
('V164', 'C01', 'C01-Sọc Bé-Freesize', 'Sọc Bé', 'Freesize', 200, 410000.00, 348500.00, NULL, 1, 1, 300, 30, 18, 5),
('V165', 'C02', 'C02-Đỏ-Freesize', 'Đỏ', 'Freesize', 150, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 18, 5),
('V166', 'C02', 'C02-Trắng-Freesize', 'Trắng', 'Freesize', 120, 400000.00, 340000.00, NULL, 0, 1, 300, 30, 18, 5),
('V167', 'C03', 'C03-Sọc Đỏ-Freesize', 'Sọc Đỏ', 'Freesize', 220, 300000.00, 255000.00, NULL, 0, 1, 300, 30, 18, 5),
('V168', 'C04', 'C04-Sọc Đen-Freesize', 'Sọc Đen', 'Freesize', 150, 200000.00, 170000.00, NULL, 0, 1, 300, 30, 18, 5),
('V169', 'C05', 'C05-Trắng-1', 'Trắng', '1', 120, 180000.00, 153000.00, NULL, 0, 1, 300, 30, 18, 5);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `review_id` char(5) NOT NULL,
  `user_id` char(5) DEFAULT NULL,
  `product_id` char(5) DEFAULT NULL,
  `rating` float DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `reply` varchar(500) DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `product_id`, `rating`, `comment`, `image`, `reply`, `status`, `created_at`) VALUES
('R01', 'U01', 'T01', 4.2, 'Áo rất đẹp, chất vải co giãn tốt!', NULL, 'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2025-01-01 00:00:00'),
('R02', 'U02', 'T02', 4.1, 'Chất vải dày dặn, ấm áp.', NULL, 'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2024-06-30 00:00:00'),
('R03', 'U03', 'T03', 4.2, 'Mặc rất tôn dáng.', NULL, 'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2024-06-07 00:00:00'),
('R04', 'U04', 'J01', 4.9, 'Hàng như ảnh, giao hàng nhanh.', NULL, 'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2025-02-01 00:00:00'),
('R05', 'U05', 'J02', 4.1, 'Mọi người nên mua nhé!', NULL, 'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2024-11-11 00:00:00'),
('R06', 'U06', 'J03', 4.1, 'Tuyệt vời, phải ủng hộ thường xuyên.', NULL, 'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2025-12-06 00:00:00'),
('R07', 'U07', 'H01', 4.7, 'Hàng đẹp mà giá lại phải chăng.', NULL, 'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2025-01-01 00:00:00'),
('R08', 'U08', 'H02', 4.9, 'Nhân viên tư vấn nhiệt tình, giao hàng nhanh, mìn', NULL, 'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2024-06-30 00:00:00'),
('R09', 'U09', 'H03', 4.9, 'Shop không bao giờ làm mình thất vọng.', NULL, 'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 0, '2024-06-07 00:00:00'),
('R10', 'U10', 'H04', 5, 'Đóng gói chuyên nghiệp, chất vải xịn xò.', NULL, 'NTK xin hân hạnh được phục vụ bạn trong những lần tiếp theo <3', 1, '2025-02-01 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_methods`
--

DROP TABLE IF EXISTS `shipping_methods`;
CREATE TABLE `shipping_methods` (
  `shipping_method_id` char(5) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT NULL,
  `estimated_delivery` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipping_methods`
--

INSERT INTO `shipping_methods` (`shipping_method_id`, `name`, `cost`, `estimated_delivery`) VALUES
('S01', 'SPX', 35000.00, '2-3 ngày'),
('S02', 'GHN', 40000.00, '2-4 ngày'),
('S03', 'GHTK', 25000.00, '3-5 ngày'),
('S04', 'J&T', 30000.00, '1-2 ngày');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` char(5) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phonenumber` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `verification_code_expires_at` datetime DEFAULT NULL,
  `is_verified` int(11) DEFAULT 0,
  `role` int(11) DEFAULT 0,
  `status` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `total_orders` int(11) DEFAULT 0,
  `total_spend` decimal(15,2) DEFAULT 0.00,
  `bank_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `bank_account_number` varchar(20) DEFAULT NULL,
  `bank_account_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `wallet_balance` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `fullname`, `email`, `phonenumber`, `address`, `verification_code`, `verification_code_expires_at`, `is_verified`, `role`, `status`, `created_at`, `total_orders`, `total_spend`, `bank_name`, `bank_account_number`, `bank_account_name`, `wallet_balance`) VALUES
('U01', 'admin', 'admin123', 'Quản Trị Viên', 'admin@ntk.vn', '334275834', 'Kho tổng HCM', NULL, NULL, 1, 1, 1, '2024-01-01 00:00:00', 0, 0.00, 'Vietcombank', '1012233445', 'QUAN TRI VIEN', 150000.00),
('U02', 'nguyenvana', 'pass123', 'Nguyễn Văn A', 'ana@gmail.com', '375788987', '123 Lê Lợi, Q1, HCM', NULL, NULL, 0, 1, 1, '2024-01-15 00:00:00', 5, 2500000.00, 'MB Bank', '987654321', 'NGUYEN VAN A', 50000.00),
('U03', 'tranthib', 'pass123', 'Trần Thị B', 'bib@gmail.com', '964326512', '45 Cầu Giấy, Hà Nội', NULL, NULL, 0, 0, 1, '2024-02-10 00:00:00', 2, 850000.00, 'Techcombank', '19033445566', 'TRAN THI B', 0.00),
('U04', 'lethic', 'pass123', 'Lê Thị C', 'cic@gmail.com', '901239876', '10 Nguyễn Trãi, Q5', NULL, NULL, 0, 1, 0, '2024-03-05 00:00:00', 0, 0.00, 'VietinBank', '1028877665', 'LE THI C', 250000.00),
('U05', 'hoanglong', 'pass123', 'Hoàng Long', 'longh@gmail.com', '987654321', '15 Lê Duẩn, Đà Nẵng', NULL, NULL, 0, 1, 1, '2024-03-20 00:00:00', 12, 15000000.00, 'BIDV', '21510001234', 'HOANG LONG', 1000000.00),
('U06', 'thanhthuy', 'pass123', 'Nguyễn Thanh Thúy', 'thuynt@gmail.com', '912345678', '88 Nguyễn Huệ, Q1, HCM', NULL, NULL, 0, 0, 1, '2024-04-12 00:00:00', 1, 450000.00, 'ACB', '77889955', 'NGUYEN THANH THUY', 0.00),
('U07', 'minhquan', 'pass123', 'Phạm Minh Quân', 'quanpm@gmail.com', '905112233', '12 Trần Phú, Hải Phòng', NULL, NULL, 0, 1, 1, '2024-05-01 00:00:00', 8, 6200000.00, 'TPBank', '4455667701', 'PHAM MINH QUAN', 120000.00),
('U08', 'kieuoanh', 'pass123', 'Võ Kiều Oanh', 'oanhvk@gmail.com', '934556677', '200 Phan Chu Trinh, Huế', NULL, NULL, 0, 1, 0, '2024-05-18 00:00:00', 0, 0.00, 'Sacombank', '601223344', 'VO KIEU OANH', 0.00),
('U09', 'ducanh', 'pass123', 'Đỗ Đức Anh', 'anhdd@gmail.com', '977889900', '45 Láng Hạ, Đống Đa, Hà Nội', NULL, NULL, 0, 1, 1, '2024-06-02 00:00:00', 3, 1200000.00, 'Agribank', '15002051234', 'DO DUC ANH', 30000.00),
('U10', 'thuytien', 'pass123', 'Bùi Thủy Tiên', 'tienbt@gmail.com', '966554433', '77 Cách Mạng Tháng 8, Cần Thơ', NULL, NULL, 0, 0, 1, '2024-06-25 00:00:00', 15, 22000000.00, 'VPBank', '155667788', 'BUI THUY TIEN', 500000.00),
('U11', 'xuanbach', 'pass123', 'Ngô Xuân Bách', 'bachnx@gmail.com', '944332211', '102 Quang Trung, Gò Vấp, HCM', NULL, NULL, 0, 1, 1, '2024-07-10 00:00:00', 4, 3100000.00, 'HDBank', '6870407123', 'NGO XUAN BACH', 0.00),
('U12', 'thuha', 'pass123', 'Nguyễn Thu Hà', 'hant@gmail.com', '922110099', '56 Kim Mã, Ba Đình, Hà Nội', NULL, NULL, 0, 1, 1, '2024-07-30 00:00:00', 7, 5400000.00, 'VIB', '257040655', 'NGUYEN THU HA', 85000.00),
('U13', 'giahuy', 'pass123', 'Trần Gia Huy', 'huytg@gmail.com', '909123456', '32 Hùng Vương, Nha Trang', NULL, NULL, 0, 0, 1, '2024-08-14 00:00:00', 0, 0.00, 'SHB', '1011223344', 'TRAN GIA HUY', 0.00),
('U14', 'mylinh', 'pass123', 'Đặng Mỹ Linh', 'linhdm@gmail.com', '988776655', '120 Võ Văn Kiệt, Q5, HCM', NULL, NULL, 0, 1, 1, '2024-09-05 00:00:00', 2, 980000.00, 'VietCapitalBank', '8007041234', 'DANG MY LINH', 200000.00),
('U15', 'quocbao', 'pass123', 'Phan Quốc Bảo', 'baopq@gmail.com', '911223344', '15 Hòa Bình, Biên Hòa', NULL, NULL, 0, 1, 0, '2024-09-21 00:00:00', 0, 0.00, 'MSB', '3501017788', 'PHAN QUOC BAO', 0.00),
('U16', 'camtu', 'pass123', 'Lý Cẩm Tú', 'tulc@gmail.com', '933445566', '09 Lê Lợi, TP Vinh', NULL, NULL, 0, 1, 1, '2024-10-08 00:00:00', 6, 4200000.00, 'SeABank', '123456', 'LY CAM TU', 450000.00),
('U17', 'nhatminh', 'pass123', 'Vũ Nhật Minh', 'minhvn@gmail.com', '955667788', '22 Điện Biên Phủ, Đà Nẵng', NULL, NULL, 0, 1, 1, '2024-10-25 00:00:00', 10, 8900000.00, 'OCB', '41000123', 'VU NHAT MINH', 15000.00),
('U18', 'phuongthao', 'pass123', 'Chu Phương Thảo', 'thaocp@gmail.com', '977112233', '412 Trường Chinh, Tân Bình, HCM', NULL, NULL, 0, 1, 1, '2024-11-12 00:00:00', 3, 1150000.00, 'LienVietPostBank', '223344556', 'CHU PHUONG THAO', 0.00),
('U19', 'huynhanh', 'pass123', 'Lê Huỳnh Anh', 'anhlh@gmail.com', '900223344', '89 Nguyễn Trãi, Thanh Xuân, HN', NULL, NULL, 0, 1, 1, '2024-11-30 00:00:00', 5, 2750000.00, 'Nam A Bank', '3010223344', 'LE HUYNH ANH', 75000.00),
('U20', 'lamminh', 'pass123', 'Lâm Khải Minh', 'lminh@gmail.com', '335378609', 'Trần Đại Nghĩa, Dĩ An, Bình Dương', NULL, NULL, 1, 0, 0, '2024-11-30 00:00:00', 3, 400000.00, 'Eximbank', '20001484123', 'LAM KHAI MINH', 0.00),
('U3237', 'nguyenthithuytram03062006gl@gmail.com', '8de7d4ce14a6925213c332d32906b880', 'Tram Nguyen', 'nguyenthithuytram03062006gl@gmail.com', '0373546431', NULL, NULL, NULL, 1, 0, 1, '2026-04-08 16:11:12', 0, 0.00, NULL, NULL, NULL, 820000.00),
('U3768', 'test@gmail.com', '482c811da5d5b4bc6d497ffa98491e38', 'Test User', 'test@gmail.com', '0900000001', NULL, '3192', NULL, 0, 0, 1, '2026-04-19 20:20:34', 0, 0.00, NULL, NULL, NULL, 0.00),
('U5655', 'test@test.com', '25d55ad283aa400af464c76d713c07ad', 'Tester', 'test@test.com', '0123456789', NULL, '2663', NULL, 0, 0, 1, '2026-04-19 20:27:01', 0, 0.00, NULL, NULL, NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
CREATE TABLE `user_addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` char(5) NOT NULL,
  `recipient_name` varchar(100) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `street` varchar(255) NOT NULL DEFAULT '',
  `ward` varchar(100) NOT NULL DEFAULT '',
  `district` varchar(100) NOT NULL DEFAULT '',
  `province` varchar(100) NOT NULL DEFAULT '',
  `note` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`address_id`, `user_id`, `recipient_name`, `phone`, `street`, `ward`, `district`, `province`, `note`, `is_default`, `created_at`) VALUES
(1, 'U01', 'Quản Trị Viên', '334275834', 'Kho tổng HCM', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(2, 'U02', 'Nguyễn Văn A', '375788987', '123 Lê Lợi, Q1, HCM', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(3, 'U03', 'Trần Thị B', '964326512', '45 Cầu Giấy, Hà Nội', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(4, 'U04', 'Lê Thị C', '901239876', '10 Nguyễn Trãi, Q5', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(5, 'U05', 'Hoàng Long', '987654321', '15 Lê Duẩn, Đà Nẵng', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(6, 'U06', 'Nguyễn Thanh Thúy', '912345678', '88 Nguyễn Huệ, Q1, HCM', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(7, 'U07', 'Phạm Minh Quân', '905112233', '12 Trần Phú, Hải Phòng', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(8, 'U08', 'Võ Kiều Oanh', '934556677', '200 Phan Chu Trinh, Huế', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(9, 'U09', 'Đỗ Đức Anh', '977889900', '45 Láng Hạ, Đống Đa, Hà Nội', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(10, 'U10', 'Bùi Thủy Tiên', '966554433', '77 Cách Mạng Tháng 8, Cần Thơ', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(11, 'U11', 'Ngô Xuân Bách', '944332211', '102 Quang Trung, Gò Vấp, HCM', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(12, 'U12', 'Nguyễn Thu Hà', '922110099', '56 Kim Mã, Ba Đình, Hà Nội', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(13, 'U13', 'Trần Gia Huy', '909123456', '32 Hùng Vương, Nha Trang', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(14, 'U14', 'Đặng Mỹ Linh', '988776655', '120 Võ Văn Kiệt, Q5, HCM', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(15, 'U15', 'Phan Quốc Bảo', '911223344', '15 Hòa Bình, Biên Hòa', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(16, 'U16', 'Lý Cẩm Tú', '933445566', '09 Lê Lợi, TP Vinh', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(17, 'U17', 'Vũ Nhật Minh', '955667788', '22 Điện Biên Phủ, Đà Nẵng', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(18, 'U18', 'Chu Phương Thảo', '977112233', '412 Trường Chinh, Tân Bình, HCM', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(19, 'U19', 'Lê Huỳnh Anh', '900223344', '89 Nguyễn Trãi, Thanh Xuân, HN', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(20, 'U20', 'Lâm Khải Minh', '335378609', 'Trần Đại Nghĩa, Dĩ An, Bình Dương', '', '', '', NULL, 1, '2026-04-19 20:51:06'),
(32, 'U3237', 'Tram Nguyen', '0373546431', 'Phường Dĩ An Thành phố Hồ Chí Minh', 'Linh Đông', 'Thủ Đức', 'Hồ Chí Minh', NULL, 0, '2026-04-19 20:57:19');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
CREATE TABLE `wallet_transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` char(5) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `related_order_id` char(5) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`transaction_id`, `user_id`, `amount`, `transaction_type`, `description`, `related_order_id`, `created_at`) VALUES
(1, 'U01', 150000.00, 1, 'Hoàn tiền đơn DH002', 'ORD02', '2026-02-26 14:30:00'),
(2, 'U01', 50000.00, 2, 'Sử dụng ví thanh toán đơn DH005', 'ORD05', '2026-03-01 09:15:00'),
(3, 'U01', 50000.00, 1, 'Thưởng hạng thành viên Vàng', NULL, '2026-03-10 20:00:00'),
(4, 'U02', 50000.00, 1, 'Hoàn tiền do lỗi vận chuyển', 'ORD01', '2026-03-15 10:20:00'),
(5, 'U03', 100000.00, 1, 'Tặng tiền đăng ký tài khoản mới', NULL, '2026-01-05 08:00:00'),
(6, 'U03', 100000.00, 2, 'Sử dụng ví thanh toán đơn ORD02', 'ORD02', '2026-01-15 09:00:00'),
(7, 'U04', 250000.00, 1, 'Hoàn tiền đơn hàng khách trả lại', NULL, '2026-03-10 14:00:00'),
(8, 'U05', 1000000.00, 1, 'Thưởng khách hàng mua sỉ tháng 3', NULL, '2026-04-01 08:00:00'),
(9, 'U06', 50000.00, 1, 'Hoàn tiền đánh giá sản phẩm', NULL, '2026-02-10 11:00:00'),
(10, 'U06', 50000.00, 2, 'Thanh toán một phần đơn ORD04', 'ORD04', '2026-02-15 15:30:00'),
(11, 'U07', 120000.00, 1, 'Hoàn tiền chênh lệch phí ship', NULL, '2026-03-20 16:45:00'),
(12, 'U08', 30000.00, 1, 'Quà tặng sinh nhật tháng 2', NULL, '2026-02-05 07:00:00'),
(13, 'U08', 30000.00, 2, 'Thanh toán phí ship đơn ORD05', 'ORD05', '2026-02-10 09:30:00'),
(14, 'U09', 30000.00, 1, 'Hoàn tiền đánh giá 5 sao có tâm', NULL, '2026-03-01 19:20:00'),
(15, 'U10', 500000.00, 1, 'Hoàn tiền bồi thường sản phẩm lỗi', NULL, '2026-03-25 10:15:00'),
(16, 'U11', 20000.00, 1, 'Thưởng tham gia Minigame Facebook', NULL, '2026-02-10 21:00:00'),
(17, 'U11', 20000.00, 2, 'Sử dụng ví thanh toán đơn ORD07', 'ORD07', '2026-02-14 10:00:00'),
(18, 'U12', 85000.00, 1, 'Hoàn tiền do khách hủy đơn hàng', NULL, '2026-04-05 13:40:00'),
(19, 'U13', 100000.00, 1, 'Quà tặng khách hàng mới', NULL, '2026-01-20 09:00:00'),
(20, 'U13', 100000.00, 2, 'Sử dụng ví thanh toán đơn ORD08', 'ORD08', '2026-02-18 14:20:00'),
(21, 'U14', 200000.00, 1, 'Hoàn tiền chương trình Flash Sale', NULL, '2026-03-30 22:00:00'),
(22, 'U15', 50000.00, 1, 'Hoàn tiền phí vận chuyển', NULL, '2026-02-15 16:10:00'),
(23, 'U15', 50000.00, 2, 'Sử dụng ví thanh toán đơn ORD09', 'ORD09', '2026-02-20 11:45:00'),
(24, 'U16', 450000.00, 1, 'Hoàn tiền đổi trả do nhầm size', NULL, '2026-04-02 08:30:00'),
(25, 'U17', 15000.00, 1, 'Hoàn tiền đánh giá có kèm hình ảnh', NULL, '2026-03-12 20:15:00'),
(26, 'U18', 40000.00, 1, 'Quy đổi voucher thành tiền mặt', NULL, '2026-02-20 09:00:00'),
(27, 'U18', 40000.00, 2, 'Sử dụng ví thanh toán đơn ORD10', 'ORD10', '2026-02-25 15:00:00'),
(28, 'U19', 75000.00, 1, 'Hoàn tiền xin lỗi do giao hàng trễ', NULL, '2026-03-28 17:30:00'),
(29, 'U20', 25000.00, 1, 'Thưởng hoa hồng giới thiệu bạn bè', NULL, '2026-04-01 10:00:00'),
(30, 'U20', 25000.00, 2, 'Rút tiền về thẻ ngân hàng', NULL, '2026-04-05 18:00:00'),
(31, 'U3237', 205000.00, 1, 'Hoàn tiền do hủy đơn hàng', 'O0016', '2026-04-20 22:17:59'),
(32, 'U3237', 205000.00, 1, 'Hoàn tiền do trả hàng (Refund)', 'O0015', '2026-04-20 22:25:56'),
(33, 'U3237', 205000.00, 1, 'Hoàn tiền do trả hàng (Refund)', 'O0011', '2026-04-20 22:31:40'),
(34, 'U3237', 205000.00, 1, 'Hoàn tiền do trả hàng (Refund)', 'O0013', '2026-04-20 23:16:00'),
(35, 'U3237', 290000.00, 2, 'Sử dụng ví thanh toán đơn hàng O0022', 'O0022', '2026-04-22 08:36:00'),
(36, 'U3237', 290000.00, 1, 'Hoàn tiền do hủy đơn hàng', 'O0022', '2026-04-22 08:36:42');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE `wishlist` (
  `wishlist_id` char(5) NOT NULL,
  `user_id` char(5) DEFAULT NULL,
  `product_id` char(5) DEFAULT NULL,
  `added_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `user_id`, `product_id`, `added_date`) VALUES
('W01', 'U01', 'T01', '2025-01-01 00:00:00'),
('W02', 'U02', 'T02', '2024-06-30 00:00:00'),
('W03', 'U03', 'T03', '2024-06-07 00:00:00'),
('W04', 'U04', 'T04', '2025-02-01 00:00:00'),
('W05', 'U05', 'T05', '2024-11-11 00:00:00'),
('W06', 'U06', 'T06', '2025-12-06 00:00:00'),
('W144', 'U3237', 'C02', '2026-04-19 19:49:41'),
('W478', 'U3237', 'T09', '2026-04-19 19:49:50'),
('W684', 'U3237', 'T08', '2026-04-19 19:50:51'),
('W897', 'U3237', 'C03', '2026-04-19 19:49:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `fk_cart_user` (`user_id`),
  ADD KEY `fk_cart_variant` (`variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_order_user` (`user_id`),
  ADD KEY `fk_order_coupon` (`coupon_id`),
  ADD KEY `fk_order_ship` (`shipping_method_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `fk_detail_order` (`order_id`),
  ADD KEY `fk_detail_variant` (`variant_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_prod_cat` (`category_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`variant_id`),
  ADD KEY `fk_variant_prod` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `fk_rev_user` (`user_id`),
  ADD KEY `fk_rev_prod` (`product_id`);

--
-- Indexes for table `shipping_methods`
--
ALTER TABLE `shipping_methods`
  ADD PRIMARY KEY (`shipping_method_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_wt_user` (`user_id`),
  ADD KEY `fk_wt_order` (`related_order_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD KEY `fk_wish_user` (`user_id`),
  ADD KEY `fk_wish_prod` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_cart_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`coupon_id`),
  ADD CONSTRAINT `fk_order_ship` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`shipping_method_id`),
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `fk_detail_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `fk_detail_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`);
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
SET FOREIGN_KEY_CHECKS=1;