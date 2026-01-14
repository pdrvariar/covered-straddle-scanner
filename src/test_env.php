<?php
require_once __DIR__ . '/bootstrap.php';

echo "OPLAB_TOKEN: " . ($_ENV['OPLAB_TOKEN'] ?? 'Not set') . "\n";
echo "APP_ENV: " . ($_ENV['APP_ENV'] ?? 'Not set') . "\n";
