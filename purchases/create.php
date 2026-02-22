<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

// Fetch Suppliers and Products
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT * FROM articles WHERE is_active = 1 ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'];
    $invoice_number = sanitize($_POST['invoice_number']);
    $payment_type = $_POST['payment_type'];
    $status = $_POST['status'];
    
    // Calculate total from items
    $items = $_POST['items']; // Array of item IDs
    $quantities = $_POST['quantities'];
    $prices = $_POST['prices'];
    
    $total_amount = 0;
    $purchase_items = [];
    
    for ($i = 0; $i < count($items); $i++) {
        if (!empty($items[$i]) && $quantities[$i] > 0) {
            $line_total = $quantities[$i] * $prices[$i];
            $total_amount += $line_total;
            $purchase_items[] = [
                'article_id' => $items[$i],
                'quantity' => $quantities[$i],
                'unit_price' => $prices[$i],
                'total_price' => $line_total
            ];
        }
    }
    
    $advance_amount = $_POST['advance_amount'] ?? 0;
    $paid_amount = ($status === 'paid') ? $total_amount : $advance_amount;

    try {
        $pdo->beginTransaction();

        // 1. Create Purchase
        $stmt = $pdo->prepare("
            INSERT INTO purchases (supplier_id, invoice_number, total_amount, paid_amount, status, type_of_payment, created_at)
            VALUES (:supplier_id, :invoice_number, :total_amount, :paid_amount, :status, :payment_type, NOW())
        ");
        $stmt->execute([
            ':supplier_id' => $supplier_id,
            ':invoice_number' => $invoice_number,
            ':total_amount' => $total_amount,
            ':paid_amount' => $paid_amount,
            ':status' => $status,
            ':payment_type' => $payment_type
        ]);
        
        $purchase_id = $pdo->lastInsertId();

        // 2. Insert Items & Update Stock
        foreach ($purchase_items as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO purchase_items (purchase_id, article_id, quantity, unit_price, total_price)
                VALUES (:pid, :aid, :qty, :price, :total)
            ");
            $stmt->execute([
                ':pid' => $purchase_id,
                ':aid' => $item['article_id'],
                ':qty' => $item['quantity'],
                ':price' => $item['unit_price'],
                ':total' => $item['total_price']
            ]);
            
            // Update Stock
            $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity + :qty WHERE article_id = :aid");
            $stmt->execute([':qty' => $item['quantity'], ':aid' => $item['article_id']]);
            
            // Log Movement
            $stmt = $pdo->prepare("
                INSERT INTO stock_movements (article_id, type, quantity, source, reference_id, created_at)
                VALUES (:aid, 'in', :qty, 'purchase', :ref, NOW())
            ");
            $stmt->execute([':aid' => $item['article_id'], ':qty' => $item['quantity'], ':ref' => $purchase_id]);
        }
        
        // 3. Update Supplier Balance
        $stmt = $pdo->prepare("
            UPDATE suppliers 
            SET balance = balance + :total_amount
            WHERE id = :supplier_id
        ");
        $stmt->execute([
            ':total_amount' => $total_amount,
            ':supplier_id' => $supplier_id
        ]);

        $pdo->commit();
        flash('success', __('purchase_recorded_successfully', 'Achat enregistré avec succès!'));
        header('Location: list.php');
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', __('error_recording_purchase', 'Erreur lors de l\'enregistrement de l\'achat:') . ' ' . $e->getMessage());
    }
}

