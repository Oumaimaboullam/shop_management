<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Test translation function
echo "Current language: " . ($_SESSION['lang'] ?? 'en') . "\n";
echo "Translation test - sale_normal: " . __('sale_normal') . "\n";
echo "Translation test - invoice: " . __('invoice') . "\n";
echo "Translation test - quote: " . __('quote') . "\n";

// Test if French translations work
$_SESSION['lang'] = 'fr';
$lang_file = __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    $lang = require $lang_file;
    echo "\nSwitched to French:\n";
    echo "Translation test - sale_normal: " . __('sale_normal') . "\n";
    echo "Translation test - invoice: " . __('invoice') . "\n";
    echo "Translation test - quote: " . __('quote') . "\n";
}
?>
