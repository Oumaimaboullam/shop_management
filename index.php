<?php
// index.php - Modern Tailwind Dashboard
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

// Get dashboard data with error handling
$today_sales = 0;
$low_stock_items = 0;
$pending_orders = 0;
$total_customers = 0;

// Fetch today's sales
try {
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM sales WHERE DATE(created_at) = CURDATE() AND status IN ('confirmed', 'paid')");
    $stmt->execute();
    $result = $stmt->fetch();
    $today_sales = $result['total'] ?? 0;
} catch (PDOException $e) {
    // Handle error silently
}

// Fetch low stock items
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM articles a JOIN stock s ON a.id = s.article_id WHERE s.quantity <= a.stock_alert_level AND s.quantity > 0");
    $stmt->execute();
    $result = $stmt->fetch();
    $low_stock_items = $result['count'] ?? 0;
} catch (PDOException $e) {
    // Handle error silently
}

// Fetch pending orders
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sales WHERE status = 'draft' OR status = 'partial'");
    $stmt->execute();
    $result = $stmt->fetch();
    $pending_orders = $result['count'] ?? 0;
} catch (PDOException $e) {
    // Handle error silently
}

// Fetch total customers
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM clients");
    $stmt->execute();
    $result = $stmt->fetch();
    $total_customers = $result['count'] ?? 0;
} catch (PDOException $e) {
    // Handle error silently
}

// Fetch recent activity
try {
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as customer_name, u.name as user_name 
        FROM sales s 
        LEFT JOIN clients c ON s.client_id = c.id 
        LEFT JOIN users u ON s.user_id = u.id 
        ORDER BY s.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_sales = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_sales = [];
}

// Fetch sales data for chart (last 7 days)
$sales_chart_data = [];
try {
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, SUM(total_amount) as total
        FROM sales 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status IN ('confirmed', 'paid')
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute();
    $sales_chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sales_chart_data = [];
}

// Prepare chart data for JavaScript
$sales_dates = [];
$sales_amounts = [];
foreach ($sales_chart_data as $data) {
    $sales_dates[] = date('M j', strtotime($data['date']));
    $sales_amounts[] = floatval($data['total']);
}
$sales_dates_json = json_encode($sales_dates);
$sales_amounts_json = json_encode($sales_amounts);

$pageTitle = getSetting('company_name', 'Dashboard');
require_once 'includes/header.php';
?>

