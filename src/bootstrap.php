<?php
/**
 * Bootstrap file for the application
 */

// Define application base path
define('APP_BASE_PATH', __DIR__);

// Load Composer autoload
require_once APP_BASE_PATH . '/vendor/autoload.php';

// Autoload classes (PSR-4 manual implementation for App\ namespace)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_BASE_PATH . '/';

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

// Load environment
$envFile = dirname(APP_BASE_PATH) . '/.env';
if (!file_exists($envFile)) {
    // Check if it's in /var/www/ (Docker common structure)
    $envFile = '/var/www/.env';
}

if (file_exists($envFile)) {
    if (class_exists('Dotenv\Dotenv')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(dirname($envFile));
            $dotenv->load();
        } catch (\Exception $e) {
            loadEnvFallback($envFile);
        }
    } else {
        loadEnvFallback($envFile);
    }
}

/**
 * Robust manual .env parser fallback
 */
function loadEnvFallback($file) {
    if (!file_exists($file)) return;
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^"(.+)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.+)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            if (!isset($_SERVER[$name]) && !isset($_ENV[$name])) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Ensure default values if not set by .env
$_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?? 'development';
$_ENV['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? true;
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'db';
$_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'straddle_scanner';
$_ENV['DB_USER'] = $_ENV['DB_USER'] ?? 'straddle_user';
$_ENV['DB_PASS'] = $_ENV['DB_PASS'] ?? 'straddle_pass';

// Load helper functions
if (file_exists(APP_BASE_PATH . '/helpers.php')) {
    require_once APP_BASE_PATH . '/helpers.php';
}

// Error reporting based on environment
if ($_ENV['APP_ENV'] === 'development' || ($_ENV['APP_DEBUG'] ?? false)) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Start session if not already started and not in CLI
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>