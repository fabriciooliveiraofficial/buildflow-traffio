<?php
require_once __DIR__ . '/vendor/autoload.php';

$env = parse_ini_file('.env');
$host = $env['DB_HOST'];
$db   = $env['DB_DATABASE'];
$user = $env['DB_USERNAME'];
$pass = $env['DB_PASSWORD'];
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     $sql = "CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(50) NOT NULL,
        endpoint TEXT NOT NULL,
        p256dh TEXT NOT NULL,
        auth TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_employee (employee_id)
     ) ENGINE=InnoDB;";
     $pdo->exec($sql);
     echo "MIGRATION_SUCCESS";
} catch (PDOException $e) {
     echo "MIGRATION_ERROR: " . $e->getMessage();
}
