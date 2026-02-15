<?php
// index.php - Entry point dell'applicazione
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/bootstrap.php';

// Avvia sessione
session_start();

// Routing semplice
$request = $_SERVER['REQUEST_URI'];
$basePath = '/'; // Modifica se necessario
$path = str_replace($basePath, '', parse_url($request, PHP_URL_PATH));
$path = trim($path, '/');

// API è gestita da api.php, qui gestiamo solo le pagine web

// Routing pagine
switch ($path) {
    case '':
    case 'login':
        require_once TEMPLATES_DIR . 'pages/login.php';
        break;
    case 'dashboard':
        require_once TEMPLATES_DIR . 'pages/dashboard.php';
        break;
    case 'products':
        require_once TEMPLATES_DIR . 'pages/products.php';
        break;
    case 'sale':
        require_once TEMPLATES_DIR . 'pages/sale.php';
        break;
    case 'inventory':
        require_once TEMPLATES_DIR . 'pages/inventory.php';
        break;
    case 'purchases':
        require_once TEMPLATES_DIR . 'pages/purchases.php';
        break;
    case 'settings':
        require_once TEMPLATES_DIR . 'pages/settings.php';
        break;
    case 'reports':
        require_once TEMPLATES_DIR . 'pages/reports.php';
        break;
    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}
?>