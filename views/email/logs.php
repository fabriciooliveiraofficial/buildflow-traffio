<?php
$pageTitle = 'Sent Emails';
$activeNav = 'email';
include APP_PATH . '/../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1>Sent Emails</h1>
        <p class="text-muted">View history of all emails sent from your account</p>
    </div>
    <div class="flex gap-2">
        <a href="/email/compose" class="btn btn-primary">Compose Email</a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="grid grid-cols-4 gap-4">
            <div class="form-group mb-0">
                <label class="form-label">Search</label>
                <input type="text" class="form-input" id="search-input" placeholder="Search by email or subject..."
                    oninput="debounceSearch()">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Status</label>
                <select class="form-select" id="status-filter" onchange="loadLogs()">
                    <option value="">All</option>
                    <option value="sent">Sent</option>
                    <option value="delivered">Delivered</option>
                    <option value="opened">Opened</option>
                    <option value="failed">Failed</option>
                    <option value="bounced">Bounced</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Context</label>
                <select class="form-select" id="context-filter" onchange="loadLogs()">
                    <option value="">All</option>
                    <option value="invoice">Invoices</option>
                    <option value="estimate">Estimates</option>
                    <option value="project">Projects</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Date Range</label>
                <select class="form-select" id="date-filter" onchange="loadLogs()">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-4 gap-4 mb-4">
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold" id="stat-total">0</div>
            <div class="text-sm text-muted">Total Sent</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-success" id="stat-delivered">0</div>
            <div class="text-sm text-muted">Delivered</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-info" id="stat-opened">0</div>
            <div class="text-sm text-muted">Opened</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-error" id="stat-failed">0</div>
            <div class="text-sm text-muted">Failed</div>
        </div>
    </div>
</div>

<!-- Email Logs Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table" id="logs-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Context</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="logs-body">
                <tr>
                    <td colspan="6" class="text-center text-muted">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <div class="flex justify-between items-center">
            <div class="text-sm text-muted" id="pagination-info">Showing 0 of 0</div>
            <div class="flex gap-2">
                <button class="btn btn-secondary btn-sm" id="prev-btn" onclick="prevPage()" disabled>Previous</button>
                <button class="btn btn-secondary btn-sm" id="next-btn" onclick="nextPage()" disabled>Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Email Detail Modal -->
<div class="modal" id="email-detail-modal">
    <div class="modal-header">
        <h3 class="modal-title">Email Details</h3>
        <button class="modal-close" onclick="Modal.close('email-detail-modal')">×</button>
    </div>
    <div class="modal-body">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <div class="text-sm text-muted">Recipient</div>
                <div id="detail-recipient" class="font-medium"></div>
            </div>
            <div>
                <div class="text-sm text-muted">Sent At</div>
                <div id="detail-date" class="font-medium"></div>
            </div>
        </div>
        <div class="mb-4">
            <div class="text-sm text-muted">Subject</div>
            <div id="detail-subject" class="font-medium"></div>
        </div>
        <div class="mb-4">
            <div class="text-sm text-muted">Status</div>
            <div id="detail-status"></div>
        </div>
        <div class="mb-4" id="detail-error-container" style="display: none;">
            <div class="text-sm text-muted">Error</div>
            <div id="detail-error" class="text-error"></div>
        </div>
        <div class="mb-4" id="detail-tracking" style="display: none;">
            <div class="text-sm text-muted mb-2">Tracking</div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-muted">Opened:</span>
                    <span id="detail-opened">-</span>
                </div>
                <div>
                    <span class="text-muted">Clicks:</span>
                    <span id="detail-clicks">-</span>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-secondary" onclick="Modal.close('email-detail-modal')">Close</button>
        <button class="btn btn-primary" id="resend-btn" onclick="resendEmail()">Resend</button>
    </div>
</div>

