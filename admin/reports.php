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

include '../includes/header.php';
?>
<main class="p-4 max-w-7xl mx-auto pb-32 sm:pb-24">
    <!-- Header with Excel Export Button -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-stone-850 font-outfit">Laporan Sistem</h1>
            <p class="text-stone-500 text-sm mt-1">Kelola pendapatan, pengeluaran, dan pantau stok herbal.</p>
        </div>
        <a href="export_excel.php" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-xl shadow-md transition duration-200 flex items-center transform hover:-translate-y-0.5">
            <i class="fas fa-file-excel text-xl mr-2"></i>
            Export ke Excel
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center">
        <i class="fas fa-check-circle mr-2 text-lg"></i>
        <span class="font-bold">Pengeluaran berhasil ditambahkan!</span>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Daily Sales Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 flex flex-col h-[500px]">
            <h2 class="font-extrabold text-lg text-stone-800 mb-4 font-outfit flex items-center">
                <i class="fas fa-chart-line text-emerald-600 mr-2"></i> 30 Hari Terakhir (Pendapatan)
            </h2>
            <div class="space-y-3 overflow-y-auto pr-2 flex-1 scrollbar-thin scrollbar-thumb-stone-300">
                <?php if (empty($dailyReport)): ?>
                    <p class="text-stone-500 text-center py-8">Belum ada data pendapatan.</p>
                <?php else: ?>
                    <?php foreach ($dailyReport as $report): ?>
                    <div class="border border-stone-100 rounded-xl p-4 bg-stone-50/50 hover:bg-stone-50 transition">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-bold text-stone-800"><?= date('d M Y', strtotime($report['date'])) ?></p>
                                <p class="text-xs text-stone-500 font-medium mt-1"><i class="fas fa-receipt mr-1"></i> <?= $report['orders'] ?> pesanan</p>
                            </div>
                            <p class="font-extrabold text-emerald-600 text-lg font-outfit">+<?= formatRupiah($report['revenue']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expenses Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 flex flex-col h-[500px]">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-extrabold text-lg text-stone-800 font-outfit flex items-center">
                    <i class="fas fa-money-bill-wave text-red-500 mr-2"></i> Pengeluaran Operasional
                </h2>
                <button onclick="document.getElementById('addExpenseForm').classList.toggle('hidden')" class="text-sm bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold py-1.5 px-3 rounded-lg transition">
                    <i class="fas fa-plus mr-1"></i> Tambah
                </button>
            </div>

            <!-- Add Expense Form (Hidden by default) -->
            <form id="addExpenseForm" action="process_expense.php" method="POST" class="hidden mb-4 p-4 bg-stone-50 border border-stone-200 rounded-xl">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Tanggal</label>
                        <input type="date" name="expense_date" required value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Keterangan (Cth: Beli Gula, Bayar Listrik)</label>
                        <input type="text" name="description" required placeholder="Keterangan pengeluaran" class="w-full px-3 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-700 mb-1">Nominal (Rp)</label>
                        <input type="number" name="amount" required min="0" step="100" placeholder="50000" class="w-full px-3 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="w-full bg-stone-800 hover:bg-stone-900 text-white font-bold py-2.5 rounded-lg transition">
                            Simpan Pengeluaran
                        </button>
                    </div>
                </div>
            </form>

            <div class="space-y-3 overflow-y-auto pr-2 flex-1 scrollbar-thin scrollbar-thumb-stone-300">
                <?php if (empty($expenses)): ?>
                    <p class="text-stone-500 text-center py-8">Belum ada data pengeluaran.</p>
                <?php else: ?>
                    <?php foreach ($expenses as $expense): ?>
                    <div class="border border-stone-100 rounded-xl p-4 bg-red-50/30 hover:bg-red-50/50 transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-bold text-stone-800 line-clamp-1"><?= htmlspecialchars($expense['description']) ?></p>
                                <p class="text-xs text-stone-500 font-medium mt-1">
                                    <i class="fas fa-calendar-alt mr-1"></i> <?= date('d M Y', strtotime($expense['expense_date'])) ?>
                                    <span class="mx-1">&bull;</span>
                                    <i class="fas fa-user mr-1"></i> <?= htmlspecialchars($expense['creator_name']) ?>
                                </p>
                            </div>
                            <p class="font-extrabold text-red-500 text-lg font-outfit whitespace-nowrap ml-3">-<?= formatRupiah($expense['amount']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
