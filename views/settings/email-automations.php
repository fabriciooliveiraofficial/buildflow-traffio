<?php
$pageTitle = 'Email Automations';
$activeNav = 'settings';
include APP_PATH . '/../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1>Email Automations</h1>
        <p class="text-muted">Configure automatic emails triggered by system events</p>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-info mb-4">
    <strong>How it works:</strong> When enabled, automatic emails will be sent using your configured SMTP settings
    when specific events occur in the system. Make sure your <a href="settings/email">SMTP is configured</a> first.
</div>

<!-- Automations List -->
<div class="card">
    <div class="card-body p-0">
        <table class="table" id="automations-table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Template</th>
                    <th>Delay</th>
                    <th>Send To</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="automations-body">
                <tr>
                    <td colspan="6" class="text-center text-muted">Loading automations...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Automation Modal -->
<div class="modal" id="automation-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Configure Automation</h3>
        <button class="modal-close" onclick="Modal.close('automation-modal')">×</button>
    </div>
    <form id="automation-form">
        <div class="modal-body">
            <input type="hidden" id="trigger-event">

            <div class="mb-4">
                <div class="text-lg font-bold" id="trigger-name"></div>
                <div class="text-muted text-sm" id="trigger-description"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Email Template</label>
                <select class="form-select" id="template-id" required>
                    <option value="">Select a template...</option>
                </select>
                <p class="form-help">Choose which template to use for this automation</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Send To</label>
                    <select class="form-select" id="send-to">
                        <option value="client">Client</option>
                        <option value="user">User/Employee</option>
                        <option value="custom">Custom Email</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Delay (minutes)</label>
                    <input type="number" class="form-input" id="delay-minutes" value="0" min="0" max="1440">
                    <p class="form-help">Wait time before sending (0 = immediate)</p>
                </div>
            </div>

            <div class="form-group" id="custom-email-group" style="display: none;">
                <label class="form-label">Custom Email Address</label>
                <input type="email" class="form-input" id="custom-email" placeholder="notify@example.com">
            </div>

            <div class="flex items-center mt-4">
                <input type="checkbox" id="is-enabled" checked>
                <label for="is-enabled" class="ml-2 font-medium">Enable this automation</label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('automation-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Automation</button>
        </div>
    </form>
</div>

<script>
    let automations = [];
    let templates = [];

    document.addEventListener('DOMContentLoaded', function () {
        loadTemplates();
        loadAutomations();

        document.getElementById('send-to').addEventListener('change', function () {
            document.getElementById('custom-email-group').style.display =
                this.value === 'custom' ? 'block' : 'none';
        });
    });

    async function loadTemplates() {
        try {
            const response = await ERP.api.get('/email/templates');
            if (response.success) {
                templates = response.data;
                const select = document.getElementById('template-id');
                templates.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.name;
                    select.appendChild(opt);
                });
            }
        } catch (error) {
            console.error('Failed to load templates:', error);
        }
    }

    async function loadAutomations() {
        try {
            const response = await ERP.api.get('/email/automations');
            if (response.success) {
                automations = response.data;
                renderAutomations();
            }
        } catch (error) {
            console.error('Failed to load automations:', error);
            document.getElementById('automations-body').innerHTML =
                '<tr><td colspan="6" class="text-center text-error">Failed to load automations</td></tr>';
        }
    }

    function renderAutomations() {
        const tbody = document.getElementById('automations-body');

        if (automations.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No automations available</td></tr>';
            return;
        }

        tbody.innerHTML = automations.map(a => {
            const statusBadge = a.is_enabled
                ? '<span class="badge success">Enabled</span>'
                : '<span class="badge">Disabled</span>';

            const templateName = a.template_name || '<span class="text-muted">Not set</span>';
            const delayText = a.delay_minutes > 0 ? a.delay_minutes + ' min' : 'Immediate';

            const sendToLabels = {
                'client': 'Client',
                'user': 'User',
                'custom': 'Custom'
            };

            return '<tr>' +
                '<td>' +
                '<div class="font-medium">' + a.name + '</div>' +
                '<div class="text-sm text-muted">' + a.description + '</div>' +
                '</td>' +
                '<td>' + templateName + '</td>' +
                '<td>' + delayText + '</td>' +
                '<td>' + (sendToLabels[a.send_to] || 'Client') + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td>' +
                '<div class="flex gap-1">' +
                '<button class="btn btn-sm btn-secondary" onclick="editAutomation(\'' + a.trigger_event + '\')">Configure</button>' +
                '<button class="btn btn-sm ' + (a.is_enabled ? 'btn-error' : 'btn-success') + '" onclick="toggleAutomation(\'' + a.trigger_event + '\', ' + !a.is_enabled + ')">' +
                (a.is_enabled ? 'Disable' : 'Enable') +
                '</button>' +
                '</div>' +
                '</td>' +
                '</tr>';
        }).join('');
    }

    function editAutomation(triggerEvent) {
        const automation = automations.find(a => a.trigger_event === triggerEvent);
        if (!automation) return;

        document.getElementById('trigger-event').value = triggerEvent;
        document.getElementById('modal-title').textContent = 'Configure: ' + automation.name;
        document.getElementById('trigger-name').textContent = automation.name;
        document.getElementById('trigger-description').textContent = automation.description;
        document.getElementById('template-id').value = automation.template_id || '';
        document.getElementById('send-to').value = automation.send_to || 'client';
        document.getElementById('delay-minutes').value = automation.delay_minutes || 0;
        document.getElementById('is-enabled').checked = automation.is_enabled;

        document.getElementById('custom-email-group').style.display =
            automation.send_to === 'custom' ? 'block' : 'none';

        Modal.open('automation-modal');
    }

    async function toggleAutomation(triggerEvent, enable) {
        try {
            const response = await ERP.api.put('/email/automations/' + triggerEvent + '/toggle', {
                is_enabled: enable
            });

            if (response.success) {
                ERP.toast.success(response.message);
                loadAutomations();
            } else {
                ERP.toast.error(response.message || 'Failed to toggle');
            }
        } catch (error) {
            ERP.toast.error('Failed to toggle automation');
        }
    }

    document.getElementById('automation-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const triggerEvent = document.getElementById('trigger-event').value;
        const templateId = document.getElementById('template-id').value;

        if (!templateId) {
            ERP.toast.error('Please select a template');
            return;
        }

        const data = {
            template_id: parseInt(templateId),
            send_to: document.getElementById('send-to').value,
            delay_minutes: parseInt(document.getElementById('delay-minutes').value) || 0,
            is_enabled: document.getElementById('is-enabled').checked,
            custom_recipients: [],
        };

        if (data.send_to === 'custom') {
            const customEmail = document.getElementById('custom-email').value;
            if (customEmail) {
                data.custom_recipients = [customEmail];
            }
        }

        try {
            const response = await ERP.api.put('/email/automations/' + triggerEvent, data);
            if (response.success) {
                ERP.toast.success('Automation saved');
                Modal.close('automation-modal');
                loadAutomations();
            } else {
                ERP.toast.error(response.message || 'Failed to save');
            }
        } catch (error) {
            ERP.toast.error('Failed to save automation');
        }
    });
</script>

<?php include APP_PATH . '/../views/layouts/footer.php'; ?>
