<?php
require_once '../config/config.php';
requireRole(['owner', 'admin']);
$pageTitle = 'Resep & Racikan Menu';
$user = getCurrentUser();
$db = getDB();

// Handle Save Recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_recipe') {
    $menu_id = (int)$_POST['menu_id'];
    
    // First, delete old recipe for this menu
    $stmt = $db->prepare("DELETE FROM menu_recipes WHERE menu_id = ?");
    $stmt->execute([$menu_id]);
    
    // Insert new recipe items
    if (isset($_POST['ingredients']) && is_array($_POST['ingredients'])) {
        $stmt = $db->prepare("INSERT INTO menu_recipes (menu_id, ingredient_id, amount_required) VALUES (?,?,?)");
        foreach ($_POST['ingredients'] as $ing_id => $amount) {
            $amount = (float)$amount;
            if ($amount > 0) {
                $stmt->execute([$menu_id, (int)$ing_id, $amount]);
            }
        }
    }
    
    header('Location: recipes.php?success=1');
    exit;
}

// Fetch all menus and categories
$stmt = $db->query("SELECT m.id, m.name, c.name as category_name, m.image, m.price FROM menus m JOIN categories c ON m.category_id = c.id ORDER BY c.sort_order, m.name");
$menus = $stmt->fetchAll();

// Fetch all ingredients
$stmt = $db->query("SELECT * FROM ingredients ORDER BY name");
$allIngredients = $stmt->fetchAll();

// Fetch current recipes mapped by menu_id
$stmt = $db->query("SELECT mr.menu_id, mr.ingredient_id, mr.amount_required, i.name, i.unit FROM menu_recipes mr JOIN ingredients i ON mr.ingredient_id = i.id");
$recipesData = $stmt->fetchAll();
$recipes = [];
foreach ($recipesData as $row) {
    $recipes[$row['menu_id']][] = $row;
}

