<?php
$pageTitle = 'Email Templates';
$activeNav = 'settings';
include APP_PATH . '/../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1>Email Templates</h1>
        <p class="text-muted">Manage email templates for invoices, estimates, and notifications</p>
    </div>
    <div class="flex gap-2">
        <button class="btn btn-primary" onclick="createTemplate()">Create Template</button>
    </div>
</div>

<!-- Templates Grid -->
<div class="grid grid-cols-3 gap-4" id="templates-grid">
    <div class="col-span-3 text-center text-muted py-8">Loading templates...</div>
</div>

<!-- Template Editor Modal -->
<div class="modal modal-lg" id="template-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Create Template</h3>
        <button class="modal-close" onclick="Modal.close('template-modal')">×</button>
    </div>
    <form id="template-form">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Template Name</label>
                    <input type="text" class="form-input" id="tpl-name" required
                        placeholder="e.g., Invoice Notification">
                </div>
                <div class="form-group">
                    <label class="form-label">Slug (ID)</label>
                    <input type="text" class="form-input" id="tpl-slug" placeholder="e.g., invoice_notification">
                    <p class="form-help">Auto-generated if left empty</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required">Subject Line</label>
                <input type="text" class="form-input" id="tpl-subject" required
                    placeholder="e.g., Invoice #{invoice_number} from {company_name}">
            </div>

            <div class="form-group">
                <label class="form-label required">Email Body</label>
                <div class="mb-2 flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="insertTemplateVar('client_name')">
                        {client_name}
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="insertTemplateVar('company_name')">
                        {company_name}
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary"
                        onclick="insertTemplateVar('invoice_number')">
                        {invoice_number}
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="showAllVars()">
                        More...
                    </button>
                </div>
                <textarea class="form-textarea" id="tpl-body" rows="12" required
                    placeholder="Write your email template here. Use {variable_name} for dynamic content."></textarea>
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="tpl-active" checked>
                <label for="tpl-active" class="ml-2">Active</label>
            </div>
        </div>
        <div class="modal-footer flex justify-between">
            <button type="button" class="btn btn-secondary" onclick="previewTemplate()">Preview</button>
            <div class="flex gap-2">
                <button type="button" class="btn btn-secondary" onclick="Modal.close('template-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Template</button>
            </div>
        </div>
    </form>
</div>

<!-- Variables Reference Modal -->
<div class="modal" id="variables-modal">
    <div class="modal-header">
        <h3 class="modal-title">Available Variables</h3>
        <button class="modal-close" onclick="Modal.close('variables-modal')">×</button>
    </div>
    <div class="modal-body">
        <div class="mb-4">
            <h4 class="font-bold mb-2">Client</h4>
            <div class="grid grid-cols-2 gap-2">
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('client_name')">{client_name}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('client_email')">{client_email}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('client_phone')">{client_phone}</button>
            </div>
        </div>
        <div class="mb-4">
            <h4 class="font-bold mb-2">Invoice</h4>
            <div class="grid grid-cols-2 gap-2">
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('invoice_number')">{invoice_number}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('invoice_total')">{invoice_total}</button>
                <button class="btn btn-sm btn-secondary" onclick="insertTemplateVar('due_date')">{due_date}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('payment_link')">{payment_link}</button>
            </div>
        </div>
        <div class="mb-4">
            <h4 class="font-bold mb-2">Estimate</h4>
            <div class="grid grid-cols-2 gap-2">
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('estimate_number')">{estimate_number}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('estimate_total')">{estimate_total}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('valid_until')">{valid_until}</button>
            </div>
        </div>
        <div class="mb-4">
            <h4 class="font-bold mb-2">Project</h4>
            <div class="grid grid-cols-2 gap-2">
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('project_name')">{project_name}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('project_code')">{project_code}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('project_status')">{project_status}</button>
            </div>
        </div>
        <div>
            <h4 class="font-bold mb-2">Company</h4>
            <div class="grid grid-cols-2 gap-2">
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('company_name')">{company_name}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('company_email')">{company_email}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('company_phone')">{company_phone}</button>
                <button class="btn btn-sm btn-secondary"
                    onclick="insertTemplateVar('current_date')">{current_date}</button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal modal-lg" id="preview-modal">
    <div class="modal-header">
        <h3 class="modal-title">Template Preview</h3>
        <button class="modal-close" onclick="Modal.close('preview-modal')">×</button>
    </div>
    <div class="modal-body">
        <div class="mb-3">
            <strong>Subject:</strong> <span id="preview-subject"></span>
        </div>
        <hr>
        <div id="preview-body" class="mt-3 p-4 bg-gray-50 rounded" style="min-height: 200px;"></div>
    </div>
</div>

