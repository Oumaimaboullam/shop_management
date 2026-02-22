<?php
require_once 'config/database.php';

// Create sample products if none exist
try {
    // Check if products exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM articles WHERE is_active = 1");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "<h2>Creating sample products...</h2>";
        
        // Sample products
        $products = [
            ['name' => 'Laptop Computer', 'barcode' => '123456789', 'sale_price' => 599.99, 'wholesale' => 499.99, 'stock' => 10],
            ['name' => 'Wireless Mouse', 'barcode' => '234567890', 'sale_price' => 29.99, 'wholesale' => 19.99, 'stock' => 25],
            ['name' => 'USB Keyboard', 'barcode' => '345678901', 'sale_price' => 49.99, 'wholesale' => 39.99, 'stock' => 15],
            ['name' => 'Monitor 24"', 'barcode' => '456789012', 'sale_price' => 199.99, 'wholesale' => 159.99, 'stock' => 8],
            ['name' => 'Webcam HD', 'barcode' => '567890123', 'sale_price' => 79.99, 'wholesale' => 64.99, 'stock' => 12],
            ['name' => 'Headphones', 'barcode' => '678901234', 'sale_price' => 89.99, 'wholesale' => 69.99, 'stock' => 20],
            ['name' => 'USB Flash Drive 32GB', 'barcode' => '789012345', 'sale_price' => 15.99, 'wholesale' => 12.99, 'stock' => 50],
            ['name' => 'Power Bank', 'barcode' => '890123456', 'sale_price' => 39.99, 'wholesale' => 29.99, 'stock' => 30],
        ];
        
        foreach ($products as $product) {
            // Insert article
            $stmt = $pdo->prepare("
                INSERT INTO articles (barcode, name, purchase_price, sale_price, wholesale, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $product['barcode'],
                $product['name'],
                $product['sale_price'] * 0.7, // Purchase price as 70% of sale price
                $product['sale_price'],
                $product['wholesale']
            ]);
            
            $article_id = $pdo->lastInsertId();
            
            // Insert stock
            $stmt = $pdo->prepare("
                INSERT INTO stock (article_id, quantity) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
            ");
            $stmt->execute([$article_id, $product['stock']]);
            
            echo "<p style='color: green;'>✓ Created: {$product['name']} (Barcode: {$product['barcode']})</p>";
        }
        
        echo "<h3>Sample products created successfully!</h3>";
    } else {
        echo "<h2>Products already exist</h2>";
        echo "<p>Found {$result['count']} active products in database.</p>";
    }
    
    // Display current products
    echo "<h2>Current Products:</h2>";
    $stmt = $pdo->query("
        SELECT a.id, a.barcode, a.name, a.sale_price, a.wholesale, s.quantity as stock
        FROM articles a
        LEFT JOIN stock s ON a.id = s.article_id
        WHERE a.is_active = 1
        ORDER BY a.name
        LIMIT 10
    ");
    $products = $stmt->fetchAll();
    
    if (count($products) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Barcode</th><th>Name</th><th>Sale Price</th><th>Wholesale</th><th>Stock</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['barcode']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td>\${$product['sale_price']}</td>";
            echo "<td>\${$product['wholesale']}</td>";
            echo "<td>{$product['stock']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No products found!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
