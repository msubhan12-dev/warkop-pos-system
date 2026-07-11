<?php
require_once '../config/config.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM reels WHERE is_active = 1 ORDER BY created_at DESC");
$reels = $stmt->fetchAll();

function getEmbedHtml($url, $title) {
    $url = trim($url);
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    
    // Direct video file
    if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
        return '<div class="absolute inset-0 flex flex-col items-center justify-center text-slate-500 z-0">
                    <i class="fas fa-circle-notch fa-spin text-4xl mb-3"></i>
                    <span class="text-xs font-bold tracking-widest uppercase">Memuat...</span>
                </div>
                <video class="reel-video relative z-10 bg-transparent" loop muted playsinline preload="auto" src="'.htmlspecialchars($url).'"></video>
                <div class="play-overlay z-20">
                    <div class="w-16 h-16 bg-black/50 backdrop-blur-md rounded-full flex items-center justify-center">
                        <i class="fas fa-play text-white text-2xl ml-1"></i>
                    </div>
                </div>';
    }
    
    // YouTube
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $match)) {
        $videoId = $match[1];
        return '<iframe class="w-full h-full pointer-events-auto" src="https://www.youtube.com/embed/'.$videoId.'?autoplay=0&loop=1&playlist='.$videoId.'&controls=1&showinfo=0&rel=0&modestbranding=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    }
    
    // TikTok
    if (preg_match('/tiktok\.com\/.*?video\/(\d+)/i', $url, $match)) {
        $videoId = $match[1];
        return '<div class="absolute inset-0 overflow-hidden bg-black flex items-center justify-center pointer-events-auto">
                    <iframe class="w-[105%] h-[105%] max-w-none transform scale-[1.02]" src="https://www.tiktok.com/embed/v2/'.$videoId.'" frameborder="0" allow="fullscreen" allowfullscreen></iframe>
                </div>';
    }
    
    // Instagram
    if (preg_match('/instagram\.com\/(?:p|reel)\/([a-zA-Z0-9_-]+)/i', $url, $match)) {
        $videoId = $match[1];
        return '<div class="absolute inset-0 overflow-hidden bg-black flex items-center justify-center pointer-events-auto">
                    <iframe class="w-[140%] h-[140%] max-w-none transform scale-125" src="https://www.instagram.com/reel/'.$videoId.'/embed" scrolling="no" frameborder="0" allowfullscreen></iframe>
                </div>';
    }
    
    // Fallback: show the button
    return '<div class="w-full h-full flex flex-col items-center justify-center bg-slate-900 p-6 text-center">
                <div class="w-24 h-24 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center mb-6 shadow-[0_0_30px_rgba(16,185,129,0.3)] animate-pulse">
                    <i class="fas fa-link text-4xl text-white"></i>
                </div>
                <h2 class="text-xl font-bold font-outfit mb-4">'.htmlspecialchars($title).'</h2>
                <a href="'.htmlspecialchars($url).'" target="_blank" class="bg-white text-slate-900 font-bold py-3 px-8 rounded-full pointer-events-auto">
                    Tonton Video
                </a>
            </div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <title>Reels - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #000;
            color: #fff;
            margin: 0;
            overflow: hidden; /* Prevent body scroll */
        }
        .font-outfit { font-family: 'Outfit', sans-serif; }
        
        /* Snap scrolling container */
        .reels-container {
            height: calc(100dvh - 96px); /* Leave space for bottom nav + padding */
            overflow-y: scroll;
            scroll-snap-type: y mandatory;
            scroll-behavior: smooth;
        }
        
        /* Hide scrollbar */
        .reels-container::-webkit-scrollbar {
            display: none;
        }
        .reels-container {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .reel-item {
            height: 100%;
            width: 100%;
            scroll-snap-align: start;
            position: relative;
            background-color: #111;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .reel-video, .reel-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .reel-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px 20px 40px 20px;
            background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.4) 50%, transparent 100%);
            z-index: 10;
            pointer-events: none; /* Let clicks pass through to iframe */
        }
        
        .play-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.3);
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 5;
            pointer-events: none;
        }
        .paused .play-overlay {
            opacity: 1;
        }
    </style>
