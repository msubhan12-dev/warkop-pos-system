<?php
require_once '../config/config.php';
requireRole(['owner', 'admin', 'kasir']);

// Prevent browser caching of order statuses
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$pageTitle = 'Pesanan';
$user = getCurrentUser();
$db = getDB();

// Check if viewing cleansing page (owner only)
$tab = $_GET['tab'] ?? 'orders'; // 'orders', 'unpaid', 'rejected', or 'cleansing'
if ($tab === 'cleansing' && $user['role'] !== 'owner') {
    $tab = 'orders'; // Force back to orders if not owner
}

// View detail
$detailId = $_GET['detail'] ?? null;
$orderDetail = null;
$orderItems = [];
$paymentDetail = null;

if ($detailId) {
    $stmt = $db->prepare("SELECT o.*, t.table_number, u.full_name as kasir_name FROM orders o LEFT JOIN tables t ON o.table_id = t.id LEFT JOIN users u ON o.created_by = u.id WHERE o.id = ?");
    $stmt->execute([$detailId]);
    $orderDetail = $stmt->fetch();
    
    $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$detailId]);
    $orderItems = $stmt->fetchAll();
    
    // Get payment details
    $paymentDetail = getPaymentDetails($detailId);
}

$filter = $_GET['filter'] ?? 'today';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = "";
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$whereClause = "";
if ($filter === 'today') {
    $whereClause = "WHERE DATE(o.created_at) = '$today'";
} else if ($filter === 'yesterday') {
    $whereClause = "WHERE DATE(o.created_at) = '$yesterday'";
} else if ($filter === 'this_week') {
    // MySQL YEARWEEK might also be affected by timezone, but let's stick to DATE for safety if possible,
    // or just use PHP to get start and end of week.
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    $whereClause = "WHERE DATE(o.created_at) >= '$startOfWeek' AND DATE(o.created_at) <= '$endOfWeek'";
} else if ($filter === 'this_month') {
    $currentMonth = date('m');
    $currentYear = date('Y');
    $whereClause = "WHERE MONTH(o.created_at) = '$currentMonth' AND YEAR(o.created_at) = '$currentYear'";
}

// Get total for pagination
$stmt = $db->query("SELECT COUNT(*) FROM orders o $whereClause");
$totalOrders = $stmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

$stmt = $db->query("SELECT o.*, t.table_number FROM orders o LEFT JOIN tables t ON o.table_id = t.id $whereClause ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset");
$orders = $stmt->fetchAll();

// Cleansing tab data (grouped by customer name, paginated, owner only)
$cleansingOrders = [];
$cleansingPage = max(1, intval($_GET['cpage'] ?? 1));
$cleansingLimit = 10;
$cleansingOffset = ($cleansingPage - 1) * $cleansingLimit;

