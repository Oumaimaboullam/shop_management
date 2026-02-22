<?php
// Ultimate network error debug script
header('Content-Type: text/html');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session for API testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mock login for testing
$_SESSION['user'] = ['id' => 1, 'name' => 'Test User', 'role' => 'admin'];
$_SESSION['user_id'] = 1;

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Ultimate Network Error Debug</title>\n<style>\n";
echo "body { font-family: 'Courier New', monospace; margin: 20px; background: #1a1a1a; color: #00ff00; }\n";
echo ".section { margin: 20px 0; padding: 15px; border: 1px solid #333; border-radius: 5px; background: #2a2a2a; }\n";
echo ".error { color: #ff4444; }\n";
echo ".success { color: #44ff44; }\n";
echo ".info { color: #44aaff; }\n";
echo ".warning { color: #ffaa44; }\n";
echo "button { padding: 10px 15px; margin: 5px; cursor: pointer; background: #444; color: white; border: 1px solid #666; border-radius: 3px; }\n";
echo "button:hover { background: #555; }\n";
echo "pre { background: #000; padding: 10px; border-radius: 3px; overflow-x: auto; color: #00ff00; }\n";
echo "code { background: #333; padding: 2px 4px; border-radius: 2px; }\n";
echo "</style>\n</head>\n<body>\n";

echo "<h1>🔧 Ultimate Network Error Debug Tool</h1>\n";
echo "<p>This tool will help you identify exactly what's causing the network error.</p>\n";

// Section 1: Server Environment
echo "<div class='section'>\n";
echo "<h2>🏗️ Server Environment</h2>\n";

echo "<h3>PHP Configuration:</h3>\n";
echo "<pre>\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";
echo "Error Reporting: " . ini_get('error_reporting') . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "</pre>\n";

echo "<h3>Server Information:</h3>\n";
echo "<pre>\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current Script: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "\n";
echo "</pre>\n";

echo "</div>\n";

// Section 2: File System Check
echo "<div class='section'>\n";
echo "<h2>📁 File System Check</h2>\n";

$files_to_check = [
    '../config/database.php',
    '../includes/functions.php',
    '../api/sales/create.php',
    '../api/auth/check_session.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $file exists</p>\n";
        if (is_readable($file)) {
            echo "<p class='success'>✅ $file is readable</p>\n";
        } else {
            echo "<p class='error'>❌ $file is not readable</p>\n";
        }
    } else {
        echo "<p class='error'>❌ $file does not exist</p>\n";
    }
}

echo "</div>\n";

// Section 3: Database Connection Test
echo "<div class='section'>\n";
echo "<h2>🗄️ Database Connection Test</h2>\n";

try {
    require_once '../config/database.php';
    echo "<p class='success'>✅ Database configuration loaded successfully</p>\n";
    
    // Test connection
    $stmt = $pdo->query("SELECT 1");
    echo "<p class='success'>✅ Database connection is working</p>\n";
    
    // Test tables
    $tables = ['users', 'sales', 'articles', 'stock', 'payment_modes'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p class='success'>✅ Table '$table' exists ($count records)</p>\n";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Table '$table' error: " . $e->getMessage() . "</p>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>\n";
}

echo "</div>\n";

// Section 4: Session Test
echo "<div class='section'>\n";
echo "<h2>🔐 Session Test</h2>\n";

echo "<p>Session Data:</p>\n";
echo "<pre>" . json_encode($_SESSION, JSON_PRETTY_PRINT) . "</pre>\n";

// Test login function
require_once '../includes/functions.php';
echo "<p>isLoggedIn(): " . (isLoggedIn() ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "</p>\n";

echo "</div>\n";

// Section 5: API Test
echo "<div class='section'>\n";
echo "<h2>🔌 API Test</h2>\n";

// Change to API directory
chdir('../api/sales');

// Set up test environment
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

$raw_input = json_encode($test_data);

// Override file_get_contents for this test
function file_get_contents($filename) {
    global $raw_input;
    if ($filename === 'php://input') {
        return $raw_input;
    }
    return \file_get_contents($filename);
}

echo "<p>Testing API with data:</p>\n";
echo "<pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>\n";

// Capture output
ob_start();

try {
    require_once 'create.php';
} catch (Exception $e) {
    echo "<p class='error'>❌ API Exception: " . $e->getMessage() . "</p>\n";
}

$output = ob_get_clean();

echo "<p>API Response:</p>\n";
echo "<pre>" . htmlspecialchars($output) . "</pre>\n";

// Parse response
$response = json_decode($output, true);
if ($response && isset($response['sale_id'])) {
    echo "<p class='success'>🎉 API TEST SUCCESSFUL! Sale ID: " . $response['sale_id'] . "</p>\n";
} else {
    echo "<p class='error'>❌ API test failed</p>\n";
}

echo "</div>\n";

// Section 6: Common Issues and Solutions
echo "<div class='section'>\n";
echo "<h2>🛠️ Common Issues & Solutions</h2>\n";

echo "<h3>1. Session Issues:</h3>\n";
echo "<ul>\n";
echo "<li>Ensure session_save_path() is writable</li>\n";
echo "<li>Check session.auto_start in php.ini</li>\n";
echo "<li>Verify session.cookie_path and session.cookie_domain</li>\n";
echo "</ul>\n";

echo "<h3>2. Path Issues:</h3>\n";
echo "<ul>\n";
echo "<li>Use absolute paths for includes</li>\n";
echo "<li>Check DOCUMENT_ROOT configuration</li>\n";
echo "<li>Verify .htaccess rewrite rules</li>\n";
echo "</ul>\n";

echo "<h3>3. Database Issues:</h3>\n";
echo "<ul>\n";
echo "<li>Check database connection credentials</li>\n";
echo "<li>Verify all required tables exist</li>\n";
echo "<li>Check for sufficient privileges</li>\n";
echo "</ul>\n";

echo "<h3>4. Network Issues:</h3>\n";
echo "<ul>\n";
echo "<li>Check if server is running (Apache/MySQL)</li>\n";
echo "<li>Verify port 80 is not blocked</li>\n";
echo "<li>Check firewall settings</li>\n";
echo "<li>Try accessing via 127.0.0.1 instead of localhost</li>\n";
echo "</ul>\n";

echo "</div>\n";

// Section 7: Next Steps
echo "<div class='section'>\n";
echo "<h2>🎯 Next Steps</h2>\n";

echo "<ol>\n";
echo "<li><strong>Check the results above</strong> for any ❌ errors</li>\n";
echo "<li><strong>Fix any file permission issues</strong> (make files readable)</li>\n";
echo "<li><strong>Ensure database is running</strong> and accessible</li>\n";
echo "<li><strong>Verify session configuration</strong> in php.ini</li>\n";
echo "<li><strong>Test the API</strong> using the debug tools provided</li>\n";
echo "<li><strong>Check browser console</strong> for JavaScript errors</li>\n";
echo "<li><strong>Monitor server logs</strong> for detailed error messages</li>\n";
echo "</ol>\n";

echo "<p><strong>Access this page at:</strong> <code>http://localhost/shop_management/sales/debug_ultimate.php</code></p>\n";

echo "</div>\n";

echo "</body>\n</html>\n";
?>