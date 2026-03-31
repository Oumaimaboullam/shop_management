<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager']);

// Handle bulk product deletion
if (isset($_POST['bulk_delete']) && !empty($_POST['selected_products'])) {
    $product_ids = array_map('intval', $_POST['selected_products']);
    $cannot_delete = [];
    $deleted_count = 0;
    
    try {
        $pdo->beginTransaction();
        
        foreach ($product_ids as $product_id) {
            // Check if product has related sales
            $salesCheck = $pdo->prepare("SELECT COUNT(*) as count FROM sale_items WHERE article_id = :id");
            $salesCheck->execute([':id' => $product_id]);
            $salesCount = $salesCheck->fetch()['count'];
            
            if ($salesCount > 0) {
                // Get product name for error message
                $productCheck = $pdo->prepare("SELECT name FROM articles WHERE id = :id");
                $productCheck->execute([':id' => $product_id]);
                $productName = $productCheck->fetch()['name'];
                $cannot_delete[] = $productName;
                continue;
            }
            
            // Delete related stock movements first
            $stmt = $pdo->prepare("DELETE FROM stock_movements WHERE article_id = :id");
            $stmt->execute([':id' => $product_id]);
            
            // Delete stock
            $stmt = $pdo->prepare("DELETE FROM stock WHERE article_id = :id");
            $stmt->execute([':id' => $product_id]);
            
            // Delete article
            $stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id");
            $stmt->execute([':id' => $product_id]);
            
            $deleted_count++;
        }
        
        $pdo->commit();
        
        // Build appropriate message
        if ($deleted_count > 0 && empty($cannot_delete)) {
            flash('success', $deleted_count . ' ' . __('products_deleted_successfully'));
        } elseif ($deleted_count > 0 && !empty($cannot_delete)) {
            $message = $deleted_count . ' products deleted successfully. ';
            $message .= count($cannot_delete) . ' products could not be deleted because they have sales records: ' . implode(', ', $cannot_delete);
            flash('warning', $message);
        } elseif (empty($cannot_delete)) {
            flash('error', 'No products were selected for deletion.');
        } else {
            $message = 'Could not delete products because they have sales records: ' . implode(', ', $cannot_delete);
            flash('error', $message);
        }
        
        header('Location: products.php');
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        flash('error', __('error_deleting_products') . $e->getMessage());
    }
}

// Handle single product deletion (Soft Delete)
if (isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    try {
        // Check if product has related purchases
        $purchaseCheck = $pdo->prepare("SELECT COUNT(*) as count FROM purchase_items WHERE article_id = :id");
        $purchaseCheck->execute([':id' => $product_id]);
        $purchaseCount = $purchaseCheck->fetch()['count'];

        // Check if product has related sales
        $salesCheck = $pdo->prepare("SELECT COUNT(*) as count FROM sale_items WHERE article_id = :id");
        $salesCheck->execute([':id' => $product_id]);
        $salesCount = $salesCheck->fetch()['count'];

        if ($purchaseCount > 0) {
            $lang = $_SESSION['lang'] ?? 'en';
            if ($lang === 'fr') {
                flash('error', 'Impossible de supprimer ce produit car il a été acheté. Les produits avec un historique d\'achat ne peuvent pas être supprimés.');
            } else {
                flash('error', 'Cannot delete this product because it has been purchased. Products with purchase history cannot be deleted.');
            }
            header('Location: products.php');
            exit();
        }

        if ($salesCount > 0) {
            $lang = $_SESSION['lang'] ?? 'en';
            if ($lang === 'fr') {
                flash('error', 'Impossible de supprimer ce produit car il a des enregistrements de vente. Les produits avec un historique de ventes ne peuvent pas être supprimés.');
            } else {
                flash('error', 'Cannot delete this product because it has sales records. Products with sales history cannot be deleted.');
            }
            header('Location: products.php');
            exit();
        }

        // Soft delete: set is_active = 0
        $stmt = $pdo->prepare("UPDATE articles SET is_active = 0 WHERE id = :id");
        $stmt->execute([':id' => $product_id]);

        flash('success', __('product_deleted_successfully'));
        header('Location: products.php');
        exit();
    } catch (PDOException $e) {
        flash('error', __('error_deleting_product') . ' ' . $e->getMessage());
    }
}

$pageTitle = __('product_inventory');
require_once '../includes/header.php';

