<?php
// create_draft_tables.php - Execute database table creation
require_once 'config/database.php';

echo "Creating draft tables...\n";

try {
    // Create draft_orders table
    $sql = "
        CREATE TABLE IF NOT EXISTS draft_orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            client_id INT NULL,
            document_type ENUM('sale', 'invoice', 'quote') NOT NULL,
            payment_mode_id INT NULL,
            discount_percent DECIMAL(5,2) DEFAULT 0,
            status ENUM('draft', 'confirmed') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (client_id) REFERENCES clients(id)
        )
    ";
    
    if (!$pdo->exec($sql)) {
        throw new Exception("Failed to create draft_orders table");
    }
    echo "✓ draft_orders table created successfully\n";
    
    // Create draft_items table
    $sql = "
        CREATE TABLE IF NOT EXISTS draft_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            draft_id INT NOT NULL,
            article_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            discount_percent DECIMAL(5,2) DEFAULT 0,
            total_price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (draft_id) REFERENCES draft_orders(id),
            FOREIGN KEY (article_id) REFERENCES articles(id)
        )
    ";
    
    if (!$pdo->exec($sql)) {
        throw new Exception("Failed to create draft_items table");
    }
    echo "✓ draft_items table created successfully\n";
    
    echo "All draft tables created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
