<?php
// api/sales.php - API per vendite
$method = $_SERVER['REQUEST_METHOD'];


$db = getDB();
$storeId = getCurrentStoreId();

if ($method === 'GET') {
    try {
        $stmt = $db->prepare("
            SELECT s.*, u.name as user_name,
                   GROUP_CONCAT(CONCAT(p.name, ' (', si.qty, ' ', si.unit_price, ')') SEPARATOR '; ') as items
            FROM sales s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN sale_items si ON s.id = si.sale_id AND si.store_id = s.store_id
            LEFT JOIN products p ON si.product_id = p.id AND p.store_id = s.store_id
            WHERE s.store_id = ?
            GROUP BY s.id
            ORDER BY s.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$storeId]);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        successResponse($sales);
    } catch (Exception $e) {
        logError("Get sales error: " . $e->getMessage());
        errorResponse('Errore nel recupero vendite', 500);
    }
} elseif ($method === 'POST') {
    if (!hasPermission('sale')) {
        errorResponse('Permesso negato', 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $items = $data['items'] ?? [];
    $discount_type = $data['discount_type'] ?? null;
    $discount_value = $data['discount_value'] ?? null;
    $payment_method = $data['payment_method'] ?? 'cash';

    if (empty($items)) {
        errorResponse('Nessun prodotto nella vendita');
    }

    try {
        $db->beginTransaction();

        // Calcola totale
        $total = 0;
        foreach ($items as $item) {
            $total += $item['line_total'];
        }

        // Applica sconto
        if ($discount_type === 'percentage' && $discount_value > 0) {
            $total -= $total * ($discount_value / 100);
        } elseif ($discount_type === 'fixed' && $discount_value > 0) {
            $total -= $discount_value;
        }
        $total = max(0, $total);

        // Inserisci vendita
        $stmt = $db->prepare("
            INSERT INTO sales (total, discount_type, discount_value, payment_method, user_id, store_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$total, $discount_type, $discount_value, $payment_method, $_SESSION['user_id'], $storeId]);
        $sale_id = $db->lastInsertId();

        // Inserisci righe
        $stmt = $db->prepare("
            INSERT INTO sale_items (sale_id, product_id, qty, unit_price, line_total, store_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($items as $item) {
            $stmt->execute([$sale_id, $item['product_id'], $item['qty'], $item['unit_price'], $item['line_total'], $storeId]);

            // Registra movimento magazzino per vendita
            $inv_stmt = $db->prepare("
                INSERT INTO inventory_movements (product_id, type, qty, unit_type, reason, user_id, store_id)
                VALUES (?, 'out', ?, ?, 'vendita', ?, ?)
            ");
            $inv_stmt->execute([$item['product_id'], $item['qty'], $item['unit_type'], $_SESSION['user_id'], $storeId]);
        }

        $db->commit();
        successResponse(['id' => $sale_id, 'total' => $total], 'Vendita registrata');
    } catch (Exception $e) {
        $db->rollBack();
        logError("Create sale error: " . $e->getMessage());
        errorResponse('Errore nella registrazione vendita', 500);
    }
} else {
    errorResponse('Metodo non supportato', 405);
}
?>