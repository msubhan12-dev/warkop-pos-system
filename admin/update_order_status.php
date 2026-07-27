<?php
require_once '../config/config.php';
requireRole(['owner', 'kasir', 'pelayan']);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $orderId = $_POST['order_id'] ?? null;
    $newStatus = $_POST['status'] ?? null;
    $user = getCurrentUser();
    $db = getDB();

    if (!$orderId || !$newStatus) {
        throw new Exception('Order ID dan Status harus diisi');
    }

    // Validate status values
    $validStatuses = ['pending', 'cooking', 'ready', 'served', 'completed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        throw new Exception('Status tidak valid');
    }

    // Get current order
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Pesanan tidak ditemukan');
    }

    // PELAYAN - Hanya bisa update: pending → cooking, cooking → ready, ready → served
    if ($user['role'] === 'pelayan') {
        $allowed = false;
        
        if ($order['status'] === 'pending' && $newStatus === 'cooking') {
            $allowed = true;
        } elseif ($order['status'] === 'cooking' && $newStatus === 'ready') {
            $allowed = true;
        } elseif ($order['status'] === 'ready' && $newStatus === 'served') {
            $allowed = true;
        }

        if (!$allowed) {
            throw new Exception('Pelayan hanya bisa update: Menunggu → Sedang Dimasak → Siap Disajikan → Disajikan');
        }
    }

    // KASIR/OWNER - Bisa update ke status apapun
    // (No additional restrictions)

    // Update order status
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);

    // Log the change
    $statusLabel = [
        'pending' => 'Menunggu',
        'cooking' => 'Sedang Dimasak',
        'ready' => 'Siap Disajikan',
        'served' => 'Disajikan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan'
    ];

    $response['success'] = true;
    $response['message'] = 'Status pesanan berhasil diubah menjadi: ' . $statusLabel[$newStatus];
    $response['new_status'] = $newStatus;
    $response['new_status_label'] = $statusLabel[$newStatus];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
