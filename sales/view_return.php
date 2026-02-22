<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$return_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($return_id <= 0) {
    header('Location: returns.php');
    exit();
}

// Fetch return details
$stmt = $pdo->prepare("
    SELECT sr.*, u.name as user_name, c.name as client_name, s.invoice_number, s.total_amount as original_sale_total
    FROM sale_returns sr
    JOIN users u ON sr.user_id = u.id
    JOIN sales s ON sr.sale_id = s.id
    LEFT JOIN clients c ON s.client_id = c.id
    WHERE sr.id = ?
");
$stmt->execute([$return_id]);
$return = $stmt->fetch();

if (!$return) {
    flash('error', 'Return not found.');
    header('Location: returns.php');
    exit();
}

// Fetch return items with stock information
$stmt = $pdo->prepare("
    SELECT sri.*, a.name as article_name, a.stock_alert_level,
           (SELECT quantity FROM stock WHERE article_id = a.id LIMIT 1) as current_stock,
           si.unit_price
    FROM sale_return_items sri
    JOIN articles a ON sri.article_id = a.id
    JOIN sale_items si ON sri.sale_item_id = si.id
    WHERE sri.return_id = ?
    ORDER BY sri.id
");
$stmt->execute([$return_id]);
$return_items = $stmt->fetchAll();

$pageTitle = __('return_details') . ' #' . $return_id;
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo __('return_details'); ?></h1>
                <p class="text-gray-600"><?php echo __('return_details'); ?> #<?php echo $return_id; ?> - <?php echo __('stock_impact_and_item_details'); ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="returns.php" 
                   class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors flex items-center space-x-2">
                    <i class="fas fa-arrow-left w-5 h-5"></i>
                    <span><?php echo __('back_to_returns'); ?></span>
                </a>
                <a href="view.php?id=<?php echo $return['sale_id']; ?>" 
                   class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center space-x-2">
                    <i class="fas fa-receipt w-5 h-5"></i>
                    <span><?php echo __('view_original_sale'); ?></span>
                </a>
            </div>
        </div>

        <!-- Return Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('return_date'); ?></p>
                        <p class="text-xl font-bold text-gray-900"><?php echo date('M j, Y', strtotime($return['created_at'])); ?></p>
                        <p class="text-xs text-gray-500"><?php echo date('H:i', strtotime($return['created_at'])); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar text-blue-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_refund'); ?></p>
                        <p class="text-xl font-bold text-red-600"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($return['total_refund'], 2); ?></p>
                        <p class="text-xs text-gray-500">Original sale: <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($return['original_sale_total'], 2); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-red-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('items_returned'); ?></p>
                        <p class="text-xs text-gray-500"><?php echo __('product_lines'); ?></p>
                        <p class="text-xl font-bold text-gray-900"><?php echo count($return_items); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-green-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('processed_by'); ?></p>
                        <p class="text-xs text-gray-500"><?php echo __('cashier_label'); ?></p>
                        <p class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($return['user_name']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user text-purple-600 text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Return Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('return_information'); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-600 mb-2"><?php echo __('sale_details_label'); ?></h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo __('original_sale'); ?>:</span>
                            <span class="font-medium">#<?php echo $return['sale_id']; ?></span>
                        </div>
                        <?php if ($return['invoice_number']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo __('invoice_label'); ?>:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($return['invoice_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo __('customer_label'); ?>:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($return['client_name'] ?? 'Walk-in'); ?></span>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-600 mb-2"><?php echo __('return_details_label'); ?></h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo __('return_id'); ?>:</span>
                            <span class="font-medium">#<?php echo $return_id; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo __('refund_amount'); ?>:</span>
                            <span class="font-medium text-red-600"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($return['total_refund'], 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo __('return_reason'); ?>:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($return['reason']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Returned Items with Stock Impact -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('returned_items_stock_impact'); ?></h2>
            
            <?php if (count($return_items) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('item'); ?></th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><?php echo __('unit_price'); ?></th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo __('quantity_label'); ?></th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><?php echo __('refund_label'); ?></th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo __('stock_impact'); ?></th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo __('current_stock'); ?></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($return_items as $item): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['article_name']); ?></div>
                                    <div class="text-xs text-gray-500">Article ID: #<?php echo $item['article_id']; ?></div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900">
                                    <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($item['unit_price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo $item['quantity']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-red-600">
                                    <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($item['refund_amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        +<?php echo $item['quantity']; ?> <?php echo __('returned_label'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-sm">
                                    <?php 
                                    $current_stock = $item['current_stock'] ?? 0;
                                    $stock_class = $current_stock <= $item['stock_alert_level'] ? 'text-red-600 font-medium' : 'text-gray-900';
                                    ?>
                                    <span class="<?php echo $stock_class; ?>">
                                        <?php echo $current_stock; ?>
                                    </span>
                                    <?php if ($current_stock <= $item['stock_alert_level']): ?>
                                        <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="<?php echo __('low_stock_alert'); ?>"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right font-medium text-gray-900"><?php echo __('total_label'); ?>:</td>
                                <td class="px-6 py-4 text-right font-bold text-red-600 text-lg"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($return['total_refund'], 2); ?></td>
                                <td class="px-6 py-4 text-center font-medium text-green-600">
                                    +<?php echo array_sum(array_column($return_items, 'quantity')); ?> <?php echo __('items_label'); ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-inbox text-gray-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo __('no_items_found'); ?></h3>
                    <p class="text-gray-600"><?php echo __('no_return_items_were_found_for_this_return'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stock Movement Summary -->
        <div class="bg-green-50 border border-green-200 rounded-xl p-6">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-warehouse text-green-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-green-900 mb-2"><?php echo __('stock_movement_summary'); ?></h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p><?php echo __('this_return_has_restored_items_to_inventory'); ?> <strong><?php echo array_sum(array_column($return_items, 'quantity')); ?> items</strong> <?php echo __('items_to_inventory_across_product_lines'); ?> <strong><?php echo count($return_items); ?></strong> <?php echo __('product_lines_label'); ?>.</p>
                        <p class="mt-2"><?php echo __('all_stock_movements_have_been_automatically_recorded_in_system'); ?> (ID: #<?php echo $return_id; ?>).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>