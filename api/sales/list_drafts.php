<?php
// api/sales/list_drafts.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    // Get all drafts with their items count and total amount
    $stmt = $pdo->query("
        SELECT 
            d.id,
            d.created_at,
            COUNT(di.id) as items_count,
            SUM(di.total_price) as total_amount,
            d.document_type,
            d.client_id,
            c.name as client_name
        FROM draft_orders d
        LEFT JOIN draft_items di ON d.id = di.draft_id
        LEFT JOIN clients c ON d.client_id = c.id
        WHERE d.status = 'draft'
        GROUP BY d.id, d.created_at, d.document_type, d.client_id, c.name
        ORDER BY d.created_at DESC
    ");
    
    $drafts = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'data' => $drafts
    ]);
    
} catch (PDOException $e) {
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
