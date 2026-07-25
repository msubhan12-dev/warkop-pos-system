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
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-bounce {
            animation: bounce 2s ease-in-out infinite;
        }
        @media print {
            body { background: white; }
            .no-print { display: none; }
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
            <h1 class="text-xl font-extrabold font-outfit text-slate-100 drop-shadow-sm">Pesanan Berhasil</h1>
        </div>
    </header>

    <main class="p-4 max-w-2xl mx-auto pb-32 sm:pb-24">
        
        <!-- Success Card -->
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl border border-emerald-500/50 p-8 mb-6 shadow-xl relative overflow-hidden text-white">
            <!-- Background Decoration -->
            <div class="absolute -right-10 -top-10 opacity-10 pointer-events-none">
                <i class="fas fa-check-circle text-[15rem]"></i>
            </div>
            
            <div class="text-center mb-8 relative z-10">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-white/20 backdrop-blur-md text-white rounded-full mb-5 shadow-lg border border-white/30 animate-bounce">
                    <i class="fas fa-check text-5xl drop-shadow-[0_0_10px_rgba(255,255,255,0.5)] checkmark"></i>
                </div>
                <h2 class="text-4xl font-extrabold font-outfit tracking-tight drop-shadow-md">Berhasil!</h2>
                <p class="text-emerald-50 mt-2 font-medium text-lg">Pesanan Anda sudah diterima oleh dapur.</p>
            </div>

            <!-- Status Info -->
            <div class="grid grid-cols-2 gap-4 mb-8 relative z-10">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <p class="text-xs text-emerald-100 font-medium uppercase tracking-wider mb-1">No. Pesanan</p>
                    <p class="font-bold text-xl font-mono text-white">
                        <?php
                            $onum = $order['order_number'];
                            $formattedNum = substr($onum, 0, 3) . '-' . substr($onum, 3, 8) . '-' . substr($onum, 11);
                        ?>
                        <?= $formattedNum ?>
                    </p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <p class="text-xs text-emerald-100 font-medium uppercase tracking-wider mb-1">Total</p>
                    <p class="font-bold text-xl text-white"><?= formatRupiah($order['total']) ?></p>
                </div>
            </div>

            <!-- Digital Receipt Card -->
            <div id="receipt" class="bg-white rounded-2xl border border-stone-200 p-6 mb-6 shadow-sm relative overflow-hidden text-slate-800">
                <!-- Watermark Stamp -->
                <div class="absolute -right-4 -top-4 opacity-10 rotate-12 pointer-events-none">
                    <i class="fas fa-check-circle text-8xl text-emerald-600"></i>
                </div>
                
                <!-- Ticket Header -->
                <div class="text-center pb-4 mb-4 border-b border-dashed border-stone-200">
                    <div class="inline-block bg-white rounded-full p-0.5 shadow-sm mb-2 overflow-hidden w-12 h-12 border border-stone-100">
                        <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" alt="Logo" class="w-full h-full object-cover rounded-full">
                    </div>
                    <h3 class="text-xl font-extrabold text-stone-850 font-outfit tracking-tight"><?= APP_NAME ?></h3>
                    <p class="text-xs text-stone-500 font-medium">Bukti Pesanan Resmi</p>
                    
                    <!-- Status Badge -->
                    <span class="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wide border border-emerald-200">
                        <i class="fas fa-check-circle"></i> Diterima
                    </span>
                </div>
                
                <!-- Ticket Details -->
                <div class="space-y-2.5 text-xs text-stone-600 mb-4 pb-4 border-b border-dashed border-stone-200">
                    <div class="flex justify-between">
                        <span class="font-medium">Nomor Struk</span>
                        <span class="font-bold text-stone-800 font-mono"><?= $order['order_number'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Tanggal Pesanan</span>
                        <span class="font-semibold text-stone-800"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Nama Pemesan</span>
                        <span class="font-bold text-stone-800"><?= $order['customer_name'] ?></span>
                    </div>
                    <?php if ($order['customer_phone']): ?>
                    <div class="flex justify-between">
                        <span class="font-medium">No. Telepon</span>
                        <span class="font-semibold text-stone-800"><?= $order['customer_phone'] ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['table_number']): ?>
                    <div class="flex justify-between">
                        <span class="font-medium">Nomor Meja</span>
                        <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">Meja <?= $order['table_number'] ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['order_type'] === 'delivery' && !empty($order['delivery_address'])): ?>
                    <div class="flex justify-between items-start">
                        <span class="font-medium">Alamat Pengiriman</span>
                        <span class="font-semibold text-stone-800 text-right max-w-xs"><?= htmlspecialchars($order['delivery_address']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Ticket Items -->
                <div class="space-y-3 mb-4 pb-4 border-b border-dashed border-stone-200">
                    <p class="text-xs font-bold text-stone-850 uppercase tracking-wider mb-2">Daftar Menu:</p>
                    <?php foreach ($items as $item): ?>
                    <div class="flex justify-between text-xs items-start">
                        <div class="flex-1 pr-4">
                            <span class="font-bold text-stone-800"><?= $item['quantity'] ?>x</span> 
                            <span class="font-medium text-stone-700"><?= $item['menu_name'] ?></span>
                        </div>
                        <span class="font-semibold text-stone-850 font-mono"><?= formatRupiah($item['subtotal']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Ticket Summary -->
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between items-center pt-2 border-t border-stone-100 mt-2">
                        <span class="text-sm font-extrabold text-stone-800">Total Pesanan</span>
                        <span class="text-lg font-black text-emerald-600 font-outfit"><?= formatRupiah($order['total']) ?></span>
                    </div>
                </div>
                
                <!-- Ticket Footer -->
                <div class="text-center pt-3 border-t border-dashed border-stone-200 mt-4 text-xs text-stone-500 font-medium">
                    <p class="text-xs leading-relaxed">Terima kasih atas pesanan Anda!</p>
                    <p class="text-xs text-stone-400 mt-0.5">Pesanan sedang dipersiapkan oleh dapur kami.</p>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 p-4 mb-6 relative z-10">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-emerald-200 text-xl flex-shrink-0 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-bold text-emerald-50 mb-1">Pesanan Diterima!</p>
                        <p class="text-xs text-emerald-100/80 leading-relaxed">
                            Pesanan Anda sedang diproses oleh dapur. Kami akan memanggil Anda melalui sistem atau kasir kami ketika pesanan siap.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 gap-3 relative z-10">
                <a 
                    href="menu.php"
                    class="bg-white text-emerald-700 hover:bg-emerald-50 font-bold py-4 px-4 rounded-2xl transition-all shadow-[0_8px_20px_-6px_rgba(0,0,0,0.3)] flex items-center justify-center gap-2"
                >
                    <i class="fas fa-shopping-basket"></i> Pesan Lagi
                </a>
            </div>
        </div>
    </main>
</body>
</html>
