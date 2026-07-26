<?php
require_once '../config/config.php';
requireRole(['owner']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$customerName = $_POST['customer_name'] ?? '';

if (empty($customerName)) {
    echo json_encode(['success' => false, 'message' => 'Customer name is required']);
    exit;
}

$db = getDB();

try {
    // Start transaction for data consistency
    $db->beginTransaction();

    // Get all order IDs for this customer
    $stmt = $db->prepare("SELECT id FROM orders WHERE LOWER(customer_name) = LOWER(?)");
    $stmt->execute([$customerName]);
    $orders = $stmt->fetchAll();

    if (empty($orders)) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Tidak ada pesanan ditemukan untuk pelanggan ini']);
        exit;
    }

    $orderIds = array_column($orders, 'id');
    $deletedCount = 0;

    // Delete all related data for each order
    foreach ($orderIds as $orderId) {
        // Delete order items
        $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);

        // Delete payments
        $stmt = $db->prepare("DELETE FROM payments WHERE order_id = ?");
        $stmt->execute([$orderId]);

        // Delete the order itself
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);

        $deletedCount++;
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => "Berhasil menghapus semua pesanan dari $customerName",
        'deleted_count' => $deletedCount
    ]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
