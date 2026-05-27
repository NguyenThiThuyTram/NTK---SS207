<?php
require_once 'config/database.php';

try {
    // Drop existing table first
    $conn->exec("DROP TABLE IF EXISTS `chat_messages`");
    
    // Create chat_messages table using utf8mb4_general_ci collation to match the users table collation
    $sql = "CREATE TABLE `chat_messages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `sender_id` char(5) NOT NULL,
      `receiver_id` char(5) DEFAULT '0',
      `message` text NOT NULL,
      `is_read` tinyint(1) DEFAULT 0,
      `created_at` timestamp NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $conn->exec($sql);
    echo "Table 'chat_messages' recreated successfully with CHAR(5) columns and utf8mb4_general_ci collation.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
