<?php
// api/router.php - Router per API

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/bootstrap.php';

// Debug temporaneo
error_log("Router called with API_PATH: " . ($_SERVER['API_PATH'] ?? 'none'));

$method = $_SERVER['REQUEST_METHOD'];

// Ottieni il path dall'index.php
$fullPath = isset($_SERVER['API_PATH']) ? $_SERVER['API_PATH'] : '';
$path = str_replace('api/', '', $fullPath);

// Gestisci parametri aggiuntivi nell'URL
if (strpos($path, '?') !== false) {
    $path = strstr($path, '?', true);
}
$path = trim($path, '/');

// Se il path contiene ancora /, prendi solo la prima parte
if (strpos($path, '/') !== false) {
    $path = explode('/', $path)[0];
}

// Login non richiede autenticazione
if ($path !== 'auth' && !isLoggedIn()) {
    errorResponse('Non autorizzato', 401);
}

switch ($path) {
    case 'auth':
        require_once __DIR__ . '/../api/auth.php';
        break;
    case 'products':
        require_once __DIR__ . '/../api/products.php';
        break;
    case 'categories':
        require_once __DIR__ . '/../api/categories.php';
        break;
    case 'inventory':
        require_once __DIR__ . '/../api/inventory.php';
        break;
    case 'sales':
        require_once __DIR__ . '/../api/sales.php';
        break;
    case 'upload':
        require_once __DIR__ . '/../api/upload.php';
        break;
    case 'receipt':
        require_once __DIR__ . '/../api/receipt.php';
        break;
    default:
        errorResponse('Endpoint non trovato', 404);
}
?>