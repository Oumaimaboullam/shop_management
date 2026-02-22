<?php
// api/categories/create.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Invalid input'], 400);
    exit;
}

// Validate required fields
if (empty($input['name'])) {
    jsonResponse(['success' => false, 'message' => 'Category name is required'], 400);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Insert new category
    $stmt = $pdo->prepare("
        INSERT INTO categories (name, parent_id)
        VALUES (:name, :parent_id)
    ");
    $stmt->execute([
        ':name' => $input['name'],
        ':parent_id' => $input['parent_id'] ?? null
    ]);
    
    $category_id = $pdo->lastInsertId();
    
    $pdo->commit();
    
    // Return the created category
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    jsonResponse([
        'success' => true,
        'message' => 'Category created successfully',
        'category' => $category
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
