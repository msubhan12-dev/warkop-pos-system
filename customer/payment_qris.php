<?php
require_once '../config/config.php';

// Prevent browser caching of payment states
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
// require_once '../includes/access_log.php';  // Disable for now

$orderNumber = clean($_GET['order'] ?? '');
if (empty($orderNumber)) {
    header('Location: menu.php');
    exit;
}

// Log access (disabled due to permission issues)
// logAccess('payment_qris.php', 'view', [
//     'order_number' => $orderNumber,
//     'method' => $_SERVER['REQUEST_METHOD']
// ]);

$db = getDB();
$stmt = $db->prepare("
    SELECT o.*, p.id as payment_id, p.amount, p.verification_status, p.payment_method, p.proof_of_payment, t.table_number
    FROM orders o
    JOIN payments p ON o.id = p.order_id
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE o.order_number = ? AND p.payment_method = 'qris'
");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: menu.php');
    exit;
}

$isVerified = $order['verification_status'] === 'verified';
$isRejected = $order['verification_status'] === 'rejected';

$error = '';
$success = '';
if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Handle proof of payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    // Protect against resubmissions resetting verified status
    $stmt = $db->prepare("SELECT verification_status FROM payments WHERE order_id = ? AND payment_method = 'qris'");
    $stmt->execute([$order['id']]);
    $currentPaymentStatus = $stmt->fetch();
    
    if ($currentPaymentStatus && $currentPaymentStatus['verification_status'] === 'verified') {
        header("Location: payment_qris.php?order=" . urlencode($orderNumber));
        exit;
    }

    if ($_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['payment_proof'], 'payment_proofs');
        
        if ($uploadResult['success']) {
            try {
                $stmt = $db->prepare("
                    UPDATE payments 
                    SET proof_of_payment = ?, verification_status = 'pending'
                    WHERE order_id = ? AND payment_method = 'qris'
                ");
                $stmt->execute([$uploadResult['path'], $order['id']]);
                
                $_SESSION['success_msg'] = 'Bukti pembayaran berhasil dikirim! Menunggu verifikasi admin.';
                header("Location: payment_qris.php?order=" . urlencode($orderNumber));
                exit;
                
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
                // logAccess('payment_qris.php', 'upload_error', [
                //     'order_number' => $orderNumber,
                //     'error' => $e->getMessage()
                // ]);
            }
        } else {
            $error = $uploadResult['message'];
            // logAccess('payment_qris.php', 'upload_failed', [
            //     'order_number' => $orderNumber,
            //     'error' => $uploadResult['message']
            // ]);
        }
    } else {
        $error = 'File gagal diupload';
        // logAccess('payment_qris.php', 'upload_error', [
        //     'order_number' => $orderNumber,
        //     'error_code' => $_FILES['payment_proof']['error']
        // ]);
    }
}

