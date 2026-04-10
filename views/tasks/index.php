<?php
$title = 'Tasks';
$page = 'tasks';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">All Tasks</h1>
        <p class="text-muted text-sm">Manage tasks across all projects</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('task-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        New Task
    </button>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-5 mb-6">
    <div class="card stat-card">
        <div class="stat-value" id="total-tasks">0</div>
        <div class="stat-label">Total</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-warning" id="pending-tasks">0</div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-primary" id="in-progress-tasks">0</div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-success" id="completed-tasks">0</div>
        <div class="stat-label">Completed</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-error" id="overdue-tasks">0</div>
        <div class="stat-label">Overdue</div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4 items-center flex-wrap">
        <div class="flex-1" style="min-width: 200px;">
            <input type="text" class="form-input" id="search-input" placeholder="Search tasks...">
        </div>
        <select class="form-select" id="status-filter" style="width: 140px;">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
        </select>
        <select class="form-select" id="project-filter" style="width: 180px;">
            <option value="">All Projects</option>
        </select>
        <select class="form-select" id="assignee-filter" style="width: 180px;">
            <option value="">All Assignees</option>
        </select>
        <select class="form-select" id="priority-filter" style="width: 120px;">
            <option value="">All Priority</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>
        <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
    </div>
</div>

<!-- Tasks Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="tasks-table">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Task</th>
                    <th>Project</th>
                    <th>Assignee</th>
                    <th>Due Date</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="8" class="text-center text-muted">Loading tasks...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="flex justify-between items-center mt-6">
    <div class="text-sm text-muted" id="pagination-info">Showing 0 of 0</div>
    <div class="flex gap-2">
        <button class="btn btn-secondary btn-sm" id="prev-btn" disabled>Previous</button>
        <button class="btn btn-secondary btn-sm" id="next-btn" disabled>Next</button>
    </div>
</div>

<!-- Create/Edit Task Modal -->
<div class="modal" id="task-modal" style="max-width: 600px;">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">New Task</h3>
        <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <form id="task-form">
        <input type="hidden" name="id" id="task-id">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Title</label>
                <input type="text" class="form-input" name="title" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Project</label>
                    <select class="form-select" name="project_id" id="project-select">
                        <option value="">No Project</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Assignee</label>
                    <select class="form-select" name="assigned_to" id="assignee-select">
                        <option value="">Unassigned</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="3"></textarea>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" class="form-input" name="due_date">
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority">
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Est. Hours</label>
                    <input type="number" step="0.5" class="form-input" name="estimated_hours" value="0">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Task</button>
        </div>
    </form>
</div>

<script>
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};
    let editingId = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadTasks();
        loadProjects();
        loadEmployees();

        document.getElementById('task-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                if (editingId) {
                    await ERP.api.put('/tasks/' + editingId, data);
                    ERP.toast.success('Task updated');
                } else {
                    await ERP.api.post('/tasks', data);
                    ERP.toast.success('Task created');
                }
                closeModal();
                this.reset();
                loadTasks();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        document.getElementById('search-input').addEventListener('input', debounce(applyFilters, 300));
        document.getElementById('status-filter').addEventListener('change', applyFilters);
        document.getElementById('project-filter').addEventListener('change', applyFilters);
        document.getElementById('assignee-filter').addEventListener('change', applyFilters);
        document.getElementById('priority-filter').addEventListener('change', applyFilters);

        document.getElementById('prev-btn').addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; loadTasks(); }
        });
        document.getElementById('next-btn').addEventListener('click', () => {
            if (currentPage < totalPages) { currentPage++; loadTasks(); }
        });
    });

    async function loadTasks() {
        const params = new URLSearchParams({ page: currentPage, per_page: 20, ...currentFilters });

        try {
            const response = await ERP.api.get('/tasks?' + params);
            if (response.success) {
                renderTasks(response.data);
                totalPages = response.meta.total_pages || 1;
                updatePagination(response.meta);
                updateStats(response.data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load tasks');
        }
    }

    async function loadProjects() {
        try {
            const response = await ERP.api.get('/projects?per_page=100');
            if (response.success) {
                const options = response.data.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
                document.getElementById('project-select').innerHTML = '<option value="">No Project</option>' + options;
                document.getElementById('project-filter').innerHTML = '<option value="">All Projects</option>' + options;
            }
        } catch (error) { console.error(error); }
    }

    async function loadEmployees() {
        try {
            const response = await ERP.api.get('/employees?per_page=100');
            if (response.success) {
                const options = response.data.map(e => `<option value="${e.user_id || e.id}">${e.first_name} ${e.last_name}</option>`).join('');
                document.getElementById('assignee-select').innerHTML = '<option value="">Unassigned</option>' + options;
                document.getElementById('assignee-filter').innerHTML = '<option value="">All Assignees</option>' + options;
            }
        } catch (error) { console.error(error); }
    }

    function updateStats(tasks) {
        const stats = { total: 0, pending: 0, in_progress: 0, completed: 0, overdue: 0 };
        const today = new Date().toISOString().split('T')[0];

        tasks.forEach(t => {
            stats.total++;
            if (t.status === 'pending') stats.pending++;
            if (t.status === 'in_progress') stats.in_progress++;
            if (t.status === 'completed') stats.completed++;
            if (t.due_date && t.due_date < today && t.status !== 'completed') stats.overdue++;
        });

        document.getElementById('total-tasks').textContent = stats.total;
        document.getElementById('pending-tasks').textContent = stats.pending;
        document.getElementById('in-progress-tasks').textContent = stats.in_progress;
        document.getElementById('completed-tasks').textContent = stats.completed;
        document.getElementById('overdue-tasks').textContent = stats.overdue;
    }

    function renderTasks(tasks) {
        const tbody = document.querySelector('#tasks-table tbody');
        const today = new Date().toISOString().split('T')[0];

        if (!tasks || tasks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No tasks found</td></tr>';
            return;
        }

        tbody.innerHTML = tasks.map(t => {
            const isOverdue = t.due_date && t.due_date < today && t.status !== 'completed';
            return `
            <tr class="${isOverdue ? 'row-overdue' : ''}">
                <td>
                    <input type="checkbox" 
                        ${t.status === 'completed' ? 'checked' : ''} 
                        onchange="toggleComplete(${t.id}, this.checked)"
                        class="task-checkbox">
                </td>
                <td>
                    <div class="font-medium ${t.status === 'completed' ? 'task-completed' : ''}">${t.title}</div>
                    ${t.description ? `<div class="text-sm text-muted">${t.description.substring(0, 60)}${t.description.length > 60 ? '...' : ''}</div>` : ''}
                </td>
                <td>${t.project_name || '<span class="text-muted">No Project</span>'}</td>
                <td>${t.assignee_name || '<span class="text-muted">Unassigned</span>'}</td>
                <td class="${isOverdue ? 'text-error' : ''}">${t.due_date ? formatDate(t.due_date) : '-'}</td>
                <td><span class="badge badge-${getPriorityColor(t.priority)}">${t.priority || 'medium'}</span></td>
                <td><span class="badge badge-${getStatusColor(t.status)}">${formatStatus(t.status)}</span></td>
                <td>
                    <div class="flex gap-1">
                        <button class="btn btn-sm btn-secondary" onclick="editTask(${t.id})" title="Edit">✎</button>
                        <button class="btn btn-sm btn-secondary" onclick="deleteTask(${t.id})" title="Delete">🗑</button>
                    </div>
                </td>
            </tr>
        `}).join('');
    }

    function updatePagination(meta) {
        document.getElementById('pagination-info').textContent =
            `Showing ${meta.from || 0}-${meta.to || 0} of ${meta.total || 0}`;
        document.getElementById('prev-btn').disabled = currentPage <= 1;
        document.getElementById('next-btn').disabled = currentPage >= totalPages;
    }

    function applyFilters() {
        currentFilters = {};
        const search = document.getElementById('search-input').value;
        const status = document.getElementById('status-filter').value;
        const project = document.getElementById('project-filter').value;
        const assignee = document.getElementById('assignee-filter').value;
        const priority = document.getElementById('priority-filter').value;

        if (search) currentFilters.search = search;
        if (status) currentFilters.status = status;
        if (project) currentFilters.project_id = project;
        if (assignee) currentFilters.assigned_to = assignee;
        if (priority) currentFilters.priority = priority;

        currentPage = 1;
        loadTasks();
    }

    function clearFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('status-filter').value = '';
        document.getElementById('project-filter').value = '';
        document.getElementById('assignee-filter').value = '';
        document.getElementById('priority-filter').value = '';
        currentFilters = {};
        currentPage = 1;
        loadTasks();
    }

    async function editTask(id) {
        try {
            const response = await ERP.api.get('/tasks/' + id);
            if (response.success) {
                const t = response.data;
                editingId = id;
                document.getElementById('modal-title').textContent = 'Edit Task';

                const form = document.getElementById('task-form');
                form.querySelector('[name="title"]').value = t.title || '';
                form.querySelector('[name="project_id"]').value = t.project_id || '';
                form.querySelector('[name="assigned_to"]').value = t.assigned_to || '';
                form.querySelector('[name="description"]').value = t.description || '';
                form.querySelector('[name="due_date"]').value = t.due_date || '';
                form.querySelector('[name="priority"]').value = t.priority || 'medium';
                form.querySelector('[name="estimated_hours"]').value = t.estimated_hours || 0;

                Modal.open('task-modal');
            }
        } catch (error) { ERP.toast.error(error.message); }
    }

    async function toggleComplete(id, completed) {
        try {
            await ERP.api.patch('/tasks/' + id + '/status', { status: completed ? 'completed' : 'pending' });
            loadTasks();
        } catch (error) { ERP.toast.error(error.message); }
    }

    async function deleteTask(id) {
        if (!confirm('Delete this task?')) return;
        try {
            await ERP.api.delete('/tasks/' + id);
            ERP.toast.success('Task deleted');
            loadTasks();
        } catch (error) { ERP.toast.error(error.message); }
    }

    function closeModal() {
        Modal.close('task-modal');
        editingId = null;
        document.getElementById('modal-title').textContent = 'New Task';
        document.getElementById('task-form').reset();
    }

    function getStatusColor(status) {
        return { pending: 'secondary', in_progress: 'primary', completed: 'success' }[status] || 'secondary';
    }

    function getPriorityColor(priority) {
        return { high: 'error', medium: 'warning', low: 'secondary' }[priority] || 'secondary';
    }

    function formatStatus(status) {
        return status ? status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : '';
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function debounce(fn, delay) {
        let timer;
        return function (...args) { clearTimeout(timer); timer = setTimeout(() => fn.apply(this, args), delay); };
    }
</script>

<style>
    .stat-card {
        text-align: center;
        padding: var(--space-4);
    }

    .stat-card .stat-value {
        font-size: var(--text-xl);
        font-weight: 700;
    }

    .text-success {
        color: var(--success-500);
    }

    .text-warning {
        color: var(--warning-500);
    }

    .text-primary {
        color: var(--primary-500);
    }

    .text-error {
        color: var(--error-500);
    }

    .row-overdue {
        background: rgba(239, 68, 68, 0.05);
    }

    .task-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .task-completed {
        text-decoration: line-through;
        color: var(--text-muted);
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
