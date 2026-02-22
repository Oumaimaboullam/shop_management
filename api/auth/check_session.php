<?php
// api/auth/check_session.php - Check if user is logged in
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display errors

require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

session_start();

$response = [
    'logged_in' => isLoggedIn(),
    'session_id' => session_id(),
    'session_status' => session_status(),
    'user' => $_SESSION['user'] ?? null,
    'user_id' => $_SESSION['user_id'] ?? null
];

jsonResponse($response);
?>