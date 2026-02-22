<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($quote_id <= 0) {
    flash('error', __('invalid_quote_id'));
    header('Location: history.php');
    exit();
}

// Fetch quote details
$stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ? AND document_type = 'quote' AND status = 'draft'");
$stmt->execute([$quote_id]);
$quote = $stmt->fetch();

if (!$quote) {
    flash('error', __('quote_not_found_or_cannot_be_converted'));
    header('Location: history.php');
    exit();
}

try {
    // Convert quote to invoice
    $stmt = $pdo->prepare("UPDATE sales SET document_type = 'invoice', status = 'confirmed', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$quote_id]);
    
    flash('success', __('quote_converted_to_invoice_successfully'));
    header('Location: ../sales/view.php?id=' . $quote_id);
    exit();
    
} catch (Exception $e) {
    flash('error', __('error_converting_quote') . ' ' . $e->getMessage());
    header('Location: history.php');
    exit();
}
?>
