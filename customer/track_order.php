<?php
require_once '../config/config.php';

$db = getDB();
$tab = $_GET['tab'] ?? 'track';
$orderNumber = $_GET['order'] ?? $_POST['order'] ?? $_SESSION['last_order_number'] ?? null;
$order = null;
$error = '';
$paymentHistory = [];

// Get customer data for history
$customerId = $_SESSION['customer_session_id'] ?? uniqid('CUST_', true);
if (!isset($_SESSION['customer_session_id'])) $_SESSION['customer_session_id'] = $customerId;

$customerPhone = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $customerPhone = $user['phone'] ?? '';
}

// TAB: TRACK - Get order info
if ($tab === 'track' && $orderNumber) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();
    if (!$order) $error = 'Pesanan tidak ditemukan.';
    else {
        // Auto-load riwayat berdasarkan phone dari order yang ditemukan
        $orderPhone = $order['customer_phone'] ?? null;
        if ($orderPhone) {
            $stmt = $db->prepare("
                SELECT o.id, o.order_number, o.customer_name, o.total, o.created_at, o.status,
                       p.payment_method, p.verification_status, p.status as payment_status, p.paid_amount
                FROM orders o
                LEFT JOIN payments p ON o.id = p.order_id
                WHERE o.customer_phone = ? AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY o.id
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$orderPhone]);
            $paymentHistory = $stmt->fetchAll();
        }
    }
}

// TAB: HISTORY - Get payment history (jika user input phone manual)
if ($tab === 'history') {
    $searchPhone = $_POST['search_phone'] ?? $_GET['phone'] ?? null;
    
    if ($searchPhone) {
        $searchPhone = clean($searchPhone);
        $stmt = $db->prepare("
            SELECT o.id, o.order_number, o.customer_name, o.total, o.created_at, o.status,
                   p.payment_method, p.verification_status, p.status as payment_status, p.paid_amount
            FROM orders o
            LEFT JOIN payments p ON o.id = p.order_id
            WHERE o.customer_phone = ? AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$searchPhone]);
        $paymentHistory = $stmt->fetchAll();
    }
}

