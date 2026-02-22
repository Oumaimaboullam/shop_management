<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'];
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = __('current_password_required_error');
        }
        
        if (empty($new_password)) {
            $errors[] = __('new_password_required_error');
        } elseif (strlen($new_password) < 6) {
            $errors[] = __('new_password_min_chars_error');
        } elseif ($new_password !== $confirm_password) {
            $errors[] = __('new_passwords_do_not_match_error');
        }

        if (empty($errors)) {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($current_password, $user['password_hash'])) {
                // Update password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$new_password_hash, $user_id]);

                flash('success', __('password_changed_successfully'));
                header('Location: profile.php');
                exit();
            } else {
                $errors[] = __('current_password_incorrect_error');
            }
        }

    } catch (Exception $e) {
        $errors[] = __('error_changing_password') . $e->getMessage();
    }
}

$pageTitle = __('change_password_title');
require_once '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('settings'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('secure_account_description'); ?></p>
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
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <a href="users.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200 whitespace-nowrap">
                    <i class="fas fa-users mr-2"></i>
                    <?php echo __('users_management'); ?>
                </a>
            <?php endif; ?>
            <a href="profile.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200 whitespace-nowrap">
                <i class="fas fa-user mr-2"></i>
                <?php echo __('profile'); ?>
            </a>
            <a href="password.php" class="px-6 py-4 text-sm font-medium text-primary-600 border-b-2 border-primary-600 bg-primary-50 whitespace-nowrap">
                <i class="fas fa-lock mr-2"></i>
                <?php echo __('change_password'); ?>
            </a>
        </nav>

        <!-- Password Content -->
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
                    <div class="text-center mb-8">
                        <div class="h-16 w-16 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-key text-2xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900"><?php echo __('update_password_heading'); ?></h2>
                        <p class="text-gray-500 mt-1"><?php echo __('update_password_description'); ?></p>
                    </div>

                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('current_password_label'); ?> *</label>
                            <input type="password" id="current_password" name="current_password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('new_password_label'); ?> *</label>
                            <input type="password" id="new_password" name="new_password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            <p class="mt-1 text-xs text-gray-500"><?php echo __('must_be_at_least_6_characters'); ?></p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('confirm_new_password_label'); ?> *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                        </div>

                        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 mt-8">
                            <a href="profile.php" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                                <?php echo __('cancel'); ?>
                            </a>
                            <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-check mr-2"></i>
                                <?php echo __('change_password_button'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>