<?php
// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic Fields
    $barcode = sanitize($_POST['barcode']);
    $reference = sanitize($_POST['reference']);
    $name = sanitize($_POST['name']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $description = sanitize($_POST['description']);
    
    // Pricing Fields
    $purchase_price = $_POST['purchase_price'];
    $percentage_profit = $_POST['percentage_profit'];
    $wholesale_percentage = $_POST['wholesale_percentage'];
    $wholesale_price = $_POST['wholesale_price'];
    $sale_price = $_POST['sale_price'];
    $tax_rate = $_POST['tax_rate'];
    
    // Stock Fields
    $stock_alert = $_POST['stock_alert'];
    $initial_stock = $_POST['initial_stock'];

    // Validate required fields
    if (empty($barcode) || empty($name) || empty($purchase_price) || empty($sale_price)) {
        flash('error', __('barcode_name_purchase_sale_required', 'Le code-barres, le nom, le prix d\'achat et le prix de vente sont obligatoires.'));
    } else {
        try {
            $pdo->beginTransaction();

            // Handle Image Upload
            $image_path = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                $upload_dir = '../uploads/products/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_path)) {
                        $image_path = 'uploads/products/' . $new_filename;
                    }
                }
            }

            // 1. Insert Article
            $stmt = $pdo->prepare("
                INSERT INTO articles (
                    barcode, reference, category_id, name, description, image,
                    purchase_price, percentage_of_sales_profit, wholesale_percentage,
                    wholesale, sale_price, tax_rate, stock_alert_level
                )
                VALUES (
                    :barcode, :reference, :category_id, :name, :description, :image,
                    :purchase_price, :percentage_profit, :wholesale_percentage,
                    :wholesale_price, :sale_price, :tax_rate, :stock_alert
                )
            ");
            
            $stmt->execute([
                ':barcode' => $barcode,
                ':reference' => $reference,
                ':category_id' => $category_id,
                ':name' => $name,
                ':description' => $description,
                ':image' => $image_path,
                ':purchase_price' => $purchase_price,
                ':percentage_profit' => $percentage_profit,
                ':wholesale_percentage' => $wholesale_percentage,
                ':wholesale_price' => $wholesale_price,
                ':sale_price' => $sale_price,
                ':tax_rate' => $tax_rate,
                ':stock_alert' => $stock_alert
            ]);
            
            $article_id = $pdo->lastInsertId();

            // 2. Insert Initial Stock
            $stmt = $pdo->prepare("INSERT INTO stock (article_id, quantity) VALUES (:article_id, :quantity)");
            $stmt->execute([':article_id' => $article_id, ':quantity' => $initial_stock]);

            // 3. Log Movement if stock > 0
            if ($initial_stock > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO stock_movements (article_id, type, quantity, source, created_at)
                    VALUES (:article_id, 'in', :quantity, 'manual', NOW())
                ");
                $stmt->execute([':article_id' => $article_id, ':quantity' => $initial_stock]);
            }

            $pdo->commit();
            flash('success', __('product_added_successfully', 'Produit ajouté avec succès!'));
            header('Location: products.php');
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) { // Duplicate entry
                flash('error', __('barcode_already_exists', 'Erreur: Le code-barres existe déjà.'));
            } else {
                flash('error', __('database_error', 'Erreur de base de données: ') . $e->getMessage());
            }
        }
    }
}

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$pageTitle = __('add_new_product', 'Ajouter un nouveau produit');
require_once '../includes/header.php';
?>

