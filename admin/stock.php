<?php
require_once '../config/config.php';
requireRole(['owner']);
$pageTitle = 'Stok Bahan Baku';
$user = getCurrentUser();
$db = getDB();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM ingredients WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: stock.php');
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $id = $_POST['id'] ?? null;
    $name = clean($_POST['name']);
    $unit = clean($_POST['unit']);
    $stock_quantity = (float)$_POST['stock_quantity'];
    
    if ($id) {
        $stmt = $db->prepare("UPDATE ingredients SET name=?, unit=?, stock_quantity=? WHERE id=?");
        $stmt->execute([$name, $unit, $stock_quantity, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO ingredients (name, unit, stock_quantity) VALUES (?,?,?)");
        $stmt->execute([$name, $unit, $stock_quantity]);
    }
    header('Location: stock.php');
    exit;
}

// Handle Quick Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $id = (int)$_POST['id'];
    $stock_quantity = (float)$_POST['stock_quantity'];
    $stmt = $db->prepare("UPDATE ingredients SET stock_quantity=? WHERE id=?");
    $stmt->execute([$stock_quantity, $id]);
    header('Location: stock.php');
    exit;
}

$stmt = $db->query("SELECT * FROM ingredients ORDER BY name");
$ingredients = $stmt->fetchAll();

// Fetch menu names for auto-complete datalist
$stmtMenu = $db->query("SELECT name FROM menus ORDER BY name");
$menuNames = $stmtMenu->fetchAll(PDO::FETCH_COLUMN);

// Stats calculations
$totalIngredients = count($ingredients);
$lowStockCount = count(array_filter($ingredients, fn($i) => $i['stock_quantity'] <= 10));
$normalStockCount = $totalIngredients - $lowStockCount;

include '../includes/header.php';
?>

<main class="p-4 sm:p-6 pb-32 sm:pb-24 max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 font-outfit tracking-tight">Stok & Persediaan Bahan</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Pantau dan kelola persediaan bahan baku (Inventory kedai).</p>
        </div>
        <button onclick="showModal()" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-2xl text-sm font-bold shadow-lg shadow-emerald-600/20 hover:shadow-xl transition-all cursor-pointer">
            <i class="fas fa-plus text-base"></i>
            <span>Tambah Bahan Baku</span>
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i class="fas fa-boxes text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Jenis Bahan</p>
                <p class="text-xl font-extrabold text-slate-800 font-outfit mt-0.5"><?= $totalIngredients ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Stok Aman</p>
                <p class="text-xl font-extrabold text-emerald-600 font-outfit mt-0.5"><?= $normalStockCount ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Stok Menipis (≤10)</p>
                <p class="text-xl font-extrabold text-rose-500 font-outfit mt-0.5"><?= $lowStockCount ?></p>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm mb-6 flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" id="stockSearchInput" onkeyup="filterStock()" placeholder="Cari nama bahan baku atau satuan..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
        </div>

        <div class="sm:w-56">
            <select id="stockFilterSelect" onchange="filterStock()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                <option value="all">Semua Kondisi Stok</option>
                <option value="low">Menipis / Habis (≤10)</option>
                <option value="normal">Stok Aman (>10)</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-400 text-[11px] uppercase tracking-wider font-bold">
                        <th class="p-4 sm:p-5">Bahan Baku</th>
                        <th class="p-4 sm:p-5 text-center">Sisa Stok</th>
                        <th class="p-4 sm:p-5 text-center">Satuan</th>
                        <th class="p-4 sm:p-5 text-center">Status</th>
                        <th class="p-4 sm:p-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="stockTableBody" class="divide-y divide-slate-100">
                    <?php if (empty($ingredients)): ?>
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400 font-medium">
                            <i class="fas fa-boxes text-4xl mb-3 text-slate-300 block"></i>
                            Belum ada data bahan baku.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($ingredients as $ing): 
                        $isLow = $ing['stock_quantity'] <= 10;
                    ?>
                    <tr class="stock-row hover:bg-slate-50/80 transition-colors"
                        data-name="<?= htmlspecialchars(strtolower($ing['name'])) ?>"
                        data-unit="<?= htmlspecialchars(strtolower($ing['unit'])) ?>"
                        data-low="<?= $isLow ? '1' : '0' ?>">
                        <td class="p-4 sm:p-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center font-bold text-sm <?= $isLow ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' ?>">
                                    <i class="fas fa-box"></i>
                                </div>
                                <span class="font-bold text-slate-800 text-sm font-outfit"><?= htmlspecialchars($ing['name']) ?></span>
                            </div>
                        </td>
                        <td class="p-4 sm:p-5 text-center">
                            <span class="font-extrabold text-base font-outfit <?= $isLow ? 'text-rose-600' : 'text-slate-800' ?>">
                                <?= number_format($ing['stock_quantity'], 1, ',', '.') ?>
                            </span>
                        </td>
                        <td class="p-4 sm:p-5 text-center">
                            <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-slate-100 text-slate-600 uppercase border border-slate-200/60">
                                <?= htmlspecialchars($ing['unit']) ?>
                            </span>
                        </td>
                        <td class="p-4 sm:p-5 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold <?= $isLow ? 'bg-rose-50 text-rose-600 border border-rose-200/60' : 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' ?>">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?= $isLow ? 'bg-rose-500' : 'bg-emerald-500' ?>"></span>
                                <?= $isLow ? 'Perlu Ditambah' : 'Cukup' ?>
                            </span>
                        </td>
                        <td class="p-4 sm:p-5 text-right whitespace-nowrap">
                            <button onclick="showUpdateModal(<?= $ing['id'] ?>, '<?= htmlspecialchars(addslashes($ing['name'])) ?>', <?= $ing['stock_quantity'] ?>)" class="inline-flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-100 w-8 h-8 rounded-lg transition-colors mr-1" title="Update Stok Cepat">
                                <i class="fas fa-bolt text-xs"></i>
                            </button>
                            <button onclick='editIngredient(<?= json_encode($ing) ?>)' class="inline-flex items-center justify-center bg-amber-50 text-amber-600 hover:bg-amber-100 w-8 h-8 rounded-lg transition-colors mr-1" title="Edit">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            <a href="?delete=<?= $ing['id'] ?>" onclick="return confirm('Hapus bahan baku ini? Resep yang menggunakan bahan ini mungkin akan terganggu.')" class="inline-flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-100 w-8 h-8 rounded-lg transition-colors" title="Hapus">
                                <i class="fas fa-trash text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal Add/Edit -->
