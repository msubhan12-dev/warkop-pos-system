<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir', 'dapur', 'pelayan']);

$order_id = intval($_GET['order_id'] ?? 0);

if (!$order_id) {
    header('Location: orders');
    exit;
}

$db = getDB();
$stmt = $db->prepare("
    SELECT o.*, t.table_number, COUNT(oi.id) as item_count, SUM(oi.quantity) as total_items
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.id = ?
    GROUP BY o.id
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo "Order not found";
    exit;
}

// Get order items
$stmt = $db->prepare("
    SELECT oi.*, m.name as menu_name, m.price
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="p-6 max-w-6xl mx-auto">
    <div class="grid grid-cols-3 gap-6">
        <!-- Order Details (Left) -->
        <div class="col-span-2">
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-slate-800">Order #<?= htmlspecialchars($order['order_number']) ?></h1>
                    <span class="px-4 py-2 bg-<?= statusColor($order['status']) ?>-100 text-<?= statusColor($order['status']) ?>-700 rounded-lg font-bold">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b border-slate-200">
                    <div>
                        <p class="text-slate-500 text-sm font-medium">Customer</p>
                        <p class="text-slate-800 font-bold"><?= htmlspecialchars($order['customer_name']) ?></p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm font-medium">Table</p>
                        <p class="text-slate-800 font-bold"><?= $order['table_number'] ?? 'Takeaway' ?></p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm font-medium">Time</p>
                        <p class="text-slate-800 font-bold"><?= date('H:i:s', strtotime($order['created_at'])) ?></p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm font-medium">Items</p>
                        <p class="text-slate-800 font-bold"><?= $order['total_items'] ?> item(s)</p>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-4">Items</h3>
                    <div class="space-y-2">
                        <?php foreach ($items as $item): ?>
                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                            <div>
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($item['menu_name']) ?></p>
                                <p class="text-sm text-slate-500">Qty: <?= $item['quantity'] ?></p>
                            </div>
                            <p class="font-bold text-slate-800"><?= formatRupiah($item['price'] * $item['quantity']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Total -->
                <div class="text-right pt-4 border-t border-slate-200">
                    <p class="text-slate-500 text-sm mb-2">Total Amount</p>
                    <p class="text-3xl font-bold text-emerald-600"><?= formatRupiah($order['total']) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Chat Widget (Right) -->
        <div class="col-span-1">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-blue-50 border-b border-slate-200">
                    <h3 class="font-bold text-slate-800 flex items-center">
                        <i class="fas fa-comments mr-2 text-blue-600"></i>
                        Order Chat
                    </h3>
                </div>
                <?php include 'chat_widget.php'; ?>
            </div>
        </div>
    </div>
</div>

<?php
function statusColor($status) {
    $colors = [
        'pending' => 'yellow',
        'confirmed' => 'blue',
        'cooking' => 'orange',
        'ready' => 'green',
        'served' => 'purple',
        'completed' => 'emerald',
        'cancelled' => 'red'
    ];
    return $colors[$status] ?? 'slate';
}

include '../includes/footer.php';
?>
