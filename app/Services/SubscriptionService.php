<?php
/**
 * Subscription Service
 * Core logic for SaaS subscription management
 */

namespace App\Services;

use App\Core\Database;

class SubscriptionService
{
    private Database $db;
    private StripeService $stripe;

    // Configuration
    private const TRIAL_DAYS = 14;
    private const GRACE_PERIOD_DAYS = 7;
    private const DATA_RETENTION_DAYS = 30;
    private const EXTRA_SEAT_PRICE = 14.00;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->stripe = new StripeService();
        $this->stripe->initializePlatform();
    }

    // ==========================================
    // PLAN MANAGEMENT
    // ==========================================

    /**
     * Get all active subscription plans
     */
    public function getPlans(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM subscription_plans 
             WHERE status = 'active' AND slug != 'extra-seat'
             ORDER BY sort_order ASC"
        );
    }

    /**
     * Get a specific plan by slug or ID
     */
    public function getPlan(string $slugOrId): ?array
    {
        $column = is_numeric($slugOrId) ? 'id' : 'slug';
        return $this->db->fetch(
            "SELECT * FROM subscription_plans WHERE {$column} = ?",
            [$slugOrId]
        );
    }

    /**
     * Get extra seat plan
     */
    public function getExtraSeatPlan(): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM subscription_plans WHERE slug = 'extra-seat'"
        );
    }

    /**
     * Get Stripe Price ID for a plan from environment variables
     * This allows easy switching between test and live mode
     */
    public function getStripePriceId(string $planSlug): ?string
    {
        $envKey = 'STRIPE_PRICE_' . strtoupper(str_replace('-', '_', $planSlug));
        $priceId = getenv($envKey);

        // Fallback to database if not in env
        if (!$priceId) {
            $plan = $this->getPlan($planSlug);
            $priceId = $plan['stripe_price_id'] ?? null;
        }

        return $priceId ?: null;
    }

    // ==========================================
    // CHECKOUT & SIGNUP
    // ==========================================

    /**
     * Create a Stripe Checkout session for new tenant signup
     */
    public function createCheckoutSession(array $data): array
    {
        $plan = $this->getPlan($data['plan_slug']);

        if (!$plan) {
            throw new \Exception('Invalid plan selected');
        }

        // Get price ID from env vars (preferred) or database (fallback)
        $stripePriceId = $this->getStripePriceId($plan['slug']);

        if (!$stripePriceId) {
            throw new \Exception('Plan not configured in Stripe. Please contact support.');
        }

        $baseUrl = getenv('APP_URL') ?: 'https://buildflow-traffio.com';

        // Create Stripe checkout session
        $checkoutParams = [
            'mode' => 'subscription',
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price' => $stripePriceId,
                    'quantity' => 1,
                ]
            ],
            'customer_email' => $data['email'],
            'success_url' => $baseUrl . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $baseUrl . '/?checkout=cancelled',
            'metadata' => [
                'company_name' => $data['company_name'],
                'plan_id' => $plan['id'],
                'admin_first_name' => $data['first_name'] ?? '',
                'admin_last_name' => $data['last_name'] ?? '',
            ],
            'subscription_data' => [
                'metadata' => [
                    'plan_slug' => $plan['slug'],
                ],
            ],
            'allow_promotion_codes' => true,
        ];

        // Add trial period if plan has trial days
        if ($plan['trial_days'] > 0) {
            $checkoutParams['subscription_data']['trial_period_days'] = $plan['trial_days'];
        }

        $session = $this->stripe->createCheckoutSession($checkoutParams);

        if (!$session || isset($session['error'])) {
            throw new \Exception($session['error']['message'] ?? 'Failed to create checkout session');
        }

        // Store pending signup
        $this->db->insert('pending_signups', [
            'checkout_session_id' => $session['id'],
            'company_name' => $data['company_name'],
            'email' => $data['email'],
            'plan_id' => $plan['id'],
            'admin_first_name' => $data['first_name'] ?? null,
            'admin_last_name' => $data['last_name'] ?? null,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
        ]);

        return [
            'checkout_url' => $session['url'],
            'session_id' => $session['id'],
        ];
    }

    /**
     * Handle successful checkout session completion
     */
    public function handleCheckoutComplete(array $session): int
    {
        // Get pending signup
        $pending = $this->db->fetch(
            "SELECT * FROM pending_signups WHERE checkout_session_id = ? AND status = 'pending'",
            [$session['id']]
        );

        if (!$pending) {
            // Try to get info from session metadata
            $pending = [
                'company_name' => $session['metadata']['company_name'] ?? 'My Company',
                'email' => $session['customer_details']['email'] ?? $session['customer_email'],
                'plan_id' => $session['metadata']['plan_id'] ?? 1,
                'admin_first_name' => $session['metadata']['admin_first_name'] ?? '',
                'admin_last_name' => $session['metadata']['admin_last_name'] ?? '',
            ];
        }

        $plan = $this->getPlan($pending['plan_id']);

        // Generate subdomain from company name
        $subdomain = $this->generateSubdomain($pending['company_name']);

        // Determine subscription status
        $subscriptionStatus = 'active';
        $trialEndsAt = null;

        if (isset($session['subscription'])) {
            $subscription = $this->stripe->getSubscription($session['subscription']);
            if ($subscription && $subscription['status'] === 'trialing') {
                $subscriptionStatus = 'trialing';
                $trialEndsAt = date('Y-m-d H:i:s', $subscription['trial_end']);
            }
        }

        // Create tenant
        $tenantId = $this->db->insert('tenants', [
            'name' => $pending['company_name'],
            'subdomain' => $subdomain,
            'email' => $pending['email'],
            'stripe_customer_id' => $session['customer'],
            'stripe_subscription_id' => $session['subscription'],
            'subscription_plan_id' => $plan['id'],
            'subscription_status' => $subscriptionStatus,
            'subscription_started_at' => date('Y-m-d H:i:s'),
            'trial_ends_at' => $trialEndsAt,
            'user_limit' => $plan['user_limit'],
            'billing_email' => $pending['email'],
            'plan' => $plan['slug'],
            'status' => 'active',
        ]);

        // Create admin user
        $this->createAdminUser($tenantId, $pending);

        // Copy system roles to tenant
        $this->copySystemRoles($tenantId);

        // Create default settings
        $this->createDefaultSettings($tenantId, $pending['company_name']);

        // Mark pending signup as completed
        if (isset($pending['id'])) {
            $this->db->update('pending_signups', [
                'status' => 'completed',
            ], ['id' => $pending['id']]);
        }

        // Log subscription event
        $this->logEvent($tenantId, 'created', null, $plan['id'], null, $plan['price_monthly']);

        return $tenantId;
    }

    /**
     * Generate unique subdomain from company name
     */
    private function generateSubdomain(string $companyName): string
    {
        // Clean and format
        $base = strtolower(trim($companyName));
        $base = preg_replace('/[^a-z0-9]+/', '-', $base);
        $base = preg_replace('/-+/', '-', $base);
        $base = trim($base, '-');
        $base = substr($base, 0, 30);

        if (empty($base)) {
            $base = 'company';
        }

        // Check for uniqueness
        $subdomain = $base;
        $counter = 1;

        while ($this->subdomainExists($subdomain)) {
            $subdomain = $base . '-' . $counter;
            $counter++;
        }

        return $subdomain;
    }

    /**
     * Check if subdomain exists
     */
    private function subdomainExists(string $subdomain): bool
    {
        $existing = $this->db->fetch(
            "SELECT id FROM tenants WHERE subdomain = ?",
            [$subdomain]
        );
        return $existing !== null;
    }

    /**
     * Create admin user for new tenant
     */
    private function createAdminUser(int $tenantId, array $data): int
    {
        // Get admin role
        $adminRole = $this->db->fetch(
            "SELECT id FROM roles WHERE tenant_id = ? AND name = 'admin'",
            [$tenantId]
        );

        if (!$adminRole) {
            // Create admin role if not exists
            $roleId = $this->db->insert('roles', [
                'tenant_id' => $tenantId,
                'name' => 'admin',
                'display_name' => 'Administrator',
                'permissions' => '["*"]',
                'is_system' => true,
            ]);
        } else {
            $roleId = $adminRole['id'];
        }

        // Generate temporary password
        $tempPassword = bin2hex(random_bytes(8));

        $userId = $this->db->insert('users', [
            'tenant_id' => $tenantId,
            'role_id' => $roleId,
            'first_name' => $data['admin_first_name'] ?: 'Admin',
            'last_name' => $data['admin_last_name'] ?: 'User',
            'email' => $data['email'],
            'password' => password_hash($tempPassword, PASSWORD_DEFAULT),
            'status' => 'active',
        ]);

        // TODO: Send welcome email with login credentials
        // For now, user will need to use password reset

        return $userId;
    }

    /**
     * Copy system roles to new tenant
     */
    private function copySystemRoles(int $tenantId): void
    {
        $systemRoles = $this->db->fetchAll(
            "SELECT * FROM roles WHERE tenant_id IS NULL AND is_system = 1"
        );

        foreach ($systemRoles as $role) {
            // Check if already exists
            $existing = $this->db->fetch(
                "SELECT id FROM roles WHERE tenant_id = ? AND name = ?",
                [$tenantId, $role['name']]
            );

            if (!$existing) {
                $this->db->insert('roles', [
                    'tenant_id' => $tenantId,
                    'name' => $role['name'],
                    'display_name' => $role['display_name'],
                    'permissions' => $role['permissions'],
                    'is_system' => true,
                ]);
            }
        }
    }

    /**
     * Create default settings for new tenant
     */
    private function createDefaultSettings(int $tenantId, string $companyName): void
    {
        $defaults = [
            'company_name' => $companyName,
            'currency' => 'USD',
            'timezone' => 'America/New_York',
            'date_format' => 'm/d/Y',
            'fiscal_year_start' => '01-01',
        ];

        foreach ($defaults as $key => $value) {
            $this->db->insert('settings', [
                'tenant_id' => $tenantId,
                'key' => $key,
                'value' => $value,
            ]);
        }
    }

    // ==========================================
    // SUBSCRIPTION MANAGEMENT
    // ==========================================

    /**
     * Get tenant's current subscription details
     */
    public function getTenantSubscription(int $tenantId): ?array
    {
        $tenant = $this->db->fetch(
            "SELECT t.*, sp.name as plan_name, sp.slug as plan_slug, sp.price_monthly,
                    sp.features as plan_features
             FROM tenants t
             LEFT JOIN subscription_plans sp ON t.subscription_plan_id = sp.id
             WHERE t.id = ?",
            [$tenantId]
        );

        if (!$tenant) {
            return null;
        }

        // Add usage info
        $usage = $this->getUserUsage($tenantId);

        return [
            'tenant_id' => $tenant['id'],
            'plan' => [
                'id' => $tenant['subscription_plan_id'],
                'name' => $tenant['plan_name'],
                'slug' => $tenant['plan_slug'],
                'price' => (float) $tenant['price_monthly'],
                'features' => json_decode($tenant['plan_features'] ?? '{}', true),
            ],
            'status' => $tenant['subscription_status'],
            'stripe_subscription_id' => $tenant['stripe_subscription_id'],
            'stripe_customer_id' => $tenant['stripe_customer_id'],
            'started_at' => $tenant['subscription_started_at'],
            'ends_at' => $tenant['subscription_ends_at'],
            'trial_ends_at' => $tenant['trial_ends_at'],
            'is_trialing' => $tenant['subscription_status'] === 'trialing',
            'is_active' => in_array($tenant['subscription_status'], ['active', 'trialing']),
            'usage' => $usage,
        ];
    }

    /**
     * Get user seat usage for tenant
     */
    public function getUserUsage(int $tenantId): array
    {
        $tenant = $this->db->fetch(
            "SELECT user_limit, extra_seats FROM tenants WHERE id = ?",
            [$tenantId]
        );

        $activeUsers = $this->db->fetch(
            "SELECT COUNT(*) as count FROM users WHERE tenant_id = ? AND status = 'active'",
            [$tenantId]
        );

        $used = (int) $activeUsers['count'];
        $limit = ($tenant['user_limit'] ?? 3) + ($tenant['extra_seats'] ?? 0);

        return [
            'used' => $used,
            'limit' => $limit,
            'available' => max(0, $limit - $used),
            'percentage' => $limit > 0 ? round(($used / $limit) * 100) : 0,
            'extra_seats' => (int) ($tenant['extra_seats'] ?? 0),
        ];
    }

    /**
     * Check if tenant can add more users
     */
    public function canAddUser(int $tenantId): bool
    {
        $usage = $this->getUserUsage($tenantId);
        return $usage['available'] > 0;
    }

    /**
     * Upgrade tenant to a new plan
     */
    public function upgradePlan(int $tenantId, string $newPlanSlug): bool
    {
        $tenant = $this->db->fetch("SELECT * FROM tenants WHERE id = ?", [$tenantId]);
        $newPlan = $this->getPlan($newPlanSlug);

        if (!$tenant || !$newPlan || !$tenant['stripe_subscription_id']) {
            return false;
        }

        // Get price ID from env vars (preferred) or database (fallback)
        $stripePriceId = $this->getStripePriceId($newPlan['slug']);

        if (!$stripePriceId) {
            return false;
        }

        // Update subscription in Stripe
        $result = $this->stripe->updateSubscription(
            $tenant['stripe_subscription_id'],
            $stripePriceId
        );

        if (!$result || isset($result['error'])) {
            return false;
        }

        $oldPlanId = $tenant['subscription_plan_id'];

        // Update tenant
        $this->db->update('tenants', [
            'subscription_plan_id' => $newPlan['id'],
            'user_limit' => $newPlan['user_limit'],
            'plan' => $newPlan['slug'],
        ], ['id' => $tenantId]);

        // Log event
        $this->logEvent($tenantId, 'upgraded', $oldPlanId, $newPlan['id']);

        return true;
    }

    /**
     * Cancel tenant subscription
     */
    public function cancelSubscription(int $tenantId, bool $atPeriodEnd = true): bool
    {
        $tenant = $this->db->fetch("SELECT * FROM tenants WHERE id = ?", [$tenantId]);

        if (!$tenant || !$tenant['stripe_subscription_id']) {
            return false;
        }

        $result = $this->stripe->cancelSubscription(
            $tenant['stripe_subscription_id'],
            $atPeriodEnd
        );

        if (!$result || isset($result['error'])) {
            return false;
        }

        $endsAt = $atPeriodEnd && isset($result['current_period_end'])
            ? date('Y-m-d H:i:s', $result['current_period_end'])
            : date('Y-m-d H:i:s');

        $this->db->update('tenants', [
            'subscription_status' => 'canceled',
            'subscription_ends_at' => $endsAt,
        ], ['id' => $tenantId]);

        $this->logEvent($tenantId, 'canceled', $tenant['subscription_plan_id'], null);

        return true;
    }

    /**
     * Handle payment failure
     */
    public function handlePaymentFailed(int $tenantId): void
    {
        $gracePeriodEnds = date('Y-m-d H:i:s', strtotime('+' . self::GRACE_PERIOD_DAYS . ' days'));

        $this->db->update('tenants', [
            'subscription_status' => 'past_due',
            'grace_period_ends_at' => $gracePeriodEnds,
        ], ['id' => $tenantId]);

        $this->logEvent($tenantId, 'payment_failed');

        // TODO: Send payment failed email
    }

    /**
     * Handle payment success (resumes from past_due)
     */
    public function handlePaymentSucceeded(int $tenantId): void
    {
        $tenant = $this->db->fetch("SELECT * FROM tenants WHERE id = ?", [$tenantId]);

        if ($tenant && $tenant['subscription_status'] === 'past_due') {
            $this->db->update('tenants', [
                'subscription_status' => 'active',
                'grace_period_ends_at' => null,
            ], ['id' => $tenantId]);

            $this->logEvent($tenantId, 'payment_succeeded');
        }
    }

    /**
     * Suspend tenant (after grace period)
     */
    public function suspendTenant(int $tenantId): void
    {
        $this->db->update('tenants', [
            'subscription_status' => 'suspended',
            'status' => 'suspended',
        ], ['id' => $tenantId]);

        $this->logEvent($tenantId, 'suspended');

        // TODO: Send suspension email
    }

    /**
     * Check and suspend tenants past grace period
     */
    public function processSuspensions(): int
    {
        $pastDueTenants = $this->db->fetchAll(
            "SELECT id FROM tenants 
             WHERE subscription_status = 'past_due' 
             AND grace_period_ends_at < NOW()"
        );

        $count = 0;
        foreach ($pastDueTenants as $tenant) {
            $this->suspendTenant($tenant['id']);
            $count++;
        }

        return $count;
    }

    // ==========================================
    // STRIPE CUSTOMER PORTAL
    // ==========================================

    /**
     * Create Stripe Customer Portal session
     */
    public function createPortalSession(int $tenantId): ?string
    {
        $tenant = $this->db->fetch("SELECT * FROM tenants WHERE id = ?", [$tenantId]);

        if (!$tenant || !$tenant['stripe_customer_id']) {
            return null;
        }

        $baseUrl = getenv('APP_URL') ?: 'https://buildflow-traffio.com';

        $session = $this->stripe->createPortalSession(
            $tenant['stripe_customer_id'],
            $baseUrl . '/settings/subscription'
        );

        return $session['url'] ?? null;
    }

    // ==========================================
    // LOGGING
    // ==========================================

    /**
     * Log subscription event
     */
    private function logEvent(
        int $tenantId,
        string $eventType,
        ?int $fromPlanId = null,
        ?int $toPlanId = null,
        ?string $stripeEventId = null,
        ?float $amount = null,
        ?string $notes = null
    ): void {
        $this->db->insert('subscription_history', [
            'tenant_id' => $tenantId,
            'event_type' => $eventType,
            'from_plan_id' => $fromPlanId,
            'to_plan_id' => $toPlanId,
            'stripe_event_id' => $stripeEventId,
            'amount' => $amount,
            'notes' => $notes,
        ]);
    }

    /**
     * Get subscription history for tenant
     */
    public function getHistory(int $tenantId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT sh.*, 
                    fp.name as from_plan_name,
                    tp.name as to_plan_name
             FROM subscription_history sh
             LEFT JOIN subscription_plans fp ON sh.from_plan_id = fp.id
             LEFT JOIN subscription_plans tp ON sh.to_plan_id = tp.id
             WHERE sh.tenant_id = ?
             ORDER BY sh.created_at DESC
             LIMIT ?",
            [$tenantId, $limit]
        );
    }

    // ==========================================
    // FEATURE ACCESS
    // ==========================================

    /**
     * Check if tenant has access to a feature
     */
    public function hasFeature(int $tenantId, string $feature): bool
    {
        $subscription = $this->getTenantSubscription($tenantId);

        if (!$subscription || !$subscription['is_active']) {
            return false;
        }

        $features = $subscription['plan']['features'] ?? [];
        return isset($features[$feature]) && $features[$feature] === true;
    }
}
