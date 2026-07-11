<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    switch ($role) {
        case 'owner':
            header('Location: admin/index.php');
            break;
        case 'kasir':
            header('Location: kasir/index.php');
            break;
        case 'dapur':
            header('Location: dapur/index.php');
            break;
        case 'pelayan':
            header('Location: kasir/index.php');
            break;
        default:
            header('Location: customer/menu.php');
    }
    exit;
}

$error = '';
$success = '';

if (isset($_GET['timeout'])) {
    $error = 'Sesi Anda telah berakhir. Silakan login kembali.';
}

if (isset($_GET['logout'])) {
    $success = 'Anda telah berhasil logout.';
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['LAST_ACTIVITY'] = time();
            
            // Log audit
            createAuditLog('login', 'users', $user['id']);
            
            // Redirect based on role
            switch ($user['role']) {
                case 'owner':
                    header('Location: admin/index.php');
                    break;
                case 'kasir':
                    header('Location: kasir/index.php');
                    break;
                case 'dapur':
                    header('Location: dapur/index.php');
                    break;
                case 'pelayan':
                    header('Location: kasir/index.php');
                    break;
                default:
                    header('Location: customer/menu.php');
            }
            exit;
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0">
    <title>Login - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-left: 1px solid rgba(255,255,255,0.4);
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4 sm:p-8 relative overflow-y-auto overflow-x-hidden text-slate-800">

    <!-- Background Pattern/Image -->
    <div class="fixed inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?q=80&w=2047&auto=format&fit=crop" alt="Coffee Background" class="w-full h-full object-cover opacity-90">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-900/90 to-slate-900/90"></div>
    </div>

    <!-- Main Container -->
    <div class="w-full max-w-5xl bg-white/10 backdrop-blur-2xl rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row z-10 border border-white/20">
        
        <!-- Left Panel: Brand -->
        <div class="w-full md:w-5/12 lg:w-1/2 p-10 md:p-14 flex flex-col justify-center items-center text-center relative overflow-hidden">
            <div class="relative z-10">
                <div class="w-28 h-28 mx-auto bg-white rounded-full p-1.5 shadow-2xl mb-6 transform hover:scale-105 transition-transform duration-500 overflow-hidden ring-4 ring-emerald-500/30">
                    <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" alt="Logo" class="w-full h-full object-cover rounded-full">
                </div>
                <h1 class="text-4xl lg:text-5xl font-extrabold text-white mb-3 font-outfit tracking-tight drop-shadow-lg">
                    <?= APP_NAME ?>
                </h1>
                <p class="text-emerald-100/90 font-medium tracking-wide text-sm uppercase drop-shadow">
                    Sistem Manajemen Warkop Digital
                </p>
            </div>
        </div>

        <!-- Right Panel: Login Form -->
        <div class="w-full md:w-7/12 lg:w-1/2 p-8 sm:p-12 md:p-14 glass-panel flex flex-col justify-center">
            
            <div class="mb-10 text-center md:text-left">
                <h2 class="text-3xl font-extrabold text-slate-800 font-outfit mb-2">Selamat Datang 👋</h2>
                <p class="text-slate-500 text-sm font-medium">Silakan masuk menggunakan kredensial staf Anda.</p>
            </div>
            
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50/90 border border-red-200 text-red-700 rounded-xl text-sm flex items-center shadow-sm backdrop-blur-sm">
                <i class="fas fa-exclamation-circle mr-3 text-red-500 text-lg"></i>
                <span class="font-medium"><?= $error ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-emerald-50/90 border border-emerald-200 text-emerald-700 rounded-xl text-sm flex items-center shadow-sm backdrop-blur-sm">
                <i class="fas fa-check-circle mr-3 text-emerald-500 text-lg"></i>
                <span class="font-medium"><?= $success ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-5">
                <div>
                    <label for="username" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider text-[11px]">
                        Username
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-user text-slate-400"></i>
                        </div>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            class="w-full pl-11 pr-4 py-3.5 bg-white/80 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 focus:bg-white font-medium text-slate-700 transition-all duration-300 placeholder-slate-400"
                            placeholder="Masukkan username"
                            autocomplete="username"
                        >
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wider text-[11px]">
                        Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-slate-400"></i>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full pl-11 pr-4 py-3.5 bg-white/80 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 focus:bg-white font-medium text-slate-700 transition-all duration-300 placeholder-slate-400"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                        >
                    </div>
                </div>
                
                <div class="pt-4">
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-extrabold py-4 px-6 rounded-xl transition-all duration-300 shadow-[0_8px_20px_-6px_rgba(16,185,129,0.5)] hover:shadow-[0_12px_25px_-6px_rgba(16,185,129,0.6)] hover:-translate-y-0.5 flex items-center justify-center gap-2 group"
                    >
                        <span>Masuk ke Dashboard</span>
                        <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </form>
            
            <div class="mt-10 text-center md:text-left text-xs font-medium text-slate-500 border-t border-slate-200 pt-6">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?>. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </div>
</body>
</html>
