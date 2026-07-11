<?php
require_once '../config/config.php';

$orderNumber = $_GET['order'] ?? null;

if (!$orderNumber) {
    header('Location: menu.php');
    exit;
}

// Get order details
$db = getDB();
$stmt = $db->prepare("
    SELECT o.*, t.table_number
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE o.order_number = ?
");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: menu.php');
    exit;
}

// Get order items
$stmt = $db->prepare("
    SELECT * FROM order_items WHERE order_id = ?
");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Pesanan Berhasil - <?= APP_NAME ?></title>
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
        @keyframes checkmark {
            0% { transform: scale(0) rotate(-45deg); opacity: 0; }
            50% { transform: scale(1.2) rotate(10deg); }
            100% { transform: scale(1) rotate(0); opacity: 1; }
        }
        .checkmark {
            animation: checkmark 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .floating {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-[#0B1121] text-slate-200 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full my-8">
        <!-- Success Card -->
        <div class="bg-slate-800/60 backdrop-blur-md rounded-[2rem] shadow-2xl border border-slate-700/50 p-8 relative overflow-hidden mb-6 text-center">
            <!-- Confetti Decoration -->
            <div class="absolute -right-4 -top-4 opacity-[0.03] pointer-events-none text-emerald-400">
                <i class="fas fa-gift text-9xl"></i>
            </div>
            
            <div class="inline-flex items-center justify-center w-28 h-28 bg-emerald-900/30 text-emerald-400 rounded-full mb-6 shadow-inner border border-emerald-500/20 checkmark floating relative z-10">
                <i class="fas fa-check text-6xl drop-shadow-[0_0_10px_rgba(52,211,153,0.5)]"></i>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-100 mb-2 font-outfit relative z-10 drop-shadow-md">Pesanan Berhasil!</h1>
            <p class="text-slate-400 font-medium mb-8 relative z-10">Terima kasih atas pesanan Anda.</p>
            
            <div class="bg-slate-900/50 rounded-2xl p-5 border border-slate-700/80 mb-6 relative z-10 shadow-inner">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">Nomor Pesanan</p>
                <p class="text-3xl font-black text-emerald-400 font-mono tracking-widest drop-shadow-[0_0_8px_rgba(52,211,153,0.3)]">#<?= $order['order_number'] ?></p>
            </div>
            
            <!-- Order Details -->
            <div class="space-y-3 text-sm relative z-10">
                <?php if ($order['table_number']): ?>
                <div class="flex items-center justify-between py-2 border-b border-slate-700/50 border-dashed">
                    <span class="text-slate-400 font-medium flex items-center">
                        <i class="fas fa-chair w-6 text-slate-500"></i> Meja
                    </span>
                    <span class="font-bold text-slate-200 bg-slate-800 px-2 py-0.5 rounded shadow-sm border border-slate-700"><?= $order['table_number'] ?></span>
                </div>
                <?php endif; ?>
                
                <div class="flex items-center justify-between py-2 border-b border-slate-700/50 border-dashed">
                    <span class="text-slate-400 font-medium flex items-center">
                        <i class="fas fa-user-circle w-6 text-slate-500"></i> Nama Pemesan
                    </span>
                    <span class="font-bold text-slate-200"><?= $order['customer_name'] ?></span>
                </div>
                
                <div class="flex items-center justify-between py-2 border-b border-slate-700/50 border-dashed">
                    <span class="text-slate-400 font-medium flex items-center">
                        <i class="fas fa-clock w-6 text-slate-500"></i> Waktu Order
                    </span>
                    <span class="font-bold text-slate-200"><?= formatDateTime($order['created_at'], 'H:i') ?></span>
                </div>
                
                <div class="flex items-center justify-between py-2">
                    <span class="text-slate-400 font-medium flex items-center">
                        <i class="fas fa-shopping-bag w-6 text-slate-500"></i> Tipe Pesanan
                    </span>
                    <span class="font-bold text-slate-200"><?= getStatusText($order['order_type']) ?></span>
                </div>
                
                <?php if ($order['order_type'] === 'delivery' && !empty($order['delivery_address'])): ?>
                <div class="flex items-start justify-between py-2 border-t border-slate-700/50 mt-2 pt-3">
                    <span class="text-slate-400 font-medium flex items-start">
                        <i class="fas fa-map-marker-alt w-6 text-slate-500 mt-1"></i> Alamat
                    </span>
                    <span class="font-bold text-slate-200 text-right text-sm max-w-[60%] leading-snug">
                        <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-slate-800/60 backdrop-blur-md rounded-[2rem] shadow-xl border border-slate-700/50 p-6 mb-6">
            <h3 class="font-extrabold text-lg text-slate-100 mb-4 font-outfit flex items-center drop-shadow-sm">
                <i class="fas fa-receipt text-emerald-400 mr-2 bg-slate-900/50 p-1.5 rounded-lg border border-slate-700"></i>
                Rincian Pesanan
            </h3>
            
            <div class="space-y-3 mb-5">
                <?php foreach ($items as $item): ?>
                <div class="flex justify-between text-sm items-start">
                    <div class="flex-1 pr-4">
                        <span class="font-bold text-slate-200"><?= $item['quantity'] ?>x</span>
                        <span class="font-medium text-slate-400 ml-1"><?= $item['menu_name'] ?></span>
                    </div>
                    <span class="font-bold text-slate-200"><?= formatRupiah($item['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="border-t border-dashed border-slate-700 pt-4">
                <div class="flex justify-between items-center text-lg">
                    <span class="font-extrabold text-slate-300 font-outfit">Total Tagihan</span>
                    <span class="font-black text-emerald-400 font-outfit drop-shadow-[0_0_8px_rgba(52,211,153,0.3)]"><?= formatRupiah($order['total']) ?></span>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="bg-gradient-to-r from-blue-900/30 to-indigo-900/30 backdrop-blur-sm border border-blue-500/20 p-5 rounded-3xl mb-6 shadow-lg flex gap-4 items-center">
            <div class="bg-blue-500/20 w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 shadow-inner border border-blue-500/20 text-blue-400 text-xl">
                <i class="fas fa-info-circle drop-shadow-sm"></i>
            </div>
            <p class="text-sm text-blue-200/90 font-medium leading-relaxed">
                Pesanan Anda sedang diproses. Silakan menunggu pemanggilan dari kasir atau pelayan kami.
            </p>
        </div>

        <!-- Actions -->
        <div class="grid grid-cols-1 gap-3">
            <a 
                href="menu.php" 
                class="w-full bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-extrabold py-4 px-6 rounded-2xl shadow-[0_8px_20px_-6px_rgba(16,185,129,0.5)] hover:shadow-[0_12px_25px_-6px_rgba(16,185,129,0.6)] hover:-translate-y-0.5 transition-all duration-300 text-center flex items-center justify-center gap-2 font-outfit text-lg"
            >
                <i class="fas fa-shopping-basket"></i> Pesan Lagi
            </a>
        </div>
    </div>

    <style>
        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</body>
</html>
