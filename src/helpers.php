<?php

if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    function config($key, $default = null) {
        static $config = null;

        if ($config === null) {
            $config = [
                'app' => [
                    'name' => env('APP_NAME', 'Options Strategy'),
                    'env' => env('APP_ENV', 'production'),
                    'url' => env('APP_URL', 'http://localhost'),
                    'debug' => env('APP_DEBUG', false)
                ],
                'database' => [
                    'host' => env('DB_HOST', 'localhost'),
                    'port' => env('DB_PORT', '3306'),
                    'database' => env('DB_NAME', 'straddle_scanner'),
                    'username' => env('DB_USER', 'straddle_user'),
                    'password' => env('DB_PASS', 'straddle_pass')
                ]
            ];
        }

        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        $baseUrl = rtrim(config('app.url'), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route($name, $params = []) {
        $routes = [
            'dashboard' => '/',
            'scanner' => '/?action=scan',
            'results' => '/?action=results',
            'api.scan' => '/api/scan'
        ];

        if (!isset($routes[$name])) {
            return '/';
        }

        $url = $routes[$name];

        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }

        return $url;
    }
}

if (!function_exists('dd')) {
    function dd(...$vars) {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die();
    }
}

if (!function_exists('format_currency')) {
    function format_currency($value, $currency = 'BRL') {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}

if (!function_exists('format_percentage')) {
    function format_percentage($value) {
        return number_format($value, 2, ',', '.') . '%';
    }
}

if (!function_exists('calculate_lots')) {
    function calculate_lots($unitCost, $totalCapital, $lotSize = 100) {
        if ($unitCost <= 0) {
            return 0;
        }

        $lots = floor($totalCapital / ($unitCost * $lotSize));
        return max(0, $lots);
    }
}

if (!function_exists('calculate_payoff')) {
    function calculate_payoff($currentPrice, $strike, $callPremium, $putPremium, $finalPrice, $quantity = 100) {
        $stockPayoff = ($finalPrice - $currentPrice) * $quantity;
        $callPayoff = ($callPremium - max($finalPrice - $strike, 0)) * $quantity;
        $putPayoff = ($putPremium - max($strike - $finalPrice, 0)) * $quantity;

        return $stockPayoff + $callPayoff + $putPayoff;
    }
}

/**
 * Adiciona uma notificação flash para ser exibida na próxima requisição
 */
function add_flash_notification(string $message, string $type = 'info'): void {
    if (!isset($_SESSION['flash_notifications'])) {
        $_SESSION['flash_notifications'] = [];
    }

    $_SESSION['flash_notifications'][] = [
        'message' => $message,
        'type' => $type,
        'timestamp' => time()
    ];
}

/**
 * Obtém todas as notificações flash e limpa a sessão
 */
function get_flash_notifications(): array {
    $notifications = $_SESSION['flash_notifications'] ?? [];
    unset($_SESSION['flash_notifications']);
    return $notifications;
}

/**
 * Renderiza as notificações flash como JavaScript
 */
function render_flash_notifications(): string {
    $notifications = get_flash_notifications();
    if (empty($notifications)) {
        return '';
    }

    $output = '<script>';
    foreach ($notifications as $notification) {
        $message = addslashes($notification['message']);
        $type = $notification['type'];
        $output .= "showNotification('{$message}', '{$type}');";
    }
    $output .= '</script>';

    return $output;
}

/**
 * Retorna uma resposta JSON com notificação
 */
function json_response(array $data = [], bool $success = true, string $message = ''): void {
    header('Content-Type: application/json');

    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if ($message && !isset($data['notification'])) {
        $response['notification'] = [
            'message' => $message,
            'type' => $success ? 'success' : 'error'
        ];
    }

    echo json_encode($response);
    exit;
}
?>