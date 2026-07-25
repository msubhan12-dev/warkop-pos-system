<?php
require_once '../config/config.php';
requireRole(['owner', 'admin', 'kasir']);
$pageTitle = 'Kelola Meja';
$user = getCurrentUser();
$db = getDB();

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE tables SET status = IF(status='available', 'occupied', 'available') WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: tables.php');
    exit;
}

$stmt = $db->query("SELECT * FROM tables WHERE is_active = 1 ORDER BY table_number");
$tables = $stmt->fetchAll();

// Calculate stats
$totalTables = count($tables);
$availableTablesCount = count(array_filter($tables, fn($t) => $t['status'] === 'available'));
$occupiedTablesCount = $totalTables - $availableTablesCount;

include '../includes/header.php';
?>

<main class="p-4 sm:p-6 pb-32 sm:pb-24 max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 font-outfit tracking-tight">Manajemen Meja Kedai</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Pantau status ketersediaan meja dan cetak QR Code pemesanan meja.</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i class="fas fa-chair text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Meja</p>
                <p class="text-xl font-extrabold text-slate-800 font-outfit mt-0.5"><?= $totalTables ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Meja Kosong (Tersedia)</p>
                <p class="text-xl font-extrabold text-emerald-600 font-outfit mt-0.5"><?= $availableTablesCount ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                <i class="fas fa-user-friends text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Meja Terisi</p>
                <p class="text-xl font-extrabold text-rose-500 font-outfit mt-0.5"><?= $occupiedTablesCount ?></p>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm mb-6 flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" id="tableSearchInput" onkeyup="filterTables()" placeholder="Cari nomor meja atau kapasitas..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
        </div>

        <div class="sm:w-56">
            <select id="tableFilterSelect" onchange="filterTables()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                <option value="all">Semua Status Meja</option>
                <option value="available">Tersedia (Kosong)</option>
                <option value="occupied">Terisi</option>
            </select>
        </div>
    </div>

    <!-- Grid Container -->
    <div id="tablesGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
        <?php foreach ($tables as $table): ?>
        <?php $isAvail = $table['status'] === 'available'; ?>
        <div class="table-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group text-center"
             data-number="<?= htmlspecialchars($table['table_number']) ?>"
             data-capacity="<?= $table['capacity'] ?>"
             data-status="<?= $table['status'] ?>">
            
            <div>
                <div class="w-16 h-16 rounded-2xl mx-auto mb-3 flex items-center justify-center transition-transform group-hover:scale-110 <?= $isAvail ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-500' ?>">
                    <i class="fas fa-chair text-3xl"></i>
                </div>

                <h3 class="font-extrabold text-slate-800 text-lg font-outfit">Meja <?= htmlspecialchars($table['table_number']) ?></h3>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Kapasitas: <?= $table['capacity'] ?> Orang</p>

                <div class="my-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold <?= $isAvail ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-rose-50 text-rose-600 border border-rose-200/60' ?>">
                        <span class="w-2 h-2 rounded-full mr-1.5 <?= $isAvail ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                        <?= $isAvail ? 'Kosong / Tersedia' : 'Terisi' ?>
                    </span>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-center gap-2 mt-2">
                <a href="?toggle=<?= $table['id'] ?>" class="flex-1 inline-flex items-center justify-center gap-1 py-2 px-3 rounded-xl text-xs font-bold transition-all shadow-sm <?= $isAvail ? 'bg-rose-50 text-rose-600 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>">
                    <i class="fas <?= $isAvail ? 'fa-user-check' : 'fa-check' ?>"></i>
                    <span><?= $isAvail ? 'Set Terisi' : 'Set Kosong' ?></span>
                </a>

                <a href="print_qr.php?id=<?= $table['id'] ?>" target="_blank" title="Cetak QR Code Pemesanan" class="w-9 h-9 shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl flex items-center justify-center transition-colors">
                    <i class="fas fa-qrcode text-sm"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
function filterTables() {
    const searchVal = document.getElementById('tableSearchInput').value.toLowerCase().trim();
    const filterVal = document.getElementById('tableFilterSelect').value;
    const cards = document.querySelectorAll('.table-card');

    cards.forEach(card => {
        const number = card.getAttribute('data-number').toLowerCase();
        const capacity = card.getAttribute('data-capacity');
        const status = card.getAttribute('data-status');

        const matchesSearch = number.includes(searchVal) || capacity.includes(searchVal);
        const matchesStatus = (filterVal === 'all') || (status === filterVal);

        if (matchesSearch && matchesStatus) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>

