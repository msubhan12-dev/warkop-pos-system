<?php
require_once '../config/config.php';
requireRole(['owner', 'admin']);
$db = getDB();

try {
    $db->beginTransaction();

    // 1. Definisikan Default Ingredients (Bahan Baku Standar Warkop)
    $defaultIngredients = [
        ['name' => 'Kopi Hitam Bubuk', 'unit' => 'gram', 'stock' => 1000],
        ['name' => 'Susu Kental Manis', 'unit' => 'ml', 'stock' => 2000],
        ['name' => 'Susu UHT Full Cream', 'unit' => 'ml', 'stock' => 5000],
        ['name' => 'Gula Pasir', 'unit' => 'gram', 'stock' => 3000],
        ['name' => 'Teh Celup', 'unit' => 'pcs', 'stock' => 200],
        ['name' => 'Es Batu', 'unit' => 'gram', 'stock' => 10000],
        ['name' => 'Mie Instan Goreng', 'unit' => 'pcs', 'stock' => 100],
        ['name' => 'Mie Instan Kuah', 'unit' => 'pcs', 'stock' => 100],
        ['name' => 'Telur Ayam', 'unit' => 'pcs', 'stock' => 150],
        ['name' => 'Roti Tawar', 'unit' => 'pcs', 'stock' => 50],
        ['name' => 'Matcha Powder', 'unit' => 'gram', 'stock' => 500],
        ['name' => 'Coklat Powder', 'unit' => 'gram', 'stock' => 500],
        ['name' => 'Cup Plastik 16oz', 'unit' => 'pcs', 'stock' => 500],
        ['name' => 'Sedotan', 'unit' => 'pcs', 'stock' => 500],
    ];

    $ingredientIds = [];

    // Insert or Get Ingredients
    foreach ($defaultIngredients as $ing) {
        $stmt = $db->prepare("SELECT id FROM ingredients WHERE name = ?");
        $stmt->execute([$ing['name']]);
        $row = $stmt->fetch();
        
        if ($row) {
            $ingredientIds[$ing['name']] = $row['id'];
        } else {
            $stmt = $db->prepare("INSERT INTO ingredients (name, unit, stock_quantity) VALUES (?,?,?)");
            $stmt->execute([$ing['name'], $ing['unit'], $ing['stock']]);
            $ingredientIds[$ing['name']] = $db->lastInsertId();
        }
    }

    // 2. Baca Semua Menu
    $stmt = $db->query("SELECT id, name FROM menus");
    $menus = $stmt->fetchAll();

    $recipesCreated = 0;

    foreach ($menus as $menu) {
        $name = strtolower($menu['name']);
        $recipe = [];
        
        // Logika AI (Mapping kata kunci nama menu ke bahan baku)
        if (strpos($name, 'kopi') !== false && strpos($name, 'hitam') !== false) {
            $recipe[$ingredientIds['Kopi Hitam Bubuk']] = 15; // 15 gram
            $recipe[$ingredientIds['Gula Pasir']] = 10;
        } elseif (strpos($name, 'kopi susu') !== false) {
            $recipe[$ingredientIds['Kopi Hitam Bubuk']] = 15;
            $recipe[$ingredientIds['Susu Kental Manis']] = 30; // 30 ml
        } elseif (strpos($name, 'matcha') !== false) {
            $recipe[$ingredientIds['Matcha Powder']] = 20; // 20 gram
            $recipe[$ingredientIds['Susu UHT Full Cream']] = 100; // 100 ml
            $recipe[$ingredientIds['Gula Pasir']] = 15;
        } elseif (strpos($name, 'coklat') !== false) {
            $recipe[$ingredientIds['Coklat Powder']] = 25;
            $recipe[$ingredientIds['Susu UHT Full Cream']] = 100;
        } elseif (strpos($name, 'teh') !== false) {
            $recipe[$ingredientIds['Teh Celup']] = 1;
            $recipe[$ingredientIds['Gula Pasir']] = 15;
        } elseif (strpos($name, 'indomie') !== false || strpos($name, 'mie') !== false) {
            if (strpos($name, 'goreng') !== false) {
                $recipe[$ingredientIds['Mie Instan Goreng']] = 1;
            } else {
                $recipe[$ingredientIds['Mie Instan Kuah']] = 1;
            }
            if (strpos($name, 'telur') !== false || strpos($name, 'telor') !== false || strpos($name, 'intel') !== false) {
                $recipe[$ingredientIds['Telur Ayam']] = 1;
            }
        } elseif (strpos($name, 'roti') !== false) {
            $recipe[$ingredientIds['Roti Tawar']] = 2;
        }

        // Kalau ini minuman dingin / ada kata "es"
        if (strpos($name, 'es') !== false || strpos($name, 'ice') !== false) {
            $recipe[$ingredientIds['Es Batu']] = 150; // 150 gram es
            $recipe[$ingredientIds['Cup Plastik 16oz']] = 1;
            $recipe[$ingredientIds['Sedotan']] = 1;
        }
        
        // Simpan Resep
        if (!empty($recipe)) {
            // Hapus resep lama (jika ada)
            $stmt = $db->prepare("DELETE FROM menu_recipes WHERE menu_id = ?");
            $stmt->execute([$menu['id']]);
            
            foreach ($recipe as $ingId => $amount) {
                $stmt = $db->prepare("INSERT INTO menu_recipes (menu_id, ingredient_id, amount_required) VALUES (?,?,?)");
                $stmt->execute([$menu['id'], $ingId, $amount]);
            }
            $recipesCreated++;
        }
    }

    $db->commit();
    echo "<script>alert('Berhasil! AI Warkop OS telah menganalisa menu dan membuat {$recipesCreated} resep standar secara otomatis.'); window.location.href='recipes.php';</script>";

} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage();
}
