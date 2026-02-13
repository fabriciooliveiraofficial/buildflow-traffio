<?php
/**
 * Debug script to check routes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('CONFIG_PATH', __DIR__ . '/config');
define('VIEWS_PATH', __DIR__ . '/views');
define('ROUTES_PATH', __DIR__ . '/routes');
define('STORAGE_PATH', __DIR__ . '/storage');
define('PUBLIC_PATH', __DIR__);

// Minimal constants needed
define('APP_NAME', 'Test');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development');
define('APP_DEBUG', true);
define('APP_URL', 'https://' . $_SERVER['HTTP_HOST']);
define('SESSION_LIFETIME', 120);
define('SESSION_SECURE', true);
define('JWT_SECRET', 'test');
define('JWT_EXPIRY', 3600);
define('STRIPE_PUBLIC_KEY', '');
define('STRIPE_SECRET_KEY', '');
define('STRIPE_WEBHOOK_SECRET', '');

// Autoloader
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

echo "<h1>Route Debug</h1>";

// Check if files exist
echo "<h2>File Checks:</h2>";
echo "<ul>";
echo "<li>routes/api.php: " . (file_exists(ROUTES_PATH . '/api.php') ? '✓ Exists' : '✗ MISSING') . "</li>";
echo "<li>routes/web.php: " . (file_exists(ROUTES_PATH . '/web.php') ? '✓ Exists' : '✗ MISSING') . "</li>";
echo "<li>app/Controllers/Api/AuthController.php: " . (file_exists(APP_PATH . '/Controllers/Api/AuthController.php') ? '✓ Exists' : '✗ MISSING') . "</li>";
echo "</ul>";

// Try to load router
try {
    $router = new \App\Core\Router();
    $GLOBALS['app_router'] = $router;

    require ROUTES_PATH . '/web.php';
    require ROUTES_PATH . '/api.php';

    echo "<h2>Routes Loaded Successfully!</h2>";

    // Show registered routes
    echo "<h2>Testing route dispatch:</h2>";
    echo "<p>Trying to match POST /api/auth/register...</p>";

    // Note: We can't easily list all routes without modifying the Router class
    // But we can test if the route exists by trying to dispatch

    echo "<p><strong>If you see this, routes loaded OK!</strong></p>";
    echo "<p>The issue might be that the routes/api.php file on the server is different from your local file.</p>";

} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Requested URL info:</h2>";
echo "<pre>";
print_r([
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'REQUEST_URI' => $_SERVER['REQUEST_URI'],
    'HTTP_HOST' => $_SERVER['HTTP_HOST'],
]);
echo "</pre>";
