<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir', 'admin']);

$user = getCurrentUser();
$db = getDB();

// Get date filter
$filter = $_GET['filter'] ?? 'today';
$startDate = '';
$endDate = '';
$dateLabel = '';

switch ($filter) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        $dateLabel = 'Hari Ini (' . date('d/m/Y') . ')';
        break;
    case 'yesterday':
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $dateLabel = 'Kemarin (' . date('d/m/Y', strtotime('-1 day')) . ')';
        break;
    case 'this_week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime('sunday this week'));
        $dateLabel = 'Minggu Ini (' . $startDate . ' s/d ' . $endDate . ')';
        break;
    case 'this_month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        $dateLabel = 'Bulan ' . date('F Y');
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        $dateLabel = '30 Hari Terakhir';
}

// === REVENUE FROM ORDERS ===
$stmt = $db->prepare("
    SELECT 
        COUNT(o.id) as total_orders,
        SUM(o.total) as total_revenue,
        SUM(CASE WHEN p.payment_method = 'qris' THEN o.total ELSE 0 END) as qris_revenue,
        SUM(CASE WHEN p.payment_method = 'cash' THEN o.total ELSE 0 END) as cash_revenue,
        SUM(CASE WHEN p.payment_method = 'transfer' THEN o.total ELSE 0 END) as transfer_revenue
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE DATE(o.created_at) >= ? AND DATE(o.created_at) <= ? AND o.status IN ('confirmed', 'cooking', 'ready', 'served', 'completed')
");
$stmt->execute([$startDate, $endDate]);
$revenueData = $stmt->fetch();

// === EXPENSES ===
$stmt = $db->prepare("
    SELECT 
        SUM(amount) as total_expenses,
        COUNT(id) as expense_count
    FROM expenses
    WHERE DATE(expense_date) >= ? AND DATE(expense_date) <= ?
");
$stmt->execute([$startDate, $endDate]);
$expensesData = $stmt->fetch();

// === DAILY BREAKDOWN ===
$stmt = $db->prepare("
    SELECT 
        DATE(o.created_at) as date,
        COUNT(o.id) as total_orders,
        SUM(o.total) as daily_revenue,
        SUM(CASE WHEN p.payment_method = 'qris' THEN o.total ELSE 0 END) as qris_revenue,
        SUM(CASE WHEN p.payment_method = 'cash' THEN o.total ELSE 0 END) as cash_revenue
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE DATE(o.created_at) >= ? AND DATE(o.created_at) <= ? AND o.status IN ('confirmed', 'cooking', 'ready', 'served', 'completed')
    GROUP BY DATE(o.created_at)
    ORDER BY DATE(o.created_at) DESC
");
$stmt->execute([$startDate, $endDate]);
$dailyRevenue = $stmt->fetchAll();

// === EXPENSES DETAIL ===
$stmt = $db->prepare("
    SELECT * FROM expenses
    WHERE DATE(expense_date) >= ? AND DATE(expense_date) <= ?
    ORDER BY expense_date DESC
");
$stmt->execute([$startDate, $endDate]);
$expensesList = $stmt->fetchAll();

// === LOW STOCK ITEMS ===
$stmt = $db->query("SELECT * FROM ingredients WHERE quantity <= 10 ORDER BY quantity ASC");
$lowStockItems = $stmt->fetchAll();

// === TOP PRODUCTS ===
$stmt = $db->prepare("
    SELECT 
        oi.menu_name,
        SUM(oi.quantity) as total_qty,
        SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) >= ? AND DATE(o.created_at) <= ? AND o.status IN ('confirmed', 'cooking', 'ready', 'served', 'completed')
    GROUP BY oi.menu_name
    ORDER BY total_qty DESC
    LIMIT 10
");
$stmt->execute([$startDate, $endDate]);
$topProducts = $stmt->fetchAll();

// === CALCULATIONS ===
$totalRevenue = $revenueData['total_revenue'] ?? 0;
$totalExpenses = $expensesData['total_expenses'] ?? 0;
$labaKotor = $totalRevenue;
$labaBersih = $totalRevenue - $totalExpenses;
$profitMargin = ($totalRevenue > 0) ? round(($labaBersih / $totalRevenue) * 100, 2) : 0;

$pageTitle = 'Laporan Keuangan';
include '../includes/header.php';

// Determine if user is kasir (limited view) or owner/admin (full view)
$isKasir = $_SESSION['user_role'] === 'kasir';
$isOwnerAdmin = in_array($_SESSION['user_role'], ['owner', 'admin']);
?>

    <div class="p-4 sm:p-6 max-w-7xl mx-auto pb-20">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-4xl font-extrabold font-outfit text-slate-800 flex items-center">
                    <i class="fas fa-<?= $isKasir ? 'cash-register' : 'file-invoice-dollar' ?> text-emerald-600 mr-3"></i> 
                    <?= $isKasir ? 'Ringkasan Pembayaran' : 'Laporan Keuangan' ?>
                </h1>
                <p class="text-slate-500 text-sm mt-2">
                    <?= $isKasir ? 'Rekapan pembayaran harian kasir' : 'Analisis lengkap pendapatan, pengeluaran, dan profitabilitas bisnis' ?>
                </p>
                <p class="text-emerald-600 font-bold text-sm mt-1"><i class="fas fa-calendar mr-1"></i> <?= $dateLabel ?></p>
            </div>
            
            <!-- Filter -->
            <div class="flex gap-2 flex-wrap">
                <a href="?filter=today" class="px-4 py-2 rounded-lg font-bold text-sm transition <?= $filter === 'today' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                    Hari Ini
                </a>
                <a href="?filter=yesterday" class="px-4 py-2 rounded-lg font-bold text-sm transition <?= $filter === 'yesterday' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                    Kemarin
                </a>
                <a href="?filter=this_week" class="px-4 py-2 rounded-lg font-bold text-sm transition <?= $filter === 'this_week' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                    Minggu
                </a>
                <a href="?filter=this_month" class="px-4 py-2 rounded-lg font-bold text-sm transition <?= $filter === 'this_month' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                    Bulan
                </a>
                <a href="?filter=all" class="px-4 py-2 rounded-lg font-bold text-sm transition <?= $filter === 'all' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                    30 Hari
                </a>
            </div>
        </div>

        <!-- === SECTION 1: PEMASUKAN === -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1.5 h-8 bg-emerald-600 rounded-full"></div>
                <h2 class="text-2xl font-extrabold text-slate-800 font-outfit">📈 PEMASUKAN (INCOME)</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Revenue -->
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl p-6 border-2 border-emerald-300 shadow-md">
                    <p class="text-slate-600 text-xs font-bold uppercase tracking-widest mb-3">Total Pendapatan</p>
                    <p class="text-4xl font-extrabold text-emerald-700 font-outfit mb-3"><?= formatRupiah($totalRevenue) ?></p>
                    <div class="pt-3 border-t border-emerald-300">
                        <p class="text-xs text-slate-600"><i class="fas fa-receipt mr-2 text-emerald-600"></i><strong><?= $revenueData['total_orders'] ?? 0 ?></strong> Transaksi</p>
                    </div>
                </div>

                <!-- QRIS -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl p-6 border-2 border-blue-300 shadow-md">
                    <p class="text-slate-600 text-xs font-bold uppercase tracking-widest mb-3">QRIS</p>
                    <p class="text-3xl font-extrabold text-blue-700 font-outfit mb-3"><?= formatRupiah($revenueData['qris_revenue'] ?? 0) ?></p>
                    <div class="pt-3 border-t border-blue-300">
                        <?php $qris_pct = ($totalRevenue > 0) ? round(($revenueData['qris_revenue'] / $totalRevenue) * 100) : 0; ?>
                        <p class="text-xs text-slate-600"><i class="fas fa-qrcode mr-2 text-blue-600"></i><strong><?= $qris_pct ?>%</strong> dari total</p>
                    </div>
                </div>

                <!-- Cash -->
                <div class="bg-gradient-to-br from-green-50 to-lime-50 rounded-2xl p-6 border-2 border-green-300 shadow-md">
                    <p class="text-slate-600 text-xs font-bold uppercase tracking-widest mb-3">Tunai</p>
                    <p class="text-3xl font-extrabold text-green-700 font-outfit mb-3"><?= formatRupiah($revenueData['cash_revenue'] ?? 0) ?></p>
                    <div class="pt-3 border-t border-green-300">
                        <?php $cash_pct = ($totalRevenue > 0) ? round(($revenueData['cash_revenue'] / $totalRevenue) * 100) : 0; ?>
                        <p class="text-xs text-slate-600"><i class="fas fa-wallet mr-2 text-green-600"></i><strong><?= $cash_pct ?>%</strong> dari total</p>
                    </div>
                </div>

                <!-- Transfer -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-6 border-2 border-purple-300 shadow-md">
                    <p class="text-slate-600 text-xs font-bold uppercase tracking-widest mb-3">Transfer</p>
                    <p class="text-3xl font-extrabold text-purple-700 font-outfit mb-3"><?= formatRupiah($revenueData['transfer_revenue'] ?? 0) ?></p>
                    <div class="pt-3 border-t border-purple-300">
                        <?php $transfer_pct = ($totalRevenue > 0) ? round(($revenueData['transfer_revenue'] / $totalRevenue) * 100) : 0; ?>
                        <p class="text-xs text-slate-600"><i class="fas fa-university mr-2 text-purple-600"></i><strong><?= $transfer_pct ?>%</strong> dari total</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- === SECTION 2: PENGELUARAN (HANYA OWNER/ADMIN) === -->
        <?php if ($isOwnerAdmin): ?>
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1.5 h-8 bg-red-600 rounded-full"></div>
                <h2 class="text-2xl font-extrabold text-slate-800 font-outfit">📉 PENGELUARAN (EXPENSES)</h2>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Total Expenses -->
                <div class="bg-gradient-to-br from-red-50 to-rose-50 rounded-2xl p-6 border-2 border-red-300 shadow-md">
                    <p class="text-slate-600 text-xs font-bold uppercase tracking-widest mb-3">Total Pengeluaran</p>
                    <p class="text-4xl font-extrabold text-red-700 font-outfit mb-3"><?= formatRupiah($totalExpenses) ?></p>
                    <div class="pt-3 border-t border-red-300">
                        <p class="text-xs text-slate-600"><i class="fas fa-list mr-2 text-red-600"></i><strong><?= $expensesData['expense_count'] ?? 0 ?></strong> Item Biaya</p>
                    </div>
                </div>

                <!-- Expenses List -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-md border border-red-200 overflow-hidden">
                    <div class="p-4 bg-red-50 border-b border-red-200">
                        <h3 class="font-bold text-slate-800 text-sm flex items-center">
                            <i class="fas fa-list-check text-red-600 mr-2"></i> Daftar Pengeluaran
                        </h3>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <?php if (empty($expensesList)): ?>
                        <div class="p-6 text-center text-slate-500">
                            <i class="fas fa-check-circle text-2xl text-emerald-500 mb-2"></i><br>
                            <span class="text-sm font-medium">Tidak ada pengeluaran</span>
                        </div>
                        <?php else: ?>
                        <div class="divide-y divide-red-100">
                            <?php foreach ($expensesList as $exp): ?>
                            <div class="px-4 py-3 hover:bg-red-50 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($exp['description']) ?></p>
                                        <p class="text-xs text-slate-500 mt-1"><i class="fas fa-calendar-day mr-1"></i><?= date('d/m/Y', strtotime($exp['expense_date'])) ?></p>
                                    </div>
                                    <p class="font-extrabold text-red-700 ml-2">-<?= formatRupiah($exp['amount']) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- === SECTION 3: PROFIT ANALYSIS (HANYA OWNER/ADMIN) === -->
        <?php if ($isOwnerAdmin): ?>
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1.5 h-8 bg-yellow-500 rounded-full"></div>
                <h2 class="text-2xl font-extrabold text-slate-800 font-outfit">💰 ANALISIS PROFIT & FORMULA</h2>
            </div>
            
            <!-- Profit Calculation Box -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-8 shadow-xl border border-slate-700">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Input 1 -->
                    <div class="bg-white/10 backdrop-blur rounded-xl p-6 border border-white/20">
                        <p class="text-white/70 text-xs font-bold uppercase mb-2 tracking-wider">Input 1</p>
                        <p class="text-white/90 text-sm font-medium mb-3">Total Pendapatan</p>
                        <p class="text-3xl font-extrabold text-emerald-400 font-outfit"><?= formatRupiah($totalRevenue) ?></p>
                    </div>

                    <!-- Minus -->
                    <div class="flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-white/50 text-xs font-bold mb-4">KURANGI</p>
                            <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center mx-auto border border-white/30">
                                <i class="fas fa-minus text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Input 2 -->
                    <div class="bg-white/10 backdrop-blur rounded-xl p-6 border border-white/20">
                        <p class="text-white/70 text-xs font-bold uppercase mb-2 tracking-wider">Input 2</p>
                        <p class="text-white/90 text-sm font-medium mb-3">Total Pengeluaran</p>
                        <p class="text-3xl font-extrabold text-red-400 font-outfit"><?= formatRupiah($totalExpenses) ?></p>
                    </div>
                </div>

                <!-- Results -->
                <div class="pt-8 border-t border-white/10 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Laba Kotor -->
                    <div class="bg-gradient-to-br from-amber-500/20 to-orange-500/20 rounded-xl p-6 border border-amber-500/40">
                        <p class="text-amber-300 text-xs font-bold uppercase mb-2 tracking-wider">Rumus 1</p>
                        <p class="text-white/80 text-xs mb-3">Laba Kotor = Pendapatan (tanpa pengurangan)</p>
                        <p class="text-3xl font-extrabold text-amber-300 font-outfit"><?= formatRupiah($labaKotor) ?></p>
                        <p class="text-xs text-amber-200/60 mt-2"><i class="fas fa-info-circle mr-1"></i> Pendapatan sebelum biaya</p>
                    </div>

                    <!-- Laba Bersih -->
                    <div class="bg-gradient-to-br from-emerald-500/30 to-teal-500/30 rounded-xl p-6 border-2 border-emerald-400">
                        <p class="text-emerald-300 text-xs font-bold uppercase mb-2 tracking-wider">✓ Rumus Akhir</p>
                        <p class="text-white/90 text-xs mb-3 font-semibold">Laba Bersih = Pendapatan - Pengeluaran</p>
                        <p class="text-4xl font-extrabold text-emerald-300 font-outfit"><?= formatRupiah($labaBersih) ?></p>
                        <div class="mt-3 pt-3 border-t border-emerald-400/50 flex justify-between items-center">
                            <p class="text-emerald-200 text-xs font-bold">Profit Margin</p>
                            <p class="text-emerald-300 text-lg font-extrabold"><?= $profitMargin ?>%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- === SECTION 4: RINGKASAN HARIAN === -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-1.5 h-8 bg-blue-600 rounded-full"></div>
                <h2 class="text-2xl font-extrabold text-slate-800 font-outfit">📊 RINGKASAN HARIAN</h2>
            </div>
            
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="px-6 py-4 text-left font-bold text-slate-700">Tanggal</th>
                                <th class="px-6 py-4 text-right font-bold text-slate-700">Transaksi</th>
                                <th class="px-6 py-4 text-right font-bold text-slate-700">QRIS</th>
                                <th class="px-6 py-4 text-right font-bold text-slate-700">Tunai</th>
                                <th class="px-6 py-4 text-right font-bold text-slate-700">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dailyRevenue)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 font-medium">
                                    <i class="fas fa-inbox text-2xl mb-2"></i><br> Tidak ada data
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($dailyRevenue as $day): ?>
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="px-6 py-4 font-bold text-slate-800"><?= date('d/m/Y', strtotime($day['date'])) ?></td>
                                    <td class="px-6 py-4 text-right"><span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg font-bold text-xs"><?= $day['total_orders'] ?></span></td>
                                    <td class="px-6 py-4 text-right font-bold text-blue-600"><?= formatRupiah($day['qris_revenue'] ?? 0) ?></td>
                                    <td class="px-6 py-4 text-right font-bold text-green-600"><?= formatRupiah($day['cash_revenue'] ?? 0) ?></td>
                                    <td class="px-6 py-4 text-right font-extrabold text-emerald-700"><?= formatRupiah($day['daily_revenue'] ?? 0) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- === SECTION 5: TOP PRODUCTS & LOW STOCK === -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Products -->
            <div>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1.5 h-8 bg-yellow-500 rounded-full"></div>
                    <h2 class="text-2xl font-extrabold text-slate-800 font-outfit">⭐ 10 MENU TERLARIS</h2>
                </div>

                <div class="bg-white rounded-2xl shadow-md border border-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-4 py-3 text-center font-bold text-slate-700 w-10">#</th>
                                    <th class="px-4 py-3 text-left font-bold text-slate-700">Menu</th>
                                    <th class="px-4 py-3 text-right font-bold text-slate-700">Qty</th>
                                    <th class="px-4 py-3 text-right font-bold text-slate-700">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topProducts)): ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-500 text-sm">Tidak ada data</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($topProducts as $idx => $product): ?>
                                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                                        <td class="px-4 py-3 text-center">
                                            <span class="bg-emerald-100 text-emerald-700 w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"><?= $idx + 1 ?></span>
                                        </td>
                                        <td class="px-4 py-3 font-bold text-slate-800 text-sm"><?= htmlspecialchars($product['menu_name']) ?></td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-700"><?= $product['total_qty'] ?> pcs</td>
                                        <td class="px-4 py-3 text-right font-extrabold text-emerald-700"><?= formatRupiah($product['total_revenue']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-1.5 h-8 bg-rose-600 rounded-full"></div>
                    <h2 class="text-2xl font-extrabold text-slate-800 font-outfit">⚠️ STOK MENIPIS (≤10)</h2>
                </div>

                <div class="bg-white rounded-2xl shadow-md border-2 border-rose-300 overflow-hidden">
                    <div class="max-h-96 overflow-y-auto">
                        <?php if (empty($lowStockItems)): ?>
                        <div class="p-8 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-4">
                                <i class="fas fa-check-circle text-2xl text-emerald-600"></i>
                            </div>
                            <p class="font-bold text-slate-700">Semua stok aman!</p>
                            <p class="text-xs text-slate-500 mt-1">Tidak ada bahan yang menipis</p>
                        </div>
                        <?php else: ?>
                        <div class="divide-y divide-rose-100">
                            <?php foreach ($lowStockItems as $item): ?>
                            <div class="px-4 py-3 hover:bg-rose-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($item['name']) ?></p>
                                        <p class="text-xs text-slate-500 mt-1"><i class="fas fa-box mr-1"></i><?= htmlspecialchars($item['unit']) ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-extrabold text-rose-600 text-lg"><?= $item['stock_quantity'] ?></p>
                                        <p class="text-xs text-rose-500 font-bold">RESTOCK!</p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-3 bg-rose-50 border-t border-rose-200 text-xs text-slate-600 text-center font-medium">
                        <i class="fas fa-link mr-1"></i> <a href="stock.php" class="text-emerald-600 hover:text-emerald-700 font-bold">→ Update Stok Bahan</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
