<?php
require_once '../config/config.php';
requireRole(['dapur', 'owner']);

$user = getCurrentUser();

// Get kitchen tickets
$db = getDB();
$stmt = $db->query("
    SELECT 
        kt.*,
        o.order_number,
        o.customer_name,
        o.order_type
    FROM kitchen_tickets kt
    JOIN orders o ON kt.order_id = o.id
    WHERE kt.status IN ('new', 'cooking')
    ORDER BY 
        CASE kt.priority 
            WHEN 'urgent' THEN 1 
            ELSE 2 
        END,
        kt.created_at ASC
");
$tickets = $stmt->fetchAll();

// Group by status
$newTickets = array_filter($tickets, fn($t) => $t['status'] === 'new');
$cookingTickets = array_filter($tickets, fn($t) => $t['status'] === 'cooking');

// Get completed tickets today
$stmt = $db->query("
    SELECT COUNT(*) as count
    FROM kitchen_tickets
    WHERE DATE(ready_at) = CURDATE() AND status = 'ready'
");
$completedToday = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Kitchen Display - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/notification-sound.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        @keyframes pulse-new {
            0%, 100% { background-color: rgb(254, 202, 202); }
            50% { background-color: rgb(252, 165, 165); }
        }
        .new-order {
            animation: pulse-new 2s ease-in-out infinite;
        }
        .ticket-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .ticket-card:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body class="bg-gray-900">
    <!-- Header -->
    <header class="bg-gray-800 text-white shadow-lg sticky top-0 z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center space-x-3">
                <i class="fas fa-fire text-3xl text-orange-500"></i>
                <div>
                    <h1 class="text-xl font-bold">Kitchen Display</h1>
                    <p class="text-xs text-gray-400"><?= $user['full_name'] ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-sm text-gray-400">Selesai Hari Ini</p>
                    <p class="text-2xl font-bold text-green-400"><?= $completedToday ?></p>
                </div>
                <a href="../admin/logout.php" class="text-gray-400 hover:text-white">
                    <i class="fas fa-sign-out-alt text-xl"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-4">
        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-red-500 text-white rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-bell text-3xl"></i>
                    <span class="text-3xl font-bold"><?= count($newTickets) ?></span>
                </div>
                <p class="font-semibold">Pesanan Baru</p>
            </div>
            <div class="bg-orange-500 text-white rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-fire text-3xl"></i>
                    <span class="text-3xl font-bold"><?= count($cookingTickets) ?></span>
                </div>
                <p class="font-semibold">Sedang Dimasak</p>
            </div>
            <div class="bg-green-500 text-white rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-check-circle text-3xl"></i>
                    <span class="text-3xl font-bold"><?= $completedToday ?></span>
                </div>
                <p class="font-semibold">Selesai Hari Ini</p>
            </div>
            <div class="bg-blue-500 text-white rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-clock text-3xl"></i>
                    <span class="text-xl font-bold"><?= date('H:i') ?></span>
                </div>
                <p class="font-semibold">Waktu Saat Ini</p>
            </div>
        </div>

        <!-- New Orders Section -->
        <?php if (!empty($newTickets)): ?>
        <div class="mb-6">
            <h2 class="text-white text-2xl font-bold mb-4 flex items-center">
                <i class="fas fa-bell mr-3 text-red-400"></i>
                Pesanan Baru (<?= count($newTickets) ?>)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($newTickets as $ticket): ?>
                <div class="ticket-card new-order rounded-xl shadow-lg overflow-hidden border-4 border-red-500">
                    <div class="bg-red-500 text-white p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-lg"><?= $ticket['ticket_number'] ?></span>
                            <span class="bg-white text-red-500 px-3 py-1 rounded-full text-xs font-bold">
                                BARU
                            </span>
                        </div>
                        <div class="text-sm space-y-1">
                            <p>
                                <i class="fas fa-receipt mr-2"></i><?= $ticket['order_number'] ?>
                            </p>
                            <?php if ($ticket['table_number']): ?>
                            <p>
                                <i class="fas fa-chair mr-2"></i>Meja <?= $ticket['table_number'] ?>
                            </p>
                            <?php else: ?>
                            <p>
                                <i class="fas fa-shopping-bag mr-2"></i>Take Away
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="bg-white p-4">
                        <div class="mb-4">
                            <h3 class="text-2xl font-bold text-gray-800 mb-2"><?= $ticket['menu_name'] ?></h3>
                            <div class="flex items-center space-x-2">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-bold text-lg">
                                    <?= $ticket['quantity'] ?>x
                                </span>
                                <span class="text-gray-600"><?= $ticket['customer_name'] ?></span>
                            </div>
                            <?php if ($ticket['notes']): ?>
                            <div class="mt-2 p-2 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                                <p class="text-sm text-gray-700">
                                    <i class="fas fa-sticky-note mr-1"></i>
                                    <?= $ticket['notes'] ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm text-gray-500 mb-4">
                            <i class="fas fa-clock mr-1"></i>
                            <?= timeAgo($ticket['created_at']) ?>
                        </div>
                        <button 
                            onclick="startCooking(<?= $ticket['id'] ?>)"
                            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg transition"
                        >
                            <i class="fas fa-fire mr-2"></i>Mulai Masak
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cooking Orders Section -->
        <?php if (!empty($cookingTickets)): ?>
        <div class="mb-6">
            <h2 class="text-white text-2xl font-bold mb-4 flex items-center">
                <i class="fas fa-fire mr-3 text-orange-400"></i>
                Sedang Dimasak (<?= count($cookingTickets) ?>)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($cookingTickets as $ticket): ?>
                <?php 
                    $cookingTime = time() - strtotime($ticket['cooking_started_at']);
                    $cookingMinutes = floor($cookingTime / 60);
                ?>
                <div class="ticket-card bg-white rounded-xl shadow-lg overflow-hidden border-4 border-orange-500">
                    <div class="bg-orange-500 text-white p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-lg"><?= $ticket['ticket_number'] ?></span>
                            <span class="bg-white text-orange-500 px-3 py-1 rounded-full text-xs font-bold">
                                <?= $cookingMinutes ?> MENIT
                            </span>
                        </div>
                        <div class="text-sm space-y-1">
                            <p>
                                <i class="fas fa-receipt mr-2"></i><?= $ticket['order_number'] ?>
                            </p>
                            <?php if ($ticket['table_number']): ?>
                            <p>
                                <i class="fas fa-chair mr-2"></i>Meja <?= $ticket['table_number'] ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="mb-4">
                            <h3 class="text-2xl font-bold text-gray-800 mb-2"><?= $ticket['menu_name'] ?></h3>
                            <div class="flex items-center space-x-2">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-bold text-lg">
                                    <?= $ticket['quantity'] ?>x
                                </span>
                                <span class="text-gray-600"><?= $ticket['customer_name'] ?></span>
                            </div>
                            <?php if ($ticket['notes']): ?>
                            <div class="mt-2 p-2 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                                <p class="text-sm text-gray-700">
                                    <i class="fas fa-sticky-note mr-1"></i>
                                    <?= $ticket['notes'] ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button 
                            onclick="markReady(<?= $ticket['id'] ?>)"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-lg transition"
                        >
                            <i class="fas fa-check-circle mr-2"></i>Siap Disajikan
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($newTickets) && empty($cookingTickets)): ?>
        <div class="text-center py-20">
            <i class="fas fa-check-circle text-green-400 text-8xl mb-4"></i>
            <h2 class="text-white text-3xl font-bold mb-2">Semua Pesanan Selesai!</h2>
            <p class="text-gray-400 text-xl">Tidak ada pesanan yang perlu diproses</p>
        </div>
        <?php endif; ?>
    </main>

    <!-- Sound for new order -->
    <audio id="newOrderSound" preload="auto">
        <source src="../assets/sounds/notification.mp3" type="audio/mpeg">
        <source src="../assets/sounds/notification.ogg" type="audio/ogg">
    </audio>

    <script>
        // Initialize notification sound
        const notificationSound = new NotificationSound();
        
        // Store last order count
        let lastNewCount = <?= count($newTickets) ?>;
        let lastCookingCount = <?= count($cookingTickets) ?>;
        
        // Request notification permission on load
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
        // Function to play notification sound
        function playNotificationSound() {
            notificationSound.play();
        }
        
        // Function to show browser notification
        function showBrowserNotification(title, message) {
            if ('Notification' in window && Notification.permission === 'granted') {
                const notification = new Notification(title, {
                    body: message,
                    icon: '../assets/img/logo.png',
                    requireInteraction: false,
                    vibrate: [200, 100, 200]
                });
                
                notification.onclick = function() {
                    window.focus();
                    notification.close();
                };
            }
        }
        
        // Check for new orders (simple polling)
        async function checkNewOrders() {
            try {
                const response = await fetch('check_new_orders.php');
                const data = await response.json();
                
                if (data.new_ticket_count > lastNewCount) {
                    // New order arrived!
                    const newOrderCount = data.new_ticket_count - lastNewCount;
                    
                    // Play sound
                    playNotificationSound();
                    
                    // Show browser notification
                    showBrowserNotification(
                        '🔔 PESANAN BARU!',
                        `Ada ${newOrderCount} pesanan baru yang perlu diproses`
                    );
                    
                    // Show in-app notification
                    showNotification('error', `🔥 ${newOrderCount} PESANAN BARU!`);
                    
                    lastNewCount = data.new_ticket_count;
                    
                    // Reload page to show new orders
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (error) {
                console.error('Error checking new orders:', error);
            }
        }
        
        // Check every 3 seconds
        setInterval(checkNewOrders, 3000);
        
        async function startCooking(ticketId) {
            try {
                const response = await fetch('update_ticket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        action: 'start_cooking'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', 'Mulai memasak!');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification('error', result.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showNotification('error', 'Gagal memproses');
                console.error(error);
            }
        }
        
        async function markReady(ticketId) {
            try {
                const response = await fetch('update_ticket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        action: 'mark_ready'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', 'Pesanan siap disajikan!');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification('error', result.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showNotification('error', 'Gagal memproses');
                console.error(error);
            }
        }
        
        function showNotification(type, message) {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500'
            };
            
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg z-50 text-lg font-bold`;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'times'}-circle mr-2"></i>${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.remove(), 3000);
        }
        
        // Update clock
        setInterval(() => {
            const now = new Date();
            const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                           now.getMinutes().toString().padStart(2, '0');
            document.querySelectorAll('.text-xl.font-bold').forEach(el => {
                if (el.textContent.includes(':')) {
                    el.textContent = timeStr;
                }
            });
        }, 1000);
    </script>
</body>
</html>
