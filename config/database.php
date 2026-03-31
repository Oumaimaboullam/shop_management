<?php
// config/database.php

// Chemin URL public vers la racine de l'app (ex. /shop-management), pour les redirections
if (!defined('APP_BASE_PATH')) {
    $docRoot = (!empty($_SERVER['DOCUMENT_ROOT']))
        ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']))
        : '';
    $projRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
    $projRoot = $projRoot ? str_replace('\\', '/', $projRoot) : '';
    if ($docRoot && $projRoot && strpos($projRoot, $docRoot) === 0) {
        $rel = substr($projRoot, strlen($docRoot));
        define('APP_BASE_PATH', rtrim(str_replace('\\', '/', $rel), '/'));
    } else {
        define('APP_BASE_PATH', '');
    }
}

// Database constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'shop_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    // In a production environment, you might want to log this instead of showing it
    die("Connection failed: " . $e->getMessage());
}
?>