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
include '../includes/header.php';
?>
<div class="p-3 sm:p-6 max-w-7xl mx-auto pb-32 sm:pb-24 w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 sm:mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 font-outfit tracking-tight">Daftar Pesanan</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Pantau dan kelola semua transaksi pesanan pelanggan.</p>
        </div>
        <div class="w-full sm:w-auto flex items-center gap-2">
            <span class="text-sm font-semibold text-slate-500"><i class="fas fa-filter mr-1"></i> Filter:</span>
            <select onchange="window.location.href='?filter='+this.value" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block p-2.5 shadow-sm font-semibold transition-all cursor-pointer">
                <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Hari Ini</option>
                <option value="yesterday" <?= $filter === 'yesterday' ? 'selected' : '' ?>>Kemarin</option>
                <option value="this_week" <?= $filter === 'this_week' ? 'selected' : '' ?>>Minggu Ini</option>
                <option value="this_month" <?= $filter === 'this_month' ? 'selected' : '' ?>>Bulan Ini</option>
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Semua Data</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider font-extrabold">
                        <th class="p-4 sm:p-5">Order ID</th>
                        <th class="p-4 sm:p-5">Pelanggan</th>
                        <th class="p-4 sm:p-5">Waktu</th>
                        <th class="p-4 sm:p-5">Metode</th>
                        <th class="p-4 sm:p-5">Status</th>
                        <th class="p-4 sm:p-5 text-right">Total</th>
                        <th class="p-4 sm:p-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
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
                        $needsVerification = ($payment && $payment['payment_method'] === 'qris' && $payment['verification_status'] === 'pending' && $payment['proof_of_payment']);
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors duration-200 <?= $needsVerification ? 'bg-orange-50/30' : '' ?>">
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center font-bold text-sm <?= $needsVerification ? 'bg-orange-100 text-orange-600' : 'bg-emerald-50 text-emerald-500' ?>">
                                    <i class="fas <?= $needsVerification ? 'fa-exclamation-triangle' : 'fa-receipt' ?>"></i>
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
                            <p class="text-xs text-slate-500 mt-1"><i class="fas fa-chair mr-1"></i>Meja <?= $order['table_number'] ?? 'TA' ?></p>
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
                            
                            <?php if ($needsVerification): ?>
                            <div class="mt-1.5">
                                <span class="text-[10px] font-bold text-orange-600 bg-orange-100/80 px-2 py-0.5 rounded shadow-sm inline-flex items-center">
                                    <i class="fas fa-circle-notch fa-spin mr-1"></i> Verifikasi
                                </span>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap">
                            <span class="px-3 py-1.5 text-xs font-extrabold rounded-xl shadow-sm border uppercase tracking-wider <?= getStatusBadge($order['status']) ?>">
                                <?= getStatusText($order['status']) ?>
                            </span>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-right">
                            <span class="font-extrabold text-emerald-600 text-lg font-outfit"><?= formatRupiah($order['total']) ?></span>
                        </td>
                        <td class="p-4 sm:p-5 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
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
            <a href="orders.php" class="w-8 h-8 flex items-center justify-center rounded-full bg-stone-100 text-stone-500 hover:bg-red-100 hover:text-red-600 transition duration-200">
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
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">Detail Layanan</p>
                                <p class="font-bold text-stone-800 flex items-start mt-1">
                                    <?php if ($orderDetail['order_type'] === 'dine_in'): ?>
                                        <i class="fas fa-chair text-stone-400 text-sm mr-2 mt-0.5"></i> Meja <?= $orderDetail['table_number'] ?>
                                    <?php elseif ($orderDetail['order_type'] === 'delivery'): ?>
                                        <i class="fas fa-motorcycle text-emerald-500 text-sm mr-2 mt-0.5"></i> Delivery
                                    <?php else: ?>
                                        <i class="fas fa-shopping-bag text-stone-400 text-sm mr-2 mt-0.5"></i> Take Away
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($orderDetail['order_type'] === 'delivery' && !empty($orderDetail['delivery_address'])): ?>
                                    <div class="mt-2 bg-stone-50 border border-stone-200 rounded-lg p-2.5">
                                        <p class="text-xs font-bold text-stone-500 mb-1"><i class="fas fa-map-marker-alt text-emerald-500 mr-1"></i> Alamat Pengiriman:</p>
                                        <p class="text-xs text-stone-700 font-medium leading-relaxed"><?= nl2br(htmlspecialchars($orderDetail['delivery_address'])) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="text-sm text-stone-500 font-medium mt-2"><i class="fas fa-user-tag text-xs mr-1 text-stone-300"></i> Kasir: <?= htmlspecialchars($orderDetail['kasir_name'] ?? '-') ?></p>
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
                                        onclick="showImageModal(this.src)"
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
                            <?php if ($paymentDetail['verification_status'] === 'pending' && $paymentDetail['proof_of_payment']): ?>
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
                        <!-- For non-QRIS (Cash) Payments -->
                        <div class="mt-4 pt-2 border-t border-stone-100 space-y-3">
                            <?php if ($paymentDetail['status'] === 'pending'): ?>
                            <button onclick="approveCashPayment(<?= $paymentDetail['id'] ?>)" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center">
                                <i class="fas fa-check-circle mr-2 text-white"></i> Terima Pembayaran Tunai
                            </button>
                            <?php endif; ?>
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

function approvePayment(paymentId) {
    if (!confirm('Verifikasi pembayaran QRIS ini?')) return;
    
    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('payment_id', paymentId);
    
    fetch('verify_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert('Error: ' + (data.message || data.error));
        }
    })
    .catch(e => alert('Error: ' + e.message));
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
            alert('Pembayaran Cash Berhasil Diterima');
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || data.error));
        }
    })
    .catch(e => alert('Error: ' + e.message));
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
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Reload page in-place to update modal status
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(e => alert('Error: ' + e.message))
    .finally(() => closeRejectDialog());
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
</script>

<?php include '../includes/footer.php'; ?>
