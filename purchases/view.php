<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Purchase Details
$stmt = $pdo->prepare("
    SELECT p.*, s.name as supplier_name, s.phone as supplier_phone, s.email as supplier_email, s.address as supplier_address
    FROM purchases p 
    JOIN suppliers s ON p.supplier_id = s.id 
    WHERE p.id = :id
");
$stmt->execute([':id' => $id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    header('Location: list.php');
    exit();
}

// Fetch Purchase Items
$stmt = $pdo->prepare("
    SELECT pi.*, a.name as product_name, a.barcode as product_code
    FROM purchase_items pi
    JOIN articles a ON pi.article_id = a.id
    WHERE pi.purchase_id = :id
    ORDER BY pi.id
");
$stmt->execute([':id' => $id]);
$items = $stmt->fetchAll();

$pageTitle = __('purchase_order_details');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('purchase_order_details'); ?></h1>
                    <p class="mt-1 text-gray-600"><?php echo __('view_purchase_information_items'); ?></p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="list.php" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <?php echo __('back_to_list'); ?>
                    </a>
                    <?php if ($purchase['status'] !== 'paid'): ?>
                        <a href="pay.php?id=<?php echo $purchase['id']; ?>" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <?php echo __('make_payment'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Purchase Information Card -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden mb-8">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white"><?php echo __('purchase_order_number'); ?><?php echo $purchase['id']; ?></h2>
                        <p class="text-blue-100 mt-1"><?php echo __('invoice'); ?>: <?php echo htmlspecialchars($purchase['invoice_number']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-100 text-sm"><?php echo __('date'); ?></p>
                        <p class="text-white font-semibold"><?php echo date('F j, Y', strtotime($purchase['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Card Content -->
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Supplier Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('supplier_information'); ?></h3>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.259-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.259.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('supplier'); ?></p>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($purchase['supplier_name']); ?></p>
                                </div>
                            </div>
                            <?php if ($purchase['supplier_phone']): ?>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 2.493a1 1 0 01.948.684l1.498-2.493A1 1 0 0110.28 3H14a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('phone'); ?></p>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($purchase['supplier_phone']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($purchase['supplier_email']): ?>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0l7.89-5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('email'); ?></p>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($purchase['supplier_email']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($purchase['supplier_address']): ?>
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?php echo __('address'); ?></p>
                                    <p class="text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($purchase['supplier_address']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Purchase Summary -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('purchase_summary'); ?></h3>
                        <div class="space-y-4">
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600"><?php echo __('total_amount'); ?>:</span>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($purchase['total_amount'], 2); ?> DH</p>
                                </div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600"><?php echo __('paid_amount'); ?>:</span>
                                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($purchase['paid_amount'], 2); ?> DH</p>
                                </div>
                                <div class="border-t border-gray-300 pt-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-medium text-gray-900"><?php echo __('balance'); ?>:</span>
                                        <span class="text-xl font-bold <?php echo ($purchase['total_amount'] - $purchase['paid_amount']) > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                            <?php echo number_format($purchase['total_amount'] - $purchase['paid_amount'], 2); ?> DH
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-blue-600"><?php echo __('status'); ?></p>
                                        <p class="text-2xl font-bold text-blue-900"><?php echo ucfirst($purchase['status']); ?></p>
                                    </div>
                                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <?php if ($purchase['status'] !== 'paid'): ?>
                            <!-- Quick Payment Form -->
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-green-600 mb-3"><?php echo __('quick_payment'); ?></h4>
                                <form id="quickPaymentForm" class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1"><?php echo __('payment_amount'); ?></label>
                                        <input type="number" 
                                               id="quickPaymentAmount"
                                               step="0.01" 
                                               min="0.01" 
                                               max="<?php echo ($purchase['total_amount'] - $purchase['paid_amount']); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                               placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1"><?php echo __('payment_type'); ?></label>
                                        <select id="quickPaymentType" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                            <option value="cash"><?php echo __('cash'); ?></option>
                                            <option value="check"><?php echo __('check'); ?></option>
                                            <option value="transfer"><?php echo __('bank_transfer'); ?></option>
                                            <option value="card"><?php echo __('credit_card'); ?></option>
                                        </select>
                                    </div>
                                    <button type="button" 
                                            onclick="makeQuickPayment()"
                                            class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors font-medium">
                                        <?php echo __('record_payment'); ?>
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Items -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden">
            <div class="px-8 py-6 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900"><?php echo __('purchase_items'); ?></h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><?php echo __('product'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><?php echo __('code'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><?php echo __('quantity'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><?php echo __('unit_price'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><?php echo __('total'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($item['product_code']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo number_format($item['quantity'], 2); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo number_format($item['unit_price'], 2); ?> DH</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900"><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?> DH</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <?php echo __('no_items_found_purchase'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function makeQuickPayment() {
    console.log('makeQuickPayment called');
    
    const amount = document.getElementById('quickPaymentAmount').value;
    const paymentType = document.getElementById('quickPaymentType').value;
    const remainingAmount = <?php echo ($purchase['total_amount'] - $purchase['paid_amount']); ?>;
    
    console.log('Payment data:', { amount, paymentType, remainingAmount });
    
    if (!amount || amount <= 0) {
        alert('<?php echo __("please_enter_valid_payment_amount"); ?>');
        return;
    }
    
    if (parseFloat(amount) > remainingAmount) {
        alert('<?php echo __("payment_amount_cannot_exceed"); ?> ' + remainingAmount.toFixed(2));
        return;
    }
    
    if (confirm('<?php echo __("are_you_sure_record_payment"); ?> ' + parseFloat(amount).toFixed(2) + '?')) {
        // Create form data
        const formData = new FormData();
        formData.append('payment_amount', amount);
        formData.append('payment_type', paymentType);
        formData.append('payment_date', new Date().toISOString().split('T')[0]);
        formData.append('notes', 'Quick payment from view page');
        
        console.log('Sending request...');
        
        // Send payment request
        fetch('pay.php?id=<?php echo $purchase['id']; ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            console.log('Response received:', response);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            if (data.success) {
                alert('<?php echo __("payment_recorded_successfully"); ?>');
                location.reload();
            } else {
                alert('<?php echo __("error"); ?>: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('<?php echo __("error_occurred_processing_payment"); ?> ' + error.message);
        });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
