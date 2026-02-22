<?php
// Simple table creation
require_once 'config/database.php';

try {
    echo "Creating draft_orders table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS draft_orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            client_id INT NULL,
            document_type ENUM('sale', 'invoice', 'quote') NOT NULL,
            payment_mode_id INT NULL,
            discount_percent DECIMAL(5,2) DEFAULT 0,
            status ENUM('draft', 'confirmed') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Creating draft_items table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS draft_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            draft_id INT NOT NULL,
            article_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            discount_percent DECIMAL(5,2) DEFAULT 0,
            total_price DECIMAL(10,2) NOT NULL
        )
    ");
    
    echo "Tables created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
