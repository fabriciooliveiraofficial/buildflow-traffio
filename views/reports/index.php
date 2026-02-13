<?php
$title = 'Reports';
$page = 'reports';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Reports & Analytics</h1>
        <p class="text-muted text-sm">Financial performance and business insights</p>
    </div>
    <div class="flex gap-3">
        <select class="form-select" id="date-range" style="width: 180px;">
            <option value="month">This Month</option>
            <option value="quarter">This Quarter</option>
            <option value="year" selected>This Year</option>
            <option value="custom">Custom Range</option>
        </select>
        <button class="btn btn-outline" onclick="exportReport()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="7 10 12 15 17 10" />
                <line x1="12" y1="15" x2="12" y2="3" />
            </svg>
            Export
        </button>
    </div>
</div>

<!-- Report Tabs -->
<div class="flex gap-2 mb-6">
    <button class="btn btn-secondary active" id="tab-financial" onclick="switchReport('financial')">Financial</button>
    <button class="btn btn-secondary" id="tab-projects" onclick="switchReport('projects')">Projects</button>
    <button class="btn btn-secondary" id="tab-employees" onclick="switchReport('employees')">Employees</button>
    <button class="btn btn-secondary" id="tab-time" onclick="switchReport('time')">Time</button>
</div>

<!-- Financial Report -->
<div id="financial-content">
    <!-- Summary Cards -->
    <div class="grid grid-cols-4 mb-6">
        <div class="card stat-card">
            <div class="stat-value text-success" id="total-revenue">$0</div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value text-warning" id="total-expenses">$0</div>
            <div class="stat-label">Total Expenses</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="total-payroll">$0</div>
            <div class="stat-label">Payroll</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value text-primary" id="net-profit">$0</div>
            <div class="stat-label">Net Profit</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-2 gap-6 mb-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Revenue vs Expenses</h3>
            </div>
            <div class="card-body" style="height: 300px;">
                <canvas id="revenue-expenses-chart"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Monthly Breakdown</h3>
            </div>
            <div class="card-body" style="height: 300px;">
                <canvas id="monthly-chart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Projects Report -->
<div id="projects-content" style="display: none;">
    <div class="card">
        <div class="table-container">
            <table class="table" id="projects-report-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Budget</th>
                        <th>Spent</th>
                        <th>Variance</th>
                        <th>Hours</th>
                        <th>Tasks</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="9" class="text-center text-muted">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Employees Report -->
<div id="employees-content" style="display: none;">
    <div class="card">
        <div class="table-container">
            <table class="table" id="employees-report-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Hours Worked</th>
                        <th>Billable Hours</th>
                        <th>Billable %</th>
                        <th>Projects</th>
                        <th>Utilization</th>
                        <th>Total Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Time Report -->
<div id="time-content" style="display: none;">
    <div class="grid grid-cols-3 mb-6">
        <div class="card stat-card">
            <div class="stat-value" id="time-total">0h</div>
            <div class="stat-label">Total Hours</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value text-success" id="time-billable">0h</div>
            <div class="stat-label">Billable</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value text-warning" id="time-overtime">0h</div>
            <div class="stat-label">Overtime</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Hours by Project</h3>
        </div>
        <div class="table-container">
            <table class="table" id="time-report-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Total Hours</th>
                        <th>Billable</th>
                        <th>Overtime</th>
                        <th>Entries</th>
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
</div>

