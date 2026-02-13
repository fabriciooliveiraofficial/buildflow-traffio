<?php
/**
 * Email Bounce Handler
 * 
 * Processes bounce webhooks and manages bounced email addresses
 */

namespace App\Services\Email;

use App\Core\Database;

class BounceService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Process bounce webhook from email provider
     * Supports generic webhook format
     */
    public function processWebhook(array $payload): array
    {
        // Extract data from webhook (generic format)
        $email = $payload['email'] ?? $payload['recipient'] ?? $payload['Email'] ?? null;
        $bounceType = $payload['bounce_type'] ?? $payload['type'] ?? 'hard';
        $reason = $payload['reason'] ?? $payload['error'] ?? $payload['description'] ?? 'Unknown';
        $timestamp = $payload['timestamp'] ?? $payload['time'] ?? date('Y-m-d H:i:s');

        if (!$email) {
            return ['success' => false, 'error' => 'No email address in webhook'];
        }

        // Find the email log entry
        $log = $this->db->fetch(
            "SELECT * FROM email_logs WHERE to_email = ? ORDER BY sent_at DESC LIMIT 1",
            [$email]
        );

        if ($log) {
            // Update log status to bounced
            $this->db->update('email_logs', [
                'status' => 'bounced',
                'error_message' => $reason,
            ], ['id' => $log['id']]);

            $tenantId = $log['tenant_id'];
        } else {
            // Try to find tenant from email settings
            $tenantId = null;
        }

        // Add to bounce list (hard bounces only)
        if ($bounceType === 'hard' || $bounceType === 'permanent') {
            $this->addToBounceList($email, $tenantId, $reason);
        }

        return ['success' => true, 'message' => 'Bounce processed'];
    }

    /**
     * Add email to bounce list
     */
    public function addToBounceList(string $email, ?int $tenantId, string $reason = ''): void
    {
        // Check if already in list
        $existing = $this->db->fetch(
            "SELECT id FROM email_bounces WHERE email = ? AND (tenant_id = ? OR tenant_id IS NULL)",
            [$email, $tenantId]
        );

        if ($existing) {
            // Update bounce count
            $this->db->query(
                "UPDATE email_bounces SET bounce_count = bounce_count + 1, last_bounce_at = ? WHERE id = ?",
                [date('Y-m-d H:i:s'), $existing['id']]
            );
            return;
        }

        // Add new bounce record
        $this->db->insert('email_bounces', [
            'tenant_id' => $tenantId,
            'email' => $email,
            'bounce_count' => 1,
            'reason' => $reason,
            'first_bounce_at' => date('Y-m-d H:i:s'),
            'last_bounce_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if email is bounced
     */
    public function isBounced(string $email): bool
    {
        $tenantId = $this->db->getTenantId();

        $result = $this->db->fetch(
            "SELECT id FROM email_bounces WHERE email = ? AND (tenant_id = ? OR tenant_id IS NULL)",
            [$email, $tenantId]
        );

        return $result !== null;
    }

    /**
     * Remove email from bounce list (manual cleanup)
     */
    public function removeFromBounceList(string $email): bool
    {
        $tenantId = $this->db->getTenantId();

        $this->db->delete('email_bounces', [
            'email' => $email,
            'tenant_id' => $tenantId,
        ]);

        return true;
    }

    /**
     * Get bounced emails for tenant
     */
    public function getBounceList(): array
    {
        $tenantId = $this->db->getTenantId();

        return $this->db->fetchAll(
            "SELECT email, bounce_count, reason, first_bounce_at, last_bounce_at 
             FROM email_bounces 
             WHERE tenant_id = ? 
             ORDER BY last_bounce_at DESC",
            [$tenantId]
        );
    }

    /**
     * Get bounce statistics
     */
    public function getStats(): array
    {
        $tenantId = $this->db->getTenantId();

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM email_bounces WHERE tenant_id = ?",
            [$tenantId]
        );

        $recent = $this->db->fetch(
            "SELECT COUNT(*) as count FROM email_bounces WHERE tenant_id = ? AND last_bounce_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
            [$tenantId]
        );

        return [
            'total_bounced' => (int) ($total['count'] ?? 0),
            'bounced_last_30_days' => (int) ($recent['count'] ?? 0),
        ];
    }
}
