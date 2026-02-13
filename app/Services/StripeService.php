<?php
/**
 * Stripe Service - Central handler for all Stripe API interactions
 * Supports both Stripe Connect (automatic) and Manual API key modes
 */

namespace App\Services;

use App\Core\Database;

class StripeService
{
    private Database $db;
    private ?string $secretKey = null;
    private ?string $stripeAccountId = null;
    private bool $livemode = false;

    // Stripe API base URL
    private const API_BASE = 'https://api.stripe.com/v1';
    private const CONNECT_BASE = 'https://connect.stripe.com';

    // Encryption key (should be in .env in production)
    private string $encryptionKey;

    public function __construct(?int $tenantId = null)
    {
        $this->db = Database::getInstance();
        $this->encryptionKey = getenv('ENCRYPTION_KEY') ?: 'default-key-change-in-production';

        if ($tenantId) {
            $this->loadTenantCredentials($tenantId);
        }
    }

    /**
     * Load Stripe credentials for a specific tenant
     */
    public function loadTenantCredentials(int $tenantId): bool
    {
        $connection = $this->db->fetch(
            "SELECT * FROM stripe_connections WHERE tenant_id = ? AND status = 'active'",
            [$tenantId]
        );

        if (!$connection) {
            return false;
        }

        if ($connection['connection_type'] === 'connect') {
            $this->stripeAccountId = $connection['stripe_account_id'];
            // Use platform's secret key for Connect
            $this->secretKey = getenv('STRIPE_SECRET_KEY');
        } else {
            // Manual mode - use tenant's own key
            $this->secretKey = $this->decrypt($connection['secret_key']);
        }

        $this->livemode = (bool) $connection['livemode'];
        return true;
    }

    /**
     * Initialize with platform credentials (for Connect operations)
     */
    public function initializePlatform(): self
    {
        $this->secretKey = getenv('STRIPE_SECRET_KEY');
        $this->stripeAccountId = null;
        return $this;
    }

    // ==========================================
    // STRIPE CONNECT METHODS
    // ==========================================

    /**
     * Create a Standard Connect account for a tenant
     */
    public function createConnectAccount(array $metadata = []): ?array
    {
        $this->initializePlatform();

        $params = [
            'type' => 'standard',
            'metadata' => $metadata,
        ];

        return $this->request('POST', '/accounts', $params);
    }

    /**
     * Generate an Account Link for onboarding
     */
    public function createAccountLink(string $accountId, string $returnUrl, string $refreshUrl, string $type = 'account_onboarding'): ?array
    {
        $this->initializePlatform();

        $params = [
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => $type,
        ];

        return $this->request('POST', '/account_links', $params);
    }

    /**
     * Get Connect account details
     */
    public function getAccount(string $accountId): ?array
    {
        $this->initializePlatform();
        return $this->request('GET', '/accounts/' . $accountId);
    }

    /**
     * Create OAuth authorization URL for existing Stripe accounts
     */
    public function getOAuthUrl(string $redirectUri, string $state): string
    {
        $clientId = getenv('STRIPE_CLIENT_ID');

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'scope' => 'read_write',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return self::CONNECT_BASE . '/oauth/authorize?' . $params;
    }

    /**
     * Exchange OAuth code for access token
     */
    public function exchangeOAuthCode(string $code): ?array
    {
        $this->initializePlatform();

        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
        ];

