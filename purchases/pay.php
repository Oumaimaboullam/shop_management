<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

// Get purchase ID from URL
$purchase_id = $_GET['id'] ?? null;
$supplier_id = $_GET['supplier_id'] ?? null;

if ($supplier_id && !$purchase_id) {
    // Supplier payment - show all unpaid purchases for this supplier
    $stmt = $pdo->prepare("
        SELECT * FROM purchases 
        WHERE supplier_id = ? AND status != 'paid'
        ORDER BY created_at ASC
    ");
    $stmt->execute([$supplier_id]);
    $unpaid_purchases = $stmt->fetchAll();
    
    $total_balance = array_sum(array_column($unpaid_purchases, 'total_amount')) - array_sum(array_column($unpaid_purchases, 'paid_amount'));
    
    $pageTitle = __('make_payment_supplier', 'Effectuer un paiement - Fournisseur');
    require_once '../includes/header.php';
    ?>
    
    <div class="min-h-screen bg-gray-50 p-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900"><?php echo __('supplier_payment', 'Paiement fournisseur'); ?></h1>
                        <p class="text-gray-600"><?php echo __('pay_all_unpaid_purchases', 'Payer tous les achats impayés pour ce fournisseur'); ?></p>
                    </div>
                    <a href="../suppliers/balance.php" 
                       class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <?php echo __('back_to_balance', 'Retour au solde'); ?>
                    </a>
                </div>
            </div>

            <!-- Unpaid Purchases -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900"><?php echo __('unpaid_purchases', 'Achats impayés'); ?></h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('date', 'Date'); ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('invoice', 'Facture'); ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('total', 'Total'); ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('paid', 'Payé'); ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('balance', 'Solde'); ?></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($unpaid_purchases as $purchase): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4"><?php echo date('M j, Y', strtotime($purchase['created_at'])); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($purchase['invoice_number']); ?></td>
                                    <td class="px-6 py-4"><?php echo number_format($purchase['total_amount'], 2); ?> DH</td>
                                    <td class="px-6 py-4"><?php echo number_format($purchase['paid_amount'], 2); ?> DH</td>
                                    <td class="px-6 py-4 font-bold text-red-600"><?php echo number_format($purchase['total_amount'] - $purchase['paid_amount'], 2); ?> DH</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="2" class="px-6 py-4 font-bold"><?php echo __('total_label', 'Total:'); ?></td>
                                <td class="px-6 py-4 font-bold"><?php echo number_format(array_sum(array_column($unpaid_purchases, 'total_amount')), 2); ?> DH</td>
                                <td class="px-6 py-4 font-bold"><?php echo number_format(array_sum(array_column($unpaid_purchases, 'paid_amount')), 2); ?> DH</td>
                                <td class="px-6 py-4 font-bold text-red-600"><?php echo number_format($total_balance, 2); ?> DH</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Record Payment</h2>
                </div>
                <form method="POST" action="" class="p-6 space-y-6">
                    <input type="hidden" name="supplier_id" value="<?php echo $supplier_id; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('payment_amount_required', 'Montant du paiement *'); ?></label>
                            <input type="number" 
                                   name="payment_amount" 
                                   step="0.01" 
                                   min="0.01" 
                                   max="<?php echo $total_balance; ?>"
                                   required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="0.00">
                            <p class="text-sm text-gray-500 mt-1"><?php echo __('maximum', 'Maximum:'); ?> <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($total_balance, 2); ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('payment_type_required', 'Type de paiement *'); ?></label>
                            <select name="payment_type" 
                                    required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value=""><?php echo __('select_payment_type', 'Sélectionner le type de paiement'); ?></option>
                                <option value="cash"><?php echo __('cash', 'Espèces'); ?></option>
                                <option value="check"><?php echo __('check', 'Chèque'); ?></option>
                                <option value="transfer"><?php echo __('bank_transfer', 'Virement bancaire'); ?></option>
                                <option value="card"><?php echo __('credit_card', 'Carte de crédit'); ?></option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('payment_date', 'Date de paiement'); ?></label>
                            <input type="date" 
                                   name="payment_date" 
                                   value="<?php echo date('Y-m-d'); ?>"
                                   required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('notes', 'Notes'); ?></label>
                            <textarea name="notes" 
                                      rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="<?php echo __('optional_payment_notes', 'Notes de paiement optionnelles'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="../suppliers/balance.php" 
                           class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            <?php echo __('cancel', 'Annuler'); ?>
                        </a>
                        <button type="submit" 
                                class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors font-semibold">
                            <?php echo __('record_payment_button', 'Enregistrer le paiement'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php
    require_once '../includes/footer.php';
    exit();
}

if (!$purchase_id) {
    flash('error', __('purchase_id_required', 'ID de commande requis'));
    header('Location: list.php');
    exit();
}

// Fetch purchase details
$stmt = $pdo->prepare("
    SELECT p.*, s.name as supplier_name
    FROM purchases p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.id = ?
");
$stmt->execute([$purchase_id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    flash('error', __('purchase_not_found', 'Commande non trouvée'));
    header('Location: list.php');
    exit();
}

// Fetch purchase items
$stmt = $pdo->prepare("
    SELECT pi.*, a.name as article_name
    FROM purchase_items pi
    LEFT JOIN articles a ON pi.article_id = a.id
    WHERE pi.purchase_id = ?
");
$stmt->execute([$purchase_id]);
$items = $stmt->fetchAll();

// Calculate remaining amount
$remaining_amount = $purchase['total_amount'] - $purchase['paid_amount'];

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a supplier payment
    $supplier_id = $_POST['supplier_id'] ?? null;
    
    if ($supplier_id) {
        // Supplier payment - distribute payment across oldest unpaid purchases
        $payment_amount = floatval($_POST['payment_amount']);
        $payment_type = $_POST['payment_type'];
        $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
        $notes = $_POST['notes'] ?? '';
        
        if ($payment_amount <= 0) {
            $error = __('payment_amount_must_be_greater', 'Le montant du paiement doit être supérieur à 0');
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error]);
                exit();
            } else {
                flash('error', $error);
            }
        } else {
            try {
                $pdo->beginTransaction();
                
                // Get unpaid purchases for this supplier
                $stmt = $pdo->prepare("
                    SELECT id, total_amount, paid_amount 
                    FROM purchases 
                    WHERE supplier_id = ? AND status != 'paid'
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$supplier_id]);
                $unpaid_purchases = $stmt->fetchAll();
                
                $remaining_payment = $payment_amount;
                
                // Distribute payment across purchases
                foreach ($unpaid_purchases as $purchase) {
                    if ($remaining_payment <= 0) break;
                    
                    $purchase_balance = $purchase['total_amount'] - $purchase['paid_amount'];
                    $payment_for_this = min($remaining_payment, $purchase_balance);
                    
                    // Update purchase paid amount
                    $new_paid_amount = $purchase['paid_amount'] + $payment_for_this;
                    $new_status = ($new_paid_amount >= $purchase['total_amount']) ? 'paid' : 'partial';
                    
                    $stmt = $pdo->prepare("
                        UPDATE purchases 
                        SET paid_amount = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$new_paid_amount, $new_status, $purchase['id']]);
                    
                    // Record payment transaction
                    $stmt = $pdo->prepare("
                        INSERT INTO purchase_payments (purchase_id, amount, payment_type, payment_date, notes, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$purchase['id'], $payment_for_this, $payment_type, $payment_date, $notes]);
                    
                    // Update supplier balance
                    $stmt = $pdo->prepare("
                        UPDATE suppliers 
                        SET balance = balance - ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$payment_for_this, $supplier_id]);
                    
                    $remaining_payment -= $payment_for_this;
                }
                
                $pdo->commit();
                
                $success = __('payment_recorded_successfully', 'Paiement enregistré avec succès!');
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $success]);
                    exit();
                } else {
                    flash('success', $success);
                    header('Location: ../suppliers/balance.php');
                    exit();
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = __('error_recording_payment', 'Erreur lors de l\'enregistrement du paiement: ') . $e->getMessage();
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit();
                } else {
                    flash('error', $error);
                }
            }
        }
    } else {
        $payment_amount = floatval($_POST['payment_amount']);
        $payment_type = $_POST['payment_type'];
        $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
        $notes = $_POST['notes'] ?? '';
        
        if ($payment_amount <= 0) {
            $error = __('payment_amount_must_be_greater', 'Le montant du paiement doit être supérieur à 0');
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error]);
                exit();
            } else {
                flash('error', $error);
            }
        } elseif ($payment_amount > $remaining_amount) {
            $error = 'Payment amount cannot exceed remaining balance';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error]);
                exit();
            } else {
                flash('error', $error);
            }
        } else {
            try {
                $pdo->beginTransaction();
                
                // Check if purchase_payments table exists
                $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_payments'");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($tables)) {
                    // Create purchase_payments table
                    $pdo->exec("
                        CREATE TABLE purchase_payments (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            purchase_id INT NOT NULL,
                            amount DECIMAL(10,2) NOT NULL,
                            payment_type ENUM('cash', 'check', 'transfer', 'card') NOT NULL,
                            payment_date DATE NOT NULL,
                            notes TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (purchase_id) REFERENCES purchases(id)
                        )
                    ");
                }
                
                // Update purchase paid amount
                $new_paid_amount = $purchase['paid_amount'] + $payment_amount;
                $new_status = ($new_paid_amount >= $purchase['total_amount']) ? 'paid' : 'partial';
                
                $stmt = $pdo->prepare("
                    UPDATE purchases 
                    SET paid_amount = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$new_paid_amount, $new_status, $purchase_id]);
                
                // Record payment transaction
                $stmt = $pdo->prepare("
                    INSERT INTO purchase_payments (purchase_id, amount, payment_type, payment_date, notes, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$purchase_id, $payment_amount, $payment_type, $payment_date, $notes]);
                
                $pdo->commit();
                
                $success = __('payment_recorded_successfully', 'Paiement enregistré avec succès!');
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $success]);
                    exit();
                } else {
                    flash('success', $success);
                    header('Location: view.php?id=' . $purchase_id);
                    exit();
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = __('error_recording_payment', 'Erreur lors de l\'enregistrement du paiement: ') . $e->getMessage();
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit();
                } else {
                    flash('error', $error);
                }
            }
        }
    }
}

