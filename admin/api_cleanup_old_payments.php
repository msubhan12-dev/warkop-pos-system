<?php
/**
 * Admin API: Auto-cleanup old payment records
 * This can be called by a cron job or manually by admin
 * Only admin/owner can access this
 */

require_once '../config/config.php';
requireRole(['owner', 'admin']);

header('Content-Type: application/json');

$db = getDB();

try {
    // Count records to be deleted
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM payments 
        WHERE DATE(created_at) < DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
        AND verification_status IN ('verified', 'rejected')
    ");
    $stmt->execute();
    $countBefore = $stmt->fetch()['count'];

    // Delete old payment records
    $stmt = $db->prepare("
        DELETE FROM payments 
        WHERE DATE(created_at) < DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
        AND verification_status IN ('verified', 'rejected')
    ");
    $stmt->execute();
    $deleted = $stmt->rowCount();

    // Also cleanup orphaned order_items if orders don't have payments
    $stmt = $db->prepare("
        DELETE FROM order_items 
        WHERE order_id IN (
            SELECT o.id FROM orders o
            LEFT JOIN payments p ON o.id = p.order_id
            WHERE p.id IS NULL 
            AND DATE(o.created_at) < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        )
    ");
    $stmt->execute();
    $orphanedItems = $stmt->rowCount();

    sendJSON([
        'success' => true,
        'message' => 'Pembersihan riwayat pembayaran lama berhasil',
        'records_deleted' => $deleted,
        'orphaned_items_deleted' => $orphanedItems,
        'records_before' => $countBefore,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Error cleaning up old records: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ], 500);
}
