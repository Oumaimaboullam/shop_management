<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $address = sanitize($_POST['address']);

    try {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone, email, address, created_at) VALUES (:name, :phone, :email, :address, NOW())");
        $stmt->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':email' => $email,
            ':address' => $address
        ]);
        
        flash('success', __('supplier_added_successfully', 'Supplier added successfully!'));
        header('Location: list.php');
        exit();
    } catch (PDOException $e) {
        flash('error', __('error_adding_supplier', 'Error adding supplier: ') . $e->getMessage());
    }
}

$pageTitle = __('add_new_supplier', 'Add New Supplier');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('create_new_supplier', 'Create New Supplier'); ?></h1>
                    <p class="mt-1 text-gray-600"><?php echo __('fill_supplier_details_below', 'Fill supplier details below'); ?></p>
                </div>
                <a href="list.php" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <?php echo __('back_to_list'); ?>
                </a>
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

        <!-- Add Supplier Form -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-8 py-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white"><?php echo __('supplier_information', 'Supplier Information'); ?></h2>
                        <p class="text-purple-100 mt-1"><?php echo __('fill_supplier_details_below', 'Fill supplier details below'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-8">
                <form method="POST" action="" class="space-y-6">
                    <!-- Supplier Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('supplier_name_required', 'Supplier Name *'); ?></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" id="name" name="name" required
                                   class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors duration-200"
                                   placeholder="<?php echo __('enter_supplier_name', 'Enter supplier name'); ?>">
                        </div>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('supplier_phone_required', 'Supplier Phone *'); ?></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400"></i>
                            </div>
                            <input type="tel" id="phone" name="phone" required
                                   class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors duration-200"
                                   placeholder="<?php echo __('enter_supplier_phone', 'Enter supplier phone'); ?>">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('supplier_email_label', 'Supplier Email'); ?></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email"
                                   class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors duration-200"
                                   placeholder="<?php echo __('enter_supplier_email_optional', 'Enter supplier email (optional)'); ?>">
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('supplier_address_label', 'Supplier Address'); ?></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 pt-3 pointer-events-none">
                                <i class="fas fa-map-marker-alt text-gray-400"></i>
                            </div>
                            <textarea id="address" name="address" rows="4"
                                      class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors duration-200"
                                      placeholder="<?php echo __('enter_supplier_address_optional', 'Enter supplier address (optional)'); ?>"></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="list.php" 
                           class="px-6 py-3 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            <?php echo __('cancel'); ?>
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg font-medium hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo __('save_supplier'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Additional Information Card -->
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-start space-x-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo __('important_information', 'Important Information'); ?></h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• <strong><?php echo __('required_fields_marked', 'Required fields are marked with *'); ?></strong></li>
                        <li>• <?php echo __('phone_required_contact', 'Phone is required for contact purposes'); ?></li>
                        <li>• <?php echo __('email_optional_recommended', 'Email is optional but recommended'); ?></li>
                        <li>• <?php echo __('address_helps_delivery', 'Address helps with delivery'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>