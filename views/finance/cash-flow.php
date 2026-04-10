<?php
$title = 'Cash Flow';
$page = 'cash-flow';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Cash Flow</h1>
        <p class="text-muted text-sm">Unified overview of financial inflows and outflows</p>
    </div>
    <div class="flex gap-2">
        <button class="btn btn-outline-primary" onclick="exportToExcel()">
            <i class="fas fa-file-excel mr-2"></i> Export Excel
        </button>
        <button class="btn btn-primary" onclick="openQuickEntryModal()">
            <i class="fas fa-plus mr-2"></i> New Entry
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="card bg-primary-light border-none shadow-sm overflow-hidden">
        <div class="card-body relative p-5">
            <div class="absolute right-[-10px] top-[-10px] opacity-10">
                <i class="fas fa-wallet text-6xl"></i>
            </div>
            <p class="text-xs uppercase font-bold tracking-wider text-primary mb-1">Net Flow (Month)</p>
            <h3 class="text-2xl font-black text-primary" id="kpi-net-flow">$0.00</h3>
            <p class="text-xs mt-2 text-primary-dark" id="kpi-savings-rate">0% margin</p>
        </div>
    </div>

    <div class="card bg-success-light border-none shadow-sm overflow-hidden">
        <div class="card-body relative p-5">
            <div class="absolute right-[-10px] top-[-10px] opacity-10">
                <i class="fas fa-arrow-down text-6xl"></i>
            </div>
            <p class="text-xs uppercase font-bold tracking-wider text-success mb-1">Cash In</p>
            <h3 class="text-2xl font-black text-success" id="kpi-cash-in">$0.00</h3>
            <p class="text-xs mt-2 text-success-dark">Confirmed receipts</p>
        </div>
    </div>

    <div class="card bg-error-light border-none shadow-sm overflow-hidden">
        <div class="card-body relative p-5">
            <div class="absolute right-[-10px] top-[-10px] opacity-10">
                <i class="fas fa-arrow-up text-6xl"></i>
            </div>
            <p class="text-xs uppercase font-bold tracking-wider text-error mb-1">Cash Out</p>
            <h3 class="text-2xl font-black text-error" id="kpi-cash-out">$0.00</h3>
            <p class="text-xs mt-2 text-error-dark">Expenses & Payroll paid</p>
        </div>
    </div>

    <div class="card bg-warning-light border-none shadow-sm overflow-hidden">
        <div class="card-body relative p-5">
            <div class="absolute right-[-10px] top-[-10px] opacity-10">
                <i class="fas fa-calendar-alt text-6xl"></i>
            </div>
            <p class="text-xs uppercase font-bold tracking-wider text-warning mb-1">Available Balance</p>
            <h3 class="text-2xl font-black text-warning" id="kpi-available-balance">Calculating...</h3>
            <p class="text-xs mt-2 text-warning-dark">Total accumulated</p>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 card">
        <div class="card-header flex justify-between items-center bg-white border-b p-4">
            <h5 class="card-title font-bold m-0">Monthly Trend (Cash In vs Cash Out)</h5>
        </div>
        <div class="card-body p-4">
            <div style="height: 300px;">
                <canvas id="cashTrendChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-white border-b p-4">
            <h5 class="card-title font-bold m-0">Spending Distribution</h5>
        </div>
        <div class="card-body p-4">
            <div style="height: 300px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Unified Ledger -->
<div class="card shadow-sm border-none">
    <div class="card-header bg-white border-b p-4 flex flex-wrap justify-between items-center gap-4">
        <h5 class="card-title font-bold m-0">Unified Ledger (Transactions)</h5>
        
        <div class="flex flex-wrap gap-2 items-center">
            <div class="form-group mb-0">
                <select id="filter-project" class="form-select text-sm p-2" onchange="loadTransactions()">
                    <option value="">All Projects</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <input type="date" id="filter-start" class="form-input text-sm p-2" onchange="loadTransactions()">
            </div>
            <div class="form-group mb-0">
                <input type="date" id="filter-end" class="form-input text-sm p-2" onchange="loadTransactions()">
            </div>
            <div class="form-group mb-0">
                <input type="text" id="ledger-search" class="form-input text-sm p-2" placeholder="Search..." onkeyup="debounceLoad()">
            </div>
        </div>
    </div>
    
    <div class="card-body p-0 overflow-x-auto">
        <table class="table table-hover mb-0" id="cash-flow-table">
            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3 border-b text-left">Date</th>
                    <th class="px-4 py-3 border-b text-left">Type</th>
                    <th class="px-4 py-3 border-b text-left">Description</th>
                    <th class="px-4 py-3 border-b text-left">Project</th>
                    <th class="px-4 py-3 border-b text-right">Amount</th>
                    <th class="px-4 py-3 border-b text-right">Balance</th>
                </tr>
            </thead>
            <tbody id="ledger-body">
                <!-- Rendered dynamically -->
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-t p-4 flex justify-between items-center">
        <span class="text-sm text-muted" id="pagination-info">Showing 0 of 0 entries</span>
        <div class="flex gap-1" id="pagination-controls">
            <!-- Rendered dynamically -->
        </div>
    </div>
</div>

<!-- Quick Entry Modal -->
<div id="quick-entry-modal" class="modal">
    <div class="modal-content max-w-lg">
        <div class="modal-header">
            <h5 class="modal-title font-bold">New Entry (Quick Entry)</h5>
            <button class="modal-close" onclick="closeQuickEntryModal()">&times;</button>
        </div>
        <div class="modal-body p-6">
            <form id="quick-entry-form" onsubmit="saveQuickEntry(event)">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="form-group">
                        <label class="form-label font-bold text-xs uppercase text-muted">Type</label>
                        <select name="type" class="form-select" required onchange="updateCategoryOptions()">
                            <option value="income">Income (Credit)</option>
                            <option value="expense">Expense (Debit)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label font-bold text-xs uppercase text-muted">Amount</label>
                        <input type="number" name="amount" class="form-input" step="0.01" required placeholder="0.00">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="form-group">
                        <label class="form-label font-bold text-xs uppercase text-muted">Date</label>
                        <input type="date" name="date" class="form-input" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label font-bold text-xs uppercase text-muted">Pay. Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Transfer / PIX</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label font-bold text-xs uppercase text-muted">Project (Optional)</label>
                    <select name="project_id" id="modal-project" class="form-select">
                        <option value="">Select Project...</option>
                    </select>
                </div>
                
                <div class="form-group mb-4">
                    <label class="form-label font-bold text-xs uppercase text-muted">Category</label>
                    <select name="category" id="modal-category" class="form-select" required>
                        <!-- Dynamic -->
                    </select>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label font-bold text-xs uppercase text-muted">Person / Vendor</label>
                    <input type="text" name="person" class="form-input" placeholder="Client or Vendor">
                </div>

                <div class="form-group mb-6">
                    <label class="form-label font-bold text-xs uppercase text-muted">Description</label>
                    <input type="text" name="description" class="form-input" required placeholder="Ex: Material Payment">
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="closeQuickEntryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart.js CDN (Checking if already included in layouts/main_v114.php typically happens via global scripts, but adding here just in case or for explicit dependency) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/cash-flow.js"></script>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
