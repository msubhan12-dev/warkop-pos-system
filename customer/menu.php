<?php
require_once '../config/config.php';

// Get table number from query string (QR Code)
$tableNumber = $_GET['table'] ?? null;
$tableId = null;

if ($tableNumber) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, table_number, capacity, status FROM tables WHERE table_number = ? AND is_active = 1");
    $stmt->execute([$tableNumber]);
    $table = $stmt->fetch();
    
    if ($table) {
        $tableId = $table['id'];
        $_SESSION['customer_table_id'] = $tableId;
        $_SESSION['customer_table_number'] = $table['table_number'];
    }
}

// Get cart from session
$cart = $_SESSION['cart'] ?? [];
$cartTotal = 0;
foreach ($cart as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

// Get categories and menus
$db = getDB();
$stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");
$categories = $stmt->fetchAll();

$stmt = $db->query("
    SELECT m.*, c.name as category_name 
    FROM menus m
    JOIN categories c ON m.category_id = c.id
    WHERE m.is_available = 1
    ORDER BY c.sort_order, m.name
");
$menus = $stmt->fetchAll();

// Group menus by category
$menusByCategory = [];
foreach ($menus as $menu) {
    $menusByCategory[$menu['category_id']][] = $menu;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Menu - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            -webkit-overflow-scrolling: touch;
        }
        .sticky-header {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            z-index: 30;
        }
        .category-tab {
            scroll-snap-align: start;
        }
        .category-tabs {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .category-tabs::-webkit-scrollbar {
            display: none;
        }
        @media (max-width: 640px) {
            .menu-card {
                min-height: 120px;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="sticky-header bg-gradient-to-r from-slate-700 to-slate-800 text-white shadow-lg">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-coffee text-2xl"></i>
                    <div>
                        <h1 class="text-xl font-bold"><?= APP_NAME ?></h1>
                        <?php if ($tableNumber): ?>
                        <p class="text-xs text-slate-300">
                            <i class="fas fa-chair mr-1"></i>Meja <?= $tableNumber ?>
                        </p>
                        <?php else: ?>
                        <p class="text-xs text-slate-300">Take Away</p>
                        <?php endif; ?>
                    </div>
                </div>
                <button onclick="toggleCart()" class="relative">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                    <?php if (!empty($cart)): ?>
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold">
                        <?= count($cart) ?>
                    </span>
                    <?php endif; ?>
                </button>
            </div>
            
            <!-- Search -->
            <div class="relative">
                <input 
                    type="text" 
                    id="searchMenu"
                    placeholder="Cari menu..."
                    class="w-full px-4 py-2 pl-10 rounded-full text-gray-800 focus:outline-none focus:ring-2 focus:ring-slate-400"
                    onkeyup="searchMenu()"
                >
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
        </div>
        
        <!-- Category Tabs -->
        <div class="category-tabs flex space-x-2 px-4 pb-3 overflow-x-auto">
            <button onclick="filterCategory('all')" class="category-tab category-btn-all bg-white text-slate-700 px-4 py-2 rounded-full whitespace-nowrap font-semibold text-sm shadow">
                Semua
            </button>
            <?php foreach ($categories as $category): ?>
            <button onclick="filterCategory(<?= $category['id'] ?>)" class="category-tab category-btn-<?= $category['id'] ?> bg-slate-600 text-white px-4 py-2 rounded-full whitespace-nowrap font-semibold text-sm hover:bg-slate-500">
                <?= $category['icon'] ?> <?= $category['name'] ?>
            </button>
            <?php endforeach; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-4 pb-24">
        <?php foreach ($categories as $category): ?>
            <?php if (isset($menusByCategory[$category['id']])): ?>
            <div class="category-section mb-6" data-category="<?= $category['id'] ?>">
                <div class="flex items-center mb-3">
                    <span class="text-2xl mr-2"><?= $category['icon'] ?></span>
                    <h2 class="text-xl font-bold text-gray-800"><?= $category['name'] ?></h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($menusByCategory[$category['id']] as $menu): ?>
                    <div class="menu-item menu-card bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition" 
                         data-name="<?= strtolower($menu['name']) ?>" 
                         data-category="<?= $category['id'] ?>">
                        <!-- Image Placeholder -->
                        <div class="bg-gradient-to-br from-slate-400 to-slate-600 h-32 sm:h-40 flex items-center justify-center">
                            <?php if ($menu['image']): ?>
                                <img src="<?= UPLOADS_URL . '/' . $menu['image'] ?>" alt="<?= $menu['name'] ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-utensils text-white text-4xl"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-3">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-800 text-sm sm:text-base"><?= $menu['name'] ?></h3>
                                    <?php if ($menu['description']): ?>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-2"><?= $menu['description'] ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($menu['is_recommended']): ?>
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full ml-2 flex-shrink-0">
                                    <i class="fas fa-star"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex items-center justify-between mt-3">
                                <span class="font-bold text-slate-700 text-sm sm:text-base">
                                    <?= formatRupiah($menu['price']) ?>
                                </span>
                                <button 
                                    onclick="addToCart(<?= $menu['id'] ?>, '<?= addslashes($menu['name']) ?>', <?= $menu['price'] ?>)"
                                    class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center space-x-1"
                                >
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Pesan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div id="noResults" class="hidden text-center py-12">
            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">Tidak ada menu yang ditemukan</p>
        </div>
    </main>

    <!-- Cart Sidebar -->
    <div id="cartSidebar" class="fixed inset-y-0 right-0 w-full sm:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50">
        <div class="flex flex-col h-full">
            <!-- Cart Header -->
            <div class="bg-slate-700 text-white p-4 flex items-center justify-between">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-shopping-cart mr-2"></i>Keranjang
                </h2>
                <button onclick="toggleCart()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <!-- Cart Items -->
            <div id="cartItems" class="flex-1 overflow-y-auto p-4">
                <!-- Items will be loaded here -->
            </div>
            
            <!-- Cart Footer -->
            <div class="border-t p-4 bg-gray-50">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-700 font-semibold">Total</span>
                    <span id="cartTotal" class="text-2xl font-bold text-slate-700">Rp 0</span>
                </div>
                <button 
                    onclick="checkout()" 
                    id="checkoutBtn"
                    class="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-3 px-4 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="fas fa-check mr-2"></i>Checkout
                </button>
            </div>
        </div>
    </div>

    <!-- Cart Overlay -->
    <div id="cartOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40" onclick="toggleCart()"></div>

    <!-- Floating Cart Button (Mobile) -->
    <?php if (!empty($cart)): ?>
    <button 
        onclick="toggleCart()"
        class="fixed bottom-4 right-4 bg-slate-700 hover:bg-slate-800 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center z-30 sm:hidden"
    >
        <i class="fas fa-shopping-cart text-xl"></i>
        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center font-bold">
            <?= count($cart) ?>
        </span>
    </button>
    <?php endif; ?>

    <script>
        // Cart state
        let cart = <?= json_encode($cart) ?>;
        
        // Toggle cart sidebar
        function toggleCart() {
            const sidebar = document.getElementById('cartSidebar');
            const overlay = document.getElementById('cartOverlay');
            
            if (sidebar.classList.contains('translate-x-full')) {
                sidebar.classList.remove('translate-x-full');
                overlay.classList.remove('hidden');
                renderCart();
            } else {
                sidebar.classList.add('translate-x-full');
                overlay.classList.add('hidden');
            }
        }
        
        // Add to cart
        function addToCart(id, name, price) {
            const existingItem = cart.find(item => item.id === id);
            
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    quantity: 1
                });
            }
            
            saveCart();
            showNotification('success', name + ' ditambahkan ke keranjang');
        }
        
        // Update quantity
        function updateQuantity(id, change) {
            const item = cart.find(item => item.id === id);
            
            if (item) {
                item.quantity += change;
                
                if (item.quantity <= 0) {
                    removeFromCart(id);
                } else {
                    saveCart();
                    renderCart();
                }
            }
        }
        
        // Remove from cart
        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            saveCart();
            renderCart();
            
            if (cart.length === 0) {
                toggleCart();
            }
        }
        
        // Render cart
        function renderCart() {
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            const checkoutBtn = document.getElementById('checkoutBtn');
            
            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full text-gray-400">
                        <i class="fas fa-shopping-cart text-6xl mb-4"></i>
                        <p>Keranjang kosong</p>
                    </div>
                `;
                cartTotal.textContent = 'Rp 0';
                checkoutBtn.disabled = true;
                return;
            }
            
            let total = 0;
            let html = '<div class="space-y-3">';
            
            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                
                html += `
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-800">${item.name}</h4>
                            <button onclick="removeFromCart(${item.id})" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 bg-white rounded-lg px-2 py-1">
                                <button onclick="updateQuantity(${item.id}, -1)" class="text-slate-700 hover:text-slate-800 w-6 h-6 flex items-center justify-center">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="font-bold text-gray-800 w-8 text-center">${item.quantity}</span>
                                <button onclick="updateQuantity(${item.id}, 1)" class="text-slate-700 hover:text-slate-800 w-6 h-6 flex items-center justify-center">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <span class="font-bold text-slate-700">
                                Rp ${subtotal.toLocaleString('id-ID')}
                            </span>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            cartItems.innerHTML = html;
            cartTotal.textContent = 'Rp ' + total.toLocaleString('id-ID');
            checkoutBtn.disabled = false;
        }
        
        // Save cart to server
        function saveCart() {
            fetch('cart_update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ cart: cart })
            });
        }
        
        // Checkout
        function checkout() {
            if (cart.length === 0) {
                showNotification('error', 'Keranjang kosong');
                return;
            }
            
            window.location.href = 'checkout.php';
        }
        
        // Search menu
        function searchMenu() {
            const searchValue = document.getElementById('searchMenu').value.toLowerCase();
            const menuItems = document.querySelectorAll('.menu-item');
            let visibleCount = 0;
            
            menuItems.forEach(item => {
                const name = item.dataset.name;
                if (name.includes(searchValue)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            document.getElementById('noResults').classList.toggle('hidden', visibleCount > 0);
        }
        
        // Filter by category
        function filterCategory(categoryId) {
            const sections = document.querySelectorAll('.category-section');
            const buttons = document.querySelectorAll('[onclick^="filterCategory"]');
            
            // Update button styles
            buttons.forEach(btn => {
                btn.classList.remove('bg-white', 'text-slate-700', 'shadow');
                btn.classList.add('bg-slate-600', 'text-white');
            });
            
            const activeBtn = document.querySelector('.category-btn-' + categoryId);
            activeBtn.classList.remove('bg-slate-600', 'text-white');
            activeBtn.classList.add('bg-white', 'text-slate-700', 'shadow');
            
            // Show/hide sections
            if (categoryId === 'all') {
                sections.forEach(section => section.style.display = '');
            } else {
                sections.forEach(section => {
                    section.style.display = section.dataset.category == categoryId ? '' : 'none';
                });
            }
        }
        
        // Show notification
        function showNotification(type, message) {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };
            
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in`;
            notification.innerHTML = `<i class="fas fa-check-circle mr-2"></i>${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Initialize cart on page load
        if (cart.length > 0) {
            renderCart();
        }
    </script>
</body>
</html>
