<?php
/**
 * Payment Verification API
 * Handle QRIS payment proof verification
 */
require_once '../config/config.php';
requireRole(['kasir', 'owner']);

$action = clean($_POST['action'] ?? '');
$paymentId = (int)($_POST['payment_id'] ?? 0);

if (!$paymentId) {
    sendJSON(['success' => false, 'message' => 'Invalid payment ID'], 400);
}

$db = getDB();

// Get payment details
$stmt = $db->prepare("
    SELECT p.*, o.id as order_id, o.order_number, o.customer_name, o.total
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.id = ?
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    sendJSON(['success' => false, 'message' => 'Payment not found'], 404);
}

if ($payment['payment_method'] !== 'qris' && $payment['payment_method'] !== 'transfer') {
    sendJSON(['success' => false, 'message' => 'Invalid payment method'], 400);
}

if (!$payment['proof_of_payment']) {
    sendJSON(['success' => false, 'message' => 'No proof of payment uploaded'], 400);
}

$userId = (int)$_SESSION['user_id'];

try {
    $db->beginTransaction();
    
    if ($action === 'approve') {
        // Step 1: Update verification status
        $stmt = $db->prepare("UPDATE payments SET verification_status = 'verified', status = 'success', verified_by = ?, verified_at = NOW(), paid_at = NOW(), paid_amount = amount WHERE id = ?");
        $stmt->execute([$userId, $paymentId]);
        
        // Step 2: Update order status to completed
        $stmt = $db->prepare("UPDATE orders SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->execute([(int)$payment['order_id']]);
        
        // Create audit log
        createAuditLog('verify_payment', 'payments', $paymentId, 
            ['verification_status' => 'pending', 'status' => 'pending'],
            ['verification_status' => 'verified', 'status' => 'success']
        );
        
        // Step 3: Release table
        $stmt = $db->prepare("SELECT table_id FROM orders WHERE id = ?");
        $stmt->execute([(int)$payment['order_id']]);
        $tableData = $stmt->fetch();
        
        if ($tableData && $tableData['table_id']) {
            $stmt = $db->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
            $stmt->execute([(int)$tableData['table_id']]);
        }
        
        $db->commit();
        sendJSON([
            'success' => true, 
            'message' => 'Pembayaran terverifikasi! Pesanan selesai.',
            'order_id' => $payment['order_id']
        ]);
        
    } elseif ($action === 'reject') {
        $reason = clean($_POST['reason'] ?? '');
        if (empty($reason)) {
            sendJSON(['success' => false, 'message' => 'Alasan penolakan harus diisi'], 400);
        }
        
        // Step 1: Update verification status
        $stmt = $db->prepare("UPDATE payments SET verification_status = 'rejected', status = 'failed', verified_by = ?, verified_at = NOW(), verification_notes = ? WHERE id = ?");
        $stmt->execute([$userId, $reason, $paymentId]);
        
        // Step 2: Update order status to cancelled
        $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([(int)$payment['order_id']]);
        
        // Step 3: Release table
        $stmt = $db->prepare("SELECT table_id FROM orders WHERE id = ?");
        $stmt->execute([$payment['order_id']]);
        $tableData = $stmt->fetch();
        
        if ($tableData && $tableData['table_id']) {
            $stmt = $db->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
            $stmt->execute([(int)$tableData['table_id']]);
        }
        
        // Create audit log
        createAuditLog('reject_payment', 'payments', $paymentId, 
            ['verification_status' => 'pending', 'status' => 'pending'],
            ['verification_status' => 'rejected', 'status' => 'failed', 'notes' => $reason]
        );
        
        $db->commit();
        sendJSON([
            'success' => true, 
            'message' => 'Pembayaran ditolak. Pesanan dibatalkan.'
        ]);
        
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Payment verification error: ' . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}

