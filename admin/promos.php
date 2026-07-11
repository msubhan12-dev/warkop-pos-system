<?php
require_once '../config/config.php';
requireRole(['owner', 'admin']);
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
include '../includes/header.php';
?>
<main class="p-4 pb-32 sm:pb-24">
    <?= $message ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Form Add Promo -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
            <h2 class="font-extrabold text-xl font-outfit mb-5 text-slate-800">Tambah Promo</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Judul Promo</label>
                    <input type="text" name="title" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Deskripsi (Opsional)</label>
                    <textarea name="description" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Berlaku Sampai</label>
                    <input type="date" name="valid_until" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Gambar Promo</label>
                    <input type="file" name="image" accept="image/*" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>
                
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-plus"></i> Simpan Promo
                </button>
            </form>
        </div>
        
        <!-- Promo List -->
        <div class="md:col-span-2 space-y-4">
            <h2 class="font-extrabold text-xl font-outfit mb-5 text-slate-800">Daftar Promo</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($promos as $promo): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden group">
                    <div class="h-40 bg-slate-200 relative overflow-hidden">
                        <img src="<?= UPLOADS_URL . '/' . $promo['image_path'] ?>" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" alt="Promo">
                        <?php if (!$promo['is_active']): ?>
                            <div class="absolute inset-0 bg-slate-900/50 flex items-center justify-center">
                                <span class="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">Tidak Aktif</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="font-extrabold text-lg text-slate-800 font-outfit mb-1"><?= $promo['title'] ?></h3>
                        <p class="text-sm text-slate-500 mb-3 line-clamp-2"><?= $promo['description'] ?></p>
                        
                        <div class="flex items-center justify-between mt-4">
                            <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-md">
                                <i class="fas fa-clock mr-1"></i> S/d <?= $promo['valid_until'] ? formatDateTime($promo['valid_until'], 'd M Y') : 'Selamanya' ?>
                            </span>
                            <div class="flex gap-2">
                                <a href="?toggle=<?= $promo['id'] ?>" class="text-slate-400 hover:text-emerald-500 transition-colors p-2" title="Toggle Aktif">
                                    <i class="fas <?= $promo['is_active'] ? 'fa-toggle-on text-emerald-500' : 'fa-toggle-off' ?> text-xl"></i>
                                </a>
                                <a href="?delete=<?= $promo['id'] ?>" onclick="return confirm('Yakin ingin menghapus promo ini?')" class="text-slate-400 hover:text-red-500 transition-colors p-2" title="Hapus">
                                    <i class="fas fa-trash text-lg"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($promos)): ?>
                <div class="col-span-full text-center py-10 bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                    <i class="fas fa-tags text-4xl text-slate-300 mb-3"></i>
                    <p class="text-slate-500 font-medium">Belum ada promo yang ditambahkan.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
