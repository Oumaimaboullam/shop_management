<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager']);

$pageTitle = __('purchase_orders', 'Commandes d\'achat');
require_once '../includes/header.php';

// Handle purchase deletion
if (isset($_POST['delete_purchase'])) {
    $purchase_id = intval($_POST['purchase_id']);
    try {
        // First check if purchase exists
        $check_stmt = $pdo->prepare("SELECT * FROM purchases WHERE id = :id");
        $check_stmt->execute([':id' => $purchase_id]);
        $purchase = $check_stmt->fetch();

        if (!$purchase) {
            flash('error', __('purchase_not_found', 'Achat non trouvé.'));
            header('Location: list.php');
            exit();
        }

        // Check if purchase has payments
        $payment_check = $pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE purchase_id = :id");
        $payment_check->execute([':id' => $purchase_id]);
        $payment_count = $payment_check->fetch()['count'];

        if ($payment_count > 0) {
            $lang = $_SESSION['lang'] ?? 'en';
            if ($lang === 'fr') {
                flash('error', 'Impossible de supprimer cet achat car il a des paiements enregistrés. Les achats avec des paiements ne peuvent pas être supprimés pour maintenir l\'intégrité des données financières.');
            } else {
                flash('error', 'Cannot delete this purchase because it has recorded payments. Purchases with payments cannot be deleted to maintain financial data integrity.');
            }
            header('Location: list.php');
            exit();
        }

        // Check if purchase is fully paid
        if ($purchase['status'] === 'paid') {
            $lang = $_SESSION['lang'] ?? 'en';
            if ($lang === 'fr') {
                flash('error', 'Impossible de supprimer cet achat car il est entièrement payé. Les achats payés ne peuvent pas être supprimés pour maintenir l\'historique financier.');
            } else {
                flash('error', 'Cannot delete this purchase because it is fully paid. Paid purchases cannot be deleted to maintain financial history.');
            }
            header('Location: list.php');
            exit();
        }

        // Check if purchase has any items
        $items_check = $pdo->prepare("SELECT COUNT(*) as count FROM purchase_items WHERE purchase_id = :id");
        $items_check->execute([':id' => $purchase_id]);
        $items_count = $items_check->fetch()['count'];

        // TEMPORARY: Always show error message for testing
        $lang = $_SESSION['lang'] ?? 'en';
        if ($lang === 'fr') {
            flash('error', 'Suppression temporairement désactivée pour test. Cette fonctionnalité est en cours de développement.');
        } else {
            flash('error', 'Deletion temporarily disabled for testing. This feature is under development.');
        }
        header('Location: list.php');
        exit();

        if ($items_count === 0) {
            $lang = $_SESSION['lang'] ?? 'en';
            if ($lang === 'fr') {
                flash('error', 'Impossible de supprimer cet achat car il ne contient aucun article. Veuillez d\'abord ajouter des articles à l\'achat.');
            } else {
                flash('error', 'Cannot delete this purchase because it contains no items. Please add items to the purchase first.');
            }
            header('Location: list.php');
            exit();
        }

        // If all checks pass, proceed with deletion
        // Delete purchase items first (foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM purchase_items WHERE purchase_id = :id");
        $stmt->execute([':id' => $purchase_id]);

        // Delete from purchases
        $stmt = $pdo->prepare("DELETE FROM purchases WHERE id = :id");
        $stmt->execute([':id' => $purchase_id]);

        flash('success', __('purchase_deleted_successfully_list', 'Achat supprimé avec succès!'));
        header('Location: list.php');
        exit();
    } catch (PDOException $e) {
        flash('error', __('error_deleting_purchase_list', 'Erreur lors de la suppression de l\'achat:') . ' ' . $e->getMessage());
    }
}

