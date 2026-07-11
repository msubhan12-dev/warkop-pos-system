<?php
require_once '../config/config.php';
requireRole(['owner', 'admin']);

$tableId = (int)($_GET['id'] ?? 0);

if (!$tableId) {
    die('Table ID required');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM tables WHERE id = ?");
$stmt->execute([$tableId]);
$table = $stmt->fetch();

if (!$table) {
    die('Table not found');
}

// Generate the URL for this table
// E.g. https://arrahmanherb.kesug.com/?table=1
$tableUrl = APP_URL . '/?table=' . urlencode($table['table_number']);
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' . urlencode($tableUrl);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Meja <?= $table['table_number'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; }
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .print-area { box-shadow: none !important; border: 2px solid #e2e8f0; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <!-- Action Buttons -->
    <div class="fixed top-4 right-4 flex flex-col gap-2 no-print z-50">
        <button onclick="window.print()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-xl shadow-lg font-bold flex items-center transition-transform hover:scale-105">
            <i class="fas fa-print mr-2"></i> Cetak QR Code
        </button>
        <button onclick="window.close()" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-3 rounded-xl shadow-lg font-bold flex items-center transition-transform hover:scale-105">
            <i class="fas fa-times mr-2"></i> Tutup
        </button>
    </div>

    <!-- QR Code Card -->
    <div class="print-area bg-white rounded-[3rem] shadow-2xl overflow-hidden w-full max-w-md relative flex flex-col">
        <!-- Header Pattern -->
        <div class="h-32 bg-slate-900 relative">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 20px 20px;"></div>
            <div class="absolute inset-x-0 bottom-0 flex justify-center translate-y-1/2">
                <div class="bg-white p-2 rounded-2xl shadow-xl">
                    <div class="w-20 h-20 bg-emerald-500 rounded-xl flex items-center justify-center text-white text-3xl">
                        <i class="fas fa-utensils"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="px-8 pt-16 pb-10 text-center flex-1">
            <h1 class="text-3xl font-extrabold text-slate-800 mb-1"><?= APP_NAME ?></h1>
            <p class="text-slate-500 font-medium mb-8">Scan untuk memesan makanan langsung dari meja Anda!</p>
            
            <div class="bg-slate-50 p-4 rounded-3xl inline-block mb-6 border-2 border-slate-100 shadow-inner">
                <img src="<?= $qrApiUrl ?>" alt="QR Code Meja <?= $table['table_number'] ?>" class="w-56 h-56 rounded-xl">
            </div>
            
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 py-3 px-6 rounded-2xl inline-flex items-center gap-3 w-full justify-center">
                <span class="text-lg font-semibold uppercase tracking-widest text-emerald-600">Meja</span>
                <span class="text-4xl font-black"><?= $table['table_number'] ?></span>
            </div>
            
            <p class="mt-8 text-xs text-slate-400 font-medium tracking-wider uppercase">Didukung oleh Warkop OS</p>
        </div>
    </div>

    <script>
        // Auto print prompt after images load
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
