<?php
/**
 * run_reviews_migration.php
 * Chạy 1 lần để thêm cột image & video vào bảng reviews
 * Truy cập: https://ntkfashion.me/run_reviews_migration.php
 */
require_once 'config/database.php';

$sqls = [
    "ALTER TABLE `reviews` ADD COLUMN IF NOT EXISTS `image` VARCHAR(500) NULL DEFAULT NULL AFTER `comment`" => "reviews.image",
    "ALTER TABLE `reviews` ADD COLUMN IF NOT EXISTS `video` VARCHAR(500) NULL DEFAULT NULL AFTER `image`"  => "reviews.video",
    "ALTER TABLE `reviews` ADD COLUMN IF NOT EXISTS `parent_id` INT(11) DEFAULT NULL"                      => "reviews.parent_id",
];

echo "<pre>\n=== Migration: reviews table ===\n\n";
foreach ($sqls as $sql => $desc) {
    try {
        $conn->exec($sql);
        echo "[OK]   $desc\n";
    } catch (PDOException $e) {
        echo "[SKIP] $desc => " . $e->getMessage() . "\n";
    }
}

// Tạo thư mục upload nếu chưa có
$uploadDir = __DIR__ . '/assets/uploads/reviews/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "[OK]   Created upload dir: assets/uploads/reviews/\n";
} else {
    echo "[SKIP] Upload dir already exists\n";
}

// Xác nhận cấu trúc bảng
echo "\n=== Cau truc bang reviews ===\n";
$stmt = $conn->query("DESCRIBE `reviews`");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo "  {$col['Field']}  ({$col['Type']})  default={$col['Default']}\n";
}

echo "\n=== Migration hoan thanh! ===\n";
echo "XOA FILE NAY KHOI SERVER SAU KHI CHAY XONG!\n</pre>";
?>
