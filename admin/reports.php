<?php
require_once '../config/config.php';
requireRole(['owner']);
$pageTitle = 'Laporan';
$user = getCurrentUser();
$db = getDB();
$stmt = $db->query("SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue FROM orders WHERE status = 'completed' GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30");
$dailyReport = $stmt->fetchAll();
include '../includes/header.php';
?>
<main class="p-4 pb-32 sm:pb-24">
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <h2 class="font-bold text-lg mb-4">Laporan Harian</h2>
        <div class="space-y-3">
            <?php foreach ($dailyReport as $report): ?>
            <div class="border-b pb-3">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-semibold"><?= date('d M Y', strtotime($report['date'])) ?></p>
                        <p class="text-sm text-gray-600"><?= $report['orders'] ?> pesanan</p>
                    </div>
                    <p class="font-bold text-green-600"><?= formatRupiah($report['revenue']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
