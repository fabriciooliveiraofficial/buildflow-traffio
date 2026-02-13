<?php
/**
 * Debug endpoint to check auth status
 * DELETE THIS FILE AFTER DEBUGGING!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Define paths
define('BASE_PATH', __DIR__);
define('ROOT_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('CONFIG_PATH', __DIR__ . '/config');
define('VIEWS_PATH', __DIR__ . '/views');
define('ROUTES_PATH', __DIR__ . '/routes');
define('STORAGE_PATH', __DIR__ . '/storage');
define('PUBLIC_PATH', __DIR__);

// Load .env
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Define JWT constants
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'change-this-secret');
define('JWT_EXPIRY', 3600);

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0)
        return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file))
        require $file;
});

use App\Core\Auth;

try {
    // Get token from Authorization header
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = $header ?: ($headers['Authorization'] ?? $headers['authorization'] ?? '');
    }

    $token = null;
    if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
        $token = $matches[1];
    }

    if (!$token) {
        echo json_encode(['error' => 'No token provided']);
        exit;
    }

    // Validate token
    $auth = new Auth();
    $user = $auth->validateToken($token);

    if (!$user) {
        echo json_encode(['error' => 'Token validation failed - user is null']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'user' => $user,
        'has_role' => $user['role'] ?? 'MISSING',
        'has_role_name' => $user['role_name'] ?? 'MISSING',
        'is_developer' => $user['is_developer'] ?? false,
        'auth_php_updated' => true
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
