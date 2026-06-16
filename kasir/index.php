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
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>POS Kasir - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center space-x-3">
                <i class="fas fa-cash-register text-2xl text-green-600"></i>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">POS Kasir</h1>
                    <p class="text-xs text-gray-500"><?= $user['full_name'] ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="showActiveOrders()" class="relative text-gray-600 hover:text-gray-800">
                    <i class="fas fa-list text-xl"></i>
                    <?php if (!empty($activeOrders)): ?>
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                        <?= count($activeOrders) ?>
                    </span>
                    <?php endif; ?>
                </button>
                <a href="../admin/logout.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-sign-out-alt text-xl"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex flex-col lg:flex-row h-screen">
        <!-- Menu Section (Left/Top) -->
        <div class="flex-1 overflow-hidden flex flex-col">
            <!-- Search & Category -->
            <div class="p-4 bg-white border-b">
                <input 
                    type="text" 
                    id="searchMenu"
                    placeholder="Cari menu..."
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 mb-3"
                    onkeyup="searchMenu()"
                >
                
                <div class="category-tabs flex space-x-2 overflow-x-auto">
                    <button onclick="filterCategory('all')" class="category-btn bg-green-600 text-white px-4 py-2 rounded-lg whitespace-nowrap font-semibold text-sm">
                        Semua
                    </button>
                    <?php foreach ($categories as $category): ?>
                    <button onclick="filterCategory(<?= $category['id'] ?>)" class="category-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg whitespace-nowrap font-semibold text-sm">
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
                        class="menu-item bg-white rounded-lg p-3 text-left hover:shadow-lg transition border-2 border-transparent hover:border-green-500"
                        data-name="<?= strtolower($menu['name']) ?>"
                        data-category="<?= $menu['category_id'] ?>"
                    >
                        <div class="bg-gradient-to-br from-green-400 to-blue-500 rounded-lg h-20 flex items-center justify-center mb-2">
                            <i class="fas fa-utensils text-white text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-sm text-gray-800 mb-1 line-clamp-2"><?= $menu['name'] ?></h3>
                        <p class="text-green-600 font-bold text-sm"><?= formatRupiah($menu['price']) ?></p>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Order Section (Right/Bottom) -->
        <div class="w-full lg:w-96 bg-white border-t lg:border-t-0 lg:border-l shadow-lg flex flex-col" style="max-height: 50vh; lg:max-height: 100vh;">
            <!-- Order Header -->
            <div class="p-4 border-b bg-green-50">
                <h2 class="font-bold text-lg mb-3">Pesanan Baru</h2>
                
                <!-- Table Selection -->
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Meja</label>
                    <select id="tableSelect" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">Take Away</option>
                        <?php foreach ($tables as $table): ?>
                        <option value="<?= $table['id'] ?>" <?= $table['status'] !== 'available' ? 'disabled' : '' ?>>
                            Meja <?= $table['table_number'] ?> <?= $table['status'] !== 'available' ? '(Terisi)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Customer Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Customer</label>
                    <input 
                        type="text" 
                        id="customerName"
                        placeholder="Masukkan nama"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                </div>
            </div>

            <!-- Order Items -->
            <div class="flex-1 overflow-y-auto p-4" id="orderItems">
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                    <p>Belum ada item</p>
                </div>
            </div>

            <!-- Order Footer -->
            <div class="p-4 border-t bg-gray-50">
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span id="subtotal">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Pajak (10%)</span>
                        <span id="tax">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-xl font-bold">
                        <span>Total</span>
                        <span class="text-green-600" id="total">Rp 0</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2">
                    <button 
                        onclick="clearOrder()"
                        class="bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-4 rounded-lg transition"
                    >
                        <i class="fas fa-trash mr-2"></i>Batal
                    </button>
                    <button 
                        onclick="processOrder()"
                        id="processBtn"
                        class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition disabled:opacity-50"
                        disabled
                    >
                        <i class="fas fa-check mr-2"></i>Proses
                    </button>
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
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600"><?= $order['item_count'] ?> item • <?= timeAgo($order['created_at']) ?></span>
                                <span class="font-bold text-green-600"><?= formatRupiah($order['total']) ?></span>
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
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                        <p>Belum ada item</p>
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
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-800 flex-1">${item.name}</h4>
                            <button onclick="removeItem(${item.id})" class="text-red-500 hover:text-red-700 ml-2">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 bg-white rounded px-2 py-1">
                                <button onclick="updateQuantity(${item.id}, -1)" class="text-green-600 hover:text-green-700 w-6">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="font-bold w-8 text-center">${item.quantity}</span>
                                <button onclick="updateQuantity(${item.id}, 1)" class="text-green-600 hover:text-green-700 w-6">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <span class="font-bold text-green-600">
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
        
        function clearOrder() {
            if (currentOrder.length > 0) {
                if (confirm('Hapus semua item?')) {
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
                    clearOrder();
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
    </script>
</body>
</html>
