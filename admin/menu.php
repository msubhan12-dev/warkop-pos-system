<?php
require_once '../config/config.php';
requireRole(['owner']);
$pageTitle = 'Menu';
$user = getCurrentUser();
$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("UPDATE menus SET is_available = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: menu.php');
    exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = clean($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $description = clean($_POST['description'] ?? '');
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $image = null;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image'], 'menu');
        if ($uploadResult['success']) {
            $image = $uploadResult['path'];
        }
    }
    
    if ($id) {
        // Get existing image if no new image uploaded
        if (!$image) {
            $stmt = $db->prepare("SELECT image FROM menus WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            $image = $existing['image'];
        }
        
        $stmt = $db->prepare("UPDATE menus SET name=?, category_id=?, price=?, description=?, is_available=?, image=? WHERE id=?");
        $stmt->execute([$name, $category_id, $price, $description, $is_available, $image, $id]);
    } else {
        $slug = strtolower(str_replace(' ', '-', $name));
        $stmt = $db->prepare("INSERT INTO menus (name, slug, category_id, price, description, is_available, image) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$name, $slug, $category_id, $price, $description, $is_available, $image]);
    }
    header('Location: menu.php');
    exit;
}

$stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");
$categories = $stmt->fetchAll();

$stmt = $db->query("SELECT m.*, c.name as category_name FROM menus m JOIN categories c ON m.category_id = c.id ORDER BY c.sort_order, m.name");
$menus = $stmt->fetchAll();

include '../includes/header.php';
?>
<main class="p-4 pb-32 sm:pb-24">
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold text-lg">Daftar Menu</h2>
            <button onclick="showAddModal()" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                <i class="fas fa-plus mr-2"></i>Tambah
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($menus as $menu): ?>
            <div class="border rounded-lg p-3 hover:shadow-md transition">
                <div class="flex gap-3">
                    <!-- Image -->
                    <div class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden bg-gradient-to-br from-slate-400 to-slate-600 flex items-center justify-center">
                        <?php if ($menu['image']): ?>
                            <img src="<?= UPLOADS_URL . '/' . $menu['image'] ?>" alt="<?= $menu['name'] ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-utensils text-white text-2xl"></i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-1">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <p class="font-bold"><?= $menu['name'] ?></p>
                                <p class="text-xs text-gray-500"><?= $menu['category_name'] ?></p>
                                <?php if ($menu['description']): ?>
                                <p class="text-xs text-gray-400 mt-1 line-clamp-1"><?= $menu['description'] ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full <?= $menu['is_available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $menu['is_available'] ? 'Tersedia' : 'Habis' ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-bold text-slate-700 text-sm"><?= formatRupiah($menu['price']) ?></p>
                            <div class="space-x-2">
                                <button onclick='editMenu(<?= json_encode($menu) ?>)' class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?= $menu['id'] ?>" onclick="return confirm('Yakin hapus?')" class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Modal Add/Edit -->
<div id="menuModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold mb-4" id="modalTitle">Tambah Menu</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="menuId">
            <div class="space-y-3">
                <!-- Image Upload -->
                <div>
                    <label class="block text-sm font-medium mb-2">Gambar Menu</label>
                    <div class="relative">
                        <div class="w-full h-32 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center bg-gray-50 hover:bg-gray-100 transition cursor-pointer" id="imageDropZone">
                            <div class="text-center">
                                <i class="fas fa-image text-3xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-600">Klik atau drag image</p>
                            </div>
                            <input type="file" name="image" id="menuImage" accept="image/*" class="hidden">
                        </div>
                        <div id="imagePreview" class="hidden mt-2">
                            <img id="previewImg" src="" alt="Preview" class="w-full h-32 object-cover rounded-lg">
                            <button type="button" onclick="clearImage()" class="mt-2 w-full text-sm text-red-600 hover:text-red-800">
                                <i class="fas fa-times mr-1"></i>Hapus Gambar
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Nama Menu</label>
                    <input type="text" name="name" id="menuName" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Kategori</label>
                    <select name="category_id" id="menuCategory" required class="w-full px-3 py-2 border rounded-lg">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Harga</label>
                    <input type="number" name="price" id="menuPrice" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Deskripsi</label>
                    <textarea name="description" id="menuDesc" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_available" id="menuAvailable" checked class="mr-2">
                        <span class="text-sm">Tersedia</span>
                    </label>
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
// Image upload handling
const imageDropZone = document.getElementById('imageDropZone');
const fileInput = document.getElementById('menuImage');
const imagePreview = document.getElementById('imagePreview');
const previewImg = document.getElementById('previewImg');

// Drag and drop
imageDropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    imageDropZone.classList.add('bg-slate-100', 'border-slate-400');
});

imageDropZone.addEventListener('dragleave', () => {
    imageDropZone.classList.remove('bg-slate-100', 'border-slate-400');
});

imageDropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    imageDropZone.classList.remove('bg-slate-100', 'border-slate-400');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        showImagePreview();
    }
});

// Click to select
imageDropZone.addEventListener('click', () => {
    fileInput.click();
});

fileInput.addEventListener('change', showImagePreview);

function showImagePreview() {
    const file = fileInput.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            imagePreview.classList.remove('hidden');
            imageDropZone.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
}

function clearImage() {
    fileInput.value = '';
    imagePreview.classList.add('hidden');
    imageDropZone.classList.remove('hidden');
}

function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Menu';
    document.getElementById('menuId').value = '';
    document.getElementById('menuName').value = '';
    document.getElementById('menuCategory').value = document.querySelector('select[name="category_id"] option').value;
    document.getElementById('menuPrice').value = '';
    document.getElementById('menuDesc').value = '';
    document.getElementById('menuAvailable').checked = true;
    clearImage();
    document.getElementById('menuModal').classList.remove('hidden');
}

function editMenu(menu) {
    document.getElementById('modalTitle').textContent = 'Edit Menu';
    document.getElementById('menuId').value = menu.id;
    document.getElementById('menuName').value = menu.name;
    document.getElementById('menuCategory').value = menu.category_id;
    document.getElementById('menuPrice').value = menu.price;
    document.getElementById('menuDesc').value = menu.description || '';
    document.getElementById('menuAvailable').checked = menu.is_available == 1;
    
    // Show existing image if available
    if (menu.image) {
        previewImg.src = '<?= UPLOADS_URL ?>' + '/' + menu.image;
        imagePreview.classList.remove('hidden');
        imageDropZone.classList.add('hidden');
    } else {
        clearImage();
    }
    
    document.getElementById('menuModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('menuModal').classList.add('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
