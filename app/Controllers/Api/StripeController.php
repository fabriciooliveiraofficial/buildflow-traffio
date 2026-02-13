<?php
/**
 * Stripe Integration Controller
 * Handles Connect onboarding, OAuth, manual keys, and payment operations
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\StripeService;

class StripeController extends Controller
{
    private StripeService $stripe;

    public function __construct()
    {
        parent::__construct();
        $this->stripe = new StripeService();
    }

    /**
     * Get Stripe connection status for current tenant
     */
    public function status(): array
    {
        $tenantId = $this->db->getTenantId();
        $status = $this->stripe->getConnectionStatus($tenantId);

        if (!$status) {
            return $this->success([
                'connected' => false,
                'connection_type' => null,
            ]);
        }

        return $this->success([
            'connected' => $status['status'] === 'active',
            'connection_type' => $status['connection_type'],
            'stripe_account_id' => $status['stripe_account_id'],
            'livemode' => (bool) $status['livemode'],
            'charges_enabled' => (bool) $status['charges_enabled'],
            'payouts_enabled' => (bool) $status['payouts_enabled'],
            'details_submitted' => (bool) $status['details_submitted'],
            'business_name' => $status['business_name'],
            'status' => $status['status'],
            'connected_at' => $status['connected_at'],
            'last_sync_at' => $status['last_sync_at'],
            'error_message' => $status['error_message'],
        ]);
    }

    /**
     * Start Stripe Connect onboarding for new accounts
     */
    public function connectOnboarding(): array
    {
        $this->authorize('settings.update');

        $tenantId = $this->db->getTenantId();
        $baseUrl = getenv('APP_URL') ?: ('https://' . $_SERVER['HTTP_HOST']);

        // Create a new Connect account
        $tenant = $this->db->fetch("SELECT * FROM tenants WHERE id = ?", [$tenantId]);

        $account = $this->stripe->createConnectAccount([
            'tenant_id' => (string) $tenantId,
            'platform' => 'construction_erp',
        ]);

        if (!$account || isset($account['error'])) {
            $this->error($account['error']['message'] ?? 'Failed to create Stripe account', 422);
        }

        // Save the connection
        $this->stripe->saveConnection($tenantId, [
            'connection_type' => 'connect',
            'stripe_account_id' => $account['id'],
            'status' => 'pending',
            'livemode' => $account['livemode'] ?? false,
        ]);

        // Create the account link for onboarding
        $accountLink = $this->stripe->createAccountLink(
            $account['id'],
            $baseUrl . '/settings?stripe=success',
            $baseUrl . '/settings?stripe=refresh'
        );

        if (!$accountLink || isset($accountLink['error'])) {
            $this->error($accountLink['error']['message'] ?? 'Failed to create onboarding link', 422);
        }

        return $this->success([
            'url' => $accountLink['url'],
            'expires_at' => $accountLink['expires_at'],
        ]);
    }

    /**
     * Refresh the onboarding link (if expired or user needs to complete more)
     */
    public function refreshOnboardingLink(): array
    {
        $this->authorize('settings.update');

        $tenantId = $this->db->getTenantId();
        $baseUrl = getenv('APP_URL') ?: ('https://' . $_SERVER['HTTP_HOST']);

        $connection = $this->db->fetch(
            "SELECT stripe_account_id FROM stripe_connections WHERE tenant_id = ?",
            [$tenantId]
        );

        if (!$connection || !$connection['stripe_account_id']) {
            $this->error('No Stripe account found. Start a new connection.', 404);
        }

        $accountLink = $this->stripe->createAccountLink(
            $connection['stripe_account_id'],
            $baseUrl . '/settings?stripe=success',
            $baseUrl . '/settings?stripe=refresh'
        );

        if (!$accountLink || isset($accountLink['error'])) {
            $this->error($accountLink['error']['message'] ?? 'Failed to create onboarding link', 422);
        }

        return $this->success([
            'url' => $accountLink['url'],
            'expires_at' => $accountLink['expires_at'],
        ]);
    }

    /**
     * Get OAuth URL for connecting existing Stripe accounts
     */
    public function oauthUrl(): array
    {
        $this->authorize('settings.update');

        $tenantId = $this->db->getTenantId();
        $baseUrl = getenv('APP_URL') ?: ('https://' . $_SERVER['HTTP_HOST']);

        // Generate a state token for security
        $state = bin2hex(random_bytes(16));
        $_SESSION['stripe_oauth_state'] = $state;
        $_SESSION['stripe_oauth_tenant'] = $tenantId;

        $redirectUri = $baseUrl . '/api/stripe/oauth/callback';
        $url = $this->stripe->getOAuthUrl($redirectUri, $state);

        return $this->success(['url' => $url]);
    }

    /**
     * Handle OAuth callback
     */
    public function oauthCallback(): void
    {
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;
        $errorDesc = $_GET['error_description'] ?? null;

        $baseUrl = getenv('APP_URL') ?: ('https://' . $_SERVER['HTTP_HOST']);

        // Verify state
        if (!$state || $state !== ($_SESSION['stripe_oauth_state'] ?? '')) {
            header('Location: ' . $baseUrl . '/settings?stripe=error&message=' . urlencode('Invalid state'));
            exit;
        }

        if ($error) {
            header('Location: ' . $baseUrl . '/settings?stripe=error&message=' . urlencode($errorDesc ?? $error));
            exit;
        }

        if (!$code) {
            header('Location: ' . $baseUrl . '/settings?stripe=error&message=' . urlencode('No authorization code'));
            exit;
        }

        $tenantId = $_SESSION['stripe_oauth_tenant'] ?? null;
        if (!$tenantId) {
            header('Location: ' . $baseUrl . '/settings?stripe=error&message=' . urlencode('Session expired'));
            exit;
        }

        // Exchange code for access token
        $response = $this->stripe->exchangeOAuthCode($code);

        if (!$response || isset($response['error'])) {
            $message = $response['error_description'] ?? $response['error']['message'] ?? 'OAuth failed';
            header('Location: ' . $baseUrl . '/settings?stripe=error&message=' . urlencode($message));
            exit;
        }

        // Save the connection
        $this->stripe->saveConnection($tenantId, [
            'connection_type' => 'connect',
            'stripe_account_id' => $response['stripe_user_id'],
            'access_token' => $response['access_token'] ?? null,
            'refresh_token' => $response['refresh_token'] ?? null,
            'livemode' => $response['livemode'] ?? false,
            'status' => 'active',
            'connected_at' => date('Y-m-d H:i:s'),
        ]);

        // Sync account status
        $this->stripe->syncConnectionStatus($tenantId);

        // Clear session data
        unset($_SESSION['stripe_oauth_state'], $_SESSION['stripe_oauth_tenant']);

        header('Location: ' . $baseUrl . '/settings?stripe=success');
        exit;
    }

    /**
     * Save manual API keys
     */
    public function saveManualKeys(): array
    {
        $this->authorize('settings.update');

        $input = $this->getJsonInput();
        $tenantId = $this->db->getTenantId();

        if (empty($input['publishable_key']) || empty($input['secret_key'])) {
            $this->error('Publishable key and secret key are required', 422);
        }

        // Validate the keys by making a test API call
        $testStripe = new StripeService();
        // We can't directly test without saving, so we trust the format

        if (!str_starts_with($input['publishable_key'], 'pk_')) {
            $this->error('Invalid publishable key format', 422);
        }

        if (!str_starts_with($input['secret_key'], 'sk_')) {
            $this->error('Invalid secret key format', 422);
        }

        $livemode = str_starts_with($input['secret_key'], 'sk_live_');

        $this->stripe->saveConnection($tenantId, [
            'connection_type' => 'manual',
            'publishable_key' => $input['publishable_key'],
            'secret_key' => $input['secret_key'],
            'webhook_secret' => $input['webhook_secret'] ?? null,
            'livemode' => $livemode,
            'status' => 'active',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
            'connected_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success(null, 'Stripe API keys saved successfully');
    }

    /**
     * Disconnect Stripe account
     */
    public function disconnect(): array
    {
        $this->authorize('settings.update');

        $tenantId = $this->db->getTenantId();

        $connection = $this->db->fetch(
            "SELECT * FROM stripe_connections WHERE tenant_id = ?",
            [$tenantId]
        );

        if (!$connection) {
            $this->error('No Stripe connection found', 404);
        }

        // If Connect account, deauthorize
        if ($connection['connection_type'] === 'connect' && $connection['stripe_account_id']) {
            $this->stripe->disconnectAccount($connection['stripe_account_id']);
        }

        // Update status
        $this->db->update('stripe_connections', [
            'status' => 'disconnected',
            'access_token' => null,
            'refresh_token' => null,
            'secret_key' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['tenant_id' => $tenantId]);

        return $this->success(null, 'Stripe disconnected successfully');
    }

    /**
     * Sync account status from Stripe
     */
    public function sync(): array
    {
        $this->authorize('settings.update');

        $tenantId = $this->db->getTenantId();
        $result = $this->stripe->syncConnectionStatus($tenantId);

        if (!$result) {
            $this->error('Failed to sync account status', 422);
        }

        return $this->success(null, 'Account status synced');
    }

    /**
     * Get dashboard stats
     */
    public function dashboard(): array
    {
        $tenantId = $this->db->getTenantId();

        if (!$this->stripe->loadTenantCredentials($tenantId)) {
            $this->error('Stripe not connected', 422);
        }

        // Get balance
        $balance = $this->stripe->getBalance();

        // Get recent charges
        $charges = $this->stripe->listCharges(['limit' => 10]);

        // Get local stats
        $monthStart = date('Y-m-01');
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as transaction_count,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as successful_amount,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_count
             FROM payments 
             WHERE tenant_id = ? AND payment_method = 'stripe' AND created_at >= ?",
            [$tenantId, $monthStart]
        );

        return $this->success([
            'balance' => $balance,
            'recent_charges' => $charges['data'] ?? [],
            'this_month' => [
                'transactions' => (int) $stats['transaction_count'],
                'total' => (float) $stats['total_amount'],
                'successful' => (float) $stats['successful_amount'],
                'success_rate' => $stats['transaction_count'] > 0
                    ? round(($stats['successful_count'] / $stats['transaction_count']) * 100, 1)
                    : 0,
            ],
        ]);
    }

    /**
     * Create a payment intent for an invoice
     */
    public function createPaymentIntent(): array
    {
        $input = $this->getJsonInput();
        $invoiceId = $input['invoice_id'] ?? null;

        if (!$invoiceId) {
            $this->error('Invoice ID required', 422);
        }

        $tenantId = $this->db->getTenantId();

        $invoice = $this->db->fetch(
            "SELECT i.*, c.name as client_name, c.email as client_email
             FROM invoices i
             JOIN clients c ON i.client_id = c.id  
             WHERE i.id = ? AND i.tenant_id = ?",
            [$invoiceId, $tenantId]
        );

        if (!$invoice) {
            $this->error('Invoice not found', 404);
        }

        $amountDue = $invoice['total_amount'] - $invoice['paid_amount'];

        if ($amountDue <= 0) {
            $this->error('Invoice is already paid', 422);
        }

        if (!$this->stripe->loadTenantCredentials($tenantId)) {
            $this->error('Stripe not configured', 422);
        }

        // Create payment intent
        $paymentIntent = $this->stripe->createPaymentIntent(
            (int) ($amountDue * 100), // Convert to cents
            'usd',
            [
                'description' => "Invoice #{$invoice['invoice_number']}",
                'metadata' => [
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoice['invoice_number'],
                    'tenant_id' => $tenantId,
                ],
            ]
        );

        if (!$paymentIntent || isset($paymentIntent['error'])) {
            $this->error($paymentIntent['error']['message'] ?? 'Failed to create payment', 422);
        }

        // Store the payment intent
        $this->db->insert('stripe_payment_intents', [
            'tenant_id' => $tenantId,
            'stripe_payment_intent_id' => $paymentIntent['id'],
            'invoice_id' => $invoiceId,
            'amount' => $paymentIntent['amount'],
            'currency' => $paymentIntent['currency'],
            'status' => $paymentIntent['status'],
            'client_secret' => $paymentIntent['client_secret'],
        ]);

        return $this->success([
            'payment_intent_id' => $paymentIntent['id'],
            'client_secret' => $paymentIntent['client_secret'],
            'amount' => $paymentIntent['amount'],
            'currency' => $paymentIntent['currency'],
            'invoice' => [
                'id' => $invoice['id'],
                'number' => $invoice['invoice_number'],
                'amount_due' => $amountDue,
                'client_name' => $invoice['client_name'],
            ],
        ]);
    }

    /**
     * Get transactions list
     */
    public function transactions(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $status = $params['status'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["p.tenant_id = ?", "p.payment_method = 'stripe'"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "p.status = ?";
            $bindings[] = $status;
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM payments p WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $payments = $this->db->fetchAll(
            "SELECT 
                p.*,
                i.invoice_number,
                c.name as client_name
             FROM payments p
             LEFT JOIN invoices i ON p.invoice_id = i.id
             LEFT JOIN clients c ON i.client_id = c.id
             WHERE {$where}
             ORDER BY p.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($payments, $total, $page, $perPage);
    }

    /**
     * Process a refund
     */
    public function refund(): array
    {
        $input = $this->getJsonInput();
        $paymentId = $input['payment_id'] ?? null;
        $amount = $input['amount'] ?? null;
        $reason = $input['reason'] ?? null;

        if (!$paymentId) {
            $this->error('Payment ID required', 422);
        }

        $tenantId = $this->db->getTenantId();

        $payment = $this->db->fetch(
            "SELECT * FROM payments WHERE id = ? AND tenant_id = ?",
            [$paymentId, $tenantId]
        );

        if (!$payment) {
            $this->error('Payment not found', 404);
        }

        if ($payment['status'] !== 'completed') {
            $this->error('Can only refund completed payments', 422);
        }

        if (!$payment['stripe_payment_id']) {
            $this->error('No Stripe payment to refund', 422);
        }

        if (!$this->stripe->loadTenantCredentials($tenantId)) {
            $this->error('Stripe not configured', 422);
        }

        $refundAmount = $amount ? (int) ($amount * 100) : null;

        $refund = $this->stripe->createRefund(
            $payment['stripe_payment_id'],
            $refundAmount,
            $reason
        );

        if (!$refund || isset($refund['error'])) {
            $this->error($refund['error']['message'] ?? 'Refund failed', 422);
        }

        // Update payment status
        $this->db->update('payments', [
            'status' => 'refunded',
            'notes' => ($payment['notes'] ?? '') . "\nRefunded: " . ($amount ?: $payment['amount']) . ". Reason: {$reason}",
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $paymentId]);

        // Update invoice
        if ($payment['invoice_id']) {
            $invoice = $this->db->fetch("SELECT * FROM invoices WHERE id = ?", [$payment['invoice_id']]);
            $refundedAmount = $amount ?: $payment['amount'];
            $newPaidAmount = max(0, $invoice['paid_amount'] - $refundedAmount);
            $newStatus = $newPaidAmount == 0 ? 'sent' : ($newPaidAmount < $invoice['total_amount'] ? 'partial' : 'paid');

            $this->db->update('invoices', [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
            ], ['id' => $payment['invoice_id']]);
        }

        return $this->success(['refund_id' => $refund['id']], 'Refund processed');
    }

    /**
     * Get publishable key for frontend
     */
    public function getPublishableKey(): array
    {
        $tenantId = $this->db->getTenantId();

        $connection = $this->db->fetch(
            "SELECT connection_type, publishable_key, stripe_account_id 
             FROM stripe_connections 
             WHERE tenant_id = ? AND status = 'active'",
            [$tenantId]
        );

        if (!$connection) {
            return $this->success(['publishable_key' => null]);
        }

        $publishableKey = null;
        $stripeAccount = null;

        if ($connection['connection_type'] === 'manual') {
            $publishableKey = $connection['publishable_key'];
        } else {
            // For Connect, use platform's publishable key
            $publishableKey = getenv('STRIPE_PUBLIC_KEY');
            $stripeAccount = $connection['stripe_account_id'];
        }

        return $this->success([
            'publishable_key' => $publishableKey,
            'stripe_account' => $stripeAccount,
        ]);
    }
}
