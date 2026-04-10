<?php
$title = 'Project Details';
$page = 'projects';

// Get project ID from URL - try local variable first (from router closure), then global params
$projectId = $id ?? $GLOBALS['params']['id'] ?? $params['id'] ?? null;

ob_start();
?>

<div class="mb-6">
    <a href="/projects" class="text-muted text-sm flex items-center gap-1 mb-2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6" />
        </svg>
        Back to Projects
    </a>

    <!-- Project Header - Responsive Layout -->
    <div class="project-header">
        <div class="project-header-info">
            <h1 class="project-title" id="project-name">Loading...</h1>
            <p class="text-muted text-sm" id="project-code"></p>
        </div>
        <div class="project-header-actions">
            <button class="btn btn-outline btn-sm" id="edit-project-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Edit
            </button>
            <button class="btn btn-error btn-sm" id="delete-project-btn" style="display: none;"
                onclick="deleteProject()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                </svg>
                Delete
            </button>
            <select class="form-select form-select-sm" id="status-select" onchange="updateStatus()">
                <option value="planning">Planning</option>
                <option value="in_progress">In Progress</option>
                <option value="on_hold">On Hold</option>
                <option value="completed">Completed</option>
            </select>
        </div>
    </div>
</div>

<!-- Project Info Cards -->
<div class="grid grid-cols-4 mb-6">
    <div class="card stat-card">
        <div class="stat-value" id="stat-progress">0%</div>
        <div class="stat-label">Progress</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value" id="stat-budget">$0</div>
        <div class="stat-label">Budget</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value" id="stat-spent">$0</div>
        <div class="stat-label">Spent</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value" id="stat-hours">0h</div>
        <div class="stat-label">Hours Logged</div>
    </div>
</div>

<!-- Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-2" style="white-space: nowrap; -webkit-overflow-scrolling: touch;">
    <button class="btn btn-secondary active" id="tab-overview" onclick="switchTab('overview')">Overview</button>
    <button class="btn btn-secondary" id="tab-financials" onclick="switchTab('financials')">Financials</button>
    <button class="btn btn-secondary" id="tab-tasks" onclick="switchTab('tasks')">Tasks</button>
    <button class="btn btn-secondary" id="tab-budget" onclick="switchTab('budget')">Budget</button>
    <button class="btn btn-secondary" id="tab-labor" onclick="switchTab('labor')">Labor Cost</button>
    <button class="btn btn-secondary" id="tab-time" onclick="switchTab('time')">Time Logs</button>
    <button class="btn btn-secondary" id="tab-documents" onclick="switchTab('documents')">Documents</button>
</div>

<!-- Overview Tab -->
<div id="overview-content">
    <div class="grid grid-cols-3 gap-6">
        <div class="card" style="grid-column: span 2;">
            <div class="card-header">
                <h3 class="card-title">Project Details</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-muted text-sm">Client</label>
                        <p class="font-medium" id="detail-client">-</p>
                    </div>
                    <div>
                        <label class="text-muted text-sm">Priority</label>
                        <p id="detail-priority">-</p>
                    </div>
                    <div>
                        <label class="text-muted text-sm">Start Date</label>
                        <p id="detail-start">-</p>
                    </div>
                    <div>
                        <label class="text-muted text-sm">End Date</label>
                        <p id="detail-end">-</p>
                    </div>
                    <div>
                        <label class="text-muted text-sm">Address</label>
                        <p id="detail-address">-</p>
                    </div>
                    <div>
                        <label class="text-muted text-sm">Manager</label>
                        <p id="detail-manager">-</p>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="text-muted text-sm">Description</label>
                    <p id="detail-description">-</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Budget Overview</h3>
            </div>
            <div class="card-body">
                <div style="height: 200px; position: relative;">
                    <canvas id="budget-chart"></canvas>
                </div>
                <div class="mt-4" id="budget-summary"></div>
            </div>
        </div>
    </div>
</div>

