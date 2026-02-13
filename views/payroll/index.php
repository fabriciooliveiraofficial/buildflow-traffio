<?php
$title = 'Payroll';
$page = 'payroll';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Payroll</h1>
        <p class="text-muted text-sm">Manage employee payments and payroll periods</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('period-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        New Payroll Period
    </button>
</div>

<!-- Tabs -->
<div class="flex gap-2 mb-6">
    <button class="btn btn-secondary active" id="tab-periods" onclick="switchTab('periods')">Payroll Periods</button>
    <button class="btn btn-secondary" id="tab-employees" onclick="switchTab('employees')">Employees</button>
</div>

<!-- Payroll Periods Tab -->
<div id="periods-content">
    <div class="card">
        <div class="table-container">
            <table class="table" id="periods-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Employees</th>
                        <th>Total Hours</th>
                        <th>Gross Pay</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th>Actions</th>
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

<!-- Employees Tab -->
<div id="employees-content" style="display: none;">
    <div class="card">
        <div class="table-container">
            <table class="table" id="employees-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Job Title</th>
                        <th>Department</th>
                        <th>Pay Type</th>
                        <th>Rate</th>
                        <th>Hours (Month)</th>
                        <th>Status</th>
                        <th>Actions</th>
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

<!-- New Period Modal -->
<div class="modal" id="period-modal" style="max-width: 500px;">
    <div class="modal-header">
        <h3 class="modal-title">Create Payroll Period</h3>
        <button class="modal-close" onclick="Modal.close('period-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="period-form">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Period Name</label>
                <input type="text" class="form-input" name="name" placeholder="e.g., December 2024 - Week 1" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Start Date</label>
                    <input type="date" class="form-input" name="period_start" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">End Date</label>
                    <input type="date" class="form-input" name="period_end" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Date</label>
                <input type="date" class="form-input" name="payment_date">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('period-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Period</button>
        </div>
    </form>
</div>

<!-- Process Payroll Modal -->
<div class="modal" id="process-modal" style="max-width: 600px;">
    <div class="modal-header">
        <h3 class="modal-title">Process Payroll</h3>
        <button class="modal-close" onclick="Modal.close('process-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <div class="modal-body">
        <p class="mb-4">Processing payroll will calculate pay for all employees based on their time logs and pay rates
            for the selected period.</p>
        <div id="process-summary"></div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('process-modal')">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirm-process-btn" onclick="confirmProcess()">Process
            Payroll</button>
    </div>
</div>

<script>
    let currentPeriodId = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadPeriods();

        document.getElementById('period-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                await ERP.api.post('/payroll/periods', data);
                ERP.toast.success('Payroll period created');
                Modal.close('period-modal');
                this.reset();
                loadPeriods();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    function switchTab(tab) {
        document.querySelectorAll('[id^="tab-"]').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('[id$="-content"]').forEach(el => el.style.display = 'none');

        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById(tab + '-content').style.display = 'block';

        if (tab === 'employees') {
            loadEmployees();
        } else {
            loadPeriods();
        }
    }

    async function loadPeriods() {
        try {
            const response = await ERP.api.get('/payroll/periods?per_page=20');
            if (response.success) {
                renderPeriods(response.data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load payroll periods');
        }
    }

    async function loadEmployees() {
        try {
            const response = await ERP.api.get('/employees?status=active&per_page=50');
            if (response.success) {
                renderEmployees(response.data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load employees');
        }
    }

    function renderPeriods(periods) {
        const tbody = document.querySelector('#periods-table tbody');

        if (periods.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No payroll periods found</td></tr>';
            return;
        }

        tbody.innerHTML = periods.map(p => `
        <tr>
            <td class="font-medium">${p.name}</td>
            <td>${formatDate(p.period_start)}</td>
            <td>${formatDate(p.period_end)}</td>
            <td>${p.employee_count || 0}</td>
            <td>${p.total_hours || 0}h</td>
            <td>${formatCurrency(p.total_gross || 0)}</td>
            <td class="font-medium">${formatCurrency(p.total_net || 0)}</td>
            <td>
                <span class="badge badge-${getStatusColor(p.status)}">${formatStatus(p.status)}</span>
            </td>
            <td>
                <div class="flex gap-1">
                    ${p.status === 'open' ? `
                    <button class="btn btn-sm btn-primary" onclick="openProcess(${p.id})">Process</button>
                    ` : `
                    <button class="btn btn-sm btn-secondary" onclick="viewPayroll(${p.id})">View</button>
                    `}
                </div>
            </td>
        </tr>
    `).join('');
    }

    function renderEmployees(employees) {
        const tbody = document.querySelector('#employees-table tbody');

        if (employees.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No employees found</td></tr>';
            return;
        }

        tbody.innerHTML = employees.map(e => `
        <tr>
            <td>
                <span class="font-medium">${e.first_name} ${e.last_name}</span>
                <div class="text-xs text-muted">${e.employee_id}</div>
            </td>
            <td>${e.job_title || '-'}</td>
            <td>${e.department || '-'}</td>
            <td><span class="badge badge-secondary">${formatPayType(e.payment_type)}</span></td>
            <td>${getRate(e)}</td>
            <td>${e.hours_this_month || 0}h</td>
            <td>
                <span class="badge badge-${e.status === 'active' ? 'success' : 'secondary'}">${e.status}</span>
            </td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="window.location.href='/employees/${e.id}'">View</button>
            </td>
        </tr>
    `).join('');
    }

    function openProcess(periodId) {
        currentPeriodId = periodId;
        document.getElementById('process-summary').innerHTML = '<div class="text-center">Loading preview...</div>';
        Modal.open('process-modal');
        loadProcessPreview(periodId);
    }

    async function loadProcessPreview(periodId) {
        try {
            const response = await ERP.api.get(`/payroll/periods/${periodId}`);
            if (response.success) {
                const p = response.data;
                document.getElementById('process-summary').innerHTML = `
                <div class="mb-4">
                    <strong>Period:</strong> ${p.name}<br>
                    <strong>Dates:</strong> ${formatDate(p.period_start)} - ${formatDate(p.period_end)}
                </div>
                <div class="p-4 bg-secondary rounded">
                    <p>All employees with time logs in this period will be processed.</p>
                </div>
            `;
            }
        } catch (error) {
            document.getElementById('process-summary').innerHTML = '<div class="text-error">Failed to load preview</div>';
        }
    }

    async function confirmProcess() {
        if (!currentPeriodId) return;

        const btn = document.getElementById('confirm-process-btn');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        try {
            await ERP.api.post(`/payroll/periods/${currentPeriodId}/process`, {});
            ERP.toast.success('Payroll processed successfully');
            Modal.close('process-modal');
            loadPeriods();
        } catch (error) {
            ERP.toast.error(error.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Process Payroll';
        }
    }

    function viewPayroll(periodId) {
        window.location.href = 'payroll/' + periodId;
    }

    function getStatusColor(status) {
        const map = { open: 'secondary', processing: 'warning', processed: 'success', completed: 'primary' };
        return map[status] || 'secondary';
    }

    function formatStatus(status) {
        return status.charAt(0).toUpperCase() + status.slice(1);
    }

    function formatPayType(type) {
        const map = { hourly: 'Hourly', daily: 'Daily', salary: 'Salary', project: 'Project', commission: 'Commission' };
        return map[type] || type;
    }

    function getRate(e) {
        if (e.hourly_rate) return formatCurrency(e.hourly_rate) + '/hr';
        if (e.daily_rate) return formatCurrency(e.daily_rate) + '/day';
        if (e.salary) return formatCurrency(e.salary) + '/mo';
        return '-';
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(amount);
    }

    function formatDate(date) {
        return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
</script>

<style>
    .stat-card {
        text-align: center;
        padding: var(--space-4);
    }

    .bg-secondary {
        background: var(--bg-secondary);
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