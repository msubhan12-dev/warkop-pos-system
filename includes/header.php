<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$isPosPage = in_array($currentPage, ['pos.php']) || ($currentDir == 'kasir' && in_array($currentPage, ['index.php', 'payments.php']));
$sidebarCollapsed = $isPosPage ? true : false;
$basePath = ($currentDir == 'kasir') ? '../admin/' : '';
$userRole = $user['role'] ?? 'pelayan'; 

$navItems = [
    ['url' => $basePath . 'index', 'icon' => 'fa-chart-pie', 'label' => 'Dashboard', 'color' => 'text-emerald-400', 'roles' => ['owner', 'admin']],
    ['url' => ($currentDir == 'kasir' ? 'index' : 'pos'), 'icon' => 'fa-cash-register', 'label' => 'POS Kasir', 'color' => 'text-blue-400', 'roles' => ['owner', 'admin', 'kasir']],
    ['url' => $basePath . 'kitchen', 'icon' => 'fa-fire', 'label' => 'Dapur', 'color' => 'text-orange-400', 'roles' => ['owner', 'admin', 'pelayan']],
    ['url' => $basePath . 'orders', 'icon' => 'fa-receipt', 'label' => 'Pesanan', 'color' => 'text-indigo-400', 'roles' => ['owner', 'admin', 'kasir', 'pelayan']],
    ['url' => $basePath . 'menu', 'icon' => 'fa-utensils', 'label' => 'Menu', 'color' => 'text-rose-400', 'roles' => ['owner', 'admin']],
    ['url' => $basePath . 'tables', 'icon' => 'fa-chair', 'label' => 'Meja', 'color' => 'text-purple-400', 'roles' => ['owner', 'admin', 'kasir']],
    ['url' => $basePath . 'promos', 'icon' => 'fa-tags', 'label' => 'Promo', 'color' => 'text-pink-400', 'roles' => ['owner', 'admin']],
    ['url' => $basePath . 'reels', 'icon' => 'fa-video', 'label' => 'Reels', 'color' => 'text-fuchsia-400', 'roles' => ['owner', 'admin']],
    ['url' => $basePath . 'reports', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Laporan', 'color' => 'text-teal-400', 'roles' => ['owner']],
    ['url' => $basePath . 'users', 'icon' => 'fa-users', 'label' => 'Karyawan', 'color' => 'text-cyan-400', 'roles' => ['owner']]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?= $pageTitle ?? 'Warkop OS' ?> - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        .sidebar-collapsed .brand-text, .sidebar-collapsed .nav-label { display: none; }
        .sidebar-collapsed .nav-link { justify-content: center; padding-left: 0; padding-right: 0; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-[100dvh] overflow-hidden">
    
    <!-- Desktop Sidebar -->
    <aside id="desktopSidebar" class="hidden md:flex flex-col <?= $sidebarCollapsed ? 'w-20 sidebar-collapsed' : 'w-64' ?> bg-slate-900 text-white flex-none h-[100dvh] z-30 border-r border-slate-800 transition-all duration-300 relative group">
        <!-- Toggle Button -->
        <button onclick="toggleDesktopSidebar()" class="absolute -right-3 top-6 bg-emerald-500 hover:bg-emerald-400 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-[0_0_10px_rgba(16,185,129,0.5)] z-40 transition-transform hover:scale-110">
            <i class="fas <?= $sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left' ?> text-[10px]" id="sidebarToggleIcon"></i>
        </button>

        <!-- Brand -->
        <div class="flex items-center space-x-3 px-6 py-5 border-b border-slate-800 h-[72px] transition-all overflow-hidden whitespace-nowrap">
            <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" alt="Logo" class="w-8 h-8 rounded-xl object-cover shadow-lg shrink-0">
            <span class="brand-text font-extrabold text-xl text-white font-outfit tracking-tight transition-opacity"><?= APP_NAME ?></span>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto sidebar-scroll overflow-x-hidden">
            <?php foreach ($navItems as $item): 
                if (!in_array($userRole, $item['roles'])) continue;
                $isActive = ($currentPage == basename($item['url']) . '.php');
                if ($currentDir == 'kasir' && $item['label'] == 'POS Kasir') $isActive = true;
            ?>
                <a href="<?= $item['url'] ?>" class="nav-link flex items-center space-x-3 p-3 <?= $isActive ? 'bg-slate-800/80 text-white shadow-inner border-l-2 border-emerald-500' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?> rounded-xl transition-all duration-200 group/link whitespace-nowrap" title="<?= $item['label'] ?>">
                    <div class="w-6 flex justify-center shrink-0">
                        <i class="fas <?= $item['icon'] ?> text-lg <?= $isActive ? $item['color'] : 'group-hover/link:' . $item['color'] ?> transition-colors"></i>
                    </div>
                    <span class="nav-label font-outfit font-semibold text-sm transition-opacity"><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
            
            <hr class="border-slate-800 my-4 mx-2">
            <a href="<?= $basePath ?>change_password.php" class="nav-link flex items-center space-x-3 p-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-xl transition-all whitespace-nowrap" title="Ganti Password">
                <div class="w-6 flex justify-center shrink-0"><i class="fas fa-key text-lg group-hover/link:text-amber-400"></i></div>
                <span class="nav-label font-outfit font-semibold text-sm">Ganti Password</span>
            </a>
            <a href="<?= $basePath ?>logout.php" class="nav-link flex items-center space-x-3 p-3 text-slate-400 hover:bg-red-500/10 hover:text-red-400 rounded-xl transition-all whitespace-nowrap" title="Logout">
                <div class="w-6 flex justify-center shrink-0"><i class="fas fa-sign-out-alt text-lg"></i></div>
                <span class="nav-label font-outfit font-semibold text-sm">Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Wrapper -->
    <div class="flex-1 flex flex-col h-[100dvh] overflow-hidden relative w-full">
        <!-- Mobile Topbar -->
        <header class="md:hidden bg-white shadow-[0_2px_10px_-3px_rgba(0,0,0,0.1)] sticky top-0 z-40">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center space-x-3">
                    <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" alt="Logo" class="w-8 h-8 rounded-full object-cover">
                    <div>
                        <h1 class="text-base font-extrabold text-slate-800 font-outfit"><?= $pageTitle ?? 'Dashboard' ?></h1>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600"><?= $user['full_name'] ?? 'User' ?></p>
                    </div>
                </div>
                <button onclick="toggleMobileMenu()" class="text-slate-600 hover:text-emerald-600 w-10 h-10 rounded-full hover:bg-slate-50 transition-colors flex items-center justify-center">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </header>

        <!-- Mobile Sidebar (Slide-over) -->
        <div id="mobileSidebar" class="fixed inset-0 bg-slate-900/60 z-50 transition-opacity duration-300 opacity-0 pointer-events-none md:hidden">
            <div id="mobileSidebarPanel" class="absolute right-0 top-0 bottom-0 w-64 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <h2 class="font-extrabold text-lg font-outfit text-slate-800">Menu</h2>
                    <button onclick="toggleMobileMenu()" class="text-slate-400 hover:text-red-500 w-8 h-8 rounded-full hover:bg-red-50 flex items-center justify-center transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                    <?php foreach ($navItems as $item): 
                        if (!in_array($userRole, $item['roles'])) continue;
                        $isActive = ($currentPage == basename($item['url']) . '.php');
                        if ($currentDir == 'kasir' && $item['label'] == 'POS Kasir') $isActive = true;
                    ?>
                        <a href="<?= $item['url'] ?>" class="flex items-center space-x-3 p-3.5 <?= $isActive ? 'bg-emerald-50 text-emerald-700 font-bold rounded-xl' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-600 rounded-xl' ?> transition-colors">
                            <div class="w-6 flex justify-center"><i class="fas <?= $item['icon'] ?> text-lg <?= $isActive ? '' : 'text-slate-400' ?>"></i></div>
                            <span class="font-outfit text-sm"><?= $item['label'] ?></span>
                        </a>
                    <?php endforeach; ?>
                    <hr class="border-slate-100 my-2">
                    <a href="<?= $basePath ?>logout.php" class="flex items-center space-x-3 p-3.5 text-red-500 hover:bg-red-50 rounded-xl font-bold transition-colors">
                        <div class="w-6 flex justify-center"><i class="fas fa-sign-out-alt text-lg"></i></div>
                        <span class="font-outfit text-sm">Logout</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Page Content -->
        <main class="flex-1 <?= $isPosPage ? 'flex flex-col overflow-hidden' : 'overflow-auto' ?> bg-slate-50/50 relative">
