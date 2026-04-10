<?php
$title = 'Journal Entries';
$page = 'journal-entries';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Journal Entries</h1>
        <p class="text-muted text-sm">View and create manual journal entries</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('entry-modal'); resetForm();">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        New Entry
    </button>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4">
        <select class="form-select" id="status-filter" style="width: 150px;">
            <option value="">All Status</option>
            <option value="posted">Posted</option>
            <option value="draft">Draft</option>
            <option value="void">Void</option>
        </select>
    </div>
</div>

<!-- Entries Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="entries-table">
            <thead>
                <tr>
                    <th class="sortable" data-sort="entry_date" onclick="sortTable('entry_date')">
                        Date <span class="sort-icon">↕</span>
                    </th>
                    <th class="sortable" data-sort="reference_number" onclick="sortTable('reference_number')">
                        Reference <span class="sort-icon">↕</span>
                    </th>
                    <th class="sortable" data-sort="description" onclick="sortTable('description')">
                        Description <span class="sort-icon">↕</span>
                    </th>
                    <th class="sortable" data-sort="total_debit" onclick="sortTable('total_debit')">
                        Debit <span class="sort-icon">↕</span>
                    </th>
                    <th class="sortable" data-sort="total_credit" onclick="sortTable('total_credit')">
                        Credit <span class="sort-icon">↕</span>
                    </th>
                    <th class="sortable" data-sort="status" onclick="sortTable('status')">
                        Status <span class="sort-icon">↕</span>
                    </th>
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

<style>
    .sortable {
        cursor: pointer;
        user-select: none;
        position: relative;
    }

    .sortable:hover {
        background: var(--bg-tertiary);
    }

    .sort-icon {
        opacity: 0.3;
        margin-left: 4px;
        font-size: 0.8em;
    }

    .sortable.asc .sort-icon,
    .sortable.desc .sort-icon {
        opacity: 1;
    }

    .sortable.asc .sort-icon::after {
        content: '↑';
    }

    .sortable.desc .sort-icon::after {
        content: '↓';
    }

    .sortable.asc .sort-icon,
    .sortable.desc .sort-icon {
        visibility: hidden;
    }

    .sortable.asc::after {
        content: ' ↑';
        opacity: 1;
    }

    .sortable.desc::after {
        content: ' ↓';
        opacity: 1;
    }

    /* Make debit/credit input fields wider and more readable */
    #lines-table input[name="debit"],
    #lines-table input[name="credit"] {
        min-width: 100px;
        width: 100%;
        text-align: right;
        padding: 8px 10px;
        font-size: 14px;
    }

    /* Ensure table cells don't compress the inputs too much */
    #lines-table td {
        padding: 8px 6px;
    }
</style>

