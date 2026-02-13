<?php
/**
 * Web Routes
 * 
 * Routes for web pages (views).
 * 
 * URL Structure:
 * - Public routes: /, /login, /register
 * - Tenant routes: /t/{tenant}/dashboard, /t/{tenant}/projects, etc.
 */

// Use global router from index.php
$router = $GLOBALS['app_router'];

// =====================================================
// Public Web Routes (no tenant required)
// =====================================================

$router->get('/', function () {
    return require VIEWS_PATH . '/welcome.php';
});

// PWA Manifest (server blocks direct JSON access)
$router->get('/manifest.json', function () {
    $manifestFile = ROOT_PATH . '/manifest.json';
    if (file_exists($manifestFile)) {
        header('Content-Type: application/manifest+json');
        header('Cache-Control: public, max-age=86400');
        echo file_get_contents($manifestFile);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Manifest not found']);
    }
    exit;
});

// Service Worker (must be at root scope)
$router->get('/sw.js', function () {
    $swFile = ROOT_PATH . '/sw.js';
    if (file_exists($swFile)) {
        header('Content-Type: application/javascript');
        header('Cache-Control: no-cache');
        header('Service-Worker-Allowed: /');
        echo file_get_contents($swFile);
    } else {
        http_response_code(404);
        echo '// Service worker not found';
    }
    exit;
});

// Offline page for PWA
$router->get('/offline.html', function () {
    $offlineFile = ROOT_PATH . '/offline.html';
    if (file_exists($offlineFile)) {
        header('Content-Type: text/html');
        echo file_get_contents($offlineFile);
    } else {
        http_response_code(404);
        echo '<h1>Offline</h1><p>You are currently offline.</p>';
    }
    exit;
});

$router->get('/login', function () {
    return require VIEWS_PATH . '/auth/login.php';
});

$router->get('/register', function () {
    return require VIEWS_PATH . '/auth/register.php';
});

// Checkout success/cancel pages
$router->get('/checkout/success', function () {
    return require VIEWS_PATH . '/auth/checkout-success.php';
});

$router->get('/checkout/cancel', function () {
    // Redirect back to homepage with message
    header('Location: /?checkout=cancelled');
    exit;
});

// Email Tracking (Public - No Auth)
$router->get('/email/track/open/{token}', 'Api\\EmailTrackingController@trackOpen');
$router->get('/email/track/click/{token}', 'Api\\EmailTrackingController@trackClick');

// Email Unsubscribe (Public)
$router->get('/email/unsubscribe/{token}', function ($token) {
    require_once APP_PATH . '/Core/Database.php';
    $db = new \App\Core\Database();
    $service = new \App\Services\Email\UnsubscribeService($db);
    $result = $service->processUnsubscribe($token);
    include VIEWS_PATH . '/email/unsubscribe-confirm.php';
    exit;
});

// Email Bounce Webhook (Public)
$router->post('/email/webhook/bounce', 'Api\\EmailAdvancedController@bounceWebhook');

// Pricing page (alias to homepage pricing section)
$router->get('/pricing', function () {
    header('Location: /#pricing');
    exit;
});

// =====================================================
// Developer Support Console (separate from tenant ERP)
// =====================================================

// Developer Panel Root - redirect to login or support
$router->get('/dev', function () {
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    if (isset($_SESSION['dev_user'])) {
        header('Location: /dev/support');
    } else {
        header('Location: /dev/login');
    }
    exit;
});

// Developer Login
$router->get('/dev/login', function () {
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    // If already logged in as developer, redirect to console
    if (isset($_SESSION['dev_user'])) {
        header('Location: /dev/support');
        exit;
    }
    return require VIEWS_PATH . '/dev/login.php';
});

