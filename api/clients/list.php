<?php
// api/clients/list.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Ensure session is started properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Log all errors for debugging
error_log("=== CLIENTS LIST REQUEST ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Session Status: " . session_status());
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

try {
    // Fetch clients from database
    $stmt = $pdo->query("SELECT id, name, phone, email, address, balance, created_at FROM clients ORDER BY name ASC");
    $clients = $stmt->fetchAll();
    
    error_log("Found " . count($clients) . " clients");
    
    jsonResponse([
        'success' => true,
        'data' => $clients
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
?>
