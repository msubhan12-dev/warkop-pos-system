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

function generateQRSVG($text, $size = 300) {
    $encodedText = urlencode($text);
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . $encodedText;
}
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
<body class="bg-stone-50 text-stone-900">
    <!-- Header -->
    <header class="bg-stone-900 text-white shadow-lg sticky top-0 z-30">
        <div class="px-4 py-4 flex items-center">
            <a href="menu.php" class="mr-4">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-xl font-bold font-outfit">Pembayaran QRIS</h1>
        </div>
    </header>

    <main class="p-4 max-w-2xl mx-auto pb-32 sm:pb-24">
        <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i><?= $success ?>
        </div>
        <?php endif; ?>

        <!-- VERIFIED STATE -->
        <?php if ($isVerified): ?>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl border-2 border-green-500 p-6 mb-6">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-500 text-white rounded-full mb-4">
                    <i class="fas fa-check-circle text-4xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-green-800">Pembayaran Berhasil!</h2>
                <p class="text-green-700 mt-2">Pesanan Anda telah diverifikasi dan diterima oleh dapur</p>
            </div>

            <!-- Status Info -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-white rounded-lg p-3 text-center">
                    <p class="text-sm text-gray-600">No. Pesanan</p>
                    <p class="font-bold text-lg text-slate-700"><?= $order['order_number'] ?></p>
                </div>
                <div class="bg-white rounded-lg p-3 text-center">
                    <p class="text-sm text-gray-600">Total</p>
                    <p class="font-bold text-lg text-green-600"><?= formatRupiah($order['amount']) ?></p>
                </div>
            </div>

            <!-- Digital Receipt Card -->
            <div id="receipt" class="bg-white rounded-2xl border border-stone-200 p-6 mb-6 shadow-sm relative overflow-hidden">
                <!-- Watermark Stamp -->
                <div class="absolute -right-4 -top-4 opacity-10 rotate-12 pointer-events-none">
                    <i class="fas fa-check-circle text-8xl text-emerald-600"></i>
                </div>
                
                <!-- Ticket Header -->
                <div class="text-center pb-4 mb-4 border-b border-dashed border-stone-200">
                    <div class="inline-block bg-white rounded-full p-0.5 shadow-sm mb-2 overflow-hidden w-12 h-12">
                        <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" alt="Logo" class="w-full h-full object-cover rounded-full">
                    </div>
                    <h3 class="text-xl font-extrabold text-stone-850 font-outfit tracking-tight"><?= APP_NAME ?></h3>
                    <p class="text-xs text-stone-500 font-medium">Bukti Transaksi Resmi</p>
                    
                    <!-- Paid Badge -->
                    <span class="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wide">
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
                        <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">Meja <?= $order['table_number'] ?></span>
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
            <div class="grid grid-cols-2 gap-3">
                <button 
                    onclick="window.print()"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition"
                >
                    <i class="fas fa-print mr-2"></i>Print Resi
                </button>
                <a 
                    href="menu.php"
                    class="bg-slate-700 hover:bg-slate-800 text-white font-semibold py-3 px-4 rounded-lg transition text-center"
                >
                    <i class="fas fa-shopping-cart mr-2"></i>Pesan Lagi
                </a>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 rounded-lg p-4 mt-6 border-l-4 border-blue-500">
                <p class="text-sm text-blue-900">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Pesanan Anda sedang dipersiapkan di dapur.</strong>
                </p>
            </div>
        </div>

        <!-- REJECTED STATE -->
        <?php elseif ($isRejected): ?>
        <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl border-2 border-red-500 p-6 mb-6">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-red-500 text-white rounded-full mb-4">
                    <i class="fas fa-times-circle text-4xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-red-800">Pembayaran Ditolak</h2>
                <p class="text-red-700 mt-2">Hubungi kasir untuk informasi lebih lanjut</p>
            </div>
            <a 
                href="menu.php"
                class="block text-center bg-slate-700 hover:bg-slate-800 text-white font-semibold py-3 px-4 rounded-lg transition"
            >
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Menu
            </a>
        </div>

        <!-- PENDING STATE -->
        <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
            <h2 class="font-bold text-lg mb-3"><i class="fas fa-receipt mr-2 text-slate-700"></i>Detail Pesanan</h2>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">No. Pesanan</span>
                    <span class="font-bold text-slate-700">#<?= $order['order_number'] ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total</span>
                    <span class="text-2xl font-bold text-green-600"><?= formatRupiah($order['amount']) ?></span>
                </div>
                <div class="flex justify-between pt-2 border-t">
                    <span class="text-gray-600">Status</span>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">Menunggu Verifikasi</span>
                        <span class="animate-spin text-yellow-600"><i class="fas fa-spinner"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 mb-4 text-center">
            <h2 class="font-bold text-lg mb-4"><i class="fas fa-qrcode mr-2 text-blue-600"></i>Scan QRIS</h2>
            <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                <div class="inline-block p-4 bg-white rounded-lg shadow-md">
                    <img src="<?= generateQRSVG('warkop:' . $order['order_number'] . ':' . $order['amount']) ?>" alt="QRIS" class="w-64 h-64">
                </div>
            </div>
            <p class="text-gray-600 text-sm mb-4"><i class="fas fa-info-circle mr-2"></i>Gunakan e-wallet untuk scan</p>
        </div>

        <?php if (!$order['proof_of_payment']): ?>
        <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
            <h2 class="font-bold text-lg mb-4"><i class="fas fa-image mr-2 text-slate-700"></i>Upload Bukti</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-gray-700 font-semibold mb-1">Klik untuk pilih file</p>
                    <p class="text-xs text-gray-500 mb-3">JPG atau PNG, max 5MB</p>
                    <input type="file" name="payment_proof" accept="image/*" class="w-full" required>
                </div>
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-xl text-lg">
                    <i class="fas fa-check-circle mr-2"></i>Kirim Bukti
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border-2 border-yellow-500 rounded-xl p-4 mb-4">
            <h2 class="font-bold text-lg mb-4 text-yellow-800"><i class="fas fa-hourglass-half mr-2"></i>Bukti Sudah Dikirim</h2>
            <img src="<?= UPLOADS_URL . '/' . $order['proof_of_payment'] ?>" alt="Bukti" class="w-full max-w-xs mx-auto rounded-lg shadow-sm mb-4">
            <div class="bg-white rounded-lg p-4">
                <p class="text-gray-700 flex items-center"><span class="animate-spin text-yellow-600 mr-2"><i class="fas fa-spinner"></i></span>Menunggu verifikasi admin...</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center">
            <a href="menu.php" class="text-slate-600 hover:text-slate-700 font-semibold">
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
