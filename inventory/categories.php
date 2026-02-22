<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$pageTitle = __('categories', 'Catégories');
require_once '../includes/header.php';

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description
                ]);
                flash('success', __('category_added_successfully', 'Catégorie ajoutée avec succès!'));
            } catch (PDOException $e) {
                flash('error', __('error_adding_category', 'Erreur lors de l\'ajout de la catégorie: ') . $e->getMessage());
            }
        } else {
            flash('error', __('category_name_required', 'Le nom de la catégorie est obligatoire!'));
        }
    } elseif (isset($_POST['delete_category'])) {
        $category_id = intval($_POST['category_id']);
        try {
            // Check if category is being used
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM articles WHERE category_id = :id");
            $check_stmt->execute([':id' => $category_id]);
            $result = $check_stmt->fetch();
            
            if ($result['count'] > 0) {
                flash('error', __('cannot_delete_category', 'Impossible de supprimer la catégorie. Elle est utilisée par') . ' ' . $result['count'] . ' ' . __('products_count', 'produit(s)') . '.');
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
                $stmt->execute([':id' => $category_id]);
                flash('success', __('category_deleted_successfully', 'Catégorie supprimée avec succès!'));
            }
        } catch (PDOException $e) {
            flash('error', __('error_deleting_category', 'Erreur lors de la suppression de la catégorie: ') . $e->getMessage());
        }
    }
}

// Fetch categories with product count
try {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(a.id) as product_count 
        FROM categories c 
        LEFT JOIN articles a ON c.id = a.category_id 
        GROUP BY c.id 
        ORDER BY c.name ASC
    ");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $error = __('error_loading_categories', 'Erreur lors du chargement des catégories: ') . $e->getMessage();
}
?>

