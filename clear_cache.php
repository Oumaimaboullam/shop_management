<?php
/**
 * Cache Clearing Script
 * Clears various types of cache: session, temp files, browser cache headers, etc.
 */

require_once 'includes/functions.php';

// Start session to clear it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear browser cache headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Clear PHP opcode cache if available
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Clear APC cache if available
if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    apc_clear_cache('user');
    apc_clear_cache('opcode');
}

// Create a simple cache clearing message
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache Cleared</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <div class="text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Cache Cleared Successfully!</h1>
            <p class="text-gray-600 mb-6">All cache, sessions, and temporary data have been cleared.</p>
            
            <div class="space-y-2 text-left bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-gray-900 mb-2">Cleared:</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>✓ Session data</li>
                    <li>✓ Session cookies</li>
                    <li>✓ Browser cache headers</li>
                    <li>✓ PHP opcode cache (if available)</li>
                    <li>✓ APC cache (if available)</li>
                    <li>✓ Output buffers</li>
                </ul>
            </div>
            
            <div class="mt-6 space-y-2">
                <a href="index.php" class="block w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                    Go to Dashboard
                </a>
                <a href="sales/pos.php" class="block w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                    Go to POS
                </a>
            </div>
        </div>
    </div>
</body>
</html>
