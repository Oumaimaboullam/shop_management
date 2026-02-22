<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
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
$company_name = getSetting('company_name', 'Société Maroc');
$company_address = getSetting('company_address', '123 Rue Business, Casablanca, Maroc');
$company_phone = getSetting('company_phone', '+212 5XX XXX XXX');
$company_email = getSetting('company_email', 'info@societe.ma');
$company_website = getSetting('company_website', 'www.societe.ma');
$company_logo = getSetting('company_logo', '');
$company_rc = getSetting('company_rc', 'RC: 123456');
$company_ice = getSetting('company_ice', 'ICE: 00123456789');
$company_cnss = getSetting('company_cnss', 'CNSS: 1234567');
$company_bank = getSetting('company_bank', 'Banque: BMCE - RIB: 007 780 0001234567800001 18');
$currency_symbol = 'MAD';

// Determine document type and set appropriate labels
$is_quote = ($sale['document_type'] === 'quote');
$document_title = $is_quote ? 'DEVIS' : 'FACTURE';
$document_number = ($is_quote ? 'D-' : 'F-') . date('Y') . '-' . str_pad($sale_id, 6, '0', STR_PAD_LEFT);
$document_label = $is_quote ? 'Devis N°:' : 'Facture N°:';
$document_client_label = $is_quote ? 'DEVIS POUR' : 'FACTURER À';
$document_footer_text = $is_quote ? 'Devis établi conformément à la réglementation fiscale marocaine' : 'Facture établie conformément à la réglementation fiscale marocaine';
$document_button_text = $is_quote ? 'Imprimer le Devis' : 'Imprimer la Facture';

// Determine sale type for dynamic table
$sale_type = isset($sale['sale_type']) ? $sale['sale_type'] : 'normal';
if ($sale['document_type'] === 'quote') {
    $sale_type = 'normal'; // Quotes use normal pricing
} elseif ($sale['document_type'] === 'invoice') {
    $sale_type = isset($sale['sale_type']) ? $sale['sale_type'] : 'normal';
}

// Create dynamic table instance
$dynamic_table = new DynamicSaleTable($sale_type, $sale_items, $currency_symbol, true, 15);

// Calculate totals using dynamic table
$subtotal_ht = $dynamic_table->getSubtotal();
$tax_amount = $dynamic_table->getVAT();
$total_ttc = $dynamic_table->getGrandTotal();

