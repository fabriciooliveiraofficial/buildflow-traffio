<?php
/**
 * Template Service - Email template management and rendering
 */

namespace App\Services\Email;

use App\Core\Database;

class TemplateService
{
    private Database $db;

    /**
     * Default templates to create for new tenants
     */
    private array $defaultTemplates = [
        'invoice' => [
            'name' => 'Invoice Notification',
            'subject' => 'Invoice #{invoice_number} from {company_name}',
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<h2 style="color: #333;">Invoice #{invoice_number}</h2>
<p>Dear {client_name},</p>
<p>Please find attached your invoice for <strong>{invoice_total}</strong>.</p>
<p><strong>Due Date:</strong> {due_date}</p>
<p>If you have any questions, please don\'t hesitate to contact us.</p>
<p>Thank you for your business!</p>
<p>Best regards,<br>{company_name}</p>
</div>',
            'variables' => ['invoice_number', 'invoice_total', 'due_date', 'client_name', 'company_name', 'payment_link'],
        ],
        'estimate' => [
            'name' => 'Estimate Notification',
            'subject' => 'Estimate #{estimate_number} from {company_name}',
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<h2 style="color: #333;">Estimate #{estimate_number}</h2>
<p>Dear {client_name},</p>
<p>Thank you for your interest! Please find attached our estimate for <strong>{estimate_total}</strong>.</p>
<p><strong>Valid Until:</strong> {valid_until}</p>
<p>Please review and let us know if you have any questions or would like to proceed.</p>
<p>Best regards,<br>{company_name}</p>
</div>',
            'variables' => ['estimate_number', 'estimate_total', 'valid_until', 'client_name', 'company_name', 'project_name'],
        ],
        'payment_reminder' => [
            'name' => 'Payment Reminder',
            'subject' => 'Reminder: Invoice #{invoice_number} is overdue',
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<h2 style="color: #e74c3c;">Payment Reminder</h2>
<p>Dear {client_name},</p>
<p>This is a friendly reminder that Invoice #{invoice_number} for <strong>{invoice_total}</strong> is now overdue.</p>
<p><strong>Original Due Date:</strong> {due_date}</p>
<p>Please process this payment at your earliest convenience.</p>
<p>If you have already made this payment, please disregard this notice.</p>
<p>Best regards,<br>{company_name}</p>
</div>',
            'variables' => ['invoice_number', 'invoice_total', 'due_date', 'client_name', 'company_name', 'payment_link'],
        ],
        'payment_received' => [
            'name' => 'Payment Confirmation',
            'subject' => 'Thank you for your payment - {company_name}',
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<h2 style="color: #27ae60;">Payment Received</h2>
<p>Dear {client_name},</p>
<p>Thank you! We have received your payment of <strong>{payment_amount}</strong> for Invoice #{invoice_number}.</p>
<p><strong>Payment Date:</strong> {payment_date}</p>
<p>We appreciate your prompt payment and look forward to working with you again.</p>
<p>Best regards,<br>{company_name}</p>
</div>',
            'variables' => ['payment_amount', 'payment_date', 'invoice_number', 'client_name', 'company_name'],
        ],
        'project_update' => [
            'name' => 'Project Status Update',
            'subject' => 'Project Update: {project_name}',
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<h2 style="color: #333;">Project Update</h2>
<p>Dear {client_name},</p>
<p>We wanted to update you on the status of your project:</p>
<p><strong>Project:</strong> {project_name}<br>
<strong>Status:</strong> {project_status}</p>
<p>If you have any questions, please let us know.</p>
<p>Best regards,<br>{company_name}</p>
</div>',
            'variables' => ['project_name', 'project_code', 'project_status', 'client_name', 'company_name'],
        ],
        'welcome' => [
            'name' => 'Welcome Email',
            'subject' => 'Welcome to {company_name}!',
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<h2 style="color: #333;">Welcome!</h2>
<p>Dear {client_name},</p>
<p>Thank you for choosing {company_name}. We\'re excited to work with you!</p>
<p>If you have any questions, please don\'t hesitate to reach out.</p>
<p>Best regards,<br>{company_name}</p>
</div>',
            'variables' => ['client_name', 'company_name', 'company_email', 'company_phone'],
        ],
    ];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all templates for current tenant
     */
    public function getAll(): array
    {
        $tenantId = $this->db->getTenantId();

        return $this->db->fetchAll(
            "SELECT * FROM email_templates WHERE tenant_id = ? ORDER BY name",
            [$tenantId]
        );
    }

