<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET FOREIGN_KEY_CHECKS=0;");

    $sqls = [
        "DROP TABLE IF EXISTS review_likes",
        "DROP TABLE IF EXISTS reviews",
        "CREATE TABLE `reviews` (
          `review_id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` char(5) NOT NULL,
          `product_id` char(5) NOT NULL,
          `parent_id` int(11) DEFAULT NULL,
          `rating` tinyint(1) DEFAULT NULL,
          `comment` text NOT NULL,
          `image` varchar(255) DEFAULT NULL,
          `status` tinyint(1) DEFAULT 1,
          `created_at` datetime DEFAULT current_timestamp(),
          PRIMARY KEY (`review_id`),
          KEY `fk_review_user` (`user_id`),
          KEY `fk_review_product` (`product_id`),
          KEY `fk_review_parent` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE `review_likes` (
          `like_id` int(11) NOT NULL AUTO_INCREMENT,
          `review_id` int(11) NOT NULL,
          `user_id` char(5) NOT NULL,
          `created_at` datetime DEFAULT current_timestamp(),
          PRIMARY KEY (`like_id`),
          UNIQUE KEY `unique_user_review_like` (`user_id`,`review_id`),
          KEY `fk_like_review` (`review_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    ];

    foreach ($sqls as $sql) {
        $conn->exec($sql);
        echo "Executed: " . substr($sql, 0, 50) . "...<br>";
    }

    $conn->exec("SET FOREIGN_KEY_CHECKS=1;");
    echo "<h1>SUCCESS: Recreated reviews & review_likes tables!</h1>";

} catch (Exception $e) {
    echo "<h1>ERROR: " . $e->getMessage() . "</h1>";
}
?>