<div id="ingredientModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="ingredientModalContent">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-extrabold text-lg text-slate-800 font-outfit" id="modalTitle">Tambah Bahan Baku</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-rose-500 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="ing_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Nama Bahan Baku</label>
                    <input type="text" list="menuList" name="name" id="ing_name" required placeholder="Ketik atau pilih dari daftar..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-medium">
                    <datalist id="menuList">
                        <?php foreach ($menuNames as $mName): ?>
                        <option value="<?= htmlspecialchars($mName) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Jumlah Stok</label>
                        <input type="number" step="0.01" name="stock_quantity" id="ing_stock" required placeholder="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-bold">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Satuan</label>
                        <select name="unit" id="ing_unit" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-medium cursor-pointer">
                            <option value="gram">Gram (g)</option>
                            <option value="ml">Mili Liter (ml)</option>
                            <option value="pcs">Pcs / Buah</option>
                            <option value="kg">Kilogram (kg)</option>
                            <option value="liter">Liter (L)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md hover:shadow-lg transition-all">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Quick Update Stock -->
<div id="updateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="updateModalContent">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-blue-50 text-blue-900">
            <h3 class="font-extrabold text-lg font-outfit"><i class="fas fa-bolt text-blue-500 mr-2"></i>Update Stok Cepat</h3>
            <button onclick="closeUpdateModal()" class="text-blue-400 hover:text-blue-700 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-blue-100">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="id" id="upd_id">
            
            <div class="text-center mb-5">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Bahan Baku</p>
                <p class="font-extrabold text-xl text-slate-800" id="upd_name">-</p>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1.5 text-center">Sisa Stok Saat Ini</label>
                <input type="number" step="0.01" name="stock_quantity" id="upd_stock" required class="w-full text-center text-3xl font-black text-blue-600 bg-blue-50/50 border-2 border-blue-200 rounded-2xl px-4 py-4 focus:outline-none focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
            </div>
            
            <div class="mt-6">
                <button type="submit" class="w-full py-3.5 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md hover:shadow-lg transition-all">Update Stok</button>
            </div>
        </form>
    </div>
</div>

<script>
function showModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Bahan Baku';
    document.getElementById('ing_id').value = '';
    document.getElementById('ing_name').value = '';
    document.getElementById('ing_stock').value = '';
    document.getElementById('ing_unit').value = 'gram';
    
    const modal = document.getElementById('ingredientModal');
    const content = document.getElementById('ingredientModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function editIngredient(ing) {
    document.getElementById('modalTitle').textContent = 'Edit Bahan Baku';
    document.getElementById('ing_id').value = ing.id;
    document.getElementById('ing_name').value = ing.name;
    document.getElementById('ing_stock').value = ing.stock_quantity;
    document.getElementById('ing_unit').value = ing.unit;
    
    const modal = document.getElementById('ingredientModal');
    const content = document.getElementById('ingredientModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeModal() {
    const modal = document.getElementById('ingredientModal');
    const content = document.getElementById('ingredientModalContent');
    modal.classList.add('opacity-0');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function showUpdateModal(id, name, stock) {
    document.getElementById('upd_id').value = id;
    document.getElementById('upd_name').textContent = name;
    document.getElementById('upd_stock').value = stock;
    
    const modal = document.getElementById('updateModal');
    const content = document.getElementById('updateModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeUpdateModal() {
    const modal = document.getElementById('updateModal');
    const content = document.getElementById('updateModalContent');
    modal.classList.add('opacity-0');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function filterStock() {
    const searchVal = document.getElementById('stockSearchInput').value.toLowerCase().trim();
    const filterVal = document.getElementById('stockFilterSelect').value;
    const rows = document.querySelectorAll('.stock-row');

    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const unit = row.getAttribute('data-unit');
        const isLow = row.getAttribute('data-low');

        const matchesSearch = name.includes(searchVal) || unit.includes(searchVal);
        let matchesFilter = true;
        if (filterVal === 'low') matchesFilter = (isLow === '1');
        if (filterVal === 'normal') matchesFilter = (isLow === '0');

        if (matchesSearch && matchesFilter) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}
</script>
<?php include '../includes/footer.php'; ?>

