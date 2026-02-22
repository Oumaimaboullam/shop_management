<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
$pageTitle = 'System Settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                if ($current_logo && file_exists('../uploads/' . $current_logo)): 
                ?>
                    <div class="mb-4">
                        <img src="../uploads/<?php echo htmlspecialchars($current_logo); ?>" 
                             alt="Company Logo" 
                             class="h-20 w-auto border border-gray-300 rounded">
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500 mb-4">No logo uploaded</p>
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