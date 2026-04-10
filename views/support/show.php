<?php
$title = 'Support Ticket';
$page = 'support';

$ticketId = $GLOBALS['params']['id'] ?? null;

ob_start();
?>

<div id="ticket-detail">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="support" class="btn btn-secondary btn-sm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12" />
                <polyline points="12 19 5 12 12 5" />
            </svg>
            Back to Tickets
        </a>
    </div>

    <!-- Loading State -->
    <div id="loading-state" class="card text-center py-6">
        <p class="text-muted">Loading ticket...</p>
    </div>

    <!-- Ticket Content -->
    <div id="ticket-content" style="display: none;">
        <!-- Header -->
        <div class="card mb-4">
            <div class="ticket-header">
                <div class="ticket-header-info">
                    <div class="ticket-number" id="ticket-number"></div>
                    <h1 class="ticket-title" id="ticket-subject"></h1>
                    <div class="ticket-meta">
                        <span id="ticket-reporter"></span>
                        <span>•</span>
                        <span id="ticket-created"></span>
                    </div>
                </div>
                <div class="ticket-header-badges">
                    <span class="badge" id="ticket-priority-badge"></span>
                    <span class="badge" id="ticket-status-badge"></span>
                    <span class="badge badge-outline" id="ticket-category-badge"></span>
                </div>
            </div>

            <!-- Admin Controls -->
            <div class="ticket-controls" id="admin-controls" style="display: none;">
                <div class="form-group">
                    <label>Status</label>
                    <select id="update-status" class="form-select form-select-sm"
                        onchange="updateTicket('status', this.value)">
                        <option value="new">New</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="awaiting_info">Awaiting Info</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select id="update-priority" class="form-select form-select-sm"
                        onchange="updateTicket('priority', this.value)">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assigned To</label>
                    <select id="update-assigned" class="form-select form-select-sm"
                        onchange="updateTicket('assigned_to', this.value)">
                        <option value="">Unassigned</option>
                    </select>
                </div>
            </div>

            <!-- System Info -->
            <div class="ticket-system-info" id="system-info-section">
                <h4>System Information</h4>
                <div class="system-info-grid" id="system-info-grid"></div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="ticket-grid">
            <!-- Messages Thread -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Conversation</h3>
                </div>
                <div class="messages-container" id="messages-container">
                    <!-- Messages loaded here -->
                </div>
                <div class="message-form">
                    <textarea id="new-message" class="form-control" placeholder="Type your reply..."
                        rows="3"></textarea>
                    <div class="message-form-actions">
                        <label class="internal-note-toggle" id="internal-toggle" style="display: none;">
                            <input type="checkbox" id="is-internal"> Internal note (not visible to user)
                        </label>
                        <button class="btn btn-primary" onclick="sendMessage()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13" />
                                <polygon points="22 2 15 22 11 13 2 9 22 2" />
                            </svg>
                            Send
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="ticket-sidebar">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Remote Support</h4>
                    </div>
                    <div class="card-body">
                        <div class="anydesk-info" id="anydesk-info">
                            <p class="text-muted">No Anydesk ID provided</p>
                        </div>
                        <div class="form-group mb-0">
                            <input type="text" id="add-anydesk" class="form-control form-control-sm"
                                placeholder="Enter Anydesk ID">
                            <button class="btn btn-sm btn-secondary mt-2" onclick="saveAnydeskId()"
                                style="width: 100%;">Save Anydesk ID</button>
                        </div>
                    </div>
                </div>

                <!-- Attachments -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Attachments</h4>
                    </div>
                    <div class="card-body" id="attachments-list">
                        <p class="text-muted text-sm">No attachments</p>
                    </div>
                </div>

                <!-- Project Link -->
                <div class="card" id="project-card" style="display: none;">
                    <div class="card-header">
                        <h4 class="card-title">Linked Project</h4>
                    </div>
                    <div class="card-body">
                        <a href="#" id="project-link" class="font-medium"></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ticketId = <?= json_encode($ticketId) ?>;
    let currentTicket = null;
    let isAdmin = false;

    document.addEventListener('DOMContentLoaded', async function () {
        if (!ticketId) {
            document.getElementById('loading-state').innerHTML = '<p class="text-error">Ticket not found</p>';
            return;
        }
        await loadTicket();
    });

    async function loadTicket() {
        try {
            const response = await ERP.api.get(`/support/tickets/${ticketId}`);
            if (response.success) {
                currentTicket = response.data.ticket;
                isAdmin = response.data.remote_sessions !== undefined; // Admin gets remote_sessions

                document.getElementById('loading-state').style.display = 'none';
                document.getElementById('ticket-content').style.display = 'block';

                renderTicket(response.data);
            } else {
                document.getElementById('loading-state').innerHTML = '<p class="text-error">Ticket not found</p>';
            }
        } catch (e) {
            console.error('Failed to load ticket:', e);
            document.getElementById('loading-state').innerHTML = '<p class="text-error">Failed to load ticket</p>';
        }
    }

    function renderTicket(data) {
        const t = data.ticket;

        // Header
        document.getElementById('ticket-number').textContent = t.ticket_number;
        document.getElementById('ticket-subject').textContent = t.subject;
        document.getElementById('ticket-reporter').textContent =
            `${t.reporter_first_name || ''} ${t.reporter_last_name || ''}`.trim() || t.reporter_email;
        document.getElementById('ticket-created').textContent = formatDate(t.created_at);

        // Badges
        const priorityBadge = document.getElementById('ticket-priority-badge');
        priorityBadge.textContent = t.priority.charAt(0).toUpperCase() + t.priority.slice(1);
        priorityBadge.className = `badge badge-${getPriorityBadge(t.priority)}`;

        const statusBadge = document.getElementById('ticket-status-badge');
        statusBadge.textContent = formatStatus(t.status);
        statusBadge.className = `badge badge-${getStatusBadge(t.status)}`;

        document.getElementById('ticket-category-badge').textContent =
            `${getCategoryIcon(t.category)} ${t.category}`;

        // System Info
        if (t.os_name || t.browser_name) {
            const grid = document.getElementById('system-info-grid');
            grid.innerHTML = `
                <div><strong>OS:</strong> ${t.os_name || '-'} ${t.os_version || ''}</div>
                <div><strong>Browser:</strong> ${t.browser_name || '-'} ${t.browser_version || ''}</div>
                <div><strong>Screen:</strong> ${t.screen_resolution || '-'}</div>
                <div><strong>Timezone:</strong> ${t.timezone || '-'}</div>
            `;
        } else {
            document.getElementById('system-info-section').style.display = 'none';
        }

        // Anydesk
        if (t.anydesk_id) {
            document.getElementById('anydesk-info').innerHTML = `
                <div class="anydesk-id-display">
                    <strong>Anydesk ID:</strong> 
                    <code>${t.anydesk_id}</code>
                    <button class="btn btn-xs btn-secondary" onclick="copyToClipboard('${t.anydesk_id}')">Copy</button>
                </div>`;
            document.getElementById('add-anydesk').value = t.anydesk_id;
        }

        // Project
        if (t.project_name) {
            document.getElementById('project-card').style.display = 'block';
            const link = document.getElementById('project-link');
            link.textContent = t.project_name;
            link.href = `../projects/${t.project_id}`;
        }

        // Admin controls
        if (isAdmin) {
            document.getElementById('admin-controls').style.display = 'flex';
            document.getElementById('internal-toggle').style.display = 'block';
            document.getElementById('update-status').value = t.status;
            document.getElementById('update-priority').value = t.priority;
            loadAssignees(t.assigned_to);
        }

        // Messages
        renderMessages(data.messages);

        // Attachments
        if (data.attachments && data.attachments.length > 0) {
            renderAttachments(data.attachments);
        }
    }

    function renderMessages(messages) {
        const container = document.getElementById('messages-container');

        if (!messages || messages.length === 0) {
            container.innerHTML = '<p class="text-center text-muted py-4">No messages yet</p>';
            return;
        }

        container.innerHTML = messages.map(m => {
            const isInternal = m.is_internal == 1;
            const isOwn = m.user_id == currentTicket.user_id;

            return `
                <div class="message ${isInternal ? 'internal' : ''} ${isOwn ? 'own' : 'support'}">
                    <div class="message-header">
                        <span class="message-author">${m.first_name || ''} ${m.last_name || ''}</span>
                        ${isInternal ? '<span class="badge badge-secondary">Internal</span>' : ''}
                        <span class="message-time">${formatDateTime(m.created_at)}</span>
                    </div>
                    <div class="message-body">${escapeHtml(m.message).replace(/\n/g, '<br>')}</div>
                </div>
            `;
        }).join('');

        container.scrollTop = container.scrollHeight;
    }

    function renderAttachments(attachments) {
        const container = document.getElementById('attachments-list');
        container.innerHTML = attachments.map(a => `
            <div class="attachment-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <a href="${a.file_path}" target="_blank">${a.file_name}</a>
            </div>
        `).join('');
    }

    async function loadAssignees(currentAssignee) {
        try {
            const response = await ERP.api.get('/employees?per_page=100');
            if (response.success && response.data) {
                const select = document.getElementById('update-assigned');
                response.data.forEach(e => {
                    const option = document.createElement('option');
                    option.value = e.user_id || '';
                    option.textContent = `${e.first_name} ${e.last_name}`;
                    if (e.user_id == currentAssignee) option.selected = true;
                    select.appendChild(option);
                });
            }
        } catch (e) {
            console.error('Failed to load assignees:', e);
        }
    }

    async function updateTicket(field, value) {
        try {
            const data = {};
            data[field] = value;
            const response = await ERP.api.put(`/support/tickets/${ticketId}`, data);
            if (response.success) {
                ERP.toast.success('Ticket updated');
                loadTicket();
            }
        } catch (e) {
            ERP.toast.error('Failed to update ticket');
        }
    }

    async function sendMessage() {
        const message = document.getElementById('new-message').value.trim();
        if (!message) return;

        const isInternal = document.getElementById('is-internal')?.checked || false;

        try {
            const response = await ERP.api.post(`/support/tickets/${ticketId}/messages`, {
                message: message,
                is_internal: isInternal
            });
            if (response.success) {
                document.getElementById('new-message').value = '';
                document.getElementById('is-internal').checked = false;
                loadTicket();
            }
        } catch (e) {
            ERP.toast.error('Failed to send message');
        }
    }

    async function saveAnydeskId() {
        const id = document.getElementById('add-anydesk').value.trim();
        if (!id) return;

        try {
            await ERP.api.put(`/support/tickets/${ticketId}`, { anydesk_id: id });
            ERP.toast.success('Anydesk ID saved');
            loadTicket();
        } catch (e) {
            ERP.toast.error('Failed to save Anydesk ID');
        }
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        ERP.toast.success('Copied to clipboard');
    }

    // Helpers
    function getCategoryIcon(cat) {
        const icons = { bug: '🐛', feature: '✨', billing: '💳', usability: '🎨', performance: '⚡', security: '🔒', other: '📋' };
        return icons[cat] || '📋';
    }

    function getPriorityBadge(p) {
        return { urgent: 'error', high: 'warning', medium: 'primary', low: 'secondary' }[p] || 'secondary';
    }

    function getStatusBadge(s) {
        return { new: 'primary', open: 'primary', in_progress: 'warning', awaiting_info: 'secondary', resolved: 'success', closed: 'secondary' }[s] || 'secondary';
    }

    function formatStatus(s) {
        return s.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function formatDateTime(d) {
        return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<style>
    .ticket-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: var(--space-4);
        border-bottom: 1px solid var(--border-color);
    }

    .ticket-number {
        font-size: var(--text-sm);
        color: var(--text-muted);
        font-family: monospace;
    }

    .ticket-title {
        font-size: var(--text-xl);
        margin: var(--space-1) 0;
    }

    .ticket-meta {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        display: flex;
        gap: var(--space-2);
    }

    .ticket-header-badges {
        display: flex;
        gap: var(--space-2);
        flex-shrink: 0;
    }

    .ticket-controls {
        display: flex;
        gap: var(--space-4);
        padding: var(--space-3) var(--space-4);
        background: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color);
    }

    .ticket-controls .form-group {
        margin: 0;
    }

    .ticket-controls label {
        font-size: var(--text-xs);
        margin-bottom: var(--space-1);
    }

    .ticket-system-info {
        padding: var(--space-3) var(--space-4);
        background: var(--bg-tertiary);
    }

    .ticket-system-info h4 {
        font-size: var(--text-sm);
        margin-bottom: var(--space-2);
        color: var(--text-secondary);
    }

    .system-info-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: var(--space-3);
        font-size: var(--text-sm);
    }

    .ticket-grid {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: var(--space-4);
    }

    .messages-container {
        max-height: 500px;
        overflow-y: auto;
        padding: var(--space-4);
    }

    .message {
        padding: var(--space-3);
        border-radius: var(--radius-lg);
        margin-bottom: var(--space-3);
        max-width: 85%;
    }

    .message.own {
        background: var(--bg-secondary);
        margin-left: auto;
    }

    .message.support {
        background: var(--primary-50);
        margin-right: auto;
    }

    .message.internal {
        background: #fff3cd;
        border: 1px dashed #ffc107;
    }

    .message-header {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        margin-bottom: var(--space-2);
        font-size: var(--text-sm);
    }

    .message-author {
        font-weight: 600;
    }

    .message-time {
        color: var(--text-muted);
        margin-left: auto;
    }

    .message-body {
        line-height: 1.5;
    }

    .message-form {
        padding: var(--space-3) var(--space-4);
        border-top: 1px solid var(--border-color);
    }

    .message-form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: var(--space-2);
    }

    .internal-note-toggle {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }

    .anydesk-id-display {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        margin-bottom: var(--space-3);
    }

    .anydesk-id-display code {
        background: var(--bg-secondary);
        padding: var(--space-1) var(--space-2);
        border-radius: var(--radius-sm);
        font-size: var(--text-lg);
    }

    .attachment-item {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-2) 0;
        border-bottom: 1px solid var(--border-color);
    }

    .attachment-item:last-child {
        border-bottom: none;
    }

    @media (max-width: 992px) {
        .ticket-grid {
            grid-template-columns: 1fr;
        }

        .system-info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .ticket-header {
            flex-direction: column;
            gap: var(--space-3);
        }

        .ticket-controls {
            flex-direction: column;
        }
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
