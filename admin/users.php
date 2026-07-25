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

$totalUsers = count($users);
$ownerCount = count(array_filter($users, fn($u) => $u['role'] === 'owner'));
$kasirCount = count(array_filter($users, fn($u) => $u['role'] === 'kasir'));
$staffCount = $totalUsers - $ownerCount - $kasirCount;

include '../includes/header.php';
?>

<main class="p-4 sm:p-6 pb-32 sm:pb-24 max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 font-outfit tracking-tight">Manajemen Karyawan & Akses</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Kelola akun pengguna, peran (*role*), dan kredensial login staf kedai.</p>
        </div>
        <button onclick="showAddModal()" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-2xl text-sm font-bold shadow-lg shadow-emerald-600/20 hover:shadow-xl transition-all cursor-pointer">
            <i class="fas fa-plus text-base"></i>
            <span>Tambah Karyawan Baru</span>
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Karyawan</p>
                <p class="text-xl font-extrabold text-slate-800 font-outfit mt-0.5"><?= $totalUsers ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <i class="fas fa-user-shield text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Owner / Admin</p>
                <p class="text-xl font-extrabold text-purple-600 font-outfit mt-0.5"><?= $ownerCount ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i class="fas fa-id-card text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Staf Kasir & Lapangan</p>
                <p class="text-xl font-extrabold text-blue-600 font-outfit mt-0.5"><?= $kasirCount + $staffCount ?></p>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm mb-6 flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" id="userSearchInput" onkeyup="filterUsers()" placeholder="Cari nama karyawan, username, atau HP..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
        </div>

        <div class="sm:w-56">
            <select id="userFilterSelect" onchange="filterUsers()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                <option value="all">Semua Role</option>
                <option value="owner">Owner</option>
                <option value="kasir">Kasir</option>
                <option value="dapur">Dapur</option>
                <option value="pelayan">Pelayan</option>
            </select>
        </div>
    </div>

    <!-- Users Grid -->
    <div id="usersGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($users as $u): ?>
        <div class="user-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group"
             data-name="<?= htmlspecialchars(strtolower($u['full_name'])) ?>"
             data-username="<?= htmlspecialchars(strtolower($u['username'])) ?>"
             data-phone="<?= htmlspecialchars(strtolower($u['phone'] ?? '')) ?>"
             data-role="<?= htmlspecialchars(strtolower($u['role'])) ?>">
            
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-600 font-extrabold text-lg flex items-center justify-center shrink-0 shadow-inner group-hover:scale-105 transition-transform">
                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-extrabold text-slate-800 font-outfit text-base truncate" title="<?= htmlspecialchars($u['full_name']) ?>">
                            <?= htmlspecialchars($u['full_name']) ?>
                        </h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider shrink-0 <?= $u['role'] === 'owner' ? 'bg-purple-50 text-purple-700 border border-purple-200/60' : ($u['role'] === 'kasir' ? 'bg-blue-50 text-blue-700 border border-blue-200/60' : 'bg-slate-100 text-slate-600') ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </div>

                    <p class="text-xs text-slate-400 font-medium mt-0.5 truncate">@<?= htmlspecialchars($u['username']) ?></p>

                    <div class="mt-3 space-y-1 text-xs text-slate-500 font-medium">
                        <?php if ($u['email']): ?>
                        <p class="flex items-center truncate"><i class="fas fa-envelope text-slate-300 w-4 text-center mr-1.5"></i> <?= htmlspecialchars($u['email']) ?></p>
                        <?php endif; ?>
                        <?php if ($u['phone']): ?>
                        <p class="flex items-center truncate"><i class="fas fa-phone text-slate-300 w-4 text-center mr-1.5"></i> <?= htmlspecialchars($u['phone']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2 mt-4">
                <button onclick='editUser(<?= json_encode($u) ?>)' class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-bold transition-colors">
                    <i class="fas fa-edit text-xs"></i> Edit
                </button>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Yakin matikan akun karyawan ini?')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold transition-colors">
                    <i class="fas fa-trash text-xs"></i> Hapus
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Modal Add/Edit User -->
<div id="userModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 max-h-[90vh] overflow-y-auto shadow-2xl transition-all border border-slate-100">
        <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
            <div>
                <h3 class="text-xl font-extrabold text-slate-800 font-outfit" id="modalTitle">Tambah Karyawan Baru</h3>
                <p class="text-slate-400 text-xs mt-0.5">Isi rincian informasi dan role staf kedai.</p>
            </div>
            <button onclick="closeModal()" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="id" id="userId">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Nama Lengkap</label>
                    <input type="text" name="full_name" id="userFullName" required placeholder="Cth: Ahmad Rifai" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Username</label>
                        <input type="text" name="username" id="userUsername" required placeholder="rifai123" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Role / Peran</label>
                        <select name="role" id="userRole" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                            <option value="owner">Owner</option>
                            <option value="kasir">Kasir</option>
                            <option value="dapur">Dapur</option>
                            <option value="pelayan">Pelayan</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Email</label>
                    <input type="email" name="email" id="userEmail" required placeholder="rifai@gmail.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">No. Telepon / WA</label>
                    <input type="text" name="phone" id="userPhone" placeholder="08123456789" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Password <span class="text-[10px] text-slate-400 font-normal lowercase">(kosongkan jika tidak diubah)</span></label>
                    <input type="password" name="password" id="userPassword" placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal()" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3 rounded-xl transition-colors text-sm">
                    Batal
                </button>
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-emerald-600/20 transition-all text-sm">
                    Simpan User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Karyawan Baru';
    document.querySelector('#userModal form').reset();
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

function filterUsers() {
    const searchVal = document.getElementById('userSearchInput').value.toLowerCase().trim();
    const filterVal = document.getElementById('userFilterSelect').value;
    const cards = document.querySelectorAll('.user-card');

    cards.forEach(card => {
        const name = card.getAttribute('data-name');
        const username = card.getAttribute('data-username');
        const phone = card.getAttribute('data-phone');
        const role = card.getAttribute('data-role');

        const matchesSearch = name.includes(searchVal) || username.includes(searchVal) || phone.includes(searchVal);
        const matchesRole = (filterVal === 'all') || (role === filterVal);

        if (matchesSearch && matchesRole) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>

