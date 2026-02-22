<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$sale_id = isset($input['sale_id']) ? (int)$input['sale_id'] : 0;
$items = isset($input['items']) ? $input['items'] : []; // Array of {sale_item_id, quantity}
$reason = isset($input['reason']) ? sanitize($input['reason']) : '';

if ($sale_id <= 0 || empty($items)) {
    jsonResponse(['success' => false, 'message' => 'Invalid data'], 400);
}

try {
    $pdo->beginTransaction();

    // 1. Validate Sale
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('Sale not found');
    }

    if ($sale['status'] === 'cancelled') {
        throw new Exception('Cannot return items from a cancelled sale');
    }

    $total_refund = 0;
    $return_items_data = [];

    // 2. Validate Items & Calculate Refund
    foreach ($items as $return_item) {
        $sale_item_id = (int)$return_item['sale_item_id'];
        $qty_to_return = (int)$return_item['quantity'];

        if ($qty_to_return <= 0) continue;

        // Fetch original item details
        $stmt = $pdo->prepare("SELECT * FROM sale_items WHERE id = ? AND sale_id = ?");
        $stmt->execute([$sale_item_id, $sale_id]);
        $original_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$original_item) {
            throw new Exception("Invalid item ID: $sale_item_id");
        }

        // Check previously returned quantity
        $stmt = $pdo->prepare("
            SELECT SUM(quantity) as returned_qty 
            FROM sale_return_items 
            WHERE sale_item_id = ?
        ");
        $stmt->execute([$sale_item_id]);
        $already_returned = (int)$stmt->fetchColumn();

        if (($already_returned + $qty_to_return) > $original_item['quantity']) {
            throw new Exception("Cannot return more than sold quantity for item ID: " . $original_item['article_id']);
        }

        $refund_amount = $qty_to_return * $original_item['unit_price'];
        $total_refund += $refund_amount;

        $return_items_data[] = [
            'sale_item_id' => $sale_item_id,
            'article_id' => $original_item['article_id'],
            'quantity' => $qty_to_return,
            'refund_amount' => $refund_amount
        ];
    }

    if (empty($return_items_data)) {
        throw new Exception('No valid items to return');
    }

    // 3. Create Return Record
    $stmt = $pdo->prepare("
        INSERT INTO sale_returns (sale_id, user_id, reason, total_refund, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$sale_id, $_SESSION['user_id'], $reason, $total_refund]);
    $return_id = $pdo->lastInsertId();

    // 4. Process Return Items & Stock
    foreach ($return_items_data as $data) {
        // Insert return item
        $stmt = $pdo->prepare("
            INSERT INTO sale_return_items (return_id, sale_item_id, article_id, quantity, refund_amount)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $return_id, 
            $data['sale_item_id'], 
            $data['article_id'], 
            $data['quantity'], 
            $data['refund_amount']
        ]);

        // Restore Stock
        updateStockQuantity($pdo, $data['article_id'], $data['quantity']);

        // Log Movement
        recordStockMovement(
            $pdo,
            $data['article_id'],
            'in',
            $data['quantity'],
            'return',
            $return_id
        );
    }

    // 5. Update Sale Status (optional logic)
    // If partial return, maybe mark sale as 'partial'? 
    // Or just leave as 'paid'/'confirmed' and let the returns table handle the logic.
    // For now, let's just keep the original status unless it was fully returned?
    // Let's not change sale status to avoid complexity with 'cancelled' vs 'returned'.

    $pdo->commit();
    jsonResponse(['success' => true, 'message' => 'Return processed successfully', 'return_id' => $return_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>