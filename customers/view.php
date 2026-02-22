<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle Delete
if (isset($_POST['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        flash('success', __('customer_deleted_successfully', 'Customer deleted successfully!'));
        header('Location: list.php');
        exit();
    } catch (PDOException $e) {
        flash('error', 'Error deleting customer: ' . $e->getMessage());
    }
}

// Fetch Customer Details
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
$stmt->execute([':id' => $id]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: list.php');
    exit();
}

// Handle invoice/quote search and filters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for sales/invoices/quotes
$query = "SELECT s.*, u.name as user_name, pm.name as payment_mode FROM sales s 
          LEFT JOIN users u ON s.user_id = u.id 
          LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id 
          WHERE s.client_id = :client_id";

$params = [':client_id' => $id];

if ($search) {
    $query .= " AND (s.invoice_number LIKE :search OR s.total_amount LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($type) {
    $query .= " AND s.document_type = :type";
    $params[':type'] = $type;
}

if ($status) {
    $query .= " AND s.status = :status";
    $params[':status'] = $status;
}

if ($date_from) {
    $query .= " AND DATE(s.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(s.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales_history = $stmt->fetchAll();

// Calculate totals
$total_sales = array_sum(array_column($sales_history, 'total_amount'));
$total_paid = array_sum(array_column($sales_history, 'paid_amount'));
$total_balance = $total_sales - $total_paid;

$pageTitle = __('view_product_title', 'Customer Details');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Customer Details</h1>
                    <p class="mt-1 text-gray-600">View customer information and transaction history</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="edit.php?id=<?php echo $client['id']; ?>" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                        <i class="fas fa-edit mr-2"></i>
                        <?php echo __('edit_customer', 'Edit Customer'); ?>
                    </a>
                    <a href="list.php" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?php echo __('back_to_list', 'Back to List'); ?>
                    </a>
                </div>
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

        <!-- Customer Information Card -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden mb-8">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-8 py-6">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-user text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($client['name']); ?></h2>
                        <p class="text-blue-100 mt-1">Customer since <?php echo date('F j, Y', strtotime($client['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Card Content -->
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Contact Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-phone text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('phone_number_required', 'Phone'); ?></p>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-envelope text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('email_address_label', 'Email'); ?></p>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($client['email'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-map-marker-alt text-purple-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('address_label', 'Address'); ?></p>
                                    <p class="text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($client['address'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Summary</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-blue-600">Total Sales</p>
                                        <p class="text-2xl font-bold text-blue-900"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($total_sales, 2); ?></p>
                                    </div>
                                    <i class="fas fa-shopping-cart text-blue-500"></i>
                                </div>
                            </div>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-green-600">Total Paid</p>
                                        <p class="text-2xl font-bold text-green-900"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($total_paid, 2); ?></p>
                                    </div>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                            </div>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-red-600"><?php echo __('balance_due', 'Balance Due'); ?></p>
                                        <p class="text-2xl font-bold text-red-900"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($total_balance, 2); ?></p>
                                    </div>
                                    <i class="fas fa-clock text-red-500"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-history text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white"><?php echo __('transaction_history', 'Transaction History'); ?></h2>
                        <p class="text-indigo-100 mt-1"><?php echo __('complete_payment_history', 'Complete payment history and remaining balances'); ?></p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <!-- Filters and Search -->
                <div class="mb-6 bg-white border border-gray-200 rounded-lg p-4">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Transaction ID or Amount" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Types</option>
                                <option value="sale" <?php echo $type === 'sale' ? 'selected' : ''; ?>>Sale</option>
                                <option value="invoice" <?php echo $type === 'invoice' ? 'selected' : ''; ?>>Invoice</option>
                                <option value="quote" <?php echo $type === 'quote' ? 'selected' : ''; ?>>Quote</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="remaining" <?php echo $status === 'remaining' ? 'selected' : ''; ?>>Remaining</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="md:col-span-5 flex justify-end space-x-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-search mr-2"></i>
                                <?php echo __('filter', 'Filter'); ?>
                            </button>
                            <a href="view.php?id=<?php echo $id; ?>" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                <i class="fas fa-sync-alt mr-2"></i>
                                <?php echo __('reset', 'Reset'); ?>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Transaction Summary -->
                <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-list text-blue-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-500">Total Transactions</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo count($sales_history); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-dollar-sign text-blue-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('total_sales', 'Total Sales'); ?></p>
                                    <p class="text-2xl font-bold text-blue-600"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($total_sales, 2); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-credit-card text-green-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('total_advance_paid', 'Total Advance Paid'); ?></p>
                                    <p class="text-2xl font-bold text-green-600"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format(array_sum(array_column($sales_history, 'advance_payment')), 2); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('total_remaining', 'Total Remaining'); ?></p>
                                    <p class="text-2xl font-bold text-red-600"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format(array_sum(array_column($sales_history, 'total_amount')) - array_sum(array_column($sales_history, 'advance_payment')), 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions List -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Advance Paid</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($sales_history as $sale): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?php echo $sale['id']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($sale['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo ucfirst($sale['document_type']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($sale['total_amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                        <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($sale['advance_payment'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php 
                                        $remaining = $sale['total_amount'] - $sale['advance_payment'];
                                        $color = $remaining <= 0 ? 'text-green-600' : 'text-red-600';
                                        echo '<span class="' . $color . '">' . getSetting('currency_symbol', 'DH') . number_format($remaining, 2) . '</span>';
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php 
                                        $remaining = $sale['total_amount'] - $sale['advance_payment'];
                                        if ($remaining <= 0) {
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Paid</span>';
                                        } else {
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Remaining</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php 
                                        $remaining = $sale['total_amount'] - $sale['advance_payment'];
                                        if ($remaining > 0) {
                                            echo '<button onclick="payRemaining(' . $sale['id'] . ', ' . $remaining . ')" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">Pay Rest</button>';
                                        } else {
                                            echo '<span class="text-gray-400">-</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($sales_history)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-file-alt text-gray-400 text-3xl mb-4"></i>
                                            <p class="text-lg font-medium"><?php echo __('no_transactions_found', 'No transactions found'); ?></p>
                                            <p class="text-sm text-gray-400"><?php echo __('customer_havent_made_any_purchases_yet', 'This customer hasn\'t made any purchases yet'); ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="mt-8 bg-red-50 border border-red-200 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-red-900">Danger Zone</h3>
                    <p class="text-red-700 mt-1">Once you delete a customer, there is no going back. Please be certain.</p>
                </div>
                <button onclick="if(confirm('<?php echo __('confirm_delete_customer', 'Are you sure you want to delete this customer? This action cannot be undone.'); ?>')) { document.getElementById('deleteForm').submit(); }" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors duration-200">
                    <i class="fas fa-trash mr-2"></i>
                    <?php echo __('delete_customer', 'Delete Customer'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="delete" value="1">
</form>

<script>
function payRemaining(transactionId, remainingAmount) {
    // Create modal for better UX
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">Payment for Transaction #${transactionId}</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remaining Amount</label>
                    <div class="text-2xl font-bold text-red-600">${remainingAmount.toFixed(2)} DH</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount</label>
                    <input type="number" 
                           id="paymentAmount" 
                           value="${remainingAmount.toFixed(2)}" 
                           step="0.01" 
                           min="0.01" 
                           max="${remainingAmount.toFixed(2)}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Mode</label>
                    <select id="paymentMode" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <?php
                        $modes = $pdo->query("SELECT * FROM payment_modes")->fetchAll();
                        foreach ($modes as $mode):
                            echo '<option value="' . $mode['id'] . '">' . htmlspecialchars($mode['name']) . '</option>';
                        endforeach;
                        ?>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button onclick="this.closest('.fixed').remove()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button onclick="processPayment(${transactionId})" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Process Payment
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close modal on outside click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

function processPayment(transactionId) {
    const paymentAmount = parseFloat(document.getElementById('paymentAmount').value);
    const paymentMode = parseInt(document.getElementById('paymentMode').value);
    
    if (!paymentAmount || paymentAmount <= 0) {
        alert('Please enter a valid payment amount');
        return;
    }
    
    // Send AJAX request
    fetch('../api/customers/pay_remaining.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            transaction_id: transactionId,
            payment_amount: paymentAmount,
            payment_mode_id: paymentMode
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Payment successful - directly refresh the page
            window.location.reload();
        } else {
            // Show error
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Payment Failed</h3>
                    <p class="text-red-600 mb-4">${data.message}</p>
                    <button onclick="this.closest('.fixed').remove()" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                        Close
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Payment error:', error);
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h8m-9-4h4.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Network Error</h3>
                <p class="text-red-600 mb-4">Failed to process payment. Please try again.</p>
                <button onclick="this.closest('.fixed').remove()" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                    Close
                </button>
            </div>
        `;
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>