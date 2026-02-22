<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$purchase_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Purchase Details
$stmt = $pdo->prepare("
    SELECT p.*, s.name as supplier_name, s.id as supplier_id 
    FROM purchases p 
    JOIN suppliers s ON p.supplier_id = s.id 
    WHERE p.id = :id
");
$stmt->execute([':id' => $purchase_id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    flash('error', __('purchase_not_found', 'Purchase not found!'));
    header('Location: list.php');
    exit();
}

// Handle Payment Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_amount = floatval($_POST['payment_amount']);
    $payment_mode_id = intval($_POST['payment_mode_id']);
    $payment_notes = trim($_POST['payment_notes'] ?? '');
    
    if ($payment_amount <= 0) {
        flash('error', __('payment_amount_must_be_greater_than_zero', 'Payment amount must be greater than 0!'));
    } else if ($payment_amount > ($purchase['total_amount'] - $purchase['paid_amount'])) {
        flash('error', __('payment_amount_cannot_exceed_remaining_balance', 'Payment amount cannot exceed remaining balance!'));
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update purchase paid amount
            $new_paid_amount = $purchase['paid_amount'] + $payment_amount;
            $new_balance = $purchase['total_amount'] - $new_paid_amount;
            $new_status = ($new_balance <= 0) ? 'paid' : 'partial';
            
            $stmt = $pdo->prepare("
                UPDATE purchases 
                SET paid_amount = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$new_paid_amount, $new_status, $purchase_id]);
            
            // Record payment in payments table
            $stmt = $pdo->prepare("
                INSERT INTO payments (entity_type, entity_id, payment_mode_id, amount, payment_date, created_at)
                VALUES ('purchase', ?, ?, ?, CURDATE(), NOW())
            ");
            $stmt->execute([$purchase_id, $payment_mode_id, $payment_amount]);
            
            // Update supplier balance
            $stmt = $pdo->prepare("
                UPDATE suppliers 
                SET balance = balance - ?
                WHERE id = ?
            ");
            $stmt->execute([$payment_amount, $purchase['supplier_id']]);
            
            $pdo->commit();
            
            flash('success', __('payment_recorded_successfully', 'Payment recorded successfully!'));
            header("Location: view.php?id={$purchase['supplier_id']}");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollback();
            flash('error', __('error_processing_payment', 'Error processing payment: ') . $e->getMessage());
        }
    }
}

$pageTitle = __('pay_purchase_balance', 'Pay Purchase Balance');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('pay_purchase_balance', 'Pay Purchase Balance'); ?></h1>
                    <p class="mt-1 text-gray-600"><?php echo __('process_payment_for_purchase_invoice', 'Process payment for purchase invoice'); ?></p>
                </div>
                <a href="view.php?id=<?php echo $purchase['supplier_id']; ?>" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <?php echo __('back_to_supplier', 'Back to Supplier'); ?>
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($msg = flash('success')): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <span class="text-green-800"><?php echo $msg; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($msg = flash('error')): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <span class="text-red-800"><?php echo $msg; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Purchase Information Card -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-green-600 to-teal-600 px-8 py-6">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-file-invoice-dollar text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white"><?php echo __('invoice', 'Invoice'); ?> #<?php echo $purchase['invoice_number']; ?></h2>
                        <p class="text-green-100 mt-1"><?php echo __('supplier', 'Supplier'); ?>: <?php echo htmlspecialchars($purchase['supplier_name']); ?></p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 mb-2"><?php echo __('total_amount', 'Total Amount'); ?></h3>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($purchase['total_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></p>
                    </div>
                    
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 mb-2"><?php echo __('already_paid', 'Already Paid'); ?></h3>
                        <p class="text-2xl font-bold text-blue-600"><?php echo number_format($purchase['paid_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></p>
                    </div>
                    
                    <div class="bg-orange-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 mb-2"><?php echo __('remaining_balance', 'Remaining Balance'); ?></h3>
                        <p class="text-2xl font-bold text-orange-600"><?php echo number_format($purchase['total_amount'] - $purchase['paid_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-dollar-sign text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white"><?php echo __('process_payment', 'Process Payment'); ?></h2>
                        <p class="text-blue-100 mt-1"><?php echo __('enter_payment_details_below', 'Enter payment details below'); ?></p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="payment_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo __('payment_amount', 'Payment Amount'); ?> *
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">
                                    <?php echo getSetting('currency_symbol', 'DH'); ?>
                                </span>
                                <input type="number" 
                                       id="payment_amount" 
                                       name="payment_amount" 
                                       value="<?php echo number_format($purchase['total_amount'] - $purchase['paid_amount'], 2); ?>"
                                       step="0.01" 
                                       min="0.01" 
                                       max="<?php echo number_format($purchase['total_amount'] - $purchase['paid_amount'], 2); ?>"
                                       required
                                       class="pl-8 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                <?php echo __('maximum', 'Maximum'); ?>: <?php echo number_format($purchase['total_amount'] - $purchase['paid_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>
                            </p>
                        </div>
                        
                        <div>
                            <label for="payment_mode_id" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo __('payment_mode', 'Payment Mode'); ?> *
                            </label>
                            <select id="payment_mode_id" 
                                    name="payment_mode_id" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <?php
                                $modes = $pdo->query("SELECT * FROM payment_modes ORDER BY name")->fetchAll();
                                foreach ($modes as $mode):
                                ?>
                                    <option value="<?php echo $mode['id']; ?>">
                                        <?php 
                                        $key = 'payment_mode_' . strtolower(str_replace([' ', '-'], '_', $mode['name']));
                                        echo htmlspecialchars(__('' . $key, $mode['name']));
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="payment_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo __('payment_notes', 'Payment Notes'); ?>
                        </label>
                        <textarea id="payment_notes" 
                                  name="payment_notes" 
                                  rows="3"
                                  placeholder="<?php echo __('enter_payment_notes_or_reference', 'Enter any payment notes or reference...'); ?>"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <a href="view.php?id=<?php echo $purchase['supplier_id']; ?>" 
                           class="px-6 py-3 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                            <?php echo __('cancel', 'Cancel'); ?>
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            <?php echo __('process_payment', 'Process Payment'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
