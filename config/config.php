<?php
// config.php - Configurazione dell'applicazione

// Carica le variabili d'ambiente da .env se esiste
if (file_exists(__DIR__ . '/../.env')) {
    $envContent = file_get_contents(__DIR__ . '/../.env');
    if ($envContent !== false) {
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue; // Salta commenti e righe vuote
            }
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Database
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'frutta_go');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

// Blob Storage (S3 compatible)
define('S3_ENDPOINT', $_ENV['S3_ENDPOINT'] ?? '');
define('S3_BUCKET', $_ENV['S3_BUCKET'] ?? 'frutta-go-images');
define('S3_KEY', $_ENV['S3_KEY'] ?? '');
define('S3_SECRET', $_ENV['S3_SECRET'] ?? '');
define('S3_REGION', $_ENV['S3_REGION'] ?? 'us-east-1');

// App settings
define('APP_NAME', 'Frutta Go');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('SESSION_NAME', 'frutta_go_session');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('REMEMBER_ME_DAYS', (int)($_ENV['REMEMBER_ME_DAYS'] ?? 30));

// Paths
define('ROOT_DIR', __DIR__ . '/../');
define('PUBLIC_DIR', ROOT_DIR . 'public/');
define('APP_DIR', ROOT_DIR . 'app/');
define('TEMPLATES_DIR', ROOT_DIR . 'templates/');
define('STORAGE_DIR', ROOT_DIR . 'storage/');
define('ASSETS_DIR', PUBLIC_DIR . 'assets/');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['DEBUG'] ?? false ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', STORAGE_DIR . 'logs/error.log');

// Session defaults
$sessionLifetime = REMEMBER_ME_DAYS * 86400;
ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', strpos(APP_URL, 'https://') === 0 ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
?>