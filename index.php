<?php
/**
 * Construction ERP - Entry Point for Hostinger
 * WORKING VERSION - All checks passed
 */

// EMERGENCY DEBUG - Check PaymentController
if (isset($_GET['debug_payment']) && $_GET['debug_payment'] === 'check') {
    header('Content-Type: text/plain');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    echo "=== PaymentController Debug ===\n\n";

    $appPath = __DIR__ . '/app';
    echo "App Path: $appPath\n";

    $file = $appPath . '/Controllers/PaymentController.php';
    echo "PaymentController Path: $file\n";
    echo "File Exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n\n";

    if (file_exists($file)) {
        echo "File Contents (first 600 chars):\n";
        echo "---\n";
        echo substr(file_get_contents($file), 0, 600);
        echo "\n---\n";
    } else {
        echo "Controllers Directory Contents:\n";
        $dir = $appPath . '/Controllers/';
        if (is_dir($dir)) {
            foreach (scandir($dir) as $f) {
                if ($f !== '.' && $f !== '..')
                    echo "  - $f\n";
            }
        } else {
            echo "  Directory doesn't exist!\n";
        }
    }
    exit;
}

// Show errors during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths FIRST
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

// Define app constants
define('APP_NAME', getenv('APP_NAME') ?: 'Construction ERP');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', true); // TEMPORARILY FORCED TO TRUE FOR DEBUGGING
define('APP_URL', getenv('APP_URL') ?: 'https://' . $_SERVER['HTTP_HOST']);
define('SESSION_LIFETIME', 120);
define('SESSION_SECURE', true);
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'change-this-secret');
define('JWT_EXPIRY', 3600);
define('STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY') ?: '');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');

// Timezone
date_default_timezone_set('America/New_York');

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load helpers
$helpersPath = APP_PATH . '/Helpers/functions.php';
if (file_exists($helpersPath)) {
    require_once $helpersPath;
}

// Main execution
try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_lifetime' => SESSION_LIFETIME * 60,
            'cookie_secure' => SESSION_SECURE,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }

    // Initialize router
    $router = new \App\Core\Router();

    // Store router in a simple container for routes to access
    $GLOBALS['app_router'] = $router;

    // Load routes
    require ROUTES_PATH . '/web.php';
    require ROUTES_PATH . '/api.php';

    // Dispatch request
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];

    $response = $router->dispatch($method, $uri);

    // Output response
    if (is_array($response) || is_object($response)) {
        header('Content-Type: application/json');
        echo json_encode($response);
    } elseif (is_string($response)) {
        echo $response;
    }

} catch (\Exception $e) {
    $code = (int) $e->getCode();
    // Ensure valid HTTP status code (100-599)
    if ($code < 100 || $code > 599) {
        $code = 500;
    }
    http_response_code($code);

    if (APP_DEBUG) {
        echo '<h1>Error ' . $code . '</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . $e->getFile() . ' (Line ' . $e->getLine() . ')</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'message' => 'An error occurred']);
    }
}
