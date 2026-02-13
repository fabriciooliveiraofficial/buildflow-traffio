let project = null;
let employees = [];

// Get current user from localStorage for permission checks
const currentUser = JSON.parse(localStorage.getItem('erp_user') || '{}');
// Check for admin role OR projects.delete permission
const isAdmin = currentUser.role === 'admin' || currentUser.role_name === 'admin';
const userPermissions = currentUser.permissions || [];
const canDeleteProjects = isAdmin || userPermissions.includes('projects.delete');

console.log('User Role Check:', {
    role: currentUser.role,
    role_name: currentUser.role_name,
    permissions: userPermissions,
    isAdmin,
    canDeleteProjects
});

// Show delete button only for admins or users with projects.delete permission
if (canDeleteProjects) {
    document.getElementById('delete-project-btn').style.display = '';
}

async function deleteProject() {
    if (!confirm('Are you sure you want to delete this project? This will delete all associated data and cannot be undone.')) {
        return;
    }

    try {
        await ERP.api.delete('/projects/' + projectId);
        ERP.toast.success('Project deleted');
        // Redirect to projects list, preserving tenant prefix
        const basePath = window.location.pathname.split('/projects')[0];
        window.location.href = basePath + '/projects';
    } catch (error) {
        ERP.toast.error(error.message || 'Failed to delete project');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    loadProject();
    loadEmployees();
    setupQuickExpenseLogic();

    // Handle hash navigation for deep linking (e.g., from notifications)
    if (window.location.hash) {
        const tab = window.location.hash.substring(1); // Remove '#'
        if (tab && document.getElementById('tab-' + tab)) {
            switchTab(tab);
        }
    }

    document.getElementById('task-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const data = ERP.FormUtils.serialize(this);
        data.project_id = projectId;

        try {
            await ERP.api.post('/tasks', data);
            ERP.toast.success('Task added');
            Modal.close('task-modal');
            this.reset();
            loadTasks();

            // Reload notifications immediately (if function exists)
            if (window.loadNotifications) {
                window.loadNotifications();
            }
        } catch (error) {
            ERP.toast.error(error.message);
        }
    });

    // Time Log Form Handler
    document.getElementById('timelog-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const data = ERP.FormUtils.serialize(this);
        data.project_id = projectId;

        try {
            await ERP.api.post('/time-logs', data);
            ERP.toast.success('Time logged');
            Modal.close('timelog-modal');
            this.reset();
            // Reset date to today
            this.querySelector('[name="log_date"]').value = new Date().toISOString().split('T')[0];
            loadTimeLogs();
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to log time');
        }
    });

    // Upload Document Form Handler
    document.getElementById('upload-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('project_id', projectId);

        try {
            const token = localStorage.getItem('erp_token');
            const response = await fetch('/api/documents', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                ERP.toast.success('Document uploaded');
                Modal.close('upload-modal');
                this.reset();
                loadDocuments();
            } else {
                throw new Error(result.error || 'Upload failed');
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to upload document');
        }
    });

    // Edit Project Form Handler
    document.getElementById('edit-project-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const data = ERP.FormUtils.serialize(this);

        try {
            await ERP.api.put('/projects/' + projectId, data);
            ERP.toast.success('Project updated');
            Modal.close('edit-modal');
            loadProject();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    });

    // Edit Button Handler
    const editBtn = document.getElementById('edit-project-btn');
    if (editBtn) {
        editBtn.addEventListener('click', function () {
            console.log('Edit button clicked');

            if (!project) {
                console.error('Project data not loaded');
                ERP.toast.warning('Please wait for project data to load...');
                return;
            }

            const form = document.getElementById('edit-project-form');
            if (!form) {
                console.error('Edit form not found');
                ERP.toast.error('Edit form configuration error');
                return;
            }

            try {
                form.querySelector('[name="name"]').value = project.name || '';
                form.querySelector('[name="code"]').value = project.code || '';
                form.querySelector('[name="contract_value"]').value = project.contract_value || '';
                form.querySelector('[name="priority"]').value = project.priority || 'medium';
                form.querySelector('[name="start_date"]').value = project.start_date || '';
                form.querySelector('[name="end_date"]').value = project.end_date || '';
                form.querySelector('[name="address"]').value = project.address || '';
                form.querySelector('[name="description"]').value = project.description || '';

                console.log('Opening edit modal');
                Modal.open('edit-modal');
            } catch (e) {
                console.error('Error populating form:', e);
                ERP.toast.error('Error preparing edit form');
            }
        });
    }
});

async function loadProject() {
    try {
        const response = await ERP.api.get('/projects/' + projectId);
        if (response.success) {
            project = response.data;
            renderProject();
            loadTasks();
            loadBudgets();
            loadTimeLogs();
            loadLaborCost();
            loadFinancials();
            loadDocuments();
        }


    } catch (error) {
        ERP.toast.error('Failed to load project');
    }
}

async function loadDocuments() {
    try {
        const response = await ERP.api.get(`/documents?project_id=${projectId}`);
        if (response.success) {
            renderDocuments(response.data);
        }
    } catch (error) {
        console.error('Failed to load documents');
    }
}

function renderDocuments(documents) {
    const container = document.getElementById('documents-list');
    if (!documents || documents.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No documents yet</p>';
        return;
    }

    container.innerHTML = documents.map(doc => `
            <div class="document-item flex justify-between items-center p-3 border-b">
                <div class="flex items-center gap-3">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <div>
                        <a href="${doc.path}" target="_blank" class="font-medium">${doc.name}</a>
                        <p class="text-sm text-muted">${doc.original_name} • ${formatFileSize(doc.size)}</p>
                    </div>
                </div>
                <button class="btn btn-icon btn-sm btn-error" onclick="deleteDocument(${doc.id})" title="Delete">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                    </svg>
                </button>
            </div>
        `).join('');
}

function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

async function deleteDocument(id) {
    if (!confirm('Delete this document?')) return;
    try {
        await ERP.api.delete(`/documents/${id}`);
        ERP.toast.success('Document deleted');
        loadDocuments();
    } catch (error) {
        ERP.toast.error('Failed to delete document');
    }
}

function renderProject() {
    if (project) updateProjectLockState(project.status);
    document.getElementById('project-name').textContent = project.name;
    document.getElementById('project-code').textContent = project.code || '';
    document.getElementById('status-select').value = project.status;

    // Use computed values from API (total_budget, total_spent, progress, hours_logged)
    document.getElementById('stat-progress').textContent = (project.progress || 0) + '%';
    document.getElementById('stat-budget').textContent = formatCurrency(project.total_budget || 0);
    document.getElementById('stat-spent').textContent = formatCurrency(project.total_spent || 0);
    document.getElementById('stat-hours').textContent = (project.hours_logged || 0) + 'h';

    document.getElementById('detail-client').textContent = project.client_name || '-';
    document.getElementById('detail-priority').innerHTML = `<span class="badge badge-${getPriorityColor(project.priority)}">${project.priority || '-'}</span>`;
    document.getElementById('detail-start').textContent = project.start_date ? formatDate(project.start_date) : '-';
    document.getElementById('detail-end').textContent = project.end_date ? formatDate(project.end_date) : '-';
    document.getElementById('detail-address').textContent = project.address || '-';
    document.getElementById('detail-manager').textContent = project.manager_name || '-';
    document.getElementById('detail-description').textContent = project.description || 'No description';

    renderBudgetChart();
}

