    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-30">
        <div class="flex justify-around items-center py-2">
            <a href="index.php" class="flex flex-col items-center <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-slate-700' : 'text-gray-600 hover:text-slate-700' ?> py-2 px-3">
                <i class="fas fa-home text-lg"></i>
                <span class="text-xs mt-1">Home</span>
            </a>
            <a href="pos.php" class="flex flex-col items-center <?= basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'text-slate-700' : 'text-gray-600 hover:text-slate-700' ?> py-2 px-3">
                <i class="fas fa-cash-register text-lg"></i>
                <span class="text-xs mt-1">POS</span>
            </a>
            <a href="kitchen.php" class="flex flex-col items-center <?= basename($_SERVER['PHP_SELF']) == 'kitchen.php' ? 'text-slate-700' : 'text-gray-600 hover:text-slate-700' ?> py-2 px-3">
                <i class="fas fa-fire text-lg"></i>
                <span class="text-xs mt-1">Dapur</span>
            </a>
            <a href="orders.php" class="flex flex-col items-center <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'text-slate-700' : 'text-gray-600 hover:text-slate-700' ?> py-2 px-3">
                <i class="fas fa-receipt text-lg"></i>
                <span class="text-xs mt-1">Orders</span>
            </a>
            <a href="menu.php" class="flex flex-col items-center <?= basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'text-slate-700' : 'text-gray-600 hover:text-slate-700' ?> py-2 px-3">
                <i class="fas fa-utensils text-lg"></i>
                <span class="text-xs mt-1">Menu</span>
            </a>
        </div>
    </nav>
</body>
</html>
