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

// Sales Metrics - Fixed to use existing columns
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

// Customer Metrics - Fixed to use existing columns
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

// Product Metrics - Fixed to use existing columns
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_products,
        COALESCE(SUM(s.quantity), 0) as total_quantity,
        COALESCE(SUM(s.quantity * unit_price), 0) as total_value,
        COALESCE(AVG(unit_price), 0) as avg_price
    FROM articles a
    LEFT JOIN stock s ON a.id = s.article_id
    WHERE a.is_active = 1
");
$stmt->execute([]);
$product_metrics = $stmt->fetch();

// Monthly Sales Data for trend chart
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

// Monthly Purchase Data for trend chart
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

// Top Selling Products - Fixed to use existing columns
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

// Top Customers - Fixed to use existing columns
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

// Sales by Category for chart
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

// Top Suppliers - Fixed to use existing columns
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

// Payment Methods Distribution - Fixed to use existing columns
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

// Daily Sales Data - Fixed to use existing columns
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #1a202c;
            line-height: 1.6;
        }
        
        .analytics-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .header h1 {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin: 0;
        }
        
        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            color: #374151;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 259, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 259, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 259, 0.3);
        }
        
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .overview-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .overview-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 20px;
        }
        
        .card-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .card-label {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .card-change {
            font-size: 0.8rem;
            margin-top: 8px;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .card-change.positive {
            background: #10b981;
            color: white;
        }
        
        .card-change.negative {
            background: #ef4444;
            color: white;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
            gap: 20px;
        }
        
        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a202c;
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
        
        .sales { background: linear-gradient(135deg, #10b981, #059669); }
        .purchases { background: linear-gradient(135deg, #f59e0b, #ef4444); }
        .customers { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .suppliers { background: linear-gradient(135deg, #ec4899, #dc2626); }
        .products { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .payments { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
    </style>
</head>
<body>
    <div class="analytics-container">
        <!-- Header -->
        <div class="header">
            <h1>📊 Complete Business Analytics</h1>
            <p>Comprehensive insights across sales, purchases, customers, suppliers, and inventory</p>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="date_from">📅 From Date</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to">📅 To Date</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn">🔍 Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Overview Cards -->
        <div class="overview-grid">
            <!-- Sales Overview -->
            <div class="overview-card">
                <div class="card-header">
                    <div class="card-icon sales">💰</div>
                    <div>
                        <div class="card-value"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($current_metrics['total_revenue'], 2); ?></div>
                        <div class="card-label">Total Revenue</div>
                        <?php if ($previous_metrics['total_revenue'] > 0): ?>
                            <div class="card-change positive">
                                <?php 
                                $change = (($current_metrics['total_revenue'] - $previous_metrics['total_revenue']) / $previous_metrics['total_revenue']) * 100;
                                echo ($change >= 0 ? '↑' : '↓') . ' ' . abs(number_format($change, 1)) . '%';
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sales Count -->
            <div class="overview-card">
                <div class="card-header">
                    <div class="card-icon sales">📈</div>
                    <div>
                        <div class="card-value"><?php echo number_format($current_metrics['total_sales']); ?></div>
                        <div class="card-label">Total Sales</div>
                        <?php if ($previous_metrics['total_sales'] > 0): ?>
                            <div class="card-change positive">
                                <?php 
                                $change = (($current_metrics['total_sales'] - $previous_metrics['total_sales']) / $previous_metrics['total_sales']) * 100;
                                echo ($change >= 0 ? '↑' : '↓') . ' ' . abs(number_format($change, 1)) . '%';
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Customers Overview -->
            <div class="overview-card">
                <div class="card-header">
                    <div class="card-icon customers">👥</div>
                    <div>
                        <div class="card-value"><?php echo number_format($customer_metrics['total_customers']); ?></div>
                        <div class="card-label">Total Customers</div>
                        <div class="card-change positive">
                            <div style="font-size: 0.7rem;">Active in period</div>
                        </div>
                    </div>
            </div>

            <!-- Purchases Overview -->
            <div class="overview-card">
                <div class="card-header">
                    <div class="card-icon purchases">🏭</div>
                    <div>
                        <div class="card-value"><?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($purchase_metrics['total_purchase_cost'], 2); ?></div>
                        <div class="card-label">Purchase Costs</div>
                    </div>
                </div>
            </div>

            <!-- Suppliers Overview -->
            <div class="overview-card">
                <div class="card-header">
                    <div class="card-icon suppliers">🏭</div>
                    <div>
                        <div class="card-value"><?php echo number_format($supplier_metrics['total_suppliers']); ?></div>
                        <div class="card-label">Active Suppliers</div>
                    </div>
                </div>
            </div>

            <!-- Products Overview -->
            <div class="overview-card">
                <div class="card-header">
                    <div class="card-icon products">📦</div>
                    <div>
                        <div class="card-value"><?php echo number_format($product_metrics['total_products']); ?></div>
                        <div class="card-label">Total Products</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Revenue Trend Chart -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3>📈 Revenue vs Purchases Trend</h3>
                </div>
                <div class="chart-container">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>

            <!-- Sales by Category Chart -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3>🥧 Sales by Category</h3>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Top Customers Chart -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3>👥 Top Customers</h3>
                </div>
                <div class="chart-container">
                    <canvas id="customersChart"></canvas>
                </div>
            </div>

            <!-- Top Products Chart -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3>📦 Top Products</h3>
                </div>
                <div class="chart-container">
                    <canvas id="productsChart"></canvas>
                </div>
            </div>

            <!-- Supplier Performance Chart -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3>🏭 Supplier Performance</h3>
                </div>
                <div class="chart-container">
                    <canvas id="supplierChart"></canvas>
                </div>
            </div>

            <!-- Payment Methods Chart -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3>💳 Payment Methods Distribution</h3>
                </div>
                <div class="chart-container">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
            </div>

            <!-- Daily Sales Chart -->
            <div class="chart-card">
                <div class="chart-title">
                    <h3>📊 Daily Sales (30 Days)</h3>
                </div>
                <div class="chart-container">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Revenue vs Purchases Trend Chart
        const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
        new Chart(revenueTrendCtx, {
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
                    label: 'Purchase Cost',
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

        // Top Products Chart
        const productsCtx = document.getElementById('productsChart').getContext('2d');
        new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($top_products, 'product_name')); ?>,
                datasets: [{
                    label: 'Total Revenue',
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

        // Supplier Performance Chart
        const supplierCtx = document.getElementById('supplierChart').getContext('2d');
        new Chart(supplierCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($top_suppliers, 'supplier_name')); ?>,
                datasets: [{
                    label: 'Total Cost',
                    data: <?php echo json_encode(array_column($top_suppliers, 'total_cost')); ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }, {
                    label: 'Paid Amount',
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
    </script>
</body>
</html>

<?php require_once '../includes/footer.php'; ?>