<!-- Modern Tailwind Add Product Form -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('add_new_product', 'Ajouter un nouveau produit'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('add_product_to_inventory', 'Ajouter un produit à votre inventaire'); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="categories.php" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-tags mr-2"></i>
                    <?php echo __('manage_categories', 'Gérer les catégories'); ?>
                </a>
                <a href="products.php" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <?php echo __('back_to_products', 'Retour aux produits'); ?>
                </a>
            </div>
        </div>
    </div>

    <?php if ($msg = flash('error')): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span class="text-red-800"><?php echo $msg; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Product Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form method="POST" action="" id="productForm" enctype="multipart/form-data" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column: Basic Information -->
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-box mr-2 text-primary-600"></i>
                            <?php echo __('basic_information', 'Informations de base'); ?>
                        </h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="barcode" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo __('barcode_required', 'Code-barres *'); ?> <span class="text-gray-500 text-xs"><?php echo __('required_field', '(Obligatoire)'); ?></span>
                                </label>
                                <input type="text" id="barcode" name="barcode" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                       placeholder="<?php echo __('enter_product_barcode', 'Entrer le code-barres du produit'); ?>">
                            </div>

                            <div>
                                <label for="reference" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo __('reference_sku', 'Référence / SKU'); ?>
                                </label>
                                <input type="text" id="reference" name="reference" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                       placeholder="<?php echo __('enter_product_reference', 'Entrer la référence du produit'); ?>">
                            </div>

                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo __('product_name_required', 'Nom du produit *'); ?> <span class="text-gray-500 text-xs"><?php echo __('required_field', '(Obligatoire)'); ?></span>
                                </label>
                                <input type="text" id="name" name="name" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                       placeholder="<?php echo __('enter_product_name', 'Entrer le nom du produit'); ?>">
                            </div>

                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo __('category_label', 'Catégorie'); ?>
                                </label>
                                <div class="flex space-x-2">
                                    <select id="category_id" name="category_id" 
                                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                                        <option value=""><?php echo __('select_category', '-- Sélectionner une catégorie --'); ?></option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" onclick="showAddCategoryModal()" 
                                            class="px-3 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 text-sm font-medium rounded-lg transition-colors duration-200"
                                            title="<?php echo __('add_new_category_title', 'Ajouter une nouvelle catégorie'); ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo __('description_label', 'Description'); ?>
                                </label>
                                <textarea id="description" name="description" rows="4" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                          placeholder="<?php echo __('enter_product_description', 'Entrer la description du produit'); ?> (optional)"></textarea>
                            </div>

                            <div>
                                <label for="product_image" class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo __('product_image'); ?>
                                </label>
                                <div class="relative">
                                    <input type="file" id="product_image" name="product_image" accept="image/*" 
                                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                           onchange="updateFileLabel(this, 'fileLabel')">
                                    <div class="flex items-center space-x-2">
                                        <button type="button" 
                                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200 border border-gray-300">
                                            <?php echo __('select_product_image'); ?>
                                        </button>
                                        <span id="fileLabel" class="text-sm text-gray-500"><?php echo __('no_image_uploaded'); ?></span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1"><?php echo __('allowed_image_formats'); ?></p>
                                <div id="imagePreview" class="mt-2 hidden">
                                    <img id="previewImg" src="" alt="Product Preview" class="h-20 w-20 object-cover rounded-lg border border-gray-200">
                                    <p class="text-xs text-gray-500 mt-1"><?php echo __('current_image'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Pricing & Stock -->
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-dollar-sign mr-2 text-green-600"></i>
                            <?php echo __('pricing_stock', 'Prix et Stock'); ?>
                        </h2>
                        
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo __('purchase_information', 'Informations d\'achat'); ?></h3>
                                <div>
                                    <label for="purchase_price" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo __('purchase_price_label', 'Prix d\'achat *'); ?> <span class="text-gray-500 text-xs"><?php echo __('required_field', '(Obligatoire)'); ?></span>
                                    </label>
                                    <input type="number" step="0.01" id="purchase_price" name="purchase_price" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                           placeholder="0.00" oninput="calculatePrices()">
                                </div>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo __('profit_margins', 'Marges de profit'); ?></h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="percentage_profit" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo __('retail_profit', 'Marge de détail (%)'); ?>
                                        </label>
                                        <input type="number" step="0.01" id="percentage_profit" name="percentage_profit" value="0" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                               placeholder="0" oninput="calculateSalePrice()">
                                    </div>
                                    <div>
                                        <label for="sale_price" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo __('sale_price_label', 'Prix de vente *'); ?> <span class="text-gray-500 text-xs"><?php echo __('required_field', '(Obligatoire)'); ?></span>
                                        </label>
                                        <input type="number" step="0.01" id="sale_price" name="sale_price" required 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                               placeholder="0.00">
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="wholesale_percentage" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo __('wholesale_margin', 'Marge grossiste (%)'); ?>
                                        </label>
                                        <input type="number" step="0.01" id="wholesale_percentage" name="wholesale_percentage" value="0" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                               placeholder="0" oninput="calculateWholesalePrice()">
                                    </div>
                                    <div>
                                        <label for="wholesale_price" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo __('wholesale_price', 'Prix grossiste'); ?>
                                        </label>
                                        <input type="number" step="0.01" id="wholesale_price" name="wholesale_price" value="0" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                               placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo __('tax_settings', 'Paramètres fiscaux'); ?></h3>
                                <div>
                                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo __('tax_rate_label', 'Taux de TVA (%)'); ?>
                                    </label>
                                    <input type="number" step="0.01" id="tax_rate" name="tax_rate" value="20" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                           placeholder="20.00" min="0" max="100">
                                    <p class="text-xs text-gray-500 mt-1"><?php echo __('enter_tax_rate_percentage', 'Entrer le taux de TVA (0-100%)'); ?></p>
                                </div>
                            </div>

                            <div class="bg-green-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-gray-700 mb-3"><?php echo __('stock_settings', 'Paramètres de stock'); ?></h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="initial_stock" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo __('initial_stock_label', 'Stock initial'); ?>
                                        </label>
                                        <input type="number" id="initial_stock" name="initial_stock" value="0" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                               placeholder="0">
                                    </div>
                                    <div>
                                        <label for="stock_alert" class="block text-sm font-medium text-gray-700 mb-2">
                                            <?php echo __('stock_alert_label', 'Alerte de stock'); ?>
                                        </label>
                                        <input type="number" id="stock_alert" name="stock_alert" value="5" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                                               placeholder="5">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="products.php" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>
                    <?php echo __('cancel', 'Annuler'); ?>
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>
                    <?php echo __('save_product', 'Enregistrer le produit'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900"><?php echo __('add_new_category', 'Ajouter une nouvelle catégorie'); ?></h3>
            <button onclick="closeAddCategoryModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="addCategoryForm" onsubmit="addCategory(event)">
            <div class="space-y-4">
                <div>
                    <label for="new_category_name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('category_name_label', 'Nom de la catégorie *'); ?></label>
                    <input type="text" id="new_category_name" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                           placeholder="<?php echo __('enter_category_name', 'Entrer le nom de la catégorie'); ?>">
                </div>
                <div>
                    <label for="new_category_description" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('category_description_label', 'Description de la catégorie'); ?></label>
                    <textarea id="new_category_description" rows="3" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"
                              placeholder="<?php echo __('enter_category_description', 'Entrer la description de la catégorie'); ?> (optional)"></textarea>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeAddCategoryModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <?php echo __('cancel', 'Annuler'); ?>
                </button>
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    <?php echo __('save_category', 'Enregistrer la catégorie'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modern JavaScript Enhancements -->
<script>
// Price calculation functions
function calculatePrices() {
    calculateSalePrice();
    calculateWholesalePrice();
}

function calculateSalePrice() {
    const purchase = parseFloat(document.getElementById('purchase_price').value) || 0;
    const margin = parseFloat(document.getElementById('percentage_profit').value) || 0;
    
    if (purchase > 0) {
        const sale = purchase * (1 + (margin / 100));
        document.getElementById('sale_price').value = sale.toFixed(2);
    }
}

function calculateWholesalePrice() {
    const purchase = parseFloat(document.getElementById('purchase_price').value) || 0;
    const margin = parseFloat(document.getElementById('wholesale_percentage').value) || 0;
    
    if (purchase > 0) {
        const wholesale = purchase * (1 + (margin / 100));
        console.log('Calculated wholesale price:', wholesale);
        document.getElementById('wholesale_price').value = wholesale.toFixed(2);
    }
}

// Initialize event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Test that JavaScript is running
    const testDiv = document.createElement('div');
    testDiv.id = 'js-test';
    testDiv.style.cssText = 'position:fixed; top:0; right:0; background:green; color:white; padding:5px; z-index:9999;';
    testDiv.textContent = 'JS OK';
    document.body.appendChild(testDiv);
    setTimeout(() => testDiv.remove(), 2000);
    
    const purchasePrice = document.getElementById('purchase_price');
    const percentageProfit = document.getElementById('percentage_profit');
    const wholesalePercentage = document.getElementById('wholesale_percentage');
    
    console.log('Elements found:', { purchasePrice: !!purchasePrice, percentageProfit: !!percentageProfit, wholesalePercentage: !!wholesalePercentage });
    
    if (purchasePrice) purchasePrice.addEventListener('input', calculatePrices);
    if (percentageProfit) percentageProfit.addEventListener('input', calculateSalePrice);
    if (wholesalePercentage) wholesalePercentage.addEventListener('input', calculateWholesalePrice);
});

// Category modal functions
function showAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.remove('hidden');
    document.getElementById('new_category_name').focus();
}

function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.add('hidden');
    document.getElementById('addCategoryForm').reset();
}