function renderBudgetChart() {
    const ctx = document.getElementById('budget-chart');
    const totalBudget = project.total_budget || 0;
    const spent = project.total_spent || 0;
    const remaining = Math.max(0, totalBudget - spent);

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Spent', 'Remaining'],
            datasets: [{
                data: [spent, remaining],
                backgroundColor: ['#ff9800', '#4caf50']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    const pct = totalBudget > 0 ? Math.round((spent / totalBudget) * 100) : 0;
    document.getElementById('budget-summary').innerHTML = `
        <div class="text-center text-sm">
            <span class="${pct > 100 ? 'text-error' : ''}">${pct}% of budget used</span>
        </div>
    `;
}

async function loadTasks() {
    try {
        const response = await ERP.api.get(`/projects/${projectId}/tasks`);
        if (response.success) {
            renderTasks(response.data);
        }
    } catch (error) {
        console.error('Failed to load tasks');
    }
}

function renderTasks(tasks) {
    const tbody = document.querySelector('#tasks-table tbody');
    if (tasks.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No tasks</td></tr>';
        return;
    }

    tbody.innerHTML = tasks.map(t => `
        <tr>
            <td>
                <input type="checkbox" ${t.status === 'completed' ? 'checked' : ''} 
                       onchange="toggleTask(${t.id}, this.checked)">
            </td>
            <td class="${t.status === 'completed' ? 'text-muted line-through' : ''}">${t.title}</td>
            <td>${t.assigned_first_name ? t.assigned_first_name + ' ' + t.assigned_last_name : '-'}</td>
            <td>${t.due_date ? formatDate(t.due_date) : '-'}</td>
            <td><span class="badge badge-${getPriorityColor(t.priority)}">${t.priority}</span></td>
            <td><span class="badge badge-${getStatusColor(t.status)}">${t.status}</span></td>
            <td>
                <button class="btn btn-icon btn-sm btn-secondary" onclick="deleteTask(${t.id})">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');
}

async function loadBudgets() {
    try {
        const response = await ERP.api.get(`/projects/${projectId}/budget`);
        if (response.success) {
            renderBudgets(response.data);
        }
    } catch (error) {
        console.error('Failed to load budgets');
    }
    // Also load expenses for Budget tab
    loadBudgetExpenses();
}

function renderBudgets(budgets) {
    const tbody = document.querySelector('#budgets-table tbody');
    if (!budgets || budgets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No budgets</td></tr>';
        return;
    }

    tbody.innerHTML = budgets.map(b => {
        const remaining = b.budgeted_amount - b.spent_amount;
        const pct = b.budgeted_amount > 0 ? Math.round((b.spent_amount / b.budgeted_amount) * 100) : 0;
        return `
        <tr>
            <td>${b.category}</td>
            <td>${formatCurrency(b.budgeted_amount)}</td>
            <td>${formatCurrency(b.spent_amount)}</td>
            <td class="${remaining < 0 ? 'text-error' : ''}">${formatCurrency(remaining)}</td>
            <td>
                <div class="progress" style="width: 60px;">
                    <div class="progress-bar ${pct > 100 ? 'error' : ''}" style="width: ${Math.min(pct, 100)}%"></div>
                </div>
            </td>
        </tr>
    `}).join('');
}

async function loadBudgetExpenses() {
    try {
        const response = await ERP.api.get(`/expenses?project_id=${projectId}&per_page=100`);
        if (response.success) {
            renderBudgetExpenses(response.data);
        }
    } catch (error) {
        console.error('Failed to load budget expenses');
    }
}

function renderBudgetExpenses(expenses) {
    const tbody = document.querySelector('#expenses-table tbody');
    if (!expenses || expenses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No expenses</td></tr>';
        return;
    }

    tbody.innerHTML = expenses.map(e => `
            <tr>
                <td>${formatDate(e.expense_date)}</td>
                <td>${e.category || '-'}</td>
                <td>${e.description || '-'}</td>
                <td>${formatCurrency(e.amount)}</td>
                <td>
                    <button class="btn btn-icon btn-sm btn-secondary" onclick="editTransaction('expense', ${e.id})" title="Edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn btn-icon btn-sm btn-error" onclick="deleteTransaction('expense', ${e.id})" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                        </svg>
                    </button>
                </td>
            </tr>
        `).join('');
}

async function loadLaborCost() {
    try {
        const response = await ERP.api.get(`/expenses?project_id=${projectId}&category=labor&per_page=100`);
        console.log('Labor Cost Response:', response);
        if (response.success) {
            console.log('Labor expenses data:', response.data);
            renderLaborCost(response.data);
        }
    } catch (error) {
        console.error('Failed to load labor cost:', error);
    }
}

function renderLaborCost(expenses) {
    // Group expenses by employee
    const laborByEmployee = {};
    let totalLabor = 0;
    let laborBudget = 0;

    expenses.forEach(e => {
        const empId = e.employee_id || 'unknown';
        const empName = e.employee_name || 'Unknown Employee';

        if (!laborByEmployee[empId]) {
            laborByEmployee[empId] = {
                name: empName,
                total: 0,
                hours: 0,
                payType: e.employee_payment_type || 'hourly'
            };
        }

        laborByEmployee[empId].total += parseFloat(e.amount);
        // Try to extract hours from description or metadata
        if (e.hours) {
            laborByEmployee[empId].hours += parseFloat(e.hours);
        }

        totalLabor += parseFloat(e.amount);
    });

    // Get labor budget from budgets
    const budgets = project?.budgets || [];
    const laborBudgetItem = budgets.find(b => b.category === 'labor');
    laborBudget = laborBudgetItem ? parseFloat(laborBudgetItem.budgeted_amount) : 0;

    // Update stats
    document.getElementById('labor-budget').textContent = formatCurrency(laborBudget);
    document.getElementById('labor-actual').textContent = formatCurrency(totalLabor);

    const variance = laborBudget - totalLabor;
    const varianceEl = document.getElementById('labor-variance');
    varianceEl.textContent = formatCurrency(Math.abs(variance));
    varianceEl.className = 'stat-value ' + (variance >= 0 ? 'text-success' : 'text-error');

    const totalHours = Object.values(laborByEmployee).reduce((sum, emp) => sum + emp.hours, 0);
    const costPerHour = totalHours > 0 ? totalLabor / totalHours : 0;
    document.getElementById('labor-cph').textContent = formatCurrency(costPerHour);

    // Render table
    const tbody = document.querySelector('#labor-table tbody');
    const employeeData = Object.values(laborByEmployee);

    if (employeeData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No labor data</td></tr>';
        return;
    }

    tbody.innerHTML = employeeData.map(emp => `
            <tr>
                <td>${emp.name}</td>
                <td><span class="badge badge-${getPaymentTypeBadgeClass(emp.payType)}">${formatPayType(emp.payType)}</span></td>
                <td>${emp.hours.toFixed(1)}</td>
                <td>0</td>
                <td>${emp.hours.toFixed(1)}</td>
                <td>${formatCurrency(emp.total)}</td>
            </tr>
        `).join('');
}


async function loadTimeLogs() {
    try {
        const response = await ERP.api.get(`/projects/${projectId}/time-logs`);
        if (response.success) {
            renderTimeLogs(response.data);
        }
    } catch (error) {
        console.error('Failed to load time logs');
    }
}

function renderTimeLogs(logs) {
    const tbody = document.querySelector('#timelogs-table tbody');
    if (!logs || logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No time logs</td></tr>';
        return;
    }

    tbody.innerHTML = logs.map(l => `
        <tr>
            <td>${formatDate(l.log_date)}</td>
            <td>${l.employee_name || '-'}</td>
            <td>${l.task_title || '-'}</td>
            <td>${l.hours}h</td>
            <td class="text-sm">${l.description || '-'}</td>
            <td>
                <button class="btn btn-icon btn-sm btn-secondary" onclick="editTimeLog(${l.id})" title="Edit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
                <button class="btn btn-icon btn-sm btn-error" onclick="deleteTimeLog(${l.id})" title="Delete">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');
}

// Edit Time Log
async function editTimeLog(id) {
    try {
        const response = await ERP.api.get(`/time-logs/${id}`);
        if (response.success) {
            const log = response.data;
            document.getElementById('edit-timelog-id').value = log.id;
            document.getElementById('edit-timelog-date').value = log.log_date;
            document.getElementById('edit-timelog-hours').value = log.hours;
            document.getElementById('edit-timelog-description').value = log.description || '';

            // Populate and select employee
            populateEditTimelogEmployee();
            document.getElementById('edit-timelog-employee').value = log.employee_id;

            Modal.open('edit-timelog-modal');
        }
    } catch (error) {
        ERP.toast.error('Failed to load time log');
    }
}

function populateEditTimelogEmployee() {
    const select = document.getElementById('edit-timelog-employee');
    if (!select || !employees) return;

    select.innerHTML = '<option value="">Select Employee</option>';
    const sortedEmployees = [...employees].sort((a, b) => {
        const nameA = `${a.first_name} ${a.last_name}`.toLowerCase();
        const nameB = `${b.first_name} ${b.last_name}`.toLowerCase();
        return nameA.localeCompare(nameB);
    });

    sortedEmployees.forEach(emp => {
        const option = document.createElement('option');
        option.value = emp.id;
        option.textContent = `${emp.first_name} ${emp.last_name}`;
        select.appendChild(option);
    });
}

// Delete Time Log
async function deleteTimeLog(id) {
    if (!confirm('Are you sure you want to delete this time log? This action cannot be undone.')) {
        return;
    }

    try {
        await ERP.api.delete(`/time-logs/${id}`);
        ERP.toast.success('Time log deleted');
        loadTimeLogs();
    } catch (error) {
        ERP.toast.error(error.message || 'Failed to delete time log');
    }
}

// Edit Timelog Form Handler
document.getElementById('edit-timelog-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const id = document.getElementById('edit-timelog-id').value;
    const data = {
        employee_id: document.getElementById('edit-timelog-employee').value,
        log_date: document.getElementById('edit-timelog-date').value,
        hours: document.getElementById('edit-timelog-hours').value,
        description: document.getElementById('edit-timelog-description').value
    };

    try {
        await ERP.api.put(`/time-logs/${id}`, data);
        ERP.toast.success('Time log updated');
        Modal.close('edit-timelog-modal');
        loadTimeLogs();
    } catch (error) {
        ERP.toast.error(error.message || 'Failed to update time log');
    }
});

function formatPayType(type) {
    return { hourly: 'Hourly', daily: 'Daily', salary: 'Salary', project: 'Project', commission: 'Commission' }[type] || type;
}

async function toggleTask(taskId, completed) {
    try {
        await ERP.api.put('/tasks/' + taskId, {
            status: completed ? 'completed' : 'pending'
        });
        loadTasks();
        loadProject();
    } catch (error) {
        ERP.toast.error(error.message);
    }
}

async function deleteTask(taskId) {
    if (!confirm('Delete this task?')) return;
    try {
        await ERP.api.delete('/tasks/' + taskId);
        ERP.toast.success('Task deleted');
        loadTasks();
    } catch (error) {
        ERP.toast.error(error.message);
    }
}

async function updateStatus() {
    const status = document.getElementById('status-select').value;
    try {
        await ERP.api.put('/projects/' + projectId, { status });
        ERP.toast.success('Status updated');
    } catch (error) {
        ERP.toast.error(error.message);
    }
}

function switchTab(tab) {
    document.querySelectorAll('[id^="tab-"]').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('[id$="-content"]').forEach(el => el.style.display = 'none');
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById(tab + '-content').style.display = 'block';
}

function getPriorityColor(p) {
    return { low: 'secondary', medium: 'primary', high: 'warning', urgent: 'error' }[p] || 'secondary';
}

function getStatusColor(s) {
    return { pending: 'secondary', in_progress: 'primary', completed: 'success', cancelled: 'error' }[s] || 'secondary';
}

function formatCurrency(a) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(a);
}

function formatDate(d) {
    if (!d) return '-';
    // Handle YYYY-MM-DD strings explicitly to avoid UTC conversion (off-by-one error)
    if (typeof d === 'string' && d.length === 10 && d.charAt(4) === '-') {
        const [year, month, day] = d.split('-').map(Number);
        return new Date(year, month - 1, day).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatPaymentMethod(method) {
    if (!method) return '-';
    const methodMap = {
        'cash': 'Cash',
        'check': 'Check',
        'credit_card': 'Credit Card',
        'transfer': 'Bank Transfer',
        'bank_transfer': 'Bank Transfer',
        'other': 'Other'
    };
    return methodMap[method.toLowerCase()] || method.charAt(0).toUpperCase() + method.slice(1).replace(/_/g, ' ');
}

// =====================================================
// FINANCIALS TAB FUNCTIONS
// =====================================================

let expensePieChart = null;
let financialsData = null;

async function loadFinancials() {
    try {
        const response = await ERP.api.get(`/projects/${projectId}/financials`);
        if (response.success) {
            financialsData = response.data;
            renderFinancials(response.data);
        }
    } catch (error) {
        console.error('Failed to load financials:', error);
    }
}

function renderFinancials(data) {
    // KPI Cards
    document.getElementById('fin-contract').textContent = formatCurrency(data.contract_value);
    document.getElementById('fin-income').textContent = formatCurrency(data.income.paid);
    document.getElementById('fin-expenses').textContent = formatCurrency(data.expenses.total);
    document.getElementById('fin-profit').textContent = formatCurrency(data.profit.gross);
    document.getElementById('fin-margin').textContent = data.profit.margin + '%';

    // Second row
    document.getElementById('fin-balance').textContent = formatCurrency(data.balance_due);
    document.getElementById('fin-labor').textContent = formatCurrency(data.labor.cost);
    document.getElementById('fin-burn-rate').textContent = formatCurrency(data.burn_rate.daily) + '/day';

    // Health indicator
    const healthEl = document.getElementById('fin-health-indicator');
    const healthColors = { green: '#22c55e', yellow: '#eab308', red: '#ef4444' };
    healthEl.textContent = '●';
    healthEl.style.color = healthColors[data.health_status] || '#9ca3af';
    healthEl.style.fontSize = '3rem';

    // Profit color
    const profitEl = document.getElementById('fin-profit');
    if (data.profit.gross < 0) {
        profitEl.classList.add('text-error');
    } else {
        profitEl.classList.remove('text-error');
        profitEl.classList.add('text-success');
    }

    // Render expense pie chart
    renderExpenseChart(data.expenses.by_category);

    // Render budget vs actual table
    renderBudgetActual(data.budget.by_category);

    // Load ledger
    loadLedger();
}

function renderExpenseChart(categories) {
    const ctx = document.getElementById('expense-pie-chart');
    if (!ctx) return;

    if (expensePieChart) {
        expensePieChart.destroy();
    }

    if (!categories || categories.length === 0) {
        ctx.parentElement.innerHTML = '<p class="text-center text-muted">No expense data</p>';
        return;
    }

    const colors = [
        '#3b82f6', '#ef4444', '#22c55e', '#f59e0b',
        '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'
    ];

    expensePieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: categories.map(c => c.category.charAt(0).toUpperCase() + c.category.slice(1)),
            datasets: [{
                data: categories.map(c => c.total),
                backgroundColor: colors.slice(0, categories.length),
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });
}

function renderBudgetActual(budgets) {
    const tbody = document.querySelector('#budget-actual-table tbody');

    if (!budgets || budgets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No budget data</td></tr>';
        return;
    }

    const statusIcons = { green: '🟢', yellow: '🟡', red: '🔴' };

    tbody.innerHTML = budgets.map(b => `
            <tr>
                <td>${b.category.charAt(0).toUpperCase() + b.category.slice(1)}</td>
                <td>${formatCurrency(b.budgeted_amount)}</td>
                <td>${formatCurrency(b.spent_amount)}</td>
                <td class="${b.variance < 0 ? 'text-error' : ''}">${formatCurrency(b.variance)}</td>
                <td>${statusIcons[b.status] || '⚪'} ${b.percent_used}%</td>
            </tr>
        `).join('');
}

// Ledger sorting state
let ledgerData = null;
let ledgerSortColumn = 'date';
let ledgerSortDirection = 'desc';

// Ledger pagination state
let ledgerCurrentPage = 1;
let ledgerPerPage = 25;

async function loadLedger() {
    const categoryFilter = document.getElementById('ledger-category-filter')?.value || '';
    const paymentFilter = document.getElementById('ledger-payment-filter')?.value || '';
    const startDate = document.getElementById('ledger-start-date')?.value || '';
    const endDate = document.getElementById('ledger-end-date')?.value || '';

    // Build query string with all filters
    let queryParams = 'per_page=1000';
    if (categoryFilter) queryParams += `&category=${encodeURIComponent(categoryFilter)}`;
    if (paymentFilter) queryParams += `&payment_method=${encodeURIComponent(paymentFilter)}`;
    if (startDate) queryParams += `&start_date=${startDate}`;
    if (endDate) queryParams += `&end_date=${endDate}`;

    try {
        const response = await ERP.api.get('/projects/' + projectId + '/ledger?' + queryParams);
        if (response.success) {
            ledgerData = response.data;
            renderLedger(ledgerData);
        }
    } catch (error) {
        console.error('Failed to load ledger:', error);
    }
}

function clearLedgerFilters() {
    document.getElementById('ledger-payment-filter').value = '';
    document.getElementById('ledger-category-filter').value = '';
    document.getElementById('ledger-start-date').value = '';
    document.getElementById('ledger-end-date').value = '';
    loadLedger();
}

function sortLedger(column) {
    if (!ledgerData || !ledgerData.transactions) return;

    // Toggle direction if same column, otherwise default to ascending (except date defaults to desc)
    if (ledgerSortColumn === column) {
        ledgerSortDirection = ledgerSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        ledgerSortColumn = column;
        ledgerSortDirection = column === 'date' ? 'desc' : 'asc';
    }

    // Sort the transactions
    const sorted = [...ledgerData.transactions].sort((a, b) => {
        let valA, valB;

        switch (column) {
            case 'date':
                valA = new Date(a.date);
                valB = new Date(b.date);
                break;
            case 'payment_method':
                valA = (a.payment_method || '').toLowerCase();
                valB = (b.payment_method || '').toLowerCase();
                break;
            case 'description':
                valA = (a.description || '').toLowerCase();
                valB = (b.description || '').toLowerCase();
                break;
            case 'category':
                valA = (a.category || '').toLowerCase();
                valB = (b.category || '').toLowerCase();
                break;
            case 'vendor':
                valA = (a.vendor || '').toLowerCase();
                valB = (b.vendor || '').toLowerCase();
                break;
            case 'amount':
                valA = parseFloat(a.amount) || 0;
                valB = parseFloat(b.amount) || 0;
                break;
            default:
                return 0;
        }

        if (valA < valB) return ledgerSortDirection === 'asc' ? -1 : 1;
        if (valA > valB) return ledgerSortDirection === 'asc' ? 1 : -1;
        return 0;
    });

    // Update sort icons
    updateSortIcons(column);

    // Render with sorted data (but recalculate running balance)
    renderLedger({ transactions: sorted });
}

function updateSortIcons(activeColumn) {
    const columns = ['date', 'payment_method', 'description', 'category', 'vendor', 'amount'];
    columns.forEach(col => {
        const icon = document.getElementById(`sort-icon-${col}`);
        if (icon) {
            if (col === activeColumn) {
                icon.textContent = ledgerSortDirection === 'asc' ? ' ↑' : ' ↓';
            } else {
                icon.textContent = '';
            }
        }
    });
}


function renderLedger(data) {
    const tbody = document.querySelector('#ledger-table tbody');
    const allTransactions = data.transactions || [];
    const totalCount = allTransactions.length;

    if (totalCount === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No transactions found</td></tr>';
        updateLedgerPagination(0, 0, 0);
        return;
    }

    // Calculate pagination
    const totalPages = Math.ceil(totalCount / ledgerPerPage);

    // Ensure current page is valid
    if (ledgerCurrentPage > totalPages) {
        ledgerCurrentPage = totalPages;
    }
    if (ledgerCurrentPage < 1) {
        ledgerCurrentPage = 1;
    }

    // Get current page transactions
    const startIndex = (ledgerCurrentPage - 1) * ledgerPerPage;
    const endIndex = Math.min(startIndex + ledgerPerPage, totalCount);
    const pageTransactions = allTransactions.slice(startIndex, endIndex);

    // Render current page
    tbody.innerHTML = pageTransactions.map(t => {
        const isIncome = t.type === 'income';
        const amountClass = isIncome ? 'text-success' : 'text-error';
        const amountSign = isIncome ? '+' : '-';
        const paymentMethod = formatPaymentMethod(t.payment_method);

        return '<tr>' +
            '<td>' + formatDate(t.date) + '</td>' +
            '<td>' + paymentMethod + '</td>' +
            '<td>' + (t.description || '-') + '</td>' +
            '<td>' + (t.category || '-') + '</td>' +
            '<td>' + (t.vendor || '-') + '</td>' +
            '<td class="text-right ' + amountClass + '">' + amountSign + formatCurrency(t.amount) + '</td>' +
            '<td class="text-right">' + formatCurrency(t.running_balance) + '</td>' +
            '<td>' +
            '<div class="flex gap-1">' +
            '<button class="btn btn-icon btn-sm btn-secondary" onclick="editTransaction(\'' + t.type + '\', ' + t.id + ', ' + (t.invoice_id || 'null') + ')" title="Edit">✏️</button>' +
            '<button class="btn btn-icon btn-sm btn-error" onclick="deleteTransaction(\'' + t.type + '\', ' + t.id + ', ' + (t.invoice_id || 'null') + ')" title="Delete">🗑️</button>' +
            '</div>' +
            '</td>' +
            '</tr>';
    }).join('');

    // Update pagination UI
    updateLedgerPagination(startIndex + 1, endIndex, totalCount, totalPages);
}

// Update pagination controls
function updateLedgerPagination(start, end, total, totalPages) {
    const pageInfo = document.getElementById('ledger-page-info');
    const pageDisplay = document.getElementById('ledger-page-display');
    const prevBtn = document.getElementById('ledger-prev-btn');
    const nextBtn = document.getElementById('ledger-next-btn');

    if (pageInfo) {
        pageInfo.textContent = total > 0
            ? 'Showing ' + start + '-' + end + ' of ' + total + ' transactions'
            : 'No transactions';
    }

    if (pageDisplay) {
        pageDisplay.textContent = 'Page ' + ledgerCurrentPage + ' of ' + (totalPages || 1);
    }

    if (prevBtn) {
        prevBtn.disabled = ledgerCurrentPage <= 1;
    }

    if (nextBtn) {
        nextBtn.disabled = ledgerCurrentPage >= totalPages;
    }
}

// Pagination navigation functions
function ledgerPrevPage() {
    if (ledgerCurrentPage > 1) {
        ledgerCurrentPage--;
        renderLedger(ledgerData);
    }
}

function ledgerNextPage() {
    const totalPages = Math.ceil((ledgerData?.transactions?.length || 0) / ledgerPerPage);
    if (ledgerCurrentPage < totalPages) {
        ledgerCurrentPage++;
        renderLedger(ledgerData);
    }
}

function ledgerChangePerPage(value) {
    ledgerPerPage = parseInt(value) || 25;
    ledgerCurrentPage = 1; // Reset to first page
    renderLedger(ledgerData);
}

// Edit Transaction - Opens edit modal based on type
async function editTransaction(type, id, invoiceId) {
    if (type === 'income') {
        // For income, id is the payment_id - use the payments endpoint
        try {
            const response = await ERP.api.get(`/payments/${id}`);
            if (response.success) {
                const payment = response.data;
                // Populate edit income modal (store payment id, not invoice id)
                document.getElementById('edit-income-id').value = id;
                document.getElementById('edit-income-amount').value = payment.amount;
                document.getElementById('edit-income-date').value = payment.payment_date;
                document.getElementById('edit-income-description').value = payment.notes || payment.invoice_notes || '';
                Modal.open('edit-income-modal');
            }
        } catch (error) {
            ERP.toast.error('Failed to load income details');
        }
    } else {
        // For expenses
        try {
            const response = await ERP.api.get(`/expenses/${id}`);
            if (response.success) {
                const expense = response.data;
                // Populate edit expense modal
                document.getElementById('edit-expense-id').value = id;
                document.getElementById('edit-expense-amount').value = expense.amount;
                document.getElementById('edit-expense-date').value = expense.expense_date;
                document.getElementById('edit-expense-description').value = expense.description || '';
                document.getElementById('edit-expense-category').value = expense.category || 'other';
                document.getElementById('edit-expense-vendor').value = expense.vendor || '';

                // Handle labor-specific fields
                const employeeContainer = document.getElementById('edit-expense-employee-container');
                const laborCalcContainer = document.getElementById('edit-labor-calc-container');
                const employeeSelect = document.getElementById('edit-expense-employee');

                if (expense.category === 'labor') {
                    // Show employee selection for labor expenses
                    employeeContainer.classList.remove('hidden');
                    // Populate employee dropdown
                    populateEditEmployeeSelect();

                    // Auto-select employee if employee_id exists
                    if (expense.employee_id && employeeSelect) {
                        employeeSelect.value = expense.employee_id;
                        // Trigger the change handler to show payment fields
                        handleEditEmployeeChange(employeeSelect);
                    }
                } else {
                    // Hide labor fields for non-labor expenses
                    employeeContainer.classList.add('hidden');
                    laborCalcContainer.classList.add('hidden');
                    if (employeeSelect) employeeSelect.value = '';
                    resetEditLaborFields();
                }

                Modal.open('edit-expense-modal');
            }
        } catch (error) {
            ERP.toast.error('Failed to load expense details');
        }
    }
}

// Delete Transaction
async function deleteTransaction(type, id, invoiceId) {
    const typeName = type === 'income' ? 'income record' : 'expense';
    if (!confirm(`Are you sure you want to delete this ${typeName}? This action cannot be undone.`)) {
        return;
    }

    try {
        if (type === 'income') {
            // id is the payment_id - use payments endpoint
            await ERP.api.delete(`/payments/${id}`);
        } else {
            await ERP.api.delete(`/expenses/${id}`);
        }
        ERP.toast.success(`${typeName.charAt(0).toUpperCase() + typeName.slice(1)} deleted`);
        loadFinancials();
        loadLedger();
        loadBudgetExpenses();
        loadLaborCost();
    } catch (error) {
        ERP.toast.error(error.message || `Failed to delete ${typeName}`);
    }
}

function exportLedger() {
    if (!financialsData) {
        ERP.toast.error('No data to export');
        return;
    }

    // Fetch ledger data and convert to CSV
    ERP.api.get('/projects/' + projectId + '/ledger?per_page=1000').then(function (response) {
        if (response.success) {
            const transactions = response.data.transactions || [];

            let csv = 'Date,Type,Description,Category,Vendor,Amount,Balance\n';
            transactions.forEach(function (t) {
                csv += '"' + t.date + '","' + t.type + '","' + (t.description || '') + '","' + (t.category || '') + '","' + (t.vendor || '') + '",' + t.amount_display + ',' + t.running_balance + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'project-' + projectId + '-ledger.csv';
            a.click();
            URL.revokeObjectURL(url);

            ERP.toast.success('Ledger exported');
        }
    });
}

function exportLedgerToPDF() {
    if (!financialsData) {
        ERP.toast.error('No data to export');
        return;
    }

    ERP.toast.info('Generating PDF report...');

    // Fetch ledger data
    ERP.api.get('/projects/' + projectId + '/ledger?per_page=1000').then(function (response) {
        if (response.success) {
            const transactions = response.data.transactions || [];
            const projectName = document.getElementById('project-name')?.textContent || 'Project';
            const projectCode = document.getElementById('project-code')?.textContent || '';

            // Calculate totals
            let totalIncome = 0;
            let totalExpenses = 0;
            transactions.forEach(function (t) {
                if (t.type === 'income') {
                    totalIncome += parseFloat(t.amount) || 0;
                } else {
                    totalExpenses += parseFloat(t.amount) || 0;
                }
            });
            const netBalance = totalIncome - totalExpenses;
            const balanceColor = netBalance >= 0 ? '#16a34a' : '#dc2626';
            const generatedDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            // Create print window
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                ERP.toast.error('Please allow popups to export PDF');
                return;
            }

            // Build HTML using string concatenation to avoid PHP interpretation
            let html = '<!DOCTYPE html><html><head>';
            html += '<title>Transaction Ledger - ' + projectName + '</title>';
            html += '<style>';
            html += '* { margin: 0; padding: 0; box-sizing: border-box; }';
            html += 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; color: #1a1a1a; font-size: 12px; }';
            html += '.header { border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }';
            html += '.header h1 { font-size: 24px; font-weight: 700; color: #1e40af; margin-bottom: 5px; }';
            html += '.header .subtitle { color: #64748b; font-size: 14px; }';
            html += '.header .date { float: right; color: #64748b; font-size: 12px; margin-top: -40px; }';
            html += '.summary-cards { display: flex; gap: 20px; margin-bottom: 30px; }';
            html += '.summary-card { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; text-align: center; }';
            html += '.summary-card .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }';
            html += '.summary-card .value { font-size: 20px; font-weight: 700; margin-top: 5px; }';
            html += '.value.income { color: #16a34a; }';
            html += '.value.expense { color: #dc2626; }';
            html += '.value.balance { color: ' + balanceColor + '; }';
            html += 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
            html += 'th { background: #1e40af; color: white; padding: 12px 8px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }';
            html += 'td { padding: 10px 8px; border-bottom: 1px solid #e2e8f0; }';
            html += 'tr:nth-child(even) { background: #f8fafc; }';
            html += '.text-right { text-align: right; }';
            html += '.text-success { color: #16a34a; }';
            html += '.text-error { color: #dc2626; }';
            html += '.badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; text-transform: uppercase; }';
            html += '.badge.income { background: #dcfce7; color: #166534; }';
            html += '.badge.expense { background: #fee2e2; color: #991b1b; }';
            html += '.footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 10px; }';
            html += '@media print { body { padding: 20px; } .no-print { display: none; } }';
            html += '</style></head><body>';

            // Header
            html += '<div class="header">';
            html += '<h1>Transaction Ledger Report</h1>';
            html += '<p class="subtitle">' + projectName + (projectCode ? ' (' + projectCode + ')' : '') + '</p>';
            html += '<p class="date">Generated: ' + generatedDate + '</p>';
            html += '</div>';

            // Summary cards
            html += '<div class="summary-cards">';
            html += '<div class="summary-card"><div class="label">Total Income</div><div class="value income">' + formatCurrency(totalIncome) + '</div></div>';
            html += '<div class="summary-card"><div class="label">Total Expenses</div><div class="value expense">' + formatCurrency(totalExpenses) + '</div></div>';
            html += '<div class="summary-card"><div class="label">Net Balance</div><div class="value balance">' + formatCurrency(netBalance) + '</div></div>';
            html += '<div class="summary-card"><div class="label">Transactions</div><div class="value">' + transactions.length + '</div></div>';
            html += '</div>';

            // Table
            html += '<table><thead><tr>';
            html += '<th>Date</th><th>Type</th><th>Description</th><th>Category</th><th>Vendor/Client</th><th class="text-right">Amount</th><th class="text-right">Balance</th>';
            html += '</tr></thead><tbody>';

            transactions.forEach(function (t) {
                const isIncome = t.type === 'income';
                const amountClass = isIncome ? 'text-success' : 'text-error';
                const amountSign = isIncome ? '+' : '-';
                html += '<tr>';
                html += '<td>' + formatDate(t.date) + '</td>';
                html += '<td><span class="badge ' + t.type + '">' + t.type + '</span></td>';
                html += '<td>' + (t.description || '-') + '</td>';
                html += '<td>' + (t.category || '-') + '</td>';
                html += '<td>' + (t.vendor || '-') + '</td>';
                html += '<td class="text-right ' + amountClass + '">' + amountSign + formatCurrency(t.amount) + '</td>';
                html += '<td class="text-right">' + formatCurrency(t.running_balance) + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';

            // Footer
            html += '<div class="footer"><p>Buildflow ERP - Transaction Ledger Report</p></div>';

            // Print script
            html += '<script>window.onload = function() { window.print(); };<\/script>';
            html += '</body></html>';

            printWindow.document.write(html);
            printWindow.document.close();

            ERP.toast.success('PDF ready for download');
        }
    }).catch(function (error) {
        console.error('Failed to generate PDF:', error);
        ERP.toast.error('Failed to generate PDF report');
    });
}


// Quick Expense Form
document.addEventListener('DOMContentLoaded', function () {
    const quickExpenseForm = document.getElementById('quick-expense-form');
    if (quickExpenseForm) {
        quickExpenseForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = ERP.FormUtils.serialize(this);
            formData.project_id = projectId;
            formData.status = 'approved'; // Auto-approve project expenses

            // Add journal entry data if checkbox is checked
            const jeToggle = document.getElementById('expense-je-toggle');
            if (jeToggle && jeToggle.checked) {
                const debitAccount = document.getElementById('expense-je-debit')?.value;
                const creditAccount = document.getElementById('expense-je-credit')?.value;
                if (debitAccount && creditAccount) {
                    formData.journal_entry = {
                        debit_account_id: debitAccount,
                        credit_account_id: creditAccount,
                        note: document.getElementById('expense-je-note')?.value || ''
                    };
                }
            }

            try {
                await ERP.api.post('/expenses', formData);
                ERP.toast.success('Expense added');
                Modal.close('quick-expense-modal');
                this.reset();
                resetJournalEntryFields('expense');
                // Set today's date again
                this.querySelector('[name="expense_date"]').value = new Date().toISOString().split('T')[0];
                // Reload financials and ledger
                loadFinancials();
                loadLedger();
                loadProject();
                loadBudgetExpenses(); // Refresh Budget tab expenses
                loadLaborCost(); // Refresh Labor Cost tab
            } catch (error) {
                ERP.toast.error(error.message || 'Failed to add expense');
            }
        });
    }
    // Add Income Form Handler
    const incomeForm = document.getElementById('income-form');
    if (incomeForm) {
        incomeForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            // Add journal entry data if checkbox is checked
            const jeToggle = document.getElementById('income-je-toggle');
            if (jeToggle && jeToggle.checked) {
                const debitAccount = document.getElementById('income-je-debit')?.value;
                const creditAccount = document.getElementById('income-je-credit')?.value;
                if (debitAccount && creditAccount) {
                    data.journal_entry = {
                        debit_account_id: debitAccount,
                        credit_account_id: creditAccount,
                        note: document.getElementById('income-je-note')?.value || ''
                    };
                }
            }

            try {
                const response = await ERP.api.post(`/projects/${projectId}/payments`, data);
                ERP.toast.success('Payment recorded successfully');
                Modal.close('income-modal');
                this.reset();
                resetJournalEntryFields('income');
                // Set default date again
                this.querySelector('[name="payment_date"]').value = new Date().toISOString().split('T')[0];
                loadFinancials(); // Updates balance and charts
                loadLedger(); // Updates ledger table
                loadProject(); // Updates status if completed
            } catch (error) {
                ERP.toast.error(error.message || 'Failed to record payment');
            }
        });
    }

    // Add Budget Form Handler
    const budgetForm = document.getElementById('budget-form');
    if (budgetForm) {
        budgetForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);
            data.project_id = projectId;

            try {
                await ERP.api.post('/budgets', data);
                ERP.toast.success('Budget added');
                Modal.close('budget-modal');
                this.reset();
                loadBudgets();
                loadProject();
            } catch (error) {
                ERP.toast.error(error.message || 'Failed to add budget');
            }
        });
    }

    // Edit Income Form Handler
    const editIncomeForm = document.getElementById('edit-income-form');
    if (editIncomeForm) {
        editIncomeForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const id = document.getElementById('edit-income-id').value;
            const data = {
                amount: document.getElementById('edit-income-amount').value,
                payment_date: document.getElementById('edit-income-date').value,
                description: document.getElementById('edit-income-description').value
            };

            try {
                await ERP.api.put(`/payments/${id}`, data);
                ERP.toast.success('Income updated');
                Modal.close('edit-income-modal');
                loadFinancials();
                loadLedger();
            } catch (error) {
                ERP.toast.error(error.message || 'Failed to update income');
            }
        });
    }

    // Edit Expense Form Handler
    const editExpenseForm = document.getElementById('edit-expense-form');
    if (editExpenseForm) {
        editExpenseForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const id = document.getElementById('edit-expense-id').value;
            const category = document.getElementById('edit-expense-category').value;
            const data = {
                amount: document.getElementById('edit-expense-amount').value,
                expense_date: document.getElementById('edit-expense-date').value,
                description: document.getElementById('edit-expense-description').value,
                category: category,
                vendor: document.getElementById('edit-expense-vendor').value
            };

            // Include employee_id for labor expenses
            if (category === 'labor') {
                const employeeSelect = document.getElementById('edit-expense-employee');
                if (employeeSelect && employeeSelect.value) {
                    data.employee_id = employeeSelect.value;
                }
            }

            try {
                await ERP.api.put(`/expenses/${id}`, data);
                ERP.toast.success('Expense updated');
                Modal.close('edit-expense-modal');
                loadFinancials();
                loadLedger();
                loadBudgetExpenses();
                loadLaborCost();
            } catch (error) {
                ERP.toast.error(error.message || 'Failed to update expense');
            }
        });
    }
});
async function loadEmployees() {
    try {
        const response = await ERP.api.get('/employees?per_page=100');
        if (response.success) {
            employees = response.data;
            populateEmployeeSelect();
        }
    } catch (error) {
        console.error('Failed to load employees', error);
    }
}

