<?php
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', __DIR__ . '/config');

// Load .env logic from index.php
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USERNAME');
$db_pass = getenv('DB_PASSWORD');
$db_name = getenv('DB_DATABASE');

echo "Attempting to connect to $db_host as $db_user...\n";

$start = microtime(true);
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5 // 5 second timeout
    ]);
    echo "SUCCESS: Connected in " . round(microtime(true) - $start, 2) . "s\n";
} catch (Exception $e) {
    echo "ERROR after " . round(microtime(true) - $start, 2) . "s: " . $e->getMessage() . "\n";
}
