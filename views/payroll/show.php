<?php
$title = 'Payroll Period Details';
$page = 'payroll';

$periodId = $GLOBALS['params']['id'] ?? null;

ob_start();
?>

<div class="mb-6">
    <a href="/payroll" class="text-muted text-sm flex items-center gap-1 mb-2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6" />
        </svg>
        Back to Payroll
    </a>
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold" id="period-name">Loading...</h1>
            <p class="text-muted" id="period-dates"></p>
        </div>
        <div class="flex gap-2">
            <span class="badge" id="period-status">Draft</span>
            <button class="btn btn-outline" id="export-btn" onclick="exportPayroll()">Export</button>
            <button class="btn btn-primary" id="process-btn" onclick="processPayroll()" style="display: none;">
                Process Payroll
            </button>
            <button class="btn btn-success" id="pay-btn" onclick="markAsPaid()" style="display: none;">
                Mark All as Paid
            </button>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-4 mb-6">
    <div class="card stat-card">
        <div class="stat-value" id="employee-count">0</div>
        <div class="stat-label">Employees</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value" id="total-hours">0h</div>
        <div class="stat-label">Total Hours</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value" id="total-gross">$0</div>
        <div class="stat-label">Gross Pay</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-success" id="total-net">$0</div>
        <div class="stat-label">Net Pay</div>
    </div>
</div>

<!-- Payroll Records Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Payroll Records</h3>
    </div>
    <div class="table-container">
        <table class="table" id="records-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Regular Hours</th>
                    <th>OT Hours</th>
                    <th>Gross Pay</th>
                    <th>Deductions</th>
                    <th>Net Pay</th>
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

<!-- Pay Employee Modal -->
<div class="modal" id="pay-modal" style="max-width: 400px;">
    <div class="modal-header">
        <h3 class="modal-title">Process Payment</h3>
        <button class="modal-close" onclick="Modal.close('pay-modal')">×</button>
    </div>
    <form id="pay-form">
        <input type="hidden" name="record_id" id="pay-record-id">
        <div class="modal-body">
            <div class="text-center mb-4">
                <div class="text-2xl font-bold" id="pay-amount">$0</div>
                <div class="text-muted">Net Pay for <span id="pay-employee-name"></span></div>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method</label>
                <select class="form-select" name="payment_method">
                    <option value="direct_deposit">Direct Deposit</option>
                    <option value="check">Check</option>
                    <option value="cash">Cash</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Date</label>
                <input type="date" class="form-input" name="payment_date">
            </div>
            <div class="form-group">
                <label class="form-label">Reference #</label>
                <input type="text" class="form-input" name="reference" placeholder="Check #, Transaction ID">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('pay-modal')">Cancel</button>
            <button type="submit" class="btn btn-success">Confirm Payment</button>
        </div>
    </form>
</div>

<script>
    const periodId = <?= json_encode($periodId) ?>;
    let period = null;
    let records = [];

    document.addEventListener('DOMContentLoaded', function () {
        loadPeriod();
        document.querySelector('[name="payment_date"]').value = new Date().toISOString().split('T')[0];

        document.getElementById('pay-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const recordId = document.getElementById('pay-record-id').value;
            const data = ERP.FormUtils.serialize(this);

            try {
                await ERP.api.post(`/payroll/records/${recordId}/pay`, data);
                ERP.toast.success('Payment processed');
                Modal.close('pay-modal');
                loadPeriod();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadPeriod() {
        try {
            const response = await ERP.api.get('/payroll/periods/' + periodId);
            if (response.success) {
                period = response.data;
                records = period.records || [];
                renderPeriod();
            }
        } catch (error) {
            ERP.toast.error('Failed to load payroll period');
        }
    }

    function renderPeriod() {
        document.getElementById('period-name').textContent = period.name;
        document.getElementById('period-dates').textContent =
            formatDate(period.period_start) + ' - ' + formatDate(period.period_end);

        const statusEl = document.getElementById('period-status');
        statusEl.textContent = period.status;
        statusEl.className = 'badge badge-' + getStatusColor(period.status);

        // Show/hide buttons based on status
        document.getElementById('process-btn').style.display = period.status === 'open' ? '' : 'none';
        document.getElementById('pay-btn').style.display = (period.status === 'processing' || period.status === 'processed') ? '' : 'none';

        // Calculate totals
        let totalHours = 0, totalGross = 0, totalNet = 0;
        records.forEach(r => {
            totalHours += parseFloat(r.regular_hours || 0) + parseFloat(r.overtime_hours || 0);
            totalGross += parseFloat(r.gross_pay || 0);
            totalNet += parseFloat(r.net_pay || 0);
        });

        document.getElementById('employee-count').textContent = records.length;
        document.getElementById('total-hours').textContent = totalHours.toFixed(1) + 'h';
        document.getElementById('total-gross').textContent = formatCurrency(totalGross);
        document.getElementById('total-net').textContent = formatCurrency(totalNet);

        renderRecords();
    }

    function renderRecords() {
        const tbody = document.querySelector('#records-table tbody');

        if (records.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No payroll records</td></tr>';
            return;
        }

        tbody.innerHTML = records.map(r => `
        <tr>
            <td>
                <a href="/employees/${r.employee_id}" class="font-medium">${r.employee_name || r.first_name + ' ' + r.last_name}</a>
                <div class="text-xs text-muted">${r.employee_id_code || ''}</div>
            </td>
            <td>${r.regular_hours}h</td>
            <td>${r.overtime_hours || 0}h</td>
            <td>${formatCurrency(r.gross_pay)}</td>
            <td>${formatCurrency(r.deductions || 0)}</td>
            <td class="font-medium">${formatCurrency(r.net_pay)}</td>
            <td>
                <span class="badge badge-${r.status === 'paid' ? 'success' : 'warning'}">${r.status}</span>
            </td>
            <td>
                ${r.status !== 'paid' ? `
                <button class="btn btn-sm btn-success" onclick="openPayModal(${r.id}, '${r.employee_name || r.first_name}', ${r.net_pay})">
                    Pay
                </button>
                ` : `
                <span class="text-muted text-sm">${formatDate(r.paid_at)}</span>
                `}
            </td>
        </tr>
    `).join('');
    }

    function openPayModal(recordId, employeeName, amount) {
        document.getElementById('pay-record-id').value = recordId;
        document.getElementById('pay-employee-name').textContent = employeeName;
        document.getElementById('pay-amount').textContent = formatCurrency(amount);
        Modal.open('pay-modal');
    }

    async function processPayroll() {
        if (!confirm('Process payroll for all employees in this period?')) return;

        try {
            await ERP.api.post(`/payroll/periods/${periodId}/process`, {});
            ERP.toast.success('Payroll processed');
            loadPeriod();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    async function markAsPaid() {
        if (!confirm('Mark all records as paid?')) return;

        try {
            for (const r of records.filter(r => r.status !== 'paid')) {
                await ERP.api.post(`/payroll/records/${r.id}/pay`, {});
            }
            ERP.toast.success('All payments processed');
            loadPeriod();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    function exportPayroll() {
        ERP.toast.info('Export functionality coming soon');
    }

    function getStatusColor(s) {
        return { open: 'secondary', processing: 'warning', processed: 'primary', completed: 'success' }[s] || 'secondary';
    }

    function formatCurrency(a) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(a || 0);
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
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
