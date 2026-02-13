<?php
$title = 'Expenses';
$page = 'expenses';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Expenses</h1>
        <p class="text-muted text-sm">Track and manage project expenses</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('expense-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Add Expense
    </button>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-4 mb-6">
    <div class="card stat-card">
        <div class="stat-value" id="total-expenses">$0</div>
        <div class="stat-label">Total Expenses</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-success" id="approved-expenses">$0</div>
        <div class="stat-label">Approved</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-warning" id="pending-expenses">$0</div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-error" id="rejected-expenses">$0</div>
        <div class="stat-label">Rejected</div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4 items-center">
        <div class="flex-1">
            <input type="text" class="form-input" id="search-input" placeholder="Search expenses...">
        </div>
        <select class="form-select" id="status-filter" style="width: 130px;">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
        <select class="form-select" id="category-filter" style="width: 150px;">
            <option value="">All Categories</option>
            <option value="materials">Materials</option>
            <option value="labor">Labor</option>
            <option value="subcontractor">Subcontractor</option>
            <option value="equipment">Equipment</option>
            <option value="fuel">Fuel</option>
            <option value="permits">Permits</option>
            <option value="utilities">Utilities</option>
            <option value="meals">Meals</option>
            <option value="other">Other</option>
        </select>
        <select class="form-select" id="project-filter" style="width: 180px;">
            <option value="">All Projects</option>
        </select>
        <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
    </div>
</div>

<!-- Expenses Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="expenses-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Project</th>
                    <th>Vendor</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="8" class="text-center text-muted">Loading expenses...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="flex justify-between items-center mt-6" id="pagination">
    <div class="text-sm text-muted" id="pagination-info">Showing 0 of 0 expenses</div>
    <div class="flex gap-2">
        <button class="btn btn-secondary btn-sm" id="prev-btn" disabled>Previous</button>
        <button class="btn btn-secondary btn-sm" id="next-btn" disabled>Next</button>
    </div>
</div>

<!-- Add/Edit Expense Modal -->
<div class="modal" id="expense-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="expense-modal-title">Add Expense</h3>
        <button class="modal-close" onclick="Modal.close('expense-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="expense-form">
        <input type="hidden" name="id" id="expense-id">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Amount</label>
                    <input type="number" step="0.01" class="form-input" name="amount" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Category</label>
                    <select class="form-select" name="category" required>
                        <option value="materials">Materials</option>
                        <option value="labor">Labor</option>
                        <option value="subcontractor">Subcontractor</option>
                        <option value="equipment">Equipment Rental</option>
                        <option value="fuel">Fuel & Transportation</option>
                        <option value="permits">Permits & Fees</option>
                        <option value="utilities">Utilities</option>
                        <option value="meals">Meals & Per Diem</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label required">Description</label>
                <input type="text" class="form-input" name="description" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Project</label>
                    <select class="form-select" name="project_id" id="project-select">
                        <option value="">No Project</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label required">Date</label>
                    <input type="date" class="form-input" name="expense_date" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Vendor</label>
                    <input type="text" class="form-input" name="vendor" placeholder="Supplier name">
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" name="payment_method">
                        <option value="credit_card">Credit Card</option>
                        <option value="check">Check</option>
                        <option value="cash">Cash</option>
                        <option value="transfer">Bank Transfer</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Reference #</label>
                <input type="text" class="form-input" name="reference_number" placeholder="Receipt/Invoice number">
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-input" name="notes" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('expense-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Expense</button>
        </div>
    </form>
</div>

<script>
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};
    let editingId = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadExpenses();
        loadProjects();
        loadSummary();

        // Form submission
        document.getElementById('expense-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                if (editingId) {
                    await ERP.api.put('/expenses/' + editingId, data);
                    ERP.toast.success('Expense updated');
                } else {
                    await ERP.api.post('/expenses', data);
                    ERP.toast.success('Expense added');
                }
                Modal.close('expense-modal');
                this.reset();
                editingId = null;
                document.querySelector('[name="expense_date"]').value = new Date().toISOString().split('T')[0];
                loadExpenses();
                loadSummary();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        // Filter events
        document.getElementById('search-input').addEventListener('input', debounce(applyFilters, 300));
        document.getElementById('status-filter').addEventListener('change', applyFilters);
        document.getElementById('category-filter').addEventListener('change', applyFilters);
        document.getElementById('project-filter').addEventListener('change', applyFilters);

        // Pagination
        document.getElementById('prev-btn').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                loadExpenses();
            }
        });
        document.getElementById('next-btn').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                loadExpenses();
            }
        });
    });

    async function loadExpenses() {
        const params = new URLSearchParams({
            page: currentPage,
            per_page: 15,
            ...currentFilters
        });

        try {
            const response = await ERP.api.get('/expenses?' + params);
            if (response.success) {
                renderExpenses(response.data);
                totalPages = response.meta.total_pages || 1;
                updatePagination(response.meta);
            }
        } catch (error) {
            ERP.toast.error('Failed to load expenses');
        }
    }

    async function loadProjects() {
        try {
            const response = await ERP.api.get('/projects?per_page=100');
            if (response.success) {
                const options = response.data.map(p =>
                    `<option value="${p.id}">${p.name}</option>`
                ).join('');

                document.getElementById('project-select').innerHTML =
                    '<option value="">No Project</option>' + options;
                document.getElementById('project-filter').innerHTML =
                    '<option value="">All Projects</option>' + options;
            }
        } catch (error) {
            console.error('Failed to load projects:', error);
        }
    }

    async function loadSummary() {
        try {
            const response = await ERP.api.get('/expenses/summary');
            if (response.success) {
                document.getElementById('total-expenses').textContent = formatCurrency(response.data.total || 0);
                document.getElementById('approved-expenses').textContent = formatCurrency(response.data.by_status?.approved || 0);
                document.getElementById('pending-expenses').textContent = formatCurrency(response.data.by_status?.pending || 0);
                document.getElementById('rejected-expenses').textContent = formatCurrency(response.data.by_status?.rejected || 0);
            }
        } catch (error) {
            console.error('Failed to load summary:', error);
        }
    }

    function renderExpenses(expenses) {
        const tbody = document.querySelector('#expenses-table tbody');

        if (!expenses || expenses.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No expenses found</td></tr>';
            return;
        }

        tbody.innerHTML = expenses.map(exp => `
            <tr>
                <td>${formatDate(exp.expense_date)}</td>
                <td>
                    <div class="font-medium">${exp.description}</div>
                    ${exp.reference_number ? `<div class="text-xs text-muted">Ref: ${exp.reference_number}</div>` : ''}
                </td>
                <td>
                    <span class="badge badge-${getCategoryColor(exp.category)}">${formatCategory(exp.category)}</span>
                </td>
                <td>${exp.project_name || '-'}</td>
                <td>${exp.vendor || '-'}</td>
                <td class="text-right font-medium">${formatCurrency(exp.amount)}</td>
                <td>
                    <span class="badge badge-${getStatusColor(exp.status)}">${exp.status}</span>
                </td>
                <td>
                    <div class="flex gap-1">
                        ${exp.status === 'pending' ? `
                            <button class="btn btn-icon btn-sm btn-success" onclick="approveExpense(${exp.id})" title="Approve">✓</button>
                            <button class="btn btn-icon btn-sm btn-error" onclick="rejectExpense(${exp.id})" title="Reject">✗</button>
                        ` : ''}
                        <button class="btn btn-icon btn-sm btn-secondary" onclick="editExpense(${exp.id})" title="Edit">✎</button>
                        <button class="btn btn-icon btn-sm btn-secondary" onclick="deleteExpense(${exp.id})" title="Delete">🗑</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function updatePagination(meta) {
        document.getElementById('pagination-info').textContent =
            `Showing ${meta.from || 0}-${meta.to || 0} of ${meta.total || 0} expenses`;

        document.getElementById('prev-btn').disabled = currentPage <= 1;
        document.getElementById('next-btn').disabled = currentPage >= totalPages;
    }

    function applyFilters() {
        currentFilters = {};
        const search = document.getElementById('search-input').value;
        const status = document.getElementById('status-filter').value;
        const category = document.getElementById('category-filter').value;
        const project = document.getElementById('project-filter').value;

        if (search) currentFilters.search = search;
        if (status) currentFilters.status = status;
        if (category) currentFilters.category = category;
        if (project) currentFilters.project_id = project;

        currentPage = 1;
        loadExpenses();
    }

    function clearFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('status-filter').value = '';
        document.getElementById('category-filter').value = '';
        document.getElementById('project-filter').value = '';
        currentFilters = {};
        currentPage = 1;
        loadExpenses();
    }

    async function editExpense(id) {
        try {
            const response = await ERP.api.get('/expenses/' + id);
            if (response.success) {
                const exp = response.data;
                editingId = id;
                document.getElementById('expense-modal-title').textContent = 'Edit Expense';
                document.getElementById('expense-id').value = id;

                const form = document.getElementById('expense-form');
                form.querySelector('[name="amount"]').value = exp.amount;
                form.querySelector('[name="category"]').value = exp.category;
                form.querySelector('[name="description"]').value = exp.description;
                form.querySelector('[name="project_id"]').value = exp.project_id || '';
                form.querySelector('[name="expense_date"]').value = exp.expense_date;
                form.querySelector('[name="vendor"]').value = exp.vendor || '';
                form.querySelector('[name="payment_method"]').value = exp.payment_method || 'credit_card';
                form.querySelector('[name="reference_number"]').value = exp.reference_number || '';
                form.querySelector('[name="notes"]').value = exp.notes || '';

                Modal.open('expense-modal');
            }
        } catch (error) {
            ERP.toast.error('Failed to load expense');
        }
    }

    async function deleteExpense(id) {
        if (!confirm('Delete this expense?')) return;

        try {
            await ERP.api.delete('/expenses/' + id);
            ERP.toast.success('Expense deleted');
            loadExpenses();
            loadSummary();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    async function approveExpense(id) {
        try {
            await ERP.api.post('/expenses/' + id + '/approve', {});
            ERP.toast.success('Expense approved');
            loadExpenses();
            loadSummary();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    async function rejectExpense(id) {
        const reason = prompt('Rejection reason (optional):');
        try {
            await ERP.api.post('/expenses/' + id + '/reject', { reason });
            ERP.toast.success('Expense rejected');
            loadExpenses();
            loadSummary();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    function getCategoryColor(cat) {
        const colors = {
            materials: 'primary',
            labor: 'secondary',
            subcontractor: 'warning',
            equipment: 'info',
            fuel: 'secondary',
            permits: 'secondary',
            utilities: 'secondary',
            meals: 'secondary',
            other: 'secondary'
        };
        return colors[cat] || 'secondary';
    }

    function getStatusColor(status) {
        return { pending: 'warning', approved: 'success', rejected: 'error' }[status] || 'secondary';
    }

    function formatCategory(cat) {
        return cat ? cat.charAt(0).toUpperCase() + cat.slice(1) : 'Other';
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(amount);
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function debounce(fn, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }
</script>

<style>
    .stat-card {
        text-align: center;
        padding: var(--space-4);
    }

    .stat-card .stat-value {
        font-size: var(--text-2xl);
        font-weight: 700;
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

    .btn-icon {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-success {
        background: var(--success-500);
        color: white;
    }

    .btn-error {
        background: var(--error-500);
        color: white;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>