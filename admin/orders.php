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
<main class="p-4 pb-32 sm:pb-24">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="font-bold text-lg mb-4">Daftar Pesanan</h2>
        <div class="space-y-3">
            <?php foreach ($orders as $order): 
                $payment = getPaymentDetails($order['id']);
                $needsVerification = ($payment && $payment['payment_method'] === 'qris' && $payment['verification_status'] === 'pending' && $payment['proof_of_payment']);
            ?>
            <div class="border rounded-lg p-3 <?= $needsVerification ? 'border-orange-300 bg-orange-50' : '' ?>">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="font-bold"><?= $order['order_number'] ?></p>
                        <p class="text-sm text-gray-600"><?= $order['customer_name'] ?> • Meja <?= $order['table_number'] ?? 'TA' ?></p>
                        <?php if ($needsVerification): ?>
                        <p class="text-xs text-orange-700 mt-1">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            Menunggu verifikasi pembayaran QRIS
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?= getStatusBadge($order['status']) ?>">
                            <?= getStatusText($order['status']) ?>
                        </span>
                        <?php if ($payment && $payment['payment_method'] === 'qris'): ?>
                        <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                            QRIS
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600"><?= formatDateTime($order['created_at']) ?></span>
                    <div class="space-x-2">
                        <span class="font-bold text-slate-700"><?= formatRupiah($order['total']) ?></span>
                        <a href="?detail=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Detail Modal -->
<?php if ($orderDetail): ?>
<div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Detail Pesanan</h3>
            <a href="orders.php" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-times text-xl"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <!-- Left Column -->
            <div class="space-y-3">
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-600">No. Pesanan</p>
                    <p class="font-bold"><?= $orderDetail['order_number'] ?></p>
                </div>
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-600">Customer</p>
                    <p class="font-semibold"><?= $orderDetail['customer_name'] ?></p>
                    <p class="text-sm text-gray-500"><?= $orderDetail['customer_phone'] ?></p>
                </div>
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-600">Detail</p>
                    <p class="text-sm">Meja: <?= $orderDetail['table_number'] ?? 'Take Away' ?></p>
                    <p class="text-sm">Tipe: <?= getStatusText($orderDetail['order_type']) ?></p>
                    <p class="text-sm">Kasir: <?= $orderDetail['kasir_name'] ?? '-' ?></p>
                </div>
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-600 mb-2">Item Pesanan</p>
                    <?php foreach ($orderItems as $item): ?>
                    <div class="flex justify-between text-sm mb-1">
                        <span><?= $item['quantity'] ?>x <?= $item['menu_name'] ?></span>
                        <span class="font-semibold"><?= formatRupiah($item['subtotal']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                    <div class="flex justify-between font-bold text-lg pt-2 border-t">
                        <span>Total</span>
                        <span class="text-green-600"><?= formatRupiah($orderDetail['total']) ?></span>
                    </div>
            </div>
            
            <!-- Right Column - Payment Info -->
            <?php if ($paymentDetail): ?>
            <div class="space-y-3">
                <div class="bg-blue-50 rounded-lg p-3">
                    <p class="text-sm text-gray-600 mb-2">Metode Pembayaran</p>
                    <p class="font-bold">
                        <?php if ($paymentDetail['payment_method'] === 'qris'): ?>
                            <i class="fas fa-qrcode text-blue-600 mr-2"></i>QRIS
                        <?php else: ?>
                            <i class="fas fa-money-bill-wave text-green-600 mr-2"></i><?= getStatusText($paymentDetail['payment_method']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- QRIS Payment Verification -->
                <?php if ($paymentDetail['payment_method'] === 'qris'): ?>
                <div class="border rounded-lg p-3">
                    <p class="text-sm text-gray-600 mb-2">Verifikasi Pembayaran</p>
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-semibold">Status:</p>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php
                            if ($paymentDetail['verification_status'] === 'verified') {
                                echo 'bg-green-100 text-green-800';
                            } elseif ($paymentDetail['verification_status'] === 'rejected') {
                                echo 'bg-red-100 text-red-800';
                            } else {
                                echo 'bg-yellow-100 text-yellow-800';
                            }
                        ?>">
                            <?= ucfirst($paymentDetail['verification_status']) ?>
                        </span>
                    </div>
                    
                    <!-- Proof of Payment -->
                    <?php if ($paymentDetail['proof_of_payment']): ?>
                    <div class="mb-3">
                        <p class="text-sm text-gray-600 mb-2">Bukti Pembayaran:</p>
                        <img 
                            src="<?= UPLOADS_URL . '/' . $paymentDetail['proof_of_payment'] ?>" 
                            alt="Bukti Pembayaran" 
                            class="w-full rounded-lg border cursor-pointer hover:opacity-75"
                            onclick="window.open(this.src, '_blank')"
                        >
                    </div>
                    <?php endif; ?>
                    
                    <!-- Verification Notes -->
                    <?php if ($paymentDetail['verification_notes']): ?>
                    <div class="mb-3 p-2 bg-gray-50 rounded text-sm">
                        <p class="text-gray-600">Catatan: <?= $paymentDetail['verification_notes'] ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Approval/Rejection Buttons (for pending verification) -->
                    <?php if ($paymentDetail['verification_status'] === 'pending' && $paymentDetail['proof_of_payment']): ?>
                    <div class="space-y-2">
                        <button 
                            class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-3 rounded-lg font-semibold text-sm transition"
                            onclick="approvePayment(<?= $paymentDetail['id'] ?>)"
                        >
                            <i class="fas fa-check mr-2"></i>Terima Pembayaran
                        </button>
                        <button 
                            class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-3 rounded-lg font-semibold text-sm transition"
                            onclick="showRejectDialog(<?= $paymentDetail['id'] ?>)"
                        >
                            <i class="fas fa-times mr-2"></i>Tolak Pembayaran
                        </button>
                    </div>
                    <?php elseif ($paymentDetail['verification_status'] === 'verified' && $paymentDetail['payment_method'] === 'qris'): ?>
                    <div class="space-y-2">
                        <button 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-3 rounded-lg font-semibold text-sm transition"
                            onclick="printReceipt(<?= $orderDetail['id'] ?>)"
                        >
                            <i class="fas fa-print mr-2"></i>Print Resi
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Verified By -->
                <?php if ($paymentDetail['verified_by'] && $paymentDetail['verified_at']): ?>
                <div class="text-xs text-gray-500 mt-2">
                    Diverifikasi oleh: <?= $paymentDetail['verified_by_name'] ?><br>
                    Waktu: <?= formatDateTime($paymentDetail['verified_at']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reject Payment Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-sm w-full p-6">
        <h3 class="text-lg font-bold mb-4">Tolak Pembayaran</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Penolakan</label>
                <textarea 
                    id="rejectReason" 
                    rows="4" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"
                    placeholder="Contoh: Jumlah tidak sesuai, bukti tidak jelas, dll"
                ></textarea>
            </div>
            <div class="flex gap-2">
                <button 
                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg font-semibold transition"
                    onclick="closeRejectDialog()"
                >
                    Batal
                </button>
                <button 
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-semibold transition"
                    onclick="submitRejectPayment()"
                >
                    Tolak
                </button>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
let rejectPaymentId = null;

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

