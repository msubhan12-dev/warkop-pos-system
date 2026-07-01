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
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>POS SYSTEM - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #181311 0%, #3e2621 100%);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo/Brand -->
        <div class="text-center mb-8">
            <div class="inline-block bg-white rounded-full p-1.5 shadow-xl mb-4 transform hover:scale-105 transition duration-300 overflow-hidden w-24 h-24">
                <img src="https://mms.img.susercontent.com/85fa98256609ae0a681bf062317895b0" alt="Logo" class="w-full h-full object-cover rounded-full">
            </div>
            <h1 class="text-4xl font-extrabold text-white mb-2 font-outfit tracking-tight"><?= APP_NAME ?></h1>
            <p class="text-stone-300 font-medium tracking-wide text-sm uppercase">Sistem Manajemen Toko Herbal Digital</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl overflow-hidden border border-stone-200">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-stone-800 mb-6 text-center font-outfit">Login Staf & Owner</h2>
                
                <?php if ($error): ?>
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg text-sm flex items-center">
                    <i class="fas fa-exclamation-circle mr-2 text-base"></i><?= $error ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg text-sm flex items-center">
                    <i class="fas fa-check-circle mr-2 text-base"></i><?= $success ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-semibold text-stone-700 mb-2">
                            <i class="fas fa-user mr-2 text-stone-500"></i>Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            class="w-full px-4 py-3 border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition duration-200"
                            placeholder="Masukkan username"
                            autocomplete="username"
                        >
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-semibold text-stone-700 mb-2">
                            <i class="fas fa-lock mr-2 text-stone-500"></i>Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition duration-200"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl transition duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-sign-in-alt"></i>Masuk ke Dashboard
                    </button>
                </form>
            </div>
            
            <!-- Demo Credentials -->
            <!-- <div class="bg-gray-50 px-8 py-6 border-t border-gray-200">
                <h3 class="font-semibold text-gray-700 mb-3 text-center">Demo Credentials</h3>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <p class="font-semibold text-slate-700 mb-1">Owner</p>
                        <p class="text-gray-600">admin / password</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <p class="font-semibold text-blue-600 mb-1">Kasir</p>
                        <p class="text-gray-600">kasir1 / password</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <p class="font-semibold text-orange-600 mb-1">Dapur</p>
                        <p class="text-gray-600">dapur1 / password</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <p class="font-semibold text-green-600 mb-1">Pelayan</p>
                        <p class="text-gray-600">pelayan1 / password</p>
                    </div>
                </div>
            </div> -->
        </div>
        
        <!-- Customer Access -->
        <div class="mt-6 text-center">
            <a href="customer/menu.php" class="inline-block bg-white hover:bg-gray-100 text-slate-700 font-semibold py-3 px-6 rounded-lg shadow-lg transition">
                <i class="fas fa-qrcode mr-2"></i>Akses Menu Customer (QR)
            </a>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-center text-white text-sm">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?></p>
            <p class="mt-1 text-slate-400">Low Budget / Full Free Starter Plan</p>
        </div>
    </div>
</body>
</html>
