<?php
$title = 'Support';
$page = 'support';

ob_start();
?>

<div id="support-portal">
    <!-- OS Detection Banner -->
    <div class="card mb-4" id="remote-support-banner" style="display: none;">
        <div class="remote-support-content">
            <div class="remote-support-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                    <line x1="8" y1="21" x2="16" y2="21" />
                    <line x1="12" y1="17" x2="12" y2="21" />
                </svg>
            </div>
            <div class="remote-support-info">
                <h3>Need Remote Assistance?</h3>
                <p>We detected you're using <strong id="detected-os">your computer</strong>. For faster support,
                    download Anydesk to allow our team to connect remotely.</p>
            </div>
            <div class="remote-support-actions">
                <a id="anydesk-download-btn" href="#" target="_blank" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                    Download Anydesk
                </a>
                <button class="btn btn-secondary" onclick="toggleAnydeskInput()">
                    I have Anydesk
                </button>
            </div>
        </div>
        <div class="anydesk-id-input" id="anydesk-id-section" style="display: none;">
            <label>Enter your Anydesk ID to share with support:</label>
            <div class="flex gap-2">
                <input type="text" id="anydesk-id" class="form-control" placeholder="e.g., 123-456-789"
                    pattern="[\d-]+">
                <button class="btn btn-primary" onclick="saveAnydeskId()">Save</button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="page-header mb-4">
        <div>
            <h1 class="page-title">Support Tickets</h1>
            <p class="page-subtitle">Get help with any issues or questions</p>
        </div>
        <button class="btn btn-primary" onclick="openNewTicketModal()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            New Ticket
        </button>
    </div>

    <!-- Stats Cards (Admin Only) -->
    <div class="grid grid-cols-4 mb-4" id="stats-row" style="display: none;">
        <div class="card stat-card">
            <div class="stat-value" id="stat-new">0</div>
            <div class="stat-label">New</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-open">0</div>
            <div class="stat-label">Open</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-urgent">0</div>
            <div class="stat-label">Urgent</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-resolved">0</div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="filters-row">
                <div class="form-group">
                    <select id="filter-status" class="form-select" onchange="loadTickets()">
                        <option value="">All Status</option>
                        <option value="new">New</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="awaiting_info">Awaiting Info</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <select id="filter-priority" class="form-select" onchange="loadTickets()">
                        <option value="">All Priority</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="form-group">
                    <select id="filter-category" class="form-select" onchange="loadTickets()">
                        <option value="">All Categories</option>
                        <option value="bug">Bug Report</option>
                        <option value="feature">Feature Request</option>
                        <option value="billing">Billing</option>
                        <option value="usability">Usability</option>
                        <option value="performance">Performance</option>
                        <option value="security">Security</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group flex-1">
                    <input type="text" id="filter-search" class="form-control" placeholder="Search tickets..."
                        onkeyup="debounceSearch()">
                </div>
            </div>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <div class="table-container">
            <table class="table" id="tickets-table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tickets-body">
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="pagination" class="card-footer"></div>
    </div>
</div>

