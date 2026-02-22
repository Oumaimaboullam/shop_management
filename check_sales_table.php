<?php
require_once 'config/database.php';

echo "<h2>Sales Table Structure</h2>";
$stmt = $pdo->query("DESCRIBE sales");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>{$column['Field']}</td>";
    echo "<td>{$column['Type']}</td>";
    echo "<td>{$column['Null']}</td>";
    echo "<td>{$column['Key']}</td>";
    echo "<td>{$column['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Check if updated_at column exists</h2>";
try {
    $stmt = $pdo->query("SELECT updated_at FROM sales LIMIT 1");
    echo "<p style='color: green;'>✅ updated_at column exists</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ updated_at column does not exist</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    
    echo "<h3>Adding updated_at column...</h3>";
    try {
        $pdo->query("ALTER TABLE sales ADD COLUMN updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "<p style='color: green;'>✅ updated_at column added successfully</p>";
    } catch (Exception $e2) {
        echo "<p style='color: red;'>❌ Failed to add updated_at column: " . $e2->getMessage() . "</p>";
    }
}
?>