$pageTitle = __('new_purchase_order', 'Nouvelle commande d\'achat');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900"><?php echo __('new_purchase_order', 'Nouvelle commande d\'achat'); ?></h1>
            <p class="text-gray-600"><?php echo __('add_new_products_inventory', 'Ajouter de nouveaux produits à votre inventaire'); ?></p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900"><?php echo __('purchase_details', 'Détails de l\'achat'); ?></h2>
            </div>
            
            <form method="POST" action="" class="p-6 space-y-6">
                <!-- Supplier and Invoice Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('supplier_required', 'Fournisseur *'); ?></label>
                            <select name="supplier_id" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value=""><?php echo __('select_supplier', '-- Sélectionner un fournisseur --'); ?></option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('invoice_number_label', 'Numéro de facture'); ?></label>
                            <input type="text" 
                                   name="invoice_number" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="<?php echo __('enter_invoice_number', 'Entrer le numéro de facture'); ?>">
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('payment_type_label', 'Type de paiement'); ?></label>
                            <select name="payment_type" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="cash"><?php echo __('cash', 'Espèces'); ?></option>
                                <option value="check"><?php echo __('check', 'Chèque'); ?></option>
                                <option value="transfer"><?php echo __('transfer', 'Virement'); ?></option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('status_label', 'Statut'); ?></label>
                            <select name="status" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="pending"><?php echo __('pending_status', 'En attente'); ?></option>
                                <option value="paid"><?php echo __('paid_status', 'Payé'); ?></option>
                                <option value="partial"><?php echo __('partial_status', 'Partiel'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo __('items_label', 'Articles'); ?></h3>
                        <div class="flex space-x-2">
                            <button type="button" 
                                    onclick="showAddProductModal()" 
                                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span><?php echo __('add_new_product', 'Ajouter un nouveau produit'); ?></span>
                            </button>
                            <button type="button" 
                                    onclick="addRow()" 
                                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span><?php echo __('add_item', 'Ajouter un article'); ?></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table id="itemsTable" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('product_column', 'Produit'); ?></th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('quantity_column', 'Quantité'); ?></th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('unit_price_column', 'Prix unitaire'); ?></th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('total_column', 'Total'); ?></th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('action_column', 'Action'); ?></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr class="item-row">
                                    <td class="px-4 py-3">
                                        <select name="items[]" 
                                                required 
                                                onchange="updateRowTotal(this)" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value=""><?php echo __('select_product', 'Sélectionner un produit'); ?></option>
                                            <?php foreach ($products as $p): ?>
                                                <option value="<?php echo $p['id']; ?>" 
                                                        data-price="<?php echo $p['purchase_price']; ?>">
                                                    <?php echo htmlspecialchars($p['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" 
                                               name="quantities[]" 
                                               value="1" 
                                               min="1" 
                                               onchange="updateRowTotal(this)"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" 
                                               name="prices[]" 
                                               step="0.01" 
                                               onchange="updateRowTotal(this)"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="0.00">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" 
                                               readonly 
                                               class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg row-total font-semibold"
                                               value="0.00">
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button" 
                                                onclick="removeRow(this)" 
                                                class="bg-red-100 text-red-600 hover:bg-red-200 px-3 py-2 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Total and Payment Section -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Total Section -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('purchase_summary', 'Résumé de l\'achat'); ?></h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><?php echo __('subtotal_label', 'Sous-total:'); ?></span>
                                    <span id="subtotal" class="font-semibold">0.00 DH</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><?php echo __('tax_label', 'Taxe (10%):'); ?></span>
                                    <span id="tax" class="font-semibold">0.00 DH</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold border-t pt-3">
                                    <span><?php echo __('total_amount_label', 'Montant total:'); ?></span>
                                    <span id="totalAmount" class="text-blue-600">0.00 DH</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Advance Payment Section -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('advance_payment', 'Paiement d\'avance'); ?></h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('advance_amount_label', 'Montant d\'avance'); ?></label>
                                    <input type="number" 
                                           id="advanceAmount"
                                           name="advance_amount" 
                                           step="0.01" 
                                           min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="0.00"
                                           onchange="updateRemaining()">
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><?php echo __('total_amount_display', 'Montant total:'); ?></span>
                                    <span id="totalDisplay" class="font-semibold">0.00 DH</span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600"><?php echo __('advance_paid_display', 'Avance payée:'); ?></span>
                                    <span id="advanceDisplay" class="font-semibold">0.00</span>
                                </div>
                                
                                <div class="flex justify-between text-lg font-bold border-t pt-3">
                                    <span><?php echo __('remaining_balance', 'Solde restant:'); ?></span>
                                    <span id="remainingBalance" class="text-red-600">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="list.php" 
                       class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <?php echo __('cancel_purchase', 'Annuler'); ?>
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-semibold">
<?php echo __('save_purchase', 'Enregistrer l\'achat'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addRow() {
    const table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    const newRow = table.rows[0].cloneNode(true);
    
    // Clear values
    newRow.querySelector('select').value = '';
    newRow.querySelector('input[name="quantities[]"]').value = 1;
    newRow.querySelector('input[name="prices[]"]').value = '';
    newRow.querySelector('.row-total').value = '0.00';
    
    table.appendChild(newRow);
}

function removeRow(btn) {
    const row = btn.closest('tr');
    const table = row.parentNode;
    if (table.rows.length > 1) {
        table.removeChild(row);
    }
}

function updateRowTotal(input) {
    const row = input.closest('tr');
    const select = row.querySelector('select');
    const qty = parseFloat(row.querySelector('input[name="quantities[]"]').value) || 0;
    const priceInput = row.querySelector('input[name="prices[]"]');
    
    // Auto-fill price if selected from product
    if (input.tagName === 'SELECT') {
        const option = select.options[select.selectedIndex];
        const defaultPrice = option.getAttribute('data-price');
        if (defaultPrice) priceInput.value = defaultPrice;
    }
    
    const price = parseFloat(priceInput.value) || 0;
    const total = qty * price;
    
    row.querySelector('.row-total').value = total.toFixed(2);
    
    // Update grand total
    updateTotals();
}

function updateTotals() {
    const rows = document.querySelectorAll('.item-row');
    let subtotal = 0;
    
    rows.forEach(row => {
        const total = parseFloat(row.querySelector('.row-total').value) || 0;
        subtotal += total;
    });
    
    const tax = subtotal * 0.10; // 10% tax
    const totalAmount = subtotal + tax;
    
    // Update display
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('tax').textContent = tax.toFixed(2);
    document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
    document.getElementById('totalDisplay').textContent = totalAmount.toFixed(2);
    
    // Update remaining balance
    updateRemaining();
}

function updateRemaining() {
    const totalAmount = parseFloat(document.getElementById('totalAmount').textContent.replace(',', '')) || 0;
    const advanceAmount = parseFloat(document.getElementById('advanceAmount').value) || 0;
    const remaining = totalAmount - advanceAmount;
    
    document.getElementById('advanceDisplay').textContent = advanceAmount.toFixed(2) + ' DH';
    document.getElementById('remainingBalance').textContent = remaining.toFixed(2) + ' DH';
    
    // Update remaining balance color
    const remainingElement = document.getElementById('remainingBalance');
    if (remaining > 0) {
        remainingElement.className = 'text-red-600';
    } else if (remaining < 0) {
        remainingElement.className = 'text-green-600';
    } else {
        remainingElement.className = 'text-gray-600';
    }
}

// Add New Product Modal Functions
function getModalTranslations() {
    const currentLang = '<?php echo $_SESSION['lang'] ?? 'en'; ?>';
    
    if (currentLang === 'fr') {
        return {
            addNewProductTitle: 'Ajouter un nouveau produit',
            productNameRequired: 'Nom du produit *',
            enterProductName: 'Entrer le nom du produit',
            barcodeLabel: 'Code-barres',
            enterBarcodeOptional: 'Entrer le code-barres (optionnel)',
            referenceLabel: 'Référence',
            enterReferenceOptional: 'Entrer la référence (optionnel)',
            categoryLabel: 'Catégorie',
            selectCategory: '-- Sélectionner une catégorie --',
            addNewCategoryOption: '+ Ajouter une nouvelle catégorie',
            enterNewCategoryName: 'Entrer le nom de la nouvelle catégorie',
            descriptionLabel: 'Description',
            enterProductDescriptionOptional: 'Entrer la description du produit (optionnel)',
            productImageLabel: 'Image du produit',
            selectProductImage: 'Sélectionner l\'image du produit',
            allowedImageFormats: 'Formats autorisés: JPG, PNG, GIF, WebP',
            noImageUploaded: 'Aucune image téléchargée',
            purchasePriceRequired: 'Prix d\'achat *',
            profitPercentageRequired: 'Pourcentage de profit (%) *',
            wholesalePercentage: 'Pourcentage grossiste (%)',
            initialQuantity: 'Quantité initiale',
            stockAlertLevel: 'Niveau d\'alerte de stock',
            cancel: 'Annuler',
            createProduct: 'Créer le produit'
        };
    } else {
        return {
            addNewProductTitle: 'Add New Product',
            productNameRequired: 'Product Name *',
            enterProductName: 'Enter product name',
            barcodeLabel: 'Barcode',
            enterBarcodeOptional: 'Enter barcode (optional)',
            referenceLabel: 'Reference',
            enterReferenceOptional: 'Enter reference (optional)',
            categoryLabel: 'Category',
            selectCategory: '-- Select category --',
            addNewCategoryOption: '+ Add new category',
            enterNewCategoryName: 'Enter new category name',
            descriptionLabel: 'Description',
            enterProductDescriptionOptional: 'Enter product description (optional)',
            productImageLabel: 'Product Image',
            selectProductImage: 'Select Product Image',
            allowedImageFormats: 'Allowed formats: JPG, PNG, GIF, WebP',
            noImageUploaded: 'No image uploaded',
            purchasePriceRequired: 'Purchase Price *',
            profitPercentageRequired: 'Profit Percentage (%) *',
            wholesalePercentage: 'Wholesale Percentage (%)',
            initialQuantity: 'Initial Quantity',
            stockAlertLevel: 'Stock Alert Level',
            cancel: 'Cancel',
            createProduct: 'Create Product'
        };
    }
}

function showAddProductModal() {
    // Remove existing modal if any
    const existingModal = document.getElementById('addProductModal');
    if (existingModal) {
        existingModal.remove();
    }

    const modalTranslations = getModalTranslations();
    const modal = document.createElement('div');
    modal.id = 'addProductModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">${modalTranslations.addNewProductTitle}</h3>
                <button onclick="closeAddProductModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="addProductForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.productNameRequired}</label>
                    <input type="text" 
                           name="name" 
                           required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="${modalTranslations.enterProductName}">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.barcodeLabel}</label>
                        <input type="text" 
                               name="barcode" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="${modalTranslations.enterBarcodeOptional}">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.referenceLabel}</label>
                        <input type="text" 
                               name="reference" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="${modalTranslations.enterReferenceOptional}">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.categoryLabel}</label>
                    <div class="flex space-x-2">
                        <select name="category_id" 
                                id="categorySelect"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">${modalTranslations.selectCategory}</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                            <option value="new">${modalTranslations.addNewCategoryOption}</option>
                        </select>
                        <input type="text" 
                               name="new_category" 
                               id="newCategoryInput"
                               placeholder="${modalTranslations.enterNewCategoryName}"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               style="display: none;">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.descriptionLabel}</label>
                    <textarea name="description" 
                              rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="${modalTranslations.enterProductDescriptionOptional}"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.productImageLabel}</label>
                    <div class="relative">
                        <input type="file" 
                               name="product_image" 
                               accept="image/*" 
                               id="modalProductImage"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                               onchange="updateModalFileLabel(this)">
                        <div class="flex items-center space-x-2">
                            <button type="button" 
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200 border border-gray-300">
                                ${modalTranslations.selectProductImage}
                            </button>
                            <span id="modalFileLabel" class="text-sm text-gray-500">${modalTranslations.noImageUploaded}</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">${modalTranslations.allowedImageFormats}</p>
                    <div id="modalImagePreview" class="mt-2 hidden">
                        <img id="modalPreviewImg" src="" alt="Product Preview" class="h-16 w-16 object-cover rounded-lg border border-gray-200">
                        <p class="text-xs text-gray-500 mt-1">${modalTranslations.currentImage}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.purchasePriceRequired}</label>
                        <input type="number" 
                               name="purchase_price" 
                               step="0.01" 
                               required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0.00">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.profitPercentageRequired}</label>
                        <input type="number" 
                               name="percentage_of_sales_profit" 
                               step="0.1" 
                               required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0.0">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.wholesalePercentage}</label>
                        <input type="number" 
                               name="wholesale_percentage" 
                               step="0.1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0.0">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.initialQuantity}</label>
                        <input type="number" 
                               name="initial_quantity" 
                               value="0"
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">${modalTranslations.stockAlertLevel}</label>
                    <input type="number" 
                           name="stock_alert_level" 
                           value="10"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="10">
                </div>
                
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" 
                            onclick="closeAddProductModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        ${modalTranslations.cancel}
                    </button>
                    <button type="submit" 
                            class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors font-semibold">
                        ${modalTranslations.createProduct}
                    </button>
                </div>
            </form>
        </div>
    `;

    document.body.appendChild(modal);
    
    // Add form submit handler
    document.getElementById('addProductForm').addEventListener('submit', handleAddProduct);
    
    // Add category change handler
    document.getElementById('categorySelect').addEventListener('change', function() {
        const newCategoryInput = document.getElementById('newCategoryInput');
        if (this.value === 'new') {
            newCategoryInput.style.display = 'block';
            this.style.display = 'none';
        } else {
            newCategoryInput.style.display = 'none';
            this.style.display = 'block';
        }
    });
    
    // Add image preview handler for modal
    const modalImageInput = document.querySelector('input[name="product_image"]');
    if (modalImageInput) {
        modalImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('modalImagePreview');
                    const img = document.getElementById('modalPreviewImg');
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
                updateModalFileLabel(e.target);
            }
        });
    }
}

function closeAddProductModal() {
    const modal = document.getElementById('addProductModal');
    if (modal) {
        modal.remove();
    }
}

async function handleAddProduct(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const productData = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('../api/articles/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(productData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Add the new product to all select dropdowns
            const newOption = document.createElement('option');
            newOption.value = result.article.id;
            newOption.setAttribute('data-price', result.article.purchase_price);
            newOption.textContent = result.article.name;
            
            // Add to all product selects in the table
            const selects = document.querySelectorAll('select[name="items[]"]');
            selects.forEach(select => {
                select.appendChild(newOption.cloneNode(true));
            });
            
            // Add a new row with the newly created product
            const table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
            const newRow = table.rows[0].cloneNode(true);
            
            // Set the new product in the select
            const select = newRow.querySelector('select[name="items[]"]');
            select.value = result.article.id;
            
            // Set the purchase price
            const priceInput = newRow.querySelector('input[name="prices[]"]');
            priceInput.value = result.article.purchase_price;
            
            // Set quantity to 1
            const qtyInput = newRow.querySelector('input[name="quantities[]"]');
            qtyInput.value = 1;
            
            // Calculate total
            const total = 1 * parseFloat(result.article.purchase_price);
            newRow.querySelector('.row-total').value = total.toFixed(2);
            
            // Add the new row to the table
            table.appendChild(newRow);
            
            alert('Product created successfully and added to purchase!');
            closeAddProductModal();
        } else {
            alert('Error creating product: ' + result.message);
        }
    } catch (error) {
        console.error('Error creating product:', error);
        alert('Error creating product. Please try again.');
    }
}

// Update modal file label function
function updateModalFileLabel(input) {
    const label = document.getElementById('modalFileLabel');
    const modalTranslations = getModalTranslations();
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
    } else {
        label.textContent = modalTranslations.noImageUploaded;
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>