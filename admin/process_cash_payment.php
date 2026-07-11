<?php
require_once '../config/config.php';
requireRole(['kasir', 'owner']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$paymentId = $_POST['payment_id'] ?? null;

if (!$paymentId) {
    echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
    exit;
}

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
?>