// Fetch Purchases with enhanced data
try {
    $stmt = $pdo->query("
        SELECT p.*, s.name as supplier_name
        FROM purchases p 
        JOIN suppliers s ON p.supplier_id = s.id 
        ORDER BY p.created_at DESC
    ");
    $purchases = $stmt->fetchAll();
} catch (PDOException $e) {
    $purchases = [];
    $error = __('error_loading_purchases_list', 'Erreur lors du chargement des achats:') . ' ' . $e->getMessage();
}
?>

<!-- Modern Tailwind Purchase Orders -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('purchase_orders', 'Commandes d\'achat'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('manage_supplier_purchases', 'Gérer les achats fournisseurs et suivre les paiements'); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="create.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    <?php echo __('new_purchase', 'Nouvel achat'); ?>
                </a>
                <a href="../export_csv.php?type=purchases" download class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-download mr-2"></i>
                    <?php echo __('export', 'Exporter'); ?>
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

    <!-- Search and <?php echo __('filter', 'Filtrer'); ?> -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="purchaseSearch" placeholder="<?php echo __('search_purchases', 'Rechercher des achats...'); ?>" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <select id="status<?php echo __('filter', 'Filtrer'); ?>" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                    <option value=""><?php echo __('all_status', 'Tous les statuts'); ?></option>
                    <option value="paid"><?php echo __('paid', 'Payé'); ?></option>
                    <option value="partial"><?php echo __('partial', 'Partiel'); ?></option>
                    <option value="pending"><?php echo __('pending', 'En attente'); ?></option>
                </select>
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-filter mr-2"></i>
                    <?php echo __('filter', 'Filtrer'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Purchases Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full" id="purchasesTable">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(0)">
                            <div class="flex items-center space-x-1">
                                <span>ID</span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(1)">
                            <div class="flex items-center space-x-1">
                                <span>Date</span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(2)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('supplier_label', 'Fournisseur'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(3)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('invoice_hash', 'N° Facture'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(4)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('total_amount', 'Montant total'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(5)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('paid_amount', 'Montant payé'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <?php echo __('status_label'); ?>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <?php echo __('actions', 'Actions'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($purchases) > 0): ?>
                        <?php foreach ($purchases as $purchase): 
                            $status_color = '';
                            $status_bg = '';
                            switch($purchase['status']) {
                                case 'paid':
                                    $status_color = 'text-green-800';
                                    $status_bg = 'bg-green-100';
                                    break;
                                case 'partial':
                                    $status_color = 'text-yellow-800';
                                    $status_bg = 'bg-yellow-100';
                                    break;
                                case 'pending':
                                    $status_color = 'text-blue-800';
                                    $status_bg = 'bg-blue-100';
                                    break;
                                default:
                                    $status_color = 'text-gray-800';
                                    $status_bg = 'bg-gray-100';
                            }
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200" data-supplier="<?php echo strtolower($purchase['supplier_name']); ?>" data-invoice="<?php echo strtolower($purchase['invoice_number']); ?>" data-status="<?php echo $purchase['status']; ?>">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">#<?php echo $purchase['id']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($purchase['created_at'])); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo date('g:i A', strtotime($purchase['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                            <?php echo substr($purchase['supplier_name'], 0, 2); ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($purchase['supplier_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo __('supplier_label', 'Fournisseur'); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($purchase['invoice_number']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900"><?php echo number_format($purchase['total_amount'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo number_format($purchase['paid_amount'], 2); ?> DH</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $status_bg . ' ' . $status_color; ?>">
                                        <?php echo __('' . $purchase['status'] . '_status', ucfirst($purchase['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="view.php?id=<?php echo $purchase['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-primary-100 hover:bg-primary-200 text-primary-700 text-xs font-medium rounded-lg transition-colors duration-200" data-tooltip="<?php echo __('view_details', 'Voir les détails'); ?>">
                                            <i class="fas fa-eye mr-1"></i>
                                            <?php echo __('view', 'Voir'); ?>
                                        </a>
                                        <?php if ($purchase['status'] !== 'paid'): ?>
                                            <a href="pay.php?id=<?php echo $purchase['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-lg transition-colors duration-200" data-tooltip="<?php echo __('make_payment', 'Effectuer un paiement'); ?>">
                                                <i class="fas fa-credit-card mr-1"></i>
<?php echo __('pay', 'Payer'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" onclick="confirmDelete(<?php echo $purchase['id']; ?>, '<?php echo htmlspecialchars(addslashes($purchase['invoice_number'])); ?>')" class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition-colors duration-200" data-tooltip="<?php echo __('delete_purchase', 'Supprimer l\'achat'); ?>">
                                            <i class="fas fa-trash mr-1"></i>
<?php echo __('delete_purchase', 'Supprimer l\'achat'); ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-truck text-gray-400 text-xl"></i>
                                </div>
                                <h4 class="text-lg font-medium text-gray-900 mb-2"><?php echo __('no_purchases_found_list', 'Aucun achat trouvé'); ?></h4>
                                <p class="text-gray-600 mb-4"><?php echo __('start_first_purchase_order', 'Commencez par créer votre première commande d\'achat'); ?></p>
                                <a href="create.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-plus mr-2"></i>
                                    <?php echo __('create_first_purchase', 'Créer le premier achat'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo __('delete_purchase_title', 'Supprimer l\'achat'); ?></h3>
            <p class="text-gray-600 mb-6"><?php echo __('delete_purchase_confirmation', 'Êtes-vous sûr de vouloir supprimer l\'achat'); ?> <span id="purchaseInvoice" class="font-semibold"></span><?php echo __('action_cannot_be_undone_purchase', '? Cette action ne peut pas être annulée.'); ?></p>
            <div class="flex justify-center space-x-4">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
<?php echo __('cancel', 'Annuler'); ?>
                </button>
                <form id="deleteForm" method="POST" class="inline">
                    <input type="hidden" name="purchase_id" id="deletePurchaseId">
                    <input type="hidden" name="delete_purchase" value="1">
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-trash mr-1"></i>
<?php echo __('delete_purchase', 'Supprimer l\'achat'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modern JavaScript Enhancements -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('purchaseSearch');
    const status<?php echo __('filter', 'Filtrer'); ?> = document.getElementById('status<?php echo __('filter', 'Filtrer'); ?>');
    const table = document.getElementById('purchasesTable');
    const rows = table.querySelectorAll('tbody tr');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = status<?php echo __('filter', 'Filtrer'); ?>.value.toLowerCase();
        
        rows.forEach(row => {
            const supplier = row.dataset.supplier;
            const invoice = row.dataset.invoice;
            const status = row.dataset.status;
            
            const matchesSearch = supplier.includes(searchTerm) || invoice.includes(searchTerm);
            const matchesStatus = !selectedStatus || status === selectedStatus;
            
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    searchInput.addEventListener('input', filterTable);
    status<?php echo __('filter', 'Filtrer'); ?>.addEventListener('change', filterTable);
    
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
function confirmDelete(purchaseId, invoiceNumber) {
    document.getElementById('purchaseInvoice').textContent = invoiceNumber;
    document.getElementById('deletePurchaseId').value = purchaseId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Table sorting function
function sortTable(columnIndex) {
    const table = document.getElementById('purchasesTable');
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
        
        // Handle numeric columns (ID, Total, Paid)
        if (columnIndex === 0 || columnIndex >= 4 && columnIndex <= 5) {
            const aNum = parseFloat(aValue.replace(/[#DH,]/g, '')) || 0;
            const bNum = parseFloat(bValue.replace(/[#DH,]/g, '')) || 0;
            return isAscending ? aNum - bNum : bNum - aNum;
        }
        
        // Handle date column
        if (columnIndex === 1) {
            const aDate = new Date(aValue);
            const bDate = new Date(bValue);
            return isAscending ? aDate - bDate : bDate - aDate;
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
#purchaseSearch, #status<?php echo __('filter', 'Filtrer'); ?> {
    transition: all 0.3s ease;
}

#purchaseSearch:focus, #status<?php echo __('filter', 'Filtrer'); ?>:focus {
    transform: scale(1.02);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
