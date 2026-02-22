<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Get current user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, name, username, role, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ../dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = sanitize($_POST['name'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $language = sanitize($_POST['language'] ?? '');

        // Validation
        $errors = [];
        
        if (empty($name)) {
            $errors[] = __('name_required');
        }
        
        if (empty($username)) {
            $errors[] = __('username_required');
        } elseif (strlen($username) < 3) {
            $errors[] = __('username_min_chars');
        } else {
            // Check if username already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = __('username_exists');
            }
        }

        if (empty($errors)) {
            // Update user profile
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
            $stmt->execute([$name, $username, $user_id]);

            // Update session data
            $_SESSION['user_name'] = $name;
            $_SESSION['username'] = $username;

            // Update language if changed
            if (!empty($language) && in_array($language, ['en', 'fr'])) {
                $_SESSION['lang'] = $language;
            }

            flash('success', __('profile_updated_successfully'));
            header('Location: profile.php');
            exit();
        }

    } catch (Exception $e) {
        $errors[] = __('error_updating_profile') . $e->getMessage();
    }
}

$pageTitle = __('profile');
require_once '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('settings'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('manage_personal_information'); ?></p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <nav class="flex border-b border-gray-200 overflow-x-auto">
            <a href="index.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200 whitespace-nowrap">
                <i class="fas fa-store mr-2"></i>
                <?php echo __('shop_information'); ?>
            </a>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <a href="users.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200 whitespace-nowrap">
                    <i class="fas fa-users mr-2"></i>
                    <?php echo __('users_management'); ?>
                </a>
            <?php endif; ?>
            <a href="profile.php" class="px-6 py-4 text-sm font-medium text-primary-600 border-b-2 border-primary-600 bg-primary-50 whitespace-nowrap">
                <i class="fas fa-user mr-2"></i>
                <?php echo __('profile'); ?>
            </a>
            <a href="password.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200 whitespace-nowrap">
                <i class="fas fa-lock mr-2"></i>
                <?php echo __('change_password'); ?>
            </a>
        </nav>

        <!-- Profile Content -->
        <div class="p-6">
            <div class="max-w-4xl">
                <?php if ($msg = flash('success')): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="text-green-800"><?php echo $msg; ?></span>
                        </div>
                    </div>
                <?php endif; ?>

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

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Profile Info Card -->
                    <div class="lg:col-span-1">
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <div class="text-center mb-6">
                                <div class="h-24 w-24 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-3xl font-bold mx-auto mb-4">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo ucfirst($user['role']); ?></p>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-500"><?php echo __('user_id'); ?></span>
                                    <span class="text-sm font-medium text-gray-900">#<?php echo htmlspecialchars($user['id']); ?></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-500"><?php echo __('role'); ?></span>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                            ($user['role'] === 'manager' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-500"><?php echo __('member_since'); ?></span>
                                    <span class="text-sm font-medium text-gray-900"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <div class="lg:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6"><?php echo __('edit_profile_information'); ?></h3>
                        <form method="POST" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('full_name'); ?> *</label>
                                <input type="text" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? $user['name']); ?>" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('username'); ?> *</label>
                                <input type="text" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? $user['username']); ?>" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('languages'); ?></label>
                                <select name="language" id="language" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                                    <option value="en" <?php echo ($_POST['language'] ?? $_SESSION['lang'] ?? 'en') == 'en' ? 'selected' : ''; ?>><?php echo __('english'); ?></option>
                                    <option value="fr" <?php echo ($_POST['language'] ?? $_SESSION['lang'] ?? 'en') == 'fr' ? 'selected' : ''; ?>><?php echo __('french'); ?></option>
                                </select>
                            </div>
                            
                            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                                <a href="../dashboard.php" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                                    <?php echo __('cancel'); ?>
                                </a>
                                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-save mr-2"></i>
                                    <?php echo __('update_profile'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>