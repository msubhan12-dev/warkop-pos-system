<?php
/**
 * Server-Sent Events (SSE) Stream for Kitchen Display
 * Real-time order notifications
 */

require_once '../config/config.php';
requireRole(['dapur', 'owner']);

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Prevent timeout
set_time_limit(0);
ini_set('max_execution_time', 0);

// Disable output buffering
if (ob_get_level()) ob_end_clean();

// Get database connection
$db = getDB();

// Store last known state
$lastNewCount = 0;
$lastCookingCount = 0;
$lastCheckTime = time();

// Initial check
$stmt = $db->query("SELECT COUNT(*) as count FROM kitchen_tickets WHERE status = 'new'");
$lastNewCount = (int)$stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM kitchen_tickets WHERE status = 'cooking'");
$lastCookingCount = (int)$stmt->fetch()['count'];

// Send initial state
$initialData = [
    'type' => 'connected',
    'message' => 'Stream connected',
    'new_count' => $lastNewCount,
    'cooking_count' => $lastCookingCount,
    'timestamp' => date('Y-m-d H:i:s')
];
echo "data: " . json_encode($initialData) . "\n\n";
flush();

// Keep-alive counter
$keepAliveCounter = 0;

// Main loop - check every 1 second
while (true) {
    try {
        // Check if connection is still alive
        if (connection_aborted()) {
            break;
        }
        
        // Get current counts
        $stmt = $db->query("SELECT COUNT(*) as count FROM kitchen_tickets WHERE status = 'new'");
        $currentNewCount = (int)$stmt->fetch()['count'];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM kitchen_tickets WHERE status = 'cooking'");
        $currentCookingCount = (int)$stmt->fetch()['count'];
        
        // Check for NEW orders (increased)
        if ($currentNewCount > $lastNewCount) {
            $newOrdersAdded = $currentNewCount - $lastNewCount;
            
            // Get latest new order details
            $stmt = $db->query("
                SELECT 
                    kt.id,
                    kt.ticket_number,
                    kt.menu_name,
                    kt.quantity,
                    kt.table_number,
                    kt.notes,
                    kt.priority,
                    o.order_number,
                    o.customer_name,
                    o.order_type,
                    kt.created_at
                FROM kitchen_tickets kt
                JOIN orders o ON kt.order_id = o.id
                WHERE kt.status = 'new'
                ORDER BY kt.created_at DESC
                LIMIT $newOrdersAdded
            ");
            $newOrders = $stmt->fetchAll();
            
            // Send new order event
            $eventData = [
                'type' => 'new_order',
                'count' => $newOrdersAdded,
                'total_new' => $currentNewCount,
                'orders' => $newOrders,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            echo "event: new_order\n";
            echo "data: " . json_encode($eventData) . "\n\n";
            flush();
            
            $lastNewCount = $currentNewCount;
        }
        
        // Check for orders moved to COOKING
        if ($currentNewCount < $lastNewCount) {
            $eventData = [
                'type' => 'order_started',
                'total_new' => $currentNewCount,
                'total_cooking' => $currentCookingCount,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            echo "event: order_started\n";
            echo "data: " . json_encode($eventData) . "\n\n";
            flush();
            
            $lastNewCount = $currentNewCount;
        }
        
        // Check for orders moved to READY
        if ($currentCookingCount < $lastCookingCount) {
            $eventData = [
                'type' => 'order_ready',
                'total_cooking' => $currentCookingCount,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            echo "event: order_ready\n";
            echo "data: " . json_encode($eventData) . "\n\n";
            flush();
            
            $lastCookingCount = $currentCookingCount;
        }
        
        // Send keep-alive ping every 30 seconds
        $keepAliveCounter++;
        if ($keepAliveCounter >= 30) {
            $pingData = [
                'type' => 'ping',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            echo "event: ping\n";
            echo "data: " . json_encode($pingData) . "\n\n";
            flush();
            $keepAliveCounter = 0;
        }
        
        // Sleep 1 second before next check
        sleep(1);
        
    } catch (Exception $e) {
        // Log error and continue
        error_log('SSE Stream Error: ' . $e->getMessage());
        
        $errorData = [
            'type' => 'error',
            'message' => 'Stream error occurred',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        echo "event: error\n";
        echo "data: " . json_encode($errorData) . "\n\n";
        flush();
        
        sleep(5); // Wait longer on error
    }
}

// Connection closed
$closeData = [
    'type' => 'closed',
    'message' => 'Stream closed',
    'timestamp' => date('Y-m-d H:i:s')
];
echo "data: " . json_encode($closeData) . "\n\n";
flush();
