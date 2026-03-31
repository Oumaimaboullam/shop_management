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
        <!-- Page Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('modern_analytics_dashboard'); ?></h1>
                    <p class="text-gray-600 mt-1"><?php echo __('real_time_insights_and_advanced_business_intelligence'); ?></p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="text-sm text-gray-500">
                        <?php echo __('date_range_filter'); ?>: <?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?>
                    </div>
                </div>
            </div>
        </div>

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
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_revenue', 'Total Revenue'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($current_metrics['total_revenue'], 2); ?> DH</p>
                        <?php
                        $revenue_change = $previous_metrics['total_revenue'] > 0 ?
                            (($current_metrics['total_revenue'] - $previous_metrics['total_revenue']) / $previous_metrics['total_revenue']) * 100 : 0;
                        ?>
                        <p class="text-xs <?php echo $revenue_change >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                            <?php echo $revenue_change >= 0 ? '+' : ''; ?><?php echo number_format($revenue_change, 1); ?>% <?php echo __('vs_last_month', 'vs last month'); ?>
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
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_customers', 'Total Customers'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($customer_metrics['total_customers']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo __('active_customers', 'Active customers'); ?></p>
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
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_products', 'Total Products'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($product_metrics['total_products']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo __('in_stock', 'In stock'); ?>: <?php echo number_format($product_metrics['total_quantity']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-orange-600 text-lg"></i>
                    </div>
                </div>
            </div>
        </div>
");
$stmt->execute([$date_from, $date_to]);
$top_products = $stmt->fetchAll();

