<?php
/**
 * API Routes
 * 
 * RESTful API endpoints.
 */

// IMMEDIATE DEBUG - runs when file loads
if (isset($_GET['debug_payment']) && $_GET['debug_payment'] === 'check') {
    header('Content-Type: text/plain');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    echo "=== Immediate Debug ===\n\n";
    echo "APP_PATH defined: " . (defined('APP_PATH') ? 'YES' : 'NO') . "\n";
    if (defined('APP_PATH')) {
        echo "APP_PATH value: " . APP_PATH . "\n";
        $file = APP_PATH . '/Controllers/PaymentController.php';
        echo "PaymentController path: $file\n";
        echo "File exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n";

        if (file_exists($file)) {
            echo "\nFile contents (first 500 chars):\n";
            echo substr(file_get_contents($file), 0, 500) . "\n...\n";
        } else {
            echo "\nControllers dir contents:\n";
            $dir = APP_PATH . '/Controllers/';
            foreach (scandir($dir) as $f) {
                if ($f !== '.' && $f !== '..')
                    echo "  $f\n";
            }
        }
    }
    exit;
}

// Use global router from index.php
$router = $GLOBALS['app_router'];

// DEBUG ROUTE (Global) - Self-contained, doesn't throw exceptions
$router->get('/debug-autoload', function () {
    try {
        header('Content-Type: text/plain');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        echo "=== Payment Controller Debug ===\n\n";

        // Check APP_PATH
        if (defined('APP_PATH')) {
            echo "APP_PATH: " . APP_PATH . "\n";
        } else {
            echo "ERROR: APP_PATH not defined!\n";
            exit;
        }

        $file = APP_PATH . '/Controllers/PaymentController.php';
        echo "Expected file: $file\n";
        echo "File exists: " . (file_exists($file) ? "YES" : "NO") . "\n\n";

        if (!file_exists($file)) {
            echo "CRITICAL: File does not exist at expected path!\n";
            // List what IS in Controllers folder
            $dir = APP_PATH . '/Controllers/';
            if (is_dir($dir)) {
                echo "Files in Controllers folder:\n";
                foreach (scandir($dir) as $f) {
                    if ($f !== '.' && $f !== '..')
                        echo "  - $f\n";
                }
            }
            exit;
        }

        // Try to load it
        echo "Attempting require_once...\n";
        require_once $file;
        echo "File loaded without errors.\n\n";

        $className = 'App\\Controllers\\PaymentController';
        echo "Checking class: $className\n";
        echo "Class exists: " . (class_exists($className) ? "YES" : "NO") . "\n\n";

        if (class_exists($className)) {
            echo "Attempting to instantiate...\n";
            $obj = new $className();
            echo "SUCCESS: Instantiated!\n";
        } else {
            echo "FAIL: Class not defined after loading file.\n";
            echo "Check that namespace in file matches: App\\Controllers\n";
        }

    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    }
    exit;
});

// =====================================================
// Public API Routes
// =====================================================

$router->group(['prefix' => 'api'], function ($router) {

    // Authentication
    $router->post('/auth/login', 'Api\\AuthController@login');
    $router->get('/auth/session', 'Api\\AuthController@session');
    $router->post('/auth/register', 'Api\\AuthController@register');
    $router->post('/auth/forgot-password', 'Api\\AuthController@forgotPassword');
    $router->post('/auth/reset-password', 'Api\\AuthController@resetPassword');
    $router->post('/auth/verify-2fa', 'Api\\AuthController@verify2FA');
    $router->post('/auth/setup-employee', 'Api\\AuthController@setupEmployee');

    // Stripe Webhook (public but verified by signature)
    $router->post('/webhooks/stripe', 'Api\\WebhookController@stripe');

    // Public invitation routes (no auth required)
    $router->get('/invitations/validate/{token}', 'Api\\InvitationController@validateToken');
    $router->post('/invitations/accept/{token}', 'Api\\InvitationController@accept');

    // Version endpoint for update system (public)
    $router->get('/version', function () {
        $versionFile = ROOT_PATH . '/version.json';
        if (file_exists($versionFile)) {
            $data = json_decode(file_get_contents($versionFile), true);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Version file not found']);
        }
        exit;
    });

    // Health check endpoint for PWA offline detection
    $router->get('/health', function () {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'timestamp' => time()
        ]);
        exit;
    });

});