function populateEmployeeSelect() {
    const select = document.getElementById('quick-expense-employee');
    const timelogSelect = document.getElementById('timelog-employee');

    // Sort employees alphabetically by name (A-Z)
    const sortedEmployees = [...employees].sort((a, b) => {
        const nameA = `${a.first_name} ${a.last_name}`.toLowerCase();
        const nameB = `${b.first_name} ${b.last_name}`.toLowerCase();
        return nameA.localeCompare(nameB);
    });

    // Populate expense employee select
    if (select) {
        select.innerHTML = '<option value="">Select Employee</option>';
        sortedEmployees.forEach(emp => {
            const name = emp.first_name + ' ' + emp.last_name;
            const option = document.createElement('option');
            option.value = emp.id;
            option.textContent = name + ' (' + (emp.payment_type || 'hourly') + ')';
            // Store ALL payment data in data attributes
            option.dataset.paymentType = emp.payment_type || 'hourly';
            option.dataset.hourlyRate = emp.hourly_rate || 0;
            option.dataset.dailyRate = emp.daily_rate || 0;
            option.dataset.salary = emp.salary || 0;
            option.dataset.commissionRate = emp.commission_rate || 0;
            select.appendChild(option);
        });
    }

    // Populate timelog employee select
    if (timelogSelect) {
        timelogSelect.innerHTML = '<option value="">Select Employee</option>';
        sortedEmployees.forEach(emp => {
            const name = emp.first_name + ' ' + emp.last_name;
            const option = document.createElement('option');
            option.value = emp.id;
            option.textContent = name;
            timelogSelect.appendChild(option);
        });
    }
}

