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
try {
    // Simple query to get all products
    $stmt = $pdo->query("
        SELECT a.*, c.name as category_name, COALESCE(s.quantity, 0) as current_stock 
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN stock s ON a.id = s.article_id
        WHERE a.is_active = 1
        ORDER BY a.name ASC
    ");
    $products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $products = [];
}


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
                    <input type="text" id="productSearch" placeholder="<?php echo __('search'); ?>..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                           autocomplete="off">
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <select id="categoryFilter" 
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                        onclick="this.focus()">
                    <option value=""><?php echo __('all_categories'); ?></option>
                    <?php foreach ($category_list as $category): ?>
                        <option value="<?php echo htmlspecialchars(strtolower($category['name'])); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
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

    <!-- Products Grid -->
    <div class="products-grid-container">
        <!-- Product Cards Display -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900">
            <?php echo __('product_inventory'); ?> (<?php echo count($products); ?> produits)
        </h2>
        <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
            <i class="fas fa-plus mr-2"></i><?php echo __('add_product'); ?>
        </a>
    </div>

    <?php if (count($products) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($products as $product): 
                $stock = $product['current_stock'] ?? 0;
                $is_low_stock = $stock <= ($product['stock_alert_level'] ?? 5) && $stock > 0;
                $is_out_of_stock = $stock <= 0;
            ?>
                <div class="bg-white border border-gray-200 rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-all duration-300 hover:scale-102">
                    <!-- Product Image -->
                    <div class="relative h-44 bg-gray-100">
                        <?php if (!empty($product['image'])): ?>
                            <img src="../<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                <span class="text-white text-2xl font-bold">
                                    <?php echo substr($product['name'], 0, 2); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Stock Badge -->
                        <div class="absolute top-3 right-3">
                            <?php if ($is_out_of_stock): ?>
                                <span class="bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold shadow">
                                    Rupture
                                </span>
                            <?php elseif ($is_low_stock): ?>
                                <span class="bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold shadow">
                                    Stock faible
                                </span>
                            <?php else: ?>
                                <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-semibold shadow">
                                    Disponible
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="p-4">
                        <h3 class="text-base font-bold text-gray-900 mb-2 leading-tight">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h3>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-tag mr-2 text-blue-500 text-sm"></i>
                                <span class="text-sm"><?php echo htmlspecialchars($product['category_name'] ?? 'Non catégorisé'); ?></span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-barcode mr-2 text-purple-500 text-sm"></i>
                                <span class="text-sm font-mono"><?php echo htmlspecialchars($product['barcode'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-box mr-2 text-green-500 text-sm"></i>
                                <span class="text-sm">Stock: <?php echo $stock; ?> unités</span>
                            </div>
                        </div>

                        <!-- Prices -->
                        <div class="bg-gray-50 rounded-lg p-3 mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-600 text-sm">Prix d'achat:</span>
                                <span class="text-base font-semibold text-gray-900">
                                    <?php echo number_format($product['purchase_price'] ?? 0, 2); ?> <?php echo getSetting('currency_symbol', 'DH'); ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 text-sm">Prix de vente:</span>
                                <span class="text-lg font-bold text-green-600">
                                    <?php echo number_format($product['sale_price'] ?? 0, 2); ?> <?php echo getSetting('currency_symbol', 'DH'); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <a href="edit.php?id=<?php echo $product['id']; ?>" 
                               class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium text-center transition-colors duration-200">
                                <i class="fas fa-edit mr-1"></i>
                                Modifier
                            </a>
                            <button onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')" 
                                    class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                <i class="fas fa-trash mr-1"></i>
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo __('no_products_found'); ?></h3>
            <p class="text-gray-500 mb-4"><?php echo __('start_add_product'); ?></p>
            <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                <i class="fas fa-plus mr-2"></i><?php echo __('add_product'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4"><?php echo __('confirm_delete'); ?></h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="deleteMessage"></p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="delete_product" value="1">
                    <input type="hidden" name="product_id" id="deleteProductId">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-24 mr-2">
                        <?php echo __('cancel'); ?>
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 hover:bg-red-700">
                        <?php echo __('delete'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search and Filter Functionality
    const searchInput = document.getElementById('productSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    
    // Fix focus issue between input and select
    if (categoryFilter) {
        categoryFilter.addEventListener('mousedown', function(e) {
            e.preventDefault();
            this.focus();
            this.click();
        });
    }
    
    function filterProducts() {
        if (!searchInput || !categoryFilter) return;
        
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value.toLowerCase();
        
        // Filter product cards
        const productCards = document.querySelectorAll('.bg-white.border-gray-200.rounded-xl');
        productCards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            const matchesSearch = !searchTerm || cardText.includes(searchTerm);
            const matchesCategory = !selectedCategory || cardText.includes(selectedCategory);
            
            if (matchesSearch && matchesCategory) {
                card.style.display = '';
                card.style.animation = 'fadeIn 0.3s ease-out';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Add event listeners
    if (searchInput) {
        searchInput.addEventListener('input', filterProducts);
    }
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterProducts);
    }
});

function confirmDelete(productId, productName) {
    document.getElementById('deleteProductId').value = productId;
    document.getElementById('deleteMessage').textContent = 
        'Êtes-vous sûr de vouloir supprimer "' + productName + '" ?';
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
        closeDeleteModal();
    }
}
</script>
