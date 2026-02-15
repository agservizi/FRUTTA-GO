<?php
// api/categories.php - API per categorie
$method = $_SERVER['REQUEST_METHOD'];


$db = getDB();

if ($method === 'GET') {
    try {
        $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order, name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        successResponse($categories);
    } catch (Exception $e) {
        logError("Get categories error: " . $e->getMessage());
        errorResponse('Errore nel recupero categorie', 500);
    }
} elseif ($method === 'POST') {
    if (!hasPermission('settings')) {
        errorResponse('Permesso negato', 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $sort_order = (int)($data['sort_order'] ?? 0);

    if ($name === '') {
        errorResponse('Nome categoria richiesto');
    }

    try {
        $stmt = $db->prepare("INSERT INTO categories (name, sort_order) VALUES (?, ?)");
        $stmt->execute([$name, $sort_order]);
        successResponse(['id' => $db->lastInsertId()], 'Categoria creata');
    } catch (Exception $e) {
        logError("Create categories error: " . $e->getMessage());
        errorResponse('Errore nella creazione categoria', 500);
    }
} elseif ($method === 'PUT') {
    if (!hasPermission('settings')) {
        errorResponse('Permesso negato', 403);
    }

    $id = $_GET['id'] ?? 0;
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $sort_order = (int)($data['sort_order'] ?? 0);

    if (!$id || $name === '') {
        errorResponse('Dati categoria non validi');
    }

    try {
        $stmt = $db->prepare("UPDATE categories SET name = ?, sort_order = ? WHERE id = ?");
        $stmt->execute([$name, $sort_order, $id]);
        successResponse(null, 'Categoria aggiornata');
    } catch (Exception $e) {
        logError("Update categories error: " . $e->getMessage());
        errorResponse('Errore nell\'aggiornamento categoria', 500);
    }
} elseif ($method === 'DELETE') {
    if (!hasPermission('settings')) {
        errorResponse('Permesso negato', 403);
    }

    $id = $_GET['id'] ?? 0;
    if (!$id) {
        errorResponse('ID categoria non valido');
    }

    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        $productsCount = (int)$stmt->fetchColumn();

        if ($productsCount > 0) {
            errorResponse('Impossibile eliminare: categoria usata da prodotti esistenti', 409);
        }

        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        successResponse(null, 'Categoria eliminata');
    } catch (Exception $e) {
        logError("Delete categories error: " . $e->getMessage());
        errorResponse('Errore nell\'eliminazione categoria', 500);
    }
} else {
    errorResponse('Metodo non supportato', 405);
}
?>