$router->group(['prefix' => 'api', 'middleware' => ['AuthMiddleware', 'TenantMiddleware']], function ($router) {

    // User & Auth
    $router->get('/auth/me', 'Api\\AuthController@me');
    $router->post('/auth/logout', 'Api\\AuthController@logout');
    $router->post('/auth/refresh', 'Api\\AuthController@refresh');
    $router->put('/auth/profile', 'Api\\AuthController@updateProfile');
    $router->post('/auth/enable-2fa', 'Api\\AuthController@enable2FA');

    // Dashboard
    $router->get('/dashboard/stats', 'Api\\DashboardController@stats');
    $router->get('/dashboard/charts', 'Api\\DashboardController@charts');
    $router->get('/dashboard/activities', 'Api\\DashboardController@activities');
    $router->get('/dashboard/cash-flow', 'Api\\DashboardController@cashFlowForecast');
    $router->get('/dashboard/alerts', 'Api\\DashboardController@alerts');
    $router->get('/dashboard/schedule', 'Api\\DashboardController@todaySchedule');
    $router->get('/dashboard/profitability', 'Api\\DashboardController@profitability');

    // Clients
    $router->get('/clients', 'Api\\ClientController@index');
    $router->post('/clients', 'Api\\ClientController@store');
    $router->get('/clients/{id}', 'Api\\ClientController@show');
    $router->put('/clients/{id}', 'Api\\ClientController@update');
    $router->delete('/clients/{id}', 'Api\\ClientController@destroy');
    $router->get('/clients/{id}/projects', 'Api\\ClientController@projects');
    $router->get('/clients/{id}/financials', 'Api\\ClientController@financials');

    // Chart of Accounts
    $router->get('/accounts', 'Api\\AccountController@index');
    $router->post('/accounts', 'Api\\AccountController@store');
    $router->get('/accounts/{id}', 'Api\\AccountController@show');
    $router->put('/accounts/{id}', 'Api\\AccountController@update');
    $router->delete('/accounts/{id}', 'Api\\AccountController@destroy');
    $router->get('/accounts/{id}/balance', 'Api\\AccountController@balance');

    // Journal Entries
    $router->get('/journal-entries', 'Api\\JournalEntryController@index');
    $router->post('/journal-entries', 'Api\\JournalEntryController@store');
    $router->get('/journal-entries/{id}', 'Api\\JournalEntryController@show');
    $router->put('/journal-entries/{id}', 'Api\\JournalEntryController@update');
    $router->delete('/journal-entries/{id}', 'Api\\JournalEntryController@destroy');
    $router->post('/journal-entries/{id}/void', 'Api\\JournalEntryController@void');

    // Financial Reports
    $router->get('/reports/trial-balance', 'Api\\FinancialReportsController@trialBalance');
    $router->get('/reports/income-statement', 'Api\\FinancialReportsController@incomeStatement');
    $router->get('/reports/balance-sheet', 'Api\\FinancialReportsController@balanceSheet');

    // Projects
    $router->get('/projects', 'Api\\ProjectController@index');
    $router->post('/projects', 'Api\\ProjectController@store');
    $router->get('/projects/{id}', 'Api\\ProjectController@show');
    $router->put('/projects/{id}', 'Api\\ProjectController@update');
    $router->delete('/projects/{id}', 'Api\\ProjectController@destroy');
    $router->get('/projects/{id}/tasks', 'Api\\ProjectController@tasks');
    $router->get('/projects/{id}/budget', 'Api\\ProjectController@budget');
    $router->get('/projects/{id}/expenses', 'Api\\ProjectController@expenses');
    $router->get('/projects/{id}/time-logs', 'Api\\ProjectController@timeLogs');
    $router->get('/projects/{id}/documents', 'Api\\ProjectController@documents');
    $router->get('/projects/{id}/labor-cost', 'Api\\ProjectController@laborCost');
    $router->get('/projects/{id}/financials', 'Api\\ProjectController@financials');
    $router->get('/projects/{id}/ledger', 'Api\\ProjectController@ledger');
    $router->post('/projects/{id}/payments', 'PaymentController@storeProjectPayment');

    // Tasks
    $router->get('/tasks', 'Api\\TaskController@index');
    $router->post('/tasks', 'Api\\TaskController@store');
    $router->get('/tasks/{id}', 'Api\\TaskController@show');
    $router->put('/tasks/{id}', 'Api\\TaskController@update');
    $router->delete('/tasks/{id}', 'Api\\TaskController@destroy');
    $router->patch('/tasks/{id}/status', 'Api\\TaskController@updateStatus');

    // Time Tracking
    $router->get('/time-logs', 'Api\\TimeLogController@index');
    $router->post('/time-logs', 'Api\\TimeLogController@store');
    $router->get('/time-logs/{id}', 'Api\\TimeLogController@show');
    $router->put('/time-logs/{id}', 'Api\\TimeLogController@update');
    $router->delete('/time-logs/{id}', 'Api\\TimeLogController@destroy');
    $router->post('/time-logs/start-timer', 'Api\\TimeLogController@startTimer');
    $router->post('/time-logs/stop-timer', 'Api\\TimeLogController@stopTimer');
    $router->get('/time-logs/active', 'Api\\TimeLogController@getActive');
    $router->post('/time-logs/start', 'Api\\TimeLogController@startTimer');
    $router->post('/time-logs/stop', 'Api\\TimeLogController@stopTimer');
    $router->post('/time-logs/{id}/approve', 'Api\\TimeLogController@approve');

    // Employees
    $router->get('/employees', 'Api\\EmployeeController@index');
    $router->post('/employees', 'Api\\EmployeeController@store');
    $router->get('/employees/my-payroll', 'Api\\EmployeeController@myPayroll');
    $router->get('/employees/my-jobs', 'Api\\EmployeeController@myJobs');
    $router->post('/employees/jobs/{id}/status', 'Api\\EmployeeController@updateJobStatus');
    $router->get('/employees/{id}', 'Api\\EmployeeController@show');
    $router->put('/employees/{id}', 'Api\\EmployeeController@update');
    $router->delete('/employees/{id}', 'Api\\EmployeeController@destroy');
    $router->get('/employees/{id}/time-logs', 'Api\\EmployeeController@timeLogs');
    $router->get('/employees/{id}/payroll', 'Api\\EmployeeController@payroll');

    // Push Notifications
    $router->post('/push/subscribe', 'Api\\PushSubscriptionController@store');
    $router->delete('/push/unsubscribe', 'Api\\PushSubscriptionController@destroy');

    // Payroll
    $router->get('/payroll/periods', 'Api\\PayrollController@periods');
    $router->post('/payroll/periods', 'Api\\PayrollController@createPeriod');
    $router->get('/payroll/periods/{id}', 'Api\\PayrollController@showPeriod');
    $router->post('/payroll/periods/{id}/process', 'Api\\PayrollController@process');
    $router->get('/payroll/records', 'Api\\PayrollController@records');
    $router->put('/payroll/records/{id}', 'Api\\PayrollController@updateRecord');
    $router->post('/payroll/records/{id}/pay', 'Api\\PayrollController@payRecord');
    $router->post('/payroll/periods/{id}/complete', 'Api\\PayrollController@completePeriod');

    // Budgets & Expenses
    $router->get('/budgets', 'Api\\BudgetController@index');
    $router->post('/budgets', 'Api\\BudgetController@store');
    $router->put('/budgets/{id}', 'Api\\BudgetController@update');
    $router->delete('/budgets/{id}', 'Api\\BudgetController@destroy');

    $router->get('/expenses', 'Api\\ExpenseController@index');
    $router->post('/expenses', 'Api\\ExpenseController@store');
    $router->get('/expenses/{id}', 'Api\\ExpenseController@show');
    $router->put('/expenses/{id}', 'Api\\ExpenseController@update');
    $router->delete('/expenses/{id}', 'Api\\ExpenseController@destroy');
    $router->post('/expenses/{id}/approve', 'Api\\ExpenseController@approve');

    // Invoices
    $router->get('/invoices', 'Api\\InvoiceController@index');
    $router->post('/invoices', 'Api\\InvoiceController@store');
    $router->get('/invoices/{id}', 'Api\\InvoiceController@show');
    $router->put('/invoices/{id}', 'Api\\InvoiceController@update');
    $router->delete('/invoices/{id}', 'Api\\InvoiceController@destroy');
    $router->post('/invoices/{id}/send', 'Api\\InvoiceController@send');
    $router->get('/invoices/{id}/pdf', 'Api\\InvoiceController@pdf');
    $router->post('/invoices/{id}/payment-link', 'Api\\InvoiceController@paymentLink');
    $router->get('/invoices/billable-time', 'Api\\InvoiceController@getBillableTime');
    $router->post('/invoices/from-time', 'Api\\InvoiceController@createFromTime');

    // Notifications
    $router->get('/notifications', 'Api\NotificationController@index');
    $router->post('/notifications/{id}/read', 'Api\NotificationController@markAsRead');
    $router->post('/notifications/read-all', 'Api\NotificationController@markAllRead');

    // Payments
    $router->get('/payments', 'Api\\PaymentController@index');
    $router->post('/payments', 'Api\\PaymentController@store');
    $router->get('/payments/{id}', 'PaymentController@show');
    $router->put('/payments/{id}', 'PaymentController@update');
    $router->delete('/payments/{id}', 'PaymentController@destroy');
    $router->post('/payments/{id}/refund', 'Api\\PaymentController@refund');

    // Inventory
    $router->get('/inventory/categories', 'Api\\InventoryController@categories');
    $router->post('/inventory/categories', 'Api\\InventoryController@storeCategory');
    $router->get('/inventory/items', 'Api\\InventoryController@items');
    $router->post('/inventory/items', 'Api\\InventoryController@storeItem');
    $router->get('/inventory/items/{id}', 'Api\\InventoryController@showItem');
    $router->put('/inventory/items/{id}', 'Api\\InventoryController@updateItem');
    $router->delete('/inventory/items/{id}', 'Api\\InventoryController@destroyItem');
    $router->post('/inventory/items/{id}/adjust', 'Api\\InventoryController@adjustStock');
    $router->get('/inventory/low-stock', 'Api\\InventoryController@lowStock');
    $router->get('/inventory/transactions', 'Api\\InventoryController@transactions');

    // Suppliers
    $router->get('/suppliers', 'Api\\SupplierController@index');
    $router->post('/suppliers', 'Api\\SupplierController@store');
    $router->get('/suppliers/{id}', 'Api\\SupplierController@show');
    $router->put('/suppliers/{id}', 'Api\\SupplierController@update');
    $router->delete('/suppliers/{id}', 'Api\\SupplierController@destroy');

    // Documents
    $router->get('/documents', 'Api\\DocumentController@index');
    $router->post('/documents', 'Api\\DocumentController@store');
    $router->get('/documents/{id}', 'Api\\DocumentController@show');
    $router->get('/documents/{id}/download', 'Api\\DocumentController@download');
    $router->delete('/documents/{id}', 'Api\\DocumentController@destroy');

    // Reports
    $router->get('/reports/project-summary', 'Api\\ReportController@projectSummary');
    $router->get('/reports/financial-summary', 'Api\\ReportController@financialSummary');
    $router->get('/reports/payroll-summary', 'Api\\ReportController@payrollSummary');
    $router->get('/reports/time-tracking', 'Api\\ReportController@timeTracking');
    $router->get('/reports/budget-vs-actual', 'Api\\ReportController@budgetVsActual');
    $router->get('/reports/client-statement', 'Api\\ReportController@clientStatement');
    $router->post('/reports/export', 'Api\\ReportController@export');

    // Settings
    $router->get('/settings', 'Api\\SettingsController@index');
    $router->put('/settings', 'Api\\SettingsController@update');
    $router->get('/settings/categories', 'Api\\SettingsController@categories');
    $router->post('/settings/categories', 'Api\\SettingsController@storeCategory');
    $router->delete('/settings/categories/{id}', 'Api\\SettingsController@destroyCategory');

    // Email System
    $router->get('/email/settings', 'Api\\EmailSettingsController@show');
    $router->put('/email/settings', 'Api\\EmailSettingsController@update');
    $router->post('/email/settings/test', 'Api\\EmailSettingsController@test');
    $router->get('/email/settings/stats', 'Api\\EmailSettingsController@stats');

    $router->get('/email/templates', 'Api\\EmailTemplateController@index');
    $router->post('/email/templates', 'Api\\EmailTemplateController@store');
    $router->get('/email/templates/{id}', 'Api\\EmailTemplateController@show');
    $router->put('/email/templates/{id}', 'Api\\EmailTemplateController@update');
    $router->delete('/email/templates/{id}', 'Api\\EmailTemplateController@destroy');
    $router->post('/email/templates/{id}/preview', 'Api\\EmailTemplateController@preview');
    $router->get('/email/variables/{context}', 'Api\\EmailTemplateController@variables');

    $router->post('/email/send', 'Api\\EmailController@send');
    $router->post('/email/queue', 'Api\\EmailController@queue');
    $router->get('/email/logs', 'Api\\EmailController@logs');
    $router->post('/email/resend/{id}', 'Api\\EmailController@resend');
    $router->post('/email/send-invoice/{id}', 'Api\\EmailController@sendInvoice');
    $router->post('/email/send-estimate/{id}', 'Api\\EmailController@sendEstimate');

    // Email Automations
    $router->get('/email/automations', 'Api\\EmailAutomationController@index');
    $router->get('/email/automations/triggers', 'Api\\EmailAutomationController@triggers');
    $router->put('/email/automations/{trigger}', 'Api\\EmailAutomationController@update');
    $router->put('/email/automations/{trigger}/toggle', 'Api\\EmailAutomationController@toggle');

    // Email Analytics
    $router->get('/email/analytics', 'Api\\EmailTrackingController@analytics');

    // Email Advanced Features
    $router->get('/email/signature', 'Api\\EmailAdvancedController@getSignature');
    $router->post('/email/signature', 'Api\\EmailAdvancedController@saveSignature');
    $router->post('/email/signature/generate', 'Api\\EmailAdvancedController@generateSignature');
    $router->post('/email/attachments', 'Api\\EmailAdvancedController@uploadAttachment');
    $router->delete('/email/attachments', 'Api\\EmailAdvancedController@deleteAttachment');
    $router->get('/email/unsubscribes', 'Api\\EmailAdvancedController@getUnsubscribes');
    $router->post('/email/resubscribe', 'Api\\EmailAdvancedController@resubscribe');
    $router->get('/email/bounces', 'Api\\EmailAdvancedController@getBounces');
    $router->post('/email/bounces/remove', 'Api\\EmailAdvancedController@removeBounce');
    $router->post('/email/test', 'Api\\EmailAdvancedController@sendTestEmail');

    // Users (Admin only)
    $router->get('/users', 'Api\\UserController@index');
    $router->post('/users', 'Api\\UserController@store');
    $router->get('/users/{id}', 'Api\\UserController@show');
    $router->put('/users/{id}', 'Api\\UserController@update');
    $router->delete('/users/{id}', 'Api\\UserController@destroy');

    // Employees
    $router->get('/employees', 'Api\\EmployeeController@index');
    $router->post('/employees', 'Api\\EmployeeController@store');
    $router->get('/employees/{id}', 'Api\\EmployeeController@show');
    $router->put('/employees/{id}', 'Api\\EmployeeController@update');
    $router->delete('/employees/{id}', 'Api\\EmployeeController@destroy');

    // Roles & Permissions
    $router->get('/roles', 'Api\\RoleController@index');
    $router->post('/roles', 'Api\\RoleController@store');
    $router->put('/roles/{id}', 'Api\\RoleController@update');
    $router->delete('/roles/{id}', 'Api\\RoleController@destroy');

    // Notifications
    $router->get('/notifications', 'Api\\NotificationController@index');
    $router->put('/notifications/{id}/read', 'Api\\NotificationController@markRead');
    $router->post('/notifications/read-all', 'Api\\NotificationController@markAllRead');

    // Stripe Integration
    $router->get('/stripe/config', 'Api\\StripeController@config');
    $router->post('/stripe/connect', 'Api\\StripeController@connect');
    $router->get('/stripe/transactions', 'Api\\StripeController@transactions');
    $router->post('/stripe/create-payment-intent', 'Api\\StripeController@createPaymentIntent');

    // Estimates
    $router->get('/estimates', 'Api\\EstimateController@index');
    $router->post('/estimates', 'Api\\EstimateController@store');
    $router->get('/estimates/summary', 'Api\\EstimateController@summary');
    $router->get('/estimates/{id}', 'Api\\EstimateController@show');
    $router->put('/estimates/{id}', 'Api\\EstimateController@update');
    $router->delete('/estimates/{id}', 'Api\\EstimateController@destroy');
    $router->post('/estimates/{id}/items', 'Api\\EstimateController@addItem');
    $router->put('/estimates/{id}/items/{itemId}', 'Api\\EstimateController@updateItem');
    $router->delete('/estimates/{id}/items/{itemId}', 'Api\\EstimateController@deleteItem');
    $router->post('/estimates/{id}/send', 'Api\\EstimateController@send');
    $router->post('/estimates/{id}/approve', 'Api\\EstimateController@approve');
    $router->post('/estimates/{id}/reject', 'Api\\EstimateController@reject');
    $router->post('/estimates/{id}/convert', 'Api\\EstimateController@convertToInvoice');

    // Vendors
    $router->get('/vendors', 'Api\\VendorController@index');
    $router->post('/vendors', 'Api\\VendorController@store');
    $router->get('/vendors/{id}', 'Api\\VendorController@show');
    $router->put('/vendors/{id}', 'Api\\VendorController@update');
    $router->delete('/vendors/{id}', 'Api\\VendorController@destroy');

    // Purchase Orders
    $router->get('/purchase-orders', 'Api\\PurchaseOrderController@index');
    $router->post('/purchase-orders', 'Api\\PurchaseOrderController@store');
    $router->get('/purchase-orders/summary', 'Api\\PurchaseOrderController@summary');
    $router->get('/purchase-orders/{id}', 'Api\\PurchaseOrderController@show');
    $router->put('/purchase-orders/{id}', 'Api\\PurchaseOrderController@update');
    $router->delete('/purchase-orders/{id}', 'Api\\PurchaseOrderController@destroy');
    $router->post('/purchase-orders/{id}/items', 'Api\\PurchaseOrderController@addItem');
    $router->put('/purchase-orders/{id}/items/{itemId}', 'Api\\PurchaseOrderController@updateItem');
    $router->delete('/purchase-orders/{id}/items/{itemId}', 'Api\\PurchaseOrderController@deleteItem');
    $router->post('/purchase-orders/{id}/send', 'Api\\PurchaseOrderController@send');
    $router->post('/purchase-orders/{id}/receive', 'Api\\PurchaseOrderController@receive');

    // Equipment
    $router->get('/equipment', 'Api\\EquipmentController@index');
    $router->post('/equipment', 'Api\\EquipmentController@store');
    $router->get('/equipment/{id}', 'Api\\EquipmentController@show');
    $router->put('/equipment/{id}', 'Api\\EquipmentController@update');
    $router->delete('/equipment/{id}', 'Api\\EquipmentController@destroy');
    $router->post('/equipment/{id}/maintenance', 'Api\\EquipmentController@addMaintenance');

    // Documents
    $router->get('/documents', 'Api\\DocumentController@index');
    $router->post('/documents', 'Api\\DocumentController@store');
    $router->get('/documents/{id}', 'Api\\DocumentController@show');
    $router->delete('/documents/{id}', 'Api\\DocumentController@destroy');

    // Roles & Permissions
    $router->get('/roles', 'Api\\RoleController@index');
    $router->get('/roles/permissions', 'Api\\RoleController@permissions');
    $router->get('/roles/users/list', 'Api\\RoleController@users');
    $router->post('/roles', 'Api\\RoleController@store');
    $router->get('/roles/{id}', 'Api\\RoleController@show');
    $router->put('/roles/{id}', 'Api\\RoleController@update');
    $router->delete('/roles/{id}', 'Api\\RoleController@destroy');
    $router->put('/users/{id}/role', 'Api\\RoleController@assignUserRole');

    // Reports
    $router->get('/reports/financial', 'Api\\ReportsController@financial');
    $router->get('/reports/projects', 'Api\\ReportsController@projects');
    $router->get('/reports/employees', 'Api\\ReportsController@employees');
    $router->get('/reports/time', 'Api\\ReportsController@timeTracking');
    $router->get('/reports/export', 'Api\\ReportsController@export');

    // Financial Reports (Trial Balance, Income Statement, Balance Sheet)
    $router->get('/reports/trial-balance', 'Api\\FinancialReportsController@trialBalance');
    $router->get('/reports/income-statement', 'Api\\FinancialReportsController@incomeStatement');
    $router->get('/reports/balance-sheet', 'Api\\FinancialReportsController@balanceSheet');

    // User Invitations (Admin only)
    $router->get('/invitations', 'Api\\InvitationController@index');
    $router->post('/invitations', 'Api\\InvitationController@store');
    $router->post('/invitations/{id}/resend', 'Api\\InvitationController@resend');
    $router->delete('/invitations/{id}', 'Api\\InvitationController@destroy');

    // Support Tickets
    $router->get('/support/tickets', 'Api\\SupportTicketController@index');
    $router->post('/support/tickets', 'Api\\SupportTicketController@store');
    $router->get('/support/tickets/stats', 'Api\\SupportTicketController@stats');
    $router->get('/support/tickets/{id}', 'Api\\SupportTicketController@show');
    $router->put('/support/tickets/{id}', 'Api\\SupportTicketController@update');
    $router->post('/support/tickets/{id}/messages', 'Api\\SupportTicketController@addMessage');

    // Stripe Integration
    $router->get('/stripe/status', 'Api\\StripeController@status');
    $router->get('/stripe/publishable-key', 'Api\\StripeController@getPublishableKey');
    $router->post('/stripe/connect/onboarding', 'Api\\StripeController@connectOnboarding');
    $router->get('/stripe/connect/refresh-link', 'Api\\StripeController@refreshOnboardingLink');
    $router->get('/stripe/oauth/url', 'Api\\StripeController@oauthUrl');
    $router->post('/stripe/manual-keys', 'Api\\StripeController@saveManualKeys');
    $router->post('/stripe/disconnect', 'Api\\StripeController@disconnect');
    $router->post('/stripe/sync', 'Api\\StripeController@sync');
    $router->get('/stripe/dashboard', 'Api\\StripeController@dashboard');
    $router->post('/stripe/payment-intent', 'Api\\StripeController@createPaymentIntent');
    $router->get('/stripe/transactions', 'Api\\StripeController@transactions');
    $router->post('/stripe/refund', 'Api\\StripeController@refund');

    // Subscription Management (authenticated tenant)
    $router->get('/my-subscription', 'Api\\SubscriptionController@mySubscription');
    $router->get('/my-subscription/usage', 'Api\\SubscriptionController@usage');
    $router->post('/my-subscription/portal', 'Api\\SubscriptionController@portal');
    $router->post('/my-subscription/upgrade', 'Api\\SubscriptionController@upgrade');
    $router->post('/my-subscription/cancel', 'Api\\SubscriptionController@cancel');
    $router->get('/my-subscription/history', 'Api\\SubscriptionController@history');
    $router->get('/my-subscription/can-add-user', 'Api\\SubscriptionController@canAddUser');

});

