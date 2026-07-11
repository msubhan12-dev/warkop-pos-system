<?php
require_once '../config/config.php';
requireRole(['owner', 'admin']);
$pageTitle = 'Kelola Reels';
$user = getCurrentUser();
$db = getDB();

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $title = clean($_POST['title']);
        $type = clean($_POST['type']); // 'url' or 'upload'
        
        if ($type == 'url') {
            $mediaPath = clean($_POST['url_link']);
            $isUrl = 1;
            
            $stmt = $db->prepare("INSERT INTO reels (title, media_path, is_url) VALUES (?, ?, ?)");
            if ($stmt->execute([$title, $mediaPath, $isUrl])) {
                $message = '<div class="bg-emerald-100 text-emerald-800 p-3 rounded-lg mb-4">Reel URL berhasil ditambahkan.</div>';
            }
        } else {
            // Handle file upload (video/image)
            if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
                $allowed = ['mp4', 'webm', 'ogg', 'jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['media']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $newName = 'reel_' . time() . '.' . $ext;
                    $uploadPath = UPLOADS_PATH . '/' . $newName;
                    
                    if (move_uploaded_file($_FILES['media']['tmp_name'], $uploadPath)) {
                        $isUrl = 0;
                        $stmt = $db->prepare("INSERT INTO reels (title, media_path, is_url) VALUES (?, ?, ?)");
                        if ($stmt->execute([$title, $newName, $isUrl])) {
                            $message = '<div class="bg-emerald-100 text-emerald-800 p-3 rounded-lg mb-4">Reel Media berhasil diunggah.</div>';
                        }
                    } else {
                        $message = '<div class="bg-red-100 text-red-800 p-3 rounded-lg mb-4">Gagal upload media.</div>';
                    }
                } else {
                    $message = '<div class="bg-red-100 text-red-800 p-3 rounded-lg mb-4">Format file tidak didukung (harus mp4, webm, jpg, png).</div>';
                }
            } else {
                $message = '<div class="bg-red-100 text-red-800 p-3 rounded-lg mb-4">File media wajib diupload.</div>';
            }
        }
    }
}

// Handle delete & toggle
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("UPDATE reels SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: reels.php');
    exit;
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE reels SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: reels.php');
    exit;
}

$stmt = $db->query("SELECT * FROM reels ORDER BY created_at DESC");
$reels = $stmt->fetchAll();
include '../includes/header.php';
?>
<main class="p-4 pb-32 sm:pb-24">
    <?= $message ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Form Add Reel -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 h-fit">
            <h2 class="font-extrabold text-xl font-outfit mb-5 text-slate-800">Tambah Reels</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4" id="reelForm">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Judul Konten</label>
                    <input type="text" name="title" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Tipe Konten</label>
                    <select name="type" id="typeSelect" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" onchange="toggleInput()">
                        <option value="url">URL Link (TikTok/IG/Youtube Shorts)</option>
                        <option value="upload">Upload File Video/Gambar</option>
                    </select>
                </div>
                
                <div id="urlInputGroup">
                    <label class="block text-sm font-bold text-slate-700 mb-2">URL Konten</label>
                    <input type="url" name="url_link" id="url_link" placeholder="https://..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                </div>
                
                <div id="uploadInputGroup" class="hidden">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Upload File</label>
                    <input type="file" name="media" id="media_file" accept="video/mp4,video/webm,image/*" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>
                
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 mt-4">
                    <i class="fas fa-plus"></i> Simpan Reels
                </button>
            </form>
        </div>
        
        <!-- Reels List -->
        <div class="md:col-span-2 space-y-4">
            <h2 class="font-extrabold text-xl font-outfit mb-5 text-slate-800">Daftar Reels</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <?php foreach ($reels as $reel): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden group relative flex flex-col h-64">
                    <div class="flex-1 bg-slate-900 relative overflow-hidden flex items-center justify-center">
                        <?php if ($reel['is_url']): ?>
                            <i class="fas fa-link text-4xl text-slate-600"></i>
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="<?= $reel['media_path'] ?>" target="_blank" class="bg-white/20 backdrop-blur-sm p-3 rounded-full text-white hover:bg-white hover:text-slate-900 transition-colors">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <?php 
                                $ext = strtolower(pathinfo($reel['media_path'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['mp4', 'webm', 'ogg'])):
                            ?>
                                <video src="<?= UPLOADS_URL . '/' . $reel['media_path'] ?>" class="w-full h-full object-cover" muted></video>
                                <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                                    <i class="fas fa-play text-white/70 text-3xl"></i>
                                </div>
                            <?php else: ?>
                                <img src="<?= UPLOADS_URL . '/' . $reel['media_path'] ?>" class="w-full h-full object-cover">
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!$reel['is_active']): ?>
                            <div class="absolute inset-0 bg-slate-900/60 flex items-center justify-center z-10">
                                <span class="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">Tidak Aktif</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-3 bg-white border-t border-slate-100">
                        <h3 class="font-extrabold text-sm text-slate-800 font-outfit truncate" title="<?= $reel['title'] ?>"><?= $reel['title'] ?></h3>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded">
                                <?= $reel['is_url'] ? 'Link URL' : 'Upload' ?>
                            </span>
                            <div class="flex gap-2">
                                <a href="?toggle=<?= $reel['id'] ?>" class="text-slate-400 hover:text-emerald-500 transition-colors" title="Toggle Aktif">
                                    <i class="fas <?= $reel['is_active'] ? 'fa-toggle-on text-emerald-500' : 'fa-toggle-off' ?>"></i>
                                </a>
                                <a href="?delete=<?= $reel['id'] ?>" onclick="return confirm('Hapus reels ini?')" class="text-slate-400 hover:text-red-500 transition-colors" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($reels)): ?>
                <div class="col-span-full text-center py-10 bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                    <i class="fas fa-video text-4xl text-slate-300 mb-3"></i>
                    <p class="text-slate-500 font-medium">Belum ada reels yang ditambahkan.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function toggleInput() {
    const type = document.getElementById('typeSelect').value;
    const urlGroup = document.getElementById('urlInputGroup');
    const uploadGroup = document.getElementById('uploadInputGroup');
    const urlInput = document.getElementById('url_link');
    const fileInput = document.getElementById('media_file');
    
    if (type === 'url') {
        urlGroup.classList.remove('hidden');
        uploadGroup.classList.add('hidden');
        urlInput.required = true;
        fileInput.required = false;
    } else {
        urlGroup.classList.add('hidden');
        uploadGroup.classList.remove('hidden');
        urlInput.required = false;
        fileInput.required = true;
    }
}
// Init
toggleInput();
</script>

<?php include '../includes/footer.php'; ?>
