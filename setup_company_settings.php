<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Company settings to add
        $company_settings = [
            'company_name' => 'Société Maroc',
            'company_address' => '123 Rue Business, Casablanca, Maroc',
            'company_phone' => '+212 5XX XXX XXX',
            'company_email' => 'info@societe.ma',
            'company_website' => 'www.societe.ma',
            'company_rc' => 'RC: 123456',
            'company_ice' => 'ICE: 00123456789',
            'company_cnss' => 'CNSS: 1234567',
            'company_bank' => 'Banque: BMCE - RIB: 007 780 0001234567800001 18',
            'company_logo' => ''
        ];

        // Insert or update each setting
        $added_count = 0;
        foreach ($company_settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO settings (key_name, value, type) VALUES (?, ?, 'string')");
            $stmt->execute([$key, $value]);
            
            // If insert failed (duplicate), update existing
            if ($stmt->rowCount() === 0) {
                $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key_name = ?");
                $stmt->execute([$value, $key]);
            }
            $added_count++;
        }

        // Update currency symbol to DH for Moroccan currency
        $stmt = $pdo->prepare("UPDATE settings SET value = 'DH' WHERE key_name = 'currency_symbol' AND value = '$'");
        $stmt->execute();

        $message = "Company settings added successfully! Added $added_count company information fields.";
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Check current settings
$current_settings = [];
$company_fields = [
    'company_name', 'company_address', 'company_phone', 'company_email', 
    'company_website', 'company_rc', 'company_ice', 'company_cnss', 
    'company_bank', 'company_logo', 'currency_symbol'
];

foreach ($company_fields as $field) {
    $current_settings[$field] = getSetting($field, 'NOT SET');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Company Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Company Settings Setup</h1>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Current Settings Status</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($current_settings as $key => $value): ?>
                    <div class="flex justify-between items-center p-3 border rounded <?php echo $value === 'NOT SET' ? 'border-red-300 bg-red-50' : 'border-green-300 bg-green-50'; ?>">
                        <span class="font-medium text-gray-700"><?php echo htmlspecialchars($key); ?></span>
                        <span class="<?php echo $value === 'NOT SET' ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo htmlspecialchars($value); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST" class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Add Missing Settings</h2>
            <p class="text-gray-600 mb-6">Click the button below to add all required company information settings for the Moroccan invoice system.</p>
            
            <button type="submit" 
                    class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Add Company Settings
            </button>
        </form>

        <div class="bg-blue-50 rounded-lg p-6 mt-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">Next Steps</h3>
            <ol class="list-decimal list-inside text-blue-800 space-y-2">
                <li>Click "Add Company Settings" button above</li>
                <li>Go to <a href="settings/system.php" class="text-blue-600 hover:underline">Settings → System</a> to configure your actual company information</li>
                <li>Test the invoice to ensure all company details appear correctly</li>
            </ol>
        </div>
    </div>
</body>
</html>
