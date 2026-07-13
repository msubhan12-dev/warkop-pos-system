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
    <script src="https://cdn.tailwindcss.com?v=1783809316.4548428"></script>
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

    <main class="px-4 sm:px-6 py-6 sm:py-8 mb-24 max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            <?php foreach ($promos as $promo): ?>
            <div class="bg-slate-800/40 backdrop-blur-md rounded-[2rem] overflow-hidden shadow-lg border border-slate-700/50 group hover:shadow-[0_20px_40px_-15px_rgba(59,130,246,0.3)] hover:-translate-y-1 transition-all duration-300 flex flex-col h-full">
                <!-- Image Container -->
                <div class="relative h-48 sm:h-56 overflow-hidden bg-slate-900 flex-shrink-0">
                    <img src="<?= UPLOADS_URL . '/' . $promo['image_path'] ?>" alt="<?= htmlspecialchars($promo['title']) ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 opacity-90 group-hover:opacity-100">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent"></div>
                    
                    <?php if ($promo['valid_until']): ?>
                    <div class="absolute top-4 right-4 bg-rose-500 text-white text-[10px] sm:text-xs font-bold px-3 py-1.5 rounded-full shadow-[0_0_15px_rgba(244,63,94,0.5)] flex items-center gap-2 border border-rose-400/50">
                        <i class="fas fa-clock animate-pulse"></i> 
                        S/d <?= formatDateTime($promo['valid_until'], 'd M Y') ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Decorative Badge -->
                    <div class="absolute bottom-4 left-4">
                        <span class="bg-blue-500/90 backdrop-blur-sm text-white text-[10px] sm:text-xs font-black px-3 py-1.5 rounded-lg uppercase tracking-widest shadow-lg border border-blue-400/50">
                            Spesial
                        </span>
                    </div>
                </div>
                
                <!-- Content Container -->
                <div class="p-5 sm:p-6 relative flex-1 flex flex-col">
                    <!-- Decorative floating element -->
                    <div class="absolute -top-12 right-6 w-20 h-20 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-full opacity-10 blur-2xl pointer-events-none group-hover:opacity-30 transition-opacity duration-500"></div>
                    
                    <h2 class="font-bold text-xl sm:text-2xl font-outfit text-slate-100 mb-2 leading-tight drop-shadow-sm group-hover:text-blue-400 transition-colors"><?= htmlspecialchars($promo['title']) ?></h2>
                    
                    <?php if ($promo['description']): ?>
                        <div class="text-xs sm:text-sm text-slate-400 leading-relaxed mb-6 flex-1"><?= nl2br(htmlspecialchars($promo['description'])) ?></div>
                    <?php endif; ?>
                    
                    <a href="menu.php" class="mt-auto w-full text-center bg-slate-700/50 hover:bg-gradient-to-r hover:from-blue-600 hover:to-cyan-500 text-blue-400 hover:text-white font-bold py-3 sm:py-3.5 px-6 rounded-xl sm:rounded-2xl transition-all duration-300 border border-slate-600/50 hover:border-transparent group-hover:shadow-[0_8px_20px_-6px_rgba(59,130,246,0.5)] flex items-center justify-center gap-2">
                        Gunakan Promo <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($promos)): ?>
        <div class="text-center py-20 px-6 max-w-sm mx-auto">
            <div class="w-20 h-20 sm:w-24 sm:h-24 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-slate-700/50">
                <i class="fas fa-tags text-3xl sm:text-4xl text-slate-500"></i>
            </div>
            <h3 class="font-extrabold text-xl sm:text-2xl font-outfit text-slate-200 mb-2 drop-shadow-sm">Yah, belum ada promo 😢</h3>
            <p class="text-xs sm:text-sm text-slate-400 font-medium leading-relaxed">Nantikan kejutan promo menarik dari kami selanjutnya! Jangan lupa cek lagi nanti ya.</p>
        </div>
        <?php endif; ?>
    </main>

    <?php include 'bottom_nav.php'; ?>
</body>
</html>
