<?php
/**
 * Admin Subscription Controller
 * Platform owner dashboard for managing all tenant subscriptions
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class AdminSubscriptionController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check if user is platform admin (developer)
     */
    private function requireAdmin(): bool
    {
        // Check if accessing from dev console with dev session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['dev_user'])) {
            $this->error('Unauthorized - Admin access required', 403);
            return false;
        }

        return true;
    }

    /**
     * GET /api/admin/subscriptions
     * List all tenant subscriptions with filters
     */
    public function index(): void
    {
        if (!$this->requireAdmin())
            return;

        try {
            $page = (int) ($_GET['page'] ?? 1);
            $limit = (int) ($_GET['limit'] ?? 25);
            $offset = ($page - 1) * $limit;

            $status = $_GET['status'] ?? null;
            $plan = $_GET['plan'] ?? null;
            $search = $_GET['search'] ?? null;

            // Build query
            $where = ['1=1'];
            $params = [];

            if ($status) {
                $where[] = 't.subscription_status = ?';
                $params[] = $status;
            }

            if ($plan) {
                $where[] = 'sp.slug = ?';
                $params[] = $plan;
            }

            if ($search) {
                $where[] = '(t.name LIKE ? OR t.email LIKE ? OR t.subdomain LIKE ?)';
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $whereClause = implode(' AND ', $where);

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM tenants t 
                         LEFT JOIN subscription_plans sp ON t.subscription_plan_id = sp.id
                         WHERE {$whereClause}";
            $total = $this->db->fetch($countSql, $params)['total'];

            // Get subscriptions
            $sql = "SELECT 
                        t.id,
                        t.name,
                        t.subdomain,
                        t.email,
                        t.status as tenant_status,
                        t.subscription_status,
                        t.subscription_started_at,
                        t.subscription_ends_at,
                        t.trial_ends_at,
                        t.user_limit,
                        t.extra_seats,
                        t.stripe_customer_id,
                        t.stripe_subscription_id,
                        t.created_at,
                        sp.name as plan_name,
                        sp.slug as plan_slug,
                        sp.price_monthly,
                        (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id AND u.status = 'active') as user_count
                    FROM tenants t
                    LEFT JOIN subscription_plans sp ON t.subscription_plan_id = sp.id
                    WHERE {$whereClause}
                    ORDER BY t.created_at DESC
                    LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;

            $subscriptions = $this->db->fetchAll($sql, $params);

            $this->json([
                'success' => true,
                'data' => $subscriptions,
                'meta' => [
                    'total' => (int) $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit),
                ],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/subscriptions/stats
     * Dashboard statistics
     */
    public function stats(): void
    {
        if (!$this->requireAdmin())
            return;

        try {
            // Total tenants
            $totalTenants = $this->db->fetch("SELECT COUNT(*) as count FROM tenants")['count'];

            // Active subscriptions
            $activeSubscriptions = $this->db->fetch(
                "SELECT COUNT(*) as count FROM tenants WHERE subscription_status IN ('active', 'trialing')"
            )['count'];

            // Subscriptions by status
            $byStatus = $this->db->fetchAll(
                "SELECT subscription_status, COUNT(*) as count 
                 FROM tenants 
                 WHERE subscription_status IS NOT NULL
                 GROUP BY subscription_status"
            );

            // Subscriptions by plan
            $byPlan = $this->db->fetchAll(
                "SELECT sp.name, sp.slug, COUNT(*) as count, SUM(sp.price_monthly) as mrr
                 FROM tenants t
                 JOIN subscription_plans sp ON t.subscription_plan_id = sp.id
                 WHERE t.subscription_status IN ('active', 'trialing')
                 GROUP BY sp.id, sp.name, sp.slug"
            );

            // Monthly Recurring Revenue (MRR)
            $mrr = $this->db->fetch(
                "SELECT COALESCE(SUM(sp.price_monthly), 0) as mrr
                 FROM tenants t
                 JOIN subscription_plans sp ON t.subscription_plan_id = sp.id
                 WHERE t.subscription_status = 'active'"
            )['mrr'];

            // Trial ending soon (next 7 days)
            $trialsEndingSoon = $this->db->fetch(
                "SELECT COUNT(*) as count FROM tenants 
                 WHERE subscription_status = 'trialing' 
                 AND trial_ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"
            )['count'];

            // Past due count
            $pastDue = $this->db->fetch(
                "SELECT COUNT(*) as count FROM tenants WHERE subscription_status = 'past_due'"
            )['count'];

            // New signups this month
            $newThisMonth = $this->db->fetch(
                "SELECT COUNT(*) as count FROM tenants 
                 WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
            )['count'];

            // Churn this month (canceled)
            $churnThisMonth = $this->db->fetch(
                "SELECT COUNT(*) as count FROM subscription_history 
                 WHERE event_type = 'canceled' 
                 AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
            )['count'];

            $this->json([
                'success' => true,
                'data' => [
                    'total_tenants' => (int) $totalTenants,
                    'active_subscriptions' => (int) $activeSubscriptions,
                    'mrr' => (float) $mrr,
                    'trials_ending_soon' => (int) $trialsEndingSoon,
                    'past_due' => (int) $pastDue,
                    'new_this_month' => (int) $newThisMonth,
                    'churn_this_month' => (int) $churnThisMonth,
                    'by_status' => $byStatus,
                    'by_plan' => $byPlan,
                ],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/subscriptions/{id}
     * Get single subscription details
     */
    public function show(int $id): void
    {
        if (!$this->requireAdmin())
            return;

        try {
            $tenant = $this->db->fetch(
                "SELECT t.*, 
                        sp.name as plan_name, sp.slug as plan_slug, sp.price_monthly,
                        sp.features as plan_features
                 FROM tenants t
                 LEFT JOIN subscription_plans sp ON t.subscription_plan_id = sp.id
                 WHERE t.id = ?",
                [$id]
            );

            if (!$tenant) {
                $this->error('Tenant not found', 404);
                return;
            }

            // Get user count
            $userCount = $this->db->fetch(
                "SELECT COUNT(*) as count FROM users WHERE tenant_id = ? AND status = 'active'",
                [$id]
            )['count'];

            // Get subscription history
            $history = $this->db->fetchAll(
                "SELECT sh.*, 
                        fp.name as from_plan_name,
                        tp.name as to_plan_name
                 FROM subscription_history sh
                 LEFT JOIN subscription_plans fp ON sh.from_plan_id = fp.id
                 LEFT JOIN subscription_plans tp ON sh.to_plan_id = tp.id
                 WHERE sh.tenant_id = ?
                 ORDER BY sh.created_at DESC
                 LIMIT 20",
                [$id]
            );

            $tenant['user_count'] = (int) $userCount;
            $tenant['history'] = $history;

            $this->json([
                'success' => true,
                'data' => $tenant,
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/admin/subscriptions/{id}/status
     * Update tenant status (active, suspended)
     */
    public function updateStatus(int $id): void
    {
        if (!$this->requireAdmin())
            return;

        try {
            $data = $this->getJsonInput();
            $status = $data['status'] ?? null;
            $reason = $data['reason'] ?? null;

            if (!in_array($status, ['active', 'suspended'])) {
                $this->error('Invalid status', 400);
                return;
            }

            $this->db->update('tenants', [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id]);

            // Log the action
            $this->db->insert('subscription_history', [
                'tenant_id' => $id,
                'event_type' => $status === 'suspended' ? 'suspended' : 'unsuspended',
                'notes' => $reason,
            ]);

            $this->json([
                'success' => true,
                'message' => "Tenant {$status} successfully",
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/admin/subscriptions/{id}/plan
     * Change tenant's plan (admin override)
     */
    public function updatePlan(int $id): void
    {
        if (!$this->requireAdmin())
            return;

        try {
            $data = $this->getJsonInput();
            $planSlug = $data['plan_slug'] ?? null;

            if (!$planSlug) {
                $this->error('Plan is required', 400);
                return;
            }

            $plan = $this->db->fetch(
                "SELECT * FROM subscription_plans WHERE slug = ?",
                [$planSlug]
            );

            if (!$plan) {
                $this->error('Plan not found', 404);
                return;
            }

            $tenant = $this->db->fetch("SELECT * FROM tenants WHERE id = ?", [$id]);
            $oldPlanId = $tenant['subscription_plan_id'];

            $this->db->update('tenants', [
                'subscription_plan_id' => $plan['id'],
                'user_limit' => $plan['user_limit'],
                'plan' => $plan['slug'],
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id]);

            // Log the action
            $this->db->insert('subscription_history', [
                'tenant_id' => $id,
                'event_type' => $plan['id'] > $oldPlanId ? 'upgraded' : 'downgraded',
                'from_plan_id' => $oldPlanId,
                'to_plan_id' => $plan['id'],
                'notes' => 'Admin override: ' . ($data['reason'] ?? 'No reason provided'),
            ]);

            $this->json([
                'success' => true,
                'message' => "Plan updated to {$plan['name']}",
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/subscriptions/recent-activity
     * Recent subscription activity
     */
    public function recentActivity(): void
    {
        if (!$this->requireAdmin())
            return;

        try {
            $activity = $this->db->fetchAll(
                "SELECT sh.*, t.name as tenant_name, t.subdomain,
                        fp.name as from_plan_name, tp.name as to_plan_name
                 FROM subscription_history sh
                 JOIN tenants t ON sh.tenant_id = t.id
                 LEFT JOIN subscription_plans fp ON sh.from_plan_id = fp.id
                 LEFT JOIN subscription_plans tp ON sh.to_plan_id = tp.id
                 ORDER BY sh.created_at DESC
                 LIMIT 50"
            );

            $this->json([
                'success' => true,
                'data' => $activity,
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
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
}
