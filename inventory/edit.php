<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Product Details
$stmt = $pdo->prepare("
    SELECT a.*, c.name as category_name, s.quantity as current_stock 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN stock s ON a.id = s.article_id
    WHERE a.id = :id
");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit();
}

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = sanitize($_POST['barcode']);
    $reference = sanitize($_POST['reference']);
    $name = sanitize($_POST['name']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $description = sanitize($_POST['description']);
    $purchase_price = $_POST['purchase_price'];
    $percentage_profit = $_POST['percentage_profit'];
    $wholesale_percentage = $_POST['wholesale_percentage'];
    $wholesale_price = $_POST['wholesale_price'];
    $sale_price = $_POST['sale_price'];
    $tax_rate = $_POST['tax_rate'];
    $stock_alert = $_POST['stock_alert'];

    // Handle stock adjustment
    $current_stock = $product['current_stock'] ?? 0;
    $adjustment_type = $_POST['adjustment_type'] ?? 'set';
    $stock_adjustment = !empty($_POST['stock_adjustment']) ? floatval($_POST['stock_adjustment']) : 0;

    $new_stock = $current_stock;
    if ($adjustment_type === 'set') {
        $new_stock = $stock_adjustment;
    } elseif ($adjustment_type === 'add') {
        $new_stock = $current_stock + $stock_adjustment;
    } elseif ($adjustment_type === 'subtract') {
        $new_stock = max(0, $current_stock - $stock_adjustment);
    }

    try {
        $pdo->beginTransaction();

        // Handle Image Upload
        $image_path = $product['image']; // Keep existing image by default
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
                    // Delete old image if exists
                    if (!empty($product['image']) && file_exists('../' . $product['image'])) {
                        unlink('../' . $product['image']);
                    }
                    $image_path = 'uploads/products/' . $new_filename;
                }
            }
        }

        // Update product information
        $stmt = $pdo->prepare("
            UPDATE articles SET
                barcode = :barcode, reference = :reference, name = :name, category_id = :category_id,
                description = :description, image = :image, purchase_price = :purchase_price, percentage_of_sales_profit = :percentage_profit,
                wholesale_percentage = :wholesale_percentage, wholesale = :wholesale_price, sale_price = :sale_price,
                tax_rate = :tax_rate, stock_alert_level = :stock_alert
            WHERE id = :id
        ");

        $stmt->execute([
            ':barcode' => $barcode,
            ':reference' => $reference,
            ':name' => $name,
            ':category_id' => $category_id,
            ':description' => $description,
            ':image' => $image_path,
            ':purchase_price' => $purchase_price,
            ':percentage_profit' => $percentage_profit,
            ':wholesale_percentage' => $wholesale_percentage,
            ':wholesale_price' => $wholesale_price,
            ':sale_price' => $sale_price,
            ':tax_rate' => $tax_rate,
            ':stock_alert' => $stock_alert,
            ':id' => $id
        ]);

        // Update stock if changed
        if ($new_stock != $current_stock) {
            // Check if stock record exists
            $stockCheck = $pdo->prepare("SELECT id FROM stock WHERE article_id = :id");
            $stockCheck->execute([':id' => $id]);
            $stockRecord = $stockCheck->fetch();

            if ($stockRecord) {
                // Update existing stock record
                $stockStmt = $pdo->prepare("UPDATE stock SET quantity = :quantity WHERE article_id = :id");
                $stockStmt->execute([':quantity' => $new_stock, ':id' => $id]);
            } else {
                // Create new stock record
                $stockStmt = $pdo->prepare("INSERT INTO stock (article_id, quantity) VALUES (:id, :quantity)");
                $stockStmt->execute([':id' => $id, ':quantity' => $new_stock]);
            }

            // Log stock movement
            $movementType = $new_stock > $current_stock ? 'in' : 'out';
            $movementQuantity = abs($new_stock - $current_stock);
            $movementStmt = $pdo->prepare("
                INSERT INTO stock_movements (article_id, type, quantity, source, reference_id, created_at)
                VALUES (:article_id, :type, :quantity, 'manual_adjustment', :ref_id, NOW())
            ");
            $movementStmt->execute([
                ':article_id' => $id,
                ':type' => $movementType,
                ':quantity' => $movementQuantity,
                ':ref_id' => $id
            ]);
        }

        $pdo->commit();

        $message = __('product_updated_successfully', 'Produit mis à jour avec succès!');
        if ($new_stock != $current_stock) {
            $message .= ' ' . __('stock_adjusted_from_to', 'Stock ajusté de') . ' ' . $current_stock . ' à ' . $new_stock . '.';
        }
        flash('success', $message);
        header('Location: view.php?id=' . $id);
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { // Duplicate entry
            flash('error', __('barcode_already_exists_edit', 'Erreur: Le code-barres existe déjà.'));
        } else {
            flash('error', __('database_error_edit', 'Erreur de base de données:') . ' ' . $e->getMessage());
        }
    }
}

