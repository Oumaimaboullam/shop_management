<?php
// api/sales/delete_draft.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

// Get draft ID from URL parameter
$draftId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($draftId === 0) {
    jsonResponse(['success' => false, 'message' => 'Draft ID required'], 400);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    exit;
}

try {
    $uid = (int) $_SESSION['user_id'];
    $pdo->beginTransaction();

    $own = $pdo->prepare("SELECT id FROM draft_orders WHERE id = ? AND user_id = ?");
    $own->execute([$draftId, $uid]);
    if ($own->rowCount() === 0) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Draft not found'], 404);
    }
    
    $stmt = $pdo->prepare("DELETE FROM draft_items WHERE draft_id = ?");
    $stmt->execute([$draftId]);
    
    $stmt = $pdo->prepare("DELETE FROM draft_orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$draftId, $uid]);
    
    // Check if draft was actually deleted
    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Draft not found'], 404);
        exit;
    }
    
    $pdo->commit();
    
    jsonResponse([
        'success' => true,
        'message' => 'Draft deleted successfully'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    jsonResponse([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], 500);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
}
?>
