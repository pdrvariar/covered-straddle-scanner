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
                    'name' => env('APP_NAME', 'Covered Straddle Scanner'),
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
?>