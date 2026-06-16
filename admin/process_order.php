<?php
require_once '../config/config.php';
requireRole(['owner']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$items = $input['items'] ?? [];
$customerName = clean($input['customer_name'] ?? '');
$tableId = $input['table_id'] ?? null;

// Validation
if (empty($items)) {
    sendJSON(['success' => false, 'message' => 'Tidak ada item']);
}

if (empty($customerName)) {
    sendJSON(['success' => false, 'message' => 'Nama customer harus diisi']);
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Calculate totals
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $tax = calculateTax($subtotal);
    $total = $subtotal + $tax;
    
    // Get table number if exists
    $tableNumber = null;
    if ($tableId) {
        $stmt = $db->prepare("SELECT table_number FROM tables WHERE id = ?");
        $stmt->execute([$tableId]);
        $table = $stmt->fetch();
        $tableNumber = $table['table_number'] ?? null;
    }
    
    // Generate order number
    $orderNumber = generateOrderNumber();
    
    // Create order
    $stmt = $db->prepare("
        INSERT INTO orders (
            order_number, table_id, customer_name,
            order_type, status, subtotal, tax, total,
            created_by
        ) VALUES (?, ?, ?, ?, 'confirmed', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $orderNumber,
        $tableId,
        $customerName,
        $tableId ? 'dine_in' : 'take_away',
        $subtotal,
        $tax,
        $total,
        $_SESSION['user_id']
    ]);
    
    $orderId = $db->lastInsertId();
    
    // Create order items and kitchen tickets
    foreach ($items as $item) {
        $itemSubtotal = $item['price'] * $item['quantity'];
        
        // Order item
        $stmt = $db->prepare("
            INSERT INTO order_items (
                order_id, menu_id, menu_name, price, quantity, subtotal, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $orderId,
            $item['id'],
            $item['name'],
            $item['price'],
            $item['quantity'],
            $itemSubtotal
        ]);
        
        $orderItemId = $db->lastInsertId();
        
        // Kitchen ticket
        $ticketNumber = generateTicketNumber();
        $stmt = $db->prepare("
            INSERT INTO kitchen_tickets (
                order_id, order_item_id, ticket_number, table_number,
                menu_name, quantity, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'new')
        ");
        $stmt->execute([
            $orderId,
            $orderItemId,
            $ticketNumber,
            $tableNumber,
            $item['name'],
            $item['quantity']
        ]);
    }
    
    // Create payment record (pending)
    $stmt = $db->prepare("
        INSERT INTO payments (
            order_id, payment_method, amount, paid_amount, status, created_by
        ) VALUES (?, 'cash', ?, 0, 'pending', ?)
    ");
    $stmt->execute([$orderId, $total, $_SESSION['user_id']]);
    
    // Update table status if dine in
    if ($tableId) {
        $stmt = $db->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
        $stmt->execute([$tableId]);
    }
    
    // Create audit log
    createAuditLog('create', 'orders', $orderId, null, [
        'order_number' => $orderNumber,
        'customer' => $customerName,
        'total' => $total,
        'created_by' => $_SESSION['username']
    ]);
    
    // Notify kitchen
    broadcastNotification(
        ['dapur', 'owner'],
        'Pesanan Baru',
        "Pesanan #{$orderNumber} dari {$customerName}",
        'info'
    );
    
    $db->commit();
    
    sendJSON([
        'success' => true,
        'message' => 'Pesanan berhasil diproses',
        'order_number' => $orderNumber,
        'order_id' => $orderId
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
