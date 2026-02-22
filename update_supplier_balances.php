<?php
require_once 'config/database.php';

echo "Updating supplier balances...\n";

try {
    // Get all suppliers
    $stmt = $pdo->query("SELECT id, name FROM suppliers");
    $suppliers = $stmt->fetchAll();

    foreach ($suppliers as $supplier) {
        // Calculate total purchases for this supplier
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total_purchases
            FROM purchases 
            WHERE supplier_id = ?
        ");
        $stmt->execute([$supplier['id']]);
        $purchases = $stmt->fetch();
        
        // Calculate total paid for this supplier
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(paid_amount), 0) as total_paid
            FROM purchases 
            WHERE supplier_id = ?
        ");
        $stmt->execute([$supplier['id']]);
        $paid = $stmt->fetch();
        
        // Calculate balance
        $balance = $purchases['total_purchases'] - $paid['total_paid'];
        
        // Update supplier balance
        $stmt = $pdo->prepare("
            UPDATE suppliers 
            SET balance = ? 
            WHERE id = ?
        ");
        $stmt->execute([$balance, $supplier['id']]);
        
        echo "Updated supplier '{$supplier['name']}' (ID: {$supplier['id']}): Purchased: {$purchases['total_purchases']}, Paid: {$paid['total_paid']}, Balance: {$balance}\n";
    }
    
    echo "\nSupplier balances updated successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
