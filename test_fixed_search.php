<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fixed Search Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .box { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        input { padding: 5px; margin: 5px; }
        button { padding: 8px 15px; margin: 5px; }
    </style>
</head>
<body>
    <h1>Fixed POS Search Test</h1>
    
    <?php
    // Test 1: Database connection
    echo "<div class='box'>";
    echo "<h2>1. Database Connection</h2>";
    if ($pdo) {
        echo "<p class='success'>✓ Database connected successfully</p>";
    } else {
        echo "<p class='error'>✗ Database connection failed</p>";
        exit;
    }
    echo "</div>";
    
    // Test 2: Check if products exist
    echo "<div class='box'>";
    echo "<h2>2. Products in Database</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM articles WHERE is_active = 1");
        $result = $stmt->fetch();
        echo "<p>Active products: {$result['count']}</p>";
        
        if ($result['count'] > 0) {
            echo "<p class='success'>✓ Products exist in database</p>";
        } else {
            echo "<p class='error'>✗ No products found. <a href='setup_sample_products.php'>Create sample products</a></p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Test 3: Test the fixed search query
    echo "<div class='box'>";
    echo "<h2>3. Fixed Search Query Test</h2>";
    
    $testQueries = ['', 'Laptop', '123', 'a'];
    
    foreach ($testQueries as $query) {
        echo "<h3>Testing: '$query'</h3>";
        
        try {
            $sql = "
                SELECT a.id, a.barcode, a.name, a.sale_price, a.wholesale, s.quantity as stock
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
            
            echo "<p><strong>SQL:</strong> " . htmlspecialchars($sql) . "</p>";
            echo "<p><strong>Params:</strong> " . json_encode($params) . "</p>";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();
            
            echo "<p class='success'>✓ Query executed successfully</p>";
            echo "<p>Found " . count($products) . " results:</p>";
            
            if (count($products) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Name</th><th>Barcode</th><th>Price</th><th>Stock</th></tr>";
                foreach ($products as $product) {
                    echo "<tr>";
                    echo "<td>{$product['id']}</td>";
                    echo "<td>{$product['name']}</td>";
                    echo "<td>{$product['barcode']}</td>";
                    echo "<td>\${$product['sale_price']}</td>";
                    echo "<td>{$product['stock']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='info'>No results found</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        }
        echo "<hr>";
    }
    echo "</div>";
    
    // Test 4: Test API endpoint directly
    echo "<div class='box'>";
    echo "<h2>4. API Endpoint Test</h2>";
    echo "<p>Testing the actual API endpoint that POS uses:</p>";
    
    $apiQueries = ['', 'Laptop'];
    
    foreach ($apiQueries as $query) {
        echo "<h3>API Test: '$query'</h3>";
        
        // Simulate API call
        $_GET['q'] = $query;
        
        ob_start();
        try {
            include 'api/products/search.php';
            $output = ob_get_clean();
            echo "<p class='success'>✓ API executed successfully</p>";
            echo "<p><strong>Response:</strong></p>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        } catch (Exception $e) {
            ob_get_clean();
            echo "<p class='error'>✗ API Error: " . $e->getMessage() . "</p>";
        }
        
        unset($_GET['q']);
        echo "<hr>";
    }
    echo "</div>";
    ?>
    
    <div class='box'>
        <h2>5. Interactive Search Test</h2>
        <p>Test the search functionality interactively:</p>
        <input type="text" id="searchInput" placeholder="Enter search term" value="Laptop">
        <button onclick="testSearch()">Search</button>
        <button onclick="testSearch('')">Load All</button>
        <div id="searchResults"></div>
    </div>
    
    <script>
        async function testSearch(query = null) {
            const searchInput = document.getElementById('searchInput');
            const results = document.getElementById('searchResults');
            
            if (query === null) {
                query = searchInput.value;
            }
            
            results.innerHTML = `<p>Searching for: "${query}"...</p>`;
            
            try {
                const response = await fetch('api/products/search.php?q=' + encodeURIComponent(query));
                const data = await response.json();
                
                let html = `<h3>Results for "${query}":</h3>`;
                html += `<p>Status: ${response.status} ${response.statusText}</p>`;
                
                if (data.success) {
                    html += `<p class="success">✓ Found ${data.data.length} products</p>`;
                    
                    if (data.data.length > 0) {
                        html += `<table>`;
                        html += `<tr><th>ID</th><th>Name</th><th>Barcode</th><th>Price</th><th>Stock</th></tr>`;
                        data.data.forEach(product => {
                            html += `<tr>`;
                            html += `<td>${product.id}</td>`;
                            html += `<td>${product.name}</td>`;
                            html += `<td>${product.barcode || 'N/A'}</td>`;
                            html += `<td>$${product.sale_price}</td>`;
                            html += `<td>${product.stock || 0}</td>`;
                            html += `</tr>`;
                        });
                        html += `</table>`;
                    }
                } else {
                    html += `<p class="error">✗ Error: ${data.message}</p>`;
                }
                
                results.innerHTML = html;
                
            } catch (error) {
                results.innerHTML = `<p class="error">Network Error: ${error.message}</p>`;
            }
        }
        
        // Auto-test on page load
        window.onload = function() {
            testSearch('Laptop');
        };
    </script>
    
    <div class='box info'>
        <h2>Next Steps</h2>
        <ol>
            <li><a href='login.php'>Login to the system</a></li>
            <li><a href='sales/pos.php'>Test the POS search</a></li>
            <li>Search by product name (e.g., "Laptop")</li>
            <li>Search by barcode (e.g., "123456789")</li>
        </ol>
        <p><strong>The SQL parameter issue has been fixed!</strong></p>
    </div>
</body>
</html>
