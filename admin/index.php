<?php
require_once '../config/config.php';
requireRole(['owner']);

$user = getCurrentUser();
$stats = getTodayStats();

// Get additional stats
$db = getDB();

// Hourly sales for today
$stmt = $db->query("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as orders,
        COALESCE(SUM(total), 0) as revenue
    FROM orders
    WHERE DATE(created_at) = CURDATE() AND status = 'completed'
    GROUP BY HOUR(created_at)
    ORDER BY hour
");
$hourlySales = $stmt->fetchAll();

// Top 5 menus today
$stmt = $db->query("
    SELECT 
        m.name,
        c.name as category,
        SUM(oi.quantity) as qty,
        SUM(oi.subtotal) as revenue
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    JOIN categories c ON m.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) = CURDATE() AND o.status = 'completed'
    GROUP BY m.id, m.name, c.name
    ORDER BY qty DESC
    LIMIT 5
");
$topMenus = $stmt->fetchAll();

// Recent orders
$stmt = $db->query("
    SELECT 
        o.*,
        t.table_number,
        u.full_name as kasir_name
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN users u ON o.created_by = u.id
    WHERE o.status NOT IN ('completed', 'cancelled')
    ORDER BY o.created_at DESC
    LIMIT 10
");
$recentOrders = $stmt->fetchAll();

// Payment method distribution today
$stmt = $db->query("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(amount) as total
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE DATE(p.created_at) = CURDATE() AND p.status = 'success'
    GROUP BY payment_method
");
$paymentMethods = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Dashboard Owner - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                <i class="fas fa-coffee text-2xl text-purple-600"></i>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">Dashboard Owner</h1>
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
                <a href="index.php" class="flex items-center space-x-3 p-3 bg-slate-50 text-slate-700 rounded-lg">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="pos.php" class="flex items-center space-x-3 p-3 hover:bg-gray-100 rounded-lg text-gray-700">
                    <i class="fas fa-cash-register w-5"></i>
                    <span>POS Kasir</span>
                </a>
                <a href="kitchen.php" class="flex items-center space-x-3 p-3 hover:bg-gray-100 rounded-lg text-gray-700">
                    <i class="fas fa-fire w-5"></i>
                    <span>Dapur</span>
                </a>
                <a href="orders.php" class="flex items-center space-x-3 p-3 hover:bg-gray-100 rounded-lg text-gray-700">
                    <i class="fas fa-receipt w-5"></i>
                    <span>Pesanan</span>
                </a>
                <a href="menu.php" class="flex items-center space-x-3 p-3 hover:bg-gray-100 rounded-lg text-gray-700">
                    <i class="fas fa-utensils w-5"></i>
                    <span>Menu</span>
                </a>
                <a href="tables.php" class="flex items-center space-x-3 p-3 hover:bg-gray-100 rounded-lg text-gray-700">
                    <i class="fas fa-chair w-5"></i>
                    <span>Meja</span>
                </a>
                <a href="reports.php" class="flex items-center space-x-3 p-3 hover:bg-gray-100 rounded-lg text-gray-700">
                    <i class="fas fa-file-alt w-5"></i>
                    <span>Laporan</span>
                </a>
                <a href="users.php" class="flex items-center space-x-3 p-3 hover:bg-gray-100 rounded-lg text-gray-700">
                    <i class="fas fa-users w-5"></i>
                    <span>Karyawan</span>
                </a>
                <a href="change_password.php" class="flex items-center space-x-3 p-3 hover:bg-gray-100 rounded-lg text-gray-700">
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

    <!-- Main Content -->
    <main class="p-4 pb-20">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Orders -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="bg-blue-100 p-2 rounded-lg">
                        <i class="fas fa-shopping-cart text-blue-600"></i>
                    </div>
                    <span class="text-xs text-gray-500">Hari Ini</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800"><?= $stats['orders'] ?></h3>
                <p class="text-sm text-gray-600">Total Pesanan</p>
            </div>

            <!-- Revenue -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="bg-green-100 p-2 rounded-lg">
                        <i class="fas fa-money-bill-wave text-green-600"></i>
                    </div>
                    <span class="text-xs text-gray-500">Hari Ini</span>
                </div>
                <h3 class="text-lg font-bold text-gray-800"><?= formatRupiah($stats['revenue']) ?></h3>
                <p class="text-sm text-gray-600">Pendapatan</p>
            </div>

            <!-- Active Orders -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="bg-orange-100 p-2 rounded-lg">
                        <i class="fas fa-clock text-orange-600"></i>
                    </div>
                    <span class="text-xs text-gray-500">Aktif</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800"><?= $stats['active_orders'] ?></h3>
                <p class="text-sm text-gray-600">Pesanan Aktif</p>
            </div>

            <!-- Available Tables -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="bg-purple-100 p-2 rounded-lg">
                        <i class="fas fa-chair text-purple-600"></i>
                    </div>
                    <span class="text-xs text-gray-500">Status</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800"><?= $stats['available_tables'] ?></h3>
                <p class="text-sm text-gray-600">Meja Tersedia</p>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <!-- Hourly Sales Chart -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                    Penjualan Per Jam
                </h3>
                <canvas id="hourlySalesChart" class="w-full" style="max-height: 250px;"></canvas>
            </div>

            <!-- Payment Method Chart -->
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-wallet mr-2 text-green-600"></i>
                    Metode Pembayaran
                </h3>
                <canvas id="paymentMethodChart" class="w-full" style="max-height: 250px;"></canvas>
            </div>
        </div>

        <!-- Top Menus -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-fire mr-2 text-red-600"></i>
                Menu Terlaris Hari Ini
            </h3>
            <div class="space-y-3">
                <?php if (empty($topMenus)): ?>
                    <p class="text-gray-500 text-center py-4">Belum ada data</p>
                <?php else: ?>
                    <?php foreach ($topMenus as $index => $menu): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="bg-gradient-to-br from-purple-500 to-pink-500 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">
                                <?= $index + 1 ?>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800"><?= $menu['name'] ?></p>
                                <p class="text-xs text-gray-500"><?= $menu['category'] ?> • <?= $menu['qty'] ?> porsi</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600"><?= formatRupiah($menu['revenue']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-xl shadow-sm p-4">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-receipt mr-2 text-blue-600"></i>
                Pesanan Terbaru
            </h3>
            <div class="space-y-3">
                <?php if (empty($recentOrders)): ?>
                    <p class="text-gray-500 text-center py-4">Tidak ada pesanan aktif</p>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                    <div class="border rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="font-semibold text-gray-800"><?= $order['order_number'] ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php if ($order['table_number']): ?>
                                        Meja <?= $order['table_number'] ?>
                                    <?php else: ?>
                                        Take Away
                                    <?php endif; ?>
                                    • <?= timeAgo($order['created_at']) ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= getStatusBadge($order['status']) ?>">
                                <?= getStatusText($order['status']) ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">
                                <i class="fas fa-user mr-1"></i><?= $order['kasir_name'] ?? '-' ?>
                            </span>
                            <span class="font-bold text-gray-800"><?= formatRupiah($order['total']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-30">
        <div class="flex justify-around items-center py-2">
            <a href="index.php" class="flex flex-col items-center text-slate-700 py-2 px-3">
                <i class="fas fa-home text-lg"></i>
                <span class="text-xs mt-1">Home</span>
            </a>
            <a href="pos.php" class="flex flex-col items-center text-gray-600 hover:text-slate-700 py-2 px-3">
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
        // Toggle sidebar menu
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        }

        // Close sidebar when clicking outside
        document.getElementById('sidebar')?.addEventListener('click', function(e) {
            if (e.target === this) {
                toggleMenu();
            }
        });

        // Hourly Sales Chart
        const hourlySalesData = <?= json_encode($hourlySales) ?>;
        const hourLabels = Array.from({length: 24}, (_, i) => i + ':00');
        const hourRevenue = new Array(24).fill(0);
        
        hourlySalesData.forEach(item => {
            hourRevenue[item.hour] = parseFloat(item.revenue);
        });

        const ctxHourly = document.getElementById('hourlySalesChart');
        new Chart(ctxHourly, {
            type: 'line',
            data: {
                labels: hourLabels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: hourRevenue,
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        // Payment Method Chart
        const paymentData = <?= json_encode($paymentMethods) ?>;
        const paymentLabels = paymentData.map(p => {
            const labels = {
                'cash': 'Tunai',
                'qris': 'QRIS',
                'transfer': 'Transfer',
                'card': 'Kartu'
            };
            return labels[p.payment_method] || p.payment_method;
        });
        const paymentValues = paymentData.map(p => parseFloat(p.total));

        const ctxPayment = document.getElementById('paymentMethodChart');
        new Chart(ctxPayment, {
            type: 'doughnut',
            data: {
                labels: paymentLabels,
                datasets: [{
                    data: paymentValues,
                    backgroundColor: [
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                        'rgb(249, 115, 22)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': Rp ' + context.parsed.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        // Auto refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