// Fetch Products with Stock and Category (Active only)
$stmt = $pdo->query("
    SELECT a.*, c.name as category_name, s.quantity as current_stock 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN stock s ON a.id = s.article_id
    WHERE a.is_active = 1
    ORDER BY a.name ASC
");
$products = $stmt->fetchAll();

// Fetch categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name");
$category_list = $categories->fetchAll();
?>

<!-- Modern Tailwind Product Inventory -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('product_inventory'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('manage_products'); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Category Management Button -->
                <a href="categories.php" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-tags mr-2"></i>
                    <?php echo __('manage_categories'); ?>
                </a>
                <a href="add.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    <?php echo __('add_product'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="productSearch" placeholder="<?php echo __('search'); ?>..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <select id="categoryFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                    <option value=""><?php echo __('all_status'); ?></option>
                    <?php foreach ($category_list as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <a href="../export_csv.php?type=products" download class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-download mr-2"></i>
                    <?php echo __('export'); ?>
                </a>
            </div>
        </div>
    </div>

    <?php if ($msg = flash('success')): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <span class="text-green-800"><?php echo $msg; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($msg = flash('warning')): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                <span class="text-yellow-800"><?php echo $msg; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($msg = flash('error')): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span class="text-red-800"><?php echo $msg; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Products Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Bulk Actions Bar -->
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="selectAll" class="text-sm font-medium text-gray-700">Select All</label>
                    <span id="selectedCount" class="text-sm text-gray-500">0 selected</span>
                </div>
                <div class="flex items-center space-x-3">
                    <button id="bulkDeleteBtn" onclick="confirmBulkDelete()" disabled class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 disabled:bg-gray-300 disabled:cursor-not-allowed">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Selected
                    </button>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <form id="bulkDeleteForm" method="POST">
                <table class="w-full" id="productsTable">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(1)">
                                <div class="flex items-center space-x-1">
                                    <span><?php echo __('image'); ?></span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(2)">
                                <div class="flex items-center space-x-1">
                                    <span><?php echo __('barcode'); ?></span>
                                    <i class="fas fa-sort text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(3)">
                                <div class="flex items-center space-x-1">
                                    <span><?php echo __('name'); ?></span>
                                    <i class="fas fa-sort text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(4)">
                                <div class="flex items-center space-x-1">
                                    <span><?php echo __('purchase_price'); ?></span>
                                    <i class="fas fa-sort text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(5)">
                                <div class="flex items-center space-x-1">
                                    <span><?php echo __('sale_price'); ?></span>
                                    <i class="fas fa-sort text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(6)">
                                <div class="flex items-center space-x-1">
                                    <span><?php echo __('stock'); ?></span>
                                    <i class="fas fa-sort text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo __('actions'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): 
                                $stock = $product['current_stock'] ?? 0;
                                $is_low_stock = $stock <= $product['stock_alert_level'] && $stock > 0;
                                $is_out_of_stock = $stock <= 0;
                                $stock_color = $is_out_of_stock ? 'text-red-600 font-bold' : ($is_low_stock ? 'text-yellow-600 font-semibold' : 'text-green-600');
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200" data-name="<?php echo strtolower($product['name']); ?>" data-barcode="<?php echo strtolower($product['barcode']); ?>" data-category="<?php echo strtolower($product['category_name'] ?? ''); ?>">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>" class="product-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-12 w-12 object-cover rounded-lg border border-gray-200">
                                        <?php else: ?>
                                            <div class="h-12 w-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                                <?php echo substr($product['name'], 0, 2); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['barcode']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo __('sku'); ?>: <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($product['category_name'] ?? __('uncategorized')); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900"><?php echo number_format($product['purchase_price'], 2); ?><?php echo getSetting('currency_symbol', 'DH'); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-green-600"><?php echo number_format($product['sale_price'], 2); ?><?php echo getSetting('currency_symbol', 'DH'); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-semibold <?php echo $stock_color; ?>"><?php echo $stock; ?></span>
                                            <?php if ($is_low_stock && !$is_out_of_stock): ?>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                                    <?php echo __('low_stock'); ?>
                                                </span>
                                            <?php elseif ($is_out_of_stock): ?>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                    <?php echo __('out_of_stock'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="view.php?id=<?php echo $product['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-primary-100 hover:bg-primary-200 text-primary-700 text-xs font-medium rounded-lg transition-colors duration-200" data-tooltip="<?php echo __('view_details'); ?>">
                                                <i class="fas fa-eye mr-1"></i>
                                                <?php echo __('view_all'); ?>
                                            </a>
                                            <a href="edit.php?id=<?php echo $product['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition-colors duration-200" data-tooltip="<?php echo __('edit_product'); ?>">
                                                <i class="fas fa-edit mr-1"></i>
                                                <?php echo __('edit'); ?>
                                            </a>
                                            <button type="button" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')" class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition-colors duration-200" data-tooltip="<?php echo __('delete_product'); ?>">
                                                <i class="fas fa-trash mr-1"></i>
                                                <?php echo __('delete'); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-lg font-medium text-gray-900"><?php echo __('no_products_found'); ?></p>
                                        <p class="text-sm text-gray-500 mt-1"><?php echo __('start_add_product'); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <input type="hidden" name="bulk_delete" value="1">
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo __('delete_product'); ?></h3>
            <p class="text-gray-600 mb-6"><?php echo __('confirm_delete'); ?> <span id="productName" class="font-semibold"></span>? <?php echo __('cannot_undone'); ?></p>
            <input type="hidden" id="deleteProductId" value="">
            <div class="flex justify-center space-x-4">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <?php echo __('cancel'); ?>
                </button>
                <button type="button" onclick="submitDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-trash mr-1"></i>
                    <?php echo __('delete_product'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Confirmation Modal -->
<div id="bulkDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Selected Products</h3>
            <p class="text-gray-600 mb-2">Are you sure you want to delete <span id="bulkDeleteCount" class="font-semibold text-red-600">0</span> selected products?</p>
            <p class="text-gray-500 text-sm mb-6">This action cannot be undone and will permanently remove all selected products from the database.</p>
            <div class="flex justify-center space-x-4">
                <button type="button" onclick="closeBulkDeleteModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <?php echo __('cancel'); ?>
                </button>
                <button type="button" onclick="submitBulkDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-trash mr-1"></i>
                    Delete Products
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modern JavaScript Enhancements -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('productSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const table = document.getElementById('productsTable');
    const rows = table.querySelectorAll('tbody tr');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value.toLowerCase();
        
        rows.forEach(row => {
            const name = row.dataset.name;
            const barcode = row.dataset.barcode;
            const category = row.dataset.category;
            
            const matchesSearch = name.includes(searchTerm) || barcode.includes(searchTerm);
            const matchesCategory = !selectedCategory || category.includes(selectedCategory);
            
            if (matchesSearch && matchesCategory) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    searchInput.addEventListener('input', filterTable);
    categoryFilter.addEventListener('change', filterTable);
    
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

// Delete confirmation functions
function confirmDelete(productId, productName) {
    document.getElementById('productName').textContent = productName;
    document.getElementById('deleteProductId').value = productId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

function submitDelete() {
    const productId = document.getElementById('deleteProductId').value;
    
    // Create a hidden form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    const productIdInput = document.createElement('input');
    productIdInput.type = 'hidden';
    productIdInput.name = 'product_id';
    productIdInput.value = productId;
    
    const deleteInput = document.createElement('input');
    deleteInput.type = 'hidden';
    deleteInput.name = 'delete_product';
    deleteInput.value = '1';
    
    form.appendChild(productIdInput);
    form.appendChild(deleteInput);
    document.body.appendChild(form);
    
    form.submit();
}

// Bulk delete functions
function confirmBulkDelete() {
    const selectedCount = document.querySelectorAll('.product-checkbox:checked').length;
    if (selectedCount === 0) {
        alert('Please select at least one product to delete.');
        return;
    }
    document.getElementById('bulkDeleteCount').textContent = selectedCount;
    document.getElementById('bulkDeleteModal').classList.remove('hidden');
}

function closeBulkDeleteModal() {
    document.getElementById('bulkDeleteModal').classList.add('hidden');
}

function submitBulkDelete() {
    document.getElementById('bulkDeleteForm').submit();
}

// Checkbox functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    // Select all functionality
    function updateSelectAll() {
        const totalCheckboxes = productCheckboxes.length;
        const checkedCheckboxes = document.querySelectorAll('.product-checkbox:checked').length;
        
        selectAllCheckbox.checked = totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes;
        
        // Update selected count
        selectedCount.textContent = checkedCheckboxes + ' selected';
        
        // Enable/disable bulk delete button
        bulkDeleteBtn.disabled = checkedCheckboxes === 0;
    }
    
    // Select all checkbox change
    selectAllCheckbox.addEventListener('change', function() {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectAll();
    });
    
    // Individual checkbox changes
    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectAll);
    });
    
    // Close bulk delete modal when clicking outside
    window.addEventListener('click', function(event) {
        const bulkDeleteModal = document.getElementById('bulkDeleteModal');
        if (event.target === bulkDeleteModal) {
            closeBulkDeleteModal();
        }
    });
});

// Table sorting function
function sortTable(columnIndex) {
    const table = document.getElementById('productsTable');
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
        
        // Handle numeric columns (prices, stock)
        if (columnIndex >= 3 && columnIndex <= 5) {
            const aNum = parseFloat(aValue.replace(/[$,]/g, '')) || 0;
            const bNum = parseFloat(bValue.replace(/[$,]/g, '')) || 0;
            return isAscending ? aNum - bNum : bNum - aNum;
        }
        
        // Handle text columns
        return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });
    
    // Reorder DOM
    rows.forEach(row => tbody.appendChild(row));
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        closeDeleteModal();
    }
});
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

/* Enhanced search input */
#productSearch, #categoryFilter {
    transition: all 0.3s ease;
}

#productSearch:focus, #categoryFilter:focus {
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

/* Modal animations */
#deleteModal {
    transition: opacity 0.3s ease;
}

#deleteModal:not(.hidden) {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<?php require_once '../includes/footer.php'; ?>
