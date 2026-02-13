<?php
$title = 'Time Tracking';
$page = 'time-tracking';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Time Tracking</h1>
        <p class="text-muted text-sm">Log and manage work hours</p>
    </div>
    <div class="flex gap-3">
        <button class="btn btn-outline" id="timer-btn" onclick="toggleTimer()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="5 3 19 12 5 21 5 3" />
            </svg>
            <span id="timer-display">00:00:00</span>
        </button>
        <button class="btn btn-primary" onclick="Modal.open('timelog-modal')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Log Time
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-4 mb-6">
    <div class="card stat-card">
        <div class="stat-value" id="today-hours">0.0h</div>
        <div class="stat-label">Today</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value" id="week-hours">0.0h</div>
        <div class="stat-label">This Week</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value" id="billable-hours">0.0h</div>
        <div class="stat-label">Billable</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value" id="overtime-hours">0.0h</div>
        <div class="stat-label">Overtime</div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4 items-center">
        <select class="form-select" id="employee-filter" style="flex: 2;">
            <option value="">All Employees</option>
        </select>
        <div class="form-group" style="margin: 0; flex: 1;">
            <input type="date" class="form-input" id="start-date">
        </div>
        <span class="text-muted">to</span>
        <div class="form-group" style="margin: 0; flex: 1;">
            <input type="date" class="form-input" id="end-date">
        </div>
        <select class="form-select" id="project-filter" style="flex: 2;">
            <option value="">All Projects</option>
        </select>
        <select class="form-select" id="approved-filter" style="width: 140px;">
            <option value="">All Status</option>
            <option value="true">Approved</option>
            <option value="false">Pending</option>
        </select>
        <button class="btn btn-secondary" onclick="loadTimeLogs()">Filter</button>
        <button class="btn btn-success" id="approve-all-btn" style="display: none;" onclick="approveSelected()">Approve
            Selected</button>
    </div>
</div>

<!-- Time Logs Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="timelogs-table">
            <thead>
                <tr>
                    <th style="width: 30px;"><input type="checkbox" id="select-all" onchange="toggleSelectAll()"></th>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Project</th>
                    <th>Hours</th>
                    <th>Description</th>
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