<script>
    let currentPage = 1;
    let perPage = 25;
    let totalPages = 1;
    let currentLogId = null;
    let searchTimeout = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadLogs();
    });

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadLogs, 300);
    }

    async function loadLogs() {
        const search = document.getElementById('search-input').value;
        const status = document.getElementById('status-filter').value;
        const context = document.getElementById('context-filter').value;

        let url = '/email/logs?page=' + currentPage + '&per_page=' + perPage;
        if (status) url += '&status=' + status;
        if (context) url += '&context_type=' + context;
        if (search) url += '&search=' + encodeURIComponent(search);

        try {
            const response = await ERP.api.get(url);
            if (response.success) {
                renderLogs(response.data.logs);
                updatePagination(response.data.pagination);
                updateStats(response.data.logs);
            }
        } catch (error) {
            console.error('Failed to load logs:', error);
            document.getElementById('logs-body').innerHTML =
                '<tr><td colspan="6" class="text-center text-error">Failed to load email logs</td></tr>';
        }
    }

    function renderLogs(logs) {
        const tbody = document.getElementById('logs-body');

        if (!logs || logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No emails found</td></tr>';
            return;
        }

        tbody.innerHTML = logs.map(log => {
            const statusClass = getStatusClass(log.status);
            const contextLink = log.context_type && log.context_id
                ? '<a href="/' + log.context_type + 's/' + log.context_id + '" class="badge">' + log.context_type + '</a>'
                : '-';

            return '<tr>' +
                '<td>' + formatDate(log.sent_at || log.created_at) + '</td>' +
                '<td>' +
                '<div class="font-medium">' + (log.to_name || '') + '</div>' +
                '<div class="text-sm text-muted">' + log.to_email + '</div>' +
                '</td>' +
                '<td class="truncate" style="max-width: 250px;">' + (log.subject || '-') + '</td>' +
                '<td>' + contextLink + '</td>' +
                '<td><span class="badge ' + statusClass + '">' + log.status + '</span></td>' +
                '<td>' +
                '<button class="btn btn-sm btn-secondary" onclick="viewDetails(' + log.id + ')" title="View Details">View</button>' +
                '</td>' +
                '</tr>';
        }).join('');
    }

    function getStatusClass(status) {
        switch (status) {
            case 'sent': return 'info';
            case 'delivered': return 'success';
            case 'opened': return 'success';
            case 'clicked': return 'success';
            case 'failed': return 'error';
            case 'bounced': return 'error';
            default: return '';
        }
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function updatePagination(pagination) {
        if (!pagination) return;

        currentPage = pagination.current_page;
        totalPages = pagination.total_pages;

        document.getElementById('pagination-info').textContent =
            'Showing page ' + currentPage + ' of ' + totalPages + ' (' + pagination.total + ' total)';

        document.getElementById('prev-btn').disabled = currentPage <= 1;
        document.getElementById('next-btn').disabled = currentPage >= totalPages;
    }

    function updateStats(logs) {
        if (!logs) return;

        const total = logs.length;
        const delivered = logs.filter(l => l.status === 'delivered' || l.status === 'sent').length;
        const opened = logs.filter(l => l.status === 'opened').length;
        const failed = logs.filter(l => l.status === 'failed' || l.status === 'bounced').length;

        document.getElementById('stat-total').textContent = total;
        document.getElementById('stat-delivered').textContent = delivered;
        document.getElementById('stat-opened').textContent = opened;
        document.getElementById('stat-failed').textContent = failed;
    }

    function prevPage() {
        if (currentPage > 1) {
            currentPage--;
            loadLogs();
        }
    }

    function nextPage() {
        if (currentPage < totalPages) {
            currentPage++;
            loadLogs();
        }
    }

    async function viewDetails(logId) {
        currentLogId = logId;

        try {
            const response = await ERP.api.get('/email/logs?per_page=1000');
            if (response.success) {
                const log = response.data.logs.find(l => l.id == logId);
                if (log) {
                    document.getElementById('detail-recipient').textContent =
                        (log.to_name ? log.to_name + ' <' + log.to_email + '>' : log.to_email);
                    document.getElementById('detail-date').textContent = formatDate(log.sent_at);
                    document.getElementById('detail-subject').textContent = log.subject || '-';
                    document.getElementById('detail-status').innerHTML =
                        '<span class="badge ' + getStatusClass(log.status) + '">' + log.status + '</span>';

                    if (log.error_message) {
                        document.getElementById('detail-error-container').style.display = 'block';
                        document.getElementById('detail-error').textContent = log.error_message;
                    } else {
                        document.getElementById('detail-error-container').style.display = 'none';
                    }

                    if (log.opened_at || log.clicked_at) {
                        document.getElementById('detail-tracking').style.display = 'block';
                        document.getElementById('detail-opened').textContent =
                            log.opened_at ? formatDate(log.opened_at) + ' (' + log.opened_count + 'x)' : '-';
                        document.getElementById('detail-clicks').textContent =
                            log.clicked_at ? log.clicked_count + ' clicks' : '-';
                    } else {
                        document.getElementById('detail-tracking').style.display = 'none';
                    }

                    document.getElementById('resend-btn').style.display =
                        log.status === 'failed' ? 'inline-block' : 'none';

                    Modal.open('email-detail-modal');
                }
            }
        } catch (error) {
            ERP.toast.error('Failed to load email details');
        }
    }

    async function resendEmail() {
        if (!currentLogId) return;

        try {
            const response = await ERP.api.post('/email/resend/' + currentLogId, {});
            if (response.success) {
                ERP.toast.success('Email queued for resending');
                Modal.close('email-detail-modal');
                loadLogs();
            } else {
                ERP.toast.error(response.message || 'Failed to resend');
            }
        } catch (error) {
            ERP.toast.error('Failed to resend email');
        }
    }
</script>

<?php include APP_PATH . '/../views/layouts/footer.php'; ?>
