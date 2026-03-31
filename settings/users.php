<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Don't allow deleting the current logged-in user
    if ($user_id != $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            flash('success', 'User deleted successfully!');
        } catch (Exception $e) {
            flash('error', 'Error deleting user: ' . $e->getMessage());
        }
    } else {
        flash('error', 'You cannot delete your own account!');
    }
    header('Location: users.php');
    exit();
}

// Get all users
$stmt = $pdo->query("SELECT id, name, username, role, is_active, created_at FROM users ORDER BY name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = __('users_management');
require_once '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('settings'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('manage_system_users_and_permissions'); ?></p>
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

        <!-- Users Content -->
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-gray-900"><?php echo __('system_users'); ?></h2>
                <a href="add_user.php" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    <?php echo __('add_new_user'); ?>
                </a>
            </div>

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

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('name_header'); ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('username_header'); ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('role_header'); ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('status_header_users'); ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('created_header'); ?></th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('actions_header_users'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold mr-3">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                            ($user['role'] === 'manager' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $user['is_active'] ? __('active_status') : __('inactive_status'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="text-primary-600 hover:text-primary-900 p-1 hover:bg-primary-50 rounded transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                               onclick="return confirm(t('confirm_delete_user'))" 
                                               class="text-red-600 hover:text-red-900 p-1 hover:bg-red-50 rounded transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>