// Filter employee select dropdown based on search text
// Note: Safari doesn't support display:none on <option>, so we use the hidden attribute
function filterEmployeeSelect(selectId, searchText) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const searchLower = searchText.toLowerCase().trim();
    const options = select.querySelectorAll('option');

    options.forEach((option, index) => {
        // Always keep the first placeholder option visible
        if (index === 0) {
            option.hidden = false;
            option.disabled = false;
            return;
        }

        const text = option.textContent.toLowerCase();
        if (searchLower === '' || text.includes(searchLower)) {
            option.hidden = false;
            option.disabled = false;
        } else {
            option.hidden = true;
            option.disabled = true; // Safari fallback - disabled options are grayed out
        }
    });

    // If there's search text and current selection doesn't match, clear it
    if (searchLower !== '' && select.selectedIndex > 0) {
        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption.textContent.toLowerCase().includes(searchLower)) {
            select.value = '';
        }
    }
}

function setupQuickExpenseLogic() {
    const categorySelect = document.getElementById('quick-expense-category');
    const employeeContainer = document.getElementById('quick-expense-employee-container');
    const employeeSelect = document.getElementById('quick-expense-employee');
    const laborCalcContainer = document.getElementById('labor-calc-container');

    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            if (this.value === 'labor') {
                employeeContainer.classList.remove('hidden');
            } else {
                employeeContainer.classList.add('hidden');
                laborCalcContainer.classList.add('hidden');
                employeeSelect.value = '';
                resetLaborFields();
            }
        });
    }

    if (employeeSelect) {
        employeeSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) {
                laborCalcContainer.classList.add('hidden');
                resetLaborFields();
                return;
            }

            const paymentType = selectedOption.dataset.paymentType;
            const hourlyRate = parseFloat(selectedOption.dataset.hourlyRate) || 0;
            const dailyRate = parseFloat(selectedOption.dataset.dailyRate) || 0;
            const salary = parseFloat(selectedOption.dataset.salary) || 0;
            const commissionRate = parseFloat(selectedOption.dataset.commissionRate) || 0;

            // Show the calc container
            laborCalcContainer.classList.remove('hidden');

            // Update badge
            const badge = document.getElementById('emp-payment-type-badge');
            const rateDisplay = document.getElementById('emp-rate-display');
            badge.textContent = paymentType.charAt(0).toUpperCase() + paymentType.slice(1);
            badge.className = 'badge ' + getPaymentTypeBadgeClass(paymentType);

            // Hide all field groups first
            document.getElementById('labor-hourly-fields').classList.add('hidden');
            document.getElementById('labor-daily-fields').classList.add('hidden');
            document.getElementById('labor-commission-fields').classList.add('hidden');
            document.getElementById('labor-fixed-fields').classList.add('hidden');

            // Show appropriate fields based on payment type
            const amountInput = document.getElementById('quick-expense-amount');

            switch (paymentType) {
                case 'hourly':
                    document.getElementById('labor-hourly-fields').classList.remove('hidden');
                    document.getElementById('labor-hourly-rate').value = '$' + hourlyRate.toFixed(2);
                    rateDisplay.textContent = '$' + hourlyRate.toFixed(2) + '/hour';
                    break;

                case 'daily':
                    document.getElementById('labor-daily-fields').classList.remove('hidden');
                    document.getElementById('labor-daily-rate').value = '$' + dailyRate.toFixed(2);
                    rateDisplay.textContent = '$' + dailyRate.toFixed(2) + '/day';
                    break;

                case 'salary':
                    document.getElementById('labor-fixed-fields').classList.remove('hidden');
                    rateDisplay.textContent = 'Salary: $' + salary.toFixed(2);
                    amountInput.value = salary.toFixed(2);
                    break;

                case 'project':
                    document.getElementById('labor-fixed-fields').classList.remove('hidden');
                    rateDisplay.textContent = 'Per Project Rate';
                    break;

                case 'commission':
                    document.getElementById('labor-commission-fields').classList.remove('hidden');
                    document.getElementById('labor-commission-rate').value = commissionRate + '%';
                    rateDisplay.textContent = commissionRate + '% commission';
                    break;

                default:
                    rateDisplay.textContent = '-';
            }
        });
    }
}

