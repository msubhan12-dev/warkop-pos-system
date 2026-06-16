<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Header -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center space-x-3">
                <i class="fas fa-coffee text-2xl text-slate-700"></i>
                <div>
                    <h1 class="text-lg font-bold text-gray-800"><?= $pageTitle ?? 'Dashboard' ?></h1>
                    <p class="text-xs text-gray-500"><?= $user['full_name'] ?></p>
                </div>
            </div>
            <button onclick="toggleMenu()" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
    </header>

    <!-- Sidebar Menu (Mobile) -->
    <div id="sidebar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
        <div class="absolute right-0 top-0 bottom-0 w-64 bg-white shadow-lg">
            <div class="p-4 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-lg">Menu</h2>
                    <button onclick="toggleMenu()" class="text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <nav class="p-4 space-y-2">
                <a href="index.php" class="flex items-center space-x-3 p-3 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-slate-50 text-slate-700' : 'hover:bg-gray-100' ?> rounded-lg text-gray-700">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="pos.php" class="flex items-center space-x-3 p-3 <?= basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'bg-slate-50 text-slate-700' : 'hover:bg-gray-100' ?> rounded-lg text-gray-700">
                    <i class="fas fa-cash-register w-5"></i>
                    <span>POS Kasir</span>
                </a>
                <a href="kitchen.php" class="flex items-center space-x-3 p-3 <?= basename($_SERVER['PHP_SELF']) == 'kitchen.php' ? 'bg-slate-50 text-slate-700' : 'hover:bg-gray-100' ?> rounded-lg text-gray-700">
                    <i class="fas fa-fire w-5"></i>
                    <span>Dapur</span>
                </a>
                <a href="orders.php" class="flex items-center space-x-3 p-3 <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-slate-50 text-slate-700' : 'hover:bg-gray-100' ?> rounded-lg text-gray-700">
                    <i class="fas fa-receipt w-5"></i>
                    <span>Pesanan</span>
                </a>
                <a href="menu.php" class="flex items-center space-x-3 p-3 <?= basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'bg-slate-50 text-slate-700' : 'hover:bg-gray-100' ?> rounded-lg text-gray-700">
                    <i class="fas fa-utensils w-5"></i>
                    <span>Menu</span>
                </a>
                <a href="tables.php" class="flex items-center space-x-3 p-3 <?= basename($_SERVER['PHP_SELF']) == 'tables.php' ? 'bg-slate-50 text-slate-700' : 'hover:bg-gray-100' ?> rounded-lg text-gray-700">
                    <i class="fas fa-chair w-5"></i>
                    <span>Meja</span>
                </a>
                <a href="reports.php" class="flex items-center space-x-3 p-3 <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-slate-50 text-slate-700' : 'hover:bg-gray-100' ?> rounded-lg text-gray-700">
                    <i class="fas fa-file-alt w-5"></i>
                    <span>Laporan</span>
                </a>
                <a href="users.php" class="flex items-center space-x-3 p-3 <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-slate-50 text-slate-700' : 'hover:bg-gray-100' ?> rounded-lg text-gray-700">
                    <i class="fas fa-users w-5"></i>
                    <span>Karyawan</span>
                </a>
                <a href="change_password.php" class="flex items-center space-x-3 p-3 <?= basename($_SERVER['PHP_SELF']) == 'change_password.php' ? 'bg-slate-50 text-slate-700' : 'hover:bg-gray-100' ?> rounded-lg text-gray-700">
                    <i class="fas fa-key w-5"></i>
                    <span>Ganti Password</span>
                </a>
                <hr class="my-2">
                <a href="logout.php" class="flex items-center space-x-3 p-3 hover:bg-red-50 text-red-600 rounded-lg">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        }
        document.getElementById('sidebar')?.addEventListener('click', function(e) {
            if (e.target === this) {
                toggleMenu();
            }
        });
    </script>
