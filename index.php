<?php
// ============================================================
// Asterisk Manager - Front Controller / Router
// ============================================================

require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
session_start();

// Initialize i18n
$lang = $_SESSION['language'] ?? getSetting('default_language') ?? 'en';
I18n::load($lang);

// CSRF protection for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST[CSRF_TOKEN_NAME])) {
        http_response_code(403);
        die(json_encode(['error' => 'CSRF validation failed']));
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auth check (except login page)
$page   = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

if ($page !== 'login' && $page !== 'logout' && !($page === 'phonebook' && $action === 'xml') && !isLoggedIn()) {
    redirect('?page=login');
}

// Route map
$routes = [
    'dashboard'       => 'DashboardController',
    'extensions'      => 'ExtensionsController',
    'queues'          => 'QueuesController',
    'ring_groups'     => 'RingGroupsController',
    'dialplan'        => 'DialplanController',
    'inbound_routes'  => 'InboundRoutesController',
    'outbound_routes' => 'OutboundRoutesController',
    'trunks'          => 'TrunksController',
    'cdr'             => 'CDRController',
    'invoices'        => 'InvoicesController',
    'customers'       => 'CustomersController',
    'rate_plans'      => 'RatePlansController',
    'settings'        => 'SettingsController',
    'provision'       => 'ProvisionController',
    'phonebook'       => 'PhonebookController',
    'security'        => 'SecurityController',
    'login'           => 'AuthController',
    'logout'          => 'AuthController',
];

$controllerClass = $routes[$page] ?? 'DashboardController';
$controllerFile  = APP_ROOT . '/controllers/' . $controllerClass . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    die('Page not found');
}

require_once APP_ROOT . '/controllers/BaseController.php';
require_once $controllerFile;

$controller = new $controllerClass();

// Handle action
$method   = strtolower($_SERVER['REQUEST_METHOD']) . '_' . $action;
$fallback = $action;

if (method_exists($controller, $method)) {
    $controller->$method();
} elseif (method_exists($controller, $fallback)) {
    $controller->$fallback();
} else {
    $controller->index();
}
