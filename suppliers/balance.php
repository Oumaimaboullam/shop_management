<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager']);

// Fetch all suppliers with their balance from database
$stmt = $pdo->query("
    SELECT 
        s.id,
        s.name,
        s.phone,
        s.email,
        s.balance,
        COUNT(p.id) as purchase_count
    FROM suppliers s
    LEFT JOIN purchases p ON s.id = p.supplier_id
    GROUP BY s.id, s.name, s.phone, s.email, s.balance
    ORDER BY s.balance DESC
");
$suppliers = $stmt->fetchAll();

// Calculate overall totals
$total_balance = array_sum(array_column($suppliers, 'balance'));

$pageTitle = 'Supplier Balance (Solde)';
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Supplier Balance (Solde)</h1>
            <p class="text-gray-600">View total amounts owed to each supplier</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v2c0 .656-.126 1.259-.356 1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.259.356-1.857m0 0a5.002 5.002 0 019.288 0H15a5.002 5.002 0 019.288 0M5 3a4 4 0 00-8 0v4h8V3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Suppliers</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($suppliers); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Balance</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo number_format($total_balance, 2); ?> DH</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suppliers Balance Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Supplier Balances</h2>
                    <div class="flex items-center space-x-2">
                        <button onclick="exportToExcel()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Export to Excel</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchases</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($supplier['name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php if ($supplier['phone']): ?>
                                            <div><?php echo htmlspecialchars($supplier['phone']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($supplier['email']): ?>
                                            <div class="text-gray-500"><?php echo htmlspecialchars($supplier['email']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $supplier['purchase_count']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold <?php echo $supplier['balance'] > 0 ? 'text-red-600' : ($supplier['balance'] < 0 ? 'text-green-600' : 'text-gray-600'); ?>"><?php echo number_format($supplier['balance'], 2); ?> DH</div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($supplier['balance'] > 0): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Owed
                                        </span>
                                    <?php elseif ($supplier['balance'] < 0): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Overpaid
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Settled
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="supplier_purchases.php?id=<?php echo $supplier['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                            View Purchases
                                        </a>
                                        <?php if ($supplier['balance'] > 0): ?>
                                            <a href="../purchases/pay.php?supplier_id=<?php echo $supplier['id']; ?>" 
                                               class="text-green-600 hover:text-green-900 text-sm font-medium">
                                                Make Payment
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($suppliers)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No suppliers found
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
function exportToExcel() {
    const table = document.querySelector('table');
    let csv = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Get rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push(td.textContent.trim());
        });
        csv.push(row.join(','));
    });
    
    // Create download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'supplier_balances_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<?php require_once '../includes/footer.php'; ?>

