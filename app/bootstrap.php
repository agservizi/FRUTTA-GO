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
    }
    return $pdo;
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

    if ($settings !== null) {
        return $settings;
    }

    $settings = getDefaultAppSettings();

    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS app_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(100) NOT NULL UNIQUE,
            value_text TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $stmt = $db->query("SELECT key_name, value_text FROM app_settings");
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
        $stmt = getDB()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
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