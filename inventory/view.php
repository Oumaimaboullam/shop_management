<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle Delete
if (isset($_POST['delete'])) {
    try {
        $pdo->beginTransaction();
        
        // Delete related stock movements first
        $stmt = $pdo->prepare("DELETE FROM stock_movements WHERE article_id = :id");
        $stmt->execute([':id' => $id]);
        
        // Delete stock
        $stmt = $pdo->prepare("DELETE FROM stock WHERE article_id = :id");
        $stmt->execute([':id' => $id]);
        
        // Delete article
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        $pdo->commit();
        flash('success', __('product_deleted_successfully_view', 'Produit supprimé avec succès!'));
        header('Location: products.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        flash('error', __('error_deleting_product_view', 'Erreur lors de la suppression du produit:') . ' ' . $e->getMessage());
    }
}

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

$pageTitle = __('view_product_title', 'Détails du produit');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('view_product_title', 'Détails du produit'); ?></h1>
                    <p class="mt-1 text-gray-600"><?php echo __('view_manage_product_info', 'Voir et gérer les informations du produit'); ?></p>
                </div>
                <div class="flex space-x-3">
                    <a href="edit.php?id=<?php echo $product['id']; ?>" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                        <i class="fas fa-edit mr-2"></i>
                        <?php echo __('edit_product_btn', 'Modifier le produit'); ?>
                    </a>
                    <a href="products.php" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <?php echo __('back_to_list_view', 'Retour à la liste'); ?>
                    </a>
                    <button onclick="if(confirm('<?php echo __('delete_product_confirmation', 'Êtes-vous sûr de vouloir supprimer ce produit?'); ?>')) { document.getElementById('deleteForm').submit(); }" 
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors duration-200">
                        <i class="fas fa-trash mr-2"></i>
                        <?php echo __('delete_product_btn', 'Supprimer'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Product Card -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden">
            <!-- Product Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <?php if (!empty($product['image'])): ?>
                            <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-16 w-16 object-cover rounded-lg border-2 border-white shadow-lg">
                        <?php else: ?>
                            <div class="h-16 w-16 bg-white rounded-lg flex items-center justify-center text-blue-600 font-bold text-xl shadow-lg">
                                <?php echo substr($product['name'], 0, 2); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($product['name']); ?></h2>
                            <p class="text-blue-100 mt-1"><?php echo htmlspecialchars($product['category_name'] ?? __('uncategorized', 'Non classé')); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $product['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $product['is_active'] ? __('active', 'Actif') : __('inactive', 'Inactif'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Product Content -->
            <div class="p-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Basic Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('basic_information_view', 'Informations de base'); ?></h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600"><?php echo __('barcode_label_view', 'Code-barres'); ?></span>
                                    <span class="text-sm font-mono text-gray-900"><?php echo htmlspecialchars($product['barcode']); ?></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600"><?php echo __('reference_label_view', 'Référence'); ?></span>
                                    <span class="text-sm font-mono text-gray-900"><?php echo htmlspecialchars($product['reference']); ?></span>
                                </div>
                                <div class="py-2 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600 block mb-2"><?php echo __('description_label_view', 'Description'); ?></span>
                                    <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('stock_information_view', 'Informations sur le stock'); ?></h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600"><?php echo __('current_stock_view', 'Stock actuel'); ?></span>
                                    <span class="text-sm font-semibold <?php echo ($product['current_stock'] ?? 0) <= $product['stock_alert_level'] ? 'text-red-600' : 'text-green-600'; ?>">
                                        <?php echo $product['current_stock'] ?? 0; ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600"><?php echo __('alert_level_view', 'Niveau d\'alerte'); ?></span>
                                    <span class="text-sm font-semibold text-orange-600"><?php echo $product['stock_alert_level']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Pricing Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('pricing_information_view', 'Informations sur les prix'); ?></h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600"><?php echo __('purchase_price_view', 'Prix d\'achat'); ?></span>
                                    <span class="text-sm font-semibold text-gray-900"><?php echo number_format($product['purchase_price'], 2); ?><?php echo getSetting('currency_symbol', 'DH'); ?></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600"><?php echo __('sale_price_view', 'Prix de vente'); ?></span>
                                    <span class="text-sm font-semibold text-green-600"><?php echo number_format($product['sale_price'], 2); ?><?php echo getSetting('currency_symbol', 'DH'); ?></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600"><?php echo __('wholesale_price_view', 'Prix grossiste'); ?></span>
                                    <span class="text-sm font-semibold text-blue-600"><?php echo number_format($product['wholesale'], 2); ?><?php echo getSetting('currency_symbol', 'DH'); ?></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600"><?php echo __('profit_margin_view', 'Marge de profit'); ?></span>
                                    <span class="text-sm font-semibold text-purple-600"><?php echo $product['percentage_of_sales_profit']; ?>%</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-100">
                                    <span class="text-sm font-medium text-gray-600"><?php echo __('tax_rate_view', 'Taux de TVA'); ?></span>
                                    <span class="text-sm font-semibold text-gray-900"><?php echo $product['tax_rate']; ?>%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('quick_stats', 'Statistiques rapides'); ?></h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-blue-50 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-blue-600"><?php echo $product['current_stock'] ?? 0; ?></div>
                                    <div class="text-sm text-blue-600 mt-1"><?php echo __('units_in_stock', 'Unités en stock'); ?></div>
                                </div>
                                <div class="bg-green-50 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-green-600"><?php echo $product['percentage_of_sales_profit']; ?>%</div>
                                    <div class="text-sm text-green-600 mt-1"><?php echo __('profit_margin_view', 'Marge de profit'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="delete" value="1">
</form>

<?php require_once '../includes/footer.php'; ?>
