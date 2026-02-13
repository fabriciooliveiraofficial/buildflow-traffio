<?php
$title = 'Employee Details';
$page = 'payroll';

$employeeId = $GLOBALS['params']['id'] ?? null;

ob_start();
?>

<div class="mb-6">
    <a href="/payroll" class="text-muted text-sm flex items-center gap-1 mb-2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6" />
        </svg>
        Back to Payroll
    </a>
</div>

<div class="grid grid-cols-3 gap-6">
    <!-- Main Content -->
    <div style="grid-column: span 2;">
        <!-- Employee Header -->
        <div class="card mb-6">
            <div class="card-body flex items-start gap-6">
                <div class="employee-avatar" id="employee-avatar">E</div>
                <div class="flex-1">
                    <div class="flex justify-between">
                        <div>
                            <h1 class="text-2xl font-bold" id="employee-name">Loading...</h1>
                            <p class="text-muted" id="employee-title">-</p>
                        </div>
                        <div class="flex gap-2">
                            <span class="badge" id="employee-status">Active</span>
                            <button class="btn btn-outline btn-sm" onclick="Modal.open('edit-modal')">Edit</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-4 mt-4">
                        <div>
                            <span class="text-muted text-sm">Employee ID</span>
                            <p class="font-mono" id="employee-id">-</p>
                        </div>
                        <div>
                            <span class="text-muted text-sm">Department</span>
                            <p id="employee-dept">-</p>
                        </div>
                        <div>
                            <span class="text-muted text-sm">Hire Date</span>
                            <p id="hire-date">-</p>
                        </div>
                        <div>
                            <span class="text-muted text-sm">Pay Type</span>
                            <p id="pay-type">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 mb-6">
            <div class="card stat-card">
                <div class="stat-value" id="hours-month">0h</div>
                <div class="stat-label">Hours This Month</div>
            </div>
            <div class="card stat-card">
                <div class="stat-value" id="projects-count">0</div>
                <div class="stat-label">Active Projects</div>
            </div>
            <div class="card stat-card">
                <div class="stat-value" id="total-earned">$0</div>
                <div class="stat-label">Total Earned (YTD)</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 mb-4">
            <button class="btn btn-secondary active" id="tab-timelogs" onclick="switchTab('timelogs')">Time
                Logs</button>
            <button class="btn btn-secondary" id="tab-payroll" onclick="switchTab('payroll')">Payroll History</button>
        </div>

        <!-- Time Logs Tab -->
        <div id="timelogs-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Time Logs</h3>
                </div>
                <div class="table-container">
                    <table class="table" id="timelogs-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Project</th>
                                <th>Task</th>
                                <th>Hours</th>
                                <th>Status</th>
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

        <!-- Payroll Tab -->
        <div id="payroll-content" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payroll History</h3>
                </div>
                <div class="table-container">
                    <table class="table" id="payroll-table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Hours</th>
                                <th>Gross Pay</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Contact Info -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Contact Info</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="text-muted text-sm">Email</span>
                    <p id="contact-email">-</p>
                </div>
                <div class="mb-3">
                    <span class="text-muted text-sm">Phone</span>
                    <p id="contact-phone">-</p>
                </div>
                <div>
                    <span class="text-muted text-sm">Address</span>
                    <p id="contact-address">-</p>
                </div>
            </div>
        </div>

        <!-- Pay Info -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Pay Information</h3>
            </div>
            <div class="card-body">
                <div class="flex justify-between py-2 border-b">
                    <span class="text-muted">Rate</span>
                    <span class="font-medium" id="pay-rate">-</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-muted">OT Threshold</span>
                    <span id="ot-threshold">40h/week</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-muted">OT Multiplier</span>
                    <span id="ot-multiplier">1.5x</span>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Emergency Contact</h3>
            </div>
            <div class="card-body">
                <p id="emergency-name">-</p>
                <p class="text-muted" id="emergency-phone">-</p>
            </div>
        </div>
    </div>
</div>

