<?php
/**
 * Check Payment Verification Status
 * AJAX endpoint for real-time polling
 */
require_once '../config/config.php';

// Prevent caching - always get fresh data
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$orderNumber = clean($_GET['order'] ?? '');

if (empty($orderNumber)) {
    sendJSON(['verified' => false, 'message' => 'Invalid order'], 400);
}

// Get database connection
$db = getDB();

$stmt = $db->prepare("
    SELECT p.id, p.verification_status, p.status as payment_status, o.status as order_status, o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE o.order_number = ?
");
$stmt->execute([$orderNumber]);
$payment = $stmt->fetch();

if (!$payment) {
    sendJSON(['verified' => false, 'message' => 'Payment not found'], 404);
}

// Consider verified if verification_status is verified, or payment is success, or order is completed
$isVerified = ($payment['verification_status'] === 'verified' || $payment['payment_status'] === 'success' || $payment['order_status'] === 'completed');

sendJSON([
    'verified' => $isVerified,
    'status' => $isVerified ? 'verified' : $payment['verification_status']
]);
