<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
requireRole(['admin', 'manager']);

// Handle search and filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$supplier_id = $_GET['supplier_id'] ?? '';

// Build comprehensive query
$query = "SELECT p.*, s.name as supplier_name FROM purchases p 
          LEFT JOIN suppliers s ON p.supplier_id = s.id 
          WHERE 1=1";

$params = [];

if ($search) {
    $query .= " AND (p.invoice_number LIKE :search OR s.name LIKE :search OR p.total_amount LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($status) {
    $query .= " AND p.status = :status";
    $params[':status'] = $status;
}

if ($supplier_id) {
    $query .= " AND p.supplier_id = :supplier_id";
    $params[':supplier_id'] = $supplier_id;
}

if ($date_from) {
    $query .= " AND DATE(p.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(p.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$purchases = $stmt->fetchAll();

// Get suppliers for dropdown
$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();

// Calculate totals
$total_amount = array_sum(array_column($purchases, 'total_amount'));
$total_paid = array_sum(array_column($purchases, 'paid_amount'));
$total_balance = $total_amount - $total_paid;

$pageTitle = 'Purchase History';
require_once '../includes/header.php';
?>

<style>
.purchase-history {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.summary-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
}

.summary-card h4 {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 14px;
}

.summary-card .amount {
    font-size: 18px;
    font-weight: bold;
}

.summary-card.total .amount {
    color: #007bff;
}

.summary-card.paid .amount {
    color: #28a745;
}

.summary-card.balance .amount {
    color: #dc3545;
}

.summary-card.count .amount {
    color: #6c757d;
}

.advanced-filters {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filter-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
    gap: 15px;
    align-items: end;
}

.filter-group {
    margin-bottom: 0;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #495057;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.history-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.history-table table {
    width: 100%;
    border-collapse: collapse;
}

.history-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e9ecef;
}

.history-table td {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
}

.history-table tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-paid {
    background: #d4edda;
    color: #155724;
}

.status-partial {
    background: #cce5ff;
    color: #004085;
}

.action-buttons {
    display: flex;
    gap: 8px;
    margin-top: 20px;
}

@media (max-width: 1024px) {
    .filter-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="purchase-history">
    <h2>Purchase History</h2>
    
    <div class="summary-cards">
        <div class="summary-card total">
            <h4>Total Purchases</h4>
            <div class="amount"><?php echo number_format($total_amount, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></div>
        </div>
        <div class="summary-card paid">
            <h4>Total Paid</h4>
            <div class="amount"><?php echo number_format($total_paid, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></div>
        </div>
        <div class="summary-card balance">
            <h4>Total Balance</h4>
            <div class="amount"><?php echo number_format($total_balance, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></div>
        </div>
        <div class="summary-card count">
            <h4>Total Purchases</h4>
            <div class="amount"><?php echo count($purchases); ?></div>
        </div>
    </div>
</div>

<div class="advanced-filters">
    <h4>Search & Filter</h4>
    <form method="GET" action="">
        <div class="filter-grid">
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Invoice #, supplier, or amount">
            </div>
            <div class="filter-group">
                <label for="supplier_id">Supplier:</label>
                <select id="supplier_id" name="supplier_id">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['id']; ?>" <?php echo $supplier_id == $supplier['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="partial" <?php echo $status === 'partial' ? 'selected' : ''; ?>>Partial</option>
                </select>
            </div>
        </div>
        
        <div style="margin-top: 15px;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="date_from">From Date:</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="filter-group">
                    <label for="date_to">To Date:</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div style="display: flex; gap: 10px; align-items: end;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="history.php" class="btn btn-secondary">Clear All</a>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="history-table">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice #</th>
                <th>Supplier</th>
                <th>Amount</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Payment Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($purchases) > 0): ?>
                <?php foreach ($purchases as $purchase): ?>
                    <?php 
                    $balance = $purchase['total_amount'] - $purchase['paid_amount'];
                    ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($purchase['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($purchase['invoice_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($purchase['supplier_name']); ?></td>
                        <td><?php echo number_format($purchase['total_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></td>
                        <td><?php echo number_format($purchase['paid_amount'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></td>
                        <td><?php echo number_format($balance, 2) . ' ' . getSetting('currency_symbol', 'DH'); ?></td>
                        <td><span class="status-badge status-<?php echo $purchase['status']; ?>"><?php echo ucfirst($purchase['status']); ?></span></td>
                        <td><?php echo htmlspecialchars($purchase['type_of_payment'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-primary">View</a>
                            <?php if ($purchase['status'] === 'pending'): ?>
                                <a href="pay.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-success">Pay</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">No purchases found matching your criteria</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="action-buttons">
    <a href="create.php" class="btn btn-primary">Create New Purchase</a>
    <a href="../suppliers/list.php" class="btn btn-secondary">Manage Suppliers</a>
</div>

<?php require_once '../includes/footer.php'; ?>
