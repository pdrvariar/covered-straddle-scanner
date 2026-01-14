<?php
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

echo "OPLAB_TOKEN: " . ($_ENV['OPLAB_TOKEN'] ?? 'Not set') . "\n";
