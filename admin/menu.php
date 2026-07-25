<?php
require_once '../config/config.php';
requireRole(['owner']);
$pageTitle = 'Kelola Menu';
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

// Statistics calculation
$totalMenus = count($menus);
$availableMenus = count(array_filter($menus, fn($m) => $m['is_available'] == 1));
$unavailableMenus = $totalMenus - $availableMenus;
$totalCategories = count($categories);

include '../includes/header.php';
?>

<main class="p-4 sm:p-6 pb-32 sm:pb-24 max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 font-outfit tracking-tight">Kelola Daftar Menu</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Atur produk, harga, kategori, dan ketersediaan menu resto & warung.</p>
        </div>
        <button onclick="showAddModal()" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-2xl text-sm font-bold shadow-lg shadow-emerald-600/20 hover:shadow-xl hover:shadow-emerald-600/30 transition-all hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
            <i class="fas fa-plus text-base"></i>
            <span>Tambah Menu Baru</span>
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="fas fa-utensils text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Menu</p>
                <p class="text-xl font-extrabold text-slate-800 font-outfit mt-0.5"><?= $totalMenus ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tersedia</p>
                <p class="text-xl font-extrabold text-teal-600 font-outfit mt-0.5"><?= $availableMenus ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                <i class="fas fa-times-circle text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Stok Habis</p>
                <p class="text-xl font-extrabold text-rose-500 font-outfit mt-0.5"><?= $unavailableMenus ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i class="fas fa-folder text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kategori</p>
                <p class="text-xl font-extrabold text-amber-600 font-outfit mt-0.5"><?= $totalCategories ?></p>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm mb-6 space-y-3">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search Input -->
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" id="searchInput" onkeyup="filterMenu()" placeholder="Cari nama menu, kategori, atau deskripsi..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                <button id="clearSearchBtn" onclick="clearSearch()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Availability Filter -->
            <div class="sm:w-48">
                <select id="statusFilter" onchange="filterMenu()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                    <option value="all">Semua Status</option>
                    <option value="1">Tersedia</option>
                    <option value="0">Habis</option>
                </select>
            </div>
        </div>

        <!-- Category Pills Filter -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none" style="scrollbar-width: none;">
            <button onclick="selectCategory('all', this)" class="cat-pill active px-4 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap bg-emerald-600 text-white shadow-sm transition-all">
                Semua Kategori
            </button>
            <?php foreach ($categories as $cat): ?>
            <button onclick="selectCategory('<?= htmlspecialchars($cat['name']) ?>', this)" class="cat-pill px-4 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Empty State -->
    <div id="noResults" class="hidden bg-white rounded-2xl p-12 text-center border border-slate-100 shadow-sm my-6">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
            <i class="fas fa-search text-2xl"></i>
        </div>
        <h3 class="text-base font-bold text-slate-700">Menu Tidak Ditemukan</h3>
        <p class="text-slate-400 text-xs mt-1">Coba gunakan kata kunci pencarian atau filter yang lain.</p>
    </div>

    <!-- Menu Cards Grid -->
    <div id="menuContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($menus as $menu): ?>
        <div class="menu-card bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden flex flex-col group"
             data-name="<?= htmlspecialchars(strtolower($menu['name'])) ?>"
             data-category="<?= htmlspecialchars($menu['category_name']) ?>"
             data-status="<?= $menu['is_available'] ?>"
             data-description="<?= htmlspecialchars(strtolower($menu['description'] ?? '')) ?>">
            
            <div class="p-4 flex gap-4 flex-1">
                <!-- Image -->
                <div class="w-24 h-24 sm:w-28 sm:h-28 shrink-0 rounded-xl overflow-hidden bg-slate-100 relative group-hover:scale-105 transition-transform duration-300">
                    <?php if ($menu['image']): ?>
                        <img src="<?= UPLOADS_URL . '/' . $menu['image'] ?>" alt="<?= htmlspecialchars($menu['name']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-slate-100 to-slate-200 flex flex-col items-center justify-center text-slate-400">
                            <i class="fas fa-utensils text-2xl mb-1"></i>
                            <span class="text-[9px] font-bold text-slate-400 uppercase">No Image</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="flex-1 flex flex-col justify-between min-w-0">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <span class="inline-block px-2.5 py-0.5 bg-slate-100 text-slate-600 rounded-md text-[10px] font-bold uppercase tracking-wider truncate">
                                <?= htmlspecialchars($menu['category_name']) ?>
                            </span>
                            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold <?= $menu['is_available'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-rose-50 text-rose-600 border border-rose-200/60' ?>">
                                <span class="w-1.5 h-1.5 rounded-full mr-1 <?= $menu['is_available'] ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                                <?= $menu['is_available'] ? 'Tersedia' : 'Habis' ?>
                            </span>
                        </div>

                        <h3 class="font-extrabold text-slate-800 text-base leading-snug font-outfit truncate" title="<?= htmlspecialchars($menu['name']) ?>">
                            <?= htmlspecialchars($menu['name']) ?>
                        </h3>

                        <?php if ($menu['description']): ?>
                        <p class="text-slate-500 text-xs mt-1 line-clamp-2 leading-relaxed">
                            <?= htmlspecialchars($menu['description']) ?>
                        </p>
                        <?php else: ?>
                        <p class="text-slate-300 text-xs mt-1 italic">Tanpa deskripsi</p>
                        <?php endif; ?>
                    </div>

                    <div class="pt-3 border-t border-slate-50 flex items-center justify-between mt-2">
                        <span class="text-emerald-600 font-extrabold text-base font-outfit">
                            <?= formatRupiah($menu['price']) ?>
                        </span>

                        <div class="flex items-center gap-1.5">
                            <button onclick='editMenu(<?= json_encode($menu) ?>)' title="Edit Menu" class="w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 flex items-center justify-center transition-colors">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            <a href="?delete=<?= $menu['id'] ?>" onclick="return confirm('Yakin matikan/hapus menu ini?')" title="Sembunyikan Menu" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-colors">
                                <i class="fas fa-trash text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Modal Add/Edit Menu -->
<div id="menuModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 max-h-[90vh] overflow-y-auto shadow-2xl transition-all border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
            <div>
                <h3 class="text-xl font-extrabold text-slate-800 font-outfit" id="modalTitle">Tambah Menu Baru</h3>
                <p class="text-slate-400 text-xs mt-0.5">Lengkapi rincian informasi produk di bawah ini.</p>
            </div>
            <button onclick="closeModal()" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="menuId">
            <div class="space-y-4">
                <!-- Image Upload Zone -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Foto Produk</label>
                    <div class="relative">
                        <div class="w-full h-36 rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center bg-slate-50 hover:bg-slate-100 transition cursor-pointer" id="imageDropZone">
                            <div class="text-center p-4">
                                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-cloud-upload-alt text-lg"></i>
                                </div>
                                <p class="text-xs font-bold text-slate-700">Klik atau tarik gambar ke sini</p>
                                <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, WEBP (Maks 2MB)</p>
                            </div>
                            <input type="file" name="image" id="menuImage" accept="image/*" class="hidden">
                        </div>
                        <div id="imagePreview" class="hidden mt-2 relative rounded-2xl overflow-hidden group">
                            <img id="previewImg" src="" alt="Preview" class="w-full h-36 object-cover rounded-2xl border border-slate-200">
                            <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <button type="button" onclick="clearImage()" class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold shadow-md transition-colors flex items-center gap-1.5">
                                    <i class="fas fa-trash"></i> Hapus Foto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Nama Menu</label>
                    <input type="text" name="name" id="menuName" required placeholder="Cth: Es Kopi Susu Aren" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Kategori</label>
                        <select name="category_id" id="menuCategory" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Harga (Rp)</label>
                        <input type="number" name="price" id="menuPrice" required placeholder="Cth: 15000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Deskripsi Singkat</label>
                    <textarea name="description" id="menuDesc" rows="3" placeholder="Jelaskan komposisi atau keunikan rasa menu..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"></textarea>
                </div>

                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                    <label class="flex items-center cursor-pointer select-none">
                        <input type="checkbox" name="is_available" id="menuAvailable" checked class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                        <span class="ml-2.5 text-sm font-bold text-slate-700">Status Menu Tersedia</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal()" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3 rounded-xl transition-colors text-sm">
                    Batal
                </button>
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-emerald-600/20 transition-all text-sm">
                    Simpan Menu
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let selectedCategory = 'all';

// Image upload handling
const imageDropZone = document.getElementById('imageDropZone');
const fileInput = document.getElementById('menuImage');
const imagePreview = document.getElementById('imagePreview');
const previewImg = document.getElementById('previewImg');

imageDropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    imageDropZone.classList.add('bg-emerald-50', 'border-emerald-400');
});

