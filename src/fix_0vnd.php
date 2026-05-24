<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Find products without variants
    $stmt = $conn->query("
        SELECT p.product_id 
        FROM products p 
        LEFT JOIN product_variants v ON p.product_id = v.product_id 
        WHERE v.variant_id IS NULL
    ");
    $missing_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($missing_products) === 0) {
        echo "No missing variants found.\n";
        exit;
    }

    echo "Found " . count($missing_products) . " products without variants. Fixing...\n";

    // Prepare insert query
    $insertStmt = $conn->prepare("
        INSERT INTO product_variants (variant_id, product_id, sku, color, size, stock, original_price, sale_price, is_active)
        VALUES (?, ?, ?, 'Mặc định', 'Freesize', 100, ?, ?, 1)
    ");

    $maxNumStmt = $conn->query("SELECT MAX(CAST(SUBSTRING(variant_id, 2) AS UNSIGNED)) as max_id FROM product_variants");
    $maxIdRow = $maxNumStmt->fetch();
    $currentMax = (int)$maxIdRow['max_id'];

    $count = 0;
    foreach ($missing_products as $prod) {
        $product_id = $prod['product_id'];
        $currentMax++;
        $variant_id = 'V' . str_pad($currentMax, 3, '0', STR_PAD_LEFT);
        $sku = 'SKU-' . $product_id;
        
        $price = rand(150, 400) * 1000;
        
        $insertStmt->execute([$variant_id, $product_id, $sku, $price, $price]);
        $count++;
    }
    echo "Fixed $count products by creating default variants.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
