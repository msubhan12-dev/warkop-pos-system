<?php
require_once '../config/config.php';
requireRole(['owner']);
$pageTitle = 'Laporan Keuangan & Stok';
$user = getCurrentUser();
$db = getDB();

// Get Daily Sales
$stmt = $db->query("SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue FROM orders WHERE status = 'completed' GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30");
$dailyReport = $stmt->fetchAll();

// Get Recent Expenses
$stmt_exp = $db->query("SELECT e.*, u.full_name as creator_name FROM expenses e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.expense_date DESC, e.created_at DESC LIMIT 50");
$expenses = $stmt_exp->fetchAll();

// Financial KPI calculations
$totalRevenue30 = array_sum(array_column($dailyReport, 'revenue'));
$totalExpenses50 = array_sum(array_column($expenses, 'amount'));
$netProfitEstimate = $totalRevenue30 - $totalExpenses50;

include '../includes/header.php';
?>
<main class="p-4 sm:p-6 pb-32 sm:pb-24 max-w-7xl mx-auto">
    <!-- Header with Excel Export Button -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 font-outfit tracking-tight">Laporan Keuangan Kedai</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Pantau omset harian, rekapitulasi pengeluaran operasional, dan laba kotor.</p>
        </div>
        <a href="export_excel.php" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-5 rounded-2xl text-sm shadow-lg shadow-emerald-600/20 hover:shadow-xl transition-all cursor-pointer">
            <i class="fas fa-file-excel text-base"></i>
            <span>Export ke Excel</span>
        </a>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pendapatan (30 Hari)</p>
                <h3 class="text-2xl font-black text-emerald-600 font-outfit mt-1">+<?= formatRupiah($totalRevenue30) ?></h3>
                <p class="text-[10px] text-emerald-700 font-bold mt-1 flex items-center"><i class="fas fa-arrow-up mr-1"></i>Omset Bruto</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0 shadow-inner">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pengeluaran Operasional</p>
                <h3 class="text-2xl font-black text-rose-500 font-outfit mt-1">-<?= formatRupiah($totalExpenses50) ?></h3>
                <p class="text-[10px] text-rose-600 font-bold mt-1 flex items-center"><i class="fas fa-arrow-down mr-1"></i>Biaya Operasional</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shrink-0 shadow-inner">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Estimasi Laba Kotor</p>
                <h3 class="text-2xl font-black text-slate-800 font-outfit mt-1"><?= formatRupiah($netProfitEstimate) ?></h3>
                <p class="text-[10px] text-blue-600 font-bold mt-1 flex items-center"><i class="fas fa-calculator mr-1"></i>Omset - Pengeluaran</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0 shadow-inner">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl mb-6 font-semibold flex items-center gap-3 shadow-sm">
        <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
        <span>Pengeluaran berhasil ditambahkan!</span>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Daily Sales Section -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 flex flex-col h-[520px]">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <h2 class="font-extrabold text-lg text-slate-800 font-outfit flex items-center">
                    <i class="fas fa-chart-line text-emerald-600 mr-2.5"></i> Pendapatan 30 Hari Terakhir
                </h2>
                <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-md">Real-time</span>
            </div>
            
            <div class="space-y-3 overflow-y-auto pr-2 flex-1 scrollbar-thin scrollbar-thumb-slate-200">
                <?php if (empty($dailyReport)): ?>
                    <div class="flex flex-col items-center justify-center h-full text-slate-400">
                        <i class="fas fa-chart-bar text-4xl text-slate-200 mb-2"></i>
                        <p class="font-medium text-sm">Belum ada data pendapatan.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($dailyReport as $report): ?>
                    <div class="border border-slate-100 rounded-2xl p-4 bg-slate-50/50 hover:bg-slate-50 transition duration-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-extrabold text-slate-800 font-outfit text-base"><?= date('d M Y', strtotime($report['date'])) ?></p>
                                <p class="text-xs text-slate-400 font-medium mt-0.5"><i class="fas fa-receipt mr-1"></i> <?= $report['orders'] ?> transaksi selesai</p>
                            </div>
                            <p class="font-extrabold text-emerald-600 text-lg font-outfit">+<?= formatRupiah($report['revenue']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expenses Section -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 flex flex-col h-[520px]">
            <div class="flex justify-between items-center pb-4 mb-4 border-b border-slate-100">
                <h2 class="font-extrabold text-lg text-slate-800 font-outfit flex items-center">
                    <i class="fas fa-money-bill-wave text-rose-500 mr-2.5"></i> Pengeluaran Operasional
                </h2>
                <button onclick="document.getElementById('addExpenseForm').classList.toggle('hidden')" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2 px-3.5 rounded-xl transition-all">
                    <i class="fas fa-plus mr-1"></i> Catat Biaya
                </button>
            </div>

            <!-- Add Expense Form (Hidden by default) -->
            <form id="addExpenseForm" action="process_expense.php" method="POST" class="hidden mb-4 p-5 bg-slate-50 border border-slate-200 rounded-2xl">
                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tanggal</label>
                            <input type="date" name="expense_date" required value="<?= date('Y-m-d') ?>" class="w-full bg-white px-3 py-2 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nominal (Rp)</label>
                            <input type="number" name="amount" required min="0" step="100" placeholder="50000" class="w-full bg-white px-3 py-2 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Keterangan (Cth: Beli Gula, Listrik)</label>
                        <input type="text" name="description" required placeholder="Keterangan rincian pengeluaran" class="w-full bg-white px-3 py-2 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div class="pt-1">
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-xl transition-all text-xs">
                            Simpan Pengeluaran
                        </button>
                    </div>
                </div>
            </form>

            <div class="space-y-3 overflow-y-auto pr-2 flex-1 scrollbar-thin scrollbar-thumb-slate-200">
                <?php if (empty($expenses)): ?>
                    <div class="flex flex-col items-center justify-center h-full text-slate-400">
                        <i class="fas fa-file-invoice text-4xl text-slate-200 mb-2"></i>
                        <p class="font-medium text-sm">Belum ada data pengeluaran.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($expenses as $expense): ?>
                    <div class="border border-slate-100 rounded-2xl p-4 bg-rose-50/20 hover:bg-rose-50/40 transition duration-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-extrabold text-slate-800 font-outfit text-sm line-clamp-1"><?= htmlspecialchars($expense['description']) ?></p>
                                <p class="text-xs text-slate-400 font-medium mt-1">
                                    <i class="fas fa-calendar-alt mr-1"></i> <?= date('d M Y', strtotime($expense['expense_date'])) ?>
                                    <span class="mx-1">&bull;</span>
                                    <i class="fas fa-user mr-1"></i> <?= htmlspecialchars($expense['creator_name']) ?>
                                </p>
                            </div>
                            <p class="font-extrabold text-rose-500 text-base font-outfit whitespace-nowrap ml-3">-<?= formatRupiah($expense['amount']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>

