<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin']);
$pageTitle = 'System Settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle logo deletion
    if (isset($_POST['action']) && $_POST['action'] === 'delete_logo') {
        header('Content-Type: application/json');
        
        try {
            // Get current logo
            $current_logo = getSetting('company_logo', '');
            $logo_path = 'uploads/' . $current_logo;
            
            if ($current_logo && file_exists($logo_path)) {
                // Delete physical file
                unlink($logo_path);
            }
            
            // Remove from database
            $stmt = $pdo->prepare("DELETE FROM settings WHERE key_name = 'company_logo'");
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Logo deleted successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // Handle logo file deletion
    if (isset($_POST['action']) && $_POST['action'] === 'delete_logo_file') {
        header('Content-Type: application/json');
        
        try {
            $filename = $_POST['filename'] ?? '';
            $logo_path = 'uploads/' . $filename;
            
            if (file_exists($logo_path)) {
                // Delete physical file
                unlink($logo_path);
                echo json_encode(['success' => true, 'message' => 'Logo file deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'File not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // Handle regular form submission
    $company_fields = [
        'company_name' => $_POST['company_name'] ?? '',
        'company_address' => $_POST['company_address'] ?? '',
        'company_phone' => $_POST['company_phone'] ?? '',
        'company_email' => $_POST['company_email'] ?? '',
        'company_website' => $_POST['company_website'] ?? '',
        'company_rc' => $_POST['company_rc'] ?? '',
        'company_ice' => $_POST['company_ice'] ?? '',
        'company_cnss' => $_POST['company_cnss'] ?? '',
        'company_bank' => $_POST['company_bank'] ?? '',
        'currency_symbol' => $_POST['currency_symbol'] ?? 'DH'
    ];

    foreach ($company_fields as $key => $value) {
        try {
            // Check if setting exists
            $stmt = $pdo->prepare("SELECT id FROM settings WHERE key_name = ?");
            $stmt->execute([$key]);
            
            if ($stmt->fetch()) {
                // Update existing setting
                $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key_name = ?");
                $stmt->execute([$value, $key]);
            } else {
                // Insert new setting
                $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
        } catch (Exception $e) {
            $error = "Error saving setting: " . $e->getMessage();
        }
    }
    
    if (!isset($error)) {
        $success = "Settings saved successfully!";
    }
}

require_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
    </div>

    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <!-- Shop Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Shop Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Company Name *
                    </label>
                    <input type="text" 
                           name="company_name" 
                           value="<?php echo htmlspecialchars(getSetting('company_name', 'Société Maroc')); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Currency Symbol *
                    </label>
                    <input type="text" 
                           name="currency_symbol" 
                           value="<?php echo htmlspecialchars(getSetting('currency_symbol', 'DH')); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Company Address *
                    </label>
                    <textarea name="company_address" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                              required><?php echo htmlspecialchars(getSetting('company_address', '123 Rue Business, Casablanca, Maroc')); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number *
                    </label>
                    <input type="tel" 
                           name="company_phone" 
                           value="<?php echo htmlspecialchars(getSetting('company_phone', '+212 5XX XXX XXX')); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address *
                    </label>
                    <input type="email" 
                           name="company_email" 
                           value="<?php echo htmlspecialchars(getSetting('company_email', 'info@societe.ma')); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Website
                    </label>
                    <input type="url" 
                           name="company_website" 
                           value="<?php echo htmlspecialchars(getSetting('company_website', 'www.societe.ma')); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Legal Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Legal Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        RC (Registre de Commerce)
                    </label>
                    <input type="text" 
                           name="company_rc" 
                           value="<?php echo htmlspecialchars(getSetting('company_rc', 'RC: 123456')); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ICE (Identifiant Commun de l'Entreprise)
                    </label>
                    <input type="text" 
                           name="company_ice" 
                           value="<?php echo htmlspecialchars(getSetting('company_ice', 'ICE: 00123456789')); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        CNSS (Caisse Nationale de Sécurité Sociale)
                    </label>
                    <input type="text" 
                           name="company_cnss" 
                           value="<?php echo htmlspecialchars(getSetting('company_cnss', 'CNSS: 1234567')); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Banking Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Banking Information</h2>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Bank Details
                </label>
                <textarea name="company_bank" 
                          rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Banque: BMCE - RIB: 007 780 0001234567800001 18"><?php echo htmlspecialchars(getSetting('company_bank', 'Banque: BMCE - RIB: 007 780 0001234567800001 18')); ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Enter bank name and RIB/IBAN details</p>
            </div>
        </div>

        <!-- Company Logo -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Company Logo</h2>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Current Logo
                </label>
                <?php 
                $current_logo = getSetting('company_logo', '');
                $logo_path = 'uploads/' . $current_logo;
                
                // DEBUG: Afficher les informations
                echo "<!-- DEBUG: current_logo = '" . htmlspecialchars($current_logo) . "' -->";
                echo "<!-- DEBUG: logo_path = '" . htmlspecialchars($logo_path) . "' -->";
                echo "<!-- DEBUG: file_exists(logo_path) = " . (file_exists($logo_path) ? 'true' : 'false') . " -->";
                
                // Check for logo in database
                if ($current_logo && file_exists($logo_path)): 
                ?>
                    <div class="mb-4">
                        <p class="text-sm text-green-600 mb-2">Logo found: <?php echo htmlspecialchars($current_logo); ?></p>
                        <div class="flex items-center space-x-4">
                            <img src="<?php echo htmlspecialchars($logo_path); ?>" 
                                 alt="Company Logo" 
                                 class="h-20 w-auto border border-gray-300 rounded">
                            <button type="button" 
                                    onclick="deleteLogo()" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-trash mr-2"></i>
                                Delete Logo
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500 mb-4">No logo uploaded</p>
                    
                    <?php 
                    // DEBUG: Vérifier le dossier uploads
                    $uploads_dir = 'uploads/';
                    echo "<!-- DEBUG: uploads_dir = '" . htmlspecialchars($uploads_dir) . "' -->";
                    echo "<!-- DEBUG: is_dir(uploads_dir) = " . (is_dir($uploads_dir) ? 'true' : 'false') . " -->";
                    
                    if (is_dir($uploads_dir)) {
                        // Lister tous les fichiers dans uploads
                        $all_files = scandir($uploads_dir);
                        echo "<!-- DEBUG: files in uploads = " . implode(', ', $all_files) . " -->";
                        
                        // Rechercher les fichiers logo
                        $logo_files = glob($uploads_dir . 'company_logo_*');
                        echo "<!-- DEBUG: logo_files found = " . implode(', ', $logo_files) . " -->";
                        
                        if (!empty($logo_files)): 
                        ?>
                            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                <p class="text-sm text-yellow-800 mb-2">Found logo file(s) not registered in database:</p>
                                <?php foreach ($logo_files as $logo_file): ?>
                                    <div class="flex items-center space-x-4 mb-2">
                                        <img src="<?php echo htmlspecialchars($logo_file); ?>" 
                                             alt="Company Logo" 
                                             class="h-16 w-auto border border-gray-300 rounded">
                                        <button type="button" 
                                                onclick="deleteLogoFile('<?php echo basename($logo_file); ?>')" 
                                                class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition-colors">
                                            <i class="fas fa-trash mr-1"></i>
                                            Delete
                                        </button>
                                        <span class="text-xs text-gray-600"><?php echo basename($logo_file); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-xs text-red-400">No logo files found in uploads directory</p>
                        <?php endif;
                    } else {
                        echo "<!-- DEBUG: uploads directory does not exist -->";
                    }
                    ?>
                <?php endif; ?>
                
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Upload New Logo
                </label>
                <input type="file" 
                       name="company_logo" 
                       accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Recommended: PNG or JPG, max 2MB</p>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" 
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Save Settings
            </button>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
function deleteLogo() {
    if (confirm('Are you sure you want to delete the current logo? This action cannot be undone.')) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'delete_logo');
        
        // Send AJAX request
        fetch('system.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4';
                successDiv.textContent = 'Logo deleted successfully!';
                
                // Insert success message
                const form = document.querySelector('form');
                form.insertBefore(successDiv, form.firstChild);
                
                // Remove success message after 3 seconds
                setTimeout(() => {
                    successDiv.remove();
                }, 3000);
                
                // Reload page to update UI
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Error deleting logo: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting logo. Please try again.');
        });
    }
}

function deleteLogoFile(filename) {
    if (confirm('Are you sure you want to delete this logo file? This action cannot be undone.')) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'delete_logo_file');
        formData.append('filename', filename);
        
        // Send AJAX request
        fetch('system.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4';
                successDiv.textContent = 'Logo file deleted successfully!';
                
                // Insert success message
                const form = document.querySelector('form');
                form.insertBefore(successDiv, form.firstChild);
                
                // Remove success message after 3 seconds
                setTimeout(() => {
                    successDiv.remove();
                }, 3000);
                
                // Reload page to update UI
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Error deleting logo file: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting logo file. Please try again.');
        });
    }
}
</script>
