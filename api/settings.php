<?php
// api/settings.php - API impostazioni generali
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
$storeId = getCurrentStoreId();

function ensureSettingsSchema(PDO $db) {
    $db->exec("CREATE TABLE IF NOT EXISTS store_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        store_id INT NOT NULL,
        key_name VARCHAR(100) NOT NULL,
        value_text TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_store_key (store_id, key_name)
    )");
}

function ensureDefaultStoreSettings(PDO $db, int $storeId) {
    $stmt = $db->prepare("INSERT INTO store_settings (store_id, key_name, value_text) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value_text = value_text");
    $defaults = [
        'store_name' => APP_NAME,
        'currency_symbol' => '€',
        'vat_rate' => '4',
        'low_stock_threshold' => '5',
        'receipt_footer' => 'Grazie per aver acquistato da ' . APP_NAME . '!'
    ];

    foreach ($defaults as $key => $value) {
        $stmt->execute([$storeId, $key, $value]);
    }
}

ensureSettingsSchema($db);
ensureDefaultStoreSettings($db, $storeId);

if ($method === 'GET') {
    try {
        $stmt = $db->prepare("SELECT key_name, value_text FROM store_settings WHERE store_id = ?");
        $stmt->execute([$storeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key_name']] = $row['value_text'];
        }

        successResponse(['settings' => $settings]);
    } catch (Exception $e) {
        logError('Get settings error: ' . $e->getMessage());
        errorResponse('Errore nel recupero impostazioni', 500);
    }
} elseif ($method === 'POST') {
    if (!hasPermission('settings')) {
        errorResponse('Permesso negato', 403);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $allowedKeys = ['store_name', 'currency_symbol', 'vat_rate', 'low_stock_threshold', 'receipt_footer'];

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO store_settings (store_id, key_name, value_text) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value_text = VALUES(value_text)");

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $value = is_scalar($data[$key]) ? trim((string)$data[$key]) : '';
                $stmt->execute([$storeId, $key, $value]);
            }
        }

        $db->commit();
        successResponse(null, 'Impostazioni salvate');
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('Save settings error: ' . $e->getMessage());
        errorResponse('Errore nel salvataggio impostazioni', 500);
    }
} else {
    errorResponse('Metodo non supportato', 405);
}
?>