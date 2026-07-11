<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
}

$orderId = $_POST['order_id'] ?? '';

if (empty($orderId)) {
    sendJSON(['success' => false, 'message' => 'Order ID tidak valid.']);
}

try {
    $db = getDB();
    
    // Check if order exists
    $stmt = $db->prepare("SELECT id, status, table_id, order_number FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        sendJSON(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
    }
    
    if ($order['status'] === 'cancelled') {
        sendJSON(['success' => false, 'message' => 'Pesanan ini sudah dibatalkan sebelumnya.']);
    }
    
    $db->beginTransaction();
    
    // Update order status to cancelled
    $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$orderId]);
    
    // Update order items status to cancelled
    $stmt = $db->prepare("UPDATE order_items SET status = 'cancelled' WHERE order_id = ?");
    $stmt->execute([$orderId]);
    
    // Update kitchen tickets to cancelled
    $stmt = $db->prepare("UPDATE kitchen_tickets SET status = 'cancelled' WHERE order_id = ?");
    $stmt->execute([$orderId]);
    
    // Update payment to cancelled
    $stmt = $db->prepare("UPDATE payments SET status = 'cancelled' WHERE order_id = ?");
    $stmt->execute([$orderId]);
    
    // Release table if any
    if (!empty($order['table_id'])) {
        // Check if there are other active orders on the same table
        $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE table_id = ? AND status NOT IN ('cancelled', 'completed', 'success') AND id != ?");
        $stmt->execute([$order['table_id'], $orderId]);
        $activeOrdersOnTable = $stmt->fetchColumn();
        
        if ($activeOrdersOnTable == 0) {
            // Free the table
            $stmt = $db->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
            $stmt->execute([$order['table_id']]);
        }
    }
    
    // Create audit log
    createAuditLog('cancel', 'orders', $orderId, null, [
        'order_number' => $order['order_number'],
        'cancelled_by' => $_SESSION['username']
    ]);
    
    $db->commit();
    
    sendJSON(['success' => true, 'message' => 'Pesanan berhasil dibatalkan (VOID). Meja telah dikosongkan.']);
    
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    sendJSON(['success' => false, 'message' => 'Gagal membatalkan pesanan: ' . $e->getMessage()]);
}