function addCategory(event) {
    event.preventDefault();
    
    const name = document.getElementById('new_category_name').value.trim();
    const description = document.getElementById('new_category_description').value.trim();
    
    if (!name) {
        alert("<?php echo addslashes(__('category_name_required_js', 'Le nom de la catégorie est obligatoire!')); ?>");
        return;
    }
    
    // Send AJAX request to add category
    fetch('add_category_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            name: name,
            description: description
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add new option to category select
            const select = document.getElementById('category_id');
            const option = document.createElement('option');
            option.value = data.category_id;
            option.textContent = name;
            option.selected = true;
            select.appendChild(option);
            
            // Show success message
            showNotification("<?php echo addslashes(__('category_added_successfully_js', 'Catégorie ajoutée avec succès!')); ?>", 'success');
            closeAddCategoryModal();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the category.');
    });
}

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 bg-white border rounded-lg shadow-lg p-4 flex items-center gap-3 max-w-sm z-50 ${
        type === 'success' ? 'border-green-200' : 
        type === 'error' ? 'border-red-200' : 'border-blue-200'
    }`;
    
    notification.innerHTML = `
        <div class="${
            type === 'success' ? 'text-green-600' : 
            type === 'error' ? 'text-red-600' : 'text-blue-600'
        }">
            <i class="fas fa-${
                type === 'success' ? 'check-circle' : 
                type === 'error' ? 'exclamation-circle' : 'info-circle'
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
    }, 3000);
}

