<?php
// Simple debug script to test the API directly
header('Content-Type: text/html');

// Start session and set up user
session_start();
$_SESSION['user'] = ['id' => 1, 'name' => 'Test User', 'role' => 'admin'];
$_SESSION['user_id'] = 1;

// Change to API directory
chdir('../api/sales');

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>API Debug</title>\n</head>\n<body>\n";
echo "<h1>API Debug Test</h1>\n";
echo "<p>Current directory: " . getcwd() . "</p>\n";

// Set up the request environment
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Test data
$test_data = [
    'items' => [
        ['id' => 3, 'name' => 'Test Product', 'price' => 17.60, 'quantity' => 1]
    ],
    'payment_mode_id' => 1,
    'document_type' => 'sale'
];

// Mock the raw input stream
$raw_input = json_encode($test_data);

// Override file_get_contents for this test is disabled.
// function file_get_contents($filename) {
//     global $raw_input;
//     if ($filename === 'php://input') {
//         return $raw_input;
//     }
//     return \file_get_contents($filename);
// }

echo "<h2>Test Data:</h2>\n";
echo "<pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>\n";

echo "<h2>Testing API...</h2>\n";

// Capture output
ob_start();

try {
    require_once 'create.php';
} catch (Exception $e) {
    echo "<div style='color: red;'>Exception: " . $e->getMessage() . "</div>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

$output = ob_get_clean();

echo "<h2>API Response:</h2>\n";
echo "<pre>" . htmlspecialchars($output) . "</pre>\n";

// Parse the response
$response = json_decode($output, true);
if ($response) {
    echo "<h2>Parsed Response:</h2>\n";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>\n";
    
    if (isset($response['success']) && $response['success']) {
        echo "<div style='color: green; font-weight: bold;'>✓ SUCCESS! Sale ID: " . $response['sale_id'] . "</div>\n";
    } else {
        echo "<div style='color: red; font-weight: bold;'>✗ FAILED: " . ($response['message'] ?? 'Unknown error') . "</div>\n";
    }
} else {
    echo "<div style='color: red; font-weight: bold;'>✗ Failed to parse JSON response</div>\n";
}

echo "</body>\n</html>\n";
?>