<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

// Add cache-busting headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');

// Get invoice ID from URL
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: history.php');
    exit;
}

// Fetch invoice data
$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
           c.address as customer_address, u.name as user_name, pm.name as payment_mode
    FROM sales s
    LEFT JOIN clients c ON s.client_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: history.php');
    exit;
}

// Fetch invoice items
$stmt = $pdo->prepare("
    SELECT si.*, a.name as product_name, a.description as product_description
    FROM sale_items si
    LEFT JOIN articles a ON si.article_id = a.id
    WHERE si.sale_id = ?
    ORDER BY si.id
");
$stmt->execute([$id]);
$invoice_items = $stmt->fetchAll();

// Fetch payment history
$stmt = $pdo->prepare("
    SELECT p.*, pm.name as payment_method, p.created_at as payment_date
    FROM payments p
    LEFT JOIN payment_modes pm ON p.payment_mode_id = pm.id
    WHERE p.entity_type IN ('sale', 'invoice') AND p.entity_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$id]);
$payments = $stmt->fetchAll();

// Calculate totals
$subtotal = array_sum(array_column($invoice_items, 'total_price'));
$tax_amount = $invoice['tax_amount'] ?? 0;
$discount_amount = $invoice['discount_amount'] ?? 0;
$total_amount = $invoice['total_amount'];
$paid_amount = $invoice['paid_amount'];
$balance = $total_amount - $paid_amount;

