<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager']);

// Add cache-busting headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');

// Get date range from GET parameters or default to current month
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Calculate previous period for comparison
$prev_date_from = date('Y-m-01', strtotime('-1 month', strtotime($date_from)));
$prev_date_to = date('Y-m-t', strtotime('-1 month', strtotime($date_to)));

// Sales Metrics
$stmt = $pdo->prepare("
    SELECT
        COUNT(DISTINCT s.id) as total_sales,
        COALESCE(SUM(s.total_amount), 0) as total_revenue,
        COALESCE(SUM(s.advance_payment), 0) as total_advance,
        COALESCE(SUM(s.total_amount - s.advance_payment), 0) as total_remaining,
        COUNT(DISTINCT s.client_id) as total_customers,
        AVG(s.total_amount) as avg_sale_value
    FROM sales s
    WHERE DATE(s.created_at) BETWEEN ? AND ?
");
$stmt->execute([$date_from, $date_to]);
$current_metrics = $stmt->fetch();

// Previous period metrics for comparison
$stmt = $pdo->prepare("
    SELECT
        COUNT(DISTINCT s.id) as total_sales,
        COALESCE(SUM(s.total_amount), 0) as total_revenue,
        COALESCE(SUM(s.advance_payment), 0) as total_advance,
        COALESCE(SUM(s.total_amount - s.advance_payment), 0) as total_remaining,
        COUNT(DISTINCT s.client_id) as total_customers,
        AVG(s.total_amount) as avg_sale_value
    FROM sales s
    WHERE DATE(s.created_at) BETWEEN ? AND ?
");
$stmt->execute([$prev_date_from, $prev_date_to]);
$previous_metrics = $stmt->fetch();

// Purchase Metrics
$stmt = $pdo->prepare("
    SELECT
        COUNT(DISTINCT p.id) as total_purchases,
        COALESCE(SUM(p.total_amount), 0) as total_purchase_cost,
        COALESCE(SUM(p.paid_amount), 0) as total_paid,
        COALESCE(SUM(p.total_amount - p.paid_amount), 0) as total_purchase_balance,
        COUNT(DISTINCT p.supplier_id) as total_suppliers
    FROM purchases p
    WHERE DATE(p.created_at) BETWEEN ? AND ?
");
$stmt->execute([$date_from, $date_to]);
$purchase_metrics = $stmt->fetch();

// Customer Metrics
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_customers,
        COALESCE(SUM(balance), 0) as total_balance,
        COALESCE(AVG(balance), 0) as avg_balance
    FROM clients
");
$stmt->execute([]);
$customer_metrics = $stmt->fetch();

// Supplier Metrics
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_suppliers,
        COALESCE(SUM(balance), 0) as total_balance,
        COALESCE(AVG(balance), 0) as avg_balance
    FROM suppliers
");
$stmt->execute([]);
$supplier_metrics = $stmt->fetch();

// Product Metrics
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_products,
        COALESCE(SUM(quantity), 0) as total_quantity,
        COALESCE(SUM(quantity * unit_price), 0) as total_value,
        COALESCE(AVG(unit_price), 0) as avg_price
    FROM articles
");
$stmt->execute([]);
$product_metrics = $stmt->fetch();

// Top Selling Products
$stmt = $pdo->prepare("
    SELECT
        a.name as product_name,
        SUM(si.quantity) as total_sold,
        SUM(si.quantity * si.unit_price) as total_revenue
    FROM sale_items si
    JOIN articles a ON si.article_id = a.id
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY a.id, a.name
    ORDER BY total_sold DESC
    LIMIT 5
");
$stmt->execute([$date_from, $date_to]);
$top_products = $stmt->fetchAll();

// Sales by Category
$stmt = $pdo->prepare("
    SELECT
        c.name as category_name,
        COUNT(DISTINCT s.id) as sales_count,
        COALESCE(SUM(s.total_amount), 0) as total_revenue
    FROM categories c
    LEFT JOIN articles a ON c.id = a.category_id
    LEFT JOIN sale_items si ON a.id = si.article_id
    LEFT JOIN sales s ON si.sale_id = s.id AND DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY c.id, c.name
    ORDER BY total_revenue DESC
    LIMIT 5
");
$stmt->execute([$date_from, $date_to]);
$sales_by_category = $stmt->fetchAll();

$pageTitle = __('modern_analytics_dashboard');
require_once '../includes/header.php';
?>

<!-- Modern Analytics Dashboard - Tailwind Design -->
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Date Range Filter -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 flex-1">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('from_date_analytics'); ?></label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('to_date_analytics'); ?></label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                    </div>
                    <div class="flex items-end">
                        <button onclick="applyDateFilter()"
                                class="w-full px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-filter mr-2"></i>
                            <?php echo __('apply_filter'); ?>
                        </button>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="clearFilters()"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>
                        <?php echo __('clear_filter'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Key Metrics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Sales Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_sales'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($current_metrics['total_sales']); ?></p>
                        <?php
                        $sales_change = $previous_metrics['total_sales'] > 0 ?
                            (($current_metrics['total_sales'] - $previous_metrics['total_sales']) / $previous_metrics['total_sales']) * 100 : 0;
                        ?>
                        <p class="text-xs <?php echo $sales_change >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                            <?php echo $sales_change >= 0 ? '+' : ''; ?><?php echo number_format($sales_change, 1); ?>% <?php echo __('vs_last_month'); ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-green-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Revenue Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_revenue'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($current_metrics['total_revenue'], 2); ?> DH</p>
                        <?php
                        $revenue_change = $previous_metrics['total_revenue'] > 0 ?
                            (($current_metrics['total_revenue'] - $previous_metrics['total_revenue']) / $previous_metrics['total_revenue']) * 100 : 0;
                        ?>
                        <p class="text-xs <?php echo $revenue_change >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                            <?php echo $revenue_change >= 0 ? '+' : ''; ?><?php echo number_format($revenue_change, 1); ?>% <?php echo __('vs_last_month'); ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-blue-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Customers Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_customers'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($customer_metrics['total_customers']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo __('active_customers'); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-purple-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Products Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_products'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($product_metrics['total_products']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo __('in_stock'); ?>: <?php echo number_format($product_metrics['total_quantity']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-orange-600 text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Detailed Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Top Selling Products -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-chart-line mr-2"></i>
                        <?php echo __('top_products'); ?>
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($top_products) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($top_products as $index => $product): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-sm font-semibold text-blue-600">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($product['product_name']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo number_format($product['total_sold']); ?> <?php echo __('units_sold'); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900"><?php echo number_format($product['total_revenue'], 2); ?> DH</p>
                                        <p class="text-xs text-gray-500"><?php echo __('revenue'); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-chart-line text-4xl mb-4 text-gray-300"></i>
                            <p><?php echo __('no_sales_data_available'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sales by Category -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-teal-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-tags mr-2"></i>
                        <?php echo __('sales_by_category'); ?>
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($sales_by_category) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($sales_by_category as $category): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($category['category_name']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo number_format($category['sales_count']); ?> <?php echo __('sales'); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900"><?php echo number_format($category['total_revenue'], 2); ?> DH</p>
                                        <p class="text-xs text-gray-500"><?php echo __('revenue'); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-tags text-4xl mb-4 text-gray-300"></i>
                            <p><?php echo __('no_category_data_available'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Additional Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <!-- Purchase Metrics -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo __('purchase_metrics'); ?></h3>
                        <p class="text-sm text-gray-600"><?php echo __('supplier_purchases_overview'); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-red-600"></i>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600"><?php echo __('total_purchases'); ?>:</span>
                        <span class="font-semibold"><?php echo number_format($purchase_metrics['total_purchases']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600"><?php echo __('total_purchase_cost'); ?>:</span>
                        <span class="font-semibold"><?php echo number_format($purchase_metrics['total_purchase_cost'], 2); ?> DH</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600"><?php echo __('outstanding_balance'); ?>:</span>
                        <span class="font-semibold text-red-600"><?php echo number_format($purchase_metrics['total_purchase_balance'], 2); ?> DH</span>
                    </div>
                </div>
            </div>

            <!-- Customer Balance -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo __('customer_balances'); ?></h3>
                        <p class="text-sm text-gray-600"><?php echo __('customer_balance_overview'); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-wallet text-yellow-600"></i>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600"><?php echo __('total_balance'); ?>:</span>
                        <span class="font-semibold"><?php echo number_format($customer_metrics['total_balance'], 2); ?> DH</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600"><?php echo __('average_balance'); ?>:</span>
                        <span class="font-semibold"><?php echo number_format($customer_metrics['avg_balance'], 2); ?> DH</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600"><?php echo __('total_customers'); ?>:</span>
                        <span class="font-semibold"><?php echo number_format($customer_metrics['total_customers']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Supplier Balance -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo __('supplier_balances'); ?></h3>
                        <p class="text-sm text-gray-600"><?php echo __('supplier_balance_overview'); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-truck text-indigo-600"></i>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600"><?php echo __('total_balance'); ?>:</span>
                        <span class="font-semibold"><?php echo number_format($supplier_metrics['total_balance'], 2); ?> DH</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600"><?php echo __('average_balance'); ?>:</span>
                        <span class="font-semibold"><?php echo number_format($supplier_metrics['avg_balance'], 2); ?> DH</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600"><?php echo __('total_suppliers'); ?>:</span>
                        <span class="font-semibold"><?php echo number_format($supplier_metrics['total_suppliers']); ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Date filter functionality
function applyDateFilter() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;

    if (dateFrom && dateTo) {
        const url = new URL(window.location);
        url.searchParams.set('date_from', dateFrom);
        url.searchParams.set('date_to', dateTo);
        window.location.href = url.toString();
    }
}

function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('date_from');
    url.searchParams.delete('date_to');
    window.location.href = url.toString();
}

// Auto-submit on date change
document.getElementById('date_from').addEventListener('change', applyDateFilter);
document.getElementById('date_to').addEventListener('change', applyDateFilter);
</script>

<?php require_once '../includes/footer.php'; ?>

