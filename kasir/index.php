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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, sans-serif;
        }
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }
        .category-tabs {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .category-tabs::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="bg-stone-50 text-stone-900 h-screen flex flex-col overflow-hidden">
    <!-- Header -->
    <header class="bg-stone-900 text-white shadow-lg flex-none z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center space-x-3">
                <i class="fas fa-cash-register text-2xl text-emerald-400"></i>
                <div>
                    <h1 class="text-lg font-extrabold font-outfit text-white">POS Register</h1>
                    <p class="text-xs text-stone-300 font-medium"><?= $user['full_name'] ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-3.5">
                <button onclick="showActiveOrders()" class="relative text-white hover:text-emerald-400 bg-stone-800 p-2.5 rounded-full flex items-center justify-center transition" title="Pesanan Aktif">
                    <i class="fas fa-list text-lg"></i>
                    <?php if (!empty($activeOrders)): ?>
                    <span class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold">
                        <?= count($activeOrders) ?>
                    </span>
                    <?php endif; ?>
                </button>
                <a href="payments.php" class="text-white hover:text-emerald-400 bg-stone-800 p-2.5 rounded-full flex items-center justify-center transition" title="Verifikasi Pembayaran">
                    <i class="fas fa-credit-card text-lg"></i>
                </a>
                <a href="../admin/logout.php" class="text-white hover:text-red-400 bg-stone-800 p-2.5 rounded-full flex items-center justify-center transition" title="Logout">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">
        <!-- Menu Section (Left/Top) -->
        <div class="flex-1 overflow-hidden flex flex-col">
            <!-- Search & Category -->
            <div class="p-4 bg-white border-b border-stone-200">
                <div class="relative mb-3">
                    <input 
                        type="text" 
                        id="searchMenu"
                        placeholder="Cari obat herbal, madu, suplemen..."
                        class="w-full px-4 py-2.5 pl-10 border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium"
                        onkeyup="searchMenu()"
                    >
                    <i class="fas fa-search absolute left-3.5 top-3.5 text-stone-400"></i>
                </div>
                
                <div class="category-tabs flex space-x-2 overflow-x-auto">
                    <button onclick="filterCategory('all')" class="category-btn bg-emerald-600 text-white px-4 py-2 rounded-xl whitespace-nowrap font-bold text-sm shadow-sm transition">
                        Semua Menu
                    </button>
                    <?php foreach ($categories as $category): ?>
                    <button onclick="filterCategory(<?= $category['id'] ?>)" class="category-btn bg-stone-100 text-stone-700 px-4 py-2 rounded-xl whitespace-nowrap font-bold text-sm hover:bg-stone-200 transition">
                        <?= $category['icon'] ?> <?= $category['name'] ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Menu Grid -->
            <div class="flex-1 overflow-y-auto p-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3" id="menuGrid">
                    <?php foreach ($menus as $menu): ?>
                    <button 
                        onclick="addToOrder(<?= $menu['id'] ?>, '<?= addslashes($menu['name']) ?>', <?= $menu['price'] ?>)"
                        class="menu-item bg-white rounded-2xl p-3 text-left border border-stone-200 hover:border-emerald-250 hover:shadow-md transition flex flex-col justify-between"
                        data-name="<?= strtolower($menu['name']) ?>"
                        data-category="<?= $menu['category_id'] ?>"
                    >
                        <div class="bg-emerald-50 text-emerald-700 rounded-xl h-24 flex items-center justify-center mb-3">
                            <i class="fas fa-leaf text-3xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-stone-800 font-outfit mb-1.5 line-clamp-2"><?= $menu['name'] ?></h3>
                            <p class="text-emerald-600 font-extrabold text-sm"><?= formatRupiah($menu['price']) ?></p>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Order Section (Right/Bottom) -->
        <div class="w-full lg:w-[400px] bg-white border-t lg:border-t-0 lg:border-l border-stone-200 shadow-xl flex flex-col max-h-[50vh] lg:max-h-none">
            <!-- Order Header -->
            <div class="p-4 border-b border-stone-200 bg-emerald-50/50">
                <h2 class="font-extrabold text-lg text-emerald-950 font-outfit mb-3 flex items-center">
                    <i class="fas fa-shopping-basket mr-2 text-emerald-600"></i>Pesanan Baru
                </h2>
                
                <!-- Table Selection -->
                <div class="mb-3">
                    <label class="block text-xs font-bold text-stone-700 mb-1.5 uppercase tracking-wide">Meja / Dine In</label>
                    <select id="tableSelect" class="w-full px-3 py-2.5 border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium">
                        <option value="">Bungkus / Take Away</option>
                        <?php foreach ($tables as $table): ?>
                        <option value="<?= $table['id'] ?>" <?= $table['status'] !== 'available' ? 'disabled' : '' ?>>
                            Meja <?= $table['table_number'] ?> <?= $table['status'] !== 'available' ? '(Terisi)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Customer Name -->
                <div>
                    <label class="block text-xs font-bold text-stone-700 mb-1.5 uppercase tracking-wide">Nama Pelanggan</label>
                    <input 
                        type="text" 
                        id="customerName"
                        placeholder="Masukkan nama customer"
                        class="w-full px-3 py-2.5 border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium"
                    >
                </div>
            </div>

            <!-- Order Items -->
            <div class="flex-1 overflow-y-auto p-4" id="orderItems">
                <div class="text-center py-12 text-stone-400">
                    <i class="fas fa-shopping-cart text-5xl mb-3"></i>
                    <p class="font-bold font-outfit">Belum Ada Item</p>
                    <p class="text-xs text-stone-400 mt-1">Pilih menu di sebelah kiri untuk ditambahkan</p>
                </div>
            </div>

            <!-- Order Footer -->
            <div class="p-4 pb-32 sm:pb-8 border-t border-stone-200 bg-stone-50">
                <div class="space-y-2.5 mb-4 text-sm">
                    <div class="flex justify-between text-stone-500" style="display: none;">
                        <span>Subtotal</span>
                        <span id="subtotal">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-stone-500" style="display: none;">
                        <span>Pajak (10%)</span>
                        <span id="tax">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-lg font-extrabold text-stone-900 border-t border-dashed border-stone-300 pt-2 font-outfit">
                        <span>Total Tagihan</span>
                        <span class="text-emerald-600" id="total">Rp 0</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2.5">
                    <button 
                        onclick="clearOrder()"
                        class="bg-stone-200 hover:bg-stone-300 text-stone-700 font-bold py-3.5 px-4 rounded-xl transition duration-200 text-sm flex items-center justify-center gap-1.5"
                    >
                        <i class="fas fa-times-circle"></i>Batal
                    </button>
                    <button 
                        onclick="processOrder()"
                        id="processBtn"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-4 rounded-xl transition duration-200 text-sm disabled:opacity-50 flex items-center justify-center gap-1.5 shadow-md hover:shadow"
                        disabled
                    >
                        <i class="fas fa-check-circle"></i>Proses
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
                    <div class="text-center py-12 text-stone-400">
                        <i class="fas fa-shopping-cart text-5xl mb-3 text-stone-300"></i>
                        <p class="font-bold font-outfit text-stone-500">Belum Ada Item</p>
                        <p class="text-xs text-stone-400 mt-1">Pilih menu di sebelah kiri untuk ditambahkan</p>
                    </div>
                `;
                processBtn.disabled = true;
                updateTotals(0);
                return;
            }
            
            let html = '<div class="space-y-3">';
            let subtotal = 0;
            
            currentOrder.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                html += `
                    <div class="bg-stone-50 border border-stone-200 rounded-xl p-3 shadow-sm transition duration-200">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-bold text-stone-800 text-sm font-outfit flex-1">${item.name}</h4>
                            <button onclick="removeItem(${item.id})" class="text-red-500 hover:text-red-700 ml-2" title="Hapus Item">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 bg-white border border-stone-200 rounded-lg px-2 py-1">
                                <button onclick="updateQuantity(${item.id}, -1)" class="text-emerald-600 hover:text-emerald-700 w-6 font-bold">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="font-extrabold w-8 text-center text-stone-800 text-sm">${item.quantity}</span>
                                <button onclick="updateQuantity(${item.id}, 1)" class="text-emerald-600 hover:text-emerald-700 w-6 font-bold">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <span class="font-extrabold text-emerald-600 text-sm font-outfit">
                                Rp ${itemTotal.toLocaleString('id-ID')}
                            </span>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
            processBtn.disabled = false;
            updateTotals(subtotal);
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
            if (!customerName) {
                showNotification('error', 'Nama customer harus diisi');
                return;
            }
            
            const tableSelect = document.getElementById('tableSelect');
            const tableName = tableSelect.options[tableSelect.selectedIndex].text;
            const tableId = tableSelect.value;

            // Calculate current total
            let subtotal = 0;
            currentOrder.forEach(item => {
                subtotal += item.price * item.quantity;
            });
            const tax = subtotal * 0.10;
            calculatedTotal = subtotal + tax;

            // Populate checkout modal values
            document.getElementById('modalCustomerName').textContent = customerName;
            document.getElementById('modalTableNumber').textContent = tableId ? tableName : 'Take Away';
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
            const customerName = document.getElementById('customerName').value.trim();
            const tableId = document.getElementById('tableSelect').value;
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
            
            try {
                const response = await fetch('process_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        items: currentOrder,
                        customer_name: customerName,
                        table_id: tableId || null,
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
    </script>
</body>
</html>