<!-- New Entry Modal -->
<div class="modal modal-lg" id="entry-modal">
    <div class="modal-header">
        <h3 class="modal-title">New Journal Entry</h3>
        <button class="modal-close" onclick="Modal.close('entry-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="entry-form">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label class="form-label required">Entry Date</label>
                    <input type="date" class="form-input" name="entry_date" id="entry-date" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Description</label>
                    <input type="text" class="form-input" name="description" id="entry-description" required
                        placeholder="e.g., Monthly rent payment">
                </div>
            </div>

            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <label class="form-label">Entry Lines</label>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="addLine()">+ Add Line</button>
                </div>
                <table class="table" id="lines-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Account</th>
                            <th style="width: 20%;">Description</th>
                            <th style="width: 140px;">Debit</th>
                            <th style="width: 140px;">Credit</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="lines-body">
                    </tbody>
                    <tfoot>
                        <tr class="font-medium">
                            <td colspan="2" class="text-right">Totals:</td>
                            <td id="total-debit">$0.00</td>
                            <td id="total-credit">$0.00</td>
                            <td></td>
                        </tr>
                        <tr id="balance-row" style="display: none;">
                            <td colspan="4" class="text-right text-error">Entry is unbalanced!</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('entry-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary" id="save-btn">Post Entry</button>
        </div>
    </form>
</div>

<!-- View Entry Modal -->
<div class="modal modal-lg" id="view-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="view-title">Journal Entry</h3>
        <button class="modal-close" onclick="Modal.close('view-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <div class="modal-body" id="view-content">
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('view-modal')">Close</button>
        <button type="button" class="btn btn-error" id="void-btn" onclick="voidEntry()">Void Entry</button>
    </div>
</div>

<script>
    let accounts = [];
    let lineCount = 0;
    let currentEntryId = null;

    document.addEventListener('DOMContentLoaded', async function () {
        // Load accounts for dropdown
        try {
            const resp = await ERP.api.get('/accounts');
            if (resp.success) accounts = resp.data;
        } catch (e) { }

        // Set default date
        document.getElementById('entry-date').value = new Date().toISOString().split('T')[0];

        loadEntries();

        document.getElementById('status-filter').addEventListener('change', loadEntries);

        document.getElementById('entry-form').addEventListener('submit', async function (e) {
            e.preventDefault();

            const lines = [];
            document.querySelectorAll('#lines-body tr').forEach(row => {
                const accountId = row.querySelector('[name="account_id"]').value;
                const desc = row.querySelector('[name="line_desc"]').value;
                const debit = parseFloat(row.querySelector('[name="debit"]').value) || 0;
                const credit = parseFloat(row.querySelector('[name="credit"]').value) || 0;

                if (accountId && (debit > 0 || credit > 0)) {
                    lines.push({ account_id: accountId, description: desc, debit, credit });
                }
            });

            const data = {
                entry_date: document.getElementById('entry-date').value,
                description: document.getElementById('entry-description').value,
                lines: lines
            };

            const editId = this.dataset.editId;

            try {
                if (editId) {
                    // Update existing entry
                    await ERP.api.put('/journal-entries/' + editId, data);
                    ERP.toast.success('Journal entry updated');
                } else {
                    // Create new entry
                    await ERP.api.post('/journal-entries', data);
                    ERP.toast.success('Journal entry posted');
                }
                Modal.close('entry-modal');
                loadEntries();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadEntries() {
        const status = document.getElementById('status-filter').value;
        const params = status ? '?status=' + status : '';

        try {
            const response = await ERP.api.get('/journal-entries' + params);
            if (response.success) {
                renderEntries(response.data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load entries');
        }
    }

    function renderEntries(entries) {
        const tbody = document.querySelector('#entries-table tbody');

        if (entries.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No entries found</td></tr>';
            return;
        }

        tbody.innerHTML = entries.map(e => `
        <tr>
            <td>${formatDate(e.entry_date)}</td>
            <td><span class="font-mono">${e.reference_number}</span></td>
            <td>${e.description}</td>
            <td class="font-medium">${formatCurrency(e.total_debit || 0)}</td>
            <td class="font-medium">${formatCurrency(e.total_credit || 0)}</td>
            <td><span class="badge badge-${getStatusBadge(e.status)}">${capitalize(e.status)}</span></td>
            <td>
                <div class="flex gap-1">
                    <button class="btn btn-icon btn-sm btn-secondary" onclick="viewEntry(${e.id})" title="View">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                    ${e.status !== 'void' ? `
                    <button class="btn btn-icon btn-sm btn-secondary" onclick="editEntry(${e.id})" title="Edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
    }

    function resetForm() {
        document.getElementById('entry-form').reset();
        document.getElementById('entry-date').value = new Date().toISOString().split('T')[0];
        document.getElementById('lines-body').innerHTML = '';
        lineCount = 0;
        addLine();
        addLine();
        updateTotals();
    }

    function addLine() {
        lineCount++;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select class="form-select form-select-sm" name="account_id" required>
                    <option value="">Select Account</option>
                    ${accounts.map(a => `<option value="${a.id}">${a.code} - ${a.name}</option>`).join('')}
                </select>
            </td>
            <td><input type="text" class="form-input form-input-sm" name="line_desc" placeholder="Memo"></td>
            <td><input type="number" class="form-input form-input-sm" name="debit" step="0.01" min="0" onchange="updateTotals()"></td>
            <td><input type="number" class="form-input form-input-sm" name="credit" step="0.01" min="0" onchange="updateTotals()"></td>
            <td><button type="button" class="btn btn-icon btn-sm btn-error" onclick="removeLine(this)">×</button></td>
        `;
        document.getElementById('lines-body').appendChild(row);
    }

    function removeLine(btn) {
        btn.closest('tr').remove();
        updateTotals();
    }

    function updateTotals() {
        let totalDebit = 0, totalCredit = 0;
        document.querySelectorAll('#lines-body tr').forEach(row => {
            totalDebit += parseFloat(row.querySelector('[name="debit"]').value) || 0;
            totalCredit += parseFloat(row.querySelector('[name="credit"]').value) || 0;
        });
        document.getElementById('total-debit').textContent = formatCurrency(totalDebit);
        document.getElementById('total-credit').textContent = formatCurrency(totalCredit);

        const balanced = Math.abs(totalDebit - totalCredit) < 0.01;
        document.getElementById('balance-row').style.display = (totalDebit > 0 || totalCredit > 0) && !balanced ? '' : 'none';
        document.getElementById('save-btn').disabled = !balanced || totalDebit === 0;
    }

    async function viewEntry(id) {
        currentEntryId = id;
        try {
            const response = await ERP.api.get('/journal-entries/' + id);
            if (response.success) {
                const e = response.data;
                document.getElementById('view-title').textContent = e.reference_number;
                document.getElementById('void-btn').style.display = e.status === 'posted' ? '' : 'none';

                let html = `
                    <div class="mb-4">
                        <strong>Date:</strong> ${formatDate(e.entry_date)}<br>
                        <strong>Description:</strong> ${e.description}<br>
                        <strong>Status:</strong> <span class="badge badge-${getStatusBadge(e.status)}">${capitalize(e.status)}</span>
                    </div>
                    <table class="table">
                        <thead><tr><th>Account</th><th>Description</th><th>Debit</th><th>Credit</th></tr></thead>
                        <tbody>
                            ${e.lines.map(l => `
                                <tr>
                                    <td>${l.account_code} - ${l.account_name}</td>
                                    <td>${l.description || '-'}</td>
                                    <td>${l.debit > 0 ? formatCurrency(l.debit) : ''}</td>
                                    <td>${l.credit > 0 ? formatCurrency(l.credit) : ''}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                document.getElementById('view-content').innerHTML = html;
                Modal.open('view-modal');
            }
        } catch (error) {
            ERP.toast.error('Failed to load entry');
        }
    }

    async function voidEntry() {
        if (!confirm('Are you sure you want to void this entry? This cannot be undone.')) return;
        try {
            await ERP.api.post('/journal-entries/' + currentEntryId + '/void');
            ERP.toast.success('Entry voided');
            Modal.close('view-modal');
            loadEntries();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    function getStatusBadge(status) {
        return { 'posted': 'success', 'draft': 'warning', 'void': 'error' }[status] || 'secondary';
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
    }

    function formatDate(dateStr) {
        return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Sorting functionality
    let currentSort = { column: 'entry_date', direction: 'desc' };
    let entriesData = [];

    function sortTable(column) {
        // Update sort direction
        if (currentSort.column === column) {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.column = column;
            currentSort.direction = 'asc';
        }

        // Update header UI
        document.querySelectorAll('.sortable').forEach(th => {
            th.classList.remove('asc', 'desc');
            if (th.dataset.sort === column) {
                th.classList.add(currentSort.direction);
            }
        });

        // Sort data
        entriesData.sort((a, b) => {
            let aVal = a[column] || '';
            let bVal = b[column] || '';

            // Handle numeric values
            if (column === 'total_debit' || column === 'total_credit') {
                aVal = parseFloat(aVal) || 0;
                bVal = parseFloat(bVal) || 0;
            }

            // Handle date values
            if (column === 'entry_date') {
                aVal = new Date(aVal).getTime();
                bVal = new Date(bVal).getTime();
            }

            if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
            }

            if (currentSort.direction === 'asc') {
                return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
            } else {
                return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
            }
        });

        renderEntries(entriesData);
    }

    // Override loadEntries to store data for sorting
    const originalLoadEntries = loadEntries;
    async function loadEntries() {
        const status = document.getElementById('status-filter').value;
        const params = status ? '?status=' + status : '';

        try {
            const response = await ERP.api.get('/journal-entries' + params);
            if (response.success) {
                entriesData = response.data;
                renderEntries(entriesData);
            }
        } catch (error) {
            ERP.toast.error('Failed to load entries');
        }
    }

    // Edit entry function
    async function editEntry(id) {
        try {
            const response = await ERP.api.get('/journal-entries/' + id);
            if (response.success) {
                const entry = response.data;

                // Populate form
                document.getElementById('entry-date').value = entry.entry_date;
                document.getElementById('entry-description').value = entry.description;

                // Clear existing lines
                document.getElementById('lines-body').innerHTML = '';
                lineCount = 0;

                // Add lines from entry
                entry.lines.forEach(line => {
                    lineCount++;
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <select class="form-select form-select-sm" name="account_id" required>
                                <option value="">Select Account</option>
                                ${accounts.map(a => `<option value="${a.id}" ${a.id == line.account_id ? 'selected' : ''}>${a.code} - ${a.name}</option>`).join('')}
                            </select>
                        </td>
                        <td><input type="text" class="form-input form-input-sm" name="line_desc" placeholder="Memo" value="${line.description || ''}"></td>
                        <td><input type="number" class="form-input form-input-sm" name="debit" step="0.01" min="0" value="${line.debit || ''}" onchange="updateTotals()"></td>
                        <td><input type="number" class="form-input form-input-sm" name="credit" step="0.01" min="0" value="${line.credit || ''}" onchange="updateTotals()"></td>
                        <td><button type="button" class="btn btn-icon btn-sm btn-error" onclick="removeLine(this)">×</button></td>
                    `;
                    document.getElementById('lines-body').appendChild(row);
                });

                updateTotals();

                // Store entry ID for update
                document.getElementById('entry-form').dataset.editId = id;
                document.querySelector('#entry-modal .modal-title').textContent = 'Edit Journal Entry';
                document.getElementById('save-btn').textContent = 'Update Entry';

                Modal.open('entry-modal');
            }
        } catch (error) {
            ERP.toast.error('Failed to load entry for editing');
        }
    }

    // Delete entry function
    async function deleteEntry(id, refNumber) {
        if (!confirm(`Are you sure you want to delete entry ${refNumber}? This cannot be undone.`)) {
            return;
        }

        try {
            await ERP.api.delete('/journal-entries/' + id);
            ERP.toast.success('Entry deleted successfully');
            loadEntries();
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to delete entry');
        }
    }

    // Reset form also clears edit mode
    const originalResetForm = resetForm;
    function resetForm() {
        document.getElementById('entry-form').reset();
        document.getElementById('entry-date').value = new Date().toISOString().split('T')[0];
        document.getElementById('lines-body').innerHTML = '';
        document.getElementById('entry-form').dataset.editId = '';
        document.querySelector('#entry-modal .modal-title').textContent = 'New Journal Entry';
        document.getElementById('save-btn').textContent = 'Post Entry';
        lineCount = 0;
        addLine();
        addLine();
        updateTotals();
    }
</script>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
