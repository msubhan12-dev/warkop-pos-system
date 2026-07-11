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
    $deliveryAddress = clean($_POST['delivery_address'] ?? '');
    $paymentMethod = clean($_POST['payment_method'] ?? 'cash');
    $notes = clean($_POST['notes'] ?? '');
    
    // Validation
    if (empty($customerName)) {
        $error = 'Nama harus diisi';
    } elseif ($orderType === 'delivery' && empty($deliveryAddress)) {
        $error = 'Alamat pengiriman harus diisi untuk pesanan delivery';
    } elseif (!empty($customerPhone) && !validatePhone($customerPhone)) {
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
                    order_number, table_id, customer_name, customer_phone, delivery_address,
                    order_type, status, subtotal, tax, total, notes
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderNumber,
                $tableId,
                $customerName,
                $customerPhone,
                $deliveryAddress,
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
                $itemNotes = isset($item['notes']) ? $item['notes'] : '';
                
                $stmt = $db->prepare("
                    INSERT INTO order_items (
                        order_id, menu_id, menu_name, price, quantity, subtotal, notes, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $orderId,
                    $item['id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $itemSubtotal,
                    $itemNotes
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
            $_SESSION['last_order_number'] = $orderNumber;
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
<body class="bg-[#0B1121] text-slate-200">
    <!-- Header -->
    <header class="bg-slate-900/80 backdrop-blur-md shadow-md sticky top-0 z-30 border-b border-slate-700/60">
        <div class="px-5 py-4 flex items-center justify-center relative">
            <a href="menu.php" class="absolute left-5 w-10 h-10 bg-slate-800 hover:bg-slate-700 border border-slate-700/50 rounded-full flex items-center justify-center text-slate-300 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-xl font-extrabold font-outfit text-slate-100 drop-shadow-sm">Ringkasan Pesanan</h1>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-md mx-auto p-4 space-y-6">
        
        <?php if (isset($error) && $error): ?>
        <div class="p-4 bg-red-900/30 border-l-4 border-red-500 text-red-300 rounded-lg text-sm">
            <?= $error ?>
        </div>
        <?php endif; ?>
        
        <!-- Summary Card -->
        <div class="bg-slate-800/60 backdrop-blur-md rounded-3xl shadow-xl border border-slate-700/50 p-6 relative overflow-hidden">
            <!-- Receipt zig-zag top decoration -->
            <div class="absolute top-0 left-0 right-0 h-2 bg-[radial-gradient(circle_at_10px_0,#1e293b_10px,transparent_11px)] bg-[length:20px_20px]"></div>
            
            <h2 class="font-extrabold text-xl mb-5 font-outfit text-slate-100 flex items-center mt-2 drop-shadow-sm">
                <i class="fas fa-receipt mr-3 text-emerald-400 bg-emerald-900/30 p-2 rounded-xl border border-emerald-500/20"></i>
                Daftar Pesanan
            </h2>
            
            <div class="divide-y divide-slate-700/50 max-h-60 overflow-y-auto mb-5 pr-2">
                <?php foreach ($cart as $item): ?>
                <div class="flex items-center justify-between py-3.5">
                    <div class="flex-1">
                        <p class="font-bold text-slate-200 text-base"><?= $item['name'] ?></p>
                        <?php if (!empty($item['notes'])): ?>
                        <p class="text-[11px] text-slate-400 mt-0.5 mb-0.5 bg-slate-900/50 p-1 rounded-lg border border-slate-700 flex items-start gap-1 w-fit"><i class="fas fa-pen-alt text-[9px] text-emerald-400 mt-0.5"></i> <?= htmlspecialchars($item['notes']) ?></p>
                        <?php endif; ?>
                        <p class="text-sm text-slate-400 font-medium"><?= $item['quantity'] ?>x • <?= formatRupiah($item['price']) ?></p>
                    </div>
                    <span class="font-extrabold text-slate-200 text-base drop-shadow-sm">
                        <?= formatRupiah($item['price'] * $item['quantity']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="space-y-3 pt-4 border-t border-dashed border-slate-600">
                <div class="flex justify-between text-xl font-extrabold text-slate-100 pt-2 font-outfit">
                    <span>Total Tagihan</span>
                    <span class="text-emerald-400 drop-shadow-[0_0_8px_rgba(52,211,153,0.3)]"><?= formatRupiah($total) ?></span>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <form method="POST" action="" class="space-y-6">
            <div class="bg-slate-800/60 backdrop-blur-md rounded-3xl shadow-xl border border-slate-700/50 p-6">
                <h2 class="font-extrabold text-xl mb-5 font-outfit text-slate-100 flex items-center drop-shadow-sm">
                    <i class="fas fa-user-circle mr-3 text-emerald-400 bg-emerald-900/30 p-2 rounded-xl border border-emerald-500/20"></i>
                    Informasi Pemesan
                </h2>
                
                <div class="space-y-5">
                    <div>
                        <label for="customer_name" class="block text-sm font-bold text-slate-300 mb-2">
                            Nama Pemesan <span class="text-red-400">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="customer_name" 
                            name="customer_name" 
                            required
                            class="w-full px-5 py-3.5 bg-slate-900/50 border border-slate-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 focus:bg-slate-800 font-medium transition-all duration-300 text-slate-200 placeholder-slate-500"
                            placeholder="Masukkan nama Anda"
                        >
                    </div>
                    
                    <div>
                        <label for="customer_phone" class="block text-sm font-bold text-slate-300 mb-2">
                            Nomor Telepon <span class="text-red-400">*</span>
                        </label>
                        <input 
                            type="tel" 
                            id="customer_phone" 
                            name="customer_phone" 
                            class="w-full px-5 py-3.5 bg-slate-900/50 border border-slate-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 focus:bg-slate-800 font-medium transition-all duration-300 text-slate-200 placeholder-slate-500"
                            placeholder="08xxxxxxxxxx"
                        >
                    </div>
                    
                    <?php if ($tableNumber): ?>
                        <input type="hidden" name="order_type" value="dine_in">
                    <?php else: ?>
                        <!-- Order type selection -->
                        <div class="space-y-3">
                            <label class="block text-sm font-bold text-slate-300 mb-1">Tipe Pesanan</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative flex flex-col p-4 border-2 border-emerald-500 rounded-xl cursor-pointer bg-emerald-900/20 transition-all duration-300" id="lblTypeTakeaway">
                                    <input type="radio" name="order_type" value="take_away" class="peer sr-only" checked onchange="toggleDeliveryAddress()">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-shopping-bag text-emerald-400"></i>
                                        <span class="font-bold text-slate-200">Ambil Sendiri</span>
                                    </div>
                                </label>
                                <label class="relative flex flex-col p-4 border-2 border-slate-700 rounded-xl cursor-pointer bg-slate-900/50 hover:border-emerald-500/50 transition-all duration-300" id="lblTypeDelivery">
                                    <input type="radio" name="order_type" value="delivery" class="peer sr-only" onchange="toggleDeliveryAddress()">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-motorcycle text-emerald-400"></i>
                                        <span class="font-bold text-slate-200">Pesan Antar</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div id="deliveryAddressContainer" class="hidden transition-all duration-300">
                            <label for="delivery_address" class="block text-sm font-bold text-slate-300 mb-2">
                                Alamat Pengiriman <span class="text-red-400">*</span>
                            </label>
                            <textarea 
                                id="delivery_address" 
                                name="delivery_address" 
                                rows="3"
                                class="w-full px-5 py-3.5 bg-slate-900/50 border border-slate-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 focus:bg-slate-800 transition-all duration-300 text-slate-200 resize-none font-medium placeholder-slate-500"
                                placeholder="Jalan, No Rumah, RT/RW, Patokan..."
                            ></textarea>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <label for="notes" class="block text-sm font-bold text-slate-300 mb-2">
                            Catatan Tambahan
                        </label>
                        <textarea 
                            id="notes" 
                            name="notes" 
                            rows="2"
                            class="w-full px-5 py-3.5 bg-slate-900/50 border border-slate-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 focus:bg-slate-800 transition-all duration-300 text-slate-200 resize-none font-medium placeholder-slate-500"
                            placeholder="Contoh: Es dipisah, gulanya dikit"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="bg-slate-800/60 backdrop-blur-md rounded-3xl shadow-xl border border-slate-700/50 p-6">
                <h2 class="font-extrabold text-xl mb-5 font-outfit text-slate-100 flex items-center drop-shadow-sm">
                    <i class="fas fa-wallet mr-3 text-emerald-400 bg-emerald-900/30 p-2 rounded-xl border border-emerald-500/20"></i>
                    Metode Pembayaran
                </h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="relative flex flex-col p-5 border-2 border-emerald-500 rounded-2xl cursor-pointer bg-emerald-900/20 hover:bg-emerald-900/30 transition-all duration-300 shadow-sm" id="lblPayCash">
                        <input type="radio" name="payment_method" value="cash" class="peer sr-only" checked>
                        <div class="flex items-center justify-between mb-2">
                            <i class="fas fa-money-bill-wave text-emerald-400 text-2xl drop-shadow-sm"></i>
                            <i class="fas fa-check-circle text-emerald-400 text-xl opacity-100 peer-checked:opacity-100 transition-opacity" id="checkCash"></i>
                        </div>
                        <span class="font-extrabold text-slate-200 text-base font-outfit block">Tunai di Kasir</span>
                        <span class="text-xs text-slate-400 font-medium mt-1">Bayar langsung ke kasir</span>
                    </label>
                    
                    <label class="relative flex flex-col p-5 border-2 border-slate-700 rounded-2xl cursor-pointer bg-slate-900/50 hover:border-emerald-500/50 transition-all duration-300 shadow-sm" id="lblPayQRIS">
                        <input type="radio" name="payment_method" value="qris" class="peer sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <i class="fas fa-qrcode text-blue-400 text-2xl drop-shadow-sm"></i>
                            <i class="fas fa-check-circle text-emerald-400 text-xl opacity-0 peer-checked:opacity-100 transition-opacity" id="checkQRIS"></i>
                        </div>
                        <span class="font-extrabold text-slate-200 text-base font-outfit block">Scan QRIS</span>
                        <span class="text-xs text-slate-400 font-medium mt-1">OVO, Gopay, Dana, dll</span>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit"
                class="w-full bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-extrabold py-4 px-6 rounded-2xl shadow-[0_8px_20px_-6px_rgba(16,185,129,0.5)] hover:shadow-[0_12px_25px_-6px_rgba(16,185,129,0.6)] hover:-translate-y-0.5 transition-all duration-300 text-lg font-outfit flex items-center justify-center gap-3 mt-4"
            >
                <i class="fas fa-paper-plane"></i>
                Pesan Sekarang
            </button>
        </form>
    </main>

    <script>
        // Handle payment method radio button styles
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const lblCash = document.getElementById('lblPayCash');
                const lblQRIS = document.getElementById('lblPayQRIS');
                const chkCash = document.getElementById('checkCash');
                const chkQRIS = document.getElementById('checkQRIS');
                
                if (this.value === 'cash') {
                    lblCash.className = "relative flex flex-col p-5 border-2 border-emerald-500 rounded-2xl cursor-pointer bg-emerald-900/20 hover:bg-emerald-900/30 transition-all duration-300 shadow-sm";
                    lblQRIS.className = "relative flex flex-col p-5 border-2 border-slate-700 rounded-2xl cursor-pointer bg-slate-900/50 hover:border-emerald-500/50 transition-all duration-300 shadow-sm";
                    chkCash.style.opacity = '1';
                    chkQRIS.style.opacity = '0';
                } else {
                    lblQRIS.className = "relative flex flex-col p-5 border-2 border-emerald-500 rounded-2xl cursor-pointer bg-emerald-900/20 hover:bg-emerald-900/30 transition-all duration-300 shadow-sm";
                    lblCash.className = "relative flex flex-col p-5 border-2 border-slate-700 rounded-2xl cursor-pointer bg-slate-900/50 hover:border-emerald-500/50 transition-all duration-300 shadow-sm";
                    chkQRIS.style.opacity = '1';
                    chkCash.style.opacity = '0';
                }
            });
        });

        // Toggle Delivery Address form
        function toggleDeliveryAddress() {
            const typeInput = document.querySelector('input[name="order_type"]:checked');
            if(!typeInput) return;
            const type = typeInput.value;
            const container = document.getElementById('deliveryAddressContainer');
            const input = document.getElementById('delivery_address');
            const lblTakeaway = document.getElementById('lblTypeTakeaway');
            const lblDelivery = document.getElementById('lblTypeDelivery');
            
            if (type === 'delivery') {
                container.classList.remove('hidden');
                input.setAttribute('required', 'required');
                lblDelivery.classList.add('border-emerald-500', 'bg-emerald-900/20');
                lblDelivery.classList.remove('border-slate-700', 'bg-slate-900/50');
                lblTakeaway.classList.remove('border-emerald-500', 'bg-emerald-900/20');
                lblTakeaway.classList.add('border-slate-700', 'bg-slate-900/50');
            } else {
                container.classList.add('hidden');
                input.removeAttribute('required');
                lblTakeaway.classList.add('border-emerald-500', 'bg-emerald-900/20');
                lblTakeaway.classList.remove('border-slate-700', 'bg-slate-900/50');
                lblDelivery.classList.remove('border-emerald-500', 'bg-emerald-900/20');
                lblDelivery.classList.add('border-slate-700', 'bg-slate-900/50');
            }
        }
    </script>
</body>
</html>
