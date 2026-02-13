<?php
/**
 * Construction ERP - SIMPLE Entry Point
 * Test version to identify the exact issue
 */

// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Construction ERP - Testing</h1>";
echo "<p>PHP is working!</p>";

// Define paths
define('BASE_PATH', __DIR__);
echo "<p>Base Path: " . BASE_PATH . "</p>";

// Check folders exist
$folders = ['app', 'config', 'routes', 'views', 'vendor', 'storage'];
echo "<h2>Folder Check:</h2><ul>";
foreach ($folders as $folder) {
    $path = BASE_PATH . '/' . $folder;
    $exists = is_dir($path) ? '✅ EXISTS' : '❌ MISSING';
    echo "<li>$folder: $exists</li>";
}
echo "</ul>";

// Check critical files
echo "<h2>File Check:</h2><ul>";
$files = [
    '.env' => BASE_PATH . '/.env',
    'app/Core/Application.php' => BASE_PATH . '/app/Core/Application.php',
    'app/Core/Database.php' => BASE_PATH . '/app/Core/Database.php',
    'app/Core/Router.php' => BASE_PATH . '/app/Core/Router.php',
    'routes/web.php' => BASE_PATH . '/routes/web.php',
    'config/database.php' => BASE_PATH . '/config/database.php',
];
foreach ($files as $name => $path) {
    $exists = file_exists($path) ? '✅ EXISTS' : '❌ MISSING';
    echo "<li>$name: $exists</li>";
}
echo "</ul>";

// Check .env content
if (file_exists(BASE_PATH . '/.env')) {
    echo "<h2>.env File Found</h2>";
    echo "<p>Database configured: ";
    $content = file_get_contents(BASE_PATH . '/.env');
    if (strpos($content, 'DB_DATABASE') !== false) {
        echo "✅ Yes</p>";
    } else {
        echo "❌ No - please configure database</p>";
    }
}

// Try to test database connection
echo "<h2>Database Test:</h2>";
try {
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
            }
        }
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $db = getenv('DB_DATABASE') ?: '';
    $user = getenv('DB_USERNAME') ?: '';
    $pass = getenv('DB_PASSWORD') ?: '';

    echo "<p>Host: $host</p>";
    echo "<p>Database: $db</p>";
    echo "<p>User: $user</p>";

    if (empty($db) || empty($user)) {
        echo "<p>❌ Database credentials not configured in .env</p>";
    } else {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        echo "<p>✅ Database connection successful!</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><strong>If you see this page, PHP is working!</strong></p>";
echo "<p>Replace this with the full index.php once all checks pass.</p>";
