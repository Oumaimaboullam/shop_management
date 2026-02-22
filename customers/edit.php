<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Customer Details
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
$stmt->execute([':id' => $id]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: list.php');
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $address = sanitize($_POST['address']);

    try {
        $stmt = $pdo->prepare("
            UPDATE clients SET 
                name = :name, phone = :phone, email = :email, address = :address
            WHERE id = :id
        ");
        $stmt->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':email' => $email,
            ':address' => $address,
            ':id' => $id
        ]);
        
        flash('success', __('customer_updated_successfully'));
        header('Location: view.php?id=' . $id);
        exit();
    } catch (PDOException $e) {
        flash('error', __('error_updating_customer') . $e->getMessage());
    }
}

$pageTitle = __('edit_customer');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('edit_customer'); ?></h1>
                    <p class="mt-1 text-gray-600"><?php echo __('update_customer_information', 'Update customer information'); ?></p>
                </div>
                <a href="view.php?id=<?php echo $client['id']; ?>" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                    <i class="fas fa-eye mr-2"></i>
                    <?php echo __('view_details'); ?>
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

        <!-- Edit Customer Form -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-8 py-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-edit text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white"><?php echo __('edit_customer_information'); ?></h2>
                        <p class="text-blue-100 mt-1"><?php echo __('update_customer_details', 'Update customer details'); ?><?php echo htmlspecialchars($client['name']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-8">
                <form method="POST" action="" class="space-y-6">
                    <!-- Customer Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('customer_name_required'); ?></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($client['name']); ?>"
                                   class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                   placeholder="<?php echo __('enter_customer_name', 'Enter customer name'); ?>">
                        </div>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('phone_number_required'); ?></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400"></i>
                            </div>
                            <input type="tel" id="phone" name="phone" required
                                   value="<?php echo htmlspecialchars($client['phone']); ?>"
                                   class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                   placeholder="<?php echo __('enter_phone_number', 'Enter phone number'); ?>">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('email_address_label'); ?></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email"
                                   value="<?php echo htmlspecialchars($client['email']); ?>"
                                   class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                   placeholder="<?php echo __('enter_email_address_optional', 'Enter email address (optional)'); ?>">
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('address_label'); ?></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 pt-3 pointer-events-none">
                                <i class="fas fa-map-marker-alt text-gray-400"></i>
                            </div>
                            <textarea id="address" name="address" rows="4"
                                      class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                      placeholder="<?php echo __('enter_customer_address_optional', 'Enter customer address (optional)'); ?>"><?php echo htmlspecialchars($client['address']); ?></textarea>
                        </div>
                    </div>

                    <!-- Current Information Display -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3"><?php echo __('current_information', 'Current information'); ?></h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500"><?php echo __('customer_since'); ?>:</span>
                                <span class="text-gray-900 ml-2"><?php echo date('F j, Y', strtotime($client['created_at'])); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-500"><?php echo __('customer_id'); ?>:</span>
                                <span class="text-gray-900 ml-2">#<?php echo $client['id']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="view.php?id=<?php echo $client['id']; ?>" 
                           class="px-6 py-3 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            <?php echo __('cancel'); ?>
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-lg font-medium hover:from-blue-700 hover:to-cyan-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo __('save_changes'); ?>
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo __('editing_guidelines', 'Editing Guidelines'); ?></h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• <strong><?php echo __('required_fields_marked', 'Required fields are marked with *'); ?></strong></li>
                        <li>• <?php echo __('phone_required_contact', 'Phone is required for contact purposes'); ?></li>
                        <li>• <?php echo __('email_optional_recommended', 'Email is optional but recommended'); ?></li>
                        <li>• <?php echo __('address_helps_delivery', 'Address helps with delivery'); ?></li>
                        <li>• <?php echo __('changes_saved_immediately', 'Changes are saved immediately'); ?></li>
                        <li>• <?php echo __('customer_info_sales_invoicing', 'Customer information is used for sales and invoicing'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>