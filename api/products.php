<?php
// api/products.php - API per prodotti
$method = $_SERVER['REQUEST_METHOD'];


$db = getDB();

if ($method === 'GET') {
    try {
        $stmt = $db->query("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1
            ORDER BY p.name
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        successResponse($products);
    } catch (Exception $e) {
        logError("Get products error: " . $e->getMessage());
        errorResponse('Errore nel recupero prodotti', 500);
    }
} elseif ($method === 'POST') {
    if (!hasPermission('products')) {
        errorResponse('Permesso negato', 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $category_id = $data['category_id'] ?? null;
    $unit_type = $data['unit_type'] ?? 'kg';
    $price_sale = $data['price_sale'] ?? 0;
    $price_cost = $data['price_cost'] ?? null;
    $is_favorite = $data['is_favorite'] ?? false;

    if (empty($name) || $price_sale <= 0) {
        errorResponse('Nome e prezzo di vendita richiesti');
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO products (name, category_id, unit_type, price_sale, price_cost, is_favorite)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $category_id, $unit_type, $price_sale, $price_cost, $is_favorite]);
        successResponse(['id' => $db->lastInsertId()], 'Prodotto creato');
    } catch (Exception $e) {
        logError("Create product error: " . $e->getMessage());
        errorResponse('Errore nella creazione del prodotto', 500);
    }
} elseif ($method === 'PUT') {
    if (!hasPermission('products')) {
        errorResponse('Permesso negato', 403);
    }

    $id = $_GET['id'] ?? 0;
    $data = json_decode(file_get_contents('php://input'), true);

    try {
        $fields = [];
        $values = [];
        $allowedFields = ['name', 'category_id', 'unit_type', 'price_sale', 'price_cost', 'is_favorite'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            errorResponse('Nessun campo da aggiornare');
        }

        $values[] = $id;
        $stmt = $db->prepare("UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($values);
        successResponse(null, 'Prodotto aggiornato');
    } catch (Exception $e) {
        logError("Update product error: " . $e->getMessage());
        errorResponse('Errore nell\'aggiornamento', 500);
    }
} elseif ($method === 'DELETE') {
    if (!hasPermission('products')) {
        errorResponse('Permesso negato', 403);
    }

    $id = $_GET['id'] ?? 0;

    try {
        $stmt = $db->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        successResponse(null, 'Prodotto disattivato');
    } catch (Exception $e) {
        logError("Delete product error: " . $e->getMessage());
        errorResponse('Errore nella disattivazione', 500);
    }
} else {
    errorResponse('Metodo non supportato', 405);
}
?>