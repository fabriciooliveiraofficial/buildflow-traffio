<?php
/**
 * Production Entry Point for Hostinger
 * 
 * This file should be placed in public_html root on Hostinger.
 * Adjust BASE_PATH according to your directory structure.
 */

// Define base path - adjust this based on your Hostinger setup
// Option 1: If app files are in same directory as public_html
define('BASE_PATH', dirname(__DIR__));

// Option 2: If app files are inside public_html
// define('BASE_PATH', __DIR__);

// Error handling for production
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Define paths
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('VIEWS_PATH', BASE_PATH . '/views');
define('ROUTES_PATH', BASE_PATH . '/routes');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Load environment variables from .env file
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

// Load autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load configuration
$config = require CONFIG_PATH . '/app.php';

// Set timezone
date_default_timezone_set($config['timezone'] ?? 'UTC');

// Initialize and run application
try {
    $app = \App\Core\Application::getInstance();

    // Load routes
    require ROUTES_PATH . '/web.php';
    require ROUTES_PATH . '/api.php';

    // Handle request
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];

    $response = $app->handle($method, $uri);

    if (is_array($response)) {
        header('Content-Type: application/json');
        echo json_encode($response);
    } elseif (is_string($response)) {
        echo $response;
    }

} catch (\Exception $e) {
    // Log error
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    // Return appropriate response
    $isApi = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;

    if ($isApi) {
        http_response_code($e->getCode() ?: 500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => getenv('APP_DEBUG') === 'true' ? $e->getMessage() : 'Internal Server Error'
        ]);
    } else {
        http_response_code($e->getCode() ?: 500);
        include VIEWS_PATH . '/errors/500.php';
    }
}
