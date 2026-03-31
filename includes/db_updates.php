<?php
// includes/db_updates.php
require_once __DIR__ . '/../config/database.php';

function checkAndFixDatabase($pdo) {
    try {
        // 1. Fix Categories Table
        try {
            $stmt = $pdo->query("SELECT description FROM categories LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN description TEXT");
        }

        // 2. Fix Articles Table (Soft Delete)
        try {
            $stmt = $pdo->query("SELECT is_active FROM articles LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE articles ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        }

        // 3. Fix Missing Stock Entries
        // Insert stock=0 for any article that doesn't have a stock record
        $pdo->exec("
            INSERT INTO stock (article_id, quantity)
            SELECT id, 0 FROM articles a
            WHERE NOT EXISTS (SELECT 1 FROM stock s WHERE s.article_id = a.id)
        ");

        // 4. Colonne advance_payment sur sales (POS / analytics)
        try {
            $pdo->query("SELECT advance_payment FROM sales LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN advance_payment DECIMAL(10,2) DEFAULT 0 AFTER paid_amount");
        }

        // 5. Table customer_payments (acomptes / soldes clients)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS customer_payments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                sale_id INT NOT NULL,
                client_id INT NOT NULL,
                payment_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                payment_mode_id INT NULL,
                payment_type ENUM('initial_payment', 'remaining_payment', 'refund') NOT NULL DEFAULT 'initial_payment',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
                FOREIGN KEY (payment_mode_id) REFERENCES payment_modes(id) ON DELETE SET NULL
            )
        ");

        // 6. Table purchase_payments (paiements fournisseurs sur achats)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS purchase_payments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                purchase_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_type ENUM('cash', 'check', 'transfer', 'card') NOT NULL,
                payment_date DATE NOT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (purchase_id) REFERENCES purchases(id)
            )
        ");

    } catch (PDOException $e) {
        // Log error silently to avoid breaking the page
        error_log("DB Fix Error: " . $e->getMessage());
    }
}

// Run the check
checkAndFixDatabase($pdo);
?>