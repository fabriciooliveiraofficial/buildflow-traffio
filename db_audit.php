<?php
/**
 * Database Audit Tool - Users & Tenants
 * 
 * Use this script to identify existing records on the server 
 * to coordinate synchronization with the local environment.
 */

// Basic setup to access App Core
define('BASE_PATH', __DIR__);
define('ROOT_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('CONFIG_PATH', __DIR__ . '/config');

// Manually load .env if available, otherwise rely on system env
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . "=" . trim($value, " \t\n\r\0\x0B\"'"));
        }
    }
}

// Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) require $file;
    }
});

use App\Core\Database;

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = Database::getInstance();
    
    echo "====================================================\n";
    echo "BUILD FLOW ERP - DATABASE AUDIT REPORT\n";
    echo "Generated at: " . date('Y-m-d H:i:s') . "\n";
    echo "====================================================\n\n";

    echo "--- TENANTS ---\n";
    $tenants = $db->fetchAll("SELECT id, name, subdomain, status, created_at FROM tenants ORDER BY id ASC");
    if (empty($tenants)) {
        echo "No tenants found.\n";
    } else {
        foreach ($tenants as $t) {
            echo "[ID: {$t['id']}] {$t['name']} ({$t['subdomain']}) - Status: {$t['status']} (Created: {$t['created_at']})\n";
        }
    }

    echo "\n--- USERS ---\n";
    $users = $db->fetchAll("
        SELECT u.id, u.email, u.first_name, u.last_name, u.role_id, t.subdomain as tenant, u.status 
        FROM users u 
        LEFT JOIN tenants t ON u.tenant_id = t.id 
        ORDER BY t.subdomain, u.email ASC
    ");
    
    if (empty($users)) {
        echo "No users found.\n";
    } else {
        foreach ($users as $u) {
            echo "[ID: {$u['id']}] {$u['first_name']} {$u['last_name']} <{$u['email']}> | Tenant: {$u['tenant']} | Status: {$u['status']}\n";
        }
    }

    echo "\n--- SYSTEM ROLES ---\n";
    $roles = $db->fetchAll("SELECT id, name, display_name FROM roles WHERE tenant_id IS NULL");
    foreach ($roles as $r) {
        echo "[ID: {$r['id']}] {$r['display_name']} ({$r['name']})\n";
    }

    echo "\n====================================================\n";
    echo "Audit Complete.\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