<!-- Financials Tab -->
<div id="financials-content" style="display: none;">
    <!-- KPI Cards -->
    <div class="grid grid-cols-5 mb-6">
        <div class="card stat-card">
            <div class="stat-value" id="fin-contract">$0</div>
            <div class="stat-label">Contract Value</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value text-success" id="fin-income">$0</div>
            <div class="stat-label">Total Income</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value text-error" id="fin-expenses">$0</div>
            <div class="stat-label">Total Expenses</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="fin-profit">$0</div>
            <div class="stat-label">Net Profit</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="fin-margin">0%</div>
            <div class="stat-label">Profit Margin</div>
        </div>
    </div>

    <!-- Second Row: Health + Burn Rate -->
    <div class="grid grid-cols-4 mb-6">
        <div class="card stat-card">
            <div class="stat-value" id="fin-balance">$0</div>
            <div class="stat-label">Balance Due</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="fin-labor">$0</div>
            <div class="stat-label">Labor Cost</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="fin-burn-rate">$0/day</div>
            <div class="stat-label">Burn Rate</div>
        </div>
        <div class="card stat-card">
            <div id="fin-health-indicator" class="stat-value">●</div>
            <div class="stat-label">Health Status</div>
        </div>
    </div>

    <!-- Charts and Budget Row -->
    <div class="grid grid-cols-2 gap-6 mb-6">
        <!-- Expense Breakdown Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Expense Breakdown</h3>
            </div>
            <div class="card-body">
                <div style="height: 250px; position: relative;">
                    <canvas id="expense-pie-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Budget vs Actual -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Budget vs Actual</h3>
            </div>
            <div class="table-container">
                <table class="table" id="budget-actual-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Budget</th>
                            <th>Spent</th>
                            <th>Variance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Project Ledger -->
    <div class="card">
        <div class="card-header flex flex-col gap-3">
            <div class="flex justify-between items-center">
                <h3 class="card-title">Transaction Ledger</h3>
                <div class="flex gap-2">
                    <button class="btn btn-outline btn-sm" onclick="exportLedger()">Export CSV</button>
                    <button class="btn btn-outline btn-sm" onclick="exportLedgerToPDF()">Export PDF</button>
                    <button class="btn btn-outline btn-sm text-success border-success hover:bg-success hover:text-white"
                        onclick="Modal.open('income-modal')">+ Add Income
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="Modal.open('quick-expense-modal')">
                        + Add Expense
                    </button>
                </div>
            </div>
            <!-- Filters Row -->
            <div class="flex gap-3 flex-wrap items-center">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted">Payment:</label>
                    <select class="form-select form-select-sm" id="ledger-payment-filter" onchange="loadLedger()"
                        style="width: 140px;">
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="transfer">Bank Transfer</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted">Category:</label>
                    <select class="form-select form-select-sm" id="ledger-category-filter" onchange="loadLedger()"
                        style="width: 150px;">
                        <option value="">All Categories</option>
                        <option value="materials">Materials</option>
                        <option value="labor">Labor</option>
                        <option value="subcontractor">Subcontractor</option>
                        <option value="equipment">Equipment Rental</option>
                        <option value="fuel">Fuel & Transportation</option>
                        <option value="permits">Permits & Fees</option>
                        <option value="utilities">Utilities</option>
                        <option value="meals">Meals & Per Diem</option>
                        <option value="other">Other</option>
                        <option value="Payment">Payment (Income)</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted">From:</label>
                    <input type="date" class="form-input form-input-sm" id="ledger-start-date" onchange="loadLedger()"
                        style="width: 140px;">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted">To:</label>
                    <input type="date" class="form-input form-input-sm" id="ledger-end-date" onchange="loadLedger()"
                        style="width: 140px;">
                </div>
                <button class="btn btn-secondary btn-sm" onclick="clearLedgerFilters()">Clear Filters</button>
            </div>
        </div>
        <div class="table-container">
            <table class="table" id="ledger-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="date" onclick="sortLedger('date')" style="cursor: pointer;">
                            Date <span class="sort-icon" id="sort-icon-date"></span>
                        </th>
                        <th class="sortable" data-sort="payment_method" onclick="sortLedger('payment_method')"
                            style="cursor: pointer;">
                            Payment Method <span class="sort-icon" id="sort-icon-payment_method"></span>
                        </th>
                        <th class="sortable" data-sort="description" onclick="sortLedger('description')"
                            style="cursor: pointer;">
                            Description <span class="sort-icon" id="sort-icon-description"></span>
                        </th>
                        <th class="sortable" data-sort="category" onclick="sortLedger('category')"
                            style="cursor: pointer;">
                            Category <span class="sort-icon" id="sort-icon-category"></span>
                        </th>
                        <th class="sortable" data-sort="vendor" onclick="sortLedger('vendor')" style="cursor: pointer;">
                            Vendor/Client <span class="sort-icon" id="sort-icon-vendor"></span>
                        </th>
                        <th class="sortable text-right" data-sort="amount" onclick="sortLedger('amount')"
                            style="cursor: pointer;">
                            Amount <span class="sort-icon" id="sort-icon-amount"></span>
                        </th>
                        <th class="text-right">Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Loading transactions...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination Controls -->
        <div class="flex justify-between items-center mt-4" id="ledger-pagination">
            <div class="text-sm text-muted" id="ledger-page-info">Showing 0 of 0 transactions</div>
            <div class="flex gap-2">
                <button class="btn btn-secondary btn-sm" id="ledger-prev-btn" onclick="ledgerPrevPage()"
                    disabled>Previous</button>
                <span class="flex items-center px-3 text-sm" id="ledger-page-display">Page 1 of 1</span>
                <button class="btn btn-secondary btn-sm" id="ledger-next-btn" onclick="ledgerNextPage()"
                    disabled>Next</button>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm">Per page:</label>
                <select class="form-select form-select-sm" id="ledger-per-page"
                    onchange="ledgerChangePerPage(this.value)" style="width: auto;">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Quick Expense Modal -->
