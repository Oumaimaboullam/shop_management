<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;

if ($sale_id <= 0) {
    header('Location: pos.php');
    exit();
}

// Fetch sale details to check document type
$stmt = $pdo->prepare("SELECT document_type FROM sales WHERE id = ?");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

// Redirect based on document type
if ($sale && $sale['document_type'] === 'invoice') {
    header('Location: print_invoice_moroccan.php?sale_id=' . $sale_id);
    exit();
} elseif ($sale && $sale['document_type'] === 'quote') {
    header('Location: print_invoice_moroccan.php?sale_id=' . $sale_id);
    exit();
} else {
    // Normal sale - redirect to thermal receipt
    header('Location: receipt.php?sale_id=' . $sale_id);
    exit();
}

// Continue with regular receipt for other document types
// Fetch sale details with all related information
$stmt = $pdo->prepare("
    SELECT s.*, u.name as user_name, c.name as client_name, c.phone as client_phone, c.email as client_email,
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

// Fetch sale items
$stmt = $pdo->prepare("
    SELECT si.*, a.name as article_name, a.barcode, a.reference
    FROM sale_items si
    JOIN articles a ON si.article_id = a.id
    WHERE si.sale_id = ?
    ORDER BY si.id
");
$stmt->execute([$sale_id]);
$sale_items = $stmt->fetchAll();

// Get company information from settings
$company_name = getSetting('company_name', 'My Shop');
$company_address = getSetting('company_address', '123 Business St, City, Country');
$company_phone = getSetting('company_phone', '+1 234 567 8900');
$company_email = getSetting('company_email', 'info@company.com');
$company_website = getSetting('company_website', 'www.company.com');
$tax_rate = getSetting('tax_rate_default', '0');
$currency_symbol = getSetting('currency_symbol', 'DH');
$receipt_header = getSetting('receipt_header', 'Thank you for your purchase!');

// Calculate tax and totals
$subtotal = $sale['subtotal_amount'];
$tax_amount = $subtotal * ($tax_rate / 100);
$total = $sale['total_amount'];

// Set page title and styling for receipt only
$pageTitle = 'RECEIPT #' . $sale_id;
$document_title = 'RECEIPT';
$container_class = 'receipt-container';
$max_width = '400px';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
        
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border: 2px solid #000;
            padding: 1rem;
        }
        
        .receipt-header {
            border-bottom: 2px dashed #000;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        
        .receipt-footer {
            border-top: 2px dashed #000;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        
        .item-row {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .total-row {
            border-top: 2px solid #000;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-100 p-4">
    <div class="<?php echo $container_class; ?> shadow-lg rounded-lg p-6">
        <!-- Company Header -->
        <div class="receipt-header text-center">
            <?php if ($company_logo = getSetting('company_logo')): ?>
                <img src="../uploads/<?php echo htmlspecialchars($company_logo); ?>" alt="Company Logo" class="w-20 h-20 mx-auto mb-2 rounded">
            <?php endif; ?>
            
            <h1 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($company_name); ?></h1>
            <p class="text-sm text-gray-600 mt-1"><?php echo nl2br(htmlspecialchars($company_address)); ?></p>
            
            <div class="mt-2 text-sm text-gray-600">
                <?php if ($company_phone): ?>
                    <div>📞 <?php echo htmlspecialchars($company_phone); ?></div>
                <?php endif; ?>
                <?php if ($company_email): ?>
                    <div>✉️ <?php echo htmlspecialchars($company_email); ?></div>
                <?php endif; ?>
                <?php if ($company_website): ?>
                    <div>🌐 <?php echo htmlspecialchars($company_website); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Document Info -->
        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-lg font-bold"><?php echo $document_title; ?></h2>
                <span class="text-sm font-mono bg-gray-100 px-2 py-1 rounded">#<?php echo $sale_id; ?></span>
            </div>
            
            <div class="text-sm text-gray-600 space-y-1">
                <div class="flex justify-between">
                    <span>Date:</span>
                    <span><?php echo date('M j, Y', strtotime($sale['created_at'])); ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Time:</span>
                    <span><?php echo date('H:i', strtotime($sale['created_at'])); ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Cashier:</span>
                    <span><?php echo htmlspecialchars($sale['user_name']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Type:</span>
                    <span class="capitalize"><?php echo htmlspecialchars($sale['document_type']); ?></span>
                </div>
                <?php if ($sale['document_type'] === 'receipt' && $sale['client_name']): ?>
                <div class="flex justify-between">
                    <span>Customer:</span>
                    <span><?php echo htmlspecialchars($sale['client_name']); ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between">
                    <span>Payment:</span>
                    <span><?php echo htmlspecialchars($sale['payment_mode']); ?></span>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="mb-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300">
                        <th class="text-left py-2">Item</th>
                        <th class="text-center py-2">Qty</th>
                        <th class="text-right py-2">Price</th>
                        <th class="text-right py-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sale_items as $item): ?>
                    <tr class="item-row">
                        <td class="py-2">
                            <div class="font-medium"><?php echo htmlspecialchars($item['article_name']); ?></div>
                            <?php if ($item['reference']): ?>
                                <div class="text-xs text-gray-500">Ref: <?php echo htmlspecialchars($item['reference']); ?></div>
                            <?php endif; ?>
                            <?php if ($item['barcode']): ?>
                                <div class="text-xs text-gray-500">Barcode: <?php echo htmlspecialchars($item['barcode']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center py-2"><?php echo $item['quantity']; ?></td>
                        <td class="text-right py-2"><?php echo $currency_symbol; ?><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-right py-2"><?php echo $currency_symbol; ?><?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="mb-4">
            <table class="w-full text-sm">
                <tbody>
                    <tr>
                        <td class="py-1">Subtotal:</td>
                        <td class="text-right py-1"><?php echo $currency_symbol; ?><?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <?php if ($tax_rate > 0): ?>
                    <tr>
                        <td class="py-1">Tax (<?php echo $tax_rate; ?>%):</td>
                        <td class="text-right py-1"><?php echo $currency_symbol; ?><?php echo number_format($tax_amount, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($sale['discount_amount'] > 0): ?>
                    <tr>
                        <td class="py-1">Discount:</td>
                        <td class="text-right py-1 text-red-600">-<?php echo $currency_symbol; ?><?php echo number_format($sale['discount_amount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td class="py-2 font-bold">TOTAL:</td>
                        <td class="text-right py-2 font-bold text-lg"><?php echo $currency_symbol; ?><?php echo number_format($total, 2); ?></td>
                    </tr>
                    <?php if ($sale['paid_amount'] > 0): ?>
                    <tr>
                        <td class="py-1">Paid:</td>
                        <td class="text-right py-1"><?php echo $currency_symbol; ?><?php echo number_format($sale['paid_amount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Status -->
        <div class="mb-4 text-center">
            <?php
            $status_color = '';
            $status_text = ucfirst($sale['status']);
            switch($sale['status']) {
                case 'paid': $status_color = 'bg-green-100 text-green-800'; break;
                case 'confirmed': $status_color = 'bg-blue-100 text-blue-800'; break;
                case 'draft': $status_color = 'bg-yellow-100 text-yellow-800'; break;
                case 'partial': $status_color = 'bg-orange-100 text-orange-800'; break;
                case 'cancelled': $status_color = 'bg-red-100 text-red-800'; break;
                default: $status_color = 'bg-gray-100 text-gray-800';
            }
            ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $status_color; ?>">
                <?php echo $status_text; ?>
            </span>
        </div>

        <!-- Footer Message -->
        <div class="receipt-footer text-center">
            <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($receipt_header); ?></p>
            <p class="text-xs text-gray-500">Thank you for your business!</p>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="no-print mt-6 text-center space-x-4">
        <button onclick="window.print()" 
                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
            <i class="fas fa-print mr-2"></i>
            Print RECEIPT
        </button>
        <button onclick="window.location.href='pos.php'" 
                class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>
            New Sale
        </button>
        <button onclick="window.location.href='view.php?id=<?php echo $sale_id; ?>'" 
                class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors">
            <i class="fas fa-eye mr-2"></i>
            View Sale
        </button>
    </div>

    <!-- Auto-print for receipts -->
    <script>
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