<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();

// Fetch Returns with detailed information including stock impact
$stmt = $pdo->query("
    SELECT 
        sr.id,
        sr.sale_id,
        sr.total_refund,
        sr.reason,
        sr.created_at,
        u.name as user_name,
        c.name as client_name,
        s.invoice_number,
        (SELECT COUNT(*) FROM sale_return_items sri WHERE sri.return_id = sr.id) as items_count,
        (SELECT SUM(sri.quantity) FROM sale_return_items sri WHERE sri.return_id = sr.id) as total_items_returned
    FROM sale_returns sr
    JOIN users u ON sr.user_id = u.id
    JOIN sales s ON sr.sale_id = s.id
    LEFT JOIN clients c ON s.client_id = c.id
    ORDER BY sr.created_at DESC
    LIMIT 50
");
$returns = $stmt->fetchAll();

$pageTitle = __('returns_management');
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo __('returns_management'); ?></h1>
                <p class="text-gray-600"><?php echo __('manage_and_track_all_sales_returns'); ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="create_return.php" 
                   class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors flex items-center space-x-2">
                    <i class="fas fa-plus w-5 h-5"></i>
                    <span><?php echo __('add_return'); ?></span>
                </a>
                <a href="list.php" 
                   class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center space-x-2">
                    <i class="fas fa-list w-5 h-5"></i>
                    <span><?php echo __('view_sales'); ?></span>
                </a>
            </div>
        </div>

        <!-- Returns Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_returns_today'); ?></p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sale_returns WHERE DATE(created_at) = CURDATE()");
                            $stmt->execute();
                            $result = $stmt->fetch();
                            echo $result['count'] ?? 0;
                            ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-undo text-red-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('total_refund_amount'); ?></p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">
                            <?php
                            $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_refund), 0) as total FROM sale_returns WHERE DATE(created_at) = CURDATE()");
                            $stmt->execute();
                            $result = $stmt->fetch();
                            echo number_format($result['total'] ?? 0, 2) . ' ' . getSetting('currency_symbol', 'DH');
                            ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-yellow-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600"><?php echo __('items_returned'); ?></p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT COALESCE(SUM(sri.quantity), 0) as total 
                                FROM sale_return_items sri 
                                JOIN sale_returns sr ON sri.return_id = sr.id 
                                WHERE DATE(sr.created_at) = CURDATE()
                            ");
                            $stmt->execute();
                            $result = $stmt->fetch();
                            echo $result['total'] ?? 0;
                            ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-blue-600 text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Returns Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900"><?php echo __('recent_returns'); ?></h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('id'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('date'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('sale'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('customer'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('cashier'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('refund'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('items'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('reason'); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo __('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($returns) > 0): ?>
                            <?php foreach ($returns as $return): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">#<?php echo $return['id']; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo date('M j, Y H:i', strtotime($return['created_at'])); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">#<?php echo $return['sale_id']; ?></span>
                                    <?php if ($return['invoice_number']): ?>
                                        <br><span class="text-xs text-gray-500">Inv: <?php echo htmlspecialchars($return['invoice_number']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($return['client_name'] ?? 'Walk-in'); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($return['user_name']); ?></span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-red-600">
                                    <?php echo number_format($return['total_refund'], 2) . ' ' . getSetting('currency_symbol', 'DH'); ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-900">
                                    <?php echo $return['total_items_returned'] ?? 0; ?> <?php echo __('items'); ?>
                                    <br><span class="text-xs text-gray-500">(<?php echo $return['items_count'] ?? 0; ?> lines)</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-900" title="<?php echo htmlspecialchars($return['reason']); ?>">
                                        <?php echo htmlspecialchars(substr($return['reason'], 0, 30)) . (strlen($return['reason']) > 30 ? '...' : ''); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="view_return.php?id=<?php echo $return['id']; ?>" 
                                           class="text-green-600 hover:text-green-900 font-medium transition-colors"
                                           title="<?php echo __('view_return_details'); ?>">
                                            <i class="fas fa-search mr-1"></i>
                                            <?php echo __('view'); ?>
                                        </a>
                                        <a href="view.php?id=<?php echo $return['sale_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 font-medium transition-colors"
                                           title="<?php echo __('view_original_sale'); ?>">
                                            <i class="fas fa-eye mr-1"></i>
                                            <?php echo __('sale_label'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2"><?php echo __('no_returns_found'); ?></h3>
                                <p class="text-gray-500"><?php echo __('no_returns_have_been_recorded_yet'); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>