function getPaymentTypeBadgeClass(type) {
    const classes = {
        'hourly': 'badge-primary',
        'daily': 'badge-info',
        'salary': 'badge-success',
        'project': 'badge-warning',
        'commission': 'badge-secondary'
    };
    return classes[type] || 'badge-secondary';
}

function resetLaborFields() {
    document.getElementById('labor-hours')?.value && (document.getElementById('labor-hours').value = '');
    document.getElementById('labor-days')?.value && (document.getElementById('labor-days').value = '');
    document.getElementById('labor-base-amount')?.value && (document.getElementById('labor-base-amount').value = '');
    document.getElementById('quick-expense-amount').value = '';
}

function calculateLaborAmount() {
    const employeeSelect = document.getElementById('quick-expense-employee');
    const selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
    if (!selectedOption.value) {
        ERP.toast.error('Please select an employee first');
        return;
    }

    const paymentType = selectedOption.dataset.paymentType;
    const hourlyRate = parseFloat(selectedOption.dataset.hourlyRate) || 0;
    const dailyRate = parseFloat(selectedOption.dataset.dailyRate) || 0;
    const salary = parseFloat(selectedOption.dataset.salary) || 0;
    const commissionRate = parseFloat(selectedOption.dataset.commissionRate) || 0;

    const amountInput = document.getElementById('quick-expense-amount');
    let amount = 0;

    switch (paymentType) {
        case 'hourly':
            const hours = parseFloat(document.getElementById('labor-hours').value) || 0;
            if (hours <= 0) {
                ERP.toast.error('Please enter hours worked');
                return;
            }
            amount = hours * hourlyRate;
            break;
        case 'daily':
            const days = parseFloat(document.getElementById('labor-days').value) || 0;
            if (days <= 0) {
                ERP.toast.error('Please enter days worked');
                return;
            }
            amount = days * dailyRate;
            break;
        case 'salary':
            amount = salary;
            break;
        case 'project':
            ERP.toast.info('Enter the project amount manually');
            return;
        case 'commission':
            const baseAmount = parseFloat(document.getElementById('labor-base-amount').value) || 0;
            if (baseAmount <= 0) {
                ERP.toast.error('Please enter base amount for commission');
                return;
            }
            amount = baseAmount * (commissionRate / 100);
            break;
    }
    amountInput.value = amount.toFixed(2);
    ERP.toast.success('Amount calculated: $' + amount.toFixed(2));
}

