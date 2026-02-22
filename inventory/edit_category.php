<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$category_id = intval($_GET['id'] ?? 0);

// Fetch category data
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => $category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        flash('error', __('category_not_found', 'Catégorie non trouvée'));
        header('Location: categories.php');
        exit();
    }
} catch (PDOException $e) {
    flash('error', __('error_loading_category', 'Erreur lors du chargement de la catégorie: ') . $e->getMessage());
    header('Location: categories.php');
    exit();
}

// Handle category update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = :name, description = :description WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':id' => $category_id
            ]);
            flash('success', __('category_updated_successfully', 'Catégorie mise à jour avec succès!'));
            header('Location: categories.php');
            exit();
        } catch (PDOException $e) {
            flash('error', __('error_updating_category', 'Erreur lors de la mise à jour de la catégorie: ') . $e->getMessage());
        }
    } else {
        flash('error', __('category_name_required', 'Le nom de la catégorie est obligatoire!'));
    }
}

$pageTitle = __('edit_category', 'Modifier la catégorie');
require_once '../includes/header.php';
?>

<!-- Modern Tailwind Category Edit -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('edit_category', 'Modifier la catégorie'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('update_category_details', 'Mettre à jour les informations de la catégorie'); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="categories.php" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <?php echo __('back_to_categories', 'Retour aux catégories'); ?>
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

    <?php if ($msg = flash('error')): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span class="text-red-800"><?php echo $msg; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Category Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" id="editCategoryForm">
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="category_name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('category_name_label', 'Nom de la catégorie *'); ?></label>
                        <input type="text" id="category_name" name="name" required 
                               value="<?php echo htmlspecialchars($category['name']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200" 
                               placeholder="<?php echo __('enter_category_name', 'Entrer le nom de la catégorie'); ?>">
                    </div>
                    <div>
                        <label for="category_id_display" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('category_id_label', 'ID de la catégorie'); ?></label>
                        <input type="text" id="category_id_display" value="<?php echo $category['id']; ?>" readonly
                               class="w-full px-4 py-2 border border-gray-200 bg-gray-50 rounded-lg text-gray-500">
                    </div>
                </div>
                
                <div>
                    <label for="category_description" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('category_description_label', 'Description de la catégorie'); ?></label>
                    <textarea id="category_description" name="description" rows="4" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200" 
                              placeholder="<?php echo __('enter_category_description', 'Entrer la description de la catégorie'); ?>"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                </div>

                <!-- Category Statistics -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3"><?php echo __('category_statistics', 'Statistiques de la catégorie'); ?></h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php
                        // Get product count for this category
                        $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM articles WHERE category_id = :id");
                        $count_stmt->execute([':id' => $category_id]);
                        $product_count = $count_stmt->fetch()['count'];
                        ?>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-box text-blue-500"></i>
                                <div>
                                    <p class="text-xs text-gray-500"><?php echo __('total_products', 'Total des produits'); ?></p>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo $product_count; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-calendar text-green-500"></i>
                                <div>
                                    <p class="text-xs text-gray-500"><?php echo __('created_date', 'Date de création'); ?></p>
                                    <p class="text-sm font-medium text-gray-900"><?php echo date('Y-m-d', strtotime($category['created_at'] ?? 'now')); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-clock text-purple-500"></i>
                                <div>
                                    <p class="text-xs text-gray-500"><?php echo __('last_updated', 'Dernière mise à jour'); ?></p>
                                    <p class="text-sm font-medium text-gray-900"><?php echo date('Y-m-d H:i', strtotime($category['updated_at'] ?? 'now')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                <a href="categories.php" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <?php echo __('cancel', 'Annuler'); ?>
                </a>
                <button type="submit" name="update_category" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>
                    <?php echo __('update_category', 'Mettre à jour la catégorie'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modern JavaScript Enhancements -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('editCategoryForm');
    const nameInput = document.getElementById('category_name');
    
    form.addEventListener('submit', function(e) {
        const name = nameInput.value.trim();
        if (!name) {
            e.preventDefault();
            showNotification('<?php echo __('category_name_js_required', 'Le nom de la catégorie est obligatoire!'); ?>', 'error');
            nameInput.focus();
            return false;
        }
    });
    
    // Auto-save functionality (optional)
    let autoSaveTimer;
    const inputs = form.querySelectorAll('input, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                // You could implement auto-save here if needed
                console.log('Auto-save triggered');
            }, 2000);
        });
    });
});

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 ${
        type === 'error' ? 'bg-red-500 text-white' : 
        type === 'success' ? 'bg-green-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
</script>

<!-- Additional Modern Styling -->
<style>
/* Enhanced form styling */
input:focus, textarea:focus {
    transform: scale(1.01);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

input, textarea {
    transition: all 0.3s ease;
}

/* Statistics cards */
.stat-card {
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Button enhancements */
button[type="submit"] {
    transition: all 0.3s ease;
}

button[type="submit"]:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Form validation styling */
input:invalid {
    border-color: #ef4444;
}

input:valid {
    border-color: #10b981;
}
</style>

<?php require_once '../includes/footer.php'; ?>
