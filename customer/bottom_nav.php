<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="fixed bottom-0 left-0 right-0 bg-slate-900/90 backdrop-blur-lg border-t border-slate-700/50 px-6 py-3 z-50 shadow-[0_-10px_40px_-10px_rgba(0,0,0,0.5)]">
    <div class="max-w-md mx-auto flex justify-between items-center gap-1 sm:gap-2">
        <!-- Menu -->
        <a href="menu.php" class="flex flex-col items-center gap-1 group flex-1 max-w-[64px] transition-transform hover:scale-110">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center transition-all duration-300 <?= $current_page == 'menu.php' ? 'bg-gradient-to-tr from-emerald-500 to-teal-400 text-white shadow-[0_0_15px_rgba(16,185,129,0.5)]' : 'bg-slate-800 text-slate-400 group-hover:text-emerald-400 group-hover:bg-slate-700' ?>">
                <i class="fas fa-utensils text-base sm:text-lg"></i>
            </div>
            <span class="text-[9px] sm:text-xs font-bold font-outfit <?= $current_page == 'menu.php' ? 'text-emerald-400' : 'text-slate-400 group-hover:text-emerald-400' ?>">Menu</span>
        </a>
        
        <!-- Promo -->
        <a href="promo.php" class="flex flex-col items-center gap-1 group flex-1 max-w-[64px] transition-transform hover:scale-110">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center transition-all duration-300 <?= $current_page == 'promo.php' ? 'bg-gradient-to-tr from-blue-500 to-blue-400 text-white shadow-[0_0_15px_rgba(59,130,246,0.5)]' : 'bg-slate-800 text-slate-400 group-hover:text-blue-400 group-hover:bg-slate-700' ?>">
                <i class="fas fa-tags text-base sm:text-lg"></i>
            </div>
            <span class="text-[9px] sm:text-xs font-bold font-outfit <?= $current_page == 'promo.php' ? 'text-blue-400' : 'text-slate-400 group-hover:text-blue-400' ?>">Promo</span>
        </a>

        <!-- Grill -->
        <a href="grill.php" class="flex flex-col items-center gap-1 group flex-1 max-w-[64px] transition-transform hover:scale-110">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center transition-all duration-300 <?= $current_page == 'grill.php' ? 'bg-gradient-to-tr from-orange-500 to-red-500 text-white shadow-[0_0_15px_rgba(249,115,22,0.5)]' : 'bg-slate-800 text-slate-400 group-hover:text-orange-400 group-hover:bg-slate-700' ?>">
                <i class="fas fa-fire text-base sm:text-lg"></i>
            </div>
            <span class="text-[9px] sm:text-xs font-bold font-outfit <?= $current_page == 'grill.php' ? 'text-orange-400' : 'text-slate-400 group-hover:text-orange-400' ?>">Grill</span>
        </a>

        <!-- Order Online -->
        <a href="menu_online.php" onclick="if(window.location.pathname.includes('menu_online.php')) { window.scrollTo({top: 0, behavior: 'smooth'}); return false; }" class="flex flex-col items-center gap-1 group flex-1 max-w-[64px] transition-transform hover:scale-110">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center transition-all duration-300 <?= $current_page == 'menu_online.php' ? 'bg-gradient-to-tr from-amber-500 to-yellow-400 text-white shadow-[0_0_15px_rgba(245,158,11,0.5)]' : 'bg-slate-800 text-slate-400 group-hover:text-amber-400 group-hover:bg-slate-700' ?>">
                <i class="fas fa-motorcycle text-base sm:text-lg"></i>
            </div>
            <span class="text-[9px] sm:text-xs font-bold font-outfit <?= $current_page == 'menu_online.php' ? 'text-amber-400' : 'text-slate-400 group-hover:text-amber-400' ?>">Order</span>
        </a>
        
        <!-- Reels -->
        <a href="reels.php" class="flex flex-col items-center gap-1 group flex-1 max-w-[64px] transition-transform hover:scale-110">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center transition-all duration-300 <?= $current_page == 'reels.php' ? 'bg-gradient-to-tr from-emerald-500 to-teal-400 text-white shadow-[0_0_15px_rgba(16,185,129,0.5)]' : 'bg-slate-800 text-slate-400 group-hover:text-emerald-400 group-hover:bg-slate-700' ?>">
                <i class="fas fa-play text-base sm:text-lg"></i>
            </div>
            <span class="text-[9px] sm:text-xs font-bold font-outfit <?= $current_page == 'reels.php' ? 'text-emerald-400' : 'text-slate-400 group-hover:text-emerald-400' ?>">Reels</span>
        </a>
        
        <!-- Pesanan -->
        <a href="track_order.php" class="flex flex-col items-center gap-1 group flex-1 max-w-[64px] transition-transform hover:scale-110 relative">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center transition-all duration-300 <?= $current_page == 'track_order.php' ? 'bg-gradient-to-tr from-rose-700 to-rose-600 text-white shadow-[0_0_15px_rgba(190,18,60,0.5)]' : 'bg-slate-800 text-slate-400 group-hover:text-rose-500 group-hover:bg-slate-700' ?>">
                <i class="fas fa-receipt text-base sm:text-lg"></i>
            </div>
            <?php if(isset($_SESSION['last_order_number'])): ?>
                <span class="absolute top-0 right-1 sm:right-2 w-2.5 h-2.5 bg-rose-500 border-2 border-slate-900 rounded-full animate-pulse"></span>
            <?php endif; ?>
            <span class="text-[9px] sm:text-xs font-bold font-outfit <?= $current_page == 'track_order.php' ? 'text-rose-500' : 'text-slate-400 group-hover:text-rose-500' ?>">Pesanan</span>
        </a>
    </div>
</div>
<!-- Safe area padding for bottom nav -->
<div class="h-24"></div>
