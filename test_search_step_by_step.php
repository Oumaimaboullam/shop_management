<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>POS Search - Step by Step Test</h1>";

// Step 1: Check session
echo "<h2>Step 1: Session Check</h2>";
session_start();
if (session_status() === PHP_SESSION_NONE) {
    echo "<p style='color: red;'>✗ Session not started</p>";
} else {
    echo "<p style='color: green;'>✓ Session started</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
}

// Step 2: Check login status
echo "<h2>Step 2: Login Status</h2>";
if (isLoggedIn()) {
    echo "<p style='color: green;'>✓ User is logged in</p>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='color: red;'>✗ User not logged in</p>";
    echo "<p>Attempting to set up test session...</p>";
    
    // Create a test session for debugging
    $_SESSION['user_id'] = 1;
    $_SESSION['user'] = ['id' => 1, 'name' => 'Test User', 'role' => 'admin'];
    echo "<p style='color: orange;'>⚠ Test session created (for debugging only)</p>";
}

// Step 3: Test database connection
echo "<h2>Step 3: Database Connection</h2>";
if ($pdo) {
    echo "<p style='color: green;'>✓ Database connected</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    exit;
}

// Step 4: Test tables
echo "<h2>Step 4: Table Structure</h2>";
$tables = ['articles', 'stock'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "<p style='color: green;'>✓ Table '$table' exists ({$result['count']} records)</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Table '$table' error: " . $e->getMessage() . "</p>";
    }
}

// Step 5: Test search query directly
echo "<h2>Step 5: Search Query Test</h2>";
$testQueries = ['', 'a', 'Laptop', '123'];

foreach ($testQueries as $query) {
    echo "<h3>Testing query: '$query'</h3>";
    
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
        
        $sql .= " LIMIT 20";

        echo "<p><strong>SQL:</strong> " . htmlspecialchars($sql) . "</p>";
        if (!empty($params)) {
            echo "<p><strong>Params:</strong> " . print_r($params, true) . "</p>";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        echo "<p style='color: green;'>✓ Query executed successfully</p>";
        echo "<p>Found " . count($products) . " products</p>";
        
        if (count($products) > 0) {
            echo "<ul>";
            foreach ($products as $product) {
                echo "<li>{$product['name']} (Barcode: {$product['barcode']}, Stock: {$product['stock']})</li>";
            }
            echo "</ul>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
    }
    echo "<hr>";
}

// Step 6: Test API response format
echo "<h2>Step 6: API Response Format</h2>";
try {
    $sql = "
        SELECT a.id, a.barcode, a.name, a.sale_price, a.wholesale, s.quantity as stock
        FROM articles a
        LEFT JOIN stock s ON a.id = s.article_id
        WHERE a.is_active = 1
        LIMIT 5
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([]);
    $products = $stmt->fetchAll();

    $apiResponse = [
        'success' => true,
        'data' => $products
    ];

    echo "<p><strong>API Response:</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($apiResponse, JSON_PRETTY_PRINT)) . "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ API test error: " . $e->getMessage() . "</p>";
}

echo "<h2>Summary</h2>";
echo "<p>If all steps show green checkmarks, the search functionality should work.</p>";
echo "<p>If you see red marks, those issues need to be fixed first.</p>";
?>
