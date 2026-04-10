<?php
$title = 'Support Console';
$page = 'support-list';
$devUser = $_SESSION['dev_user'] ?? null;

ob_start();
?>

<!-- Stats Dashboard -->
<div class="grid grid-cols-5 mb-6" id="stats-row">
    <div class="stat-card">
        <div class="stat-value" id="stat-total">-</div>
        <div class="stat-label">Total Tickets</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="stat-new" style="color: #60a5fa;">-</div>
        <div class="stat-label">New</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="stat-open" style="color: #fbbf24;">-</div>
        <div class="stat-label">Open / In Progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="stat-urgent" style="color: #f87171;">-</div>
        <div class="stat-label">Urgent</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="stat-resolved" style="color: #4ade80;">-</div>
        <div class="stat-label">Resolved Today</div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="filters-row" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
            <div class="form-group" style="margin: 0;">
                <select id="filter-tenant" class="form-select" onchange="loadTickets()" style="min-width: 180px;">
                    <option value="">All Tenants</option>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
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
            <div class="form-group" style="margin: 0;">
                <select id="filter-priority" class="form-select" onchange="loadTickets()">
                    <option value="">All Priority</option>
                    <option value="urgent">🔴 Urgent</option>
                    <option value="high">🟠 High</option>
                    <option value="medium">🟡 Medium</option>
                    <option value="low">🟢 Low</option>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <select id="filter-category" class="form-select" onchange="loadTickets()">
                    <option value="">All Categories</option>
                    <option value="bug">🐛 Bug</option>
                    <option value="feature">✨ Feature</option>
                    <option value="billing">💳 Billing</option>
                    <option value="usability">🎨 Usability</option>
                    <option value="performance">⚡ Performance</option>
                    <option value="security">🔒 Security</option>
                </select>
            </div>
            <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                <input type="text" id="filter-search" class="form-control" placeholder="Search tickets..."
                    onkeyup="debounceSearch()">
            </div>
            <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
        </div>
    </div>
</div>

<!-- Bulk Actions Bar -->
<div class="card mb-4" id="bulk-actions" style="display: none;">
    <div class="card-body" style="display: flex; align-items: center; gap: 16px; padding: 12px 16px;">
        <span id="selected-count">0 selected</span>
        <button class="btn btn-sm btn-secondary" onclick="bulkAssignToMe()">
            Assign to Me
        </button>
        <button class="btn btn-sm btn-secondary" onclick="bulkChangeStatus('in_progress')">
            Mark In Progress
        </button>
        <button class="btn btn-sm btn-secondary" onclick="bulkChangeStatus('resolved')">
            Mark Resolved
        </button>
        <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
            Clear Selection
        </button>
    </div>
</div>

<!-- Tickets Table -->
<div class="card">
    <div class="table-container" style="overflow-x: auto;">
        <table class="table" id="tickets-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                    </th>
                    <th>Ticket</th>
                    <th>Tenant</th>
                    <th>Subject</th>
                    <th>Reporter</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tickets-body">
                <tr>
                    <td colspan="11" style="text-align: center; padding: 40px; color: var(--dev-muted);">Loading
                        tickets...</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div id="pagination" class="card-footer"
        style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px;"></div>
</div>

