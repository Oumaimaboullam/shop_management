<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager']);

// Get filters
$category_id = $_GET['category_id'] ?? '';
$stock_status = $_GET['stock_status'] ?? '';

// Build query
$query = "
    SELECT 
        a.id,
        a.name,
        a.description,
        a.quantity,
        a.unit_price,
        a.purchase_price,
        a.min_stock,
        c.name as category_name,
        (a.quantity * a.unit_price) as total_value,
        (a.quantity * a.purchase_price) as total_cost,
        CASE 
            WHEN a.quantity <= a.min_stock THEN 'critical'
            WHEN a.quantity <= (a.min_stock * 2) THEN 'low'
            WHEN a.quantity = 0 THEN 'out'
            ELSE 'good'
        END as stock_status
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE 1=1
";

$params = [];

if ($category_id) {
    $query .= " AND a.category_id = ?";
    $params[] = $category_id;
}

if ($stock_status) {
    switch ($stock_status) {
        case 'critical':
            $query .= " AND a.quantity <= a.min_stock AND a.quantity > 0";
            break;
        case 'low':
            $query .= " AND a.quantity <= (a.min_stock * 2) AND a.quantity > a.min_stock";
            break;
        case 'out':
            $query .= " AND a.quantity = 0";
            break;
        case 'good':
            $query .= " AND a.quantity > (a.min_stock * 2)";
            break;
    }
}

$query .= " ORDER BY a.name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$inventory = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// Calculate totals
$total_items = count($inventory);
$total_quantity = array_sum(array_column($inventory, 'quantity'));
$total_value = array_sum(array_column($inventory, 'total_value'));
$total_cost = array_sum(array_column($inventory, 'total_cost'));
$critical_items = count(array_filter($inventory, fn($item) => $item['stock_status'] === 'critical'));
$out_of_stock = count(array_filter($inventory, fn($item) => $item['stock_status'] === 'out'));

$pageTitle = 'Inventory Report';
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Inventory Report</h1>
                    <p class="mt-1 text-gray-600">Current stock levels and inventory valuation</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                        Back to Dashboard
                    </a>
                    <a href="../export_csv.php?type=inventory_report" download class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Export Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow-lg rounded-lg mb-8">
            <div class="p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stock Status</label>
                        <select name="stock_status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Items</option>
                            <option value="critical" <?php echo $stock_status === 'critical' ? 'selected' : ''; ?>>Critical Stock</option>
                            <option value="low" <?php echo $stock_status === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                            <option value="out" <?php echo $stock_status === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                            <option value="good" <?php echo $stock_status === 'good' ? 'selected' : ''; ?>>Good Stock</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Items</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo number_format($total_items); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Quantity</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo number_format($total_quantity); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Value</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($total_value, 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Critical Stock</dt>
                                <dd class="text-lg font-medium text-red-900"><?php echo number_format($critical_items); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Out of Stock</dt>
                                <dd class="text-lg font-medium text-orange-900"><?php echo number_format($out_of_stock); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="bg-white shadow-lg rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Inventory Items</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Value</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Min Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($inventory)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4z"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No inventory items found</p>
                                        <p class="text-sm text-gray-400">Try adjusting your filters</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <?php if ($item['description']): ?>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($item['category_name'] ?: 'Uncategorized'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="font-medium <?php 
                                            echo match($item['stock_status']) {
                                                'critical' => 'text-red-600',
                                                'low' => 'text-orange-600', 
                                                'out' => 'text-red-900',
                                                default => 'text-green-600'
                                            }; 
                                        ?>">
                                            <?php echo number_format($item['quantity']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($item['unit_price'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($item['total_value'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo number_format($item['min_stock']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php
                                        $status_config = [
                                            'critical' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Critical'],
                                            'low' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'label' => 'Low'],
                                            'out' => ['bg' => 'bg-red-100', 'text' => 'text-red-900', 'label' => 'Out of Stock'],
                                            'good' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Good']
                                        ];
                                        $status = $status_config[$item['stock_status']];
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status['bg'] . ' ' . $status['text']; ?>">
                                            <?php echo $status['label']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

