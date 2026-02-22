<?php
// Test if settings are working
echo "Testing settings functionality...\n";

// Try to include required files
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
    echo "✓ functions.php loaded\n";
} else {
    echo "✗ functions.php not found\n";
    exit;
}

if (file_exists('config/database.php')) {
    require_once 'config/database.php';
    echo "✓ database.php loaded\n";
} else {
    echo "✗ database.php not found\n";
    exit;
}

// Test getSetting function
try {
    $company_name = getSetting('company_name', 'Default Name');
    echo "✓ Company Name: $company_name\n";
    
    $company_address = getSetting('company_address', 'Default Address');
    echo "✓ Company Address: $company_address\n";
    
    $currency_symbol = getSetting('currency_symbol', 'Default Currency');
    echo "✓ Currency Symbol: $currency_symbol\n";
    
    // Test all company fields
    $company_fields = [
        'company_name', 'company_address', 'company_phone', 'company_email', 
        'company_website', 'company_rc', 'company_ice', 'company_cnss', 
        'company_bank', 'company_logo', 'currency_symbol'
    ];
    
    echo "\n--- All Company Settings ---\n";
    foreach ($company_fields as $field) {
        $value = getSetting($field, 'NOT SET');
        echo "$field: $value\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>