<!-- New Ticket Modal -->
<div class="modal" id="new-ticket-modal">
    <div class="modal-header">
        <h3 class="modal-title">Create Support Ticket</h3>
        <button class="modal-close" onclick="Modal.close('new-ticket-modal')">×</button>
    </div>
    <div class="modal-body">
        <form id="new-ticket-form">
            <div class="form-group">
                <label>Category *</label>
                <select id="ticket-category" class="form-select" required>
                    <option value="bug">🐛 Bug Report</option>
                    <option value="feature">✨ Feature Request</option>
                    <option value="billing">💳 Billing</option>
                    <option value="usability">🎨 Usability</option>
                    <option value="performance">⚡ Performance</option>
                    <option value="security">🔒 Security</option>
                    <option value="other">📋 Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Priority *</label>
                <div class="priority-options">
                    <label class="priority-option">
                        <input type="radio" name="priority" value="low"> Low
                    </label>
                    <label class="priority-option selected">
                        <input type="radio" name="priority" value="medium" checked> Medium
                    </label>
                    <label class="priority-option">
                        <input type="radio" name="priority" value="high"> High
                    </label>
                    <label class="priority-option">
                        <input type="radio" name="priority" value="urgent"> Urgent
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Subject *</label>
                <input type="text" id="ticket-subject" class="form-control" required
                    placeholder="Brief description of the issue">
            </div>

            <div class="form-group">
                <label>Description *</label>
                <textarea id="ticket-description" class="form-control" rows="5" required
                    placeholder="Describe your issue in detail..."></textarea>
            </div>

            <div class="form-group">
                <label>Steps to Reproduce (optional)</label>
                <textarea id="ticket-steps" class="form-control" rows="3"
                    placeholder="1. Go to...&#10;2. Click on...&#10;3. See error..."></textarea>
            </div>

            <div class="form-group">
                <label>Link Project (optional)</label>
                <select id="ticket-project" class="form-select">
                    <option value="">-- Select a project --</option>
                </select>
            </div>

            <div class="form-group">
                <label>Anydesk ID (for remote support)</label>
                <input type="text" id="ticket-anydesk" class="form-control" placeholder="e.g., 123-456-789">
            </div>

            <!-- System Info Auto-captured -->
            <div class="system-info-preview">
                <div class="system-info-header">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                        <line x1="8" y1="21" x2="16" y2="21" />
                        <line x1="12" y1="17" x2="12" y2="21" />
                    </svg>
                    System Info (auto-detected)
                </div>
                <div class="system-info-content" id="system-info-display">
                    Detecting...
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('new-ticket-modal')">Cancel</button>
        <button type="submit" form="new-ticket-form" class="btn btn-primary">Submit Ticket</button>
    </div>
</div>

