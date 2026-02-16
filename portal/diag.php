<?php
header('Content-Type: text/plain');
echo "=== BUILD FLOW DIAGNOSTIC ===\n\n";

echo "TIME: " . date('Y-m-d H:i:s') . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";
echo "SERVER_NAME: " . $_SERVER['SERVER_NAME'] . "\n";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'OFF') . "\n";
echo "REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . "\n";

echo "\n--- ENVIRONMENT ---\n";
echo "APP_URL (getenv): " . getenv('APP_URL') . "\n";
echo "APP_ENV (getenv): " . getenv('APP_ENV') . "\n";

echo "\n--- REQUEST HEADERS ---\n";
foreach (getallheaders() as $name => $value) {
    echo "$name: $value\n";
}

echo "\n--- DATABASE CHECK ---\n";
try {
    $dsn = "mysql:host=" . env('DB_HOST', 'db') . ";dbname=" . env('DB_DATABASE', 'traffio_erp');
    $pdo = new PDO($dsn, env('DB_USERNAME', 'traffio'), env('DB_PASSWORD', 'secure_pass'));
    echo "DB Connection: SUCCESS\n";
} catch (Exception $e) {
    echo "DB Connection: FAILED (" . $e->getMessage() . ")\n";
}

function env($key, $default) {
    $val = getenv($key);
    return $val !== false ? $val : $default;
}
?>