$pageTitle = __('edit_product', 'Modifier le produit');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('edit_product', 'Modifier le produit'); ?></h1>
                    <p class="mt-1 text-gray-600"><?php echo __('update_product_information_pricing', 'Mettre à jour les informations et les prix du produit'); ?></p>
                </div>
                <div class="flex space-x-3">
                    <a href="view.php?id=<?php echo $product['id']; ?>" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                        <i class="fas fa-eye mr-2"></i>
                        <?php echo __('view_product', 'Voir le produit'); ?>
                    </a>
                    <a href="products.php" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?php echo __('back_to_list', 'Retour à la liste'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($msg = flash('success')): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <span class="text-green-800"><?php echo $msg; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($msg = flash('error')): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <span class="text-red-800"><?php echo $msg; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <form method="POST" action="" enctype="multipart/form-data" class="space-y-8">
            <div class="bg-white shadow-xl rounded-xl overflow-hidden">
                <!-- Form Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white"><?php echo __('edit_product_information', 'Modifier les informations du produit'); ?></h2>
                    <p class="text-blue-100 mt-1"><?php echo __('update_details_pricing_for', 'Mettre à jour les détails et les prix pour:'); ?> <?php echo htmlspecialchars($product['name']); ?></p>
                </div>

                <!-- Form Content -->
                <div class="p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Left Column: Basic Info -->
                        <div class="space-y-6">
                            <!-- Basic Information Section -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('basic_information_edit', 'Informations de base'); ?></h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="barcode" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('barcode_required', 'Code-barres *'); ?></label>
                                        <input type="text" id="barcode" name="barcode" 
                                               value="<?php echo htmlspecialchars($product['barcode']); ?>" 
                                               required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                    </div>
                                    <div>
                                        <label for="reference" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('reference_sku', 'Référence / SKU'); ?></label>
                                        <input type="text" id="reference" name="reference" 
                                               value="<?php echo htmlspecialchars($product['reference']); ?>" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                    </div>
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('product_name_required', 'Nom du produit *'); ?></label>
                                        <input type="text" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($product['name']); ?>" 
                                               required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                    </div>
                                    <div>
                                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('category_label', 'Catégorie'); ?></label>
                                        <select id="category_id" name="category_id" 
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                            <option value=""><?php echo __('select_category', '-- Sélectionner une catégorie --'); ?></option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('description_label', 'Description'); ?></label>
                                        <textarea id="description" name="description" rows="4" 
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"><?php echo htmlspecialchars($product['description']); ?></textarea>
                                    </div>
                                    <div>
                                        <label for="product_image" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('product_image', 'Image du produit'); ?></label>
                                        <div class="space-y-3">
                                            <?php if (!empty($product['image'])): ?>
                                                <div class="flex items-center space-x-4">
                                                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Current Image" class="h-20 w-20 object-cover rounded-lg border border-gray-200">
                                                    <div>
                                                        <p class="text-sm text-gray-600"><?php echo __('current_image', 'Image actuelle'); ?></p>
                                                        <p class="text-xs text-gray-500"><?php echo __('change_image_info', 'Téléchargez une nouvelle image pour remplacer'); ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="relative">
                                                <input type="file" id="product_image" name="product_image" accept="image/*" 
                                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                       onchange="updateFileLabel(this, 'fileLabel')">
                                                <div class="flex items-center space-x-2">
                                                    <button type="button" 
                                                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200 border border-gray-300">
                                                        <?php echo !empty($product['image']) ? __('change_image', 'Changer l\'image') : __('select_product_image', 'Sélectionner une image'); ?>
                                                    </button>
                                                    <span id="fileLabel" class="text-sm text-gray-500"><?php echo __('no_new_image', 'Aucune nouvelle image'); ?></span>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500"><?php echo __('allowed_image_formats', 'Formats acceptés: JPG, PNG, GIF, WebP'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Pricing -->
                        <div class="space-y-6">
                            <!-- Pricing Section -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('pricing_information', 'Informations de prix'); ?></h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="purchase_price" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('purchase_price_edit', 'Prix d\'achat'); ?></label>
                                        <input type="number" id="purchase_price" name="purchase_price" 
                                               value="<?php echo $product['purchase_price']; ?>" 
                                               step="0.01"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                    </div>
                                    <div>
                                        <label for="percentage_profit" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('profit_margin', 'Marge de profit (%)'); ?></label>
                                        <input type="number" id="percentage_profit" name="percentage_profit" 
                                               value="<?php echo $product['percentage_of_sales_profit']; ?>" 
                                               step="0.01"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                    </div>
                                    <div>
                                        <label for="sale_price" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('sale_price_edit', 'Prix de vente'); ?> *</label>
                                        <input type="number" id="sale_price" name="sale_price" 
                                               value="<?php echo $product['sale_price']; ?>" 
                                               step="0.01"
                                               required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                    </div>
                                    <div>
                                        <label for="wholesale_percentage" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('wholesale_margin_edit', 'Marge grossiste (%)'); ?></label>
                                        <input type="number" id="wholesale_percentage" name="wholesale_percentage" 
                                               value="<?php echo $product['wholesale_percentage']; ?>" 
                                               step="0.01"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                    </div>
                                    <div>
                                        <label for="wholesale_price" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('wholesale_price_edit', 'Prix grossiste'); ?></label>
                                        <input type="number" id="wholesale_price" name="wholesale_price" 
                                               value="<?php echo $product['wholesale']; ?>" 
                                               step="0.01"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                    </div>
                                    <div>
                                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('tax_rate_edit', 'Taux de TVA'); ?></label>
                                        <select id="tax_rate" name="tax_rate" 
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                            <option value="0" <?php echo $product['tax_rate'] == 0 ? 'selected' : ''; ?>>0%</option>
                                            <option value="7" <?php echo $product['tax_rate'] == 7 ? 'selected' : ''; ?>>7%</option>
                                            <option value="15" <?php echo $product['tax_rate'] == 15 ? 'selected' : ''; ?>>15%</option>
                                            <option value="20" <?php echo $product['tax_rate'] == 20 ? 'selected' : ''; ?>>20%</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="stock_alert" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('stock_alert_level', 'Niveau d\'alerte de stock'); ?></label>
                                        <input type="number" id="stock_alert" name="stock_alert" 
                                               value="<?php echo $product['stock_alert_level']; ?>" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Management Section -->
                        <div class="lg:col-span-2">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                                <i class="fas fa-boxes mr-2 text-blue-600"></i>
                                <?php echo __('stock_management', 'Gestion des stocks'); ?>
                            </h3>
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-8 shadow-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <!-- Current Stock Display -->
                                    <div>
                                        <div class="flex items-center justify-between mb-4">
                                            <label class="text-sm font-medium text-gray-700"><?php echo __('current_stock_quantity', 'Quantité de stock actuelle'); ?></label>
                                            <div class="text-sm text-gray-600">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo ($product['current_stock'] ?? 0) <= $product['stock_alert_level'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                                    <?php echo ($product['current_stock'] ?? 0) <= $product['stock_alert_level'] ? __('low_stock', 'Stock faible') : __('in_stock', 'En stock'); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="bg-white border-2 border-blue-300 rounded-xl px-8 py-6 text-center shadow-sm">
                                            <div class="flex items-center justify-center space-x-4">
                                                <i class="fas fa-cubes text-blue-600 text-3xl"></i>
                                                <div class="text-left">
                                                    <div class="text-3xl font-bold text-gray-900"><?php echo $product['current_stock'] ?? 0; ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo __('units_available', 'unités disponibles'); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stock Adjustment -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-4"><?php echo __('stock_adjustment', 'Ajustement de stock'); ?></label>
                                        <div class="space-y-4">
                                            <div class="flex items-center space-x-4">
                                                <div class="relative">
                                                    <select id="adjustment_type" name="adjustment_type" 
                                                            class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-3 pr-8 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 cursor-pointer">
                                                        <option value="set"><?php echo __('set_new_quantity', 'Définir une nouvelle quantité'); ?></option>
                                                        <option value="add"><?php echo __('add_stock', 'Ajouter du stock (+)'); ?></option>
                                                        <option value="subtract"><?php echo __('remove_stock', 'Retirer du stock (-)'); ?></option>
                                                    </select>
                                                    <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <input type="number" id="stock_adjustment" name="stock_adjustment" 
                                                           placeholder="0"
                                                           min="0"
                                                           step="1"
                                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                                </div>
                                            </div>
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                                <div class="flex items-start space-x-3">
                                                    <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                                                    <div class="flex-1">
                                                        <p class="text-sm text-blue-800 font-medium"><?php echo __('how_to_adjust_stock', 'Comment ajuster le stock:'); ?></p>
                                                        <ul class="text-xs text-blue-700 mt-1 space-y-1">
                                                            <li>• <strong><?php echo __('set_new_quantity', 'Définir une nouvelle quantité'); ?>:</strong> <?php echo __('set_new_quantity_desc', 'Remplacer le stock actuel par un montant spécifique'); ?></li>
                                                            <li>• <strong><?php echo __('add_stock', 'Ajouter du stock (+)'); ?>:</strong> <?php echo __('add_stock_desc', 'Augmenter le stock d\'un montant spécifique'); ?></li>
                                                            <li>• <strong><?php echo __('remove_stock', 'Retirer du stock (-)'); ?>:</strong> <?php echo __('remove_stock_desc', 'Diminuer le stock d\'un montant spécifique'); ?></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
                        <div class="flex justify-end space-x-4">
                            <a href="view.php?id=<?php echo $product['id']; ?>" 
                               class="px-6 py-3 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                                <?php echo __('cancel', 'Annuler'); ?>
                            </a>
                            <button type="submit" 
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                                <i class="fas fa-save mr-2"></i>
                                <?php echo __('save_changes', 'Enregistrer les modifications'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// File label update function
function updateFileLabel(input, labelId) {
    const label = document.getElementById(labelId);
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
    } else {
        label.textContent = '<?php echo __('no_new_image', 'Aucune nouvelle image'); ?>';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
