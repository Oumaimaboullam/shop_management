<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("
        SELECT a.id, a.barcode, a.name, s.quantity as stock
        FROM articles a
        LEFT JOIN stock s ON a.id = s.article_id
        WHERE a.is_active = 1
        LIMIT 5
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Stock Debug</h1>";
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Stock (Raw)</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>" . var_export($row['stock'], true) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>