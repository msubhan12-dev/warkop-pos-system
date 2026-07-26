<?php
require_once '../config/config.php';
requireRole(['owner', 'admin']);

$user = getCurrentUser();
$db = getDB();
$pageTitle = 'Verifikasi Pembayaran QRIS';

// Handle payment verification (AJAX endpoint - used by orders.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $paymentId = (int)$_POST['payment_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

    $status = ($action === 'approve') ? 'verified' : 'rejected';
    $currentUser = getCurrentUser();
    $verifiedById = $currentUser['id'] ?? null;
    $verifiedAt = date('Y-m-d H:i:s');
    
    // Get order_id for this payment
    $stmt = $db->prepare("SELECT order_id FROM payments WHERE id = ?");
    $stmt->execute([$paymentId]);
    $paymentRow = $stmt->fetch();
    $orderId = $paymentRow['order_id'] ?? null;
    
    // Update payment status with verification details
    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE payments SET verification_status = ?, verified_by = ?, verified_at = ?, verification_notes = NULL WHERE id = ?");
        $stmt->execute([$status, $verifiedById, $verifiedAt, $paymentId]);
    } else {
        $stmt = $db->prepare("UPDATE payments SET verification_status = ?, verified_by = ?, verified_at = ?, verification_notes = ? WHERE id = ?");
        $stmt->execute([$status, $verifiedById, $verifiedAt, $reason, $paymentId]);
    }
    
    // Update order status based on payment verification
    if ($orderId) {
        if ($action === 'approve') {
            // Payment approved - move order to "pending" (waiting to be cooked)
            $stmt = $db->prepare("UPDATE orders SET status = 'pending' WHERE id = ?");
            $stmt->execute([$orderId]);
        } else {
            // Payment rejected - move order back to "confirmed" (waiting for re-payment)
            $stmt = $db->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$orderId]);
        }
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => ($action === 'approve') ? 'Pembayaran berhasil diverifikasi!' : 'Pembayaran ditolak.',
        'action' => $action,
        'payment_id' => $paymentId,
        'order_id' => $orderId
    ]);
    exit;
}

// If GET request, redirect to orders page (this page is AJAX endpoint only, not a UI page)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: orders.php?tab=orders');
    exit;
}

// Page should never reach here - all requests are handled above
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;


