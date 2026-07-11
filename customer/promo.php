<?php
require_once '../config/config.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM promos WHERE is_active = 1 AND (valid_until >= CURRENT_DATE OR valid_until IS NULL) ORDER BY created_at DESC");
$promos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <title>Promo - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #0B1121;
            color: #f1f5f9;
        }
        .font-outfit { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="pb-24">
    <!-- Header -->
    <header class="bg-slate-900/80 backdrop-blur-md sticky top-0 z-40 border-b border-slate-800">
        <div class="px-6 py-4 flex items-center justify-center">
            <h1 class="font-extrabold text-2xl font-outfit text-white tracking-tight drop-shadow-sm">
                🔥 Promo Spesial
            </h1>
        </div>
    </header>

    <main class="p-6 space-y-6 max-w-2xl mx-auto">
        <?php foreach ($promos as $promo): ?>
        <div class="bg-slate-800 rounded-3xl overflow-hidden shadow-[0_10px_30px_-10px_rgba(0,0,0,0.5)] border border-slate-700/50 group">
            <div class="relative h-64 overflow-hidden bg-slate-900">
                <img src="<?= UPLOADS_URL . '/' . $promo['image_path'] ?>" alt="<?= htmlspecialchars($promo['title']) ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/20 to-transparent"></div>
                
                <?php if ($promo['valid_until']): ?>
                <div class="absolute top-4 right-4 bg-rose-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg flex items-center gap-2 backdrop-blur-sm">
                    <i class="fas fa-clock animate-pulse"></i> 
                    S/d <?= formatDateTime($promo['valid_until'], 'd M Y') ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="p-6 relative">
                <!-- Decorative floating circle -->
                <div class="absolute -top-10 right-6 w-16 h-16 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl rotate-12 opacity-20 blur-xl pointer-events-none"></div>
                
                <h2 class="font-extrabold text-2xl font-outfit text-white mb-2"><?= htmlspecialchars($promo['title']) ?></h2>
                <?php if ($promo['description']): ?>
                    <p class="text-slate-400 text-sm leading-relaxed mb-4"><?= nl2br(htmlspecialchars($promo['description'])) ?></p>
                <?php endif; ?>
                
                <a href="menu.php" class="inline-block mt-2 w-full text-center bg-slate-700/50 hover:bg-emerald-600 text-emerald-400 hover:text-white font-bold py-3.5 px-6 rounded-2xl transition-all duration-300 border border-slate-600/50 hover:border-emerald-500 group-hover:shadow-[0_0_20px_rgba(16,185,129,0.3)]">
                    Gunakan Promo <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($promos)): ?>
        <div class="text-center py-20 px-6">
            <div class="w-24 h-24 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-slate-700">
                <i class="fas fa-tags text-4xl text-slate-500"></i>
            </div>
            <h3 class="font-extrabold text-2xl font-outfit text-white mb-2">Yah, belum ada promo 😢</h3>
            <p class="text-slate-400">Nantikan kejutan promo menarik dari kami selanjutnya!</p>
        </div>
        <?php endif; ?>
    </main>

    <?php include 'bottom_nav.php'; ?>
</body>
</html>
