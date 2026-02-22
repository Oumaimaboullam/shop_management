<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

requireLogin();

// Create uploads directory if it doesn't exist
$uploads_dir = '../uploads';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $company_name = sanitize($_POST['company_name'] ?? '');
        $company_phone = sanitize($_POST['company_phone'] ?? '');
        $company_address = sanitize($_POST['company_address'] ?? '');
        $company_email = sanitize($_POST['company_email'] ?? '');
        $company_website = sanitize($_POST['company_website'] ?? '');
        $company_rc = sanitize($_POST['company_rc'] ?? '');
        $company_ice = sanitize($_POST['company_ice'] ?? '');
        $company_cnss = sanitize($_POST['company_cnss'] ?? '');
        $company_bank = sanitize($_POST['company_bank'] ?? '');

        // Handle logo upload
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $_FILES['company_logo']['tmp_name']);
            finfo_close($file_info);

            if (in_array($mime_type, $allowed_types)) {
                $extension = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
                $logo_filename = 'company_logo_' . time() . '.' . $extension;
                $upload_path = $uploads_dir . '/' . $logo_filename;

                if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $upload_path)) {
                    // Delete old logo if exists
                    $old_logo = getSetting('company_logo');
                    if ($old_logo && file_exists($uploads_dir . '/' . $old_logo)) {
                        unlink($uploads_dir . '/' . $old_logo);
                    }
                    
                    // Save new logo filename to settings
                    $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
                    $stmt->execute(['company_logo', $logo_filename, $logo_filename]);
                }
            } else {
                flash('error', __('invalid_file_type') . ' JPG, PNG, GIF, and WebP ' . __('images_allowed'));
            }
        }

        // Update company settings only
        $settings = [
            'company_name' => $company_name,
            'company_phone' => $company_phone,
            'company_address' => $company_address,
            'company_email' => $company_email,
            'company_website' => $company_website,
            'company_rc' => $company_rc,
            'company_ice' => $company_ice,
            'company_cnss' => $company_cnss,
            'company_bank' => $company_bank
        ];

        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        flash('success', __('settings_updated_successfully'));
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        flash('error', __('error_updating_settings') . $e->getMessage());
    }
}

// Get current settings
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['value'];
}

$pageTitle = __('settings');
require_once '../includes/header.php';
?>

