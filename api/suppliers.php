<?php
// api/suppliers.php - API fornitori
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
$storeId = getCurrentStoreId();

function ensureSuppliersSchema(PDO $db) {
    $db->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        phone VARCHAR(50) NULL,
        email VARCHAR(150) NULL,
        address VARCHAR(255) NULL,
        note TEXT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        store_id INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

ensureSuppliersSchema($db);

if ($method === 'GET') {
    try {
        $stmt = $db->prepare("SELECT * FROM suppliers WHERE is_active = 1 AND store_id = ? ORDER BY name");
        $stmt->execute([$storeId]);
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        successResponse(['suppliers' => $suppliers]);
    } catch (Exception $e) {
        logError("Get suppliers error: " . $e->getMessage());
        errorResponse('Errore nel recupero fornitori', 500);
    }
} elseif ($method === 'POST') {
    if (!hasPermission('purchases')) {
        errorResponse('Permesso negato', 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $address = trim($data['address'] ?? '');
    $note = trim($data['note'] ?? '');

    if ($name === '') {
        errorResponse('Nome fornitore richiesto');
    }

    try {
        $stmt = $db->prepare("INSERT INTO suppliers (name, phone, email, address, note, store_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone ?: null, $email ?: null, $address ?: null, $note ?: null, $storeId]);

        successResponse(['id' => $db->lastInsertId()], 'Fornitore creato');
    } catch (Exception $e) {
        logError("Create supplier error: " . $e->getMessage());
        errorResponse('Errore nella creazione fornitore', 500);
    }
} elseif ($method === 'DELETE') {
    if (!hasPermission('purchases')) {
        errorResponse('Permesso negato', 403);
    }

    $id = $_GET['id'] ?? 0;
    if (!$id) {
        errorResponse('ID fornitore non valido');
    }

    try {
        $stmt = $db->prepare("UPDATE suppliers SET is_active = 0 WHERE id = ? AND store_id = ?");
        $stmt->execute([$id, $storeId]);
        successResponse(null, 'Fornitore disattivato');
    } catch (Exception $e) {
        logError("Delete supplier error: " . $e->getMessage());
        errorResponse('Errore nella disattivazione fornitore', 500);
    }
} else {
    errorResponse('Metodo non supportato', 405);
}
?>