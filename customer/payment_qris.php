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

// Generate the dynamic QRIS string based on the total order amount
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
                    <p class="text-[11px] leading-relaxed">Terima kasih atas kunjungan Anda!</p>
                    <p class="text-[10px] text-stone-400 mt-0.5">Silakan tunjukkan struk digital ini ke kasir jika diperlukan.</p>
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
                    <span class="text-slate-400 font-medium text-sm">Total Tagihan</span>
                    <span class="text-xl font-extrabold text-emerald-400 drop-shadow-[0_0_5px_rgba(52,211,153,0.3)]"><?= formatRupiah($order['amount']) ?></span>
                </div>
                <div class="flex justify-between items-center pt-3 border-t border-slate-700/50">
                    <span class="text-slate-400 font-medium text-sm">Status</span>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-900/40 text-amber-400 border border-amber-500/30 uppercase tracking-wider">Menunggu Pembayaran</span>
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
            <div class="mb-6 p-5 bg-gradient-to-br from-blue-900/40 to-indigo-900/40 rounded-3xl border border-blue-500/30 inline-block shadow-inner relative group">
                <div class="inline-block p-4 bg-white rounded-2xl shadow-[0_0_20px_rgba(59,130,246,0.3)] border border-blue-200">
                    <img src="<?= generateQRSVG($dynamicQrisString) ?>" alt="QRIS Dinamis" class="w-64 h-64 object-contain">
                </div>
            </div>
            
            <div class="mb-6">
                <!-- Tambahkan link untuk download QRIS Dinamis ini -->
                <a href="<?= generateQRSVG($dynamicQrisString) ?>" download="QRIS_Tagihan_<?= $order['order_number'] ?>.png" target="_blank" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-[0_0_15px_rgba(37,99,235,0.4)]">
                    <i class="fas fa-download"></i> Simpan Kode Tagihan
                </a>
            </div>

            <p class="text-slate-300 text-sm font-medium flex items-center justify-center gap-2 bg-slate-900/50 py-3 rounded-xl border border-slate-700 mx-4">
                <i class="fas fa-mobile-alt text-lg text-slate-400"></i>
                Simpan gambar, lalu buka aplikasi e-wallet Anda (Gopay, Dana, dll) dan pilih upload gambar.
            </p>
        </div>

        <?php if (!$order['proof_of_payment']): ?>
        <div class="bg-slate-800/60 backdrop-blur-md rounded-3xl shadow-xl border border-slate-700/50 p-6 mb-6">
            <h2 class="font-extrabold text-xl mb-5 font-outfit text-slate-100 flex items-center drop-shadow-sm">
                <i class="fas fa-cloud-upload-alt mr-3 text-emerald-400 bg-emerald-900/30 p-2 rounded-xl border border-emerald-500/20"></i>
                Upload Bukti
            </h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <label class="block relative border-2 border-dashed border-slate-600 rounded-3xl p-8 text-center bg-slate-900/50 hover:bg-emerald-900/20 hover:border-emerald-500/50 transition-all duration-300 cursor-pointer group shadow-inner">
                    <div class="w-16 h-16 bg-slate-800 border border-slate-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm group-hover:scale-110 transition-transform duration-300 group-hover:text-emerald-400 group-hover:border-emerald-500/40 text-slate-400">
                        <i class="fas fa-image text-2xl"></i>
                    </div>
                    <p class="text-slate-200 font-bold mb-1 group-hover:text-emerald-400 transition-colors">Pilih screenshot bukti transfer</p>
                    <p class="text-xs text-slate-400 font-medium">Format JPG atau PNG (Max 5MB)</p>
                    <input type="file" name="payment_proof" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
                </label>
                <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-extrabold py-4 px-6 rounded-2xl shadow-[0_8px_20px_-6px_rgba(16,185,129,0.5)] hover:shadow-[0_12px_25px_-6px_rgba(16,185,129,0.6)] hover:-translate-y-0.5 transition-all duration-300 text-lg font-outfit flex items-center justify-center gap-3">
                    <i class="fas fa-paper-plane"></i> Kirim Bukti
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-gradient-to-br from-amber-900/30 to-orange-900/30 backdrop-blur-md border border-amber-500/30 rounded-3xl p-8 mb-6 shadow-xl text-center">
            <div class="w-16 h-16 bg-amber-900/50 border border-amber-500/30 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                <i class="fas fa-hourglass-half text-amber-400 text-2xl animate-pulse"></i>
            </div>
            <h2 class="font-extrabold text-2xl mb-4 text-amber-400 font-outfit drop-shadow-sm">Bukti Sedang Diproses</h2>
            
            <div class="bg-slate-900/60 backdrop-blur-sm rounded-xl p-5 border border-amber-500/20 max-w-sm mx-auto">
                <p class="text-amber-400 font-medium flex items-center justify-center gap-3">
                    <span class="animate-spin"><i class="fas fa-circle-notch text-amber-500 text-xl"></i></span>
                    Mohon tunggu, admin sedang mengecek pembayaran Anda...
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center pb-6">
            <a href="menu.php" class="inline-flex items-center text-slate-300 hover:text-white font-bold bg-slate-800 hover:bg-slate-700 px-6 py-3 rounded-full shadow-md border border-slate-600 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Menu
            </a>
        </div>
        <?php endif; ?>
    </main>

    <script>
        // Handle form submission - simple version
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const fileInput = this.querySelector('input[type="file"]');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Silahkan pilih foto terlebih dahulu');
                e.preventDefault();
                return false;
            }
            
            const file = fileInput.files[0];
            
            // Log file info
            console.log('File submit:', {
                name: file.name,
                size: file.size,
                type: file.type
            });
            
            // Check file size
            if (file.size > 5 * 1024 * 1024) {
                alert('File terlalu besar! Max 5MB. Ukuran file: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
                e.preventDefault();
                return false;
            }
            
            // Form akan submit normally
        });

        <?php if (!$isVerified && !$isRejected): ?>
        // Polling untuk check status verification (every 1 second)
        let checkCount = 0;
        function checkPaymentStatus() {
            checkCount++;
            
            fetch('check_payment_status.php?order=<?= $orderNumber ?>&t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'pending') {
                        location.reload();
                    }
                })
                .catch(e => console.log('Status check error:', e));
        }
        
        // Start polling
        checkPaymentStatus();
        setInterval(checkPaymentStatus, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
