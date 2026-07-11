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
    <script src="https://cdn.tailwindcss.com"></script>
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
            background: linear-gradient(135deg, #10b981 0%, #0d9488 100%);
            border-color: #34d399;
            color: white;
            box-shadow: 0 0 20px rgba(16,185,129,0.5);
        }
        .timeline-step.past .step-icon {
            background-color: #065f46;
            border-color: #10b981;
            color: #a7f3d0;
        }
        .timeline-step.future .step-icon {
            background-color: #1e293b;
            border-color: #334155;
            color: #64748b;
        }
        
        .timeline-step.past .step-line,
        .timeline-step.active .step-line {
            background-color: #10b981;
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
                <i class="fas fa-map-marker-alt text-emerald-400"></i> Lacak Pesanan
            </h1>
        </div>
    </header>

    <main class="p-6 max-w-md mx-auto space-y-6">
        
        <!-- Search Form -->
        <div class="bg-slate-800/60 backdrop-blur-md p-5 rounded-3xl border border-slate-700/50 shadow-lg">
            <form method="GET" class="flex gap-3 relative group">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-receipt text-slate-400 group-focus-within:text-emerald-400 transition-colors"></i>
                    </div>
                    <input type="text" name="order" value="<?= htmlspecialchars($orderNumber ?? '') ?>" placeholder="Nomor Pesanan (Cth: ORD...)" class="w-full bg-slate-900/50 border border-slate-700 rounded-2xl pl-11 pr-4 py-3.5 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-medium uppercase shadow-inner" required>
                </div>
                <button type="submit" class="bg-gradient-to-tr from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold w-14 h-[52px] rounded-2xl shadow-lg transition-transform hover:-translate-y-0.5 flex items-center justify-center flex-shrink-0 border border-emerald-500/50">
                    <i class="fas fa-search text-lg"></i>
                </button>
            </form>
            <?php if ($error): ?>
                <p class="text-rose-400 text-sm mt-3 font-medium flex items-center gap-2"><i class="fas fa-exclamation-circle"></i> <?= $error ?></p>
            <?php endif; ?>
        </div>

        <?php if ($order): ?>
            <!-- Order Details Card -->
            <div class="bg-slate-800/80 backdrop-blur-md rounded-3xl border border-slate-700 p-6 shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl -mr-10 -mt-10 pointer-events-none"></div>
                
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Status Pesanan</p>
                        <h2 class="font-extrabold text-xl font-outfit text-white">#<?= $order['order_number'] ?></h2>
                    </div>
                    <span class="bg-slate-900 border border-slate-700 text-slate-300 font-bold px-3 py-1.5 rounded-lg text-sm flex items-center gap-2 shadow-inner">
                        <i class="fas fa-clock text-emerald-500"></i> <?= date('H:i', strtotime($order['created_at'])) ?>
                    </span>
                </div>
                
                <!-- Status Timeline -->
                <?php 
                    $statuses = ['pending', 'confirmed', 'cooking', 'ready', 'completed'];
                    if ($order['order_type'] === 'dine_in') {
                        $statuses = ['pending', 'confirmed', 'cooking', 'ready', 'served'];
                    } elseif ($order['order_type'] === 'delivery') {
                        $statuses = ['pending', 'confirmed', 'cooking', 'ready', 'completed']; // For delivery, 'completed' means 'Pesanan Diantar'
                    }
                    
                    $statusIcons = [
                        'pending' => 'fa-wallet',
                        'confirmed' => 'fa-check-circle',
                        'cooking' => 'fa-fire',
                        'ready' => 'fa-bell',
                        'served' => 'fa-concierge-bell',
                        'completed' => 'fa-box-open'
                    ];
                    
                    $statusText = [
                        'pending' => 'Menunggu Pembayaran',
                        'confirmed' => 'Dikonfirmasi',
                        'cooking' => 'Sedang Dimasak',
                        'ready' => 'Siap Disajikan',
                        'served' => 'Sudah Diantar',
                        'completed' => 'Selesai'
                    ];
                    
                    $statusDesc = [
                        'pending' => 'Segera selesaikan pembayaran.',
                        'confirmed' => 'Pesanan diterima admin.',
                        'cooking' => 'Koki sedang meracik pesananmu.',
                        'ready' => 'Pesanan siap diambil/diantar!',
                        'served' => 'Pesanan sudah di mejamu. Selamat menikmati!',
                        'completed' => $order['order_type'] === 'delivery' ? 'Pesanan sedang/sudah diantar ke alamat Anda.' : 'Pesanan selesai. Terima kasih!'
                    ];
                    
                    // Show delivery address if applicable
                    if ($order['order_type'] === 'delivery' && !empty($order['delivery_address'])):
                ?>
                    <div class="mb-6 p-4 bg-slate-900/50 rounded-2xl border border-slate-700/50 flex items-start gap-3">
                        <i class="fas fa-motorcycle text-emerald-500 mt-1"></i>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Alamat Pengiriman</p>
                            <p class="text-sm text-slate-200 font-medium leading-relaxed"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
                        </div>
                    </div>
                <?php 
                    endif;
                    
                    // Determine current status index
                    $currentIndex = array_search($order['status'], $statuses);
                    if ($order['status'] === 'cancelled') $currentIndex = -1;
                ?>

                <div class="space-y-0 relative mt-4">
                    <!-- Line behind the steps -->
                    <div class="absolute left-6 top-6 bottom-6 w-0.5 bg-slate-700 rounded-full z-0"></div>
                    
                    <?php if ($order['status'] === 'cancelled'): ?>
                        <div class="flex gap-4 relative z-10 timeline-step active">
                            <div class="w-12 h-12 rounded-full border-4 flex items-center justify-center step-icon !bg-rose-600 !border-rose-400 !shadow-[0_0_20px_rgba(225,29,72,0.5)] z-10 flex-shrink-0">
                                <i class="fas fa-times-circle text-lg"></i>
                            </div>
                            <div class="pt-3 pb-6">
                                <h3 class="font-extrabold text-rose-400 font-outfit">Pesanan Dibatalkan</h3>
                                <p class="text-sm text-slate-400 mt-1">Pesanan ini telah dibatalkan oleh kasir/admin.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($statuses as $index => $status): 
                            $stepState = 'future';
                            if ($index < $currentIndex) $stepState = 'past';
                            if ($index === $currentIndex) $stepState = 'active';
                        ?>
                        <div class="flex gap-4 relative z-10 timeline-step <?= $stepState ?>">
                            <!-- Colored line overlay for past steps -->
                            <?php if ($index > 0): ?>
                                <div class="absolute -top-12 left-6 w-0.5 h-12 step-line z-0 <?= $index <= $currentIndex ? '!bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : '' ?>"></div>
                            <?php endif; ?>
                            
                            <div class="w-12 h-12 rounded-full border-4 flex items-center justify-center step-icon z-10 flex-shrink-0">
                                <i class="fas <?= $statusIcons[$status] ?> text-lg"></i>
                            </div>
                            
                            <div class="pt-3 pb-8">
                                <h3 class="font-bold <?= $stepState === 'active' ? 'text-emerald-400 font-extrabold text-lg' : ($stepState === 'past' ? 'text-white' : 'text-slate-500') ?> font-outfit transition-colors">
                                    <?= $statusText[$status] ?>
                                </h3>
                                <?php if ($stepState === 'active' || $stepState === 'past'): ?>
                                    <p class="text-sm <?= $stepState === 'active' ? 'text-slate-300' : 'text-slate-500' ?> mt-1 font-medium">
                                        <?= $statusDesc[$status] ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="bg-slate-800/50 rounded-2xl p-4 border border-slate-700/50 text-center">
                <p class="text-sm text-slate-400 font-medium">Laman ini akan refresh otomatis setiap 10 detik.</p>
                <script>
                    setTimeout(() => {
                        window.location.reload();
                    }, 10000);
                </script>
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
