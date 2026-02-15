<?php
// api/purchases.php - API acquisti
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

function ensurePurchasesSchema(PDO $db) {
    $db->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        phone VARCHAR(50) NULL,
        email VARCHAR(150) NULL,
        address VARCHAR(255) NULL,
        note TEXT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NULL,
        total DECIMAL(10,2) NOT NULL,
        note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id INT NOT NULL,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS purchase_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_id INT NOT NULL,
        product_id INT NOT NULL,
        qty DECIMAL(10,3) NOT NULL,
        unit_type ENUM('kg', 'pz', 'cassetta') NOT NULL,
        unit_cost DECIMAL(10,2) NOT NULL,
        line_total DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
}

ensurePurchasesSchema($db);

if ($method === 'GET') {
    try {
        $stmt = $db->query("
            SELECT p.id, p.total, p.note, p.created_at,
                   s.name as supplier_name,
                   u.name as user_name,
                   COUNT(pi.id) as items_count
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            JOIN users u ON p.user_id = u.id
            LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
            GROUP BY p.id, p.total, p.note, p.created_at, s.name, u.name
            ORDER BY p.created_at DESC
            LIMIT 100
        ");
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        successResponse(['purchases' => $purchases]);
    } catch (Exception $e) {
        logError("Get purchases error: " . $e->getMessage());
        errorResponse('Errore nel recupero acquisti', 500);
    }
} elseif ($method === 'POST') {
    if (!hasPermission('purchases')) {
        errorResponse('Permesso negato', 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $supplier_id = $data['supplier_id'] ?? null;
    $note = trim($data['note'] ?? '');
    $items = $data['items'] ?? [];

    if (empty($items)) {
        errorResponse('Nessun prodotto nell\'acquisto');
    }

    try {
        $db->beginTransaction();

        $total = 0;
        foreach ($items as $item) {
            $product_id = (int)($item['product_id'] ?? 0);
            $qty = (float)($item['qty'] ?? 0);
            $unit_cost = (float)($item['unit_cost'] ?? 0);
            $unit_type = $item['unit_type'] ?? 'kg';

            if (!$product_id || $qty <= 0 || $unit_cost < 0 || !in_array($unit_type, ['kg', 'pz', 'cassetta'], true)) {
                throw new Exception('Riga acquisto non valida');
            }

            $total += $qty * $unit_cost;
        }

        $stmt = $db->prepare("INSERT INTO purchases (supplier_id, total, note, user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$supplier_id ?: null, $total, $note ?: null, $_SESSION['user_id']]);
        $purchase_id = $db->lastInsertId();

        $itemStmt = $db->prepare("INSERT INTO purchase_items (purchase_id, product_id, qty, unit_type, unit_cost, line_total) VALUES (?, ?, ?, ?, ?, ?)");
        $invStmt = $db->prepare("INSERT INTO inventory_movements (product_id, type, qty, unit_type, cost_total, note, user_id) VALUES (?, 'in', ?, ?, ?, ?, ?)");
        $costUpdateStmt = $db->prepare("UPDATE products SET price_cost = ? WHERE id = ?");

        foreach ($items as $item) {
            $product_id = (int)$item['product_id'];
            $qty = (float)$item['qty'];
            $unit_cost = (float)$item['unit_cost'];
            $unit_type = $item['unit_type'];
            $line_total = $qty * $unit_cost;

            $itemStmt->execute([$purchase_id, $product_id, $qty, $unit_type, $unit_cost, $line_total]);

            $movementNote = 'Acquisto #' . $purchase_id;
            $invStmt->execute([$product_id, $qty, $unit_type, $line_total, $movementNote, $_SESSION['user_id']]);

            $costUpdateStmt->execute([$unit_cost, $product_id]);
        }

        $db->commit();
        successResponse(['id' => $purchase_id, 'total' => $total], 'Acquisto registrato');
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError("Create purchase error: " . $e->getMessage());
        errorResponse('Errore nella registrazione acquisto', 500);
    }
} else {
    errorResponse('Metodo non supportato', 405);
}
?>