$router->post('/dev/login', function () {
    if (session_status() === PHP_SESSION_NONE)
        session_start();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $GLOBALS['login_error'] = 'Email and password are required';
        return require VIEWS_PATH . '/dev/login.php';
    }

    try {
        $db = \App\Core\Database::getInstance();

        // Find developer by email (separate from main users)
        $developer = $db->fetch(
            "SELECT * FROM developers WHERE email = ? AND status = 'active'",
            [$email]
        );

        if (!$developer || !password_verify($password, $developer['password'])) {
            $GLOBALS['login_error'] = 'Invalid email or password';
            return require VIEWS_PATH . '/dev/login.php';
        }

        // Update last login
        $db->query("UPDATE developers SET last_login_at = NOW() WHERE id = ?", [$developer['id']]);

        // Find any tenant to use for API calls (developers can access all tenants)
        $tenant = $db->fetch("SELECT id FROM tenants LIMIT 1");
        $tenantId = $tenant ? $tenant['id'] : 1;

        // Set developer session
        $_SESSION['dev_user'] = [
            'id' => $developer['id'],
            'email' => $developer['email'],
            'name' => $developer['name'],
            'role' => $developer['role']
        ];

        // Generate JWT for API access
        // Use a high ID offset (9999000+) to avoid collision with real user IDs
        $devUserId = 9999000 + $developer['id'];
        $auth = new \App\Core\Auth();
        $jwt = $auth->generateToken([
            'id' => $devUserId,
            'tenant_id' => $tenantId,
            'role' => 'super_admin', // Developers have full access
            'email' => $developer['email'],
            'is_dev_token' => true   // Mark as developer token
        ]);
        $_SESSION['dev_api_token'] = $jwt;
        error_log("Dev login: Generated token for developer " . $developer['email'] . " with ID offset " . $devUserId);

        header('Location: /dev/support');
        exit;

    } catch (\Exception $e) {
        error_log("Dev login error: " . $e->getMessage());
        $GLOBALS['login_error'] = 'Login failed. Please try again.';
        return require VIEWS_PATH . '/dev/login.php';
    }
});

$router->get('/dev/logout', function () {
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    unset($_SESSION['dev_user']);
    setcookie('dev_token', '', time() - 3600, '/');
    header('Location: /dev/login');
    exit;
});

$router->get('/dev/support', function () {
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    if (!isset($_SESSION['dev_user'])) {
        header('Location: /dev/login');
        exit;
    }
    return require VIEWS_PATH . '/dev/support/index.php';
});

$router->get('/dev/support/{id}', function ($id) {
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    if (!isset($_SESSION['dev_user'])) {
        header('Location: /dev/login');
        exit;
    }
    $GLOBALS['params'] = ['id' => $id];
    return require VIEWS_PATH . '/dev/support/show.php';
});

// Admin Subscriptions Dashboard
$router->get('/dev/subscriptions', function () {
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    if (!isset($_SESSION['dev_user'])) {
        header('Location: /dev/login');
        exit;
    }
    return require VIEWS_PATH . '/dev/subscriptions/index.php';
});

// Admin Releases Dashboard
$router->get('/dev/releases', function () {
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    if (!isset($_SESSION['dev_user'])) {
        header('Location: /dev/login');
        exit;
    }
    return require VIEWS_PATH . '/dev/releases/index.php';
});

// =====================================================
// Tenant-Scoped Web Routes
// Format: /t/{tenant}/...
// =====================================================

// Dashboard
$router->get('/t/{tenant}/dashboard', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/dashboard.php';
});

// Projects
$router->get('/t/{tenant}/projects', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/projects/index.php';
});

$router->get('/t/{tenant}/projects/{id}', function ($tenant, $id) {
    $GLOBALS['tenant_slug'] = $tenant;
    $GLOBALS['params'] = ['id' => $id];
    return require VIEWS_PATH . '/projects/show.php';
});

// Clients
$router->get('/t/{tenant}/clients', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/clients/index.php';
});

// Chart of Accounts
$router->get('/t/{tenant}/accounts', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/finance/accounts.php';
});

// Journal Entries
$router->get('/t/{tenant}/journal-entries', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/finance/journal-entries.php';
});

// Financial Reports
$router->get('/t/{tenant}/financial-reports', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/finance/reports.php';
});

$router->get('/t/{tenant}/clients/{id}', function ($tenant, $id) {
    $GLOBALS['tenant_slug'] = $tenant;
    $GLOBALS['params'] = ['id' => $id];
    return require VIEWS_PATH . '/clients/show.php';
});

// Time Tracking
$router->get('/t/{tenant}/time-tracking', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/time-tracking/index.php';
});

// Time Clock (Mobile-friendly)
$router->get('/t/{tenant}/time-clock', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/time-clock.php';
});

