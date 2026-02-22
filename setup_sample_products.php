<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Sample Products</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .box { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Setup Sample Products for POS</h1>
    
    <?php
    try {
        // Check and create sample products
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM articles WHERE is_active = 1");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            echo "<div class='box info'>";
            echo "<h2>Creating Sample Products...</h2>";
            
            // Sample products data
            $products = [
                ['barcode' => '123456789', 'name' => 'Laptop Computer', 'sale_price' => 599.99, 'wholesale' => 499.99, 'stock' => 10],
                ['barcode' => '234567890', 'name' => 'Wireless Mouse', 'sale_price' => 29.99, 'wholesale' => 19.99, 'stock' => 25],
                ['barcode' => '345678901', 'name' => 'USB Keyboard', 'sale_price' => 49.99, 'wholesale' => 39.99, 'stock' => 15],
                ['barcode' => '456789012', 'name' => 'Monitor 24"', 'sale_price' => 199.99, 'wholesale' => 159.99, 'stock' => 8],
                ['barcode' => '567890123', 'name' => 'Webcam HD', 'sale_price' => 79.99, 'wholesale' => 64.99, 'stock' => 12],
                ['barcode' => '678901234', 'name' => 'Headphones', 'sale_price' => 89.99, 'wholesale' => 69.99, 'stock' => 20],
                ['barcode' => '789012345', 'name' => 'USB Flash Drive 32GB', 'sale_price' => 15.99, 'wholesale' => 12.99, 'stock' => 50],
                ['barcode' => '890123456', 'name' => 'Power Bank', 'sale_price' => 39.99, 'wholesale' => 29.99, 'stock' => 30],
            ];
            
            $pdo->beginTransaction();
            
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
                
                echo "<p class='success'>✓ Created: {$product['name']} (Barcode: {$product['barcode']})</p>";
            }
            
            $pdo->commit();
            echo "<p class='success'><strong>Sample products created successfully!</strong></p>";
            echo "</div>";
        } else {
            echo "<div class='box info'>";
            echo "<h2>Products Already Exist</h2>";
            echo "<p>Found {$result['count']} active products in database.</p>";
            echo "</div>";
        }
        
        // Display current products
        echo "<div class='box'>";
        echo "<h2>Current Products in Database:</h2>";
        
        $stmt = $pdo->query("
            SELECT a.id, a.barcode, a.name, a.sale_price, a.wholesale, s.quantity as stock
            FROM articles a
            LEFT JOIN stock s ON a.id = s.article_id
            WHERE a.is_active = 1
            ORDER BY a.name
        ");
        $products = $stmt->fetchAll();
        
        if (count($products) > 0) {
            echo "<table>";
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
            echo "<p class='error'>No products found!</p>";
        }
        echo "</div>";
        
        // Test search functionality
        echo "<div class='box'>";
        echo "<h2>Test Search Functionality:</h2>";
        
        $testQueries = ['', 'a', 'Laptop', '123'];
        
        foreach ($testQueries as $query) {
            echo "<h3>Testing search for: '$query'</h3>";
            
            $sql = "
                SELECT a.id, a.barcode, a.name, a.sale_price, s.quantity as stock
                FROM articles a
                LEFT JOIN stock s ON a.id = s.article_id
                WHERE a.is_active = 1
            ";
            
            $params = [];
            if ($query !== '') {
                $sql .= " AND (a.name LIKE :name_query OR a.barcode LIKE :barcode_query)";
                $params[':name_query'] = "%$query%";
                $params[':barcode_query'] = "%$query%";
            }
            
            $sql .= " LIMIT 5";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            echo "<p>Found " . count($results) . " results:</p>";
            if (count($results) > 0) {
                echo "<ul>";
                foreach ($results as $result) {
                    echo "<li>{$result['name']} (Barcode: {$result['barcode']}, Stock: {$result['stock']})</li>";
                }
                echo "</ul>";
            }
        }
        echo "</div>";
        
        echo "<div class='box info'>";
        echo "<h2>Next Steps:</h2>";
        echo "<ol>";
        echo "<li><a href='login.php'>Login to the system</a> (use admin/admin if you haven't changed it)</li>";
        echo "<li><a href='sales/pos.php'>Test the POS search functionality</a></li>";
        echo "<li><a href='simple_search_test.html'>Run the search test tool</a></li>";
        echo "</ol>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div class='box error'>";
        echo "<h2>Database Error</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    ?>
</body>
</html>