</head>
<body>
    
    <!-- Top Header -->
    <div class="absolute top-0 left-0 right-0 p-6 z-20 flex justify-between items-center bg-gradient-to-b from-black/80 to-transparent pointer-events-none">
        <h1 class="font-extrabold text-2xl font-outfit text-white drop-shadow-md">
            Warkop Reels
        </h1>
        <i class="fas fa-camera text-white/80 text-xl drop-shadow-md"></i>
    </div>

    <!-- Reels Container -->
    <div class="reels-container" id="reelsContainer">
        <?php foreach ($reels as $reel): ?>
        <div class="reel-item" data-type="<?= $reel['is_url'] ? 'url' : 'file' ?>">
            
            <?php if ($reel['is_url']): ?>
                <?= getEmbedHtml($reel['media_path'], $reel['title']) ?>
            <?php else: ?>
                <?php 
                    $ext = strtolower(pathinfo($reel['media_path'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['mp4', 'webm', 'ogg'])):
                ?>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-500 z-0">
                        <i class="fas fa-circle-notch fa-spin text-4xl mb-3"></i>
                        <span class="text-xs font-bold tracking-widest uppercase">Memuat...</span>
                    </div>
                    <video class="reel-video relative z-10 bg-transparent" loop muted playsinline preload="auto" src="<?= UPLOADS_URL . '/' . $reel['media_path'] ?>"></video>
                    <div class="play-overlay z-20">
                        <div class="w-16 h-16 bg-black/50 backdrop-blur-md rounded-full flex items-center justify-center">
                            <i class="fas fa-play text-white text-2xl ml-1"></i>
                        </div>
                    </div>
                <?php else: ?>
                    <img class="reel-image relative z-10" src="<?= UPLOADS_URL . '/' . $reel['media_path'] ?>">
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="reel-overlay flex justify-between items-end">
                <div class="flex-1 pr-4">
                    <div class="flex items-center gap-2 mb-2">
                        <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" class="w-8 h-8 rounded-full border border-white/50">
                        <span class="font-bold font-outfit">@Arrahmanherb</span>
                    </div>
                    <p class="text-sm text-gray-200"><?= htmlspecialchars($reel['title']) ?></p>
                </div>
                
                <div class="flex flex-col items-center gap-6 pb-2 pointer-events-auto">
                    <button class="flex flex-col items-center gap-1 group text-white hover:text-rose-500 transition-colors">
                        <div class="w-12 h-12 bg-black/40 backdrop-blur-sm rounded-full flex items-center justify-center border border-white/10 group-hover:bg-rose-500/20">
                            <i class="fas fa-heart text-2xl"></i>
                        </div>
                    </button>
                    <button class="flex flex-col items-center gap-1 group text-white hover:text-emerald-400 transition-colors" onclick="window.location.href='menu.php'">
                        <div class="w-12 h-12 bg-black/40 backdrop-blur-sm rounded-full flex items-center justify-center border border-white/10 group-hover:bg-emerald-400/20">
                            <i class="fas fa-shopping-basket text-xl"></i>
                        </div>
                        <span class="text-xs font-bold">Pesan</span>
                    </button>
                    <button class="flex flex-col items-center gap-1 group text-white hover:text-blue-400 transition-colors">
                        <div class="w-12 h-12 bg-black/40 backdrop-blur-sm rounded-full flex items-center justify-center border border-white/10 group-hover:bg-blue-400/20">
                            <i class="fas fa-share text-xl"></i>
                        </div>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($reels)): ?>
        <div class="w-full h-full flex flex-col items-center justify-center bg-slate-900 text-center px-6">
            <i class="fas fa-video-slash text-6xl text-slate-700 mb-4"></i>
            <h2 class="text-xl font-bold font-outfit text-white mb-2">Belum Ada Reels</h2>
            <p class="text-slate-400 text-sm">Kembali lagi nanti untuk melihat konten menarik kami!</p>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'bottom_nav.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('reelsContainer');
            const items = document.querySelectorAll('.reel-item');
            let currentVideo = null;

            // Unmute all videos on first interaction
            document.body.addEventListener('click', function unmuteAll() {
                document.querySelectorAll('video').forEach(v => v.muted = false);
            }, { once: true });

            // Function to handle video playback based on visibility
            const handleIntersection = (entries, observer) => {
                entries.forEach(entry => {
                    const video = entry.target.querySelector('video');
                    if (!video) return;

                    if (entry.isIntersecting) {
                        // Play video when visible
                        video.play().catch(e => console.log('Autoplay blocked', e));
                        currentVideo = video;
                        entry.target.classList.remove('paused');
                    } else {
                        // Pause video when out of view
                        video.pause();
                        video.currentTime = 0; // Reset
                        entry.target.classList.add('paused');
                    }
                });
            };

            const observer = new IntersectionObserver(handleIntersection, {
                root: container,
                threshold: 0.7 // Trigger when 70% of the item is visible
            });

            items.forEach(item => observer.observe(item));

            // Toggle play/pause on click
            items.forEach(item => {
                item.addEventListener('click', (e) => {
                    // Ignore clicks on buttons/links
                    if (e.target.closest('button') || e.target.closest('a')) return;
                    
                    const video = item.querySelector('video');
                    if (video) {
                        if (video.paused) {
                            video.play();
                            item.classList.remove('paused');
                        } else {
                            video.pause();
                            item.classList.add('paused');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
