<?php
// setup_finance.php

// Define paths
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');

// Load .env manualy since we don't have the full app bootstrapper here
if (file_exists(BASE_PATH . '/.env')) {
    $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
        $_ENV[trim($name)] = trim($value);
    }
}

// Autoload (manual if composer not used, or just require files)
require_once APP_PATH . '/Core/Database.php';
require_once APP_PATH . '/Core/Tenant.php';
require_once APP_PATH . '/Core/Finance/GeneralLedger.php';
require_once APP_PATH . '/Models/Finance/Account.php';

use App\Core\Database;
use App\Models\Finance\Account;
use App\Core\Tenant;
use App\Core\Finance\GeneralLedger;

header('Content-Type: text/plain');

echo "========================================\n";
echo "FINANCIAL MODULE SETUP & VERIFICATION\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance();

    // 1. Run SQL Migration
    echo "[1/3] Running Database Migration...\n";
    $sql = file_get_contents(BASE_PATH . '/database/migrations/001_finance_core.sql');

    // Split by semicolon to run multiple queries
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $db->query($stmt);
        }
    }
    echo "✓ Tables created successfully.\n\n";

    // 2. Seed Chart of Accounts
    // We need a context (Tenant). For this script, we'll pick the first tenant found.
    $tenant = $db->fetch("SELECT id, name, subdomain FROM tenants LIMIT 1");
    if (!$tenant) {
        die("Error: No tenants found. Please create a tenant first.\n");
    }

    // Initialize Tenant Class properly
    Tenant::setCurrentById((int) $tenant['id']);

    echo "[2/3] Seeding Chart of Accounts for Tenant: {$tenant['name']} (ID: {$tenant['id']})...\n";

    // Instantiate Account model
    $accountModel = new Account();
    $accountModel->seedDefaults();

    $accounts = $accountModel->getAll();
    echo "✓ Seeded " . count($accounts) . " accounts.\n";

    if (empty($accounts)) {
        throw new Exception("No accounts were created. Seeding failed.");
    }

    echo "  - Sample: " . $accounts[0]['code'] . " - " . $accounts[0]['name'] . "\n\n";

    // 3. Test General Ledger
    echo "[3/3] Testing General Ledger...\n";

    $gl = new GeneralLedger();

    // Find generic accounts
    $cash = $accounts[0] ?? null; // Cash
    $equity = end($accounts) ?: null; // Owner Equity

    if (!$cash || !$equity) {
        throw new Exception("Could not find Cash or Equity accounts for testing.");
    }

    echo "  - Attempting to post Initial Investment entry...\n";

    // Check if JE already exists to prevent duplicates on re-run
    $exists = $db->fetch("SELECT id FROM journal_entries WHERE reference_number LIKE 'JE-TEST-%' LIMIT 1");

    if (!$exists) {
        $entryId = $gl->postEntry(
            date('Y-m-d'),
            'Initial System Test Investment',
            [
                [
                    'account_id' => $cash['id'],
                    'type' => 'debit',
                    'amount' => 1000.00,
                    'description' => 'Cash Injection'
                ],
                [
                    'account_id' => $equity['id'],
                    'type' => 'credit',
                    'amount' => 1000.00,
                    'description' => 'Owner Investment'
                ]
            ],
            'manual',
            null
        );
        echo "✓ Entry Posted! ID: $entryId\n\n";
    } else {
        echo "✓ Test entry already exists. Skipping.\n\n";
    }

    echo "========================================\n";
    echo "SETUP COMPLETE - SUCCESS\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
}