<!-- Modern Tailwind Category Management -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('categories', 'Catégories'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('manage_product_categories', 'Gérer les catégories de produits'); ?> <?php echo __('organize_your_inventory', 'Organiser votre inventaire'); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="../inventory/products.php" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <?php echo __('back_to_products', 'Retour aux produits'); ?>
                </a>
                <button onclick="showAddModal()" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    <?php echo __('add_category', 'Ajouter une catégorie'); ?>
                </button>
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

    <?php if ($msg = flash('error')): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span class="text-red-800"><?php echo $msg; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Categories Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full" id="categoriesTable">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(0)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('name', 'Nom'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(1)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('description', 'Description'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(2)">
                            <div class="flex items-center space-x-1">
                                <span><?php echo __('products', 'Produits'); ?></span>
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <?php echo __('actions', 'Actions'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($categories) > 0): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200" data-name="<?php echo strtolower($category['name']); ?>" data-description="<?php echo strtolower($category['description'] ?? ''); ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                            <?php echo substr($category['name'], 0, 2); ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo __('id_label', 'ID'); ?>: <?php echo $category['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php echo !empty($category['description']) ? htmlspecialchars($category['description']) : '<span class="text-gray-400">' . __('no_description', 'Aucune description') . '</span>'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $category['product_count'] > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $category['product_count']; ?> <?php echo __('products', 'produits'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="edit_category.php?id=<?php echo $category['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-primary-100 hover:bg-primary-200 text-primary-700 text-xs font-medium rounded-lg transition-colors duration-200" data-tooltip="<?php echo __('edit_category_tooltip', 'Modifier la catégorie'); ?>">
                                            <i class="fas fa-edit mr-1"></i>
                                            <?php echo __('edit_category_btn', 'Modifier'); ?>
                                        </a>
                                        <?php if ($category['product_count'] == 0): ?>
                                            <button onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')" class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition-colors duration-200" data-tooltip="<?php echo __('delete_category_tooltip', 'Supprimer la catégorie'); ?>">
                                                <i class="fas fa-trash mr-1"></i>
                                                <?php echo __('delete_category_btn', 'Supprimer'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-tags text-gray-400 text-xl"></i>
                                </div>
                                <h4 class="text-lg font-medium text-gray-900 mb-2"><?php echo __('no_categories_found_categories', 'Aucune catégorie trouvée'); ?></h4>
                                <p class="text-gray-600 mb-4"><?php echo __('start_by_adding_first_category_desc', 'Commencez par ajouter votre première catégorie pour organiser vos produits'); ?></p>
                                <button onclick="showAddModal()" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-plus mr-2"></i>
                                    <?php echo __('add_your_first_category_btn', 'Ajouter votre première catégorie'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900"><?php echo __('add_new_category', 'Ajouter une nouvelle catégorie'); ?></h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="addCategoryForm">
            <div class="space-y-4">
                <div>
                    <label for="category_name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('category_name_label', 'Nom de la catégorie *'); ?></label>
                    <input type="text" id="category_name" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200" placeholder="<?php echo __('enter_category_name', 'Entrer le nom de la catégorie'); ?>">
                </div>
                <div>
                    <label for="category_description" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('category_description_label', 'Description de la catégorie'); ?></label>
                    <textarea id="category_description" name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200" placeholder="<?php echo __('enter_category_description', 'Entrer la description de la catégorie'); ?> (optional)"></textarea>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <?php echo __('cancel', 'Annuler'); ?>
                </button>
                <button type="submit" name="add_category" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    <?php echo __('save_category', 'Enregistrer la catégorie'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo __('delete_category_title', 'Supprimer la catégorie'); ?></h3>
            <p class="text-gray-600 mb-6"><?php echo __('delete_category_confirmation', 'Êtes-vous sûr de vouloir supprimer'); ?> <span id="categoryName" class="font-semibold"></span>? <?php echo __('action_cannot_be_undone', 'cette action ne peut pas être annulée.'); ?></p>
            <div class="flex justify-center space-x-4">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <?php echo __('cancel', 'Annuler'); ?>
                </button>
                <form id="deleteForm" method="POST" class="inline">
                    <input type="hidden" name="category_id" id="deleteCategoryId">
                    <input type="hidden" name="delete_category" value="1">
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-trash mr-1"></i>
                        <?php echo __('delete_category', 'Supprimer la catégorie'); ?>
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
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = '<?php echo __('search_placeholder_categories', 'Rechercher des catégories...'); ?>';
    searchInput.className = 'w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200';
    
    // Add search input to page header
    const header = document.querySelector('.bg-white.rounded-xl.shadow-sm.border.border-gray-200.p-6');
    if (header) {
        const searchContainer = document.createElement('div');
        searchContainer.className = 'mt-4';
        searchContainer.appendChild(searchInput);
        header.appendChild(searchContainer);
    }
    
    const table = document.getElementById('categoriesTable');
    const rows = table.querySelectorAll('tbody tr');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        rows.forEach(row => {
            const name = row.dataset.name;
            const description = row.dataset.description;
            
            if (name.includes(searchTerm) || description.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
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

// Modal functions
function showAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('category_name').focus();
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.getElementById('addCategoryForm').reset();
}

function confirmDelete(categoryId, categoryName) {
    document.getElementById('categoryName').textContent = categoryName;
    document.getElementById('deleteCategoryId').value = categoryId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Table sorting function
function sortTable(columnIndex) {
    const table = document.getElementById('categoriesTable');
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
        
        // Handle numeric columns (product count)
        if (columnIndex === 2) {
            const aNum = parseInt(aValue) || 0;
            const bNum = parseInt(bValue) || 0;
            return isAscending ? aNum - bNum : bNum - aNum;
        }
        
        // Handle text columns
        return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });
    
    // Reorder DOM
    rows.forEach(row => tbody.appendChild(row));
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const addModal = document.getElementById('addModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === addModal) {
        closeAddModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
});

// Form validation
document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
    const name = document.getElementById('category_name').value.trim();
    if (!name) {
        e.preventDefault();
        alert('<?php echo __('category_name_js_required', 'Le nom de la catégorie est obligatoire!'); ?>');
        document.getElementById('category_name').focus();
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

/* Modal animations */
#addModal, #deleteModal {
    transition: opacity 0.3s ease;
}

#addModal:not(.hidden), #deleteModal:not(.hidden) {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Enhanced form inputs */
input, textarea {
    transition: all 0.3s ease;
}

input:focus, textarea:focus {
    transform: scale(1.02);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Search input styling */
input[type="text"][placeholder="Search categories..."] {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

input[type="text"][placeholder="Search categories..."]:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
}
</style>

<?php require_once '../includes/footer.php'; ?>