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
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Menu - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-overflow-scrolling: touch;
        }
        .font-outfit {
            font-family: 'Outfit', sans-serif;
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
<body class="bg-stone-50 text-stone-900">
    <!-- Cover/Banner Image -->
    <div class="w-full h-44 sm:h-56 bg-cover bg-center relative" style="background-image: url('<?= APP_URL ?>/assets/img/warkop_banner.png');">
        <div class="absolute inset-0 bg-black/40 flex items-end p-4 sm:p-6">
            <div class="flex items-center gap-3">
                <div class="bg-white rounded-full p-0.5 shadow-lg w-16 h-16 sm:w-20 sm:h-20 overflow-hidden flex-shrink-0">
                    <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" alt="Logo" class="w-full h-full object-cover rounded-full">
                </div>
                <div>
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-white font-outfit tracking-tight"><?= APP_NAME ?></h1>
                    <p class="text-xs sm:text-sm text-stone-200 mt-0.5 sm:mt-1">Herbal Alami & Kesehatan Keluarga</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="sticky-header bg-stone-900 text-white shadow-lg">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <?php if ($tableNumber): ?>
                    <span class="bg-emerald-600 text-white text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wider flex items-center">
                        <i class="fas fa-chair mr-1.5"></i>Meja <?= $tableNumber ?>
                    </span>
                    <?php endif; ?>
                </div>
                <button onclick="toggleCart()" class="relative bg-stone-800 hover:bg-stone-700 p-2.5 rounded-full transition flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-xl text-emerald-400"></i>
                    <?php if (!empty($cart)): ?>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center animate-pulse">
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
                    placeholder="Cari kopi, cemilan, atau makanan..."
                    class="w-full px-4 py-2.5 pl-10 rounded-xl text-stone-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium"
                    onkeyup="searchMenu()"
                >
                <i class="fas fa-search absolute left-3.5 top-3.5 text-stone-400"></i>
            </div>
        </div>
        
        <!-- Category Tabs -->
        <div class="category-tabs flex space-x-2 px-4 pb-3 overflow-x-auto">
            <button onclick="filterCategory('all')" class="category-tab category-btn-all bg-emerald-600 text-white px-4 py-2 rounded-full whitespace-nowrap font-bold text-sm shadow-md transition">
                Semua Menu
            </button>
            <?php foreach ($categories as $category): ?>
            <button onclick="filterCategory(<?= $category['id'] ?>)" class="category-tab category-btn-<?= $category['id'] ?> bg-stone-800 text-stone-300 px-4 py-2 rounded-full whitespace-nowrap font-bold text-sm hover:bg-stone-750 transition">
                <?= $category['icon'] ?> <?= $category['name'] ?>
            </button>
            <?php endforeach; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-4 pb-32 sm:pb-24">
        <!-- Friendly Guide for Tech-Illiterate Users -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 mb-6 shadow-sm flex items-start gap-3">
            <div class="bg-emerald-600 text-white rounded-full p-2.5 flex-shrink-0">
                <i class="fas fa-info-circle text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-emerald-900 text-sm font-outfit">Cara Memesan Gampang:</h3>
                <p class="text-xs text-emerald-800 mt-1 leading-relaxed">
                    1. Klik tombol hijau <span class="font-bold text-emerald-950">+ Pesan</span> pada menu yang Anda sukai.<br>
                    2. Klik ikon <i class="fas fa-shopping-cart text-emerald-600"></i> <strong>Keranjang</strong> di kanan atas jika sudah selesai memilih.<br>
                    3. Isi nama Anda dan bayar di kasir/via QRIS.
                </p>
            </div>
        </div>

        <?php foreach ($categories as $category): ?>
            <?php if (isset($menusByCategory[$category['id']])): ?>
            <div class="category-section mb-8" data-category="<?= $category['id'] ?>">
                <div class="flex items-center mb-4">
                    <span class="text-2xl mr-2.5"><?= $category['icon'] ?></span>
                    <h2 class="text-xl font-extrabold text-stone-800 font-outfit tracking-tight"><?= $category['name'] ?></h2>
                </div>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <?php foreach ($menusByCategory[$category['id']] as $menu): ?>
                    <div class="menu-item menu-card bg-white rounded-2xl shadow-sm border border-stone-200 overflow-hidden hover:shadow-md hover:border-emerald-200 transition duration-300 flex flex-col justify-between" 
                         data-name="<?= strtolower($menu['name']) ?>" 
                         data-category="<?= $category['id'] ?>">
                        <div>
                            <!-- Image Container -->
                            <div class="bg-stone-100 h-28 sm:h-40 relative flex items-center justify-center overflow-hidden border-b border-stone-200">
                                <?php if ($menu['image']): ?>
                                    <img src="<?= UPLOADS_URL . '/' . $menu['image'] ?>" alt="<?= $menu['name'] ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-leaf text-stone-300 text-2xl sm:text-4xl"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-2.5 sm:p-4">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1 mb-2">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-stone-800 text-sm sm:text-base font-outfit line-clamp-1 sm:line-clamp-2"><?= $menu['name'] ?></h3>
                                        <?php if ($menu['description']): ?>
                                        <p class="text-[10px] sm:text-xs text-stone-500 mt-0.5 sm:mt-1 leading-normal line-clamp-2"><?= $menu['description'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($menu['is_recommended']): ?>
                                    <span class="bg-amber-100 text-amber-800 text-[9px] sm:text-xs px-2 py-0.5 rounded-full font-bold self-start flex items-center gap-0.5 whitespace-nowrap">
                                        <i class="fas fa-star text-[9px] sm:text-xs"></i> Laris
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="px-2.5 pb-2.5 sm:px-4 sm:pb-4 pt-1 flex items-center justify-between mt-auto">
                            <span class="font-extrabold text-stone-900 text-sm sm:text-base">
                                <?= formatRupiah($menu['price']) ?>
                            </span>
                            <button 
                                onclick="addToCart(<?= $menu['id'] ?>, '<?= addslashes($menu['name']) ?>', <?= $menu['price'] ?>)"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl text-xs sm:text-sm font-bold shadow-sm hover:shadow transition flex items-center space-x-1"
                            >
                                <i class="fas fa-plus text-[9px] sm:text-xs"></i>
                                <span>Pesan</span>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div id="noResults" class="hidden text-center py-16">
            <i class="fas fa-search text-6xl text-stone-300 mb-4"></i>
            <p class="text-stone-500 font-semibold font-outfit">Menu tidak ditemukan</p>
            <p class="text-xs text-stone-400 mt-1">Coba gunakan kata kunci pencarian yang lain.</p>
        </div>
    </main>

    <!-- Cart Sidebar -->
    <div id="cartSidebar" class="fixed inset-y-0 right-0 w-full sm:w-[380px] bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50 border-l border-stone-200">
        <div class="flex flex-col h-full">
            <!-- Cart Header -->
            <div class="bg-stone-900 text-white p-4 flex items-center justify-between shadow-md">
                <h2 class="text-lg font-bold font-outfit flex items-center">
                    <i class="fas fa-shopping-cart mr-2 text-emerald-400"></i>Keranjang Belanja
                </h2>
                <button onclick="toggleCart()" class="text-white hover:text-stone-300 p-1">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Cart Items -->
            <div id="cartItems" class="flex-1 overflow-y-auto p-4 space-y-3 bg-stone-50">
                <!-- Items will be loaded here -->
            </div>
            
            <!-- Cart Footer -->
            <div class="border-t border-stone-200 p-4 bg-white shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-stone-700 font-bold text-sm uppercase tracking-wider">Total Tagihan</span>
                    <span id="cartTotal" class="text-xl font-extrabold text-emerald-600 font-outfit">Rp 0</span>
                </div>
                <button 
                    onclick="checkout()" 
                    id="checkoutBtn"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-4 rounded-xl transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed font-outfit shadow-md flex items-center justify-center gap-2"
                >
                    <i class="fas fa-check-circle"></i>Lanjutkan ke Checkout
                </button>
            </div>
        </div>
    </div>

    <!-- Cart Overlay -->
    <div id="cartOverlay" class="hidden fixed inset-0 bg-black/60 z-40" onclick="toggleCart()"></div>

    <!-- Floating Cart Button (Mobile) -->
    <?php if (!empty($cart)): ?>
    <button 
        onclick="toggleCart()"
        class="fixed bottom-6 right-6 bg-emerald-600 hover:bg-emerald-700 text-white w-14 h-14 rounded-full shadow-2xl flex items-center justify-center z-30 sm:hidden transition duration-200"
    >
        <i class="fas fa-shopping-cart text-xl"></i>
        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5.5 h-5.5 rounded-full flex items-center justify-center font-bold animate-pulse">
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
                    <div class="flex flex-col items-center justify-center py-16 text-stone-400">
                        <i class="fas fa-shopping-basket text-5xl mb-3 text-stone-300"></i>
                        <p class="font-bold font-outfit text-stone-500">Keranjang Kosong</p>
                        <p class="text-xs text-stone-400 mt-1">Pilih menu untuk ditambahkan</p>
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
                    <div class="bg-white border border-stone-200 rounded-xl p-3 shadow-sm transition duration-200">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-bold text-stone-850 text-sm font-outfit">${item.name}</h4>
                            <button onclick="removeFromCart(${item.id})" class="text-red-500 hover:text-red-700 p-1" title="Hapus Item">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 bg-stone-50 border border-stone-200 rounded-lg px-2 py-1">
                                <button onclick="updateQuantity(${item.id}, -1)" class="text-emerald-600 hover:text-emerald-700 w-6 h-6 flex items-center justify-center font-bold">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="font-extrabold text-stone-800 w-8 text-center text-sm">${item.quantity}</span>
                                <button onclick="updateQuantity(${item.id}, 1)" class="text-emerald-600 hover:text-emerald-700 w-6 h-6 flex items-center justify-center font-bold">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <span class="font-extrabold text-emerald-600 text-sm font-outfit">
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
                btn.classList.remove('bg-emerald-600', 'text-white', 'shadow-md');
                btn.classList.add('bg-stone-800', 'text-stone-300');
            });
            
            const activeBtn = categoryId === 'all' 
                ? document.querySelector('[onclick="filterCategory(\'all\')"]')
                : document.querySelector('.category-btn-' + categoryId);
            if (activeBtn) {
                activeBtn.classList.remove('bg-stone-800', 'text-stone-300');
                activeBtn.classList.add('bg-emerald-600', 'text-white', 'shadow-md');
            }
            
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