// Count statuses
$pendingCount = $verifiedCount = $rejectedCount = 0;
foreach ($paymentHistory as $p) {
    // Untuk QRIS: gunakan verification_status
    if ($p['payment_method'] === 'qris') {
        if ($p['verification_status'] === 'pending') $pendingCount++;
        elseif ($p['verification_status'] === 'verified') $verifiedCount++;
        elseif ($p['verification_status'] === 'rejected') $rejectedCount++;
    }
    // Untuk Cash/Transfer: gunakan payment_status + paid_amount
    else {
        if ($p['payment_status'] === 'success' && $p['paid_amount'] > 0) $verifiedCount++;
        elseif ($p['payment_status'] === 'pending' && $p['paid_amount'] == 0) $pendingCount++;
        else $rejectedCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <title>Pesananku - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="pb-24 bg-slate-900 text-slate-100">
    
    <header class="bg-slate-900/80 backdrop-blur-md sticky top-0 z-40 border-b border-slate-800">
        <div class="px-6 py-4 text-center">
            <h1 class="font-extrabold text-2xl font-outfit text-white drop-shadow-sm flex items-center justify-center gap-2">
                <i class="fas fa-map-marker-alt text-rose-400"></i> Pesananku
            </h1>
        </div>
    </header>

    <!-- Tab Navigation -->
    <div class="bg-slate-800/60 backdrop-blur-md sticky top-[72px] z-30 border-b border-slate-700 px-4 py-2 flex gap-2 max-w-md mx-auto">
        <a href="?tab=track" class="flex-1 py-3 px-4 rounded-lg font-bold text-center transition-all <?= $tab === 'track' ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">
            <i class="fas fa-map-location-dot mr-2"></i>Lacak
        </a>
        <a href="?tab=history" class="flex-1 py-3 px-4 rounded-lg font-bold text-center transition-all <?= $tab === 'history' ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">
            <i class="fas fa-history mr-2"></i>Riwayat
        </a>
    </div>

    <main class="p-6 max-w-md mx-auto space-y-6">

        <!-- TAB 1: TRACK PESANAN -->
        <?php if ($tab === 'track'): ?>

            <!-- Search Form -->
            <div class="bg-slate-800/60 backdrop-blur-md p-5 rounded-3xl border border-slate-700/50 shadow-lg">
                <form method="GET" class="flex gap-3 relative group">
                    <input type="hidden" name="tab" value="track">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-receipt text-slate-400"></i>
                    </div>
                    <input type="text" name="order" value="<?= htmlspecialchars($orderNumber ?? '') ?>" placeholder="Nomor Pesanan..." class="flex-1 bg-slate-900/50 border border-slate-700 rounded-2xl pl-11 pr-4 py-3.5 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500" required>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-500 text-white font-bold w-14 h-[52px] rounded-2xl flex items-center justify-center transition-all">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <?php if ($error): ?>
                    <p class="text-rose-400 text-sm mt-3 flex items-center gap-2"><i class="fas fa-exclamation-circle"></i> <?= $error ?></p>
                <?php endif; ?>
            </div>

            <?php if ($order): ?>
                <!-- Get payment info -->
                <?php 
                    $stmt = $db->prepare("SELECT payment_method, verification_status, status as payment_status FROM payments WHERE order_id = ? LIMIT 1");
                    $stmt->execute([$order['id']]);
                    $payment = $stmt->fetch();
                    $paymentMethod = $payment['payment_method'] ?? 'cash';
                    $verificationStatus = $payment['verification_status'] ?? 'pending';
                    
                    // Determine actual order status based on payment verification
                    $displayStatus = $order['status'];
                    
                    // If QRIS and verified, show as 'cooking' instead of 'pending'/'confirmed'
                    if ($paymentMethod === 'qris' && $verificationStatus === 'verified') {
                        $displayStatus = 'cooking'; // Or 'pending' if not cooking yet
                    }
                    // If QRIS and rejected, show as 'cancelled'
                    elseif ($paymentMethod === 'qris' && $verificationStatus === 'rejected') {
                        $displayStatus = 'cancelled';
                    }
                    // If QRIS and pending, stay as 'pending' (waiting payment)
                    elseif ($paymentMethod === 'qris' && $verificationStatus === 'pending') {
                        $displayStatus = 'pending';
                    }
                    // Cash always shows normal status
                ?>

                <div class="flex gap-2">
                    <button onclick="location.reload()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-slate-300 font-bold py-3 px-6 rounded-2xl transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-sync-alt"></i> Perbarui
                    </button>
                    
                    <!-- Button "Cek Status Pembayaran" jika pending dan QRIS -->
                    <?php if ($paymentMethod === 'qris' && $verificationStatus === 'pending'): ?>
                    <button onclick="goToPaymentCheckout()" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-2xl transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-credit-card"></i> Cek Bayar
                    </button>
                    <?php endif; ?>
                </div>

                <div class="bg-slate-800/80 backdrop-blur-md rounded-3xl border border-slate-700 p-6 shadow-xl">
                    <!-- Order ID -->
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-700/50">
                        <div>
                            <p class="text-slate-400 text-xs font-bold uppercase mb-1">Nomor Pesanan</p>
                            <h3 class="font-extrabold text-lg font-outfit text-white">#<?= htmlspecialchars($order['order_number']) ?></h3>
                        </div>
                        <span class="bg-slate-900 border border-slate-700 text-slate-300 font-bold px-3 py-1.5 rounded-lg text-sm">
                            <i class="fas fa-clock text-rose-500 mr-1"></i><?= date('H:i', strtotime($order['created_at'])) ?>
                        </span>
                    </div>

                    <!-- Status Display -->
                    <div class="text-center py-6">
                        <?php 
                            $statusIcons = [
                                'pending' => 'fa-hourglass-end text-amber-500',
                                'confirmed' => 'fa-check-circle text-blue-500',
                                'cooking' => 'fa-fire text-orange-500',
                                'ready' => 'fa-check-double text-green-500',
                                'served' => 'fa-utensils text-emerald-500',
                                'completed' => 'fa-check-circle text-emerald-500',
                                'cancelled' => 'fa-times-circle text-red-500'
                            ];
                            $statusColor = [
                                'pending' => 'from-amber-500 to-yellow-500',
                                'confirmed' => 'from-blue-500 to-cyan-500',
                                'cooking' => 'from-orange-500 to-red-500',
                                'ready' => 'from-green-500 to-emerald-500',
                                'served' => 'from-emerald-500 to-teal-500',
                                'completed' => 'from-green-500 to-emerald-500',
                                'cancelled' => 'from-red-500 to-rose-500'
                            ];
                            $icon = $statusIcons[$displayStatus] ?? 'fa-info-circle text-slate-400';
                            $color = $statusColor[$displayStatus] ?? 'from-slate-500 to-slate-600';
                        ?>
                        <div class="w-24 h-24 mx-auto bg-gradient-to-br <?= $color ?> rounded-full flex items-center justify-center shadow-lg shadow-rose-500/30 mb-5 border-4 border-slate-800">
                            <i class="fas <?= $icon ?> text-4xl text-white"></i>
                        </div>
                        <h2 class="font-extrabold text-2xl font-outfit text-rose-400 mb-2">
                            <?php
                                $statusLabel = [
                                    'pending' => 'Menunggu Pembayaran',
                                    'confirmed' => 'Pesanan Diterima',
                                    'cooking' => 'Sedang Dimasak',
                                    'ready' => 'Siap Diantar',
                                    'served' => 'Disajikan',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Dibatalkan'
                                ];
                                echo $statusLabel[$displayStatus] ?? ucfirst($displayStatus);
                            ?>
                        </h2>
                        <p class="text-sm text-slate-300">
                            <?php
                                $statusDesc = [
                                    'pending' => 'Tunggu konfirmasi pembayaran dari sistem',
                                    'confirmed' => 'Order sudah dikonfirmasi, silakan tunggu',
                                    'cooking' => 'Koki sedang menyiapkan pesanan Anda',
                                    'ready' => 'Order siap diantar/diambil',
                                    'served' => 'Order telah disajikan',
                                    'completed' => 'Terima kasih telah memesan!',
                                    'cancelled' => 'Order telah dibatalkan'
                                ];
                                echo $statusDesc[$displayStatus] ?? 'Status tidak diketahui';
                            ?>
                        </p>
                    </div>

                    <!-- Order Details -->
                    <div class="bg-slate-900/50 rounded-2xl p-4 space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Nama Pemesan</span>
                            <span class="font-bold text-slate-200"><?= htmlspecialchars($order['customer_name']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Total</span>
                            <span class="font-bold text-emerald-400"><?= formatRupiah($order['total']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Metode Bayar</span>
                            <span class="font-bold text-blue-400">
                                <?= ucfirst($paymentMethod === 'qris' ? 'QRIS' : $paymentMethod) ?>
                                <?php if ($verificationStatus === 'verified'): ?>
                                    <i class="fas fa-check-circle text-emerald-500 ml-1"></i>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Waktu Pesan</span>
                            <span class="font-bold text-slate-200"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Pesanan Lain dari Customer yang Sama -->
                <?php if (!empty($paymentHistory) && count($paymentHistory) > 1): ?>
                <div class="mt-6">
                    <h3 class="font-bold text-slate-300 mb-3 flex items-center gap-2">
                        <i class="fas fa-history text-rose-400"></i> Riwayat Pesanan Pelanggan
                    </h3>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        <?php foreach ($paymentHistory as $hist): 
                            if ($hist['order_number'] === $orderNumber) continue; // Skip current order
                            
                            // Determine payment status
                            if ($hist['payment_method'] === 'qris') {
                                $st = $hist['verification_status'] ?? 'pending';
                            } else {
                                if ($hist['payment_status'] === 'success' && $hist['paid_amount'] > 0) {
                                    $st = 'verified';
                                } elseif ($hist['payment_status'] === 'pending' && $hist['paid_amount'] == 0) {
                                    $st = 'pending';
                                } else {
                                    $st = 'rejected';
                                }
                            }
                            
                            $badge = [
                                'pending' => ['bg' => 'bg-amber-900/30', 'text' => 'text-amber-400', 'icon' => 'fa-clock'],
                                'verified' => ['bg' => 'bg-emerald-900/30', 'text' => 'text-emerald-400', 'icon' => 'fa-check-circle'],
                                'rejected' => ['bg' => 'bg-red-900/30', 'text' => 'text-red-400', 'icon' => 'fa-times-circle']
                            ][$st] ?? ['bg' => 'bg-slate-700/30', 'text' => 'text-slate-400', 'icon' => 'fa-question'];
                        ?>
                        <a href="?order=<?= $hist['order_number'] ?>&tab=track" class="block bg-slate-800/40 hover:bg-slate-700/40 rounded-lg p-3 transition-colors border border-slate-700/50">
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <p class="text-xs font-mono text-slate-400"><?= $hist['order_number'] ?></p>
                                    <p class="text-sm text-slate-300 font-bold"><?= formatRupiah($hist['total']) ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-bold <?= $badge['bg'] ?> <?= $badge['text'] ?>">
                                        <i class="fas <?= $badge['icon'] ?>"></i>
                                    </span>
                                    <p class="text-xs text-slate-500 mt-1"><?= date('d/m', strtotime($hist['created_at'])) ?></p>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-20 px-6">
                    <div class="w-24 h-24 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-slate-700">
                        <i class="fas fa-search-location text-4xl text-slate-500"></i>
                    </div>
                    <h3 class="font-extrabold text-2xl font-outfit text-white mb-2">Cari Pesananmu</h3>
                    <p class="text-slate-400">Masukkan nomor pesanan untuk melihat status</p>
                </div>
            <?php endif; ?>

        <!-- TAB 2: RIWAYAT PEMBAYARAN -->
        <?php else: ?>

            <!-- Status Summary -->
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-amber-900/30 border border-amber-500/30 rounded-2xl p-4 text-center">
                    <p class="text-amber-400 text-xs font-bold uppercase mb-2">Menunggu</p>
                    <p class="text-3xl font-extrabold text-amber-400 font-outfit"><?= $pendingCount ?></p>
                </div>
                <div class="bg-emerald-900/30 border border-emerald-500/30 rounded-2xl p-4 text-center">
                    <p class="text-emerald-400 text-xs font-bold uppercase mb-2">Lunas</p>
                    <p class="text-3xl font-extrabold text-emerald-400 font-outfit"><?= $verifiedCount ?></p>
                </div>
                <div class="bg-red-900/30 border border-red-500/30 rounded-2xl p-4 text-center">
                    <p class="text-red-400 text-xs font-bold uppercase mb-2">Ditolak</p>
                    <p class="text-3xl font-extrabold text-red-400 font-outfit"><?= $rejectedCount ?></p>
                </div>
            </div>

            <!-- Search Form -->
            <div class="bg-slate-800/60 backdrop-blur-md p-5 rounded-3xl border border-slate-700/50 shadow-lg">
                <form method="POST" class="flex gap-3">
                    <input type="hidden" name="tab" value="history">
                    <input type="tel" name="search_phone" placeholder="Nomor telepon..." value="<?= htmlspecialchars($searchPhone ?? '') ?>" class="flex-1 bg-slate-900/50 border border-slate-700 rounded-2xl px-4 py-3 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-4 py-3 rounded-2xl flex items-center justify-center transition-all">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- Payment History -->
            <?php if (empty($paymentHistory)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-4xl text-slate-500 mb-4"></i>
                <p class="text-slate-300 font-bold">Tidak Ada Riwayat</p>
                <p class="text-slate-500 text-sm mt-1">Belum ada riwayat pembayaran</p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($paymentHistory as $p): 
                    // Determine payment status
                    if ($p['payment_method'] === 'qris') {
                        // QRIS: use verification_status
                        $st = $p['verification_status'] ?? 'pending';
                    } else {
                        // Cash/Transfer: use payment_status + paid_amount
                        if ($p['payment_status'] === 'success' && $p['paid_amount'] > 0) {
                            $st = 'verified'; // Sudah bayar
                        } elseif ($p['payment_status'] === 'pending' && $p['paid_amount'] == 0) {
                            $st = 'pending'; // Belum bayar
                        } else {
                            $st = 'rejected';
                        }
                    }
                    
                    $badge = [
                        'pending' => ['bg' => 'bg-amber-900/30', 'text' => 'text-amber-400', 'icon' => 'fa-clock', 'label' => 'Belum Bayar'],
                        'verified' => ['bg' => 'bg-emerald-900/30', 'text' => 'text-emerald-400', 'icon' => 'fa-check-circle', 'label' => 'Lunas'],
                        'rejected' => ['bg' => 'bg-red-900/30', 'text' => 'text-red-400', 'icon' => 'fa-times-circle', 'label' => 'Ditolak']
                    ][$st] ?? ['bg' => 'bg-slate-700/30', 'text' => 'text-slate-400', 'icon' => 'fa-question', 'label' => '?'];
                    
                    $method = [
                        'qris' => ['icon' => 'fa-qrcode', 'label' => 'QRIS', 'color' => 'text-blue-400'],
                        'cash' => ['icon' => 'fa-wallet', 'label' => 'Tunai', 'color' => 'text-green-400'],
                        'transfer' => ['icon' => 'fa-bank', 'label' => 'Transfer', 'color' => 'text-purple-400']
                    ][$p['payment_method'] ?? 'cash'] ?? ['icon' => 'fa-money', 'label' => 'Lain', 'color' => 'text-slate-400'];
                ?>
                <div class="bg-slate-800/60 backdrop-blur-md rounded-2xl border border-slate-700 p-4 hover:border-slate-600 transition-colors">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-mono text-sm font-bold text-slate-300 mb-1"><?= $p['order_number'] ?></p>
                            <p class="text-sm text-slate-400"><i class="fas fa-user mr-1"></i><?= htmlspecialchars($p['customer_name']) ?></p>
                            <div class="flex gap-2 mt-2">
                                <span class="text-xs bg-slate-900/50 text-slate-400 px-2 py-1 rounded">
                                    <i class="fas fa-calendar mr-1"></i><?= date('d/m', strtotime($p['created_at'])) ?>
                                </span>
                                <span class="text-xs <?= $method['color'] ?> bg-slate-900/50 px-2 py-1 rounded"><i class="fas <?= $method['icon'] ?> mr-1"></i><?= $method['label'] ?></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-bold <?= $badge['bg'] ?> <?= $badge['text'] ?>">
                                <i class="fas <?= $badge['icon'] ?>"></i><?= $badge['label'] ?>
                            </span>
                            <p class="text-xl font-extrabold text-emerald-400 font-outfit mt-2"><?= formatRupiah($p['total']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="p-4 bg-blue-900/20 border border-blue-500/30 rounded-xl text-sm text-blue-300">
                <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                Riwayat otomatis dihapus 24 jam setelah pembelian
            </div>

        <?php endif; ?>

    </main>

    <?php include 'bottom_nav.php'; ?>

    <script>
        function goToPaymentCheckout() {
            const orderNum = '<?= htmlspecialchars($orderNumber ?? '') ?>';
            if (!orderNum) {
                alert('Order number tidak ditemukan');
                return;
            }
            window.location.href = 'payment_qris.php?order=' + encodeURIComponent(orderNum);
        }
    </script>

    <?php if ($tab === 'track' && $order && !in_array($order['status'], ['completed', 'cancelled'])): ?>
    <script>
        setTimeout(() => location.reload(), 10000);
        
        // Real-time payment status polling
        const orderNum = '<?= htmlspecialchars($orderNumber) ?>';
        let statusCheckCount = 0;
        const maxStatusChecks = 300;
        
        function checkPaymentStatus() {
            statusCheckCount++;
            if (statusCheckCount > maxStatusChecks) return;
            
            fetch('check_payment_status.php?order=' + orderNum + '&t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    if (data.verified === true) {
                        console.log('Payment verified');
                        setTimeout(() => location.reload(), 500);
                        return;
                    }
                    if (data.status === 'pending' || data.verified === false) {
                        setTimeout(checkPaymentStatus, 1000);
                    }
                })
                .catch(e => {
                    if (statusCheckCount < maxStatusChecks) {
                        setTimeout(checkPaymentStatus, 2000);
                    }
                });
        }
        
        checkPaymentStatus();
    </script>
    <?php endif; ?>

</body>
</html>
