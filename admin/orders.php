<?php
require_once '../config/config.php';
requireRole(['owner']);

// Prevent browser caching of order statuses
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$pageTitle = 'Pesanan';
$user = getCurrentUser();
$db = getDB();

// View detail
$detailId = $_GET['detail'] ?? null;
$orderDetail = null;
$orderItems = [];
$paymentDetail = null;

if ($detailId) {
    $stmt = $db->prepare("SELECT o.*, t.table_number, u.full_name as kasir_name FROM orders o LEFT JOIN tables t ON o.table_id = t.id LEFT JOIN users u ON o.created_by = u.id WHERE o.id = ?");
    $stmt->execute([$detailId]);
    $orderDetail = $stmt->fetch();
    
    $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$detailId]);
    $orderItems = $stmt->fetchAll();
    
    // Get payment details
    $paymentDetail = getPaymentDetails($detailId);
}

$stmt = $db->query("SELECT o.*, t.table_number FROM orders o LEFT JOIN tables t ON o.table_id = t.id ORDER BY o.created_at DESC LIMIT 50");
$orders = $stmt->fetchAll();
include '../includes/header.php';
?>
<main class="p-4 max-w-7xl mx-auto pb-32 sm:pb-24">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-stone-850 font-outfit">Daftar Pesanan</h1>
            <p class="text-stone-500 text-sm mt-1">Pantau dan kelola semua transaksi pesanan pelanggan.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
        <div class="space-y-4">
            <?php foreach ($orders as $order): 
                $payment = getPaymentDetails($order['id']);
                $needsVerification = ($payment && $payment['payment_method'] === 'qris' && $payment['verification_status'] === 'pending' && $payment['proof_of_payment']);
            ?>
            <div class="border <?= $needsVerification ? 'border-orange-300 bg-orange-50/50' : 'border-stone-200 hover:border-emerald-300 hover:shadow-md' ?> rounded-xl p-5 transition duration-200">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 shrink-0 rounded-full flex items-center justify-center font-bold text-xl shadow-inner <?= $needsVerification ? 'bg-orange-100 text-orange-600' : 'bg-emerald-50 text-emerald-600' ?>">
                            <i class="fas <?= $needsVerification ? 'fa-exclamation-triangle' : 'fa-receipt' ?>"></i>
                        </div>
                        <div>
                            <p class="font-extrabold text-lg text-stone-850 font-outfit mb-0.5"><?= $order['order_number'] ?></p>
                            <p class="text-sm font-medium text-stone-600 flex items-center flex-wrap gap-2">
                                <span><i class="fas fa-user text-stone-400 mr-1"></i><?= htmlspecialchars($order['customer_name']) ?></span>
                                <span class="text-stone-300">|</span>
                                <span><i class="fas fa-chair text-stone-400 mr-1"></i>Meja <?= $order['table_number'] ?? 'TA' ?></span>
                            </p>
                            <?php if ($needsVerification): ?>
                            <p class="text-xs font-bold text-orange-600 mt-2 bg-orange-100/80 border border-orange-200 inline-block px-2.5 py-1 rounded-md">
                                <i class="fas fa-circle-notch fa-spin mr-1"></i> Menunggu verifikasi pembayaran QRIS
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex flex-col md:items-end gap-2 w-full md:w-auto">
                        <div class="flex items-center gap-2">
                            <?php if ($payment && $payment['payment_method'] === 'qris'): ?>
                            <span class="px-2.5 py-1 text-xs font-extrabold rounded-lg bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-wide">
                                <i class="fas fa-qrcode mr-1"></i> QRIS
                            </span>
                            <?php endif; ?>
                            <span class="px-3 py-1 text-xs font-extrabold rounded-lg shadow-sm border uppercase tracking-wide <?= getStatusBadge($order['status']) ?>">
                                <?= getStatusText($order['status']) ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between md:justify-end w-full gap-5 mt-2 md:mt-0">
                            <span class="text-xs text-stone-500 font-medium"><i class="far fa-clock mr-1"></i><?= formatDateTime($order['created_at']) ?></span>
                            <span class="font-extrabold text-emerald-600 text-xl font-outfit"><?= formatRupiah($order['total']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-dashed border-stone-200 flex justify-end">
                    <a href="?detail=<?= $order['id'] ?>" class="bg-stone-800 hover:bg-stone-900 text-white font-bold py-2.5 px-6 rounded-lg text-sm transition duration-200 flex items-center shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-eye mr-2"></i> Lihat Detail
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Detail Modal -->
<?php if ($orderDetail): ?>
<div class="fixed inset-0 bg-stone-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-stone-50 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-stone-200">
        <!-- Modal Header -->
        <div class="bg-white px-6 py-4 border-b border-stone-200 flex justify-between items-center z-10 shadow-sm">
            <h3 class="text-xl font-extrabold text-stone-850 font-outfit flex items-center">
                <i class="fas fa-file-invoice text-emerald-600 mr-2.5"></i> Detail Pesanan
            </h3>
            <a href="orders.php" class="w-8 h-8 flex items-center justify-center rounded-full bg-stone-100 text-stone-500 hover:bg-red-100 hover:text-red-600 transition duration-200">
                <i class="fas fa-times text-lg"></i>
            </a>
        </div>
        
        <!-- Modal Content Scrollable -->
        <div class="p-6 overflow-y-auto flex-1">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Left Column (Order Info & Items) -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- Order Info Card -->
                    <div class="bg-white rounded-xl p-5 border border-stone-200 shadow-sm">
                        <div class="flex justify-between items-start mb-4 pb-4 border-b border-stone-100">
                            <div>
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">No. Pesanan</p>
                                <p class="font-extrabold text-xl text-stone-850 font-outfit"><?= $orderDetail['order_number'] ?></p>
                            </div>
                            <span class="px-3 py-1 text-xs font-extrabold rounded-lg shadow-sm border uppercase tracking-wide <?= getStatusBadge($orderDetail['status']) ?>">
                                <?= getStatusText($orderDetail['status']) ?>
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">Customer</p>
                                <p class="font-bold text-stone-800"><?= htmlspecialchars($orderDetail['customer_name']) ?></p>
                                <?php if ($orderDetail['customer_phone']): ?>
                                <p class="text-sm text-stone-500 font-medium mt-0.5"><i class="fas fa-phone-alt text-xs mr-1 text-stone-300"></i><?= htmlspecialchars($orderDetail['customer_phone']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">Detail Layanan</p>
                                <p class="font-bold text-stone-800"><i class="fas fa-chair text-stone-400 text-xs mr-1"></i> <?= $orderDetail['table_number'] ? 'Meja '.$orderDetail['table_number'] : 'Take Away' ?></p>
                                <p class="text-sm text-stone-500 font-medium mt-0.5"><i class="fas fa-user-tag text-xs mr-1 text-stone-300"></i> Kasir: <?= htmlspecialchars($orderDetail['kasir_name'] ?? '-') ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items Card -->
                    <div class="bg-white rounded-xl p-5 border border-stone-200 shadow-sm">
                        <h4 class="text-sm font-extrabold text-stone-700 uppercase tracking-wider mb-4 flex items-center">
                            <i class="fas fa-shopping-basket text-emerald-500 mr-2"></i> Daftar Menu
                        </h4>
                        
                        <div class="space-y-3 mb-4">
                            <?php foreach ($orderItems as $item): ?>
                            <div class="flex justify-between items-center text-sm p-3 bg-stone-50 rounded-lg border border-stone-100">
                                <div class="flex items-center">
                                    <span class="w-6 h-6 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs mr-3"><?= $item['quantity'] ?>x</span>
                                    <span class="font-bold text-stone-700"><?= htmlspecialchars($item['menu_name']) ?></span>
                                </div>
                                <span class="font-extrabold text-stone-800"><?= formatRupiah($item['subtotal']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="flex justify-between items-center pt-4 border-t border-dashed border-stone-300">
                            <span class="font-extrabold text-stone-500 text-lg">TOTAL TAGIHAN</span>
                            <span class="text-2xl font-extrabold text-emerald-600 font-outfit"><?= formatRupiah($orderDetail['total']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column (Payment Info) -->
                <div class="lg:col-span-2 space-y-6">
                    <?php if ($paymentDetail): ?>
                    <!-- Payment Status Card -->
                    <div class="bg-white rounded-xl p-5 border border-stone-200 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-blue-50 rounded-bl-full -z-0"></div>
                        
                        <h4 class="text-sm font-extrabold text-stone-700 uppercase tracking-wider mb-4 relative z-10 flex items-center">
                            <i class="fas fa-wallet text-blue-500 mr-2"></i> Info Pembayaran
                        </h4>
                        
                        <div class="bg-stone-50 rounded-lg p-4 border border-stone-100 mb-4">
                            <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">Metode</p>
                            <p class="font-extrabold text-lg text-stone-800 flex items-center">
                                <?php if ($paymentDetail['payment_method'] === 'qris'): ?>
                                    <i class="fas fa-qrcode text-blue-600 mr-2"></i>QRIS
                                <?php else: ?>
                                    <i class="fas fa-money-bill-wave text-emerald-600 mr-2"></i><?= getStatusText($paymentDetail['payment_method']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <!-- QRIS Verification Section -->
                        <?php if ($paymentDetail['payment_method'] === 'qris'): ?>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-1">Status Verifikasi</p>
                                <?php
                                    $vStatus = $paymentDetail['verification_status'];
                                    $vClass = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                                    $vIcon = 'fa-clock';
                                    if ($vStatus === 'verified') {
                                        $vClass = 'bg-green-100 text-green-700 border-green-200';
                                        $vIcon = 'fa-check-circle';
                                    } elseif ($vStatus === 'rejected') {
                                        $vClass = 'bg-red-100 text-red-700 border-red-200';
                                        $vIcon = 'fa-times-circle';
                                    }
                                ?>
                                <span class="px-3 py-1.5 text-xs font-extrabold rounded-lg border <?= $vClass ?> inline-flex items-center uppercase tracking-wide">
                                    <i class="fas <?= $vIcon ?> mr-1.5"></i> <?= ucfirst($vStatus) ?>
                                </span>
                            </div>
                            
                            <?php if ($paymentDetail['proof_of_payment']): ?>
                            <div>
                                <p class="text-xs font-bold text-stone-400 uppercase tracking-wider mb-2">Bukti Transfer</p>
                                <div class="rounded-xl overflow-hidden border-2 border-stone-200 shadow-sm group relative">
                                    <img 
                                        src="<?= UPLOADS_URL . '/' . $paymentDetail['proof_of_payment'] ?>" 
                                        alt="Bukti Pembayaran" 
                                        class="w-full h-48 object-cover cursor-zoom-in transition duration-300 group-hover:scale-105"
                                        onclick="showImageModal(this.src)"
                                    >
                                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300 pointer-events-none">
                                        <i class="fas fa-search-plus text-white text-3xl"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($paymentDetail['verification_notes']): ?>
                            <div class="bg-red-50 border border-red-100 rounded-lg p-3">
                                <p class="text-xs font-bold text-red-500 uppercase tracking-wider mb-1"><i class="fas fa-info-circle mr-1"></i> Catatan Penolakan</p>
                                <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($paymentDetail['verification_notes']) ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Action Buttons -->
                            <?php if ($paymentDetail['verification_status'] === 'pending' && $paymentDetail['proof_of_payment']): ?>
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button onclick="showRejectDialog(<?= $paymentDetail['id'] ?>)" class="bg-white border-2 border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300 py-2.5 rounded-xl font-bold text-sm transition flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i> Tolak
                                </button>
                                <button onclick="approvePayment(<?= $paymentDetail['id'] ?>)" class="bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center transform hover:-translate-y-0.5">
                                    <i class="fas fa-check mr-2"></i> Terima
                                </button>
                            </div>
                            <?php elseif ($paymentDetail['verification_status'] === 'verified'): ?>
                            <div class="pt-2">
                                <button onclick="printReceipt(<?= $orderDetail['id'] ?>)" class="w-full bg-stone-800 hover:bg-stone-900 text-white py-3 rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center">
                                    <i class="fas fa-print mr-2 text-emerald-400"></i> Print Struk Resi
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Verified By Info -->
                        <?php if ($paymentDetail['verified_by'] && $paymentDetail['verified_at']): ?>
                        <div class="mt-4 pt-4 border-t border-stone-100 text-xs text-stone-500 font-medium">
                            <p class="mb-1"><i class="fas fa-user-check mr-1.5"></i> Verifikator: <span class="font-bold text-stone-700"><?= htmlspecialchars($paymentDetail['verified_by_name']) ?></span></p>
                            <p><i class="far fa-clock mr-1.5"></i> Waktu: <?= formatDateTime($paymentDetail['verified_at']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <!-- No Payment Info -->
                    <div class="bg-stone-100 border border-dashed border-stone-300 rounded-xl p-8 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-stone-200 rounded-full flex items-center justify-center text-stone-400 text-2xl mb-4">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <p class="font-bold text-stone-700 mb-1">Belum Ada Pembayaran</p>
                        <p class="text-sm text-stone-500">Customer belum menyelesaikan proses pembayaran untuk pesanan ini.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Payment Modal (Upgraded) -->
<div id="rejectModal" class="hidden fixed inset-0 bg-stone-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
        <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex items-center">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 mr-3">
                <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
            <h3 class="text-xl font-extrabold text-red-900 font-outfit">Tolak Pembayaran</h3>
        </div>
        <div class="p-6">
            <div class="mb-5">
                <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-2">Alasan Penolakan</label>
                <textarea 
                    id="rejectReason" 
                    rows="3" 
                    class="w-full px-4 py-3 bg-stone-50 border border-stone-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white transition resize-none font-medium text-sm"
                    placeholder="Contoh: Bukti transfer buram, nominal transfer kurang..."
                ></textarea>
                <p class="text-xs text-stone-500 mt-2"><i class="fas fa-info-circle mr-1"></i>Alasan ini akan ditampilkan ke sistem/kasir.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeRejectDialog()" class="flex-1 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 py-3 rounded-xl font-bold text-sm transition">
                    Kembali
                </button>
                <button onclick="submitRejectPayment()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-bold text-sm shadow-md transition">
                    Ya, Tolak Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Image Preview Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-[60] flex items-center justify-center p-4">
    <div class="max-w-3xl w-full relative">
        <img id="modalImage" src="" alt="Bukti Pembayaran" class="w-full rounded-2xl max-h-[85vh] object-contain shadow-2xl border border-stone-800">
        <button 
            onclick="closeImageModal()"
            class="absolute -top-12 right-0 text-white text-3xl cursor-pointer hover:text-stone-300 transition"
        >
            <i class="fas fa-times-circle"></i>
        </button>
    </div>
</div>

<script>
let rejectPaymentId = null;

function showImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}

function showRejectDialog(paymentId) {
    rejectPaymentId = paymentId;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectDialog() {
    rejectPaymentId = null;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectModal').classList.add('hidden');
}

function approvePayment(paymentId) {
    if (!confirm('Verifikasi pembayaran QRIS ini?')) return;
    
    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('payment_id', paymentId);
    
    fetch('verify_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert('Error: ' + (data.message || data.error));
        }
    })
    .catch(e => alert('Error: ' + e.message));
}

function submitRejectPayment() {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        alert('Alasan penolakan harus diisi');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('payment_id', rejectPaymentId);
    formData.append('reason', reason);
    
    fetch('verify_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Reload page in-place to update modal status
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(e => alert('Error: ' + e.message))
    .finally(() => closeRejectDialog());
}

function printReceipt(orderId) {
    window.open('print_receipt.php?order=' + orderId, '_blank', 'width=400,height=600');
}
</script>

<?php include '../includes/footer.php'; ?>

