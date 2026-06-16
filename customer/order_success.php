<?php
require_once '../config/config.php';

$orderNumber = $_GET['order'] ?? null;

if (!$orderNumber) {
    header('Location: menu.php');
    exit;
}

// Get order details
$db = getDB();
$stmt = $db->prepare("
    SELECT o.*, t.table_number
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE o.order_number = ?
");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: menu.php');
    exit;
}

// Get order items
$stmt = $db->prepare("
    SELECT * FROM order_items WHERE order_id = ?
");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Pesanan Berhasil - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        @keyframes checkmark {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        .checkmark {
            animation: checkmark 0.6s ease-out;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Success Icon -->
        <div class="text-center mb-6">
            <div class="inline-block bg-green-100 rounded-full p-6 mb-4 checkmark">
                <i class="fas fa-check-circle text-6xl text-green-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Pesanan Berhasil!</h1>
            <p class="text-gray-600">Terima kasih atas pesanan Anda</p>
        </div>

        <!-- Order Details -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-4">
            <div class="text-center mb-6 pb-6 border-b">
                <p class="text-sm text-gray-600 mb-1">Nomor Pesanan</p>
                <p class="text-3xl font-bold text-purple-600"><?= $order['order_number'] ?></p>
            </div>

            <div class="space-y-4 mb-6">
                <?php if ($order['table_number']): ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">
                        <i class="fas fa-chair mr-2"></i>Meja
                    </span>
                    <span class="font-semibold"><?= $order['table_number'] ?></span>
                </div>
                <?php endif; ?>
                
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">
                        <i class="fas fa-user mr-2"></i>Nama
                    </span>
                    <span class="font-semibold"><?= $order['customer_name'] ?></span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">
                        <i class="fas fa-clock mr-2"></i>Waktu
                    </span>
                    <span class="font-semibold"><?= formatDateTime($order['created_at'], 'H:i') ?></span>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">
                        <i class="fas fa-shopping-bag mr-2"></i>Tipe
                    </span>
                    <span class="font-semibold"><?= getStatusText($order['order_type']) ?></span>
                </div>
            </div>

            <!-- Items -->
            <div class="border-t pt-4 mb-4">
                <h3 class="font-bold text-gray-800 mb-3">Detail Pesanan</h3>
                <div class="space-y-2">
                    <?php foreach ($items as $item): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">
                            <?= $item['quantity'] ?>x <?= $item['menu_name'] ?>
                        </span>
                        <span class="font-semibold"><?= formatRupiah($item['subtotal']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Total -->
            <div class="border-t pt-4">
                <div class="flex justify-between text-lg font-bold">
                    <span>Total</span>
                    <span class="text-purple-600"><?= formatRupiah($order['total']) ?></span>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg mb-4">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                Pesanan Anda sedang diproses. Silakan menunggu pemanggilan dari kasir.
            </p>
        </div>

        <!-- Actions -->
        <div class="space-y-3">
            <a 
                href="menu.php" 
                class="block w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition"
            >
                <i class="fas fa-utensils mr-2"></i>Pesan Lagi
            </a>
            <button 
                onclick="window.print()"
                class="block w-full bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3 px-6 rounded-lg border-2 border-gray-300 text-center transition"
            >
                <i class="fas fa-print mr-2"></i>Cetak Pesanan
            </button>
        </div>
    </div>

    <style>
        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</body>
</html>
