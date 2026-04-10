<?php
$title = 'Estimates';
$page = 'estimates';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Estimates & Quotes</h1>
        <p class="text-muted text-sm">Create and manage project estimates</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('estimate-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        New Estimate
    </button>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-4 mb-6">
    <div class="card stat-card">
        <div class="stat-value" id="total-estimates">0</div>
        <div class="stat-label">Total Estimates</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-warning" id="pending-value">$0</div>
        <div class="stat-label">Pending Value</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-success" id="approved-value">$0</div>
        <div class="stat-label">Approved</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-primary" id="converted-value">$0</div>
        <div class="stat-label">Converted to Invoice</div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4 items-center">
        <div class="flex-1">
            <input type="text" class="form-input" id="search-input" placeholder="Search estimates...">
        </div>
        <select class="form-select" id="status-filter" style="width: 140px;">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="sent">Sent</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="converted">Converted</option>
        </select>
        <select class="form-select" id="client-filter" style="width: 180px;">
            <option value="">All Clients</option>
        </select>
        <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
    </div>
</div>

<!-- Estimates Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="estimates-table">
            <thead>
                <tr>
                    <th>Estimate #</th>
                    <th>Client</th>
                    <th>Title</th>
                    <th>Issue Date</th>
                    <th>Expiry Date</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="8" class="text-center text-muted">Loading estimates...</td>
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

<!-- Create Estimate Modal -->
<div class="modal modal-lg" id="estimate-modal">
    <div class="modal-header">
        <h3 class="modal-title">New Estimate</h3>
        <button class="modal-close" onclick="Modal.close('estimate-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="estimate-form">
        <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
            <!-- Client & Project Info -->
            <div class="form-section">
                <h4 class="form-section-title">Basic Information</h4>
                <div class="grid grid-cols-2 gap-4">
                    <!-- Client Selection with Quick Create -->
                    <div class="form-group">
                        <div class="flex items-center justify-between mb-1">
                            <label class="form-label required mb-0">Client</label>
                            <button type="button" class="btn btn-link btn-sm" onclick="toggleQuickCreateClient()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                                New Client
                            </button>
                        </div>
                        <select class="form-select" name="client_id" id="client-select" required>
                            <option value="">Select Client</option>
                        </select>
                        <!-- Quick Create Client Form -->
                        <div id="quick-create-client" class="quick-create-form" style="display: none;">
                            <div class="quick-create-header">
                                <span>Quick Create Client</span>
                                <button type="button" class="btn btn-icon btn-xs btn-ghost"
                                    onclick="toggleQuickCreateClient()">×</button>
                            </div>
                            <div class="quick-create-body">
                                <div class="form-group mb-2">
                                    <input type="text" class="form-input form-input-sm" id="new-client-name"
                                        placeholder="Client Name *" required>
                                </div>
                                <div class="grid grid-cols-2 gap-2 mb-2">
                                    <input type="email" class="form-input form-input-sm" id="new-client-email"
                                        placeholder="Email">
                                    <input type="tel" class="form-input form-input-sm" id="new-client-phone"
                                        placeholder="Phone">
                                </div>
                                <div class="form-group mb-2">
                                    <input type="text" class="form-input form-input-sm" id="new-client-address"
                                        placeholder="Address">
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" class="btn btn-sm btn-secondary flex-1"
                                        onclick="toggleQuickCreateClient()">Cancel</button>
                                    <button type="button" class="btn btn-sm btn-primary flex-1"
                                        onclick="createQuickClient()">Create Client</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Selection with Quick Create -->
                    <div class="form-group">
                        <div class="flex items-center justify-between mb-1">
                            <label class="form-label mb-0">Project</label>
                            <button type="button" class="btn btn-link btn-sm" onclick="toggleQuickCreateProject()"
                                id="new-project-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                                New Project
                            </button>
                        </div>
                        <select class="form-select" name="project_id" id="project-select">
                            <option value="">No Project</option>
                        </select>
                        <!-- Quick Create Project Form -->
                        <div id="quick-create-project" class="quick-create-form" style="display: none;">
                            <div class="quick-create-header">
                                <span>Quick Create Project</span>
                                <button type="button" class="btn btn-icon btn-xs btn-ghost"
                                    onclick="toggleQuickCreateProject()">×</button>
                            </div>
                            <div class="quick-create-body">
                                <div class="form-group mb-2">
                                    <input type="text" class="form-input form-input-sm" id="new-project-name"
                                        placeholder="Project Name *" required>
                                </div>
                                <div class="grid grid-cols-2 gap-2 mb-2">
                                    <select class="form-select form-select-sm" id="new-project-type">
                                        <option value="">Project Type</option>
                                        <option value="residential">Residential</option>
                                        <option value="commercial">Commercial</option>
                                        <option value="industrial">Industrial</option>
                                        <option value="renovation">Renovation</option>
                                        <option value="new_construction">New Construction</option>
                                    </select>
                                    <select class="form-select form-select-sm" id="new-project-status">
                                        <option value="planning">Planning</option>
                                        <option value="active">Active</option>
                                        <option value="on_hold">On Hold</option>
                                    </select>
                                </div>
                                <div class="form-group mb-2">
                                    <input type="text" class="form-input form-input-sm" id="new-project-address"
                                        placeholder="Project Address">
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" class="btn btn-sm btn-secondary flex-1"
                                        onclick="toggleQuickCreateProject()">Cancel</button>
                                    <button type="button" class="btn btn-sm btn-primary flex-1"
                                        onclick="createQuickProject()">Create Project</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label required">Estimate Title</label>
                    <input type="text" class="form-input" name="title" placeholder="e.g. Kitchen Renovation Estimate"
                        required>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label required">Issue Date</label>
                        <input type="date" class="form-input" name="issue_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valid Until</label>
                        <input type="date" class="form-input" name="expiry_date"
                            value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estimate Type</label>
                        <select class="form-select" name="estimate_type" id="estimate-type">
                            <option value="standard">Standard</option>
                            <option value="detailed">Detailed (with categories)</option>
                            <option value="labor_materials">Labor & Materials</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Line Items Section -->
            <div class="form-section">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="form-section-title mb-0">Line Items</h4>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addEstimateCategory()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                            </svg>
                            Add Category
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addEstimateLineItem()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            Add Item
                        </button>
                    </div>
                </div>

                <div class="line-items-container" id="estimate-line-items">
                    <!-- Line item header -->
                    <div class="line-item-header">
                        <div class="line-item-desc">Description</div>
                        <div class="line-item-qty">Qty</div>
                        <div class="line-item-unit">Unit</div>
                        <div class="line-item-rate">Rate</div>
                        <div class="line-item-amount">Amount</div>
                        <div class="line-item-action"></div>
                    </div>
                    <!-- Default first line item -->
                    <div class="line-item" data-index="0">
                        <input type="text" class="form-input line-item-desc" name="items[0][description]"
                            placeholder="Item description">
                        <input type="number" class="form-input line-item-qty" name="items[0][quantity]" value="1"
                            min="0" step="any" onchange="calculateEstimateLineTotal(this)">
                        <select class="form-select line-item-unit" name="items[0][unit]">
                            <option value="each">Each</option>
                            <option value="hour">Hour</option>
                            <option value="day">Day</option>
                            <option value="sqft">Sq Ft</option>
                            <option value="lnft">Ln Ft</option>
                            <option value="lot">Lot</option>
                            <option value="set">Set</option>
                        </select>
                        <input type="number" class="form-input line-item-rate" name="items[0][unit_price]" value="0"
                            step="0.01" onchange="calculateEstimateLineTotal(this)">
                        <div class="line-item-amount">$0.00</div>
                        <button type="button" class="btn btn-icon btn-sm btn-ghost"
                            onclick="removeEstimateLineItem(this)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pricing Section -->
            <div class="form-section">
                <h4 class="form-section-title">Pricing</h4>
                <div class="pricing-summary">
                    <div class="pricing-row">
                        <span>Subtotal</span>
                        <span id="estimate-subtotal">$0.00</span>
                    </div>
                    <div class="pricing-row">
                        <div class="flex items-center gap-2">
                            <span>Discount</span>
                            <select class="form-select form-select-sm" name="discount_type" id="discount-type"
                                style="width: 90px;">
                                <option value="fixed">$</option>
                                <option value="percent">%</option>
                            </select>
                            <input type="number" class="form-input form-input-sm" name="discount_value"
                                id="discount-value" value="0" min="0" step="0.01" style="width: 80px;"
                                onchange="calculateEstimateTotals()">
                        </div>
                        <span id="estimate-discount">-$0.00</span>
                    </div>
                    <div class="pricing-row">
                        <div class="flex items-center gap-2">
                            <span>Tax Rate</span>
                            <input type="number" class="form-input form-input-sm" name="tax_rate" id="tax-rate"
                                value="0" min="0" step="0.01" style="width: 70px;" onchange="calculateEstimateTotals()">
                            <span>%</span>
                        </div>
                        <span id="estimate-tax">$0.00</span>
                    </div>
                    <div class="pricing-row pricing-total">
                        <span>Total</span>
                        <span id="estimate-total">$0.00</span>
                    </div>
                </div>
            </div>

            <!-- Terms & Notes Section -->
            <div class="form-section">
                <h4 class="form-section-title">Terms & Notes</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Payment Terms</label>
                        <select class="form-select" name="payment_terms">
                            <option value="">Select payment terms</option>
                            <option value="50_deposit">50% Deposit, 50% on Completion</option>
                            <option value="due_on_completion">Due on Completion</option>
                            <option value="net_15">Net 15</option>
                            <option value="net_30">Net 30</option>
                            <option value="progress">Progress Payments</option>
                            <option value="custom">Custom (specify in notes)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Warranty Period</label>
                        <select class="form-select" name="warranty_period">
                            <option value="">No warranty</option>
                            <option value="30_days">30 Days</option>
                            <option value="90_days">90 Days</option>
                            <option value="1_year">1 Year</option>
                            <option value="2_years">2 Years</option>
                            <option value="custom">Custom (specify in notes)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Scope of Work</label>
                    <textarea class="form-input" name="scope_of_work" rows="2"
                        placeholder="Describe what is included in this estimate..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Exclusions</label>
                    <textarea class="form-input" name="exclusions" rows="2"
                        placeholder="List any items not included in this estimate..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Terms & Conditions</label>
                    <textarea class="form-input" name="terms" rows="2"
                        placeholder="Standard terms and conditions...">This estimate is valid for 30 days. Prices may vary based on actual site conditions. Changes to scope will require a change order.</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Internal Notes</label>
                    <textarea class="form-input" name="notes" rows="2"
                        placeholder="Internal notes (not visible to client)..."></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="flex items-center gap-2 mr-auto">
                <input type="checkbox" id="send-after-create" name="send_after_create">
                <label for="send-after-create" class="text-sm">Send to client after creating</label>
            </div>
            <button type="button" class="btn btn-secondary" onclick="Modal.close('estimate-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Estimate</button>
        </div>
    </form>
</div>


<script>
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};

    document.addEventListener('DOMContentLoaded', function () {
        loadEstimates();
        loadClients();
        loadProjects();
        loadSummary();

        document.getElementById('estimate-form').addEventListener('submit', async function (e) {
            e.preventDefault();

            // Collect basic form data
            const formData = new FormData(this);
            const data = {};

            // Process basic fields
            for (const [key, value] of formData.entries()) {
                if (!key.startsWith('items[') && !key.startsWith('categories[')) {
                    data[key] = value;
                }
            }

            // Collect line items
            const lineItems = [];
            document.querySelectorAll('#estimate-line-items .line-item').forEach((item, index) => {
                const description = item.querySelector('.line-item-desc').value;
                const quantity = parseFloat(item.querySelector('.line-item-qty').value) || 0;
                const unit = item.querySelector('.line-item-unit').value;
                const unitPrice = parseFloat(item.querySelector('.line-item-rate').value) || 0;

                if (description || quantity > 0 || unitPrice > 0) {
                    lineItems.push({
                        description,
                        quantity,
                        unit,
                        unit_price: unitPrice,
                        amount: quantity * unitPrice
                    });
                }
            });

            data.items = lineItems;

            // Calculate totals
            const subtotal = lineItems.reduce((sum, item) => sum + (item.amount || 0), 0);
            const discountType = document.getElementById('discount-type').value;
            const discountValue = parseFloat(document.getElementById('discount-value').value) || 0;
            const taxRate = parseFloat(document.getElementById('tax-rate').value) || 0;

            let discount = 0;
            if (discountType === 'percent') {
                discount = subtotal * (discountValue / 100);
            } else {
                discount = discountValue;
            }

            const afterDiscount = subtotal - discount;
            const tax = afterDiscount * (taxRate / 100);
            const total = afterDiscount + tax;

            data.subtotal = subtotal;
            data.discount_type = discountType;
            data.discount_value = discountValue;
            data.discount_amount = discount;
            data.tax_amount = tax;
            data.total_amount = total;

            try {
                const response = await ERP.api.post('/estimates', data);
                if (response.success) {
                    Modal.close('estimate-modal');
                    resetEstimateForm();

                    // Check if send after create is checked
                    if (document.getElementById('send-after-create').checked) {
                        await ERP.api.post(`/estimates/${response.data.id}/send`, {});
                        ERP.toast.success('Estimate created and sent!');
                    }

                    // Navigate to the new estimate detail page
                    window.location.href = window.location.pathname + '/' + response.data.id;
                }
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        document.getElementById('search-input').addEventListener('input', debounce(applyFilters, 300));
        document.getElementById('status-filter').addEventListener('change', applyFilters);
        document.getElementById('client-filter').addEventListener('change', applyFilters);

        document.getElementById('prev-btn').addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; loadEstimates(); }
        });
        document.getElementById('next-btn').addEventListener('click', () => {
            if (currentPage < totalPages) { currentPage++; loadEstimates(); }
        });
    });

    async function loadEstimates() {
        const params = new URLSearchParams({ page: currentPage, per_page: 15, ...currentFilters });

        try {
            const response = await ERP.api.get('/estimates?' + params);
            if (response.success) {
                renderEstimates(response.data);
                totalPages = response.meta.total_pages || 1;
                updatePagination(response.meta);
            }
        } catch (error) {
            ERP.toast.error('Failed to load estimates');
        }
    }

    async function loadClients() {
        try {
            const response = await ERP.api.get('/clients?per_page=100');
            if (response.success) {
                const options = response.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
                document.getElementById('client-select').innerHTML = '<option value="">Select Client</option>' + options;
                document.getElementById('client-filter').innerHTML = '<option value="">All Clients</option>' + options;
            }
        } catch (error) { console.error(error); }
    }

    async function loadProjects() {
        try {
            const response = await ERP.api.get('/projects?per_page=100');
            if (response.success) {
                const options = response.data.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
                document.getElementById('project-select').innerHTML = '<option value="">No Project</option>' + options;
            }
        } catch (error) { console.error(error); }
    }

    async function loadSummary() {
        try {
            const response = await ERP.api.get('/estimates/summary');
            if (response.success) {
                const d = response.data;
                document.getElementById('total-estimates').textContent = d.total_count || 0;
                document.getElementById('pending-value').textContent = formatCurrency(
                    parseFloat(d.total_value || 0) - parseFloat(d.approved_value || 0) - parseFloat(d.converted_value || 0)
                );
                document.getElementById('approved-value').textContent = formatCurrency(d.approved_value || 0);
                document.getElementById('converted-value').textContent = formatCurrency(d.converted_value || 0);
            }
        } catch (error) { console.error(error); }
    }

    function renderEstimates(estimates) {
        const tbody = document.querySelector('#estimates-table tbody');

        if (!estimates || estimates.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No estimates found</td></tr>';
            return;
        }

        tbody.innerHTML = estimates.map(est => `
            <tr class="cursor-pointer" onclick="window.location.href=window.location.pathname+'/${est.id}'">
                <td><strong>${est.estimate_number}</strong></td>
                <td>${est.client_name || '-'}</td>
                <td>${est.title || '-'}</td>
                <td>${formatDate(est.issue_date)}</td>
                <td>${est.expiry_date ? formatDate(est.expiry_date) : '-'}</td>
                <td class="text-right font-medium">${formatCurrency(est.total_amount)}</td>
                <td><span class="badge badge-${getStatusColor(est.status)}">${est.status}</span></td>
                <td>
                    <div class="flex gap-1" onclick="event.stopPropagation()">
                        ${est.status === 'approved' ? `
                            <button class="btn btn-sm btn-success" onclick="convertToInvoice(${est.id})" title="Convert to Invoice">📃</button>
                        ` : ''}
                        <button class="btn btn-sm btn-secondary" onclick="deleteEstimate(${est.id})" title="Delete">🗑</button>
                    </div>
                </td>
            </tr>
        `).join('');
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
        const client = document.getElementById('client-filter').value;
        if (search) currentFilters.search = search;
        if (status) currentFilters.status = status;
        if (client) currentFilters.client_id = client;
        currentPage = 1;
        loadEstimates();
    }

    function clearFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('status-filter').value = '';
        document.getElementById('client-filter').value = '';
        currentFilters = {};
        currentPage = 1;
        loadEstimates();
    }

    async function convertToInvoice(id) {
        if (!confirm('Convert this estimate to an invoice?')) return;
        try {
            const response = await ERP.api.post('/estimates/' + id + '/convert', {});
            if (response.success) {
                ERP.toast.success('Converted to Invoice #' + response.data.invoice_number);
                loadEstimates();
                loadSummary();
            }
        } catch (error) { ERP.toast.error(error.message); }
    }

    async function deleteEstimate(id) {
        if (!confirm('Delete this estimate?')) return;
        try {
            await ERP.api.delete('/estimates/' + id);
            ERP.toast.success('Estimate deleted');
            loadEstimates();
            loadSummary();
        } catch (error) { ERP.toast.error(error.message); }
    }

    function getStatusColor(status) {
        return { draft: 'secondary', sent: 'warning', approved: 'success', rejected: 'error', expired: 'secondary', converted: 'primary' }[status] || 'secondary';
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(amount);
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function debounce(fn, delay) {
        let timer;
        return function (...args) { clearTimeout(timer); timer = setTimeout(() => fn.apply(this, args), delay); };
    }

    // ===== Line Items Functions =====
    let estimateLineItemIndex = 1;

    function addEstimateLineItem() {
        const container = document.getElementById('estimate-line-items');
        const lineItem = document.createElement('div');
        lineItem.className = 'line-item';
        lineItem.dataset.index = estimateLineItemIndex;

        lineItem.innerHTML = `
            <input type="text" class="form-input line-item-desc" name="items[${estimateLineItemIndex}][description]" placeholder="Item description">
            <input type="number" class="form-input line-item-qty" name="items[${estimateLineItemIndex}][quantity]" value="1" min="0" step="any" onchange="calculateEstimateLineTotal(this)">
            <select class="form-select line-item-unit" name="items[${estimateLineItemIndex}][unit]">
                <option value="each">Each</option>
                <option value="hour">Hour</option>
                <option value="day">Day</option>
                <option value="sqft">Sq Ft</option>
                <option value="lnft">Ln Ft</option>
                <option value="lot">Lot</option>
                <option value="set">Set</option>
            </select>
            <input type="number" class="form-input line-item-rate" name="items[${estimateLineItemIndex}][unit_price]" value="0" step="0.01" onchange="calculateEstimateLineTotal(this)">
            <div class="line-item-amount">$0.00</div>
            <button type="button" class="btn btn-icon btn-sm btn-ghost" onclick="removeEstimateLineItem(this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        `;

        container.appendChild(lineItem);
        estimateLineItemIndex++;

        // Focus the description field
        lineItem.querySelector('.line-item-desc').focus();
    }

    function removeEstimateLineItem(button) {
        const lineItem = button.closest('.line-item');
        const container = document.getElementById('estimate-line-items');
        const lineItems = container.querySelectorAll('.line-item');

        if (lineItems.length > 1) {
            lineItem.remove();
            calculateEstimateTotals();
        } else {
            ERP.toast.warning('At least one line item is required');
        }
    }

    function addEstimateCategory() {
        const container = document.getElementById('estimate-line-items');
        const category = document.createElement('div');
        category.className = 'line-item-category';

        category.innerHTML = `
            <input type="text" class="form-input category-name" placeholder="Category name (e.g. Materials, Labor, Equipment)" 
                   name="categories[]" style="flex: 1; font-weight: 600;">
            <button type="button" class="btn btn-icon btn-sm btn-ghost" onclick="removeEstimateCategory(this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        `;

        container.appendChild(category);
        category.querySelector('.category-name').focus();
    }

    function removeEstimateCategory(button) {
        button.closest('.line-item-category').remove();
    }

    function calculateEstimateLineTotal(input) {
        const lineItem = input.closest('.line-item');
        const qty = parseFloat(lineItem.querySelector('.line-item-qty').value) || 0;
        const rate = parseFloat(lineItem.querySelector('.line-item-rate').value) || 0;
        const amount = qty * rate;

        lineItem.querySelector('.line-item-amount').textContent = formatCurrency(amount);
        calculateEstimateTotals();
    }

    function calculateEstimateTotals() {
        const lineItems = document.querySelectorAll('#estimate-line-items .line-item');
        let subtotal = 0;

        lineItems.forEach(item => {
            const qty = parseFloat(item.querySelector('.line-item-qty').value) || 0;
            const rate = parseFloat(item.querySelector('.line-item-rate').value) || 0;
            subtotal += qty * rate;
        });

        const discountType = document.getElementById('discount-type').value;
        const discountValue = parseFloat(document.getElementById('discount-value').value) || 0;
        const taxRate = parseFloat(document.getElementById('tax-rate').value) || 0;

        let discount = 0;
        if (discountType === 'percent') {
            discount = subtotal * (discountValue / 100);
        } else {
            discount = discountValue;
        }

        const afterDiscount = subtotal - discount;
        const tax = afterDiscount * (taxRate / 100);
        const total = afterDiscount + tax;

        document.getElementById('estimate-subtotal').textContent = formatCurrency(subtotal);
        document.getElementById('estimate-discount').textContent = '-' + formatCurrency(discount);
        document.getElementById('estimate-tax').textContent = formatCurrency(tax);
        document.getElementById('estimate-total').textContent = formatCurrency(total);
    }

    function resetEstimateForm() {
        document.getElementById('estimate-form').reset();

        // Reset line items to just one
        const container = document.getElementById('estimate-line-items');
        const lineItems = container.querySelectorAll('.line-item');
        const categories = container.querySelectorAll('.line-item-category');

        categories.forEach(cat => cat.remove());
        lineItems.forEach((item, index) => {
            if (index > 0) item.remove();
        });

        // Reset first line item
        if (lineItems[0]) {
            lineItems[0].querySelector('.line-item-desc').value = '';
            lineItems[0].querySelector('.line-item-qty').value = '1';
            lineItems[0].querySelector('.line-item-rate').value = '0';
            lineItems[0].querySelector('.line-item-amount').textContent = '$0.00';
        }

        estimateLineItemIndex = 1;
        calculateEstimateTotals();
    }

    // Add discount type change listener
    document.getElementById('discount-type')?.addEventListener('change', calculateEstimateTotals);

    // ===== Quick Create Client Functions =====
    function toggleQuickCreateClient() {
        const form = document.getElementById('quick-create-client');
        const isVisible = form.style.display !== 'none';
        form.style.display = isVisible ? 'none' : 'block';

        if (!isVisible) {
            document.getElementById('new-client-name').focus();
        } else {
            // Clear form when closing
            document.getElementById('new-client-name').value = '';
            document.getElementById('new-client-email').value = '';
            document.getElementById('new-client-phone').value = '';
            document.getElementById('new-client-address').value = '';
        }
    }

    async function createQuickClient() {
        const name = document.getElementById('new-client-name').value.trim();
        if (!name) {
            ERP.toast.error('Client name is required');
            return;
        }

        const clientData = {
            name: name,
            email: document.getElementById('new-client-email').value.trim(),
            phone: document.getElementById('new-client-phone').value.trim(),
            address: document.getElementById('new-client-address').value.trim(),
            status: 'active'
        };

        try {
            const response = await ERP.api.post('/clients', clientData);
            if (response.success) {
                ERP.toast.success('Client created successfully!');

                // Add new client to dropdown and select it
                const clientSelect = document.getElementById('client-select');
                const newOption = document.createElement('option');
                newOption.value = response.data.id;
                newOption.textContent = response.data.name;
                clientSelect.appendChild(newOption);
                clientSelect.value = response.data.id;

                // Also add to filter dropdown
                const filterSelect = document.getElementById('client-filter');
                if (filterSelect) {
                    const filterOption = document.createElement('option');
                    filterOption.value = response.data.id;
                    filterOption.textContent = response.data.name;
                    filterSelect.appendChild(filterOption);
                }

                // Close the quick create form
                toggleQuickCreateClient();

                // Trigger change event to update project list if needed
                clientSelect.dispatchEvent(new Event('change'));
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to create client');
        }
    }

    // ===== Quick Create Project Functions =====
    function toggleQuickCreateProject() {
        const clientId = document.getElementById('client-select').value;
        if (!clientId) {
            ERP.toast.warning('Please select a client first before creating a project');
            return;
        }

        const form = document.getElementById('quick-create-project');
        const isVisible = form.style.display !== 'none';
        form.style.display = isVisible ? 'none' : 'block';

        if (!isVisible) {
            document.getElementById('new-project-name').focus();
        } else {
            // Clear form when closing
            document.getElementById('new-project-name').value = '';
            document.getElementById('new-project-type').value = '';
            document.getElementById('new-project-status').value = 'planning';
            document.getElementById('new-project-address').value = '';
        }
    }

    async function createQuickProject() {
        const name = document.getElementById('new-project-name').value.trim();
        const clientId = document.getElementById('client-select').value;

        if (!name) {
            ERP.toast.error('Project name is required');
            return;
        }

        if (!clientId) {
            ERP.toast.error('Please select a client first');
            return;
        }

        const projectData = {
            name: name,
            client_id: clientId,
            project_type: document.getElementById('new-project-type').value,
            status: document.getElementById('new-project-status').value || 'planning',
            address: document.getElementById('new-project-address').value.trim(),
            start_date: new Date().toISOString().split('T')[0]
        };

        try {
            const response = await ERP.api.post('/projects', projectData);
            if (response.success) {
                ERP.toast.success('Project created and assigned to client!');

                // Add new project to dropdown and select it
                const projectSelect = document.getElementById('project-select');
                const newOption = document.createElement('option');
                newOption.value = response.data.id;
                newOption.textContent = response.data.name;
                projectSelect.appendChild(newOption);
                projectSelect.value = response.data.id;

                // Close the quick create form
                toggleQuickCreateProject();
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to create project');
        }
    }

    // Show/hide new project button based on client selection
    document.getElementById('client-select')?.addEventListener('change', function () {
        const newProjectBtn = document.getElementById('new-project-btn');
        if (newProjectBtn) {
            newProjectBtn.style.opacity = this.value ? '1' : '0.5';
        }

        // Close quick create project form if open and client changes
        const projectForm = document.getElementById('quick-create-project');
        if (projectForm && projectForm.style.display !== 'none') {
            projectForm.style.display = 'none';
        }
    });
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

    .text-primary {
        color: var(--primary-500);
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .cursor-pointer:hover {
        background: var(--bg-hover);
    }

    /* Large Modal */
    .modal.modal-lg {
        max-width: 900px;
        width: 95%;
    }

    /* Form Sections */
    .form-section {
        padding: var(--space-4);
        margin-bottom: var(--space-4);
        background: var(--bg-secondary);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
    }

    .form-section:last-child {
        margin-bottom: 0;
    }

    .form-section-title {
        font-size: var(--text-base);
        font-weight: 600;
        margin-bottom: var(--space-3);
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }

    .form-section-title.mb-0 {
        margin-bottom: 0;
    }

    /* Line Items */
    .line-items-container {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .line-item-header {
        display: grid;
        grid-template-columns: 1fr 80px 90px 100px 100px 40px;
        gap: var(--space-2);
        padding: var(--space-2) var(--space-3);
        background: var(--bg-tertiary);
        font-size: var(--text-xs);
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .line-item {
        display: grid;
        grid-template-columns: 1fr 80px 90px 100px 100px 40px;
        gap: var(--space-2);
        padding: var(--space-2) var(--space-3);
        border-bottom: 1px solid var(--border-color);
        align-items: center;
    }

    .line-item:last-child {
        border-bottom: none;
    }

    .line-item:hover {
        background: var(--bg-hover);
    }

    .line-item input,
    .line-item select {
        padding: var(--space-1) var(--space-2);
        font-size: var(--text-sm);
    }

    .line-item .line-item-amount {
        text-align: right;
        font-weight: 500;
        color: var(--text-primary);
    }

    .line-item-category {
        display: flex;
        gap: var(--space-2);
        padding: var(--space-2) var(--space-3);
        background: var(--primary-50);
        border-bottom: 1px solid var(--border-color);
        align-items: center;
    }

    .line-item-category .category-name {
        border: none;
        background: transparent;
        font-weight: 600;
        color: var(--primary-600);
    }

    /* Pricing Summary */
    .pricing-summary {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .pricing-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-3);
        border-bottom: 1px solid var(--border-color);
    }

    .pricing-row:last-child {
        border-bottom: none;
    }

    .pricing-row.pricing-total {
        background: var(--bg-tertiary);
        font-weight: 700;
        font-size: var(--text-lg);
    }

    .pricing-row.pricing-total span:last-child {
        color: var(--primary-600);
    }

    /* Small form controls */
    .form-select-sm,
    .form-input-sm {
        padding: var(--space-1) var(--space-2);
        font-size: var(--text-sm);
        height: auto;
    }

    /* Text utilities */
    .text-sm {
        font-size: var(--text-sm);
    }

    .mr-auto {
        margin-right: auto;
    }

    /* Checkbox styling */
    #send-after-create {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }

    #send-after-create+label {
        cursor: pointer;
        user-select: none;
    }

    /* Quick Create Forms */
    .quick-create-form {
        margin-top: var(--space-3);
        background: var(--bg-primary);
        border: 1px solid var(--primary-200);
        border-radius: var(--radius-md);
        overflow: hidden;
        animation: slideDown 0.2s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .quick-create-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-2) var(--space-3);
        background: var(--primary-50);
        border-bottom: 1px solid var(--primary-100);
        font-weight: 600;
        font-size: var(--text-sm);
        color: var(--primary-700);
    }

    .quick-create-body {
        padding: var(--space-3);
    }

    .quick-create-body .form-group {
        margin-bottom: 0;
    }

    .quick-create-body .mb-2 {
        margin-bottom: var(--space-2);
    }

    /* Button Link Style */
    .btn-link {
        background: none;
        border: none;
        color: var(--primary-500);
        padding: var(--space-1) var(--space-2);
        font-size: var(--text-sm);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: var(--space-1);
        transition: color 0.15s;
    }

    .btn-link:hover {
        color: var(--primary-600);
        text-decoration: underline;
    }

    .btn-xs {
        padding: 2px 6px;
        font-size: 14px;
        line-height: 1;
    }

    .flex-1 {
        flex: 1;
    }

    .mb-0 {
        margin-bottom: 0 !important;
    }

    .mb-1 {
        margin-bottom: var(--space-1);
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
