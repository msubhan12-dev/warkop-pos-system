    <?php
    require_once '../config/config.php';

    $db = getDB();
    $orderNumber = $_GET['order'] ?? $_POST['order'] ?? $_SESSION['last_order_number'] ?? null;
    $order = null;
    $error = '';

    if ($orderNumber) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ?");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $error = 'Pesanan tidak ditemukan.';
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
        <title>Lacak Pesanan - <?= APP_NAME ?></title>
        <script src="https://cdn.tailwindcss.com?v=1783809316.4554088"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                background-color: #0B1121;
                color: #f1f5f9;
            }
            .font-outfit { font-family: 'Outfit', sans-serif; }
            
            .timeline-step.active .step-icon {
                background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
                border-color: #fb7185;
                color: white;
                box-shadow: 0 0 20px rgba(225,29,72,0.5);
            }
            .timeline-step.past .step-icon {
                background-color: #881337;
                border-color: #f43f5e;
                color: #fecdd3;
            }
            .timeline-step.future .step-icon {
                background-color: #1e293b;
                border-color: #334155;
                color: #64748b;
            }
            
            .timeline-step.past .step-line,
            .timeline-step.active .step-line {
                background-color: #f43f5e;
            }
            .timeline-step.future .step-line {
                background-color: #334155;
            }
        </style>
    </head>
    <body class="pb-24 bg-slate-900">
        
        <header class="bg-slate-900/80 backdrop-blur-md sticky top-0 z-40 border-b border-slate-800">
            <div class="px-6 py-4 flex flex-col items-center justify-center">
                <h1 class="font-extrabold text-2xl font-outfit text-white tracking-tight drop-shadow-sm flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-rose-400"></i> Lacak Pesanan
                </h1>
            </div>
        </header>

        <main class="p-6 max-w-md mx-auto space-y-6">
            
            <!-- Search Form -->
            <div class="bg-slate-800/60 backdrop-blur-md p-5 rounded-3xl border border-slate-700/50 shadow-lg">
                <form method="GET" class="flex gap-3 relative group">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-receipt text-slate-400 group-focus-within:text-rose-400 transition-colors"></i>
                        </div>
                        <input type="text" name="order" value="<?= htmlspecialchars($orderNumber ?? '') ?>" placeholder="Nomor Pesanan (Cth: ORD...)" class="w-full bg-slate-900/50 border border-slate-700 rounded-2xl pl-11 pr-4 py-3.5 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all font-medium uppercase shadow-inner" required>
                    </div>
                    <button type="submit" class="bg-gradient-to-tr from-rose-600 to-red-500 hover:from-rose-500 hover:to-red-400 text-white font-bold w-14 h-[52px] rounded-2xl shadow-lg transition-transform hover:-translate-y-0.5 flex items-center justify-center flex-shrink-0 border border-rose-500/50">
                        <i class="fas fa-search text-lg"></i>
                    </button>
                </form>
                <?php if ($error): ?>
                    <p class="text-rose-400 text-sm mt-3 font-medium flex items-center gap-2"><i class="fas fa-exclamation-circle"></i> <?= $error ?></p>
                <?php endif; ?>
            </div>

            <?php if ($order): ?>
                <?php 
                    $orderType = $order['order_type'] ?? 'dine_in';
                    
                    if ($orderType === 'dine_in') {
                        $statuses = ['pending', 'confirmed', 'cooking', 'ready', 'served'];
                    } else {
                        $statuses = ['pending', 'confirmed', 'cooking', 'ready', 'completed'];
                    }
                    
                    $statusIcons = [
                        'pending' => 'fa-wallet',
                        'confirmed' => 'fa-receipt',
                        'cooking' => 'fa-fire-burner',
                    ];
                    if ($orderType === 'dine_in') {
                        $statusIcons['ready'] = 'fa-bell';
                        $statusIcons['served'] = 'fa-utensils';
                    } elseif ($orderType === 'take_away') {
                        $statusIcons['ready'] = 'fa-shopping-bag';
                        $statusIcons['completed'] = 'fa-check-double';
                    } else {
                        $statusIcons['ready'] = 'fa-motorcycle';
                        $statusIcons['completed'] = 'fa-house-chimney-user';
                    }
                    
                    $statusText = [
                        'pending' => 'Menunggu Pembayaran',
                        'confirmed' => 'Pesanan Diterima',
                        'cooking' => 'Sedang Disiapkan',
                    ];
                    if ($orderType === 'dine_in') {
                        $statusText['ready'] = 'Siap Disajikan';
                        $statusText['served'] = 'Selesai';
                    } elseif ($orderType === 'take_away') {
                        $statusText['ready'] = 'Siap Diambil';
                        $statusText['completed'] = 'Selesai';
                    } else {
                        $statusText['ready'] = 'Dalam Perjalanan';
                        $statusText['completed'] = 'Pesanan Tiba';
                    }
                    
                    $statusDesc = [
                        'pending' => 'Segera selesaikan pembayaran agar pesanan diproses.',
                        'confirmed' => 'Pesananmu sudah masuk dan diterima oleh kasir.',
                        'cooking' => 'Koki kami sedang meracik pesananmu dengan sepenuh hati.',
                    ];
                    if ($orderType === 'dine_in') {
                        $statusDesc['ready'] = 'Pesanan sudah siap dan akan segera diantar ke mejamu.';
                        $statusDesc['served'] = 'Pesanan sudah disajikan. Selamat menikmati!';
                    } elseif ($orderType === 'take_away') {
                        $statusDesc['ready'] = 'Pesanan sudah matang dan siap untuk kamu ambil di kasir.';
                        $statusDesc['completed'] = 'Pesanan sudah diambil. Terima kasih!';
                    } else {
                        $statusDesc['ready'] = 'Kurir sedang meluncur mengantarkan pesanan panasmu ke alamat tujuan.';
                        $statusDesc['completed'] = 'Pesanan sudah sampai. Selamat menikmati!';
                    }
                    
                    $currentIndex = array_search($order['status'], $statuses);
                    if ($order['status'] === 'cancelled') $currentIndex = -1;

                    // For Hero Banner
                    $currentStatusLabel = $order['status'] === 'cancelled' ? 'Dibatalkan' : $statusText[$order['status']];
                    $currentStatusDesc = $order['status'] === 'cancelled' ? 'Pesanan ini telah dibatalkan oleh kasir/admin.' : $statusDesc[$order['status']];
                    $currentIcon = $order['status'] === 'cancelled' ? 'fa-times-circle' : $statusIcons[$order['status']];
                ?>

                <!-- Refresh Button Moved to Top -->
                <button onclick="window.location.reload()" class="w-full bg-slate-800/80 hover:bg-slate-700 backdrop-blur-md text-slate-300 font-bold py-3.5 px-6 rounded-2xl border border-slate-700 transition-colors shadow-lg flex items-center justify-center gap-2 mt-2">
                    <i class="fas fa-sync-alt"></i> Perbarui Status Pesanan
                </button>

                <!-- Unified Order Tracking Card -->
                <div class="bg-slate-800/80 backdrop-blur-md rounded-3xl border border-slate-700 p-6 shadow-xl relative overflow-hidden flex flex-col gap-8">
                    
                    <!-- Background glow -->
                    <div class="absolute top-0 right-0 w-48 h-48 bg-rose-500/10 rounded-full blur-3xl -mr-10 -mt-10 pointer-events-none"></div>

                    <!-- 1. Order ID & Time -->
                    <div class="flex justify-between items-center relative z-10 border-b border-slate-700/50 pb-4">
                        <div>
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Nomor Pesanan</p>
                            <?php
                                $onum = $order['order_number'];
                                $formattedNum = substr($onum, 0, 3) . '-' . substr($onum, 3, 8) . '-' . substr($onum, 11);
                            ?>
                            <h3 class="font-extrabold text-lg font-outfit text-white">#<?= $formattedNum ?></h3>
                        </div>
                        <span class="bg-slate-900 border border-slate-700 text-slate-300 font-bold px-3 py-1.5 rounded-lg text-sm flex items-center gap-2 shadow-inner">
                            <i class="fas fa-clock text-rose-500"></i> <?= date('H:i', strtotime($order['created_at'])) ?>
                        </span>
                    </div>
                    
                    <!-- 2. Hero Status -->
                    <div class="text-center relative z-10 pt-2 pb-4">
                        <div class="w-24 h-24 mx-auto bg-gradient-to-br <?= $order['status'] === 'cancelled' ? 'from-rose-500 to-red-600' : 'from-rose-400 to-red-500' ?> rounded-full flex items-center justify-center shadow-lg shadow-rose-500/30 mb-5 border-4 border-slate-800">
                            <i class="fas <?= $currentIcon ?> text-4xl text-white"></i>
                        </div>
                        
                        <h2 class="font-extrabold text-2xl font-outfit <?= $order['status'] === 'cancelled' ? 'text-rose-400' : 'text-rose-400' ?>">
                            <?= $currentStatusLabel ?>
                        </h2>
                        <p class="text-sm text-slate-300 mt-2 font-medium px-4">
                            <?= $currentStatusDesc ?>
                        </p>
                    </div>

                    <!-- 3. Horizontal Stepper -->
                    <?php if ($order['status'] !== 'cancelled'): ?>
                    <div class="relative z-10 pb-4">
                        <div class="flex justify-between items-center relative">
                            <!-- Background Line -->
                            <div class="absolute left-[10%] right-[10%] top-1/2 h-1 bg-slate-700 -translate-y-1/2 z-0 rounded-full"></div>
                            <!-- Active Progress Line -->
                            <?php 
                                $progressPercent = count($statuses) > 1 ? ($currentIndex / (count($statuses) - 1)) * 100 : 0;
                                if ($progressPercent < 0) $progressPercent = 0;
                            ?>
                            <div class="absolute left-[10%] top-1/2 h-1 bg-rose-500 -translate-y-1/2 z-0 rounded-full transition-all duration-700" style="width: calc(<?= $progressPercent ?>% * 0.8)"></div>
                            
                            <?php foreach ($statuses as $index => $status): 
                                $isPast = $index < $currentIndex;
                                $isActive = $index === $currentIndex;
                                
                                $bgColor = $isPast || $isActive ? 'bg-rose-500 border-rose-400' : 'bg-slate-800 border-slate-600';
                                $textColor = $isPast || $isActive ? 'text-white' : 'text-slate-500';
                                $glow = $isActive ? 'shadow-[0_0_15px_rgba(225,29,72,0.6)]' : '';
                            ?>
                                <div class="relative z-10 flex flex-col items-center gap-2">
                                    <div class="w-10 h-10 rounded-full border-2 <?= $bgColor ?> <?= $textColor ?> <?= $glow ?> flex items-center justify-center transition-all duration-500 bg-slate-900">
                                        <i class="fas <?= $statusIcons[$status] ?> text-sm"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Steps Text Map -->
                        <div class="flex justify-between mt-3 px-2">
                            <span class="text-xs font-bold text-center w-12 text-slate-400">Bayar</span>
                            <span class="text-xs font-bold text-center w-12 text-slate-400">Terima</span>
                            <span class="text-xs font-bold text-center w-12 text-slate-400">Masak</span>
                            <span class="text-xs font-bold text-center w-12 text-slate-400">Kirim</span>
                            <span class="text-xs font-bold text-center w-12 text-slate-400">Selesai</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 4. Delivery Address -->
                    <?php if ($order['order_type'] === 'delivery' && !empty($order['delivery_address'])): ?>
                    <div class="p-4 bg-slate-900/50 rounded-2xl border border-slate-700/50 flex items-start gap-3 relative z-10 mt-2">
                        <i class="fas fa-motorcycle text-rose-500 mt-1"></i>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Alamat Pengiriman</p>
                            <p class="text-sm text-slate-200 font-medium leading-relaxed"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            <?php else: ?>
                <div class="text-center py-20 px-6">
                    <div class="w-24 h-24 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-slate-700">
                        <i class="fas fa-search-location text-4xl text-slate-500"></i>
                    </div>
                    <h3 class="font-extrabold text-2xl font-outfit text-white mb-2">Cari Pesananmu</h3>
                    <p class="text-slate-400">Masukkan nomor pesanan di atas untuk melihat status pesananmu saat ini.</p>
                </div>
            <?php endif; ?>
        </main>

        <?php include 'bottom_nav.php'; ?>
    </body>
    </html>
