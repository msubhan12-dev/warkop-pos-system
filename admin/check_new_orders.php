<?php
require_once '../config/config.php';
requireRole(['admin', 'owner', 'kasir']);

$db = getDB();
$since = $_GET['since'] ?? date('Y-m-d H:i:s', time() - 30);

$stmt = $db->prepare("
    SELECT o.id, o.order_number, o.customer_name, o.status, o.payment_method, o.proof_of_payment, o.total, t.table_number
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE (o.created_at > ? OR o.updated_at > ?) 
    AND o.status IN ('pending', 'processing')
");
$stmt->execute([$since, $since]);
$orders = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'new_orders' => count($orders),
    'orders' => $orders
]);
