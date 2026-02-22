<?php
// update_analytics_columns.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Updating Database Schema for Analytics...</h1>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Add advance_payment column to sales table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'advance_payment'");
    if ($stmt->rowCount() == 0) {
        echo "Adding 'advance_payment' column to 'sales' table...<br>";
        $pdo->exec("ALTER TABLE sales ADD COLUMN advance_payment DECIMAL(10,2) DEFAULT 0.00 AFTER paid_amount");
        echo "<span style='color:green'>Success: Added advance_payment.</span><br>";
    } else {
        echo "Column 'advance_payment' already exists.<br>";
    }

    // 2. Add unit_price column to stock table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM stock LIKE 'unit_price'");
    if ($stmt->rowCount() == 0) {
        echo "Adding 'unit_price' column to 'stock' table...<br>";
        $pdo->exec("ALTER TABLE stock ADD COLUMN unit_price DECIMAL(10,2) DEFAULT 0.00 AFTER quantity");
        echo "<span style='color:green'>Success: Added unit_price.</span><br>";
        
        // Update unit_price with sale_price from articles table
        echo "Updating unit_price values...<br>";
        $pdo->exec("UPDATE stock s JOIN articles a ON s.article_id = a.id SET s.unit_price = a.sale_price");
        echo "<span style='color:green'>Success: Updated unit_price values.</span><br>";
    } else {
        echo "Column 'unit_price' already exists.<br>";
    }

    echo "<hr><h3>Analytics Database Update Completed Successfully!</h3>";
    echo "<p>You can now access the <a href='reports/analytics_modern.php'>Modern Analytics Dashboard</a>.</p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Database Error: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
