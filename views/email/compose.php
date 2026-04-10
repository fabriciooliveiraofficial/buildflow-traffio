<?php
$pageTitle = 'Compose Email';
$activeNav = 'email';
include APP_PATH . '/../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1>Compose Email</h1>
        <p class="text-muted">Send an email to clients or team members</p>
    </div>
</div>

<div class="grid grid-cols-3 gap-6">
    <!-- Main Compose Form -->
    <div class="col-span-2">
        <div class="card">
            <form id="compose-form">
                <div class="card-body">
                    <!-- Recipients -->
                    <div class="form-group">
                        <label class="form-label required">To</label>
                        <input type="text" class="form-input" id="to" name="to"
                            placeholder="email@example.com, or select a client" required>
                        <p class="form-help">Separate multiple emails with commas</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">CC</label>
                            <input type="text" class="form-input" id="cc" name="cc" placeholder="cc@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">BCC</label>
                            <input type="text" class="form-input" id="bcc" name="bcc" placeholder="bcc@example.com">
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Template Selection -->
                    <div class="form-group">
                        <label class="form-label">Use Template</label>
                        <select class="form-select" id="template-select" onchange="loadTemplate(this.value)">
                            <option value="">-- No Template --</option>
                        </select>
                    </div>

                    <!-- Subject -->
                    <div class="form-group">
                        <label class="form-label required">Subject</label>
                        <input type="text" class="form-input" id="subject" name="subject" placeholder="Email subject"
                            required>
                    </div>

                    <!-- Body -->
                    <div class="form-group">
                        <label class="form-label required">Message</label>
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="insertVariable()">
                                Insert Variable
                            </button>
                        </div>
                        <textarea class="form-textarea" id="body" name="body" rows="12"
                            placeholder="Write your message here..." required></textarea>
                    </div>

                    <!-- Attachments -->
                    <div class="form-group">
                        <label class="form-label">Attachments</label>
                        <div class="border-dashed border-2 rounded p-4 text-center" id="attachment-zone">
                            <input type="file" id="attachments" multiple class="hidden"
                                onchange="handleFiles(this.files)">
                            <p class="text-muted mb-2">Drag files here or</p>
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick="document.getElementById('attachments').click()">
                                Browse Files
                            </button>
                        </div>
                        <div id="attachment-list" class="mt-2"></div>
                    </div>
                </div>
                <div class="card-footer flex justify-between">
                    <div>
                        <button type="button" class="btn btn-secondary" onclick="saveDraft()">
                            Save Draft
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-secondary" onclick="previewEmail()">
                            Preview
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Send Email
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Quick Recipients -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Quick Recipients</h3>
            </div>
            <div class="card-body p-0">
                <div class="p-3 border-b">
                    <input type="text" class="form-input form-input-sm" id="client-search"
                        placeholder="Search clients..." oninput="searchClients(this.value)">
                </div>
                <div id="client-list" style="max-height: 200px; overflow-y: auto;">
                    <div class="p-3 text-center text-muted">Loading clients...</div>
                </div>
            </div>
        </div>

        <!-- Context Selection -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Link to Context</h3>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label class="form-label">Type</label>
                    <select class="form-select form-select-sm" id="context-type" onchange="loadContextOptions()">
                        <option value="">None</option>
                        <option value="invoice">Invoice</option>
                        <option value="estimate">Estimate</option>
                        <option value="project">Project</option>
                    </select>
                </div>
                <div class="form-group" id="context-select-container" style="display: none;">
                    <label class="form-label">Select</label>
                    <select class="form-select form-select-sm" id="context-id">
                        <option value="">Select...</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Recent Emails -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Emails</h3>
            </div>
            <div class="card-body p-0" id="recent-emails">
                <div class="p-3 text-center text-muted">No recent emails</div>
            </div>
            <div class="card-footer text-center">
                <a href="/email/logs" class="text-sm">View All Sent Emails</a>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal" id="preview-modal">
    <div class="modal-header">
        <h3 class="modal-title">Email Preview</h3>
        <button class="modal-close" onclick="Modal.close('preview-modal')">×</button>
    </div>
    <div class="modal-body">
        <div class="mb-3">
            <strong>To:</strong> <span id="preview-to"></span>
        </div>
        <div class="mb-3">
            <strong>Subject:</strong> <span id="preview-subject"></span>
        </div>
        <hr>
        <div id="preview-body" class="mt-3" style="max-height: 400px; overflow-y: auto;"></div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-secondary" onclick="Modal.close('preview-modal')">Close</button>
        <button class="btn btn-primary"
            onclick="Modal.close('preview-modal'); document.getElementById('compose-form').requestSubmit();">Send</button>
    </div>
