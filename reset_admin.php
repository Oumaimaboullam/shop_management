<?php
// reset_admin.php
require_once 'config/database.php';

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
$username = 'admin';

try {
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE username = :username");
    $stmt->execute(['hash' => $hash, 'username' => $username]);
    
    if ($stmt->rowCount() > 0) {
        echo "<h1>Success!</h1><p>Password for user 'admin' has been reset to: <strong>admin123</strong></p>";
    } else {
        echo "<h1>Notice</h1><p>No changes made. Either user 'admin' does not exist or password was already set.</p>";
        // Try to insert if not exists
        $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role) VALUES ('Administrator', 'admin', :hash, 'admin')");
        try {
            $stmt->execute(['hash' => $hash]);
            echo "<p>Created new admin user.</p>";
        } catch (Exception $e) {
            // User exists but password matched, or other error
        }
    }
    echo "<br><a href='login.php'>Go to Login</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>