<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Checking if categories table exists...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'categories'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "Categories table does not exist!\n";
    } else {
        echo "Categories table exists. Checking structure...\n";
        $stmt = $pdo->query("DESCRIBE categories");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo $column['Field'] . " - " . $column['Type'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
