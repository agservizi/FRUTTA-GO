<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/bootstrap.php';

try {
    $pdo = getDB();
    $storeId = getCurrentStoreId();

    // Inserisci categorie demo
    $categories = [
        'Frutta',
        'Verdura',
        'Erbe aromatiche',
        'Tuberi',
        'Frutta secca'
    ];

    $categoryIds = [];
    foreach ($categories as $name) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND store_id = ?");
        $stmt->execute([$name, $storeId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $categoryIds[$name] = $existing['id'];
        } else {
            $pdo->prepare("INSERT INTO categories (name, store_id) VALUES (?, ?)")->execute([$name, $storeId]);
            $categoryIds[$name] = $pdo->lastInsertId();
        }
    }

    // Inserisci prodotti demo
    $products = [
        // Frutta
        ['name' => 'Mele', 'category' => 'Frutta', 'unit_type' => 'kg', 'price_sale' => 2.50, 'price_cost' => 1.80, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Banane', 'category' => 'Frutta', 'unit_type' => 'kg', 'price_sale' => 1.80, 'price_cost' => 1.20, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Arance', 'category' => 'Frutta', 'unit_type' => 'kg', 'price_sale' => 2.20, 'price_cost' => 1.50, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Pere', 'category' => 'Frutta', 'unit_type' => 'kg', 'price_sale' => 2.80, 'price_cost' => 2.00, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Uva', 'category' => 'Frutta', 'unit_type' => 'kg', 'price_sale' => 3.50, 'price_cost' => 2.50, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Limoni', 'category' => 'Frutta', 'unit_type' => 'kg', 'price_sale' => 2.00, 'price_cost' => 1.40, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Mandarini', 'category' => 'Frutta', 'unit_type' => 'kg', 'price_sale' => 2.30, 'price_cost' => 1.60, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Kiwi', 'category' => 'Frutta', 'unit_type' => 'kg', 'price_sale' => 4.00, 'price_cost' => 3.00, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Ananas', 'category' => 'Frutta', 'unit_type' => 'pz', 'price_sale' => 3.50, 'price_cost' => 2.50, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Melone', 'category' => 'Frutta', 'unit_type' => 'pz', 'price_sale' => 2.50, 'price_cost' => 1.80, 'is_active' => 1, 'is_favorite' => 0],
        // Verdura
        ['name' => 'Pomodori', 'category' => 'Verdura', 'unit_type' => 'kg', 'price_sale' => 3.00, 'price_cost' => 2.00, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Insalata', 'category' => 'Verdura', 'unit_type' => 'pz', 'price_sale' => 1.50, 'price_cost' => 1.00, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Carote', 'category' => 'Verdura', 'unit_type' => 'kg', 'price_sale' => 2.00, 'price_cost' => 1.30, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Zucchine', 'category' => 'Verdura', 'unit_type' => 'kg', 'price_sale' => 2.50, 'price_cost' => 1.80, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Melanzane', 'category' => 'Verdura', 'unit_type' => 'kg', 'price_sale' => 3.20, 'price_cost' => 2.20, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Cipolle', 'category' => 'Verdura', 'unit_type' => 'kg', 'price_sale' => 1.80, 'price_cost' => 1.20, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Aglio', 'category' => 'Verdura', 'unit_type' => 'pz', 'price_sale' => 0.50, 'price_cost' => 0.30, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Peperoni', 'category' => 'Verdura', 'unit_type' => 'kg', 'price_sale' => 4.00, 'price_cost' => 3.00, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Cetrioli', 'category' => 'Verdura', 'unit_type' => 'kg', 'price_sale' => 2.20, 'price_cost' => 1.50, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Spinaci', 'category' => 'Verdura', 'unit_type' => 'kg', 'price_sale' => 3.50, 'price_cost' => 2.50, 'is_active' => 1, 'is_favorite' => 1],
        // Erbe aromatiche
        ['name' => 'Basilico', 'category' => 'Erbe aromatiche', 'unit_type' => 'pz', 'price_sale' => 2.00, 'price_cost' => 1.50, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Prezzemolo', 'category' => 'Erbe aromatiche', 'unit_type' => 'pz', 'price_sale' => 1.50, 'price_cost' => 1.00, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Rosmarino', 'category' => 'Erbe aromatiche', 'unit_type' => 'pz', 'price_sale' => 2.50, 'price_cost' => 2.00, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Timo', 'category' => 'Erbe aromatiche', 'unit_type' => 'pz', 'price_sale' => 2.20, 'price_cost' => 1.80, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Menta', 'category' => 'Erbe aromatiche', 'unit_type' => 'pz', 'price_sale' => 2.00, 'price_cost' => 1.50, 'is_active' => 1, 'is_favorite' => 1],
        // Tuberi
        ['name' => 'Patate', 'category' => 'Tuberi', 'unit_type' => 'kg', 'price_sale' => 1.50, 'price_cost' => 1.00, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Zenzero', 'category' => 'Tuberi', 'unit_type' => 'pz', 'price_sale' => 3.00, 'price_cost' => 2.50, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Rafano', 'category' => 'Tuberi', 'unit_type' => 'pz', 'price_sale' => 2.50, 'price_cost' => 2.00, 'is_active' => 1, 'is_favorite' => 0],
        // Frutta secca
        ['name' => 'Mandorle', 'category' => 'Frutta secca', 'unit_type' => 'kg', 'price_sale' => 15.00, 'price_cost' => 12.00, 'is_active' => 1, 'is_favorite' => 1],
        ['name' => 'Noci', 'category' => 'Frutta secca', 'unit_type' => 'kg', 'price_sale' => 12.00, 'price_cost' => 10.00, 'is_active' => 1, 'is_favorite' => 0],
        ['name' => 'Nocciole', 'category' => 'Frutta secca', 'unit_type' => 'kg', 'price_sale' => 14.00, 'price_cost' => 11.00, 'is_active' => 1, 'is_favorite' => 1]
    ];

    foreach ($products as $prod) {
        $categoryId = $categoryIds[$prod['category']] ?? null;
        $checkStmt = $pdo->prepare("SELECT id FROM products WHERE store_id = ? AND name = ? LIMIT 1");
        $checkStmt->execute([$storeId, $prod['name']]);
        $existingProductId = $checkStmt->fetchColumn();

        if ($existingProductId) {
            $pdo->prepare("UPDATE products SET category_id = ?, unit_type = ?, price_sale = ?, price_cost = ?, is_active = ?, is_favorite = ? WHERE id = ? AND store_id = ?")
                ->execute([$categoryId, $prod['unit_type'], $prod['price_sale'], $prod['price_cost'], $prod['is_active'], $prod['is_favorite'], $existingProductId, $storeId]);
        } else {
            $pdo->prepare("INSERT INTO products (store_id, name, category_id, unit_type, price_sale, price_cost, is_active, is_favorite) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$storeId, $prod['name'], $categoryId, $prod['unit_type'], $prod['price_sale'], $prod['price_cost'], $prod['is_active'], $prod['is_favorite']]);
        }
    }

    echo "Dati demo inseriti con successo per store_id={$storeId}!\n";
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
?>