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

    } catch (PDOException $e) {
        // Log error silently to avoid breaking the page
        error_log("DB Fix Error: " . $e->getMessage());
    }
}

// Run the check
checkAndFixDatabase($pdo);
?>