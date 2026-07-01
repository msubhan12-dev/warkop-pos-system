<?php
require_once '../config/config.php';
requireRole(['owner']);
$pageTitle = 'Karyawan';
$user = getCurrentUser();
$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: users.php');
    exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $full_name = clean($_POST['full_name']);
    $role = clean($_POST['role']);
    $phone = clean($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($id) {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, phone=?, password=? WHERE id=?");
            $stmt->execute([$username, $email, $full_name, $role, $phone, $hash, $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, phone=? WHERE id=?");
            $stmt->execute([$username, $email, $full_name, $role, $phone, $id]);
        }
    } else {
        $hash = password_hash($password ?: 'password', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, phone) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$username, $email, $hash, $full_name, $role, $phone]);
    }
    header('Location: users.php');
    exit;
}

$stmt = $db->query("SELECT * FROM users WHERE is_active = 1 ORDER BY role, full_name");
$users = $stmt->fetchAll();
include '../includes/header.php';
?>
<main class="p-4 pb-32 sm:pb-24">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold text-lg">Daftar Karyawan</h2>
            <button onclick="showAddModal()" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                <i class="fas fa-plus mr-2"></i>Tambah
            </button>
        </div>
        <div class="space-y-3">
            <?php foreach ($users as $u): ?>
            <div class="border rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 flex-1">
                        <div class="bg-slate-100 w-12 h-12 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-slate-700"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-bold"><?= $u['full_name'] ?></p>
                            <p class="text-sm text-gray-600">@<?= $u['username'] ?> • <?= ucfirst($u['role']) ?></p>
                            <p class="text-xs text-gray-500"><?= $u['phone'] ?></p>
                        </div>
                    </div>
                    <div class="space-x-2">
                        <button onclick='editUser(<?= json_encode($u) ?>)' class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Yakin hapus?')" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Modal -->
<div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold mb-4" id="modalTitle">Tambah Karyawan</h3>
        <form method="POST">
            <input type="hidden" name="id" id="userId">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Username</label>
                    <input type="text" name="username" id="userUsername" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" id="userEmail" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
                    <input type="text" name="full_name" id="userFullName" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Role</label>
                    <select name="role" id="userRole" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="owner">Owner</option>
                        <option value="kasir">Kasir</option>
                        <option value="dapur">Dapur</option>
                        <option value="pelayan">Pelayan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Telepon</label>
                    <input type="text" name="phone" id="userPhone" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Password <span class="text-xs text-gray-500">(kosongkan jika tidak diubah)</span></label>
                    <input type="password" name="password" id="userPassword" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
            <div class="flex space-x-2 mt-4">
                <button type="submit" class="flex-1 bg-slate-700 hover:bg-slate-800 text-white py-2 rounded-lg">Simpan</button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Karyawan';
    document.querySelector('form').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userModal').classList.remove('hidden');
}

function editUser(user) {
    document.getElementById('modalTitle').textContent = 'Edit Karyawan';
    document.getElementById('userId').value = user.id;
    document.getElementById('userUsername').value = user.username;
    document.getElementById('userEmail').value = user.email;
    document.getElementById('userFullName').value = user.full_name;
    document.getElementById('userRole').value = user.role;
    document.getElementById('userPhone').value = user.phone || '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('userModal').classList.add('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
