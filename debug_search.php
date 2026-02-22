<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Test database connection
echo "<h2>Database Connection Test</h2>";
if ($pdo) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    exit;
}

// Test tables exist
echo "<h2>Table Structure Test</h2>";
$tables = ['articles', 'stock'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Table '$table' error: " . $e->getMessage() . "</p>";
    }
}

// Test sample data
echo "<h2>Sample Data Test</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM articles WHERE is_active = 1");
    $result = $stmt->fetch();
    echo "<p>Active articles count: " . $result['count'] . "</p>";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT id, name, barcode, sale_price FROM articles WHERE is_active = 1 LIMIT 5");
        $articles = $stmt->fetchAll();
        echo "<h3>Sample Articles:</h3><ul>";
        foreach ($articles as $article) {
            echo "<li>ID: {$article['id']}, Name: {$article['name']}, Barcode: {$article['barcode']}, Price: {$article['sale_price']}</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error querying articles: " . $e->getMessage() . "</p>";
}

// Test stock data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM stock");
    $result = $stmt->fetch();
    echo "<p>Stock records count: " . $result['count'] . "</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error querying stock: " . $e->getMessage() . "</p>";
}

// Test the exact search query
echo "<h2>Search Query Test</h2>";
$query = isset($_GET['q']) ? $_GET['q'] : '';
echo "<p>Testing search for: '$query'</p>";

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

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    echo "<p>Query executed successfully</p>";
    echo "<p>Found " . count($products) . " products</p>";
    
    if (count($products) > 0) {
        echo "<h3>Results:</h3><ul>";
        foreach ($products as $product) {
            echo "<li>ID: {$product['id']}, Name: {$product['name']}, Barcode: {$product['barcode']}, Stock: {$product['stock']}, Price: {$product['sale_price']}</li>";
        }
        echo "</ul>";
    }
    
    // Test JSON response
    echo "<h2>JSON Response Test</h2>";
    jsonResponse(['success' => true, 'data' => $products]);
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Search query error: " . $e->getMessage() . "</p>";
    echo "<p>SQL: " . $sql . "</p>";
}
?>