$due_date = date('d/m/Y', strtotime($sale['created_at'] . ' +30 days'));
$invoice_date = date('d/m/Y', strtotime($sale['created_at']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $document_title; ?> <?php echo $document_number; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { 
                display: none !important; 
                visibility: hidden !important;
            }
            
            body { 
                print-color-adjust: exact; 
                -webkit-print-color-adjust: exact; 
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }
            
            @page {
                size: A4;
                margin: 5mm;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .invoice-container {
                max-width: 210mm !important;
                width: 210mm !important;
                margin: 0 auto !important;
                background: white !important;
                border: 1px solid #000 !important;
                box-sizing: border-box !important;
                /* Remove fixed height to allow natural flow */
                page-break-inside: avoid !important;
            }
            
            .header-section {
                border-bottom: 2px solid #000 !important;
                padding: 15px !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
            }
            
            .content-section {
                padding: 15px !important;
                page-break-inside: avoid !important;
                page-break-before: avoid !important;
                /* Allow natural page break only if content overflows */
            }
            
            .footer-section {
                border-top: 2px solid #000 !important;
                padding: 15px !important;
                /* Keep footer with content, don't force page break */
                page-break-inside: avoid !important;
                page-break-before: avoid !important;
                /* Only break if absolutely necessary */
            }
            
            .invoice-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin: 10px 0 !important;
                page-break-inside: avoid !important;
                font-size: 10px !important;
            }
            
            .invoice-table th,
            .invoice-table td {
                border: 1px solid #000 !important;
                padding: 6px !important;
                text-align: left !important;
                page-break-inside: avoid !important;
            }
            
            .invoice-table th {
                background-color: #000 !important;
                color: white !important;
                font-weight: bold !important;
                font-size: 10px !important;
            }
            
            .invoice-table td.text-right {
                text-align: right !important;
            }
            
            .invoice-table td.text-center {
                text-align: center !important;
            }
            
            .total-row {
                border-top: 2px solid #000 !important;
                font-weight: bold !important;
                page-break-inside: avoid !important;
            }
            
            .signature-line {
                border-bottom: 1px solid #000 !important;
                width: 150px !important;
            }
            
            /* Prevent breaks in critical sections */
            .grid {
                page-break-inside: avoid !important;
            }
            
            .flex {
                page-break-inside: avoid !important;
            }
            
            .text-center {
                page-break-inside: avoid !important;
            }
            
            /* Reduce font sizes for print */
            h1 {
                font-size: 20px !important;
                margin: 10px 0 !important;
            }
            
            h3 {
                font-size: 12px !important;
                margin: 5px 0 !important;
            }
            
            .text-sm {
                font-size: 9px !important;
            }
            
            .text-xs {
                font-size: 8px !important;
            }
            
            /* Compact spacing */
            .space-y-1 > * + * {
                margin-top: 2px !important;
            }
            
            .space-y-2 > * + * {
                margin-top: 4px !important;
            }
            
            .gap-4 {
                gap: 16px !important;
            }
            
            .mb-2 {
                margin-bottom: 4px !important;
            }
            
            .mb-3 {
                margin-bottom: 6px !important;
            }
            
            .mb-6 {
                margin-bottom: 12px !important;
            }
            
            .mt-2 {
                margin-top: 4px !important;
            }
            
            .mt-3 {
                margin-top: 6px !important;
            }
            
            .mt-4 {
                margin-top: 8px !important;
            }
            
            .py-2 {
                padding-top: 4px !important;
                padding-bottom: 4px !important;
            }
            
            .py-3 {
                padding-top: 6px !important;
                padding-bottom: 6px !important;
            }
            
            .p-3 {
                padding: 6px !important;
            }
            
            /* Hide URLs and debug info */
            a[href]:after {
                content: "" !important;
            }
            
            .debug-info {
                display: none !important;
            }
            
            /* Special rule to keep footer with content */
            .invoice-container > *:last-child {
                page-break-after: auto !important;
            }
        }
        
        /* Screen styles */
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header-section {
            border-bottom: 2px solid #000;
            padding: 20px;
        }
        
        .footer-section {
            border-top: 2px solid #000;
            padding: 20px;
            margin-top: auto;
        }
        
        .table-header {
            background-color: #000;
            color: white;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            width: 200px;
        }
        
        /* Table styling for screen */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .invoice-table th,
        .invoice-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .invoice-table th {
            background-color: #000;
            color: white;
            font-weight: bold;
        }
        
        .invoice-table td.text-right {
            text-align: right;
        }
        
        .invoice-table td.text-center {
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-100 p-4">
    <div class="invoice-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="flex justify-between items-start">
                <!-- Left: Company Logo and Name -->
                <div class="flex-1">
                    <?php if ($company_logo): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($company_logo); ?>" 
                             alt="Logo" class="w-16 h-16 mb-2 object-contain">
                    <?php endif; ?>
                    <h1 class="text-xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($company_name); ?></h1>
                    <div class="text-xs text-gray-600 space-y-0">
                        <div><?php echo htmlspecialchars($company_address); ?></div>
                        <div><?php echo htmlspecialchars($company_phone); ?></div>
                        <div><?php echo htmlspecialchars($company_email); ?></div>
                        <div><?php echo htmlspecialchars($company_website); ?></div>
                    </div>
                </div>
                
                <!-- Right: Invoice Info -->
                <div class="text-right">
                    <div class="text-xs text-gray-600 space-y-0">
                        <div><strong><?php echo $document_label; ?></strong> <?php echo $document_number; ?></div>
                        <div><strong>Date:</strong> <?php echo $invoice_date; ?></div>
                        <div><strong>Date d'échéance:</strong> <?php echo $due_date; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Title -->
        <div class="text-center py-3">
            <h1 class="text-2xl font-bold text-gray-900"><?php echo $document_title; ?></h1>
        </div>

        <!-- Content Section -->
        <div class="content-section">
            <!-- Client Information -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <!-- FACTURER À -->
                <div class="border border-gray-300 p-3">
                    <h3 class="font-bold text-sm mb-2"><?php echo $document_client_label; ?></h3>
                    <div class="text-xs space-y-0">
                        <div><strong><?php echo htmlspecialchars($sale['client_name'] ?: 'Client Comptant'); ?></strong></div>
                        <?php if ($sale['client_address']): ?>
                            <div><?php echo nl2br(htmlspecialchars($sale['client_address'])); ?></div>
                        <?php endif; ?>
                        <?php if ($sale['client_phone']): ?>
                            <div>Tél: <?php echo htmlspecialchars($sale['client_phone']); ?></div>
                        <?php endif; ?>
                        <?php if ($sale['client_email']): ?>
                            <div>Email: <?php echo htmlspecialchars($sale['client_email']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- EXPÉDIER À -->
                <div class="border border-gray-300 p-3">
                    <h3 class="font-bold text-sm mb-2">EXPÉDIER À</h3>
                    <div class="text-xs space-y-0">
                        <div><strong><?php echo htmlspecialchars($sale['client_name'] ?: 'Client Comptant'); ?></strong></div>
                        <?php if ($sale['client_address']): ?>
                            <div><?php echo nl2br(htmlspecialchars($sale['client_address'])); ?></div>
                        <?php endif; ?>
                        <?php if ($sale['client_phone']): ?>
                            <div>Tél: <?php echo htmlspecialchars($sale['client_phone']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <?php echo $dynamic_table->renderTable(false); ?>

            <!-- Totals and Payment Section -->
            <div class="flex justify-between items-start mt-3">
                <!-- Payment Information -->
                <div class="text-xs text-gray-600">
                    <div><strong>Mode de paiement:</strong> <?php echo htmlspecialchars($sale['payment_mode']); ?></div>
                    <?php if ($sale['paid_amount'] > 0): ?>
                        <div><strong>Montant payé:</strong> <?php echo number_format($sale['paid_amount'], 2, ',', ' '); ?> <?php echo $currency_symbol; ?></div>
                        <?php if ($sale['paid_amount'] < $total_ttc): ?>
                            <div><strong>Solde dû:</strong> <?php echo number_format($total_ttc - $sale['paid_amount'], 2, ',', ' '); ?> <?php echo $currency_symbol; ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Totals Section -->
                <div class="w-5/12">
                    <?php echo $dynamic_table->renderTotals(false); ?>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="flex justify-end mt-4">
                <div class="text-center">
                    <div class="signature-line mb-1"></div>
                    <div class="text-xs text-gray-600">Signature et cachet</div>
                </div>
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer-section">
            <div class="text-xs text-gray-600 space-y-0">
                <div><strong><?php echo htmlspecialchars($company_name); ?></strong></div>
                <div><?php echo htmlspecialchars($company_rc); ?> | <?php echo htmlspecialchars($company_ice); ?> | <?php echo htmlspecialchars($company_cnss); ?></div>
                <div><?php echo htmlspecialchars($company_bank); ?></div>
                <div class="mt-1 text-center"><?php echo $document_footer_text; ?></div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="no-print mt-6 text-center space-x-4">
        <button onclick="window.print()" 
                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
            <i class="fas fa-print mr-2"></i>
            <?php echo $document_button_text; ?>
        </button>
        <button onclick="window.location.href='pos.php'" 
                class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>
            Nouvelle Vente
        </button>
        <button onclick="window.location.href='view.php?id=<?php echo $sale_id; ?>'" 
                class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors">
            <i class="fas fa-eye mr-2"></i>
            Voir la Vente
        </button>
    </div>

    <script>
        // Auto-print for invoices
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
        
        // Handle print completion
        window.addEventListener('afterprint', function() {
            setTimeout(function() {
                window.location.href = 'pos.php';
            }, 1000);
        });
    </script>
</body>
</html>
