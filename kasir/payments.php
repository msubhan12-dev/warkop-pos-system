<?php
/**
 * Kasir Payment Verification & Processing
 * Handle cash, QRIS, dan bank transfer payments
 */
require_once '../config/config.php';
requireRole(['kasir', 'owner']);

$user = getCurrentUser();
$db = getDB();

// Get pending payment orders (QRIS dan bank transfer yang butuh verifikasi)
$stmt = $db->query("
    SELECT 
        o.id,
        o.order_number,
        o.customer_name,
        o.total,
        o.status,
        o.created_at,
        t.table_number,
        p.id as payment_id,
        p.payment_method,
        p.amount,
        p.verification_status,
        p.proof_of_payment
    FROM orders o
    JOIN payments p ON o.id = p.order_id
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE p.payment_method IN ('qris', 'transfer')
        AND o.status = 'pending'
        AND p.verification_status = 'pending'
        AND p.proof_of_payment IS NOT NULL
    ORDER BY o.created_at DESC
    LIMIT 50
");
$pendingPayments = $stmt->fetchAll();

// Get completed orders (untuk cash payment processing)
$stmt = $db->query("
    SELECT 
        o.id,
        o.order_number,
        o.customer_name,
        o.total,
        o.status,
        o.created_at,
        t.table_number,
        p.id as payment_id,
        p.payment_method,
        p.amount,
        p.status as payment_status
    FROM orders o
    JOIN payments p ON o.id = p.order_id
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE p.payment_method = 'cash'
        AND p.status = 'pending'
        AND o.status != 'cancelled'
    ORDER BY o.created_at DESC
    LIMIT 50
");
$completedOrders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Verifikasi Pembayaran - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #receipt, #receipt * {
                visibility: visible;
            }
            #receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
            }
        }
    </style>
