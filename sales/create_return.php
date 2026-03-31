<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager', 'cashier']);

// Fetch recent sales for return selection
$stmt = $pdo->query("
    SELECT s.*, u.name as user_name, c.name as client_name, pm.name as payment_mode,
           (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) as item_count
    FROM sales s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN clients c ON s.client_id = c.id
    LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id
    WHERE s.status IN ('confirmed', 'paid')
    ORDER BY s.created_at DESC
    LIMIT 20
");
$sales = $stmt->fetchAll();

$pageTitle = __('create_return');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo __('create_return'); ?></h1>
                <p class="text-gray-600"><?php echo __('process_a_new_return_for_a_sale'); ?></p>
            </div>
            <a href="returns.php" 
               class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors flex items-center space-x-2">
                <i class="fas fa-arrow-left w-5 h-5"></i>
                <span><?php echo __('back_to_returns'); ?></span>
            </a>
        </div>

        <!-- Select Sale Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('select_sale_for_return'); ?></h2>
            <p class="text-gray-600 mb-4"><?php echo __('choose_a_sale_from_list_below_to_process_a_return'); ?></p>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('sale_id'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('date'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('customer'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('cashier'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('total'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('items'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($sales) > 0): ?>
                            <?php foreach ($sales as $sale): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">#<?php echo $sale['id']; ?></span>
                                    <?php echo $sale['item_count']; ?> <?php echo __('items'); ?>
                                    <?php if ($sale['invoice_number']): ?>
                                        <br><span class="text-xs text-gray-500">Inv: <?php echo htmlspecialchars($sale['invoice_number']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo date('M j, Y H:i', strtotime($sale['created_at'])); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($sale['client_name'] ?? 'Walk-in'); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($sale['user_name']); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-gray-900">$<?php echo number_format($sale['total_amount'], 2); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo $sale['item_count']; ?> items</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="process_return.php?sale_id=<?php echo $sale['id']; ?>" 
                                       class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
                                        <i class="fas fa-undo mr-1"></i>
                                        <?php echo __('process_return'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo __('no_eligible_sales_found'); ?></h3>
                                        <p class="text-gray-500 mb-4"><?php echo __('only_confirmed_or_paid_sales_can_be_returned'); ?></p>
                                        <a href="list.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                            <i class="fas fa-list mr-2"></i>
                                            <?php echo __('view_all_sales'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Return Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-blue-900"><?php echo __('return_process_instructions'); ?></h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li><?php echo __('select_a_sale_from_list_above_to_process_a_return'); ?></li>
                            <li><?php echo __('only_confirmed_or_paid_sales_are_eligible_for_returns'); ?></li>
                            <li><?php echo __('stock_quantities_will_be_automatically_updated_when_items_are_returned'); ?></li>
                            <li><?php echo __('a_reason_for_return_is_required_for_each_transaction'); ?></li>
                            <li><?php echo __('partial_returns_are_supported_you_can_return_specific_items_or_quantities'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