<div class="modal" id="quick-expense-modal">
    <div class="modal-header">
        <h3 class="modal-title">Add Project Expense</h3>
        <button class="modal-close" onclick="Modal.close('quick-expense-modal')">×</button>
    </div>
    <form id="quick-expense-form">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Category *</label>
                <select class="form-select" name="category" required id="quick-expense-category">
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

            <div class="form-group hidden" id="quick-expense-employee-container">
                <label class="form-label">Employee</label>
                <input type="text" class="form-input mb-2" id="quick-expense-employee-search"
                    placeholder="Type to search employees..."
                    oninput="filterEmployeeSelect('quick-expense-employee', this.value)">
                <select class="form-select" name="employee_id" id="quick-expense-employee">
                    <option value="">Select Employee</option>
                </select>
                <p class="form-help">Employees are sorted A-Z. Type to filter the list.</p>
            </div>

            <!-- Dynamic Payment Type Fields -->
            <div id="labor-calc-container" class="hidden"
                style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <div class="mb-3">
                    <span class="badge" id="emp-payment-type-badge">-</span>
                    <span class="text-muted ml-2" id="emp-rate-display">-</span>
                </div>

                <!-- Hourly: Hours input -->
                <div id="labor-hourly-fields" class="hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Hours Worked *</label>
                            <input type="number" step="0.5" class="form-input" id="labor-hours" min="0"
                                placeholder="e.g. 8">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rate/Hour</label>
                            <input type="text" class="form-input" id="labor-hourly-rate" readonly>
                        </div>
                    </div>
                </div>

                <!-- Daily: Days input -->
                <div id="labor-daily-fields" class="hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Days Worked *</label>
                            <input type="number" step="0.5" class="form-input" id="labor-days" min="0"
                                placeholder="e.g. 5">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rate/Day</label>
                            <input type="text" class="form-input" id="labor-daily-rate" readonly>
                        </div>
                    </div>
                </div>

                <!-- Commission: Base amount + % -->
                <div id="labor-commission-fields" class="hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Base Amount *</label>
                            <input type="number" step="0.01" class="form-input" id="labor-base-amount" min="0"
                                placeholder="e.g. 5000">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Commission %</label>
                            <input type="text" class="form-input" id="labor-commission-rate" readonly>
                        </div>
                    </div>
                </div>

                <!-- Salary/Project: Shows fixed amount -->
                <div id="labor-fixed-fields" class="hidden">
                    <p class="text-muted">Fixed amount will be applied.</p>
                </div>

                <button type="button" class="btn btn-outline btn-sm" onclick="calculateLaborAmount()">Calculate
                    Amount</button>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Amount *</label>
                    <input type="number" step="0.01" class="form-input" name="amount" required
                        id="quick-expense-amount">
                </div>
                <div class="form-group">
                    <label class="form-label">Date *</label>
                    <input type="date" class="form-input" name="expense_date" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description *</label>
                <input type="text" class="form-input" name="description" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Vendor</label>
                    <input type="text" class="form-input" name="vendor">
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

            <!-- Optional Journal Entry -->
            <div class="form-group mt-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="expense-je-toggle" onchange="toggleJournalEntry('expense')">
                    <span class="text-sm font-medium">Create Journal Entry (Accounting)</span>
                </label>
            </div>
            <div id="expense-je-fields" class="hidden" style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                <p class="text-sm text-muted mb-3">Select accounts for double-entry bookkeeping:</p>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Debit Account</label>
                        <select class="form-select" id="expense-je-debit">
                            <option value="">Select Account</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Credit Account</label>
                        <select class="form-select" id="expense-je-credit">
                            <option value="">Select Account</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Note (optional)</label>
                    <input type="text" class="form-input" id="expense-je-note"
                        placeholder="Additional note for journal entry">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('quick-expense-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Expense</button>
        </div>
    </form>
</div>

<!-- Add Budget Modal -->
<div class="modal" id="budget-modal">
    <div class="modal-header">
        <h3 class="modal-title">Add Budget Item</h3>
        <button class="modal-close" onclick="Modal.close('budget-modal')">×</button>
    </div>
    <form id="budget-form">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Category *</label>
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
            <div class="form-group">
                <label class="form-label">Budgeted Amount *</label>
                <input type="number" step="0.01" class="form-input" name="budgeted_amount" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('budget-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Budget</button>
        </div>
    </form>
</div>

<!-- Add Income Modal -->
<div class="modal" id="income-modal">
    <div class="modal-header">
        <h3 class="modal-title">Record Project Income</h3>
        <button class="modal-close" onclick="Modal.close('income-modal')">×</button>
    </div>
    <form id="income-form">
        <div class="modal-body">
            <div class="alert alert-info mb-4">
                <small>Recording income will decrease the balance due. When fully paid, the project will be marked as
                    Completed.</small>
            </div>
            <div class="form-group">
                <label class="form-label required">Amount</label>
                <input type="number" step="0.01" class="form-input" name="amount" required>
            </div>
            <div class="form-group">
                <label class="form-label required">Date</label>
                <input type="date" class="form-input" name="payment_date" required>
            </div>
            <div class="form-group">
                <label class="form-label required">Payment Method</label>
                <select class="form-select" name="payment_method" required>
                    <option value="check">Check</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">Description / Ref #</label>
                <textarea class="form-input" name="description" rows="2" required
                    placeholder="e.g. Down payment, Final Invoice payment..."></textarea>
            </div>

            <!-- Optional Journal Entry -->
            <div class="form-group mt-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="income-je-toggle" onchange="toggleJournalEntry('income')">
                    <span class="text-sm font-medium">Create Journal Entry (Accounting)</span>
                </label>
            </div>
            <div id="income-je-fields" class="hidden" style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                <p class="text-sm text-muted mb-3">Select accounts for double-entry bookkeeping:</p>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Debit Account</label>
                        <select class="form-select" id="income-je-debit">
                            <option value="">Select Account</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Credit Account</label>
                        <select class="form-select" id="income-je-credit">
                            <option value="">Select Account</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Note (optional)</label>
                    <input type="text" class="form-input" id="income-je-note"
                        placeholder="Additional note for journal entry">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('income-modal')">Cancel</button>
            <button type="submit" class="btn btn-success">Record Payment</button>
        </div>
    </form>
</div>

<!-- Edit Income Modal -->
<div class="modal" id="edit-income-modal">
    <div class="modal-header">
        <h3 class="modal-title">Edit Income</h3>
        <button class="modal-close" onclick="Modal.close('edit-income-modal')">×</button>
    </div>
    <form id="edit-income-form">
        <input type="hidden" id="edit-income-id">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Amount</label>
                <input type="number" step="0.01" class="form-input" id="edit-income-amount" required>
            </div>
            <div class="form-group">
                <label class="form-label required">Date</label>
                <input type="date" class="form-input" id="edit-income-date" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" id="edit-income-description" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('edit-income-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<!-- Edit Expense Modal -->
<div class="modal" id="edit-expense-modal">
    <div class="modal-header">
        <h3 class="modal-title">Edit Expense</h3>
        <button class="modal-close" onclick="Modal.close('edit-expense-modal')">×</button>
    </div>
    <form id="edit-expense-form">
        <input type="hidden" id="edit-expense-id">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Category</label>
                <select class="form-select" id="edit-expense-category">
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

            <!-- Labor-specific fields (hidden by default) -->
            <div class="form-group hidden" id="edit-expense-employee-container">
                <label class="form-label">Employee</label>
                <input type="text" class="form-input mb-2" id="edit-expense-employee-search"
                    placeholder="Type to search employees..."
                    oninput="filterEmployeeSelect('edit-expense-employee', this.value)">
                <select class="form-select" id="edit-expense-employee">
                    <option value="">Select Employee</option>
                </select>
                <p class="form-help">Employees are sorted A-Z. Type to filter the list.</p>
            </div>

            <!-- Dynamic Payment Type Fields for Edit -->
            <div id="edit-labor-calc-container" class="hidden"
                style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <div class="mb-3">
                    <span class="badge" id="edit-emp-payment-type-badge">-</span>
                    <span class="text-muted ml-2" id="edit-emp-rate-display">-</span>
                </div>

                <!-- Hourly: Hours input -->
                <div id="edit-labor-hourly-fields" class="hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Hours Worked *</label>
                            <input type="number" step="0.5" class="form-input" id="edit-labor-hours" min="0"
                                placeholder="e.g. 8">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rate/Hour</label>
                            <input type="text" class="form-input" id="edit-labor-hourly-rate" readonly>
                        </div>
                    </div>
                </div>

                <!-- Daily: Days input -->
                <div id="edit-labor-daily-fields" class="hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Days Worked *</label>
                            <input type="number" step="0.5" class="form-input" id="edit-labor-days" min="0"
                                placeholder="e.g. 5">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rate/Day</label>
                            <input type="text" class="form-input" id="edit-labor-daily-rate" readonly>
                        </div>
                    </div>
                </div>

                <!-- Commission: Base amount + % -->
                <div id="edit-labor-commission-fields" class="hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Base Amount *</label>
                            <input type="number" step="0.01" class="form-input" id="edit-labor-base-amount" min="0"
                                placeholder="e.g. 5000">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Commission %</label>
                            <input type="text" class="form-input" id="edit-labor-commission-rate" readonly>
                        </div>
                    </div>
                </div>

                <!-- Salary/Project: Shows fixed amount -->
                <div id="edit-labor-fixed-fields" class="hidden">
                    <p class="text-muted">Fixed amount will be applied.</p>
                </div>

                <button type="button" class="btn btn-outline btn-sm" onclick="calculateEditLaborAmount()">Calculate
                    Amount</button>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Amount</label>
                    <input type="number" step="0.01" class="form-input" id="edit-expense-amount" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Date</label>
                    <input type="date" class="form-input" id="edit-expense-date" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" class="form-input" id="edit-expense-description">
            </div>
            <div class="form-group">
                <label class="form-label">Vendor</label>
                <input type="text" class="form-input" id="edit-expense-vendor">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('edit-expense-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<!-- Tasks Tab -->
<div id="tasks-content" style="display: none;">
    <div class="card">
        <div class="card-header flex justify-between">
            <h3 class="card-title">Tasks</h3>
            <button class="btn btn-primary btn-sm" onclick="Modal.open('task-modal')">Add Task</button>
        </div>
        <div class="table-container">
            <table class="table" id="tasks-table">
                <thead>
                    <tr>
                        <th width="30"></th>
                        <th>Task</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
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
</div>

<!-- Budget Tab -->
<div id="budget-content" style="display: none;">
    <div class="grid grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header flex justify-between">
                <h3 class="card-title">Budget Categories</h3>
                <button class="btn btn-primary btn-sm" onclick="Modal.open('budget-modal')">Add Budget</button>
            </div>
            <div class="table-container">
                <table class="table" id="budgets-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Budgeted</th>
                            <th>Spent</th>
                            <th>Remaining</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex justify-between">
                <h3 class="card-title">Expenses</h3>
                <button class="btn btn-primary btn-sm" onclick="Modal.open('quick-expense-modal')">Add Expense</button>
            </div>
            <div class="table-container">
                <table class="table" id="expenses-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Labor Cost Tab -->
<div id="labor-content" style="display: none;">
    <div class="grid grid-cols-4 mb-6">
        <div class="card stat-card">
            <div class="stat-value" id="labor-budget">$0</div>
            <div class="stat-label">Labor Budget</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="labor-actual">$0</div>
            <div class="stat-label">Actual Cost</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="labor-variance">$0</div>
            <div class="stat-label">Variance</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="labor-cph">$0</div>
            <div class="stat-label">Cost/Hour</div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Labor Cost by Employee</h3>
        </div>
        <div class="table-container">
            <table class="table" id="labor-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Pay Type</th>
                        <th>Regular Hours</th>
                        <th>OT Hours</th>
                        <th>Total Hours</th>
                        <th>Labor Cost</th>
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

<!-- Time Logs Tab -->
<div id="time-content" style="display: none;">
    <div class="card">
        <div class="card-header flex justify-between">
            <h3 class="card-title">Time Logs</h3>
            <button class="btn btn-primary btn-sm" onclick="Modal.open('timelog-modal')">Log Time</button>
        </div>
        <div class="table-container">
            <table class="table" id="timelogs-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Task</th>
                        <th>Hours</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Documents Tab -->
<div id="documents-content" style="display: none;">
    <div class="card">
        <div class="card-header flex justify-between">
            <h3 class="card-title">Documents</h3>
            <button class="btn btn-primary btn-sm" onclick="Modal.open('upload-modal')">Upload</button>
        </div>
        <div class="card-body" id="documents-list">
            <p class="text-muted text-center">No documents yet</p>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal" id="task-modal" style="max-width: 500px;">
    <div class="modal-header">
        <h3 class="modal-title">Add Task</h3>
        <button class="modal-close" onclick="Modal.close('task-modal')">×</button>
    </div>
    <form id="task-form">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Title</label>
                <input type="text" class="form-input" name="title" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="2"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" class="form-input" name="due_date">
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('task-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Task</button>
        </div>
    </form>
</div>

<!-- Add Time Log Modal -->
<div class="modal" id="timelog-modal" style="max-width: 500px;">
    <div class="modal-header">
        <h3 class="modal-title">Log Time</h3>
        <button class="modal-close" onclick="Modal.close('timelog-modal')">×</button>
    </div>
    <form id="timelog-form">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Employee</label>
                <select class="form-select" name="employee_id" id="timelog-employee" required>
                    <option value="">Select Employee</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">Date</label>
                <input type="date" class="form-input" name="log_date" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-label required">Hours</label>
                <input type="number" step="0.25" class="form-input" name="hours" required min="0.25"
                    placeholder="e.g. 2.5">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="3" placeholder="What did you work on?"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('timelog-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Log Time</button>
        </div>
    </form>
</div>

<!-- Edit Time Log Modal -->
<div class="modal" id="edit-timelog-modal" style="max-width: 500px;">
    <div class="modal-header">
        <h3 class="modal-title">Edit Time Log</h3>
        <button class="modal-close" onclick="Modal.close('edit-timelog-modal')">×</button>
    </div>
    <form id="edit-timelog-form">
        <input type="hidden" id="edit-timelog-id">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Employee</label>
                <select class="form-select" id="edit-timelog-employee" required>
                    <option value="">Select Employee</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">Date</label>
                <input type="date" class="form-input" id="edit-timelog-date" required>
            </div>
            <div class="form-group">
                <label class="form-label required">Hours</label>
                <input type="number" step="0.25" class="form-input" id="edit-timelog-hours" required min="0.25"
                    placeholder="e.g. 2.5">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" id="edit-timelog-description" rows="3"
                    placeholder="What did you work on?"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('edit-timelog-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Time Log</button>
        </div>
    </form>
</div>

<div class="modal" id="upload-modal" style="max-width: 500px;">
    <div class="modal-header">
        <h3 class="modal-title">Upload Document</h3>
        <button class="modal-close" onclick="Modal.close('upload-modal')">×</button>
    </div>
    <form id="upload-form" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Document Name</label>
                <input type="text" class="form-input" name="name" required placeholder="e.g. Contract, Invoice">
            </div>
            <div class="form-group">
                <label class="form-label required">File</label>
                <input type="file" class="form-input" name="file" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="2" placeholder="Optional description"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('upload-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>
    </form>
</div>

<!-- Edit Project Modal -->
<div class="modal" id="edit-modal">
    <div class="modal-header">
        <h3 class="modal-title">Edit Project</h3>
        <button class="modal-close" onclick="Modal.close('edit-modal')">×</button>
    </div>
    <form id="edit-project-form">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Project Name</label>
                <input type="text" class="form-input" name="name" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Project Code</label>
                    <input type="text" class="form-input" name="code">
                </div>
                <div class="form-group">
                    <label class="form-label">Contract Value</label>
                    <input type="number" step="0.01" class="form-input" name="contract_value" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-input" name="start_date">
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-input" name="end_date">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" class="form-input" name="address">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('edit-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<script>
    // Inject projectId from PHP - this is the only PHP dependency in the JavaScript
    const projectId = <?= json_encode($projectId) ?>;
</script>
<script src="/assets/js/project-show.js?v=<?= time() ?>"></script>
<script>
    // Placeholder script tag for backward compatibility
    const __projectShowLoaded = true;
</script>

<style>
    .stat-card {
        text-align: center;
        padding: var(--space-4);
    }

    .stat-card .stat-value {
        font-size: var(--text-2xl);
    }

    .btn.active {
        background: var(--primary-500);
        color: white;
    }

    .line-through {
        text-decoration: line-through;
    }

    .text-error {
        color: var(--error-500);
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
