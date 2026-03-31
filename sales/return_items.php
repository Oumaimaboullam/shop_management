<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager', 'cashier']);

$sale_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sale_id <= 0) {
    header('Location: list.php');
    exit();
}

// Fetch Sale
$stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale || $sale['status'] === 'cancelled') {
    flash('error', 'Invalid sale or sale is cancelled.');
    header('Location: view.php?id=' . $sale_id);
    exit();
}

// Fetch Sale Items with available quantity check
$stmt = $pdo->prepare("
    SELECT si.*, a.name as article_name, 
           (SELECT COALESCE(SUM(quantity), 0) FROM sale_return_items WHERE sale_item_id = si.id) as returned_qty
    FROM sale_items si
    JOIN articles a ON si.article_id = a.id
    WHERE si.sale_id = ?
");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = __('return_items_page') . ' - Sale #' . $sale_id;
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><?php echo __('return_items_page'); ?></h1>
                <a href="view.php?id=<?php echo $sale_id; ?>" class="text-gray-600 hover:text-gray-900">
                    <?php echo __('cancel'); ?>
                </a>
            </div>

            <form id="returnForm" onsubmit="submitReturn(event)">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('reason_for_return'); ?></label>
                    <textarea id="reason" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" rows="2" placeholder="e.g., Defective, Customer changed mind" required></textarea>
                </div>

                <div class="overflow-x-auto border rounded-lg mb-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('item'); ?></th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><?php echo __('sold_price'); ?></th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo __('qty_sold'); ?></th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo __('returned'); ?></th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo __('return_now'); ?></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($items as $item): 
                                $remaining = $item['quantity'] - $item['returned_qty'];
                                if ($remaining <= 0) continue;
                            ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['article_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-500">
                                    $<?php echo number_format($item['unit_price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-900">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-500">
                                    <?php echo $item['returned_qty']; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="number" 
                                           class="return-qty w-20 px-2 py-1 border border-gray-300 rounded text-center" 
                                           min="0" 
                                           max="<?php echo $remaining; ?>" 
                                           data-id="<?php echo $item['id']; ?>"
                                           value="0">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        <?php echo __('process_return_button'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function submitReturn(e) {
    e.preventDefault();
    
    const reason = document.getElementById('reason').value;
    const inputs = document.querySelectorAll('.return-qty');
    const items = [];
    
    inputs.forEach(input => {
        const qty = parseInt(input.value);
        if (qty > 0) {
            items.push({
                sale_item_id: input.dataset.id,
                quantity: qty
            });
        }
    });
    
    if (items.length === 0) {
        alert(t('alert_select_items_return'));
        return;
    }
    
    if (!confirm(t('confirm_process_return'))) {
        return;
    }
    
    try {
        const res = await fetch('../api/sales/return.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                sale_id: <?php echo $sale_id; ?>,
                items: items,
                reason: reason
            })
        });
        
        const data = await res.json();
        
        if (data.success) {
            alert(t('alert_return_processed'));
            window.location.href = 'view.php?id=<?php echo $sale_id; ?>';
        } else {
            alert(t('alert_error') + ': ' + data.message);
        }
    } catch (err) {
        alert(t('alert_network_error'));
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