<script>
    let allTickets = [];
    let selectedTickets = new Set();
    let searchTimeout = null;
    const currentUserId = <?= json_encode($devUser['id'] ?? null) ?>;

    document.addEventListener('DOMContentLoaded', async function () {
        // Load tenants for filter
        await loadTenants();

        // Apply URL params to filters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status')) document.getElementById('filter-status').value = urlParams.get('status');
        if (urlParams.get('priority')) document.getElementById('filter-priority').value = urlParams.get('priority');

        // Load tickets
        await loadTickets();
        await loadStats();
    });

    async function loadTenants() {
        try {
            // Try to load tenants - this endpoint may not exist
            const response = await ERP.api.get('/tenants');
            if (response.success && response.data) {
                const select = document.getElementById('filter-tenant');
                response.data.forEach(t => {
                    select.innerHTML += `<option value="${t.id}">${t.name}</option>`;
                });
            }
        } catch (e) {
            // Tenants API not available - that's okay, filter will just show "All Tenants"
            console.log('Tenants filter disabled (API not available)');
        }
    }

    async function loadStats() {
        try {
            const response = await ERP.api.get('/support/tickets/stats');
            if (response.success) {
                const c = response.data.counts;
                document.getElementById('stat-total').textContent = c.total || 0;
                document.getElementById('stat-new').textContent = c.new_count || 0;
                document.getElementById('stat-open').textContent = (parseInt(c.open_count) || 0) + (parseInt(c.in_progress_count) || 0);
                document.getElementById('stat-urgent').textContent = c.urgent_open || 0;
                document.getElementById('stat-resolved').textContent = c.resolved_count || 0;
            }
        } catch (e) {
            console.error('Failed to load stats:', e);
        }
    }

    async function loadTickets() {
        const tenant = document.getElementById('filter-tenant').value;
        const status = document.getElementById('filter-status').value;
        const priority = document.getElementById('filter-priority').value;
        const category = document.getElementById('filter-category').value;
        const search = document.getElementById('filter-search').value;

        let url = '/support/tickets?per_page=50&';
        if (tenant) url += `tenant_id=${tenant}&`;
        if (status) url += `status=${status}&`;
        if (priority) url += `priority=${priority}&`;
        if (category) url += `category=${category}&`;
        if (search) url += `search=${encodeURIComponent(search)}&`;

        try {
            const response = await ERP.api.get(url);
            if (response.success) {
                allTickets = response.data.tickets || [];
                renderTickets(allTickets);
            }
        } catch (e) {
            console.error('Failed to load tickets:', e);
            document.getElementById('tickets-body').innerHTML =
                '<tr><td colspan="11" style="text-align: center; padding: 40px; color: #f87171;">Failed to load tickets</td></tr>';
        }
    }

    function renderTickets(tickets) {
        const tbody = document.getElementById('tickets-body');

        if (!tickets || tickets.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 60px; color: var(--dev-muted);">No tickets found</td></tr>';
            return;
        }

        tbody.innerHTML = tickets.map(t => {
            const reporterName = `${t.reporter_first_name || ''} ${t.reporter_last_name || ''}`.trim() || t.reporter_email || '-';
            const assigneeName = t.assignee_first_name ? `${t.assignee_first_name} ${t.assignee_last_name || ''}`.trim() : '<span style="color: var(--dev-muted);">Unassigned</span>';

            return `
                <tr data-id="${t.id}">
                    <td>
                        <input type="checkbox" class="ticket-checkbox" data-id="${t.id}" onchange="updateSelection()">
                    </td>
                    <td>
                        <a href="/dev/support/${t.id}" style="color: #a78bfa; font-weight: 500;">${t.ticket_number}</a>
                    </td>
                    <td>
                        <span class="tenant-badge">${escapeHtml(t.tenant_name || 'Tenant ' + t.tenant_id)}</span>
                    </td>
                    <td style="max-width: 250px;">
                        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(t.subject)}</div>
                        ${t.project_name ? `<div style="font-size: 12px; color: var(--dev-muted);">${escapeHtml(t.project_name)}</div>` : ''}
                    </td>
                    <td style="font-size: 13px;">${escapeHtml(reporterName)}</td>
                    <td>${getCategoryBadge(t.category)}</td>
                    <td>${getPriorityBadge(t.priority)}</td>
                    <td>${getStatusBadge(t.status)}</td>
                    <td style="font-size: 13px;">${assigneeName}</td>
                    <td style="font-size: 13px; color: var(--dev-muted);">${formatTimeAgo(t.created_at)}</td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="/dev/support/${t.id}" class="btn btn-sm btn-secondary">View</a>
                            ${!t.assigned_to ? `<button class="btn btn-sm btn-primary" onclick="assignToMe(${t.id})">Take</button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function assignToMe(ticketId) {
        try {
            await ERP.api.put(`/support/tickets/${ticketId}`, { assigned_to: currentUserId });
            ERP.toast.success('Assigned to you');
            loadTickets();
        } catch (e) {
            ERP.toast.error('Failed to assign');
        }
    }

    function updateSelection() {
        selectedTickets.clear();
        document.querySelectorAll('.ticket-checkbox:checked').forEach(cb => {
            selectedTickets.add(parseInt(cb.dataset.id));
        });

        const bulkBar = document.getElementById('bulk-actions');
        if (selectedTickets.size > 0) {
            bulkBar.style.display = 'block';
            document.getElementById('selected-count').textContent = `${selectedTickets.size} selected`;
        } else {
            bulkBar.style.display = 'none';
        }
    }

    function toggleSelectAll() {
        const checked = document.getElementById('select-all').checked;
        document.querySelectorAll('.ticket-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        updateSelection();
    }

    function clearSelection() {
        document.querySelectorAll('.ticket-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('select-all').checked = false;
        updateSelection();
    }

    async function bulkAssignToMe() {
        for (const id of selectedTickets) {
            try {
                await ERP.api.put(`/support/tickets/${id}`, { assigned_to: currentUserId });
            } catch (e) { }
        }
        ERP.toast.success(`Assigned ${selectedTickets.size} tickets to you`);
        clearSelection();
        loadTickets();
    }

    async function bulkChangeStatus(status) {
        for (const id of selectedTickets) {
            try {
                await ERP.api.put(`/support/tickets/${id}`, { status });
            } catch (e) { }
        }
        ERP.toast.success(`Updated ${selectedTickets.size} tickets`);
        clearSelection();
        loadTickets();
        loadStats();
    }

    function clearFilters() {
        document.getElementById('filter-tenant').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-priority').value = '';
        document.getElementById('filter-category').value = '';
        document.getElementById('filter-search').value = '';
        loadTickets();
    }

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadTickets, 300);
    }

    // Helpers
    function getCategoryBadge(cat) {
        const icons = { bug: '🐛', feature: '✨', billing: '💳', usability: '🎨', performance: '⚡', security: '🔒', other: '📋' };
        return `<span style="font-size: 13px;">${icons[cat] || '📋'} ${cat}</span>`;
    }

    function getPriorityBadge(p) {
        const colors = { urgent: '#f87171', high: '#fb923c', medium: '#fbbf24', low: '#4ade80' };
        const labels = { urgent: '🔴 Urgent', high: '🟠 High', medium: '🟡 Medium', low: '🟢 Low' };
        return `<span style="color: ${colors[p] || '#94a3b8'}; font-size: 13px; font-weight: 500;">${labels[p] || p}</span>`;
    }

    function getStatusBadge(s) {
        const colors = {
            new: '#60a5fa',
            open: '#818cf8',
            in_progress: '#fbbf24',
            awaiting_info: '#94a3b8',
            resolved: '#4ade80',
            closed: '#64748b'
        };
        return `<span style="background: ${colors[s]}22; color: ${colors[s]}; padding: 2px 8px; border-radius: 4px; font-size: 12px;">${formatStatus(s)}</span>`;
    }

    function formatStatus(s) {
        return s.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
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
