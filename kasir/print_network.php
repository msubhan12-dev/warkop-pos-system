<?php
/**
 * Network Thermal Printer Integration
 * Direct socket connection to network printer (ESC/POS)
 */
require_once '../config/config.php';
requireRole(['kasir', 'owner']);

header('Content-Type: application/json');

$action = clean($_POST['action'] ?? '');
$orderId = (int)($_POST['order_id'] ?? 0);
$printerIp = clean($_POST['printer_ip'] ?? '');
$printerPort = (int)($_POST['printer_port'] ?? 9100);

if (!$orderId) {
    sendJSON(['success' => false, 'message' => 'Order ID required'], 400);
}

$db = getDB();

// Get order details
$stmt = $db->prepare("
    SELECT 
        o.id,
        o.order_number,
        o.customer_name,
        o.total,
        o.created_at,
        t.table_number,
        GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.menu_name) SEPARATOR '\n') as items
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.id = ?
    GROUP BY o.id
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    sendJSON(['success' => false, 'message' => 'Order not found'], 404);
}

if ($action === 'print_network') {
    // Generate ESC/POS commands for thermal printer
    $receipt = generateESCPOS($order);
    
    try {
        // Connect to network printer
        $socket = @fsockopen($printerIp, $printerPort, $errno, $errstr, 5);
        
        if (!$socket) {
            sendJSON(['success' => false, 'message' => "Connection failed: $errstr ($errno)"], 500);
        }
        
        // Send ESC/POS commands
        fwrite($socket, $receipt);
        fclose($socket);
        
        // Mark as printed in database
        $stmt = $db->prepare("UPDATE orders SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->execute([$orderId]);
        
        sendJSON([
            'success' => true,
            'message' => 'Receipt sent to printer successfully',
            'printer' => "$printerIp:$printerPort"
        ]);
        
    } catch (Exception $e) {
        sendJSON(['success' => false, 'message' => 'Print error: ' . $e->getMessage()], 500);
    }
    
} elseif ($action === 'preview') {
    // Return receipt HTML for preview
    sendJSON([
        'success' => true,
        'html' => generateReceiptHTML($order)
    ]);
    
} elseif ($action === 'test_connection') {
    // Test printer connection
    $socket = @fsockopen($printerIp, $printerPort, $errno, $errstr, 5);
    
    if ($socket) {
        fclose($socket);
        sendJSON([
            'success' => true,
            'message' => "Connected to printer at $printerIp:$printerPort"
        ]);
    } else {
        sendJSON([
            'success' => false,
            'message' => "Cannot connect to $printerIp:$printerPort - $errstr"
        ], 500);
    }
    
} else {
    sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Generate ESC/POS commands for thermal printer
 */
function generateESCPOS($order) {
    // ESC/POS commands
    $esc = chr(27);
    $receipt = '';
    
    // Initialize printer
    $receipt .= $esc . "@";
    
    // Set alignment to center
    $receipt .= $esc . "a" . chr(1);
    
    // Set font size (double height)
    $receipt .= $esc . "!" . chr(56);
    $receipt .= APP_NAME . "\n";
    
    // Reset font
    $receipt .= $esc . "!" . chr(0);
    $receipt .= $esc . "a" . chr(1);
    $receipt .= "Sistem Kasir Terpadu\n";
    
    // Dashed line
    $receipt .= $esc . "a" . chr(0);
    $receipt .= "================================\n";
    
    // Left align
    $receipt .= $esc . "a" . chr(0);
    
    // Order details
    $receipt .= "No. Pesanan: " . $order['order_number'] . "\n";
    $receipt .= "Waktu: " . date('d/m/Y H:i', strtotime($order['created_at'])) . "\n";
    
    // Center alignment
    $receipt .= $esc . "a" . chr(1);
    $receipt .= "================================\n";
    
    // Left align for items
    $receipt .= $esc . "a" . chr(0);
    $receipt .= "Customer: " . $order['customer_name'] . "\n";
    if ($order['table_number']) {
        $receipt .= "Meja: " . $order['table_number'] . "\n";
    }
    
    $receipt .= "\n";
    
    // Items
    $receipt .= $order['items'] . "\n\n";
    
    // Dashed line
    $receipt .= $esc . "a" . chr(1);
    $receipt .= "================================\n";
    
    // Total (right aligned)
    $receipt .= $esc . "a" . chr(2);
    $receipt .= "Total: Rp " . number_format($order['total'], 0, ',', '.') . "\n";
    $receipt .= "Metode: TUNAI\n";
    
    // Center alignment
    $receipt .= $esc . "a" . chr(1);
    $receipt .= "================================\n";
    $receipt .= "Terima Kasih!\n";
    $receipt .= "Selamat Menikmati\n\n";
    
    // Timestamp
    $receipt .= $esc . "a" . chr(1);
    $receipt .= $esc . "!" . chr(0);
    $receipt .= date('d/m/Y H:i:s') . "\n\n\n";
    
    // Cut paper
    $receipt .= $esc . "m";
    
    return $receipt;
}

/**
 * Generate receipt HTML for preview
 */
function generateReceiptHTML($order) {
    return "
    <div style='font-family: monospace; width: 80mm; margin: 0 auto; font-size: 12px; line-height: 1.2;'>
        <div style='text-align: center; font-weight: bold; font-size: 14px;'>" . APP_NAME . "</div>
        <div style='text-align: center; font-size: 10px;'>Sistem Kasir Terpadu</div>
        <div style='text-align: center; border-top: 1px dashed; border-bottom: 1px dashed; padding: 5px 0; margin: 5px 0;'>
            <div>No. Pesanan: <strong>" . $order['order_number'] . "</strong></div>
            <div>Waktu: " . date('d/m/Y H:i', strtotime($order['created_at'])) . "</div>
        </div>
        <div style='text-align: left; margin: 10px 0;'>
            <div>Customer: <strong>" . $order['customer_name'] . "</strong></div>
            " . ($order['table_number'] ? "<div>Meja: " . $order['table_number'] . "</div>" : "") . "
        </div>
        <div style='text-align: left; margin: 10px 0; white-space: pre-line;'>
            " . $order['items'] . "
        </div>
        <div style='border-top: 1px dashed; border-bottom: 1px dashed; padding: 10px 0; margin: 10px 0;'>
            <div style='text-align: right;'><strong>Total: Rp " . number_format($order['total'], 0, ',', '.') . "</strong></div>
            <div style='text-align: right;'>Metode: TUNAI</div>
        </div>
        <div style='text-align: center;'>
            <div>Terima Kasih!</div>
            <div>Selamat Menikmati</div>
        </div>
        <div style='text-align: center; font-size: 10px; margin-top: 10px;'>
            " . date('d/m/Y H:i:s') . "
        </div>
    </div>
    ";
}