</head>
<body class="bg-stone-50 text-stone-900">
    <!-- Header -->
    <header class="bg-stone-900 text-white shadow-lg sticky top-0 z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center space-x-3">
                <i class="fas fa-credit-card text-2xl text-emerald-400"></i>
                <div>
                    <h1 class="text-lg font-extrabold font-outfit text-white">Verifikasi Pembayaran</h1>
                    <p class="text-xs text-stone-300 font-medium"><?= $user['full_name'] ?></p>
                </div>
            </div>
            <a href="index.php" class="text-white hover:text-emerald-400 bg-stone-800 p-2.5 rounded-full flex items-center justify-center transition" title="Kembali ke POS">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
        </div>
    </header>

    <main class="p-4 max-w-6xl mx-auto pb-32 sm:pb-24">
        <!-- Tabs -->
        <div class="mb-6 flex gap-2 border-b border-stone-250">
            <button 
                class="tab-btn active px-4 py-3 font-extrabold text-emerald-600 border-b-2 border-emerald-600 transition flex items-center gap-2"
                onclick="switchTab('qris')"
            >
                <i class="fas fa-qrcode"></i>QRIS & Transfer (<?= count($pendingPayments) ?>)
            </button>
            <button 
                class="tab-btn px-4 py-3 font-bold text-stone-500 border-b-2 border-transparent hover:text-stone-750 transition flex items-center gap-2"
                onclick="switchTab('cash')"
            >
                <i class="fas fa-money-bill-wave"></i>Tunai / Open Bill (<?= count($completedOrders) ?>)
            </button>
        </div>

        <!-- QRIS & Transfer Tab -->
        <div id="qrisTab" class="tab-content space-y-4">
            <?php if (empty($pendingPayments)): ?>
            <div class="bg-white rounded-2xl p-12 text-center border border-stone-200">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full mb-3">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
                <p class="text-stone-600 font-bold font-outfit text-base">Semua Bersih!</p>
                <p class="text-stone-400 text-xs mt-1">Tidak ada pembayaran QRIS yang menunggu verifikasi saat ini.</p>
            </div>
            <?php else: ?>
                <?php foreach ($pendingPayments as $payment): ?>
                <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-5 hover:border-emerald-250 hover:shadow-md transition duration-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Left: Order Info -->
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Nomor Pesanan</p>
                                <p class="font-extrabold text-lg text-stone-900 font-outfit mt-0.5"><?= $payment['order_number'] ?></p>
                            </div>
                            
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Nama Pelanggan</p>
                                <p class="font-bold text-stone-800 text-sm mt-0.5"><?= $payment['customer_name'] ?></p>
                            </div>
                            
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Meja / Tipe</p>
                                <p class="text-sm font-semibold text-stone-850 mt-0.5">
                                    <?php if ($payment['table_number']): ?>
                                        <span class="bg-stone-100 px-2.5 py-1 rounded-lg text-xs font-extrabold text-stone-700 mr-1"><i class="fas fa-chair mr-1"></i>Meja <?= $payment['table_number'] ?></span>
                                    <?php else: ?>
                                        <span class="bg-amber-50 px-2.5 py-1 rounded-lg text-xs font-extrabold text-amber-850 mr-1"><i class="fas fa-shopping-bag mr-1"></i>Take Away</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Waktu Pesan</p>
                                <p class="text-xs text-stone-500 mt-0.5"><?= timeAgo($payment['created_at']) ?></p>
                            </div>
                        </div>

                        <!-- Middle: Payment Info -->
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Metode Pembayaran</p>
                                <div class="flex items-center mt-1">
                                    <?php if ($payment['payment_method'] === 'qris'): ?>
                                        <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg text-xs font-extrabold flex items-center gap-1.5"><i class="fas fa-qrcode"></i>QRIS</span>
                                    <?php else: ?>
                                        <span class="bg-purple-50 text-purple-700 px-2.5 py-1 rounded-lg text-xs font-extrabold flex items-center gap-1.5"><i class="fas fa-bank"></i>Transfer Bank</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Total Pembayaran</p>
                                <p class="font-extrabold text-2xl text-emerald-600 font-outfit mt-0.5"><?= formatRupiah($payment['amount']) ?></p>
                            </div>
                            
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Status</p>
                                <div class="mt-1">
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-800 text-xs font-bold rounded-lg uppercase tracking-wider flex-inline items-center gap-1">
                                        <i class="fas fa-clock text-xs"></i> Menunggu Verifikasi
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Proof & Actions -->
                        <div>
                            <p class="text-xs font-bold text-stone-500 mb-2 uppercase tracking-wide">Bukti Pembayaran (Klik perbesar)</p>
                            <div class="mb-4">
                                <img 
                                    src="<?= UPLOADS_URL . '/' . $payment['proof_of_payment'] ?>" 
                                    alt="Bukti Pembayaran"
                                    class="w-full h-36 object-cover rounded-xl border border-stone-200 cursor-zoom-in hover:opacity-90 transition duration-200"
                                    onclick="showImageModal('<?= UPLOADS_URL . '/' . $payment['proof_of_payment'] ?>')"
                                >
                            </div>
                            
                            <div class="flex gap-2">
                                <button 
                                    onclick="approvePayment(<?= $payment['payment_id'] ?>, '<?= $payment['order_number'] ?>')"
                                    class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-3 rounded-xl transition duration-200 text-sm flex items-center justify-center gap-1.5 shadow-sm"
                                >
                                    <i class="fas fa-check-circle"></i>Terima
                                </button>
                                <button 
                                    onclick="rejectPayment(<?= $payment['payment_id'] ?>, '<?= $payment['order_number'] ?>')"
                                    class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold py-2.5 px-3 rounded-xl transition duration-200 text-sm flex items-center justify-center gap-1.5"
                                >
                                    <i class="fas fa-times-circle"></i>Tolak
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Cash Tab -->
        <div id="cashTab" class="tab-content space-y-4 hidden">
            <?php if (empty($completedOrders)): ?>
            <div class="bg-white rounded-2xl p-12 text-center border border-stone-200">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-stone-50 text-stone-400 rounded-full mb-3">
                    <i class="fas fa-check-double text-3xl"></i>
                </div>
                <p class="text-stone-600 font-bold font-outfit text-base">Tidak Ada Pesanan Tunai Aktif</p>
                <p class="text-stone-400 text-xs mt-1">Belum ada pesanan berstatus bayar tunai yang menunggu penyelesaian.</p>
            </div>
            <?php else: ?>
                <?php foreach ($completedOrders as $order): ?>
                <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-5 hover:border-emerald-250 hover:shadow-md transition duration-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Left: Order Info -->
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Nomor Pesanan</p>
                                <p class="font-extrabold text-lg text-stone-900 font-outfit mt-0.5"><?= $order['order_number'] ?></p>
                            </div>
                            
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Nama Pelanggan</p>
                                <p class="font-bold text-stone-800 text-sm mt-0.5"><?= $order['customer_name'] ?></p>
                            </div>
                            
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Meja / Tipe</p>
                                <p class="text-sm font-semibold text-stone-850 mt-0.5">
                                    <?php if ($order['table_number']): ?>
                                        <span class="bg-stone-100 px-2.5 py-1 rounded-lg text-xs font-extrabold text-stone-700 mr-1"><i class="fas fa-chair mr-1"></i>Meja <?= $order['table_number'] ?></span>
                                    <?php else: ?>
                                        <span class="bg-amber-50 px-2.5 py-1 rounded-lg text-xs font-extrabold text-amber-850 mr-1"><i class="fas fa-shopping-bag mr-1"></i>Take Away</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <!-- Middle: Payment Info -->
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Metode Pembayaran</p>
                                <div class="flex items-center mt-1">
                                    <span class="bg-emerald-55 text-emerald-800 px-2.5 py-1 rounded-lg text-xs font-extrabold flex items-center gap-1.5"><i class="fas fa-money-bill-wave text-green-600"></i>Bayar Tunai</span>
                                </div>
                            </div>
                            
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Total Pembayaran</p>
                                <p class="font-extrabold text-2xl text-emerald-600 font-outfit mt-0.5"><?= formatRupiah($order['amount']) ?></p>
                            </div>
                            
                            <div>
                                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Status</p>
                                <div class="mt-1">
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 text-xs font-bold rounded-lg uppercase tracking-wider flex-inline items-center gap-1">
                                        <i class="fas fa-print text-xs"></i> Siap Cetak Struk
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Actions -->
                        <div class="flex flex-col gap-2.5 justify-center">
                            <button 
                                onclick="printReceipt(<?= $order['id'] ?>)"
                                class="bg-stone-200 hover:bg-stone-300 text-stone-700 font-bold py-3 px-4 rounded-xl transition duration-200 text-sm flex items-center justify-center gap-1.5 shadow-sm"
                            >
                                <i class="fas fa-print"></i>Cetak Struk Resi
                            </button>
                            <button 
                                onclick="completeCashPayment(<?= $order['payment_id'] ?>)"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl transition duration-200 text-sm flex items-center justify-center gap-1.5 shadow-md hover:shadow"
                            >
                                <i class="fas fa-check-double"></i>Selesaikan Bill
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Image Modal -->
    <div id="imageModal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4">
        <div class="max-w-2xl w-full relative">
            <img id="modalImage" src="" alt="Bukti Pembayaran" class="w-full rounded-2xl max-h-[80vh] object-contain shadow-2xl border border-stone-800">
            <button 
                onclick="closeImageModal()"
                class="absolute -top-12 right-0 text-white text-3xl cursor-pointer hover:text-stone-300 transition"
            >
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
    </div>

    <!-- Receipt Preview (untuk print) -->
    <div id="receipt" class="hidden" style="width: 80mm;">
        <div id="receiptContent"></div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all tabs
            document.getElementById('qrisTab').classList.add('hidden');
            document.getElementById('cashTab').classList.add('hidden');
            
            // Remove active class from buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('text-emerald-600', 'border-b-2', 'border-emerald-600', 'font-extrabold');
                btn.classList.add('text-stone-500', 'border-b-2', 'border-transparent', 'font-bold');
            });
            
            // Show selected tab
            if (tab === 'qris') {
                document.getElementById('qrisTab').classList.remove('hidden');
                event.target.closest('.tab-btn').classList.remove('text-stone-500', 'border-transparent', 'font-bold');
                event.target.closest('.tab-btn').classList.add('text-emerald-600', 'border-b-2', 'border-emerald-600', 'font-extrabold');
            } else {
                document.getElementById('cashTab').classList.remove('hidden');
                event.target.closest('.tab-btn').classList.remove('text-stone-500', 'border-transparent', 'font-bold');
                event.target.closest('.tab-btn').classList.add('text-emerald-600', 'border-b-2', 'border-emerald-600', 'font-extrabold');
            }
        }

        function showImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.remove('hidden');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }

        function approvePayment(paymentId, orderNumber) {
            if (!confirm('Terima pembayaran untuk ' + orderNumber + '?')) return;
            
            const formData = new FormData();
            formData.append('action', 'approve');
            formData.append('payment_id', paymentId);
            
            fetch('../admin/verify_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(e => alert('Error: ' + e.message));
        }

        function rejectPayment(paymentId, orderNumber) {
            const reason = prompt('Alasan penolakan untuk ' + orderNumber + ':');
            if (!reason) return;
            
            const formData = new FormData();
            formData.append('action', 'reject');
            formData.append('payment_id', paymentId);
            formData.append('reason', reason);
            
            fetch('../admin/verify_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(e => alert('Error: ' + e.message));
        }

        function printReceipt(orderId) {
            window.open('<?= APP_URL ?>/admin/print_receipt.php?order=' + orderId, '_blank');
        }

        function formatCurrency(amount) {
            return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function completeCashPayment(paymentId) {
            if (!confirm('Tandai pembayaran tunai sebagai selesai?')) return;
            
            const formData = new FormData();
            formData.append('action', 'complete_cash');
            formData.append('payment_id', paymentId);
            
            fetch('process_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Pembayaran selesai!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(e => alert('Error: ' + e.message));
        }
    </script>
</body>
</html>
