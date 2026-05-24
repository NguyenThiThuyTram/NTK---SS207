<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';

echo "Debugging DB queries...<br>";

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $product_id = 'T08';
    
    // Test reviews query
    $sql_reviews = "SELECT r.*, u.fullname, u.role,
                           (SELECT COUNT(*) FROM review_likes WHERE review_id = r.review_id) as total_likes
                    FROM reviews r 
                    LEFT JOIN users u ON r.user_id = u.user_id 
                    WHERE r.product_id = :prod_id AND r.parent_id IS NULL 
                    ORDER BY r.created_at DESC";
    $stmt_rev = $conn->prepare($sql_reviews);
    $stmt_rev->bindParam(':prod_id', $product_id);
    $stmt_rev->execute();
    echo "Query 1 (reviews) OK!<br>";
} catch (Exception $e) {
    echo "Error 1: " . $e->getMessage() . "<br>";
}

try {
    $sql_prod = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 JOIN categories c ON p.category_id = c.category_id 
                 WHERE p.product_id = :id";
    $stmt = $conn->prepare($sql_prod);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    echo "Query 2 (products) OK!<br>";
} catch (Exception $e) {
    echo "Error 2: " . $e->getMessage() . "<br>";
}

try {
    $sql_variants = "SELECT * FROM product_variants WHERE product_id = :id AND is_active = 1";
    $stmt_v = $conn->prepare($sql_variants);
    $stmt_v->bindParam(':id', $product_id);
    $stmt_v->execute();
    echo "Query 3 (variants) OK!<br>";
} catch (Exception $e) {
    echo "Error 3: " . $e->getMessage() . "<br>";
}

// Lấy danh sách cột của reviews, users, orders để biết schema hiện tại
$tables = ['reviews', 'users', 'orders', 'review_likes', 'product_variants', 'order_details'];
foreach ($tables as $t) {
    try {
        $stmt = $conn->query("SHOW COLUMNS FROM $t");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<br><b>Schema for $t:</b> ";
        $colNames = array_column($cols, 'Field');
        echo implode(", ", $colNames);
    } catch (Exception $e) {
        echo "<br><b>Schema for $t:</b> Error " . $e->getMessage();
    }
}
?>
