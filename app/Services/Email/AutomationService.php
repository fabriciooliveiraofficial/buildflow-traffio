<?php
/**
 * Email Automation Service
 * 
 * Handles automatic email triggers based on system events
 */

namespace App\Services\Email;

use App\Core\Database;

class AutomationService
{
    private Database $db;
    private EmailService $emailService;
    private TemplateService $templateService;

    /**
     * Available automation triggers
     */
    public const TRIGGERS = [
        'invoice_created' => [
            'name' => 'Invoice Created',
            'description' => 'Send email when a new invoice is created',
            'context' => 'invoice',
            'default_template' => 'invoice',
        ],
        'invoice_overdue' => [
            'name' => 'Invoice Overdue',
            'description' => 'Send reminder when invoice is past due date',
            'context' => 'invoice',
            'default_template' => 'payment_reminder',
        ],
        'payment_received' => [
            'name' => 'Payment Received',
            'description' => 'Send confirmation when payment is recorded',
            'context' => 'payment',
            'default_template' => 'payment_received',
        ],
        'estimate_created' => [
            'name' => 'Estimate Created',
            'description' => 'Send email when a new estimate is created',
            'context' => 'estimate',
            'default_template' => 'estimate',
        ],
        'estimate_accepted' => [
            'name' => 'Estimate Accepted',
            'description' => 'Send confirmation when client accepts estimate',
            'context' => 'estimate',
            'default_template' => 'estimate',
        ],
        'project_status_change' => [
            'name' => 'Project Status Changed',
            'description' => 'Notify client when project status changes',
            'context' => 'project',
            'default_template' => 'project_update',
        ],
        'expense_approved' => [
            'name' => 'Expense Approved',
            'description' => 'Notify employee when expense is approved',
            'context' => 'expense',
            'default_template' => null,
        ],
    ];

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->emailService = new EmailService($db);
        $this->templateService = new TemplateService($db);
    }

    /**
     * Get all automations for current tenant
     */
    public function getAll(): array
    {
        $tenantId = $this->db->getTenantId();

        $automations = $this->db->fetchAll(
            "SELECT a.*, t.name as template_name 
             FROM email_automations a
             LEFT JOIN email_templates t ON a.template_id = t.id
             WHERE a.tenant_id = ?
             ORDER BY a.trigger_event",
            [$tenantId]
        );

        // Merge with available triggers
        $result = [];
        foreach (self::TRIGGERS as $event => $info) {
            $existing = array_filter($automations, fn($a) => $a['trigger_event'] === $event);
            $automation = reset($existing) ?: null;

            $result[] = [
                'trigger_event' => $event,
                'name' => $info['name'],
                'description' => $info['description'],
                'context' => $info['context'],
                'is_enabled' => $automation ? (bool) $automation['is_enabled'] : false,
                'template_id' => $automation['template_id'] ?? null,
                'template_name' => $automation['template_name'] ?? null,
                'delay_minutes' => $automation['delay_minutes'] ?? 0,
                'send_to' => $automation['send_to'] ?? 'client',
                'id' => $automation['id'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Save automation settings
     */
    public function save(string $triggerEvent, array $data): array
    {
        $tenantId = $this->db->getTenantId();

        if (!isset(self::TRIGGERS[$triggerEvent])) {
            throw new \Exception('Invalid trigger event');
        }

        $existing = $this->db->fetch(
            "SELECT id FROM email_automations WHERE tenant_id = ? AND trigger_event = ?",
            [$tenantId, $triggerEvent]
        );

        $automationData = [
            'tenant_id' => $tenantId,
            'trigger_event' => $triggerEvent,
            'template_id' => $data['template_id'] ?: null,
            'is_enabled' => $data['is_enabled'] ?? false,
            'delay_minutes' => (int) ($data['delay_minutes'] ?? 0),
            'send_to' => $data['send_to'] ?? 'client',
            'custom_recipients' => !empty($data['custom_recipients'])
                ? json_encode($data['custom_recipients'])
                : null,
            'conditions' => !empty($data['conditions'])
                ? json_encode($data['conditions'])
                : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->update('email_automations', $automationData, ['id' => $existing['id']]);
            return ['id' => $existing['id']];
        } else {
            $automationData['created_at'] = date('Y-m-d H:i:s');
            $id = $this->db->insert('email_automations', $automationData);
            return ['id' => $id];
        }
    }

    /**
     * Trigger an automation event
     * This is called from various parts of the app when events occur
     */
    public function trigger(string $event, array $context): bool
    {
        $tenantId = $this->db->getTenantId();

        // Get automation settings
        $automation = $this->db->fetch(
            "SELECT * FROM email_automations 
             WHERE tenant_id = ? AND trigger_event = ? AND is_enabled = 1",
            [$tenantId, $event]
        );

        if (!$automation) {
            return false; // Automation not enabled
        }

        // Check SMTP is configured
        $settings = $this->emailService->getSettings();
        if (!$settings || !$settings['is_verified']) {
            return false; // SMTP not configured
        }

        // Build email data
        $emailData = $this->buildEmailFromContext($automation, $context);
        if (!$emailData) {
            return false;
        }

        // Queue the email (with optional delay)
        $scheduledAt = null;
        if ($automation['delay_minutes'] > 0) {
            $scheduledAt = date('Y-m-d H:i:s', strtotime("+{$automation['delay_minutes']} minutes"));
        }

        try {
            $this->emailService->queue($emailData, $scheduledAt);
            return true;
        } catch (\Exception $e) {
            error_log("Automation trigger failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build email data from automation context
     */
    private function buildEmailFromContext(array $automation, array $context): ?array
    {
        // Get recipient
        $recipient = $this->getRecipient($automation, $context);
        if (!$recipient) {
            return null;
        }

        // Get template
        $templateId = $automation['template_id'];
        if (!$templateId) {
            return null;
        }

        try {
            $template = $this->templateService->getById($templateId);
            if (!$template) {
                return null;
            }

            // Get tenant info for context
            $tenantId = $this->db->getTenantId();
            $tenant = $this->db->fetch("SELECT * FROM tenants WHERE id = ?", [$tenantId]);

            // Build variable context
            $vars = array_merge($context, [
                'company_name' => $tenant['company_name'] ?? 'Your Company',
                'company_email' => $tenant['email'] ?? '',
                'company_phone' => $tenant['phone'] ?? '',
                'current_date' => date('F j, Y'),
            ]);

            // Render template
            $subject = $this->templateService->render($template['subject'], $vars);
            $bodyHtml = $this->templateService->render($template['body_html'], $vars);
            $bodyPlain = $this->templateService->render($template['body_plain'] ?? strip_tags($template['body_html']), $vars);

            return [
                'to' => [$recipient],
                'subject' => $subject,
                'body_html' => $bodyHtml,
                'body_plain' => $bodyPlain,
                'template_id' => $templateId,
                'context_type' => self::TRIGGERS[$automation['trigger_event']]['context'] ?? null,
                'context_id' => $context['id'] ?? null,
            ];
        } catch (\Exception $e) {
            error_log("Failed to build email from context: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recipient based on automation settings
     */
    private function getRecipient(array $automation, array $context): ?array
    {
        $sendTo = $automation['send_to'] ?? 'client';

        switch ($sendTo) {
            case 'client':
                if (!empty($context['client_email'])) {
                    return [
                        'email' => $context['client_email'],
                        'name' => $context['client_name'] ?? null,
                    ];
                }
                break;

            case 'user':
                if (!empty($context['user_email'])) {
                    return [
                        'email' => $context['user_email'],
                        'name' => $context['user_name'] ?? null,
                    ];
                }
                break;

            case 'custom':
                $customRecipients = json_decode($automation['custom_recipients'] ?? '[]', true);
                if (!empty($customRecipients[0])) {
                    return is_array($customRecipients[0])
                        ? $customRecipients[0]
                        : ['email' => $customRecipients[0], 'name' => null];
                }
                break;
        }

        return null;
    }

    /**
     * Process overdue invoices (called by cron)
     */
    public function processOverdueInvoices(): int
    {
        $count = 0;

        // Get all tenants with invoice_overdue automation enabled
        $automations = $this->db->fetchAll(
            "SELECT a.*, t.id as tid 
             FROM email_automations a
             JOIN tenants t ON a.tenant_id = t.id
             WHERE a.trigger_event = 'invoice_overdue' AND a.is_enabled = 1"
        );

        foreach ($automations as $automation) {
            // Set tenant context
            $this->db->setTenantId($automation['tenant_id']);

            // Find overdue invoices not yet notified today
            $overdueInvoices = $this->db->fetchAll(
                "SELECT i.*, c.name as client_name, c.email as client_email
                 FROM invoices i
                 JOIN clients c ON i.client_id = c.id
                 WHERE i.tenant_id = ? 
                   AND i.status IN ('sent', 'partial')
                   AND i.due_date < CURDATE()
                   AND (i.last_reminder_sent IS NULL OR i.last_reminder_sent < CURDATE())",
                [$automation['tenant_id']]
            );

            foreach ($overdueInvoices as $invoice) {
                $context = [
                    'id' => $invoice['id'],
                    'invoice_number' => $invoice['invoice_number'],
                    'invoice_total' => '$' . number_format($invoice['total_amount'], 2),
                    'due_date' => date('F j, Y', strtotime($invoice['due_date'])),
                    'client_name' => $invoice['client_name'],
                    'client_email' => $invoice['client_email'],
                ];

                if ($this->trigger('invoice_overdue', $context)) {
                    // Mark invoice as reminded
                    $this->db->update('invoices', [
                        'last_reminder_sent' => date('Y-m-d H:i:s'),
                    ], ['id' => $invoice['id']]);

                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get available triggers with info
     */
    public static function getTriggers(): array
    {
        return self::TRIGGERS;
    }
}
