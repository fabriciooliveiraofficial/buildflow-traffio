<?php
/**
 * Email Controller - Send and manage emails
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\Email\EmailService;
use App\Services\Email\TemplateService;

class EmailController extends Controller
{
    private EmailService $emailService;
    private TemplateService $templateService;

    public function __construct()
    {
        parent::__construct();
        $this->emailService = new EmailService($this->db);
        $this->templateService = new TemplateService($this->db);
    }

    /**
     * Send email immediately
     */
    public function send(): array
    {
        $this->validate([
            'to' => 'required',
            'subject' => 'required',
        ]);

        $input = $this->getJsonInput();

        // Build recipient list
        $to = $this->parseRecipients($input['to']);
        if (empty($to)) {
            return $this->error('At least one valid recipient is required');
        }

        // If template is specified, render it
        $subject = $input['subject'];
        $bodyHtml = $input['body_html'] ?? '';

        if (!empty($input['template_id'])) {
            try {
                $template = $this->templateService->getById((int) $input['template_id']);
                if ($template) {
                    $context = $input['context'] ?? [];
                    $subject = $this->templateService->render($template['subject'], $context);
                    $bodyHtml = $this->templateService->render($template['body_html'], $context);
                }
            } catch (\Exception $e) {
                // Continue with provided subject/body if template fails
            }
        }

        $email = [
            'to' => $to,
            'cc' => $this->parseRecipients($input['cc'] ?? []),
            'bcc' => $this->parseRecipients($input['bcc'] ?? []),
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_plain' => $input['body_plain'] ?? strip_tags($bodyHtml),
            'attachments' => $input['attachments'] ?? [],
            'context_type' => $input['context_type'] ?? null,
            'context_id' => $input['context_id'] ?? null,
        ];

        $result = $this->emailService->send($email);

        if ($result['success']) {
            return $this->success([
                'message_id' => $result['message_id'],
            ], 'Email sent successfully');
        }

        return $this->error('Failed to send email: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Queue email for later sending
     */
    public function queue(): array
    {
        $this->validate([
            'to' => 'required',
            'subject' => 'required',
        ]);

        $input = $this->getJsonInput();

        $to = $this->parseRecipients($input['to']);
        if (empty($to)) {
            return $this->error('At least one valid recipient is required');
        }

        $email = [
            'to' => $to,
            'cc' => $this->parseRecipients($input['cc'] ?? []),
            'bcc' => $this->parseRecipients($input['bcc'] ?? []),
            'subject' => $input['subject'],
            'body_html' => $input['body_html'] ?? '',
            'body_plain' => $input['body_plain'] ?? '',
            'attachments' => $input['attachments'] ?? [],
            'template_id' => $input['template_id'] ?? null,
            'context_type' => $input['context_type'] ?? null,
            'context_id' => $input['context_id'] ?? null,
            'priority' => $input['priority'] ?? 5,
        ];

        $scheduledAt = $input['scheduled_at'] ?? null;

        try {
            $queueId = $this->emailService->queue($email, $scheduledAt);
            return $this->success(['queue_id' => $queueId], 'Email queued successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to queue email: ' . $e->getMessage());
        }
    }

    /**
     * Get email logs
     */
    public function logs(): array
    {
        $params = $this->getQueryParams();

        $page = (int) ($params['page'] ?? 1);
        $perPage = min(100, (int) ($params['per_page'] ?? 25));
        $contextType = $params['context_type'] ?? null;
        $contextId = isset($params['context_id']) ? (int) $params['context_id'] : null;

        $result = $this->emailService->getLogs($page, $perPage, $contextType, $contextId);

        return $this->success($result);
    }

    /**
     * Resend a failed email
     */
    public function resend(string $logId): array
    {
        $tenantId = $this->db->getTenantId();

        $log = $this->db->fetch(
            "SELECT * FROM email_logs WHERE id = ? AND tenant_id = ?",
            [$logId, $tenantId]
        );

        if (!$log) {
            return $this->error('Email log not found', 404);
        }

        // For now, we can only resend from queue
        if (empty($log['queue_id'])) {
            return $this->error('Cannot resend - original email data not available');
        }

        $queueItem = $this->db->fetch(
            "SELECT * FROM email_queue WHERE id = ? AND tenant_id = ?",
            [$log['queue_id'], $tenantId]
        );

        if (!$queueItem) {
            return $this->error('Original queue item not found');
        }

        // Reset queue item for resending
        $this->db->update('email_queue', [
            'status' => 'pending',
            'attempts' => 0,
            'last_error' => null,
            'scheduled_at' => date('Y-m-d H:i:s'),
        ], ['id' => $queueItem['id']]);

        return $this->success(null, 'Email queued for resending');
    }

    /**
     * Send email for a specific invoice
     */
    public function sendInvoice(string $invoiceId): array
    {
        $tenantId = $this->db->getTenantId();

        $invoice = $this->db->fetch(
            "SELECT i.*, c.name as client_name, c.email as client_email
             FROM invoices i
             LEFT JOIN clients c ON i.client_id = c.id
             WHERE i.id = ? AND i.tenant_id = ?",
            [$invoiceId, $tenantId]
        );

        if (!$invoice) {
            return $this->error('Invoice not found', 404);
        }

        if (empty($invoice['client_email'])) {
            return $this->error('Client has no email address');
        }

        // Get tenant/company info
        $tenant = $this->db->fetch("SELECT * FROM tenants WHERE id = ?", [$tenantId]);

        // Build context
        $context = [
            'invoice_number' => $invoice['invoice_number'],
            'invoice_total' => '$' . number_format($invoice['total_amount'], 2),
            'due_date' => date('F j, Y', strtotime($invoice['due_date'])),
            'client_name' => $invoice['client_name'],
            'company_name' => $tenant['company_name'] ?? 'Your Company',
            'payment_link' => '#', // TODO: Generate payment link
        ];

        try {
            $rendered = $this->templateService->renderBySlug('invoice', $context);

            $email = [
                'to' => [['email' => $invoice['client_email'], 'name' => $invoice['client_name']]],
                'subject' => $rendered['subject'],
                'body_html' => $rendered['body_html'],
                'body_plain' => $rendered['body_plain'],
                'context_type' => 'invoice',
                'context_id' => (int) $invoiceId,
            ];

            $result = $this->emailService->send($email);

            if ($result['success']) {
                return $this->success(['message_id' => $result['message_id']], 'Invoice email sent');
            }

            return $this->error('Failed to send: ' . ($result['error'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            return $this->error('Failed: ' . $e->getMessage());
        }
    }

    /**
     * Send email for a specific estimate
     */
    public function sendEstimate(string $estimateId): array
    {
        $tenantId = $this->db->getTenantId();

        $estimate = $this->db->fetch(
            "SELECT e.*, c.name as client_name, c.email as client_email
             FROM estimates e
             LEFT JOIN clients c ON e.client_id = c.id
             WHERE e.id = ? AND e.tenant_id = ?",
            [$estimateId, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        if (empty($estimate['client_email'])) {
            return $this->error('Client has no email address');
        }

        $tenant = $this->db->fetch("SELECT * FROM tenants WHERE id = ?", [$tenantId]);

        $context = [
            'estimate_number' => $estimate['estimate_number'],
            'estimate_total' => '$' . number_format($estimate['total_amount'], 2),
            'valid_until' => date('F j, Y', strtotime($estimate['valid_until'] ?? '+30 days')),
            'client_name' => $estimate['client_name'],
            'company_name' => $tenant['company_name'] ?? 'Your Company',
            'project_name' => $estimate['project_name'] ?? '',
        ];

        try {
            $rendered = $this->templateService->renderBySlug('estimate', $context);

            $email = [
                'to' => [['email' => $estimate['client_email'], 'name' => $estimate['client_name']]],
                'subject' => $rendered['subject'],
                'body_html' => $rendered['body_html'],
                'body_plain' => $rendered['body_plain'],
                'context_type' => 'estimate',
                'context_id' => (int) $estimateId,
            ];

            $result = $this->emailService->send($email);

            if ($result['success']) {
                return $this->success(['message_id' => $result['message_id']], 'Estimate email sent');
            }

            return $this->error('Failed to send: ' . ($result['error'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            return $this->error('Failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse recipients into standardized format
     */
    private function parseRecipients($input): array
    {
        if (empty($input)) {
            return [];
        }

        if (is_string($input)) {
            // Single email or comma-separated
            $emails = array_map('trim', explode(',', $input));
            return array_map(fn($email) => ['email' => $email, 'name' => null], $emails);
        }

        if (is_array($input)) {
            $result = [];
            foreach ($input as $item) {
                if (is_string($item)) {
                    $result[] = ['email' => $item, 'name' => null];
                } elseif (is_array($item) && isset($item['email'])) {
                    $result[] = ['email' => $item['email'], 'name' => $item['name'] ?? null];
                }
            }
            return $result;
        }

        return [];
    }
}
