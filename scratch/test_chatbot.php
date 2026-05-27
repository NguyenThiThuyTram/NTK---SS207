<?php
require_once '../src/config/database.php';

try {
    echo "=== Running Fallback Query ===\n";
    $sql_fallback = "SELECT p.*, MIN(v.original_price) as original_price, MIN(v.sale_price) as sale_price,
                            GROUP_CONCAT(DISTINCT v.size SEPARATOR ', ') as available_sizes,
                            SUM(v.stock) as total_stock
                    FROM products p 
                    LEFT JOIN product_variants v ON p.product_id = v.product_id 
                    WHERE p.status = 1 
                      AND (p.name LIKE '%áo%' OR p.description LIKE '%áo%' OR p.name LIKE '%babytee%')
                      AND (v.original_price > 0) 
                      AND (COALESCE(NULLIF(v.sale_price, 0), v.original_price) < 400000) 
                    GROUP BY p.product_id 
                    ORDER BY p.sold_count DESC 
                    LIMIT 3";
                    
    $stmt_fallback = $conn->prepare($sql_fallback);
    $stmt_fallback->execute();
    $products = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
    echo "Count fallback: " . count($products) . "\n";
    print_r($products);

    echo "\n=== Running Backup Query ===\n";
    $stmt_backup = $conn->query("SELECT p.*, MIN(v.original_price) as original_price, MIN(v.sale_price) as sale_price, GROUP_CONCAT(DISTINCT v.size SEPARATOR ', ') as available_sizes, SUM(v.stock) as total_stock FROM products p LEFT JOIN product_variants v ON p.product_id = v.product_id WHERE p.status = 1 AND v.original_price > 0 GROUP BY p.product_id ORDER BY p.sold_count DESC LIMIT 3");
    $backup_products = $stmt_backup->fetchAll(PDO::FETCH_ASSOC);
    echo "Count backup: " . count($backup_products) . "\n";
    print_r($backup_products);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