$pageTitle = __('make_payment_purchase', 'Effectuer un paiement') . ' - Purchase #' . $purchase_id;
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('make_payment_purchase', 'Effectuer un paiement'); ?></h1>
                    <p class="text-gray-600"><?php echo __('record_payment_for_purchase', 'Enregistrer le paiement pour la commande #'); ?><?php echo $purchase_id; ?></p>
                </div>
                <a href="view.php?id=<?php echo $purchase_id; ?>" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <?php echo __('back_to_purchase', 'Retour à la commande'); ?>
                </a>
            </div>
        </div>

        <!-- Purchase Summary Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900"><?php echo __('purchase_summary', 'Résumé de la commande'); ?></h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600"><?php echo __('supplier_label', 'Fournisseur'); ?></p>
                        <p class="font-semibold"><?php echo htmlspecialchars($purchase['supplier_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600"><?php echo __('invoice_number', 'Numéro de facture'); ?></p>
                        <p class="font-semibold"><?php echo htmlspecialchars($purchase['invoice_number']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600"><?php echo __('total_amount', 'Montant total'); ?></p>
                        <p class="font-semibold text-blue-600"><?php echo number_format($purchase['total_amount'], 2); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600"><?php echo __('already_paid', 'Déjà payé'); ?></p>
                        <p class="font-semibold text-green-600"><?php echo number_format($purchase['paid_amount'], 2); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600"><?php echo __('remaining_balance', 'Solde restant'); ?></p>
                        <p class="font-bold text-red-600"><?php echo number_format($remaining_amount, 2); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600"><?php echo __('status', 'Statut'); ?></p>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                            <?php
                            switch ($purchase['status']) {
                                case 'paid':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'partial':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                default:
                                    echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($purchase['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Summary -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900"><?php echo __('items', 'Articles'); ?></h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('product', 'Produit'); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('quantity', 'Quantité'); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('unit_price', 'Prix unitaire'); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('total', 'Total'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($item['article_name']); ?></td>
                                <td class="px-4 py-3"><?php echo $item['quantity']; ?></td>
                                <td class="px-4 py-3"><?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="px-4 py-3 font-semibold"><?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Form -->
        <?php if ($remaining_amount > 0): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Record Payment</h2>
                </div>
                <form method="POST" action="" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('payment_amount_required', 'Montant du paiement *'); ?></label>
                            <input type="number" 
                                   name="payment_amount" 
                                   step="0.01" 
                                   min="0.01" 
                                   max="<?php echo $remaining_amount; ?>"
                                   required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="0.00">
                            <p class="text-sm text-gray-500 mt-1">Maximum: <?php echo number_format($remaining_amount, 2); ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('payment_type_required', 'Type de paiement *'); ?></label>
                            <select name="payment_type" 
                                    required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value=""><?php echo __('select_payment_type', 'Sélectionner le type de paiement'); ?></option>
                                <option value="cash"><?php echo __('cash', 'Espèces'); ?></option>
                                <option value="check"><?php echo __('check', 'Chèque'); ?></option>
                                <option value="transfer"><?php echo __('bank_transfer', 'Virement bancaire'); ?></option>
                                <option value="card"><?php echo __('credit_card', 'Carte de crédit'); ?></option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('payment_date', 'Date de paiement'); ?></label>
                            <input type="date" 
                                   name="payment_date" 
                                   value="<?php echo date('Y-m-d'); ?>"
                                   required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('notes', 'Notes'); ?></label>
                            <textarea name="notes" 
                                      rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="<?php echo __('optional_payment_notes', 'Notes de paiement optionnelles'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="view.php?id=<?php echo $purchase_id; ?>" 
                           class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            <?php echo __('cancel', 'Annuler'); ?>
                        </a>
                        <button type="submit" 
                                class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors font-semibold">
                            <?php echo __('record_payment_button', 'Enregistrer le paiement'); ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                <svg class="w-16 h-16 text-green-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-green-800 mb-2"><?php echo __('fully_paid', 'Entièrement payé'); ?></h3>
                <p class="text-green-700"><?php echo __('this_purchase_fully_paid', 'Cette commande a été complètement payée.'); ?></p>
                <a href="view.php?id=<?php echo $purchase_id; ?>" 
                   class="mt-4 inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <?php echo __('view_purchase_details', 'Voir les détails de la commande'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
