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
<body class="bg-[#0B1121] text-slate-100 selection:bg-emerald-500/30">
    <!-- Cover/Banner Image -->
    <div class="w-full h-48 sm:h-64 bg-cover bg-center relative rounded-b-[2.5rem] shadow-[0_10px_40px_-10px_rgba(16,185,129,0.2)] overflow-hidden mb-6" style="background-image: url('<?= APP_URL ?>/assets/img/warkop_banner.png');">
        <!-- Dark gradient overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-[#0B1121] via-[#0B1121]/60 to-transparent flex items-end p-5 sm:p-8">
            <div class="flex items-center gap-4 relative z-10">
                <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-1 shadow-2xl w-20 h-20 sm:w-24 sm:h-24 overflow-hidden flex-shrink-0 border border-white/10">
                    <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" alt="Logo" class="w-full h-full object-cover rounded-xl">
                </div>
                <div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-white font-outfit tracking-tight drop-shadow-lg"><?= APP_NAME ?></h1>
                    <p class="text-sm sm:text-base text-emerald-400 mt-1 font-medium drop-shadow-sm flex items-center gap-1">
                        <i class="fas fa-leaf text-xs"></i> Herbal Alami & Kesehatan Keluarga
                    </p>
                </div>
            </div>
            
            <!-- Glow effect behind text -->
            <div class="absolute bottom-5 left-5 w-40 h-20 bg-emerald-500/20 blur-3xl rounded-full"></div>
        </div>
    </div>

    <!-- Header / Glass Search & Categories -->
    <header class="sticky-header bg-slate-800/60 backdrop-blur-xl text-slate-200 shadow-lg z-30 mx-4 sm:mx-6 mt-[-2rem] rounded-3xl border border-slate-700/50 mb-6 transition-all duration-300">
        <div class="px-5 py-4">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <?php if ($tableNumber): ?>
                    <span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-extrabold px-4 py-2 rounded-full uppercase tracking-wider flex items-center shadow-[0_0_15px_rgba(16,185,129,0.15)]">
                        <i class="fas fa-chair mr-2"></i>Meja <?= $tableNumber ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <a href="#" target="_blank" class="relative bg-slate-700/50 hover:bg-red-500/20 p-2 sm:p-3 rounded-full transition-colors flex items-center justify-center text-red-500 hover:text-red-400 shadow-inner group border border-slate-600/50" title="Pesan via GoFood">
                        <i class="fas fa-motorcycle text-lg group-hover:scale-110 transition-all"></i>
                    </a>
                    <a href="#" target="_blank" class="relative bg-slate-700/50 hover:bg-orange-500/20 p-2 sm:p-3 rounded-full transition-colors flex items-center justify-center text-orange-500 hover:text-orange-400 shadow-inner group border border-slate-600/50" title="Pesan via ShopeeFood">
                        <i class="fas fa-shopping-bag text-lg group-hover:scale-110 transition-all"></i>
                    </a>
                    <a href="https://instagram.com/warkop_os" target="_blank" class="relative bg-slate-700/50 hover:bg-pink-500/20 p-2 sm:p-3 rounded-full transition-colors flex items-center justify-center text-pink-500 hover:text-pink-400 shadow-inner group border border-slate-600/50" title="Follow Instagram Kami">
                        <i class="fab fa-instagram text-lg group-hover:scale-110 transition-all"></i>
                    </a>
                    <div class="w-px h-6 bg-slate-600/50 mx-1"></div> <!-- Divider -->
                    <button onclick="toggleCart()" class="relative bg-slate-700/50 hover:bg-emerald-500/20 p-2 sm:p-3 rounded-full transition-colors flex items-center justify-center text-emerald-500 hover:text-emerald-400 shadow-inner group border border-slate-600/50">
                        <i class="fas fa-shopping-basket text-lg sm:text-xl group-hover:scale-110 transition-all"></i>
                        <?php if (!empty($cart)): ?>
                        <span class="absolute -top-1 -right-1 bg-gradient-to-tr from-emerald-600 to-emerald-400 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center shadow-[0_0_10px_rgba(52,211,153,0.5)] animate-bounce border border-emerald-300">
                            <?= count($cart) ?>
                        </span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
            
            <!-- Search -->
            <div class="relative group">
                <input 
                    type="text" 
                    id="searchMenu"
                    placeholder="Cari menu kesukaanmu..."
                    class="w-full px-5 py-3 pl-12 bg-slate-900/50 border border-slate-700/50 rounded-2xl text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 focus:bg-slate-900 font-medium transition-all shadow-inner"
                    onkeyup="searchMenu()"
                >
                <i class="fas fa-search absolute left-4 top-3.5 text-slate-400 text-lg group-focus-within:text-emerald-400 transition-colors"></i>
            </div>
        </div>
        
        <!-- Category Tabs -->
        <div class="category-tabs flex space-x-3 px-5 pb-4 overflow-x-auto">
            <button onclick="filterCategory('all')" class="category-tab category-btn-all bg-emerald-600 text-white px-5 py-2.5 rounded-full whitespace-nowrap font-bold text-sm shadow-[0_4px_15px_-3px_rgba(16,185,129,0.4)] transition-all hover:-translate-y-0.5 border border-emerald-500">
                Semua Menu
            </button>
            <?php foreach ($categories as $category): ?>
            <button onclick="filterCategory(<?= $category['id'] ?>)" class="category-tab category-btn-<?= $category['id'] ?> bg-slate-800 text-slate-300 border border-slate-700/80 px-5 py-2.5 rounded-full whitespace-nowrap font-bold text-sm hover:bg-slate-700 hover:text-emerald-400 hover:border-slate-600 transition-all shadow-sm">
                <?= $category['icon'] ?> <?= $category['name'] ?>
            </button>
            <?php endforeach; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="px-4 sm:px-6 pb-32 sm:pb-24 max-w-7xl mx-auto">
        <!-- Delivery Buttons -->

        <!-- Friendly Guide -->
        <div class="bg-gradient-to-r from-emerald-900/30 to-teal-900/30 border border-emerald-500/20 rounded-3xl p-5 mb-8 shadow-lg backdrop-blur-sm flex items-start gap-4">
            <div class="bg-emerald-500/20 text-emerald-400 rounded-full p-3 shadow-inner flex-shrink-0 mt-1 border border-emerald-500/20">
                <i class="fas fa-lightbulb text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-emerald-400 text-sm font-outfit mb-1 drop-shadow-sm">Cara Memesan Gampang:</h3>
                <p class="text-xs text-emerald-200/70 leading-relaxed font-medium">
                    1. Klik tombol hijau <span class="bg-emerald-600/80 text-white px-1.5 py-0.5 rounded text-[10px] mx-0.5 border border-emerald-500/50">+ Pesan</span> pada menu yang Anda sukai.<br>
                    2. Klik ikon <i class="fas fa-shopping-basket text-emerald-400 mx-0.5"></i> keranjang di kanan atas jika sudah selesai.<br>
                    3. Isi nama Anda dan bayar langsung di kasir atau via QRIS.
                </p>
            </div>
        </div>

        <?php foreach ($categories as $category): ?>
            <?php if (isset($menusByCategory[$category['id']])): ?>
            <div class="category-section mb-10" data-category="<?= $category['id'] ?>">
                <div class="flex items-center mb-5 pl-2">
                    <span class="text-2xl mr-3 bg-slate-800/80 backdrop-blur-sm w-10 h-10 rounded-full flex items-center justify-center shadow-lg border border-slate-700/50"><?= $category['icon'] ?></span>
                    <h2 class="text-2xl font-extrabold text-slate-100 font-outfit tracking-tight drop-shadow-md"><?= $category['name'] ?></h2>
                </div>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    <?php foreach ($menusByCategory[$category['id']] as $menu): ?>
                    <div class="menu-item menu-card group bg-slate-800/40 backdrop-blur-md rounded-3xl shadow-lg border border-slate-700/50 overflow-hidden hover:shadow-[0_15px_30px_-10px_rgba(16,185,129,0.3)] hover:-translate-y-1 hover:border-emerald-500/40 transition-all duration-300 flex flex-col justify-between relative cursor-pointer" 
                         data-name="<?= strtolower($menu['name']) ?>" 
                         data-category="<?= $category['id'] ?>"
                         onclick="showMenuDetail(<?= $menu['id'] ?>, '<?= addslashes($menu['name']) ?>', <?= $menu['price'] ?>, '<?= $menu['image'] ? UPLOADS_URL . '/' . $menu['image'] : '' ?>', '<?= htmlspecialchars(addslashes(str_replace(array("\r", "\n"), '', $menu['description']))) ?>')">
                        <div>
                            <!-- Image Container -->
                            <div class="bg-slate-900/60 h-32 sm:h-44 relative flex items-center justify-center overflow-hidden">
                                <?php if ($menu['image']): ?>
                                    <img src="<?= UPLOADS_URL . '/' . $menu['image'] ?>" alt="<?= $menu['name'] ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 opacity-90 group-hover:opacity-100">
                                <?php else: ?>
                                    <i class="fas fa-mug-hot text-slate-700 text-4xl group-hover:scale-110 transition-transform duration-500"></i>
                                <?php endif; ?>
                                
                                <!-- Floating Add Button (Visual Only now, click handled by card) -->
                                <div class="absolute bottom-3 right-3 bg-slate-800/80 backdrop-blur-md text-emerald-400 w-10 h-10 rounded-full flex items-center justify-center shadow-lg transition-colors duration-300 z-10 border border-slate-600/50 group-hover:bg-emerald-500 group-hover:text-white group-hover:border-emerald-400">
                                    <i class="fas fa-plus text-sm drop-shadow-sm"></i>
                                </div>
                                
                                <?php if ($menu['is_recommended']): ?>
                                <span class="absolute top-3 left-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] px-2.5 py-1 rounded-full font-bold shadow-[0_0_10px_rgba(245,158,11,0.5)] flex items-center gap-1 z-10 border border-amber-400/30">
                                    <i class="fas fa-star text-[9px]"></i> Laris
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-4 pt-4">
                                <h3 class="font-bold text-slate-100 text-sm sm:text-base font-outfit line-clamp-2 leading-snug group-hover:text-emerald-400 transition-colors drop-shadow-sm"><?= $menu['name'] ?></h3>
                                <?php if ($menu['description']): ?>
                                <p class="text-[11px] sm:text-xs text-slate-400 mt-1.5 leading-relaxed line-clamp-2"><?= $menu['description'] ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="px-4 pb-4 mt-auto">
                            <span class="font-extrabold text-emerald-400 text-base sm:text-lg drop-shadow-[0_0_8px_rgba(52,211,153,0.3)]">
                                <?= formatRupiah($menu['price']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div id="noResults" class="hidden text-center py-20">
            <div class="w-24 h-24 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-5 border border-slate-700/50">
                <i class="fas fa-search text-4xl text-slate-500"></i>
            </div>
            <p class="text-slate-300 font-bold font-outfit text-lg">Menu tidak ditemukan</p>
            <p class="text-sm text-slate-500 mt-1 font-medium">Coba gunakan kata kunci pencarian yang lain.</p>
        </div>
    </main>

    <!-- Grand Opening Promo Modal -->
    <div id="promoModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 hidden pointer-events-none opacity-0 transition-opacity duration-300">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closePromoModal()"></div>
        
        <!-- Modal Content -->
        <div class="bg-slate-800 border border-slate-700/50 rounded-[2rem] w-full max-w-sm shadow-2xl pointer-events-auto transform scale-95 transition-transform duration-300 relative overflow-hidden flex flex-col" id="promoModalContent">
            <!-- Close button -->
            <button onclick="closePromoModal()" class="absolute top-4 right-4 z-10 bg-black/40 hover:bg-black/60 backdrop-blur-md text-white w-8 h-8 rounded-full flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
            
            <!-- Image Header -->
            <div class="w-full h-48 bg-gradient-to-br from-emerald-600 to-teal-800 relative flex items-center justify-center p-6">
                <!-- Decorative elements -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl -mr-10 -mt-10"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-black/20 rounded-full blur-xl -ml-5 -mb-5"></div>
                
                <div class="text-center relative z-10">
                    <span class="inline-block bg-amber-500 text-amber-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest mb-3 shadow-[0_0_15px_rgba(245,158,11,0.4)] border border-amber-400">Spesial Grand Opening</span>
                    <h3 class="text-3xl font-extrabold text-white font-outfit drop-shadow-lg leading-tight">FREE Wedang Jahe</h3>
                </div>
            </div>
            
            <div class="p-6 text-center bg-slate-800 relative">
                <!-- Gift Icon overlay -->
                <div class="absolute -top-8 left-1/2 -translate-x-1/2 w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center shadow-[0_10px_25px_-5px_rgba(0,0,0,0.5)] border border-slate-700/80">
                    <i class="fas fa-gift text-2xl text-emerald-400 animate-bounce drop-shadow-sm"></i>
                </div>
                
                <div class="pt-6">
                    <p class="text-slate-300 text-sm font-medium leading-relaxed mb-6">
                        Khusus hari ini! Nikmati kehangatan Wedang Jahe khas kami secara <strong>GRATIS</strong> untuk setiap pemesanan.
                    </p>
                    
                    <button onclick="claimPromo()" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3.5 px-6 rounded-2xl shadow-[0_8px_20px_-6px_rgba(16,185,129,0.5)] transition-all flex items-center justify-center gap-2 font-outfit text-lg hover:-translate-y-0.5">
                        Klaim Promo Sekarang
                    </button>
                    <button onclick="closePromoModal()" class="w-full mt-3 text-slate-500 hover:text-slate-400 text-sm font-medium transition-colors">
                        Mungkin Nanti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Sidebar -->
    <div id="cartSidebar" class="fixed inset-y-0 right-0 w-full sm:w-[400px] bg-[#0B1121] shadow-2xl transform translate-x-full transition-transform duration-300 z-[60] border-l border-slate-800">
        <div class="flex flex-col h-full">
            <!-- Cart Header -->
            <div class="bg-slate-800/80 backdrop-blur-md text-slate-100 p-5 flex items-center justify-between shadow-md border-b border-slate-700 z-10">
                <h2 class="text-xl font-extrabold font-outfit flex items-center drop-shadow-sm">
                    <i class="fas fa-shopping-basket mr-3 text-emerald-400"></i>Keranjang Anda
                </h2>
                <button onclick="toggleCart()" class="text-slate-400 hover:text-emerald-400 bg-slate-800 hover:bg-slate-700 border border-slate-700 w-8 h-8 rounded-full flex items-center justify-center transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Cart Items -->
            <div id="cartItems" class="flex-1 overflow-y-auto p-5 scrollbar-hide">
                <!-- Items will be loaded here -->
            </div>
            
            <!-- Cart Footer -->
            <div class="border-t border-slate-700 p-4 bg-slate-800/90 backdrop-blur-lg shadow-[0_-10px_20px_-5px_rgba(0,0,0,0.3)]">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-slate-400 font-bold text-sm uppercase tracking-wider">Total Tagihan</span>
                    <span id="cartTotal" class="text-xl font-extrabold text-emerald-400 font-outfit drop-shadow-[0_0_8px_rgba(52,211,153,0.3)]">Rp 0</span>
                </div>
                <button 
                    onclick="checkout()" 
                    id="checkoutBtn"
                    class="w-full bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-bold py-3.5 px-4 rounded-xl transition duration-300 disabled:opacity-50 disabled:grayscale font-outfit shadow-[0_8px_20px_-6px_rgba(16,185,129,0.5)] hover:shadow-[0_12px_25px_-6px_rgba(16,185,129,0.6)] flex items-center justify-center gap-2 hover:-translate-y-0.5"
                >
                    <i class="fas fa-check-circle"></i>Lanjutkan ke Checkout
                </button>
            </div>
        </div>
    </div>

    <!-- Overlay for Sidebar & Modal -->
    <div id="cartOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-[55] transition-opacity" onclick="closeAll()"></div>

    <!-- Menu Detail Modal -->
    <div id="menuModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 hidden pointer-events-none opacity-0 transition-opacity duration-300">
        <div class="bg-slate-800 border border-slate-700/80 rounded-3xl w-full max-w-md max-h-[90vh] overflow-y-auto shadow-2xl pointer-events-auto transform scale-95 transition-transform duration-300 relative" id="menuModalContent">
            <!-- Close button -->
            <button onclick="closeModal()" class="absolute top-4 right-4 z-10 bg-black/50 hover:bg-black/80 backdrop-blur-md text-white border border-white/10 w-8 h-8 rounded-full flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
            
            <!-- Header Image -->
            <div class="w-full h-48 sm:h-56 bg-slate-900 relative">
                <img id="modalImage" src="" alt="Menu Image" class="w-full h-full object-cover hidden opacity-90">
                <div id="modalImageFallback" class="w-full h-full flex items-center justify-center bg-slate-900 hidden">
                    <i class="fas fa-utensils text-4xl text-slate-700"></i>
                </div>
                <!-- Gradient overlay at bottom of image to blend with content -->
                <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-slate-800 to-transparent"></div>
            </div>
            
            <div class="p-6">
                <div class="flex justify-between items-start mb-2">
                    <h3 id="modalTitle" class="text-2xl font-extrabold text-slate-100 font-outfit pr-4 drop-shadow-sm">Nama Menu</h3>
                    <span id="modalPrice" class="font-extrabold text-emerald-400 text-lg whitespace-nowrap drop-shadow-[0_0_8px_rgba(52,211,153,0.3)]">Rp 0</span>
                </div>
                
                <p id="modalDesc" class="text-slate-400 text-sm leading-relaxed mb-6 hidden"></p>
                
                <!-- Notes Input -->
                <div class="mb-6">
                    <label for="modalNotes" class="block text-sm font-bold text-slate-300 mb-2 font-outfit">
                        <i class="fas fa-pen-alt text-emerald-500 mr-1"></i> Catatan Khusus
                    </label>
                    <textarea id="modalNotes" rows="2" class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700/80 rounded-2xl text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 resize-none transition-all placeholder-slate-600 shadow-inner" placeholder="Contoh: Es dipisah, gula dikit, pedas sedang..."></textarea>
                </div>
                
                <!-- Action Area -->
                <div class="flex items-center gap-4">
                    <!-- Quantity -->
                    <div class="flex items-center space-x-1 bg-slate-900/60 border border-slate-700/80 rounded-2xl p-1.5 shadow-inner">
                        <button onclick="updateModalQty(-1)" class="w-10 h-10 rounded-xl flex items-center justify-center bg-slate-800 text-emerald-400 hover:bg-slate-700 hover:text-emerald-300 shadow-sm transition-all font-bold border border-slate-700/50">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <span id="modalQty" class="font-extrabold text-slate-100 w-10 text-center text-lg">1</span>
                        <button onclick="updateModalQty(1)" class="w-10 h-10 rounded-xl flex items-center justify-center bg-slate-800 text-emerald-400 hover:bg-slate-700 hover:text-emerald-300 shadow-sm transition-all font-bold border border-slate-700/50">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                    
                    <!-- Add Button -->
                    <button onclick="submitModalCart()" class="flex-1 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-extrabold py-3.5 px-6 rounded-2xl shadow-[0_8px_20px_-6px_rgba(16,185,129,0.5)] hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-shopping-basket"></i> Tambah
                    </button>
                </div>
            </div>
            
            <!-- Hidden inputs -->
            <input type="hidden" id="modalMenuId">
            <input type="hidden" id="modalMenuPrice">
        </div>
    </div>

    <!-- Floating Cart Button (Mobile) -->
    <?php if (!empty($cart)): ?>
    <button 
        onclick="toggleCart()"
        class="fixed bottom-28 right-6 bg-emerald-600 hover:bg-emerald-700 text-white w-14 h-14 rounded-full shadow-2xl flex items-center justify-center z-[45] sm:hidden transition duration-200"
    >
        <i class="fas fa-shopping-cart text-xl"></i>
        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5.5 h-5.5 rounded-full flex items-center justify-center font-bold animate-pulse">
            <?= count($cart) ?>
        </span>
    </button>
    <?php endif; ?>

    <script>
        // Modals Logic
        let currentModalQty = 1;
        
        function showMenuDetail(id, name, price, image, desc) {
            document.getElementById('modalMenuId').value = id;
            document.getElementById('modalTitle').textContent = name;
            document.getElementById('modalMenuPrice').value = price;
            document.getElementById('modalPrice').textContent = 'Rp ' + price.toLocaleString('id-ID');
            
            // Image handling
            const imgEl = document.getElementById('modalImage');
            const fallbackEl = document.getElementById('modalImageFallback');
            if (image) {
                imgEl.src = image;
                imgEl.classList.remove('hidden');
                fallbackEl.classList.add('hidden');
            } else {
                imgEl.classList.add('hidden');
                fallbackEl.classList.remove('hidden');
            }
            
            // Description handling
            const descEl = document.getElementById('modalDesc');
            if (desc && desc.trim() !== '') {
                descEl.textContent = desc;
                descEl.classList.remove('hidden');
            } else {
                descEl.classList.add('hidden');
            }
            
            // Reset state
            document.getElementById('modalNotes').value = '';
            currentModalQty = 1;
            document.getElementById('modalQty').textContent = currentModalQty;
            
            // Show modal
            document.getElementById('cartOverlay').classList.remove('hidden');
            const modal = document.getElementById('menuModal');
            modal.classList.remove('hidden');
            // Small delay to allow display block to apply before animating opacity
            setTimeout(() => {
                modal.classList.remove('opacity-0', 'pointer-events-none');
                document.getElementById('menuModalContent').classList.remove('scale-95');
            }, 10);
        }
        
        function closeModal() {
            const modal = document.getElementById('menuModal');
            modal.classList.add('opacity-0');
            document.getElementById('menuModalContent').classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden', 'pointer-events-none');
                if (document.getElementById('cartSidebar').classList.contains('translate-x-full')) {
                    document.getElementById('cartOverlay').classList.add('hidden');
                }
            }, 300);
        }
        
        function closeAll() {
            closeModal();
            if (!document.getElementById('cartSidebar').classList.contains('translate-x-full')) {
                toggleCart();
            }
        }
        
        function updateModalQty(change) {
            currentModalQty += change;
            if (currentModalQty < 1) currentModalQty = 1;
            document.getElementById('modalQty').textContent = currentModalQty;
        }
        
        function submitModalCart() {
            const id = parseInt(document.getElementById('modalMenuId').value);
            const name = document.getElementById('modalTitle').textContent;
            const price = parseFloat(document.getElementById('modalMenuPrice').value);
            const notes = document.getElementById('modalNotes').value.trim();
            const qty = currentModalQty;
            
            addToCart(id, name, price, qty, notes);
            closeModal();
            
            // Tampilkan badge bounce effect
            const btnCart = document.querySelector('button[onclick="toggleCart()"]');
            btnCart.classList.add('animate-pulse');
            setTimeout(() => btnCart.classList.remove('animate-pulse'), 1000);
        }

        // Cart state
        let cart = <?= json_encode($cart) ?>;
        // Ensure legacy cart items have cartItemId
        cart = cart.map(item => {
            if (!item.cartItemId) {
                item.cartItemId = item.id + '_' + Date.now() + Math.random().toString(36).substr(2, 5);
                item.notes = '';
            }
            return item;
        });
        
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
                if (document.getElementById('menuModal').classList.contains('hidden')) {
                    overlay.classList.add('hidden');
                }
            }
        }
        
        // Add to cart
        function addToCart(id, name, price, quantity = 1, notes = '') {
            // Check if exact same item (id + notes) exists
            const existingItem = cart.find(item => item.id === id && item.notes === notes);
            
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({
                    cartItemId: id + '_' + Date.now(),
                    id: id,
                    name: name,
                    price: price,
                    quantity: quantity,
                    notes: notes
                });
            }
            
            saveCart();
            showNotification('success', name + ' ditambahkan ke keranjang');
        }
        
        // Update quantity
        function updateQuantity(cartItemId, change) {
            const item = cart.find(item => item.cartItemId === cartItemId);
            
            if (item) {
                item.quantity += change;
                
                if (item.quantity <= 0) {
                    removeFromCart(cartItemId);
                } else {
                    saveCart();
                    renderCart();
                }
            }
        }
        
        // Remove from cart
        function removeFromCart(cartItemId) {
            cart = cart.filter(item => item.cartItemId !== cartItemId);
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
                    <div class="flex flex-col items-center justify-center py-20 text-slate-400">
                        <div class="w-24 h-24 bg-slate-800/50 rounded-full flex items-center justify-center mb-4 border border-slate-700/80 shadow-inner">
                            <i class="fas fa-shopping-basket text-4xl text-slate-600"></i>
                        </div>
                        <p class="font-bold text-lg font-outfit text-slate-300">Keranjang Kosong</p>
                        <p class="text-sm">Silakan pilih menu favorit Anda</p>
                    </div>
                `;
                
                // Update badge if any
                const badge = document.querySelector('button[onclick="toggleCart()"] span');
                if (badge) badge.remove();
                
                cartTotal.textContent = 'Rp 0';
                checkoutBtn.disabled = true;
                return;
            }
            
            let total = 0;
            let totalItems = 0;
            let html = '<div class="space-y-4">';
            
            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                totalItems += item.quantity;
                
                let notesHtml = '';
                if (item.notes && item.notes.trim() !== '') {
                    notesHtml = `<p class="text-xs text-slate-400 mt-1 bg-slate-800/80 p-1.5 rounded-lg border border-slate-700/50 flex items-start gap-1"><i class="fas fa-pen-alt text-[10px] text-emerald-500 mt-0.5"></i> ${item.notes}</p>`;
                }
                
                html += `
                    <div class="bg-slate-900/60 border border-slate-700/80 rounded-2xl p-4 shadow-sm hover:shadow-md hover:border-emerald-500/30 transition-all duration-300 group">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 pr-4">
                                <h4 class="font-bold text-slate-200 text-base font-outfit drop-shadow-sm">${item.name}</h4>
                                ${notesHtml}
                            </div>
                            <button onclick="removeFromCart('${item.cartItemId}')" class="text-slate-400 hover:text-red-400 bg-slate-800 hover:bg-red-900/30 w-7 h-7 rounded-full flex items-center justify-center transition-colors flex-shrink-0 border border-slate-700 hover:border-red-500/30 shadow-sm" title="Hapus Item">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                        <div class="flex items-end justify-between">
                            <div class="flex items-center space-x-1 bg-slate-800 border border-slate-700/80 rounded-xl p-1 shadow-inner">
                                <button onclick="updateQuantity('${item.cartItemId}', -1)" class="text-emerald-400 hover:text-emerald-300 hover:bg-slate-700 w-7 h-7 rounded-lg flex items-center justify-center font-bold bg-slate-900/60 border border-slate-700/50 shadow-sm transition-all">
                                    <i class="fas fa-minus text-[10px]"></i>
                                </button>
                                <span class="font-extrabold text-slate-200 w-8 text-center text-sm">${item.quantity}</span>
                                <button onclick="updateQuantity('${item.cartItemId}', 1)" class="text-emerald-400 hover:text-emerald-300 hover:bg-slate-700 w-7 h-7 rounded-lg flex items-center justify-center font-bold bg-slate-900/60 border border-slate-700/50 shadow-sm transition-all">
                                    <i class="fas fa-plus text-[10px]"></i>
                                </button>
                            </div>
                            <span class="font-extrabold text-emerald-400 text-base font-outfit drop-shadow-[0_0_5px_rgba(52,211,153,0.2)]">
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
            
            // Update or create badge
            const btnCart = document.querySelector('button[onclick="toggleCart()"]');
            let badge = btnCart.querySelector('span');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'absolute -top-1 -right-1 bg-gradient-to-tr from-emerald-600 to-emerald-400 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center shadow-[0_0_10px_rgba(52,211,153,0.5)] animate-bounce border border-emerald-300';
                btnCart.appendChild(badge);
            }
            badge.textContent = cart.length; // Or totalItems if you want total qty
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
                btn.classList.remove('bg-emerald-600', 'text-white', 'shadow-md', 'shadow-emerald-500/30');
                btn.classList.add('bg-slate-100', 'text-slate-600');
            });
            
            const activeBtn = categoryId === 'all' 
                ? document.querySelector('[onclick="filterCategory(\'all\')"]')
                : document.querySelector('.category-btn-' + categoryId);
            if (activeBtn) {
                activeBtn.classList.remove('bg-slate-100', 'text-slate-600');
                activeBtn.classList.add('bg-emerald-600', 'text-white', 'shadow-md', 'shadow-emerald-500/30');
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
        // Promo Modal Logic
        function closePromoModal() {
            const modal = document.getElementById('promoModal');
            modal.classList.add('opacity-0');
            document.getElementById('promoModalContent').classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden', 'pointer-events-none');
            }, 300);
            sessionStorage.setItem('promoShown', 'true');
        }
        
        function claimPromo() {
            window.location.href = 'promo.php';
        }

        // Show promo modal on load
        window.addEventListener('load', () => {
            if (!sessionStorage.getItem('promoShown')) {
                setTimeout(() => {
                    const modal = document.getElementById('promoModal');
                    modal.classList.remove('hidden');
                    
                    setTimeout(() => {
                        modal.classList.remove('opacity-0', 'pointer-events-none');
                        document.getElementById('promoModalContent').classList.remove('scale-95');
                    }, 10);
                }, 1500);
            }
        });
    </script>
    <?php include 'bottom_nav.php'; ?>
</body>
</html>
