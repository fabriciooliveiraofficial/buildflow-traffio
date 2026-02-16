<?php
require 'index.php'; // Load autoloader and config

use App\Core\Database;

try {
    $db = Database::getInstance();
    echo "=== Users Check ===\n";
    $users = $db->fetchAll("SELECT id, email, role_id, tenant_id, status FROM users LIMIT 10");
    print_r($users);

    echo "\n=== Tenants Check ===\n";
    $tenants = $db->fetchAll("SELECT id, name, subdomain, status FROM tenants LIMIT 10");
    print_r($tenants);

    echo "\n=== Database Connectivity ===\n";
    echo "Connected successfully\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