        return $this->request('POST', '/oauth/token', $params, true);
    }

    /**
     * Disconnect a Connect account
     */
    public function disconnectAccount(string $accountId): bool
    {
        $clientId = getenv('STRIPE_CLIENT_ID');

        $params = [
            'client_id' => $clientId,
            'stripe_user_id' => $accountId,
        ];

        $result = $this->request('POST', '/oauth/deauthorize', $params, true);
        return $result && isset($result['stripe_user_id']);
    }

    // ==========================================
    // CUSTOMER METHODS
    // ==========================================

    /**
     * Create a Stripe customer
     */
    public function createCustomer(array $data): ?array
    {
        return $this->request('POST', '/customers', $data);
    }

    /**
     * Get a customer
     */
    public function getCustomer(string $customerId): ?array
    {
        return $this->request('GET', '/customers/' . $customerId);
    }

    /**
     * Update a customer
     */
    public function updateCustomer(string $customerId, array $data): ?array
    {
        return $this->request('POST', '/customers/' . $customerId, $data);
    }

    /**
     * List customers
     */
    public function listCustomers(array $params = []): ?array
    {
        return $this->request('GET', '/customers', $params);
    }

    // ==========================================
    // PAYMENT INTENT METHODS
    // ==========================================

    /**
     * Create a Payment Intent
     */
    public function createPaymentIntent(int $amount, string $currency = 'usd', array $options = []): ?array
    {
        $params = array_merge([
            'amount' => $amount,
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
        ], $options);

        return $this->request('POST', '/payment_intents', $params);
    }

    /**
     * Get a Payment Intent
     */
    public function getPaymentIntent(string $paymentIntentId): ?array
    {
        return $this->request('GET', '/payment_intents/' . $paymentIntentId);
    }

    /**
     * Confirm a Payment Intent
     */
    public function confirmPaymentIntent(string $paymentIntentId, array $params = []): ?array
    {
        return $this->request('POST', '/payment_intents/' . $paymentIntentId . '/confirm', $params);
    }

    /**
     * Cancel a Payment Intent
     */
    public function cancelPaymentIntent(string $paymentIntentId): ?array
    {
        return $this->request('POST', '/payment_intents/' . $paymentIntentId . '/cancel');
    }

    // ==========================================
    // REFUND METHODS
    // ==========================================

    /**
     * Create a refund
     */
    public function createRefund(string $paymentIntentId, ?int $amount = null, ?string $reason = null): ?array
    {
        $params = ['payment_intent' => $paymentIntentId];

        if ($amount !== null) {
            $params['amount'] = $amount;
        }

        if ($reason !== null) {
            $params['reason'] = $reason;
        }

        return $this->request('POST', '/refunds', $params);
    }

    // ==========================================
    // INVOICE METHODS
    // ==========================================

    /**
     * Create an invoice
     */
    public function createInvoice(string $customerId, array $options = []): ?array
    {
        $params = array_merge(['customer' => $customerId], $options);
        return $this->request('POST', '/invoices', $params);
    }

    /**
     * Finalize an invoice
     */
    public function finalizeInvoice(string $invoiceId): ?array
    {
        return $this->request('POST', '/invoices/' . $invoiceId . '/finalize');
    }

    /**
     * Send an invoice
     */
    public function sendInvoice(string $invoiceId): ?array
    {
        return $this->request('POST', '/invoices/' . $invoiceId . '/send');
    }

    // ==========================================
    // WEBHOOK METHODS
    // ==========================================

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[$key] = $value;
        }

        if (!isset($parts['t']) || !isset($parts['v1'])) {
            return false;
        }

        $timestamp = $parts['t'];
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSig = hash_hmac('sha256', $signedPayload, $secret);

        // Timing-safe comparison
        return hash_equals($expectedSig, $parts['v1']);
    }

    /**
     * Construct event from webhook payload
     */
    public function constructEvent(string $payload, string $signature, string $secret): ?array
    {
        if (!$this->verifyWebhookSignature($payload, $signature, $secret)) {
            return null;
        }

        return json_decode($payload, true);
    }

    // ==========================================
    // BALANCE & TRANSACTIONS
    // ==========================================

    /**
     * Get balance
     */
    public function getBalance(): ?array
    {
        return $this->request('GET', '/balance');
    }

    /**
     * List balance transactions
     */
    public function listBalanceTransactions(array $params = []): ?array
    {
        return $this->request('GET', '/balance_transactions', $params);
    }

    /**
     * List charges
     */
    public function listCharges(array $params = []): ?array
    {
        return $this->request('GET', '/charges', $params);
    }

    // ==========================================
    // ENCRYPTION HELPERS
    // ==========================================

    /**
     * Encrypt sensitive data
     */
    public function encrypt(string $data): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data
     */
    public function decrypt(string $data): string
    {
        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
    }

    // ==========================================
    // HTTP REQUEST HELPER
    // ==========================================

    /**
     * Make a request to Stripe API
     */
    private function request(string $method, string $endpoint, array $params = [], bool $isConnect = false): ?array
    {
        if (!$this->secretKey) {
            error_log('Stripe: No secret key configured');
            return null;
        }

        $baseUrl = $isConnect ? self::CONNECT_BASE : self::API_BASE;
        $url = $baseUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/x-www-form-urlencoded',
            'Stripe-Version: 2023-10-16',
        ];

        // Add Stripe-Account header for Connect requests
        if ($this->stripeAccountId && !$isConnect) {
            $headers[] = 'Stripe-Account: ' . $this->stripeAccountId;
        }

        $ch = curl_init();

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->flattenParams($params)));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('Stripe cURL error: ' . $error);
            return null;
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            error_log('Stripe API error: ' . ($data['error']['message'] ?? 'Unknown error'));
            return $data; // Return error for handling
        }

        return $data;
    }

    /**
     * Flatten nested arrays for Stripe API format
     */
    private function flattenParams(array $params, string $prefix = ''): array
    {
        $result = [];

        foreach ($params as $key => $value) {
            $newKey = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenParams($value, $newKey));
            } elseif (is_bool($value)) {
                // Stripe requires 'true' or 'false' strings, not 1 or 0
                $result[$newKey] = $value ? 'true' : 'false';
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    // ==========================================
    // CONNECTION MANAGEMENT
    // ==========================================

    /**
     * Save a new Stripe connection for a tenant
     */
    public function saveConnection(int $tenantId, array $data): int
    {
        $existing = $this->db->fetch(
            "SELECT id FROM stripe_connections WHERE tenant_id = ?",
            [$tenantId]
        );

        // Encrypt sensitive data
        if (!empty($data['access_token'])) {
            $data['access_token'] = $this->encrypt($data['access_token']);
        }
        if (!empty($data['refresh_token'])) {
            $data['refresh_token'] = $this->encrypt($data['refresh_token']);
        }
        if (!empty($data['secret_key'])) {
            $data['secret_key'] = $this->encrypt($data['secret_key']);
        }
        if (!empty($data['webhook_secret'])) {
            $data['webhook_secret'] = $this->encrypt($data['webhook_secret']);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            $this->db->update('stripe_connections', $data, ['id' => $existing['id']]);
            return $existing['id'];
        } else {
            $data['tenant_id'] = $tenantId;
            $data['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert('stripe_connections', $data);
        }
    }

    /**
     * Get connection status for a tenant
     */
    public function getConnectionStatus(int $tenantId): ?array
    {
        $connection = $this->db->fetch(
            "SELECT 
                connection_type, stripe_account_id, livemode, 
                charges_enabled, payouts_enabled, details_submitted,
                business_name, connected_at, last_sync_at, status, error_message
             FROM stripe_connections 
             WHERE tenant_id = ?",
            [$tenantId]
        );

        if (!$connection) {
            return null;
        }

        // Don't expose sensitive data
        return $connection;
    }

    /**
     * Update connection status from Stripe account data
     */
    public function syncConnectionStatus(int $tenantId): bool
    {
        $connection = $this->db->fetch(
            "SELECT stripe_account_id, connection_type FROM stripe_connections WHERE tenant_id = ?",
            [$tenantId]
        );

        if (!$connection || $connection['connection_type'] !== 'connect') {
            return false;
        }

        $account = $this->getAccount($connection['stripe_account_id']);

        if (!$account || isset($account['error'])) {
            $this->db->update('stripe_connections', [
                'status' => 'error',
                'error_message' => $account['error']['message'] ?? 'Failed to fetch account',
            ], ['tenant_id' => $tenantId]);
            return false;
        }

        $this->db->update('stripe_connections', [
            'charges_enabled' => $account['charges_enabled'] ?? false,
            'payouts_enabled' => $account['payouts_enabled'] ?? false,
            'details_submitted' => $account['details_submitted'] ?? false,
            'business_name' => $account['business_profile']['name'] ?? null,
            'livemode' => $account['payouts_enabled'] ?? false, // Use payouts as indicator
            'status' => ($account['charges_enabled'] ?? false) ? 'active' : 'pending',
            'last_sync_at' => date('Y-m-d H:i:s'),
            'error_message' => null,
        ], ['tenant_id' => $tenantId]);

        return true;
    }

    // ==========================================
    // SUBSCRIPTION METHODS (Platform Billing)
    // ==========================================

    /**
     * Create a Checkout Session for subscription signup
     */
    public function createCheckoutSession(array $params): ?array
    {
        $this->initializePlatform();
        return $this->request('POST', '/checkout/sessions', $params);
    }

    /**
     * Retrieve a Checkout Session
     */
    public function getCheckoutSession(string $sessionId): ?array
    {
        $this->initializePlatform();
        return $this->request('GET', '/checkout/sessions/' . $sessionId);
    }

    /**
     * Get a subscription
     */
    public function getSubscription(string $subscriptionId): ?array
    {
        $this->initializePlatform();
        return $this->request('GET', '/subscriptions/' . $subscriptionId);
    }

    /**
     * Update a subscription (e.g., change plan)
     */
    public function updateSubscription(string $subscriptionId, string $newPriceId): ?array
    {
        $this->initializePlatform();

        // Get current subscription to find the item ID
        $subscription = $this->getSubscription($subscriptionId);

        if (!$subscription || !isset($subscription['items']['data'][0]['id'])) {
            return null;
        }

        $itemId = $subscription['items']['data'][0]['id'];

        return $this->request('POST', '/subscriptions/' . $subscriptionId, [
            'items' => [
                [
                    'id' => $itemId,
                    'price' => $newPriceId,
                ]
            ],
            'proration_behavior' => 'create_prorations',
        ]);
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = true): ?array
    {
        $this->initializePlatform();

        if ($atPeriodEnd) {
            return $this->request('POST', '/subscriptions/' . $subscriptionId, [
                'cancel_at_period_end' => true,
            ]);
        } else {
            return $this->request('DELETE', '/subscriptions/' . $subscriptionId);
        }
    }

    /**
     * Reactivate a canceled subscription
     */
    public function reactivateSubscription(string $subscriptionId): ?array
    {
        $this->initializePlatform();
        return $this->request('POST', '/subscriptions/' . $subscriptionId, [
            'cancel_at_period_end' => false,
        ]);
    }

    /**
     * Create a Customer Portal session
     */
    public function createPortalSession(string $customerId, string $returnUrl): ?array
    {
        $this->initializePlatform();
        return $this->request('POST', '/billing_portal/sessions', [
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ]);
    }

    /**
     * List invoices for a customer
     */
    public function listCustomerInvoices(string $customerId, int $limit = 10): ?array
    {
        $this->initializePlatform();
        return $this->request('GET', '/invoices', [
            'customer' => $customerId,
            'limit' => $limit,
        ]);
    }
}
