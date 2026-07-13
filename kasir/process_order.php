<?php
require_once '../config/config.php';
requireRole(['kasir', 'pelayan', 'owner']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$items = $input['items'] ?? [];
$customerName = clean($input['customer_name'] ?? '');
$tableIdInput = $input['table_id'] ?? null;
$deliveryAddress = clean($input['delivery_address'] ?? '');
$paymentAction = clean($input['payment_action'] ?? 'pay_later');
$paymentMethod = clean($input['payment_method'] ?? 'cash');
$paidAmount = (float)($input['paid_amount'] ?? 0.0);

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
    
    // Handle order type and table id
    $tableId = null;
    $tableNumber = null;
    $orderType = 'take_away';
    
    if ($tableIdInput === 'delivery') {
        $orderType = 'delivery';
    } elseif ($tableIdInput) {
        $tableId = $tableIdInput;
        $orderType = 'dine_in';
        $stmt = $db->prepare("SELECT table_number FROM tables WHERE id = ?");
        $stmt->execute([$tableId]);
        $table = $stmt->fetch();
        $tableNumber = $table['table_number'] ?? null;
    }
    
    // Generate order number
    $orderNumber = generateOrderNumber();
    $orderStatus = ($paymentAction === 'pay_now') ? 'completed' : 'confirmed';
    
    // Create order
    $stmt = $db->prepare("
        INSERT INTO orders (
            order_number, table_id, customer_name, delivery_address,
            order_type, status, subtotal, tax, total,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $orderNumber,
        $tableId,
        $customerName,
        $deliveryAddress,
        $orderType,
        $orderStatus,
        $subtotal,
        $tax,
        $total,
        $_SESSION['user_id']
    ]);
    
    $orderId = $db->lastInsertId();
    
    // Create order items
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
    }
    
    // Create payment record
    if ($paymentAction === 'pay_now') {
        $paymentStatus = 'success';
        $actualPaidAmount = ($paymentMethod === 'cash') ? $paidAmount : $total;
        $changeAmount = ($paymentMethod === 'cash') ? max(0.0, $paidAmount - $total) : 0.0;
        
        $stmt = $db->prepare("
            INSERT INTO payments (
                order_id, payment_method, amount, paid_amount, change_amount, status, paid_at, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$orderId, $paymentMethod, $total, $actualPaidAmount, $changeAmount, $paymentStatus, $_SESSION['user_id']]);
    } else {
        $paymentStatus = 'pending';
        $stmt = $db->prepare("
            INSERT INTO payments (
                order_id, payment_method, amount, paid_amount, status, created_by
            ) VALUES (?, ?, ?, 0, ?, ?)
        ");
        $stmt->execute([$orderId, $paymentMethod, $total, $paymentStatus, $_SESSION['user_id']]);
    }
    
    // Update table status if dine in
    if ($tableId) {
        $stmt = $db->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
        $stmt->execute([$tableId]);
    }
    
    // Deduct inventory stock
    deductStockForOrder($orderId, $db);
    
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
