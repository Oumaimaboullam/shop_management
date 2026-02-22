<?php
// api/articles/create.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Invalid input'], 400);
    exit;
}

// Handle category creation first
$category_id = null;
if (!empty($input['new_category'])) {
    // Check if categories table exists first
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'categories'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            // Create categories table if it doesn't exist
            $pdo->exec("
                CREATE TABLE categories (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(100) NOT NULL,
                    parent_id INT NULL
                )
            ");
        }
        
        // Create new category
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO categories (name, parent_id)
            VALUES (:name, :parent_id)
        ");
        $stmt->execute([
            ':name' => $input['new_category'],
            ':parent_id' => null
        ]);
        
        $category_id = $pdo->lastInsertId();
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse([
            'success' => false,
            'message' => 'Error creating category: ' . $e->getMessage()
        ], 500);
        exit;
    }
} else {
    // Use existing category
    $category_id = !empty($input['category_id']) ? $input['category_id'] : null;
}

// Validate required fields
$required_fields = ['name', 'purchase_price', 'percentage_of_sales_profit'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        jsonResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
        exit;
    }
}

try {
    $pdo->beginTransaction();
    
    // Calculate sale price and wholesale price from percentages
    $purchase_price = $input['purchase_price'];
    $profit_percentage = $input['percentage_of_sales_profit'] ?? 0;
    $wholesale_percentage = $input['wholesale_percentage'] ?? 0;
    
    $sale_price = $purchase_price * (1 + ($profit_percentage / 100));
    $wholesale = $purchase_price * (1 + ($wholesale_percentage / 100));
    
    // Insert new article
    $stmt = $pdo->prepare("
        INSERT INTO articles (
            name, 
            barcode, 
            reference, 
            category_id, 
            description, 
            purchase_price, 
            percentage_of_sales_profit, 
            wholesale_percentage, 
            wholesale, 
            sale_price, 
            tax_rate, 
            stock_alert_level, 
            is_active, 
            created_at
        ) VALUES (
            :name, 
            :barcode, 
            :reference, 
            :category_id, 
            :description, 
            :purchase_price, 
            :percentage_of_sales_profit, 
            :wholesale_percentage, 
            :wholesale, 
            :sale_price, 
            :tax_rate, 
            :stock_alert_level, 
            1, 
            NOW()
        )
    ");
    
    $stmt->execute([
        ':name' => $input['name'],
        ':barcode' => $input['barcode'] ?? null,
        ':reference' => $input['reference'] ?? null,
        ':category_id' => $category_id,
        ':description' => $input['description'] ?? null,
        ':purchase_price' => $purchase_price,
        ':percentage_of_sales_profit' => $input['percentage_of_sales_profit'],
        ':wholesale_percentage' => $input['wholesale_percentage'],
        ':wholesale' => $wholesale,
        ':sale_price' => $sale_price,
        ':tax_rate' => $input['tax_rate'] ?? 0,
        ':stock_alert_level' => $input['stock_alert_level'] ?? 0
    ]);
    
    $article_id = $pdo->lastInsertId();
    
    // Create stock record for the new article
    $stmt = $pdo->prepare("
        INSERT INTO stock (article_id, quantity, updated_at)
        VALUES (:article_id, :quantity, NOW())
    ");
    $stmt->execute([
        ':article_id' => $article_id,
        ':quantity' => $input['initial_quantity'] ?? 0
    ]);
    
    $pdo->commit();
    
    // Return the created article data
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
    
    jsonResponse([
        'success' => true,
        'message' => 'Product created successfully',
        'article' => $article
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    jsonResponse([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], 500);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
}
?>
