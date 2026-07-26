<?php
require_once '../config/config.php';
requireRole(['kasir', 'owner', 'admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$paymentId = $_POST['payment_id'] ?? null;
$newStatus = $_POST['status'] ?? null;

// Debug logging
error_log("DEBUG: paymentId=$paymentId, newStatus=$newStatus, user_id=" . ($_SESSION['user_id'] ?? 'NOT_SET'));

if (!$paymentId) {
    echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
    exit;
}

// If status is provided, this is a status update (from admin detail modal or POS)
// If status is not provided, this is the legacy confirmation endpoint
if ($newStatus) {
    // New endpoint: Update payment status ('success' or 'pending')
    if (!in_array($newStatus, ['success', 'pending'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        // Get payment details
        $stmt = $db->prepare("SELECT p.*, o.order_number, o.id as order_id FROM payments p JOIN orders o ON p.order_id = o.id WHERE p.id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            throw new Exception('Payment not found');
        }

        // Update payment status
        if ($newStatus === 'success') {
            // Mark as paid
            $stmt = $db->prepare("UPDATE payments SET status = 'success', paid_amount = amount, verified_by = ?, verified_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $paymentId]);
            
            // Update order status to confirmed (ready for kitchen)
            $stmt = $db->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$payment['order_id']]);
            
            $auditNewStatus = 'success';
            $auditMessage = 'Mark cash payment as paid (Sudah Bayar)';
        } else {
            // Mark as not paid yet
            $stmt = $db->prepare("UPDATE payments SET status = 'pending', paid_amount = 0, verified_by = NULL, verified_at = NULL WHERE id = ?");
            $stmt->execute([$paymentId]);
            
            // Order stays in confirmed state (but payment is pending)
            $auditNewStatus = 'pending';
            $auditMessage = 'Mark cash payment as not paid (Belum Bayar)';
        }

        // Audit log
        createAuditLog('update', 'payments', $paymentId, $payment['status'], [
            'new_status' => $auditNewStatus,
            'verified_by' => $_SESSION['username'],
            'order_number' => $payment['order_number'],
            'notes' => $auditMessage
        ]);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Status pembayaran berhasil diperbarui']);

    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
} else {
    // Legacy endpoint: Direct confirmation (backward compatibility)
    try {
        $db = getDB();
        $db->beginTransaction();

        // Get payment details
        $stmt = $db->prepare("SELECT p.*, o.order_number, o.customer_name FROM payments p JOIN orders o ON p.order_id = o.id WHERE p.id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            throw new Exception('Payment not found');
        }

        if ($payment['status'] === 'success') {
            throw new Exception('Pembayaran ini sudah lunas');
        }

        // Update payment status to success and paid_amount to amount
        $stmt = $db->prepare("UPDATE payments SET status = 'success', paid_amount = amount, verified_by = ?, verified_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $paymentId]);
        
        // Update order status if necessary (it's likely already 'confirmed', but we can make sure)
        $stmt = $db->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$payment['order_id']]);

        // Audit log
        createAuditLog('update', 'payments', $paymentId, $payment['status'], [
            'new_status' => 'success',
            'verified_by' => $_SESSION['username'],
            'order_number' => $payment['order_number']
        ]);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Pembayaran tunai berhasil diterima']);

    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
}
?>
