<?php
require_once '../config/config.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: menu.php');
    exit;
}

$tableId = $_SESSION['customer_table_id'] ?? null;
$tableNumber = $_SESSION['customer_table_number'] ?? null;

// Calculate totals
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = calculateTax($subtotal);
$total = $subtotal + $tax;

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = clean($_POST['customer_name'] ?? '');
    $customerPhone = clean($_POST['customer_phone'] ?? '');
    $orderType = clean($_POST['order_type'] ?? 'dine_in');
    $paymentMethod = clean($_POST['payment_method'] ?? 'cash');
    $notes = clean($_POST['notes'] ?? '');
    
    // Validation
    if (empty($customerName)) {
        $error = 'Nama harus diisi';
    } elseif (empty($customerPhone) || !validatePhone($customerPhone)) {
        $error = 'Nomor telepon tidak valid';
    } else {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            // Generate order number
            $orderNumber = generateOrderNumber();
            
            // Create order
            $stmt = $db->prepare("
                INSERT INTO orders (
                    order_number, table_id, customer_name, customer_phone,
                    order_type, status, subtotal, tax, total, notes
                ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderNumber,
                $tableId,
                $customerName,
                $customerPhone,
                $orderType,
                $subtotal,
                $tax,
                $total,
                $notes
            ]);
            
            $orderId = $db->lastInsertId();
            
            // Create order items and kitchen tickets
            foreach ($cart as $item) {
                // Order item
                $itemSubtotal = $item['price'] * $item['quantity'];
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
            
            // Create payment record
            $stmt = $db->prepare("
                INSERT INTO payments (
                    order_id, payment_method, amount, paid_amount, status
                ) VALUES (?, ?, ?, 0, 'pending')
            ");
            $stmt->execute([$orderId, $paymentMethod, $total]);
            
            // Update table status if dine in
            if ($orderType === 'dine_in' && $tableId) {
                $stmt = $db->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
                $stmt->execute([$tableId]);
            }
            
            // Create audit log
            createAuditLog('create', 'orders', $orderId, null, [
                'order_number' => $orderNumber,
                'customer' => $customerName,
                'total' => $total
            ]);
            
            // Notify staff
            broadcastNotification(
                ['kasir', 'dapur', 'owner'],
                'Pesanan Baru',
                "Pesanan baru #{$orderNumber} dari {$customerName}",
                'info',
                '/admin/orders.php?id=' . $orderId
            );
            
            $db->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            unset($_SESSION['customer_table_id']);
            unset($_SESSION['customer_table_number']);
            
            // Redirect to success page
            header('Location: order_success.php?order=' . $orderNumber);
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Checkout - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-purple-600 text-white shadow-lg sticky top-0 z-30">
        <div class="px-4 py-4 flex items-center">
            <a href="menu.php" class="mr-4">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-xl font-bold">Checkout</h1>
        </div>
    </header>

    <main class="p-4 max-w-2xl mx-auto">
        <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
        </div>
        <?php endif; ?>

        <!-- Order Summary -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
            <h2 class="font-bold text-lg mb-4 flex items-center">
                <i class="fas fa-shopping-cart mr-2 text-purple-600"></i>
                Ringkasan Pesanan
            </h2>
            
            <?php if ($tableNumber): ?>
            <div class="mb-4 p-3 bg-purple-50 rounded-lg">
                <p class="text-sm text-gray-600">
                    <i class="fas fa-chair mr-2"></i>
                    <strong>Meja:</strong> <?= $tableNumber ?>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="space-y-3 mb-4">
                <?php foreach ($cart as $item): ?>
                <div class="flex items-center justify-between py-2 border-b">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800"><?= $item['name'] ?></p>
                        <p class="text-sm text-gray-500">
                            <?= $item['quantity'] ?> x <?= formatRupiah($item['price']) ?>
                        </p>
                    </div>
                    <span class="font-bold text-purple-600">
                        <?= formatRupiah($item['price'] * $item['quantity']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="space-y-2 pt-3 border-t">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span><?= formatRupiah($subtotal) ?></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Pajak (10%)</span>
                    <span><?= formatRupiah($tax) ?></span>
                </div>
                <div class="flex justify-between text-lg font-bold text-gray-800 pt-2 border-t">
                    <span>Total</span>
                    <span class="text-purple-600"><?= formatRupiah($total) ?></span>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <form method="POST" action="" class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h2 class="font-bold text-lg mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-purple-600"></i>
                    Informasi Customer
                </h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="customer_name" 
                            name="customer_name" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Masukkan nama Anda"
                        >
                    </div>
                    
                    <div>
                        <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Nomor Telepon <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="tel" 
                            id="customer_phone" 
                            name="customer_phone" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="08xxxxxxxxxx"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tipe Pesanan <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center justify-center p-4 border-2 border-purple-600 rounded-lg cursor-pointer bg-purple-50">
                                <input type="radio" name="order_type" value="dine_in" class="mr-2" checked>
                                <i class="fas fa-chair mr-2"></i>
                                <span class="font-semibold">Dine In</span>
                            </label>
                            <label class="flex items-center justify-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-600">
                                <input type="radio" name="order_type" value="take_away" class="mr-2">
                                <i class="fas fa-shopping-bag mr-2"></i>
                                <span class="font-semibold">Take Away</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Catatan (Opsional)
                        </label>
                        <textarea 
                            id="notes" 
                            name="notes" 
                            rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Contoh: Pedas sedang, tidak pakai bawang"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h2 class="font-bold text-lg mb-4 flex items-center">
                    <i class="fas fa-wallet mr-2 text-purple-600"></i>
                    Metode Pembayaran
                </h2>
                
                <div class="space-y-2">
                    <label class="flex items-center p-4 border-2 border-purple-600 rounded-lg cursor-pointer bg-purple-50">
                        <input type="radio" name="payment_method" value="cash" class="mr-3" checked>
                        <i class="fas fa-money-bill-wave text-green-600 mr-3"></i>
                        <span class="font-semibold">Tunai (Bayar di Kasir)</span>
                    </label>
                    <label class="flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-600">
                        <input type="radio" name="payment_method" value="qris" class="mr-3">
                        <i class="fas fa-qrcode text-blue-600 mr-3"></i>
                        <span class="font-semibold">QRIS</span>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit"
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg transition text-lg"
            >
                <i class="fas fa-check-circle mr-2"></i>
                Konfirmasi Pesanan
            </button>
        </form>
    </main>

    <script>
        // Handle order type radio button styles
        document.querySelectorAll('input[name="order_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="order_type"]').forEach(r => {
                    const label = r.closest('label');
                    if (r.checked) {
                        label.classList.add('border-purple-600', 'bg-purple-50');
                        label.classList.remove('border-gray-300');
                    } else {
                        label.classList.remove('border-purple-600', 'bg-purple-50');
                        label.classList.add('border-gray-300');
                    }
                });
            });
        });
        
        // Handle payment method radio button styles
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="payment_method"]').forEach(r => {
                    const label = r.closest('label');
                    if (r.checked) {
                        label.classList.add('border-purple-600', 'bg-purple-50');
                        label.classList.remove('border-gray-300');
                    } else {
                        label.classList.remove('border-purple-600', 'bg-purple-50');
                        label.classList.add('border-gray-300');
                    }
                });
            });
        });
    </script>
</body>
</html>
