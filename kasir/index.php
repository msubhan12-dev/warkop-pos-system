<?php
require_once '../config/config.php';
requireRole(['kasir', 'pelayan', 'owner']);

$user = getCurrentUser();

// Get tables
$db = getDB();
$stmt = $db->query("SELECT * FROM tables WHERE is_active = 1 ORDER BY table_number");
$tables = $stmt->fetchAll();

// Get categories
$stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");
$categories = $stmt->fetchAll();

// Get menus
$stmt = $db->query("
    SELECT m.*, c.name as category_name 
    FROM menus m
    JOIN categories c ON m.category_id = c.id
    WHERE m.is_available = 1
    ORDER BY c.sort_order, m.name
");
$menus = $stmt->fetchAll();

// Get active orders
$stmt = $db->query("
    SELECT 
        o.*,
        t.table_number,
        COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.status IN ('pending', 'confirmed', 'cooking', 'ready')
    GROUP BY o.id
    ORDER BY o.created_at ASC
");
$activeOrders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>POS Kasir - <?= APP_NAME ?></title>
<?php
$pageTitle = 'POS Kasir';
include '../includes/header.php';
?>
        <div class="flex-1 flex flex-col h-full overflow-hidden">
            <!-- Top Header Action Bar -->
            <div class="bg-white px-4 py-3 border-b border-stone-200 flex justify-between items-center z-30">
                <div class="hidden md:block">
                    <h1 class="text-xl font-extrabold font-outfit text-stone-850">Terminal Kasir</h1>
                </div>
                <div class="flex items-center space-x-3.5 ml-auto">
                    <!-- Cart Button (Mobile) -->
                    <button onclick="toggleOrderSidebar()" class="lg:hidden relative text-emerald-600 bg-emerald-50 hover:bg-emerald-100 p-2.5 rounded-full flex items-center justify-center transition shadow-sm" title="Keranjang">
                        <i class="fas fa-shopping-basket text-lg"></i>
                        <span id="floatingCartBadge" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center font-bold shadow-md hidden">0</span>
                    </button>
                    
                    <button onclick="showActiveOrders()" class="relative text-stone-500 hover:text-emerald-600 bg-stone-100 hover:bg-emerald-50 p-2.5 rounded-full flex items-center justify-center transition shadow-sm" title="Pesanan Aktif">
                        <i class="fas fa-list text-lg"></i>
                        <?php if (!empty($activeOrders)): ?>
                        <span class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold shadow-md">
                            <?= count($activeOrders) ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    <!-- Mobile-only payment/logout links -->
                    <a href="payments.php" class="md:hidden text-stone-500 hover:text-emerald-600 bg-stone-100 hover:bg-emerald-50 p-2.5 rounded-full flex items-center justify-center transition" title="Verifikasi Pembayaran">
                        <i class="fas fa-credit-card text-lg"></i>
                    </a>
                </div>
            </div>

    <!-- Main Content -->
    <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">
        <!-- Menu Section (Left/Top) -->
        <div class="flex-1 overflow-hidden flex flex-col">
            <!-- Search & Category -->
            <div class="p-5 bg-white border-b border-slate-200/60 shadow-sm z-10">
                <div class="relative mb-4">
                    <input 
                        type="text" 
                        id="searchMenu"
                        placeholder="Cari kopi, cemilan, minuman..."
                        class="w-full px-5 py-3 pl-11 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 font-medium transition-all duration-300"
                        onkeyup="searchMenu()"
                    >
                    <i class="fas fa-search absolute left-4 top-3.5 text-slate-400 text-lg"></i>
                </div>
                
                <div class="category-tabs flex space-x-2.5 overflow-x-auto pb-1">
                    <button onclick="filterCategory('all')" class="category-btn bg-emerald-600 text-white px-5 py-2.5 rounded-full whitespace-nowrap font-bold text-sm shadow-md shadow-emerald-600/20 transition-all duration-300">
                        Semua Menu
                    </button>
                    <?php foreach ($categories as $category): ?>
                    <button onclick="filterCategory(<?= $category['id'] ?>)" class="category-btn bg-white border border-slate-200 text-slate-600 px-5 py-2.5 rounded-full whitespace-nowrap font-bold text-sm hover:bg-slate-50 hover:text-emerald-600 hover:border-emerald-200 transition-all duration-300">
                        <?= $category['icon'] ?> <?= $category['name'] ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Menu Grid -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-5 pb-24 lg:pb-5 bg-slate-50/50">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4" id="menuGrid">
                    <?php foreach ($menus as $menu): ?>
                    <button 
                        onclick="addToOrder(<?= $menu['id'] ?>, '<?= addslashes($menu['name']) ?>', <?= $menu['price'] ?>)"
                        class="menu-item group bg-white rounded-2xl p-3 text-left border border-slate-100 hover:border-emerald-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                        data-name="<?= strtolower($menu['name']) ?>"
                        data-category="<?= $menu['category_id'] ?>"
                    >
                        <div class="bg-slate-100/80 text-slate-400 rounded-xl h-28 sm:h-32 flex items-center justify-center mb-3.5 overflow-hidden group-hover:bg-emerald-50 transition-colors duration-300">
                            <?php if (!empty($menu['image'])): ?>
                                <img src="<?= UPLOADS_URL . '/' . $menu['image'] ?>" alt="<?= htmlspecialchars($menu['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <?php else: ?>
                                <i class="fas fa-mug-hot text-4xl group-hover:text-emerald-500 transition-colors duration-300"></i>
                            <?php endif; ?>
                        </div>
                        <div class="px-1 pb-1">
                            <h3 class="font-bold text-sm text-slate-800 font-outfit mb-1.5 line-clamp-2 group-hover:text-emerald-700 transition-colors"><?= $menu['name'] ?></h3>
                            <p class="text-emerald-600 font-extrabold text-sm"><?= formatRupiah($menu['price']) ?></p>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Order Section (Right/Bottom) -->
        <div id="orderSidebar" class="fixed inset-y-0 right-0 w-full lg:w-[420px] bg-white shadow-2xl lg:shadow-[-10px_0_30px_-15px_rgba(0,0,0,0.1)] flex flex-col z-50 transform translate-x-full lg:translate-x-0 transition-transform duration-300 lg:relative lg:inset-auto lg:h-full lg:border-l lg:border-slate-200">
            <!-- Order Header -->
            <div class="p-5 border-b border-slate-100 bg-gradient-to-b from-slate-50 to-white">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-extrabold text-xl text-slate-800 font-outfit flex items-center">
                        Pesanan Saat Ini
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-lg text-xs font-bold" id="itemCountBadge">0 Item</span>
                        <button onclick="toggleOrderSidebar()" class="lg:hidden text-slate-400 hover:text-red-500 w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="space-y-3.5">
                    <!-- Table Selection -->
                    <div>
                        <div class="relative">
                            <select id="tableSelect" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 font-semibold text-slate-700 appearance-none transition-all duration-300 cursor-pointer" onchange="toggleKasirDeliveryAddress()">
                                <option value="">🛒 Take Away / Bungkus</option>
                                <option value="delivery">🏍️ Delivery / Pesan Antar</option>
                                <?php foreach ($tables as $table): ?>
                                <option value="<?= $table['id'] ?>" <?= $table['status'] !== 'available' ? 'disabled' : '' ?>>
                                    🪑 Meja <?= $table['table_number'] ?> <?= $table['status'] !== 'available' ? '(Terisi)' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-4 text-slate-400 pointer-events-none text-sm"></i>
                        </div>
                    </div>
                    
                    <!-- Customer Name -->
                    <div>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="customerName"
                                placeholder="Nama Pelanggan (Wajib)"
                                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 font-semibold text-slate-700 transition-all duration-300"
                            >
                        </div>
                    </div>
                    
                    <!-- Delivery Address (Hidden by default) -->
                    <div id="kasirDeliveryAddressContainer" class="hidden">
                        <textarea 
                            id="kasirDeliveryAddress"
                            placeholder="Alamat Pengiriman (Wajib untuk Delivery)"
                            rows="2"
                            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 font-semibold text-slate-700 transition-all duration-300 resize-none"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="flex-1 overflow-y-auto p-4 bg-slate-50/30" id="orderItems">
                <div class="h-full flex flex-col items-center justify-center text-slate-400 opacity-80">
                    <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-shopping-basket text-4xl text-slate-300"></i>
                    </div>
                    <p class="font-bold text-slate-500 font-outfit text-lg">Keranjang Kosong</p>
                    <p class="text-xs mt-1 text-slate-400">Yuk, pilih menu di sebelah kiri!</p>
                </div>
            </div>

            <!-- Order Footer -->
            <div class="p-5 pb-32 sm:pb-8 border-t border-slate-200 bg-white shadow-[0_-10px_30px_-15px_rgba(0,0,0,0.05)]">
                <div class="space-y-3 mb-5 text-sm">
                    <div class="flex justify-between text-slate-500 font-medium" style="display: none;">
                        <span>Subtotal</span>
                        <span id="subtotal">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-slate-500 font-medium" style="display: none;">
                        <span>Pajak (10%)</span>
                        <span id="tax">Rp 0</span>
                    </div>
                    <div class="flex justify-between items-end border-t border-dashed border-slate-300 pt-3">
                        <span class="text-slate-500 font-bold font-outfit uppercase tracking-wider text-xs">Total Tagihan</span>
                        <span class="text-2xl lg:text-3xl font-extrabold text-emerald-600 font-outfit leading-none" id="total">Rp 0</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-12 gap-3">
                    <button 
                        onclick="clearOrder()"
                        class="col-span-4 bg-slate-100 hover:bg-red-50 text-slate-600 hover:text-red-600 font-bold py-3 lg:py-4 rounded-2xl transition-all duration-300 flex flex-col items-center justify-center gap-1 border border-transparent hover:border-red-200"
                    >
                        <i class="fas fa-trash-alt text-sm lg:text-base"></i>
                        <span class="text-[9px] lg:text-[10px] uppercase tracking-wider">Batal</span>
                    </button>
                    <button 
                        onclick="processOrder()"
                        id="processBtn"
                        class="col-span-8 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-extrabold py-3 lg:py-4 rounded-2xl transition-all duration-300 disabled:opacity-50 disabled:grayscale flex items-center justify-center gap-2 shadow-[0_8px_20px_-6px_rgba(16,185,129,0.4)] hover:shadow-[0_12px_25px_-6px_rgba(16,185,129,0.5)] hover:-translate-y-0.5"
                        disabled
                    >
                        <span class="text-base lg:text-lg">Proses Bayar</span>
                        <i class="fas fa-arrow-right text-sm lg:text-base"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Checkout Modal (Bayar Langsung / Nanti) -->
    <div id="checkoutModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-stone-200">
            <div class="flex justify-between items-center mb-4 border-b pb-2.5">
                <h3 class="text-xl font-bold text-stone-850 font-outfit flex items-center">
                    <i class="fas fa-cash-register mr-2 text-emerald-600"></i>Konfirmasi Pembayaran
                </h3>
                <button onclick="closeCheckoutModal()" class="text-stone-400 hover:text-stone-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div class="bg-stone-50 border border-stone-200 rounded-xl p-3 text-sm text-stone-600 space-y-1">
                    <div class="flex justify-between">
                        <span>Customer:</span>
                        <span class="font-bold text-stone-800" id="modalCustomerName">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Meja/Tipe:</span>
                        <span class="font-bold text-stone-800" id="modalTableNumber">-</span>
                    </div>
                    <div class="flex justify-between hidden" id="modalDeliveryAddressRow">
                        <span>Alamat:</span>
                        <span class="font-bold text-stone-800 text-right max-w-[60%] leading-snug" id="modalDeliveryAddressText">-</span>
                    </div>
                </div>

                <div class="flex justify-between items-center font-extrabold text-xl border-b pb-2.5 font-outfit">
                    <span>Total Tagihan:</span>
                    <span class="text-emerald-600" id="modalTotalBill">Rp 0</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-700 mb-2 uppercase tracking-wider">Metode Pembayaran</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex flex-col items-center justify-center p-3 border-2 border-emerald-600 rounded-xl cursor-pointer bg-emerald-50 transition" id="optPayLater">
                            <input type="radio" name="payment_action" value="pay_later" checked class="hidden" onchange="togglePaymentInputs()">
                            <i class="fas fa-clock text-orange-500 text-lg mb-1"></i>
                            <span class="text-xs font-bold text-stone-800">Bayar Nanti</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 border-2 border-stone-200 rounded-xl cursor-pointer hover:border-emerald-600 transition" id="optPayNowCash">
                            <input type="radio" name="payment_action" value="pay_now_cash" class="hidden" onchange="togglePaymentInputs()">
                            <i class="fas fa-money-bill-wave text-green-600 text-lg mb-1"></i>
                            <span class="text-xs font-bold text-stone-800">Tunai (Sekarang)</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 border-2 border-stone-200 rounded-xl cursor-pointer hover:border-emerald-600 transition" id="optPayNowQRIS">
                            <input type="radio" name="payment_action" value="pay_now_qris" class="hidden" onchange="togglePaymentInputs()">
                            <i class="fas fa-qrcode text-blue-600 text-lg mb-1"></i>
                            <span class="text-xs font-bold text-stone-800">QRIS (Sekarang)</span>
                        </label>
                    </div>
                </div>

                <!-- Input area for Cash payment -->
                <div id="cashInputArea" class="hidden space-y-3 p-3 bg-emerald-50/50 rounded-xl border border-emerald-200">
                    <div>
                        <label class="block text-xs font-bold text-emerald-900 mb-1.5 uppercase">Uang Diterima (Cash Paid)</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 text-stone-500 font-bold text-sm">Rp</span>
                            <input 
                                type="number" 
                                id="cashPaidAmount" 
                                class="w-full pl-9 pr-3 py-2.5 border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 font-extrabold text-xl"
                                placeholder="0"
                                onkeyup="calculateChange()"
                            >
                        </div>
                    </div>
                    <!-- Quick cash choices -->
                    <div class="flex flex-wrap gap-1.5" id="quickCashButtons">
                        <!-- Will be dynamically populated based on total -->
                    </div>
                    <div class="flex justify-between items-center text-sm font-bold pt-2.5 border-t border-emerald-200">
                        <span class="text-stone-700">Uang Kembalian:</span>
                        <span class="text-blue-600 text-xl font-extrabold font-outfit" id="cashChangeAmount">Rp 0</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-2.5">
                <button 
                    onclick="closeCheckoutModal()"
                    class="flex-1 bg-stone-200 hover:bg-stone-300 text-stone-700 py-3.5 rounded-xl font-bold transition text-sm"
                >
                    Batal
                </button>
                <button 
                    onclick="submitOrder()"
                    class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-bold transition text-sm flex items-center justify-center gap-1.5 shadow-md hover:shadow"
                >
                    <i class="fas fa-check-circle"></i>Proses Order
                </button>
            </div>
        </div>
    </div>

    <!-- Active Orders Modal -->
    <div id="activeOrdersModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden border border-stone-200 flex flex-col shadow-2xl">
            <div class="p-4 border-b border-stone-200 flex items-center justify-between bg-emerald-50/50">
                <h3 class="font-extrabold text-lg text-emerald-950 font-outfit flex items-center">
                    <i class="fas fa-list-ul mr-2 text-emerald-600"></i>Pesanan Aktif
                </h3>
                <button onclick="closeActiveOrders()" class="text-stone-400 hover:text-stone-750">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4 overflow-y-auto flex-1 max-h-96 space-y-3">
                <?php if (empty($activeOrders)): ?>
                    <div class="text-center py-12 text-stone-400">
                        <i class="fas fa-list-ul text-4xl mb-2"></i>
                        <p class="font-bold">Tidak ada pesanan aktif</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3.5">
                        <?php foreach ($activeOrders as $order): ?>
                        <div class="border border-stone-200 rounded-2xl p-4 bg-white hover:border-emerald-200 hover:shadow-sm transition flex flex-col justify-between">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <p class="font-extrabold text-stone-900 font-outfit text-base"><?= $order['order_number'] ?></p>
                                    <p class="text-xs text-stone-500 mt-1">
                                        <?php if ($order['table_number']): ?>
                                            <span class="bg-stone-100 text-stone-700 px-2 py-1 rounded font-bold mr-1"><i class="fas fa-chair mr-1"></i>Meja <?= $order['table_number'] ?></span>
                                        <?php else: ?>
                                            <span class="bg-amber-50 text-amber-800 px-2 py-1 rounded font-bold mr-1"><i class="fas fa-shopping-bag mr-1"></i>Take Away</span>
                                        <?php endif; ?>
                                        • Pelanggan: <strong class="text-stone-700"><?= $order['customer_name'] ?></strong>
                                    </p>
                                </div>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= getStatusBadge($order['status']) ?>">
                                    <?= getStatusText($order['status']) ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm mb-3.5 pt-1">
                                <span class="text-stone-500 font-medium"><?= $order['item_count'] ?> item • <?= timeAgo($order['created_at']) ?></span>
                                <span class="font-extrabold text-emerald-600 text-base"><?= formatRupiah($order['total']) ?></span>
                            </div>
                            <div class="flex justify-end gap-2 pt-2.5 border-t border-dashed border-stone-200">
                                <button 
                                    onclick="printReceipt(<?= $order['id'] ?>)"
                                    class="bg-stone-200 hover:bg-stone-300 text-stone-700 text-xs font-bold py-2 px-3 rounded-xl flex items-center transition"
                                >
                                    <i class="fas fa-print mr-1"></i>Cetak Struk
                                </button>
                                <button 
                                    onclick="serveAndComplete(<?= $order['id'] ?>)"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 px-3 rounded-xl flex items-center transition"
                                >
                                    <i class="fas fa-check-double mr-1"></i>Sajikan & Selesai
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
      </div>
    <script>
        let currentOrder = [];
        
        function addToOrder(id, name, price) {
            const existingItem = currentOrder.find(item => item.id === id);
            
            if (existingItem) {
                existingItem.quantity++;
            } else {
                currentOrder.push({
                    id: id,
                    name: name,
                    price: price,
                    quantity: 1
                });
            }
            
            renderOrder();
            showNotification('success', name + ' ditambahkan');
        }
        
        function updateQuantity(id, change) {
            const item = currentOrder.find(item => item.id === id);
            
            if (item) {
                item.quantity += change;
                
                if (item.quantity <= 0) {
                    removeItem(id);
                } else {
                    renderOrder();
                }
            }
        }
        
        function removeItem(id) {
            currentOrder = currentOrder.filter(item => item.id !== id);
            renderOrder();
        }
        
        function renderOrder() {
            const container = document.getElementById('orderItems');
            const processBtn = document.getElementById('processBtn');
            
            if (currentOrder.length === 0) {
                container.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-slate-400 opacity-80">
                    <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-shopping-basket text-4xl text-slate-300"></i>
                    </div>
                </div>
                `;
                processBtn.disabled = true;
                updateTotals(0);
                document.getElementById('itemCountBadge').textContent = '0 Item';
                updateFloatingCartBadge(0);
                return;
            }
            
            let html = '<div class="space-y-3.5">';
            let subtotal = 0;
            let itemCount = 0;
            
            currentOrder.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                itemCount += item.quantity;
                
                html += `
                    <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow duration-300 relative group">
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="font-bold text-slate-800 text-sm font-outfit flex-1 pr-6 leading-tight">${item.name}</h4>
                            <button onclick="removeItem(${item.id})" class="absolute right-3 top-3 w-7 h-7 bg-red-50 text-red-500 rounded-full flex items-center justify-center hover:bg-red-500 hover:text-white transition-colors duration-300 opacity-0 group-hover:opacity-100" title="Hapus Item">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                        <div class="flex items-end justify-between mt-auto">
                            <div class="flex items-center space-x-1 bg-slate-50 border border-slate-200/60 rounded-xl p-1">
                                <button onclick="updateQuantity(${item.id}, -1)" class="w-7 h-7 flex items-center justify-center text-emerald-600 bg-white hover:bg-emerald-50 rounded-lg shadow-sm font-bold transition-colors">
                                    <i class="fas fa-minus text-[10px]"></i>
                                </button>
                                <span class="font-extrabold w-8 text-center text-slate-700 text-sm font-outfit">${item.quantity}</span>
                                <button onclick="updateQuantity(${item.id}, 1)" class="w-7 h-7 flex items-center justify-center text-emerald-600 bg-white hover:bg-emerald-50 rounded-lg shadow-sm font-bold transition-colors">
                                    <i class="fas fa-plus text-[10px]"></i>
                                </button>
                            </div>
                            <div class="text-right">
                                <span class="font-extrabold text-emerald-600 text-base font-outfit">
                                    Rp ${itemTotal.toLocaleString('id-ID')}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
            processBtn.disabled = false;
            document.getElementById('itemCountBadge').textContent = itemCount + ' Item';
            updateTotals(subtotal);
            updateFloatingCartBadge(itemCount);
        }
        
        function updateFloatingCartBadge(count) {
            const badge = document.getElementById('floatingCartBadge');
            if (badge) {
                badge.textContent = count;
                if (count > 0) {
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        }
        
        function toggleOrderSidebar() {
            document.getElementById('orderSidebar').classList.toggle('translate-x-full');
        }
        
        function updateTotals(subtotal) {
            const tax = subtotal * 0.10;
            const total = subtotal + tax;
            
            document.getElementById('subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            document.getElementById('tax').textContent = 'Rp ' + tax.toLocaleString('id-ID');
            document.getElementById('total').textContent = 'Rp ' + total.toLocaleString('id-ID');
        }
        
        function clearOrder(silent = false) {
            if (currentOrder.length > 0) {
                if (silent || confirm('Hapus semua item?')) {
                    currentOrder = [];
                    document.getElementById('customerName').value = '';
                    document.getElementById('tableSelect').value = '';
                    renderOrder();
                }
            }
        }
        
        let calculatedTotal = 0;

        function processOrder() {
            if (currentOrder.length === 0) {
                showNotification('error', 'Belum ada item');
                return;
            }
            
            const customerName = document.getElementById('customerName').value.trim();
            const tableSelect = document.getElementById('tableSelect');
            const tableId = tableSelect.value;
            const deliveryAddress = document.getElementById('kasirDeliveryAddress').value.trim();
            
            if (!customerName) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: 'Nama pelanggan harus diisi!',
                    confirmButtonColor: '#10b981'
                });
                return;
            }
            
            if (tableId === 'delivery' && !deliveryAddress) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: 'Alamat pengiriman harus diisi untuk pesanan Delivery!',
                    confirmButtonColor: '#10b981'
                });
                return;
            }

            // Calculate current total
            let subtotal = 0;
            currentOrder.forEach(item => {
                subtotal += item.price * item.quantity;
            });
            const tax = subtotal * 0.10;
            calculatedTotal = subtotal + tax;

            // Populate checkout modal values
            document.getElementById('modalCustomerName').textContent = customerName;
            
            let tableText = 'Take Away';
            if (tableId === 'delivery') {
                tableText = 'Delivery';
                document.getElementById('modalDeliveryAddressRow').classList.remove('hidden');
                document.getElementById('modalDeliveryAddressText').textContent = deliveryAddress;
            } else if (tableId) {
                tableText = tableSelect.options[tableSelect.selectedIndex].text.replace(/🪑\s*|Meja\s*/g, 'Meja ');
                document.getElementById('modalDeliveryAddressRow').classList.add('hidden');
            } else {
                document.getElementById('modalDeliveryAddressRow').classList.add('hidden');
            }
            
            document.getElementById('modalTableNumber').textContent = tableText;
            document.getElementById('modalTotalBill').textContent = 'Rp ' + calculatedTotal.toLocaleString('id-ID');
            
            // Set payment action to pay_later by default
            document.querySelector('input[name="payment_action"][value="pay_later"]').checked = true;
            document.getElementById('cashPaidAmount').value = '';
            
            togglePaymentInputs();
            generateQuickCash();
            
            // Open modal
            document.getElementById('checkoutModal').classList.remove('hidden');
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.add('hidden');
        }

        function togglePaymentInputs() {
            const actionVal = document.querySelector('input[name="payment_action"]:checked').value;
            const cashArea = document.getElementById('cashInputArea');
            
            // Remove active classes
            document.getElementById('optPayLater').className = "flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-green-600";
            document.getElementById('optPayNowCash').className = "flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-green-600";
            document.getElementById('optPayNowQRIS').className = "flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-green-600";
            
            if (actionVal === 'pay_later') {
                document.getElementById('optPayLater').className = "flex flex-col items-center justify-center p-3 border-2 border-green-600 rounded-lg cursor-pointer bg-green-50";
                cashArea.classList.add('hidden');
            } else if (actionVal === 'pay_now_cash') {
                document.getElementById('optPayNowCash').className = "flex flex-col items-center justify-center p-3 border-2 border-green-600 rounded-lg cursor-pointer bg-green-50";
                cashArea.classList.remove('hidden');
                calculateChange();
            } else if (actionVal === 'pay_now_qris') {
                document.getElementById('optPayNowQRIS').className = "flex flex-col items-center justify-center p-3 border-2 border-green-600 rounded-lg cursor-pointer bg-green-50";
                cashArea.classList.add('hidden');
            }
        }

        function calculateChange() {
            const cashInput = document.getElementById('cashPaidAmount').value;
            const cashPaid = parseFloat(cashInput) || 0;
            const change = Math.max(0, cashPaid - calculatedTotal);
            
            document.getElementById('cashChangeAmount').textContent = 'Rp ' + change.toLocaleString('id-ID');
        }

        function generateQuickCash() {
            const container = document.getElementById('quickCashButtons');
            container.innerHTML = '';
            
            // Generate sensible quick cash amounts
            const amounts = [
                calculatedTotal,
                Math.ceil(calculatedTotal / 10000) * 10000,
                Math.ceil(calculatedTotal / 20000) * 20000,
                Math.ceil(calculatedTotal / 50000) * 50000,
                Math.ceil(calculatedTotal / 100000) * 100000
            ];
            
            // Remove duplicates and filter smaller amounts
            const uniqueAmounts = [...new Set(amounts)].filter(amt => amt >= calculatedTotal);
            
            uniqueAmounts.slice(0, 4).forEach(amt => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'bg-white hover:bg-gray-100 border text-gray-700 text-xs font-bold py-1.5 px-3 rounded';
                btn.textContent = 'Rp ' + amt.toLocaleString('id-ID');
                btn.onclick = () => {
                    document.getElementById('cashPaidAmount').value = amt;
                    calculateChange();
                };
                container.appendChild(btn);
            });
        }

        async function submitOrder() {
            const customerName = document.getElementById('customerName').value;
            const tableId = document.getElementById('tableSelect').value;
            const deliveryAddress = document.getElementById('kasirDeliveryAddress').value.trim();
            const actionVal = document.querySelector('input[name="payment_action"]:checked').value;
            
            let paymentAction = 'pay_later';
            let paymentMethod = 'cash';
            let paidAmount = 0;
            
            if (actionVal === 'pay_now_cash') {
                paymentAction = 'pay_now';
                paymentMethod = 'cash';
                const cashInput = document.getElementById('cashPaidAmount').value;
                paidAmount = parseFloat(cashInput) || 0;
                
                if (paidAmount < calculatedTotal) {
                    alert('Uang yang dibayar kurang dari total tagihan!');
                    return;
                }
            } else if (actionVal === 'pay_now_qris') {
                paymentAction = 'pay_now';
                paymentMethod = 'qris';
                paidAmount = calculatedTotal;
            }
            
            // Check validation again (just in case)
            if (tableId === 'delivery' && !deliveryAddress) {
                Swal.fire('Error', 'Alamat pengiriman harus diisi', 'error');
                return;
            }

            // Disable button & show loading
            const btn = document.getElementById('btnConfirmPayment');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';
            btn.disabled = true;

            try {
                const response = await fetch('process_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        items: currentOrder.map(item => ({
                            id: item.id,
                            name: item.name,
                            price: item.price,
                            quantity: item.quantity,
                            notes: item.notes
                        })),
                        customer_name: customerName,
                        table_id: tableId || null,
                        delivery_address: deliveryAddress,
                        payment_action: paymentAction,
                        payment_method: paymentMethod,
                        paid_amount: paidAmount
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', 'Pesanan berhasil diproses!');
                    closeCheckoutModal();
                    clearOrder(true);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', result.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showNotification('error', 'Gagal memproses pesanan');
                console.error(error);
            }
        }

        async function serveAndComplete(orderId) {
            if (!confirm('Tandai pesanan ini sebagai selesai & sudah disajikan?')) return;
            
            const formData = new FormData();
            formData.append('action', 'complete_order');
            formData.append('order_id', orderId);
            
            try {
                const response = await fetch('process_payment.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', result.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('error', result.message || 'Gagal menyelesaikan pesanan');
                }
            } catch (error) {
                showNotification('error', 'Terjadi kesalahan');
                console.error(error);
        }
        
        function printReceipt(orderId) {
            window.open('<?= APP_URL ?>/admin/print_receipt.php?order=' + orderId, '_blank');
        }
        
        function searchMenu() {
            const searchValue = document.getElementById('searchMenu').value.toLowerCase();
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                const name = item.dataset.name;
                item.style.display = name.includes(searchValue) ? '' : 'none';
            });
        }
        
        function filterCategory(categoryId) {
            const menuItems = document.querySelectorAll('.menu-item');
            const buttons = document.querySelectorAll('.category-btn');
            
            buttons.forEach(btn => {
                btn.classList.remove('bg-emerald-600', 'text-white', 'shadow-sm');
                btn.classList.add('bg-stone-100', 'text-stone-700');
            });
            
            const targetBtn = event ? event.target.closest('button') : null;
            if (targetBtn) {
                targetBtn.classList.remove('bg-stone-100', 'text-stone-700');
                targetBtn.classList.add('bg-emerald-600', 'text-white', 'shadow-sm');
            }
            
            if (categoryId === 'all') {
                menuItems.forEach(item => item.style.display = '');
            } else {
                menuItems.forEach(item => {
                    item.style.display = item.dataset.category == categoryId ? '' : 'none';
                });
            }
        }
        
        function showActiveOrders() {
            document.getElementById('activeOrdersModal').classList.remove('hidden');
        }
        
        function closeActiveOrders() {
            document.getElementById('activeOrdersModal').classList.add('hidden');
        }
        
        function showNotification(type, message) {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500'
            };
            
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'times'}-circle mr-2"></i>${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.remove(), 3000);
        }

        function toggleKasirDeliveryAddress() {
            const select = document.getElementById('tableSelect');
            const container = document.getElementById('kasirDeliveryAddressContainer');
            if (select.value === 'delivery') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }
    </script>
    </div> <!-- Close main wrapper -->
<?php include '../includes/footer.php'; ?>
