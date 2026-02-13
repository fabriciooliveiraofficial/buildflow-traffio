<?php
/**
 * Email Settings API Controller
 * 
 * Handles SMTP configuration for tenant email sending
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\Email\EmailService;

class EmailSettingsController extends Controller
{
    private EmailService $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->emailService = new EmailService($this->db);
    }

    /**
     * Get current SMTP settings
     */
    public function show(): array
    {
        $settings = $this->emailService->getSettings();

        if (!$settings) {
            return $this->success([
                'configured' => false,
                'smtp_host' => '',
                'smtp_port' => 587,
                'encryption' => 'tls',
                'username' => '',
                'sender_name' => '',
                'sender_email' => '',
                'reply_to_email' => '',
                'daily_limit' => 100,
                'is_verified' => false,
            ]);
        }

        // Don't expose the encrypted password
        unset($settings['password_encrypted']);
        $settings['has_password'] = !empty($settings['password_encrypted'] ?? true);
        $settings['configured'] = true;

        return $this->success($settings);
    }

    /**
     * Save SMTP settings
     */
    public function update(): array
    {
        $input = $this->getJsonInput();

        $this->validate([
            'smtp_host' => 'required',
            'smtp_port' => 'required|numeric',
            'sender_email' => 'required|email',
        ]);

        try {
            $settings = $this->emailService->saveSettings([
                'smtp_host' => $input['smtp_host'],
                'smtp_port' => $input['smtp_port'],
                'encryption' => $input['encryption'] ?? 'tls',
                'username' => $input['username'] ?? '',
                'password' => $input['password'] ?? null, // Only update if provided
                'sender_name' => $input['sender_name'] ?? '',
                'sender_email' => $input['sender_email'],
                'reply_to_email' => $input['reply_to_email'] ?? '',
                'daily_limit' => $input['daily_limit'] ?? 100,
            ]);

            // Don't expose password
            unset($settings['password_encrypted']);
            $settings['has_password'] = true;

            return $this->success($settings, 'Email settings saved');
        } catch (\Exception $e) {
            return $this->error('Failed to save settings: ' . $e->getMessage());
        }
    }

    /**
     * Test SMTP connection
     */
    public function test(): array
    {
        $result = $this->emailService->testConnection();

        if ($result['success']) {
            return $this->success([
                'connected' => true,
                'message' => $result['message'],
            ]);
        }

        return $this->error($result['error']);
    }

    /**
     * Get email sending statistics
     */
    public function stats(): array
    {
        $tenantId = $this->db->getTenantId();

        // Today's stats
        $today = date('Y-m-d');
        $settings = $this->emailService->getSettings();

        $todayStats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) as opened
             FROM email_logs 
             WHERE tenant_id = ? AND DATE(sent_at) = ?",
            [$tenantId, $today]
        );

        // This week
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekStats = $this->db->fetch(
            "SELECT COUNT(*) as total FROM email_logs 
             WHERE tenant_id = ? AND sent_at >= ?",
            [$tenantId, $weekStart]
        );

        // This month
        $monthStart = date('Y-m-01');
        $monthStats = $this->db->fetch(
            "SELECT COUNT(*) as total FROM email_logs 
             WHERE tenant_id = ? AND sent_at >= ?",
            [$tenantId, $monthStart]
        );

        return $this->success([
            'today' => [
                'sent' => (int) ($todayStats['sent'] ?? 0),
                'failed' => (int) ($todayStats['failed'] ?? 0),
                'opened' => (int) ($todayStats['opened'] ?? 0),
                'limit' => $settings['daily_limit'] ?? 100,
                'used' => $settings['emails_sent_today'] ?? 0,
            ],
            'this_week' => (int) ($weekStats['total'] ?? 0),
            'this_month' => (int) ($monthStats['total'] ?? 0),
        ]);
    }
}
