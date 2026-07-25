<?php
/**
 * Real-time Pending Payments API
 * Returns all pending QRIS/Transfer payments for dashboard updates
 */
require_once '../config/config.php';
requireRole(['kasir', 'owner', 'admin']);

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$db = getDB();

// Get all pending QRIS payments with order details
$stmt = $db->prepare("
    SELECT 
        p.id as payment_id,
        p.amount,
        p.payment_method,
        p.verification_status,
        p.proof_of_payment,
        p.created_at as payment_created,
        o.id as order_id,
        o.order_number,
        o.customer_name,
        o.status as order_status,
        o.created_at as order_created,
        COUNT(oi.id) as item_count
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE p.verification_status = 'pending' 
        AND p.payment_method IN ('qris', 'transfer')
        AND o.status NOT IN ('cancelled', 'rejected')
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 50
");
$stmt->execute();
$pendingPayments = $stmt->fetchAll();

sendJSON([
    'success' => true,
    'count' => count($pendingPayments),
    'payments' => array_map(function($payment) {
        return [
            'payment_id' => $payment['payment_id'],
            'order_id' => $payment['order_id'],
            'order_number' => $payment['order_number'],
            'customer_name' => $payment['customer_name'],
            'amount' => $payment['amount'],
            'method' => $payment['payment_method'],
            'items' => $payment['item_count'],
            'status' => $payment['verification_status'],
            'has_proof' => !empty($payment['proof_of_payment']),
            'created_at' => $payment['payment_created'],
            'order_created' => $payment['order_created']
        ];
    }, $pendingPayments)
]);
