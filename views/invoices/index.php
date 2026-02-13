<?php
$title = 'Invoices';
$page = 'invoices';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Invoices</h1>
        <p class="text-muted text-sm">Manage billing and payments</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('invoice-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        New Invoice
    </button>
    <button class="btn btn-secondary" onclick="openFromTimeModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
        </svg>
        Invoice from Time
    </button>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-4 mb-6">
    <div class="card stat-card">
        <div class="stat-value" id="total-invoiced">$0</div>
        <div class="stat-label">Total Invoiced</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-success" id="total-paid">$0</div>
        <div class="stat-label">Paid</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-warning" id="total-pending">$0</div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-error" id="total-overdue">$0</div>
        <div class="stat-label">Overdue</div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4 items-center">
        <div class="flex-1">
            <input type="text" class="form-input" id="search-input" placeholder="Search invoice number...">
        </div>
        <select class="form-select" id="status-filter" style="width: 150px;">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="sent">Sent</option>
            <option value="partial">Partial</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
        </select>
        <select class="form-select" id="client-filter" style="width: 180px;">
            <option value="">All Clients</option>
        </select>
        <button class="btn btn-secondary" onclick="loadInvoices()">Filter</button>
    </div>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="invoices-table">
            <thead>
                <tr>
                    <th class="sortable" data-sort="invoice_number" onclick="sortInvoices('invoice_number')"
                        style="cursor: pointer;">
                        Invoice # <span class="sort-icon" id="sort-icon-invoice_number"></span>
                    </th>
                    <th class="sortable" data-sort="client" onclick="sortInvoices('client')" style="cursor: pointer;">
                        Client <span class="sort-icon" id="sort-icon-client"></span>
                    </th>
                    <th class="sortable" data-sort="project" onclick="sortInvoices('project')" style="cursor: pointer;">
                        Project <span class="sort-icon" id="sort-icon-project"></span>
                    </th>
                    <th class="sortable" data-sort="issue_date" onclick="sortInvoices('issue_date')"
                        style="cursor: pointer;">
                        Issue Date <span class="sort-icon" id="sort-icon-issue_date"></span>
                    </th>
                    <th class="sortable" data-sort="due_date" onclick="sortInvoices('due_date')"
                        style="cursor: pointer;">
                        Due Date <span class="sort-icon" id="sort-icon-due_date"></span>
                    </th>
                    <th class="sortable" data-sort="amount" onclick="sortInvoices('amount')" style="cursor: pointer;">
                        Amount <span class="sort-icon" id="sort-icon-amount"></span>
                    </th>
                    <th class="sortable" data-sort="paid" onclick="sortInvoices('paid')" style="cursor: pointer;">
                        Paid <span class="sort-icon" id="sort-icon-paid"></span>
                    </th>
                    <th class="sortable" data-sort="status" onclick="sortInvoices('status')" style="cursor: pointer;">
                        Status <span class="sort-icon" id="sort-icon-status"></span>
                    </th>
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

<!-- New Invoice Modal -->
<div class="modal" id="invoice-modal" style="max-width: 600px;">
    <div class="modal-header">
        <h3 class="modal-title">Create Invoice</h3>
        <button class="modal-close" onclick="Modal.close('invoice-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="invoice-form">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Client</label>
                    <select class="form-select" name="client_id" id="modal-client-select" required>
                        <option value="">Select Client</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Project</label>
                    <select class="form-select" name="project_id" id="modal-project-select">
                        <option value="">Select Project</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Issue Date</label>
                    <input type="date" class="form-input" name="issue_date">
                </div>
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" class="form-input" name="due_date">
                </div>
            </div>

            <!-- Line Items -->
            <div class="form-group">
                <label class="form-label">Line Items</label>
                <div id="line-items">
                    <div class="line-item flex gap-2 mb-2">
                        <input type="text" class="form-input" name="items[0][description]" placeholder="Description"
                            style="flex: 2;">
                        <input type="number" class="form-input" name="items[0][quantity]" placeholder="Qty" value="1"
                            min="1" style="width: 80px;">
                        <input type="number" class="form-input" name="items[0][unit_price]" placeholder="Price"
                            step="0.01" style="width: 100px;">
                        <button type="button" class="btn btn-secondary btn-icon"
                            onclick="this.parentElement.remove(); calculateTotal();">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addLineItem()">
                    + Add Item
                </button>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" class="form-input" name="tax_rate" value="0" min="0" max="100" step="0.1"
                        onchange="calculateTotal()">
                </div>
                <div class="form-group">
                    <label class="form-label">Discount</label>
                    <input type="number" class="form-input" name="discount_amount" value="0" min="0" step="0.01"
                        onchange="calculateTotal()">
                </div>
            </div>

            <div class="invoice-totals mt-4 p-4 bg-secondary rounded">
                <div class="flex justify-between mb-2">
                    <span>Subtotal:</span>
                    <span id="subtotal">$0.00</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span>Tax:</span>
                    <span id="tax-amount">$0.00</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span>Discount:</span>
                    <span id="discount-display">-$0.00</span>
                </div>
                <div class="flex justify-between font-bold text-lg">
                    <span>Total:</span>
                    <span id="total-amount">$0.00</span>
                </div>
            </div>

            <div class="form-group mt-4">
                <label class="form-label">Notes</label>
                <textarea class="form-input" name="notes" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('invoice-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Invoice</button>
        </div>
    </form>
</div>

<!-- Invoice from Time Modal -->
<div class="modal" id="from-time-modal" style="max-width: 800px;">
    <div class="modal-header">
        <h3 class="modal-title">Invoice from Billable Time</h3>
        <button class="modal-close" onclick="Modal.close('from-time-modal')">×</button>
    </div>
    <div class="modal-body">
        <div class="flex gap-4 mb-4">
            <select class="form-select" id="time-project-filter" style="flex: 1;" onchange="loadBillableTime()">
                <option value="">All Projects</option>
            </select>
            <button class="btn btn-secondary" onclick="loadBillableTime()">Refresh</button>
        </div>

        <div class="billable-summary p-4 bg-secondary rounded mb-4" id="billable-summary" style="display: none;">
            <div class="flex justify-between">
                <div>
                    <span id="selected-count">0</span> time logs selected
                    (<span id="selected-hours">0</span> hours)
                </div>
                <div class="font-bold text-lg">
                    Total: <span id="selected-amount">$0</span>
                </div>
            </div>
        </div>

        <div class="table-container" style="max-height: 400px; overflow-y: auto;">
            <table class="table" id="billable-time-table">
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" id="select-all-time"
                                onchange="toggleAllTime(this.checked)"></th>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Project</th>
                        <th>Hours</th>
                        <th>Rate</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Loading billable time...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('from-time-modal')">Cancel</button>
        <button type="button" class="btn btn-primary" id="create-from-time-btn" onclick="createFromTime()" disabled>
            Create Invoice
        </button>
    </div>
</div>

<script>
    let lineItemIndex = 1;

    // Sorting state
    let invoicesData = [];
    let invoiceSortColumn = 'issue_date';
    let invoiceSortDirection = 'desc';

    document.addEventListener('DOMContentLoaded', function () {
        // Set default dates
        const today = new Date();
        const dueDate = new Date(today);
        dueDate.setDate(today.getDate() + 30);

        document.querySelector('[name="issue_date"]').value = today.toISOString().split('T')[0];
        document.querySelector('[name="due_date"]').value = dueDate.toISOString().split('T')[0];

        loadInvoices();
        loadClients();
        loadStats();

        // Client change loads projects
        document.getElementById('modal-client-select').addEventListener('change', function (e) {
            if (e.target.value) {
                loadClientProjects(e.target.value);
            }
        });

        // Form submit
        document.getElementById('invoice-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {
                client_id: formData.get('client_id'),
                project_id: formData.get('project_id'),
                issue_date: formData.get('issue_date'),
                due_date: formData.get('due_date'),
                tax_rate: parseFloat(formData.get('tax_rate')) || 0,
                discount_amount: parseFloat(formData.get('discount_amount')) || 0,
                notes: formData.get('notes'),
                items: []
            };

            // Collect line items
            document.querySelectorAll('.line-item').forEach((item, i) => {
                const desc = item.querySelector('[name*="description"]').value;
                const qty = parseFloat(item.querySelector('[name*="quantity"]').value) || 1;
                const price = parseFloat(item.querySelector('[name*="unit_price"]').value) || 0;
                if (desc && price) {
                    data.items.push({ description: desc, quantity: qty, unit_price: price });
                }
            });

            if (data.items.length === 0) {
                ERP.toast.error('Please add at least one line item');
                return;
            }

            try {
                await ERP.api.post('/invoices', data);
                ERP.toast.success('Invoice created');
                Modal.close('invoice-modal');
                loadInvoices();
                loadStats();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        // Calculate on input
        document.getElementById('line-items').addEventListener('input', calculateTotal);
    });

    async function loadInvoices() {
        const params = new URLSearchParams({
            search: document.getElementById('search-input').value,
            status: document.getElementById('status-filter').value,
            client_id: document.getElementById('client-filter').value,
            per_page: 100,  // Increased for client-side sorting
        });

        try {
            const response = await ERP.api.get('/invoices?' + params);
            if (response.success) {
                invoicesData = response.data;  // Store for sorting
                renderInvoices(invoicesData);
            }
        } catch (error) {
            ERP.toast.error('Failed to load invoices');
        }
    }

    function sortInvoices(column) {
        if (!invoicesData || invoicesData.length === 0) return;

        // Toggle direction if same column, otherwise default to ascending
        if (invoiceSortColumn === column) {
            invoiceSortDirection = invoiceSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            invoiceSortColumn = column;
            invoiceSortDirection = 'asc';
        }

        // Sort the data
        invoicesData.sort((a, b) => {
            let valA, valB;

            switch (column) {
                case 'invoice_number':
                    valA = (a.invoice_number || '').toLowerCase();
                    valB = (b.invoice_number || '').toLowerCase();
                    break;
                case 'client':
                    valA = (a.client_name || '').toLowerCase();
                    valB = (b.client_name || '').toLowerCase();
                    break;
                case 'project':
                    valA = (a.project_name || '').toLowerCase();
                    valB = (b.project_name || '').toLowerCase();
                    break;
                case 'issue_date':
                    valA = new Date(a.issue_date || 0).getTime();
                    valB = new Date(b.issue_date || 0).getTime();
                    break;
                case 'due_date':
                    valA = new Date(a.due_date || 0).getTime();
                    valB = new Date(b.due_date || 0).getTime();
                    break;
                case 'amount':
                    valA = parseFloat(a.total_amount || 0);
                    valB = parseFloat(b.total_amount || 0);
                    break;
                case 'paid':
                    valA = parseFloat(a.paid_amount || 0);
                    valB = parseFloat(b.paid_amount || 0);
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

            return invoiceSortDirection === 'asc' ? comparison : -comparison;
        });

        updateInvoiceSortIcons(column);
        renderInvoices(invoicesData);
    }

    function updateInvoiceSortIcons(activeColumn) {
        const columns = ['invoice_number', 'client', 'project', 'issue_date', 'due_date', 'amount', 'paid', 'status'];
        columns.forEach(col => {
            const icon = document.getElementById(`sort-icon-${col}`);
            if (icon) {
                if (col === activeColumn) {
                    icon.textContent = invoiceSortDirection === 'asc' ? ' ↑' : ' ↓';
                } else {
                    icon.textContent = '';
                }
            }
        });
    }

    async function loadClients() {
        try {
            const response = await ERP.api.get('/clients?per_page=100');
            if (response.success) {
                const options = response.data.map(c =>
                    `<option value="${c.id}">${c.name}</option>`
                ).join('');

                document.getElementById('client-filter').innerHTML =
                    '<option value="">All Clients</option>' + options;
                document.getElementById('modal-client-select').innerHTML =
                    '<option value="">Select Client</option>' + options;
            }
        } catch (error) {
            console.error('Failed to load clients:', error);
        }
    }

    async function loadClientProjects(clientId) {
        try {
            const response = await ERP.api.get(`/clients/${clientId}/projects`);
            if (response.success) {
                const options = response.data.map(p =>
                    `<option value="${p.id}">${p.name}</option>`
                ).join('');
                document.getElementById('modal-project-select').innerHTML =
                    '<option value="">Select Project</option>' + options;
            }
        } catch (error) {
            console.error('Failed to load projects:', error);
        }
    }

    async function loadStats() {
        try {
            const response = await ERP.api.get('/invoices?per_page=200');
            if (response.success) {
                let total = 0, paid = 0, pending = 0, overdue = 0;
                const today = new Date().toISOString().split('T')[0];

                response.data.forEach(inv => {
                    const amount = parseFloat(inv.total_amount);
                    const paidAmount = parseFloat(inv.paid_amount);
                    total += amount;
                    paid += paidAmount;

                    if (inv.status === 'paid') return;
                    if (inv.due_date < today) {
                        overdue += amount - paidAmount;
                    } else {
                        pending += amount - paidAmount;
                    }
                });

                document.getElementById('total-invoiced').textContent = formatCurrency(total);
                document.getElementById('total-paid').textContent = formatCurrency(paid);
                document.getElementById('total-pending').textContent = formatCurrency(pending);
                document.getElementById('total-overdue').textContent = formatCurrency(overdue);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    function renderInvoices(invoices) {
        const tbody = document.querySelector('#invoices-table tbody');
        const today = new Date().toISOString().split('T')[0];

        if (invoices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No invoices found</td></tr>';
            return;
        }

        tbody.innerHTML = invoices.map(inv => {
            const isOverdue = inv.due_date < today && inv.status !== 'paid';
            return `
        <tr>
            <td><a href="invoices/${inv.id}" class="font-medium">${inv.invoice_number}</a></td>
            <td>${inv.client_name || '-'}</td>
            <td>${inv.project_name || '-'}</td>
            <td>${formatDate(inv.issue_date)}</td>
            <td class="${isOverdue ? 'text-error' : ''}">${formatDate(inv.due_date)}</td>
            <td class="font-medium">${formatCurrency(inv.total_amount)}</td>
            <td>${formatCurrency(inv.paid_amount)}</td>
            <td>
                <span class="badge badge-${getStatusBadge(inv.status, isOverdue)}">
                    ${isOverdue ? 'Overdue' : formatStatus(inv.status)}
                </span>
            </td>
            <td>
                <div class="flex gap-1">
                    <button class="btn btn-icon btn-sm btn-secondary" onclick="viewInvoice(${inv.id})" title="View">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                    ${inv.status === 'draft' ? `
                    <button class="btn btn-icon btn-sm btn-primary" onclick="sendInvoice(${inv.id})" title="Send">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>
                    ` : ''}
                    <button class="btn btn-icon btn-sm btn-error" onclick="deleteInvoice(${inv.id})" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `}).join('');
    }

    function addLineItem() {
        const container = document.getElementById('line-items');
        const html = `
        <div class="line-item flex gap-2 mb-2">
            <input type="text" class="form-input" name="items[${lineItemIndex}][description]" placeholder="Description" style="flex: 2;">
            <input type="number" class="form-input" name="items[${lineItemIndex}][quantity]" placeholder="Qty" value="1" min="1" style="width: 80px;">
            <input type="number" class="form-input" name="items[${lineItemIndex}][unit_price]" placeholder="Price" step="0.01" style="width: 100px;">
            <button type="button" class="btn btn-secondary btn-icon" onclick="this.parentElement.remove(); calculateTotal();">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    `;
        container.insertAdjacentHTML('beforeend', html);
        lineItemIndex++;
    }

    function calculateTotal() {
        let subtotal = 0;
        document.querySelectorAll('.line-item').forEach(item => {
            const qty = parseFloat(item.querySelector('[name*="quantity"]').value) || 0;
            const price = parseFloat(item.querySelector('[name*="unit_price"]').value) || 0;
            subtotal += qty * price;
        });

        const taxRate = parseFloat(document.querySelector('[name="tax_rate"]').value) || 0;
        const discount = parseFloat(document.querySelector('[name="discount_amount"]').value) || 0;
        const taxAmount = subtotal * (taxRate / 100);
        const total = subtotal + taxAmount - discount;

        document.getElementById('subtotal').textContent = formatCurrency(subtotal);
        document.getElementById('tax-amount').textContent = formatCurrency(taxAmount);
        document.getElementById('discount-display').textContent = '-' + formatCurrency(discount);
        document.getElementById('total-amount').textContent = formatCurrency(total);
    }

    async function sendInvoice(id) {
        if (!confirm('Send this invoice to the client?')) return;
        try {
            await ERP.api.post(`/invoices/${id}/send`, {});
            ERP.toast.success('Invoice sent');
            loadInvoices();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    function viewInvoice(id) {
        window.location.href = 'invoices/' + id;
    }

    async function deleteInvoice(id) {
        if (!confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
            return;
        }

        try {
            await ERP.api.delete(`/invoices/${id}`);
            ERP.toast.success('Invoice deleted');
            loadInvoices();
            loadStats();
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to delete invoice');
        }
    }

    function getStatusBadge(status, isOverdue) {
        if (isOverdue) return 'error';
        const map = {
            'draft': 'secondary',
            'sent': 'primary',
            'partial': 'warning',
            'paid': 'success',
            'cancelled': 'error'
        };
        return map[status] || 'secondary';
    }

    function formatStatus(status) {
        return status.charAt(0).toUpperCase() + status.slice(1);
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0
        }).format(amount);
    }

    function formatDate(date) {
        return new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    // Time-to-Invoice functions
    let billableLogs = [];
    let selectedTimeLogIds = [];

    async function openFromTimeModal() {
        Modal.open('from-time-modal');
        await loadProjectsForTimeFilter();
        loadBillableTime();
    }

    async function loadProjectsForTimeFilter() {
        try {
            const response = await ERP.api.get('/projects?status=in_progress&per_page=50');
            if (response.success) {
                const options = response.data.map(p =>
                    `<option value="${p.id}">${p.name}</option>`
                ).join('');
                document.getElementById('time-project-filter').innerHTML =
                    '<option value="">All Projects</option>' + options;
            }
        } catch (e) {
            console.error('Failed to load projects:', e);
        }
    }

    async function loadBillableTime() {
        const projectId = document.getElementById('time-project-filter').value;
        let url = '/invoices/billable-time';
        if (projectId) url += `?project_id=${projectId}`;

        try {
            const response = await ERP.api.get(url);
            if (response.success) {
                billableLogs = response.data.logs;
                selectedTimeLogIds = [];
                renderBillableTime();
                updateTimeSelection();
            }
        } catch (e) {
            ERP.toast.error('Failed to load billable time');
        }
    }

    function renderBillableTime() {
        const tbody = document.querySelector('#billable-time-table tbody');

        if (billableLogs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No billable time logs found</td></tr>';
            document.getElementById('billable-summary').style.display = 'none';
            return;
        }

        document.getElementById('billable-summary').style.display = 'block';

        tbody.innerHTML = billableLogs.map(log => `
            <tr>
                <td>
                    <input type="checkbox" class="time-checkbox" 
                           data-id="${log.id}" 
                           data-hours="${log.hours}" 
                           data-amount="${log.billable_amount}"
                           onchange="updateTimeSelection()">
                </td>
                <td>${formatDate(log.log_date)}</td>
                <td>${log.first_name} ${log.last_name}</td>
                <td>${log.project_name || '-'}</td>
                <td>${log.hours}h</td>
                <td>${formatCurrency(log.hourly_rate || 0)}/hr</td>
                <td class="font-medium">${formatCurrency(log.billable_amount)}</td>
            </tr>
        `).join('');
    }

    function toggleAllTime(checked) {
        document.querySelectorAll('.time-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        updateTimeSelection();
    }

    function updateTimeSelection() {
        selectedTimeLogIds = [];
        let totalHours = 0;
        let totalAmount = 0;

        document.querySelectorAll('.time-checkbox:checked').forEach(cb => {
            selectedTimeLogIds.push(parseInt(cb.dataset.id));
            totalHours += parseFloat(cb.dataset.hours);
            totalAmount += parseFloat(cb.dataset.amount);
        });

        document.getElementById('selected-count').textContent = selectedTimeLogIds.length;
        document.getElementById('selected-hours').textContent = totalHours.toFixed(1);
        document.getElementById('selected-amount').textContent = formatCurrency(totalAmount);
        document.getElementById('create-from-time-btn').disabled = selectedTimeLogIds.length === 0;
    }

    async function createFromTime() {
        if (selectedTimeLogIds.length === 0) {
            ERP.toast.error('Please select time logs');
            return;
        }

        const btn = document.getElementById('create-from-time-btn');
        btn.disabled = true;
        btn.textContent = 'Creating...';

        try {
            const response = await ERP.api.post('/invoices/from-time', {
                time_log_ids: selectedTimeLogIds
            });

            if (response.success) {
                ERP.toast.success('Invoice created from time logs');
                Modal.close('from-time-modal');
                loadInvoices();
                loadStats();

                // Navigate to the new invoice
                if (response.data && response.data.id) {
                    window.location.href = 'invoices/' + response.data.id;
                }
            }
        } catch (e) {
            ERP.toast.error(e.message || 'Failed to create invoice');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Create Invoice';
        }
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

    .text-warning {
        color: var(--warning-500);
    }

    .text-error {
        color: var(--error-500);
    }

    .bg-secondary {
        background: var(--bg-secondary);
    }

    .rounded {
        border-radius: var(--radius-lg);
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>