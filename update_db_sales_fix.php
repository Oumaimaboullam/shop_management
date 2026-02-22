<?php
// update_db_sales_fix.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Updating Database Schema...</h1>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Check if 'document_type' column exists in 'sales' table
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'document_type'");
    if ($stmt->rowCount() == 0) {
        echo "Adding 'document_type' column to 'sales' table...<br>";
        $pdo->exec("ALTER TABLE sales ADD COLUMN document_type ENUM('sale', 'invoice', 'quote') NOT NULL DEFAULT 'sale' AFTER user_id");
        echo "<span style='color:green'>Success: Added document_type.</span><br>";
    } else {
        echo "Column 'document_type' already exists.<br>";
    }

    // 2. Check if 'invoice_number' column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'invoice_number'");
    if ($stmt->rowCount() == 0) {
        echo "Adding 'invoice_number' column to 'sales' table...<br>";
        $pdo->exec("ALTER TABLE sales ADD COLUMN invoice_number VARCHAR(100) AFTER payment_mode_id");
        echo "<span style='color:green'>Success: Added invoice_number.</span><br>";
    } else {
        echo "Column 'invoice_number' already exists.<br>";
    }

    // 3. Check for 'subtotal_amount'
    $stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'subtotal_amount'");
    if ($stmt->rowCount() == 0) {
        echo "Adding 'subtotal_amount' column...<br>";
        $pdo->exec("ALTER TABLE sales ADD COLUMN subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER invoice_number");
        echo "<span style='color:green'>Success: Added subtotal_amount.</span><br>";
    }

    // 4. Ensure return tables exist (just in case)
    $pdo->exec("CREATE TABLE IF NOT EXISTS sale_returns (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sale_id INT NOT NULL,
        user_id INT NOT NULL,
        reason TEXT,
        total_refund DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sale_id) REFERENCES sales(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS sale_return_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        return_id INT NOT NULL,
        sale_item_id INT NOT NULL,
        article_id INT NOT NULL,
        quantity INT NOT NULL,
        refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (return_id) REFERENCES sale_returns(id),
        FOREIGN KEY (sale_item_id) REFERENCES sale_items(id),
        FOREIGN KEY (article_id) REFERENCES articles(id)
    )");
    echo "Verified return tables exist.<br>";

    echo "<hr><h3>Update Completed Successfully!</h3>";
    echo "<p>You can now go back to <a href='sales/pos.php'>POS</a> and try checkout.</p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Database Error: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
