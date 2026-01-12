<?php
// Turn on error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

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

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
} else {
    // Default values for development
    $_ENV = array_merge($_ENV, [
        'DB_HOST' => 'db',
        'DB_NAME' => 'straddle_scanner',
        'DB_USER' => 'straddle_user',
        'DB_PASS' => 'straddle_pass',
        'APP_URL' => 'http://localhost:8080',
        'APP_ENV' => 'development'
    ]);
}

// Load Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Simple routing
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($query ?? '', $params);

// API routes
if (strpos($path, '/api/') === 0) {
    require __DIR__ . '/../Controllers/ApiController.php';
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

switch ($action) {
    case 'scan':
        require __DIR__ . '/../Controllers/ScannerController.php';
        $controller = new \App\Controllers\ScannerController();
        $controller->scan();
        break;

    case 'results':
        require __DIR__ . '/../Controllers/ScannerController.php';
        $controller = new \App\Controllers\ScannerController();
        $controller->results();
        break;

    case 'details':
        require __DIR__ . '/../Controllers/ScannerController.php';
        $controller = new \App\Controllers\ScannerController();
        $controller->details();
        break;

    case 'save':
        require __DIR__ . '/../Controllers/ScannerController.php';
        $controller = new \App\Controllers\ScannerController();
        $controller->save();
        break;

    case 'operations':
        require __DIR__ . '/../Controllers/OperationController.php';
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
        require __DIR__ . '/../Controllers/DashboardController.php';
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