<script>
    const employeeId = <?= json_encode($employeeId) ?>;
    let employee = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadEmployee();
    });

    async function loadEmployee() {
        try {
            const response = await ERP.api.get('/employees/' + employeeId);
            if (response.success) {
                employee = response.data;
                renderEmployee();
            }
        } catch (error) {
            ERP.toast.error('Failed to load employee');
        }
    }

    function renderEmployee() {
        const e = employee;

        document.getElementById('employee-name').textContent = e.first_name + ' ' + e.last_name;
        document.getElementById('employee-avatar').textContent = e.first_name.charAt(0).toUpperCase();
        document.getElementById('employee-title').textContent = e.job_title || 'Employee';
        document.getElementById('employee-id').textContent = e.employee_id;
        document.getElementById('employee-dept').textContent = e.department || '-';
        document.getElementById('hire-date').textContent = e.hire_date ? formatDate(e.hire_date) : '-';
        document.getElementById('pay-type').textContent = formatPayType(e.payment_type);

        const statusEl = document.getElementById('employee-status');
        statusEl.textContent = e.status;
        statusEl.className = 'badge badge-' + (e.status === 'active' ? 'success' : 'secondary');

        // Stats
        document.getElementById('hours-month').textContent = (e.hours_this_month || 0) + 'h';
        document.getElementById('total-earned').textContent = formatCurrency(e.payroll_summary?.total_earned || 0);

        // Contact
        document.getElementById('contact-email').textContent = e.email || '-';
        document.getElementById('contact-phone').textContent = e.phone || '-';
        document.getElementById('contact-address').textContent = [e.address, e.city, e.state].filter(Boolean).join(', ') || '-';

        // Pay info
        document.getElementById('pay-rate').textContent = getPayRate(e);
        document.getElementById('ot-threshold').textContent = (e.overtime_threshold || 40) + 'h/week';
        document.getElementById('ot-multiplier').textContent = (e.overtime_multiplier || 1.5) + 'x';

        // Emergency
        document.getElementById('emergency-name').textContent = e.emergency_contact || '-';
        document.getElementById('emergency-phone').textContent = e.emergency_phone || '-';

        // Time logs
        renderTimeLogs(e.recent_time_logs || []);
    }

    function renderTimeLogs(logs) {
        const tbody = document.querySelector('#timelogs-table tbody');
        if (!logs || logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No time logs</td></tr>';
            return;
        }

        tbody.innerHTML = logs.map(l => `
        <tr>
            <td>${formatDate(l.log_date)}</td>
            <td>${l.project_name || '-'}</td>
            <td>${l.task_title || '-'}</td>
            <td>${l.hours}h ${l.is_overtime ? '<span class="badge badge-warning text-xs">OT</span>' : ''}</td>
            <td>${l.approved ? '<span class="badge badge-success">Approved</span>' : '<span class="badge badge-secondary">Pending</span>'}</td>
        </tr>
    `).join('');
    }

    function switchTab(tab) {
        document.querySelectorAll('[id^="tab-"]').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('[id$="-content"]').forEach(el => el.style.display = 'none');
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById(tab + '-content').style.display = 'block';

        if (tab === 'payroll') {
            loadPayrollHistory();
        }
    }

    async function loadPayrollHistory() {
        try {
            const response = await ERP.api.get(`/payroll/employees/${employeeId}`);
            if (response.success) {
                renderPayrollHistory(response.data);
            }
        } catch (error) {
            document.querySelector('#payroll-table tbody').innerHTML =
                '<tr><td colspan="6" class="text-center text-muted">No payroll records</td></tr>';
        }
    }

    function renderPayrollHistory(records) {
        const tbody = document.querySelector('#payroll-table tbody');
        if (!records || records.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No payroll records</td></tr>';
            return;
        }

        tbody.innerHTML = records.map(r => `
        <tr>
            <td>${r.period_name || '-'}</td>
            <td>${r.regular_hours + (r.overtime_hours || 0)}h</td>
            <td>${formatCurrency(r.gross_pay)}</td>
            <td>${formatCurrency(r.deductions || 0)}</td>
            <td class="font-medium">${formatCurrency(r.net_pay)}</td>
            <td><span class="badge badge-${r.status === 'paid' ? 'success' : 'warning'}">${r.status}</span></td>
        </tr>
    `).join('');
    }

    function formatPayType(type) {
        return { hourly: 'Hourly', daily: 'Daily', salary: 'Salary', project: 'Per Project', commission: 'Commission' }[type] || type;
    }

    function getPayRate(e) {
        if (e.hourly_rate) return formatCurrency(e.hourly_rate) + '/hr';
        if (e.daily_rate) return formatCurrency(e.daily_rate) + '/day';
        if (e.salary) return formatCurrency(e.salary) + '/mo';
        return '-';
    }

    function formatCurrency(a) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(a || 0);
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
</script>

<style>
    .employee-avatar {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary-600), var(--primary-400));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        font-weight: 700;
    }

    .flex-1 {
        flex: 1;
    }

    .stat-card {
        text-align: center;
        padding: var(--space-4);
    }

    .stat-card .stat-value {
        font-size: var(--text-2xl);
    }

    .border-b {
        border-bottom: 1px solid var(--border-color);
    }

    .btn.active {
        background: var(--primary-500);
        color: white;
    }

    .font-mono {
        font-family: monospace;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>