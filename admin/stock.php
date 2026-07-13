<?php
require_once '../config/config.php';
requireRole(['owner', 'admin']);
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

include '../includes/header.php';
?>
<main class="p-4 sm:p-6 pb-32 sm:pb-24 max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 font-outfit tracking-tight">Stok Bahan Baku</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Pantau dan kelola persediaan bahan baku (Inventory).</p>
        </div>
        <button onclick="showModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center">
            <i class="fas fa-plus mr-2"></i> Tambah Bahan
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider font-bold">
                        <th class="p-4 sm:p-5">Bahan Baku</th>
                        <th class="p-4 sm:p-5 text-center">Sisa Stok</th>
                        <th class="p-4 sm:p-5 text-center">Satuan</th>
                        <th class="p-4 sm:p-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($ingredients)): ?>
                    <tr>
                        <td colspan="4" class="py-12 text-center text-slate-400 font-medium">
                            <i class="fas fa-boxes text-4xl mb-3 text-slate-300 block"></i>
                            Belum ada data bahan baku.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($ingredients as $ing): 
                        $isLow = $ing['stock_quantity'] <= 10; // Warning threshold
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-4 sm:p-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center font-bold text-sm <?= $isLow ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-500' ?>">
                                    <i class="fas fa-box"></i>
                                </div>
                                <span class="font-bold text-slate-800 text-base"><?= htmlspecialchars($ing['name']) ?></span>
                            </div>
                        </td>
                        <td class="p-4 sm:p-5 text-center">
                            <span class="font-extrabold text-lg <?= $isLow ? 'text-rose-600' : 'text-slate-700' ?>">
                                <?= number_format($ing['stock_quantity'], 1, ',', '.') ?>
                            </span>
                        </td>
                        <td class="p-4 sm:p-5 text-center">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-slate-100 text-slate-600 border border-slate-200 uppercase">
                                <?= htmlspecialchars($ing['unit']) ?>
                            </span>
                        </td>
                        <td class="p-4 sm:p-5 text-right whitespace-nowrap">
                            <button onclick="showUpdateModal(<?= $ing['id'] ?>, '<?= htmlspecialchars(addslashes($ing['name'])) ?>', <?= $ing['stock_quantity'] ?>)" class="inline-flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-100 w-9 h-9 rounded-xl transition-colors mr-1" title="Update Stok Cepat">
                                <i class="fas fa-bolt text-sm"></i>
                            </button>
                            <button onclick='editIngredient(<?= json_encode($ing) ?>)' class="inline-flex items-center justify-center bg-amber-50 text-amber-600 hover:bg-amber-100 w-9 h-9 rounded-xl transition-colors mr-1" title="Edit">
                                <i class="fas fa-edit text-sm"></i>
                            </button>
                            <a href="?delete=<?= $ing['id'] ?>" onclick="return confirm('Hapus bahan baku ini? Resep yang menggunakan bahan ini mungkin akan terganggu.')" class="inline-flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-100 w-9 h-9 rounded-xl transition-colors" title="Hapus">
                                <i class="fas fa-trash text-sm"></i>
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
                    <input type="text" name="name" id="ing_name" required placeholder="Cth: Bubuk Matcha Premium" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-medium">
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
</script>
<?php include '../includes/footer.php'; ?>
