<?php
// api/products/barcode.php - Fast barcode lookup for POS
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

if (empty($barcode)) {
    jsonResponse(['success' => false, 'message' => 'Barcode required'], 400);
}

try {
    // Fast exact barcode lookup
    $sql = "
        SELECT a.id, a.barcode, a.name, a.sale_price, a.wholesale, a.purchase_price,
               s.quantity as stock, c.name as category_name
        FROM articles a
        LEFT JOIN stock s ON a.id = s.article_id
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.is_active = 1 AND a.barcode = :barcode
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':barcode' => $barcode]);
    $product = $stmt->fetch();
    
    if ($product) {
        jsonResponse(['success' => true, 'data' => $product]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
    }
    
} catch (PDOException $e) {
    error_log("Barcode lookup error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error'], 500);
}
?>
