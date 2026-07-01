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
            
            // Create order items
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
            }
            
            // Create payment record
            $paymentStatus = ($paymentMethod === 'qris') ? 'pending' : 'success';
            $stmt = $db->prepare("
                INSERT INTO payments (
                    order_id, payment_method, amount, paid_amount, status, verification_status
                ) VALUES (?, ?, ?, 0, ?, ?)
            ");
            $stmt->execute([$orderId, $paymentMethod, $total, $paymentStatus, 'pending']);
            
            // For QRIS: set order status to pending payment verification
            if ($paymentMethod === 'qris') {
                $stmt = $db->prepare("UPDATE orders SET status = 'pending' WHERE id = ?");
                $stmt->execute([$orderId]);
            } else {
                // For cash: set to confirmed immediately
                $stmt = $db->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
                $stmt->execute([$orderId]);
            }
            
            // Update table status if dine in
            if ($orderType === 'dine_in' && $tableId) {
                $stmt = $db->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
                $stmt->execute([$tableId]);
            }
            
            // Create audit log
            createAuditLog('create', 'orders', $orderId, null, [
                'order_number' => $orderNumber,
                'customer' => $customerName,
                'total' => $total,
                'payment_method' => $paymentMethod
            ]);
            
            // Notify staff (but not dapur for QRIS until verified)
            if ($paymentMethod === 'cash') {
                broadcastNotification(
                    ['kasir', 'dapur', 'owner'],
                    'Pesanan Baru',
                    "Pesanan baru #{$orderNumber} dari {$customerName}",
                    'info',
                    '/admin/orders.php?id=' . $orderId
                );
            } else {
                // For QRIS, notify admin for verification
                broadcastNotification(
                    ['owner'],
                    'Verifikasi Pembayaran QRIS',
                    "Pesanan #{$orderNumber} menunggu verifikasi pembayaran QRIS",
                    'warning',
                    '/admin/orders.php?id=' . $orderId
                );
            }
            
            $db->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            unset($_SESSION['customer_table_id']);
            unset($_SESSION['customer_table_number']);
            
            // Redirect based on payment method
            if ($paymentMethod === 'qris') {
                header('Location: payment_qris.php?order=' . $orderNumber);
            } else {
                header('Location: order_success.php?order=' . $orderNumber);
            }
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
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Checkout - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="bg-stone-50 text-stone-900">
    <!-- Header -->
    <header class="bg-stone-900 text-white shadow-lg sticky top-0 z-30">
        <div class="px-4 py-4 flex items-center">
            <a href="menu.php" class="mr-4">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-xl font-bold font-outfit">Ringkasan Pesanan</h1>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-md mx-auto p-4 space-y-6">
        
        <?php if (isset($error) && $error): ?>
        <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg text-sm">
            <?= $error ?>
        </div>
        <?php endif; ?>
        
        <!-- Summary Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-4">
            <h2 class="font-bold text-lg mb-4 font-outfit text-stone-850 flex items-center">
                <i class="fas fa-receipt mr-2 text-stone-600"></i>
                Daftar Pesanan
            </h2>
            
            <div class="divide-y divide-stone-100 max-h-60 overflow-y-auto mb-4">
                <?php foreach ($cart as $item): ?>
                <div class="flex items-center justify-between py-3">
                    <div class="flex-1">
                        <p class="font-bold text-stone-800 text-sm"><?= $item['name'] ?></p>
                        <p class="text-xs text-stone-500"><?= $item['quantity'] ?>x • <?= formatRupiah($item['price']) ?></p>
                    </div>
                    <span class="font-bold text-stone-750 text-sm">
                        <?= formatRupiah($item['price'] * $item['quantity']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="space-y-2 pt-3 border-t">
                <div class="flex justify-between text-lg font-extrabold text-stone-900 pt-2 font-outfit">
                    <span>Total</span>
                    <span class="text-emerald-600"><?= formatRupiah($total) ?></span>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <form method="POST" action="" class="space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-4">
                <h2 class="font-bold text-lg mb-4 font-outfit text-stone-850 flex items-center">
                    <i class="fas fa-user mr-2 text-stone-600"></i>
                    Informasi Customer
                </h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="customer_name" class="block text-sm font-semibold text-stone-700 mb-2">
                            Nama <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="customer_name" 
                            name="customer_name" 
                            required
                            class="w-full px-4 py-3 border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium transition duration-200"
                            placeholder="Masukkan nama Anda"
                        >
                    </div>
                    
                    <div>
                        <label for="customer_phone" class="block text-sm font-semibold text-stone-700 mb-2">
                            Nomor Telepon <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="tel" 
                            id="customer_phone" 
                            name="customer_phone" 
                            required
                            class="w-full px-4 py-3 border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium transition duration-200"
                            placeholder="08xxxxxxxxxx"
                        >
                    </div>
                    
                    <!-- Tipe pesanan dideteksi otomatis berdasarkan scan meja -->
                    <input type="hidden" name="order_type" value="<?= $tableNumber ? 'dine_in' : 'take_away' ?>">
                    
                    <div>
                        <label for="notes" class="block text-sm font-semibold text-stone-700 mb-2">
                            Catatan (Opsional)
                        </label>
                        <textarea 
                            id="notes" 
                            name="notes" 
                            rows="3"
                            class="w-full px-4 py-3 border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 transition duration-200"
                            placeholder="Contoh: Manis sedang, kopi dipisah"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-4">
                <h2 class="font-bold text-lg mb-4 font-outfit text-stone-850 flex items-center">
                    <i class="fas fa-wallet mr-2 text-stone-600"></i>
                    Metode Pembayaran
                </h2>
                
                <div class="space-y-3">
                    <label class="flex items-center p-4 border-2 border-emerald-600 rounded-xl cursor-pointer bg-emerald-50 transition" id="lblPayCash">
                        <input type="radio" name="payment_method" value="cash" class="mr-3" checked>
                        <i class="fas fa-money-bill-wave text-green-600 text-lg mr-3"></i>
                        <span class="font-bold text-stone-800 text-sm">Tunai (Bayar di Kasir)</span>
                    </label>
                    <label class="flex items-center p-4 border-2 border-stone-200 rounded-xl cursor-pointer hover:border-emerald-600 transition" id="lblPayQRIS">
                        <input type="radio" name="payment_method" value="qris" class="mr-3">
                        <i class="fas fa-qrcode text-blue-600 text-lg mr-3"></i>
                        <span class="font-bold text-stone-800 text-sm">QRIS</span>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 px-6 rounded-2xl shadow-md hover:shadow-lg transition duration-200 text-base font-outfit flex items-center justify-center gap-2"
            >
                <i class="fas fa-check-circle"></i>
                Konfirmasi & Kirim Pesanan
            </button>
        </form>
    </main>

    <script>
        // Handle payment method radio button styles
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="payment_method"]').forEach(r => {
                    const label = r.closest('label');
                    if (r.checked) {
                        label.className = "flex items-center p-4 border-2 border-emerald-600 rounded-xl cursor-pointer bg-emerald-50 transition";
                    } else {
                        label.className = "flex items-center p-4 border-2 border-stone-200 rounded-xl cursor-pointer hover:border-emerald-600 transition";
                    }
                });
            });
        });
    </script>
</body>
</html>
