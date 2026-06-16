<?php
require_once '../config/config.php';
requireRole(['owner']);
$pageTitle = 'Pesanan';
$user = getCurrentUser();
$db = getDB();

// View detail
$detailId = $_GET['detail'] ?? null;
$orderDetail = null;
$orderItems = [];
if ($detailId) {
    $stmt = $db->prepare("SELECT o.*, t.table_number, u.full_name as kasir_name FROM orders o LEFT JOIN tables t ON o.table_id = t.id LEFT JOIN users u ON o.created_by = u.id WHERE o.id = ?");
    $stmt->execute([$detailId]);
    $orderDetail = $stmt->fetch();
    
    $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$detailId]);
    $orderItems = $stmt->fetchAll();
}

$stmt = $db->query("SELECT o.*, t.table_number FROM orders o LEFT JOIN tables t ON o.table_id = t.id ORDER BY o.created_at DESC LIMIT 50");
$orders = $stmt->fetchAll();
include '../includes/header.php';
?>
<main class="p-4 pb-20">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="font-bold text-lg mb-4">Daftar Pesanan</h2>
        <div class="space-y-3">
            <?php foreach ($orders as $order): ?>
            <div class="border rounded-lg p-3">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="font-bold"><?= $order['order_number'] ?></p>
                        <p class="text-sm text-gray-600"><?= $order['customer_name'] ?> • Meja <?= $order['table_number'] ?? 'TA' ?></p>
                    </div>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?= getStatusBadge($order['status']) ?>">
                        <?= getStatusText($order['status']) ?>
                    </span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600"><?= formatDateTime($order['created_at']) ?></span>
                    <div class="space-x-2">
                        <span class="font-bold text-slate-700"><?= formatRupiah($order['total']) ?></span>
                        <a href="?detail=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Detail Modal -->
<?php if ($orderDetail): ?>
<div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Detail Pesanan</h3>
            <a href="orders.php" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-times text-xl"></i>
            </a>
        </div>
        <div class="space-y-3">
            <div class="border-b pb-3">
                <p class="text-sm text-gray-600">No. Pesanan</p>
                <p class="font-bold"><?= $orderDetail['order_number'] ?></p>
            </div>
            <div class="border-b pb-3">
                <p class="text-sm text-gray-600">Customer</p>
                <p class="font-semibold"><?= $orderDetail['customer_name'] ?></p>
                <p class="text-sm text-gray-500"><?= $orderDetail['customer_phone'] ?></p>
            </div>
            <div class="border-b pb-3">
                <p class="text-sm text-gray-600">Detail</p>
                <p class="text-sm">Meja: <?= $orderDetail['table_number'] ?? 'Take Away' ?></p>
                <p class="text-sm">Tipe: <?= getStatusText($orderDetail['order_type']) ?></p>
                <p class="text-sm">Kasir: <?= $orderDetail['kasir_name'] ?? '-' ?></p>
            </div>
            <div class="border-b pb-3">
                <p class="text-sm text-gray-600 mb-2">Item Pesanan</p>
                <?php foreach ($orderItems as $item): ?>
                <div class="flex justify-between text-sm mb-1">
                    <span><?= $item['quantity'] ?>x <?= $item['menu_name'] ?></span>
                    <span class="font-semibold"><?= formatRupiah($item['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span>Subtotal</span>
                    <span><?= formatRupiah($orderDetail['subtotal']) ?></span>
                </div>
                <div class="flex justify-between text-sm mb-2">
                    <span>Pajak</span>
                    <span><?= formatRupiah($orderDetail['tax']) ?></span>
                </div>
                <div class="flex justify-between font-bold text-lg">
                    <span>Total</span>
                    <span class="text-green-600"><?= formatRupiah($orderDetail['total']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
