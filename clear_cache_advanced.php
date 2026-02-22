<?php
/**
 * Advanced Cache Clearing Script
 * Clears cache, temp files, logs, and optimizes the system
 */

// Set time limit to avoid timeouts
set_time_limit(300);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths to clear
$paths_to_clear = [
    'cache' => __DIR__ . '/cache',
    'temp' => __DIR__ . '/temp',
    'logs' => __DIR__ . '/logs',
    'sessions' => session_save_path(),
    'uploads_temp' => __DIR__ . '/uploads/temp',
];

$files_to_clear = [
    '*.tmp',
    '*.cache',
    '*.log',
    '*.temp',
    'session_*',
];

$cleared_items = [];
$errors = [];

function clearDirectory($dir, &$cleared_items, &$errors) {
    if (!file_exists($dir)) {
        return;
    }
    
    try {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $cleared_items[] = "Deleted file: " . basename($file);
                } else {
                    $errors[] = "Failed to delete file: " . $file;
                }
            } elseif (is_dir($file)) {
                clearDirectory($file, $cleared_items, $errors);
                if (rmdir($file)) {
                    $cleared_items[] = "Deleted directory: " . basename($file);
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = "Error clearing directory $dir: " . $e->getMessage();
    }
}

// Clear session
session_start();
session_unset();
session_destroy();
$cleared_items[] = "Session data cleared";

// Clear cache headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Clear PHP caches
if (function_exists('opcache_reset')) {
    opcache_reset();
    $cleared_items[] = "OPcache reset";
}

if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    apc_clear_cache('user');
    apc_clear_cache('opcode');
    $cleared_items[] = "APC cache cleared";
}

// Clear defined directories
foreach ($paths_to_clear as $name => $path) {
    if ($path && file_exists($path)) {
        clearDirectory($path, $cleared_items, $errors);
        $cleared_items[] = "Cleared directory: $name";
    }
}

// Clear specific file patterns
foreach ($files_to_clear as $pattern) {
    $files = glob(__DIR__ . '/' . $pattern);
    foreach ($files as $file) {
        if (is_file($file) && unlink($file)) {
            $cleared_items[] = "Deleted: " . basename($file);
        }
    }
}

// Clear output buffers
while (ob_get_level()) {
    ob_end_clean();
}
$cleared_items[] = "Output buffers cleared";

// Get memory usage before and after
$memory_before = memory_get_usage(true);
gc_collect_cycles();
$memory_after = memory_get_usage(true);
$memory_freed = $memory_before - $memory_after;

if ($memory_freed > 0) {
    $cleared_items[] = "Memory freed: " . formatBytes($memory_freed);
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Cache Clear</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Advanced Cache Clear Complete</h1>
                <p class="text-gray-600">System optimization and cache clearing performed</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cleared Items -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="font-semibold text-green-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Cleared Successfully (<?php echo count($cleared_items); ?>)
                    </h3>
                    <div class="max-h-60 overflow-y-auto">
                        <ul class="text-sm text-green-800 space-y-1">
                            <?php foreach ($cleared_items as $item): ?>
                                <li class="flex items-start">
                                    <span class="text-green-600 mr-2">•</span>
                                    <?php echo htmlspecialchars($item); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Errors (if any) -->
                <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h3 class="font-semibold text-red-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Errors (<?php echo count($errors); ?>)
                    </h3>
                    <div class="max-h-60 overflow-y-auto">
                        <ul class="text-sm text-red-800 space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li class="flex items-start">
                                    <span class="text-red-600 mr-2">•</span>
                                    <?php echo htmlspecialchars($error); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- System Info -->
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">System Information</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Memory Usage:</span>
                        <div class="font-medium"><?php echo formatBytes(memory_get_usage(true)); ?></div>
                    </div>
                    <div>
                        <span class="text-gray-600">Peak Memory:</span>
                        <div class="font-medium"><?php echo formatBytes(memory_get_peak_usage(true)); ?></div>
                    </div>
                    <div>
                        <span class="text-gray-600">PHP Version:</span>
                        <div class="font-medium"><?php echo PHP_VERSION; ?></div>
                    </div>
                    <div>
                        <span class="text-gray-600">Time Taken:</span>
                        <div class="font-medium"><?php echo round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2); ?>s</div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <a href="index.php" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-center">
                    Dashboard
                </a>
                <a href="sales/pos.php" class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors text-center">
                    POS System
                </a>
                <button onclick="window.location.reload()" class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors">
                    Clear Again
                </button>
            </div>
        </div>
    </div>
</body>
</html>
