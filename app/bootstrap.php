<?php
// bootstrap.php - Inizializzazione applicazione

// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Funzioni helper
require_once __DIR__ . '/helpers.php';

// Database connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        ensureMultiStoreSchema($pdo);
    }
    return $pdo;
}

function tableHasColumn(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

function ensureMultiStoreSchema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS stores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        code VARCHAR(100) NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_store_code (code)
    )");

    $db->exec("INSERT INTO stores (id, name, code) VALUES (1, 'Negozio Principale', 'main')
        ON DUPLICATE KEY UPDATE name = VALUES(name)");

    $tables = [
        'users',
        'categories',
        'products',
        'inventory_movements',
        'sales',
        'sale_items',
        'suppliers',
        'purchases',
        'purchase_items'
    ];

    foreach ($tables as $table) {
        if (!tableHasColumn($db, $table, 'store_id')) {
            $db->exec("ALTER TABLE `$table` ADD COLUMN store_id INT NOT NULL DEFAULT 1");
        }
    }

    $db->exec("CREATE TABLE IF NOT EXISTS store_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        store_id INT NOT NULL,
        key_name VARCHAR(100) NOT NULL,
        value_text TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_store_key (store_id, key_name),
        INDEX idx_store_settings_store (store_id),
        CONSTRAINT fk_store_settings_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
    )");

    $stmt = $db->prepare("INSERT INTO store_settings (store_id, key_name, value_text)
        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value_text = VALUES(value_text)");
    foreach (getDefaultAppSettings() as $key => $value) {
        $stmt->execute([1, $key, $value]);
    }
}

function getDefaultAppSettings() {
    return [
        'store_name' => APP_NAME,
        'currency_symbol' => '€',
        'vat_rate' => '4',
        'low_stock_threshold' => '5',
        'receipt_footer' => 'Grazie per aver acquistato da ' . APP_NAME . '!'
    ];
}

function getAppSettings() {
    static $settings = null;
    static $settingsStoreId = null;

    $storeId = getCurrentStoreId();

    if ($settings !== null && $settingsStoreId === $storeId) {
        return $settings;
    }

    $settings = getDefaultAppSettings();
    $settingsStoreId = $storeId;

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT key_name, value_text FROM store_settings WHERE store_id = ?");
        $stmt->execute([$storeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            if (array_key_exists($row['key_name'], $settings)) {
                $settings[$row['key_name']] = (string)$row['value_text'];
            }
        }
    } catch (Exception $e) {
        logError('Load app settings error: ' . $e->getMessage());
    }

    return $settings;
}

function getCurrentStoreId(): int {
    if (isset($_SESSION['store_id'])) {
        return (int)$_SESSION['store_id'];
    }

    if (!empty($_SESSION['user_id'])) {
        try {
            $stmt = getDB()->prepare("SELECT store_id FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $storeId = (int)($stmt->fetchColumn() ?: 1);
            $_SESSION['store_id'] = $storeId;
            return $storeId;
        } catch (Exception $e) {
            logError('Load current store error: ' . $e->getMessage());
        }
    }

    return 1;
}

function getAppSetting($key, $default = null) {
    $settings = getAppSettings();

    if (array_key_exists($key, $settings)) {
        return $settings[$key];
    }

    return $default;
}

// S3 Client
function getS3Client() {
    return new Aws\S3\S3Client([
        'version' => 'latest',
        'region' => S3_REGION,
        'endpoint' => S3_ENDPOINT,
        'credentials' => [
            'key' => S3_KEY,
            'secret' => S3_SECRET,
        ],
    ]);
}

// Funzione per sanitizzare output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Funzione per generare CSRF token
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Verifica CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Controlla se utente loggato
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Ottieni utente corrente
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = getDB()->prepare("SELECT * FROM users WHERE id = ? AND store_id = ?");
        $stmt->execute([$_SESSION['user_id'], getCurrentStoreId()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $user;
}

// Controlla permesso
function hasPermission($permission) {
    $user = getCurrentUser();
    if (!$user) return false;
    if ($user['role'] === 'admin') return true;
    // Logica permessi per operator
    $operatorPermissions = ['sale', 'inventory', 'purchases', 'products_read', 'reports_read'];
    return in_array($permission, $operatorPermissions);
}
?>