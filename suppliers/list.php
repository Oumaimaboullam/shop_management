<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager']);

$pageTitle = __('supplier_directory');
require_once '../includes/header.php';

// Fetch Suppliers with enhanced data
try {
    $stmt = $pdo->query("
        SELECT s.*, 
               COUNT(p.id) as purchase_count,
               COALESCE(SUM(p.total_amount), 0) as total_purchases,
               COALESCE(SUM(p.paid_amount), 0) as total_paid,
               (COALESCE(SUM(p.total_amount), 0) - COALESCE(SUM(p.paid_amount), 0)) as remaining_balance
        FROM suppliers s 
        LEFT JOIN purchases p ON s.id = p.supplier_id 
        GROUP BY s.id 
        ORDER BY s.name
    ");
    $suppliers = $stmt->fetchAll();
} catch (PDOException $e) {
    $suppliers = [];
    $error = "<?php echo __('error_loading_suppliers'); ?>" . $e->getMessage();
}
?>

<!-- Modern Tailwind Supplier List -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('supplier_directory'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('manage_suppliers'); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="add.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    <?php echo __('add_supplier'); ?>
                </a>
                <a href="../export_csv.php?type=suppliers" download class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-download mr-2"></i>
                    <?php echo __('export'); ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span class="text-red-800"><?php echo htmlspecialchars($error); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <span class="text-green-800"><?php echo $msg; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search and Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="supplierSearch" placeholder="<?php echo __('search_suppliers'); ?>..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                    <option><?php echo __('all_status'); ?></option>
                    <option><?php echo __('active'); ?></option>
                    <option><?php echo __('inactive'); ?></option>
                </select>
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-filter mr-2"></i>
                    <?php echo __('filter'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full" id="suppliersTable">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(0)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('name'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(1)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('contact'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(2)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('total_purchases'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(3)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('orders'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(4)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('balance'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($suppliers) > 0): ?>
                        <?php foreach ($suppliers as $supplier): 
                            $status_class = $supplier['remaining_balance'] > 0 ? 'status-warning' : 'status-success';
                            $status_text = $supplier['remaining_balance'] > 0 ? 'Balance Due' : 'Current';
                            $status_color = $supplier['remaining_balance'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200" data-name="<?php echo strtolower($supplier['name']); ?>" data-contact="<?php echo strtolower($supplier['email'] . ' ' . $supplier['phone']); ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                            <?php echo substr($supplier['name'], 0, 2); ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($supplier['name']); ?></div>
                                            <div class="text-xs text-gray-500">Since <?php echo date('M Y', strtotime($supplier['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <?php if ($supplier['email']): ?>
                                            <div class="flex items-center space-x-2 text-sm text-gray-600">
                                                <i class="fas fa-envelope w-4 text-gray-400"></i>
                                                <span><?php echo htmlspecialchars($supplier['email']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($supplier['phone']): ?>
                                            <div class="flex items-center space-x-2 text-sm text-gray-600">
                                                <i class="fas fa-phone w-4 text-gray-400"></i>
                                                <span><?php echo htmlspecialchars($supplier['phone']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900"><?php echo number_format($supplier['total_purchases'], 2); ?> DH</div>
                                    <div class="text-xs text-gray-500">Total purchases</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900"><?php echo $supplier['purchase_count']; ?></div>
                                    <div class="text-xs text-gray-500">Orders</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold <?php echo $supplier['remaining_balance'] > 0 ? 'text-red-600' : 'text-green-600'; ?>"><?php echo number_format($supplier['remaining_balance'], 2); ?> DH</div>
                                    <div class="text-xs text-gray-500">Balance</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $status_color; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="view.php?id=<?php echo $supplier['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-primary-100 hover:bg-primary-200 text-primary-700 text-xs font-medium rounded-lg transition-colors duration-200" data-tooltip="View Details">
                                            <i class="fas fa-eye mr-1"></i>
                                            <?php echo __('view'); ?>
                                        </a>
                                        <a href="edit.php?id=<?php echo $supplier['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition-colors duration-200" data-tooltip="Edit Supplier">
                                            <i class="fas fa-edit mr-1"></i>
                                            <?php echo __('edit'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-industry text-gray-400 text-xl"></i>
                                </div>
                                <h4 class="text-lg font-medium text-gray-900 mb-2"><?php echo __('no_suppliers_found'); ?></h4>
                                <p class="text-gray-600 mb-4"><?php echo __('start_add_supplier'); ?></p>
                                <a href="add.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-plus mr-2"></i>
                                    <?php echo __('add_supplier'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modern JavaScript Enhancements -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('supplierSearch');
    const table = document.getElementById('suppliersTable');
    const rows = table.querySelectorAll('tbody tr');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        rows.forEach(row => {
            const name = row.dataset.name;
            const contact = row.dataset.contact;
            
            if (name.includes(searchTerm) || contact.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Add loading states to buttons
    const actionButtons = document.querySelectorAll('a[href^="view.php"], a[href^="edit.php"]');
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.classList.add('loading');
            this.style.pointerEvents = 'none';
            setTimeout(() => {
                this.classList.remove('loading');
                this.style.pointerEvents = 'auto';
            }, 1000);
        });
    });
    
    // Table row hover effects
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-1px)';
            this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
});

// Table sorting function
function sortTable(columnIndex) {
    const table = document.getElementById('suppliersTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const headers = table.querySelectorAll('th');
    
    // Toggle sort direction
    const currentHeader = headers[columnIndex];
    const isAscending = currentHeader.classList.contains('sorted-asc');
    
    // Remove sort classes from all headers
    headers.forEach(header => {
        header.classList.remove('sorted-asc', 'sorted-desc');
    });
    
    // Add appropriate class to current header
    currentHeader.classList.add(isAscending ? 'sorted-desc' : 'sorted-asc');
    
    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Handle numeric columns (purchases, orders, balance)
        if (columnIndex >= 2 && columnIndex <= 4) {
            const aNum = parseFloat(aValue.replace(/[$,]/g, '')) || 0;
            const bNum = parseFloat(bValue.replace(/[$,]/g, '')) || 0;
            return isAscending ? bNum - aNum : aNum - bNum;
        }
        
        // Handle text columns
        return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });
    
    // Reorder DOM
    rows.forEach(row => tbody.appendChild(row));
}
</script>

<!-- Additional Modern Styling -->
<style>
/* Enhanced table styling */
.table-container {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    transition: box-shadow 0.3s ease;
}

.table-container:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Sortable header styles */
.sortable:hover {
    background-color: #f9fafb;
}

.sortable.sorted-asc i::before {
    content: '\f0de';
}

.sortable.sorted-desc i::before {
    content: '\f0dd';
}

/* Loading states */
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

/* Enhanced search input */
#supplierSearch {
    transition: all 0.3s ease;
}

#supplierSearch:focus {
    transform: scale(1.02);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Status badge enhancements */
.status-badge {
    transition: all 0.2s ease;
}

.status-badge:hover {
    transform: scale(1.05);
}

/* Button hover effects */
.btn-hover-effect {
    transition: all 0.3s ease;
}

.btn-hover-effect:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>

<?php require_once '../includes/footer.php'; ?>