<script>
    let charts = {};

    document.addEventListener('DOMContentLoaded', function () {
        loadFinancialReport();

        document.getElementById('date-range').addEventListener('change', function () {
            loadCurrentReport();
        });
    });

    function switchReport(report) {
        document.querySelectorAll('[id^="tab-"]').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('[id$="-content"]').forEach(el => el.style.display = 'none');

        document.getElementById('tab-' + report).classList.add('active');
        document.getElementById(report + '-content').style.display = 'block';

        switch (report) {
            case 'financial': loadFinancialReport(); break;
            case 'projects': loadProjectsReport(); break;
            case 'employees': loadEmployeesReport(); break;
            case 'time': loadTimeReport(); break;
        }
    }

    function loadCurrentReport() {
        const active = document.querySelector('[id^="tab-"].active').id.replace('tab-', '');
        switchReport(active);
    }

    function getDateRange() {
        const range = document.getElementById('date-range').value;
        const now = new Date();
        let start, end;

        switch (range) {
            case 'month':
                start = new Date(now.getFullYear(), now.getMonth(), 1);
                end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                break;
            case 'quarter':
                const q = Math.floor(now.getMonth() / 3);
                start = new Date(now.getFullYear(), q * 3, 1);
                end = new Date(now.getFullYear(), q * 3 + 3, 0);
                break;
            case 'year':
            default:
                start = new Date(now.getFullYear(), 0, 1);
                end = new Date(now.getFullYear(), 11, 31);
        }

        return {
            start_date: start.toISOString().split('T')[0],
            end_date: end.toISOString().split('T')[0]
        };
    }

    async function loadFinancialReport() {
        const { start_date, end_date } = getDateRange();

        try {
            const response = await ERP.api.get(`/reports/financial?start_date=${start_date}&end_date=${end_date}`);
            if (response.success) {
                const { summary, monthly_revenue, monthly_expenses } = response.data;

                document.getElementById('total-revenue').textContent = formatCurrency(summary.revenue);
                document.getElementById('total-expenses').textContent = formatCurrency(summary.expenses);
                document.getElementById('total-payroll').textContent = formatCurrency(summary.payroll);
                document.getElementById('net-profit').textContent = formatCurrency(summary.net_profit);

                renderFinancialCharts(monthly_revenue, monthly_expenses);
            }
        } catch (error) {
            ERP.toast.error('Failed to load financial report');
        }
    }

    function renderFinancialCharts(revenue, expenses) {
        // Revenue vs Expenses pie
        const ctx1 = document.getElementById('revenue-expenses-chart');
        if (charts.revExp) charts.revExp.destroy();

        const totalRev = revenue.reduce((s, r) => s + parseFloat(r.revenue || 0), 0);
        const totalExp = expenses.reduce((s, e) => s + parseFloat(e.expenses || 0), 0);

        charts.revExp = new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Revenue', 'Expenses'],
                datasets: [{
                    data: [totalRev, totalExp],
                    backgroundColor: ['#4caf50', '#ff9800']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Monthly trend
        const ctx2 = document.getElementById('monthly-chart');
        if (charts.monthly) charts.monthly.destroy();

        const months = [...new Set([...revenue.map(r => r.month), ...expenses.map(e => e.month)])].sort();
        const revData = months.map(m => {
            const r = revenue.find(x => x.month === m);
            return r ? parseFloat(r.revenue) : 0;
        });
        const expData = months.map(m => {
            const e = expenses.find(x => x.month === m);
            return e ? parseFloat(e.expenses) : 0;
        });

        charts.monthly = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: months.map(m => formatMonth(m)),
                datasets: [
                    { label: 'Revenue', data: revData, backgroundColor: '#4caf50' },
                    { label: 'Expenses', data: expData, backgroundColor: '#ff9800' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    async function loadProjectsReport() {
        try {
            const response = await ERP.api.get('/reports/projects');
            if (response.success) {
                renderProjectsTable(response.data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load projects report');
        }
    }

    function renderProjectsTable(projects) {
        const tbody = document.querySelector('#projects-report-table tbody');
        tbody.innerHTML = projects.map(p => {
            const variance = p.total_budget - p.total_spent;
            return `
        <tr>
            <td class="font-medium">${p.name}</td>
            <td>${p.client_name || '-'}</td>
            <td><span class="badge badge-secondary">${p.status}</span></td>
            <td>${formatCurrency(p.total_budget)}</td>
            <td>${formatCurrency(p.total_spent)}</td>
            <td class="${variance < 0 ? 'text-error' : 'text-success'}">${formatCurrency(variance)}</td>
            <td>${p.hours_logged}h</td>
            <td>${p.completed_tasks}/${p.total_tasks}</td>
            <td>
                <div class="flex items-center gap-2">
                    <div class="progress" style="width: 60px;">
                        <div class="progress-bar" style="width: ${p.task_completion}%"></div>
                    </div>
                    <span>${p.task_completion}%</span>
                </div>
            </td>
        </tr>
    `}).join('');
    }

    async function loadEmployeesReport() {
        const { start_date, end_date } = getDateRange();

        try {
            const response = await ERP.api.get(`/reports/employees?start_date=${start_date}&end_date=${end_date}`);
            if (response.success) {
                renderEmployeesTable(response.data.employees);
            }
        } catch (error) {
            ERP.toast.error('Failed to load employees report');
        }
    }

    function renderEmployeesTable(employees) {
        const tbody = document.querySelector('#employees-report-table tbody');
        tbody.innerHTML = employees.map(e => `
        <tr>
            <td class="font-medium">${e.first_name} ${e.last_name}</td>
            <td>${e.department || '-'}</td>
            <td>${e.hours_worked}h</td>
            <td>${e.billable_hours}h</td>
            <td>${e.billable_percentage}%</td>
            <td>${e.projects_worked}</td>
            <td>
                <div class="flex items-center gap-2">
                    <div class="progress" style="width: 60px;">
                        <div class="progress-bar ${e.utilization > 80 ? 'success' : e.utilization > 50 ? '' : 'warning'}" style="width: ${Math.min(e.utilization, 100)}%"></div>
                    </div>
                    <span>${e.utilization}%</span>
                </div>
            </td>
            <td>${formatCurrency(e.total_paid)}</td>
        </tr>
    `).join('');
    }

    async function loadTimeReport() {
        const { start_date, end_date } = getDateRange();

        try {
            const response = await ERP.api.get(`/reports/time?start_date=${start_date}&end_date=${end_date}`);
            if (response.success) {
                const { summary, data } = response.data;

                document.getElementById('time-total').textContent = summary.total_hours + 'h';
                document.getElementById('time-billable').textContent = summary.billable_hours + 'h';
                document.getElementById('time-overtime').textContent = summary.overtime_hours + 'h';

                renderTimeTable(data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load time report');
        }
    }

    function renderTimeTable(data) {
        const tbody = document.querySelector('#time-report-table tbody');
        tbody.innerHTML = data.map(d => `
        <tr>
            <td class="font-medium">${d.group_name || 'Unassigned'}</td>
            <td>${d.total_hours}h</td>
            <td>${d.billable_hours}h</td>
            <td>${d.overtime_hours}h</td>
            <td>${d.entry_count}</td>
        </tr>
    `).join('');
    }

    function exportReport() {
        ERP.toast.info('Export functionality coming soon');
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(amount || 0);
    }

    function formatMonth(ym) {
        const [y, m] = ym.split('-');
        return new Date(y, m - 1).toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
    }
</script>

<style>
    .stat-card {
        text-align: center;
        padding: var(--space-4);
    }

    .stat-card .stat-value {
        font-size: var(--text-2xl);
    }

    .text-success {
        color: var(--success-500);
    }

    .text-warning {
        color: var(--warning-500);
    }

    .text-error {
        color: var(--error-500);
    }

    .text-primary {
        color: var(--primary-500);
    }

    .btn.active {
        background: var(--primary-500);
        color: white;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>