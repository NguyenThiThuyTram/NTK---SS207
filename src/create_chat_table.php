<?php
require_once 'config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `chat_messages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `sender_id` int(11) NOT NULL,
      `receiver_id` int(11) DEFAULT NULL, -- NULL means Admin if sender is user, or user_id if sender is Admin
      `message` text NOT NULL,
      `is_read` tinyint(1) DEFAULT 0,
      `created_at` timestamp NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $conn->exec($sql);
    echo "Table 'chat_messages' created successfully.";
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
