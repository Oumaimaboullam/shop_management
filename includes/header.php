<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions file for getSetting function
require_once 'functions.php';
// Auto-fix DB issues
require_once __DIR__ . '/db_updates.php';

$currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$current_page = basename($_SERVER['PHP_SELF']);

// Determine current section for breadcrumb
$current_section = '';
$path = $_SERVER['PHP_SELF'];
if (strpos($path, 'sales') !== false) $current_section = 'Sales';
elseif (strpos($path, 'inventory') !== false) $current_section = 'Inventory';
elseif (strpos($path, 'purchases') !== false) $current_section = 'Purchases';
elseif (strpos($path, 'customers') !== false) $current_section = 'Customers';
elseif (strpos($path, 'suppliers') !== false) $current_section = 'Suppliers';
elseif (strpos($path, 'reports') !== false) $current_section = 'Reports';
elseif (strpos($path, 'settings') !== false) $current_section = 'Settings';
elseif (strpos($path, 'invoices') !== false) $current_section = 'History';
elseif (strpos($path, 'returns') !== false) $current_section = 'Returns';

// Fix path depth for assets
$depth = substr_count($_SERVER['PHP_SELF'], '/') - 2;
$prefix = $depth > 0 ? str_repeat('../', $depth) : '';

// Fetch low stock notifications
$low_stock_count = 0;
$low_stock_notifications = [];
if (isset($_SESSION['user'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT a.name, s.quantity AS stock_quantity
            FROM articles a
            INNER JOIN stock s ON s.article_id = a.id
            WHERE s.quantity <= a.stock_alert_level AND s.quantity > 0
            ORDER BY s.quantity ASC
            LIMIT 5
        ");
        $stmt->execute();
        $low_stock_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $low_stock_count = count($low_stock_notifications);
    } catch (PDOException $e) {
        $low_stock_notifications = [];
        $low_stock_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Cache Control - Prevent Browser Caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <title><?php echo htmlspecialchars($pageTitle ?? 'Shop Management'); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        secondary: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-in': 'slideIn 0.3s ease-out',
                        'bounce-in': 'bounceIn 0.6s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        slideIn: {
                            '0%': { opacity: '0', transform: 'translateX(-10px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                        bounceIn: {
                            '0%': { opacity: '0', transform: 'scale(0.3)' },
                            '50%': { opacity: '1', transform: 'scale(1.05)' },
                            '70%': { transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Styles -->
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Smooth transitions */
        * {
            transition: all 0.2s ease;
        }
        
        /* Loading animation */
        .loading {
            position: relative;
            overflow: hidden;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Notification styles */
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 50;
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Glass morphism effects */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Gradient backgrounds */
        .gradient-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        .gradient-success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        
        .gradient-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .gradient-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        /* Ripple effect for buttons */
        .ripple {
            position: relative;
            overflow: hidden;
        }
        
        .ripple::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .ripple:hover::before {
            width: 300px;
            height: 300px;
        }
        
        /* Card hover effects */
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .status-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .status-warning {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fed7aa;
        }
        
        .status-info {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        
        .status-primary {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
    </style>
    
    <!-- Modern JavaScript -->
    <script>
        // Modern UI utilities
        const UI = {
            toggleNotifications: function() {
                const notifications = document.getElementById('notifications');
                notifications.classList.toggle('hidden');
            },
            
            showNotification: function(message, type = 'info', duration = 5000) {
                const notification = document.createElement('div');
                notification.className = `notification bg-white border rounded-lg shadow-lg p-4 flex items-center gap-3 max-w-sm ${
                    type === 'success' ? 'border-green-200' : 
                    type === 'error' ? 'border-red-200' : 
                    type === 'warning' ? 'border-yellow-200' : 'border-blue-200'
                }`;
                
                notification.innerHTML = `
                    <div class="${
                        type === 'success' ? 'text-green-600' : 
                        type === 'error' ? 'text-red-600' : 
                        type === 'warning' ? 'text-yellow-600' : 'text-blue-600'
                    }">
                        <i class="fas fa-${
                            type === 'success' ? 'check-circle' : 
                            type === 'error' ? 'exclamation-circle' : 
                            type === 'warning' ? 'exclamation-triangle' : 'info-circle'
                        }"></i>
                    </div>
                    <div class="flex-1 text-gray-800 text-sm">${message}</div>
                    <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, duration);
            },
            
            showLoading: function(element) {
                element.classList.add('loading');
                element.style.pointerEvents = 'none';
            },
            
            hideLoading: function(element) {
                element.classList.remove('loading');
                element.style.pointerEvents = 'auto';
            },
            
            init: function() {
                // Initialize modern UI components
                document.addEventListener('DOMContentLoaded', function() {
                    // Add page loaded animation
                    document.body.classList.add('animate-fade-in');
                    
                    // Initialize tooltips
                    const tooltipElements = document.querySelectorAll('[data-tooltip]');
                    tooltipElements.forEach(element => {
                        element.addEventListener('mouseenter', function(e) {
                            const tooltip = document.createElement('div');
                            tooltip.className = 'absolute bg-gray-800 text-white text-xs px-2 py-1 rounded z-50';
                            tooltip.textContent = e.target.dataset.tooltip;
                            document.body.appendChild(tooltip);
                            
                            const rect = e.target.getBoundingClientRect();
                            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                            tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
                            
                            e.target._tooltip = tooltip;
                        });
                        
                        element.addEventListener('mouseleave', function() {
                            if (element._tooltip) {
                                element._tooltip.remove();
                                element._tooltip = null;
                            }
                        });
                    });
                    
                    // Add ripple effects to buttons
                    const buttons = document.querySelectorAll('.btn');
                    buttons.forEach(button => {
                        button.classList.add('ripple');
                    });
                    
                    // Close notifications when clicking outside
                    document.addEventListener('click', function(event) {
                        const notifications = document.getElementById('notifications');
                        const button = event.target.closest('button[onclick="toggleNotifications()"]');
                        if (!button && notifications && !notifications.classList.contains('hidden') && !notifications.contains(event.target)) {
                            notifications.classList.add('hidden');
                        }
                    });
                });
            }
        };
        
        // Initialize UI
        UI.init();
        
        // Global functions for inline use
        function toggleSidebar() {
            UI.toggleSidebar();
        }
        
        function toggleNotifications() {
            UI.toggleNotifications();
        }
        
        function showNotification(message, type, duration) {
            UI.showNotification(message, type, duration);
        }
        
        // Translation helper function
        function t(key, replacements = {}) {
            let text = window.JSTranslations[key] || key;
            
            // Replace placeholders like {amount}, {currency}, {id}
            for (const [placeholder, value] of Object.entries(replacements)) {
                text = text.replace(new RegExp(`{${placeholder}}`, 'g'), value);
            }
            
            return text;
        }
    </script>
    
    <!-- Modern Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Shop Management">
    
    <!-- JavaScript Translations -->
    <?php require_once __DIR__ . '/js_translations.php'; ?>
</head>
<body class="h-full bg-gray-50 font-sans">
    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="UI.toggleSidebar()"></div>
    
    <!-- Modern Sidebar -->
    <div id="sidebar" class="flex flex-col fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-br from-primary-700 to-primary-800 shadow-2xl transform transition-transform duration-300 ease-in-out -translate-x-full lg:translate-x-0">
        <div class="flex items-center justify-between h-20 px-6 border-b border-primary-600 shrink-0">
            <a href="<?php echo $prefix; ?>index.php" class="flex items-center space-x-3 text-white">
                <?php
                $company_name = getSetting('company_name', 'Shop Management');
                $company_logo = getSetting('company_logo');
                ?>
                <span class="text-xl font-bold">Shop Management</span>
            </a>
        </div>
        
        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-2">
            <?php if (hasRole(['admin'])): ?>
                <!-- Admin can see everything -->
                <a href="<?php echo $prefix; ?>index.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'index.php' ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-home w-5"></i>
                    <span class="font-medium"><?php echo __('dashboard'); ?></span>
                </a>
                
                <a href="<?php echo $prefix; ?>sales/pos.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'pos.php' ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-cash-register w-5"></i>
                    <span class="font-medium"><?php echo __('pos'); ?></span>
                </a>
                
                <a href="<?php echo $prefix; ?>inventory/products.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'products.php' ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-box w-5"></i>
                    <span class="font-medium"><?php echo __('inventory'); ?></span>
                </a>
                
                <a href="<?php echo $prefix; ?>purchases/list.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'purchases') ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-truck w-5"></i>
                    <span class="font-medium"><?php echo __('purchases'); ?></span>
                </a>
                
                <a href="<?php echo $prefix; ?>customers/list.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'customers') ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-users w-5"></i>
                    <span class="font-medium"><?php echo __('customers'); ?></span>
                </a>
                
                <a href="<?php echo $prefix; ?>suppliers/list.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'suppliers') ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-industry w-5"></i>
                    <span class="font-medium"><?php echo __('suppliers'); ?></span>
                </a>
                
                <a href="<?php echo $prefix; ?>sales/returns.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'returns.php' ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-undo w-5"></i>
                    <span class="font-medium"><?php echo __('returns'); ?></span>
                </a>
                
                <a href="<?php echo $prefix; ?>invoices/history.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'history.php' && strpos($_SERVER['PHP_SELF'], 'invoices') ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-file-invoice w-5"></i>
                    <span class="font-medium"><?php echo __('history'); ?></span>
                </a>
                
                <a href="<?php echo $prefix; ?>reports/analytics_modern.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'analytics_modern.php' ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-chart-line w-5"></i>
                    <span class="font-medium"><?php echo __('analytics'); ?></span>
                </a>
                
                <a href="<?php echo $prefix; ?>settings/index.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo strpos($_SERVER['PHP_SELF'], 'settings') ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-cog w-5"></i>
                    <span class="font-medium"><?php echo __('settings'); ?></span>
                </a>
                
            <?php elseif (hasRole(['manager', 'cashier'])): ?>
                <!-- Manager and Cashier can only see POS/Sales/Returns -->
                <a href="<?php echo $prefix; ?>sales/pos.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'pos.php' ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-cash-register w-5"></i>
                    <span class="font-medium"><?php echo __('pos'); ?></span>
                </a>
                
                <a href="<?php echo $prefix; ?>sales/returns.php" class="flex items-center space-x-3 px-4 py-3 text-white hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 <?php echo $current_page == 'returns.php' ? 'bg-white bg-opacity-20' : ''; ?>">
                    <i class="fas fa-undo w-5"></i>
                    <span class="font-medium"><?php echo __('returns'); ?></span>
                </a>
                
            <?php endif; ?>
        </nav>
        
        <div class="shrink-0 px-4 py-6 border-t border-primary-600">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                    <?php echo substr($currentUser['name'] ?? 'U', 0, 1); ?>
                </div>
                <div class="flex-1">
                    <div class="text-white font-medium text-sm"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></div>
                    <div class="text-primary-200 text-xs capitalize"><?php echo $currentUser['role'] ?? 'Guest'; ?></div>
                </div>
            </div>
            <a href="<?php echo $prefix; ?>api/auth/logout.php" class="flex items-center justify-center space-x-2 w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                <i class="fas fa-sign-out-alt"></i>
                <span class="font-medium"><?php echo __('logout'); ?></span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="lg:ml-72 min-h-screen">
        <!-- Modern Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="flex items-center justify-between h-20 px-6">
                <!-- Left Side: Dashboard Title & Welcome -->
                <div class="flex items-center space-x-4">
                    <!-- Mobile menu button -->
                    <button onclick="UI.toggleSidebar()" class="lg:hidden p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                    
                    <div>
                        <h1 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($pageTitle ?? __('dashboard')); ?></h1>
                        <?php if (isset($currentUser)): ?>
                        <p class="text-xs text-gray-500 hidden md:block"><?php echo __('welcome_back'); ?>, <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?>!</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Date display -->
                    <div class="hidden md:flex items-center space-x-2 text-sm text-gray-600">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php 
                            // Manual date translation to avoid locale issues
                            $current_lang = $_SESSION['lang'] ?? 'en';
                            $day_names = [
                                'en' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                                'fr' => ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
                            ];
                            $month_names = [
                                'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                                'fr' => ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
                            ];
                            
                            $day_name = $day_names[$current_lang][date('w')];
                            $month_name = $month_names[$current_lang][date('n') - 1];
                            $day = date('j');
                            $year = date('Y');
                            
                            if ($current_lang === 'fr') {
                                echo "$day_name $day $month_name $year";
                            } else {
                                echo "$day_name, $month_name $day, $year";
                            }
                        ?></span>
                    </div>

                    <!-- Quick actions -->
                    <button onclick="window.location.reload()" class="p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors duration-200" title="<?php echo __('refresh'); ?>">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    
                    <!-- Notifications -->
                    <div class="relative">
                        <button onclick="toggleNotifications()" class="p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors duration-200 relative">
                            <i class="fas fa-bell"></i>
                            <?php if ($low_stock_count > 0): ?>
                                <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"><?php echo $low_stock_count; ?></span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Notifications Dropdown -->
                        <div id="notifications" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-900"><?php echo __('notifications', 'Notifications'); ?></h3>
                            </div>
                            <div class="py-2">
                                <?php if ($low_stock_count > 0): ?>
                                    <?php foreach ($low_stock_notifications as $item): ?>
                                        <a href="<?php echo $prefix; ?>inventory/products.php" class="flex items-center px-4 py-3 hover:bg-gray-50 transition-colors duration-200">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-exclamation-triangle text-orange-500"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo __('low_stock_notification', 'Low stock') . ': ' . $item['stock_quantity'] . ' ' . __('units', 'units'); ?></p>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="px-4 py-3 text-center text-gray-500 text-sm">
                                        <?php echo __('no_notifications', 'No notifications'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Content Area -->
        <main class="p-6">