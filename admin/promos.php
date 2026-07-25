<?php
require_once '../config/config.php';
requireRole(['owner']);
$pageTitle = 'Kelola Promo';
$user = getCurrentUser();
$db = getDB();

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $title = clean($_POST['title']);
        $description = clean($_POST['description']);
        $valid_until = clean($_POST['valid_until']);
        
        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $newName = 'promo_' . time() . '.' . $ext;
                $uploadPath = UPLOADS_PATH . '/' . $newName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $stmt = $db->prepare("INSERT INTO promos (title, description, image_path, valid_until) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$title, $description, $newName, $valid_until])) {
                        $message = '<div class="bg-emerald-100 text-emerald-800 p-3 rounded-lg mb-4">Promo berhasil ditambahkan.</div>';
                    }
                } else {
                    $message = '<div class="bg-red-100 text-red-800 p-3 rounded-lg mb-4">Gagal upload gambar.</div>';
                }
            } else {
                $message = '<div class="bg-red-100 text-red-800 p-3 rounded-lg mb-4">Format file tidak didukung.</div>';
            }
        } else {
            $message = '<div class="bg-red-100 text-red-800 p-3 rounded-lg mb-4">Gambar wajib diupload.</div>';
        }
    }
}

// Handle delete & toggle
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("UPDATE promos SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: promos.php');
    exit;
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE promos SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: promos.php');
    exit;
}

$stmt = $db->query("SELECT * FROM promos ORDER BY created_at DESC");
$promos = $stmt->fetchAll();

$totalPromos = count($promos);
$activePromosCount = count(array_filter($promos, fn($p) => $p['is_active'] == 1));
$inactivePromosCount = $totalPromos - $activePromosCount;

include '../includes/header.php';
?>
<main class="p-4 sm:p-6 pb-32 sm:pb-24 max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 font-outfit tracking-tight">Kelola Promo & Diskon</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Buat dan kelola spanduk promo penawaran menarik untuk pelanggan.</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center shrink-0">
                <i class="fas fa-tags text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Promo</p>
                <p class="text-xl font-extrabold text-slate-800 font-outfit mt-0.5"><?= $totalPromos ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Promo Aktif</p>
                <p class="text-xl font-extrabold text-emerald-600 font-outfit mt-0.5"><?= $activePromosCount ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                <i class="fas fa-eye-slash text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Non-Aktif</p>
                <p class="text-xl font-extrabold text-slate-600 font-outfit mt-0.5"><?= $inactivePromosCount ?></p>
            </div>
        </div>
    </div>

    <?= $message ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Form Add Promo -->
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-slate-100 h-fit">
            <h2 class="font-extrabold text-lg font-outfit mb-1 text-slate-800">Tambah Promo Baru</h2>
            <p class="text-slate-400 text-xs mb-5">Unggah gambar spanduk promo dan tentukan batas waktunya.</p>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Judul Promo</label>
                    <input type="text" name="title" required placeholder="Cth: Diskon Kopi 20%" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Deskripsi Singkat</label>
                    <textarea name="description" rows="3" placeholder="Tuliskan syarat & ketentuan promo..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"></textarea>
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Berlaku Sampai</label>
                    <input type="date" name="valid_until" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Gambar Promo Banner</label>
                    <input type="file" name="image" accept="image/*" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                </div>
                
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 text-sm mt-2">
                    <i class="fas fa-plus text-xs"></i> Simpan Promo
                </button>
            </form>
        </div>
        
        <!-- Promo List Section -->
        <div class="md:col-span-2 space-y-4">
            <!-- Search & Filter Bar -->
            <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="promoSearchInput" onkeyup="filterPromos()" placeholder="Cari judul promo..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>

                <div class="sm:w-48">
                    <select id="promoFilterSelect" onchange="filterPromos()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                        <option value="all">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non-Aktif</option>
                    </select>
                </div>
            </div>

            <div id="promosGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($promos as $promo): ?>
                <div class="promo-card bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden group hover:shadow-md transition-all flex flex-col"
                     data-title="<?= htmlspecialchars(strtolower($promo['title'])) ?>"
                     data-active="<?= $promo['is_active'] ?>">
                    
                    <div class="h-40 bg-slate-100 relative overflow-hidden">
                        <img src="<?= UPLOADS_URL . '/' . $promo['image_path'] ?>" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" alt="Promo">
                        <?php if (!$promo['is_active']): ?>
                            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center">
                                <span class="bg-rose-500 text-white text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">Tidak Aktif</span>
                            </div>
                        <?php else: ?>
                            <div class="absolute top-3 left-3">
                                <span class="bg-emerald-500/90 text-white text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider backdrop-blur-xs shadow-sm">Aktif</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-extrabold text-base text-slate-800 font-outfit mb-1 truncate" title="<?= htmlspecialchars($promo['title']) ?>"><?= htmlspecialchars($promo['title']) ?></h3>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed"><?= htmlspecialchars($promo['description'] ?? 'Tanpa deskripsi') ?></p>
                        </div>
                        
                        <div class="flex items-center justify-between pt-3 mt-3 border-t border-slate-100">
                            <span class="text-[11px] font-bold text-slate-400 bg-slate-50 px-2.5 py-1 rounded-md border border-slate-100">
                                <i class="fas fa-clock mr-1 text-slate-400"></i> S/d <?= $promo['valid_until'] ? formatDateTime($promo['valid_until'], 'd M Y') : 'Selamanya' ?>
                            </span>
                            <div class="flex items-center gap-1">
                                <a href="?toggle=<?= $promo['id'] ?>" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors" title="Ubah Status Aktif">
                                    <i class="fas <?= $promo['is_active'] ? 'fa-toggle-on text-emerald-600 text-base' : 'fa-toggle-off text-slate-400 text-base' ?>"></i>
                                </a>
                                <a href="?delete=<?= $promo['id'] ?>" onclick="return confirm('Yakin ingin menghapus promo ini?')" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-colors" title="Hapus">
                                    <i class="fas fa-trash text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($promos)): ?>
                <div class="col-span-full text-center py-10 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <i class="fas fa-tags text-4xl text-slate-300 mb-3"></i>
                    <p class="text-slate-500 font-medium text-sm">Belum ada promo yang ditambahkan.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function filterPromos() {
    const searchVal = document.getElementById('promoSearchInput').value.toLowerCase().trim();
    const filterVal = document.getElementById('promoFilterSelect').value;
    const cards = document.querySelectorAll('.promo-card');

    cards.forEach(card => {
        const title = card.getAttribute('data-title');
        const active = card.getAttribute('data-active');

        const matchesSearch = title.includes(searchVal);
        const matchesFilter = (filterVal === 'all') || (active === filterVal);

        if (matchesSearch && matchesFilter) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>