// ==========================================
// Edit Expense Labor Functions
// ==========================================
function setupEditExpenseLogic() {
    const categorySelect = document.getElementById('edit-expense-category');
    const employeeContainer = document.getElementById('edit-expense-employee-container');
    const employeeSelect = document.getElementById('edit-expense-employee');
    const laborCalcContainer = document.getElementById('edit-labor-calc-container');

    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            if (this.value === 'labor') {
                employeeContainer.classList.remove('hidden');
                populateEditEmployeeSelect();
            } else {
                employeeContainer.classList.add('hidden');
                laborCalcContainer.classList.add('hidden');
                employeeSelect.value = '';
                resetEditLaborFields();
            }
        });
    }

    if (employeeSelect) {
        employeeSelect.addEventListener('change', function () {
            handleEditEmployeeChange(this);
        });
    }
}

function populateEditEmployeeSelect() {
    const select = document.getElementById('edit-expense-employee');
    if (!select) return;

    select.innerHTML = '<option value="">Select Employee</option>';
    if (employees && employees.length > 0) {
        // Sort employees alphabetically by name (A-Z)
        const sortedEmployees = [...employees].sort((a, b) => {
            const nameA = `${a.first_name} ${a.last_name}`.toLowerCase();
            const nameB = `${b.first_name} ${b.last_name}`.toLowerCase();
            return nameA.localeCompare(nameB);
        });

        sortedEmployees.forEach(emp => {
            const option = document.createElement('option');
            option.value = emp.id;
            option.textContent = `${emp.first_name} ${emp.last_name} - ${emp.job_title || 'Employee'}`;
            option.dataset.paymentType = emp.payment_type || 'hourly';
            option.dataset.hourlyRate = emp.hourly_rate || 0;
            option.dataset.dailyRate = emp.daily_rate || 0;
            option.dataset.salary = emp.salary || 0;
            option.dataset.commissionRate = emp.commission_rate || 0;
            select.appendChild(option);
        });
    }
}

