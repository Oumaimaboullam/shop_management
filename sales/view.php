<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$sale_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sale_id <= 0) {
    header('Location: list.php');
    exit();
}

// Fetch Sale Details
$stmt = $pdo->prepare("
    SELECT s.*, u.name as user_name, c.name as client_name, pm.name as payment_mode
    FROM sales s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN clients c ON s.client_id = c.id
    LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    header('Location: list.php');
    exit();
}

// Fetch Sale Items
$stmt = $pdo->prepare("
    SELECT si.*, a.name as article_name, a.barcode
    FROM sale_items si
    JOIN articles a ON si.article_id = a.id
    WHERE si.sale_id = ?
");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Returns
$stmt = $pdo->prepare("
    SELECT sr.*, u.name as user_name 
    FROM sale_returns sr
    JOIN users u ON sr.user_id = u.id
    WHERE sr.sale_id = ?
    ORDER BY sr.created_at DESC
");
$stmt->execute([$sale_id]);
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = __('sale_details') . ' #' . $sale_id;
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('sale_label'); ?> #<?php echo $sale_id; ?></h1>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold 
                        <?php echo $sale['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                        <?php echo __($sale['status']); ?>
                    </span>
                </div>
                <p class="text-gray-600 mt-1"><?php echo __('processed_on'); ?> <?php echo date('M j, Y H:i', strtotime($sale['created_at'])); ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="list.php" class="bg-white text-gray-700 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <?php echo __('back_to_list'); ?>
                </a>
                <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    <?php echo __('print'); ?>
                </button>
                
                <?php if ($sale['status'] !== 'cancelled'): ?>
                    <a href="return_items.php?id=<?php echo $sale_id; ?>" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        <?php echo __('return_items'); ?>
                    </a>
                    
                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                        <button onclick="voidSale()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php echo __('void_sale'); ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Items -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Items Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900"><?php echo __('items_purchased'); ?></h2>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('item'); ?></th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><?php echo __('price'); ?></th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo __('qty'); ?></th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><?php echo __('total'); ?></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['article_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['barcode']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-500">
                                    <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($item['unit_price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-900">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                    <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($item['total_price'], 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right font-bold text-gray-900"><?php echo __('total_amount'); ?></td>
                                <td class="px-6 py-4 text-right font-bold text-blue-600 text-lg">
                                    <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($sale['total_amount'], 2); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Returns History -->
                <?php if (count($returns) > 0): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900"><?php echo __('return_history'); ?></h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($returns as $return): ?>
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="text-sm font-semibold text-red-600"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($return['total_refund'], 2); ?></span>
                                    <span class="text-xs text-gray-500 ml-2"><?php echo __('by_label'); ?> <?php echo htmlspecialchars($return['user_name']); ?></span>
                                </div>
                                <span class="text-xs text-gray-500"><?php echo date('M j, Y H:i', strtotime($return['created_at'])); ?></span>
                            </div>
                            <?php if ($return['reason']): ?>
                                <p class="text-sm text-gray-600 italic">"<?php echo htmlspecialchars($return['reason']); ?>"</p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Info -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4"><?php echo __('sale_information'); ?></h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600"><?php echo __('cashier'); ?></dt>
                            <dd class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['user_name']); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600"><?php echo __('customer'); ?></dt>
                            <dd class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['client_name'] ?? 'Walk-in'); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600"><?php echo __('payment_method'); ?></dt>
                            <dd class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['payment_mode']); ?></dd>
                        </div>
                        <?php if ($sale['invoice_number']): ?>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600"><?php echo __('invoice_no'); ?></dt>
                            <dd class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['invoice_number']); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function voidSale() {
    if (!confirm(t('confirm_void_sale'))) {
        return;
    }

    const reason = prompt(t('prompt_void_reason'));
    if (reason === null) return;

    try {
        const res = await fetch('../api/sales/void.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                sale_id: <?php echo $sale_id; ?>,
                reason: reason
            })
        });
        
        const data = await res.json();
        
        if (data.success) {
            alert(t('alert_sale_voided'));
            location.reload();
        } else {
            alert(t('alert_error') + ' ' + data.message);
        }
    } catch (err) {
        alert(t('alert_network_error'));
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>