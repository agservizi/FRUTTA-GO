<?php
// api.php - API entry point

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/bootstrap.php';

// Avvia sessione
session_name(SESSION_NAME);
session_start();

header('Content-Type: application/json');

// Ottieni l'action dalla query string o dal path
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Se non c'è action, prova a prenderla dal path della richiesta
if (empty($action)) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);

    // Rimuovi /api.php/ dal path
    if (strpos($path, '/api.php/') === 0) {
        $action = str_replace('/api.php/', '', $path);
    }

    // Se l'action contiene parametri, separali
    if (strpos($action, '?') !== false) {
        $action = strstr($action, '?', true);
    }
    $action = trim($action, '/');
}

// Login non richiede autenticazione
if ($action !== 'auth' && !isLoggedIn()) {
    errorResponse('Non autorizzato', 401);
}

switch ($action) {
    case 'auth':
        require_once __DIR__ . '/../api/auth.php';
        break;
    case 'products':
        require_once __DIR__ . '/../api/products.php';
        break;
    case 'categories':
        require_once __DIR__ . '/../api/categories.php';
        break;
    case 'settings':
        require_once __DIR__ . '/../api/settings.php';
        break;
    case 'inventory':
        require_once __DIR__ . '/../api/inventory.php';
        break;
    case 'suppliers':
        require_once __DIR__ . '/../api/suppliers.php';
        break;
    case 'purchases':
        require_once __DIR__ . '/../api/purchases.php';
        break;
    case 'sales':
        require_once __DIR__ . '/../api/sales.php';
        break;
    case 'reports':
        require_once __DIR__ . '/../api/reports.php';
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