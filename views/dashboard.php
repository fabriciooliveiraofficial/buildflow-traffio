<?php
$title = 'Dashboard';
$page = 'dashboard';

ob_start();
?>

<div id="dashboard">
    <!-- Stats Cards -->
    <div class="grid grid-cols-4 mb-6">
        <div class="card stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value" id="projects-active">--</div>
                    <div class="stat-label">Active Projects</div>
                </div>
                <div class="stat-card-icon primary">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                    </svg>
                </div>
            </div>
            <div class="stat-change positive">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                </svg>
                vs last month
            </div>
        </div>

        <div class="card stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value" id="outstanding-amount">--</div>
                    <div class="stat-label">Outstanding</div>
                </div>
                <div class="stat-card-icon warning">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23" />
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                </div>
            </div>
            <div class="stat-change negative">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 18 13.5 8.5 8.5 13.5 1 6" />
                </svg>
                needs attention
            </div>
        </div>

        <div class="card stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value" id="monthly-expenses">--</div>
                    <div class="stat-label">Monthly Expenses</div>
                </div>
                <div class="stat-card-icon error">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                        <line x1="1" y1="10" x2="23" y2="10" />
                    </svg>
                </div>
            </div>
            <div class="stat-change">this month</div>
        </div>

        <div class="card stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><span id="weekly-hours">--</span>h</div>
                    <div class="stat-label">Hours This Week</div>
                </div>
                <div class="stat-card-icon success">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </div>
            </div>
            <div class="stat-change positive">on track</div>
        </div>
    </div>

    <!-- Alerts Bar -->
    <div class="card alert-banner mb-6" id="alerts-banner" style="display: none;">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4" id="alerts-summary"></div>
            <button class="btn btn-sm btn-outline" onclick="toggleAlertsPanel()">View All</button>
        </div>
    </div>

    <!-- Today's Schedule & Cash Flow -->
    <div class="grid grid-cols-2 mb-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Today's Schedule</h2>
                <span class="badge badge-primary" id="working-count">0 working</span>
            </div>
            <div class="card-body" id="schedule-content" style="max-height: 280px; overflow-y: auto;">
                <p class="text-center text-muted">Loading...</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Cash Flow Forecast</h2>
                <span class="text-sm text-muted">Next 12 weeks</span>
            </div>
            <div class="card-body" style="height: 280px;">
                <canvas id="cashflow-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Project Profitability -->
    <div class="card mb-6">
        <div class="card-header">
            <h2 class="card-title">Project Profitability</h2>
            <div class="flex gap-4">
                <div class="text-right">
                    <div class="text-sm text-muted">Total Revenue</div>
                    <div class="font-medium text-success" id="total-revenue">$0</div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-muted">Total Profit</div>
                    <div class="font-medium" id="total-profit">$0</div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <table class="table" id="profitability-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Revenue</th>
                        <th>Labor Cost</th>
                        <th>Expenses</th>
                        <th>Profit</th>
                        <th>Margin</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-2 mb-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Revenue Overview</h2>
            </div>
            <div class="card-body" style="height: 280px;">
                <canvas id="revenue-chart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Expenses by Category</h2>
            </div>
            <div class="card-body" style="height: 280px;">
                <canvas id="expenses-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Recent Projects -->
    <div class="grid grid-cols-3">
        <div class="card" style="grid-column: span 2;">
            <div class="card-header">
                <h2 class="card-title">Recent Projects</h2>
                <a href="projects" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <div class="table-container">
                <table class="table" id="recent-projects-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Budget</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Quick Actions</h2>
            </div>
            <div class="card-body flex flex-col gap-3">
                <a href="projects" class="btn btn-primary" style="width: 100%;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    New Project
                </a>
                <a href="invoices" class="btn btn-outline" style="width: 100%;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                    Create Invoice
                </a>
                <a href="time-tracking" class="btn btn-outline" style="width: 100%;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    Log Time
                </a>
                <a href="time-clock" class="btn btn-success" style="width: 100%;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    Time Clock
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Alerts Panel (Modal-like overlay) -->
<div class="modal" id="alerts-panel" style="max-width: 600px;">
    <div class="modal-header">
        <h3 class="modal-title">Alerts & Notifications</h3>
        <button class="modal-close" onclick="Modal.close('alerts-panel')">×</button>
    </div>
    <div class="modal-body" id="alerts-detail"></div>
</div>