// Public API Routes (No Authentication Required)
// =====================================================

// Tenant Discovery (Slack-style login)
$router->get('/api/tenants/discover', 'Api\\TenantDiscoveryController@discover');

// Subscription Plans & Checkout (public - for new signups)
$router->get('/api/plans', 'Api\\SubscriptionController@plans');
$router->post('/api/checkout', 'Api\\SubscriptionController@checkout');
$router->get('/api/checkout/verify', 'Api\\SubscriptionController@verifyCheckout');

// Stripe OAuth Callback (outside auth group - handles redirect)
$router->get('/api/stripe/oauth/callback', 'Api\\StripeController@oauthCallback');

// Stripe Webhooks (outside auth group - no auth required, signature verified)
$router->post('/api/stripe/webhooks', 'Api\\StripeWebhookController@handle');
$router->post('/api/stripe/webhooks/connect', 'Api\\StripeWebhookController@handleConnect');

// =====================================================
// Admin API Routes (Developer Console)
// =====================================================

// Admin Subscription Management
$router->get('/api/admin/subscriptions', 'Api\\AdminSubscriptionController@index');
$router->get('/api/admin/subscriptions/stats', 'Api\\AdminSubscriptionController@stats');
$router->get('/api/admin/subscriptions/recent-activity', 'Api\\AdminSubscriptionController@recentActivity');
$router->get('/api/admin/subscriptions/{id}', 'Api\\AdminSubscriptionController@show');
$router->put('/api/admin/subscriptions/{id}/status', 'Api\\AdminSubscriptionController@updateStatus');
$router->put('/api/admin/subscriptions/{id}/plan', 'Api\\AdminSubscriptionController@updatePlan');

// =====================================================
// Version Management API Routes
// =====================================================

// Public: Get current version (for update-service.js)
$router->get('/api/version', 'Api\\VersionController@current');

// Admin: Release management (requires dev session)
$router->get('/api/dev/releases', 'Api\\VersionController@index');
$router->post('/api/dev/releases', 'Api\\VersionController@store');
$router->post('/api/dev/releases/quick', 'Api\\VersionController@quickRelease');
$router->put('/api/dev/releases/{id}', 'Api\\VersionController@update');
$router->post('/api/dev/releases/{id}/publish', 'Api\\VersionController@publish');
$router->delete('/api/dev/releases/{id}', 'Api\\VersionController@destroy');


