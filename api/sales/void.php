<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

// Only admin or manager can void sales
if (!hasRole(['admin', 'manager'])) {
    jsonResponse(['success' => false, 'message' => 'Permission denied'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$sale_id = isset($input['sale_id']) ? (int)$input['sale_id'] : 0;
$reason = isset($input['reason']) ? sanitize($input['reason']) : 'Voided by user';

if ($sale_id <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid sale ID'], 400);
}

try {
    $pdo->beginTransaction();

    // 1. Get sale details
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('Sale not found');
    }

    if ($sale['status'] === 'cancelled') {
        throw new Exception('Sale is already cancelled');
    }

    // Check strict voiding policy (e.g., same day only)
    $sale_date = new DateTime($sale['created_at']);
    $today = new DateTime();
    // Allow same day cancellation or if admin
    if ($sale_date->format('Y-m-d') !== $today->format('Y-m-d') && $_SESSION['user']['role'] !== 'admin') {
        throw new Exception('Cannot void past sales. Please use Return/Credit Note instead.');
    }

    // 2. Get sale items
    $stmt = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Restore stock
    foreach ($items as $item) {
        // Increase stock
        updateStockQuantity($pdo, $item['article_id'], $item['quantity']);
        
        // Log movement
        recordStockMovement(
            $pdo, 
            $item['article_id'], 
            'in', 
            $item['quantity'], 
            'return', // Using 'return' as source for void/cancellation implies stock coming back
            $sale_id
        );
    }

    // 4. Update sale status
    $stmt = $pdo->prepare("UPDATE sales SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$sale_id]);

    // 5. Log the void reason (optional: create a sales_notes or audit log table, currently just sticking to simple status change)
    // Ideally we should store the reason. Let's assume we might add a 'notes' column to sales later or just log it to a file/audit table.
    // For now, we proceed with status update.

    $pdo->commit();
    jsonResponse(['success' => true, 'message' => 'Sale voided successfully']);

} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>