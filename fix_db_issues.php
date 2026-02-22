<?php
require_once 'config/database.php';

try {
    // 1. Add description to categories if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'description'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN description TEXT");
        echo "Added 'description' column to categories table.<br>";
    }

    // 2. Add is_active to articles if not exists (for soft delete)
    $stmt = $pdo->query("SHOW COLUMNS FROM articles LIKE 'is_active'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE articles ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        echo "Added 'is_active' column to articles table.<br>";
    } else {
        // Ensure default is 1
        $pdo->exec("ALTER TABLE articles MODIFY COLUMN is_active TINYINT(1) DEFAULT 1");
    }

    // 3. Ensure stock table has entries for all articles
    $stmt = $pdo->query("
        INSERT INTO stock (article_id, quantity)
        SELECT id, 0 FROM articles a
        WHERE NOT EXISTS (SELECT 1 FROM stock s WHERE s.article_id = a.id)
    ");
    if ($stmt->rowCount() > 0) {
        echo "Created missing stock entries for " . $stmt->rowCount() . " articles.<br>";
    }

    echo "Database structure updated successfully.";

} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>