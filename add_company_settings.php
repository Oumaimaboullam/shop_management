<?php
require_once 'config/database.php';

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
    foreach ($company_settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (key_name, value, type) VALUES (?, ?, 'string')");
        $stmt->execute([$key, $value]);
        
        // If insert failed (duplicate), update existing
        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key_name = ?");
            $stmt->execute([$value, $key]);
        }
    }

    // Update currency symbol to DH for Moroccan currency
    $stmt = $pdo->prepare("UPDATE settings SET value = 'DH' WHERE key_name = 'currency_symbol' AND value = '$'");
    $stmt->execute();

    echo "Company settings added successfully!\n";
    echo "Added " . count($company_settings) . " company information fields.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
