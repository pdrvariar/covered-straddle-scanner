<?php
// Turn on error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Increase memory limit for scanning large amount of tickers
ini_set('memory_limit', '256M');

// Load bootstrap
require_once __DIR__ . '/../bootstrap.php';

// Iniciar Xdebug quando o parâmetro estiver presente
if (isset($_GET['XDEBUG_SESSION_START']) || isset($_POST['XDEBUG_SESSION_START']) || isset($_COOKIE['XDEBUG_SESSION'])) {
    // Forçar o início do Xdebug
    if (function_exists('xdebug_break')) {
        xdebug_break(); // Ponto de breakpoint automático
    }

    // Definir cookie para Xdebug
    if (!isset($_COOKIE['XDEBUG_SESSION'])) {
        $cookieParams = session_get_cookie_params();
        setcookie(
            'XDEBUG_SESSION',
            'PHPSTORM',
            time() + (24 * 3600), // 24 horas
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }
}

// Simple routing
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($query ?? '', $params);

// API routes
if (strpos($path, '/api/') === 0) {
    // Auth Check for API
    if (!isset($_SESSION['user_id'])) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $api = new \App\Controllers\ApiController();

    $apiPath = substr($path, 5); // Remove '/api/'

    switch ($apiPath) {
        case 'scan':
            $api->scan();
            break;
        case 'operations':
            $api->getOperations();
            break;
        case 'save':
            $api->saveOperation();
            break;
        case 'operations/delete':
            $api->deleteOperation();
            break;
        default:
            if (isset($params['id'])) {
                $api->getOperation($params['id']);
            } else {
                header('HTTP/1.1 404 Not Found');
                echo json_encode(['error' => 'API endpoint not found']);
            }
            break;
    }
    exit;
}

// Web routes
$action = $params['action'] ?? 'dashboard';

// Auth Check
if (!isset($_SESSION['user_id']) && $action !== 'login') {
    header('Location: /?action=login');
    exit;
}

switch ($action) {
    case 'login':
        $controller = new \App\Controllers\AuthController();
        $controller->login();
        break;

    case 'logout':
        $controller = new \App\Controllers\AuthController();
        $controller->logout();
        break;

    case 'scan':
        $controller = new \App\Controllers\ScannerController();
        $controller->scan();
        break;

    case 'results':
        $controller = new \App\Controllers\ScannerController();
        $controller->results();
        break;

    case 'details':
        $controller = new \App\Controllers\ScannerController();
        $controller->details();
        break;

    case 'save':
        $controller = new \App\Controllers\ScannerController();
        $controller->save();
        break;

    case 'operations':
        $controller = new \App\Controllers\OperationController();

        // Verificar se há sub-ação (show, export, etc.)
        $subAction = $_GET['sub'] ?? 'index';
        $id = $_GET['id'] ?? null;

        switch ($subAction) {
            case 'show':
                if ($id) {
                    $controller->show($id);
                } else {
                    header('Location: /?action=operations');
                }
                break;
            case 'export':
                $controller->export();
                break;
            default:
                $controller->index();
                break;
        }
        break;

    case 'dashboard':
    default:
        $controller = new \App\Controllers\DashboardController();
        $controller->index();
        break;
}

// Handle 404
if (!isset($controller)) {
    header('HTTP/1.1 404 Not Found');
    echo '<h1>404 - Page Not Found</h1>';
    echo '<p>The requested page could not be found.</p>';
    exit;
}
?>