<?php
require_once 'config/database.php';

try {
    // Determine the type of review_id in reviews table
    $stmt = $conn->query("SHOW COLUMNS FROM reviews WHERE Field = 'review_id'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    $review_id_type = $col['Type'] ?? 'int(11)';

    $sqls = [
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancel_reason VARCHAR(500) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancel_requested_at DATETIME DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS return_reason VARCHAR(500) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS return_image VARCHAR(500) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS return_requested_at DATETIME DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_failed_at DATETIME DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS admin_note VARCHAR(500) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE order_returns ADD COLUMN IF NOT EXISTS admin_note VARCHAR(500) DEFAULT NULL",
        "CREATE TABLE IF NOT EXISTS notifications (
            noti_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id CHAR(5) DEFAULT NULL,
            type VARCHAR(50) DEFAULT 'system',
            title VARCHAR(200) NOT NULL,
            message VARCHAR(500) NOT NULL,
            related_order_id CHAR(5) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (noti_id),
            KEY idx_noti_user (user_id),
            KEY idx_noti_order (related_order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS review_likes (
            like_id INT(11) NOT NULL AUTO_INCREMENT,
            review_id {$review_id_type} NOT NULL,
            user_id CHAR(5) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (like_id),
            UNIQUE KEY unique_user_review_like (user_id, review_id),
            KEY fk_like_review (review_id),
            CONSTRAINT fk_like_review FOREIGN KEY (review_id) REFERENCES reviews (review_id) ON DELETE CASCADE,
            CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    ];

    foreach ($sqls as $sql) {
        try {
            $conn->exec($sql);
            echo "OK: <br>" . htmlspecialchars(substr($sql, 0, 100)) . "...<br><br>";
        } catch (Exception $e) {
            echo "ERR: " . htmlspecialchars($e->getMessage()) . " (" . htmlspecialchars(substr($sql, 0, 50)) . ")<br><br>";
        }
    }
    echo "DONE!";
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