function handleEditEmployeeChange(selectElement) {
    const laborCalcContainer = document.getElementById('edit-labor-calc-container');
    const selectedOption = selectElement.options[selectElement.selectedIndex];

    if (!selectedOption.value) {
        laborCalcContainer.classList.add('hidden');
        resetEditLaborFields();
        return;
    }

    const paymentType = selectedOption.dataset.paymentType;
    const hourlyRate = parseFloat(selectedOption.dataset.hourlyRate) || 0;
    const dailyRate = parseFloat(selectedOption.dataset.dailyRate) || 0;
    const salary = parseFloat(selectedOption.dataset.salary) || 0;
    const commissionRate = parseFloat(selectedOption.dataset.commissionRate) || 0;

    // Show the calc container
    laborCalcContainer.classList.remove('hidden');

    // Update badge
    const badge = document.getElementById('edit-emp-payment-type-badge');
    const rateDisplay = document.getElementById('edit-emp-rate-display');
    badge.textContent = paymentType.charAt(0).toUpperCase() + paymentType.slice(1);
    badge.className = 'badge ' + getPaymentTypeBadgeClass(paymentType);

    // Hide all field groups first
    document.getElementById('edit-labor-hourly-fields').classList.add('hidden');
    document.getElementById('edit-labor-daily-fields').classList.add('hidden');
    document.getElementById('edit-labor-commission-fields').classList.add('hidden');
    document.getElementById('edit-labor-fixed-fields').classList.add('hidden');

    // Show appropriate fields based on payment type
    const amountInput = document.getElementById('edit-expense-amount');

    switch (paymentType) {
        case 'hourly':
            document.getElementById('edit-labor-hourly-fields').classList.remove('hidden');
            document.getElementById('edit-labor-hourly-rate').value = '$' + hourlyRate.toFixed(2);
            rateDisplay.textContent = '$' + hourlyRate.toFixed(2) + '/hour';
            break;

        case 'daily':
            document.getElementById('edit-labor-daily-fields').classList.remove('hidden');
            document.getElementById('edit-labor-daily-rate').value = '$' + dailyRate.toFixed(2);
            rateDisplay.textContent = '$' + dailyRate.toFixed(2) + '/day';
            break;

        case 'salary':
            document.getElementById('edit-labor-fixed-fields').classList.remove('hidden');
            rateDisplay.textContent = 'Salary: $' + salary.toFixed(2);
            amountInput.value = salary.toFixed(2);
            break;

        case 'project':
            document.getElementById('edit-labor-fixed-fields').classList.remove('hidden');
            rateDisplay.textContent = 'Per Project Rate';
            break;

        case 'commission':
            document.getElementById('edit-labor-commission-fields').classList.remove('hidden');
            document.getElementById('edit-labor-commission-rate').value = commissionRate + '%';
            rateDisplay.textContent = commissionRate + '% commission';
            break;

        default:
            rateDisplay.textContent = '-';
    }
}