<script>
    let templates = [];
    let editingId = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadTemplates();
    });

    async function loadTemplates() {
        try {
            const response = await ERP.api.get('/email/templates');
            if (response.success) {
                templates = response.data;
                renderTemplates();
            }
        } catch (error) {
            console.error('Failed to load templates:', error);
            document.getElementById('templates-grid').innerHTML =
                '<div class="col-span-3 text-center text-error py-8">Failed to load templates</div>';
        }
    }

    function renderTemplates() {
        const grid = document.getElementById('templates-grid');

        if (templates.length === 0) {
            grid.innerHTML = '<div class="col-span-3 text-center text-muted py-8">No templates yet. Create your first template!</div>';
            return;
        }

        grid.innerHTML = templates.map(t => {
            const isSystem = t.is_system == 1;
            const statusBadge = t.is_active == 1
                ? '<span class="badge success">Active</span>'
                : '<span class="badge">Inactive</span>';

            return '<div class="card">' +
                '<div class="card-body">' +
                '<div class="flex justify-between items-start mb-2">' +
                '<h4 class="font-bold">' + t.name + '</h4>' +
                statusBadge +
                '</div>' +
                '<p class="text-sm text-muted mb-2">Slug: ' + t.slug + '</p>' +
                '<p class="text-sm mb-3 truncate">' + t.subject + '</p>' +
                (isSystem ? '<span class="badge info text-xs">System Template</span>' : '') +
                '</div>' +
                '<div class="card-footer flex justify-between">' +
                '<button class="btn btn-sm btn-secondary" onclick="previewTemplateById(' + t.id + ')">Preview</button>' +
                '<div class="flex gap-1">' +
                '<button class="btn btn-sm btn-secondary" onclick="editTemplate(' + t.id + ')">Edit</button>' +
                (!isSystem ? '<button class="btn btn-sm btn-error" onclick="deleteTemplate(' + t.id + ')">Delete</button>' : '') +
                '</div>' +
                '</div>' +
                '</div>';
        }).join('');
    }

    function createTemplate() {
        editingId = null;
        document.getElementById('modal-title').textContent = 'Create Template';
        document.getElementById('tpl-name').value = '';
        document.getElementById('tpl-slug').value = '';
        document.getElementById('tpl-subject').value = '';
        document.getElementById('tpl-body').value = '';
        document.getElementById('tpl-active').checked = true;
        Modal.open('template-modal');
    }

    function editTemplate(id) {
        const template = templates.find(t => t.id == id);
        if (!template) return;

        editingId = id;
        document.getElementById('modal-title').textContent = 'Edit Template';
        document.getElementById('tpl-name').value = template.name;
        document.getElementById('tpl-slug').value = template.slug;
        document.getElementById('tpl-subject').value = template.subject;
        document.getElementById('tpl-body').value = template.body_html || '';
        document.getElementById('tpl-active').checked = template.is_active == 1;
        Modal.open('template-modal');
    }

    document.getElementById('template-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const data = {
            name: document.getElementById('tpl-name').value,
            slug: document.getElementById('tpl-slug').value || undefined,
            subject: document.getElementById('tpl-subject').value,
            body_html: document.getElementById('tpl-body').value,
            is_active: document.getElementById('tpl-active').checked,
        };

        try {
            let response;
            if (editingId) {
                response = await ERP.api.put('/email/templates/' + editingId, data);
            } else {
                response = await ERP.api.post('/email/templates', data);
            }

            if (response.success) {
                ERP.toast.success(editingId ? 'Template updated' : 'Template created');
                Modal.close('template-modal');
                loadTemplates();
            } else {
                ERP.toast.error(response.message || 'Failed to save template');
            }
        } catch (error) {
            ERP.toast.error('Failed to save template');
        }
    });

    async function deleteTemplate(id) {
        if (!confirm('Are you sure you want to delete this template?')) return;

        try {
            const response = await ERP.api.delete('/email/templates/' + id);
            if (response.success) {
                ERP.toast.success('Template deleted');
                loadTemplates();
            } else {
                ERP.toast.error(response.message || 'Failed to delete');
            }
        } catch (error) {
            ERP.toast.error('Failed to delete template');
        }
    }

    function insertTemplateVar(varName) {
        const body = document.getElementById('tpl-body');
        const start = body.selectionStart;
        const end = body.selectionEnd;
        const text = body.value;
        const insert = '{' + varName + '}';

        body.value = text.substring(0, start) + insert + text.substring(end);
        body.focus();
        body.selectionStart = body.selectionEnd = start + insert.length;

        Modal.close('variables-modal');
    }

    function showAllVars() {
        Modal.open('variables-modal');
    }

    function previewTemplate() {
        const subject = document.getElementById('tpl-subject').value;
        const body = document.getElementById('tpl-body').value;

        // Replace variables with sample data
        const samples = {
            client_name: 'John Doe',
            client_email: 'john@example.com',
            client_phone: '(555) 123-4567',
            company_name: 'Your Company',
            company_email: 'contact@company.com',
            company_phone: '(555) 987-6543',
            invoice_number: 'INV-2024-001',
            invoice_total: '$1,500.00',
            due_date: 'December 31, 2024',
            estimate_number: 'EST-2024-001',
            estimate_total: '$2,500.00',
            valid_until: 'January 15, 2025',
            project_name: 'Sample Project',
            project_code: 'PRJ-001',
            project_status: 'In Progress',
            payment_link: '#payment-link',
            current_date: new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }),
        };

        let previewSubject = subject;
        let previewBody = body;

        for (const [key, value] of Object.entries(samples)) {
            previewSubject = previewSubject.replace(new RegExp('\\{' + key + '\\}', 'g'), value);
            previewBody = previewBody.replace(new RegExp('\\{' + key + '\\}', 'g'), value);
        }

        document.getElementById('preview-subject').textContent = previewSubject;
        document.getElementById('preview-body').innerHTML = previewBody.replace(/\n/g, '<br>');

        Modal.open('preview-modal');
    }

    async function previewTemplateById(id) {
        try {
            const response = await ERP.api.post('/email/templates/' + id + '/preview', {});
            if (response.success) {
                document.getElementById('preview-subject').textContent = response.data.subject;
                document.getElementById('preview-body').innerHTML = response.data.body_html;
                Modal.open('preview-modal');
            }
        } catch (error) {
            ERP.toast.error('Failed to preview template');
        }
    }
</script>

<?php include APP_PATH . '/../views/layouts/footer.php'; ?>