<?php
require_once '../config/config.php';
requireRole(['owner']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$ticketId = $input['ticket_id'] ?? null;
$action = $input['action'] ?? null;

if (!$ticketId || !$action) {
    sendJSON(['success' => false, 'message' => 'Invalid data']);
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Get ticket details
    $stmt = $db->prepare("
        SELECT kt.*, oi.id as order_item_id
        FROM kitchen_tickets kt
        JOIN order_items oi ON kt.order_item_id = oi.id
        WHERE kt.id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        sendJSON(['success' => false, 'message' => 'Ticket not found']);
    }
    
    if ($action === 'start_cooking') {
        // Update ticket status to cooking
        $stmt = $db->prepare("
            UPDATE kitchen_tickets 
            SET status = 'cooking', 
                cooking_started_at = NOW(),
                prepared_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $ticketId]);
        
        // Update order item status
        $stmt = $db->prepare("
            UPDATE order_items 
            SET status = 'cooking' 
            WHERE id = ?
        ");
        $stmt->execute([$ticket['order_item_id']]);
        
        // Check if all items in order are cooking
        $stmt = $db->prepare("
            SELECT COUNT(*) as pending_count
            FROM order_items
            WHERE order_id = ? AND status = 'pending'
        ");
        $stmt->execute([$ticket['order_id']]);
        $pendingCount = $stmt->fetch()['pending_count'];
        
        if ($pendingCount == 0) {
            // Update order status to cooking
            $stmt = $db->prepare("UPDATE orders SET status = 'cooking' WHERE id = ?");
            $stmt->execute([$ticket['order_id']]);
        }
        
        createAuditLog('update', 'kitchen_tickets', $ticketId, 
            ['status' => 'new'], 
            ['status' => 'cooking']
        );
        
        $message = 'Mulai memasak: ' . $ticket['menu_name'];
        
    } elseif ($action === 'mark_ready') {
        // Update ticket status to ready
        $stmt = $db->prepare("
            UPDATE kitchen_tickets 
            SET status = 'ready', 
                ready_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$ticketId]);
        
        // Update order item status
        $stmt = $db->prepare("
            UPDATE order_items 
            SET status = 'ready' 
            WHERE id = ?
        ");
        $stmt->execute([$ticket['order_item_id']]);
        
        // Check if all items in order are ready
        $stmt = $db->prepare("
            SELECT COUNT(*) as not_ready_count
            FROM order_items
            WHERE order_id = ? AND status NOT IN ('ready', 'served')
        ");
        $stmt->execute([$ticket['order_id']]);
        $notReadyCount = $stmt->fetch()['not_ready_count'];
        
        if ($notReadyCount == 0) {
            // Update order status to ready
            $stmt = $db->prepare("UPDATE orders SET status = 'ready' WHERE id = ?");
            $stmt->execute([$ticket['order_id']]);
            
            // Notify pelayan/kasir
            $stmt = $db->prepare("SELECT order_number, table_number FROM orders o LEFT JOIN tables t ON o.table_id = t.id WHERE o.id = ?");
            $stmt->execute([$ticket['order_id']]);
            $order = $stmt->fetch();
            
            broadcastNotification(
                ['kasir', 'pelayan', 'owner'],
                'Pesanan Siap',
                "Pesanan #{$order['order_number']} " . ($order['table_number'] ? "Meja {$order['table_number']}" : "Take Away") . " siap disajikan",
                'success'
            );
        }
        
        createAuditLog('update', 'kitchen_tickets', $ticketId, 
            ['status' => 'cooking'], 
            ['status' => 'ready']
        );
        
        $message = 'Pesanan siap: ' . $ticket['menu_name'];
        
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid action']);
    }
    
    $db->commit();
    
    sendJSON([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    sendJSON([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ], 500);
}
