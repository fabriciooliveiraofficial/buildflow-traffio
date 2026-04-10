<?php
$title = 'Ticket Detail';
$page = 'support-detail';
$devUser = $_SESSION['dev_user'] ?? null;
$ticketId = $GLOBALS['params']['id'] ?? null;

ob_start();
?>

<div id="ticket-detail">
    <!-- Back Button -->
    <div style="margin-bottom: 24px;">
        <a href="/dev/support"
            style="color: var(--dev-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12" />
                <polyline points="12 19 5 12 12 5" />
            </svg>
            Back to All Tickets
        </a>
    </div>

    <!-- Loading State -->
    <div id="loading-state" class="card" style="text-align: center; padding: 60px;">
        <p style="color: var(--dev-muted);">Loading ticket...</p>
    </div>

    <!-- Ticket Content -->
    <div id="ticket-content" style="display: none;">
        <div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px;">
            <!-- Main Content -->
            <div>
                <!-- Ticket Header -->
                <div class="card" style="margin-bottom: 24px;">
                    <div style="padding: 20px; border-bottom: 1px solid var(--dev-border);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <span id="ticket-number"
                                        style="color: var(--dev-muted); font-family: monospace;"></span>
                                    <span class="tenant-badge" id="ticket-tenant"></span>
                                </div>
                                <h1 id="ticket-subject" style="font-size: 24px; margin: 0 0 12px;"></h1>
                                <div id="ticket-meta" style="font-size: 14px; color: var(--dev-muted);"></div>
                            </div>
                            <div id="ticket-badges" style="display: flex; gap: 8px;"></div>
                        </div>
                    </div>

                    <!-- System Info -->
                    <div id="system-info" style="padding: 16px 20px; background: rgba(0,0,0,0.2);"></div>
                </div>

                <!-- Messages -->
                <div class="card">
                    <div class="card-header" style="padding: 16px 20px; border-bottom: 1px solid var(--dev-border);">
                        <h3 style="margin: 0; font-size: 16px;">Conversation</h3>
                    </div>
                    <div id="messages-container" style="max-height: 500px; overflow-y: auto; padding: 20px;"></div>

                    <!-- Reply Form -->
                    <div style="padding: 16px 20px; border-top: 1px solid var(--dev-border);">
                        <textarea id="new-message" class="form-control" placeholder="Type your reply..." rows="3"
                            style="margin-bottom: 12px;"></textarea>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label
                                style="display: flex; align-items: center; gap: 8px; color: var(--dev-muted); font-size: 14px; cursor: pointer;">
                                <input type="checkbox" id="is-internal">
                                <span style="color: #fbbf24;">📝 Internal note (not visible to user)</span>
                            </label>
                            <button class="btn btn-primary" onclick="sendMessage()">Send Reply</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Quick Actions -->
                <div class="card" style="margin-bottom: 16px;">
                    <div style="padding: 16px; border-bottom: 1px solid var(--dev-border);">
                        <h4 style="margin: 0; font-size: 14px; color: var(--dev-muted);">Quick Actions</h4>
                    </div>
                    <div style="padding: 16px;">
                        <button class="btn btn-primary" style="width: 100%; margin-bottom: 8px;" onclick="assignToMe()">
                            Assign to Me
                        </button>
                        <button class="btn btn-secondary" style="width: 100%;" onclick="changeStatus('resolved')">
                            Mark Resolved
                        </button>
                    </div>
                </div>

                <!-- Status & Priority -->
                <div class="card" style="margin-bottom: 16px;">
                    <div style="padding: 16px; border-bottom: 1px solid var(--dev-border);">
                        <h4 style="margin: 0; font-size: 14px; color: var(--dev-muted);">Ticket Details</h4>
                    </div>
                    <div style="padding: 16px;">
                        <div style="margin-bottom: 16px;">
                            <label
                                style="display: block; font-size: 13px; color: var(--dev-muted); margin-bottom: 6px;">Status</label>
                            <select id="update-status" class="form-select"
                                onchange="updateTicketField('status', this.value)">
                                <option value="new">New</option>
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="awaiting_info">Awaiting Info</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 16px;">
                            <label
                                style="display: block; font-size: 13px; color: var(--dev-muted); margin-bottom: 6px;">Priority</label>
                            <select id="update-priority" class="form-select"
                                onchange="updateTicketField('priority', this.value)">
                                <option value="low">🟢 Low</option>
                                <option value="medium">🟡 Medium</option>
                                <option value="high">🟠 High</option>
                                <option value="urgent">🔴 Urgent</option>
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 13px; color: var(--dev-muted); margin-bottom: 6px;">Category</label>
                            <select id="update-category" class="form-select"
                                onchange="updateTicketField('category', this.value)">
                                <option value="bug">🐛 Bug</option>
                                <option value="feature">✨ Feature</option>
                                <option value="billing">💳 Billing</option>
                                <option value="usability">🎨 Usability</option>
                                <option value="performance">⚡ Performance</option>
                                <option value="security">🔒 Security</option>
                                <option value="other">📋 Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Assignment -->
                <div class="card" style="margin-bottom: 16px;">
                    <div style="padding: 16px; border-bottom: 1px solid var(--dev-border);">
                        <h4 style="margin: 0; font-size: 14px; color: var(--dev-muted);">Assignment</h4>
                    </div>
                    <div style="padding: 16px;">
                        <select id="update-assigned" class="form-select"
                            onchange="updateTicketField('assigned_to', this.value)">
                            <option value="">Unassigned</option>
                        </select>
                        <div id="assigned-info" style="margin-top: 12px; font-size: 13px; color: var(--dev-muted);">
                        </div>
                    </div>
                </div>

                <!-- Remote Support -->
                <div class="card" style="margin-bottom: 16px;">
                    <div style="padding: 16px; border-bottom: 1px solid var(--dev-border);">
                        <h4 style="margin: 0; font-size: 14px; color: var(--dev-muted);">Remote Support</h4>
                    </div>
                    <div style="padding: 16px;">
                        <div id="anydesk-info" style="margin-bottom: 12px;">
                            <span style="color: var(--dev-muted);">No Anydesk ID</span>
                        </div>
                        <a id="anydesk-connect" href="#" target="_blank" class="btn btn-sm btn-secondary"
                            style="display: none; width: 100%;">
                            🖥️ Connect via Anydesk
                        </a>
                    </div>
                </div>

                <!-- Reporter Info -->
                <div class="card">
                    <div style="padding: 16px; border-bottom: 1px solid var(--dev-border);">
                        <h4 style="margin: 0; font-size: 14px; color: var(--dev-muted);">Reporter</h4>
                    </div>
                    <div id="reporter-info" style="padding: 16px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ticketId = <?= json_encode($ticketId) ?>;
    const currentUserId = <?= json_encode($devUser['id'] ?? null) ?>;
    let currentTicket = null;

    document.addEventListener('DOMContentLoaded', async function () {
        if (!ticketId) {
            document.getElementById('loading-state').innerHTML = '<p style="color: #f87171;">Ticket not found</p>';
            return;
        }
        await loadAssignees();
        await loadTicket();
    });

    async function loadAssignees() {
        try {
            const response = await ERP.api.get('/employees?per_page=100');
            if (response.success && response.data) {
                const select = document.getElementById('update-assigned');
                response.data.forEach(e => {
                    select.innerHTML += `<option value="${e.user_id || ''}">${e.first_name} ${e.last_name}</option>`;
                });
            }
        } catch (e) {
            console.log('Employees API error');
        }
    }

    async function loadTicket() {
        try {
            const response = await ERP.api.get(`/support/tickets/${ticketId}`);
            if (response.success) {
                currentTicket = response.data.ticket;
                document.getElementById('loading-state').style.display = 'none';
                document.getElementById('ticket-content').style.display = 'block';
                renderTicket(response.data);
            } else {
                document.getElementById('loading-state').innerHTML = '<p style="color: #f87171;">Ticket not found</p>';
            }
        } catch (e) {
            console.error('Failed to load ticket:', e);
            document.getElementById('loading-state').innerHTML = `<p style="color: #f87171;">Failed to load: ${e.message}</p>`;
        }
    }

    function renderTicket(data) {
        const t = data.ticket;

        // Header
        document.getElementById('ticket-number').textContent = t.ticket_number;
        document.getElementById('ticket-tenant').textContent = t.tenant_name || 'Tenant ' + t.tenant_id;
        document.getElementById('ticket-subject').textContent = t.subject;

        const reporter = `${t.reporter_first_name || ''} ${t.reporter_last_name || ''}`.trim() || t.reporter_email;
        document.getElementById('ticket-meta').innerHTML = `
            Opened by <strong>${escapeHtml(reporter)}</strong> on ${formatDate(t.created_at)}
        `;

        // Badges
        document.getElementById('ticket-badges').innerHTML = `
            ${getPriorityBadge(t.priority)}
            ${getStatusBadge(t.status)}
            <span style="background: #334155; padding: 4px 12px; border-radius: 6px; font-size: 13px;">${getCategoryIcon(t.category)} ${t.category}</span>
        `;

        // System Info
        if (t.os_name || t.browser_name) {
            document.getElementById('system-info').innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; font-size: 13px;">
                    <div><strong>OS:</strong> ${t.os_name || '-'} ${t.os_version || ''}</div>
                    <div><strong>Browser:</strong> ${t.browser_name || '-'} ${t.browser_version || ''}</div>
                    <div><strong>Screen:</strong> ${t.screen_resolution || '-'}</div>
                    <div><strong>Timezone:</strong> ${t.timezone || '-'}</div>
                </div>
            `;
        } else {
            document.getElementById('system-info').style.display = 'none';
        }

        // Sidebar selects
        document.getElementById('update-status').value = t.status;
        document.getElementById('update-priority').value = t.priority;
        document.getElementById('update-category').value = t.category;
        if (t.assigned_to) {
            document.getElementById('update-assigned').value = t.assigned_to;
        }

        // Assigned info
        if (t.assignee_first_name) {
            document.getElementById('assigned-info').innerHTML = `
                Assigned to ${t.assignee_first_name} ${t.assignee_last_name || ''}<br>
                <small>${t.assigned_at ? formatDate(t.assigned_at) : ''}</small>
            `;
        }

        // Anydesk
        if (t.anydesk_id) {
            document.getElementById('anydesk-info').innerHTML = `
                <div style="font-family: monospace; font-size: 18px; color: var(--dev-text); margin-bottom: 8px;">${t.anydesk_id}</div>
                <button class="btn btn-xs btn-secondary" onclick="copyToClipboard('${t.anydesk_id}')">Copy ID</button>
            `;
            document.getElementById('anydesk-connect').style.display = 'block';
            document.getElementById('anydesk-connect').href = `anydesk:${t.anydesk_id}`;
        }

        // Reporter
        document.getElementById('reporter-info').innerHTML = `
            <div style="font-weight: 500; margin-bottom: 4px;">${escapeHtml(reporter)}</div>
            <div style="font-size: 13px; color: var(--dev-muted); margin-bottom: 8px;">${escapeHtml(t.reporter_email || '')}</div>
            ${t.project_name ? `<div style="font-size: 13px;">Project: <strong>${escapeHtml(t.project_name)}</strong></div>` : ''}
        `;

        // Messages
        renderMessages(data.messages);
    }

    function renderMessages(messages) {
        const container = document.getElementById('messages-container');

        if (!messages || messages.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: var(--dev-muted); padding: 40px;">No messages yet</p>';
            return;
        }

        container.innerHTML = messages.map(m => {
            const isInternal = m.is_internal == 1;
            const isSupport = ['admin', 'super_admin', 'developer'].includes(m.role);

            return `
                <div style="
                    padding: 16px;
                    border-radius: 12px;
                    margin-bottom: 16px;
                    ${isInternal ? 'background: rgba(251, 191, 36, 0.1); border: 1px dashed #fbbf24;' :
                    isSupport ? 'background: rgba(124, 58, 237, 0.1); margin-left: 40px;' :
                        'background: rgba(255,255,255,0.05); margin-right: 40px;'}
                ">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                        <span style="font-weight: 500;">
                            ${escapeHtml(m.first_name || '')} ${escapeHtml(m.last_name || '')}
                            ${isInternal ? '<span style="color: #fbbf24; margin-left: 8px;">📝 Internal</span>' : ''}
                            ${isSupport ? '<span style="color: #a78bfa; margin-left: 8px;">Support</span>' : ''}
                        </span>
                        <span style="color: var(--dev-muted);">${formatDateTime(m.created_at)}</span>
                    </div>
                    <div style="line-height: 1.6; white-space: pre-wrap;">${escapeHtml(m.message)}</div>
                </div>
            `;
        }).join('');

        container.scrollTop = container.scrollHeight;
    }

    async function sendMessage() {
        const message = document.getElementById('new-message').value.trim();
        if (!message) return;

        const isInternal = document.getElementById('is-internal').checked;

        try {
            await ERP.api.post(`/support/tickets/${ticketId}/messages`, {
                message,
                is_internal: isInternal
            });
            document.getElementById('new-message').value = '';
            document.getElementById('is-internal').checked = false;
            ERP.toast.success('Reply sent');
            loadTicket();
        } catch (e) {
            ERP.toast.error('Failed to send');
        }
    }

    async function updateTicketField(field, value) {
        try {
            const data = {};
            data[field] = value;
            await ERP.api.put(`/support/tickets/${ticketId}`, data);
            ERP.toast.success('Updated');
        } catch (e) {
            ERP.toast.error('Failed to update');
        }
    }

    async function assignToMe() {
        await updateTicketField('assigned_to', currentUserId);
        loadTicket();
    }

    async function changeStatus(status) {
        await updateTicketField('status', status);
        loadTicket();
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        ERP.toast.success('Copied');
    }

    // Helpers
    function getCategoryIcon(cat) {
        const icons = { bug: '🐛', feature: '✨', billing: '💳', usability: '🎨', performance: '⚡', security: '🔒', other: '📋' };
        return icons[cat] || '📋';
    }

    function getPriorityBadge(p) {
        const colors = { urgent: '#f87171', high: '#fb923c', medium: '#fbbf24', low: '#4ade80' };
        return `<span style="background: ${colors[p]}22; color: ${colors[p]}; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 500;">${p}</span>`;
    }

    function getStatusBadge(s) {
        const colors = { new: '#60a5fa', open: '#818cf8', in_progress: '#fbbf24', awaiting_info: '#94a3b8', resolved: '#4ade80', closed: '#64748b' };
        return `<span style="background: ${colors[s]}22; color: ${colors[s]}; padding: 4px 12px; border-radius: 6px; font-size: 13px;">${s.replace(/_/g, ' ')}</span>`;
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function formatDateTime(d) {
        return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/dev.php';
?>