// Payroll
$router->get('/t/{tenant}/payroll', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/payroll/index.php';
});

$router->get('/t/{tenant}/payroll/{id}', function ($tenant, $id) {
    $GLOBALS['tenant_slug'] = $tenant;
    $GLOBALS['params'] = ['id' => $id];
    return require VIEWS_PATH . '/payroll/show.php';
});

// Employees
$router->get('/t/{tenant}/employees', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/employees/index.php';
});

$router->get('/t/{tenant}/employees/{id}', function ($tenant, $id) {
    $GLOBALS['tenant_slug'] = $tenant;
    $GLOBALS['params'] = ['id' => $id];
    return require VIEWS_PATH . '/employees/show.php';
});

// Inventory
$router->get('/t/{tenant}/inventory', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/inventory/index.php';
});

// Invoices
$router->get('/t/{tenant}/invoices', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/invoices/index.php';
});

// Expenses
$router->get('/t/{tenant}/expenses', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/expenses/index.php';
});

$router->get('/t/{tenant}/invoices/{id}', function ($tenant, $id) {
    $GLOBALS['tenant_slug'] = $tenant;
    $GLOBALS['params'] = ['id' => $id];
    return require VIEWS_PATH . '/invoices/show.php';
});

// Estimates
$router->get('/t/{tenant}/estimates', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/estimates/index.php';
});

$router->get('/t/{tenant}/estimates/{id}', function ($tenant, $id) {
    $GLOBALS['tenant_slug'] = $tenant;
    $GLOBALS['params'] = ['id' => $id];
    return require VIEWS_PATH . '/estimates/show.php';
});

// Purchase Orders
$router->get('/t/{tenant}/purchase-orders', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/purchase-orders/index.php';
});

$router->get('/t/{tenant}/purchase-orders/{id}', function ($tenant, $id) {
    $GLOBALS['tenant_slug'] = $tenant;
    $GLOBALS['params'] = ['id' => $id];
    return require VIEWS_PATH . '/purchase-orders/show.php';
});

// Vendors
$router->get('/t/{tenant}/vendors', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/vendors/index.php';
});

// Equipment
$router->get('/t/{tenant}/equipment', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/equipment/index.php';
});

// Tasks (Standalone)
$router->get('/t/{tenant}/tasks', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/tasks/index.php';
});

// Documents
$router->get('/t/{tenant}/documents', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/documents/index.php';
});

// Scheduling
$router->get('/t/{tenant}/scheduling', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/scheduling/index.php';
});

// Reports
$router->get('/t/{tenant}/reports', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/reports/index.php';
});

// Settings
$router->get('/t/{tenant}/settings', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/settings/index.php';
});

// Subscription Settings
$router->get('/t/{tenant}/settings/subscription', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/settings/subscription.php';
});

// Email Settings
$router->get('/t/{tenant}/settings/email', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/settings/email.php';
});

// Email Templates
$router->get('/t/{tenant}/settings/email-templates', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/settings/email-templates.php';
});

// Email Logs
$router->get('/t/{tenant}/email/logs', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/email/logs.php';
});

// Compose Email
$router->get('/t/{tenant}/email/compose', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/email/compose.php';
});

// Email Automations
$router->get('/t/{tenant}/settings/email-automations', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/settings/email-automations.php';
});

// Email Analytics
$router->get('/t/{tenant}/email/analytics', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/email/analytics.php';
});

// Email Signature Builder
$router->get('/t/{tenant}/settings/email-signature', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/settings/email-signature.php';
});

// Support Portal
$router->get('/t/{tenant}/support', function ($tenant) {
    $GLOBALS['tenant_slug'] = $tenant;
    return require VIEWS_PATH . '/support/index.php';
});

$router->get('/t/{tenant}/support/{id}', function ($tenant, $id) {
    $GLOBALS['tenant_slug'] = $tenant;
    $GLOBALS['params'] = ['id' => $id];
    return require VIEWS_PATH . '/support/show.php';
});

// =====================================================
// Public Invitation Routes (no auth required)
// =====================================================

// Accept Invitation
$router->get('/invite/{token}', function ($token) {
    $GLOBALS['invitation_token'] = $token;
    return require VIEWS_PATH . '/invitations/accept.php';
});
