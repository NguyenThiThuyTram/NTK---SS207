-- ============================================================
-- MIGRATION: Thêm cột image và video vào bảng reviews
-- Chạy file này trên server hosting (phpMyAdmin hoặc SSH)
-- Safe: Dùng IF NOT EXISTS để không lỗi nếu cột đã tồn tại
-- ============================================================

-- Thêm cột image (lưu đường dẫn ảnh đánh giá)
ALTER TABLE `reviews`
    ADD COLUMN IF NOT EXISTS `image` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Đường dẫn ảnh đính kèm đánh giá' AFTER `comment`;

-- Thêm cột video (lưu đường dẫn video đánh giá)  
ALTER TABLE `reviews`
    ADD COLUMN IF NOT EXISTS `video` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Đường dẫn video đính kèm đánh giá' AFTER `image`;

-- Tạo thư mục upload nếu chưa có (chạy trên server)
-- mkdir -p /path/to/website/src/assets/uploads/reviews/

-- Xác nhận cấu trúc bảng sau migration
DESCRIBE `reviews`;