</div>

<!-- Variable Insert Modal -->
<div class="modal" id="variable-modal">
    <div class="modal-header">
        <h3 class="modal-title">Insert Variable</h3>
        <button class="modal-close" onclick="Modal.close('variable-modal')">×</button>
    </div>
    <div class="modal-body">
        <p class="text-muted mb-3">Click a variable to insert it at cursor position:</p>
        <div id="variable-list" class="grid grid-cols-2 gap-2">
            <button type="button" class="btn btn-sm btn-secondary text-left"
                onclick="insertVar('client_name')">{client_name}</button>
            <button type="button" class="btn btn-sm btn-secondary text-left"
                onclick="insertVar('client_email')">{client_email}</button>
            <button type="button" class="btn btn-sm btn-secondary text-left"
                onclick="insertVar('company_name')">{company_name}</button>
            <button type="button" class="btn btn-sm btn-secondary text-left"
                onclick="insertVar('invoice_number')">{invoice_number}</button>
            <button type="button" class="btn btn-sm btn-secondary text-left"
                onclick="insertVar('invoice_total')">{invoice_total}</button>
            <button type="button" class="btn btn-sm btn-secondary text-left"
                onclick="insertVar('due_date')">{due_date}</button>
            <button type="button" class="btn btn-sm btn-secondary text-left"
                onclick="insertVar('project_name')">{project_name}</button>
            <button type="button" class="btn btn-sm btn-secondary text-left"
                onclick="insertVar('estimate_number')">{estimate_number}</button>
            <button type="button" class="btn btn-sm btn-secondary text-left"
                onclick="insertVar('current_date')">{current_date}</button>
        </div>
    </div>
</div>

