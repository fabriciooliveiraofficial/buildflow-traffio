<?php
$title = 'Estimate Details';
$page = 'estimates';
$estimateId = $GLOBALS['params']['id'] ?? null;

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= dirname($_SERVER['REQUEST_URI']) ?>" class="btn btn-secondary btn-sm">← Back</a>
        <div>
            <h1 class="text-2xl font-bold" id="estimate-title">Estimate #</h1>
            <p class="text-muted text-sm" id="estimate-client"></p>
        </div>
    </div>
    <div class="flex gap-2" id="action-buttons">
        <!-- Buttons populated by JS based on status -->
    </div>
</div>

<div class="grid grid-cols-12 gap-6">
    <!-- Main Content -->
    <div class="col-span-8">
        <!-- Estimate Info Card -->
        <div class="card mb-6">
            <div class="card-header flex justify-between">
                <h3>Estimate Details</h3>
                <span class="badge" id="status-badge">Draft</span>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <label class="text-muted text-sm">Client</label>
                        <div id="client-name" class="font-medium">-</div>
                    </div>
                    <div>
                        <label class="text-muted text-sm">Project</label>
                        <div id="project-name" class="font-medium">-</div>
                    </div>
                    <div>
                        <label class="text-muted text-sm">Issue Date</label>
                        <div id="issue-date" class="font-medium">-</div>
                    </div>
                    <div>
                        <label class="text-muted text-sm">Expiry Date</label>
                        <div id="expiry-date" class="font-medium">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="card mb-6">
            <div class="card-header flex justify-between">
                <h3>Line Items</h3>
                <button class="btn btn-sm btn-primary" onclick="Modal.open('item-modal')" id="add-item-btn">+ Add
                    Item</button>
            </div>
            <div class="card-body p-0">
                <table class="table" id="items-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right" style="width: 100px;">Qty</th>
                            <th class="text-right" style="width: 120px;">Unit Price</th>
                            <th class="text-right" style="width: 120px;">Total</th>
                            <th style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Loading items...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notes -->
        <div class="card">
            <div class="card-header">
                <h3>Notes</h3>
            </div>
            <div class="card-body">
                <p id="estimate-notes" class="text-muted">No notes</p>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-span-4">
        <div class="card sticky-top">
            <div class="card-header">
                <h3>Summary</h3>
            </div>
            <div class="card-body">
                <div class="flex justify-between py-2 border-b">
                    <span>Subtotal</span>
                    <strong id="subtotal">$0.00</strong>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span>Tax (<span id="tax-rate">0</span>%)</span>
                    <strong id="tax-amount">$0.00</strong>
                </div>
                <div class="flex justify-between py-3 text-lg">
                    <span><strong>Total</strong></span>
                    <strong id="total" class="text-primary">$0.00</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Item Modal -->
