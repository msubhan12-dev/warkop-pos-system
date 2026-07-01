<?php
/**
 * Network Printer Settings
 * Configure network thermal printer
 */
require_once '../config/config.php';
requireRole(['kasir', 'owner']);

$user = getCurrentUser();
$saved = false;
$error = '';

// Get saved printer settings from session
$printerIp = $_SESSION['printer_ip'] ?? '192.168.1.100';
$printerPort = $_SESSION['printer_port'] ?? 9100;
$printerType = $_SESSION['printer_type'] ?? 'network';

// Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $printerIp = clean($_POST['printer_ip'] ?? '');
    $printerPort = (int)($_POST['printer_port'] ?? 9100);
    $printerType = clean($_POST['printer_type'] ?? 'network');
    
    // Validate IP
    if (!filter_var($printerIp, FILTER_VALIDATE_IP)) {
        $error = 'Invalid IP address format';
    } elseif ($printerPort < 1 || $printerPort > 65535) {
        $error = 'Invalid port number (1-65535)';
    } else {
        $_SESSION['printer_ip'] = $printerIp;
        $_SESSION['printer_port'] = $printerPort;
        $_SESSION['printer_type'] = $printerType;
        $saved = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Pengaturan Printer - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center space-x-3">
                <i class="fas fa-print text-2xl text-blue-600"></i>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">Pengaturan Printer</h1>
                    <p class="text-xs text-gray-500"><?= $user['full_name'] ?></p>
                </div>
            </div>
            <a href="index.php" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
        </div>
    </header>

    <main class="p-4 max-w-2xl mx-auto pb-32 sm:pb-24">
        <!-- Success Message -->
        <?php if ($saved): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>
            Pengaturan printer berhasil disimpan!
        </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= $error ?>
        </div>
        <?php endif; ?>

        <!-- Printer Type Selection -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="font-bold text-lg mb-4 flex items-center">
                <i class="fas fa-cog mr-2 text-blue-600"></i>
                Tipe Printer
            </h2>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <label class="flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-600 <?= $printerType === 'network' ? 'border-blue-600 bg-blue-50' : '' ?>">
                    <input type="radio" name="printer_type" value="network" class="mr-3" <?= $printerType === 'network' ? 'checked' : '' ?>>
                    <div>
                        <div class="font-semibold">Network Printer</div>
                        <div class="text-sm text-gray-600">Thermal via Network (ESC/POS)</div>
                    </div>
                </label>
                
                <label class="flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-600 <?= $printerType === 'browser' ? 'border-blue-600 bg-blue-50' : '' ?>">
                    <input type="radio" name="printer_type" value="browser" class="mr-3" <?= $printerType === 'browser' ? 'checked' : '' ?>>
                    <div>
                        <div class="font-semibold">Browser Print</div>
                        <div class="text-sm text-gray-600">Print via Browser Dialog</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Network Settings -->
        <form method="POST" class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="font-bold text-lg mb-4 flex items-center">
                <i class="fas fa-network-wired mr-2 text-blue-600"></i>
                Konfigurasi Network Printer
            </h2>

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        IP Address Printer
                    </label>
                    <input 
                        type="text" 
                        name="printer_ip" 
                        value="<?= $printerIp ?>"
                        placeholder="192.168.1.100"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <p class="text-xs text-gray-500 mt-1">Contoh: 192.168.1.100 atau 10.143.149.50</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Port
                    </label>
                    <input 
                        type="number" 
                        name="printer_port" 
                        value="<?= $printerPort ?>"
                        min="1"
                        max="65535"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <p class="text-xs text-gray-500 mt-1">Default: 9100 untuk ESC/POS</p>
                </div>

                <input type="hidden" name="printer_type" value="<?= $printerType ?>">
            </div>

            <!-- Buttons -->
            <div class="flex gap-3">
                <button 
                    type="button"
                    onclick="testConnection()"
                    class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-3 px-4 rounded-lg transition"
                >
                    <i class="fas fa-wifi mr-2"></i>Test Koneksi
                </button>
                <button 
                    type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition"
                >
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
            </div>
        </form>

        <!-- Info Boxes -->
        <div class="bg-blue-50 rounded-xl p-6 mb-6 border-l-4 border-blue-500">
            <h3 class="font-bold text-blue-900 mb-3">
                <i class="fas fa-info-circle mr-2"></i>
                Cara Setup Network Printer
            </h3>
            <ol class="list-decimal list-inside text-sm text-blue-900 space-y-2">
                <li>Hubungkan printer ke network (WiFi atau Ethernet)</li>
                <li>Cari IP address printer (lihat di printer menu)</li>
                <li>Input IP & port di form di atas</li>
                <li>Klik "Test Koneksi" untuk verify</li>
                <li>Jika sukses, pengaturan otomatis tersimpan</li>
            </ol>
        </div>

        <div class="bg-green-50 rounded-xl p-6 mb-6 border-l-4 border-green-500">
            <h3 class="font-bold text-green-900 mb-3">
                <i class="fas fa-check-circle mr-2"></i>
                Printer Kompatibel
            </h3>
            <ul class="list-disc list-inside text-sm text-green-900 space-y-1">
                <li>Epson TM-T20II, TM-T88, TM-U220</li>
                <li>Star Micronics TSP100, mC-Print3</li>
                <li>Bixolon SPP-R200, SPP-R400</li>
                <li>Semua printer dengan ESC/POS support</li>
            </ul>
        </div>

        <div class="bg-yellow-50 rounded-xl p-6 border-l-4 border-yellow-500">
            <h3 class="font-bold text-yellow-900 mb-3">
                <i class="fas fa-lightbulb mr-2"></i>
                Tips
            </h3>
            <ul class="list-disc list-inside text-sm text-yellow-900 space-y-1">
                <li>Port default untuk ESC/POS adalah 9100</li>
                <li>Test koneksi sebelum simpan pengaturan</li>
                <li>Printer harus di network yang sama dengan server</li>
                <li>Jika gagal, cek IP printer di printer menu/settings</li>
                <li>Bisa gunakan "Browser Print" sebagai fallback</li>
            </ul>
        </div>
    </main>

    <script>
        function testConnection() {
            const ip = document.querySelector('input[name="printer_ip"]').value;
            const port = document.querySelector('input[name="printer_port"]').value;
            
            if (!ip) {
                alert('IP address harus diisi');
                return;
            }
            
            if (!port) {
                alert('Port harus diisi');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'test_connection');
            formData.append('printer_ip', ip);
            formData.append('printer_port', port);
            formData.append('order_id', 1);
            
            fetch('print_network.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Koneksi berhasil!\n\nPrinter: ' + data.message);
                } else {
                    alert('❌ Koneksi gagal\n\n' + data.message);
                }
            })
            .catch(e => {
                alert('Error: ' + e.message);
            });
        }
    </script>
</body>
</html>
