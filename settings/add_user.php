<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = sanitize($_POST['name'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = sanitize($_POST['role'] ?? 'cashier');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validation
        $errors = [];
        
        if (empty($name)) {
            $errors[] = __('name_required_error');
        }
        
        if (empty($username)) {
            $errors[] = __('username_required_error');
        } elseif (strlen($username) < 3) {
            $errors[] = __('username_min_chars_error');
        } else {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = __('username_exists_error');
            }
        }
        
        if (empty($password)) {
            $errors[] = __('password_required_error');
        } elseif (strlen($password) < 6) {
            $errors[] = __('password_min_chars_error');
        } elseif ($password !== $confirm_password) {
            $errors[] = __('passwords_do_not_match_error');
        }

        if (!in_array($role, ['admin', 'manager', 'cashier'])) {
            $errors[] = __('invalid_role_error');
        }

        if (empty($errors)) {
            // Hash password and create user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $username, $password_hash, $role, $is_active]);

            flash('success', __('user_created_successfully'));
            header('Location: users.php');
            exit();
        }

    } catch (Exception $e) {
        $errors[] = __('error_creating_user') . $e->getMessage();
    }
}

$pageTitle = __('add_new_user_title');
require_once '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('settings'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('add_new_user_description'); ?></p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <nav class="flex border-b border-gray-200 overflow-x-auto">
            <a href="index.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200 whitespace-nowrap">
                <i class="fas fa-store mr-2"></i>
                <?php echo __('shop_info'); ?>
            </a>
            <a href="users.php" class="px-6 py-4 text-sm font-medium text-primary-600 border-b-2 border-primary-600 bg-primary-50 whitespace-nowrap">
                <i class="fas fa-users mr-2"></i>
                <?php echo __('users_management'); ?>
            </a>
            <a href="profile.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200 whitespace-nowrap">
                <i class="fas fa-user mr-2"></i>
                <?php echo __('profile'); ?>
            </a>
            <a href="password.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200 whitespace-nowrap">
                <i class="fas fa-lock mr-2"></i>
                <?php echo __('change_password'); ?>
            </a>
        </nav>

        <!-- Add User Content -->
        <div class="p-6">
            <div class="max-w-2xl mx-auto">
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <span class="text-red-800 font-medium"><?php echo __('please_correct_errors'); ?></span>
                        </div>
                        <ul class="list-disc list-inside text-red-700 ml-4">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-xl border border-gray-200 p-8 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo __('user_information_form'); ?></h2>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('full_name_required'); ?> *</label>
                                <input type="text" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('username_required'); ?> *</label>
                                <input type="text" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('password_required'); ?> *</label>
                                <input type="password" id="password" name="password" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('confirm_password_required'); ?> *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('role_required'); ?> *</label>
                                <select id="role" name="role" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                                    <option value="cashier" <?php echo (($_POST['role'] ?? 'cashier') == 'cashier') ? 'selected' : ''; ?>><?php echo __('cashier_role'); ?></option>
                                    <option value="manager" <?php echo (($_POST['role'] ?? '') == 'manager') ? 'selected' : ''; ?>><?php echo __('manager_role'); ?></option>
                                    <option value="admin" <?php echo (($_POST['role'] ?? '') == 'admin') ? 'selected' : ''; ?>><?php echo __('admin_role'); ?></option>
                                </select>
                            </div>

                            <div class="flex items-end">
                                <label class="flex items-center space-x-3 p-2 cursor-pointer">
                                    <input type="checkbox" id="is_active" name="is_active" 
                                           <?php echo isset($_POST['is_active']) || !isset($_POST['submit']) ? 'checked' : ''; ?>
                                           class="form-checkbox h-5 w-5 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                                    <span class="text-gray-700 font-medium"><?php echo __('active_account'); ?></span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 mt-8">
                            <a href="users.php" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                                <?php echo __('cancel'); ?>
                            </a>
                            <button type="submit" name="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i>
                                <?php echo __('create_user_button'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>