    /**
     * Get template by ID
     */
    public function getById(int $id): ?array
    {
        $tenantId = $this->db->getTenantId();

        return $this->db->fetch(
            "SELECT * FROM email_templates WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );
    }

    /**
     * Get template by slug
     */
    public function getBySlug(string $slug): ?array
    {
        $tenantId = $this->db->getTenantId();

        return $this->db->fetch(
            "SELECT * FROM email_templates WHERE slug = ? AND tenant_id = ?",
            [$slug, $tenantId]
        );
    }

    /**
     * Create or update template
     */
    public function save(array $data): array
    {
        $tenantId = $this->db->getTenantId();

        $templateData = [
            'tenant_id' => $tenantId,
            'slug' => $data['slug'] ?? strtolower(preg_replace('/[^a-z0-9]+/', '_', $data['name'])),
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body_html' => $data['body_html'],
            'body_plain' => $data['body_plain'] ?? strip_tags($data['body_html']),
            'variables' => json_encode($data['variables'] ?? []),
            'is_active' => $data['is_active'] ?? true,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($data['id'])) {
            // Update existing
            $this->db->update('email_templates', $templateData, [
                'id' => $data['id'],
                'tenant_id' => $tenantId,
            ]);
            return $this->getById($data['id']);
        } else {
            // Create new
            $templateData['created_at'] = date('Y-m-d H:i:s');
            $id = $this->db->insert('email_templates', $templateData);
            return $this->getById($id);
        }
    }

    /**
     * Delete template
     */
    public function delete(int $id): bool
    {
        $tenantId = $this->db->getTenantId();

        // Check if system template
        $template = $this->getById($id);
        if ($template && $template['is_system']) {
            throw new \Exception('Cannot delete system templates');
        }

        return $this->db->delete('email_templates', [
            'id' => $id,
            'tenant_id' => $tenantId,
        ]) > 0;
    }

    /**
     * Render template with variables
     */
    public function render(string $template, array $variables): string
    {
        $result = $template;

        foreach ($variables as $key => $value) {
            $result = str_replace('{' . $key . '}', $value, $result);
        }

        return $result;
    }

    /**
     * Render template by slug with context
     */
    public function renderBySlug(string $slug, array $context): array
    {
        $template = $this->getBySlug($slug);
        if (!$template) {
            throw new \Exception("Template not found: $slug");
        }

        return [
            'subject' => $this->render($template['subject'], $context),
            'body_html' => $this->render($template['body_html'], $context),
            'body_plain' => $this->render($template['body_plain'] ?? strip_tags($template['body_html']), $context),
        ];
    }

    /**
     * Get available variables for a context type
     */
    public function getVariablesForContext(string $contextType): array
    {
        $variables = [
            // Always available
            'company_name' => 'Company name',
            'company_email' => 'Company email',
            'company_phone' => 'Company phone',
            'current_date' => 'Current date',
        ];

        switch ($contextType) {
            case 'invoice':
                $variables += [
                    'invoice_number' => 'Invoice number',
                    'invoice_total' => 'Invoice total amount',
                    'due_date' => 'Payment due date',
                    'client_name' => 'Client name',
                    'client_email' => 'Client email',
                    'payment_link' => 'Online payment link',
                ];
                break;
            case 'estimate':
                $variables += [
                    'estimate_number' => 'Estimate number',
                    'estimate_total' => 'Estimate total amount',
                    'valid_until' => 'Quote valid until date',
                    'client_name' => 'Client name',
                    'project_name' => 'Project name',
                ];
                break;
            case 'project':
                $variables += [
                    'project_name' => 'Project name',
                    'project_code' => 'Project code',
                    'project_status' => 'Current status',
                    'client_name' => 'Client name',
                ];
                break;
            case 'payment':
                $variables += [
                    'payment_amount' => 'Payment amount',
                    'payment_date' => 'Payment date',
                    'invoice_number' => 'Related invoice number',
                    'client_name' => 'Client name',
                ];
                break;
            case 'client':
                $variables += [
                    'client_name' => 'Client name',
                    'client_email' => 'Client email',
                    'client_phone' => 'Client phone',
                ];
                break;
        }

        return $variables;
    }

    /**
     * Create default templates for a new tenant
     */
    public function createDefaultTemplates(int $tenantId): void
    {
        foreach ($this->defaultTemplates as $slug => $template) {
            $this->db->insert('email_templates', [
                'tenant_id' => $tenantId,
                'slug' => $slug,
                'name' => $template['name'],
                'subject' => $template['subject'],
                'body_html' => $template['body_html'],
                'body_plain' => strip_tags($template['body_html']),
                'variables' => json_encode($template['variables']),
                'is_system' => true,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Preview template with sample data
     */
    public function preview(int $templateId): array
    {
        $template = $this->getById($templateId);
        if (!$template) {
            throw new \Exception('Template not found');
        }

        // Sample data for preview
        $sampleData = [
            'company_name' => 'Your Company Name',
            'company_email' => 'contact@yourcompany.com',
            'company_phone' => '(555) 123-4567',
            'current_date' => date('F j, Y'),
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'invoice_number' => 'INV-2024-001',
            'invoice_total' => '$1,500.00',
            'due_date' => date('F j, Y', strtotime('+30 days')),
            'estimate_number' => 'EST-2024-001',
            'estimate_total' => '$2,500.00',
            'valid_until' => date('F j, Y', strtotime('+14 days')),
            'project_name' => 'Sample Project',
            'project_code' => 'PRJ-001',
            'project_status' => 'In Progress',
            'payment_amount' => '$500.00',
            'payment_date' => date('F j, Y'),
            'payment_link' => '#preview-link',
        ];

        return [
            'subject' => $this->render($template['subject'], $sampleData),
            'body_html' => $this->render($template['body_html'], $sampleData),
        ];
    }
}
