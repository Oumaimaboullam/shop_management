<?php
// api/products/search.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display errors

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Add debug info
error_log("POS Search: Session started, session_id: " . session_id());
error_log("POS Search: Session data: " . json_encode($_SESSION));

// Check if user is logged in
if (!isLoggedIn()) {
    error_log("POS Search: Unauthorized access attempt - Session data: " . json_encode($_SESSION));
    jsonResponse(['success' => false, 'message' => 'Unauthorized - Please login first'], 401);
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    $sql = "
        SELECT a.id, a.barcode, a.name, a.sale_price, a.wholesale, s.quantity as stock
        FROM articles a
        LEFT JOIN stock s ON a.id = s.article_id
        WHERE a.is_active = 1
    ";

    $params = [];
    if ($query !== '') {
        $sql .= " AND (a.name LIKE :name_query OR a.barcode LIKE :barcode_query)";
        $params[':name_query'] = "%$query%";
        $params[':barcode_query'] = "%$query%";
    }
    
    // Limit results for performance
    $sql .= " LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    jsonResponse(['success' => true, 'data' => $products]);

} catch (PDOException $e) {
    error_log("POS Search Database Error: " . $e->getMessage());
    error_log("POS Search SQL: " . $sql);
    error_log("POS Search Params: " . json_encode($params));
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>