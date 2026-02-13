<?php
/**
 * Email Advanced Features Controller
 * 
 * Handles signature, attachments, unsubscribe, bounce, and test email endpoints
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\Email\SignatureService;
use App\Services\Email\AttachmentService;
use App\Services\Email\UnsubscribeService;
use App\Services\Email\BounceService;
use App\Services\Email\EmailService;

class EmailAdvancedController extends Controller
{
    private SignatureService $signatureService;
    private AttachmentService $attachmentService;
    private UnsubscribeService $unsubscribeService;
    private BounceService $bounceService;
    private EmailService $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->signatureService = new SignatureService($this->db);
        $this->attachmentService = new AttachmentService($this->db);
        $this->unsubscribeService = new UnsubscribeService($this->db);
        $this->bounceService = new BounceService($this->db);
        $this->emailService = new EmailService($this->db);
    }

    // ==================== SIGNATURE ====================

    /**
     * Get current signature
     */
    public function getSignature(): array
    {
        $signature = $this->signatureService->get();
        return $this->success($signature);
    }

    /**
     * Save signature HTML
     */
    public function saveSignature(): array
    {
        $input = $this->getJsonInput();
        $html = $input['signature_html'] ?? '';

        $this->signatureService->save($html);
        return $this->success(null, 'Signature saved');
    }

    /**
     * Generate signature from structured data
     */
    public function generateSignature(): array
    {
        $input = $this->getJsonInput();
        $html = $this->signatureService->generateFromData($input);
        return $this->success(['html' => $html]);
    }

    // ==================== ATTACHMENTS ====================

    /**
     * Upload attachment(s)
     */
    public function uploadAttachment(): array
    {
        if (empty($_FILES['file'])) {
            return $this->error('No file uploaded');
        }

        try {
            $attachments = $this->attachmentService->uploadMultiple($_FILES['file']);
            return $this->success($attachments, 'File(s) uploaded');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment(): array
    {
        $input = $this->getJsonInput();
        $path = $input['path'] ?? '';

        if ($this->attachmentService->delete($path)) {
            return $this->success(null, 'File deleted');
        }

        return $this->error('Failed to delete file');
    }

    // ==================== UNSUBSCRIBE ====================

    /**
     * Get unsubscribe list
     */
    public function getUnsubscribes(): array
    {
        $list = $this->unsubscribeService->getUnsubscribedList();
        return $this->success($list);
    }

    /**
     * Resubscribe an email
     */
    public function resubscribe(): array
    {
        $input = $this->getJsonInput();
        $email = $input['email'] ?? '';

        if (!$email) {
            return $this->error('Email required');
        }

        $this->unsubscribeService->resubscribe($email);
        return $this->success(null, 'Email resubscribed');
    }

    // ==================== BOUNCE ====================

    /**
     * Get bounce list
     */
    public function getBounces(): array
    {
        $list = $this->bounceService->getBounceList();
        $stats = $this->bounceService->getStats();

        return $this->success([
            'bounces' => $list,
            'stats' => $stats,
        ]);
    }

    /**
     * Remove from bounce list
     */
    public function removeBounce(): array
    {
        $input = $this->getJsonInput();
        $email = $input['email'] ?? '';

        if (!$email) {
            return $this->error('Email required');
        }

        $this->bounceService->removeFromBounceList($email);
        return $this->success(null, 'Email removed from bounce list');
    }

    /**
     * Webhook endpoint for bounce notifications
     * Public endpoint - no auth required
     */
    public function bounceWebhook(): array
    {
        $payload = $this->getJsonInput();
        $result = $this->bounceService->processWebhook($payload);
        return $result['success'] ? $this->success(null, 'Processed') : $this->error($result['error']);
    }

    // ==================== TEST EMAIL ====================

    /**
     * Send test email to verify SMTP configuration
     */
    public function sendTestEmail(): array
    {
        $input = $this->getJsonInput();
        $toEmail = $input['email'] ?? $_SESSION['user']['email'] ?? null;

        if (!$toEmail) {
            return $this->error('Email address required');
        }

        $settings = $this->emailService->getSettings();
        if (!$settings) {
            return $this->error('SMTP not configured');
        }

        // Build test email
        $email = [
            'to' => [['email' => $toEmail, 'name' => 'Test User']],
            'subject' => 'Test Email from BuildFlow ERP',
            'body_html' => $this->getTestEmailHtml($settings),
            'body_plain' => 'This is a test email from BuildFlow ERP. If you received this, your SMTP configuration is working correctly.',
        ];

        $result = $this->emailService->send($email);

        if ($result['success']) {
            return $this->success(null, "Test email sent to $toEmail");
        }

        return $this->error('Failed to send test email: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Get test email HTML content
     */
    private function getTestEmailHtml(array $settings): string
    {
        $companyName = $settings['sender_name'] ?? 'Your Company';

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="color: white; margin: 0;">Test Email</h1>
    </div>
    <div style="background: #fff; padding: 30px; border: 1px solid #eee; border-top: none; border-radius: 0 0 10px 10px;">
        <h2 style="color: #333; margin-top: 0;">SMTP Configuration Verified</h2>
        <p style="color: #666; line-height: 1.6;">
            Congratulations! If you\'re reading this email, your SMTP settings are correctly configured.
        </p>
        <div style="background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #1e40af;"><strong>Configuration Summary:</strong></p>
            <ul style="color: #1e40af; margin: 10px 0;">
                <li>SMTP Host: ' . htmlspecialchars($settings['smtp_host'] ?? 'N/A') . '</li>
                <li>Port: ' . htmlspecialchars($settings['smtp_port'] ?? 'N/A') . '</li>
                <li>Encryption: ' . htmlspecialchars($settings['encryption'] ?? 'N/A') . '</li>
                <li>Sender: ' . htmlspecialchars($settings['sender_email'] ?? 'N/A') . '</li>
            </ul>
        </div>
        <p style="color: #666; line-height: 1.6;">
            You can now send emails from your BuildFlow ERP application, including:
        </p>
        <ul style="color: #666; line-height: 1.8;">
            <li>Invoice notifications</li>
            <li>Estimate delivery</li>
            <li>Payment reminders</li>
            <li>Automated emails</li>
        </ul>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #999; font-size: 12px; text-align: center;">
            Sent from ' . htmlspecialchars($companyName) . ' via BuildFlow ERP<br>
            ' . date('F j, Y g:i A') . '
        </p>
    </div>
</body>
</html>';
    }
}