<!-- Log Time Modal -->
<div class="modal" id="timelog-modal">
    <div class="modal-header">
        <h3 class="modal-title">Log Time</h3>
        <button class="modal-close" onclick="Modal.close('timelog-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="timelog-form">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Employee</label>
                <select class="form-select" name="employee_id" id="modal-employee-select" required>
                    <option value="">Select Employee</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Date</label>
                    <input type="date" class="form-input" name="log_date" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Hours</label>
                    <input type="number" class="form-input" name="hours" step="0.25" min="0.25" max="24" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Project</label>
                <select class="form-select" name="project_id" id="modal-project-select">
                    <option value="">Select Project</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Task</label>
                <select class="form-select" name="task_id" id="modal-task-select">
                    <option value="">Select Task</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="3" placeholder="What did you work on?"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Start Time</label>
                    <input type="time" class="form-input" name="start_time">
                </div>
                <div class="form-group">
                    <label class="form-label">End Time</label>
                    <input type="time" class="form-input" name="end_time">
                </div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="billable" checked>
                    Billable
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('timelog-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Time Log</button>
        </div>
    </form>
</div>

<script>
    let timer = null;
    let timerRunning = false;
    let employeeId = null;

    document.addEventListener('DOMContentLoaded', function () {
        // Initialize timer after ERP is available
        if (typeof ERP !== 'undefined' && ERP.Timer) {
            timer = new ERP.Timer();
            timer.setDisplay(document.getElementById('timer-display'));
        } else {
            console.warn('ERP.Timer not available');
        }

        // Set default dates (this week)
        const today = new Date();
        const monday = new Date(today);
        monday.setDate(today.getDate() - today.getDay() + 1);

        document.getElementById('start-date').value = monday.toISOString().split('T')[0];
        document.getElementById('end-date').value = today.toISOString().split('T')[0];
        document.querySelector('[name="log_date"]').value = today.toISOString().split('T')[0];

        loadTimeLogs();
        loadProjects();
        loadEmployees();
        loadStats();

        // Project change loads tasks
        document.getElementById('modal-project-select').addEventListener('change', function (e) {
            if (e.target.value) {
                loadTasks(e.target.value);
            }
        });

        // Form submit
        document.getElementById('timelog-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);
            data.billable = this.querySelector('[name="billable"]').checked;

            try {
                await ERP.api.post('/time-logs', data);
                ERP.toast.success('Time log saved');
                Modal.close('timelog-modal');
                this.reset();
                document.querySelector('[name="log_date"]').value = new Date().toISOString().split('T')[0];
                loadTimeLogs();
                loadStats();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadTimeLogs() {
        const params = new URLSearchParams({
            employee_id: document.getElementById('employee-filter').value,
            start_date: document.getElementById('start-date').value,
            end_date: document.getElementById('end-date').value,
            project_id: document.getElementById('project-filter').value,
            approved: document.getElementById('approved-filter').value,
            per_page: 50,
        });

        try {
            const response = await ERP.api.get('/time-logs?' + params);
            if (response.success) {
                renderTimeLogs(response.data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load time logs');
        }
    }

    async function loadProjects() {
        try {
            const response = await ERP.api.get('/projects?status=in_progress&per_page=100');
            if (response.success) {
                const options = response.data.map(p =>
                    `<option value="${p.id}">${p.name}</option>`
                ).join('');

                document.getElementById('project-filter').innerHTML =
                    '<option value="">All Projects</option>' + options;
                document.getElementById('modal-project-select').innerHTML =
                    '<option value="">Select Project</option>' + options;
            }
        } catch (error) {
            console.error('Failed to load projects:', error);
        }
    }

    async function loadEmployees() {
        try {
            const response = await ERP.api.get('/employees?status=active&per_page=100');
            if (response.success) {
                const options = response.data.map(e =>
                    `<option value="${e.id}">${e.first_name} ${e.last_name}</option>`
                ).join('');

                document.getElementById('employee-filter').innerHTML =
                    '<option value="">All Employees</option>' + options;
                document.getElementById('modal-employee-select').innerHTML =
                    '<option value="">Select Employee</option>' + options;
            }
        } catch (error) {
            console.error('Failed to load employees:', error);
        }
    }

    async function loadTasks(projectId) {
        try {
            const response = await ERP.api.get(`/projects/${projectId}/tasks`);
            if (response.success) {
                const options = response.data.map(t =>
                    `<option value="${t.id}">${t.title}</option>`
                ).join('');
                document.getElementById('modal-task-select').innerHTML =
                    '<option value="">Select Task</option>' + options;
            }
        } catch (error) {
            console.error('Failed to load tasks:', error);
        }
    }

    async function loadStats() {
        try {
            const today = new Date().toISOString().split('T')[0];
            const monday = new Date();
            monday.setDate(monday.getDate() - monday.getDay() + 1);
            const weekStart = monday.toISOString().split('T')[0];

            const response = await ERP.api.get(`/time-logs?start_date=${weekStart}&end_date=${today}&per_page=100`);
            if (response.success) {
                let todayHours = 0, weekHours = 0, billableHours = 0, overtimeHours = 0;

                response.data.forEach(log => {
                    const hours = parseFloat(log.hours);
                    weekHours += hours;
                    if (log.log_date === today) todayHours += hours;
                    if (log.billable) billableHours += hours;
                    if (log.is_overtime) overtimeHours += hours;
                });

                document.getElementById('today-hours').textContent = todayHours.toFixed(1) + 'h';
                document.getElementById('week-hours').textContent = weekHours.toFixed(1) + 'h';
                document.getElementById('billable-hours').textContent = billableHours.toFixed(1) + 'h';
                document.getElementById('overtime-hours').textContent = overtimeHours.toFixed(1) + 'h';
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    function renderTimeLogs(logs) {
        const tbody = document.querySelector('#timelogs-table tbody');

        if (logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No time logs found</td></tr>';
            return;
        }

        tbody.innerHTML = logs.map(log => `
        <tr>
            <td>${!log.approved ? `<input type="checkbox" class="log-checkbox" value="${log.id}" onchange="updateApproveBtn()">` : ''}</td>
            <td>${formatDate(log.log_date)}</td>
            <td>
                <span class="font-medium">${log.first_name || ''} ${log.last_name || ''}</span>
            </td>
            <td>${log.project_name || '-'}</td>
            <td>
                <span class="font-medium">${parseFloat(log.hours).toFixed(2)}h</span>
                ${log.is_overtime ? '<span class="badge badge-warning ml-1">OT</span>' : ''}
            </td>
            <td class="text-sm">${log.description || '-'}</td>
            <td>
                ${log.approved
                ? '<span class="badge badge-success">Approved</span>'
                : '<span class="badge badge-secondary">Pending</span>'
            }
            </td>
            <td>
                <div class="flex gap-1">
                    ${!log.approved ? `
                    <button class="btn btn-icon btn-sm btn-success" onclick="approveLog(${log.id})" title="Approve">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </button>
                    <button class="btn btn-icon btn-sm btn-secondary" onclick="deleteTimeLog(${log.id})" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
    }

    async function toggleTimer() {
        const btn = document.getElementById('timer-btn');

        if (!timerRunning) {
            // Start timer
            try {
                await ERP.api.post('/time-logs/timer/start', {});
                if (timer) timer.start();
                timerRunning = true;
                btn.classList.remove('btn-outline');
                btn.classList.add('btn-danger');
                btn.querySelector('svg').innerHTML = '<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>';
                ERP.toast.info('Timer started');
            } catch (error) {
                ERP.toast.error(error.message);
            }
        } else {
            // Stop timer
            try {
                await ERP.api.post('/time-logs/timer/stop', {});
                if (timer) timer.stop();
                timerRunning = false;
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-outline');
                btn.querySelector('svg').innerHTML = '<polygon points="5 3 19 12 5 21 5 3"/>';
                ERP.toast.success('Timer stopped');
                loadTimeLogs();
                loadStats();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        }
    }

    async function deleteTimeLog(id) {
        if (!confirm('Delete this time log?')) return;

        try {
            await ERP.api.delete('/time-logs/' + id);
            ERP.toast.success('Time log deleted');
            loadTimeLogs();
            loadStats();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    async function approveLog(id) {
        try {
            await ERP.api.post('/time-logs/' + id + '/approve');
            ERP.toast.success('Time log approved');
            loadTimeLogs();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    async function approveSelected() {
        const checked = document.querySelectorAll('.log-checkbox:checked');
        if (checked.length === 0) return;

        try {
            for (const cb of checked) {
                await ERP.api.post('/time-logs/' + cb.value + '/approve');
            }
            ERP.toast.success(checked.length + ' logs approved');
            loadTimeLogs();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    function toggleSelectAll() {
        const all = document.getElementById('select-all').checked;
        document.querySelectorAll('.log-checkbox').forEach(cb => cb.checked = all);
        updateApproveBtn();
    }

    function updateApproveBtn() {
        const count = document.querySelectorAll('.log-checkbox:checked').length;
        document.getElementById('approve-all-btn').style.display = count > 0 ? '' : 'none';
    }

    function formatDate(date) {
        return new Date(date).toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        });
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

    .ml-1 {
        margin-left: var(--space-1);
    }

    .btn-danger {
        background: var(--error-500);
        color: white;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>