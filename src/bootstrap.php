<?php
/**
 * Bootstrap file for the application
 */

// Define application base path
define('APP_BASE_PATH', dirname(__DIR__));

// Load Composer autoload
require_once APP_BASE_PATH . '/vendor/autoload.php';

// Load environment
$envFile = APP_BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(APP_BASE_PATH);
    $dotenv->load();
} else {
    // Set default development environment
    $_ENV['APP_ENV'] = 'development';
    $_ENV['APP_DEBUG'] = true;
    $_ENV['DB_HOST'] = 'db';
    $_ENV['DB_NAME'] = 'straddle_scanner';
    $_ENV['DB_USER'] = 'straddle_user';
    $_ENV['DB_PASS'] = 'straddle_pass';
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

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>