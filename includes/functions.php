<?php
// includes/functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language System
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fr'; // Default language
}

$lang = [];
$lang_file = __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    // Fallback to English
    $lang = require __DIR__ . '/lang/en.php';
}

/**
 * Translate string
 * @param string $key
 * @param string $default Optional default translation
 * @return string
 */
function __($key, $default = null) {
    static $lang = null;

    if ($lang === null) {
        if (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = 'en';
        }

        $lang_file = __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
        if (file_exists($lang_file)) {
            $lang = require $lang_file;
        } else {
            // Fallback to English
            $lang = require __DIR__ . '/lang/en.php';
        }
    }

    // If default is provided, use it
    if ($default !== null && !isset($lang[$key])) {
        return $default;
    }

    return isset($lang[$key]) ? $lang[$key] : $key;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login to access a page
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Adjust path dynamically if needed, or assume standard structure
        $path = '/shop_management/login.php';
        header("Location: " . $path);
        exit();
    }
}

/**
 * Send JSON response
 * @param mixed $data
 * @param int $statusCode
 */
function jsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

/**
 * Sanitize input
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

/**
 * Check if user has specific role
 * @param string|array $roles
 * @return bool
 */
function hasRole($roles) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    if (is_array($roles)) {
        return in_array($user['role'], $roles);
    }
    
    return $user['role'] === $roles;
}

/**
 * Set or get flash messages
 * @param string $type 'success', 'error', or 'warning'
 * @param string|null $message
 * @return string|null
 */
function flash($type, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$type] = $message;
    } else {
        if (isset($_SESSION['flash'][$type])) {
            $msg = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $msg;
        }
    }
    return null;
}

/**
 * Check if flash message exists
 * @param string $type 'success' or 'error'
 * @return bool
 */
function hasFlash($type) {
    return isset($_SESSION['flash'][$type]);
}

/**
 * Get flash message without clearing it
 * @param string $type 'success' or 'error'
 * @return string|null
 */
function getFlash($type) {
    return isset($_SESSION['flash'][$type]) ? $_SESSION['flash'][$type] : null;
}

/**
 * Get setting value from database
 * @param string $key
 * @param string $default
 * @return string|null
 */
function getSetting($key, $default = null) {
    global $pdo;
    static $settings = null;
    
    if ($settings === null) {
        $settings = [];
        if (isset($pdo)) {
            try {
                $stmt = $pdo->query("SELECT key_name, value FROM settings");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $settings[$row['key_name']] = $row['value'];
                }
            } catch (Exception $e) {
                // Return default if database error
                return $default;
            }
        }
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Record a stock movement
 * @param PDO $pdo Database connection
 * @param int $article_id Article ID
 * @param string $type 'in', 'out', or 'adjustment'
 * @param int $quantity Quantity (always positive)
 * @param string $source 'purchase', 'sale', 'return', 'manual'
 * @param int $reference_id ID of the related record (sale_id, purchase_id, etc.)
 * @return bool
 */
function recordStockMovement($pdo, $article_id, $type, $quantity, $source, $reference_id) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO stock_movements (article_id, type, quantity, source, reference_id, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$article_id, $type, $quantity, $source, $reference_id]);
    } catch (Exception $e) {
        error_log("Stock movement error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update stock quantity
 * @param PDO $pdo Database connection
 * @param int $article_id Article ID
 * @param int $quantity Quantity change (positive to increase, negative to decrease)
 * @return bool
 */
function updateStockQuantity($pdo, $article_id, $quantity) {
    try {
        $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity + ? WHERE article_id = ?");
        return $stmt->execute([$quantity, $article_id]);
    } catch (Exception $e) {
        error_log("Stock update error: " . $e->getMessage());
        return false;
    }
}
?>