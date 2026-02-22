<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle Delete
if (isset($_POST['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        flash('success', __('supplier_deleted_successfully', 'Supplier deleted successfully!'));
        header('Location: list.php');
        exit();
    } catch (PDOException $e) {
        flash('error', __('error_deleting_supplier', 'Error deleting supplier:') . ' ' . $e->getMessage());
    }
}

// Fetch Supplier Details
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = :id");
$stmt->execute([':id' => $id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    header('Location: list.php');
    exit();
}

// Handle purchase search and filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for purchases
$query = "SELECT p.* FROM purchases p 
          WHERE p.supplier_id = :supplier_id";

$params = [':supplier_id' => $id];

if ($search) {
    $query .= " AND (p.invoice_number LIKE :search OR p.total_amount LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($status) {
    $query .= " AND p.status = :status";
    $params[':status'] = $status;
}

if ($date_from) {
    $query .= " AND DATE(p.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(p.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$purchase_history = $stmt->fetchAll();

// Calculate totals
$total_purchases = array_sum(array_column($purchase_history, 'total_amount'));
$total_paid = array_sum(array_column($purchase_history, 'paid_amount'));
$calculated_balance = $total_purchases - $total_paid;
$database_balance = $supplier['balance'] ?? 0;

$pageTitle = __('supplier_details', 'Supplier Details');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('supplier_details', 'Supplier Details'); ?></h1>
                    <p class="mt-1 text-gray-600"><?php echo __('view_supplier_information_and_purchase_history', 'View supplier information and purchase history'); ?></p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="edit.php?id=<?php echo $supplier['id']; ?>" 
                       class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition-colors duration-200">
                        <i class="fas fa-edit mr-2"></i>
                        <?php echo __('edit_supplier', 'Edit Supplier'); ?>
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

        <!-- Supplier Information Card -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden mb-8">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-8 py-6">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($supplier['name']); ?></h2>
                        <p class="text-purple-100 mt-1"><?php echo __('supplier_since', 'Supplier since'); ?> <?php echo date('F j, Y', strtotime($supplier['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Card Content -->
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Contact Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('contact_information', 'Contact Information'); ?></h3>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-phone text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('phone', 'Phone'); ?></p>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($supplier['phone'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-envelope text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('email', 'Email'); ?></p>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($supplier['email'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fas fa-map-marker-alt text-purple-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('address', 'Address'); ?></p>
                                    <p class="text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($supplier['address'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('financial_summary', 'Financial Summary'); ?></h3>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-red-600"><?php echo __('total_transactions', 'Total transactions'); ?></p>
                                        <p class="text-2xl font-bold text-red-900"><?php echo number_format($database_balance, 2); ?> DH</p>
                                        <?php if (abs($database_balance - $calculated_balance) > 0.01): ?>
                                            <p class="text-xs text-orange-600 mt-1"><?php echo __('rest', 'Rest'); ?>: <?php echo number_format($calculated_balance, 2); ?> DH</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-dollar-sign text-red-500 text-2xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase History -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-green-600 to-teal-600 px-8 py-6">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-history text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white"><?php echo __('purchase_history', 'Purchase History'); ?></h2>
                        <p class="text-green-100 mt-1"><?php echo __('complete_purchase_transactions_and_payment_history', 'Complete purchase transactions and payment history'); ?></p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <!-- Filters and Search -->
                <div class="mb-6 bg-white border border-gray-200 rounded-lg p-4">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('search', 'Search'); ?></label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="<?php echo __('search_placeholder_invoice', 'Invoice # or Amount'); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('status', 'Status'); ?></label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                                <option value=""><?php echo __('all_status', 'All Status'); ?></option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>><?php echo __('pending', 'Pending'); ?></option>
                                <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>><?php echo __('paid', 'Paid'); ?></option>
                                <option value="partial" <?php echo $status === 'partial' ? 'selected' : ''; ?>><?php echo __('partial', 'Partial'); ?></option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('from_date', 'From Date'); ?></label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('to_date', 'To Date'); ?></label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div class="md:col-span-5 flex justify-end space-x-2">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-search mr-2"></i>
                                <?php echo __('filter', 'Filter'); ?>
                            </button>
                            <a href="view.php?id=<?php echo $id; ?>" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>
                                <?php echo __('reset', 'Reset'); ?>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Purchase Summary -->
                <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-teal-50 border border-green-200 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('purchase_summary', 'Purchase Summary'); ?></h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-file-invoice-dollar text-green-600 text-2xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('total_purchases', 'Total Purchases'); ?></p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_purchases, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-dollar-sign text-blue-600 text-2xl"></i>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('total_paid', 'Total Paid'); ?></p>
                                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($total_paid, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-orange-600 text-2xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('balance_due', 'Balance Due'); ?></p>
                                    <p class="text-2xl font-bold text-orange-600"><?php echo number_format($calculated_balance, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purchases List -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('invoice_number', 'Invoice #'); ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('date', 'Date'); ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('total_amount', 'Total Amount'); ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('paid_amount', 'Paid Amount'); ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('balance', 'Balance'); ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('status', 'Status'); ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('actions', 'Actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($purchase_history)): ?>
                                <?php foreach ($purchase_history as $purchase): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?php echo $purchase['invoice_number']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y', strtotime($purchase['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo number_format($purchase['total_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                            <?php echo number_format($purchase['paid_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php 
                                            $balance = $purchase['total_amount'] - $purchase['paid_amount'];
                                            $color = $balance <= 0 ? 'text-green-600' : 'text-orange-600';
                                            echo '<span class="' . $color . '">' . number_format($balance, 2) . ' ' . getSetting('currency_symbol', 'DH') . '</span>';
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php 
                                            $balance = $purchase['total_amount'] - $purchase['paid_amount'];
                                            if ($balance <= 0) {
                                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">' . __('paid', 'Paid') . '</span>';
                                            } else {
                                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">' . __('partial', 'Partial') . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="pay_purchase.php?id=<?php echo $purchase['id']; ?>" 
                                               class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                                                <?php echo __('pay_balance', 'Pay Balance'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-file-alt text-gray-400 text-3xl mb-4"></i>
                                            <p class="text-lg font-medium"><?php echo __('no_purchases_found', 'No purchases found'); ?></p>
                                            <p class="text-sm text-gray-400"><?php echo __('supplier_has_no_purchases_yet', 'This supplier hasn\'t made any purchases yet'); ?></p>
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
                    <h3 class="text-lg font-semibold text-red-900"><?php echo __('danger_zone', 'Danger Zone'); ?></h3>
                    <p class="text-red-700 mt-1"><?php echo __('delete_supplier_warning', 'Once you delete a supplier, there is no going back. Please be certain.'); ?></p>
                </div>
                <button onclick="if(confirm(t('confirm_delete_supplier'))) { document.getElementById('deleteForm').submit(); }" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors duration-200">
                    <i class="fas fa-trash mr-2"></i>
                    <?php echo __('delete_supplier', 'Delete Supplier'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="delete" value="1">
</form>

<?php require_once '../includes/footer.php'; ?>