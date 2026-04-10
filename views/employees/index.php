<?php
$title = 'Employees';
$page = 'employees';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Employees</h1>
        <p class="text-muted text-sm">Manage your workforce</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('employee-modal'); resetForm();">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Add Employee
    </button>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4">
        <input type="text" class="form-input flex-1" id="search-input" placeholder="Search by name or email...">
        <select class="form-select" id="status-filter" style="width: 140px;">
            <option value="">All Status</option>
            <option value="active" selected>Active</option>
            <option value="on_leave">On Leave</option>
            <option value="terminated">Terminated</option>
        </select>
        <select class="form-select" id="department-filter" style="width: 150px;">
            <option value="">All Departments</option>
            <option value="field">Field</option>
            <option value="office">Office</option>
            <option value="management">Management</option>
        </select>
        <select class="form-select" id="payment-filter" style="width: 140px;">
            <option value="">All Pay Types</option>
            <option value="hourly">Hourly</option>
            <option value="salary">Salary</option>
            <option value="daily">Daily</option>
        </select>
    </div>
</div>

<!-- Employees Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="employees-table">
            <thead>
                <tr>
                    <th class="sortable" data-sort="name" onclick="sortEmployees('name')" style="cursor: pointer;">
                        Employee <span class="sort-icon" id="sort-icon-name"></span>
                    </th>
                    <th class="sortable" data-sort="job_title" onclick="sortEmployees('job_title')"
                        style="cursor: pointer;">
                        Job Title <span class="sort-icon" id="sort-icon-job_title"></span>
                    </th>
                    <th class="sortable" data-sort="department" onclick="sortEmployees('department')"
                        style="cursor: pointer;">
                        Department <span class="sort-icon" id="sort-icon-department"></span>
                    </th>
                    <th class="sortable" data-sort="payment_type" onclick="sortEmployees('payment_type')"
                        style="cursor: pointer;">
                        Pay Type <span class="sort-icon" id="sort-icon-payment_type"></span>
                    </th>
                    <th class="sortable" data-sort="rate" onclick="sortEmployees('rate')" style="cursor: pointer;">
                        Rate <span class="sort-icon" id="sort-icon-rate"></span>
                    </th>
                    <th class="sortable" data-sort="hours" onclick="sortEmployees('hours')" style="cursor: pointer;">
                        Hours (Month) <span class="sort-icon" id="sort-icon-hours"></span>
                    </th>
                    <th class="sortable" data-sort="status" onclick="sortEmployees('status')" style="cursor: pointer;">
                        Status <span class="sort-icon" id="sort-icon-status"></span>
                    </th>
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

