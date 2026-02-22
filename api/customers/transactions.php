<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Get customer ID from request
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

if ($clientId === 0) {
    echo json_encode(['success' => false, 'message' => 'Customer ID required']);
    exit;
}

try {
    // Get customer transactions with advance payments
    $stmt = $pdo->prepare("
        SELECT 
            s.id as transaction_id,
            s.total_amount,
            s.advance_payment,
            s.created_at,
            s.document_type,
            s.payment_mode_id,
            pm.name as payment_mode,
            c.name as customer_name,
            c.phone as customer_phone,
            (s.total_amount - s.advance_payment) as remaining_amount
        FROM sales s
        LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id
        LEFT JOIN clients c ON s.client_id = c.id
        WHERE s.client_id = ?
        ORDER BY s.created_at DESC
    ");
    
    $stmt->execute([$clientId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total balance
    $totalAdvance = 0;
    $totalRemaining = 0;
    
    foreach ($transactions as $transaction) {
        $totalAdvance += $transaction['advance_payment'];
        $totalRemaining += $transaction['remaining_amount'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'transactions' => $transactions,
            'summary' => [
                'total_advance' => $totalAdvance,
                'total_remaining' => $totalRemaining,
                'net_balance' => $totalRemaining - $totalAdvance
            ]
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
