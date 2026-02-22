<?php
// api/auth/login.php
require_once '../../config/database.php';

session_start();

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit();
}

$username = trim($input['username']);
$password = $input['password'];

try {
    // Prepare statement to prevent SQL injection
    $stmt = $pdo->prepare("SELECT id, name, username, password_hash, role, is_active FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (!$user['is_active']) {
            echo json_encode(['success' => false, 'message' => 'Account is inactive']);
            exit();
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'username' => $user['username'],
            'role' => $user['role']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user_id' => $user['id'],
            'role' => $user['role']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>