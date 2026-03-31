<?php
// api/sales/create.php - Enhanced with error logging and debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display errors to prevent invalid JSON
ini_set('log_errors', 1);

// Ensure session is started properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Log all errors for debugging
error_log("=== SALE CREATE REQUEST ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Session Status: " . session_status());
error_log("Session ID: " . session_id());
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

// Get raw input properly
$rawInput = file_get_contents('php://input');
error_log("Raw Input: " . $rawInput);

// Handle both JSON and form data
if (empty($rawInput)) {
    $rawInput = json_encode($_POST);
    error_log("Using POST data as fallback: " . $rawInput);
}

$input = json_decode($rawInput, true);
error_log("Decoded Input: " . json_encode($input));

// Validate input
if (!is_array($input)) {
    error_log("Invalid input format");
    jsonResponse(['success' => false, 'message' => 'Invalid request format'], 400);
}

if (!isset($input['items']) || empty($input['items'])) {
    jsonResponse(['success' => false, 'message' => 'Cart is empty'], 400);
}

try {
    $pdo->beginTransaction();
    
    $user_id = $_SESSION['user_id'];
    $client_id = !empty($input['client_id']) ? $input['client_id'] : null;
    $payment_mode_id = !empty($input['payment_mode_id']) ? $input['payment_mode_id'] : 1; // Default to Cash
    $document_type = !empty($input['document_type']) ? $input['document_type'] : 'sale'; // sale, invoice, quote
    $discount_percent = !empty($input['discount_percent']) ? floatval($input['discount_percent']) : 0;
    $advance_payment = !empty($input['advance_payment']) ? floatval($input['advance_payment']) : 0;
    
    // Calculate totals
    $subtotal = 0;
    $total_discount_amount = 0;
    foreach ($input['items'] as $item) {
        $original_total = $item['unit_price'] * $item['quantity'];
        $item_discount_percent = !empty($item['discount_percent']) ? floatval($item['discount_percent']) : 0;
        $item_discount_amount = $original_total * ($item_discount_percent / 100);
        $item_total = $original_total - $item_discount_amount;
        
        $subtotal += $original_total;
        $total_discount_amount += $item_discount_amount;
        
        error_log("Item: {$item['id']} - Price: {$item['unit_price']} - Qty: {$item['quantity']} - Discount: {$item_discount_percent}% - Original Total: {$original_total} - Discount Amount: {$item_discount_amount} - Final Total: {$item_total}");
    }
    
    // Apply additional discount
    $additional_discount_amount = $subtotal * ($discount_percent / 100);
    $total_discount_amount += $additional_discount_amount;
    $total = $subtotal - $total_discount_amount;
    
    error_log("Subtotal: $subtotal, Individual Discounts: " . ($total_discount_amount - $additional_discount_amount) . ", Additional Discount: {$discount_percent}% ({$additional_discount_amount}), Total Discounts: {$total_discount_amount}, Final Total: $total, Advance Payment: $advance_payment");
    
    // Determine status based on document type
    $status = 'draft';
    $paid_amount = 0;
    if ($document_type === 'sale') {
        $status = 'paid';
        $paid_amount = $total;
    } elseif ($document_type === 'invoice') {
        $status = 'confirmed';
    } elseif ($document_type === 'quote') {
        $status = 'draft';
    }
    
    error_log("User ID: $user_id, Client ID: " . ($client_id ?? 'null') . ", Payment Mode: $payment_mode_id, Document Type: $document_type, Status: $status");
    
    // 1. Create Sale
    $stmt = $pdo->prepare("
        INSERT INTO sales (user_id, client_id, document_type, payment_mode_id, subtotal_amount, total_amount, paid_amount, advance_payment, status, created_at)
        VALUES (:user_id, :client_id, :document_type, :payment_mode_id, :subtotal, :total, :paid_amount, :advance_payment, :status, NOW())
    ");
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':client_id' => $client_id,
        ':document_type' => $document_type,
        ':payment_mode_id' => $payment_mode_id,
        ':subtotal' => $subtotal,
        ':total' => $total,
        ':paid_amount' => $paid_amount,
        ':advance_payment' => $advance_payment,
        ':status' => $status
    ]);
    
    $sale_id = $pdo->lastInsertId();
    error_log("Sale created with ID: $sale_id");
    
    // 2. Process Items
    foreach ($input['items'] as $item) {
        // Only validate and update stock for actual sales (not quotes)
        if ($document_type !== 'quote') {
            // Calculate total quantity requested for this item across all cart items
            $totalRequested = 0;
            foreach ($input['items'] as $cartItem) {
                if ($cartItem['id'] == $item['id']) {
                    $totalRequested += $cartItem['quantity'];
                }
            }
            
            // Validate stock before processing
            $stockCheck = $pdo->prepare("SELECT quantity FROM stock WHERE article_id = :id");
            $stockCheck->execute([':id' => $item['id']]);
            $currentStock = $stockCheck->fetchColumn();
            
            error_log("Stock check for item {$item['id']}: Current=$currentStock, Total Requested=$totalRequested");
            
            if ($currentStock === false || $currentStock < $totalRequested) {
                throw new Exception("Insufficient stock for item ID: {$item['id']}. Available: $currentStock, Requested: $totalRequested");
            }
        }
        
        // Insert Sale Item
        $stmt = $pdo->prepare("
            INSERT INTO sale_items (sale_id, article_id, quantity, unit_price, total_price)
            VALUES (:sale_id, :article_id, :quantity, :price, :total)
        ");
        $stmt->execute([
            ':sale_id' => $sale_id,
            ':article_id' => $item['id'],
            ':quantity' => $item['quantity'],
            ':price' => $item['unit_price'],
            ':total' => $item['unit_price'] * $item['quantity']
        ]);
        
        // Only update stock for actual sales (not quotes)
        if ($document_type !== 'quote') {
            // Update Stock
            $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity - :qty WHERE article_id = :id");
            $stmt->execute([':qty' => $item['quantity'], ':id' => $item['id']]);
            
            // Log Movement
            $stmt = $pdo->prepare("
                INSERT INTO stock_movements (article_id, type, quantity, source, reference_id, created_at)
                VALUES (:article_id, 'out', :qty, 'sale', :ref_id, NOW())
            ");
            $stmt->execute([
                ':article_id' => $item['id'],
                ':qty' => $item['quantity'],
                ':ref_id' => $sale_id
            ]);
        }
    }
    
    // 3. Record Payment (customer_payments uniquement si client associé)
    if ($advance_payment > 0 && !empty($client_id)) {
        $stmt = $pdo->prepare("
            INSERT INTO customer_payments (sale_id, client_id, payment_amount, payment_mode_id, payment_type, notes, created_at)
            VALUES (:sale_id, :client_id, :advance_payment, :payment_mode_id, 'initial_payment', 'Advance payment from POS', NOW())
        ");
        $stmt->execute([
            ':sale_id' => $sale_id,
            ':client_id' => $client_id,
            ':advance_payment' => $advance_payment,
            ':payment_mode_id' => $payment_mode_id
        ]);
    }
    
    // Record main payment if not fully covered by advance
    $payment_amount = $total - $advance_payment;
    if ($payment_amount > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO payments (entity_type, entity_id, payment_mode_id, amount, payment_date, created_at)
            VALUES ('sale', :sale_id, :payment_mode, :amount, CURDATE(), NOW())
        ");
        $stmt->execute([
            ':sale_id' => $sale_id,
            ':payment_mode' => $payment_mode_id,
            ':amount' => $payment_amount
        ]);
    }
    
    error_log("Payment recorded for sale $sale_id");
    
    $pdo->commit();
    error_log("Transaction committed successfully for sale $sale_id");
    jsonResponse(['success' => true, 'message' => 'Sale completed successfully', 'sale_id' => $sale_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Transaction failed: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    jsonResponse(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()], 500);
}
?>