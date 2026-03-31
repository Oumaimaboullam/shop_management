<?php
// api/sales/save_draft.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Invalid input'], 400);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $userId = (int) $_SESSION['user_id'];
    
    // Handle client_id - only set if it's a valid integer
    $clientId = null;
    if (isset($input['client_id']) && $input['client_id'] !== null && $input['client_id'] !== '' && $input['client_id'] !== 'null') {
        $clientId = intval($input['client_id']);
        // Verify client exists
        $checkStmt = $pdo->prepare("SELECT id FROM clients WHERE id = ?");
        $checkStmt->execute([$clientId]);
        if ($checkStmt->rowCount() === 0) {
            $clientId = null; // Client doesn't exist, set to null
        }
    }
    
    // Insert draft record
    $stmt = $pdo->prepare("
        INSERT INTO draft_orders (user_id, client_id, document_type, payment_mode_id, discount_percent, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'draft', NOW())
    ");
    $stmt->execute([
        $userId,
        $clientId,
        $input['sale_type'] ?? 'sale',
        $input['payment_mode_id'] ?? 1,
        $input['discount_percent'] ?? 0
    ]);
    $draftId = $pdo->lastInsertId();
    
    // Insert draft items
    foreach ($input['items'] as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO draft_items (draft_id, article_id, quantity, unit_price, discount_percent, total_price)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $draftId,
            $item['id'],
            $item['quantity'],
            $item['unit_price'],
            $item['discount_percent'] ?? 0,
            $item['unit_price'] * $item['quantity'] * (1 - ($item['discount_percent'] ?? 0) / 100)
        ]);
    }
    
    $pdo->commit();
    
    jsonResponse([
        'success' => true,
        'message' => 'Draft saved successfully',
        'draft_id' => $draftId
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
