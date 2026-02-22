<?php
// api/sales/load_draft.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$draftId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($draftId === 0) {
    jsonResponse(['success' => false, 'message' => 'Draft ID required'], 400);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get draft details with items
    $stmt = $pdo->prepare("
        SELECT 
            d.*,
            c.name as client_name,
            di.article_id,
            di.quantity,
            di.unit_price,
            di.discount_percent,
            a.name as product_name
        FROM draft_orders d
        LEFT JOIN clients c ON d.client_id = c.id
        LEFT JOIN draft_items di ON d.id = di.draft_id
        LEFT JOIN articles a ON di.article_id = a.id
        WHERE d.id = ? AND d.status = 'draft'
        ORDER BY di.id
    ");
    $stmt->execute([$draftId]);
    $draftData = $stmt->fetchAll();
    
    if (empty($draftData)) {
        jsonResponse(['success' => false, 'message' => 'Draft not found'], 404);
        exit;
    }
    
    // Format draft data for frontend
    $draft = [
        'id' => $draftData[0]['id'],
        'client_id' => $draftData[0]['client_id'],
        'client_name' => $draftData[0]['client_name'],
        'document_type' => $draftData[0]['document_type'],
        'payment_mode_id' => $draftData[0]['payment_mode_id'] ?? 1,
        'discount_percent' => $draftData[0]['discount_percent'] ?? 0,
        'created_at' => $draftData[0]['created_at'],
        'items' => []
    ];
    
    foreach ($draftData as $row) {
        $draft['items'][] = [
            'article_id' => $row['article_id'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'unit_price' => floatval($row['unit_price']),
            'discount_percent' => floatval($row['discount_percent'])
        ];
    }
    
    $pdo->commit();
    
    jsonResponse([
        'success' => true,
        'data' => $draft
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
