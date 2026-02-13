<?php
$title = 'Purchase Order Details';
$page = 'purchase-orders';
$poId = $GLOBALS['params']['id'] ?? null;

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= dirname($_SERVER['REQUEST_URI']) ?>" class="btn btn-secondary btn-sm">← Back</a>
        <div>
            <h1 class="text-2xl font-bold" id="po-title">Purchase Order #</h1>
            <p class="text-muted text-sm" id="po-vendor"></p>
        </div>
    </div>
    <div class="flex gap-2" id="action-buttons"></div>
</div>

<div class="grid grid-cols-12 gap-6">
    <div class="col-span-8">
        <!-- PO Info -->
        <div class="card mb-6">
            <div class="card-header flex justify-between">
                <h3>Order Details</h3>
                <span class="badge" id="status-badge">Draft</span>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <label class="text-muted text-sm">Vendor</label>
                        <div id="vendor-name" class="font-medium">-</div>
                    </div>
                    <div>
                        <label class="text-muted text-sm">Project</label>
                        <div id="project-name" class="font-medium">-</div>
                    </div>
                    <div>
                        <label class="text-muted text-sm">Order Date</label>
                        <div id="order-date" class="font-medium">-</div>
                    </div>
                    <div>
                        <label class="text-muted text-sm">Expected Date</label>
                        <div id="expected-date" class="font-medium">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="card mb-6">
            <div class="card-header flex justify-between">
                <h3>Items</h3>
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
                <h3>Shipping & Notes</h3>
            </div>
            <div class="card-body">
                <div class="mb-2"><strong>Shipping Address:</strong></div>
                <p id="shipping-address" class="text-muted mb-4">-</p>
                <div class="mb-2"><strong>Notes:</strong></div>
                <p id="po-notes" class="text-muted">-</p>
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
    const poId = <?= json_encode($poId) ?>;
    let currentPO = null;
    let editingItemId = null;

    document.addEventListener('DOMContentLoaded', function () {
        if (!poId) { ERP.toast.error('Purchase Order not found'); return; }
        loadPurchaseOrder();

        document.getElementById('item-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                if (editingItemId) {
                    await ERP.api.put(`/purchase-orders/${poId}/items/${editingItemId}`, data);
                    ERP.toast.success('Item updated');
                } else {
                    await ERP.api.post(`/purchase-orders/${poId}/items`, data);
                    ERP.toast.success('Item added');
                }
                Modal.close('item-modal');
                this.reset();
                editingItemId = null;
                loadPurchaseOrder();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadPurchaseOrder() {
        try {
            const response = await ERP.api.get('/purchase-orders/' + poId);
            if (response.success) {
                currentPO = response.data;
                renderPO(response.data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load order');
        }
    }

    function renderPO(po) {
        document.getElementById('po-title').textContent = 'Purchase Order #' + po.po_number;
        document.getElementById('po-vendor').textContent = po.vendor_name || '';
        document.getElementById('vendor-name').textContent = po.vendor_name || '-';
        document.getElementById('project-name').textContent = po.project_name || 'No Project';
        document.getElementById('order-date').textContent = formatDate(po.order_date);
        document.getElementById('expected-date').textContent = po.expected_date ? formatDate(po.expected_date) : '-';
        document.getElementById('shipping-address').textContent = po.shipping_address || '-';
        document.getElementById('po-notes').textContent = po.notes || '-';

        const badge = document.getElementById('status-badge');
        badge.textContent = po.status.charAt(0).toUpperCase() + po.status.slice(1);
        badge.className = 'badge badge-' + getStatusColor(po.status);

        document.getElementById('subtotal').textContent = formatCurrency(po.subtotal);
        document.getElementById('tax-rate').textContent = po.tax_rate || 0;
        document.getElementById('tax-amount').textContent = formatCurrency(po.tax_amount);
        document.getElementById('total').textContent = formatCurrency(po.total_amount);

        renderItems(po.items || []);
        renderActions(po);
    }

    function renderItems(items) {
        const tbody = document.querySelector('#items-table tbody');

        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No items yet.</td></tr>';
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

    function renderActions(po) {
        const container = document.getElementById('action-buttons');
        let html = '';

        if (po.status === 'draft') {
            html += `<button class="btn btn-warning" onclick="sendPO()">📤 Send to Vendor</button>`;
        }
        if (po.status === 'sent') {
            html += `<button class="btn btn-success" onclick="receivePO()">✓ Mark Received</button>`;
        }

        if (po.status === 'received') {
            document.getElementById('add-item-btn').style.display = 'none';
        }

        container.innerHTML = html;
    }

    function editItem(itemId) {
        const item = currentPO.items.find(i => i.id == itemId);
        if (!item) return;

        editingItemId = itemId;
        document.getElementById('item-modal-title').textContent = 'Edit Item';

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
            await ERP.api.delete(`/purchase-orders/${poId}/items/${itemId}`);
            ERP.toast.success('Item deleted');
            loadPurchaseOrder();
        } catch (error) { ERP.toast.error(error.message); }
    }

    async function sendPO() {
        try {
            await ERP.api.post(`/purchase-orders/${poId}/send`, {});
            ERP.toast.success('Sent to vendor');
            loadPurchaseOrder();
        } catch (error) { ERP.toast.error(error.message); }
    }

    async function receivePO() {
        if (!confirm('Mark this order as received?')) return;
        try {
            await ERP.api.post(`/purchase-orders/${poId}/receive`, {});
            ERP.toast.success('Order marked as received');
            loadPurchaseOrder();
        } catch (error) { ERP.toast.error(error.message); }
    }

    function getStatusColor(status) {
        return { draft: 'secondary', sent: 'warning', received: 'success' }[status] || 'secondary';
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