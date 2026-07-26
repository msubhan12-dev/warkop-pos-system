<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$orderNumber = clean($_GET['order'] ?? '');

if (empty($orderNumber)) {
    sendJSON(['success' => false, 'message' => 'Order number required'], 400);
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT 
            o.status as order_status,
            p.payment_method,
            p.verification_status,
            p.amount,
            o.total
        FROM orders o
        LEFT JOIN payments p ON o.id = p.order_id
        WHERE o.order_number = ?
    ");
    $stmt->execute([$orderNumber]);
    $payment = $stmt->fetch();

    if (!$payment) {
        sendJSON([
            'success' => false,
            'message' => 'Order not found',
            'verified' => false,
            'status' => 'not_found'
        ], 404);
    }

    $status = $payment['verification_status'] ?? 'pending';
    $isVerified = $status === 'verified';
    $isRejected = $status === 'rejected';

    sendJSON([
        'success' => true,
        'order_number' => $orderNumber,
        'payment_method' => $payment['payment_method'] ?? 'cash',
        'verification_status' => $status,
        'verified' => $isVerified,
        'rejected' => $isRejected,
        'status' => $payment['order_status'],
        'amount' => $payment['amount'] ?? $payment['total'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Error checking payment status',
        'error' => $e->getMessage()
    ], 500);
}