<!-- Modern Tailwind Settings Page -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo __('settings'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo __('configure_shop_preferences'); ?></p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <nav class="flex border-b border-gray-200">
            <a href="index.php" class="px-6 py-4 text-sm font-medium text-primary-600 border-b-2 border-primary-600 bg-primary-50">
                <i class="fas fa-store mr-2"></i>
                <?php echo __('shop_info'); ?>
            </a>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <a href="users.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200">
                    <i class="fas fa-users mr-2"></i>
                    <?php echo __('users_management'); ?>
                </a>
            <?php endif; ?>
            <a href="profile.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200">
                <i class="fas fa-user mr-2"></i>
                <?php echo __('profile'); ?>
            </a>
            <a href="password.php" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors duration-200">
                <i class="fas fa-lock mr-2"></i>
                <?php echo __('change_password'); ?>
            </a>
        </nav>

        <!-- Settings Content -->
        <div class="p-6">
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

            <form method="POST" enctype="multipart/form-data" class="max-w-4xl">
                <!-- Shop Information -->
                <div class="space-y-8">
                    <!-- Basic Information -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-store mr-2 text-primary-600"></i>
                            <?php echo __('basic_information'); ?>
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('shop_name'); ?> *</label>
                                <input type="text" id="company_name" name="company_name" 
                                       value="<?php echo htmlspecialchars($settings['company_name'] ?? 'My Shop'); ?>" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('phone'); ?></label>
                                <input type="tel" id="company_phone" name="company_phone" 
                                       value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <label for="company_email" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('email'); ?></label>
                                <input type="email" id="company_email" name="company_email" 
                                       value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <label for="company_website" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('website'); ?></label>
                                <input type="url" id="company_website" name="company_website" 
                                       value="<?php echo htmlspecialchars($settings['company_website'] ?? ''); ?>"
                                       placeholder="www.example.com"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div class="md:col-span-2">
                                <label for="company_address" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('address'); ?></label>
                                <textarea id="company_address" name="company_address" rows="3" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"><?php echo htmlspecialchars($settings['company_address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Company Logo -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-image mr-2 text-primary-600"></i>
                            <?php echo __('company_logo'); ?>
                        </h2>
                        
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors duration-200">
                            <?php if (isset($settings['company_logo']) && $settings['company_logo']): ?>
                                <div class="mb-4">
                                    <img src="../uploads/<?php echo htmlspecialchars($settings['company_logo']); ?>" alt="Company Logo" class="w-32 h-16 object-contain mx-auto rounded-lg border border-gray-200">
                                    <p class="text-sm text-gray-600 mt-2"><?php echo __('current_logo'); ?></p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="company_logo" name="company_logo" accept="image/*" 
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            <p class="text-xs text-gray-500 mt-2"><?php echo __('allowed_formats'); ?></p>
                        </div>
                    </div>

                    <!-- Legal Information -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-certificate mr-2 text-primary-600"></i>
                            <?php echo __('legal_info'); ?>
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="company_rc" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('rc_number'); ?></label>
                                <input type="text" id="company_rc" name="company_rc" 
                                       value="<?php echo htmlspecialchars($settings['company_rc'] ?? ''); ?>"
                                       placeholder="RC: 123456"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <label for="company_ice" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('ice_number'); ?></label>
                                <input type="text" id="company_ice" name="company_ice" 
                                       value="<?php echo htmlspecialchars($settings['company_ice'] ?? ''); ?>"
                                       placeholder="ICE: 00123456789"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>

                            <div>
                                <label for="company_cnss" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('cnss_number'); ?></label>
                                <input type="text" id="company_cnss" name="company_cnss" 
                                       value="<?php echo htmlspecialchars($settings['company_cnss'] ?? ''); ?>"
                                       placeholder="CNSS: 1234567"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200">
                            </div>
                        </div>
                    </div>

                    <!-- Banking Information -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-university mr-2 text-primary-600"></i>
                            <?php echo __('banking_info'); ?>
                        </h2>
                        
                        <div>
                            <label for="company_bank" class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('bank_account'); ?></label>
                            <textarea id="company_bank" name="company_bank" rows="2" 
                                      placeholder="Banque: BMCE - RIB: 007 780 0001234567800001 18"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200"><?php echo htmlspecialchars($settings['company_bank'] ?? ''); ?></textarea>
                            <p class="text-xs text-gray-500 mt-1"><?php echo __('bank_details'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-4 pt-8 border-t border-gray-200 mt-8">
                    <button type="reset" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-undo mr-2"></i>
                        <?php echo __('reset'); ?>
                    </button>
                    <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo __('save_settings'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modern JavaScript Enhancements -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to buttons
    const submitButton = document.querySelector('button[type="submit"]');
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function() {
        submitButton.classList.add('loading');
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><?php echo __('saving'); ?>';
        submitButton.disabled = true;
    });
    
    // Add preview functionality for logo upload
    const logoInput = document.getElementById('company_logo');
    logoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.querySelector('.logo-upload img');
                if (preview) {
                    preview.src = e.target.result;
                } else {
                    // Create preview if it doesn't exist
                    const uploadDiv = document.querySelector('.logo-upload');
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'mb-4';
                    previewDiv.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" class="w-32 h-16 object-contain mx-auto rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-600 mt-2"><?php echo __('new_logo_preview'); ?></p>
                    `;
                    uploadDiv.insertBefore(previewDiv, uploadDiv.firstChild);
                }
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Add confirmation for reset button
    const resetButton = document.querySelector('button[type="reset"]');
    resetButton.addEventListener('click', function(e) {
        if (!confirm(t('confirm_reset_changes'))) {
            e.preventDefault();
        }
    });
});
</script>

<!-- Additional Modern Styling -->
<style>
/* Enhanced form styling */
input:focus, textarea:focus {
    transform: scale(1.02);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Loading states */
.loading {
    position: relative;
    overflow: hidden;
}

.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { left: -100%; }
    100% { left: 100%; }
}

/* Enhanced file input styling */
input[type="file"] {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.5rem;
    transition: all 0.2s ease;
}

input[type="file"]:hover {
    border-color: #3b82f6;
}

/* Navigation tabs enhancement */
nav a {
    transition: all 0.2s ease;
}

nav a:hover {
    transform: translateY(-1px);
}

/* Form group enhancements */
.form-group {
    transition: all 0.2s ease;
}

.form-group:hover {
    transform: translateY(-1px);
}
</style>

<?php require_once '../includes/footer.php'; ?>