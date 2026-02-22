<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
requireLogin();

$type = $_GET['type'] ?? '';

if (!$type) {
    die('Invalid type');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $type . '_export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch ($type) {
    case 'purchases':
        // Header row
        fputcsv($output, [
            'ID',
            'Date',
            'Supplier',
            'Invoice Number',
            'Total Amount',
            'Paid Amount',
            'Status'
        ]);
        
        // Query data
        $stmt = $pdo->query("
            SELECT p.*, s.name as supplier_name 
            FROM purchases p 
            JOIN suppliers s ON p.supplier_id = s.id 
            ORDER BY p.created_at DESC
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                date('M j, Y H:i', strtotime($row['created_at'])),
                $row['supplier_name'],
                $row['invoice_number'],
                number_format($row['total_amount'], 2),
                number_format($row['paid_amount'], 2),
                ucfirst($row['status'])
            ]);
        }
        break;
        
    case 'customers':
        fputcsv($output, [
            'ID',
            'Name',
            'Phone',
            'Email',
            'Address',
            'Created'
        ]);
        
        $stmt = $pdo->query("SELECT * FROM clients ORDER BY created_at DESC");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['phone'],
                $row['email'],
                $row['address'],
                date('M j, Y', strtotime($row['created_at']))
            ]);
        }
        break;
        
    case 'suppliers':
        fputcsv($output, [
            'ID',
            'Name',
            'Phone',
            'Email',
            'Address',
            'Created'
        ]);
        
        $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY created_at DESC");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['phone'],
                $row['email'],
                $row['address'],
                date('M j, Y', strtotime($row['created_at']))
            ]);
        }
        break;
        
    case 'products':
        fputcsv($output, [
            'ID',
            'Name',
            'Barcode',
            'Category',
            'Stock',
            'Sale Price',
            'Purchase Price'
        ]);
        
        $stmt = $pdo->query("
            SELECT a.*, c.name as category_name 
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            ORDER BY a.name
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['barcode'],
                $row['category_name'] ?? '',
                $row['stock_quantity'],
                number_format($row['sale_price'], 2),
                number_format($row['purchase_price'], 2)
            ]);
        }
        break;
        
    case 'inventory_report':
        fputcsv($output, [
            'Product Name',
            'Barcode',
            'Category',
            'Stock Quantity',
            'Alert Level',
            'Status'
        ]);
        
        $stmt = $pdo->query("
            SELECT a.name, a.barcode, c.name as category_name, a.stock_quantity, a.alert_level 
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            ORDER BY a.stock_quantity DESC
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = $row['stock_quantity'] <= $row['alert_level'] ? 'Low Stock' : 'In Stock';
            fputcsv($output, [
                $row['name'],
                $row['barcode'],
                $row['category_name'] ?? '',
                $row['stock_quantity'],
                $row['alert_level'],
                $status
            ]);
        }
        break;
        
    default:
        die('Unknown export type');
}

fclose($output);
?>