<script>
    // OS Detection
    const systemInfo = detectSystemInfo();
    let searchTimeout = null;

    function detectSystemInfo() {
        const ua = navigator.userAgent;
        let os = 'Unknown';
        let osVersion = '';
        let browser = 'Unknown';
        let browserVersion = '';

        // Detect OS
        if (ua.includes('Windows NT 10.0')) {
            os = 'Windows';
            osVersion = ua.includes('Windows NT 10.0; Win64') ? '10/11' : '10';
        } else if (ua.includes('Windows NT')) {
            os = 'Windows';
            osVersion = 'Legacy';
        } else if (ua.includes('Mac OS X')) {
            os = 'macOS';
            const match = ua.match(/Mac OS X (\d+[._]\d+)/);
            osVersion = match ? match[1].replace('_', '.') : '';
        } else if (ua.includes('Linux')) {
            os = 'Linux';
            if (ua.includes('Ubuntu')) osVersion = 'Ubuntu';
            else if (ua.includes('Fedora')) osVersion = 'Fedora';
            else if (ua.includes('Debian')) osVersion = 'Debian';
        } else if (ua.includes('Android')) {
            os = 'Android';
        } else if (ua.includes('iOS') || ua.includes('iPhone') || ua.includes('iPad')) {
            os = 'iOS';
        }

        // Detect Browser
        if (ua.includes('Chrome') && !ua.includes('Edg')) {
            browser = 'Chrome';
            const match = ua.match(/Chrome\/(\d+)/);
            browserVersion = match ? match[1] : '';
        } else if (ua.includes('Firefox')) {
            browser = 'Firefox';
            const match = ua.match(/Firefox\/(\d+)/);
            browserVersion = match ? match[1] : '';
        } else if (ua.includes('Safari') && !ua.includes('Chrome')) {
            browser = 'Safari';
        } else if (ua.includes('Edg')) {
            browser = 'Edge';
            const match = ua.match(/Edg\/(\d+)/);
            browserVersion = match ? match[1] : '';
        }

        return {
            os_name: os,
            os_version: osVersion,
            browser_name: browser,
            browser_version: browserVersion,
            screen_resolution: `${screen.width}x${screen.height}`,
            user_agent: ua,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        };
    }

    function getAnydeskUrl() {
        const os = systemInfo.os_name;
        if (os === 'Windows') return 'https://download.anydesk.com/AnyDesk.exe';
        if (os === 'macOS') return 'https://download.anydesk.com/anydesk.dmg';
        if (os === 'Linux') return 'https://anydesk.com/en/downloads/linux';
        return 'https://anydesk.com/downloads';
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', async function () {
        // Show OS detection banner
        document.getElementById('detected-os').textContent =
            `${systemInfo.os_name} ${systemInfo.os_version}`.trim();
        document.getElementById('anydesk-download-btn').href = getAnydeskUrl();
        document.getElementById('remote-support-banner').style.display = 'block';

        // Display system info
        document.getElementById('system-info-display').textContent =
            `OS: ${systemInfo.os_name} ${systemInfo.os_version} | Browser: ${systemInfo.browser_name} ${systemInfo.browser_version} | Screen: ${systemInfo.screen_resolution}`;

        // Load projects for dropdown
        await loadProjects();

        // Load tickets
        await loadTickets();

        // Load stats if admin
        await loadStats();

        // Form submit handler
        document.getElementById('new-ticket-form').addEventListener('submit', submitTicket);

        // Priority option styling
        document.querySelectorAll('.priority-option input').forEach(input => {
            input.addEventListener('change', function () {
                document.querySelectorAll('.priority-option').forEach(opt => opt.classList.remove('selected'));
                this.parentElement.classList.add('selected');
            });
        });
    });

    async function loadProjects() {
        try {
            const response = await ERP.api.get('/projects?per_page=100');
            if (response.success && response.data) {
                const select = document.getElementById('ticket-project');
                response.data.forEach(project => {
                    select.innerHTML += `<option value="${project.id}">${project.name}</option>`;
                });
            }
        } catch (e) {
            console.error('Failed to load projects:', e);
        }
    }

    async function loadStats() {
        try {
            const response = await ERP.api.get('/support/tickets/stats');
            if (response.success) {
                document.getElementById('stats-row').style.display = 'grid';
                const c = response.data.counts;
                document.getElementById('stat-new').textContent = c.new_count || 0;
                document.getElementById('stat-open').textContent = (c.open_count || 0) + (c.in_progress_count || 0);
                document.getElementById('stat-urgent').textContent = c.urgent_open || 0;
                document.getElementById('stat-resolved').textContent = c.resolved_count || 0;
            }
        } catch (e) {
            // Non-admin, hide stats
        }
    }

    async function loadTickets() {
        const status = document.getElementById('filter-status').value;
        const priority = document.getElementById('filter-priority').value;
        const category = document.getElementById('filter-category').value;
        const search = document.getElementById('filter-search').value;

        let url = '/support/tickets?';
        if (status) url += `status=${status}&`;
        if (priority) url += `priority=${priority}&`;
        if (category) url += `category=${category}&`;
        if (search) url += `search=${encodeURIComponent(search)}&`;

        try {
            const response = await ERP.api.get(url);
            if (response.success) {
                renderTickets(response.data.tickets);
            }
        } catch (e) {
            console.error('Failed to load tickets:', e);
            document.getElementById('tickets-body').innerHTML =
                '<tr><td colspan="7" class="text-center text-error py-4">Failed to load tickets</td></tr>';
        }
    }

    function renderTickets(tickets) {
        const tbody = document.getElementById('tickets-body');

        if (!tickets || tickets.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-6">
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            <p>No support tickets yet</p>
                            <button class="btn btn-primary btn-sm" onclick="openNewTicketModal()">Create your first ticket</button>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = tickets.map(t => `
            <tr>
                <td>
                    <a href="support/${t.id}" class="font-medium">${t.ticket_number}</a>
                </td>
                <td>
                    <div class="ticket-subject">${escapeHtml(t.subject)}</div>
                    ${t.project_name ? `<div class="text-xs text-muted">${escapeHtml(t.project_name)}</div>` : ''}
                </td>
                <td><span class="badge badge-outline">${getCategoryIcon(t.category)} ${t.category}</span></td>
                <td><span class="badge badge-${getPriorityBadge(t.priority)}">${t.priority}</span></td>
                <td><span class="badge badge-${getStatusBadge(t.status)}">${formatStatus(t.status)}</span></td>
                <td>
                    <div class="text-sm">${formatDate(t.created_at)}</div>
                    <div class="text-xs text-muted">${formatTimeAgo(t.created_at)}</div>
                </td>
                <td>
                    <a href="support/${t.id}" class="btn btn-sm btn-secondary">View</a>
                </td>
            </tr>
        `).join('');
    }

    function openNewTicketModal() {
        // Pre-fill saved Anydesk ID
        const savedId = localStorage.getItem('anydesk_id');
        if (savedId) document.getElementById('ticket-anydesk').value = savedId;
        Modal.open('new-ticket-modal');
    }

    async function submitTicket(e) {
        e.preventDefault();

        const description = document.getElementById('ticket-description').value;
        const steps = document.getElementById('ticket-steps').value;
        const fullDescription = steps ? `${description}\n\n**Steps to Reproduce:**\n${steps}` : description;

        const data = {
            category: document.getElementById('ticket-category').value,
            priority: document.querySelector('input[name="priority"]:checked').value,
            subject: document.getElementById('ticket-subject').value,
            description: fullDescription,
            project_id: document.getElementById('ticket-project').value || null,
            anydesk_id: document.getElementById('ticket-anydesk').value || null,
            ...systemInfo
        };

        try {
            const response = await ERP.api.post('/support/tickets', data);
            if (response.success) {
                ERP.toast.success('Ticket created successfully!');
                Modal.close('new-ticket-modal');
                document.getElementById('new-ticket-form').reset();
                loadTickets();
                loadStats();
            }
        } catch (e) {
            ERP.toast.error('Failed to create ticket');
            console.error(e);
        }
    }

    function toggleAnydeskInput() {
        const section = document.getElementById('anydesk-id-section');
        section.style.display = section.style.display === 'none' ? 'block' : 'none';
    }

    function saveAnydeskId() {
        const id = document.getElementById('anydesk-id').value;
        if (id) {
            localStorage.setItem('anydesk_id', id);
            ERP.toast.success('Anydesk ID saved!');
            document.getElementById('ticket-anydesk').value = id;
        }
    }

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadTickets, 300);
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

    function formatTimeAgo(d) {
        const diff = Date.now() - new Date(d).getTime();
        const mins = Math.floor(diff / 60000);
        if (mins < 60) return `${mins}m ago`;
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return `${hrs}h ago`;
        const days = Math.floor(hrs / 24);
        return `${days}d ago`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<style>
    .remote-support-content {
        display: flex;
        align-items: center;
        gap: var(--space-4);
        padding: var(--space-4);
        background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
        border-radius: var(--radius-lg);
    }

    .remote-support-icon {
        flex-shrink: 0;
        width: 64px;
        height: 64px;
        background: var(--primary-500);
        color: white;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .remote-support-info {
        flex: 1;
    }

    .remote-support-info h3 {
        margin: 0 0 var(--space-1);
        font-size: var(--text-lg);
    }

    .remote-support-info p {
        margin: 0;
        color: var(--text-secondary);
    }

    .remote-support-actions {
        display: flex;
        gap: var(--space-2);
    }

    .anydesk-id-input {
        padding: var(--space-3) var(--space-4);
        border-top: 1px solid var(--border-color);
    }

    .filters-row {
        display: flex;
        gap: var(--space-3);
        flex-wrap: wrap;
    }

    .filters-row .form-group {
        margin: 0;
    }

    .priority-options {
        display: flex;
        gap: var(--space-2);
    }

    .priority-option {
        flex: 1;
        padding: var(--space-2) var(--space-3);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        text-align: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .priority-option input {
        display: none;
    }

    .priority-option:hover {
        border-color: var(--primary-500);
    }

    .priority-option.selected {
        background: var(--primary-50);
        border-color: var(--primary-500);
        color: var(--primary-700);
    }

    .system-info-preview {
        background: var(--bg-secondary);
        border-radius: var(--radius-md);
        padding: var(--space-3);
        margin-top: var(--space-3);
    }

    .system-info-header {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        font-size: var(--text-sm);
        font-weight: 500;
        color: var(--text-secondary);
        margin-bottom: var(--space-2);
    }

    .system-info-content {
        font-size: var(--text-sm);
        color: var(--text-muted);
        font-family: monospace;
    }

    .ticket-subject {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .empty-state {
        padding: var(--space-6);
        text-align: center;
        color: var(--text-muted);
    }

    .empty-state svg {
        margin-bottom: var(--space-3);
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .remote-support-content {
            flex-direction: column;
            text-align: center;
        }

        .remote-support-actions {
            flex-direction: column;
            width: 100%;
        }

        .filters-row {
            flex-direction: column;
        }

        .priority-options {
            flex-wrap: wrap;
        }

        .priority-option {
            flex: 1 1 45%;
        }
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
