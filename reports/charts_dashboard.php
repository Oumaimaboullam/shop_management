<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager']);

// Get date range from GET parameters or default to current month
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Monthly Sales Data (for line chart)
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as sales_count,
        COALESCE(SUM(total_amount), 0) as revenue
    FROM sales 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute([]);
$monthly_sales = $stmt->fetchAll();

// Daily Sales Data (last 30 days)
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as sales_count,
        COALESCE(SUM(total_amount), 0) as revenue
    FROM sales 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([]);
$daily_sales = $stmt->fetchAll();

// Sales by Category
$stmt = $pdo->prepare("
    SELECT 
        c.name as category,
        COUNT(DISTINCT s.id) as sales_count,
        COALESCE(SUM(si.quantity * si.unit_price), 0) as revenue
    FROM sales s
    JOIN sale_items si ON s.id = si.sale_id
    JOIN articles a ON si.article_id = a.id
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY c.id, c.name
    ORDER BY revenue DESC
    LIMIT 10
");
$stmt->execute([$date_from, $date_to]);
$sales_by_category = $stmt->fetchAll();

// Top Customers
$stmt = $pdo->prepare("
    SELECT 
        c.name as customer_name,
        COUNT(DISTINCT s.id) as sales_count,
        COALESCE(SUM(s.total_amount), 0) as total_spent
    FROM clients c
    JOIN sales s ON c.id = s.client_id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY c.id, c.name
    ORDER BY total_spent DESC
    LIMIT 10
");
$stmt->execute([$date_from, $date_to]);
$top_customers = $stmt->fetchAll();

// Purchase Data
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as purchase_count,
        COALESCE(SUM(total_amount), 0) as cost
    FROM purchases 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute([]);
$monthly_purchases = $stmt->fetchAll();

// Supplier Performance
$stmt = $pdo->prepare("
    SELECT 
        s.name as supplier_name,
        COUNT(DISTINCT p.id) as purchase_count,
        COALESCE(SUM(p.total_amount), 0) as total_cost,
        COALESCE(SUM(p.paid_amount), 0) as paid_amount
    FROM suppliers s
    JOIN purchases p ON s.id = p.supplier_id
    WHERE DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY s.id, s.name
    ORDER BY total_cost DESC
    LIMIT 10
");
$stmt->execute([$date_from, $date_to]);
$supplier_performance = $stmt->fetchAll();

// Payment Methods Distribution
$stmt = $pdo->prepare("
    SELECT 
        pm.name as payment_mode,
        COUNT(DISTINCT s.id) as transaction_count,
        COALESCE(SUM(s.total_amount), 0) as total_amount
    FROM sales s
    LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY s.payment_mode_id, pm.name
    ORDER BY total_amount DESC
");
$stmt->execute([$date_from, $date_to]);
$payment_methods = $stmt->fetchAll();

// Customer vs Supplier Balance
$stmt = $pdo->prepare("
    SELECT 
        'Customers' as type,
        COUNT(*) as count,
        COALESCE(SUM(balance), 0) as total_balance
    FROM clients
    UNION ALL
    SELECT 
        'Suppliers' as type,
        COUNT(*) as count,
        COALESCE(SUM(balance), 0) as total_balance
    FROM suppliers
");
$stmt->execute([]);
$balance_comparison = $stmt->fetchAll();

$pageTitle = 'Analytics Dashboard';
require_once '../includes/header.php';
?>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
                    <p class="mt-1 text-gray-600">Visual analytics and business insights</p>
                </div>
                <div class="flex items-center space-x-3">
                    <form method="GET" class="flex items-center space-x-2">
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <span class="text-gray-500">to</span>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Filter
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Revenue -->
            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format(array_sum(array_column($monthly_sales, 'revenue')), 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Sales -->
            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Sales</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo number_format(array_sum(array_column($monthly_sales, 'sales_count'))); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Customers -->
            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v2c0 .656-.126 1.259-.356 1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.259.356-1.857m0 0a5.002 5.002 0 019.288 0H15a5.002 5.002 0 019.288 0M5 3a4 4 0 00-8 0v4h8V3z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Customers</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo count($top_customers); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Suppliers -->
            <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v2c0 .656-.126 1.259-.356 1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.259.356-1.857m0 0a5.002 5.002 0 019.288 0H15a5.002 5.002 0 019.288 0M5 3a4 4 0 00-8 0v4h8V3z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Suppliers</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo count($supplier_performance); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="chart-grid">
            <!-- Monthly Sales Trend -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Sales Trend</h3>
                <div class="chart-container">
                    <canvas id="monthlySalesChart"></canvas>
                </div>
            </div>

            <!-- Daily Sales Trend -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Daily Sales (Last 30 Days)</h3>
                <div class="chart-container">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>

            <!-- Sales by Category -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Sales by Category</h3>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Top Customers</h3>
                <div class="chart-container">
                    <canvas id="customersChart"></canvas>
                </div>
            </div>

            <!-- Purchase vs Sales Comparison -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Sales vs Purchases</h3>
                <div class="chart-container">
                    <canvas id="purchaseVsSalesChart"></canvas>
                </div>
            </div>

            <!-- Supplier Performance -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Supplier Performance</h3>
                <div class="chart-container">
                    <canvas id="supplierChart"></canvas>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Methods Distribution</h3>
                <div class="chart-container">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
            </div>

            <!-- Balance Comparison -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Customer vs Supplier Balances</h3>
                <div class="chart-container">
                    <canvas id="balanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Monthly Sales Chart
    const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
    new Chart(monthlySalesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_sales, 'month')); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode(array_column($monthly_sales, 'revenue')); ?>,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                yAxisID: 'y',
            }, {
                label: 'Sales Count',
                data: <?php echo json_encode(array_column($monthly_sales, 'sales_count')); ?>,
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                yAxisID: 'y1',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });

    // Daily Sales Chart
    const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
    new Chart(dailySalesCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($daily_sales, 'date')); ?>,
            datasets: [{
                label: 'Daily Revenue',
                data: <?php echo json_encode(array_column($daily_sales, 'revenue')); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });

    // Top Customers Chart
    const customersCtx = document.getElementById('customersChart').getContext('2d');
    new Chart(customersCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($top_customers, 'customer_name')); ?>,
            datasets: [{
                label: 'Total Spent',
                data: <?php echo json_encode(array_column($top_customers, 'total_spent')); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
        }
    });

    // Purchase vs Sales Chart
    const purchaseVsSalesCtx = document.getElementById('purchaseVsSalesChart').getContext('2d');
    new Chart(purchaseVsSalesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_sales, 'month')); ?>,
            datasets: [{
                label: 'Sales Revenue',
                data: <?php echo json_encode(array_column($monthly_sales, 'revenue')); ?>,
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
            }, {
                label: 'Purchase Cost',
                data: <?php echo json_encode(array_column($monthly_purchases, 'cost')); ?>,
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });

    // Supplier Performance Chart
    const supplierCtx = document.getElementById('supplierChart').getContext('2d');
    new Chart(supplierCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($supplier_performance, 'supplier_name')); ?>,
            datasets: [{
                label: 'Total Cost',
                data: <?php echo json_encode(array_column($supplier_performance, 'total_cost')); ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.8)',
            }, {
                label: 'Paid Amount',
                data: <?php echo json_encode(array_column($supplier_performance, 'paid_amount')); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
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
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });

    // Balance Comparison Chart
    const balanceCtx = document.getElementById('balanceChart').getContext('2d');
    new Chart(balanceCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($balance_comparison, 'type')); ?>,
            datasets: [{
                label: 'Count',
                data: <?php echo json_encode(array_column($balance_comparison, 'count')); ?>,
                backgroundColor: 'rgba(156, 163, 175, 0.8)',
                yAxisID: 'y',
            }, {
                label: 'Total Balance',
                data: <?php echo json_encode(array_column($balance_comparison, 'total_balance')); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                yAxisID: 'y1',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>

