<?php
/**
 * Stripe Webhook Controller
 * Handles incoming webhook events from Stripe
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Services\StripeService;

class StripeWebhookController extends Controller
{
    private StripeService $stripe;

    public function __construct()
    {
        // Don't call parent - webhooks don't use auth
        $this->db = Database::getInstance();
        $this->stripe = new StripeService();
    }

    /**
     * Handle webhooks from platform account
     */
    public function handle(): void
    {
        $this->processWebhook(false);
    }

    /**
     * Handle webhooks from Connect accounts
     */
    public function handleConnect(): void
    {
        $this->processWebhook(true);
    }

    /**
     * Process incoming webhook
     */
    private function processWebhook(bool $isConnect): void
    {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        if (empty($payload) || empty($signature)) {
            $this->sendResponse(400, ['error' => 'Missing payload or signature']);
            return;
        }

        // Get webhook secret
        $webhookSecret = $isConnect
            ? getenv('STRIPE_CONNECT_WEBHOOK_SECRET')
            : getenv('STRIPE_WEBHOOK_SECRET');

        if (!$webhookSecret) {
            error_log('Stripe Webhook: No webhook secret configured');
            $this->sendResponse(500, ['error' => 'Webhook not configured']);
            return;
        }

        // Verify signature
        if (!$this->stripe->verifyWebhookSignature($payload, $signature, $webhookSecret)) {
            error_log('Stripe Webhook: Invalid signature');
            $this->sendResponse(400, ['error' => 'Invalid signature']);
            return;
        }

        $event = json_decode($payload, true);

        if (!$event || !isset($event['id']) || !isset($event['type'])) {
            $this->sendResponse(400, ['error' => 'Invalid event']);
            return;
        }

        // Check for duplicate event
        $existing = $this->db->fetch(
            "SELECT id, processed FROM stripe_webhook_events WHERE stripe_event_id = ?",
            [$event['id']]
        );

        if ($existing && $existing['processed']) {
            // Already processed, return success
            $this->sendResponse(200, ['status' => 'already_processed']);
            return;
        }

        // Determine tenant from event
        $tenantId = $this->getTenantFromEvent($event, $isConnect);

        // Store the event
        $eventId = $this->storeEvent($event, $tenantId, $existing['id'] ?? null);

        // Process the event
        try {
            $this->handleEvent($event, $tenantId);

            // Mark as processed
            $this->db->update('stripe_webhook_events', [
                'processed' => true,
                'processed_at' => date('Y-m-d H:i:s'),
            ], ['id' => $eventId]);

            $this->sendResponse(200, ['status' => 'processed']);

        } catch (\Exception $e) {
            error_log('Stripe Webhook Error: ' . $e->getMessage());

            $this->db->query(
                "UPDATE stripe_webhook_events 
                 SET processing_error = ?, retry_count = retry_count + 1 
                 WHERE id = ?",
                [$e->getMessage(), $eventId]
            );

            // Return 500 to trigger Stripe retry
            $this->sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Determine tenant from event
     */
    private function getTenantFromEvent(array $event, bool $isConnect): ?int
    {
        // For Connect events, look up by account ID
        if ($isConnect && isset($event['account'])) {
            $connection = $this->db->fetch(
                "SELECT tenant_id FROM stripe_connections WHERE stripe_account_id = ?",
                [$event['account']]
            );
            return $connection ? (int) $connection['tenant_id'] : null;
        }

        // Check metadata for tenant_id
        $object = $event['data']['object'] ?? [];
        if (isset($object['metadata']['tenant_id'])) {
            return (int) $object['metadata']['tenant_id'];
        }

        // For customer events, look up by customer ID
        if (isset($object['customer'])) {
            $customer = $this->db->fetch(
                "SELECT tenant_id FROM stripe_customers WHERE stripe_customer_id = ?",
                [$object['customer']]
            );
            if ($customer) {
                return (int) $customer['tenant_id'];
            }
        }

        return null;
    }

    /**
     * Store event in database
     */
    private function storeEvent(array $event, ?int $tenantId, ?int $existingId = null): int
    {
        $data = [
            'stripe_event_id' => $event['id'],
            'event_type' => $event['type'],
            'stripe_account_id' => $event['account'] ?? null,
            'api_version' => $event['api_version'] ?? null,
            'payload' => json_encode($event),
            'tenant_id' => $tenantId,
        ];

        if ($existingId) {
            $this->db->update('stripe_webhook_events', $data, ['id' => $existingId]);
            return $existingId;
        }

        return $this->db->insert('stripe_webhook_events', $data);
    }

    /**
     * Handle specific event types
     */
    private function handleEvent(array $event, ?int $tenantId): void
    {
        $type = $event['type'];
        $object = $event['data']['object'];

        switch ($type) {
            // Account events (Connect)
            case 'account.updated':
                $this->handleAccountUpdated($object);
                break;

            // Checkout session (SaaS signup)
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($object);
                break;

            // Payment events
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($object, $tenantId);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($object, $tenantId);
                break;

            // Charge events
            case 'charge.succeeded':
                $this->handleChargeSucceeded($object, $tenantId);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($object, $tenantId);
                break;

            // Invoice events
            case 'invoice.paid':
                $this->handleInvoicePaid($object, $tenantId);
                break;

            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($object, $tenantId);
                break;

            // Customer events
            case 'customer.created':
            case 'customer.updated':
                $this->handleCustomerUpdated($object, $tenantId);
                break;

            case 'customer.deleted':
                $this->handleCustomerDeleted($object, $tenantId);
                break;

            // Subscription events
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($object, $tenantId);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($object, $tenantId);
                break;

            // Payout events
            case 'payout.paid':
            case 'payout.failed':
                // Log for reporting, no action needed
                break;

            default:
                // Unknown event type, just log it
                error_log("Stripe Webhook: Unhandled event type: {$type}");
        }
    }

    /**
     * Handle account.updated (Connect)
     */
    private function handleAccountUpdated(array $account): void
    {
        $connection = $this->db->fetch(
            "SELECT tenant_id FROM stripe_connections WHERE stripe_account_id = ?",
            [$account['id']]
        );

        if (!$connection) {
            return;
        }

        $this->db->update('stripe_connections', [
            'charges_enabled' => $account['charges_enabled'] ?? false,
            'payouts_enabled' => $account['payouts_enabled'] ?? false,
            'details_submitted' => $account['details_submitted'] ?? false,
            'business_name' => $account['business_profile']['name'] ?? null,
            'status' => ($account['charges_enabled'] ?? false) ? 'active' : 'pending',
            'last_sync_at' => date('Y-m-d H:i:s'),
        ], ['stripe_account_id' => $account['id']]);
    }

    /**
     * Handle payment_intent.succeeded
     */
    private function handlePaymentIntentSucceeded(array $paymentIntent, ?int $tenantId): void
    {
        if (!$tenantId) {
            return;
        }

        // Update local payment intent record
        $this->db->query(
            "UPDATE stripe_payment_intents 
             SET status = ?, updated_at = NOW() 
             WHERE stripe_payment_intent_id = ? AND tenant_id = ?",
            [$paymentIntent['status'], $paymentIntent['id'], $tenantId]
        );

        // Get invoice ID from metadata
        $invoiceId = $paymentIntent['metadata']['invoice_id'] ?? null;

        if ($invoiceId) {
            $this->recordPayment($tenantId, $invoiceId, $paymentIntent);
        }
    }

    /**
     * Handle payment_intent.payment_failed
     */
    private function handlePaymentIntentFailed(array $paymentIntent, ?int $tenantId): void
    {
        if (!$tenantId) {
            return;
        }

        $this->db->query(
            "UPDATE stripe_payment_intents 
             SET status = ?, last_payment_error = ?, updated_at = NOW() 
             WHERE stripe_payment_intent_id = ? AND tenant_id = ?",
            [
                $paymentIntent['status'],
                json_encode($paymentIntent['last_payment_error'] ?? null),
                $paymentIntent['id'],
                $tenantId
            ]
        );
    }

    /**
     * Handle charge.succeeded
     */
    private function handleChargeSucceeded(array $charge, ?int $tenantId): void
    {
        // Charges are usually part of payment intents, handled there
        // But log for completeness
        error_log("Charge succeeded: {$charge['id']} for tenant {$tenantId}");
    }

    /**
     * Handle charge.refunded
     */
    private function handleChargeRefunded(array $charge, ?int $tenantId): void
    {
        if (!$tenantId) {
            return;
        }

        // Update payment status
        $this->db->query(
            "UPDATE payments 
             SET status = 'refunded', updated_at = NOW() 
             WHERE stripe_charge_id = ? AND tenant_id = ?",
            [$charge['id'], $tenantId]
        );
    }

    /**
     * Handle invoice.paid
     */
    private function handleInvoicePaid(array $invoice, ?int $tenantId): void
    {
        // This is Stripe's invoice, not our local invoice
        // Log for reference
        error_log("Stripe invoice paid: {$invoice['id']}");
    }

    /**
     * Handle invoice.payment_failed
     */
    private function handleInvoicePaymentFailed(array $invoice, ?int $tenantId): void
    {
        error_log("Stripe invoice payment failed: {$invoice['id']}");
    }

    /**
     * Handle customer created/updated
     */
    private function handleCustomerUpdated(array $customer, ?int $tenantId): void
    {
        if (!$tenantId) {
            return;
        }

        $existing = $this->db->fetch(
            "SELECT id FROM stripe_customers WHERE stripe_customer_id = ? AND tenant_id = ?",
            [$customer['id'], $tenantId]
        );

        $data = [
            'email' => $customer['email'] ?? null,
            'name' => $customer['name'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'default_payment_method' => $customer['default_source'] ?? $customer['invoice_settings']['default_payment_method'] ?? null,
            'balance' => $customer['balance'] ?? 0,
            'currency' => $customer['currency'] ?? 'usd',
            'synced_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->update('stripe_customers', $data, ['id' => $existing['id']]);
        }
        // Don't create - customers should be created via our system
    }

    /**
     * Handle customer.deleted
     */
    private function handleCustomerDeleted(array $customer, ?int $tenantId): void
    {
        if (!$tenantId) {
            return;
        }

        $this->db->query(
            "DELETE FROM stripe_customers WHERE stripe_customer_id = ? AND tenant_id = ?",
            [$customer['id'], $tenantId]
        );
    }

    /**
     * Handle subscription created/updated
     */
    private function handleSubscriptionUpdated(array $subscription, ?int $tenantId): void
    {
        if (!$tenantId) {
            return;
        }

        $existing = $this->db->fetch(
            "SELECT id FROM stripe_subscriptions WHERE stripe_subscription_id = ? AND tenant_id = ?",
            [$subscription['id'], $tenantId]
        );

        $data = [
            'tenant_id' => $tenantId,
            'stripe_subscription_id' => $subscription['id'],
            'stripe_customer_id' => $subscription['customer'],
            'status' => $subscription['status'],
            'current_period_start' => date('Y-m-d H:i:s', $subscription['current_period_start']),
            'current_period_end' => date('Y-m-d H:i:s', $subscription['current_period_end']),
            'cancel_at_period_end' => $subscription['cancel_at_period_end'] ?? false,
            'canceled_at' => isset($subscription['canceled_at']) ? date('Y-m-d H:i:s', $subscription['canceled_at']) : null,
            'ended_at' => isset($subscription['ended_at']) ? date('Y-m-d H:i:s', $subscription['ended_at']) : null,
            'synced_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->update('stripe_subscriptions', $data, ['id' => $existing['id']]);
        } else {
            $this->db->insert('stripe_subscriptions', $data);
        }
    }

    /**
     * Handle subscription deleted
     */
    private function handleSubscriptionDeleted(array $subscription, ?int $tenantId): void
    {
        if (!$tenantId) {
            return;
        }

        $this->db->update('stripe_subscriptions', [
            'status' => 'canceled',
            'ended_at' => date('Y-m-d H:i:s'),
            'synced_at' => date('Y-m-d H:i:s'),
        ], [
            'stripe_subscription_id' => $subscription['id'],
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Record a successful payment
     */
    private function recordPayment(int $tenantId, int $invoiceId, array $paymentIntent): void
    {
        $invoice = $this->db->fetch(
            "SELECT * FROM invoices WHERE id = ? AND tenant_id = ?",
            [$invoiceId, $tenantId]
        );

        if (!$invoice) {
            return;
        }

        $amount = $paymentIntent['amount'] / 100; // Convert from cents

        // Check if payment already recorded
        $existing = $this->db->fetch(
            "SELECT id FROM payments WHERE stripe_payment_id = ? AND tenant_id = ?",
            [$paymentIntent['id'], $tenantId]
        );

        if ($existing) {
            // Update existing
            $this->db->update('payments', [
                'status' => 'completed',
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $existing['id']]);
        } else {
            // Create new payment
            $this->db->insert('payments', [
                'tenant_id' => $tenantId,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_method' => 'stripe',
                'status' => 'completed',
                'stripe_payment_id' => $paymentIntent['id'],
                'stripe_charge_id' => $paymentIntent['latest_charge'] ?? null,
                'payment_date' => date('Y-m-d'),
                'notes' => 'Paid via Stripe',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Update invoice
        $newPaidAmount = $invoice['paid_amount'] + $amount;
        $newStatus = $newPaidAmount >= $invoice['total_amount'] ? 'paid' : 'partial';

        $this->db->update('invoices', [
            'paid_amount' => $newPaidAmount,
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $invoiceId]);
    }

    /**
     * Handle checkout.session.completed (SaaS signup)
     */
    private function handleCheckoutSessionCompleted(array $session): void
    {
        // Only handle subscription checkouts
        if ($session['mode'] !== 'subscription') {
            return;
        }

        // Create new tenant via SubscriptionService
        require_once ROOT_PATH . '/app/Services/SubscriptionService.php';
        $subscriptionService = new \App\Services\SubscriptionService();

        try {
            $tenantId = $subscriptionService->handleCheckoutComplete($session);
            error_log("Stripe Webhook: Created tenant {$tenantId} from checkout session {$session['id']}");
        } catch (\Exception $e) {
            error_log("Stripe Webhook: Failed to create tenant: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle invoice.payment_failed for platform subscriptions
     * (This updates tenant subscription status)
     */
    private function handlePlatformInvoicePaymentFailed(array $invoice): void
    {
        // Get tenant by Stripe customer ID
        $tenant = $this->db->fetch(
            "SELECT id FROM tenants WHERE stripe_customer_id = ?",
            [$invoice['customer']]
        );

        if (!$tenant) {
            return;
        }

        require_once ROOT_PATH . '/app/Services/SubscriptionService.php';
        $subscriptionService = new \App\Services\SubscriptionService();
        $subscriptionService->handlePaymentFailed($tenant['id']);
    }

    /**
     * Handle invoice.paid for platform subscriptions
     */
    private function handlePlatformInvoicePaid(array $invoice): void
    {
        // Get tenant by Stripe customer ID
        $tenant = $this->db->fetch(
            "SELECT id FROM tenants WHERE stripe_customer_id = ?",
            [$invoice['customer']]
        );

        if (!$tenant) {
            return;
        }

        require_once ROOT_PATH . '/app/Services/SubscriptionService.php';
        $subscriptionService = new \App\Services\SubscriptionService();
        $subscriptionService->handlePaymentSucceeded($tenant['id']);
    }

    /**
     * Send JSON response
     */
    private function sendResponse(int $code, array $data): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
