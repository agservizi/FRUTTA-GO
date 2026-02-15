<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/bootstrap.php';

try {
    $db = getDB();

    $tables = ['users', 'categories', 'products', 'inventory_movements', 'sales', 'sale_items', 'suppliers', 'purchases', 'purchase_items'];
    $fkMap = [
        'users' => 'fk_users_store',
        'categories' => 'fk_categories_store',
        'products' => 'fk_products_store',
        'inventory_movements' => 'fk_inventory_movements_store',
        'sales' => 'fk_sales_store',
        'sale_items' => 'fk_sale_items_store',
        'suppliers' => 'fk_suppliers_store',
        'purchases' => 'fk_purchases_store',
        'purchase_items' => 'fk_purchase_items_store',
    ];

    echo "--INDEX--\n";
    $indexStmt = $db->prepare(
        "SELECT COUNT(*) AS c
         FROM information_schema.statistics
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND index_name = ?"
    );
    foreach ($tables as $table) {
        $indexName = 'idx_' . $table . '_store_id';
        $indexStmt->execute([$table, $indexName]);
        $count = (int)$indexStmt->fetchColumn();
        echo $table . ':' . $count . "\n";
    }

    echo "--FK--\n";
    $fkStmt = $db->prepare(
        "SELECT COUNT(*) AS c
         FROM information_schema.table_constraints
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND constraint_name = ?
           AND constraint_type = 'FOREIGN KEY'"
    );
    foreach ($fkMap as $table => $fkName) {
        $fkStmt->execute([$table, $fkName]);
        $count = (int)$fkStmt->fetchColumn();
        echo $table . ':' . $count . "\n";
    }

    echo "--STORES--\n";
    $storesStmt = $db->query(
        "SELECT s.code,
                (SELECT COUNT(*) FROM users u WHERE u.store_id = s.id) AS users_count,
                (SELECT COUNT(*) FROM categories c WHERE c.store_id = s.id) AS categories_count,
                (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id) AS products_count
         FROM stores s
         WHERE s.code IN ('main', 'demo2')
         ORDER BY s.code"
    );

    while ($row = $storesStmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['code']
            . ' users=' . $row['users_count']
            . ' categories=' . $row['categories_count']
            . ' products=' . $row['products_count']
            . "\n";
    }

    echo "VERIFY_OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'VERIFY_ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
