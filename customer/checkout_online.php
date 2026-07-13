<?php
require_once '../config/config.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: menu.php');
    exit;
}

// Clear table sessions for online order
unset($_SESSION['customer_table_id']);
unset($_SESSION['customer_table_number']);
$tableId = null;
$tableNumber = null;

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
    
    // Process Delivery Fee
    $deliveryFee = 0;
    if ($orderType === 'delivery') {
        $deliveryFee = (float)($_POST['delivery_fee'] ?? 0);
    }
    $finalTotal = $total + $deliveryFee;
    
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
                    order_type, status, subtotal, tax, delivery_fee, total, notes
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
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
                $deliveryFee,
                $finalTotal,
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
            // For online orders, cash is paid later, QRIS is verified later. Both are pending.
            $paymentStatus = 'pending';
            $stmt = $db->prepare("
                INSERT INTO payments (
                    order_id, payment_method, amount, paid_amount, status, verification_status
                ) VALUES (?, ?, ?, 0, ?, ?)
            ");
            $stmt->execute([$orderId, $paymentMethod, $finalTotal, $paymentStatus, 'pending']);
            
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
                'total' => $finalTotal,
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
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
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
                        <p class="text-xs text-slate-400 mt-0.5 mb-0.5 bg-slate-900/50 p-1 rounded-lg border border-slate-700 flex items-start gap-1 w-fit"><i class="fas fa-pen-alt text-xs text-emerald-400 mt-0.5"></i> <?= htmlspecialchars($item['notes']) ?></p>
                        <?php endif; ?>
                        <p class="text-sm text-slate-400 font-medium"><?= $item['quantity'] ?>x • <?= formatRupiah($item['price']) ?></p>
                    </div>
                    <span class="font-extrabold text-slate-200 text-base drop-shadow-sm">
                        <?= formatRupiah($item['price'] * $item['quantity']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="space-y-2 pt-4 border-t border-dashed border-slate-600">
                <div class="flex justify-between text-sm text-slate-300">
                    <span>Subtotal</span>
                    <span id="subtotalAmount" data-value="<?= $subtotal ?>"><?= formatRupiah($subtotal) ?></span>
                </div>
                <?php if ($tax > 0): ?>
                <div class="flex justify-between text-sm text-slate-300">
                    <span>Pajak (0%)</span>
                    <span><?= formatRupiah($tax) ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-sm text-emerald-400 hidden" id="summaryOngkirContainer">
                    <span>Ongkos Kirim</span>
                    <span id="summaryOngkirAmount">+ Rp 0</span>
                </div>
                <div class="flex justify-between text-xl font-extrabold text-slate-100 pt-3 border-t border-slate-700/50 font-outfit">
                    <span>Total Tagihan</span>
                    <span id="grandTotalAmount" class="text-emerald-400 drop-shadow-[0_0_8px_rgba(52,211,153,0.3)]"><?= formatRupiah($total) ?></span>
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
                            <div class="flex justify-between items-end mb-2">
                                <label class="block text-sm font-bold text-slate-300">
                                    Lokasi Pengiriman <span class="text-red-400">*</span>
                                </label>
                                <button type="button" onclick="getCurrentLocation()" id="btnLocation" class="text-xs bg-slate-800 hover:bg-emerald-600 text-slate-300 hover:text-white px-3 py-1.5 rounded-lg border border-slate-700 hover:border-emerald-500 transition-colors flex items-center gap-1 font-bold shadow-sm">
                                    <i class="fas fa-location-arrow"></i> Lokasi Saya
                                </button>
                            </div>
                            <!-- Map Container -->
                            <div id="map" class="w-full h-48 rounded-2xl border-2 border-slate-700 mb-3 z-10" style="background: #1e293b;"></div>
                            
                            <div class="bg-emerald-900/20 border border-emerald-500/30 rounded-xl p-3 mb-3 flex justify-between items-center hidden" id="distanceInfoContainer">
                                <div>
                                    <p class="text-xs text-emerald-400 font-bold mb-0.5">Estimasi Jarak</p>
                                    <p class="text-sm text-slate-200 font-bold" id="distanceText">0 KM</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-emerald-400 font-bold mb-0.5">Ongkos Kirim</p>
                                    <p class="text-sm text-slate-200 font-bold" id="ongkirText">Rp 0</p>
                                </div>
                            </div>
                            
                            <input type="hidden" name="delivery_fee" id="delivery_fee" value="0">
                            <input type="hidden" name="delivery_distance" id="delivery_distance" value="0">

                            <textarea 
                                id="delivery_address" 
                                name="delivery_address" 
                                rows="2"
                                class="w-full px-5 py-3 bg-slate-900/50 border border-slate-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 focus:bg-slate-800 transition-all duration-300 text-slate-200 resize-none font-medium placeholder-slate-500 text-sm"
                                placeholder="Detail Alamat (Contoh: Jalan, No Rumah, RT/RW, Patokan...)"
                            ></textarea>
                        </div>
                    
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
                
                <div class="grid grid-cols-1 gap-4">
                    <label class="relative flex flex-col p-5 border-2 border-emerald-500 rounded-2xl cursor-pointer bg-emerald-900/20 shadow-sm" id="lblPayQRIS">
                        <input type="radio" name="payment_method" value="qris" class="peer sr-only" checked>
                        <div class="flex items-center justify-between mb-2">
                            <i class="fas fa-qrcode text-blue-400 text-2xl drop-shadow-sm"></i>
                            <i class="fas fa-check-circle text-emerald-400 text-xl opacity-100" id="checkQRIS"></i>
                        </div>
                        <span class="font-extrabold text-slate-200 text-base font-outfit block">Scan QRIS (Otomatis)</span>
                        <span class="text-xs text-slate-400 font-medium mt-1">OVO, Gopay, Dana, ShopeePay, dll</span>
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // Formatting function
        const formatRupiah = (number) => {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(number);
        };

        // Constants
        const WARKOP_LAT = -6.3770866; 
        const WARKOP_LNG = 106.9700012;
        const COST_PER_KM = 3000;
        const BASE_TOTAL = <?= $total ?>;
        let currentMap = null;
        let currentMarker = null;
        let routeLayer = null;

        // Initialize Map
        function initMap() {
            if (currentMap) return;
            
            currentMap = L.map('map', { attributionControl: false }).setView([WARKOP_LAT, WARKOP_LNG], 13);
            
            // Menggunakan tile Google Maps agar tampilan familiar
            L.tileLayer('http://mt0.google.com/vt/lyrs=m&hl=id&x={x}&y={y}&z={z}', {
                attribution: ''
            }).addTo(currentMap);

            // Warkop Marker
            const warkopIcon = L.icon({
                iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
            L.marker([WARKOP_LAT, WARKOP_LNG], {icon: warkopIcon}).addTo(currentMap)
             .bindPopup('<b>Lokasi Arrahmanherb</b>').openPopup();

            // Customer Marker (Draggable)
            const customerIcon = L.icon({
                iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
            
            currentMarker = L.marker([WARKOP_LAT - 0.01, WARKOP_LNG], {
                draggable: false,
                icon: customerIcon
            }).addTo(currentMap);

            // Calculate initial distance
            calculateDistance(WARKOP_LAT - 0.01, WARKOP_LNG);
            drawRoute(WARKOP_LAT - 0.01, WARKOP_LNG);
            
            // Fix map rendering issue in hidden div
            setTimeout(() => { currentMap.invalidateSize(); }, 300);
        }
        
        // Reverse Geocoding
        function fetchAddressFromCoordinates(lat, lng) {
            const addressInput = document.getElementById('delivery_address');
            addressInput.placeholder = "Sedang mencari alamat...";
            
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        addressInput.value = data.display_name;
                    } else {
                        addressInput.placeholder = "Detail Alamat (Contoh: Jalan, No Rumah, RT/RW, Patokan...)";
                    }
                })
                .catch(error => {
                    console.error('Error fetching address:', error);
                    addressInput.placeholder = "Detail Alamat (Contoh: Jalan, No Rumah, RT/RW, Patokan...)";
                });
        }

        // Draw Route using OSRM
        function drawRoute(lat, lng) {
            const url = `https://router.project-osrm.org/route/v1/driving/${WARKOP_LNG},${WARKOP_LAT};${lng},${lat}?overview=full&geometries=geojson`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.routes && data.routes[0]) {
                        const coords = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
                        
                        // Remove old route
                        if (routeLayer) {
                            currentMap.removeLayer(routeLayer);
                        }
                        
                        // Draw new route
                        routeLayer = L.polyline(coords, {
                            color: '#3b82f6', // blue-500
                            weight: 5,
                            opacity: 0.8,
                            dashArray: '10, 10',
                            lineJoin: 'round'
                        }).addTo(currentMap);
                        
                        // Optional: Adjust map bounds to fit the route
                        // currentMap.fitBounds(routeLayer.getBounds(), { padding: [20, 20] });
                    }
                })
                .catch(err => console.error('Error fetching route:', err));
        }

        // Haversine formula
        function calculateDistance(lat, lng) {
            const R = 6371; // km
            const dLat = (lat - WARKOP_LAT) * Math.PI / 180;
            const dLng = (lng - WARKOP_LNG) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(WARKOP_LAT * Math.PI / 180) * Math.cos(lat * Math.PI / 180) *
                      Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const distance = R * c;
            
            // Round distance up (e.g., 2.2 -> 3)
            const billedDistance = Math.ceil(distance);
            const ongkir = billedDistance * COST_PER_KM;
            
            // Update UI
            document.getElementById('distanceText').innerText = distance.toFixed(1) + ' KM';
            document.getElementById('ongkirText').innerText = formatRupiah(ongkir);
            document.getElementById('summaryOngkirContainer').classList.remove('hidden');
            document.getElementById('summaryOngkirAmount').innerText = '+ ' + formatRupiah(ongkir);
            
            // Update hidden inputs
            document.getElementById('delivery_fee').value = ongkir;
            document.getElementById('delivery_distance').value = distance.toFixed(2);
            
            // Update Grand Total
            document.getElementById('grandTotalAmount').innerText = formatRupiah(BASE_TOTAL + ongkir);
        }

        // Geolocation
        function getCurrentLocation() {
            const btn = document.getElementById('btnLocation');
            const originalText = btn.innerHTML;
            
            if (!navigator.geolocation) {
                alert("Browser Anda tidak mendukung fitur lokasi.");
                return;
            }
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    if (currentMap && currentMarker) {
                        currentMap.setView([lat, lng], 16);
                        currentMarker.setLatLng([lat, lng]);
                        calculateDistance(lat, lng);
                        fetchAddressFromCoordinates(lat, lng);
                        drawRoute(lat, lng);
                    }
                    
                    btn.innerHTML = '<i class="fas fa-check"></i> Ditemukan';
                    btn.classList.remove('opacity-75', 'cursor-not-allowed');
                    btn.classList.add('bg-emerald-600', 'text-white', 'border-emerald-500');
                    
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-500');
                    }, 3000);
                },
                (error) => {
                    alert("Gagal mendapatkan lokasi. Pastikan Anda memberikan izin akses lokasi.");
                    btn.innerHTML = originalText;
                    btn.classList.remove('opacity-75', 'cursor-not-allowed');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }


        // Toggle Delivery Address form
        function toggleDeliveryAddress() {
            const typeInput = document.querySelector('input[name="order_type"]:checked');
            if(!typeInput) return;
            const type = typeInput.value;
            const container = document.getElementById('deliveryAddressContainer');
            const input = document.getElementById('delivery_address');
            const lblTakeaway = document.getElementById('lblTypeTakeaway');
            const lblDelivery = document.getElementById('lblTypeDelivery');
            const distContainer = document.getElementById('distanceInfoContainer');
            const summaryOngkir = document.getElementById('summaryOngkirContainer');
            
            if (type === 'delivery') {
                container.classList.remove('hidden');
                input.setAttribute('required', 'required');
                lblDelivery.classList.add('border-emerald-500', 'bg-emerald-900/20');
                lblDelivery.classList.remove('border-slate-700', 'bg-slate-900/50');
                lblTakeaway.classList.remove('border-emerald-500', 'bg-emerald-900/20');
                lblTakeaway.classList.add('border-slate-700', 'bg-slate-900/50');
                distContainer.classList.remove('hidden');
                summaryOngkir.classList.remove('hidden');
                
                // Initialize map when delivery is selected
                setTimeout(initMap, 100);
            } else {
                container.classList.add('hidden');
                input.removeAttribute('required');
                lblTakeaway.classList.add('border-emerald-500', 'bg-emerald-900/20');
                lblTakeaway.classList.remove('border-slate-700', 'bg-slate-900/50');
                lblDelivery.classList.remove('border-emerald-500', 'bg-emerald-900/20');
                lblDelivery.classList.add('border-slate-700', 'bg-slate-900/50');
                distContainer.classList.add('hidden');
                summaryOngkir.classList.add('hidden');
                
                // Reset ongkir for takeaway
                document.getElementById('delivery_fee').value = 0;
                document.getElementById('grandTotalAmount').innerText = formatRupiah(BASE_TOTAL);
            }
        }
        
        // Initial state
        toggleDeliveryAddress();
    </script>
</body>
</html>
