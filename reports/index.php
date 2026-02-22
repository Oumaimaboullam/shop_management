<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

// Add cache-busting header
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');

// Get date range from GET parameters or default to current month
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Calculate previous period for comparison
$prev_date_from = date('Y-m-01', strtotime('-1 month', strtotime($date_from)));
$prev_date_to = date('Y-m-t', strtotime('-1 month', strtotime($date_to)));

// Fetch key metrics
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

// Purchase metrics
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

// Top selling products
$stmt = $pdo->prepare("
    SELECT 
        a.name as product_name,
        SUM(si.quantity) as total_quantity,
        SUM(si.quantity * si.unit_price) as total_revenue,
        COUNT(DISTINCT si.sale_id) as number_of_sales
    FROM sale_items si
    JOIN articles a ON si.article_id = a.id
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY si.article_id, a.name
    ORDER BY total_revenue DESC
    LIMIT 10
");
$stmt->execute([$date_from, $date_to]);
$top_products = $stmt->fetchAll();

// Top customers
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

// Payment methods breakdown
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

// Daily sales trend (last 30 days)
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

$pageTitle = 'Analytics Dashboard';
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
        }
        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .metric-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
        }
        .header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .filters form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .filters input, .filters select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }
        .filters button {
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
        }
        .filters button:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Analytics Dashboard</h1>
        <p>Comprehensive business analytics and insights</p>
    </div>

    <div class="filters">
        <form method="GET">
            <input type="hidden" name="date_from" value="<?php echo $date_from; ?>">
            <input type="hidden" name="date_to" value="<?php echo $date_to; ?>">
            <div>
                <label>From Date:</label>
                <input type="date" name="date_from" value="<?php echo $date_from; ?>" onchange="this.form.submit()">
            </div>
            <div>
                <label>To Date:</label>
                <input type="date" name="date_to" value="<?php echo $date_to; ?>" onchange="this.form.submit()">
            </div>
            <div>
                <button type="submit">Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- Key Metrics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="metric-card">
            <div class="metric-value"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($current_metrics['total_revenue'], 2); ?></div>
            <div class="metric-label">Total Revenue</div>
            <?php if ($previous_metrics['total_revenue'] > 0): ?>
                <div style="color: <?php echo $current_metrics['total_revenue'] >= $previous_metrics['total_revenue'] ? '#10b981' : '#ef4444'; ?>; font-size: 0.875rem;">
                    <?php 
                    $change = (($current_metrics['total_revenue'] - $previous_metrics['total_revenue']) / $previous_metrics['total_revenue']) * 100;
                    echo ($change >= 0 ? '+' : '') . number_format($change, 1) . '% vs last month';
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="metric-card">
            <div class="metric-value"><?php echo number_format($current_metrics['total_sales']); ?></div>
            <div class="metric-label">Total Sales</div>
            <?php if ($previous_metrics['total_sales'] > 0): ?>
                <div style="color: <?php echo $current_metrics['total_sales'] >= $previous_metrics['total_sales'] ? '#10b981' : '#ef4444'; ?>; font-size: 0.875rem;">
                    <?php 
                    $change = (($current_metrics['total_sales'] - $previous_metrics['total_sales']) / $previous_metrics['total_sales']) * 100;
                    echo ($change >= 0 ? '+' : '') . number_format($change, 1) . '% vs last month';
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="metric-card">
            <div class="metric-value"><?php echo number_format($current_metrics['total_customers']); ?></div>
            <div class="metric-label">Active Customers</div>
            <?php if ($previous_metrics['total_customers'] > 0): ?>
                <div style="color: <?php echo $current_metrics['total_customers'] >= $previous_metrics['total_customers'] ? '#10b981' : '#ef4444'; ?>; font-size: 0.875rem;">
                    <?php 
                    $change = (($current_metrics['total_customers'] - $previous_metrics['total_customers']) / $previous_metrics['total_customers']) * 100;
                    echo ($change >= 0 ? '+' : '') . number_format($change, 1) . '% vs last month';
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="metric-card">
            <div class="metric-value"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($current_metrics['avg_sale_value'], 2); ?></div>
            <div class="metric-label">Average Sale Value</div>
            <?php if ($previous_metrics['avg_sale_value'] > 0): ?>
                <div style="color: <?php echo $current_metrics['avg_sale_value'] >= $previous_metrics['avg_sale_value'] ? '#10b981' : '#ef4444'; ?>; font-size: 0.875rem;">
                    <?php 
                    $change = (($current_metrics['avg_sale_value'] - $previous_metrics['avg_sale_value']) / $previous_metrics['avg_sale_value']) * 100;
                    echo ($change >= 0 ? '+' : '') . number_format($change, 1) . '% vs last month';
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="chart-grid">
        <!-- Monthly Sales Trend -->
        <div class="metric-card">
            <h3>Monthly Sales Trend</h3>
            <div class="chart-container">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>

        <!-- Daily Sales Trend -->
        <div class="metric-card">
            <h3>Daily Sales (Last 30 Days)</h3>
            <div class="chart-container">
                <canvas id="dailySalesChart"></canvas>
            </div>
        </div>

        <!-- Sales by Category -->
        <div class="metric-card">
            <h3>Sales by Category</h3>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="metric-card">
            <h3>Top Customers</h3>
            <div class="chart-container">
                <canvas id="customersChart"></canvas>
            </div>
        </div>

        <!-- Purchase vs Sales Comparison -->
        <div class="metric-card">
            <h3>Sales vs Purchases</h3>
            <div class="chart-container">
                <canvas id="purchaseVsSalesChart"></canvas>
            </div>
        </div>

        <!-- Supplier Performance -->
        <div class="metric-card">
            <h3>Supplier Performance</h3>
            <div class="chart-container">
                <canvas id="supplierChart"></canvas>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="metric-card">
            <h3>Payment Methods Distribution</h3>
            <div class="chart-container">
                <canvas id="paymentMethodsChart"></canvas>
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
                labels: <?php echo json_encode(array_column($daily_sales, 'sale_date')); ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?php echo json_encode(array_column($daily_sales, 'daily_revenue')); ?>,
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
    </script>
</body>
</html>

<?php require_once '../includes/footer.php'; ?>