// Get order items for receipt
$stmt = $db->prepare("
    SELECT oi.*, oi.menu_name, oi.price, oi.quantity, oi.subtotal
    FROM order_items oi
    WHERE oi.order_id = ?
");
$stmt->execute([$order['id']]);
$orderItems = $stmt->fetchAll();

require_once '../includes/QrisGenerator.php';

function generateQRSVG($text, $size = 350) {
    $encodedText = urlencode($text);
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . $encodedText;
}

// Generate the dynamic QRIS string based on the total order amount (no admin fee)
$dynamicQrisString = QrisGenerator::generateDynamic($order['amount']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Pembayaran QRIS - <?= APP_NAME ?></title>
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
        @media print {
            body * { visibility: hidden; }
            #receipt, #receipt * { visibility: visible; }
            #receipt { position: absolute; left: 0; top: 0; width: 80mm; }
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
            <h1 class="text-xl font-extrabold font-outfit text-slate-100 drop-shadow-sm">Pembayaran QRIS</h1>
        </div>
    </header>

    <main class="p-4 max-w-2xl mx-auto pb-32 sm:pb-24">
        <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-900/30 border-l-4 border-red-500 text-red-300 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-4 p-4 bg-emerald-900/30 border-l-4 border-emerald-500 text-emerald-300 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i><?= $success ?>
        </div>
        <?php endif; ?>

        <!-- VERIFIED STATE -->
        <?php if ($isVerified): ?>
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl border border-emerald-500/50 p-8 mb-6 shadow-xl relative overflow-hidden text-white">
            <!-- Background Decoration -->
            <div class="absolute -right-10 -top-10 opacity-10 pointer-events-none">
                <i class="fas fa-check-circle text-[15rem]"></i>
            </div>
            
            <div class="text-center mb-8 relative z-10">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-white/20 backdrop-blur-md text-white rounded-full mb-5 shadow-lg border border-white/30 animate-bounce">
                    <i class="fas fa-check text-5xl drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]"></i>
                </div>
                <h2 class="text-4xl font-extrabold font-outfit tracking-tight drop-shadow-md">Berhasil!</h2>
                <p class="text-emerald-50 mt-2 font-medium text-lg">Pembayaran telah dikonfirmasi dapur.</p>
            </div>

            <!-- Status Info -->
            <div class="grid grid-cols-2 gap-4 mb-8 relative z-10">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <p class="text-xs text-emerald-100 font-medium uppercase tracking-wider mb-1">No. Pesanan</p>
                    <p class="font-bold text-xl font-mono text-white"><?= $order['order_number'] ?></p>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 text-center border border-white/20">
                    <p class="text-xs text-emerald-100 font-medium uppercase tracking-wider mb-1">Total</p>
                    <p class="font-bold text-xl text-white"><?= formatRupiah($order['amount']) ?></p>
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
                    <p class="text-xs text-stone-500 font-medium">Bukti Transaksi Resmi</p>
                    
                    <!-- Paid Badge -->
                    <span class="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wide border border-emerald-200">
                        <i class="fas fa-check-circle"></i> Lunas / Paid
                    </span>
                </div>
                
                <!-- Ticket Details -->
                <div class="space-y-2.5 text-xs text-stone-600 mb-4 pb-4 border-b border-dashed border-stone-200">
                    <div class="flex justify-between">
                        <span class="font-medium">Nomor Struk</span>
                        <span class="font-bold text-stone-800 font-mono"><?= $order['order_number'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Tanggal Transaksi</span>
                        <span class="font-semibold text-stone-800"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Nama Pelanggan</span>
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
                </div>
                
                <!-- Ticket Items -->
                <div class="space-y-3 mb-4 pb-4 border-b border-dashed border-stone-200">
                    <p class="text-xs font-bold text-stone-850 uppercase tracking-wider mb-2">Daftar Menu:</p>
                    <?php foreach ($orderItems as $item): ?>
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
                        <span class="text-sm font-extrabold text-stone-800">Total Pembayaran</span>
                        <span class="text-lg font-black text-emerald-600 font-outfit"><?= formatRupiah($order['total']) ?></span>
                    </div>
                </div>
                
                <!-- Ticket Footer -->
                <div class="text-center pt-3 border-t border-dashed border-stone-200 mt-4 text-xs text-stone-500 font-medium">
                    <p class="text-xs leading-relaxed">Terima kasih atas kunjungan Anda!</p>
                    <p class="text-xs text-stone-400 mt-0.5">Silakan tunjukkan struk digital ini ke kasir jika diperlukan.</p>
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

        <!-- REJECTED STATE -->
        <?php elseif ($isRejected): ?>
        <div class="bg-gradient-to-br from-red-900/40 to-orange-900/40 backdrop-blur-md rounded-3xl border border-red-500/30 p-8 mb-6 shadow-xl text-center">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-red-900/50 text-red-400 rounded-full mb-6 shadow-inner border border-red-500/30">
                <i class="fas fa-times text-5xl drop-shadow-[0_0_8px_rgba(239,68,68,0.5)]"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-red-400 font-outfit drop-shadow-sm">Pembayaran Ditolak</h2>
            <p class="text-slate-300 mt-3 font-medium">Silakan hubungi kasir untuk konfirmasi lebih lanjut.</p>
            
            <a 
                href="menu.php"
                class="mt-8 inline-flex items-center justify-center bg-slate-800 border border-slate-600 hover:bg-slate-700 text-slate-200 font-bold py-4 px-8 rounded-2xl transition-all shadow-md w-full"
            >
                <i class="fas fa-arrow-left mr-3"></i>Kembali ke Menu
            </a>
        </div>

        <!-- PENDING STATE -->
        <?php else: ?>
        <div class="bg-slate-800/60 backdrop-blur-md rounded-3xl shadow-xl border border-slate-700/50 p-6 mb-6">
            <h2 class="font-extrabold text-xl mb-4 font-outfit text-slate-100 flex items-center drop-shadow-sm">
                <i class="fas fa-receipt mr-3 text-emerald-400 bg-emerald-900/30 p-2 rounded-xl border border-emerald-500/20"></i>
                Detail Pesanan
            </h2>
            <div class="space-y-3 bg-slate-900/50 p-4 rounded-2xl border border-slate-700/80 shadow-inner">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-medium text-sm">No. Pesanan</span>
                    <span class="font-bold text-slate-200 bg-slate-800 px-3 py-1 rounded-lg border border-slate-600 shadow-sm">#<?= $order['order_number'] ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-medium text-sm">Subtotal</span>
                    <span class="font-bold text-slate-300"><?= formatRupiah($order['amount']) ?></span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-slate-700/50">
                    <span class="text-slate-200 font-bold">Total Transfer</span>
                    <span class="text-2xl font-black text-emerald-400 drop-shadow-[0_0_8px_rgba(52,211,153,0.4)] font-outfit"><?= formatRupiah($order['amount']) ?></span>
                </div>
                <div class="flex justify-between items-center pt-3 border-t border-slate-700/50">
                    <span class="text-slate-400 font-medium text-sm">Status</span>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-900/40 text-amber-400 border border-amber-500/30 uppercase tracking-wider">Menunggu Pembayaran</span>
                        <span class="animate-spin text-amber-500"><i class="fas fa-circle-notch"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-800/60 backdrop-blur-md rounded-3xl shadow-xl border border-slate-700/50 p-8 mb-6 text-center">
            <h2 class="font-extrabold text-xl mb-6 font-outfit text-slate-100 flex items-center justify-center drop-shadow-sm">
                <i class="fas fa-qrcode mr-3 text-blue-400 bg-blue-900/30 p-2 rounded-xl border border-blue-500/20"></i>
                Scan Kode QRIS
            </h2>
            <div class="mb-6 inline-block">
                <div class="bg-white p-4 rounded-2xl shadow-[0_0_30px_rgba(59,130,246,0.4)]">
                    <img src="<?= generateQRSVG($dynamicQrisString) ?>&t=<?= time() ?>" alt="QRIS Pembayaran Dinamis" class="w-64 h-64 object-contain">
                </div>
            </div>
            
            <div class="mb-6">
                <!-- Link untuk force download QRIS Dinamis ini -->
                <a href="download_qris.php?order=<?= urlencode($order['order_number']) ?>" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-[0_0_15px_rgba(37,99,235,0.4)]">
                    <i class="fas fa-download"></i> Simpan Kode QRIS
                </a>
            </div>

            <div class="mx-auto max-w-sm sm:max-w-md w-full">
                <div class="bg-gradient-to-br from-slate-800/80 to-slate-900/80 p-5 rounded-2xl border border-slate-700/60 shadow-lg text-left backdrop-blur-sm">
                    <h3 class="text-sm font-bold text-slate-200 mb-3 flex items-center gap-2 border-b border-slate-700/50 pb-2">
                        <i class="fas fa-info-circle text-blue-400"></i>
                        Cara Pembayaran
                    </h3>
                    
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-sm text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-slate-700/50 border border-slate-600 flex items-center justify-center flex-shrink-0 mt-0.5 text-slate-400 font-bold text-xs">1</div>
                            <p>Simpan gambar QRIS di atas, atau scan langsung pakai HP lain.</p>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-slate-700/50 border border-slate-600 flex items-center justify-center flex-shrink-0 mt-0.5 text-slate-400 font-bold text-xs">2</div>
                            <p>Buka aplikasi e-wallet (Gopay, OVO, Dana) atau M-Banking Anda.</p>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-slate-700/50 border border-slate-600 flex items-center justify-center flex-shrink-0 mt-0.5 text-slate-400 font-bold text-xs">3</div>
                            <p>Pilih upload gambar QRIS yang baru saja disimpan.</p>
                        </li>
                    </ul>
                    
                    <div class="mt-4 pt-3 border-t border-slate-700/50">
                        <div class="flex items-start gap-3 p-3 bg-emerald-900/20 border border-emerald-500/30 rounded-xl">
                            <i class="fas fa-check-circle text-emerald-400 text-lg mt-0.5"></i>
                            <p class="text-emerald-300 text-xs sm:text-sm font-medium leading-relaxed">
                                Nominal pembayaran <strong><?= formatRupiah($order['amount']) ?></strong> akan terisi <span class="font-bold text-emerald-200">otomatis</span> di aplikasi Anda. Anda tinggal konfirmasi dan bayar!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONFIRM PAYMENT BUTTON -->
        <div class="bg-white/5 backdrop-blur-xl rounded-[2rem] shadow-2xl border border-white/10 p-6 sm:p-8 mb-8 overflow-hidden relative">
            <!-- Decorative Elements -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 text-center mb-6">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 mb-4">
                    <i class="fas fa-check-double text-2xl"></i>
                </div>
                <h2 class="font-outfit text-xl font-bold text-slate-100">Sudah Melakukan Pembayaran?</h2>
                <p class="text-sm text-slate-400 mt-1">Tekan tombol di bawah setelah scan & bayar QRIS berhasil</p>
            </div>

            <div class="relative z-10">
                <button id="confirmPaymentBtn" onclick="confirmQrisPayment()" class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-white font-extrabold py-5 px-6 rounded-2xl shadow-lg shadow-emerald-900/30 hover:shadow-emerald-500/30 transition-all duration-300 text-lg flex items-center justify-center gap-3 group active:scale-[0.98] font-outfit">
                    <i class="fas fa-check-circle text-xl"></i>
                    Saya Sudah Bayar
                    <i class="fas fa-arrow-right text-sm group-hover:translate-x-1 transition-transform"></i>
                </button>
                <p class="text-center text-xs text-slate-500 mt-3">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Pesanan otomatis masuk ke dapur setelah konfirmasi
                </p>
            </div>
        </div>

        <div class="text-center pb-6">
            <a href="menu.php" class="inline-flex items-center text-slate-300 hover:text-white font-bold bg-slate-800 hover:bg-slate-700 px-6 py-3 rounded-full shadow-md border border-slate-600 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Menu
            </a>
        </div>
        <?php endif; ?>

    </main>

    <!-- Loading Overlay (hidden by default) -->
    <div id="paymentLoadingOverlay" class="hidden fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-900/95 backdrop-blur-sm">
        <div class="text-center px-8">
            <!-- Animated spinner -->
            <div class="relative w-28 h-28 mx-auto mb-8">
                <div class="absolute inset-0 rounded-full border-4 border-emerald-500/20"></div>
                <div class="absolute inset-0 rounded-full border-4 border-t-emerald-400 animate-spin"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <i class="fas fa-qrcode text-4xl text-emerald-400"></i>
                </div>
            </div>

            <h2 class="text-2xl font-extrabold text-white font-outfit mb-3">Memproses Pembayaran...</h2>
            <p class="text-slate-400 text-sm mb-6">Harap tunggu, kami sedang memverifikasi transaksi QRIS Anda</p>

            <!-- Progress bar -->
            <div class="w-64 mx-auto bg-slate-700/50 rounded-full h-1.5 overflow-hidden">
                <div id="paymentProgressBar" class="h-full bg-gradient-to-r from-emerald-400 to-teal-400 rounded-full transition-all duration-100" style="width:0%"></div>
            </div>
            <p id="paymentLoadingMsg" class="text-xs text-slate-500 mt-3">Menghubungkan ke server...</p>
        </div>
    </div>

    <script>
        function confirmQrisPayment() {
            const btn = document.getElementById('confirmPaymentBtn');
            const overlay = document.getElementById('paymentLoadingOverlay');
            const progressBar = document.getElementById('paymentProgressBar');
            const loadingMsg = document.getElementById('paymentLoadingMsg');

            // Disable button to prevent double submit
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');

            // Show overlay
            overlay.classList.remove('hidden');

            // Animate progress bar over 3 seconds
            const messages = [
                'Menghubungkan ke server...',
                'Memverifikasi transaksi QRIS...',
                'Mengkonfirmasi pesanan ke dapur...',
                'Hampir selesai...'
            ];
            let progress = 0;
            let msgIdx = 0;

            const progressInterval = setInterval(() => {
                progress = Math.min(progress + 1.5, 90); // cap at 90, jump to 100 on success
                progressBar.style.width = progress + '%';
                if (progress > 20 && msgIdx === 0) { msgIdx = 1; loadingMsg.textContent = messages[1]; }
                if (progress > 50 && msgIdx === 1) { msgIdx = 2; loadingMsg.textContent = messages[2]; }
                if (progress > 75 && msgIdx === 2) { msgIdx = 3; loadingMsg.textContent = messages[3]; }
            }, 50);

            // After 3 seconds, send confirm request
            setTimeout(() => {
                const formData = new FormData();
                formData.append('order', '<?= htmlspecialchars($orderNumber) ?>');

                fetch('confirm_qris.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    clearInterval(progressInterval);
                    if (data.success) {
                        progressBar.style.width = '100%';
                        loadingMsg.textContent = 'Pembayaran berhasil!';
                        // Redirect to order success page after short delay
                        setTimeout(() => {
                            window.location.href = 'order_success.php?order=<?= htmlspecialchars($orderNumber) ?>';
                        }, 600);
                    } else {
                        overlay.classList.add('hidden');
                        btn.disabled = false;
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                        alert('Gagal: ' + data.message + '\nSilahkan coba lagi.');
                    }
                })
                .catch(err => {
                    clearInterval(progressInterval);
                    overlay.classList.add('hidden');
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    alert('Terjadi kesalahan koneksi. Silahkan coba lagi.');
                });
            }, 3000);
        }

        <?php if (!$isVerified && !$isRejected): ?>
        // Real-time polling for payment verification status (check every 1 second)
        let statusCheckCount = 0;
        const maxStatusChecks = 300; // 5 minutes max polling
        
        function checkPaymentStatusRealTime() {
            statusCheckCount++;
            
            if (statusCheckCount > maxStatusChecks) {
                console.log('Status check timeout');
                return;
            }

            fetch('check_payment_status.php?order=<?= htmlspecialchars($orderNumber) ?>&t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    console.log('Payment status:', data);
                    
                    // Auto-reload if verified or rejected
                    if (data.verified === true) {
                        console.log('Payment verified! Reloading...');
                        setTimeout(() => location.reload(), 500);
                        return;
                    }
                    
                    // Continue polling if still pending
                    if (data.status === 'pending' || data.verified === false) {
                        setTimeout(checkPaymentStatusRealTime, 1000);
                    } else {
                        // Unknown status, reload page
                        setTimeout(() => location.reload(), 500);
                    }
                })
                .catch(e => {
                    console.log('Status check error:', e);
                    // Retry on error
                    if (statusCheckCount < maxStatusChecks) {
                        setTimeout(checkPaymentStatusRealTime, 2000);
                    }
                });
        }

        // Start polling immediately when page loads
        checkPaymentStatusRealTime();
        <?php endif; ?>
    </script>
</body>
</html>

