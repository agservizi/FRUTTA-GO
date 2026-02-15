<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/bootstrap.php';

try {
    $pdo = getDB();
    $storeId = getCurrentStoreId();

    // Ordine per evitare violazioni di foreign key
    $tables = ['sale_items', 'purchase_items', 'inventory_movements', 'sales', 'purchases', 'products', 'categories', 'suppliers'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE store_id = ?");
        $stmt->execute([$storeId]);
        echo "Tabella $table ripulita per store_id={$storeId}.\n";
    }
    echo "Dati demo ripuliti con successo per store_id={$storeId}!\n";
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
?>