<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager', 'cashier']);

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;

if ($sale_id <= 0) {
    header('Location: create_return.php');
    exit();
}

// Fetch sale details
$stmt = $pdo->prepare("
    SELECT s.*, u.name as user_name, c.name as client_name, pm.name as payment_mode
    FROM sales s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN clients c ON s.client_id = c.id
    LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id
    WHERE s.id = ? AND s.status IN ('confirmed', 'paid')
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    flash('error', 'Invalid sale or sale cannot be returned.');
    header('Location: create_return.php');
    exit();
}

// Fetch sale items with return tracking
$stmt = $pdo->prepare("
    SELECT si.*, a.name as article_name, a.stock_alert_level,
           (SELECT COALESCE(SUM(quantity), 0) FROM sale_return_items WHERE sale_item_id = si.id) as returned_qty,
           s.quantity as current_stock
    FROM sale_items si
    JOIN articles a ON si.article_id = a.id
    LEFT JOIN stock s ON a.id = s.article_id
    WHERE si.sale_id = ?
    ORDER BY si.id
");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll();

$pageTitle = __('process_return_page') . ' - Sale #' . $sale_id;
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo __('process_return_page'); ?></h1>
                <p class="text-gray-600"><?php echo __('sale_label'); ?> #<?php echo $sale_id; ?> - <?php echo __('return_items_to_stock'); ?></p>
            </div>
            <a href="create_return.php" 
               class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors flex items-center space-x-2">
                <i class="fas fa-arrow-left w-5 h-5"></i>
                <span><?php echo __('back_button'); ?></span>
            </a>
        </div>

        <!-- Sale Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('sale_information'); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-600"><?php echo __('sale_date'); ?></p>
                    <p class="font-medium"><?php echo date('M j, Y H:i', strtotime($sale['created_at'])); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600"><?php echo __('customer_label'); ?></p>
                    <p class="font-medium"><?php echo htmlspecialchars($sale['client_name'] ?? 'Walk-in'); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600"><?php echo __('cashier_label'); ?></p>
                    <p class="font-medium"><?php echo htmlspecialchars($sale['user_name']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600"><?php echo __('payment_method'); ?></p>
                    <p class="font-medium"><?php echo htmlspecialchars($sale['payment_mode'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600"><?php echo __('original_total'); ?></p>
                    <p class="font-medium text-green-600"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($sale['total_amount'], 2); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Status</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <?php echo __($sale['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Return Form -->
        <form id="returnForm" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('return_details_label'); ?></h2>
            
            <div class="mb-6">
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('reason_for_return'); ?> *</label>
                <textarea id="reason" name="reason" rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                          placeholder="e.g., Defective product, customer changed mind, wrong item shipped..." 
                          required></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-4"><?php echo __('items_to_return'); ?></label>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty Sold</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo __('already_returned'); ?></th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo __('return_now'); ?></th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><?php echo __('refund_label'); ?></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($items as $item): 
                                $available = $item['quantity'] - $item['returned_qty'];
                                if ($available <= 0) continue; // Skip fully returned items
                            ?>
                            <tr class="return-item" data-item-id="<?php echo $item['id']; ?>">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['article_name']); ?></div>
                                    <div class="text-xs text-gray-500">Stock: <?php echo $item['current_stock'] ?? 0; ?> | Alert: <?php echo $item['stock_alert_level']; ?></div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-500">
                                    <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($item['unit_price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-900">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-500">
                                    <?php echo $item['returned_qty']; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" 
                                           name="return_qty[<?php echo $item['id']; ?>]"
                                           class="return-qty w-20 px-2 py-1 border border-gray-300 rounded text-center" 
                                           min="0" 
                                           max="<?php echo $available; ?>" 
                                           data-price="<?php echo $item['unit_price']; ?>"
                                           data-available="<?php echo $available; ?>"
                                           value="0">
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-red-600 refund-amount">
                                    <?php echo getSetting('currency_symbol', 'DH'); ?>0.00
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-right font-medium text-gray-900"><?php echo __('total_refund_label'); ?>:</td>
                                <td class="px-6 py-4 text-right font-bold text-red-600 text-lg" id="totalRefund"><?php echo getSetting('currency_symbol', 'DH'); ?>0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="flex justify-end space-x-4">
                <button type="button" onclick="window.location.href='create_return.php'" 
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                    <?php echo __('cancel'); ?>
                </button>
                <button type="submit" 
                        class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                    <i class="fas fa-check mr-2"></i>
                    <?php echo __('process_return_page'); ?>
                </button>
            </div>
        </form>

        <!-- Return Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-blue-900"><?php echo __('return_process_information'); ?></h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li><?php echo __('stock_quantities_will_be_automatically_updated'); ?></li>
                            <li><?php echo __('enter_quantity_to_return_for_each_item'); ?></li>
                            <li><?php echo __('items_fully_returned_will_not_appear'); ?></li>
                            <li><?php echo __('refund_amount_will_be_calculated_automatically'); ?></li>
                            <li><?php echo __('return_reason_required_for_record_keeping'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const returnForm = document.getElementById('returnForm');
    const returnQtyInputs = document.querySelectorAll('.return-qty');
    const totalRefundElement = document.getElementById('totalRefund');

    // Calculate refund amounts
    function calculateRefund() {
        let totalRefund = 0;
        
        returnQtyInputs.forEach(input => {
            const qty = parseInt(input.value) || 0;
            const price = parseFloat(input.dataset.price);
            const refund = qty * price;
            
            // Update individual refund amount
            const row = input.closest('tr');
            const refundElement = row.querySelector('.refund-amount');
            refundElement.textContent = '<?php echo getSetting('currency_symbol', 'DH'); ?>' + refund.toFixed(2);
            
            totalRefund += refund;
        });
        
        totalRefundElement.textContent = '<?php echo getSetting('currency_symbol', 'DH'); ?>' + totalRefund.toFixed(2);
    }

    // Add event listeners for quantity changes
    returnQtyInputs.forEach(input => {
        input.addEventListener('input', calculateRefund);
    });

    // Form submission
    returnForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const reason = document.getElementById('reason').value.trim();
        if (!reason) {
            alert(t('alert_provide_return_reason'));
            return;
        }
        
        const items = [];
        let hasReturns = false;
        
        returnQtyInputs.forEach(input => {
            const qty = parseInt(input.value) || 0;
            if (qty > 0) {
                hasReturns = true;
                items.push({
                    sale_item_id: parseInt(input.closest('tr').dataset.itemId),
                    quantity: qty
                });
            }
        });
        
        if (!hasReturns) {
            alert(t('alert_select_items_return'));
            return;
        }
        
        if (!confirm(t('confirm_process_return'))) {
            return;
        }
        
        // Submit via AJAX
        fetch('../api/sales/return.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                sale_id: <?php echo $sale_id; ?>,
                items: items,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(t('alert_return_processed'));
                window.location.href = 'returns.php';
            } else {
                alert(t('alert_error') + ': ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(t('alert_error_processing_return'));
        });
    });

    // Initial calculation
    calculateRefund();
});
</script>

<?php require_once '../includes/footer.php'; ?>
