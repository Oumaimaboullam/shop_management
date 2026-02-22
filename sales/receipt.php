<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../config/printer.php';
require_once '../includes/dynamic_table.php';
requireLogin();

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;

if ($sale_id <= 0) {
    header('Location: pos.php');
    exit();
}

// Fetch sale details with all related information
$stmt = $pdo->prepare("
    SELECT s.*, u.name as user_name, c.name as client_name, c.phone as client_phone, c.email as client_email, c.address as client_address,
           pm.name as payment_mode
    FROM sales s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN clients c ON s.client_id = c.id
    LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    header('Location: pos.php');
    exit();
}

// Fetch sale items with wholesale data
$stmt = $pdo->prepare("
    SELECT si.*, a.name as article_name, a.barcode, a.reference, a.tax_rate as item_tax_rate,
           a.sale_price, a.wholesale_percentage
    FROM sale_items si
    JOIN articles a ON si.article_id = a.id
    WHERE si.sale_id = ?
    ORDER BY si.id
");
$stmt->execute([$sale_id]);
$sale_items = $stmt->fetchAll();

// Get company information from settings
$company_name = getSetting('company_name', 'My Shop');
$company_address = getSetting('company_address', '123 Main St');
$company_phone = getSetting('company_phone', '+212 123-456789');
$company_email = getSetting('company_email', 'info@shop.com');
$company_logo = getSetting('company_logo', '');
$company_rc = getSetting('company_rc', '');
$company_ice = getSetting('company_ice', '');
$currency_symbol = getSetting('currency_symbol', 'DH');

// Calculate totals
$subtotal = 0;
$total_tax = 0;
$default_tax_rate = 20; // Default tax rate if not set

foreach ($sale_items as $item) {
    $subtotal += $item['total_price'];
    $item_tax_rate = isset($item['tax_rate']) ? $item['tax_rate'] : $default_tax_rate;
    $item_tax = $item['total_price'] * ($item_tax_rate / 100);
    $total_tax += $item_tax;
}
$total_amount = $subtotal + $total_tax;

// Apply discount if any
$discount_percent = isset($sale['discount_percent']) ? $sale['discount_percent'] : 0;
if ($discount_percent > 0) {
    $discount_amount = $subtotal * ($discount_percent / 100);
    $total_amount -= $discount_amount;
} else {
    $discount_amount = 0;
}

// Generate receipt number
$receipt_number = 'R-' . date('Y') . '-' . str_pad($sale_id, 6, '0', STR_PAD_LEFT);
$receipt_date = date('d/m/Y H:i', strtotime($sale['created_at']));

// French labels
$labels = [
    'receipt' => 'REÇU',
    'date' => 'Date',
    'cashier' => 'Caissier',
    'customer' => 'Client',
    'subtotal' => 'Sous-total',
    'vat' => 'TVA',
    'discount' => 'Remise',
    'total' => 'TOTAL',
    'payment_method' => 'Méthode de paiement',
    'advance' => 'Avance',
    'remaining' => 'Reste',
    'thank_you' => 'MERCI POUR VOTRE ACHAT!',
    'come_again' => 'Revenez nous voir',
    'support' => 'Pour support',
    'for_support' => 'Pour support'
];

// Get printer configuration
$paper_config = getPrinterConfig('paper');
$format_config = getPrinterConfig('formatting');
$auto_print = getPrinterConfig('auto_print');

// Helper functions for receipt formatting
function formatMoney($amount, $currency = 'DH') {
    return number_format($amount, 2) . ' ' . $currency;
}

function formatItemRow($quantity, $unit_price, $total, $currency = 'DH') {
    $left = sprintf("%d x %.2f", $quantity, $unit_price);
    $right = formatMoney($total, $currency);
    return createTableRow($left, $right);
}

function createSeparator($char = '-', $length = null) {
    if ($length === null) {
        $length = getCharsPerLine();
    }
    return str_repeat($char, $length);
}

// Log printer activity
logPrinterActivity("Generating receipt for sale ID: $sale_id");

// Determine sale type for dynamic table
$sale_type = isset($sale['sale_type']) ? $sale['sale_type'] : 'normal';

// Create dynamic table instance
$dynamic_table = new DynamicSaleTable($sale_type, $sale_items, $currency_symbol, true, $default_tax_rate);

// Calculate totals using dynamic table
$subtotal = $dynamic_table->getSubtotal();
$total_tax = $dynamic_table->getVAT();
$total_amount = $dynamic_table->getGrandTotal();

// Apply discount if any
$discount_percent = isset($sale['discount_percent']) ? $sale['discount_percent'] : 0;
if ($discount_percent > 0) {
    $discount_amount = $subtotal * ($discount_percent / 100);
    $total_amount -= $discount_amount;
} else {
    $discount_amount = 0;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?php echo $receipt_number; ?></title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: <?php echo $format_config['normal_font_size']; ?>px;
            line-height: 1.1;
            width: <?php echo $paper_config['width']; ?>mm;
            padding: <?php echo $paper_config['margins']['left']; ?>mm;
            background: white;
            color: black;
        }
        
        /* Thermal printer optimized styles */
        .receipt {
            max-width: <?php echo ($paper_config['width'] - ($paper_config['margins']['left'] + $paper_config['margins']['right'])); ?>mm;
            margin: 0 auto;
        }
        
        /* Header styles */
        .header {
            text-align: center;
            margin-bottom: 6px;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
        }
        
        .logo {
            max-width: 40mm;
            height: auto;
            margin-bottom: 3px;
        }
        
        .company-name {
            font-size: <?php echo $format_config['title_font_size']; ?>px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .company-info {
            font-size: <?php echo $format_config['small_font_size']; ?>px;
            margin-bottom: 1px;
        }
        
        /* Receipt info */
        .receipt-info {
            margin-bottom: 6px;
            font-size: <?php echo $format_config['small_font_size']; ?>px;
        }
        
        .receipt-info div {
            margin-bottom: 1px;
        }
        
        /* Items table */
        .items {
            margin-bottom: 6px;
        }
        
        .item {
            margin-bottom: 3px;
            font-size: <?php echo $format_config['normal_font_size']; ?>px;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 1px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: <?php echo $format_config['small_font_size']; ?>px;
        }
        
        /* Totals section */
        .totals {
            margin-bottom: 6px;
            border-top: 1px dashed #000;
            padding-top: 3px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
            font-size: <?php echo $format_config['normal_font_size']; ?>px;
        }
        
        .grand-total {
            font-weight: bold;
            font-size: <?php echo $format_config['header_font_size']; ?>px;
            border-top: 1px solid #000;
            padding-top: 2px;
            margin-top: 3px;
        }
        
        /* Payment info */
        .payment {
            margin-bottom: 6px;
            font-size: <?php echo $format_config['normal_font_size']; ?>px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 6px;
            border-top: 1px dashed #000;
            padding-top: 6px;
            font-size: <?php echo $format_config['small_font_size']; ?>px;
        }
        
        .thank-you {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: <?php echo $format_config['normal_font_size']; ?>px;
        }
        
        /* Print optimization */
        @media print {
            body {
                margin: 0;
                padding: <?php echo $paper_config['margins']['left']; ?>mm;
                width: <?php echo $paper_config['width']; ?>mm;
            }
            
            .receipt {
                margin: 0;
                max-width: <?php echo ($paper_config['width'] - ($paper_config['margins']['left'] + $paper_config['margins']['right'])); ?>mm;
            }
            
            /* Ensure no page breaks */
            * {
                page-break-inside: avoid;
                page-break-after: avoid;
                page-break-before: avoid;
            }
            
            /* Hide any potential controls */
            .no-print {
                display: none !important;
            }
        }
        
        /* ESC/POS compatibility */
        @page {
            size: <?php echo $paper_config['width']; ?>mm auto;
            margin: <?php echo $paper_config['margins']['left']; ?>mm;
        }
        
        /* Ensure text doesn't overflow */
        .text-wrap {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        /* Compact spacing for thermal printer */
        .compact {
            line-height: 1.0;
        }
        
        /* Separator line */
        .separator {
            border-top: 1px dashed #000;
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header Section -->
        <div class="header">
            <?php if ($company_logo && file_exists('../uploads/' . $company_logo)): ?>
                <img src="../uploads/<?php echo htmlspecialchars($company_logo); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            
            <div class="company-name"><?php echo htmlspecialchars($company_name); ?></div>
            <div class="company-info"><?php echo htmlspecialchars($company_address); ?></div>
            <div class="company-info">Tel: <?php echo htmlspecialchars($company_phone); ?></div>
            <?php if ($company_email): ?>
                <div class="company-info"><?php echo htmlspecialchars($company_email); ?></div>
            <?php endif; ?>
            <?php if ($company_rc || $company_ice): ?>
                <div class="company-info">
                    <?php if ($company_rc) echo htmlspecialchars($company_rc); ?>
                    <?php if ($company_rc && $company_ice) echo ' | '; ?>
                    <?php if ($company_ice) echo htmlspecialchars($company_ice); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Receipt Information -->
        <div class="receipt-info">
            <div><strong><?php echo $labels['receipt']; ?> #: <?php echo $receipt_number; ?></strong></div>
            <div><?php echo $labels['date']; ?>: <?php echo $receipt_date; ?></div>
            <div><?php echo $labels['cashier']; ?>: <?php echo htmlspecialchars($sale['user_name']); ?></div>
            <?php if ($sale['client_name']): ?>
                <div><?php echo $labels['customer']; ?>: <?php echo htmlspecialchars($sale['client_name']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Items Section -->
        <?php echo $dynamic_table->renderTable(true); ?>
        
        <!-- Totals Section -->
        <?php echo $dynamic_table->renderTotals(true); ?>
        
        <!-- Payment Information -->
        <div class="payment">
            <div><strong><?php echo $labels['payment_method']; ?>:</strong> <?php echo htmlspecialchars($sale['payment_mode']); ?></div>
            <?php if ($sale['advance_payment'] > 0): ?>
                <div><?php echo $labels['advance']; ?>: <?php echo formatMoney($sale['advance_payment'], $currency_symbol); ?></div>
                <div><?php echo $labels['remaining']; ?>: <?php echo formatMoney($total_amount - $sale['advance_payment'], $currency_symbol); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Footer Section -->
        <div class="footer">
            <div class="thank-you"><?php echo $labels['thank_you']; ?></div>
            <div><?php echo $labels['come_again']; ?></div>
            <?php if ($company_phone): ?>
                <div><?php echo $labels['for_support']; ?>: <?php echo htmlspecialchars($company_phone); ?></div>
            <?php endif; ?>
            <div class="compact" style="margin-top: 6px; font-size: 9px;">
                <?php echo date('Y'); ?> © <?php echo htmlspecialchars($company_name); ?> 
            </div>
        </div>
    </div>
    
    <!-- Auto print script -->
    <script>
        // Auto-trigger print when page loads
        window.addEventListener('load', function() {
            <?php if ($auto_print['enabled']): ?>
            setTimeout(function() {
                window.print();
                <?php if ($auto_print['close_after_print']): ?>
                // Optional: close window after printing
                setTimeout(function() {
                    window.close();
                }, <?php echo $auto_print['close_delay']; ?>);
                <?php endif; ?>
            }, <?php echo $auto_print['delay']; ?>);
            <?php endif; ?>
        });
        
        // Handle print dialog cancellation
        window.addEventListener('afterprint', function() {
            <?php if ($auto_print['close_after_print']): ?>
            window.close();
            <?php endif; ?>
        });
    </script>
</body>
</html>