<script>
    let cashFlowChart = null;
    let revenueChart = null;
    let expensesChart = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadStats();
        loadAlerts();
        loadSchedule();
        loadCashFlow();
        loadProfitability();
        loadCharts();
        loadRecentProjects();
    });

    async function loadStats() {
        try {
            const response = await ERP.api.get('/dashboard/stats');
            if (response.success) {
                const d = response.data;
                document.getElementById('projects-active').textContent = d.projects.active;
                document.getElementById('outstanding-amount').textContent = formatCurrency(d.financials.outstanding);
                document.getElementById('monthly-expenses').textContent = formatCurrency(d.monthly_expenses);
                document.getElementById('weekly-hours').textContent = Math.round(d.weekly_hours);
            }
        } catch (e) {
            console.error('Failed to load stats:', e);
        }
    }

    async function loadAlerts() {
        try {
            const response = await ERP.api.get('/dashboard/alerts');
            if (response.success && response.data.total_alerts > 0) {
                const d = response.data;
                const banner = document.getElementById('alerts-banner');
                banner.style.display = 'block';

                let summary = [];
                if (d.over_budget_projects.length > 0) {
                    summary.push(`<span class="alert-item error"><strong>${d.over_budget_projects.length}</strong> over-budget</span>`);
                }
                if (d.overdue_invoices.length > 0) {
                    summary.push(`<span class="alert-item warning"><strong>${d.overdue_invoices.length}</strong> overdue invoices</span>`);
                }
                if (d.pending_time_logs.count > 0) {
                    summary.push(`<span class="alert-item"><strong>${d.pending_time_logs.count}</strong> time logs pending</span>`);
                }
                if (d.pending_expenses.count > 0) {
                    summary.push(`<span class="alert-item"><strong>${d.pending_expenses.count}</strong> expenses pending</span>`);
                }

                document.getElementById('alerts-summary').innerHTML = summary.join('');

                // Build detailed panel
                let detail = '';
                if (d.over_budget_projects.length > 0) {
                    detail += '<h4 class="mb-2">Over-Budget Projects</h4><ul class="alert-list mb-4">';
                    d.over_budget_projects.forEach(p => {
                        const over = p.spent - p.budget;
                        detail += `<li><a href="projects/${p.id}">${p.name}</a> - ${formatCurrency(over)} over</li>`;
                    });
                    detail += '</ul>';
                }
                if (d.overdue_invoices.length > 0) {
                    detail += '<h4 class="mb-2">Overdue Invoices</h4><ul class="alert-list mb-4">';
                    d.overdue_invoices.forEach(inv => {
                        detail += `<li><a href="invoices/${inv.id}">#${inv.invoice_number}</a> - ${inv.client_name} - ${formatCurrency(inv.amount_due)} (${inv.days_overdue} days)</li>`;
                    });
                    detail += '</ul>';
                }
                document.getElementById('alerts-detail').innerHTML = detail || '<p class="text-muted">No alerts</p>';
            }
        } catch (e) {
            console.error('Failed to load alerts:', e);
        }
    }

    async function loadSchedule() {
        try {
            const response = await ERP.api.get('/dashboard/schedule');
            if (response.success) {
                const d = response.data;
                document.getElementById('working-count').textContent = `${d.summary.employees_working} working`;

                const container = document.getElementById('schedule-content');
                if (d.working_today.length === 0) {
                    container.innerHTML = '<p class="text-center text-muted">No time logged today yet</p>';
                    return;
                }

                container.innerHTML = d.working_today.map(w => `
                    <div class="schedule-item">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="font-medium">${w.first_name} ${w.last_name}</span>
                                <span class="text-muted text-sm">${w.job_title || ''}</span>
                            </div>
                            <span class="badge badge-secondary">${parseFloat(w.hours_today).toFixed(1)}h</span>
                        </div>
                        ${w.project_name ? `<div class="text-sm text-muted">${w.project_name}</div>` : ''}
                    </div>
                `).join('');
            }
        } catch (e) {
            console.error('Failed to load schedule:', e);
        }
    }

    async function loadCashFlow() {
        try {
            const response = await ERP.api.get('/dashboard/cash-flow');
            if (response.success) {
                const d = response.data;
                const ctx = document.getElementById('cashflow-chart');

                if (cashFlowChart) cashFlowChart.destroy();

                cashFlowChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: d.forecast.map(f => f.week),
                        datasets: [
                            {
                                label: 'Incoming',
                                data: d.forecast.map(f => f.incoming),
                                backgroundColor: '#4caf50'
                            },
                            {
                                label: 'Outgoing',
                                data: d.forecast.map(f => f.outgoing),
                                backgroundColor: '#ff9800'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        } catch (e) {
            console.error('Failed to load cash flow:', e);
        }
    }

    async function loadProfitability() {
        try {
            const response = await ERP.api.get('/dashboard/profitability');
            if (response.success) {
                const d = response.data;

                document.getElementById('total-revenue').textContent = formatCurrency(d.totals.total_revenue);
                document.getElementById('total-profit').textContent = formatCurrency(d.totals.total_profit);
                document.getElementById('total-profit').className = 'font-medium ' + (d.totals.total_profit >= 0 ? 'text-success' : 'text-error');

                const tbody = document.querySelector('#profitability-table tbody');
                if (d.projects.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No project data</td></tr>';
                    return;
                }

                tbody.innerHTML = d.projects.map(p => `
                    <tr>
                        <td>
                            <a href="projects/${p.id}" class="font-medium">${p.name}</a>
                            ${p.code ? `<div class="text-xs text-muted">${p.code}</div>` : ''}
                        </td>
                        <td>${p.client_name || '-'}</td>
                        <td>${formatCurrency(p.revenue)}</td>
                        <td>${formatCurrency(p.labor_cost)}</td>
                        <td>${formatCurrency(p.expenses)}</td>
                        <td class="${p.profit >= 0 ? 'text-success' : 'text-error'} font-medium">${formatCurrency(p.profit)}</td>
                        <td>
                            <span class="badge badge-${p.margin >= 20 ? 'success' : p.margin >= 0 ? 'warning' : 'error'}">${p.margin}%</span>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error('Failed to load profitability:', e);
        }
    }

    async function loadCharts() {
        try {
            const response = await ERP.api.get('/dashboard/charts');
            if (response.success) {
                const d = response.data;

                // Revenue chart
                const revenueCtx = document.getElementById('revenue-chart');
                if (revenueChart) revenueChart.destroy();
                revenueChart = new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: d.revenue_by_month.map(r => formatMonth(r.month)),
                        datasets: [{
                            label: 'Revenue',
                            data: d.revenue_by_month.map(r => r.total),
                            borderColor: '#4caf50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });

                // Expenses chart
                const expensesCtx = document.getElementById('expenses-chart');
                if (expensesChart) expensesChart.destroy();
                expensesChart = new Chart(expensesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: d.expenses_by_category.map(e => e.category),
                        datasets: [{
                            data: d.expenses_by_category.map(e => e.total),
                            backgroundColor: ['#2196f3', '#ff9800', '#4caf50', '#f44336', '#9c27b0', '#00bcd4']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }
        } catch (e) {
            console.error('Failed to load charts:', e);
        }
    }

    async function loadRecentProjects() {
        try {
            const response = await ERP.api.get('/projects?per_page=5&status=in_progress');
            if (response.success && response.data) {
                renderProjectsTable(response.data);
            }
        } catch (e) {
            console.error('Failed to load projects:', e);
        }
    }

    function renderProjectsTable(projects) {
        const tbody = document.querySelector('#recent-projects-table tbody');

        if (projects.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No active projects</td></tr>';
            return;
        }

        tbody.innerHTML = projects.map(project => `
        <tr>
            <td>
                <a href="projects/${project.id}" class="font-medium">${project.name}</a>
                ${project.code ? `<div class="text-xs text-muted">${project.code}</div>` : ''}
            </td>
            <td>${project.client_name || '-'}</td>
            <td>
                <span class="badge badge-${getStatusBadge(project.status)}">
                    ${project.status.replace('_', ' ')}
                </span>
            </td>
            <td>
                <div class="flex items-center gap-2">
                    <div class="progress" style="width: 80px;">
                        <div class="progress-bar" style="width: ${project.progress || 0}%"></div>
                    </div>
                    <span class="text-sm">${project.progress || 0}%</span>
                </div>
            </td>
            <td>${formatCurrency(project.total_budget || 0)}</td>
        </tr>
    `).join('');
    }

    function toggleAlertsPanel() {
        Modal.open('alerts-panel');
    }

    function getStatusBadge(status) {
        const map = {
            'planning': 'primary',
            'in_progress': 'success',
            'on_hold': 'warning',
            'completed': 'secondary',
            'cancelled': 'error'
        };
        return map[status] || 'secondary';
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0
        }).format(amount);
    }

    function formatMonth(m) {
        const [year, month] = m.split('-');
        return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short' });
    }
</script>

<style>
    .alert-banner {
        background: linear-gradient(135deg, var(--warning-500), var(--error-500));
        color: white;
        padding: var(--space-3) var(--space-4);
    }

    .alert-banner .btn-outline {
        border-color: white;
        color: white;
    }

    .alert-item {
        padding: var(--space-1) var(--space-2);
        background: rgba(255, 255, 255, 0.2);
        border-radius: var(--radius-sm);
        font-size: var(--text-sm);
    }

    .alert-item.error {
        background: var(--error-500);
    }

    .alert-item.warning {
        background: var(--warning-500);
    }

    .schedule-item {
        padding: var(--space-3);
        border-bottom: 1px solid var(--border-color);
    }

    .schedule-item:last-child {
        border-bottom: none;
    }

    .alert-list {
        list-style: none;
        padding: 0;
    }

    .alert-list li {
        padding: var(--space-2) 0;
        border-bottom: 1px solid var(--border-color);
    }

    .text-success {
        color: var(--success-500);
    }

    .text-error {
        color: var(--error-500);
    }

    .btn-success {
        background: var(--success-500);
        color: white;
    }

    .btn-success:hover {
        background: var(--success-600);
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
