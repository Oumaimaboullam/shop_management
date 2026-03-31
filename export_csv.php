<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    die('Accès non autorisé');
}

$type = $_GET['type'] ?? '';

if (empty($type)) {
    die('Type d\'export manquant');
}

// Clean any accidental output or spaces before generating CSV
ob_end_clean();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $type . '_export_' . date('Y-m-d_H-i-s') . '.csv');

$output = fopen('php://output', 'w');

// Add BOM for correct UTF-8 display in applications like Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch ($type) {
    case 'products':
        fputcsv($output, ['ID', 'Référence', 'Code-barres', 'Nom', 'Catégorie', 'Prix Achat', 'Prix Vente', 'Prix Gros', 'Stock'], ';');
        $stmt = $pdo->query("SELECT a.id, a.reference, a.barcode, a.name, c.name as category_name, a.purchase_price, a.sale_price, a.wholesale, COALESCE(s.quantity, 0) as quantity 
                            FROM articles a 
                            LEFT JOIN categories c ON a.category_id = c.id 
                            LEFT JOIN stock s ON s.article_id = a.id
                            ORDER BY a.name");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'], 
                $row['reference'], 
                $row['barcode'], 
                $row['name'], 
                $row['category_name'], 
                $row['purchase_price'], 
                $row['sale_price'], 
                $row['wholesale'], 
                $row['quantity']
            ], ';');
        }
        break;

    case 'customers':
        fputcsv($output, ['ID', 'Nom', 'Téléphone', 'Email', 'Adresse', 'Solde (Balance)'], ';');
        $stmt = $pdo->query("SELECT id, name, phone, email, address, balance FROM clients ORDER BY name");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'], 
                $row['name'], 
                $row['phone'], 
                $row['email'], 
                $row['address'], 
                $row['balance']
            ], ';');
        }
        break;

    case 'suppliers':
        fputcsv($output, ['ID', 'Nom', 'Téléphone', 'Email', 'Adresse', 'Solde'], ';');
        $stmt = $pdo->query("SELECT id, name, phone, email, address, balance FROM suppliers ORDER BY name");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'], 
                $row['name'], 
                $row['phone'], 
                $row['email'], 
                $row['address'], 
                $row['balance']
            ], ';');
        }
        break;

    case 'purchases':
        fputcsv($output, ['ID', 'N° Facture', 'Date', 'Fournisseur', 'Montant Total', 'Montant Payé', 'Reste à Payer', 'Statut'], ';');
        $stmt = $pdo->query("SELECT p.id, p.invoice_number, p.created_at, s.name as supplier_name, 
                            p.total_amount, p.paid_amount, (p.total_amount - p.paid_amount) as remaining, p.status 
                            FROM purchases p 
                            LEFT JOIN suppliers s ON p.supplier_id = s.id 
                            ORDER BY p.created_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $date = date('Y-m-d', strtotime($row['created_at']));
            $status = '';
            switch(strtolower($row['status'])) {
                case 'paid': $status = 'Payé'; break;
                case 'partial': $status = 'Partiel'; break;
                default: $status = 'En Attente'; break;
            }
            fputcsv($output, [
                $row['id'], 
                $row['invoice_number'], 
                $date, 
                $row['supplier_name'], 
                $row['total_amount'], 
                $row['paid_amount'], 
                $row['remaining'], 
                $status
            ], ';');
        }
        break;
        
    case 'inventory_report':
        fputcsv($output, ['ID', 'Article', 'Catégorie', 'Quantité en Stock', 'Seuil d\'alerte', 'État du Stock'], ';');
        $stmt = $pdo->query("SELECT a.id, a.name, c.name as category_name, COALESCE(s.quantity, 0) as quantity, a.stock_alert_level 
                            FROM articles a 
                            LEFT JOIN categories c ON a.category_id = c.id 
                            LEFT JOIN stock s ON s.article_id = a.id
                            ORDER BY s.quantity ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = 'En Stock';
            if ($row['quantity'] == 0) {
                $status = 'Rupture de Stock';
            } elseif ($row['quantity'] <= $row['stock_alert_level']) {
                $status = 'Stock Faible';
            }
            
            fputcsv($output, [
                $row['id'], 
                $row['name'], 
                $row['category_name'], 
                $row['quantity'], 
                $row['stock_alert_level'], 
                $status
            ], ';');
        }
        break;

    default:
        fputcsv($output, ['Erreur : Type d\'export non valide'], ';');
        break;
}

fclose($output);
exit();
?>
