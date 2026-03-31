<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager', 'cashier']);
$pageTitle = __('sales_history', 'Sales History');
require_once '../includes/header.php';

// Fetch Sales
$stmt = $pdo->query("
    SELECT s.*, u.name as user_name, c.name as client_name, pm.name as payment_mode
    FROM sales s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN clients c ON s.client_id = c.id
    LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id
    ORDER BY s.created_at DESC
    LIMIT 50
");
$sales = $stmt->fetchAll();
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo __('sales_history', 'Sales History'); ?></h1>
                <p class="text-gray-600"><?php echo __('view_and_manage_all_sales_transactions', 'View and manage all sales transactions'); ?></p>
            </div>
            <a href="pos.php" 
               class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center space-x-2">
                <i class="fas fa-cash-register"></i>
                <span><?php echo __('new_sale', 'New Sale'); ?></span>
            </a>
        </div>

        <!-- Sales Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900"><?php echo __('recent_sales', 'Recent Sales'); ?></h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('id', 'ID'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('date', 'Date'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('cashier', 'Cashier'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('customer', 'Customer'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('total', 'Total'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('payment', 'Payment'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('status', 'Status'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($sales) > 0): ?>
                            <?php foreach ($sales as $s): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">#<?php echo $s['id']; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo date('M j, Y H:i', strtotime($s['created_at'])); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span class="text-blue-600 font-medium text-sm">
                                                <?php echo strtoupper(substr($s['user_name'], 0, 2)); ?>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($s['user_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($s['client_name'] ?? 'Walk-in'); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-gray-900">$<?php echo number_format($s['total_amount'], 2); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($s['payment_mode']); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <?php echo ucfirst($s['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view.php?id=<?php echo $s['id']; ?>" class="text-blue-600 hover:text-blue-900 font-medium transition-colors">
                                        <?php echo __('view_details', 'View Details'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-file-alt text-gray-400 text-3xl mb-4"></i>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo __('no_sales_found', 'No sales found'); ?></h3>
                                        <p class="text-gray-500"><?php echo __('no_sales_transactions_have_been_recorded_yet', 'No sales transactions have been recorded yet'); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
