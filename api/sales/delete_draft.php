<?php
// api/sales/delete_draft.php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

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
    $pdo->beginTransaction();
    
    // First delete draft items (due to foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM draft_items WHERE draft_id = ?");
    $stmt->execute([$draftId]);
    
    // Then delete the draft order
    $stmt = $pdo->prepare("DELETE FROM draft_orders WHERE id = ?");
    $stmt->execute([$draftId]);
    
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