<div class="modal" id="item-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="item-modal-title">Add Item</h3>
        <button class="modal-close" onclick="Modal.close('item-modal')">×</button>
    </div>
    <form id="item-form">
        <input type="hidden" name="item_id" id="item-id">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Description</label>
                <input type="text" class="form-input" name="description" required>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" step="0.01" class="form-input" name="quantity" value="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <input type="text" class="form-input" name="unit" value="unit">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit Price</label>
                    <input type="number" step="0.01" class="form-input" name="unit_price" value="0">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('item-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Item</button>
        </div>
    </form>
</div>

<script>
    const estimateId = <?= json_encode($estimateId) ?>;
    let currentEstimate = null;
    let editingItemId = null;

    document.addEventListener('DOMContentLoaded', function () {
        if (!estimateId) {
            ERP.toast.error('Estimate not found');
            return;
        }
        loadEstimate();

        document.getElementById('item-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                if (editingItemId) {
                    await ERP.api.put(`/estimates/${estimateId}/items/${editingItemId}`, data);
                    ERP.toast.success('Item updated');
                } else {
                    await ERP.api.post(`/estimates/${estimateId}/items`, data);
                    ERP.toast.success('Item added');
                }
                Modal.close('item-modal');
                this.reset();
                editingItemId = null;
                loadEstimate();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadEstimate() {
        try {
            const response = await ERP.api.get('/estimates/' + estimateId);
            if (response.success) {
                currentEstimate = response.data;
                renderEstimate(response.data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load estimate');
        }
    }

    function renderEstimate(est) {
        document.getElementById('estimate-title').textContent = 'Estimate #' + est.estimate_number;
        document.getElementById('estimate-client').textContent = est.client_name || '';
        document.getElementById('client-name').textContent = est.client_name || '-';
        document.getElementById('project-name').textContent = est.project_name || 'No Project';
        document.getElementById('issue-date').textContent = formatDate(est.issue_date);
        document.getElementById('expiry-date').textContent = est.expiry_date ? formatDate(est.expiry_date) : '-';
        document.getElementById('estimate-notes').textContent = est.notes || 'No notes';

        // Status badge
        const badge = document.getElementById('status-badge');
        badge.textContent = est.status.charAt(0).toUpperCase() + est.status.slice(1);
        badge.className = 'badge badge-' + getStatusColor(est.status);

        // Summary
        document.getElementById('subtotal').textContent = formatCurrency(est.subtotal);
        document.getElementById('tax-rate').textContent = est.tax_rate || 0;
        document.getElementById('tax-amount').textContent = formatCurrency(est.tax_amount);
        document.getElementById('total').textContent = formatCurrency(est.total_amount);

        // Line items
        renderItems(est.items || []);

        // Action buttons
        renderActions(est);
    }

    function renderItems(items) {
        const tbody = document.querySelector('#items-table tbody');

        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No items yet. Add your first item.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(item => `
            <tr>
                <td>${item.description}</td>
                <td class="text-right">${item.quantity} ${item.unit || ''}</td>
                <td class="text-right">${formatCurrency(item.unit_price)}</td>
                <td class="text-right font-medium">${formatCurrency(item.total)}</td>
                <td>
                    <div class="flex gap-1">
                        <button class="btn btn-icon btn-sm" onclick="editItem(${item.id})" title="Edit">✎</button>
                        <button class="btn btn-icon btn-sm" onclick="deleteItem(${item.id})" title="Delete">🗑</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderActions(est) {
        const container = document.getElementById('action-buttons');
        let html = '';

        if (est.status === 'draft') {
            html += `<button class="btn btn-warning" onclick="sendEstimate()">📤 Send</button>`;
        }
        if (est.status === 'sent') {
            html += `<button class="btn btn-success" onclick="approveEstimate()">✓ Approve</button>`;
            html += `<button class="btn btn-error" onclick="rejectEstimate()">✗ Reject</button>`;
        }
        if (est.status === 'approved') {
            html += `<button class="btn btn-primary" onclick="convertToInvoice()">📃 Convert to Invoice</button>`;
        }
        if (est.status === 'converted' && est.converted_invoice_id) {
            html += `<a class="btn btn-secondary" href="${window.location.pathname.replace('/estimates/', '/invoices/').replace('/' + estimateId, '/' + est.converted_invoice_id)}">View Invoice</a>`;
        }

        // Hide add item button for converted estimates
        if (est.status === 'converted') {
            document.getElementById('add-item-btn').style.display = 'none';
        }

        container.innerHTML = html;
    }

    function editItem(itemId) {
        const item = currentEstimate.items.find(i => i.id == itemId);
        if (!item) return;

        editingItemId = itemId;
        document.getElementById('item-modal-title').textContent = 'Edit Item';
        document.getElementById('item-id').value = itemId;

        const form = document.getElementById('item-form');
        form.querySelector('[name="description"]').value = item.description;
        form.querySelector('[name="quantity"]').value = item.quantity;
        form.querySelector('[name="unit"]').value = item.unit || 'unit';
        form.querySelector('[name="unit_price"]').value = item.unit_price;

        Modal.open('item-modal');
    }

    async function deleteItem(itemId) {
        if (!confirm('Delete this item?')) return;
        try {
            await ERP.api.delete(`/estimates/${estimateId}/items/${itemId}`);
            ERP.toast.success('Item deleted');
            loadEstimate();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    async function sendEstimate() {
        try {
            await ERP.api.post(`/estimates/${estimateId}/send`, {});
            ERP.toast.success('Estimate sent');
            loadEstimate();
        } catch (error) { ERP.toast.error(error.message); }
    }

    async function approveEstimate() {
        try {
            await ERP.api.post(`/estimates/${estimateId}/approve`, {});
            ERP.toast.success('Estimate approved');
            loadEstimate();
        } catch (error) { ERP.toast.error(error.message); }
    }

    async function rejectEstimate() {
        try {
            await ERP.api.post(`/estimates/${estimateId}/reject`, {});
            ERP.toast.success('Estimate rejected');
            loadEstimate();
        } catch (error) { ERP.toast.error(error.message); }
    }

    async function convertToInvoice() {
        if (!confirm('Convert this estimate to an invoice?')) return;
        try {
            const response = await ERP.api.post(`/estimates/${estimateId}/convert`, {});
            if (response.success) {
                ERP.toast.success('Converted to Invoice #' + response.data.invoice_number);
                loadEstimate();
            }
        } catch (error) { ERP.toast.error(error.message); }
    }

    function getStatusColor(status) {
        return { draft: 'secondary', sent: 'warning', approved: 'success', rejected: 'error', converted: 'primary' }[status] || 'secondary';
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
</script>

<style>
    .sticky-top {
        position: sticky;
        top: 20px;
    }

    .border-b {
        border-bottom: 1px solid var(--border-color);
    }

    .text-primary {
        color: var(--primary-500);
    }

    .btn-icon {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>