<?php
require_once '../config/config.php';
requireRole(['owner']);
$pageTitle = 'Meja';
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
include '../includes/header.php';
?>
<main class="p-4 pb-32 sm:pb-24">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="font-bold text-lg mb-4">Daftar Meja</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <?php foreach ($tables as $table): ?>
            <div class="border rounded-lg p-4 text-center">
                <i class="fas fa-chair text-3xl mb-2 <?= $table['status'] == 'available' ? 'text-green-600' : 'text-red-600' ?>"></i>
                <p class="font-bold text-lg">Meja <?= $table['table_number'] ?></p>
                <p class="text-sm text-gray-600">Kapasitas: <?= $table['capacity'] ?> orang</p>
                <span class="inline-block mt-2 px-3 py-1 text-xs rounded-full <?= getStatusBadge($table['status']) ?>">
                    <?= getStatusText($table['status']) ?>
                </span>
                <div class="mt-3 flex items-center justify-center gap-3">
                    <a href="?toggle=<?= $table['id'] ?>" class="text-sm text-blue-600 hover:text-blue-800 bg-blue-50 px-3 py-1.5 rounded-lg transition-colors font-semibold">
                        <?= $table['status'] == 'available' ? 'Set Terisi' : 'Set Tersedia' ?>
                    </a>
                    <a href="print_qr.php?id=<?= $table['id'] ?>" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-lg transition-colors font-semibold" title="Cetak QR Code">
                        <i class="fas fa-qrcode mr-1"></i> QR Code
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