if ($tab === 'cleansing' && $user['role'] === 'owner') {
    // Get grouped customers with order counts
    $stmt = $db->query("
        SELECT 
            customer_name,
            COUNT(*) as order_count,
            SUM(total) as total_amount,
            MAX(created_at) as latest_order
        FROM orders 
        GROUP BY LOWER(customer_name)
        ORDER BY customer_name ASC
    ");
    $groupedCustomers = $stmt->fetchAll();
    $totalCleansingOrders = count($groupedCustomers);
    $totalCleansingPages = ceil($totalCleansingOrders / $cleansingLimit);
    
    // Paginate grouped results
    $paginatedCustomers = array_slice($groupedCustomers, $cleansingOffset, $cleansingLimit);
    
    // For each customer, get their orders
    foreach ($paginatedCustomers as $customer) {
        $stmt = $db->prepare("
            SELECT id, order_number, created_at, total, status
            FROM orders 
            WHERE LOWER(customer_name) = LOWER(?)
            ORDER BY created_at DESC
        ");
        $stmt->execute([$customer['customer_name']]);
        $customer['orders'] = $stmt->fetchAll();
        $cleansingOrders[] = $customer;
    }
}

// Unpaid orders tab data - ONLY orders that are truly unpaid
$unpaidOrders = [];
$unpaidPage = max(1, intval($_GET['upage'] ?? 1));
$unpaidLimit = 10;
$unpaidOffset = ($unpaidPage - 1) * $unpaidLimit;
$totalUnpaidOrders = 0;
$totalUnpaidPages = 0;

if ($tab === 'unpaid') {
    // Simple approach: Get all orders with their payment status, filter in PHP
    // This is more reliable than complex SQL queries
    
    $stmt = $db->query("
        SELECT o.*, t.table_number, p.payment_method, p.status as payment_status, p.verification_status, p.id as payment_id
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.id
        LEFT JOIN payments p ON o.id = p.order_id
        ORDER BY o.id DESC, p.created_at DESC
    ");
    $allOrdersWithPayments = $stmt->fetchAll();
    
    // Filter for truly unpaid orders
    $unpaidOrdersList = [];
    $processedOrderIds = [];
    
    foreach ($allOrdersWithPayments as $row) {
        $orderId = $row['id'];
        
        // Skip if already processed (we only check first payment per order, which is latest due to ORDER BY)
        if (in_array($orderId, $processedOrderIds)) {
            continue;
        }
        $processedOrderIds[] = $orderId;
        
        // Skip if order is cancelled
        if ($row['status'] === 'cancelled') {
            continue;
        }
        
        $paymentId = $row['payment_id'];
        $paymentMethod = $row['payment_method'];
        $paymentStatus = $row['payment_status'];
        $verificationStatus = $row['verification_status'];
        
        $isUnpaid = false;
        
        // No payment record = unpaid
        if ($paymentId === null) {
            $isUnpaid = true;
        }
        // Cash payment not success = unpaid
        elseif ($paymentMethod === 'cash' && $paymentStatus !== 'success') {
            $isUnpaid = true;
        }
        // QRIS not verified and not rejected = unpaid
        elseif ($paymentMethod === 'qris' && !in_array($verificationStatus, ['verified', 'rejected'])) {
            $isUnpaid = true;
        }
        
        if ($isUnpaid) {
            $unpaidOrdersList[] = $row;
        }
    }
    
    $totalUnpaidOrders = count($unpaidOrdersList);
    $totalUnpaidPages = ceil($totalUnpaidOrders / $unpaidLimit);
    
    // Paginate
    $unpaidOrders = array_slice($unpaidOrdersList, $unpaidOffset, $unpaidLimit);
}

// Rejected orders tab data - QRIS orders that were rejected
$rejectedOrders = [];
$rejectedPage = max(1, intval($_GET['rpage'] ?? 1));
$rejectedLimit = 10;
$rejectedOffset = ($rejectedPage - 1) * $rejectedLimit;
$totalRejectedOrders = 0;
$totalRejectedPages = 0;

if ($tab === 'rejected') {
    // Get all orders with cancelled status or rejected QRIS payments
    $stmt = $db->query("
        SELECT o.*, t.table_number, p.payment_method, p.status as payment_status, p.verification_status, p.verification_notes, p.verified_at, p.verified_by, p.id as payment_id
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.id
        LEFT JOIN payments p ON o.id = p.order_id
        WHERE o.status = 'cancelled' OR (p.payment_method = 'qris' AND p.verification_status = 'rejected')
        ORDER BY o.created_at DESC, p.verified_at DESC
    ");
    $rejectedOrdersList = $stmt->fetchAll();
    
    $totalRejectedOrders = count($rejectedOrdersList);
    $totalRejectedPages = ceil($totalRejectedOrders / $rejectedLimit);
    
    // Paginate
    $rejectedOrders = array_slice($rejectedOrdersList, $rejectedOffset, $rejectedLimit);
}

// Calculate statistics for current query
$totalAmountSum = array_sum(array_column($orders, 'total'));
$pendingOrdersCount = count(array_filter($orders, fn($o) => in_array($o['status'], ['pending', 'cooking', 'ready'])));
$completedOrdersCount = count(array_filter($orders, fn($o) => $o['status'] === 'completed'));
include '../includes/header.php';
?>
<div class="p-3 sm:p-6 max-w-7xl mx-auto pb-32 sm:pb-24 w-full">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 sm:mb-8 gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 font-outfit tracking-tight">Daftar Pesanan & Transaksi</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Pantau dan kelola seluruh alur pesanan kedai secara real-time.</p>
        </div>
        <div class="w-full sm:w-auto flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider"><i class="fas fa-filter mr-1"></i> Periode:</span>
            <select onchange="window.location.href='?tab=<?= $tab ?>&filter='+this.value" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block p-2.5 shadow-sm font-bold transition-all cursor-pointer">
                <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Hari Ini</option>
                <option value="yesterday" <?= $filter === 'yesterday' ? 'selected' : '' ?>>Kemarin</option>
                <option value="this_week" <?= $filter === 'this_week' ? 'selected' : '' ?>>Minggu Ini</option>
                <option value="this_month" <?= $filter === 'this_month' ? 'selected' : '' ?>>Bulan Ini</option>
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Semua Data</option>
            </select>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex gap-3 mb-6 border-b border-slate-200">
        <a href="?tab=orders" class="px-4 py-3 font-bold text-sm border-b-2 transition-colors <?= $tab === 'orders' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
            <i class="fas fa-list mr-2"></i>Pesanan
        </a>
        <a href="?tab=unpaid" class="px-4 py-3 font-bold text-sm border-b-2 transition-colors <?= $tab === 'unpaid' ? 'border-amber-600 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700' ?> inline-flex items-center">
            <i class="fas fa-clock mr-2"></i>Belum Bayar
            <?php 
            // Calculate actual unpaid count using the same logic as in unpaid tab
            $countStmt = $db->query("
                SELECT o.id, o.id as order_id, o.status, p.payment_method, p.status as payment_status, p.verification_status, p.id as payment_id
                FROM orders o
                LEFT JOIN tables t ON o.table_id = t.id
                LEFT JOIN payments p ON o.id = p.order_id
                ORDER BY o.id DESC, p.created_at DESC
            ");
            $allForCount = $countStmt->fetchAll();
            
            $unpaidForBadge = [];
            $processedForBadge = [];
            
            foreach ($allForCount as $row) {
                $orderId = $row['order_id'];
                if (in_array($orderId, $processedForBadge)) continue;
                $processedForBadge[] = $orderId;
                
                // Skip if order is cancelled
                if ($row['status'] === 'cancelled') continue;
                
                $paymentId = $row['payment_id'];
                $paymentMethod = $row['payment_method'];
                $paymentStatus = $row['payment_status'];
                $verificationStatus = $row['verification_status'];
                
                $isUnpaid = false;
                
                if ($paymentId === null) {
                    $isUnpaid = true;
                } elseif ($paymentMethod === 'cash' && $paymentStatus !== 'success') {
                    $isUnpaid = true;
                } elseif ($paymentMethod === 'qris' && !in_array($verificationStatus, ['verified', 'rejected'])) {
                    $isUnpaid = true;
                }
                
                if ($isUnpaid) {
                    $unpaidForBadge[] = $row;
                }
            }
            
            $unpaidCountBadge = count($unpaidForBadge);
            if ($unpaidCountBadge > 0): 
            ?>
            <span class="ml-2 bg-amber-100 text-amber-700 text-xs font-extrabold px-2 py-0.5 rounded-full"><?= $unpaidCountBadge ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=rejected" class="px-4 py-3 font-bold text-sm border-b-2 transition-colors <?= $tab === 'rejected' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500 hover:text-slate-700' ?> inline-flex items-center">
            <i class="fas fa-times-circle mr-2"></i>Dibatalkan/Ditolak
            <?php 
            // Calculate rejected + cancelled count
            $countStmt = $db->query("
                SELECT COUNT(*) as rejected_count
                FROM payments p
                WHERE p.payment_method = 'qris' AND p.verification_status = 'rejected'
                UNION ALL
                SELECT COUNT(*) as rejected_count
                FROM orders o
                WHERE o.status = 'cancelled'
            ");
            $counts = $countStmt->fetchAll();
            $rejectedCount = ($counts[0]['rejected_count'] ?? 0) + ($counts[1]['rejected_count'] ?? 0);
            if ($rejectedCount > 0): 
            ?>
            <span class="ml-2 bg-red-100 text-red-700 text-xs font-extrabold px-2 py-0.5 rounded-full"><?= $rejectedCount ?></span>
            <?php endif; ?>
        </a>
        <?php if ($user['role'] === 'owner'): ?>
        <a href="?tab=cleansing" class="px-4 py-3 font-bold text-sm border-b-2 transition-colors <?= $tab === 'cleansing' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
            <i class="fas fa-trash mr-2"></i>Data Cleaning
        </a>
        <?php endif; ?>
    </div>

    <!-- Statistics Cards -->
    <?php if ($tab === 'orders'): ?>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i class="fas fa-receipt text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Pesanan Tampil</p>
                <p class="text-xl font-extrabold text-slate-800 font-outfit mt-0.5"><?= count($orders) ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i class="fas fa-fire text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dalam Proses</p>
                <p class="text-xl font-extrabold text-amber-600 font-outfit mt-0.5"><?= $pendingOrdersCount ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="fas fa-coins text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Nilai Transaksi</p>
                <p class="text-xl font-extrabold text-emerald-600 font-outfit mt-0.5"><?= formatRupiah($totalAmountSum) ?></p>
            </div>
        </div>
    </div>

    <!-- Search & Quick Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm mb-6 flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" id="orderSearchInput" onkeyup="filterOrderTable()" placeholder="Cari nomor pesanan, nama pemesan, atau meja..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
        </div>

        <div class="sm:w-56">
            <select id="orderStatusFilterSelect" onchange="filterOrderTable()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                <option value="all">Semua Status Pesanan</option>
                <option value="pending">Menunggu</option>
                <option value="cooking">Sedang Dimasak</option>
                <option value="ready">Siap Disajikan</option>
                <option value="completed">Selesai</option>
                <option value="cancelled">Dibatalkan</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-400 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="p-4 sm:p-5">Order ID</th>
                        <th class="p-4 sm:p-5">Pelanggan</th>
                        <th class="p-4 sm:p-5">Waktu</th>
                        <th class="p-4 sm:p-5">Metode</th>
                        <th class="p-4 sm:p-5 text-center hidden">Status</th>
                        <th class="p-4 sm:p-5 text-right">Total</th>
                        <th class="p-4 sm:p-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody" class="divide-y divide-slate-100">
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                            <i class="fas fa-clipboard-list text-3xl mb-3 text-slate-300 block"></i>
                            Tidak ada pesanan ditemukan.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order): 
                        $payment = getPaymentDetails($order['id']);
                        // For QRIS orders, check payment verification status
                        $isQRIS = ($payment && $payment['payment_method'] === 'qris');
                        $isPaymentVerified = ($payment && $payment['verification_status'] === 'verified');
                        $isPaymentRejected = ($payment && $payment['verification_status'] === 'rejected');
                        
                        // For cash orders, get payment status
                        $cashStatus = $payment ? $payment['status'] : 'pending';
                        
                        // Determine display status based on payment verification for QRIS orders
                        $displayStatus = $order['status'];
                        if ($isQRIS && $isPaymentVerified) {
                            $displayStatus = 'pending'; // Show as pending to cook (verified payment)
                        } elseif ($isQRIS && $isPaymentRejected) {
                            $displayStatus = 'rejected'; // Show as rejected
                        } elseif ($isQRIS && $order['status'] === 'confirmed') {
                            $displayStatus = 'confirmed'; // Still waiting for payment
                        }
                    ?>
                    <tr class="order-row hover:bg-slate-50 transition-colors duration-200 <?= $isPaymentVerified ? 'bg-emerald-50/40 border-l-4 border-emerald-500' : '' ?>"
                        data-ordernum="<?= htmlspecialchars(strtolower($order['order_number'])) ?>"
                        data-customer="<?= htmlspecialchars(strtolower($order['customer_name'])) ?>"
                        data-table="<?= htmlspecialchars(strtolower($order['table_number'] ?? 'ta')) ?>"
                        data-status="<?= htmlspecialchars(strtolower($displayStatus)) ?>">
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center font-bold text-sm <?= $isPaymentVerified ? 'bg-emerald-100 text-emerald-600' : 'bg-emerald-50 text-emerald-500' ?>">
                                    <i class="fas <?= $isPaymentVerified ? 'fa-check-circle' : 'fa-receipt' ?>"></i>
                                </div>
                                <?php
                                    // Format order number for display (e.g. ORD-20260713-ABCD)
                                    $onum = $order['order_number'];
                                    $formattedNum = substr($onum, 0, 3) . '-' . substr($onum, 3, 8) . '-' . substr($onum, 11);
                                ?>
                                <span class="font-bold text-slate-800 font-outfit tracking-wide"><?= $formattedNum ?></span>
                            </div>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <p class="font-bold text-slate-700"><?= htmlspecialchars($order['customer_name']) ?></p>
                            <p class="text-[11px] mt-1">
                                <?php if (($order['order_type'] ?? 'dine_in') === 'dine_in'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-wide">
                                        <i class="fas fa-chair mr-1"></i> Dine In (Meja <?= $order['table_number'] ?? '-' ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wide">
                                        <i class="fas fa-shopping-bag mr-1"></i> Take Away
                                    </span>
                                <?php endif; ?>
                            </p>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-sm font-medium text-slate-500">
                            <?= formatDateTime($order['created_at']) ?>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <?php if ($payment && $payment['payment_method'] === 'qris'): ?>
                            <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-lg bg-blue-50 text-blue-600 border border-blue-200 uppercase tracking-wide inline-block">
                                <i class="fas fa-qrcode mr-1"></i> QRIS
                            </span>
                            <?php else: ?>
                            <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 uppercase tracking-wide inline-block">
                                <i class="fas fa-money-bill-wave mr-1"></i> TUNAI
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($isQRIS && $isPaymentVerified): ?>
                            <div class="mt-1.5">
                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-100/80 px-2 py-0.5 rounded shadow-sm inline-flex items-center">
                                    <i class="fas fa-check-circle mr-1"></i> Terverifikasi
                                </span>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-center hidden">
                        <td class="p-4 sm:p-5 whitespace-nowrap text-right">
                            <span class="font-extrabold text-emerald-600 text-lg font-outfit"><?= formatRupiah($order['total']) ?></span>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <?php 
                                    // Use original $payment variable fetched at loop start
                                    $isCashPayment = $payment && $payment['payment_method'] !== 'qris';
                                    $paymentId = $payment ? $payment['id'] : 0;
                                ?>
                                <?php if ($isCashPayment && $paymentId): ?>
                                    <!-- Cash Payment Action Buttons -->
                                    <?php if ($cashStatus === 'pending'): ?>
                                    <button onclick="setCashPaymentStatus(<?= $paymentId ?>, 'success')" class="inline-flex items-center justify-center bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-300 font-bold text-xs px-2.5 py-1.5 rounded-lg transition-all duration-300 shadow-sm hover:-translate-y-0.5 whitespace-nowrap" title="Klik untuk mengonfirmasi pembayaran tunai">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Belum Bayar (Tandai Lunas)
                                    </button>
                                    <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1.5 rounded-lg border border-emerald-200">
                                        <i class="fas fa-check-circle mr-1"></i> ✓ Lunas
                                    </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button onclick="printReceipt(<?= $order['id'] ?>)" class="inline-flex items-center justify-center bg-stone-100 hover:bg-stone-200 text-stone-600 border border-stone-300 font-bold w-9 h-9 rounded-xl transition-all duration-300 shadow-sm hover:-translate-y-0.5" title="Print Struk">
                                    <i class="fas fa-print text-sm"></i>
                                </button>
                                <a href="?detail=<?= $order['id'] ?>&filter=<?= $filter ?>&t=<?= time() ?>" class="inline-flex items-center justify-center bg-slate-800 hover:bg-slate-900 text-white font-bold w-9 h-9 rounded-xl transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5" title="Lihat Detail">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination UI -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 bg-white border-t border-slate-200 flex items-center justify-between sm:flex-row flex-col gap-4">
            <div class="text-sm font-semibold text-slate-500">
                Menampilkan <span class="text-slate-800"><?= $offset + 1 ?></span> sampai <span class="text-slate-800"><?= min($offset + $limit, $totalOrders) ?></span> dari <span class="text-slate-800"><?= $totalOrders ?></span> pesanan
            </div>
            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                <a href="?filter=<?= $filter ?>&page=<?= $page - 1 ?>" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-emerald-600 transition-colors shadow-sm">
                    <i class="fas fa-chevron-left text-sm"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?filter=<?= $filter ?>&page=<?= $i ?>" class="w-10 h-10 flex items-center justify-center rounded-xl text-sm font-bold transition-colors shadow-sm <?= $i === $page ? 'bg-emerald-600 text-white border-none' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-emerald-600' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?filter=<?= $filter ?>&page=<?= $page + 1 ?>" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-emerald-600 transition-colors shadow-sm">
                    <i class="fas fa-chevron-right text-sm"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?> <!-- End of Orders Tab -->

    <!-- Unpaid Orders Tab -->
    <?php if ($tab === 'unpaid'): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center shrink-0 font-bold text-lg">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <h3 class="font-bold text-amber-900 mb-1">Pesanan Belum Bayar</h3>
                <p class="text-sm text-amber-800">Daftar pesanan yang sudah diterima namun belum terbayar. Customer dapat membayar langsung di tempat atau melalui QRIS.</p>
            </div>
        </div>
    </div>

    <!-- Search & Quick Filter Bar for Unpaid -->
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm mb-6 flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" id="unpaidSearchInput" onkeyup="filterUnpaidTable()" placeholder="Cari nomor pesanan, nama pemesan, atau meja..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
        </div>

        <div class="sm:w-56">
            <select id="unpaidMethodFilterSelect" onchange="filterUnpaidTable()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                <option value="all">Semua Metode</option>
                <option value="qris">QRIS</option>
                <option value="cash">Tunai</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-400 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="p-4 sm:p-5">Order ID</th>
                        <th class="p-4 sm:p-5">Pelanggan</th>
                        <th class="p-4 sm:p-5">Waktu</th>
                        <th class="p-4 sm:p-5">Metode</th>
                        <th class="p-4 sm:p-5 text-right">Total</th>
                        <th class="p-4 sm:p-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="unpaidTableBody" class="divide-y divide-slate-100">
                    <?php if (empty($unpaidOrders)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 font-medium">
                            <i class="fas fa-check-circle text-3xl mb-3 text-emerald-300 block"></i>
                            Semua pesanan sudah terbayar! 🎉
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($unpaidOrders as $order): 
                        $payment = getPaymentDetails($order['id']);
                        $isQRIS = ($payment && $payment['payment_method'] === 'qris');
                        $isCashPayment = $payment && $payment['payment_method'] !== 'qris';
                        $cashStatus = $payment ? $payment['status'] : 'pending';
                        $paymentId = $payment ? $payment['id'] : 0;
                    ?>
                    <tr class="unpaid-row hover:bg-amber-50/40 transition-colors duration-200"
                        data-ordernum="<?= htmlspecialchars(strtolower($order['order_number'])) ?>"
                        data-customer="<?= htmlspecialchars(strtolower($order['customer_name'])) ?>"
                        data-table="<?= htmlspecialchars(strtolower($order['table_number'] ?? 'ta')) ?>"
                        data-method="<?= $isQRIS ? 'qris' : 'cash' ?>">
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center font-bold text-sm bg-amber-100 text-amber-600">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <?php
                                    $onum = $order['order_number'];
                                    $formattedNum = substr($onum, 0, 3) . '-' . substr($onum, 3, 8) . '-' . substr($onum, 11);
                                ?>
                                <span class="font-bold text-slate-800 font-outfit tracking-wide"><?= $formattedNum ?></span>
                            </div>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <p class="font-bold text-slate-700"><?= htmlspecialchars($order['customer_name']) ?></p>
                            <p class="text-[11px] mt-1">
                                <?php if (($order['order_type'] ?? 'dine_in') === 'dine_in'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-wide">
                                        <i class="fas fa-chair mr-1"></i> Dine In (Meja <?= $order['table_number'] ?? '-' ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wide">
                                        <i class="fas fa-shopping-bag mr-1"></i> Take Away
                                    </span>
                                <?php endif; ?>
                            </p>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-sm font-medium text-slate-500">
                            <?= formatDateTime($order['created_at']) ?>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <?php if ($isQRIS): ?>
                            <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-lg bg-blue-50 text-blue-600 border border-blue-200 uppercase tracking-wide inline-block">
                                <i class="fas fa-qrcode mr-1"></i> QRIS
                            </span>
                            <?php else: ?>
                            <span class="px-2.5 py-1 text-[11px] font-extrabold rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 uppercase tracking-wide inline-block">
                                <i class="fas fa-money-bill-wave mr-1"></i> TUNAI
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-right">
                            <span class="font-extrabold text-amber-600 text-lg font-outfit"><?= formatRupiah($order['total']) ?></span>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <?php 
                                    // Check if this is truly an unpaid order:
                                    // 1. No payment record OR
                                    // 2. Cash payment pending OR  
                                    // 3. QRIS payment not verified
                                    $hasNoPayment = !$payment;
                                    $isCashPending = ($payment && $payment['payment_method'] === 'cash' && $payment['status'] === 'pending');
                                    $isQRISPending = ($payment && $payment['payment_method'] === 'qris' && $payment['verification_status'] !== 'verified');
                                ?>
                                
                                <?php if ($hasNoPayment): ?>
                                    <!-- No payment record - show info badge, need to create payment first -->
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1.5 rounded-lg border border-slate-300">
                                        <i class="fas fa-question-circle"></i> Perlu Input Pembayaran
                                    </span>
                                <?php elseif ($isCashPending): ?>
                                    <!-- Cash payment waiting - show mark as paid button -->
                                    <button onclick="setCashPaymentStatus(<?= $paymentId ?>, 'success')" class="inline-flex items-center justify-center bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-300 font-bold text-xs px-2.5 py-1.5 rounded-lg transition-all duration-300 shadow-sm hover:-translate-y-0.5 whitespace-nowrap" title="Klik untuk mengonfirmasi pembayaran tunai">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Belum Bayar (Tandai Lunas)
                                    </button>
                                <?php elseif ($isQRISPending): ?>
                                    <!-- QRIS payment waiting for verification -->
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-200">
                                        <i class="fas fa-qrcode mr-1"></i> <i class="fas fa-clock-circle animate-spin"></i> Menunggu Verifikasi
                                    </span>
                                <?php endif; ?>
                                <button onclick="printReceipt(<?= $order['id'] ?>)" class="inline-flex items-center justify-center bg-stone-100 hover:bg-stone-200 text-stone-600 border border-stone-300 font-bold w-9 h-9 rounded-xl transition-all duration-300 shadow-sm hover:-translate-y-0.5" title="Print Struk">
                                    <i class="fas fa-print text-sm"></i>
                                </button>
                                <a href="?tab=unpaid&detail=<?= $order['id'] ?>&filter=<?= $filter ?>&t=<?= time() ?>" class="inline-flex items-center justify-center bg-slate-800 hover:bg-slate-900 text-white font-bold w-9 h-9 rounded-xl transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5" title="Lihat Detail">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Unpaid Pagination UI -->
        <?php if ($totalUnpaidPages > 1): ?>
        <div class="px-6 py-4 bg-white border-t border-slate-200 flex items-center justify-between sm:flex-row flex-col gap-4">
            <div class="text-sm font-semibold text-slate-500">
                Menampilkan <span class="text-slate-800"><?= $unpaidOffset + 1 ?></span> sampai <span class="text-slate-800"><?= min($unpaidOffset + $unpaidLimit, $totalUnpaidOrders) ?></span> dari <span class="text-slate-800"><?= $totalUnpaidOrders ?></span> pesanan belum bayar
            </div>
            <div class="flex items-center gap-1">
                <?php if ($unpaidPage > 1): ?>
                <a href="?tab=unpaid&filter=<?= $filter ?>&upage=<?= $unpaidPage - 1 ?>" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-amber-600 transition-colors shadow-sm">
                    <i class="fas fa-chevron-left text-sm"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $unpaidPage - 2); $i <= min($totalUnpaidPages, $unpaidPage + 2); $i++): ?>
                <a href="?tab=unpaid&filter=<?= $filter ?>&upage=<?= $i ?>" class="w-10 h-10 flex items-center justify-center rounded-xl text-sm font-bold transition-colors shadow-sm <?= $i === $unpaidPage ? 'bg-amber-600 text-white border-none' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-amber-600' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($unpaidPage < $totalUnpaidPages): ?>
                <a href="?tab=unpaid&filter=<?= $filter ?>&upage=<?= $unpaidPage + 1 ?>" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-amber-600 transition-colors shadow-sm">
                    <i class="fas fa-chevron-right text-sm"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?> <!-- End of Unpaid Tab -->

    <!-- Rejected Orders Tab -->
    <?php if ($tab === 'rejected'): ?>
    <div class="bg-red-50 border border-red-200 rounded-2xl p-6 mb-6">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-red-100 text-red-600 rounded-full flex items-center justify-center shrink-0 font-bold text-lg">
                <i class="fas fa-times-circle"></i>
            </div>
            <div>
                <h3 class="font-bold text-red-900 mb-1">Pesanan Dibatalkan & QRIS Ditolak</h3>
                <p class="text-sm text-red-800">Daftar pesanan yang dibatalkan atau pembayaran QRIS yang ditolak. Untuk pembayaran QRIS, pelanggan dapat mengirimkan ulang bukti atau menggunakan metode lain.</p>
            </div>
        </div>
    </div>

    <!-- Search & Quick Filter Bar for Rejected -->
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm mb-6 flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" id="rejectedSearchInput" onkeyup="filterRejectedTable()" placeholder="Cari nomor pesanan, nama pemesan, atau meja..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-400 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="p-4 sm:p-5">Order ID</th>
                        <th class="p-4 sm:p-5">Pelanggan</th>
                        <th class="p-4 sm:p-5">Waktu</th>
                        <th class="p-4 sm:p-5">Status</th>
                        <th class="p-4 sm:p-5 text-right">Total</th>
                        <th class="p-4 sm:p-5">Alasan/Catatan</th>
                        <th class="p-4 sm:p-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="rejectedTableBody" class="divide-y divide-slate-100">
                    <?php if (empty($rejectedOrders)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                            <i class="fas fa-check-circle text-3xl mb-3 text-emerald-300 block"></i>
                            Tidak ada pesanan yang ditolak! 🎉
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($rejectedOrders as $order): 
                        $onum = $order['order_number'];
                        $formattedNum = substr($onum, 0, 3) . '-' . substr($onum, 3, 8) . '-' . substr($onum, 11);
                    ?>
                    <tr class="rejected-row hover:bg-red-50/30 transition-colors duration-200"
                        data-ordernum="<?= htmlspecialchars(strtolower($order['order_number'])) ?>"
                        data-customer="<?= htmlspecialchars(strtolower($order['customer_name'])) ?>"
                        data-table="<?= htmlspecialchars(strtolower($order['table_number'] ?? 'ta')) ?>">
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center font-bold text-sm bg-red-100 text-red-600">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <span class="font-bold text-slate-800 font-outfit tracking-wide"><?= $formattedNum ?></span>
                            </div>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <p class="font-bold text-slate-700"><?= htmlspecialchars($order['customer_name']) ?></p>
                            <p class="text-[11px] mt-1">
                                <?php if (($order['order_type'] ?? 'dine_in') === 'dine_in'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-wide">
                                        <i class="fas fa-chair mr-1"></i> Dine In (Meja <?= $order['table_number'] ?? '-' ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wide">
                                        <i class="fas fa-shopping-bag mr-1"></i> Take Away
                                    </span>
                                <?php endif; ?>
                            </p>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-sm font-medium text-slate-500">
                            <?= formatDateTime($order['created_at']) ?>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <?php 
                            // Determine status type: cancelled or rejected QRIS
                            $isCancelled = $order['status'] === 'cancelled';
                            $isRejected = $order['verification_status'] === 'rejected';
                            ?>
                            <?php if ($isCancelled): ?>
                            <span class="px-3 py-1.5 text-xs font-extrabold rounded-lg bg-orange-100 text-orange-700 border border-orange-300 inline-flex items-center">
                                <i class="fas fa-ban mr-1.5"></i> Dibatalkan
                            </span>
                            <?php elseif ($isRejected): ?>
                            <span class="px-3 py-1.5 text-xs font-extrabold rounded-lg bg-red-100 text-red-700 border border-red-300 inline-flex items-center">
                                <i class="fas fa-times-circle mr-1.5"></i> Ditolak QRIS
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-right">
                            <span class="font-extrabold text-slate-800 text-lg font-outfit"><?= formatRupiah($order['total']) ?></span>
                        </td>
                        <td class="p-4 sm:p-5">
                            <div class="max-w-xs">
                                <?php if ($isCancelled): ?>
                                <div class="bg-orange-50 border border-orange-200 rounded-lg p-2 text-xs text-orange-700 font-medium">
                                    <p class="line-clamp-2">Pesanan dibatalkan</p>
                                </div>
                                <?php else: ?>
                                <div class="bg-red-50 border border-red-200 rounded-lg p-2 text-xs text-red-700 font-medium">
                                    <p class="line-clamp-2"><?= htmlspecialchars($order['verification_notes'] ?? 'Tidak ada alasan') ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="printReceipt(<?= $order['id'] ?>)" class="inline-flex items-center justify-center bg-stone-100 hover:bg-stone-200 text-stone-600 border border-stone-300 font-bold w-9 h-9 rounded-xl transition-all duration-300 shadow-sm hover:-translate-y-0.5" title="Print Struk">
                                    <i class="fas fa-print text-sm"></i>
                                </button>
                                <a href="?tab=rejected&detail=<?= $order['id'] ?>&filter=<?= $filter ?>&t=<?= time() ?>" class="inline-flex items-center justify-center bg-slate-800 hover:bg-slate-900 text-white font-bold w-9 h-9 rounded-xl transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5" title="Lihat Detail">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Rejected Pagination UI -->
        <?php if ($totalRejectedPages > 1): ?>
        <div class="px-6 py-4 bg-white border-t border-slate-200 flex items-center justify-between sm:flex-row flex-col gap-4">
            <div class="text-sm font-semibold text-slate-500">
                Menampilkan <span class="text-slate-800"><?= $rejectedOffset + 1 ?></span> sampai <span class="text-slate-800"><?= min($rejectedOffset + $rejectedLimit, $totalRejectedOrders) ?></span> dari <span class="text-slate-800"><?= $totalRejectedOrders ?></span> pesanan ditolak
            </div>
            <div class="flex items-center gap-1">
                <?php if ($rejectedPage > 1): ?>
                <a href="?tab=rejected&filter=<?= $filter ?>&rpage=<?= $rejectedPage - 1 ?>" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-red-600 transition-colors shadow-sm">
                    <i class="fas fa-chevron-left text-sm"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $rejectedPage - 2); $i <= min($totalRejectedPages, $rejectedPage + 2); $i++): ?>
                <a href="?tab=rejected&filter=<?= $filter ?>&rpage=<?= $i ?>" class="w-10 h-10 flex items-center justify-center rounded-xl text-sm font-bold transition-colors shadow-sm <?= $i === $rejectedPage ? 'bg-red-600 text-white border-none' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-red-600' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($rejectedPage < $totalRejectedPages): ?>
                <a href="?tab=rejected&filter=<?= $filter ?>&rpage=<?= $rejectedPage + 1 ?>" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-red-600 transition-colors shadow-sm">
                    <i class="fas fa-chevron-right text-sm"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?> <!-- End of Rejected Tab -->

    <!-- Cleansing Tab -->
    <?php if ($tab === 'cleansing' && $user['role'] === 'owner'): ?>
    <div class="bg-red-50 border border-red-200 rounded-2xl p-6 mb-6">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-red-100 text-red-600 rounded-full flex items-center justify-center shrink-0 font-bold text-lg">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h3 class="font-bold text-red-900 mb-1">Zona Berbahaya - Data Cleaning</h3>
                <p class="text-sm text-red-800">Fitur ini hanya untuk owner. Hapus pesanan dummy atau data test. <strong>Aksi ini tidak dapat dibatalkan!</strong></p>
            </div>
        </div>
    </div>

    <!-- Search Bar for Cleansing -->
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm mb-6 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
        <div class="relative flex-1 w-full sm:w-auto">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" id="cleansingSearchInput" onkeyup="filterCleansingTable()" placeholder="Cari nama pelanggan..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button onclick="selectAllCleansing()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fas fa-check-square mr-1"></i> Pilih Semua
            </button>
            <button onclick="bulkDeleteCleansing()" id="bulkDeleteBtn" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <i class="fas fa-trash mr-1"></i> Hapus Dipilih
            </button>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-400 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="p-4 sm:p-5 w-12 text-center">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="w-4 h-4 accent-red-600 cursor-pointer">
                        </th>
                        <th class="p-4 sm:p-5 w-16">Buka</th>
                        <th class="p-4 sm:p-5">Nama Pelanggan</th>
                        <th class="p-4 sm:p-5 text-center">Jumlah Pesanan</th>
                        <th class="p-4 sm:p-5">Total Nilai</th>
                        <th class="p-4 sm:p-5">Pesanan Terakhir</th>
                        <th class="p-4 sm:p-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="cleansingTableBody" class="divide-y divide-slate-100">
                    <?php if (empty($cleansingOrders)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                            <i class="fas fa-inbox text-3xl mb-3 text-slate-300 block"></i>
                            Tidak ada pesanan ditemukan.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($cleansingOrders as $customerGroup): 
                        $customerHash = md5(strtolower($customerGroup['customer_name']));
                    ?>
                    <!-- Customer Group Row -->
                    <tr class="cleansing-row hover:bg-red-50/30 transition-colors"
                        data-customer="<?= htmlspecialchars(strtolower($customerGroup['customer_name'])) ?>"
                        data-hash="<?= $customerHash ?>">
                        <td class="p-4 sm:p-5 w-12 text-center">
                            <input type="checkbox" class="cleansing-checkbox w-4 h-4 accent-red-600 cursor-pointer customer-checkbox" 
                                   data-customer-hash="<?= $customerHash ?>" onchange="updateBulkDeleteButton()">
                        </td>
                        <td class="p-4 sm:p-5 w-16 text-center">
                            <button onclick="toggleOrderDetails('<?= $customerHash ?>')" class="inline-flex items-center justify-center w-8 h-8 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition" title="Buka/Tutup Detail">
                                <i class="fas fa-chevron-down toggle-icon" id="icon-<?= $customerHash ?>"></i>
                            </button>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <p class="font-bold text-slate-800 text-base"><?= htmlspecialchars($customerGroup['customer_name']) ?></p>
                        </td>
                        <td class="p-4 sm:p-5 text-center">
                            <span class="inline-flex items-center justify-center bg-slate-100 text-slate-700 font-bold px-3 py-1 rounded-lg text-sm">
                                <?= $customerGroup['order_count'] ?> pesanan
                            </span>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <span class="font-extrabold text-emerald-600 text-base"><?= formatRupiah($customerGroup['total_amount']) ?></span>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-sm text-slate-500">
                            <?= formatDateTime($customerGroup['latest_order']) ?>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-center">
                            <button onclick="bulkDeleteByCustomer('<?= htmlspecialchars($customerGroup['customer_name']) ?>', <?= $customerGroup['order_count'] ?>)" class="inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white font-bold px-3 py-1.5 rounded-lg transition-all shadow-sm text-xs">
                                <i class="fas fa-trash-alt mr-1"></i> Hapus Semua
                            </button>
                        </td>
                    </tr>
                    
                    <!-- Expandable Order Details Row -->
                    <tr class="order-details-row hidden" id="details-<?= $customerHash ?>">
                        <td colspan="7" class="p-0">
                            <div class="bg-slate-50 border-t border-b border-slate-200">
                                <div class="p-4 sm:p-5">
                                    <h4 class="font-bold text-slate-700 mb-3 text-sm uppercase tracking-wide">Riwayat Pesanan dari <?= htmlspecialchars($customerGroup['customer_name']) ?></h4>
                                    <div class="space-y-2 max-h-96 overflow-y-auto">
                                        <?php foreach ($customerGroup['orders'] as $order): ?>
                                        <div class="bg-white border border-slate-200 rounded-lg p-3 flex items-center justify-between hover:shadow-sm transition">
                                            <div class="flex-1">
                                                <p class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($order['order_number']) ?></p>
                                                <p class="text-xs text-slate-500 mt-0.5">
                                                    <i class="fas fa-calendar-alt mr-1"></i> <?= formatDateTime($order['created_at']) ?>
                                                    <span class="mx-2">•</span>
                                                    <span class="font-semibold"><?= formatRupiah($order['total']) ?></span>
                                                </p>
                                            </div>
                                            <span class="text-xs font-bold px-2 py-1 rounded-md <?= $order['status'] === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Cleansing Pagination -->
        <?php if (isset($totalCleansingPages) && $totalCleansingPages > 1): ?>
        <div class="px-6 py-4 bg-white border-t border-slate-200 flex items-center justify-between sm:flex-row flex-col gap-4">
            <div class="text-sm font-semibold text-slate-500">
                Menampilkan <span class="text-slate-800"><?= $cleansingOffset + 1 ?></span> sampai <span class="text-slate-800"><?= min($cleansingOffset + $cleansingLimit, $totalCleansingOrders) ?></span> dari <span class="text-slate-800"><?= $totalCleansingOrders ?></span> pesanan
            </div>
            <div class="flex items-center gap-1">
                <?php if ($cleansingPage > 1): ?>
                <a href="?tab=cleansing&cpage=<?= $cleansingPage - 1 ?>" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-red-600 transition-colors shadow-sm">
                    <i class="fas fa-chevron-left text-sm"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $cleansingPage - 2); $i <= min($totalCleansingPages, $cleansingPage + 2); $i++): ?>
                <a href="?tab=cleansing&cpage=<?= $i ?>" class="w-10 h-10 flex items-center justify-center rounded-xl text-sm font-bold transition-colors shadow-sm <?= $i === $cleansingPage ? 'bg-red-600 text-white border-none' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-red-600' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($cleansingPage < $totalCleansingPages): ?>
                <a href="?tab=cleansing&cpage=<?= $cleansingPage + 1 ?>" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-red-600 transition-colors shadow-sm">
                    <i class="fas fa-chevron-right text-sm"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?> <!-- End of Cleansing Tab -->
</div>

<!-- Detail Modal -->
<?php if ($orderDetail): ?>
<div class="fixed inset-0 bg-stone-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-stone-50 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-stone-200">
        <!-- Modal Header -->
        <div class="bg-white px-4 sm:px-6 py-3 sm:py-4 border-b border-stone-200 flex justify-between items-center z-10 shadow-sm">
            <h3 class="text-lg sm:text-xl font-extrabold text-stone-850 font-outfit flex items-center">
                <i class="fas fa-file-invoice text-emerald-600 mr-2.5"></i> Detail Pesanan
            </h3>
            <a href="?tab=<?= htmlspecialchars($tab) ?>" class="w-8 h-8 flex items-center justify-center rounded-full bg-stone-100 text-stone-500 hover:bg-red-100 hover:text-red-600 transition duration-200">
                <i class="fas fa-times text-lg"></i>
            </a>
        </div>
        
        <!-- Modal Content Scrollable -->
        <div class="p-4 sm:p-6 overflow-y-auto flex-1">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 sm:gap-6">
                <!-- Left Column (Order Info & Items) -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- Order Info Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-5 border border-stone-200 shadow-sm">
                        <div class="flex justify-between items-start mb-4 pb-4 border-b border-stone-100">
                            <div>
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">No. Pesanan</p>
                                <?php
                                    $mOnum = $orderDetail['order_number'];
                                    $mFormattedNum = substr($mOnum, 0, 3) . '-' . substr($mOnum, 3, 8) . '-' . substr($mOnum, 11);
                                ?>
                                <p class="font-extrabold text-xl text-stone-850 font-outfit tracking-wide"><?= $mFormattedNum ?></p>
                            </div>
                            <span class="px-3 py-1 text-xs font-extrabold rounded-lg shadow-sm border uppercase tracking-wide <?= getStatusBadge($orderDetail['status']) ?>">
                                <?= getStatusText($orderDetail['status']) ?>
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">Customer</p>
                                <p class="font-bold text-stone-800"><?= htmlspecialchars($orderDetail['customer_name']) ?></p>
                                <?php if ($orderDetail['customer_phone']): ?>
                                <p class="text-sm text-stone-500 font-medium mt-0.5"><i class="fas fa-phone-alt text-xs mr-1 text-stone-300"></i><?= htmlspecialchars($orderDetail['customer_phone']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">Tipe Pesanan / Layanan</p>
                                <p class="mt-1">
                                    <?php if ($orderDetail['order_type'] === 'dine_in'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-wider">
                                            <i class="fas fa-chair mr-1.5"></i> Makan di Tempat (Meja <?= $orderDetail['table_number'] ?>)
                                        </span>
                                    <?php elseif ($orderDetail['order_type'] === 'delivery'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wider">
                                            <i class="fas fa-motorcycle mr-1.5"></i> Delivery
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-extrabold bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wider">
                                            <i class="fas fa-shopping-bag mr-1.5"></i> Bawa Pulang (Take Away)
                                        </span>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($orderDetail['order_type'] === 'delivery' && !empty($orderDetail['delivery_address'])): ?>
                                    <div class="mt-2 bg-stone-50 border border-stone-200 rounded-lg p-2.5">
                                        <p class="text-xs font-bold text-stone-500 mb-1"><i class="fas fa-map-marker-alt text-emerald-500 mr-1"></i> Alamat Pengiriman:</p>
                                        <p class="text-xs text-stone-700 font-medium leading-relaxed"><?= nl2br(htmlspecialchars($orderDetail['delivery_address'])) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php 
                                    $kasirName = '-';
                                    if (!empty($orderDetail['kasir_name'])) {
                                        $kasirName = $orderDetail['kasir_name'];
                                    } elseif (!empty($paymentDetail['verified_by_name'])) {
                                        $kasirName = $paymentDetail['verified_by_name'];
                                    }
                                ?>
                                <p class="text-sm text-stone-500 font-medium mt-2"><i class="fas fa-user-tag text-xs mr-1 text-stone-300"></i> Kasir: <?= htmlspecialchars($kasirName) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-5 border border-stone-200 shadow-sm">
                        <h4 class="text-sm font-extrabold text-stone-700 uppercase tracking-wider mb-4 flex items-center">
                            <i class="fas fa-shopping-basket text-emerald-500 mr-2"></i> Daftar Menu
                        </h4>
                        
                        <div class="space-y-3 mb-4">
                            <?php foreach ($orderItems as $item): ?>
                            <div class="flex justify-between items-center text-sm p-3 bg-stone-50 rounded-lg border border-stone-100">
                                <div class="flex items-center">
                                    <span class="w-6 h-6 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs mr-3"><?= $item['quantity'] ?>x</span>
                                    <span class="font-bold text-stone-700"><?= htmlspecialchars($item['menu_name']) ?></span>
                                </div>
                                <span class="font-extrabold text-stone-800"><?= formatRupiah($item['subtotal']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="flex justify-between items-center pt-4 border-t border-dashed border-stone-300">
                            <span class="font-extrabold text-stone-500 text-sm sm:text-lg">TOTAL TAGIHAN</span>
                            <span class="text-xl sm:text-2xl font-extrabold text-emerald-600 font-outfit"><?= formatRupiah($orderDetail['total']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column (Payment Info) -->
                <div class="lg:col-span-2 space-y-6">
                    <?php if ($paymentDetail): ?>
                    <!-- Payment Status Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-5 border border-stone-200 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 bg-blue-50 rounded-bl-full -z-0"></div>
                        
                        <h4 class="text-sm font-extrabold text-stone-700 uppercase tracking-wider mb-4 relative z-10 flex items-center">
                            <i class="fas fa-wallet text-blue-500 mr-2"></i> Info Pembayaran
                        </h4>
                        
                        <div class="bg-stone-50 rounded-lg p-4 border border-stone-100 mb-4">
                            <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">Metode</p>
                            <p class="font-extrabold text-lg text-stone-800 flex items-center">
                                <?php if ($paymentDetail['payment_method'] === 'qris'): ?>
                                    <i class="fas fa-qrcode text-blue-600 mr-2"></i>QRIS
                                <?php else: ?>
                                    <i class="fas fa-money-bill-wave text-emerald-600 mr-2"></i><?= getStatusText($paymentDetail['payment_method']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <!-- QRIS Verification Section -->
                        <?php if ($paymentDetail['payment_method'] === 'qris'): ?>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">Status Verifikasi</p>
                                <?php
                                    $vStatus = $paymentDetail['verification_status'];
                                    $vClass = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                                    $vIcon = 'fa-clock';
                                    if ($vStatus === 'verified') {
                                        $vClass = 'bg-green-100 text-green-700 border-green-200';
                                        $vIcon = 'fa-check-circle';
                                    } elseif ($vStatus === 'rejected') {
                                        $vClass = 'bg-red-100 text-red-700 border-red-200';
                                        $vIcon = 'fa-times-circle';
                                    }
                                ?>
                                <span class="px-3 py-1.5 text-xs font-extrabold rounded-lg border <?= $vClass ?> inline-flex items-center uppercase tracking-wide">
                                    <i class="fas <?= $vIcon ?> mr-1.5"></i> <?= ucfirst($vStatus) ?>
                                </span>
                            </div>
                            
                            <?php if ($paymentDetail['proof_of_payment']): ?>
                            <div>
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-2">Bukti Transfer</p>
                                <div class="rounded-xl overflow-hidden border-2 border-stone-200 shadow-sm group relative">
                                    <img 
                                        src="<?= UPLOADS_URL . '/' . $paymentDetail['proof_of_payment'] ?>" 
                                        alt="Bukti Pembayaran" 
                                        class="w-full h-48 object-cover cursor-zoom-in transition duration-300 group-hover:scale-105"
                                        onclick="showImageModal('<?= htmlspecialchars(UPLOADS_URL . '/' . $paymentDetail['proof_of_payment'], ENT_QUOTES, 'UTF-8') ?>')"
                                    >
                                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300 pointer-events-none">
                                        <i class="fas fa-search-plus text-white text-3xl"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($paymentDetail['verification_notes']): ?>
                            <div class="bg-red-50 border border-red-100 rounded-lg p-3">
                                <p class="text-xs font-bold text-red-500 uppercase tracking-wider mb-1"><i class="fas fa-info-circle mr-1"></i> Catatan Penolakan</p>
                                <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($paymentDetail['verification_notes']) ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Action Buttons -->
                            <?php if ($paymentDetail['verification_status'] === 'pending'): ?>
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button onclick="showRejectDialog(<?= $paymentDetail['id'] ?>)" class="bg-white border-2 border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300 py-2.5 rounded-xl font-bold text-sm transition flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i> Tolak
                                </button>
                                <button onclick="approvePayment(<?= $paymentDetail['id'] ?>)" class="bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center transform hover:-translate-y-0.5">
                                    <i class="fas fa-check mr-2"></i> Terima
                                </button>
                            </div>
                            <?php elseif ($paymentDetail['verification_status'] === 'verified'): ?>
                            <div class="pt-2">
                                <button onclick="printReceipt(<?= $orderDetail['id'] ?>)" class="w-full bg-stone-800 hover:bg-stone-900 text-white py-3 rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center">
                                    <i class="fas fa-print mr-2 text-emerald-400"></i> Print Struk Resi
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <!-- For non-QRIS (Cash) Payments - No status selection here, use buttons in list -->
                        <div class="mt-4 pt-2 border-t border-stone-100 space-y-3">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1"><i class="fas fa-info-circle mr-1"></i> Info</p>
                                <p class="text-sm font-medium text-blue-800">Status pembayaran tunai dapat diubah langsung dari daftar pesanan di tabel.</p>
                            </div>
                            <button onclick="printReceipt(<?= $orderDetail['id'] ?>)" class="w-full bg-stone-800 hover:bg-stone-900 text-white py-3 rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center">
                                <i class="fas fa-print mr-2 text-emerald-400"></i> Print Struk Resi
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Verified By Info -->
                        <?php if ($paymentDetail['verified_by'] && $paymentDetail['verified_at']): ?>
                        <div class="mt-4 pt-4 border-t border-stone-100 text-xs text-stone-500 font-medium">
                            <p class="mb-1"><i class="fas fa-user-check mr-1.5"></i> Verifikator: <span class="font-bold text-stone-700"><?= htmlspecialchars($paymentDetail['verified_by_name']) ?></span></p>
                            <p><i class="far fa-clock mr-1.5"></i> Waktu: <?= formatDateTime($paymentDetail['verified_at']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <!-- No Payment Info -->
                    <div class="bg-stone-100 border border-dashed border-stone-300 rounded-xl p-8 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-stone-200 rounded-full flex items-center justify-center text-stone-400 text-2xl mb-4">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <p class="font-bold text-stone-700 mb-1">Belum Ada Pembayaran</p>
                        <p class="text-sm text-stone-500">Customer belum menyelesaikan proses pembayaran untuk pesanan ini.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Status Update Buttons (For Pelayan) -->
                <?php 
                $currentStatus = $orderDetail['status'];
                $nextStatusOptions = [];
                
                // Define allowed transitions
                if ($currentStatus === 'pending') {
                    $nextStatusOptions = ['cooking' => 'Sedang Dimasak'];
                } elseif ($currentStatus === 'cooking') {
                    $nextStatusOptions = ['ready' => 'Siap Disajikan'];
                } elseif ($currentStatus === 'ready') {
                    $nextStatusOptions = ['served' => 'Disajikan'];
                } elseif ($currentStatus === 'served') {
                    $nextStatusOptions = ['completed' => 'Selesai'];
                }
                
                // Show status update buttons for pelayan and kasir (not for completed/cancelled)
                if (!in_array($currentStatus, ['completed', 'cancelled']) && count($nextStatusOptions) > 0): 
                ?>
                <div class="mt-6 pt-4 border-t border-dashed border-stone-300">
                    <p class="text-xs font-bold text-stone-500 uppercase tracking-wider mb-3"><i class="fas fa-arrow-right mr-1.5"></i> Update Status Pesanan</p>
                    <div class="grid gap-3">
                        <?php foreach ($nextStatusOptions as $status => $label): ?>
                        <button onclick="updateOrderStatus(<?= $orderDetail['id'] ?>, '<?= $status ?>')" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center">
                            <i class="fas fa-check-circle mr-2"></i> Ubah Menjadi: <?= $label ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Extra Action Buttons (Cancel Order) -->
                <?php if ($orderDetail['status'] !== 'cancelled'): ?>
                <div class="mt-6 pt-4 border-t border-dashed border-stone-300">
                    <button onclick="cancelOrder(<?= $orderDetail['id'] ?>)" class="w-full bg-white border-2 border-red-200 hover:bg-red-50 text-red-600 py-3 rounded-xl font-bold text-sm shadow-sm transition flex items-center justify-center">
                        <i class="fas fa-ban mr-2"></i> Batalkan Pesanan (Void)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Reject Payment Modal (Upgraded) -->
<div id="rejectModal" class="hidden fixed inset-0 bg-stone-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
        <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex items-center">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 mr-3">
                <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
            <h3 class="text-xl font-extrabold text-red-900 font-outfit">Tolak Pembayaran</h3>
        </div>
        <div class="p-6">
            <div class="mb-5">
                <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-2">Alasan Penolakan</label>
                <textarea 
                    id="rejectReason" 
                    rows="3" 
                    class="w-full px-4 py-3 bg-stone-50 border border-stone-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white transition resize-none font-medium text-sm"
                    placeholder="Contoh: Bukti transfer buram, nominal transfer kurang..."
                ></textarea>
                <p class="text-xs text-stone-500 mt-2"><i class="fas fa-info-circle mr-1"></i>Alasan ini akan ditampilkan ke sistem/kasir.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeRejectDialog()" class="flex-1 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 py-3 rounded-xl font-bold text-sm transition">
                    Kembali
                </button>
                <button onclick="submitRejectPayment()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-bold text-sm shadow-md transition">
                    Ya, Tolak Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Image Preview Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-[60] flex items-center justify-center p-4">
    <div class="max-w-3xl w-full relative">
        <img id="modalImage" src="" alt="Bukti Pembayaran" class="w-full rounded-2xl max-h-[85vh] object-contain shadow-2xl border border-stone-800">
        <button 
            onclick="closeImageModal()"
            class="absolute -top-12 right-0 text-white text-3xl cursor-pointer hover:text-stone-300 transition"
        >
            <i class="fas fa-times-circle"></i>
        </button>
    </div>
</div>

<script>
let rejectPaymentId = null;

function showImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}

function showRejectDialog(paymentId) {
    rejectPaymentId = paymentId;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectDialog() {
    rejectPaymentId = null;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectModal').classList.add('hidden');
}

function updateOrderStatus(orderId, newStatus) {
    const statusLabels = {
        'pending': 'Menunggu',
        'cooking': 'Sedang Dimasak',
        'ready': 'Siap Disajikan',
        'served': 'Disajikan',
        'completed': 'Selesai',
        'cancelled': 'Dibatalkan'
    };
    
    if (!confirm(`Ubah status pesanan menjadi "${statusLabels[newStatus]}"?`)) return;
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('status', newStatus);
    
    fetch('update_order_status.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            setTimeout(() => window.location.reload(), 500);
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(e => {
        console.error('Update status error:', e);
        alert('Error: ' + e.message);
    });
}

function approvePayment(paymentId) {
    if (!confirm('Verifikasi pembayaran QRIS ini?')) return;
    
    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('payment_id', paymentId);
    
    fetch('verify_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(data => {
        if (data && data.success) {
            alert(data.message || 'Pembayaran berhasil diverifikasi');
            // Redirect to orders list to refresh all data
            setTimeout(() => window.location.href = 'orders.php', 500);
        } else {
            const errorMsg = (data && data.message) ? data.message : (data && data.error) ? data.error : 'Unknown error';
            alert('Error: ' + errorMsg);
        }
    })
    .catch(e => {
        console.error('Approve error:', e);
        alert('Error: ' + e.message);
    });
}

function approveCashPayment(paymentId) {
    if (!confirm('Apakah Anda sudah menerima uang cash dari pembeli?')) return;
    
    const formData = new FormData();
    formData.append('payment_id', paymentId);
    
    fetch('process_cash_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✓ Pembayaran Cash Berhasil Diterima');
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || data.error));
        }
    })
    .catch(e => alert('Error: ' + e.message));
}

function rejectCashPayment(paymentId) {
    if (!confirm('Tandai pembayaran ini belum diterima?')) return;
    
    alert('Pembayaran tunai masih belum diterima.\n\nTunggu pelanggan menyelesaikan pembayaran sebelum dapat melanjutkan pesanan.');
    // Tidak perlu update database, status sudah pending
}

function submitRejectPayment() {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        alert('Alasan penolakan harus diisi');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('payment_id', rejectPaymentId);
    formData.append('reason', reason);
    
    fetch('verify_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(data => {
        if (data && data.success) {
            alert(data.message || 'Pembayaran berhasil ditolak');
            closeRejectDialog();
            // Redirect to orders list to refresh all data
            setTimeout(() => window.location.href = 'orders.php', 500);
        } else {
            const errorMsg = (data && data.message) ? data.message : 'Unknown error';
            alert('Error: ' + errorMsg);
            closeRejectDialog();
        }
    })
    .catch(e => {
        console.error('Reject error:', e);
        alert('Error: ' + e.message);
        closeRejectDialog();
    });
}

function printReceipt(orderId) {
    window.open('print_receipt.php?order=' + orderId, '_blank', 'width=400,height=600');
}

function cancelOrder(orderId) {
    if (!confirm('AWAS! Apakah Anda yakin ingin membatalkan pesanan ini secara permanen? Stok dan laporan akan disesuaikan kembali, dan meja (jika ada) akan dikosongkan.')) {
        return;
    }
    
    fetch('cancel_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'order_id=' + orderId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = 'orders.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(e => alert('Terjadi kesalahan: ' + e.message));
}

function filterOrderTable() {
    const searchVal = document.getElementById('orderSearchInput').value.toLowerCase().trim();
    const statusVal = document.getElementById('orderStatusFilterSelect').value;
    const rows = document.querySelectorAll('.order-row');

    rows.forEach(row => {
        const ordernum = row.getAttribute('data-ordernum');
        const customer = row.getAttribute('data-customer');
        const table = row.getAttribute('data-table');
        const status = row.getAttribute('data-status');

        const matchesSearch = ordernum.includes(searchVal) || customer.includes(searchVal) || table.includes(searchVal);
        const matchesStatus = (statusVal === 'all') || (status === statusVal);

        if (matchesSearch && matchesStatus) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

// Filter unpaid orders table
function filterUnpaidTable() {
    const searchVal = document.getElementById('unpaidSearchInput').value.toLowerCase().trim();
    const methodVal = document.getElementById('unpaidMethodFilterSelect').value;
    const rows = document.querySelectorAll('.unpaid-row');

    rows.forEach(row => {
        const ordernum = row.getAttribute('data-ordernum');
        const customer = row.getAttribute('data-customer');
        const table = row.getAttribute('data-table');
        const method = row.getAttribute('data-method');

        const matchesSearch = ordernum.includes(searchVal) || customer.includes(searchVal) || table.includes(searchVal);
        const matchesMethod = (methodVal === 'all') || (method === methodVal);

        if (matchesSearch && matchesMethod) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

// Filter rejected orders table
function filterRejectedTable() {
    const searchVal = document.getElementById('rejectedSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.rejected-row');

    rows.forEach(row => {
        const ordernum = row.getAttribute('data-ordernum');
        const customer = row.getAttribute('data-customer');
        const table = row.getAttribute('data-table');

        const matchesSearch = ordernum.includes(searchVal) || customer.includes(searchVal) || table.includes(searchVal);

        if (matchesSearch) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

// Modal helper functions
function showConfirmModal(title, message, onConfirm, confirmButtonText = 'Konfirmasi', onCancel = null) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-stone-900/80 backdrop-blur-sm z-[9999] flex items-center justify-center p-4';
    modal.id = 'confirmModal';
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-stone-200 animate-in fade-in zoom-in-95 duration-300">
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-4 border-b border-stone-200 flex items-start gap-3">
                <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center shrink-0 font-bold text-lg">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3 class="font-bold text-stone-900 text-lg mt-0.5">${title}</h3>
            </div>
            
            <div class="px-6 py-5">
                <p class="text-stone-700 text-sm leading-relaxed whitespace-pre-wrap">${message}</p>
            </div>
            
            <div class="bg-slate-50 px-6 py-3 border-t border-stone-200 flex gap-3 justify-end">
                <button id="confirmModalCancelBtn" class="px-4 py-2 bg-white border border-stone-200 text-stone-700 font-bold rounded-lg hover:bg-stone-50 transition-colors">
                    Batal
                </button>
                <button id="confirmModalConfirmBtn" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg transition-colors shadow-sm">
                    ${confirmButtonText}
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Cancel button handler
    document.getElementById('confirmModalCancelBtn').addEventListener('click', () => {
        modal.remove();
        if (onCancel) onCancel();
    });
    
    // Confirm button handler
    document.getElementById('confirmModalConfirmBtn').addEventListener('click', () => {
        modal.remove();
        if (onConfirm && typeof onConfirm === 'function') {
            onConfirm();
        }
    });
    
    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
            if (onCancel) onCancel();
        }
    });
}

function showSuccessModal(title, message, onClose = null) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-stone-900/80 backdrop-blur-sm z-[9999] flex items-center justify-center p-4';
    modal.id = 'successModal';
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-stone-200 animate-in fade-in zoom-in-95 duration-300">
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 px-6 py-4 border-b border-stone-200 flex items-start gap-3">
                <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center shrink-0 font-bold text-lg">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="font-bold text-stone-900 text-lg mt-0.5">${title}</h3>
            </div>
            
            <div class="px-6 py-5">
                <p class="text-stone-700 text-sm leading-relaxed">${message}</p>
            </div>
            
            <div class="bg-slate-50 px-6 py-3 border-t border-stone-200 flex justify-end">
                <button onclick="document.getElementById('successModal').remove()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition-colors shadow-sm">
                    OK
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Auto close after 3 seconds if onClose callback provided
    if (onClose) {
        setTimeout(() => {
            const el = document.getElementById('successModal');
            if (el) {
                el.remove();
                onClose();
            }
        }, 2000);
    }
}

function showErrorModal(title, message) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-stone-900/80 backdrop-blur-sm z-[9999] flex items-center justify-center p-4';
    modal.id = 'errorModal';
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-stone-200 animate-in fade-in zoom-in-95 duration-300">
            <div class="bg-gradient-to-r from-rose-50 to-red-50 px-6 py-4 border-b border-stone-200 flex items-start gap-3">
                <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center shrink-0 font-bold text-lg">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h3 class="font-bold text-stone-900 text-lg mt-0.5">${title}</h3>
            </div>
            
            <div class="px-6 py-5">
                <p class="text-stone-700 text-sm leading-relaxed">${message}</p>
            </div>
            
            <div class="bg-slate-50 px-6 py-3 border-t border-stone-200 flex justify-end">
                <button onclick="document.getElementById('errorModal').remove()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg transition-colors shadow-sm">
                    Tutup
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function filterCleansingTable() {
    const searchVal = document.getElementById('cleansingSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.cleansing-row');

    rows.forEach(row => {
        const customer = row.getAttribute('data-customer');
        const matchesSearch = customer.includes(searchVal);

        if (matchesSearch) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.customer-checkbox:not(.hidden)');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkDeleteButton();
}

function selectAllCleansing() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    selectAllCheckbox.checked = true;
    const checkboxes = document.querySelectorAll('.customer-checkbox:not(.hidden)');
    checkboxes.forEach(cb => cb.checked = true);
    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    bulkDeleteBtn.disabled = checkboxes.length === 0;
}

function bulkDeleteCleansing() {
    const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
    if (checkboxes.length === 0) {
        showErrorModal('Pilih Customer', 'Pilih minimal 1 customer untuk dihapus semua pesanannya');
        return;
    }

    const customerNames = Array.from(checkboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(3)').textContent.trim();
    });
    
    const message = `⚠️ PERHATIAN!\n\nAnda akan menghapus SEMUA pesanan dari ${checkboxes.length} pelanggan:\n\n${customerNames.join(', ')}\n\nAksi ini TIDAK DAPAT DIBATALKAN!`;
    
    showConfirmModal('Hapus Semua Pesanan Customer?', message, () => {
        confirmBulkDeleteCleansing();
    });
}

function confirmBulkDeleteCleansing() {
    const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
    
    // Disable button while processing
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    bulkDeleteBtn.disabled = true;
    bulkDeleteBtn.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> Menghapus...';

    // Delete all orders for each selected customer
    let processedCount = 0;
    let totalCount = checkboxes.length;
    let hasError = false;

    Array.from(checkboxes).forEach((checkbox, index) => {
        const row = checkbox.closest('tr');
        const customerName = row.querySelector('td:nth-child(3)').textContent.trim();
        
        setTimeout(() => {
            fetch('delete_customer_orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'customer_name=' + encodeURIComponent(customerName)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    processedCount++;
                    // Remove customer row and details row
                    const customerHash = checkbox.getAttribute('data-customer-hash');
                    document.querySelector(`[data-hash="${customerHash}"]`).remove();
                    document.getElementById(`details-${customerHash}`).remove();
                    
                    // If all deleted, reload page
                    if (processedCount === totalCount) {
                        if (!hasError) {
                            showSuccessModal('Berhasil!', `Berhasil menghapus pesanan dari ${processedCount} pelanggan`, () => {
                                location.reload();
                            });
                        }
                    }
                } else {
                    hasError = true;
                    showErrorModal('Error', `Error menghapus pesanan ${customerName}: ${data.message}`);
                }
            })
            .catch(e => {
                hasError = true;
                showErrorModal('Error', 'Terjadi kesalahan: ' + e.message);
            });
        }, index * 300);
    });
}

// Delete all orders for a customer
function bulkDeleteByCustomer(customerName, orderCount) {
    const message = `⚠️ PERHATIAN!\n\nAnda akan menghapus SEMUA ${orderCount} pesanan dari pelanggan:\n\n${customerName}\n\nAksi ini TIDAK DAPAT DIBATALKAN!`;
    
    // Store button reference for later use
    window.deleteButtonRef = event.target;
    
    showConfirmModal('Hapus Semua Pesanan?', message, () => {
        confirmDeleteByCustomer(customerName);
    });
}

function confirmDeleteByCustomer(customerName) {
    const btn = window.deleteButtonRef || event.target;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> Menghapus...';
    }

    fetch('delete_customer_orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'customer_name=' + encodeURIComponent(customerName)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showSuccessModal('Berhasil!', `Berhasil menghapus semua pesanan dari ${customerName}`, () => {
                location.reload();
            });
        } else {
            showErrorModal('Error', data.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt mr-1"></i> Hapus Semua';
            }
        }
    })
    .catch(e => {
        showErrorModal('Error', 'Terjadi kesalahan: ' + e.message);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash-alt mr-1"></i> Hapus Semua';
        }
    });
}

// Toggle order details visibility for a customer
function toggleOrderDetails(customerHash) {
    const detailsRow = document.getElementById(`details-${customerHash}`);
    const icon = document.getElementById(`icon-${customerHash}`);
    
    if (detailsRow.classList.contains('hidden')) {
        detailsRow.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        detailsRow.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

// Real-time order updates polling for admin dashboard
// Auto-refreshes when new QRIS payments are confirmed (auto-verified)
// Skip polling if on cleansing tab
function pollForOrderUpdates() {
    // Don't poll if on cleansing tab (data cleaning)
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('tab') || 'orders';
    
    if (currentTab === 'cleansing') {
        return; // Skip polling on cleansing tab
    }
    
    fetch('api_pending_payments.php?t=' + Date.now())
        .then(r => r.json())
        .then(data => {
            if (data.success && data.count > 0) {
                console.log('New pending payments detected:', data.count);
                
                // Auto-refresh page to show latest order statuses
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        })
        .catch(e => console.log('Poll error:', e));
}

// Poll every 5 seconds for new auto-verified QRIS payments
setInterval(pollForOrderUpdates, 5000);

function updateCashPaymentStatus(paymentId, status) {
    const displayStatus = status === 'success' ? 'Sudah Bayar' : 'Belum Bayar';
    const message = `Ubah status pembayaran menjadi:\n\n${displayStatus}?`;
    
    showConfirmModal('Ubah Status Pembayaran?', message, () => {
        confirmCashPaymentUpdate(paymentId, status, displayStatus);
    });
}

function confirmCashPaymentUpdate(paymentId, status, displayStatus) {
    const formData = new FormData();
    formData.append('payment_id', paymentId);
    formData.append('status', status);
    
    fetch('process_cash_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showSuccessModal('Berhasil!', `Status pembayaran berhasil diubah menjadi:\n\n${displayStatus}`, () => {
                window.location.reload();
            });
        } else {
            showErrorModal('Error', data.message || 'Gagal mengubah status');
        }
    })
    .catch(e => {
        console.error('Error:', e);
        showErrorModal('Error', 'Terjadi kesalahan: ' + e.message);
    });
}

function setCashPaymentStatus(paymentId, status) {
    const displayStatus = status === 'success' ? 'Sudah Dibayar' : 'Belum Dibayar';
    const message = `Ubah status pembayaran menjadi:\n\n${displayStatus}?`;
    const buttonText = status === 'success' ? '✓ Konfirmasi Bayar' : '⊘ Ubah ke Belum Dibayar';
    
    showConfirmModal('Ubah Status Pembayaran?', message, () => {
        confirmCashPaymentUpdate(paymentId, status, displayStatus);
    }, buttonText);
}


</script>

<?php include '../includes/footer.php'; ?>

