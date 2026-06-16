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
    
    if ($id) {
        $stmt = $db->prepare("UPDATE menus SET name=?, category_id=?, price=?, description=?, is_available=? WHERE id=?");
        $stmt->execute([$name, $category_id, $price, $description, $is_available, $id]);
    } else {
        $slug = strtolower(str_replace(' ', '-', $name));
        $stmt = $db->prepare("INSERT INTO menus (name, slug, category_id, price, description, is_available) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name, $slug, $category_id, $price, $description, $is_available]);
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
<main class="p-4 pb-20">
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold text-lg">Daftar Menu</h2>
            <button onclick="showAddModal()" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                <i class="fas fa-plus mr-2"></i>Tambah
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($menus as $menu): ?>
            <div class="border rounded-lg p-3">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <p class="font-bold"><?= $menu['name'] ?></p>
                        <p class="text-xs text-gray-500"><?= $menu['category_name'] ?></p>
                        <?php if ($menu['description']): ?>
                        <p class="text-xs text-gray-400 mt-1"><?= substr($menu['description'], 0, 50) ?>...</p>
                        <?php endif; ?>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full <?= $menu['is_available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $menu['is_available'] ? 'Tersedia' : 'Habis' ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <p class="font-bold text-slate-700"><?= formatRupiah($menu['price']) ?></p>
                    <div class="space-x-2">
                        <button onclick='editMenu(<?= json_encode($menu) ?>)' class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?delete=<?= $menu['id'] ?>" onclick="return confirm('Yakin hapus?')" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Modal Add/Edit -->
<div id="menuModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold mb-4" id="modalTitle">Tambah Menu</h3>
        <form method="POST">
            <input type="hidden" name="id" id="menuId">
            <div class="space-y-3">
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
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Menu';
    document.getElementById('menuId').value = '';
    document.getElementById('menuName').value = '';
    document.getElementById('menuPrice').value = '';
    document.getElementById('menuDesc').value = '';
    document.getElementById('menuAvailable').checked = true;
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
    document.getElementById('menuModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('menuModal').classList.add('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
