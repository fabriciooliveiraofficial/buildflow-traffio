<?php
// Mock server variables for CLI
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_HOST'] = 'localhost';

define('BASE_PATH', __DIR__);
define('ROOT_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('CONFIG_PATH', __DIR__ . '/config');

// Load .env
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

// Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) require $file;
    }
});

use App\Core\Database;

try {
    $db = Database::getInstance();
    echo "=== Users Check ===\n";
    $users = $db->fetchAll("SELECT id, email, role_id, tenant_id, status FROM users LIMIT 10");
    foreach ($users as $u) {
        echo "ID: {$u['id']} | Email: {$u['email']} | Status: {$u['status']}\n";
    }

    echo "\n=== Tenants Check ===\n";
    $tenants = $db->fetchAll("SELECT id, name, subdomain, status FROM tenants LIMIT 10");
    foreach ($tenants as $t) {
        echo "ID: {$t['id']} | Name: {$t['name']} | Subdomain: {$t['subdomain']} | Status: {$t['status']}\n";
    }

    echo "\n=== Database Connectivity ===\n";
    echo "Connected successfully\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
