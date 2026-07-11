<?php
require_once '../config/config.php';
requireRole(['owner']);

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
<?php
$pageTitle = 'POS Kasir';
include '../includes/header.php';
?>
        <!-- Main Wrapper -->
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
                    <!-- Mobile-only logout link -->
                    <a href="logout.php" class="md:hidden text-stone-500 hover:text-red-600 bg-stone-100 hover:bg-red-50 p-2.5 rounded-full flex items-center justify-center transition" title="Logout">
                        <i class="fas fa-sign-out-alt text-lg"></i>
                    </a>
                </div>
            </div>

            <!-- Main Content -->
    <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">
        <!-- Menu Section (Left/Top) -->
        <div class="flex-1 overflow-hidden flex flex-col">
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
                            <select id="tableSelect" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 font-semibold text-slate-700 appearance-none transition-all duration-300 cursor-pointer">
                                <option value="">🛒 Take Away / Bungkus</option>
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
            <div class="p-5 border-t border-slate-200 bg-white shadow-[0_-10px_30px_-15px_rgba(0,0,0,0.05)]">
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
    </div>
    
    <!-- Active Orders Modal -->
    <div id="activeOrdersModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between bg-green-50">
                <h3 class="font-bold text-lg">Pesanan Aktif</h3>
                <button onclick="closeActiveOrders()" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4 overflow-y-auto max-h-96">
                <?php if (empty($activeOrders)): ?>
                    <p class="text-center text-gray-500 py-8">Tidak ada pesanan aktif</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($activeOrders as $order): ?>
                        <div class="border rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="font-bold text-gray-800"><?= $order['order_number'] ?></p>
                                    <p class="text-sm text-gray-600">
                                        <?php if ($order['table_number']): ?>
                                            <i class="fas fa-chair mr-1"></i>Meja <?= $order['table_number'] ?>
                                        <?php else: ?>
                                            <i class="fas fa-shopping-bag mr-1"></i>Take Away
                                        <?php endif; ?>
                                        • <?= $order['customer_name'] ?>
                                    </p>
                                </div>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= getStatusBadge($order['status']) ?>">
                                    <?= getStatusText($order['status']) ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm mt-2 pt-2 border-t border-gray-100">
                                <span class="text-gray-600"><?= $order['item_count'] ?> item • <?= timeAgo($order['created_at']) ?></span>
                                <div class="flex items-center gap-2 lg:gap-3">
                                    <span class="font-bold text-green-600"><?= formatRupiah($order['total']) ?></span>
                                    <button onclick="cancelOrder(<?= $order['id'] ?>)" class="bg-red-50 hover:bg-red-100 text-red-600 px-2 lg:px-3 py-1.5 rounded-lg text-[10px] lg:text-xs font-bold transition flex items-center border border-red-200" title="Void Pesanan">
                                        <i class="fas fa-ban mr-1"></i> Void
                                    </button>
                                    <button onclick="window.open('print_receipt.php?order=<?= $order['id'] ?>', '_blank', 'width=400,height=600')" class="bg-stone-800 hover:bg-stone-900 text-white px-2 lg:px-3 py-1.5 rounded-lg text-[10px] lg:text-xs font-bold transition flex items-center shadow-sm">
                                        <i class="fas fa-print mr-1 text-emerald-400"></i> Print Struk
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-30 md:hidden">
        <div class="flex justify-around items-center py-2">
            <a href="index.php" class="flex flex-col items-center text-gray-600 hover:text-slate-700 py-2 px-3">
                <i class="fas fa-home text-lg"></i>
                <span class="text-xs mt-1">Home</span>
            </a>
            <a href="pos.php" class="flex flex-col items-center text-slate-700 py-2 px-3">
                <i class="fas fa-cash-register text-lg"></i>
                <span class="text-xs mt-1">POS</span>
            </a>
            <a href="kitchen.php" class="flex flex-col items-center text-gray-600 hover:text-slate-700 py-2 px-3">
                <i class="fas fa-fire text-lg"></i>
                <span class="text-xs mt-1">Dapur</span>
            </a>
            <a href="orders.php" class="flex flex-col items-center text-gray-600 hover:text-slate-700 py-2 px-3">
                <i class="fas fa-receipt text-lg"></i>
                <span class="text-xs mt-1">Orders</span>
            </a>
            <a href="menu.php" class="flex flex-col items-center text-gray-600 hover:text-slate-700 py-2 px-3">
                <i class="fas fa-utensils text-lg"></i>
                <span class="text-xs mt-1">Menu</span>
            </a>
        </div>
    </nav>

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
                    <p class="font-bold text-slate-500 font-outfit text-lg">Keranjang Kosong</p>
                    <p class="text-xs mt-1 text-slate-400">Yuk, pilih menu di sebelah kiri!</p>
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
        
        function clearOrder() {
            if (currentOrder.length > 0) {
                if (confirm('Batalkan input pesanan ini?')) {
                    currentOrder = [];
                    document.getElementById('customerName').value = '';
                    document.getElementById('tableSelect').value = '';
                    renderOrder();
                }
            }
        }
        
        async function processOrder() {
            if (currentOrder.length === 0) {
                showNotification('error', 'Belum ada item');
                return;
            }
            
            const customerName = document.getElementById('customerName').value.trim();
            if (!customerName) {
                showNotification('error', 'Nama customer harus diisi');
                return;
            }
            
            const tableId = document.getElementById('tableSelect').value;
            
            try {
                const response = await fetch('process_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        items: currentOrder,
                        customer_name: customerName,
                        table_id: tableId || null
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', 'Pesanan berhasil diproses!');
                    
                    // Open print receipt popup automatically
                    if (result.order_id) {
                        window.open('print_receipt.php?order=' + result.order_id, '_blank', 'width=400,height=600');
                    }
                    
                    currentOrder = [];
                    document.getElementById('customerName').value = '';
                    document.getElementById('tableSelect').value = '';
                    renderOrder();
                    
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
                btn.classList.remove('bg-green-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });
            
            event.target.classList.remove('bg-gray-200', 'text-gray-700');
            event.target.classList.add('bg-green-600', 'text-white');
            
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
        
        function cancelOrder(orderId) {
            if (!confirm('AWAS! Yakin mau Void pesanan ini? Stok akan dikembalikan dan meja dikosongkan.')) {
                return;
            }
            
            fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'order_id=' + orderId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('error', data.message);
                }
            })
            .catch(e => {
                showNotification('error', 'Terjadi kesalahan jaringan.');
                console.error(e);
            });
        }
    </script>
        </div>
    </div>
<?php include '../includes/footer.php'; ?>