<!-- Modern Tailwind Dashboard -->
<div class="space-y-6">
    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today's Sales -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-300 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600"><?php echo __('todays_sales'); ?></p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($today_sales, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600 text-lg"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                <span class="text-green-600 font-medium">12%</span>
                <span class="text-gray-600 ml-1"><?php echo __('vs_yesterday'); ?></span>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-300 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600"><?php echo __('low_stock_items'); ?></p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1"><?php echo $low_stock_items; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-lg"></i>
                </div>
            </div>
            <div class="mt-4">
                <a href="inventory/products.php?filter=low_stock" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    <i class="fas fa-eye mr-1"></i>
                    <?php echo __('view_all'); ?>
                </a>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-300 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600"><?php echo __('pending_orders'); ?></p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1"><?php echo $pending_orders; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-blue-600 text-lg"></i>
                </div>
            </div>
            <div class="mt-4">
                <a href="sales/list.php?status=draft" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    <i class="fas fa-list mr-1"></i>
                    <?php echo __('view_all'); ?>
                </a>
            </div>
        </div>

        <!-- Total Customers -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-300 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600"><?php echo __('total_customers'); ?></p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1"><?php echo $total_customers; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-purple-600 text-lg"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                <span class="text-green-600 font-medium">8%</span>
                <span class="text-gray-600 ml-1"><?php echo __('this_month'); ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                <p class="text-gray-600 text-sm"><?php echo __('common_tasks'); ?></p>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="sales/pos.php" class="group bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 border border-green-200 rounded-xl p-4 text-center transition-all duration-300 hover:shadow-lg">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-cash-register text-white text-lg"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1"><?php echo __('new_sale'); ?></h3>
                <p class="text-xs text-gray-600"><?php echo __('start_transaction'); ?></p>
            </a>
            
            <a href="inventory/add.php" class="group bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 border border-blue-200 rounded-xl p-4 text-center transition-all duration-300 hover:shadow-lg">
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-plus text-white text-lg"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1"><?php echo __('add_product'); ?></h3>
                <p class="text-xs text-gray-600"><?php echo __('add_inventory_item'); ?></p>
            </a>
            
            <a href="customers/add.php" class="group bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 border border-purple-200 rounded-xl p-4 text-center transition-all duration-300 hover:shadow-lg">
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-user-plus text-white text-lg"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1"><?php echo __('add_customer'); ?></h3>
                <p class="text-xs text-gray-600"><?php echo __('register_customer'); ?></p>
            </a>
            
            <a href="suppliers/add.php" class="group bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 border border-orange-200 rounded-xl p-4 text-center transition-all duration-300 hover:shadow-lg">
                <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-industry text-white text-lg"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1"><?php echo __('add_supplier'); ?></h3>
                <p class="text-xs text-gray-600"><?php echo __('register_supplier'); ?></p>
            </a>
        </div>
    </div>

    <!-- Sales Trend Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900"><?php echo __('sales_trend', 'Sales Trend'); ?></h2>
                    <p class="text-gray-600 text-sm"><?php echo __('last_7_days', 'Last 7 days performance'); ?></p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-600"><?php echo __('sales', 'Sales'); ?></span>
                </div>
            </div>
            <div class="h-64">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Additional Info Panel -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6"><?php echo __('quick_stats', 'Quick Stats'); ?></h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-cart text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900"><?php echo __('total_sales_this_month', 'This Month Sales'); ?></p>
                            <p class="text-sm text-gray-600"><?php echo __('vs_last_month', 'vs last month'); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-blue-600">
                            <?php
                            // Calculate this month sales
                            try {
                                $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM sales WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status IN ('confirmed', 'paid')");
                                $stmt->execute();
                                $this_month = $stmt->fetch()['total'] ?? 0;
                                echo number_format($this_month, 2) . ' ' . getSetting('currency_symbol', 'DH');
                            } catch (PDOException $e) {
                                echo '0' . ' ' . getSetting('currency_symbol', 'DH');
                            }
                            ?>
                        </p>
                        <p class="text-sm text-green-600 font-medium">+12.5%</p>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900"><?php echo __('new_customers', 'New Customers'); ?></p>
                            <p class="text-sm text-gray-600"><?php echo __('this_month', 'This month'); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-green-600">
                            <?php
                            // Calculate new customers this month
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM clients WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
                                $stmt->execute();
                                $new_customers = $stmt->fetch()['count'] ?? 0;
                                echo $new_customers;
                            } catch (PDOException $e) {
                                echo '0';
                            }
                            ?>
                        </p>
                        <p class="text-sm text-green-600 font-medium">+8.2%</p>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-box text-purple-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900"><?php echo __('low_stock_alerts', 'Low Stock Alerts'); ?></p>
                            <p class="text-sm text-gray-600"><?php echo __('items_need_attention', 'Items need attention'); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-purple-600"><?php echo $low_stock_items; ?></p>
                        <p class="text-sm text-orange-600 font-medium"><?php echo __('needs_action', 'Needs action'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900"><?php echo __('recent_activity'); ?></h2>
                <p class="text-gray-600 text-sm"><?php echo __('latest_updates'); ?></p>
            </div>
            <a href="sales/list.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                <i class="fas fa-list mr-1"></i>
                <?php echo __('view_all'); ?>
            </a>
        </div>
        
        <div class="space-y-4">
            <?php if (count($recent_sales) > 0): ?>
                <?php foreach ($recent_sales as $sale): 
                    $status_class = '';
                    $status_color = '';
                    switch($sale['status']) {
                        case 'paid': 
                            $status_class = 'status-success'; 
                            $status_color = 'bg-green-100 text-green-800';
                            break;
                        case 'partial': 
                            $status_class = 'status-warning'; 
                            $status_color = 'bg-yellow-100 text-yellow-800';
                            break;
                        case 'cancelled': 
                            $status_class = 'status-error'; 
                            $status_color = 'bg-red-100 text-red-800';
                            break;
                        default: 
                            $status_class = 'status-info'; 
                            $status_color = 'bg-blue-100 text-blue-800';
                            break;
                    }
                ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-receipt text-primary-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($sale['customer_name'] ?? __('walk_in_customer')); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Invoice #<?php echo htmlspecialchars($sale['invoice_number'] ?? 'N/A'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <p class="font-semibold text-gray-900"><?php echo number_format($sale['total_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></p>
                                <p class="text-sm text-gray-600"><?php echo date('M j, Y g:i A', strtotime($sale['created_at'])); ?></p>
                            </div>
                            <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $status_color; ?>">
                                <?php echo ucfirst($sale['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-inbox text-gray-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo __('no_recent_sales'); ?></h3>
                    <p class="text-gray-600 mb-4"><?php echo __('start_first_sale'); ?></p>
                    <a href="sales/pos.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        <?php echo __('create_new_sale'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Additional Modern JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to buttons
    const buttons = document.querySelectorAll('a[href^="sales/"], a[href^="inventory/"], a[href^="customers/"], a[href^="suppliers/"]');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            this.classList.add('loading');
            setTimeout(() => {
                this.classList.remove('loading');
            }, 1000);
        });
    });
    
    // Add hover effects to cards
    const cards = document.querySelectorAll('.card-hover');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Initialize Sales Chart
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        const salesDates = <?php echo $sales_dates_json; ?>;
        const salesAmounts = <?php echo $sales_amounts_json; ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: salesDates,
                datasets: [{
                    label: '<?php echo __('sales', 'Sales'); ?>',
                    data: salesAmounts,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(34, 197, 94)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toFixed(2) + ' ' + '<?php echo getSetting('currency_symbol', 'DH'); ?>';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(0) + ' ' + '<?php echo getSetting('currency_symbol', 'DH'); ?>';
                            }
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    // Auto-refresh dashboard every 30 seconds
    setInterval(() => {
        // You can implement AJAX refresh here
        console.log('Dashboard auto-refresh');
    }, 30000);
    
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Apply animations to dashboard sections
    const sections = document.querySelectorAll('.space-y-6 > div');
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        section.style.transition = `all 0.6s ease ${index * 0.1}s`;
        observer.observe(section);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>