<script>
    let clients = [];
    let templates = [];
    let attachments = [];

    document.addEventListener('DOMContentLoaded', function () {
        loadClients();
        loadTemplates();
        loadRecentEmails();

        // Check URL params for pre-filled data
        const params = new URLSearchParams(window.location.search);
        if (params.get('to')) {
            document.getElementById('to').value = params.get('to');
        }
        if (params.get('subject')) {
            document.getElementById('subject').value = params.get('subject');
        }
    });

    async function loadClients() {
        try {
            const response = await ERP.api.get('/clients?per_page=100');
            if (response.success) {
                clients = response.data.data || response.data;
                renderClientList(clients);
            }
        } catch (error) {
            console.error('Failed to load clients:', error);
        }
    }

    function renderClientList(list) {
        const container = document.getElementById('client-list');
        if (list.length === 0) {
            container.innerHTML = '<div class="p-3 text-center text-muted">No clients found</div>';
            return;
        }

        container.innerHTML = list.slice(0, 10).map(c =>
            '<div class="px-3 py-2 hover:bg-gray-50 cursor-pointer border-b" onclick="addRecipient(\'' + (c.email || '') + '\', \'' + c.name + '\')">' +
            '<div class="font-medium">' + c.name + '</div>' +
            '<div class="text-sm text-muted">' + (c.email || 'No email') + '</div>' +
            '</div>'
        ).join('');
    }

    function searchClients(query) {
        const filtered = clients.filter(c =>
            c.name.toLowerCase().includes(query.toLowerCase()) ||
            (c.email && c.email.toLowerCase().includes(query.toLowerCase()))
        );
        renderClientList(filtered);
    }

    function addRecipient(email, name) {
        if (!email) {
            ERP.toast.error('This client has no email address');
            return;
        }

        const toField = document.getElementById('to');
        const current = toField.value.trim();

        if (current && !current.endsWith(',')) {
            toField.value = current + ', ' + email;
        } else {
            toField.value = (current ? current + ' ' : '') + email;
        }

        ERP.toast.success('Added ' + name);
    }

    async function loadTemplates() {
        try {
            const response = await ERP.api.get('/email/templates');
            if (response.success) {
                templates = response.data;
                const select = document.getElementById('template-select');
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

    function loadTemplate(id) {
        if (!id) {
            document.getElementById('subject').value = '';
            document.getElementById('body').value = '';
            return;
        }

        const template = templates.find(t => t.id == id);
        if (template) {
            document.getElementById('subject').value = template.subject;
            document.getElementById('body').value = template.body_html.replace(/<[^>]*>/g, '');
        }
    }

    async function loadRecentEmails() {
        try {
            const response = await ERP.api.get('/email/logs?per_page=5');
            if (response.success && response.data.logs && response.data.logs.length > 0) {
                const container = document.getElementById('recent-emails');
                container.innerHTML = response.data.logs.map(log =>
                    '<div class="px-3 py-2 border-b">' +
                    '<div class="text-sm font-medium truncate">' + log.subject + '</div>' +
                    '<div class="text-xs text-muted">To: ' + log.to_email + '</div>' +
                    '<div class="text-xs text-muted">' + formatDate(log.sent_at) + '</div>' +
                    '</div>'
                ).join('');
            }
        } catch (error) {
            console.error('Failed to load recent emails:', error);
        }
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function insertVariable() {
        Modal.open('variable-modal');
    }

    function insertVar(varName) {
        const body = document.getElementById('body');
        const start = body.selectionStart;
        const end = body.selectionEnd;
        const text = body.value;
        const insert = '{' + varName + '}';

        body.value = text.substring(0, start) + insert + text.substring(end);
        body.focus();
        body.selectionStart = body.selectionEnd = start + insert.length;

        Modal.close('variable-modal');
    }

    function previewEmail() {
        const to = document.getElementById('to').value;
        const subject = document.getElementById('subject').value;
        const body = document.getElementById('body').value;

        document.getElementById('preview-to').textContent = to;
        document.getElementById('preview-subject').textContent = subject;
        document.getElementById('preview-body').innerHTML = body.replace(/\n/g, '<br>');

        Modal.open('preview-modal');
    }

    function handleFiles(files) {
        const list = document.getElementById('attachment-list');

        for (const file of files) {
            if (file.size > 10 * 1024 * 1024) {
                ERP.toast.error('File too large: ' + file.name + ' (max 10MB)');
                continue;
            }

            attachments.push(file);

            const div = document.createElement('div');
            div.className = 'flex justify-between items-center p-2 bg-gray-50 rounded mb-1';
            div.innerHTML =
                '<span class="text-sm">' + file.name + ' (' + formatBytes(file.size) + ')</span>' +
                '<button type="button" class="btn btn-sm btn-icon" onclick="removeAttachment(' + (attachments.length - 1) + ', this.parentElement)">&times;</button>';
            list.appendChild(div);
        }
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function removeAttachment(index, el) {
        attachments.splice(index, 1);
        el.remove();
    }

    async function loadContextOptions() {
        const type = document.getElementById('context-type').value;
        const container = document.getElementById('context-select-container');
        const select = document.getElementById('context-id');

        if (!type) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        select.innerHTML = '<option value="">Loading...</option>';

        try {
            let endpoint = '';
            switch (type) {
                case 'invoice': endpoint = '/invoices?per_page=50'; break;
                case 'estimate': endpoint = '/estimates?per_page=50'; break;
                case 'project': endpoint = '/projects?per_page=50'; break;
            }

            const response = await ERP.api.get(endpoint);
            if (response.success) {
                const items = response.data.data || response.data;
                select.innerHTML = '<option value="">Select...</option>';
                items.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.invoice_number || item.estimate_number || item.name || item.id;
                    select.appendChild(opt);
                });
            }
        } catch (error) {
            select.innerHTML = '<option value="">Error loading</option>';
        }
    }

    document.getElementById('compose-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const to = document.getElementById('to').value;
        const cc = document.getElementById('cc').value;
        const bcc = document.getElementById('bcc').value;
        const subject = document.getElementById('subject').value;
        const body = document.getElementById('body').value;
        const contextType = document.getElementById('context-type').value;
        const contextId = document.getElementById('context-id').value;

        if (!to || !subject || !body) {
            ERP.toast.error('Please fill in all required fields');
            return;
        }

        const emailData = {
            to: to,
            cc: cc || undefined,
            bcc: bcc || undefined,
            subject: subject,
            body_html: body.replace(/\n/g, '<br>'),
            body_plain: body,
            context_type: contextType || undefined,
            context_id: contextId || undefined,
        };

        try {
            const response = await ERP.api.post('/email/send', emailData);
            if (response.success) {
                ERP.toast.success('Email sent successfully!');
                // Clear form
                document.getElementById('compose-form').reset();
                attachments = [];
                document.getElementById('attachment-list').innerHTML = '';
                loadRecentEmails();
            } else {
                ERP.toast.error(response.message || 'Failed to send email');
            }
        } catch (error) {
            ERP.toast.error('Failed to send email: ' + (error.message || 'Unknown error'));
        }
    });

    function saveDraft() {
        ERP.toast.info('Draft saving coming soon!');
    }
</script>

<?php include APP_PATH . '/../views/layouts/footer.php'; ?>
