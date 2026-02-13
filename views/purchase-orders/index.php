<?php
$title = 'Purchase Orders';
$page = 'purchase-orders';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Purchase Orders</h1>
        <p class="text-muted text-sm">Manage vendor orders and procurement</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('po-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        New Purchase Order
    </button>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-4 mb-6">
    <div class="card stat-card">
        <div class="stat-value" id="total-pos">0</div>
        <div class="stat-label">Total Orders</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-warning" id="draft-count">0</div>
        <div class="stat-label">Drafts</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-primary" id="pending-value">$0</div>
        <div class="stat-label">Pending Value</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-success" id="received-count">0</div>
        <div class="stat-label">Received</div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4 items-center">
        <div class="flex-1">
            <input type="text" class="form-input" id="search-input" placeholder="Search orders...">
        </div>
        <select class="form-select" id="status-filter" style="width: 140px;">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="sent">Sent</option>
            <option value="received">Received</option>
        </select>
        <select class="form-select" id="vendor-filter" style="width: 180px;">
            <option value="">All Vendors</option>
        </select>
        <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
    </div>
</div>

<!-- Purchase Orders Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="po-table">
            <thead>
                <tr>
                    <th>PO #</th>
                    <th>Vendor</th>
                    <th>Project</th>
                    <th>Order Date</th>
                    <th>Expected Date</th>
                    <th class="text-right">Total</th>
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

<!-- Pagination -->
<div class="flex justify-between items-center mt-6">
    <div class="text-sm text-muted" id="pagination-info">Showing 0 of 0</div>
    <div class="flex gap-2">
        <button class="btn btn-secondary btn-sm" id="prev-btn" disabled>Previous</button>
        <button class="btn btn-secondary btn-sm" id="next-btn" disabled>Next</button>
    </div>
</div>

<!-- Create PO Modal -->
<div class="modal" id="po-modal">
    <div class="modal-header">
        <h3 class="modal-title">New Purchase Order</h3>
        <button class="modal-close" onclick="Modal.close('po-modal')">×</button>
    </div>
    <form id="po-form">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Vendor</label>
                    <select class="form-select" name="vendor_id" id="vendor-select" required>
                        <option value="">Select Vendor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Project</label>
                    <select class="form-select" name="project_id" id="project-select">
                        <option value="">No Project</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="form-group">
                    <label class="form-label required">Order Date</label>
                    <input type="date" class="form-input" name="order_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Expected Date</label>
                    <input type="date" class="form-input" name="expected_date">
                </div>
                <div class="form-group">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" step="0.01" class="form-input" name="tax_rate" value="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Shipping Address</label>
                <textarea class="form-input" name="shipping_address" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-input" name="notes" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('po-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Order</button>
        </div>
    </form>
</div>

<script>
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};

    document.addEventListener('DOMContentLoaded', function () {
        loadPurchaseOrders();
        loadVendors();
        loadProjects();
        loadSummary();

        document.getElementById('po-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                const response = await ERP.api.post('/purchase-orders', data);
                if (response.success) {
                    Modal.close('po-modal');
                    this.reset();
                    window.location.href = window.location.pathname + '/' + response.data.id;
                }
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        document.getElementById('search-input').addEventListener('input', debounce(applyFilters, 300));
        document.getElementById('status-filter').addEventListener('change', applyFilters);
        document.getElementById('vendor-filter').addEventListener('change', applyFilters);

        document.getElementById('prev-btn').addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; loadPurchaseOrders(); }
        });
        document.getElementById('next-btn').addEventListener('click', () => {
            if (currentPage < totalPages) { currentPage++; loadPurchaseOrders(); }
        });
    });

    async function loadPurchaseOrders() {
        const params = new URLSearchParams({ page: currentPage, per_page: 15, ...currentFilters });

        try {
            const response = await ERP.api.get('/purchase-orders?' + params);
            if (response.success) {
                renderOrders(response.data);
                totalPages = response.meta.total_pages || 1;
                updatePagination(response.meta);
            }
        } catch (error) {
            ERP.toast.error('Failed to load orders');
        }
    }

    async function loadVendors() {
        try {
            const response = await ERP.api.get('/vendors?per_page=100');
            if (response.success) {
                const options = response.data.map(v => `<option value="${v.id}">${v.name}</option>`).join('');
                document.getElementById('vendor-select').innerHTML = '<option value="">Select Vendor</option>' + options;
                document.getElementById('vendor-filter').innerHTML = '<option value="">All Vendors</option>' + options;
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
            const response = await ERP.api.get('/purchase-orders/summary');
            if (response.success) {
                const d = response.data;
                document.getElementById('total-pos').textContent = d.total_count || 0;
                document.getElementById('draft-count').textContent = d.draft_count || 0;
                document.getElementById('pending-value').textContent = formatCurrency(d.pending_value || 0);
                document.getElementById('received-count').textContent = d.received_count || 0;
            }
        } catch (error) { console.error(error); }
    }

    function renderOrders(orders) {
        const tbody = document.querySelector('#po-table tbody');

        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No purchase orders found</td></tr>';
            return;
        }

        tbody.innerHTML = orders.map(po => `
            <tr class="cursor-pointer" onclick="window.location.href=window.location.pathname+'/${po.id}'">
                <td><strong>${po.po_number}</strong></td>
                <td>${po.vendor_name || '-'}</td>
                <td>${po.project_name || '-'}</td>
                <td>${formatDate(po.order_date)}</td>
                <td>${po.expected_date ? formatDate(po.expected_date) : '-'}</td>
                <td class="text-right font-medium">${formatCurrency(po.total_amount)}</td>
                <td><span class="badge badge-${getStatusColor(po.status)}">${po.status}</span></td>
                <td>
                    <div class="flex gap-1" onclick="event.stopPropagation()">
                        ${po.status === 'sent' ? `
                            <button class="btn btn-sm btn-success" onclick="receiveOrder(${po.id})" title="Mark Received">✓</button>
                        ` : ''}
                        <button class="btn btn-sm btn-secondary" onclick="deletePO(${po.id})" title="Delete">🗑</button>
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
        const vendor = document.getElementById('vendor-filter').value;
        if (search) currentFilters.search = search;
        if (status) currentFilters.status = status;
        if (vendor) currentFilters.vendor_id = vendor;
        currentPage = 1;
        loadPurchaseOrders();
    }

    function clearFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('status-filter').value = '';
        document.getElementById('vendor-filter').value = '';
        currentFilters = {};
        currentPage = 1;
        loadPurchaseOrders();
    }

    async function receiveOrder(id) {
        if (!confirm('Mark this order as received?')) return;
        try {
            const response = await ERP.api.post('/purchase-orders/' + id + '/receive', {});
            if (response.success) {
                ERP.toast.success('Order marked as received');
                loadPurchaseOrders();
                loadSummary();
            }
        } catch (error) { ERP.toast.error(error.message); }
    }

    async function deletePO(id) {
        if (!confirm('Delete this purchase order?')) return;
        try {
            await ERP.api.delete('/purchase-orders/' + id);
            ERP.toast.success('Order deleted');
            loadPurchaseOrders();
            loadSummary();
        } catch (error) { ERP.toast.error(error.message); }
    }

    function getStatusColor(status) {
        return { draft: 'secondary', sent: 'warning', received: 'success' }[status] || 'secondary';
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(amount);
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
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
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>