include '../includes/header.php';
?>
<main class="p-4 sm:p-6 pb-32 sm:pb-24 max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 font-outfit tracking-tight">Resep Menu</h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">Atur racikan bahan baku (Bill of Materials) untuk setiap menu.</p>
        </div>
        <a href="generate_ai_recipe.php" onclick="return confirm('Ini akan menggunakan AI untuk meracik semua resep kosong secara otomatis. Lanjutkan?')" class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2 border border-indigo-400/30">
            <i class="fas fa-magic"></i> Auto-Generate via AI
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 font-semibold flex items-center gap-3">
        <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
        Resep berhasil disimpan!
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($menus as $menu): ?>
        <?php 
            $menuRecipes = $recipes[$menu['id']] ?? []; 
            $hasRecipe = count($menuRecipes) > 0;
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col group hover:shadow-md transition-shadow">
            <div class="p-5 flex gap-4 items-start border-b border-slate-100">
                <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 shrink-0">
                    <?php if ($menu['image']): ?>
                        <img src="<?= UPLOADS_URL . '/' . $menu['image'] ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-slate-400"><i class="fas fa-utensils text-xl"></i></div>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?= $menu['category_name'] ?></span>
                    <h3 class="font-extrabold text-slate-800 font-outfit text-lg leading-tight mt-0.5"><?= htmlspecialchars($menu['name']) ?></h3>
                    <div class="mt-2">
                        <?php if ($hasRecipe): ?>
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase border border-emerald-100">
                                <i class="fas fa-check"></i> Resep Diatur
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-rose-50 text-rose-600 text-[10px] font-bold uppercase border border-rose-100">
                                <i class="fas fa-exclamation-circle"></i> Belum Ada Resep
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="p-4 bg-slate-50 flex-1 flex flex-col">
                <?php if ($hasRecipe): ?>
                    <ul class="space-y-2 mb-4 flex-1">
                        <?php foreach ($menuRecipes as $ing): ?>
                        <li class="flex justify-between items-center text-sm">
                            <span class="text-slate-600 font-medium"><i class="fas fa-circle text-[6px] text-slate-300 mr-2 relative -top-0.5"></i><?= htmlspecialchars($ing['name']) ?></span>
                            <span class="font-bold text-slate-800"><?= $ing['amount_required'] ?> <span class="text-slate-400 text-xs font-semibold"><?= $ing['unit'] ?></span></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="flex-1 flex items-center justify-center text-slate-400 text-sm font-medium py-4">
                        Tidak ada bahan baku yang dipotong saat pesanan dibuat.
                    </div>
                <?php endif; ?>
                
                <button onclick='openRecipeModal(<?= json_encode($menu) ?>, <?= json_encode($menuRecipes) ?>)' class="w-full mt-auto py-2.5 bg-white border border-slate-200 text-slate-700 hover:text-emerald-600 hover:border-emerald-300 rounded-xl font-bold text-sm shadow-sm transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-edit"></i> Atur Resep
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Recipe Modal -->
<div id="recipeModal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl my-8 overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="recipeModalContent">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 sticky top-0 z-10">
            <div>
                <h3 class="font-extrabold text-lg text-slate-800 font-outfit">Atur Resep</h3>
                <p class="text-emerald-600 font-bold text-sm" id="modalMenuName">Nama Menu</p>
            </div>
            <button onclick="closeRecipeModal()" class="text-slate-400 hover:text-rose-500 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="action" value="save_recipe">
            <input type="hidden" name="menu_id" id="modalMenuId">
            
            <div class="bg-blue-50 text-blue-800 p-4 rounded-xl text-sm font-medium border border-blue-100 mb-6 flex gap-3">
                <i class="fas fa-info-circle text-lg mt-0.5 text-blue-500"></i>
                <p>Isi jumlah (takaran) bahan baku yang diperlukan untuk membuat 1 porsi menu ini. Kosongkan jika bahan baku tidak digunakan.</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[50vh] overflow-y-auto pr-2 custom-scroll">
                <?php foreach ($allIngredients as $ing): ?>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 hover:border-emerald-300 transition-colors focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 group">
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        <?= htmlspecialchars($ing['name']) ?>
                    </label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" name="ingredients[<?= $ing['id'] ?>]" id="ing_input_<?= $ing['id'] ?>" placeholder="0" class="w-full bg-white border border-slate-300 rounded-lg pl-3 pr-16 py-2 text-slate-700 focus:outline-none focus:border-emerald-500 font-bold">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-slate-400 text-xs font-bold uppercase"><?= $ing['unit'] ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($allIngredients)): ?>
                    <div class="col-span-full p-6 text-center border-2 border-dashed border-slate-200 rounded-xl text-slate-500 font-medium">
                        Belum ada bahan baku. Silakan tambahkan bahan baku di menu <b>Stok Bahan</b> terlebih dahulu.
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeRecipeModal()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                    <i class="fas fa-save"></i> Simpan Resep
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.custom-scroll::-webkit-scrollbar { width: 6px; }
.custom-scroll::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>

<script>
function openRecipeModal(menu, currentRecipes) {
    document.getElementById('modalMenuId').value = menu.id;
    document.getElementById('modalMenuName').textContent = menu.name;
    
    // Reset all inputs to blank
    document.querySelectorAll('[id^="ing_input_"]').forEach(el => el.value = '');
    
    // Fill current recipes
    currentRecipes.forEach(recipe => {
        const input = document.getElementById('ing_input_' + recipe.ingredient_id);
        if (input) {
            input.value = recipe.amount_required;
        }
    });
    
    const modal = document.getElementById('recipeModal');
    const content = document.getElementById('recipeModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeRecipeModal() {
    const modal = document.getElementById('recipeModal');
    const content = document.getElementById('recipeModalContent');
    modal.classList.add('opacity-0');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 300);
}
</script>
<?php include '../includes/footer.php'; ?>
