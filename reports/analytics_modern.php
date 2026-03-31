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
        COALESCE(SUM(s.paid_amount), 0) as total_paid,
        COALESCE(SUM(s.total_amount - s.paid_amount), 0) as total_remaining,
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
        COALESCE(SUM(s.paid_amount), 0) as total_paid,
        COALESCE(SUM(s.total_amount - s.paid_amount), 0) as total_remaining,
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
        COALESCE(SUM(s.quantity), 0) as total_quantity,
        COALESCE(SUM(s.quantity * a.sale_price), 0) as total_value,
        COALESCE(AVG(a.sale_price), 0) as avg_price
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

// Top Selling Products
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
$stmt->execute([$date_from, $date_to, $date_from, $date_to]);
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

$pageTitle = __('analytics');
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.8);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.8);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-hover {
            transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .card-hover:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
        
        .metric-card {
            position: relative;
            overflow: hidden;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: left 0.8s ease-in-out;
        }
        
        .metric-card:hover::before {
            left: 100%;
        }
        
        .metric-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 300% 100%;
            animation: gradient-shift 4s ease-in-out infinite;
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .chart-container {
            position: relative;
            height: 320px;
            padding: 10px;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-slide-in {
            animation: slideIn 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .animate-slide-in-delay-1 { animation-delay: 0.15s; }
        .animate-slide-in-delay-2 { animation-delay: 0.25s; }
        .animate-slide-in-delay-3 { animation-delay: 0.35s; }
        .animate-slide-in-delay-4 { animation-delay: 0.45s; }
        .animate-slide-in-delay-5 { animation-delay: 0.55s; }
        .animate-slide-in-delay-6 { animation-delay: 0.65s; }
        .animate-slide-in-delay-7 { animation-delay: 0.75s; }
        .animate-slide-in-delay-8 { animation-delay: 0.85s; }
        
        .pulse-animation {
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 2s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            position: relative;
            overflow: hidden;
        }
        
        .metric-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: rotate(45deg);
            animation: shine 4s ease-in-out infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .status-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .chart-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease-in-out;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: left 0.6s ease-in-out;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 25s ease-in-out infinite;
        }
        
        .shape1 {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape2 {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape3 {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 30%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            33% { transform: translateY(-15px) rotate(120deg); }
            66% { transform: translateY(15px) rotate(240deg); }
        }
        
        .icon-gradient-1 { background: linear-gradient(135deg, #10b981, #059669); }
        .icon-gradient-2 { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .icon-gradient-3 { background: linear-gradient(135deg, #8b5cf6, #6366f1); }
        .icon-gradient-4 { background: linear-gradient(135deg, #f59e0b, #ef4444); }
        .icon-gradient-5 { background: linear-gradient(135deg, #ec4899, #dc2626); }
        .icon-gradient-6 { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
    </style>
</head>
<body>
    <div class="min-h-screen p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Filters -->
            <div class="glass-card rounded-2xl p-6 mb-8">
                <div class="flex items-center mb-4">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-2 rounded-lg mr-3">
                        <i class="fas fa-filter text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800"><?php echo __('date_range_filter'); ?></h2>
                </div>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-purple-500"></i>
                            <?php echo __('from_date_analytics'); ?>
                        </label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                    </div>
                    
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-calendar-check mr-2 text-purple-500"></i>
                            <?php echo __('to_date_analytics'); ?>
                        </label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>" 
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="btn-primary w-full px-6 py-3 text-white rounded-xl font-semibold shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-search"></i>
                            <?php echo __('apply_filter'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Enhanced Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Revenue Card -->
                <div class="metric-card glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="metric-icon icon-gradient-1">
                            <i class="fas fa-dollar-sign text-white"></i>
                        </div>
                        <span class="status-badge">
                            <?php 
                            if ($previous_metrics['total_revenue'] > 0) {
                                $change = (($current_metrics['total_revenue'] - $previous_metrics['total_revenue']) / $previous_metrics['total_revenue']) * 100;
                                echo ($change >= 0 ? '↑' : '↓') . ' ' . abs(number_format($change, 1)) . '%';
                            } else {
                                echo __('new');
                            }
                            ?>
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-2">
                        <?php echo getSetting('currency_symbol', 'DH'); ?><?php echo number_format($current_metrics['total_revenue'], 2); ?>
                    </h3>
                    <p class="text-sm text-gray-600 font-medium"><?php echo __('total_revenue'); ?></p>
                    <div class="mt-4 h-1 bg-gradient-to-r from-green-400 to-green-600 rounded-full"></div>
                </div>

                <!-- Sales Card -->
                <div class="metric-card glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="metric-icon icon-gradient-2">
                            <i class="fas fa-shopping-cart text-white"></i>
                        </div>
                        <span class="status-badge">
                            <?php 
                            if ($previous_metrics['total_sales'] > 0) {
                                $change = (($current_metrics['total_sales'] - $previous_metrics['total_sales']) / $previous_metrics['total_sales']) * 100;
                                echo ($change >= 0 ? '↑' : '↓') . ' ' . abs(number_format($change, 1)) . '%';
                            } else {
                                echo __('new');
                            }
                            ?>
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-2"><?php echo number_format($current_metrics['total_sales']); ?></h3>
                    <p class="text-sm text-gray-600 font-medium"><?php echo __('total_sales'); ?></p>
                    <div class="mt-4 h-1 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full"></div>
                </div>

                <!-- Customers Card -->
                <div class="metric-card glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="metric-icon icon-gradient-3">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <span class="status-badge"><?php echo __('active'); ?></span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-2"><?php echo number_format($customer_metrics['total_customers']); ?></h3>
                    <p class="text-sm text-gray-600 font-medium"><?php echo __('total_customers'); ?></p>
                    <div class="mt-4 h-1 bg-gradient-to-r from-purple-400 to-purple-600 rounded-full"></div>
                </div>

                <!-- Products Card -->
                <div class="metric-card glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="metric-icon icon-gradient-4">
                            <i class="fas fa-box text-white"></i>
                        </div>
                        <span class="status-badge"><?php echo __('in_stock'); ?></span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-2"><?php echo number_format($product_metrics['total_products']); ?></h3>
                    <p class="text-sm text-gray-600 font-medium"><?php echo __('total_products'); ?></p>
                    <div class="mt-4 h-1 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
                </div>
            </div>

            <!-- Enhanced Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Revenue Trend Chart -->
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center mb-4">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-2 rounded-lg mr-3">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                        <h3 class="chart-title text-lg"><?php echo __('revenue_vs_purchases_trend'); ?></h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                </div>

                <!-- Sales by Category Chart -->
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center mb-4">
                        <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-2 rounded-lg mr-3">
                            <i class="fas fa-chart-pie text-white"></i>
                        </div>
                        <h3 class="chart-title text-lg"><?php echo __('sales_by_category'); ?></h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>

                <!-- Top Customers Chart -->
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center mb-4">
                        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 p-2 rounded-lg mr-3">
                            <i class="fas fa-user-check text-white"></i>
                        </div>
                        <h3 class="chart-title text-lg"><?php echo __('top_customers'); ?></h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="customersChart"></canvas>
                    </div>
                </div>

                <!-- Top Products Chart -->
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center mb-4">
                        <div class="bg-gradient-to-r from-orange-500 to-red-600 p-2 rounded-lg mr-3">
                            <i class="fas fa-star text-white"></i>
                        </div>
                        <h3 class="chart-title text-lg"><?php echo __('top_products'); ?></h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="productsChart"></canvas>
                    </div>
                </div>
            </div>
                        </div>

            <!-- Additional Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Payment Methods Chart -->
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center mb-4">
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-2 rounded-lg mr-3">
                            <i class="fas fa-credit-card text-white"></i>
                        </div>
                        <h3 class="chart-title text-lg"><?php echo __('payment_methods'); ?></h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="paymentMethodsChart"></canvas>
                    </div>
                </div>

                <!-- Daily Sales Chart -->
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center mb-4">
                        <div class="bg-gradient-to-r from-teal-500 to-green-600 p-2 rounded-lg mr-3">
                            <i class="fas fa-calendar-days text-white"></i>
                        </div>
                        <h3 class="chart-title text-lg"><?php echo __('daily_sales_30_days'); ?></h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="dailySalesChart"></canvas>
                    </div>
                </div>

                <!-- Supplier Performance Chart -->
                <div class="glass-card rounded-2xl p-6 card-hover">
                    <div class="flex items-center mb-4">
                        <div class="bg-gradient-to-r from-pink-500 to-rose-600 p-2 rounded-lg mr-3">
                            <i class="fas fa-truck text-white"></i>
                        </div>
                        <h3 class="chart-title text-lg"><?php echo __('top_suppliers'); ?></h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="supplierChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Font Awesome icons
        document.addEventListener('DOMContentLoaded', function() {
            // Chart.js configuration with enhanced styling
            Chart.defaults.font.family = 'Inter, sans-serif';
            Chart.defaults.color = '#4B5563';
            Chart.defaults.borderColor = 'rgba(156, 163, 175, 0.1)';
            
            // Enhanced chart options
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        displayColors: true,
                        usePointStyle: true,
                    }
                }
            };

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
                        tension: 0.4,
                        borderWidth: 3
                    }, {
                        label: 'Purchase Cost',
                        data: <?php echo json_encode(array_column($monthly_purchases, 'cost')); ?>,
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3
                    }]
                },
                options: { ...defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(156, 163, 175, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        ...defaultOptions.plugins,
                        tooltip: {
                            ...defaultOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += '<?php echo getSetting('currency_symbol', 'DH'); ?>' + new Intl.NumberFormat('en-US').format(context.parsed.y);
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
                            '#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
                            '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#84CC16'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: { ...defaultOptions,
                    plugins: {
                        ...defaultOptions.plugins,
                        legend: {
                            ...defaultOptions.plugins.legend,
                            position: 'right'
                        },
                        tooltip: {
                            ...defaultOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const dataset = context.dataset;
                                    const value = dataset.data[context.dataIndex];
                                    const total = dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: <?php echo getSetting('currency_symbol', 'DH'); ?>${new Intl.NumberFormat('en-US').format(value)} (${percentage}%)`;
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
                    }]
                },
                options: { ...defaultOptions,
                    indexAxis: 'y',
                    scales: {
                        y: {
                            grid: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(156, 163, 175, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        ...defaultOptions.plugins,
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...defaultOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    return 'Total: <?php echo getSetting('currency_symbol', 'DH'); ?>' + new Intl.NumberFormat('en-US').format(context.parsed.x);
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
                    }]
                },
                options: { ...defaultOptions,
                    indexAxis: 'y',
                    scales: {
                        y: {
                            grid: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(156, 163, 175, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        ...defaultOptions.plugins,
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...defaultOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: <?php echo getSetting('currency_symbol', 'DH'); ?>' + new Intl.NumberFormat('en-US').format(context.parsed.x);
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
                    labels: <?php 
                    $payment_modes = array_column($payment_methods, 'payment_mode');
                    $translated_labels = array_map(function($mode) {
                        // Try exact match first
                        $translated = __($mode);
                        if ($translated !== $mode) {
                            return $translated;
                        }
                        
                        // Try common variations
                        $mode_lower = strtolower($mode);
                        if (strpos($mode_lower, 'cash') !== false) {
                            return __('cash');
                        }
                        if (strpos($mode_lower, 'bank') !== false || strpos($mode_lower, 'transfer') !== false) {
                            return __('bank_transfer');
                        }
                        if (strpos($mode_lower, 'credit') !== false) {
                            return __('credit_card');
                        }
                        if (strpos($mode_lower, 'debit') !== false) {
                            return __('debit_card');
                        }
                        if (strpos($mode_lower, 'check') !== false) {
                            return __('check');
                        }
                        if (strpos($mode_lower, 'mobile') !== false) {
                            return __('mobile_payment');
                        }
                        
                        // Fallback to original
                        return $mode;
                    }, $payment_modes);
                    echo json_encode($translated_labels);
                ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($payment_methods, 'total_amount')); ?>,
                        backgroundColor: [
                            '#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
                            '#EC4899', '#14B8A6', '#F97316'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: { ...defaultOptions,
                    plugins: {
                        ...defaultOptions.plugins,
                        legend: {
                            ...defaultOptions.plugins.legend,
                            position: 'bottom'
                        },
                        tooltip: {
                            ...defaultOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const dataset = context.dataset;
                                    const value = dataset.data[context.dataIndex];
                                    return `${label}: <?php echo getSetting('currency_symbol', 'DH'); ?>${new Intl.NumberFormat('en-US').format(value)}`;
                                }
                            }
                        }
                    }
                }
            });

            // Daily Sales Chart
            const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
            new Chart(dailySalesCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($daily_sales, 'sale_date')); ?>,
                    datasets: [{
                        label: '<?php echo __('daily_revenue'); ?>',
                        data: <?php echo json_encode(array_column($daily_sales, 'daily_revenue')); ?>,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2
                    }]
                },
                options: { ...defaultOptions,
                    plugins: {
                        ...defaultOptions.plugins,
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...defaultOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: <?php echo getSetting('currency_symbol', 'DH'); ?>' + new Intl.NumberFormat('en-US').format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(156, 163, 175, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
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
                        backgroundColor: 'rgba(236, 72, 153, 0.8)',
                        borderColor: 'rgb(236, 72, 153)',
                        borderWidth: 2,
                        borderRadius: 8,
                    }]
                },
                options: { ...defaultOptions,
                    indexAxis: 'y',
                    scales: {
                        y: {
                            grid: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(156, 163, 175, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        ...defaultOptions.plugins,
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...defaultOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    return 'Cost: <?php echo getSetting('currency_symbol', 'DH'); ?>' + new Intl.NumberFormat('en-US').format(context.parsed.x);
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
                                            

