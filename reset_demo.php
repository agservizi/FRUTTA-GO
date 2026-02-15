<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/bootstrap.php';

try {
    $pdo = getDB();
    // Ordine per evitare violazioni di foreign key
    $tables = ['sale_items', 'purchase_items', 'sales', 'purchases', 'products', 'categories'];
    foreach ($tables as $table) {
        $pdo->exec("DELETE FROM $table");
        echo "Tabella $table svuotata.\n";
    }
    echo "Dati demo ripuliti con successo!\n";
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
?>