<?php
/**
 * Standalone Debug File - No routing involved
 */
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PaymentController Debug ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$appPath = __DIR__ . '/app';
echo "App Path: $appPath\n";

$file = $appPath . '/Controllers/PaymentController.php';
echo "PaymentController Path: $file\n";
echo "File Exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n\n";

if (file_exists($file)) {
    echo "File Contents (first 800 chars):\n";
    echo "---\n";
    echo substr(file_get_contents($file), 0, 800);
    echo "\n---\n\n";

    // Try to include it
    echo "Attempting to include file...\n";
    try {
        require_once $file;
        echo "Include successful!\n";

        $className = 'App\\Controllers\\PaymentController';
        echo "Class exists: " . (class_exists($className) ? 'YES' : 'NO') . "\n";
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    }
} else {
    echo "FILE NOT FOUND!\n\n";
    echo "Controllers Directory Contents:\n";
    $dir = $appPath . '/Controllers/';
    if (is_dir($dir)) {
        foreach (scandir($dir) as $f) {
            if ($f !== '.' && $f !== '..') {
                echo "  - $f\n";
            }
        }
    } else {
        echo "  Directory doesn't exist!\n";
    }
}
