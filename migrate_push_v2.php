<?php
// Use framework's autoloader and config
require_once 'app/Core/Database.php';

// Mock a simple environment if needed or just use plain PDO with values from .env
$env = parse_ini_file('.env');

try {
    // In Docker, DB_HOST usually refers to the service name like 'db' or 'traffio_db'
    // But since the user updated .env for Hostinger, maybe it's trying to connect externally?
    // Actually, local Docker should connect to local MySQL if DB_HOST is traffio_db or localhost
    
    $dsn = "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_DATABASE'] . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
} catch (Exception $e) {
    echo "MIGRATION_ERROR: " . $e->getMessage();
}