imageDropZone.addEventListener('dragleave', () => {
    imageDropZone.classList.remove('bg-emerald-50', 'border-emerald-400');
});

imageDropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    imageDropZone.classList.remove('bg-emerald-50', 'border-emerald-400');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        showImagePreview();
    }
});

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
    document.getElementById('modalTitle').textContent = 'Tambah Menu Baru';
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

// Filtering Functionality
function filterMenu() {
    const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
    const statusVal = document.getElementById('statusFilter').value;
    const clearBtn = document.getElementById('clearSearchBtn');
    
    clearBtn.classList.toggle('hidden', searchVal === '');

    const cards = document.querySelectorAll('.menu-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const name = card.getAttribute('data-name');
        const category = card.getAttribute('data-category');
        const status = card.getAttribute('data-status');
        const desc = card.getAttribute('data-description');

        const matchesSearch = name.includes(searchVal) || category.toLowerCase().includes(searchVal) || desc.includes(searchVal);
        const matchesStatus = (statusVal === 'all') || (status === statusVal);
        const matchesCategory = (selectedCategory === 'all') || (category === selectedCategory);

        if (matchesSearch && matchesStatus && matchesCategory) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });

    const noResults = document.getElementById('noResults');
    noResults.classList.toggle('hidden', visibleCount > 0);
}

function selectCategory(catName, btnElement) {
    selectedCategory = catName;
    
    document.querySelectorAll('.cat-pill').forEach(btn => {
        btn.classList.remove('bg-emerald-600', 'text-white', 'shadow-sm');
        btn.classList.add('bg-slate-100', 'text-slate-600');
    });

    btnElement.classList.remove('bg-slate-100', 'text-slate-600');
    btnElement.classList.add('bg-emerald-600', 'text-white', 'shadow-sm');

    filterMenu();
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    filterMenu();
}
</script>

<?php include '../includes/footer.php'; ?>

