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

$pageTitle = 'Business Analytics Dashboard';
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .header h1 {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin: 8px 0 0;
        }
        
        .filters-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .metric-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 20px;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .metric-change {
            font-size: 0.8rem;
            margin-top: 4px;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .metric-change.positive {
            background: #10b981;
            color: white;
        }
        
        .metric-change.negative {
            background: #ef4444;
            color: white;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        
        .chart-title h3 {
            margin: 0;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
        }
        
        .revenue { background: linear-gradient(135deg, #10b981, #059669); }
        .sales { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .customers { background: linear-gradient(135deg, #8b5cf6, #6366f1); }
        .purchases { background: linear-gradient(135deg, #f59e0b, #ef4444); }
        .suppliers { background: linear-gradient(135deg, #ec4899, #dc2626); }
        .payments { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-chart-line mr-3"></i>Business Analytics Dashboard</h1>
            <p>Real-time insights and performance metrics</p>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="date_from"><i class="fas fa-calendar-alt mr-2"></i>From Date</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to"><i class="fas fa-calendar-alt mr-2"></i>To Date</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn"><i class="fas fa-filter mr-2"></i>Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon revenue"><i class="fas fa-money-bill-wave"></i></div>
                    <div>
                        <div class="metric-value"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($current_metrics['total_revenue'], 2); ?></div>
                        <div class="metric-label">Total Revenue</div>
                        <?php if ($previous_metrics['total_revenue'] > 0): ?>
                            <div class="metric-change positive">
                                <?php 
                                $change = (($current_metrics['total_revenue'] - $previous_metrics['total_revenue']) / $previous_metrics['total_revenue']) * 100;
                                echo ($change >= 0 ? '↑' : '↓') . ' ' . abs(number_format($change, 1)) . '%';
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon sales"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <div class="metric-value"><?php echo number_format($current_metrics['total_sales']); ?></div>
                        <div class="metric-label">Total Sales</div>
                        <?php if ($previous_metrics['total_sales'] > 0): ?>
                            <div class="metric-change positive">
                                <?php 
                                $change = (($current_metrics['total_sales'] - $previous_metrics['total_sales']) / $previous_metrics['total_sales']) * 100;
                                echo ($change >= 0 ? '↑' : '↓') . ' ' . abs(number_format($change, 1)) . '%';
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon customers"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="metric-value"><?php echo number_format($current_metrics['total_customers']); ?></div>
                        <div class="metric-label">Active Customers</div>
                        <?php if ($previous_metrics['total_customers'] > 0): ?>
                            <div class="metric-change positive">
                                <?php 
                                $change = (($current_metrics['total_customers'] - $previous_metrics['total_customers']) / $previous_metrics['total_customers']) * 100;
                                echo ($change >= 0 ? '↑' : '↓') . ' ' . abs(number_format($change, 1)) . '%';
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon purchases"><i class="fas fa-shopping-cart"></i></div>
                    <div>
                        <div class="metric-value"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($purchase_metrics['total_purchase_cost'], 2); ?></div>
                        <div class="metric-label">Purchase Costs</div>
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon suppliers"><i class="fas fa-truck"></i></div>
                    <div>
                        <div class="metric-value"><?php echo number_format($purchase_metrics['total_suppliers']); ?></div>
                        <div class="metric-label">Active Suppliers</div>
                    </div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon payments"><i class="fas fa-credit-card"></i></div>
                    <div>
                        <div class="metric-value"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($current_metrics['avg_sale_value'], 2); ?></div>
                        <div class="metric-label">Average Sale</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Monthly Sales Trend -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3><i class="fas fa-chart-line mr-2"></i>Monthly Sales Trend</h3>
                </div>
                <div class="chart-container">
                    <canvas id="monthlySalesChart"></canvas>
                </div>
            </div>

            <!-- Daily Sales -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3><i class="fas fa-chart-bar mr-2"></i>Daily Sales (30 Days)</h3>
                </div>
                <div class="chart-container">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>

            <!-- Sales by Category -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3><i class="fas fa-chart-pie mr-2"></i>Sales by Category</h3>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3><i class="fas fa-users mr-2"></i>Top Customers</h3>
                </div>
                <div class="chart-container">
                    <canvas id="customersChart"></canvas>
                </div>
            </div>

            <!-- Supplier Performance -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3><i class="fas fa-truck mr-2"></i>Supplier Performance</h3>
                </div>
                <div class="chart-container">
                    <canvas id="supplierChart"></canvas>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3><i class="fas fa-credit-card mr-2"></i>Payment Methods</h3>
                </div>
                <div class="chart-container">
                    <canvas id="paymentMethodsChart"></canvas>
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
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Sales Count',
                    data: <?php echo json_encode(array_column($monthly_sales, 'sales_count')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
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
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
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
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
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
                                return 'Revenue: ' + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
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
                    label: 'Total Spent',
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
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
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
                                return 'Total: ' + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
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
                labels: <?php echo json_encode(array_column($supplier_performance, 'supplier_name')); ?>,
                datasets: [{
                    label: 'Total Cost',
                    data: <?php echo json_encode(array_column($supplier_performance, 'total_cost')); ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }, {
                    label: 'Paid Amount',
                    data: <?php echo json_encode(array_column($supplier_performance, 'paid_amount')); ?>,
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
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
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
    </script>
</body>
</html>

<?php require_once '../includes/footer.php'; ?>

