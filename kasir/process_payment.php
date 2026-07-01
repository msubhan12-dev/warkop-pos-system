<?php
/**
 * Kasir Payment Processing
 * Handle cash payment completion
 */
require_once '../config/config.php';
requireRole(['kasir', 'owner']);

$action = clean($_POST['action'] ?? '');
$paymentId = (int)($_POST['payment_id'] ?? 0);

if (!$paymentId) {
    sendJSON(['success' => false, 'message' => 'Invalid payment ID'], 400);
}

$db = getDB();

if ($action === 'complete_cash') {
    try {
        $db->beginTransaction();
        
        // Get payment details
        $stmt = $db->prepare("
            SELECT p.*, o.id as order_id
            FROM payments p
            JOIN orders o ON p.order_id = o.id
            WHERE p.id = ? AND p.payment_method = 'cash'
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            sendJSON(['success' => false, 'message' => 'Payment not found'], 404);
        }
        
        // Update payment status to success
        $stmt = $db->prepare("
            UPDATE payments
            SET status = 'success', paid_at = NOW(), paid_amount = amount
            WHERE id = ?
        ");
        $stmt->execute([$paymentId]);
        
        // Update order status to completed
        $stmt = $db->prepare("
            UPDATE orders
            SET status = 'completed', completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$payment['order_id']]);
        
        // Create audit log
        createAuditLog('complete_payment', 'payments', $paymentId, 
            ['status' => 'pending'],
            ['status' => 'success']
        );
        
        // Release table
        $stmt = $db->prepare("SELECT table_id FROM orders WHERE id = ?");
        $stmt->execute([(int)$payment['order_id']]);
        $tableData = $stmt->fetch();
        
        if ($tableData && $tableData['table_id']) {
            $stmt = $db->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
            $stmt->execute([(int)$tableData['table_id']]);
        }
        
        $db->commit();
        
        sendJSON([
            'success' => true, 
            'message' => 'Pembayaran berhasil diproses!'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
    }
    
    } elseif ($action === 'complete_order') {
        try {
            $orderId = (int)($_POST['order_id'] ?? 0);
            if (!$orderId) {
                sendJSON(['success' => false, 'message' => 'Invalid order ID'], 400);
            }
            
            $db->beginTransaction();
            
            // Update order status to completed
            $stmt = $db->prepare("
                UPDATE orders
                SET status = 'completed', completed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$orderId]);
            
            // Update payment status to success for pending payments (fallback/auto-complete)
            $stmt = $db->prepare("
                UPDATE payments
                SET status = 'success', paid_at = COALESCE(paid_at, NOW()), paid_amount = amount
                WHERE order_id = ? AND status = 'pending'
            ");
            $stmt->execute([$orderId]);
            
            // Create audit log
            createAuditLog('complete_order', 'orders', $orderId, 
                ['status' => 'active'],
                ['status' => 'completed']
            );
            
            // Release table
            $stmt = $db->prepare("SELECT table_id FROM orders WHERE id = ?");
            $stmt->execute([(int)$orderId]);
            $tableData = $stmt->fetch();
            
            if ($tableData && $tableData['table_id']) {
                $stmt = $db->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
                $stmt->execute([(int)$tableData['table_id']]);
            }
            
            $db->commit();
            
            sendJSON([
                'success' => true, 
                'message' => 'Pesanan selesai disajikan!'
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
        
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