function resetEditLaborFields() {
    const hours = document.getElementById('edit-labor-hours');
    const days = document.getElementById('edit-labor-days');
    const baseAmount = document.getElementById('edit-labor-base-amount');

    if (hours) hours.value = '';
    if (days) days.value = '';
    if (baseAmount) baseAmount.value = '';
}

function calculateEditLaborAmount() {
    const employeeSelect = document.getElementById('edit-expense-employee');
    const selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
    if (!selectedOption.value) {
        ERP.toast.error('Please select an employee first');
        return;
    }

    const paymentType = selectedOption.dataset.paymentType;
    const hourlyRate = parseFloat(selectedOption.dataset.hourlyRate) || 0;
    const dailyRate = parseFloat(selectedOption.dataset.dailyRate) || 0;
    const salary = parseFloat(selectedOption.dataset.salary) || 0;
    const commissionRate = parseFloat(selectedOption.dataset.commissionRate) || 0;

    const amountInput = document.getElementById('edit-expense-amount');
    let amount = 0;

    switch (paymentType) {
        case 'hourly':
            const hours = parseFloat(document.getElementById('edit-labor-hours').value) || 0;
            if (hours <= 0) {
                ERP.toast.error('Please enter hours worked');
                return;
            }
            amount = hours * hourlyRate;
            break;
        case 'daily':
            const days = parseFloat(document.getElementById('edit-labor-days').value) || 0;
            if (days <= 0) {
                ERP.toast.error('Please enter days worked');
                return;
            }
            amount = days * dailyRate;
            break;
        case 'salary':
            amount = salary;
            break;
        case 'project':
            ERP.toast.info('Enter the project amount manually');
            return;
        case 'commission':
            const baseAmount = parseFloat(document.getElementById('edit-labor-base-amount').value) || 0;
            if (baseAmount <= 0) {
                ERP.toast.error('Please enter base amount for commission');
                return;
            }
            amount = baseAmount * (commissionRate / 100);
            break;
    }
    amountInput.value = amount.toFixed(2);
    ERP.toast.success('Amount calculated: $' + amount.toFixed(2));
}

// Initialize edit expense logic
document.addEventListener('DOMContentLoaded', function () {
    setupEditExpenseLogic();
});

function updateProjectLockState(status) {
    const isCompleted = status === 'completed' || status === 'cancelled';
    const buttons = document.querySelectorAll('button[onclick*="Modal.open"]');

    buttons.forEach(btn => {
        if (btn.innerText.includes('Add') || btn.innerText.includes('Edit')) {
            btn.disabled = isCompleted;
            if (isCompleted) {
                btn.title = "Project is closed/completed";
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    });

    const header = document.querySelector('.page-header');
    if (isCompleted) {
        if (header && !document.getElementById('project-closed-banner')) {
            const banner = document.createElement('div');
            banner.id = 'project-closed-banner';
            banner.className = 'bg-success text-white px-4 py-2 text-center mb-4 rounded font-bold';
            banner.innerText = status === 'completed' ? '✅ This project is PAID IN FULL and CLOSED.' : '🚫 This project is CANCELLED.';
            header.after(banner);
        }
    } else {
        const banner = document.getElementById('project-closed-banner');
        if (banner) banner.remove();
    }
}

// Initialize Income Date
const incomeDate = document.querySelector('#income-form [name="payment_date"]');
if (incomeDate) incomeDate.value = new Date().toISOString().split('T')[0];

// =====================================================
// JOURNAL ENTRY INTEGRATION FUNCTIONS
// =====================================================

let chartOfAccounts = null;

// Toggle journal entry fields visibility
function toggleJournalEntry(type) {
    const fields = document.getElementById(`${type}-je-fields`);
    const toggle = document.getElementById(`${type}-je-toggle`);

    if (toggle && toggle.checked) {
        fields.classList.remove('hidden');
        // Load accounts if not already loaded
        if (!chartOfAccounts) {
            loadChartOfAccounts(type);
        } else {
            populateAccountSelects(type);
        }
    } else {
        fields.classList.add('hidden');
    }
}

// Load chart of accounts from API
async function loadChartOfAccounts(type) {
    try {
        const response = await ERP.api.get('/accounts');
        if (response.success) {
            chartOfAccounts = response.data || [];
            populateAccountSelects(type);
        }
    } catch (error) {
        console.error('Failed to load accounts:', error);
        ERP.toast.error('Failed to load chart of accounts');
    }
}

// Populate account select dropdowns
function populateAccountSelects(type) {
    const debitSelect = document.getElementById(`${type}-je-debit`);
    const creditSelect = document.getElementById(`${type}-je-credit`);

    if (!debitSelect || !creditSelect || !chartOfAccounts) return;

    // Clear existing options (keep first placeholder)
    debitSelect.innerHTML = '<option value="">Select Account</option>';
    creditSelect.innerHTML = '<option value="">Select Account</option>';

    // Group accounts by type for better UX
    const accountsByType = {};
    chartOfAccounts.forEach(account => {
        const type = account.type || 'Other';
        if (!accountsByType[type]) {
            accountsByType[type] = [];
        }
        accountsByType[type].push(account);
    });

    // Add grouped options
    Object.keys(accountsByType).sort().forEach(accountType => {
        const optgroup = document.createElement('optgroup');
        optgroup.label = accountType.charAt(0).toUpperCase() + accountType.slice(1);

        accountsByType[accountType]
            .sort((a, b) => (a.code || '').localeCompare(b.code || ''))
            .forEach(account => {
                const option = document.createElement('option');
                option.value = account.id;
                option.textContent = `${account.code || ''} - ${account.name}`;
                optgroup.appendChild(option);
            });

        debitSelect.appendChild(optgroup.cloneNode(true));
        creditSelect.appendChild(optgroup);
    });
}

// Reset journal entry fields after form submission
function resetJournalEntryFields(type) {
    const toggle = document.getElementById(`${type}-je-toggle`);
    const fields = document.getElementById(`${type}-je-fields`);
    const debitSelect = document.getElementById(`${type}-je-debit`);
    const creditSelect = document.getElementById(`${type}-je-credit`);
    const noteField = document.getElementById(`${type}-je-note`);

    if (toggle) toggle.checked = false;
    if (fields) fields.classList.add('hidden');
    if (debitSelect) debitSelect.value = '';
    if (creditSelect) creditSelect.value = '';
    if (noteField) noteField.value = '';
}