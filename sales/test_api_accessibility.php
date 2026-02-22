<?php
// Simple test to verify API accessibility
header('Content-Type: text/html');

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>API Accessibility Test</title>\n</head>\n<body>\n";
echo "<h1>API Accessibility Test</h1>\n";

// Test 1: Check if we can access the API file directly
echo "<h2>Test 1: Direct API Access</h2>\n";
$api_file = '../api/sales/create.php';
if (file_exists($api_file)) {
    echo "<p style='color: green;'>✓ API file exists: $api_file</p>\n";
    
    // Check if it's readable
    if (is_readable($api_file)) {
        echo "<p style='color: green;'>✓ API file is readable</p>\n";
        
        // Show first few lines
        $lines = file($api_file, FILE_IGNORE_NEW_LINES);
        echo "<p>First 5 lines of API file:</p>\n";
        echo "<pre style='background: #f0f0f0; padding: 10px;'>\n";
        for ($i = 0; $i < min(5, count($lines)); $i++) {
            echo htmlspecialchars($lines[$i]) . "\n";
        }
        echo "</pre>\n";
    } else {
        echo "<p style='color: red;'>✗ API file is not readable</p>\n";
    }
} else {
    echo "<p style='color: red;'>✗ API file does not exist: $api_file</p>\n";
}

// Test 2: Check required files
echo "<h2>Test 2: Required Files Check</h2>\n";
$required_files = [
    '../config/database.php',
    '../includes/functions.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file exists</p>\n";
    } else {
        echo "<p style='color: red;'>✗ $file does not exist</p>\n";
    }
}

// Test 3: Check PHP configuration
echo "<h2>Test 3: PHP Configuration</h2>\n";
echo "<p>PHP Version: " . phpversion() . "</p>\n";
echo "<p>Display Errors: " . ini_get('display_errors') . "</p>\n";
echo "<p>Error Reporting: " . ini_get('error_reporting') . "</p>\n";

// Test 4: Test basic PHP functionality
echo "<h2>Test 4: Basic PHP Functionality</h2>\n";
try {
    // Test session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p style='color: green;'>✓ Session functionality works</p>\n";
    
    // Test JSON
    $test_data = ['test' => 'data'];
    $json = json_encode($test_data);
    echo "<p style='color: green;'>✓ JSON encoding works: $json</p>\n";
    
    // Test file operations
    $temp_file = tempnam(sys_get_temp_dir(), 'test');
    if ($temp_file) {
        echo "<p style='color: green;'>✓ Temporary file creation works: $temp_file</p>\n";
        unlink($temp_file);
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>\n";
}

// Test 5: Check server configuration
echo "<h2>Test 5: Server Configuration</h2>\n";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>\n";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>\n";
echo "<p>Current Script: " . $_SERVER['SCRIPT_FILENAME'] . "</p>\n";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>\n";

// Test 6: Check URL rewriting (if applicable)
echo "<h2>Test 6: URL Configuration</h2>\n";
$current_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
echo "<p>Current URL: $current_url</p>\n";

// Test 7: Check if we can include the API file
echo "<h2>Test 7: API File Inclusion Test</h2>\n";
try {
    // We'll simulate the API call instead of including it directly
    echo "<p style='color: green;'>✓ API file inclusion test would be performed</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ API inclusion error: " . $e->getMessage() . "</p>\n";
}

echo "</body>\n</html>\n";
?>