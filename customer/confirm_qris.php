<?php
require_once '../config/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$orderNumber = clean($_POST['order'] ?? '');
if (empty($orderNumber)) {
    echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // Get order and payment details
    $stmt = $db->prepare("
        SELECT o.id as order_id, o.status as order_status, o.customer_name, o.total,
               p.id as payment_id, p.verification_status, p.status as payment_status
        FROM orders o
        JOIN payments p ON o.id = p.order_id
        WHERE o.order_number = ? AND p.payment_method = 'qris'
        LIMIT 1
    ");
    $stmt->execute([$orderNumber]);
    $row = $stmt->fetch();

    if (!$row) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
        exit;
    }

    // Already verified — silently succeed
    if ($row['verification_status'] === 'verified') {
        $db->rollBack();
        echo json_encode(['success' => true, 'message' => 'Pembayaran sudah dikonfirmasi']);
        exit;
    }

    // Auto-verify the payment (QRIS Auto-Verification)
    $stmt = $db->prepare("
        UPDATE payments
        SET status = 'success', verification_status = 'verified', paid_amount = amount, verified_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$row['payment_id']]);

    // Confirm the order so it goes to dapur
    $stmt = $db->prepare("
        UPDATE orders SET status = 'confirmed', completed_at = NOW() WHERE id = ?
    ");
    $stmt->execute([$row['order_id']]);

    // Release table if exists
    $stmt = $db->prepare("SELECT table_id FROM orders WHERE id = ?");
    $stmt->execute([$row['order_id']]);
    $tableData = $stmt->fetch();
    
    if ($tableData && $tableData['table_id']) {
        // Don't release table immediately - keep it occupied while order is being prepared
        // Will be released when order is marked as ready/served
    }

    // Notify dapur & owner with auto-confirmed status
    broadcastNotification(
        ['kasir', 'dapur', 'owner'],
        'Pesanan Baru (QRIS Auto-Confirmed)',
        "✓ Pesanan #{$orderNumber} dari {$row['customer_name']} telah DIKONFIRMASI OTOMATIS via QRIS. Mulai persiapan!",
        'success',
        APP_URL . '/admin/orders.php?id=' . $row['order_id']
    );

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pembayaran berhasil dikonfirmasi! Pesanan Anda sedang diproses.'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