<!-- Add/Edit Employee Modal -->
<div class="modal modal-lg" id="employee-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Add Employee</h3>
        <button class="modal-close" onclick="Modal.close('employee-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="employee-form">
        <input type="hidden" name="id" id="emp-id">
        <div class="modal-body">
            <!-- Tabs -->
            <div class="flex gap-2 mb-4">
                <button type="button" class="btn btn-sm btn-primary" id="form-tab-basic"
                    onclick="switchFormTab('basic')">Basic Info</button>
                <button type="button" class="btn btn-sm btn-secondary" id="form-tab-pay"
                    onclick="switchFormTab('pay')">Pay Settings</button>
                <button type="button" class="btn btn-sm btn-secondary" id="form-tab-other"
                    onclick="switchFormTab('other')">Other</button>
            </div>

            <!-- Basic Info Tab -->
            <div id="form-basic">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label required">First Name</label>
                        <input type="text" class="form-input" name="first_name" id="emp-first-name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Last Name</label>
                        <input type="text" class="form-input" name="last_name" id="emp-last-name" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" name="email" id="emp-email">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-input" name="phone" id="emp-phone">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Job Title</label>
                        <input type="text" class="form-input" name="job_title" id="emp-job-title"
                            placeholder="e.g., Electrician, Foreman">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department" id="emp-department">
                            <option value="">Select</option>
                            <option value="field">Field</option>
                            <option value="office">Office</option>
                            <option value="management">Management</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Hire Date</label>
                        <input type="date" class="form-input" name="hire_date" id="emp-hire-date">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="emp-status">
                            <option value="active">Active</option>
                            <option value="on_leave">On Leave</option>
                            <option value="suspended">Suspended</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-input" name="address" id="emp-address">
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" class="form-input" name="city" id="emp-city">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" class="form-input" name="state" id="emp-state">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ZIP</label>
                        <input type="text" class="form-input" name="zip_code" id="emp-zip">
                    </div>
                </div>
            </div>

            <!-- Pay Settings Tab -->
            <div id="form-pay" style="display: none;">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label required">Pay Type</label>
                        <select class="form-select" name="payment_type" id="emp-payment-type" required
                            onchange="updatePayFields()">
                            <option value="hourly">Hourly</option>
                            <option value="salary">Salary</option>
                            <option value="daily">Daily</option>
                            <option value="project">Per Project</option>
                            <option value="commission">Commission</option>
                        </select>
                    </div>
                    <div class="form-group" id="rate-hourly">
                        <label class="form-label">Hourly Rate ($)</label>
                        <input type="number" class="form-input" name="hourly_rate" id="emp-hourly-rate" step="0.01"
                            min="0">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group" id="rate-daily" style="display: none;">
                        <label class="form-label">Daily Rate ($)</label>
                        <input type="number" class="form-input" name="daily_rate" id="emp-daily-rate" step="0.01"
                            min="0">
                    </div>
                    <div class="form-group" id="rate-salary" style="display: none;">
                        <label class="form-label">Monthly Salary ($)</label>
                        <input type="number" class="form-input" name="salary" id="emp-salary" step="0.01" min="0">
                    </div>
                    <div class="form-group" id="rate-commission" style="display: none;">
                        <label class="form-label">Commission Rate (%)</label>
                        <input type="number" class="form-input" name="commission_rate" id="emp-commission" step="0.01"
                            min="0" max="100">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Overtime Threshold (hours/week)</label>
                        <input type="number" class="form-input" name="overtime_threshold" id="emp-ot-threshold"
                            value="40" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Overtime Multiplier</label>
                        <input type="number" class="form-input" name="overtime_multiplier" id="emp-ot-multiplier"
                            value="1.5" step="0.1" min="1">
                    </div>
                </div>
            </div>

            <!-- Other Tab -->
            <div id="form-other" style="display: none;">
                <h4 class="font-semibold mb-3">Bank Information</h4>
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="form-group">
                        <label class="form-label">Bank Name</label>
                        <input type="text" class="form-input" name="bank_name" id="emp-bank-name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Number</label>
                        <input type="text" class="form-input" name="bank_account" id="emp-bank-account">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Routing Number</label>
                        <input type="text" class="form-input" name="bank_routing" id="emp-bank-routing">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Tax ID (SSN)</label>
                    <input type="text" class="form-input" name="tax_id" id="emp-tax-id" style="max-width: 200px;">
                </div>

                <h4 class="font-semibold mb-3 mt-4">Emergency Contact</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Contact Name</label>
                        <input type="text" class="form-input" name="emergency_contact" id="emp-emergency-name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Phone</label>
                        <input type="tel" class="form-input" name="emergency_phone" id="emp-emergency-phone">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-input" name="notes" id="emp-notes" rows="2"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('employee-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary" id="save-btn">Save Employee</button>
        </div>
    </form>
</div>

