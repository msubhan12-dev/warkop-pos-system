<?php
require_once '../config/config.php';
requireRole(['admin', 'owner', 'kasir']);

$db = getDB();
$since = $_GET['since'] ?? date('Y-m-d H:i:s', time() - 30);

$stmt = $db->prepare("
    SELECT o.id, o.order_number, o.customer_name, o.status, p.payment_method, p.proof_of_payment, o.total, t.table_number, o.created_at, o.updated_at
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE (o.created_at > ? OR o.updated_at > ?) 
    AND o.status IN ('pending', 'processing', 'confirmed')
");
$stmt->execute([$since, $since]);
$orders = $stmt->fetchAll();
$maxTimestamp = $since;
foreach ($orders as $order) {
    if (isset($order['updated_at']) && $order['updated_at'] > $maxTimestamp) $maxTimestamp = $order['updated_at'];
    if (isset($order['created_at']) && $order['created_at'] > $maxTimestamp) $maxTimestamp = $order['created_at'];
}

echo json_encode([  
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'), // We won't use this in JS for polling anymore
    'max_order_time' => count($orders) > 0 ? $maxTimestamp : null,
    'new_orders' => count($orders),
    'orders' => $orders
]);
