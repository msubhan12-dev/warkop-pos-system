<?php
require_once '../config/config.php';
requireRole(['owner']); // Only owner can delete

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    // Get order details for audit
    $stmt = $db->prepare("SELECT order_number, customer_name, total FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Delete order items first (foreign key constraint)
    $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);

    // Delete payments
    $stmt = $db->prepare("DELETE FROM payments WHERE order_id = ?");
    $stmt->execute([$orderId]);

    // Delete order
    $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);

    // Audit log
    createAuditLog('delete', 'orders', $orderId, json_encode($order), 'Order deleted for cleansing');

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Order #' . $order['order_number'] . ' berhasil dihapus'
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
