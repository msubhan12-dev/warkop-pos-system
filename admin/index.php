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
<?php
$pageTitle = 'Dashboard Owner';
include '../includes/header.php';
?>
            <div class="p-6 pb-24 md:pb-6 max-w-7xl mx-auto w-full">
                <!-- Header Info -->
                <div class="mb-6">
                    <h2 class="text-2xl font-extrabold text-slate-800 font-outfit">Ringkasan Hari Ini</h2>
                    <p class="text-xs text-slate-500 font-medium mt-1">Pantau laporan transaksi dan performa kedai secara real-time</p>
                </div>
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
                    <!-- Total Orders -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-5 sm:p-6 hover:shadow-lg hover:-translate-y-1 transition duration-300 flex items-center justify-between group">
                        <div>
                            <p class="text-[10px] sm:text-xs text-slate-400 font-extrabold uppercase tracking-wider mb-1">Total Pesanan</p>
                            <h3 class="text-3xl sm:text-4xl font-black text-slate-800 font-outfit"><?= $stats['orders'] ?></h3>
                            <p class="text-[10px] sm:text-xs text-emerald-500 font-bold mt-1.5 flex items-center"><i class="fas fa-calendar-day mr-1.5"></i>Hari Ini</p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 text-blue-600 w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center text-xl sm:text-2xl shadow-inner group-hover:scale-110 transition-transform">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>

                    <!-- Revenue -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-5 sm:p-6 hover:shadow-lg hover:-translate-y-1 transition duration-300 flex items-center justify-between group">
                        <div>
                            <p class="text-[10px] sm:text-xs text-slate-400 font-extrabold uppercase tracking-wider mb-1">Pendapatan</p>
                            <h3 class="text-2xl sm:text-3xl font-black text-emerald-600 font-outfit"><?= formatRupiah($stats['revenue']) ?></h3>
                            <p class="text-[10px] sm:text-xs text-emerald-500 font-bold mt-1.5 flex items-center"><i class="fas fa-calendar-day mr-1.5"></i>Hari Ini</p>
                        </div>
                        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-600 w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center text-xl sm:text-2xl shadow-inner group-hover:scale-110 transition-transform">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>

                    <!-- Active Orders -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-5 sm:p-6 hover:shadow-lg hover:-translate-y-1 transition duration-300 flex items-center justify-between group">
                        <div>
                            <p class="text-[10px] sm:text-xs text-slate-400 font-extrabold uppercase tracking-wider mb-1">Pesanan Aktif</p>
                            <h3 class="text-3xl sm:text-4xl font-black text-slate-850 font-outfit"><?= $stats['active_orders'] ?></h3>
                            <p class="text-[10px] sm:text-xs text-amber-500 font-bold mt-1.5 flex items-center"><i class="fas fa-fire mr-1.5"></i>Dapur / Kasir</p>
                        </div>
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 text-amber-600 w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center text-xl sm:text-2xl shadow-inner group-hover:scale-110 transition-transform">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>

                    <!-- Available Tables -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-5 sm:p-6 hover:shadow-lg hover:-translate-y-1 transition duration-300 flex items-center justify-between group">
                        <div>
                            <p class="text-[10px] sm:text-xs text-slate-400 font-extrabold uppercase tracking-wider mb-1">Meja Tersedia</p>
                            <h3 class="text-3xl sm:text-4xl font-black text-slate-850 font-outfit"><?= $stats['available_tables'] ?></h3>
                            <p class="text-[10px] sm:text-xs text-purple-500 font-bold mt-1.5 flex items-center"><i class="fas fa-info-circle mr-1.5"></i>Status Meja</p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 text-purple-600 w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center text-xl sm:text-2xl shadow-inner group-hover:scale-110 transition-transform">
                            <i class="fas fa-chair"></i>
                        </div>
                    </div>
                </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-8">
            <!-- Hourly Sales Chart -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-5 sm:p-7">
                <h3 class="font-extrabold text-slate-800 mb-5 flex items-center text-lg font-outfit tracking-tight">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center mr-3 text-blue-600"><i class="fas fa-chart-line"></i></div>
                    Penjualan Per Jam
                </h3>
                <canvas id="hourlySalesChart" class="w-full" style="max-height: 280px;"></canvas>
            </div>

            <!-- Payment Method Chart -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-5 sm:p-7">
                <h3 class="font-extrabold text-slate-800 mb-5 flex items-center text-lg font-outfit tracking-tight">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center mr-3 text-emerald-600"><i class="fas fa-wallet"></i></div>
                    Metode Pembayaran
                </h3>
                <canvas id="paymentMethodChart" class="w-full" style="max-height: 280px;"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
            <!-- Top Menus -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-5 sm:p-7">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-extrabold text-slate-800 flex items-center text-lg font-outfit tracking-tight">
                        <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center mr-3 text-red-500"><i class="fas fa-fire"></i></div>
                        Top Menu Hari Ini
                    </h3>
                </div>
                
                <div class="space-y-3">
                    <?php if (empty($topMenus)): ?>
                        <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                            <i class="fas fa-box-open text-4xl mb-3 text-slate-200"></i>
                            <p class="font-medium text-sm">Belum ada data penjualan</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($topMenus as $index => $menu): ?>
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 hover:bg-slate-100 rounded-2xl border border-transparent hover:border-slate-200 transition duration-300">
                            <div class="flex items-center space-x-4">
                                <div class="bg-gradient-to-br from-amber-400 to-orange-500 text-white w-9 h-9 rounded-xl flex items-center justify-center font-black shadow-inner">
                                    <?= $index + 1 ?>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 font-outfit leading-snug"><?= $menu['name'] ?></p>
                                    <p class="text-[11px] font-semibold text-slate-500 mt-0.5"><span class="bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded mr-1"><?= $menu['category'] ?></span> <?= $menu['qty'] ?> porsi</p>
                                </div>
                            </div>
                            <div class="text-right pl-3 border-l border-slate-200">
                                <p class="font-extrabold text-emerald-600 text-sm"><?= formatRupiah($menu['revenue']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-5 sm:p-7">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-extrabold text-slate-800 flex items-center text-lg font-outfit tracking-tight">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center mr-3 text-emerald-600"><i class="fas fa-receipt"></i></div>
                        Pesanan Terbaru
                    </h3>
                    <a href="orders.php" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors">Lihat Semua</a>
                </div>
                
                <div class="space-y-3">
                    <?php if (empty($recentOrders)): ?>
                        <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                            <i class="fas fa-check-circle text-4xl mb-3 text-slate-200"></i>
                            <p class="font-medium text-sm">Semua pesanan sudah selesai</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                        <div class="border border-slate-100 hover:border-emerald-200 rounded-2xl p-4 bg-white hover:shadow-md transition duration-300">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p class="font-bold text-slate-800 font-outfit mb-0.5"><?= $order['order_number'] ?></p>
                                    <p class="text-[11px] font-semibold text-slate-500 flex items-center">
                                        <i class="fas fa-clock mr-1.5 text-slate-400"></i><?= timeAgo($order['created_at']) ?>
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 text-[10px] font-extrabold rounded-lg shadow-sm border uppercase tracking-wider <?= getStatusBadge($order['status']) ?>">
                                    <?= getStatusText($order['status']) ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-dashed border-slate-100">
                                <span class="text-[11px] font-semibold text-slate-500 flex items-center bg-slate-50 px-2 py-1 rounded-md">
                                    <i class="fas <?= $order['table_number'] ? 'fa-chair text-purple-400' : 'fa-shopping-bag text-amber-400' ?> mr-1.5"></i>
                                    <?= $order['table_number'] ? 'Meja '.$order['table_number'] : 'Take Away' ?>
                                </span>
                                <span class="font-extrabold text-emerald-600 font-outfit"><?= formatRupiah($order['total']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
            </main>
        </div> <!-- Close Main View Area -->
    </div> <!-- Close flex min-h-screen -->

    <script>
        // Format Rupiah
        const formatRupiah = (number) => {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);
        };

        // Initialize Hourly Sales Chart
        const hourlyCtx = document.getElementById('hourlySalesChart');
        if (hourlyCtx) {
            new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_map(function($h) { return sprintf("%02d:00", $h['hour']); }, $hourlySales)) ?>,
                    datasets: [{
                        label: 'Pendapatan (Rp)',
                        data: <?= json_encode(array_column($hourlySales, 'revenue')) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) return (value / 1000000) + 'M';
                                    if (value >= 1000) return (value / 1000) + 'K';
                                    return value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initialize Payment Method Chart
        const paymentCtx = document.getElementById('paymentMethodChart');
        if (paymentCtx) {
            new Chart(paymentCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_map('strtoupper', array_column($paymentMethods, 'payment_method'))) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($paymentMethods, 'total')) ?>,
                        backgroundColor: [
                            '#10b981', // emerald-500
                            '#3b82f6', // blue-500
                            '#f59e0b', // amber-500
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
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
        }

        // Auto refresh every 60 seconds
        setInterval(() => {
            location.reload();
        }, 60000);
    </script>
<?php include '../includes/footer.php'; ?>
