<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$supplier_id = $_GET['id'] ?? null;

if (!$supplier_id) {
    header('Location: balance.php');
    exit();
}

// Fetch supplier details
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    flash('error', 'Supplier not found');
    header('Location: balance.php');
    exit();
}

// Fetch supplier's purchases
$stmt = $pdo->prepare("
    SELECT 
        p.*
    FROM purchases p
    WHERE p.supplier_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$supplier_id]);
$purchases = $stmt->fetchAll();

// Get supplier balance from database
$stmt = $pdo->prepare("SELECT balance FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$supplier_data = $stmt->fetch();
$supplier_balance = $supplier_data['balance'] ?? 0;

// Calculate totals from purchases
$total_purchases = array_sum(array_column($purchases, 'total_amount'));
$total_paid = array_sum(array_column($purchases, 'paid_amount'));

$pageTitle = 'Supplier Purchases - ' . htmlspecialchars($supplier['name']);
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Supplier Purchases</h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($supplier['name']); ?></p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="balance.php" 
                       class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        Back to Balance
                    </a>
                    <a href="pay.php?supplier_id=<?php echo $supplier_id; ?>" 
                       class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        Make Payment
                    </a>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Purchases</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_purchases, 2); ?> DH</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Paid</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($total_paid, 2); ?> DH</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h8m-9-4h4.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Balance</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo number_format($supplier_balance, 2); ?> DH</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchases Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Purchase History</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($purchases as $purchase): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($purchase['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($purchase['invoice_number']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $purchase['item_count']; ?> items</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($purchase['total_amount'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-green-600"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($purchase['paid_amount'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold <?php echo ($purchase['total_amount'] - $purchase['paid_amount']) > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                        <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($purchase['total_amount'] - $purchase['paid_amount'], 2); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $balance = $purchase['total_amount'] - $purchase['paid_amount'];
                                    if ($balance > 0) {
                                        echo '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Unpaid</span>';
                                    } elseif ($balance < 0) {
                                        echo '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Overpaid</span>';
                                    } else {
                                        echo '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Paid</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="../purchases/view.php?id=<?php echo $purchase['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                            View
                                        </a>
                                        <?php if ($balance > 0): ?>
                                            <a href="../purchases/pay.php?id=<?php echo $purchase['id']; ?>" 
                                               class="text-green-600 hover:text-green-900 text-sm font-medium">
                                                Pay
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($purchases)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    No purchases found for this supplier
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
