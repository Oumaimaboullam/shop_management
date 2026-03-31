<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input data (support both JSON and form data)
$data = json_decode(file_get_contents('php://input'), true);

// If no JSON data, try to get from POST
if (!$data) {
    $data = $_POST;
}

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$transactionId = isset($data['transaction_id']) ? (int)$data['transaction_id'] : 0;
$paymentAmount = isset($data['payment_amount']) ? (float)$data['payment_amount'] : 0;
$paymentMode = isset($data['payment_mode_id']) ? (int)$data['payment_mode_id'] : 1;

if ($transactionId === 0 || $paymentAmount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction data']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get current transaction details
    $stmt = $pdo->prepare("SELECT s.*, c.name as client_name FROM sales s LEFT JOIN clients c ON s.client_id = c.id WHERE s.id = ?");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }

    if (empty($transaction['client_id'])) {
        throw new Exception('Cette vente n\'est pas associée à un client');
    }
    
    $currentAdvance = $transaction['advance_payment'] ?? 0;
    $totalAmount = $transaction['total_amount'];
    $currentRemaining = $totalAmount - $currentAdvance;
    
    if ($paymentAmount > $currentRemaining + 0.01) { // Allow small rounding differences
        throw new Exception('Payment amount exceeds remaining balance');
    }
    
    // Update transaction with additional payment
    $newAdvance = $currentAdvance + $paymentAmount;
    $newRemaining = $totalAmount - $newAdvance;
    
    $stmt = $pdo->prepare("
        UPDATE sales 
        SET advance_payment = ? 
        WHERE id = ?
    ");
    $stmt->execute([$newAdvance, $transactionId]);
    
    // Create payment record
    $stmt = $pdo->prepare("
        INSERT INTO customer_payments (
            sale_id, 
            client_id, 
            payment_amount, 
            payment_mode_id, 
            payment_type, 
            notes,
            created_at
        ) VALUES (?, ?, ?, ?, 'remaining_payment', ?, NOW())
    ");
    $notes = "Remaining payment for transaction #{$transactionId}";
    $stmt->execute([$transactionId, $transaction['client_id'], $paymentAmount, $paymentMode, $notes]);
    
    // Update customer balance if needed
    if ($newRemaining <= 0) {
        // Transaction fully paid - update customer balance
        $stmt = $pdo->prepare("
            UPDATE clients 
            SET balance = balance + ? 
            WHERE id = ?
        ");
        $stmt->execute([$newAdvance, $transaction['client_id']]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Set success message and redirect if form submission
    $response = [
        'success' => true,
        'message' => 'Payment recorded successfully',
        'data' => [
            'transaction_id' => $transactionId,
            'payment_amount' => $paymentAmount,
            'new_advance' => $newAdvance,
            'new_remaining' => $newRemaining,
            'is_fully_paid' => $newRemaining <= 0,
            'client_name' => $transaction['client_name']
        ]
    ];
    
    // Check if this was a form submission (not AJAX)
    if (isset($_POST['transaction_id'])) {
        // Set flash message and redirect back to customer view
        $_SESSION['flash_success'] = $response['message'];
        $response['redirect'] = "../customers/view.php?id=" . $transaction['client_id'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
