<?php
require_once '../config/config.php';

// This endpoint allows customers to manually clear their old payment history
// Only clears records older than 24 hours

header('Content-Type: application/json');

$db = getDB();
$customerId = null;
$customerPhone = null;

// Get customer identifier
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id'];
    
    // Get customer phone
    $stmt = $db->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$customerId]);
    $user = $stmt->fetch();
    $customerPhone = $user['phone'] ?? null;
} else {
    // For anonymous customers, use phone from POST
    if (!isset($_POST['phone'])) {
        sendJSON(['success' => false, 'message' => 'Phone number required'], 400);
    }
    $customerPhone = clean($_POST['phone']);
}

// Validate phone
if (empty($customerPhone)) {
    sendJSON(['success' => false, 'message' => 'Phone number not found'], 400);
}

try {
    // Only delete payments older than 24 hours
    $stmt = $db->prepare("
        DELETE FROM payments 
        WHERE order_id IN (
            SELECT id FROM orders 
            WHERE customer_phone = ? 
            AND DATE(created_at) < DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        )
        AND verification_status IN ('verified', 'rejected')
    ");
    $stmt->execute([$customerPhone]);
    
    $deleted = $stmt->rowCount();
    
    // Also delete associated orders if they exist
    $stmt = $db->prepare("
        DELETE FROM order_items 
        WHERE order_id IN (
            SELECT id FROM orders 
            WHERE customer_phone = ? 
            AND DATE(created_at) < DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        )
    ");
    $stmt->execute([$customerPhone]);
    
    sendJSON([
        'success' => true,
        'message' => 'Riwayat pembayaran lama berhasil dihapus',
        'deleted' => $deleted
    ]);
    
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Gagal menghapus riwayat: ' . $e->getMessage()
    ], 500);
}
