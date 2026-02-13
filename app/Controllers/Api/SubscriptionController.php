<?php
/**
 * Subscription Controller
 * Handles public subscription endpoints (plans, checkout)
 * and authenticated subscription management endpoints
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\SubscriptionService;

class SubscriptionController extends Controller
{
    private SubscriptionService $subscriptionService;

    public function __construct()
    {
        parent::__construct();
        $this->subscriptionService = new SubscriptionService();
    }

    /**
     * Helper to send JSON response
     */
    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // ==========================================
    // PUBLIC ENDPOINTS
    // ==========================================

    /**
     * GET /api/plans
     * List all available subscription plans
     */
    public function plans(): void
    {
        try {
            $plans = $this->subscriptionService->getPlans();

            // Format plans for frontend
            $formattedPlans = array_map(function ($plan) {
                return [
                    'id' => $plan['id'],
                    'name' => $plan['name'],
                    'slug' => $plan['slug'],
                    'description' => $plan['description'],
                    'price' => (float) $plan['price_monthly'],
                    'price_formatted' => '$' . number_format($plan['price_monthly'], 0),
                    'user_limit' => $plan['user_limit'],
                    'features' => json_decode($plan['features'] ?? '{}', true),
                    'is_popular' => (bool) $plan['is_popular'],
                    'trial_days' => $plan['trial_days'],
                ];
            }, $plans);

            $this->json([
                'success' => true,
                'data' => $formattedPlans,
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/checkout
     * Create a Stripe Checkout session for new tenant signup
     */
    public function checkout(): void
    {
        try {
            $data = $this->getJsonInput();

            // Validate required fields
            if (empty($data['plan_slug'])) {
                $this->error('Plan is required', 400);
                return;
            }

            if (empty($data['company_name'])) {
                $this->error('Company name is required', 400);
                return;
            }

            if (empty($data['email'])) {
                $this->error('Email is required', 400);
                return;
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->error('Invalid email address', 400);
                return;
            }

            // Check if email already exists as a tenant
            $existingTenant = $this->db->fetch(
                "SELECT id FROM tenants WHERE email = ?",
                [$data['email']]
            );

            if ($existingTenant) {
                $this->error('An account with this email already exists. Please login instead.', 400);
                return;
            }

            $result = $this->subscriptionService->createCheckoutSession($data);

            $this->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            error_log('Checkout error: ' . $e->getMessage());
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/checkout/verify
     * Verify a checkout session and get status
     */
    public function verifyCheckout(): void
    {
        try {
            $sessionId = $_GET['session_id'] ?? null;

            if (!$sessionId) {
                $this->error('Session ID is required', 400);
                return;
            }

            $stripe = new \App\Services\StripeService();
            $stripe->initializePlatform();

            $session = $stripe->getCheckoutSession($sessionId);

            if (!$session || isset($session['error'])) {
                $this->error('Invalid session', 400);
                return;
            }

            // Get the tenant if already created
            $tenant = null;
            if ($session['payment_status'] === 'paid' || $session['status'] === 'complete') {
                $tenant = $this->db->fetch(
                    "SELECT subdomain, name FROM tenants WHERE stripe_customer_id = ?",
                    [$session['customer']]
                );
            }

            $this->json([
                'success' => true,
                'data' => [
                    'status' => $session['status'],
                    'payment_status' => $session['payment_status'],
                    'customer_email' => $session['customer_details']['email'] ?? null,
                    'tenant' => $tenant,
                ],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    // ==========================================
    // AUTHENTICATED ENDPOINTS (Tenant)
    // ==========================================

    /**
     * GET /api/my-subscription
     * Get current tenant's subscription details
     */
    public function mySubscription(): void
    {
        try {
            $tenantId = $this->db->getTenantId();

            if (!$tenantId) {
                $this->error('Unauthorized', 401);
                return;
            }

            $subscription = $this->subscriptionService->getTenantSubscription($tenantId);

            if (!$subscription) {
                $this->error('No subscription found', 404);
                return;
            }

            // Get available plans for upgrade
            $allPlans = $this->subscriptionService->getPlans();
            $currentPlanId = $subscription['plan']['id'];

            $upgradePlans = array_filter($allPlans, function ($plan) use ($currentPlanId) {
                return $plan['id'] > $currentPlanId;
            });

            $this->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription,
                    'upgrade_options' => array_values($upgradePlans),
                ],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/my-subscription/usage
     * Get current seat usage
     */
    public function usage(): void
    {
        try {
            $tenantId = $this->db->getTenantId();

            if (!$tenantId) {
                $this->error('Unauthorized', 401);
                return;
            }

            $usage = $this->subscriptionService->getUserUsage($tenantId);

            $this->json([
                'success' => true,
                'data' => $usage,
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/my-subscription/portal
     * Create a Stripe Customer Portal session for billing management
     */
    public function portal(): void
    {
        try {
            $tenantId = $this->db->getTenantId();

            if (!$tenantId) {
                $this->error('Unauthorized', 401);
                return;
            }

            $portalUrl = $this->subscriptionService->createPortalSession($tenantId);

            if (!$portalUrl) {
                $this->error('Unable to create portal session', 500);
                return;
            }

            $this->json([
                'success' => true,
                'data' => [
                    'url' => $portalUrl,
                ],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/my-subscription/upgrade
     * Upgrade to a new plan
     */
    public function upgrade(): void
    {
        try {
            $tenantId = $this->db->getTenantId();

            if (!$tenantId) {
                $this->error('Unauthorized', 401);
                return;
            }

            $data = $this->getJsonInput();

            if (empty($data['plan_slug'])) {
                $this->error('Plan is required', 400);
                return;
            }

            $success = $this->subscriptionService->upgradePlan($tenantId, $data['plan_slug']);

            if (!$success) {
                $this->error('Failed to upgrade plan', 500);
                return;
            }

            $this->json([
                'success' => true,
                'message' => 'Plan upgraded successfully',
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/my-subscription/cancel
     * Cancel subscription (at period end)
     */
    public function cancel(): void
    {
        try {
            $tenantId = $this->db->getTenantId();

            if (!$tenantId) {
                $this->error('Unauthorized', 401);
                return;
            }

            $data = $this->getJsonInput();
            $immediate = isset($data['immediate']) && $data['immediate'] === true;

            $success = $this->subscriptionService->cancelSubscription($tenantId, !$immediate);

            if (!$success) {
                $this->error('Failed to cancel subscription', 500);
                return;
            }

            $this->json([
                'success' => true,
                'message' => $immediate
                    ? 'Subscription canceled immediately'
                    : 'Subscription will be canceled at the end of the billing period',
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/my-subscription/history
     * Get subscription history
     */
    public function history(): void
    {
        try {
            $tenantId = $this->db->getTenantId();

            if (!$tenantId) {
                $this->error('Unauthorized', 401);
                return;
            }

            $history = $this->subscriptionService->getHistory($tenantId);

            $this->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Check if tenant can add a new user (for user creation flow)
     * GET /api/my-subscription/can-add-user
     */
    public function canAddUser(): void
    {
        try {
            $tenantId = $this->db->getTenantId();

            if (!$tenantId) {
                $this->error('Unauthorized', 401);
                return;
            }

            $canAdd = $this->subscriptionService->canAddUser($tenantId);
            $usage = $this->subscriptionService->getUserUsage($tenantId);

            $this->json([
                'success' => true,
                'data' => [
                    'can_add' => $canAdd,
                    'usage' => $usage,
                    'message' => $canAdd
                        ? null
                        : 'User limit reached. Please upgrade your plan to add more users.',
                ],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }
}
