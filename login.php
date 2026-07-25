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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
        }
        .font-outfit { font-family: 'Outfit', sans-serif; }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #0F766E 0%, #0B1121 50%, #111827 100%);
            position: relative;
            overflow: hidden;
        }
        
        .gradient-bg::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(16,185,129,0.15) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        .gradient-bg::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(34,197,94,0.1) 0%, transparent 70%);
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }
        
        @keyframes slide-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .slide-in {
            animation: slide-in 0.6s ease-out forwards;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .input-field {
            transition: all 0.3s ease;
        }
        
        .input-field:focus-within {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .btn-login {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .logo-bounce {
            animation: logo-bounce 3s ease-in-out infinite;
        }
        
        @keyframes logo-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
    </style>
</head>
<body class="login-container gradient-bg">
    
    <!-- Main Login Container -->
    <div class="w-full max-w-6xl px-4 sm:px-6 md:px-8 py-8 sm:py-12 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            
            <!-- Left Side: Brand & Features (Hidden on mobile, visible on md+) -->
            <div class="hidden lg:flex flex-col justify-center items-start text-white">
                <div class="mb-12 slide-in">
                    <div class="w-24 h-24 bg-white/10 backdrop-blur-md rounded-3xl p-2 shadow-2xl mb-8 logo-bounce overflow-hidden border border-white/20">
                        <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" alt="Logo" class="w-full h-full object-cover rounded-2xl">
                    </div>
                    <h1 class="text-5xl lg:text-6xl font-extrabold font-outfit mb-3 leading-tight">
                        <?= APP_NAME ?>
                    </h1>
                    <p class="text-emerald-200/80 text-lg font-medium">
                        Sistem Manajemen Warkop Terpadu
                    </p>
                </div>
                
                <!-- Features List -->
                <div class="space-y-6 slide-in" style="animation-delay: 0.2s">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-emerald-500/30">
                            <i class="fas fa-chart-line text-emerald-300 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-white text-lg">Dashboard Real-Time</p>
                            <p class="text-white/60 text-sm mt-1">Pantau pesanan dan transaksi secara langsung</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-blue-500/30">
                            <i class="fas fa-qrcode text-blue-300 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-white text-lg">Pembayaran QRIS</p>
                            <p class="text-white/60 text-sm mt-1">Verifikasi otomatis & real-time sync</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center flex-shrink-0 border border-purple-500/30">
                            <i class="fas fa-lock text-purple-300 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-white text-lg">Keamanan Terjamin</p>
                            <p class="text-white/60 text-sm mt-1">Enkripsi end-to-end & audit trail lengkap</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side: Login Form -->
            <div class="w-full slide-in" style="animation-delay: 0.1s">
                <div class="glass-effect rounded-3xl p-6 sm:p-8 md:p-10 shadow-2xl">
                    
                    <!-- Mobile Logo (visible only on mobile) -->
                    <div class="lg:hidden text-center mb-8">
                        <div class="w-20 h-20 bg-emerald-500/10 backdrop-blur-md rounded-2xl p-1.5 shadow-xl mb-4 mx-auto overflow-hidden border border-emerald-500/30">
                            <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" alt="Logo" class="w-full h-full object-cover rounded-xl">
                        </div>
                        <h2 class="text-2xl font-extrabold text-slate-900 font-outfit"><?= APP_NAME ?></h2>
                        <p class="text-slate-500 text-xs uppercase tracking-wider mt-1">Login Dashboard</p>
                    </div>
                    
                    <!-- Welcome Message -->
                    <div class="mb-8">
                        <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-outfit mb-2">Selamat Datang 👋</h3>
                        <p class="text-slate-500 text-sm font-medium">Masuk dengan kredensial staf Anda untuk melanjutkan</p>
                    </div>
                    
                    <!-- Error Message -->
                    <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg flex items-start gap-3 animate-pulse">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="font-bold text-sm">Login Gagal</p>
                            <p class="text-xs mt-1"><?= $error ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Success Message -->
                    <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 rounded-lg flex items-start gap-3">
                        <i class="fas fa-check-circle text-emerald-500 text-xl flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="font-bold text-sm">Berhasil</p>
                            <p class="text-xs mt-1"><?= $success ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Login Form -->
                    <form method="POST" action="" class="space-y-5">
                        
                        <!-- Username Field -->
                        <div>
                            <label for="username" class="block text-xs font-extrabold text-slate-700 mb-2.5 uppercase tracking-wider">
                                <i class="fas fa-user mr-1 text-emerald-500"></i> Username
                            </label>
                            <div class="input-field relative">
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    required
                                    class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white font-medium text-slate-700 placeholder-slate-400 transition-all duration-300"
                                    placeholder="Masukkan username Anda"
                                    autocomplete="username"
                                >
                            </div>
                        </div>
                        
                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-xs font-extrabold text-slate-700 mb-2.5 uppercase tracking-wider">
                                <i class="fas fa-lock mr-1 text-emerald-500"></i> Password
                            </label>
                            <div class="input-field relative">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required
                                    class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white font-medium text-slate-700 placeholder-slate-400 transition-all duration-300"
                                    placeholder="Masukkan password Anda"
                                    autocomplete="current-password"
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePassword()" 
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                                >
                                    <i class="fas fa-eye-slash" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="pt-6">
                            <button 
                                type="submit" 
                                class="btn-login w-full bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-500 hover:from-emerald-500 hover:via-emerald-400 hover:to-teal-400 text-white font-extrabold py-4 px-6 rounded-xl transition-all duration-300 shadow-[0_10px_30px_-8px_rgba(16,185,129,0.6)] hover:shadow-[0_15px_40px_-8px_rgba(16,185,129,0.7)] hover:-translate-y-1 flex items-center justify-center gap-2 group text-base sm:text-lg"
                            >
                                <span>Masuk ke Dashboard</span>
                                <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </div>
                    </form>
                    
                    <!-- Footer -->
                    <div class="mt-10 pt-6 border-t border-slate-200">
                        <p class="text-xs text-slate-500 text-center font-medium">
                            <i class="fas fa-shield-alt mr-1 text-emerald-500"></i>
                            &copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?> | Semua Hak Dilindungi
                        </p>
                    </div>
                </div>
                
                <!-- Info Box for Mobile -->
                <div class="lg:hidden mt-6 p-4 bg-white/10 backdrop-blur-md rounded-xl border border-white/20 text-white text-center text-xs font-medium">
                    <i class="fas fa-info-circle text-blue-300 mr-2"></i>
                    Gunakan kredensial staf untuk login ke dashboard admin
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }
        
        // Auto-focus username on page load
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            if (usernameInput) {
                usernameInput.focus();
            }
        });
    </script>
</body>
</html>
