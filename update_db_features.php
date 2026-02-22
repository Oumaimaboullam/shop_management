<?php
require_once 'config/database.php';

try {
    $pdo->beginTransaction();

    // 1. Create sale_returns table
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

    // 2. Create sale_return_items table
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

    // 3. Ensure sales table has proper status enum (if not already)
    // We can't easily alter enum in a safe cross-db way without raw SQL, 
    // but we can assume it exists based on schema. 
    // Let's add an index for status queries if it doesn't exist
    $stmt = $pdo->query("SHOW INDEX FROM sales WHERE Key_name = 'idx_sales_status'");
    if (!$stmt->fetch()) {
        $pdo->exec("CREATE INDEX idx_sales_status ON sales(status)");
    }

    $pdo->commit();
    echo "Database updated successfully for new features.";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error updating database: " . $e->getMessage();
}
?>