// Form validation
document.getElementById('productForm').addEventListener('submit', function(e) {
    const barcode = document.getElementById('barcode').value.trim();
    const name = document.getElementById('name').value.trim();
    const purchasePrice = parseFloat(document.getElementById('purchase_price').value) || 0;
    const salePrice = parseFloat(document.getElementById('sale_price').value) || 0;
    
    if (!barcode || !name || purchasePrice <= 0 || salePrice <= 0) {
        e.preventDefault();
        showNotification("<?php echo addslashes(__('please_fill_required_fields', 'Veuillez remplir tous les champs obligatoires correctement.')); ?>", 'error');
        return;
    }
    
    if (salePrice <= purchasePrice) {
        e.preventDefault();
        showNotification("<?php echo addslashes(__('sale_price_greater_purchase', 'Le prix de vente doit être supérieur au prix d\'achat.')); ?>", 'error');
        return;
    }
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('addCategoryModal');
    if (event.target === modal) {
        closeAddCategoryModal();
    }
});

// File label update function
function updateFileLabel(input, labelId) {
    const label = document.getElementById(labelId);
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
    } else {
        label.textContent = "<?php echo addslashes(__('no_image_uploaded')); ?>";
    }
}

// Image preview functionality
document.getElementById('product_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            const img = document.getElementById('previewImg');
            img.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
        updateFileLabel(e.target, 'fileLabel');
    }
});
</script>

<!-- Additional Modern Styling -->
<style>
/* Enhanced form styling */
input:focus, textarea:focus, select:focus {
    transform: scale(1.02);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Modal animations */
#addCategoryModal {
    transition: opacity 0.3s ease;
}

#addCategoryModal:not(.hidden) {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Form section styling */
.bg-gray-50, .bg-blue-50, .bg-yellow-50, .bg-green-50 {
    transition: all 0.2s ease;
}

.bg-gray-50:hover, .bg-blue-50:hover, .bg-yellow-50:hover, .bg-green-50:hover {
    transform: translateY(-1px);
}

/* Button hover effects */
button[type="submit"] {
    transition: all 0.3s ease;
}

button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}
</style>

<?php require_once '../includes/footer.php'; ?>