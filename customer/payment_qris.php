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
    // Send JSON response
    header('Content-Type: application/json');
    
    // Protect against resubmissions resetting verified status
    $stmt = $db->prepare("SELECT verification_status FROM payments WHERE order_id = ? AND payment_method = 'qris'");
    $stmt->execute([$order['id']]);
    $currentPaymentStatus = $stmt->fetch();
    
    if ($currentPaymentStatus && $currentPaymentStatus['verification_status'] === 'verified') {
        http_response_code(200);
        echo json_encode([
            'success' => true, 
            'message' => 'Pembayaran sudah diverifikasi',
            'verified' => true
        ]);
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
                
                // Log success with full path info
                error_log("[PAYMENT_UPLOAD] Order: " . $orderNumber . " | Payment ID: " . $order['payment_id'] . " | File: " . $uploadResult['path'] . " | Size: " . $_FILES['payment_proof']['size']);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Bukti pembayaran berhasil dikirim! Menunggu verifikasi dari admin.',
                    'path' => $uploadResult['path'],
                    'order_id' => $order['id']
                ]);
                exit;
                
            } catch (Exception $e) {
                error_log("[PAYMENT_UPLOAD_ERROR] Order: " . $orderNumber . " | Error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Gagal menyimpan data pembayaran: ' . $e->getMessage()
                ]);
                exit;
            }
        } else {
            error_log("[PAYMENT_UPLOAD_FAILED] Order: " . $orderNumber . " | Message: " . $uploadResult['message']);
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => $uploadResult['message']
            ]);
            exit;
        }
    } else {
        // PHP upload error codes
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (form limit)',
            UPLOAD_ERR_PARTIAL => 'Upload tidak lengkap',
            UPLOAD_ERR_NO_FILE => 'File tidak dipilih',
            UPLOAD_ERR_NO_TMP_DIR => 'Direktori temp tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file',
            UPLOAD_ERR_EXTENSION => 'Extension file tidak diizinkan'
        ];
        
        $errorMsg = $uploadErrors[$_FILES['payment_proof']['error']] ?? 'Unknown error';
        error_log("[PAYMENT_UPLOAD_ERROR] Order: " . $orderNumber . " | Upload Error: " . $_FILES['payment_proof']['error'] . " (" . $errorMsg . ")");
        
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal upload file: ' . $errorMsg
        ]);
        exit;
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
                    <img src="<?= APP_URL ?>/assets/img/qrisupdate.jpg" alt="QRIS <?= APP_NAME ?>" class="w-64 h-64 object-contain">
                </div>
            </div>
            
            <div class="mb-6">
                <!-- Link untuk force download QRIS -->
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

            <div class="relative z-10">
                <!-- UPLOAD PROOF OF PAYMENT - Only if no proof yet -->
                <?php if (empty($order['proof_of_payment'])): ?>
                <div class="mb-6">
                    <h3 class="font-outfit text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                        <i class="fas fa-image text-blue-400"></i> Upload Bukti Pembayaran
                        <span class="text-xs bg-red-500/20 text-red-300 px-2.5 py-0.5 rounded-full font-bold uppercase">WAJIB</span>
                    </h3>
                    
                    <form id="uploadForm" method="POST" enctype="multipart/form-data" class="relative" style="touch-action: auto;">
                        <input 
                            type="file" 
                            id="paymentProof" 
                            name="payment_proof" 
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                            required
                            class="hidden"
                            onchange="handleProofUpload()"
                            style="pointer-events: none;"
                        >
                        
                        <label for="paymentProof" class="block p-6 border-2 border-dashed border-slate-600 rounded-2xl hover:border-emerald-500 cursor-pointer transition-colors bg-slate-900/50 text-center group active:bg-emerald-900/30 active:border-emerald-500" style="touch-action: auto; user-select: none; -webkit-user-select: none; pointer-events: auto;">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-cloud-upload-alt text-4xl text-slate-500 group-hover:text-emerald-400 group-active:text-emerald-400 transition-colors mb-3"></i>
                                <p class="text-slate-300 font-bold mb-1">Klik untuk upload</p>
                                <p class="text-xs text-slate-500 hidden sm:block">atau drag & drop</p>
                                <p class="text-xs text-slate-500">JPG, PNG (Max 5MB)</p>
                                <p class="text-xs text-emerald-400 mt-2 font-semibold">💡 Bisa ambil dari galeri atau kamera</p>
                            </div>
                        </label>
                        
                        <div id="proofPreview" class="hidden mt-3 p-3 bg-slate-800/50 rounded-xl border border-emerald-500/30">
                            <div class="flex items-center gap-2 text-emerald-400 text-sm font-bold">
                                <i class="fas fa-check-circle"></i>
                                <span id="proofFileName">File dipilih</span>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- CONFIRM BUTTON (DISABLED UNTIL PROOF UPLOADED) -->
                <div class="text-center">
                    <button type="submit" form="uploadForm" id="confirmPaymentBtn" disabled class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 disabled:from-slate-600 disabled:to-slate-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none text-white font-extrabold py-5 px-6 rounded-2xl shadow-lg shadow-emerald-900/30 hover:shadow-emerald-500/30 transition-all duration-300 text-lg flex items-center justify-center gap-3 group active:scale-[0.98] font-outfit">
                        <i class="fas fa-check-circle text-xl"></i>
                        Kirim Bukti & Lanjutkan
                        <i class="fas fa-arrow-right text-sm group-hover:translate-x-1 transition-transform"></i>
                    </button>
                    <p class="text-center text-xs text-slate-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Admin akan memverifikasi pembayaran Anda dalam waktu singkat
                    </p>
                </div>
                
                <?php else: ?>
                <!-- WAITING FOR VERIFICATION STATE - Already uploaded -->
                <div class="bg-gradient-to-br from-amber-900/30 to-orange-900/30 backdrop-blur-md rounded-2xl border border-amber-500/30 p-6 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-900/50 text-amber-400 rounded-full mb-4 animate-pulse">
                        <i class="fas fa-hourglass-half text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-amber-300 font-outfit mb-2">Menunggu Verifikasi Admin</h3>
                    <p class="text-slate-300 text-sm mb-4">Bukti pembayaran Anda sudah diterima. Admin akan memverifikasinya dalam waktu singkat.</p>
                    
                    <div class="bg-slate-800/50 rounded-xl p-4 mb-4 text-left">
                        <p class="text-xs font-bold text-slate-400 uppercase mb-2">Status:</p>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1.5 bg-amber-900/50 text-amber-300 rounded-lg text-xs font-bold uppercase tracking-wide flex items-center gap-1.5">
                                <i class="fas fa-clock animate-spin"></i> Menunggu Verifikasi
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Jangan menutup halaman ini. Halaman akan otomatis refresh ketika ada keputusan dari admin.</p>
                    </div>
                    
                    <button onclick="manualCheckPaymentStatus()" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md mb-3">
                        <i class="fas fa-refresh mr-2"></i> Cek Status Sekarang
                    </button>
                    
                    <p class="text-xs text-slate-400 flex items-center justify-center gap-1">
                        <i class="fas fa-lock mr-1"></i>
                        Upload sudah dikunci. Tunggu keputusan admin.
                    </p>
                </div>
                <?php endif; ?>
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
        // Detect mobile device
        function isMobileDevice() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        function handleProofUpload() {
            const fileInput = document.getElementById('paymentProof');
            const preview = document.getElementById('proofPreview');
            const fileName = document.getElementById('proofFileName');
            const confirmBtn = document.getElementById('confirmPaymentBtn');
            
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                
                // Validate file size (5MB max)
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if (file.size > maxSize) {
                    alert('File terlalu besar. Maksimal 5MB. File Anda: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
                    fileInput.value = '';
                    preview.classList.add('hidden');
                    confirmBtn.disabled = true;
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan: JPG, PNG, GIF, atau WebP');
                    fileInput.value = '';
                    preview.classList.add('hidden');
                    confirmBtn.disabled = true;
                    return;
                }
                
                fileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + 'KB)';
                preview.classList.remove('hidden');
                confirmBtn.disabled = false;
                console.log('✅ File selected:', file.name, 'Size:', (file.size / 1024).toFixed(1) + 'KB', 'Type:', file.type);
            } else {
                preview.classList.add('hidden');
                confirmBtn.disabled = true;
            }
        }

        // Make label clickable on mobile for better UX
        document.addEventListener('DOMContentLoaded', function() {
            const label = document.querySelector('label[for="paymentProof"]');
            const fileInput = document.getElementById('paymentProof');
            
            if (label && fileInput) {
                // Ensure label is clickable on all devices
                label.style.userSelect = 'none';
                label.style.webkitUserSelect = 'none';
                label.style.pointerEvents = 'auto'; // Ensure pointer events enabled
                
                // Explicit click handler for all devices (especially mobile)
                label.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    fileInput.click();
                });
                
                // Add touch feedback
                label.addEventListener('touchstart', function() {
                    this.style.opacity = '0.8';
                });
                label.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                });
            }
            
            // Enable button if proof was already uploaded
            const proofUploadedBefore = <?= !empty($order['proof_of_payment']) ? 'true' : 'false' ?>;
            if (proofUploadedBefore) {
                document.getElementById('confirmPaymentBtn').disabled = false;
            }
        });

        // Handle form submit dengan loading animation
        let isSubmitting = false;
        document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Prevent double submission
            if (isSubmitting) {
                console.warn('Already submitting, please wait...');
                return;
            }
            isSubmitting = true;
            
            const fileInput = document.getElementById('paymentProof');
            const btn = document.getElementById('confirmPaymentBtn');
            const overlay = document.getElementById('paymentLoadingOverlay');
            const progressBar = document.getElementById('paymentProgressBar');
            const loadingMsg = document.getElementById('paymentLoadingMsg');
            
            if (!fileInput.files.length) {
                alert('Pilih file terlebih dahulu');
                isSubmitting = false;
                return;
            }
            
            // Show loading overlay
            overlay.classList.remove('hidden');
            btn.disabled = true;
            
            // Animate progress bar
            const messages = [
                'Mengupload bukti pembayaran...',
                'Memverifikasi file...',
                'Mengirim ke server...',
                'Hampir selesai...'
            ];
            let progress = 0;
            let msgIdx = 0;
            
            const progressInterval = setInterval(() => {
                progress = Math.min(progress + 1.5, 90);
                progressBar.style.width = progress + '%';
                if (progress > 20 && msgIdx === 0) { msgIdx = 1; loadingMsg.textContent = messages[1]; }
                if (progress > 50 && msgIdx === 1) { msgIdx = 2; loadingMsg.textContent = messages[2]; }
                if (progress > 75 && msgIdx === 2) { msgIdx = 3; loadingMsg.textContent = messages[3]; }
            }, 50);
            
            // Submit form via FormData
            const formData = new FormData(this);
            
            fetch('payment_qris.php?order=<?= htmlspecialchars($orderNumber) ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Upload response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Upload response data:', data);
                clearInterval(progressInterval);
                
                if (data.success) {
                    progressBar.style.width = '100%';
                    loadingMsg.textContent = 'Bukti pembayaran berhasil dikirim!';
                    
                    setTimeout(() => {
                        // Hide upload form and show uploaded file info
                        const uploadForm = document.getElementById('uploadForm');
                        const fileInput = document.getElementById('paymentProof');
                        
                        if (uploadForm) {
                            uploadForm.innerHTML = `
                                <div class="bg-emerald-900/30 border border-emerald-500/50 rounded-2xl p-6 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full mb-3">
                                        <i class="fas fa-check-circle text-3xl"></i>
                                    </div>
                                    <h4 class="text-emerald-300 font-bold mb-1">Gambar Berhasil Diupload</h4>
                                    <p class="text-emerald-200 text-sm mb-3">${fileInput.files[0].name}</p>
                                    <p class="text-xs text-slate-400">Menunggu verifikasi dari admin. Jangan tutup halaman ini.</p>
                                </div>
                            `;
                        }
                        
                        location.reload();
                    }, 1500);
                } else {
                    clearInterval(progressInterval);
                    overlay.classList.add('hidden');
                    btn.disabled = false;
                    isSubmitting = false;
                    alert('Gagal mengupload: ' + (data.message || 'Unknown error'));
                    console.error('Upload failed:', data);
                }
            })
            .catch(error => {
                clearInterval(progressInterval);
                overlay.classList.add('hidden');
                btn.disabled = false;
                isSubmitting = false;
                alert('Error: ' + error.message);
                console.error('Upload error:', error);
            });
        });

        // Support drag & drop for file upload (only on desktop)
        const uploadLabel = document.querySelector('label[for="paymentProof"]');
        if (uploadLabel && !isMobileDevice()) {
            uploadLabel.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadLabel.classList.add('border-emerald-500', 'bg-emerald-900/20');
            });
            uploadLabel.addEventListener('dragleave', () => {
                uploadLabel.classList.remove('border-emerald-500', 'bg-emerald-900/20');
            });
            uploadLabel.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadLabel.classList.remove('border-emerald-500', 'bg-emerald-900/20');
                
                const fileInput = document.getElementById('paymentProof');
                fileInput.files = e.dataTransfer.files;
                handleProofUpload();
            });
        }
        
        <?php if (!$isVerified && !$isRejected): ?>
        // Real-time polling for payment verification status
        // Start at 2 seconds, gradually increase to avoid lag
        let statusCheckCount = 0;
        let pollingInterval = 2000; // Start at 2 seconds
        let pollingTimeout = null;
        
        function checkPaymentStatusRealTime() {
            if (statusCheckCount >= 150) { // 150 checks max (5 mins at adaptive rate)
                console.log('Status check timeout - stopped polling');
                return;
            }
            
            statusCheckCount++;
            
            fetch('check_payment_status.php?order=<?= htmlspecialchars($orderNumber) ?>&t=' + Date.now(), {
                signal: AbortSignal.timeout(5000) // 5 second timeout per request
            })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(data => {
                    console.log('✓ Status check #' + statusCheckCount + ':', data.verification_status);
                    
                    // Auto-reload if verified
                    if (data.verified === true || data.verification_status === 'verified') {
                        console.log('✓ Payment verified! Reloading...');
                        clearTimeout(pollingTimeout);
                        setTimeout(() => location.reload(), 300);
                        return;
                    }
                    
                    // Auto-reload if rejected
                    if (data.verification_status === 'rejected' || data.rejected === true) {
                        console.log('✗ Payment rejected! Reloading...');
                        clearTimeout(pollingTimeout);
                        setTimeout(() => location.reload(), 300);
                        return;
                    }
                    
                    // Continue polling if still pending
                    if (data.status === 'pending' || data.verification_status === 'pending') {
                        // Gradually increase polling interval (up to 5 seconds max)
                        pollingInterval = Math.min(pollingInterval + 500, 5000);
                        console.log('→ Still pending, next check in ' + (pollingInterval / 1000) + 's');
                        pollingTimeout = setTimeout(checkPaymentStatusRealTime, pollingInterval);
                    } else {
                        // Unknown status, reload
                        console.log('? Unknown status:', data.status, ', reloading...');
                        clearTimeout(pollingTimeout);
                        setTimeout(() => location.reload(), 300);
                    }
                })
                .catch(e => {
                    console.warn('✗ Status check error:', e.message);
                    // Retry on error with longer interval
                    if (statusCheckCount < 150) {
                        pollingInterval = Math.min(pollingInterval + 1000, 5000);
                        console.log('→ Error, retry in ' + (pollingInterval / 1000) + 's');
                        pollingTimeout = setTimeout(checkPaymentStatusRealTime, pollingInterval);
                    }
                });
        }

        // Start polling when page loads
        console.log('Starting payment status polling...');
        pollingTimeout = setTimeout(checkPaymentStatusRealTime, 2000);
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            clearTimeout(pollingTimeout);
            console.log('Polling stopped - page unloading');
        });
        
        // Manual check function for customer
        function manualCheckPaymentStatus() {
            const btn = event.target.closest('button');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i> Mengecek...';
            }
            
            fetch('check_payment_status.php?order=<?= htmlspecialchars($orderNumber) ?>&t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    if (data.verification_status === 'verified') {
                        location.reload();
                    } else if (data.verification_status === 'rejected') {
                        location.reload();
                    } else {
                        alert('Status masih menunggu verifikasi. Coba lagi dalam beberapa saat.');
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-refresh mr-2"></i> Cek Status Sekarang';
                        }
                    }
                })
                .catch(e => {
                    alert('Error checking status: ' + e.message);
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-refresh mr-2"></i> Cek Status Sekarang';
                    }
                });
        }
        <?php endif; ?>
    </script>
</body>
</html>