<script>
    let isEditing = false;
    let searchTimeout;

    // Sorting state
    let employeesData = [];
    let employeeSortColumn = 'name';
    let employeeSortDirection = 'asc';

    document.addEventListener('DOMContentLoaded', function () {
        loadEmployees();

        document.getElementById('search-input').addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadEmployees, 300);
        });

        document.getElementById('status-filter').addEventListener('change', loadEmployees);
        document.getElementById('department-filter').addEventListener('change', loadEmployees);
        document.getElementById('payment-filter').addEventListener('change', loadEmployees);

        document.getElementById('employee-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);
            const id = document.getElementById('emp-id').value;

            try {
                if (isEditing && id) {
                    await ERP.api.put('/employees/' + id, data);
                    ERP.toast.success('Employee updated');
                } else {
                    await ERP.api.post('/employees', data);
                    ERP.toast.success('Employee created');
                }
                Modal.close('employee-modal');
                loadEmployees();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadEmployees() {
        const params = new URLSearchParams({
            search: document.getElementById('search-input').value,
            status: document.getElementById('status-filter').value,
            department: document.getElementById('department-filter').value,
            payment_type: document.getElementById('payment-filter').value,
            per_page: 100  // Increased to get all for client-side sorting
        });

        try {
            const response = await ERP.api.get('/employees?' + params);
            if (response.success) {
                employeesData = response.data;  // Store for sorting
                renderEmployees(employeesData);
            }
        } catch (error) {
            ERP.toast.error('Failed to load employees');
        }
    }

    function sortEmployees(column) {
        if (!employeesData || employeesData.length === 0) return;

        // Toggle direction if same column, otherwise default to ascending
        if (employeeSortColumn === column) {
            employeeSortDirection = employeeSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            employeeSortColumn = column;
            employeeSortDirection = 'asc';
        }

        // Sort the data
        employeesData.sort((a, b) => {
            let valA, valB;

            switch (column) {
                case 'name':
                    valA = `${a.first_name} ${a.last_name}`.toLowerCase();
                    valB = `${b.first_name} ${b.last_name}`.toLowerCase();
                    break;
                case 'job_title':
                    valA = (a.job_title || '').toLowerCase();
                    valB = (b.job_title || '').toLowerCase();
                    break;
                case 'department':
                    valA = (a.department || '').toLowerCase();
                    valB = (b.department || '').toLowerCase();
                    break;
                case 'payment_type':
                    valA = (a.payment_type || '').toLowerCase();
                    valB = (b.payment_type || '').toLowerCase();
                    break;
                case 'rate':
                    valA = parseFloat(a.hourly_rate || a.daily_rate || a.salary || 0);
                    valB = parseFloat(b.hourly_rate || b.daily_rate || b.salary || 0);
                    break;
                case 'hours':
                    valA = parseFloat(a.hours_this_month || 0);
                    valB = parseFloat(b.hours_this_month || 0);
                    break;
                case 'status':
                    valA = (a.status || '').toLowerCase();
                    valB = (b.status || '').toLowerCase();
                    break;
                default:
                    valA = 0;
                    valB = 0;
            }

            let comparison = 0;
            if (typeof valA === 'string') {
                comparison = valA.localeCompare(valB);
            } else {
                comparison = valA - valB;
            }

            return employeeSortDirection === 'asc' ? comparison : -comparison;
        });

        updateEmployeeSortIcons(column);
        renderEmployees(employeesData);
    }

    function updateEmployeeSortIcons(activeColumn) {
        const columns = ['name', 'job_title', 'department', 'payment_type', 'rate', 'hours', 'status'];
        columns.forEach(col => {
            const icon = document.getElementById(`sort-icon-${col}`);
            if (icon) {
                if (col === activeColumn) {
                    icon.textContent = employeeSortDirection === 'asc' ? ' ↑' : ' ↓';
                } else {
                    icon.textContent = '';
                }
            }
        });
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
                <div class="flex items-center gap-3">
                    <div class="avatar-sm">${e.first_name.charAt(0)}${e.last_name.charAt(0)}</div>
                    <div>
                        <a href="employees/${e.id}" class="font-medium">${e.first_name} ${e.last_name}</a>
                        <div class="text-xs text-muted">${e.employee_id}</div>
                    </div>
                </div>
            </td>
            <td>${e.job_title || '-'}</td>
            <td>${e.department ? capitalize(e.department) : '-'}</td>
            <td>${formatPayType(e.payment_type)}</td>
            <td class="font-mono">${getPayRate(e)}</td>
            <td>${e.hours_this_month || 0}h</td>
            <td><span class="badge badge-${getStatusBadge(e.status)}">${capitalize(e.status)}</span></td>
            <td>
                <div class="flex gap-1">
                    <button class="btn btn-icon btn-sm btn-secondary" onclick="editEmployee(${e.id})" title="Edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="btn btn-icon btn-sm btn-secondary" onclick="window.location.href='employees/${e.id}'" title="View">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                    <button class="btn btn-icon btn-sm btn-error" onclick="deleteEmployee(${e.id})" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    }

    function resetForm() {
        isEditing = false;
        document.getElementById('employee-form').reset();
        document.getElementById('emp-id').value = '';
        document.getElementById('modal-title').textContent = 'Add Employee';
        document.getElementById('emp-hire-date').value = new Date().toISOString().split('T')[0];
        switchFormTab('basic');
        updatePayFields();
    }

    async function editEmployee(id) {
        try {
            const response = await ERP.api.get('/employees/' + id);
            if (response.success) {
                const e = response.data;
                isEditing = true;
                document.getElementById('emp-id').value = e.id;
                document.getElementById('emp-first-name').value = e.first_name;
                document.getElementById('emp-last-name').value = e.last_name;
                document.getElementById('emp-email').value = e.email || '';
                document.getElementById('emp-phone').value = e.phone || '';
                document.getElementById('emp-job-title').value = e.job_title || '';
                document.getElementById('emp-department').value = e.department || '';
                document.getElementById('emp-hire-date').value = e.hire_date || '';
                document.getElementById('emp-status').value = e.status || 'active';
                document.getElementById('emp-address').value = e.address || '';
                document.getElementById('emp-city').value = e.city || '';
                document.getElementById('emp-state').value = e.state || '';
                document.getElementById('emp-zip').value = e.zip_code || '';
                document.getElementById('emp-payment-type').value = e.payment_type || 'hourly';
                document.getElementById('emp-hourly-rate').value = e.hourly_rate || '';
                document.getElementById('emp-daily-rate').value = e.daily_rate || '';
                document.getElementById('emp-salary').value = e.salary || '';
                document.getElementById('emp-commission').value = e.commission_rate || '';
                document.getElementById('emp-ot-threshold').value = e.overtime_threshold || 40;
                document.getElementById('emp-ot-multiplier').value = e.overtime_multiplier || 1.5;
                document.getElementById('emp-bank-name').value = e.bank_name || '';
                document.getElementById('emp-bank-account').value = e.bank_account || '';
                document.getElementById('emp-bank-routing').value = e.bank_routing || '';
                document.getElementById('emp-tax-id').value = e.tax_id || '';
                document.getElementById('emp-emergency-name').value = e.emergency_contact || '';
                document.getElementById('emp-emergency-phone').value = e.emergency_phone || '';
                document.getElementById('emp-notes').value = e.notes || '';

                document.getElementById('modal-title').textContent = 'Edit Employee';
                updatePayFields();
                Modal.open('employee-modal');
            }
        } catch (error) {
            ERP.toast.error('Failed to load employee');
        }
    }

    function switchFormTab(tab) {
        ['basic', 'pay', 'other'].forEach(t => {
            document.getElementById('form-' + t).style.display = t === tab ? '' : 'none';
            document.getElementById('form-tab-' + t).classList.remove('btn-primary');
            document.getElementById('form-tab-' + t).classList.add('btn-secondary');
        });
        document.getElementById('form-tab-' + tab).classList.remove('btn-secondary');
        document.getElementById('form-tab-' + tab).classList.add('btn-primary');
    }

    function updatePayFields() {
        const type = document.getElementById('emp-payment-type').value;
        document.getElementById('rate-hourly').style.display = type === 'hourly' ? '' : 'none';
        document.getElementById('rate-daily').style.display = type === 'daily' ? '' : 'none';
        document.getElementById('rate-salary').style.display = type === 'salary' ? '' : 'none';
        document.getElementById('rate-commission').style.display = type === 'commission' ? '' : 'none';
    }

    function formatPayType(type) {
        return { hourly: 'Hourly', daily: 'Daily', salary: 'Salary', project: 'Per Project', commission: 'Commission' }[type] || type || '-';
    }

    function getPayRate(e) {
        if (e.hourly_rate) return '$' + parseFloat(e.hourly_rate).toFixed(2) + '/hr';
        if (e.daily_rate) return '$' + parseFloat(e.daily_rate).toFixed(2) + '/day';
        if (e.salary) return '$' + parseFloat(e.salary).toFixed(0) + '/mo';
        if (e.commission_rate) return e.commission_rate + '%';
        return '-';
    }

    function getStatusBadge(status) {
        return { active: 'success', on_leave: 'warning', suspended: 'error', terminated: 'secondary' }[status] || 'secondary';
    }

    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ') : '';
    }

    async function deleteEmployee(id) {
        if (!confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
            return;
        }

        try {
            await ERP.api.delete('/employees/' + id);
            ERP.toast.success('Employee deleted');
            loadEmployees();
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to delete employee');
        }
    }
</script>

<style>
    .avatar-sm {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, var(--primary-500), var(--primary-400));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .flex-1 {
        flex: 1;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
