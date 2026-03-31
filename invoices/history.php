<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager', 'cashier']);

// Handle search and filters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$customer_id = $_GET['customer_id'] ?? '';

// Build comprehensive query
$query = "SELECT s.*, c.name as customer_name, u.name as user_name, pm.name as payment_mode 
          FROM sales s 
          LEFT JOIN clients c ON s.client_id = c.id 
          LEFT JOIN users u ON s.user_id = u.id 
          LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id 
          WHERE 1=1";

$params = [];

if ($search) {
    $query .= " AND (s.invoice_number LIKE :search OR c.name LIKE :search OR s.total_amount LIKE :search)";
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

if ($customer_id) {
    $query .= " AND s.client_id = :customer_id";
    $params[':customer_id'] = $customer_id;
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
$invoices = $stmt->fetchAll();

// Get customers for dropdown
$customers = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();

// Calculate totals
$total_amount = array_sum(array_column($invoices, 'total_amount'));
$total_paid = array_sum(array_column($invoices, 'paid_amount'));
$total_balance = $total_amount - $total_paid;

// Add cache-busting headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');

// Set page title
$pageTitle = __('history', 'History');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Modern Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-history text-white"></i>
                        </div>
                        <?php echo __('history', 'History'); ?>
                    </h1>
                    <p class="mt-1 text-gray-600"><?php echo __('invoice_quote_history_description', 'View and manage all your invoices, quotes, and sales history'); ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-2">
                        <div class="text-sm text-gray-600"><?php echo __('total_records', 'Total Records'); ?>:</div>
                        <div class="text-lg font-semibold text-gray-900"><?php echo count($invoices); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Amount Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-calculator text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_amount_label', 'Total Amount'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($total_amount, 2); ?></p>
                        <div class="flex items-center mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-chart-line mr-1"></i><?php echo __('all_time', 'All Time'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Paid Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-check-circle text-green-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_paid_label', 'Total Paid'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($total_paid, 2); ?></p>
                        <div class="flex items-center mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-dollar-sign mr-1"></i><?php echo __('collected', 'Collected'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outstanding Balance Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-clock text-orange-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_balance_label', 'Outstanding Balance'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($total_balance, 2); ?></p>
                        <div class="flex items-center mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                <i class="fas fa-exclamation-triangle mr-1"></i><?php echo __('pending', 'Pending'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Documents Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-file-alt text-purple-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_documents', 'Total Documents'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($invoices); ?></p>
                        <div class="flex items-center mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <i class="fas fa-list mr-1"></i><?php echo __('records', 'Records'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Filters Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-500 p-2 rounded-lg mr-3">
                    <i class="fas fa-filter text-white"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900"><?php echo __('advanced_filters', 'Advanced Filters'); ?></h2>
                    <p class="text-sm text-gray-600"><?php echo __('filter_search_description', 'Filter and search through your invoice history'); ?></p>
                </div>
            </div>

            <form method="GET" class="space-y-6">
                <!-- Primary Filters Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <!-- Search Input -->
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-search mr-2 text-gray-500"></i>
                            <?php echo __('search_label', 'Search'); ?>
                        </label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="<?php echo __('invoice_customer_or_amount', 'Invoice number, customer name, or amount'); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>

                    <!-- Document Type -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-file-alt mr-2 text-gray-500"></i>
                            <?php echo __('document_type_label', 'Type'); ?>
                        </label>
                        <select name="type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                            <option value=""><?php echo __('all_types', 'All Types'); ?></option>
                            <option value="invoice" <?php echo $type === 'invoice' ? 'selected' : ''; ?>><?php echo __('invoice', 'Invoice'); ?></option>
                            <option value="sale" <?php echo $type === 'sale' ? 'selected' : ''; ?>><?php echo __('sale_label', 'Sale'); ?></option>
                            <option value="quote" <?php echo $type === 'quote' ? 'selected' : ''; ?>><?php echo __('quote', 'Quote'); ?></option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-info-circle mr-2 text-gray-500"></i>
                            <?php echo __('status_header', 'Status'); ?>
                        </label>
                        <select name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                            <option value=""><?php echo __('all_status', 'All Status'); ?></option>
                            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>><?php echo __('draft', 'Draft'); ?></option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>><?php echo __('confirmed', 'Confirmed'); ?></option>
                            <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>><?php echo __('paid', 'Paid'); ?></option>
                            <option value="partial" <?php echo $status === 'partial' ? 'selected' : ''; ?>><?php echo __('partial', 'Partial'); ?></option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>><?php echo __('cancelled', 'Cancelled'); ?></option>
                        </select>
                    </div>

                    <!-- Customer Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-user mr-2 text-gray-500"></i>
                            <?php echo __('customer_header', 'Customer'); ?>
                        </label>
                        <select name="customer_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                            <option value=""><?php echo __('all_customers', 'All Customers'); ?></option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" <?php echo $customer_id == $customer['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Date Range Filters -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-gray-500"></i>
                            <?php echo __('from_date_label', 'From Date'); ?>
                        </label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-calendar-check mr-2 text-gray-500"></i>
                            <?php echo __('to_date_label', 'To Date'); ?>
                        </label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-search"></i>
                            <?php echo __('apply_filters', 'Apply Filters'); ?>
                        </button>
                        <a href="history.php" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-times"></i>
                            <?php echo __('clear_all', 'Clear All'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Enhanced Data Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="bg-gradient-to-r from-green-500 to-teal-500 p-2 rounded-lg">
                            <i class="fas fa-table text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900"><?php echo __('invoice_history', 'Invoice History'); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo __('showing_results', 'Showing') . ' ' . count($invoices) . ' ' . __('records', 'records'); ?></p>
                        </div>
                    </div>
                    <?php if (count($invoices) > 0): ?>
                    <div class="text-sm text-gray-500">
                        <?php echo __('last_updated', 'Last updated'); ?>: <?php echo date('M j, Y H:i'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (count($invoices) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('date_header', 'Date'); ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('invoice_header', 'Invoice'); ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('customer_header', 'Customer'); ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('type_header', 'Type'); ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('amount_header', 'Amount'); ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('paid_header', 'Paid'); ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('balance_header', 'Balance'); ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('status_header', 'Status'); ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('created_by_header', 'Created By'); ?>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('actions_header', 'Actions'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($invoices as $invoice): ?>
                            <?php
                            $balance = $invoice['total_amount'] - $invoice['paid_amount'];
                            $type_class = '';
                            $type_icon = '';
                            switch($invoice['document_type']) {
                                case 'invoice':
                                    $type_class = 'bg-blue-100 text-blue-800 border-blue-200';
                                    $type_icon = 'fa-file-invoice';
                                    break;
                                case 'sale':
                                    $type_class = 'bg-green-100 text-green-800 border-green-200';
                                    $type_icon = 'fa-shopping-cart';
                                    break;
                                case 'quote':
                                    $type_class = 'bg-orange-100 text-orange-800 border-orange-200';
                                    $type_icon = 'fa-calculator';
                                    break;
                            }

                            $status_class = '';
                            $status_icon = '';
                            switch($invoice['status']) {
                                case 'draft':
                                    $status_class = 'bg-gray-100 text-gray-800 border-gray-200';
                                    $status_icon = 'fa-edit';
                                    break;
                                case 'confirmed':
                                    $status_class = 'bg-blue-100 text-blue-800 border-blue-200';
                                    $status_icon = 'fa-check';
                                    break;
                                case 'paid':
                                    $status_class = 'bg-green-100 text-green-800 border-green-200';
                                    $status_icon = 'fa-check-circle';
                                    break;
                                case 'partial':
                                    $status_class = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                    $status_icon = 'fa-clock';
                                    break;
                                case 'cancelled':
                                    $status_class = 'bg-red-100 text-red-800 border-red-200';
                                    $status_icon = 'fa-times-circle';
                                    break;
                            }
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <div class="text-gray-900 font-medium"><?php echo date('M j, Y', strtotime($invoice['created_at'])); ?></div>
                                        <div class="text-gray-500 ml-2"><?php echo date('H:i', strtotime($invoice['created_at'])); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php if ($invoice['invoice_number']): ?>
                                                <span class="font-mono"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">N/A</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-user text-gray-600 text-xs"></i>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($invoice['customer_name'] ?? __('walk_in_customer', 'Walk-in Customer')); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border <?php echo $type_class; ?>">
                                        <i class="fas <?php echo $type_icon; ?> mr-1"></i>
                                        <?php echo __($invoice['document_type'], ucfirst($invoice['document_type'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    <?php echo number_format($invoice['total_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">
                                    <?php echo number_format($invoice['paid_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($balance > 0): ?>
                                        <span class="text-orange-600"><?php echo number_format($balance, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></span>
                                    <?php else: ?>
                                        <span class="text-green-600"><?php echo __('paid', 'Paid'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?> mr-1"></i>
                                        <?php echo __($invoice['status'], ucfirst($invoice['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <div class="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center mr-2">
                                            <i class="fas fa-user text-gray-600 text-xs"></i>
                                        </div>
                                        <?php echo htmlspecialchars($invoice['user_name'] ?? __('unknown', 'Unknown')); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="view.php?id=<?php echo $invoice['id']; ?>"
                                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors duration-150">
                                            <i class="fas fa-eye mr-1"></i>
                                            <?php echo __('view_button', 'View'); ?>
                                        </a>
                                        <?php if ($invoice['document_type'] === 'quote' && $invoice['status'] === 'draft'): ?>
                                        <a href="convert.php?id=<?php echo $invoice['id']; ?>"
                                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 transition-colors duration-150">
                                            <i class="fas fa-exchange-alt mr-1"></i>
                                            <?php echo __('convert_button', 'Convert'); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-alt text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo __('no_invoices_found', 'No invoices found'); ?></h3>
                <p class="text-gray-600 mb-6"><?php echo __('no_invoices_quotes_found', 'No invoices or quotes found matching your criteria.'); ?></p>
                <div class="flex justify-center gap-3">
                    <a href="create.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors duration-150">
                        <i class="fas fa-plus mr-2"></i>
                        <?php echo __('create_new_invoice', 'Create New Invoice'); ?>
                    </a>
                    <a href="history.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-150">
                        <i class="fas fa-times mr-2"></i>
                        <?php echo __('clear_filters', 'Clear Filters'); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="create.php" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <i class="fas fa-plus mr-2"></i>
                <?php echo __('create_new_invoice', 'Create New Invoice'); ?>
            </a>
            <a href="../sales/pos.php" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <i class="fas fa-cash-register mr-2"></i>
                <?php echo __('go_to_pos', 'Go to POS'); ?>
            </a>
            <a href="../reports/analytics_modern.php" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <i class="fas fa-chart-line mr-2"></i>
                <?php echo __('view_analytics', 'View Analytics'); ?>
            </a>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