// Set page title
$pageTitle = __('invoice_details', 'Invoice Details') . ' - ' . ($invoice['invoice_number'] ?: 'N/A');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-invoice text-white"></i>
                        </div>
                        <?php echo __('invoice_details', 'Invoice Details'); ?>
                        <span class="text-lg font-medium text-gray-600">#<?php echo htmlspecialchars($invoice['invoice_number'] ?: 'N/A'); ?></span>
                    </h1>
                    <p class="mt-1 text-gray-600"><?php echo __('view_invoice_information', 'View and manage invoice information, payments, and details'); ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-2">
                        <div class="text-sm text-gray-600"><?php echo __('status', 'Status'); ?>:</div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                            <?php
                            switch($invoice['status']) {
                                case 'draft': echo 'bg-gray-100 text-gray-800'; break;
                                case 'confirmed': echo 'bg-blue-100 text-blue-800'; break;
                                case 'paid': echo 'bg-green-100 text-green-800'; break;
                                case 'partial': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo __($invoice['status'], ucfirst($invoice['status'])); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mb-8">
            <div class="flex flex-wrap gap-3">
                <a href="history.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <?php echo __('back_to_history', 'Back to History'); ?>
                </a>

                <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-print mr-2"></i>
                    <?php echo __('print_invoice', 'Print Invoice'); ?>
                </button>

                <?php if ($invoice['status'] === 'draft'): ?>
                <a href="edit.php?id=<?php echo $invoice['id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-edit mr-2"></i>
                    <?php echo __('edit_invoice', 'Edit Invoice'); ?>
                </a>
                <?php endif; ?>

                <?php if ($balance > 0): ?>
                <a href="../suppliers/pay_purchase.php?sale_id=<?php echo $invoice['id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                    <i class="fas fa-credit-card mr-2"></i>
                    <?php echo __('record_payment', 'Record Payment'); ?>
                </a>
                <?php endif; ?>

                <button onclick="downloadPDF()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-purple-600 hover:bg-purple-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    <?php echo __('download_pdf', 'Download PDF'); ?>
                </button>
            </div>
        </div>

        <!-- Invoice Header Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Invoice Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-500 p-2 rounded-lg mr-3">
                        <i class="fas fa-info-circle text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900"><?php echo __('invoice_information', 'Invoice Information'); ?></h2>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600"><?php echo __('invoice_number', 'Invoice Number'); ?></label>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($invoice['invoice_number'] ?: 'N/A'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600"><?php echo __('invoice_date', 'Invoice Date'); ?></label>
                            <p class="text-lg font-semibold text-gray-900"><?php echo date('M j, Y', strtotime($invoice['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600"><?php echo __('document_type', 'Document Type'); ?></label>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                <?php
                                switch($invoice['document_type']) {
                                    case 'invoice': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'sale': echo 'bg-green-100 text-green-800'; break;
                                    case 'quote': echo 'bg-orange-100 text-orange-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <i class="fas fa-<?php
                                switch($invoice['document_type']) {
                                    case 'invoice': echo 'file-invoice'; break;
                                    case 'sale': echo 'shopping-cart'; break;
                                    case 'quote': echo 'calculator'; break;
                                    default: echo 'file';
                                }
                                ?> mr-1"></i>
                                <?php echo __($invoice['document_type'], ucfirst($invoice['document_type'])); ?>
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600"><?php echo __('payment_method', 'Payment Method'); ?></label>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($invoice['payment_mode'] ?: __('not_specified', 'Not Specified')); ?></p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600"><?php echo __('notes', 'Notes'); ?></label>
                        <p class="text-gray-900"><?php echo htmlspecialchars($invoice['notes'] ?? __('no_notes', 'No notes available')); ?></p>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="bg-gradient-to-r from-green-500 to-teal-500 p-2 rounded-lg mr-3">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900"><?php echo __('customer_information', 'Customer Information'); ?></h2>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600"><?php echo __('customer_name', 'Customer Name'); ?></label>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($invoice['customer_name'] ?: __('walk_in_customer', 'Walk-in Customer')); ?></p>
                    </div>

                    <?php if ($invoice['customer_phone']): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-600"><?php echo __('phone', 'Phone'); ?></label>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($invoice['customer_phone']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($invoice['customer_email']): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-600"><?php echo __('email', 'Email'); ?></label>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($invoice['customer_email']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($invoice['customer_address']): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-600"><?php echo __('address', 'Address'); ?></label>
                        <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($invoice['customer_address'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-2 rounded-lg mr-3">
                        <i class="fas fa-list text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900"><?php echo __('invoice_items', 'Invoice Items'); ?></h3>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('item', 'Item'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('description', 'Description'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('quantity', 'Qty'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('unit_price', 'Unit Price'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('total', 'Total'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($invoice_items as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-box text-gray-600 text-xs"></i>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($item['product_description'] ?: __('no_description', 'No description available')); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo number_format($item['quantity'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo number_format($item['unit_price'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo number_format($item['total_price'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Invoice Totals -->
            <div class="bg-gray-50 px-6 py-4">
                <div class="flex justify-end">
                    <div class="w-full max-w-md space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600"><?php echo __('subtotal', 'Subtotal'); ?>:</span>
                            <span class="text-gray-900 font-medium"><?php echo number_format($subtotal, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></span>
                        </div>

                        <?php if ($tax_amount > 0): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600"><?php echo __('tax', 'Tax'); ?>:</span>
                            <span class="text-gray-900 font-medium"><?php echo number_format($tax_amount, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($discount_amount > 0): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600"><?php echo __('discount', 'Discount'); ?>:</span>
                            <span class="text-red-600 font-medium">(-<?php echo number_format($discount_amount, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>)</span>
                        </div>
                        <?php endif; ?>

                        <div class="border-t border-gray-300 pt-2">
                            <div class="flex justify-between text-lg font-bold">
                                <span class="text-gray-900"><?php echo __('total', 'Total'); ?>:</span>
                                <span class="text-gray-900"><?php echo number_format($total_amount, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Payment Summary -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 p-2 rounded-lg mr-3">
                        <i class="fas fa-credit-card text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900"><?php echo __('payment_summary', 'Payment Summary'); ?></h3>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600"><?php echo __('total_amount', 'Total Amount'); ?></div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo number_format($total_amount, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600"><?php echo __('paid_amount', 'Paid Amount'); ?></div>
                            <div class="text-2xl font-bold text-green-600"><?php echo number_format($paid_amount, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></div>
                        </div>
                    </div>

                    <div class="bg-orange-50 rounded-lg p-4">
                        <div class="text-sm text-gray-600"><?php echo __('outstanding_balance', 'Outstanding Balance'); ?></div>
                        <div class="text-2xl font-bold <?php echo $balance > 0 ? 'text-orange-600' : 'text-green-600'; ?>">
                            <?php echo number_format($balance, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>
                        </div>
                        <div class="text-xs text-gray-600 mt-1">
                            <?php echo $balance > 0 ? __('amount_due', 'Amount due') : __('fully_paid', 'Fully paid'); ?>
                        </div>
                    </div>

                    <?php if ($balance > 0): ?>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-600"><?php echo __('payment_progress', 'Payment Progress'); ?></div>
                                <div class="text-lg font-semibold text-blue-600"><?php echo round(($paid_amount / $total_amount) * 100, 1); ?>%</div>
                            </div>
                            <div class="w-16 h-16 relative">
                                <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#E5E7EB" stroke-width="2" stroke-dasharray="100, 100"/>
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#3B82F6" stroke-width="2" stroke-dasharray="<?php echo ($paid_amount / $total_amount) * 100; ?>, 100"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-6">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-500 p-2 rounded-lg mr-3">
                        <i class="fas fa-history text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900"><?php echo __('payment_history', 'Payment History'); ?></h3>
                </div>

                <?php if (count($payments) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($payments as $payment): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo number_format($payment['amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>
                            </div>
                            <div class="text-xs text-gray-600">
                                <?php echo date('M j, Y H:i', strtotime($payment['payment_date'])); ?>
                            </div>
                            <div class="text-sm text-gray-600">
                                <?php
                                $method = $payment['payment_method'];
                                $key = strtolower(str_replace(' ', '_', $method));
                                echo htmlspecialchars(__('' . $key, $method));
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-receipt text-gray-400"></i>
                    </div>
                    <p class="text-gray-600"><?php echo __('no_payments_recorded', 'No payments recorded yet'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Information -->
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    <p><?php echo __('invoice_created_by', 'Invoice created by'); ?>: <span class="font-medium"><?php echo htmlspecialchars($invoice['user_name'] ?: __('unknown', 'Unknown')); ?></span></p>
                    <p><?php echo __('last_updated', 'Last updated'); ?>: <?php echo date('M j, Y H:i', strtotime($invoice['updated_at'] ?? $invoice['created_at'])); ?></p>
                </div>
                <div class="text-right text-sm text-gray-600">
                    <p><?php echo __('powered_by', 'Powered by'); ?> Shop Management System</p>
                    <p><?php echo __('generated_on', 'Generated on'); ?> <?php echo date('M j, Y H:i'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function downloadPDF() {
    // Implement PDF download functionality
    alert('<?php echo __('pdf_download_coming_soon', 'PDF download functionality coming soon'); ?>');
}

// Print styles
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: white !important;
    }
    .shadow-sm, .shadow-lg, .shadow-xl {
        box-shadow: none !important;
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
