<?php
require_once '../config/config.php';
requireLogin();
$pageTitle = 'Ganti Password';
$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Semua field harus diisi';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password baru minimal 6 karakter';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password tidak cocok';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userDb = $stmt->fetch();
        
        if (!password_verify($oldPassword, $userDb['password'])) {
            $error = 'Password lama salah';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $_SESSION['user_id']]);
            createAuditLog('update', 'users', $_SESSION['user_id'], null, ['action' => 'change_password']);
            $success = 'Password berhasil diubah!';
        }
    }
}
include '../includes/header.php';
?>
<main class="p-4 pb-32 sm:pb-24 max-w-md mx-auto">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="text-center mb-6">
                <div class="inline-block bg-slate-100 rounded-full p-4 mb-3">
                    <i class="fas fa-lock text-3xl text-slate-700"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800"><?= $user['full_name'] ?></h2>
                <p class="text-sm text-gray-600">@<?= $user['username'] ?> • <?= ucfirst($user['role']) ?></p>
            </div>

            <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded">
                <i class="fas fa-check-circle mr-2"></i><?= $success ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-key mr-2"></i>Password Lama
                    </label>
                    <input 
                        type="password" 
                        name="old_password" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent"
                        placeholder="Masukkan password lama"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Password Baru
                    </label>
                    <input 
                        type="password" 
                        name="new_password" 
                        required
                        minlength="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent"
                        placeholder="Minimal 6 karakter"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Konfirmasi Password Baru
                    </label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        required
                        minlength="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent"
                        placeholder="Ketik ulang password baru"
                    >
                </div>

                <button 
                    type="submit"
                    class="w-full bg-slate-700 hover:bg-slate-800 text-white font-semibold py-3 px-4 rounded-lg transition"
                >
                    <i class="fas fa-check mr-2"></i>Ubah Password
                </button>

                <a href="index.php" class="block text-center text-gray-600 hover:text-gray-800 py-2">Batal</a>
            </form>
        </div>
    </main>
<?php include '../includes/footer.php'; ?>
