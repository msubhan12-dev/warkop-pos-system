<?php
require_once '../config/config.php';
requireRole(['dapur', 'owner']);

header('Content-Type: application/json');

$db = getDB();

// Count new tickets
$stmt = $db->query("
    SELECT COUNT(*) as count
    FROM kitchen_tickets
    WHERE status = 'new'
");
$result = $stmt->fetch();

// Get latest order info
$stmt = $db->query("
    SELECT 
        kt.ticket_number,
        kt.menu_name,
        kt.table_number,
        o.customer_name
    FROM kitchen_tickets kt
    JOIN orders o ON kt.order_id = o.id
    WHERE kt.status = 'new'
    ORDER BY kt.created_at DESC
    LIMIT 1
");
$latestOrder = $stmt->fetch();

sendJSON([
    'success' => true,
    'new_ticket_count' => (int)$result['count'],
    'cooking_count' => (int)$db->query("SELECT COUNT(*) FROM kitchen_tickets WHERE status = 'cooking'")->fetch()['count'],
    'latest_order' => $latestOrder
]);