// Top Customers
$stmt = $pdo->prepare("
    SELECT 
        c.name as customer_name,
        c.phone,
        COUNT(DISTINCT s.id) as number_of_sales,
        COALESCE(SUM(s.total_amount), 0) as total_spent,
        COALESCE(AVG(s.total_amount), 0) as avg_sale_value
    FROM clients c
    JOIN sales s ON c.id = s.client_id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY c.id, c.name, c.phone
    ORDER BY total_spent DESC
    LIMIT 10
");
$stmt->execute([$date_from, $date_to]);
$top_customers = $stmt->fetchAll();

// Top Suppliers
$stmt = $pdo->prepare("
    SELECT 
        s.name as supplier_name,
        COUNT(DISTINCT p.id) as number_of_purchases,
        COALESCE(SUM(p.total_amount), 0) as total_cost,
        COALESCE(SUM(p.paid_amount), 0) as total_paid
    FROM suppliers s
    JOIN purchases p ON s.id = p.supplier_id
    WHERE DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY s.id, s.name
    ORDER BY total_cost DESC
    LIMIT 10
");
$stmt->execute([$date_from, $date_to]);
$top_suppliers = $stmt->fetchAll();

// Payment Methods Distribution
$stmt = $pdo->prepare("
    SELECT 
        pm.name as payment_mode,
        COUNT(DISTINCT s.id) as number_of_transactions,
        COALESCE(SUM(s.total_amount), 0) as total_amount,
        ROUND(COUNT(DISTINCT s.id) * 100.0 / (SELECT COUNT(*) FROM sales WHERE DATE(created_at) BETWEEN ? AND ?), 2) as percentage
    FROM sales s
    LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY s.payment_mode_id, pm.name
    ORDER BY total_amount DESC
");
$stmt->execute([$date_from, $date_to]);
$payment_methods = $stmt->fetchAll();

// Daily Sales Data
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as sale_date,
        COUNT(*) as number_of_sales,
        COALESCE(SUM(total_amount), 0) as daily_revenue,
        COALESCE(AVG(total_amount), 0) as avg_sale_value
    FROM sales 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY sale_date ASC
");
$stmt->execute([]);
$daily_sales = $stmt->fetchAll();

// Add cache-busting headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');

// Set page title
$pageTitle = __('analytics', 'Analytics');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Filters Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i><?php echo __('from_date', 'From Date'); ?>
                    </label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i><?php echo __('to_date', 'To Date'); ?>
                    </label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i><?php echo __('apply_filters', 'Apply Filters'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
            <!-- Sales Overview -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-dollar-sign text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_revenue', 'Total Revenue'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($current_metrics['total_revenue'], 2); ?> DH</p>
                        <?php
                        $revenue_change = $previous_metrics['total_revenue'] > 0 ?
                            (($current_metrics['total_revenue'] - $previous_metrics['total_revenue']) / $previous_metrics['total_revenue']) * 100 : 0;
                        ?>
                        <p class="text-xs <?php echo $revenue_change >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                            <?php echo $revenue_change >= 0 ? '+' : ''; ?><?php echo number_format($revenue_change, 1); ?>% <?php echo __('vs_last_month', 'vs last month'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sales Count -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-chart-line text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_sales', 'Total Sales'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($current_metrics['total_sales']); ?></p>
                        <?php if ($previous_metrics['total_sales'] > 0): ?>
                            <p class="text-xs text-green-600 font-medium">
                                <?php
                                $change = (($current_metrics['total_sales'] - $previous_metrics['total_sales']) / $previous_metrics['total_sales']) * 100;
                                echo ($change >= 0 ? '+' : '') . number_format($change, 1) . '%';
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Customers Overview -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-users text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_customers', 'Total Customers'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($customer_metrics['total_customers']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo __('active_customers', 'Active customers'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Purchases Overview -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-industry text-orange-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('purchase_costs', 'Purchase Costs'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($purchase_metrics['total_purchase_cost'], 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- Suppliers Overview -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-truck text-red-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('active_suppliers', 'Active Suppliers'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($supplier_metrics['total_suppliers']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Products Overview -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-box text-indigo-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_products', 'Total Products'); ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($product_metrics['total_products']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo __('in_stock', 'In stock'); ?>: <?php echo number_format($product_metrics['total_quantity']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Revenue Trend Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900"><?php echo __('revenue_vs_purchases_trend', 'Revenue vs Purchases Trend'); ?></h3>
                </div>
                <div class="relative h-80">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>

            <!-- Sales by Category -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900"><?php echo __('sales_by_category', 'Sales by Category'); ?></h3>
                </div>
                <div class="relative h-80">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900"><?php echo __('top_customers', 'Top Customers'); ?></h3>
                </div>
                <div class="relative h-80">
                    <canvas id="customersChart"></canvas>
                </div>
            </div>

            <!-- Top Products -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900"><?php echo __('top_products', 'Top Products'); ?></h3>
                </div>
                <div class="relative h-80">
                    <canvas id="productsChart"></canvas>
                </div>
            </div>

            <!-- Supplier Performance -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900"><?php echo __('supplier_performance', 'Supplier Performance'); ?></h3>
                </div>
                <div class="relative h-80">
                    <canvas id="supplierChart"></canvas>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900"><?php echo __('payment_methods_distribution', 'Payment Methods Distribution'); ?></h3>
                </div>
                <div class="relative h-80">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
            </div>

            <!-- Daily Sales Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 xl:col-span-2">
                <div class="flex items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900"><?php echo __('daily_sales_30_days', 'Daily Sales (30 Days)'); ?></h3>
                </div>
                <div class="relative h-80">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Revenue vs Purchases Trend Chart
        const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
        new Chart(revenueTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_sales, 'month')); ?>,
                datasets: [{
                    label: '<?php echo __('revenue', 'Revenue'); ?>',
                    data: <?php echo json_encode(array_column($monthly_sales, 'revenue')); ?>,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: '<?php echo __('purchase_cost', 'Purchase Cost'); ?>',
                    data: <?php echo json_encode(array_column($monthly_purchases, 'cost')); ?>,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgb(255, 255, 255)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Sales by Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($sales_by_category, 'category')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($sales_by_category, 'revenue')); ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                        '#FF6384',
                        '#C9CBCF',
                        '#4BC0C0',
                        '#FF6384'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 12
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    const dataset = data.datasets[0];
                                    const total = dataset.data.reduce((a, b) => a + b, 0);
                                    return data.labels.map((label, i) => {
                                        const value = dataset.data[i];
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${percentage}%`;
                                    });
                                }
                                return null;
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const dataset = context.dataset;
                                const value = dataset.data[context.dataIndex];
                                const total = dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${getSetting('currency_symbol', 'DH')}${new Intl.NumberFormat('en-US').format(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Top Customers Chart
        const customersCtx = document.getElementById('customersChart').getContext('2d');
        new Chart(customersCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($top_customers, 'customer_name')); ?>,
                datasets: [{
                    label: '<?php echo __('total_spent', 'Total Spent'); ?>',
                    data: <?php echo json_encode(array_column($top_customers, 'total_spent')); ?>,
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: 'rgb(139, 92, 246)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgb(255, 255, 255)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return '<?php echo __('total', 'Total'); ?>: ' + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });

        // Top Products Chart
        const productsCtx = document.getElementById('productsChart').getContext('2d');
        new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($top_products, 'product_name')); ?>,
                datasets: [{
                    label: '<?php echo __('total_revenue', 'Total Revenue'); ?>',
                    data: <?php echo json_encode(array_column($top_products, 'total_revenue')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgb(255, 255, 255)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return '<?php echo __('revenue', 'Revenue'); ?>: ' + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });

        // Supplier Performance Chart
        const supplierCtx = document.getElementById('supplierChart').getContext('2d');
        new Chart(supplierCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($top_suppliers, 'supplier_name')); ?>,
                datasets: [{
                    label: '<?php echo __('total_cost', 'Total Cost'); ?>',
                    data: <?php echo json_encode(array_column($top_suppliers, 'total_cost')); ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }, {
                    label: '<?php echo __('paid_amount', 'Paid Amount'); ?>',
                    data: <?php echo json_encode(array_column($top_suppliers, 'paid_amount')); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgb(255, 255, 255)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });

        // Payment Methods Chart
        const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
        new Chart(paymentMethodsCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($payment_methods, 'payment_mode')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($payment_methods, 'total_amount')); ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 12
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    const dataset = data.datasets[0];
                                    const total = dataset.data.reduce((a, b) => a + b, 0);
                                    return data.labels.map((label, i) => {
                                        const value = dataset.data[i];
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${percentage}%`;
                                    });
                                }
                                return null;
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const dataset = context.dataset;
                                const value = dataset.data[context.dataIndex];
                                const total = dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${getSetting('currency_symbol', 'DH')}${new Intl.NumberFormat('en-US').format(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Daily Sales Chart
        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        new Chart(dailySalesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($daily_sales, 'sale_date')); ?>,
                datasets: [{
                    label: '<?php echo __('daily_revenue', 'Daily Revenue'); ?>',
                    data: <?php echo json_encode(array_column($daily_sales, 'daily_revenue')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgb(255, 255, 255)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return '<?php echo __('revenue', 'Revenue'); ?>: ' + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
    </script>
</div>

<?php require_once '../includes/footer.php'; ?>

