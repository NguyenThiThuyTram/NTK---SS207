-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ntk
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_read_logs`
--

DROP TABLE IF EXISTS `admin_read_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_read_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `event_id` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_event` (`user_id`,`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_read_logs`
--

LOCK TABLES `admin_read_logs` WRITE;
/*!40000 ALTER TABLE `admin_read_logs` DISABLE KEYS */;
INSERT INTO `admin_read_logs` VALUES (1,0,'return_O0020','2026-05-27 14:01:19'),(2,0,'new_review_12','2026-05-27 14:01:19'),(3,0,'new_review_11','2026-05-27 14:01:19'),(4,0,'completed_order_O0035','2026-05-27 14:01:19'),(5,0,'completed_order_O0031','2026-05-27 14:01:19');
/*!40000 ALTER TABLE `admin_read_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `cart_id` char(5) NOT NULL,
  `user_id` char(5) DEFAULT NULL,
  `variant_id` char(5) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `is_selected` int(11) DEFAULT 1,
  PRIMARY KEY (`cart_id`),
  KEY `fk_cart_user` (`user_id`),
  KEY `fk_cart_variant` (`variant_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_cart_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES ('C0001','U04','V001',2,NULL,0),('C0002','U04','V051',1,NULL,0),('C0003','U07','V072',1,NULL,0),('C0004','U11','V023',1,NULL,1),('C0005',NULL,'V015',3,'sess_998877abc',0),('C0006',NULL,'V041',1,'sess_998877abc',0),('C0007','U14','V011',1,NULL,0),('C0008','U17','V003',2,NULL,1),('C5530','U01','V163',1,NULL,1),('C5531','U01','V036',1,NULL,1),('C5533','U01','V167',1,NULL,1);
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `category_id` char(5) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_show_home` int(11) DEFAULT 1,
  `priority` int(11) DEFAULT 0,
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES ('CAT01','├üo thun','ao-thun','https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7ne96vcjmiu46.webp',1,1,'├üo thun basic dß╗à mß║╖c, ph├╣ hß╗úp mß╗ìi phong c├ích'),('CAT02','├üo kho├íc','ao-khoac','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg7d8s7jrvnvb7.webp',0,2,'├üo kho├íc thß╗¥i trang, giß╗» ß║Ñm v├á chß╗æng nß║»ng'),('CAT03','Hoodie&Sweater','hoodie-sweater','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m1djz3jqsva0d1.webp',1,3,'Hoodie v├á sweater trß║╗ trung, n─âng ─æß╗Öng'),('CAT04','Quß║ºn','quan','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mc8wdg5whi6qb8.webp',0,4,'Quß║ºn thß╗¥i trang, s├ánh ─æiß╗çu'),('CAT05','├üo s╞í mi','ao-so-mi','https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-llqdtiaj374v5c.webp',0,5,'├üo s╞í mi lß╗ïch sß╗▒, ph├╣ hß╗úp ─æi l├ám v├á ─æi ch╞íi'),('CAT06','Quß║ºn ─æ├╣i','quan-dui','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcg2d2ixd4fw7b@resize_w900_nl.webp',0,6,'Quß║ºn ─æ├╣i thoß║úi m├íi cho hoß║ít ─æß╗Öng h├áng ng├áy'),('CAT07','├üo polo','ao-polo','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcrbsixysl9pbc@resize_w900_nl.webp',0,7,'├üo polo thanh lß╗ïch, dß╗à phß╗æi ─æß╗ô'),('CAT08','Quß║ºn jeans','quan-jeans','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfcb1buk8sumb0@resize_w900_nl.webp',1,8,'Quß║ºn jeans bß╗ün ─æß║╣p, phong c├ích c├í t├¡nh'),('CAT09','Ch├ón v├íy','chan-vay','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mczvn82i30rx20@resize_w900_nl.webp',0,9,'Ch├ón v├íy nß╗» t├¡nh, ─æa dß║íng kiß╗âu d├íng'),('CAT10','├üo len & cardigan','ao-len-cardigan','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m4hdzd36m4q8c9@resize_w900_nl.webp',0,10,'├üo len v├á cardigan giß╗» ß║Ñm, thß╗¥i trang');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` char(5) NOT NULL,
  `receiver_id` char(5) DEFAULT '0',
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES (1,'U4937','0','ch├áo',1,'2026-05-27 03:36:31'),(2,'U01','U4937','kkk',1,'2026-05-27 03:36:49'),(3,'U4937','0','hhh',1,'2026-05-27 03:36:56'),(4,'U4937','0','kkk',1,'2026-05-27 03:37:18'),(5,'U01','U4937','kkk',1,'2026-05-27 03:37:22'),(6,'U4937','0','nnn',1,'2026-05-27 03:44:27'),(7,'U01','U4937','kkk',1,'2026-05-27 03:44:39'),(8,'U4937','0','ch├áo',1,'2026-05-27 03:50:29'),(9,'U4937','0','ch├áo',1,'2026-05-27 06:30:01'),(10,'U4937','0','kkk',1,'2026-05-27 06:30:42'),(11,'U4937','0','kkk',1,'2026-05-27 06:46:28'),(12,'U01','U4937','xin ch├áo',1,'2026-05-27 06:51:05'),(13,'U01','0','xin ch├áo',1,'2026-05-27 06:51:15'),(14,'U4937','0','xin ch├áo',1,'2026-05-27 06:51:55'),(15,'U01','U4937','ch├áo',1,'2026-05-27 06:52:25'),(16,'U4937','0','hello',1,'2026-05-27 06:54:06'),(17,'U01','U4937','ch├áo',1,'2026-05-27 06:54:13');
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `status` int(11) DEFAULT 1,
  `coupon_type` int(11) DEFAULT 0,
  PRIMARY KEY (`coupon_id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES ('CP01','WELCOME',0,10.00,250000.00,'2026-01-01 00:00:00','2027-01-01 00:00:00',30000.00,1000,27,1,0),('CP02','FREESHIP',1,20000.00,200000.00,'2024-01-01 00:00:00','2027-06-30 00:00:00',NULL,500,36,1,1),('CP03','SALE',0,10.00,500000.00,'2024-06-01 00:00:00','2027-06-07 00:00:00',50000.00,100,56,0,0),('CP033','NTKFASHION',1,20000.00,200000.00,NULL,'2026-12-30 00:00:00',NULL,50,0,1,0),('CP04','TET',1,50000.00,1000000.00,'2025-01-01 00:00:00','2027-02-01 00:00:00',NULL,50,50,0,0),('CP05','NTK',0,10.00,2000000.00,'2024-11-11 00:00:00','2024-12-11 00:00:00',200000.00,20,20,1,0),('CP111','NTKXINCHAO',0,10.00,299000.00,NULL,'2026-06-30 00:00:00',NULL,50,5,1,0),('CP185','XINCAMON',0,10.00,200000.00,NULL,'2026-04-01 00:00:00',NULL,100,0,1,0),('CP778','MIß╗àN PH├¡ Vß║¡N CHUYß╗âN',1,30000.00,300000.00,NULL,'2026-07-11 00:00:00',NULL,100,1,1,1);
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flash_sales`
--

DROP TABLE IF EXISTS `flash_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `flash_sales` (
  `flash_sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `variant_id` char(5) NOT NULL,
  `sale_date` date NOT NULL,
  `flash_sale_price` decimal(15,2) NOT NULL,
  `status` int(11) DEFAULT 1,
  PRIMARY KEY (`flash_sale_id`),
  KEY `fk_flash_sale_variant` (`variant_id`),
  CONSTRAINT `fk_flash_sale_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flash_sales`
--

LOCK TABLES `flash_sales` WRITE;
/*!40000 ALTER TABLE `flash_sales` DISABLE KEYS */;
INSERT INTO `flash_sales` VALUES (1,'V036','2026-05-27',240000.00,1),(2,'V037','2026-05-27',249000.00,1),(3,'V042','2026-05-27',359000.00,1),(4,'V043','2026-05-27',189000.00,1),(5,'V044','2026-05-27',259000.00,1),(6,'V108','2026-05-27',379000.00,1),(7,'V109','2026-05-27',259000.00,1),(8,'V110','2026-05-27',169000.00,1);
/*!40000 ALTER TABLE `flash_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `noti_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` char(5) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'system',
  `title` varchar(200) NOT NULL,
  `message` varchar(500) NOT NULL,
  `related_order_id` char(5) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`noti_id`),
  KEY `idx_noti_user` (`user_id`),
  KEY `idx_noti_order` (`related_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'U04','cart_reminder','Bß║ín c├│ sß║ún phß║⌐m ─æang chß╗¥! ≡ƒ¢Æ','Sß║ún phß║⌐m \"├üo thun babytee thß╗â thao\" vß║½n ─æang ─æß╗úi bß║ín trong giß╗Å h├áng. H├úy ho├án tß║Ñt ─æß║╖t h├áng ngay nh├⌐!',NULL,0,'2026-05-23 15:14:46'),(2,'U04','cart_reminder','Bß║ín c├│ sß║ún phß║⌐m ─æang chß╗¥! ≡ƒ¢Æ','Sß║ún phß║⌐m \"├üo kho├íc Bomber\" vß║½n ─æang ─æß╗úi bß║ín trong giß╗Å h├áng. H├úy ho├án tß║Ñt ─æß║╖t h├áng ngay nh├⌐!',NULL,0,'2026-05-23 15:14:46'),(3,'U07','cart_reminder','Bß║ín c├│ sß║ún phß║⌐m ─æang chß╗¥! ≡ƒ¢Æ','Sß║ún phß║⌐m \"├üo Hoodie Zip phß╗æi Caro\" vß║½n ─æang ─æß╗úi bß║ín trong giß╗Å h├áng. H├úy ho├án tß║Ñt ─æß║╖t h├áng ngay nh├⌐!',NULL,0,'2026-05-23 15:14:46'),(4,'U11','cart_reminder','Bß║ín c├│ sß║ún phß║⌐m ─æang chß╗¥! ≡ƒ¢Æ','Sß║ún phß║⌐m \"├üo thun form rß╗Öng\" vß║½n ─æang ─æß╗úi bß║ín trong giß╗Å h├áng. H├úy ho├án tß║Ñt ─æß║╖t h├áng ngay nh├⌐!',NULL,0,'2026-05-23 15:14:46'),(5,'U14','cart_reminder','Bß║ín c├│ sß║ún phß║⌐m ─æang chß╗¥! ≡ƒ¢Æ','Sß║ún phß║⌐m \"├üo thun babytee basic\" vß║½n ─æang ─æß╗úi bß║ín trong giß╗Å h├áng. H├úy ho├án tß║Ñt ─æß║╖t h├áng ngay nh├⌐!',NULL,0,'2026-05-23 15:14:46'),(6,'U17','cart_reminder','Bß║ín c├│ sß║ún phß║⌐m ─æang chß╗¥! ≡ƒ¢Æ','Sß║ún phß║⌐m \"├üo thun babytee thß╗â thao\" vß║½n ─æang ─æß╗úi bß║ín trong giß╗Å h├áng. H├úy ho├án tß║Ñt ─æß║╖t h├áng ngay nh├⌐!',NULL,0,'2026-05-23 15:14:46'),(7,'U4937','cart_reminder','Bß║ín c├│ sß║ún phß║⌐m ─æang chß╗¥! ≡ƒ¢Æ','Sß║ún phß║⌐m \"├üo Kho├íc Cardigan Len\" vß║½n ─æang ─æß╗úi bß║ín trong giß╗Å h├áng. H├úy ho├án tß║Ñt ─æß║╖t h├áng ngay nh├⌐!',NULL,1,'2026-05-23 15:14:46'),(8,'U01','new_order','─É╞ín h├áng mß╗¢i #O0030','C├│ ─æ╞ín h├áng mß╗¢i #O0030 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 1.301.500 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0030',0,'2026-05-23 20:16:08'),(9,'U01','new_order','─É╞ín h├áng mß╗¢i #O0031','C├│ ─æ╞ín h├áng mß╗¢i #O0031 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 205.000 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0031',0,'2026-05-23 21:25:52'),(10,'U4937','order_shipping','─É╞ín h├áng ─æang ─æ╞░ß╗úc giao','─É╞ín h├áng #O0031 ─æ├ú ─æ╞░ß╗úc b├án giao cho ─æ╞ín vß╗ï vß║¡n chuyß╗ân. Bß║ín sß║╜ nhß║¡n h├áng trong 1-3 ng├áy tß╗¢i.','O0031',1,'2026-05-23 23:36:31'),(11,'U4937','order_completed','─É╞ín h├áng ho├án th├ánh!','─É╞ín h├áng #O0031 ─æ├ú ho├án th├ánh. Cß║úm ╞ín bß║ín ─æ├ú mua sß║»m tß║íi NTK Fashion! H├úy ─æ├ính gi├í sß║ún phß║⌐m ─æß╗â nhß║¡n xu th╞░ß╗ƒng nh├⌐!','O0031',1,'2026-05-23 23:38:37'),(12,'U01','new_order','─É╞ín h├áng mß╗¢i #O0032','C├│ ─æ╞ín h├áng mß╗¢i #O0032 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 723.500 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0032',0,'2026-05-24 00:00:51'),(13,'U01','new_order','─É╞ín h├áng mß╗¢i #O0033','C├│ ─æ╞ín h├áng mß╗¢i #O0033 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 35.000 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0033',0,'2026-05-24 00:00:53'),(14,'U01','new_order','─É╞ín h├áng mß╗¢i #O0034','C├│ ─æ╞ín h├áng mß╗¢i #O0034 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 370.500 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0034',0,'2026-05-24 00:01:37'),(15,'U01','new_order','─É╞ín h├áng mß╗¢i #O0035','C├│ ─æ╞ín h├áng mß╗¢i #O0035 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 400.500 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0035',0,'2026-05-24 00:02:48'),(16,'U4937','order_shipping','─É╞ín h├áng ─æang ─æ╞░ß╗úc giao','─É╞ín h├áng #O0035 ─æ├ú ─æ╞░ß╗úc b├án giao cho ─æ╞ín vß╗ï vß║¡n chuyß╗ân. Bß║ín sß║╜ nhß║¡n h├áng trong 1-3 ng├áy tß╗¢i.','O0035',1,'2026-05-24 00:03:14'),(17,'U4937','order_completed','─É╞ín h├áng ho├án th├ánh!','─É╞ín h├áng #O0035 ─æ├ú ho├án th├ánh. Cß║úm ╞ín bß║ín ─æ├ú mua sß║»m tß║íi NTK Fashion! H├úy ─æ├ính gi├í sß║ún phß║⌐m ─æß╗â nhß║¡n xu th╞░ß╗ƒng nh├⌐!','O0035',1,'2026-05-24 00:03:23'),(18,'U01','new_order','─É╞ín h├áng mß╗¢i #O0036','C├│ ─æ╞ín h├áng mß╗¢i #O0036 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 409.850 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0036',0,'2026-05-24 09:08:13'),(19,'U01','new_order','─É╞ín h├áng mß╗¢i #O0037','C├│ ─æ╞ín h├áng mß╗¢i #O0037 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 380.500 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0037',0,'2026-05-24 09:17:13'),(20,'U4937','order_shipping','─É╞ín h├áng ─æang ─æ╞░ß╗úc giao','─É╞ín h├áng #O0036 ─æ├ú ─æ╞░ß╗úc b├án giao cho ─æ╞ín vß╗ï vß║¡n chuyß╗ân. Bß║ín sß║╜ nhß║¡n h├áng trong 1-3 ng├áy tß╗¢i.','O0036',1,'2026-05-24 09:17:29'),(21,'U3237','return_request','Y├¬u cß║ºu trß║ú h├áng ─æ├ú ─æ╞░ß╗úc gß╗¡i','Y├¬u cß║ºu trß║ú h├áng cho ─æ╞ín h├áng #O0020 ─æ├ú ─æ╞░ß╗úc gß╗¡i th├ánh c├┤ng. Admin sß║╜ xem x├⌐t v├á phß║ún hß╗ôi trong v├▓ng 24 giß╗¥.','O0020',1,'2026-05-24 13:57:15'),(22,'U01','new_order','─É╞ín h├áng mß╗¢i #O0038','C├│ ─æ╞ín h├áng mß╗¢i #O0038 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 362.000 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0038',0,'2026-05-24 13:59:43'),(23,'U3237','order_cancelled','─É╞ín h├áng ─æ├ú hß╗ºy','─É╞ín h├áng #O0008 ─æ├ú ─æ╞░ß╗úc hß╗ºy th├ánh c├┤ng. Sß╗æ tiß╗ün 205.000 VN─É sß║╜ ─æ╞░ß╗úc ho├án v├áo v├¡ cß╗ºa bß║ín trong 1-3 ng├áy l├ám viß╗çc.','O0008',1,'2026-05-24 14:05:09'),(24,'U01','new_order','─É╞ín h├áng mß╗¢i #O0039','C├│ ─æ╞ín h├áng mß╗¢i #O0039 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 290.000 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0039',0,'2026-05-24 15:53:07'),(25,'U4937','order_placed','─Éß║╖t h├áng th├ánh c├┤ng #O0040','─É╞ín h├áng #O0040 cß╗ºa bß║ín ─æ├ú ─æ╞░ß╗úc ghi nhß║¡n. Tß╗òng thanh to├ín l├á 685.250 VN─É.','O0040',1,'2026-05-26 22:55:00'),(26,'U01','new_order','─É╞ín h├áng mß╗¢i #O0040','C├│ ─æ╞ín h├áng mß╗¢i #O0040 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 685.250 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0040',0,'2026-05-26 22:55:00'),(27,'U4937','order_placed','─Éß║╖t h├áng th├ánh c├┤ng #O0041','─É╞ín h├áng #O0041 cß╗ºa bß║ín ─æ├ú ─æ╞░ß╗úc ghi nhß║¡n. Tß╗òng thanh to├ín l├á 251.500 VN─É.','O0041',0,'2026-05-26 23:11:49'),(28,'U01','new_order','─É╞ín h├áng mß╗¢i #O0041','C├│ ─æ╞ín h├áng mß╗¢i #O0041 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 251.500 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0041',0,'2026-05-26 23:11:49'),(29,'U4937','order_placed','─Éß║╖t h├áng th├ánh c├┤ng #O0042','─É╞ín h├áng #O0042 cß╗ºa bß║ín ─æ├ú ─æ╞░ß╗úc ghi nhß║¡n. Tß╗òng thanh to├ín l├á 478.700 VN─É.','O0042',0,'2026-05-27 09:00:20'),(30,'U01','new_order','─É╞ín h├áng mß╗¢i #O0042','C├│ ─æ╞ín h├áng mß╗¢i #O0042 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 478.700 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0042',0,'2026-05-27 09:00:20'),(31,'U4937','order_placed','─Éß║╖t h├áng th├ánh c├┤ng #O0043','─É╞ín h├áng #O0043 cß╗ºa bß║ín ─æ├ú ─æ╞░ß╗úc ghi nhß║¡n. Tß╗òng thanh to├ín l├á 283.100 VN─É.','O0043',0,'2026-05-27 09:36:15'),(32,'U01','new_order','─É╞ín h├áng mß╗¢i #O0043','C├│ ─æ╞ín h├áng mß╗¢i #O0043 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 283.100 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0043',0,'2026-05-27 09:36:15'),(33,'U4937','order_placed','─Éß║╖t h├áng th├ánh c├┤ng #O0044','─É╞ín h├áng #O0044 cß╗ºa bß║ín ─æ├ú ─æ╞░ß╗úc ghi nhß║¡n. Tß╗òng thanh to├ín l├á 311.000 VN─É.','O0044',1,'2026-05-27 14:00:27'),(34,'U01','new_order','─É╞ín h├áng mß╗¢i #O0044','C├│ ─æ╞ín h├áng mß╗¢i #O0044 tß╗½ kh├ích h├áng, tß╗òng tiß╗ün 311.000 VN─É. Vui l├▓ng xß╗¡ l├╜.','O0044',0,'2026-05-27 14:00:27'),(35,'U4937','system','Nhß║¡n ─æiß╗âm th╞░ß╗ƒng','Tuyß╗çt vß╗¥i! Bß║ín nhß║¡n ─æ╞░ß╗úc 40 ─æiß╗âm tß╗½ viß╗çc ho├án th├ánh ─æ╞ín h├áng #O0036.',NULL,0,'2026-05-27 14:01:59'),(36,'U4937','order_completed','─É╞ín h├áng ho├án th├ánh!','─É╞ín h├áng #O0036 ─æ├ú ho├án th├ánh. Cß║úm ╞ín bß║ín ─æ├ú mua sß║»m tß║íi NTK Fashion! H├úy ─æ├ính gi├í sß║ún phß║⌐m ─æß╗â nhß║¡n xu th╞░ß╗ƒng nh├⌐!','O0036',0,'2026-05-27 14:01:59'),(37,'U4937','system','Nhß║¡n ─æiß╗âm th╞░ß╗ƒng','Tuyß╗çt vß╗¥i! Bß║ín nhß║¡n ─æ╞░ß╗úc 200 ─æiß╗âm tß╗½ viß╗çc ─æ├ính gi├í sß║ún phß║⌐m.',NULL,0,'2026-05-27 14:02:37');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_details`
--

DROP TABLE IF EXISTS `order_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_details` (
  `detail_id` char(5) NOT NULL,
  `order_id` char(5) DEFAULT NULL,
  `variant_id` char(5) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `feedback` varchar(500) DEFAULT NULL,
  `is_reviewed` int(11) DEFAULT 0,
  `product_name` varchar(200) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`detail_id`),
  KEY `fk_detail_order` (`order_id`),
  KEY `fk_detail_variant` (`variant_id`),
  CONSTRAINT `fk_detail_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  CONSTRAINT `fk_detail_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_details`
--

LOCK TABLES `order_details` WRITE;
/*!40000 ALTER TABLE `order_details` DISABLE KEYS */;
INSERT INTO `order_details` VALUES ('D0006','O0002','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0008','O0004','V165',2,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0009','O0005','V140',1,153000.00,NULL,0,'Quß║ºn V├íy Ngß║»n D├íng Xo├¿',NULL),('D0010','O0006','V167',1,255000.00,NULL,0,'├üo Len Kß║╗ Sß╗ìc Thu ─É├┤ng',NULL),('D0011','O0007','V169',1,153000.00,NULL,0,'├üo Len Mß╗Ång Cß╗Öc Tay',NULL),('D0012','O0008','V168',1,170000.00,NULL,0,'├üo L├┤ng Thß╗Å D├ái Tay',NULL),('D0013','O0009','V167',1,255000.00,NULL,0,'├üo Len Kß║╗ Sß╗ìc Thu ─É├┤ng',NULL),('D0014','O0010','V165',2,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0015','O0011','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0017','O0013','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0019','O0015','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0020','O0016','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0021','O0017','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0023','O0020','V165',2,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0025','O0022','V167',1,255000.00,NULL,0,'├üo Len Kß║╗ Sß╗ìc Thu ─É├┤ng',NULL),('D0026','O0023','V165',2,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0027','O0024','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0028','O0024','V034',1,187000.00,NULL,0,'├üo babytee ─æß╗⌐ng form',NULL),('D0029','O0025','V025',3,340000.00,NULL,0,'├üo babytee chß║Ñm bi',NULL),('D0030','O0025','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0031','O0026','V165',2,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0032','O0027','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0033','O0027','V167',1,255000.00,NULL,0,'├üo Len Kß║╗ Sß╗ìc Thu ─É├┤ng',NULL),('D0034','O0028','V165',2,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0035','O0028','V033',1,374000.00,NULL,0,'├üo babytee ─æß╗⌐ng form',NULL),('D0036','O0029','V166',1,340000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0037','O0029','V168',1,170000.00,NULL,0,'├üo L├┤ng Thß╗Å D├ái Tay',NULL),('D0038','O0030','V163',3,365500.00,NULL,0,'├üo Kho├íc Cardigan Len',NULL),('D0039','O0030','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0040','O0031','V165',1,170000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0041','O0032','V164',1,348500.00,NULL,0,'├üo Kho├íc Cardigan Len',NULL),('D0042','O0032','V166',1,340000.00,NULL,0,'├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å',NULL),('D0043','O0034','V163',1,365500.00,NULL,0,'├üo Kho├íc Cardigan Len',NULL),('D0044','O0035','V163',1,365500.00,NULL,0,'├üo Kho├íc Cardigan Len',NULL),('D0045','O0036','V043',1,170000.00,NULL,0,'├üo kho├íc d├╣',NULL),('D0046','O0036','V044',1,246500.00,NULL,0,'├üo kho├íc d├╣',NULL),('D0047','O0037','V163',1,365500.00,NULL,0,'├üo Kho├íc Cardigan Len',NULL),('D0048','O0038','V029',1,357000.00,NULL,0,'├üo Babytee Lucky Horse',NULL),('D0049','O0039','V167',1,255000.00,NULL,0,'├üo Len Kß║╗ Sß╗ìc Thu ─É├┤ng',NULL),('D0050','O0040','V011',1,357000.00,NULL,0,'├üo thun babytee basic',NULL),('D0051','O0040','V111',1,365500.00,NULL,0,'├üo S╞í Mi Cß╗Öc Tay Form Rß╗Öng',NULL),('D0052','O0041','V036',1,246500.00,NULL,0,'├üo Baby Tee \"I Love Cat\"',NULL),('D0053','O0042','V036',2,246500.00,NULL,0,'├üo Baby Tee \"I Love Cat\"',NULL),('D0054','O0043','V185',1,309000.00,NULL,0,'├üo Polo Chiß║┐t Eo Tay Bß╗ông',NULL),('D0055','O0044','V084',1,340000.00,NULL,0,'├üo Hoodie Zip ORIGINALS',NULL),('DT001','ORD01','V001',1,159000.00,'├üo rß║Ñt ─æß║╣p, chß║Ñt vß║úi co gi├ún tß╗æt!',0,NULL,NULL),('DT002','ORD01','V051',1,289000.00,'Vß║úi d├áy dß║╖n, ß║Ñm ├íp.',0,NULL,NULL),('DT003','ORD02','V072',1,189000.00,'Mß║╖c rß║Ñt t├┤n d├íng.',0,NULL,NULL),('DT004','ORD02','V005',1,149000.00,'Giao h├áng nhanh.',0,NULL,NULL),('DT005','ORD03','V037',2,349000.00,'Mß╗ìi ng╞░ß╗¥i n├¬n mua nh├⌐!',0,NULL,NULL),('DT006','ORD03','V003',1,159000.00,'Tuyß╗çt vß╗¥i, phß║úi ß╗ºng hß╗Ö th╞░╞íng xuy├¬n.',1,NULL,NULL),('DT007','ORD04','V104',1,189000.00,'H├áng ─æß║╣p m├á gi├í lß║íi phß║úi ch─âng.',0,NULL,NULL),('DT008','ORD05','V045',2,399000.00,'Nh├ón vi├¬n t╞░ vß║Ñn nhiß╗çt t├¼nh, giao h├áng nhanh, m├¼nh',0,NULL,NULL),('DT009','ORD05','V037',1,349000.00,'Shop kh├┤ng bao giß╗¥ l├ám m├¼nh thß║Ñt vß╗ìng.',0,NULL,NULL),('DT010','ORD06','V142',2,219000.00,'─É├│ng g├│i chuy├¬n nghiß╗çp, chß║Ñt vß║úi xß╗ïn x├▓.',0,NULL,NULL),('DT011','ORD07','V088',1,189000.00,'Vß║úi bß║┐n ─æß║╣p, ─æ├íng tiß║┐n.',0,NULL,NULL),('DT012','ORD08','V051',2,289000.00,'Sß║╜ mua lß║íi, rß║Ñt ─æ├íng tiß╗ün.',0,NULL,NULL);
/*!40000 ALTER TABLE `order_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_returns`
--

DROP TABLE IF EXISTS `order_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_returns` (
  `return_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` char(5) NOT NULL,
  `detail_id` char(5) DEFAULT NULL,
  `reason` varchar(500) NOT NULL,
  `image_proof` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT 0 COMMENT '0:Pending, 1:Approved, 2:Rejected',
  `created_at` datetime DEFAULT current_timestamp(),
  `admin_note` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`return_id`),
  KEY `order_id` (`order_id`),
  KEY `detail_id` (`detail_id`),
  CONSTRAINT `order_returns_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  CONSTRAINT `order_returns_ibfk_2` FOREIGN KEY (`detail_id`) REFERENCES `order_details` (`detail_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_returns`
--

LOCK TABLES `order_returns` WRITE;
/*!40000 ALTER TABLE `order_returns` DISABLE KEYS */;
INSERT INTO `order_returns` VALUES (3,'O0020',NULL,'H├áng bß╗ï lß╗ùi / h╞░ hß╗Ång','assets/uploads/returns/return_O0020_1779605835.png',0,'2026-05-24 13:57:15',NULL);
/*!40000 ALTER TABLE `order_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `payos_checkout_url` varchar(1000) DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `cancel_requested_at` datetime DEFAULT NULL,
  `return_reason` varchar(500) DEFAULT NULL,
  `return_image` varchar(500) DEFAULT NULL,
  `return_requested_at` datetime DEFAULT NULL,
  `delivery_failed_at` datetime DEFAULT NULL,
  `admin_note` varchar(500) DEFAULT NULL,
  `freeship_coupon_id` char(5) DEFAULT NULL,
  `freeship_discount_value` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`order_id`),
  KEY `fk_order_user` (`user_id`),
  KEY `fk_order_coupon` (`coupon_id`),
  KEY `fk_order_ship` (`shipping_method_id`),
  CONSTRAINT `fk_order_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`coupon_id`),
  CONSTRAINT `fk_order_ship` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`shipping_method_id`),
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES ('O0002',2604207261,NULL,'U3237','2026-04-20 20:13:59','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',205000.00,35000.00,NULL,NULL,0,205000.00,0,2,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0004',2604202477,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454063750005802VN62250821CSPZKHEL6A2 NTK O000463044729','U3237','2026-04-20 20:50:31','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',375000.00,35000.00,NULL,NULL,0,375000.00,0,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/a3c93636f3844eb899f5ad1cee488633',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0005',2604207573,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454061880005802VN62250821CS3GXCGU662 NTK O00056304C3F0','U3237','2026-04-20 20:53:55','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',188000.00,35000.00,NULL,NULL,0,188000.00,0,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/939a438485c54bc29f5ab23b52676dad',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0006',2604209391,NULL,'U3237','2026-04-20 20:55:07','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',290000.00,35000.00,NULL,NULL,1,290000.00,1,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0007',2604201143,NULL,'U3237','2026-04-20 20:56:55','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',188000.00,35000.00,NULL,NULL,1,188000.00,1,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0008',2604209254,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454062050005802VN62250821CSKH5XTLOH8 NTK O000863042EFB','U3237','2026-04-20 21:00:28','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',205000.00,35000.00,NULL,NULL,4,205000.00,1,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/1d35d523e4974b91ad50f5d49fa3d328','Kh├ích h├áng tß╗▒ hß╗ºy',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0009',2604201275,NULL,'U3237','2026-04-20 21:21:19','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',290000.00,35000.00,NULL,NULL,1,290000.00,1,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0010',2604207687,NULL,'U3237','2026-04-20 21:47:35','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',375000.00,35000.00,NULL,NULL,1,375000.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0011',2604205924,NULL,'U3237','2026-04-20 21:47:53','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',205000.00,35000.00,NULL,NULL,4,205000.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0013',2604201477,NULL,'U3237','2026-04-20 21:49:21','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',205000.00,35000.00,NULL,NULL,4,205000.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0015',2604204885,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454062050005802VN62250821CS8TZRFQK86 NTK O00156304F2EA','U3237','2026-04-20 21:50:09','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',205000.00,35000.00,NULL,NULL,4,205000.00,1,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/6a3d5ff0f80e48f58b4aea3293b9d897',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0016',2604204002,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454062050005802VN62250821CSUO0FD6OD1 NTK O00166304DFA1','U3237','2026-04-20 21:56:42','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',205000.00,35000.00,NULL,NULL,4,205000.00,1,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/70757ae94f3b4662aaff59c5886414ed',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0017',2604206761,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454062050005802VN62250821CS4XA6E4IC7 NTK O00176304A22A','U3237','2026-04-20 23:16:22','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',205000.00,35000.00,NULL,NULL,1,205000.00,1,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/f6e1212dfce64199b0a3857c29e6ca6d',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0019',2604204756,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA53037045405350005802VN62250821CSOXH6HVWS2 NTK O001963042306','U3237','2026-04-20 23:18:38','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',35000.00,35000.00,NULL,NULL,4,35000.00,1,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/8a09a86005bf40f7a77d0dd1119d0be3',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0020',2604212219,NULL,'U3237','2026-04-21 12:25:30','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',375000.00,35000.00,NULL,NULL,5,375000.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,'H├áng bß╗ï lß╗ùi / h╞░ hß╗Ång','assets/uploads/returns/return_O0020_1779605835.png','2026-05-24 13:57:15',NULL,NULL,NULL,0.00),('O0022',2604225406,NULL,'U3237','2026-04-22 08:36:00','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',290000.00,35000.00,NULL,NULL,4,0.00,1,2,NULL,NULL,290000.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0023',2604225255,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454063750005802VN62250821CSXVN4FNFO0 NTK O00236304D767','U3237','2026-04-22 20:29:46','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',375000.00,35000.00,NULL,NULL,3,375000.00,0,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/e4ea152ddcb04eb3aee7702c7921ebeb',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0024',2604282610,NULL,'U3237','2026-04-28 16:05:13','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',392000.00,35000.00,NULL,NULL,1,392000.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0025',2604287810,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA5303704540712250005802VN62250821CSJ0L4NY9L5 NTK O002563041D5E','U5872','2026-04-28 22:00:19','lau','0329848845','x├│m v╞░ß╗¥n ╞░╞ím, Ia Yok, Ia Grai, Gia Lai',1225000.00,35000.00,NULL,NULL,0,1225000.00,0,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/32362adfd3b44854b059af3d8da3990e',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0026',2604286571,NULL,'U5872','2026-04-28 22:05:00','lau','0329848845','Gia Lai',375000.00,35000.00,NULL,NULL,1,375000.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0027',2604281774,NULL,'U5872','2026-04-28 22:42:25','lau','0329848845','Gia Lai',460000.00,35000.00,NULL,NULL,1,460000.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0028',2604295758,NULL,'U5872','2026-04-29 15:10:48','lau','0329848845','Gia Lai',749000.00,35000.00,NULL,0.00,1,749000.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0029',2605217661,NULL,'U4937','2026-05-21 08:23:35','nghi','0938211589','ktx khu b d─⌐ an b├¼nh d╞░╞íng, Linh Xu├ón, Thß╗º ─Éß╗⌐c, Hß╗ô Ch├¡ Minh',545000.00,35000.00,NULL,0.00,1,545000.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0030',2605239442,NULL,'U4937','2026-05-23 20:16:08','nghi','0938211589','ktx khu b d─⌐ an b├¼nh d╞░╞íng, Linh Xu├ón, Thß╗º ─Éß╗⌐c, Hß╗ô Ch├¡ Minh',1301500.00,35000.00,NULL,0.00,1,1301500.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0031',2605236801,NULL,'U4937','2026-05-23 21:25:52','nghi','0938211589','ktx khu b d─⌐ an b├¼nh d╞░╞íng, D─⌐ An, D─⌐ An, B├¼nh D╞░╞íng',205000.00,35000.00,NULL,0.00,3,205000.00,1,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0032',2605236431,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454067235005802VN62300826CSOKEA06K64 Don hang O00326304EEC8','U4937','2026-05-24 00:00:51','nghi','0938211589','An Thß╗¢i, B├¼nh Thuß╗╖, Cß║ºn Th╞í',723500.00,35000.00,NULL,0.00,0,723500.00,0,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/eac9a5a16f184e2fbb394e614a035f0e',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0033',2605232353,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA53037045405350005802VN62300826CSHXEUC9EI9 Don hang O003363043950','U4937','2026-05-24 00:00:53','nghi','0938211589','An Thß╗¢i, B├¼nh Thuß╗╖, Cß║ºn Th╞í',35000.00,35000.00,NULL,0.00,0,35000.00,0,2,NULL,NULL,0.00,'','https://pay.payos.vn/web/96538c1b5c0a48d5a2c73e64ce00e2f7',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0034',2605236533,'00020101021238590010A000000727012900069704180115V3CAS62627239240208QRIBFTTA530370454063705005802VN62300826CS1CPQWPZ80 Don hang O00346304C941','U4937','2026-05-24 00:01:37','nghi','0938211589','Tr├íng Viß╗çt, M├¬ Linh, H├á Nß╗Öi',400500.00,35000.00,NULL,30000.00,0,370500.00,0,2,'CP01',NULL,0.00,'','https://pay.payos.vn/web/7737ebb0d3424182a10902f987e8ff6c',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0035',2605237303,NULL,'U4937','2026-05-24 00:02:48','nghi','0938211589','Quß╗æc Toß║ún, Quß║úng H├▓a, Cao Bß║▒ng',400500.00,35000.00,NULL,0.00,3,400500.00,1,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0036',2605242944,NULL,'U4937','2026-05-24 09:08:13','nghi','0938211589','Ph╞░╞íng ─Éß╗Ö, H├á Giang, H├á Giang',451500.00,35000.00,NULL,41650.00,3,409850.00,1,1,'CP111',NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0037',2605241770,NULL,'U4937','2026-05-24 09:17:13','Nghi','0938211589','Y├¬n Mß╗╣, Lß║íng Giang, Bß║»c Giang',400500.00,35000.00,NULL,20000.00,1,380500.00,0,1,'CP02',NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0038',2605241626,NULL,'U3237','2026-05-24 13:59:43','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',392000.00,35000.00,NULL,30000.00,1,362000.00,0,1,'CP01',NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0039',2605244175,NULL,'U3237','2026-05-24 15:53:07','Tram Nguyen','0373546431','Hß╗ô Ch├¡ Minh',290000.00,35000.00,NULL,0.00,1,290000.00,0,1,NULL,NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0040',2605265184,NULL,'U4937','2026-05-26 22:55:00','nghi','0938211589','ktx khu b d─⌐ an b├¼nh d╞░╞íng, ─É├┤ng H├▓a, D─⌐ An, B├¼nh D╞░╞íng',757500.00,35000.00,NULL,72250.00,1,685250.00,0,1,'CP111',NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0041',2605268217,NULL,'U4937','2026-05-26 23:11:49','nghi','0938211589','B├¼nh D╞░╞íng',271500.00,25000.00,'S03',20000.00,1,251500.00,0,1,'CP02',NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0042',2605273222,NULL,'U4937','2026-05-27 09:00:20','nghi','0938211589','B├¼nh D╞░╞íng',528000.00,35000.00,NULL,49300.00,1,478700.00,0,1,'CP111',NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('O0043',2605271462,NULL,'U4937','2026-05-27 09:36:15','nghi','0938211589','B├¼nh D╞░╞íng',334000.00,25000.00,'S03',30900.00,1,283100.00,0,1,'CP111',NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CP02',20000.00),('O0044',2605276820,NULL,'U4937','2026-05-27 14:00:27','nghi','0938211589','B├¼nh D╞░╞íng',375000.00,35000.00,'S01',34000.00,1,311000.00,0,1,'CP111',NULL,0.00,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CP778',30000.00),('ORD01',NULL,NULL,'U02','2025-01-10 00:00:00','Nguyß╗àn V─ân A','0375788987','123 L├¬ Lß╗úi, Q1, HCM',450000.00,30000.00,'S01',30000.00,0,450000.00,0,0,'CP01','ORD01-U02-TN',0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('ORD02',NULL,NULL,'U03','2025-01-15 00:00:00','Trß║ºn Thß╗ï B','0964326512','45 Cß║ºu Giß║Ñy, H├á Nß╗Öi',300000.00,30000.00,'S02',30000.00,1,300000.00,0,0,'CP02','ORD02-U03-TN',100000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('ORD03',NULL,NULL,'U05','2025-02-01 00:00:00','Ho├áng Long','0987654321','15 L├¬ Duß║⌐n, ─É├á Nß║╡ng',800000.00,30000.00,'S03',30000.00,2,800000.00,0,1,'CP03','ORD03-U05-TN',0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('ORD04',NULL,NULL,'U06','2025-02-05 00:00:00','Nguyß╗àn Thanh Thß╗ºy','0912345678','88 Nguyß╗àn Huß╗ç, Q1, HCM',250000.00,30000.00,'S04',30000.00,3,250000.00,1,2,NULL,'ORD04-U06-TN',50000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('ORD05',NULL,NULL,'U08','2025-02-10 00:00:00','V├╡ Kiß╗üu Oanh','0934556677','200 Phan Chu Trinh, Huß║┐',1200000.00,30000.00,'S01',30000.00,2,1200000.00,0,1,'CP04','ORD05-U08-TN',30000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('ORD06',NULL,NULL,'U09','2025-02-12 00:00:00','─Éß╗ù ─Éß╗⌐c Anh','0977889900','45 L├íng Hß║í, ─Éß╗æng ─Éa, H├á Nß╗Öi',500000.00,30000.00,'S02',30000.00,1,500000.00,0,2,'CP05','ORD06-U09-TN',0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('ORD07',NULL,NULL,'U11','2025-02-14 00:00:00','Ng├┤ Xu├ón B├ích','0944332211','102 Quang Trung, G├▓ Vß║Ñp, HCM',190000.00,30000.00,'S03',28500.00,3,191500.00,0,1,NULL,'ORD07-U11-TN',20000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('ORD08',NULL,NULL,'U13','2025-02-18 00:00:00','Trß║ºn Gia Huy','0909123456','32 H├╣ng V╞░╞íng, Nha Trang',600000.00,30000.00,'S04',30000.00,4,600000.00,0,0,'CP01','ORD08-U13-TN',100000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('ORD09',NULL,NULL,'U15','2025-02-20 00:00:00','Phan Quß╗æc Bß║úo','0911223344','15 H├▓a B├¼nh, Bi├¬n H├▓a',350000.00,30000.00,'S01',30000.00,1,350000.00,0,1,'CP02','ORD09-U15-TN',50000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00),('ORD10',NULL,NULL,'U18','2025-02-25 00:00:00','Chu Ph╞░╞íng Thß║úo','0977112233','412 Tr╞░ß╗¥ng Chinh, T├ón B├¼nh, HCM',420000.00,30000.00,'S03',30000.00,1,420000.00,0,2,NULL,'ORD10-U18-TN',40000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `height` int(11) DEFAULT NULL,
  PRIMARY KEY (`variant_id`),
  KEY `fk_variant_prod` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES ('V001','T01','T01-Trß║»ng-S','Trß║»ng','S',150,330000.00,280500.00,NULL,0,1,200,25,20,2),('V002','T01','T01-Trß║»ng-M','Trß║»ng','M',120,230000.00,195500.00,NULL,0,1,200,25,20,2),('V003','T01','T01-Xanh Navy-S','Xanh Navy','S',200,180000.00,153000.00,NULL,0,1,200,25,20,2),('V004','T01','T01-Xanh Navy-M','Xanh Navy','M',215,230000.00,195500.00,NULL,0,1,200,25,20,2),('V005','T02','T02-─Éen-S','─Éen','S',170,250000.00,212500.00,NULL,0,1,200,25,20,2),('V006','T02','T02-─Éen-M','─Éen','M',180,280000.00,238000.00,NULL,0,1,200,25,20,2),('V007','T02','T02-─Éen-L','─Éen','L',210,250000.00,212500.00,NULL,0,1,200,25,20,2),('V008','T02','T02-Ghi-S','Ghi','S',200,190000.00,161500.00,NULL,0,1,200,25,20,2),('V009','T02','T02-Ghi-M','Ghi','M',100,310000.00,263500.00,NULL,0,1,200,25,20,2),('V010','T02','T02-Ghi-L','Ghi','L',110,250000.00,212500.00,NULL,0,1,200,25,20,2),('V011','T03','T03-Hß╗ông-S','Hß╗ông','S',110,420000.00,357000.00,NULL,1,1,200,25,20,2),('V012','T03','T03-Hß╗ông-M','Hß╗ông','M',110,180000.00,153000.00,NULL,0,1,200,25,20,2),('V013','T04','T04-Sß╗ìc Trß║»ng-S','Sß╗ìc Trß║»ng','S',220,350000.00,297500.00,NULL,0,1,200,25,20,2),('V014','T04','T04-Sß╗ìc Trß║»ng-M','Sß╗ìc Trß║»ng','M',200,350000.00,297500.00,NULL,0,1,200,25,20,2),('V015','T04','T04-N├óu-S','N├óu','S',210,320000.00,272000.00,NULL,0,1,200,25,20,2),('V016','T04','T04-N├óu-M','N├óu','M',130,220000.00,187000.00,NULL,0,1,200,25,20,2),('V017','T05','T05-Xanh-S','Xanh','S',120,350000.00,297500.00,NULL,0,1,200,25,20,2),('V018','T05','T05-Xanh-M','Xanh','M',300,240000.00,204000.00,NULL,0,1,200,25,20,2),('V019','T05','T05-─Éen-S','─Éen','S',300,200000.00,170000.00,NULL,0,1,200,25,20,2),('V020','T05','T05-─Éen-M','─Éen','M',120,330000.00,280500.00,NULL,0,1,200,25,20,2),('V021','T06','T06-Trß║»ng-S','Trß║»ng','S',170,310000.00,263500.00,NULL,0,1,200,25,20,2),('V022','T06','T06-Trß║»ng-M','Trß║»ng','M',200,330000.00,280500.00,NULL,1,1,200,25,20,2),('V023','T06','T06-Kem-S','Kem','S',150,440000.00,374000.00,NULL,0,1,200,25,20,2),('V024','T06','T06-Kem-M','Kem','M',120,180000.00,153000.00,NULL,0,1,200,25,20,2),('V025','T07','T07-Trß║»ng-S','Trß║»ng','S',220,400000.00,340000.00,NULL,0,1,200,25,20,2),('V026','T07','T07-Trß║»ng-M','Trß║»ng','M',200,260000.00,221000.00,NULL,0,1,200,25,20,2),('V027','T07','T07-Xanh-S','Xanh','S',210,310000.00,263500.00,NULL,0,1,200,25,20,2),('V028','T07','T07-Xanh-M','Xanh','M',130,270000.00,229500.00,NULL,0,1,200,25,20,2),('V029','T08','T08-─Éen-S','─Éen','S',120,420000.00,357000.00,NULL,0,1,200,25,20,2),('V030','T08','T08-─Éen-M','─Éen','M',300,400000.00,340000.00,NULL,0,1,200,25,20,2),('V031','T08','T08-Trß║»ng-S','Trß║»ng','S',300,240000.00,204000.00,NULL,0,1,200,25,20,2),('V032','T08','T08-Trß║»ng-M','Trß║»ng','M',120,270000.00,229500.00,NULL,0,1,200,25,20,2),('V033','T09','T09-─Éen-S','─Éen','S',150,440000.00,374000.00,NULL,1,1,200,25,20,2),('V034','T09','T09-─Éen-M','─Éen','M',120,220000.00,187000.00,NULL,0,1,200,25,20,2),('V035','T09','T09-─Éen-L','─Éen','L',200,290000.00,246500.00,NULL,0,1,200,25,20,2),('V036','T10','T10-Xanh-S','Xanh','S',215,290000.00,246500.00,NULL,0,1,200,25,20,2),('V037','T10','T10-Xanh-M','Xanh','M',170,310000.00,263500.00,NULL,0,1,200,25,20,2),('V038','T10','T10-Xanh-L','Xanh','L',180,200000.00,170000.00,NULL,0,1,200,25,20,2),('V039','J01','J01-Xanh-S','Xanh','S',210,350000.00,297500.00,NULL,0,1,300,30,20,2),('V040','J01','J01-Xanh-M','Xanh','M',200,280000.00,238000.00,NULL,0,1,300,30,20,2),('V041','J01','J01-Xanh-L','Xanh','L',100,280000.00,238000.00,NULL,0,1,300,30,20,2),('V042','J02','J02-─Éen-S','─Éen','S',110,420000.00,357000.00,NULL,0,1,300,30,20,2),('V043','J02','J02-─Éen-M','─Éen','M',110,200000.00,170000.00,NULL,0,1,300,30,20,2),('V044','J02','J02-─Éen-L','─Éen','L',110,290000.00,246500.00,NULL,1,1,300,30,20,2),('V045','J03','J03-Ghi-S','Ghi','S',220,220000.00,187000.00,NULL,0,1,300,30,20,2),('V046','J03','J03-Ghi-M','Ghi','M',200,280000.00,238000.00,NULL,0,1,300,30,20,2),('V047','J03','J03-Ghi-L','Ghi','L',210,300000.00,255000.00,NULL,0,1,300,30,20,2),('V048','J04','J04-Xanh-S','Xanh','S',130,320000.00,272000.00,NULL,0,1,300,30,20,2),('V049','J04','J04-Xanh-M','Xanh','M',120,420000.00,357000.00,NULL,0,1,300,30,20,2),('V050','J04','J04-Xanh-L','Xanh','L',300,430000.00,365500.00,NULL,0,1,300,30,20,2),('V051','J05','J05-Xanh Nhß║ít-S','Xanh Nhß║ít','S',300,230000.00,195500.00,NULL,0,1,300,30,20,2),('V052','J05','J05-Xanh Nhß║ít-M','Xanh Nhß║ít','M',120,330000.00,280500.00,NULL,0,1,300,30,20,2),('V053','J05','J05-Xanh Nhß║ít-L','Xanh Nhß║ít','L',170,230000.00,195500.00,NULL,0,1,300,30,20,2),('V069','H01','H01-─Éen-S','─Éen','S',200,450000.00,382500.00,NULL,0,1,300,30,20,2),('V070','H01','H01-─Éen-M','─Éen','M',215,220000.00,187000.00,NULL,0,1,300,30,20,2),('V071','H01','H01-─Éen-L','─Éen','L',170,270000.00,229500.00,NULL,0,1,300,30,20,2),('V072','H02','H02-Ghi-S','Ghi','S',180,430000.00,365500.00,NULL,0,1,300,30,20,2),('V073','H02','H02-Ghi-M','Ghi','M',210,440000.00,374000.00,NULL,0,1,300,30,20,2),('V074','H02','H02-Ghi-L','Ghi','L',200,280000.00,238000.00,NULL,0,1,300,30,20,2),('V075','H03','H03-Xanh-S','Xanh','S',100,430000.00,365500.00,NULL,0,1,300,30,20,2),('V076','H03','H03-Xanh-M','Xanh','M',110,410000.00,348500.00,NULL,0,1,300,30,20,2),('V077','H03','H03-Xanh-L','Xanh','L',110,200000.00,170000.00,NULL,1,1,300,30,20,2),('V078','H04','H04-N├óu-S','N├óu','S',110,400000.00,340000.00,NULL,0,1,300,30,20,2),('V079','H04','H04-N├óu-M','N├óu','M',220,300000.00,255000.00,NULL,0,1,300,30,20,2),('V080','H04','H04-N├óu-L','N├óu','L',200,200000.00,170000.00,NULL,0,1,300,30,20,2),('V081','H05','H05-─Éß╗Å-S','─Éß╗Å','S',210,180000.00,153000.00,NULL,0,1,300,30,20,2),('V082','H05','H05-─Éß╗Å-M','─Éß╗Å','M',130,430000.00,365500.00,NULL,0,1,300,30,20,2),('V083','H05','H05-─Éß╗Å-L','─Éß╗Å','L',120,200000.00,170000.00,NULL,0,1,300,30,20,2),('V084','H06','H06-Trß║»ng-S','Trß║»ng','S',300,400000.00,340000.00,NULL,0,1,300,30,20,2),('V085','H06','H06-Trß║»ng-M','Trß║»ng','M',300,300000.00,255000.00,NULL,0,1,300,30,20,2),('V086','H06','H06-Trß║»ng-L','Trß║»ng','L',120,180000.00,153000.00,NULL,0,1,300,30,20,2),('V087','H07','H07-V├áng-S','V├áng','S',170,430000.00,365500.00,NULL,0,1,300,30,20,2),('V088','H07','H07-V├áng-M','V├áng','M',200,410000.00,348500.00,NULL,1,1,300,30,20,2),('V089','H07','H07-V├áng-L','V├áng','L',150,200000.00,170000.00,NULL,0,1,300,30,20,2),('V099','S01','S01-Trß║»ng-S','Trß║»ng','S',150,430000.00,365500.00,NULL,1,1,300,30,20,2),('V100','S01','S01-Trß║»ng-M','Trß║»ng','M',120,410000.00,348500.00,NULL,0,1,300,30,20,2),('V101','S01','S01-Trß║»ng-L','Trß║»ng','L',200,200000.00,170000.00,NULL,0,1,300,30,20,2),('V102','S02','S02-Xanh-S','Xanh','S',215,400000.00,340000.00,NULL,0,1,300,30,20,2),('V103','S02','S02-Xanh-M','Xanh','M',170,300000.00,255000.00,NULL,0,1,300,30,20,2),('V104','S02','S02-Xanh-L','Xanh','L',180,180000.00,153000.00,NULL,0,1,300,30,20,2),('V105','S03','S03-V├áng-S','V├áng','S',210,430000.00,365500.00,NULL,0,1,300,30,20,2),('V106','S03','S03-V├áng-M','V├áng','M',200,410000.00,348500.00,NULL,0,1,300,30,20,2),('V107','S03','S03-V├áng-L','V├áng','L',100,200000.00,170000.00,NULL,0,1,300,30,20,2),('V108','S04','S04-─Éen-S','─Éen','S',110,400000.00,340000.00,NULL,0,1,300,30,20,2),('V109','S04','S04-─Éen-M','─Éen','M',110,300000.00,255000.00,NULL,0,1,300,30,20,2),('V110','S04','S04-─Éen-L','─Éen','L',110,180000.00,153000.00,NULL,1,1,300,30,20,2),('V111','S05','S05-─Éß╗Å-S','─Éß╗Å','S',220,430000.00,365500.00,NULL,0,1,300,30,20,2),('V112','S05','S05-─Éß╗Å-M','─Éß╗Å','M',200,410000.00,348500.00,NULL,0,1,300,30,20,2),('V113','S05','S05-─Éß╗Å-L','─Éß╗Å','L',210,200000.00,170000.00,NULL,0,1,300,30,20,2),('V114','S06','S06-Hß╗ông-S','Hß╗ông','S',130,400000.00,340000.00,NULL,0,1,300,30,20,2),('V115','S06','S06-Hß╗ông-M','Hß╗ông','M',120,300000.00,255000.00,NULL,0,1,300,30,20,2),('V116','S06','S06-Hß╗ông-L','Hß╗ông','L',300,180000.00,153000.00,NULL,0,1,300,30,20,2),('V117','S07','S07-T├¡m-S','T├¡m','S',300,430000.00,365500.00,NULL,0,1,300,30,20,2),('V118','S07','S07-T├¡m-M','T├¡m','M',120,410000.00,348500.00,NULL,0,1,300,30,20,2),('V119','S07','S07-T├¡m-L','T├¡m','L',170,200000.00,170000.00,NULL,0,1,300,30,20,2),('V129','SK01','SK01-─Éen-Freesize','─Éen','Freesize',300,430000.00,365500.00,NULL,0,1,300,30,20,2),('V130','SK01','SK01-Kem-Freesize','Kem','Freesize',120,410000.00,348500.00,NULL,0,1,300,30,20,2),('V131','SK01','SK01-N├óu-Freesize','N├óu','Freesize',170,200000.00,170000.00,NULL,1,1,300,30,20,2),('V132','SK02','SK02-─Éen-Freesize','─Éen','Freesize',200,400000.00,340000.00,NULL,0,1,300,30,20,2),('V133','SK02','SK02-X├ím-Freesize','X├ím','Freesize',150,300000.00,255000.00,NULL,0,1,300,30,20,2),('V134','SK03','SK03-─Éen-Freesize','─Éen','Freesize',120,180000.00,153000.00,NULL,0,1,300,30,20,2),('V135','SK03','SK03-Trß║»ng-Freesize','Trß║»ng','Freesize',220,430000.00,365500.00,NULL,0,1,300,30,20,2),('V136','SK03','SK03-X├ím-Freesize','X├ím','Freesize',200,410000.00,348500.00,NULL,0,1,300,30,20,2),('V137','SK04','SK04-N├óu-Freesize','N├óu','Freesize',210,200000.00,170000.00,NULL,0,1,300,30,20,2),('V138','SK04','SK04-─Éen-Freesize','─Éen','Freesize',130,400000.00,340000.00,NULL,0,1,300,30,20,2),('V139','SK04','SK04-Ghi-Freesize','Ghi','Freesize',120,300000.00,255000.00,NULL,0,1,300,30,20,2),('V140','SK05','SK05-─Éen-Freesize','─Éen','Freesize',300,180000.00,153000.00,NULL,0,1,300,30,20,2),('V141','SK05','SK05-Xanh Navy-Freesize','Xanh Navy','Freesize',300,430000.00,365500.00,NULL,0,1,300,30,20,2),('V142','SK05','SK05-Trß║»ng-Freesize','Trß║»ng','Freesize',120,410000.00,348500.00,NULL,1,1,300,30,20,2),('V143','SK06','SK06-─Éen-Freesize','─Éen','Freesize',170,200000.00,170000.00,NULL,0,1,300,30,20,2),('V144','SK06','SK06-Trß║»ng-Freesize','Trß║»ng','Freesize',200,400000.00,340000.00,NULL,0,1,300,30,20,2),('V145','SK07','SK07-Hß╗ông-Freesize','Hß╗ông','Freesize',150,300000.00,255000.00,NULL,0,1,300,30,20,2),('V146','SK07','SK07-Trß║»ng-Freesize','Trß║»ng','Freesize',120,180000.00,153000.00,NULL,0,1,300,30,20,2),('V147','SK08','SK08-Xanh-Freesize','Xanh','Freesize',220,430000.00,365500.00,NULL,0,1,300,30,20,2),('V148','SK08','SK08-Trß║»ng Lung Linh-Freesize','Trß║»ng Lung Linh','Freesize',200,410000.00,348500.00,NULL,0,1,300,30,20,2),('V149','SK09','SK09-─Éen-Freesize','─Éen','Freesize',210,200000.00,170000.00,NULL,0,1,300,30,20,2),('V150','SK09','SK09-N├óu-Freesize','N├óu','Freesize',130,400000.00,340000.00,NULL,0,1,300,30,20,2),('V151','SK10','SK10-─Éen-1','─Éen','1',120,300000.00,255000.00,NULL,0,1,300,30,20,2),('V152','SK10','SK10-─Éen-2','─Éen','2',300,180000.00,153000.00,NULL,0,1,300,30,20,2),('V153','SK10','SK10-N├óu-1','N├óu','1',300,430000.00,365500.00,NULL,1,1,300,30,20,2),('V154','SK10','SK10-N├óu-2','N├óu','2',120,410000.00,348500.00,NULL,0,1,300,30,20,2),('V155','SK06','SK06-Trß║»ng-Freesize','Trß║»ng','Freesize',150,400000.00,340000.00,NULL,0,1,300,30,20,2),('V156','SK07','SK07-Trß║»ng-Freesize','Trß║»ng','Freesize',120,290000.00,246500.00,NULL,0,1,300,30,20,2),('V157','SK08','SK08-Trß║»ng Lung Linh-Freesize','Trß║»ng Lung Linh','Freesize',250,450000.00,382500.00,NULL,1,1,300,30,20,2),('V158','SK09','SK09-─Éen-Freesize','─Éen','Freesize',220,220000.00,187000.00,NULL,0,1,300,30,20,2),('V159','SK10','SK10-N├óu-1','N├óu','1',200,210000.00,178500.00,NULL,0,1,300,30,20,2),('V160','SK10','SK10-N├óu-2','N├óu','2',300,320000.00,272000.00,NULL,0,1,300,30,20,2),('V161','SK10','SK10-─Éen-1','─Éen','1',300,420000.00,357000.00,NULL,0,1,300,30,20,2),('V162','SK10','SK10-─Éen-2','─Éen','2',120,330000.00,280500.00,NULL,1,1,300,30,20,2),('V163','C01','C01-─Éß╗Å-Freesize','─Éß╗Å','Freesize',170,430000.00,365500.00,NULL,0,1,300,30,18,5),('V164','C01','C01-Sß╗ìc B├⌐-Freesize','Sß╗ìc B├⌐','Freesize',200,410000.00,348500.00,NULL,1,1,300,30,18,5),('V165','C02','C02-─Éß╗Å-Freesize','─Éß╗Å','Freesize',150,200000.00,170000.00,NULL,0,1,300,30,18,5),('V166','C02','C02-Trß║»ng-Freesize','Trß║»ng','Freesize',120,400000.00,340000.00,NULL,0,1,300,30,18,5),('V167','C03','C03-Sß╗ìc ─Éß╗Å-Freesize','Sß╗ìc ─Éß╗Å','Freesize',220,300000.00,255000.00,NULL,0,1,300,30,18,5),('V168','C04','C04-Sß╗ìc ─Éen-Freesize','Sß╗ìc ─Éen','Freesize',150,200000.00,170000.00,NULL,0,1,300,30,18,5),('V169','C05','C05-Trß║»ng-1','Trß║»ng','1',120,180000.00,153000.00,NULL,0,1,300,30,18,5),('V170','P01','SKU-P01','Mß║╖c ─æß╗ïnh','Freesize',100,331000.00,331000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V171','P02','SKU-P02','Mß║╖c ─æß╗ïnh','Freesize',100,165000.00,165000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V172','P03','SKU-P03','Mß║╖c ─æß╗ïnh','Freesize',100,343000.00,343000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V173','P04','SKU-P04','Mß║╖c ─æß╗ïnh','Freesize',100,181000.00,181000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V174','P05','SKU-P05','Mß║╖c ─æß╗ïnh','Freesize',100,230000.00,230000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V175','P06','SKU-P06','Mß║╖c ─æß╗ïnh','Freesize',100,178000.00,178000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V176','P07','SKU-P07','Mß║╖c ─æß╗ïnh','Freesize',100,268000.00,268000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V177','P08','SKU-P08','Mß║╖c ─æß╗ïnh','Freesize',100,353000.00,353000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V178','P09','SKU-P09','Mß║╖c ─æß╗ïnh','Freesize',100,329000.00,329000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V179','P10','SKU-P10','Mß║╖c ─æß╗ïnh','Freesize',100,265000.00,265000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V180','SH01','SKU-SH01','Mß║╖c ─æß╗ïnh','Freesize',100,374000.00,374000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V181','SH02','SKU-SH02','Mß║╖c ─æß╗ïnh','Freesize',100,203000.00,203000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V182','SH03','SKU-SH03','Mß║╖c ─æß╗ïnh','Freesize',100,211000.00,211000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V183','PL01','SKU-PL01','Mß║╖c ─æß╗ïnh','Freesize',100,183000.00,183000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V184','PL02','SKU-PL02','Mß║╖c ─æß╗ïnh','Freesize',100,353000.00,353000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V185','PL03','SKU-PL03','Mß║╖c ─æß╗ïnh','Freesize',100,309000.00,309000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V186','PL04','SKU-PL04','Mß║╖c ─æß╗ïnh','Freesize',100,355000.00,355000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V187','PL05','SKU-PL05','Mß║╖c ─æß╗ïnh','Freesize',100,194000.00,194000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V188','PL06','SKU-PL06','Mß║╖c ─æß╗ïnh','Freesize',100,281000.00,281000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V189','JE01','SKU-JE01','Mß║╖c ─æß╗ïnh','Freesize',100,177000.00,177000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V190','JE02','SKU-JE02','Mß║╖c ─æß╗ïnh','Freesize',100,376000.00,376000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V191','JE03','SKU-JE03','Mß║╖c ─æß╗ïnh','Freesize',100,260000.00,260000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V192','JE04','SKU-JE04','Mß║╖c ─æß╗ïnh','Freesize',100,322000.00,322000.00,NULL,0,1,NULL,NULL,NULL,NULL),('V193','JE05','SKU-JE05','Mß║╖c ─æß╗ïnh','Freesize',100,271000.00,271000.00,NULL,0,1,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `seo_description` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `fk_prod_cat` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES ('C01','CAT10','├üo Kho├íc Cardigan Len','├üo Kho├íc Cardigan Len H├án Quß╗æc D├áy Dß║╖n Nhiß╗üu M├áu Th├¬u Logo','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m4hdzd36m4q8c9@resize_w900_nl.webp',4.8,58,1,4.8,100,'Mua ├üo Len gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Len chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('C02','CAT10','├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å','├üo Len Cß╗ò Tr├▓n L├┤ng Thß╗Å Mß╗üm Mß╗ïn ├üo Sweater Sß╗úi Dß╗çt D├áy Dß║╖n ß║ñm ├üp M├╣a ─É├┤ng','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mji6wkvavqx1fa@resize_w900_nl.webp',4.4,167,1,4.8,100,'Mua ├üo Len gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Len chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('C03','CAT10','├üo Len Kß║╗ Sß╗ìc Thu ─É├┤ng','├üo Len D├ái Tay Thu ─É├┤ng Kß║╗ Sß╗ìc Croptop Phong C├ích H├án Quß╗æc Basic N─âng ─Éß╗Öng','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mhmvya34ka2p92@resize_w900_nl.webp',4.7,378,1,4.8,100,'Mua ├üo Len gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Len chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('C04','CAT10','├üo L├┤ng Thß╗Å D├ái Tay','├üo L├┤ng Thß╗Å D├ái Tay Mß╗üm Mß╗ïn ├üo Len Kß║╗ Sß╗ìc Sleeves Form Rß╗Öng Basic Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mji9pf2xw1dzb9@resize_w900_nl.webp',4.4,365,1,4.8,100,'Mua ├üo Len gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Len chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('C05','CAT10','├üo Len Mß╗Ång Cß╗Öc Tay','├üo Len Mß╗Ång M├╣a Thu Cß╗Öc Tay Phß╗æi M├áu ├üo Len C├│ Cß╗ò Tho├íng Kh├¡ Dß╗à Phß╗æi ─Éß╗ô','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mdbj2ec8tetb7a@resize_w900_nl.webp',4.1,242,1,4.8,100,'Mua ├üo Len gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Len chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('H01','CAT03','├üo Hoodie Zip basic','├üo Hoodie Zip Basic Vß║úi Nß╗ë 2 Da Chß╗æng Nß║»ng Tß╗æt Form Rß╗Öng Nam Nß╗» Unisex','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m1djz3jqsva0d1.webp',4.6,305,1,4.6,92,'Mua ├üo Hoodie Zip basic gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Hoodie Zip basic chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('H02','CAT03','├üo Hoodie Zip phß╗æi Caro','├üo Hoodie Zip Phß╗æi Caro Nß╗ë 2 Da Th├¬u 77 Foreveryoung Form Rß╗Öng Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg6a54w1k8az55.webp',4.8,422,1,4.8,127,'Mua ├üo Hoodie Zip phß╗æi Caro gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Hoodie Zip phß╗æi Caro chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('H03','CAT03','├üo hoodie cß╗¥ Mß╗╣','├üo Hoodie in lß╗Ña cß╗¥ Mß╗╣ Form Rß╗Öng Phong C├ích ├éu Mß╗╣ Unisex Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg253sm8k6x758.webp',4.5,59,1,4.5,18,'Mua ├üo hoodie cß╗¥ Mß╗╣ gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo hoodie cß╗¥ Mß╗╣ chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('H04','CAT03','├üo Hoodie Zip Nß╗ë B├┤ng Form Boxy','├üo Hoodie Zip Nß╗ë B├┤ng Basic Form Boxy Urban Kho├í K├⌐o BYC Streetwear Unisex Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mk0do4ap7xtz53.webp',4.4,334,1,4.4,100,'Mua ├üo Hoodie Zip Nß╗ë B├┤ng Form Boxy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Hoodie Zip Nß╗ë B├┤ng Form Boxy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('H05','CAT03','├üo Hoodie Zip Nß╗ë B├┤ng Kho├í K├⌐o 2 ─Éß║ºu','├üo Hoodie Zip Nß╗ë B├┤ng Kho├í K├⌐o 2 ─Éß║ºu WITHLOVE Form Boxy Basic Phong C├ích H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mdzue2ay0tmrba.webp',4.8,490,1,4.8,147,'Mua ├üo Hoodie Zip Nß╗ë B├┤ng Kho├í K├⌐o 2 ─Éß║ºu gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Hoodie Zip Nß╗ë B├┤ng Kho├í K├⌐o 2 ─Éß║ºu chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('H06','CAT03','├üo Hoodie Zip ORIGINALS','├üo Hoodie Zip ORIGINALS Nß╗ë 2 Da Kh├┤ng X├╣ Chß╗» Th├¬u','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mdwm103kinlud9.webp',4.1,387,1,4.1,116,'Mua ├üo Hoodie Zip ORIGINALS gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Hoodie Zip ORIGINALS chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('H07','CAT03','├üo Kho├íc Hoodie Zip Nß╗ë Ch├ón Cua','├üo Kho├íc Hoodie Zip Nß╗ë Ch├ón Cua D├áy Dß║╖n ├üo Hoodie Form Boxy Unisex Phong C├ích H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mjibi5ytlb0l94.webp',4.2,237,1,4.2,71,'Mua ├üo Kho├íc Hoodie Zip Nß╗ë Ch├ón Cua gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Kho├íc Hoodie Zip Nß╗ë Ch├ón Cua chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('J01','CAT02','├üo kho├íc da','├üo Kho├íc Da Tay D├ái K├¿m T├║i Trong Da Cao Cß║Ñp Phong C├ích Retro Cß╗ò ─Éiß╗ân','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mh71v1j7gb9nc8.webp',4.8,311,1,4.8,93,'Mua ├üo kho├íc da gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo kho├íc da chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('J02','CAT02','├üo kho├íc d├╣','├üo Kho├íc D├╣ Chß║»n Gi├│ Nhiß╗üu M├áu M┼⌐ D├óy R├║t Chß╗æng Nß║»ng H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mf3y04daxxxm7b.webp',4.5,229,1,4.5,69,'Mua ├üo kho├íc d├╣ gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo kho├íc d├╣ chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('J03','CAT02','├üo kho├íc Canvas','├üo Kho├íc Canvas D├íng Ngß║»n ├üo Kho├íc Phß╗æi Cß╗ò Nhung T─âm Basic Unisex Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg7d8s7jrvnvb7.webp',4.1,214,1,4.1,64,'Mua ├üo kho├íc Canvas gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo kho├íc Canvas chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('J04','CAT02','├üo kho├íc Phao','├üo Kho├íc Phao Phß╗ông Si├¬u Nhß║╣ Si├¬u ß║ñm ├üo Phao B├⌐o D├íng Lß╗¡ng M├╣a ─É├┤ng','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m5ca38ruq4ae89.webp',4.7,474,1,4.7,142,'Mua ├üo kho├íc Phao gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo kho├íc Phao chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('J05','CAT02','├üo kho├íc Bomber','├üo Kho├íc Bomber Pilot Oversized Chß║ºn B├┤ng Th├¬u Chß╗» Thß╗¥i Trang Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m33curbuutamf5.webp',4.3,244,1,4.3,73,'Mua ├üo kho├íc Bomber gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo kho├íc Bomber chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('JE01','CAT08','Quß║ºn Jean D├íng B├¡','Quß║ºn Jean D├íng B├¡ Cat Washing Denim Retro Unisex Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfcb1buk8sumb0@resize_w900_nl.webp',4.8,353,1,4.8,100,'Mua  Quß║ºn Jeans  gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Jeans chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('JE02','CAT08','Quß║ºn Jeans Mß╗üm D├íng D├ái','Quß║ºn Jeans Mß╗üm D├íng D├ái Gß║¡p Gß║Ñu Quß║ºn D├ái Form Rß╗Öng Chß║Ñt Denim Mß╗üm ─Éß╗⌐ng Form Unisex','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mftf1gdhowll3e@resize_w900_nl.webp',4.3,123,1,4.8,100,'Mua  Quß║ºn Jeans  gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Jeans chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('JE03','CAT08','Quß║ºn Jean D├íng Lß╗¡ng D├ái Demi','Quß║ºn Jean D├íng Lß╗¡ng D├ái Demi Jean Short N─âng ─Éß╗Öng Denim Wash','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mbc529lr4zbsf1@resize_w900_nl.webp',4.8,127,1,4.8,100,'Mua  Quß║ºn Jeans  gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Jeans chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('JE04','CAT08','Quß║ºn Jeans Wash','Quß║ºn Jeans Wash New Cß║íp Cao Quß║ºn B├▓ ß╗Éng Rß╗Öng T├┤n D├íng Basic','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfxc467plp8te5@resize_w900_nl.webp',4.0,305,1,4.8,100,'Mua  Quß║ºn Jeans  gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Jeans chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('JE05','CAT08','Quß║ºn B├▓ Wash M├áu','Quß║ºn Jean ß╗Éng Rß╗Öng T├┤n D├íng Quß║ºn B├▓ Wash M├áu Unisex Thß╗¥i Trang Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfzb05oeal8odb@resize_w900_nl.webp',4.4,422,1,4.8,100,'Mua  Quß║ºn Jeans  gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Jeans chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('P01','CAT04','Quß║ºn D├ái Kß║╗ Sß╗ìc Kaki','Quß║ºn D├ái Kß║╗ Sß╗ìc Kaki ß╗Éng Rß╗Öng Phß╗æi D├óy Belt','https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7q889omjzub28.webp',4.9,170,1,4.9,51,'Mua Quß║ºn D├ái Kß║╗ Sß╗ìc Kaki gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn D├ái Kß║╗ Sß╗ìc Kaki chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('P02','CAT04','Quß║ºn Kaki BALLOON','Quß║ºn Kaki BALLOON ß╗Éng Rß╗Öng D├íng Cong Pants Hack Eo Phong C├ích','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mc8wdg5whi6qb8.webp',4.0,313,1,4.0,94,'Mua Quß║ºn Kaki BALLOON gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Kaki BALLOON chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('P03','CAT04','Quß║ºn Nß╗ë Form Rß╗Öng ORIGINALS','Quß║ºn Nß╗ë Form Rß╗Öng ORIGINALS Kh├┤ng X├╣ Phong C├ích ─É╞ín Giß║ún Thoß║úi M├íi','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-meksw027d340f0.webp',4.5,99,1,4.5,30,'Mua Quß║ºn Nß╗ë Form Rß╗Öng ORIGINALS gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Nß╗ë Form Rß╗Öng ORIGINALS chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('P04','CAT04','Quß║ºn Vß║úi D├╣ Xß║┐p Ly ß╗Éng Thß╗Ñng','Quß║ºn Vß║úi D├╣ Xß║┐p Ly ß╗Éng Thß╗Ñng Form Wide Leg Phong C├ích ─É╞░ß╗¥ng Phß╗æ H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mgrnaj06x2bw5f.webp',4.7,36,1,4.7,11,'Mua Quß║ºn Vß║úi D├╣ Xß║┐p Ly ß╗Éng Thß╗Ñng gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Vß║úi D├╣ Xß║┐p Ly ß╗Éng Thß╗Ñng chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('P05','CAT04','Quß║ºn Parachute Harem','Quß║ºn Parachute Harem D├íng Thß╗Ñng Vintage Quß║ºn D├ái Dß╗à Vß║¡n ─Éß╗Öng Nhß║¡t Bß║ún','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mdk2lm3j25s5f1.webp',4.6,247,1,4.6,74,'Mua Quß║ºn Parachute Harem gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Parachute Harem chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('P06','CAT04','Quß║ºn Jean ß╗Éng Rß╗Öng','Quß║ºn Jean ß╗Éng Rß╗Öng T├┤n D├íng Quß║ºn B├▓ Wash M├áu Unisex Thß╗¥i Trang Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfzb05oeal8odb.webp',4.8,400,1,4.8,120,'Mua Quß║ºn Jean ß╗Éng Rß╗Öng gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Jean ß╗Éng Rß╗Öng chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('P07','CAT04','Quß║ºn Vß║úi D├╣ T├║i Hß╗Öp','Quß║ºn Vß║úi D├╣ T├║i Hß╗Öp Form Thß╗Ñng Phß╗æi D├óy R├║t Nam Nß╗» Cargo Pants Streetwear','https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lzjxjlys0s695c.webp',4.4,471,1,4.4,141,'Mua Quß║ºn Vß║úi D├╣ T├║i Hß╗Öp gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Vß║úi D├╣ T├║i Hß╗Öp chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('P08','CAT04','Quß║ºn D├ái Vß║úi ─É┼⌐i Cß║íp Chun','Quß║ºn D├ái Vß║úi ─É┼⌐i Cß║íp Chun Mß╗üm Mß║íi Th├┤ng Tho├íng ─Éa N─âng M├╣a Thu M├╣a ─É├┤ng','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcm9c1erguct08.webp',4.3,123,1,4.3,37,'Mua Quß║ºn D├ái Vß║úi ─É┼⌐i Cß║íp Chun gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn D├ái Vß║úi ─É┼⌐i Cß║íp Chun chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('P09','CAT04','Quß║ºn Kaki ß╗Éng Rß╗Öng ß╗Éng Su├┤ng','Quß║ºn Kaki ß╗Éng Rß╗Öng ß╗Éng Su├┤ng Phong C├ích Trß║╗ Trung N─âng ─Éß╗Öng Dß╗à Phß╗æi ─Éß╗ô','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-md9vnutr17doc8@resize_w900_nl.webp',5.0,297,1,5.0,89,'Mua Quß║ºn Kaki ß╗Éng Rß╗Öng ß╗Éng Su├┤ng gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Kaki ß╗Éng Rß╗Öng ß╗Éng Su├┤ng chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('P10','CAT04','Quß║ºn Vß║úi D├╣ ß╗Éng Rß╗Öng PARACHUTE','Quß║ºn Vß║úi D├╣ ß╗Éng Rß╗Öng PARACHUTE M├áu Tr╞ín','https://down-vn.img.susercontent.com/file/vn-11134207-7qukw-lj6mxj354wzwd3.webp',4.9,206,1,4.9,62,'Mua Quß║ºn Vß║úi D├╣ ß╗Éng Rß╗Öng PARACHUTE gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Vß║úi D├╣ ß╗Éng Rß╗Öng PARACHUTE chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('PL01','CAT07','├üo Thun Polo Phß╗æi Cß╗ò','├üo Thun Polo Phß╗æi Cß╗ò Basic N─âng ─Éß╗Öng Cho Nß╗» Xu├ón H├¿ 2025','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcrbsixysl9pbc@resize_w900_nl.webp',4.7,413,1,4.7,124,'Mua ├üo Thun Polo Phß╗æi Cß╗ò gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Thun Polo Phß╗æi Cß╗ò chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('PL02','CAT07','├üo Polo Kß║╗ Sß╗ìc BabyTee','├üo Polo Kß║╗ Sß╗ìc BabyTee Hß╗ìa Tiß║┐t Th├¬u Thiß║┐t Kß║┐ T├┤n D├íng Cho Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m9o595a72b1qf8@resize_w900_nl.webp',4.2,359,1,4.2,108,'Mua ├üo Polo Kß║╗ Sß╗ìc BabyTee gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Polo Kß║╗ Sß╗ìc BabyTee chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('PL03','CAT07','├üo Polo Chiß║┐t Eo Tay Bß╗ông','├üo Polo Chiß║┐t Eo Tay Bß╗ông Form ├öm Vß╗½a T├┤n D├íng Cho Nß╗» Xu├ón H├¿ 2025','https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m9k2akpj4mqi7b@resize_w900_nl.webp',4.7,499,1,4.7,150,'Mua ├üo Polo Chiß║┐t Eo Tay Bß╗ông gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Polo Chiß║┐t Eo Tay Bß╗ông chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('PL04','CAT07','├üo Thun D├ái Tay Polo Kß║╗ Ngang','├üo Thun D├ái Tay Polo Kß║╗ Ngang Sß╗ìc Lß╗¢n H├án Quß╗æc Thu ─É├┤ng Logo Th├¬u Trendy','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mh5pidvewdu7e2@resize_w900_nl.webp',4.7,175,1,4.7,53,'Mua ├üo Thun D├ái Tay Polo Kß║╗ Ngang gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Thun D├ái Tay Polo Kß║╗ Ngang chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('PL05','CAT07','├üo Polo Basic Babytee','├üo Polo Basic Babytee Cho Nß╗» Vß║úi C├í Sß║Ñu Cotton Logo Th├¬u T├║i Ngß╗▒c','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-m0mh43fd5ocvc4@resize_w900_nl.webp',4.7,163,1,4.7,49,'Mua ├üo Polo Basic Babytee gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Polo Basic Babytee chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('PL06','CAT07','├üo Len D├ái Tay Cß╗ò Polo','├üo Len D├ái Tay Cß╗ò Polo ├üo Len Vß║╖n Thß╗½ng Basic Chß║Ñt Mß╗ïn D├áy Dß║╖n Premium','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mjl2uiqymznrd3@resize_w900_nl.webp',4.3,436,1,4.3,131,'Mua ├üo Len D├ái Tay Cß╗ò Polo gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Len D├ái Tay Cß╗ò Polo chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('S01','CAT05','├üo S╞í Mi Basic','├üo S╞í Mi Basic Nhiß╗üu M├áu D├íng Rß╗Öng Hß╗ìa Tiß║┐t Kß║╗ Sß╗ìc Thß╗¥i Trang ─É╞░ß╗¥ng Phß╗æ','https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-llqdtiaj374v5c.webp',4.7,450,1,4.7,135,'Mua ├üo S╞í Mi Basic gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo S╞í Mi Basic chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('S02','CAT05','├üo S╞í Mi Chiß║┐t Eo','├üo S╞í Mi Chiß║┐t Eo Buß╗Öc N╞í C├│ T├║i Ngß╗▒c D├ánh Cho Nß╗» Phong C├ích H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-md2x7l0cran0fb@resize_w900_nl.webp',4.1,484,1,4.1,145,'Mua ├üo S╞í Mi Chiß║┐t Eo gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo S╞í Mi Chiß║┐t Eo chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('S03','CAT05','├üo S╞í Mi Kß║╗ Cß╗ÿC TAY','├üo S╞í Mi Kß║╗ Cß╗ÿC TAY Vß║úi Oxford Phß╗æi Cß╗ò Trß║»ng D├íng Rß╗Öng Phong C├ích H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-ltdmmaiv6t9548@resize_w900_nl.webp',4.3,180,1,4.3,54,'Mua ├üo S╞í Mi Kß║╗ Cß╗ÿC TAY gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo S╞í Mi Kß║╗ Cß╗ÿC TAY chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('S04','CAT05','├üo S╞í Mi Form Fit','├üo S╞í Mi Bycamcam Form Fit Tr╞ín Nhiß╗üu M├áu Tho├íng Kh├¡ ─Éß╗⌐ng Form','https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lzrhde113o9t1f.webp',4.6,253,1,4.6,76,'Mua ├üo S╞í Mi Form Fit gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo S╞í Mi Form Fit chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('S05','CAT05','├üo S╞í Mi Cß╗Öc Tay Form Rß╗Öng','├üo S╞í Mi Cß╗Öc Tay Form Rß╗Öng Trß║╗ Trung Hoß║í Tiß║┐t Kß║╗ Kho├í Tr├íi Tim','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mbgh9gzxgbc0ed.webp',4.2,430,1,4.2,129,'Mua ├üo S╞í Mi Cß╗Öc Tay Form Rß╗Öng gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo S╞í Mi Cß╗Öc Tay Form Rß╗Öng chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('S06','CAT05','├üo S╞í Mi Kß║╗ Sß╗ìc Tay D├ái Mß╗Ång M├ít','├üo S╞í Mi Kß║╗ Sß╗ìc Tay D├ái Mß╗Ång M├ít Form Rß╗Öng Vß║ít T├┤m Thß╗¥i Trang H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lxunl9nhukt783@resize_w900_nl.webp',4.8,357,1,4.8,107,'Mua ├üo S╞í Mi Kß║╗ Sß╗ìc Tay D├ái Mß╗Ång M├ít gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo S╞í Mi Kß║╗ Sß╗ìc Tay D├ái Mß╗Ång M├ít chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('S07','CAT05','├üo S╞í Mi Kß║╗ Sß╗ìc Cß╗ò Nhß╗ìn','├üo S╞í Mi Kß║╗ Sß╗ìc Cß╗ò Nhß╗ìn Striped Shirt D├íng Lß╗¡ng Phong C├ích H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lvzhuqgyr1ej37@resize_w900_nl.webp',4.5,82,1,4.5,25,'Mua ├üo S╞í Mi Kß║╗ Sß╗ìc Cß╗ò Nhß╗ìn gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo S╞í Mi Kß║╗ Sß╗ìc Cß╗ò Nhß╗ìn chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SH01','CAT06','Quß║ºn Short Kaki T├║i Hß╗Öp','Quß║ºn Short Kaki T├║i Hß╗Öp D├íng Ngß║»n Phong C├ích Retro','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcg2d2ixd4fw7b@resize_w900_nl.webp',4.7,396,1,4.7,119,'Mua Quß║ºn Short Kaki T├║i Hß╗Öp gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Short Kaki T├║i Hß╗Öp chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SH02','CAT06','Quß║ºn Short D├╣ Thß╗â Thao','Quß║ºn Short D├╣ Thß╗â Thao Sß╗ìc Vß║úi D├╣ Phong C├ích Sporty','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-md5i7uvw67nj7a@resize_w900_nl.webp',4.1,138,1,4.1,41,'Mua Quß║ºn Short D├╣ Thß╗â Thao gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Short D├╣ Thß╗â Thao chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SH03','CAT06','Quß║ºn Jeans Short Lß╗¡ng','Quß║ºn Jeans Short Lß╗¡ng Cß║íp ─É├¡nh C├║c Vß║úi Denim Phong C├ích H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfxj5ibz0u8c2f@resize_w900_nl.webp',4.7,194,1,4.7,58,'Mua Quß║ºn Jeans Short Lß╗¡ng gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m Quß║ºn Jeans Short Lß╗¡ng chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SK01','CAT09','Ch├ón V├íy Ngß║»n Y2K','Ch├ón V├íy Ngß║»n Y2K Caro L╞░ng Thß║Ñp K├¿m Quß║ºn Bß║úo Hß╗Ö','https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lzbdjua7vpe59e@resize_w900_nl.webp',4.4,349,1,4.8,100,'Mua V├íy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m V├íy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SK02','CAT09','Quß║ºn V├íy Ngß║»n Nß╗ë ├ëp','Quß║ºn V├íy Ngß║»n Nß╗ë ├ëp Basic T├┤n D├íng D├ánh Cho Nß╗» Phong C├ích H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcr9179vpcx98a@resize_w900_nl.webp',4.8,58,1,4.8,100,'Mua V├íy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m V├íy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SK03','CAT09','Ch├ón V├íy D├ái Xß║┐p Ly','Ch├ón V├íy D├ái Xß║┐p Ly L╞░ng Cao Phong C├ích H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mczvn82i30rx20@resize_w900_nl.webp',4.2,98,1,4.8,100,'Mua V├íy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m V├íy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SK04','CAT09','Quß║ºn V├íy C├ích ─Éiß╗çu','Quß║ºn V├íy C├ích ─Éiß╗çu H├án Quß╗æc Vß║úi Ch├⌐o H├án ─É├¡nh Logo Nß╗» T├¡nh','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcpv6fnksgnw5f@resize_w900_nl.webp',4.2,203,1,4.8,100,'Mua V├íy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m V├íy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SK05','CAT09','Quß║ºn V├íy Ngß║»n D├íng Xo├¿','Quß║ºn V├íy Ngß║»n D├íng Xo├¿ Cß║íp Cao Phong C├ích ├éu Mß╗╣ C├í T├¡nh','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-md2zgopg67qlc2@resize_w900_nl.webp',4.4,122,1,4.8,100,'Mua V├íy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m V├íy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SK06','CAT09','Quß║ºn V├íy Ngß║»n D├íng B├¡','Quß║ºn V├íy Ngß║»n D├íng B├¡ Nß╗» Si├¬u Phß╗ông Chß║Ñt D├╣ Form Nhß╗Å D├íng Ngß║»n Hack D├íng Cho Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7q1ympl44vl12@resize_w900_nl.webp',4.0,331,1,4.8,100,'Mua V├íy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m V├íy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SK07','CAT09','Quß║ºn V├íy Nß╗ë ├ëp Chß║Ñm Bi','Quß║ºn V├íy Nß╗ë ├ëp Hoß║í Tiß║┐t Chß║Ñm Bi Basic Trendy N─âng ─Éß╗Öng D├ánh Cho Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mgdi1fbogft48d@resize_w900_nl.webp',4.7,325,1,4.8,100,'Mua V├íy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m V├íy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SK08','CAT09','Quß║ºn V├íy D├íng Chß╗» A','Quß║ºn V├íy D├íng Chß╗» A Mei Skirt Pants Kß║╗ Sß╗ìc Phß╗æi ─Éai Phong C├ích H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-madkfvd9lj7w09@resize_w900_nl.webp',4.7,420,1,4.8,100,'Mua V├íy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m V├íy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SK09','CAT09','Ch├ón V├íy Ngß║»n Swan Skirt','Ch├ón V├íy Ngß║»n Swan Skirt Xß║┐p Tß║ºng C├│ D├óy R├║t D├íng Xo├¿ Cho Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mbd9fec9s4w10b@resize_w900_nl.webp',4.5,118,1,4.8,100,'Mua V├íy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m V├íy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('SK10','CAT09','Ch├ón V├íy Form B├¡ Chß║Ñm Bi','Ch├ón V├íy Ngß║»n Form B├¡ Chß║Ñm Bi D├óy Buß╗Öc N╞í Dß╗à Th╞░╞íng K├¿m Quß║ºn Bß║úo Hß╗Ö','https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcbxwnm5wrid6f@resize_w900_nl.webp',4.9,112,1,4.8,100,'Mua V├íy gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m V├íy chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('T01','CAT01','├üo thun babytee thß╗â thao','├üo Thun Babytee Thß╗â Thao Jersey Soccer Hack D├íng ─É╞░ß╗¥ng Phß╗æ Cho Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7ne96vcjmiu46.webp',4.2,433,1,4.2,130,'Mua ├üo thun babytee thß╗â thao gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo thun babytee thß╗â thao chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('T02','CAT01','├üo thun babytee cß╗ò ├┤m','├üo Babytee Y2K Cß╗ò ├öm 100% Cotton Phong C├ích Streetwear 2025','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-med1e440xbev37.webp',4.7,180,1,4.7,54,'Mua ├üo thun babytee cß╗ò ├┤m gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo thun babytee cß╗ò ├┤m chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('T03','CAT01','├üo thun babytee basic','├üo Thun Baby Tee Basic 100% Cotton HOT TREND dß╗à phß╗æi ─æß╗ô','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mee3cwm3s4cgfd.webp',4.4,239,1,4.4,72,'Mua ├üo thun babytee basic gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo thun babytee basic chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('T04','CAT01','├üo thun kiß╗âu trß╗à vai','├üo Thun Sß╗ìc Trß╗à Vai ├üo Trß╗à Vai Phß╗æi D├óy Buß╗Öc N╞í Nß╗» T├¡nh H├án Quß╗æc','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-meb5j0sgmvba74.webp',4.6,446,1,4.6,134,'Mua ├üo thun kiß╗âu trß╗à vai gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo thun kiß╗âu trß╗à vai chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('T05','CAT01','├üo thun tay d├ái','├üo Thun Kß║╗ Long Sleeves Cotton Kß║╗ D├áy Dß║╖n Logo Th├¬u Unisex Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mgomv4ifjpqiab.webp',4.9,491,1,4.9,147,'Mua ├üo thun tay d├ái gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo thun tay d├ái chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('T06','CAT01','├üo thun form rß╗Öng','├üo Thun Kß║╗ 100% Cotton Stripes Tee Form Rß╗Öng Oversized Nam Nß╗»','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mdy3txnrb8qoab@resize_w900_nl.webp',4.3,314,1,4.3,94,'Mua ├üo thun form rß╗Öng gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo thun form rß╗Öng chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('T07','CAT01','├üo babytee chß║Ñm bi','├üo Babytee Phß╗æi Hoß║í Tiß║┐t Chß║Ñm Bi ├üo tay Raglan Nß╗» T├¡nh Trendy','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mjtii2dx62o585.webp',4.5,437,1,4.5,131,'Mua ├üo babytee chß║Ñm bi gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo babytee chß║Ñm bi chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('T08','CAT01','├üo Babytee Lucky Horse','├üo Babytee Lucky Horse Form Basic Ch├áo N─âm Mß╗¢i May Mß║»n 2026','https://down-vn.img.susercontent.com/file/vn-11134207-81ztc-mkdpz011e29xc7.webp',4.4,301,1,4.4,90,'Mua ├üo Babytee Lucky Horse gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Babytee Lucky Horse chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('T09','CAT01','├üo babytee ─æß╗⌐ng form','├üo Thun Babytee 3-Star Form Fit Regular Cotton 2 Chiß╗üu ─Éß╗⌐ng Form','https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfaxfknhsxze9d.webp',4.5,101,1,4.5,30,'Mua ├üo babytee ─æß╗⌐ng form gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo babytee ─æß╗⌐ng form chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.'),('T10','CAT01','├üo Baby Tee \"I Love Cat\"','├üo Baby Tee \"I Love Cat\" 100% Cotton','https://down-vn.img.susercontent.com/file/vn-11134207-7r98o-lza1i2khf9zh61.webp',4.3,302,1,4.3,91,'Mua ├üo Baby Tee \"I Love Cat\" gi├í tß╗æt tß║íi NTK Fashion','Sß║ún phß║⌐m ├üo Baby Tee \"I Love Cat\" chß║Ñt l╞░ß╗úng cao, thiß║┐t kß║┐ chuß║⌐n, giao h├áng nhanh.');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_likes`
--

DROP TABLE IF EXISTS `review_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `review_likes` (
  `like_id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` char(5) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`like_id`),
  UNIQUE KEY `unique_user_review_like` (`user_id`,`review_id`),
  KEY `fk_like_review` (`review_id`),
  CONSTRAINT `fk_like_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_like_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_likes`
--

LOCK TABLES `review_likes` WRITE;
/*!40000 ALTER TABLE `review_likes` DISABLE KEYS */;
INSERT INTO `review_likes` VALUES (2,11,'U4937','2026-05-24 00:16:57');
/*!40000 ALTER TABLE `review_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` char(5) DEFAULT NULL,
  `product_id` char(5) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `rating` float DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_pinned` tinyint(1) DEFAULT 0,
  `reward_coupon_id` char(5) DEFAULT NULL,
  PRIMARY KEY (`review_id`),
  KEY `fk_rev_user` (`user_id`),
  KEY `fk_rev_prod` (`product_id`),
  KEY `fk_review_parent` (`parent_id`),
  CONSTRAINT `fk_review_parent` FOREIGN KEY (`parent_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,'U01','T01',NULL,4.2,'├üo rß║Ñt ─æß║╣p, chß║Ñt vß║úi co gi├ún tß╗æt!',NULL,0,'2025-01-01 00:00:00',0,NULL),(2,'U02','T02',NULL,4.1,'Chß║Ñt vß║úi d├áy dß║╖n, ß║Ñm ├íp.',NULL,0,'2024-06-30 00:00:00',0,NULL),(3,'U03','T03',NULL,4.2,'Mß║╖c rß║Ñt t├┤n d├íng.',NULL,0,'2024-06-07 00:00:00',0,NULL),(4,'U04','J01',NULL,4.9,'H├áng nh╞░ ß║únh, giao h├áng nhanh.',NULL,0,'2025-02-01 00:00:00',0,NULL),(5,'U05','J02',NULL,4.1,'Mß╗ìi ng╞░ß╗¥i n├¬n mua nh├⌐!',NULL,0,'2024-11-11 00:00:00',0,NULL),(6,'U06','J03',NULL,4.1,'Tuyß╗çt vß╗¥i, phß║úi ß╗ºng hß╗Ö th╞░ß╗¥ng xuy├¬n.',NULL,0,'2025-12-06 00:00:00',0,NULL),(7,'U07','H01',NULL,4.7,'H├áng ─æß║╣p m├á gi├í lß║íi phß║úi ch─âng.',NULL,0,'2025-01-01 00:00:00',0,NULL),(8,'U08','H02',NULL,4.9,'Nh├ón vi├¬n t╞░ vß║Ñn nhiß╗çt t├¼nh, giao h├áng nhanh, m├¼n',NULL,0,'2024-06-30 00:00:00',0,NULL),(9,'U09','H03',NULL,4.9,'Shop kh├┤ng bao giß╗¥ l├ám m├¼nh thß║Ñt vß╗ìng.',NULL,0,'2024-06-07 00:00:00',0,NULL),(10,'U10','H04',NULL,5,'─É├│ng g├│i chuy├¬n nghiß╗çp, chß║Ñt vß║úi xß╗ïn x├▓.',NULL,1,'2025-02-01 00:00:00',0,NULL),(11,'U4937','C01',NULL,5,'├üo ─æß║╣p, chß║Ñt vß║úi d├áy dß║╖n','assets/uploads/reviews/review_1779556598_424b40b74b.png',1,'2026-05-24 00:16:38',0,NULL),(12,'U4937','C01',NULL,5,'Kh├┤ng c├│ g├¼ ─æß╗â ch├¬!',NULL,1,'2026-05-24 00:17:25',0,NULL),(13,'U01','C01',12,NULL,'Cß║úm ╞ín kh├ích iu ─æ├ú ß╗ºng hß╗Ö. Shop mong ─æ╞░ß╗úc phß╗Ñc vß╗Ñ kh├ích iu trong nhß╗»ng lß║ºn mua h├áng tiß║┐p theo ß║í <333',NULL,1,'2026-05-24 00:18:58',0,NULL),(14,'U4937','J02',NULL,5,'xinh nha b├á','assets/uploads/reviews/review_1779865357_575690136a.png',1,'2026-05-27 14:02:37',0,NULL);
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_methods`
--

DROP TABLE IF EXISTS `shipping_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_methods` (
  `shipping_method_id` char(5) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT NULL,
  `estimated_delivery` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`shipping_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_methods`
--

LOCK TABLES `shipping_methods` WRITE;
/*!40000 ALTER TABLE `shipping_methods` DISABLE KEYS */;
INSERT INTO `shipping_methods` VALUES ('S01','SPX',35000.00,'2-3 ng├áy'),('S02','GHN',40000.00,'2-4 ng├áy'),('S03','GHTK',25000.00,'3-5 ng├áy'),('S04','J&T',30000.00,'1-2 ng├áy');
/*!40000 ALTER TABLE `shipping_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_addresses` (
  `address_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` char(5) NOT NULL,
  `recipient_name` varchar(100) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `street` varchar(255) NOT NULL DEFAULT '',
  `ward` varchar(100) NOT NULL DEFAULT '',
  `district` varchar(100) NOT NULL DEFAULT '',
  `province` varchar(100) NOT NULL DEFAULT '',
  `note` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`address_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_addresses`
--

LOCK TABLES `user_addresses` WRITE;
/*!40000 ALTER TABLE `user_addresses` DISABLE KEYS */;
INSERT INTO `user_addresses` VALUES (1,'U01','Quß║ún Trß╗ï Vi├¬n','334275834','Kho tß╗òng HCM','','','',NULL,1,'2026-04-19 20:51:06'),(2,'U02','Nguyß╗àn V─ân A','375788987','123 L├¬ Lß╗úi, Q1, HCM','','','',NULL,1,'2026-04-19 20:51:06'),(3,'U03','Trß║ºn Thß╗ï B','964326512','45 Cß║ºu Giß║Ñy, H├á Nß╗Öi','','','',NULL,1,'2026-04-19 20:51:06'),(4,'U04','L├¬ Thß╗ï C','901239876','10 Nguyß╗àn Tr├úi, Q5','','','',NULL,1,'2026-04-19 20:51:06'),(5,'U05','Ho├áng Long','987654321','15 L├¬ Duß║⌐n, ─É├á Nß║╡ng','','','',NULL,1,'2026-04-19 20:51:06'),(6,'U06','Nguyß╗àn Thanh Th├║y','912345678','88 Nguyß╗àn Huß╗ç, Q1, HCM','','','',NULL,1,'2026-04-19 20:51:06'),(7,'U07','Phß║ím Minh Qu├ón','905112233','12 Trß║ºn Ph├║, Hß║úi Ph├▓ng','','','',NULL,1,'2026-04-19 20:51:06'),(8,'U08','V├╡ Kiß╗üu Oanh','934556677','200 Phan Chu Trinh, Huß║┐','','','',NULL,1,'2026-04-19 20:51:06'),(9,'U09','─Éß╗ù ─Éß╗⌐c Anh','977889900','45 L├íng Hß║í, ─Éß╗æng ─Éa, H├á Nß╗Öi','','','',NULL,1,'2026-04-19 20:51:06'),(10,'U10','B├╣i Thß╗ºy Ti├¬n','966554433','77 C├ích Mß║íng Th├íng 8, Cß║ºn Th╞í','','','',NULL,1,'2026-04-19 20:51:06'),(11,'U11','Ng├┤ Xu├ón B├ích','944332211','102 Quang Trung, G├▓ Vß║Ñp, HCM','','','',NULL,1,'2026-04-19 20:51:06'),(12,'U12','Nguyß╗àn Thu H├á','922110099','56 Kim M├ú, Ba ─É├¼nh, H├á Nß╗Öi','','','',NULL,1,'2026-04-19 20:51:06'),(13,'U13','Trß║ºn Gia Huy','909123456','32 H├╣ng V╞░╞íng, Nha Trang','','','',NULL,1,'2026-04-19 20:51:06'),(14,'U14','─Éß║╖ng Mß╗╣ Linh','988776655','120 V├╡ V─ân Kiß╗çt, Q5, HCM','','','',NULL,1,'2026-04-19 20:51:06'),(15,'U15','Phan Quß╗æc Bß║úo','911223344','15 H├▓a B├¼nh, Bi├¬n H├▓a','','','',NULL,1,'2026-04-19 20:51:06'),(16,'U16','L├╜ Cß║⌐m T├║','933445566','09 L├¬ Lß╗úi, TP Vinh','','','',NULL,1,'2026-04-19 20:51:06'),(17,'U17','V┼⌐ Nhß║¡t Minh','955667788','22 ─Éiß╗çn Bi├¬n Phß╗º, ─É├á Nß║╡ng','','','',NULL,1,'2026-04-19 20:51:06'),(18,'U18','Chu Ph╞░╞íng Thß║úo','977112233','412 Tr╞░ß╗¥ng Chinh, T├ón B├¼nh, HCM','','','',NULL,1,'2026-04-19 20:51:06'),(19,'U19','L├¬ Huß╗│nh Anh','900223344','89 Nguyß╗àn Tr├úi, Thanh Xu├ón, HN','','','',NULL,1,'2026-04-19 20:51:06'),(20,'U20','L├óm Khß║úi Minh','335378609','Trß║ºn ─Éß║íi Ngh─⌐a, D─⌐ An, B├¼nh D╞░╞íng','','','',NULL,1,'2026-04-19 20:51:06'),(32,'U3237','Tram Nguyen','0373546431','Ph╞░ß╗¥ng D─⌐ An Th├ánh phß╗æ Hß╗ô Ch├¡ Minh','Linh ─É├┤ng','Thß╗º ─Éß╗⌐c','Hß╗ô Ch├¡ Minh',NULL,0,'2026-04-19 20:57:19'),(33,'U5872','lau','0329848845','x├│m v╞░ß╗¥n ╞░╞ím','Ia Yok','Ia Grai','Gia Lai',NULL,1,'2026-04-28 22:00:19'),(34,'U4937','nghi','0938211589','ktx khu b d─⌐ an b├¼nh d╞░╞íng','─É├┤ng H├▓a','D─⌐ An','B├¼nh D╞░╞íng',NULL,1,'2026-05-26 22:55:00');
/*!40000 ALTER TABLE `user_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `wallet_balance` decimal(15,2) DEFAULT 0.00,
  `current_points` int(11) DEFAULT 0,
  `accumulated_points` int(11) DEFAULT 0,
  `tier` varchar(20) DEFAULT 'Member',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('U01','admin','0192023a7bbd73250516f069df18b500','Quß║ún Trß╗ï Vi├¬n','admin@ntk.vn','334275834','Kho tß╗òng HCM',NULL,NULL,1,1,1,'2024-01-01 00:00:00',0,0.00,'Vietcombank','1012233445','QUAN TRI VIEN',150000.00,0,0,'Member'),('U02','nguyenvana','pass123','Nguyß╗àn V─ân A','ana@gmail.com','375788987','123 L├¬ Lß╗úi, Q1, HCM',NULL,NULL,0,1,1,'2024-01-15 00:00:00',5,2500000.00,'MB Bank','987654321','NGUYEN VAN A',50000.00,0,0,'Member'),('U03','tranthib','pass123','Trß║ºn Thß╗ï B','bib@gmail.com','964326512','45 Cß║ºu Giß║Ñy, H├á Nß╗Öi',NULL,NULL,0,0,1,'2024-02-10 00:00:00',2,850000.00,'Techcombank','19033445566','TRAN THI B',0.00,0,0,'Member'),('U04','lethic','pass123','L├¬ Thß╗ï C','cic@gmail.com','901239876','10 Nguyß╗àn Tr├úi, Q5',NULL,NULL,0,1,1,'2024-03-05 00:00:00',0,0.00,'VietinBank','1028877665','LE THI C',250000.00,0,0,'Member'),('U05','hoanglong','pass123','Ho├áng Long','longh@gmail.com','987654321','15 L├¬ Duß║⌐n, ─É├á Nß║╡ng',NULL,NULL,0,1,1,'2024-03-20 00:00:00',12,15000000.00,'BIDV','21510001234','HOANG LONG',1000000.00,0,0,'Member'),('U06','thanhthuy','pass123','Nguyß╗àn Thanh Th├║y','thuynt@gmail.com','912345678','88 Nguyß╗àn Huß╗ç, Q1, HCM',NULL,NULL,0,0,1,'2024-04-12 00:00:00',1,450000.00,'ACB','77889955','NGUYEN THANH THUY',0.00,0,0,'Member'),('U07','minhquan','pass123','Phß║ím Minh Qu├ón','quanpm@gmail.com','905112233','12 Trß║ºn Ph├║, Hß║úi Ph├▓ng',NULL,NULL,0,1,1,'2024-05-01 00:00:00',8,6200000.00,'TPBank','4455667701','PHAM MINH QUAN',120000.00,0,0,'Member'),('U08','kieuoanh','pass123','V├╡ Kiß╗üu Oanh','oanhvk@gmail.com','934556677','200 Phan Chu Trinh, Huß║┐',NULL,NULL,0,1,0,'2024-05-18 00:00:00',0,0.00,'Sacombank','601223344','VO KIEU OANH',0.00,0,0,'Member'),('U09','ducanh','pass123','─Éß╗ù ─Éß╗⌐c Anh','anhdd@gmail.com','977889900','45 L├íng Hß║í, ─Éß╗æng ─Éa, H├á Nß╗Öi',NULL,NULL,0,1,1,'2024-06-02 00:00:00',3,1200000.00,'Agribank','15002051234','DO DUC ANH',30000.00,0,0,'Member'),('U10','thuytien','pass123','B├╣i Thß╗ºy Ti├¬n','tienbt@gmail.com','966554433','77 C├ích Mß║íng Th├íng 8, Cß║ºn Th╞í',NULL,NULL,0,0,1,'2024-06-25 00:00:00',15,22000000.00,'VPBank','155667788','BUI THUY TIEN',500000.00,0,0,'Member'),('U11','xuanbach','pass123','Ng├┤ Xu├ón B├ích','bachnx@gmail.com','944332211','102 Quang Trung, G├▓ Vß║Ñp, HCM',NULL,NULL,0,1,1,'2024-07-10 00:00:00',4,3100000.00,'HDBank','6870407123','NGO XUAN BACH',0.00,0,0,'Member'),('U12','thuha','pass123','Nguyß╗àn Thu H├á','hant@gmail.com','922110099','56 Kim M├ú, Ba ─É├¼nh, H├á Nß╗Öi',NULL,NULL,0,1,1,'2024-07-30 00:00:00',7,5400000.00,'VIB','257040655','NGUYEN THU HA',85000.00,0,0,'Member'),('U13','giahuy','pass123','Trß║ºn Gia Huy','huytg@gmail.com','909123456','32 H├╣ng V╞░╞íng, Nha Trang',NULL,NULL,0,0,1,'2024-08-14 00:00:00',0,0.00,'SHB','1011223344','TRAN GIA HUY',0.00,0,0,'Member'),('U14','mylinh','pass123','─Éß║╖ng Mß╗╣ Linh','linhdm@gmail.com','988776655','120 V├╡ V─ân Kiß╗çt, Q5, HCM',NULL,NULL,0,1,1,'2024-09-05 00:00:00',2,980000.00,'VietCapitalBank','8007041234','DANG MY LINH',200000.00,0,0,'Member'),('U15','quocbao','pass123','Phan Quß╗æc Bß║úo','baopq@gmail.com','911223344','15 H├▓a B├¼nh, Bi├¬n H├▓a',NULL,NULL,0,1,0,'2024-09-21 00:00:00',0,0.00,'MSB','3501017788','PHAN QUOC BAO',0.00,0,0,'Member'),('U16','camtu','pass123','L├╜ Cß║⌐m T├║','tulc@gmail.com','933445566','09 L├¬ Lß╗úi, TP Vinh',NULL,NULL,0,1,1,'2024-10-08 00:00:00',6,4200000.00,'SeABank','123456','LY CAM TU',450000.00,0,0,'Member'),('U17','nhatminh','pass123','V┼⌐ Nhß║¡t Minh','minhvn@gmail.com','955667788','22 ─Éiß╗çn Bi├¬n Phß╗º, ─É├á Nß║╡ng',NULL,NULL,0,1,1,'2024-10-25 00:00:00',10,8900000.00,'OCB','41000123','VU NHAT MINH',15000.00,0,0,'Member'),('U18','phuongthao','pass123','Chu Ph╞░╞íng Thß║úo','thaocp@gmail.com','977112233','412 Tr╞░ß╗¥ng Chinh, T├ón B├¼nh, HCM',NULL,NULL,0,1,1,'2024-11-12 00:00:00',3,1150000.00,'LienVietPostBank','223344556','CHU PHUONG THAO',0.00,0,0,'Member'),('U19','huynhanh','pass123','L├¬ Huß╗│nh Anh','anhlh@gmail.com','900223344','89 Nguyß╗àn Tr├úi, Thanh Xu├ón, HN',NULL,NULL,0,1,1,'2024-11-30 00:00:00',5,2750000.00,'Nam A Bank','3010223344','LE HUYNH ANH',75000.00,0,0,'Member'),('U20','lamminh','pass123','L├óm Khß║úi Minh','lminh@gmail.com','335378609','Trß║ºn ─Éß║íi Ngh─⌐a, D─⌐ An, B├¼nh D╞░╞íng',NULL,NULL,1,0,1,'2024-11-30 00:00:00',3,400000.00,'Eximbank','20001484123','LAM KHAI MINH',0.00,0,0,'Member'),('U3237','nguyenthithuytram03062006gl@gmail.com','8de7d4ce14a6925213c332d32906b880','Tram Nguyen','nguyenthithuytram03062006gl@gmail.com','0373546431',NULL,NULL,NULL,1,0,1,'2026-04-08 16:11:12',0,0.00,NULL,NULL,NULL,1025000.00,0,0,'Member'),('U3768','test@gmail.com','482c811da5d5b4bc6d497ffa98491e38','Test User','test@gmail.com','0900000001',NULL,'3192',NULL,0,0,1,'2026-04-19 20:20:34',0,0.00,NULL,NULL,NULL,0.00,0,0,'Member'),('U4937','tnighue@gmail.com','827ccb0eea8a706c4c34a16891f84e7b','nghi','tnighue@gmail.com','0938211589',NULL,NULL,NULL,1,0,1,'2026-04-29 19:49:48',0,0.00,NULL,NULL,NULL,0.00,240,240,'Member'),('U5655','test@test.com','25d55ad283aa400af464c76d713c07ad','Tester','test@test.com','0123456789',NULL,'2663',NULL,0,0,1,'2026-04-19 20:27:01',0,0.00,NULL,NULL,NULL,0.00,0,0,'Member'),('U5872','phamlau488@gmail.com','827ccb0eea8a706c4c34a16891f84e7b','lau','phamlau488@gmail.com','0329848845',NULL,NULL,NULL,1,0,1,'2026-04-28 21:34:09',0,0.00,NULL,NULL,NULL,0.00,0,0,'Member');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallet_transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` char(5) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `related_order_id` char(5) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `fk_wt_user` (`user_id`),
  KEY `fk_wt_order` (`related_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
INSERT INTO `wallet_transactions` VALUES (1,'U01',150000.00,1,'Ho├án tiß╗ün ─æ╞ín DH002','ORD02','2026-02-26 14:30:00'),(2,'U01',50000.00,2,'Sß╗¡ dß╗Ñng v├¡ thanh to├ín ─æ╞ín DH005','ORD05','2026-03-01 09:15:00'),(3,'U01',50000.00,1,'Th╞░ß╗ƒng hß║íng th├ánh vi├¬n V├áng',NULL,'2026-03-10 20:00:00'),(4,'U02',50000.00,1,'Ho├án tiß╗ün do lß╗ùi vß║¡n chuyß╗ân','ORD01','2026-03-15 10:20:00'),(5,'U03',100000.00,1,'Tß║╖ng tiß╗ün ─æ─âng k├╜ t├ái khoß║ún mß╗¢i',NULL,'2026-01-05 08:00:00'),(6,'U03',100000.00,2,'Sß╗¡ dß╗Ñng v├¡ thanh to├ín ─æ╞ín ORD02','ORD02','2026-01-15 09:00:00'),(7,'U04',250000.00,1,'Ho├án tiß╗ün ─æ╞ín h├áng kh├ích trß║ú lß║íi',NULL,'2026-03-10 14:00:00'),(8,'U05',1000000.00,1,'Th╞░ß╗ƒng kh├ích h├áng mua sß╗ë th├íng 3',NULL,'2026-04-01 08:00:00'),(9,'U06',50000.00,1,'Ho├án tiß╗ün ─æ├ính gi├í sß║ún phß║⌐m',NULL,'2026-02-10 11:00:00'),(10,'U06',50000.00,2,'Thanh to├ín mß╗Öt phß║ºn ─æ╞ín ORD04','ORD04','2026-02-15 15:30:00'),(11,'U07',120000.00,1,'Ho├án tiß╗ün ch├¬nh lß╗çch ph├¡ ship',NULL,'2026-03-20 16:45:00'),(12,'U08',30000.00,1,'Qu├á tß║╖ng sinh nhß║¡t th├íng 2',NULL,'2026-02-05 07:00:00'),(13,'U08',30000.00,2,'Thanh to├ín ph├¡ ship ─æ╞ín ORD05','ORD05','2026-02-10 09:30:00'),(14,'U09',30000.00,1,'Ho├án tiß╗ün ─æ├ính gi├í 5 sao c├│ t├óm',NULL,'2026-03-01 19:20:00'),(15,'U10',500000.00,1,'Ho├án tiß╗ün bß╗ôi th╞░ß╗¥ng sß║ún phß║⌐m lß╗ùi',NULL,'2026-03-25 10:15:00'),(16,'U11',20000.00,1,'Th╞░ß╗ƒng tham gia Minigame Facebook',NULL,'2026-02-10 21:00:00'),(17,'U11',20000.00,2,'Sß╗¡ dß╗Ñng v├¡ thanh to├ín ─æ╞ín ORD07','ORD07','2026-02-14 10:00:00'),(18,'U12',85000.00,1,'Ho├án tiß╗ün do kh├ích hß╗ºy ─æ╞ín h├áng',NULL,'2026-04-05 13:40:00'),(19,'U13',100000.00,1,'Qu├á tß║╖ng kh├ích h├áng mß╗¢i',NULL,'2026-01-20 09:00:00'),(20,'U13',100000.00,2,'Sß╗¡ dß╗Ñng v├¡ thanh to├ín ─æ╞ín ORD08','ORD08','2026-02-18 14:20:00'),(21,'U14',200000.00,1,'Ho├án tiß╗ün ch╞░╞íng tr├¼nh Flash Sale',NULL,'2026-03-30 22:00:00'),(22,'U15',50000.00,1,'Ho├án tiß╗ün ph├¡ vß║¡n chuyß╗ân',NULL,'2026-02-15 16:10:00'),(23,'U15',50000.00,2,'Sß╗¡ dß╗Ñng v├¡ thanh to├ín ─æ╞ín ORD09','ORD09','2026-02-20 11:45:00'),(24,'U16',450000.00,1,'Ho├án tiß╗ün ─æß╗òi trß║ú do nhß║ºm size',NULL,'2026-04-02 08:30:00'),(25,'U17',15000.00,1,'Ho├án tiß╗ün ─æ├ính gi├í c├│ k├¿m h├¼nh ß║únh',NULL,'2026-03-12 20:15:00'),(26,'U18',40000.00,1,'Quy ─æß╗òi voucher th├ánh tiß╗ün mß║╖t',NULL,'2026-02-20 09:00:00'),(27,'U18',40000.00,2,'Sß╗¡ dß╗Ñng v├¡ thanh to├ín ─æ╞ín ORD10','ORD10','2026-02-25 15:00:00'),(28,'U19',75000.00,1,'Ho├án tiß╗ün xin lß╗ùi do giao h├áng trß╗à',NULL,'2026-03-28 17:30:00'),(29,'U20',25000.00,1,'Th╞░ß╗ƒng hoa hß╗ông giß╗¢i thiß╗çu bß║ín b├¿',NULL,'2026-04-01 10:00:00'),(30,'U20',25000.00,2,'R├║t tiß╗ün vß╗ü thß║╗ ng├ón h├áng',NULL,'2026-04-05 18:00:00'),(31,'U3237',205000.00,1,'Ho├án tiß╗ün do hß╗ºy ─æ╞ín h├áng','O0016','2026-04-20 22:17:59'),(32,'U3237',205000.00,1,'Ho├án tiß╗ün do trß║ú h├áng (Refund)','O0015','2026-04-20 22:25:56'),(33,'U3237',205000.00,1,'Ho├án tiß╗ün do trß║ú h├áng (Refund)','O0011','2026-04-20 22:31:40'),(34,'U3237',205000.00,1,'Ho├án tiß╗ün do trß║ú h├áng (Refund)','O0013','2026-04-20 23:16:00'),(35,'U3237',290000.00,2,'Sß╗¡ dß╗Ñng v├¡ thanh to├ín ─æ╞ín h├áng O0022','O0022','2026-04-22 08:36:00'),(36,'U3237',290000.00,1,'Ho├án tiß╗ün do hß╗ºy ─æ╞ín h├áng','O0022','2026-04-22 08:36:42'),(37,'U3237',205000.00,1,'Ho├án tiß╗ün do hß╗ºy ─æ╞ín h├áng #O0008','O0008','2026-05-24 14:05:09');
/*!40000 ALTER TABLE `wallet_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `wishlist_id` char(5) NOT NULL,
  `user_id` char(5) DEFAULT NULL,
  `product_id` char(5) DEFAULT NULL,
  `added_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`wishlist_id`),
  KEY `fk_wish_user` (`user_id`),
  KEY `fk_wish_prod` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES ('W01','U01','T01','2025-01-01 00:00:00'),('W02','U02','T02','2024-06-30 00:00:00'),('W03','U03','T03','2024-06-07 00:00:00'),('W04','U04','T04','2025-02-01 00:00:00'),('W05','U05','T05','2024-11-11 00:00:00'),('W06','U06','T06','2025-12-06 00:00:00'),('W144','U3237','C02','2026-04-19 19:49:41'),('W415','U01','C02','2026-05-23 14:02:40'),('W478','U3237','T09','2026-04-19 19:49:50'),('W567','U4937','C04','2026-05-21 08:18:56'),('W579','U01','C03','2026-05-23 14:05:33'),('W636','U01','C01','2026-05-23 14:02:26'),('W639','U4937','C01','2026-05-23 20:59:10'),('W684','U3237','T08','2026-04-19 19:50:51'),('W897','U3237','C03','2026-04-19 19:49:44');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
CREATE TABLE IF NOT EXISTS `recent_views` (
  `product_id` CHAR(5) NOT NULL,
  `viewed_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `view_count` INT DEFAULT 0;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-27 14:26:59

ALTER TABLE products ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0;
UPDATE products 
SET view_count = (sold_count * 3) + FLOOR(10 + (RAND() * 40)) 
WHERE view_count <= sold_count AND sold_count > 0;

ALTER TABLE coupons ADD user_id VARCHAR(50);