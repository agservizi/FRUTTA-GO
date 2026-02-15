<?php
// api/inventory.php - API per magazzino
$method = $_SERVER['REQUEST_METHOD'];


$db = getDB();
$storeId = getCurrentStoreId();

if ($method === 'GET') {
    try {
        // Giacenze attuali
        $stmt = $db->prepare("
            SELECT p.id, p.name, p.unit_type,
                   COALESCE(SUM(CASE WHEN im.type = 'in' THEN im.qty ELSE -im.qty END), 0) as stock
            FROM products p
            LEFT JOIN inventory_movements im ON p.id = im.product_id AND im.store_id = p.store_id
            WHERE p.is_active = 1 AND p.store_id = ?
            GROUP BY p.id, p.name, p.unit_type
            ORDER BY p.name
        ");
        $stmt->execute([$storeId]);
        $stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Movimenti recenti
        $stmt = $db->prepare("
            SELECT im.*, p.name as product_name, u.name as user_name
            FROM inventory_movements im
            JOIN products p ON im.product_id = p.id AND p.store_id = im.store_id
            JOIN users u ON im.user_id = u.id
            WHERE im.store_id = ?
            ORDER BY im.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$storeId]);
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        successResponse(['stock' => $stock, 'movements' => $movements]);
    } catch (Exception $e) {
        logError("Get inventory error: " . $e->getMessage());
        errorResponse('Errore nel recupero magazzino', 500);
    }
} elseif ($method === 'POST') {
    if (!hasPermission('inventory')) {
        errorResponse('Permesso negato', 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $product_id = $data['product_id'] ?? 0;
    $type = $data['type'] ?? '';
    $qty = $data['qty'] ?? 0;
    $unit_type = $data['unit_type'] ?? 'kg';
    $cost_total = $data['cost_total'] ?? null;
    $reason = $data['reason'] ?? null;
    $note = $data['note'] ?? null;

    if (!$product_id || !in_array($type, ['in', 'out']) || $qty <= 0) {
        errorResponse('Dati movimento non validi');
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO inventory_movements (product_id, type, qty, unit_type, cost_total, reason, note, user_id, store_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$product_id, $type, $qty, $unit_type, $cost_total, $reason, $note, $_SESSION['user_id'], $storeId]);
        successResponse(['id' => $db->lastInsertId()], 'Movimento registrato');
    } catch (Exception $e) {
        logError("Create inventory movement error: " . $e->getMessage());
        errorResponse('Errore nella registrazione movimento', 500);
    }
} else {
    errorResponse('Metodo non supportato', 405);
}
?>