<?php
$title = 'Inventory';
$page = 'inventory';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Inventory</h1>
        <p class="text-muted text-sm">Track materials, tools, and equipment</p>
    </div>
    <div class="flex gap-3">
        <button class="btn btn-outline" onclick="window.location.href='/inventory/low-stock'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                <line x1="12" y1="9" x2="12" y2="13" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
            Low Stock (<span id="low-stock-count">0</span>)
        </button>
        <button class="btn btn-primary" onclick="Modal.open('item-modal')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Add Item
        </button>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-4 mb-6">
    <div class="card stat-card">
        <div class="stat-value" id="total-items">0</div>
        <div class="stat-label">Total Items</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value" id="total-units">0</div>
        <div class="stat-label">Total Units</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value" id="total-value">$0</div>
        <div class="stat-label">Total Value</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value text-warning" id="low-stock-value">0</div>
        <div class="stat-label">Low Stock Items</div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4">
        <input type="text" class="form-input flex-1" id="search-input" placeholder="Search items by name, SKU...">
        <select class="form-select" id="category-filter" style="width: 180px;">
            <option value="">All Categories</option>
        </select>
        <select class="form-select" id="status-filter" style="width: 140px;">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="low_stock">Low Stock</option>
            <option value="discontinued">Discontinued</option>
        </select>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="inventory-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Min Qty</th>
                    <th>Unit Cost</th>
                    <th>Total Value</th>
                    <th>Status</th>
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

<!-- Add Item Modal -->
<div class="modal" id="item-modal">
    <div class="modal-header">
        <h3 class="modal-title">Add Inventory Item</h3>
        <button class="modal-close" onclick="Modal.close('item-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="item-form">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Item Name</label>
                    <input type="text" class="form-input" name="name" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Category</label>
                    <select class="form-select" name="category" id="modal-category" required>
                        <option value="">Select Category</option>
                        <option value="materials">Materials</option>
                        <option value="tools">Tools</option>
                        <option value="equipment">Equipment</option>
                        <option value="safety">Safety Gear</option>
                        <option value="electrical">Electrical</option>
                        <option value="plumbing">Plumbing</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="form-group">
                    <label class="form-label">SKU</label>
                    <input type="text" class="form-input" name="sku" placeholder="Auto-generated">
                </div>
                <div class="form-group">
                    <label class="form-label">Barcode</label>
                    <input type="text" class="form-input" name="barcode">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <select class="form-select" name="unit">
                        <option value="piece">Piece</option>
                        <option value="box">Box</option>
                        <option value="kg">Kilogram</option>
                        <option value="liter">Liter</option>
                        <option value="meter">Meter</option>
                        <option value="sqm">Square Meter</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-input" name="quantity" value="0" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Min Quantity</label>
                    <input type="number" class="form-input" name="min_quantity" value="5" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Quantity</label>
                    <input type="number" class="form-input" name="max_quantity" min="0">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Unit Cost</label>
                    <input type="number" class="form-input" name="unit_cost" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit Price (Sell)</label>
                    <input type="number" class="form-input" name="unit_price" step="0.01" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" class="form-input" name="location" placeholder="Warehouse, shelf, etc.">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('item-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Item</button>
        </div>
    </form>
</div>

<!-- Adjust Stock Modal -->
<div class="modal" id="adjust-modal" style="max-width: 400px;">
    <div class="modal-header">
        <h3 class="modal-title">Adjust Stock</h3>
        <button class="modal-close" onclick="Modal.close('adjust-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="adjust-form">
        <input type="hidden" name="item_id" id="adjust-item-id">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Adjustment Type</label>
                <select class="form-select" name="type">
                    <option value="add">Add Stock</option>
                    <option value="remove">Remove Stock</option>
                    <option value="set">Set Quantity</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-input" name="quantity" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label">Reason</label>
                <input type="text" class="form-input" name="reason"
                    placeholder="e.g., Restock, Damaged, Used on project">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('adjust-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Adjust</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        loadInventory();
        loadCategories();
        loadStats();

        let searchTimeout;
        document.getElementById('search-input').addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadInventory, 300);
        });

        document.getElementById('category-filter').addEventListener('change', loadInventory);
        document.getElementById('status-filter').addEventListener('change', loadInventory);

        document.getElementById('item-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                await ERP.api.post('/inventory', data);
                ERP.toast.success('Item added');
                Modal.close('item-modal');
                this.reset();
                loadInventory();
                loadStats();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        document.getElementById('adjust-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const itemId = document.getElementById('adjust-item-id').value;
            const data = ERP.FormUtils.serialize(this);

            try {
                await ERP.api.post(`/inventory/${itemId}/adjust`, data);
                ERP.toast.success('Stock adjusted');
                Modal.close('adjust-modal');
                loadInventory();
                loadStats();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadInventory() {
        const params = new URLSearchParams({
            search: document.getElementById('search-input').value,
            category: document.getElementById('category-filter').value,
            low_stock: document.getElementById('status-filter').value === 'low_stock' ? 'true' : '',
            per_page: 25,
        });

        try {
            const response = await ERP.api.get('/inventory?' + params);
            if (response.success) {
                renderInventory(response.data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load inventory');
        }
    }

    async function loadCategories() {
        try {
            const response = await ERP.api.get('/inventory/categories');
            if (response.success) {
                const options = response.data.map(c =>
                    `<option value="${c.category}">${c.category} (${c.item_count})</option>`
                ).join('');
                document.getElementById('category-filter').innerHTML =
                    '<option value="">All Categories</option>' + options;
            }
        } catch (error) {
            console.error('Failed to load categories');
        }
    }

    async function loadStats() {
        try {
            const response = await ERP.api.get('/inventory/valuation');
            if (response.success) {
                const s = response.data.summary;
                document.getElementById('total-items').textContent = s.total_items;
                document.getElementById('total-units').textContent = Number(s.total_units).toLocaleString();
                document.getElementById('total-value').textContent = formatCurrency(s.total_cost_value);
            }

            const lowStock = await ERP.api.get('/inventory/low-stock');
            if (lowStock.success) {
                const count = lowStock.data.length;
                document.getElementById('low-stock-count').textContent = count;
                document.getElementById('low-stock-value').textContent = count;
            }
        } catch (error) {
            console.error('Failed to load stats');
        }
    }

    function renderInventory(items) {
        const tbody = document.querySelector('#inventory-table tbody');

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No items found</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(item => {
            const isLow = item.quantity <= item.min_quantity;
            return `
        <tr>
            <td class="font-mono text-sm">${item.sku || '-'}</td>
            <td>
                <span class="font-medium">${item.name}</span>
                ${item.location ? `<div class="text-xs text-muted">${item.location}</div>` : ''}
            </td>
            <td><span class="badge badge-secondary">${item.category}</span></td>
            <td class="${isLow ? 'text-error font-bold' : ''}">${item.quantity} ${item.unit}</td>
            <td class="text-muted">${item.min_quantity}</td>
            <td>${formatCurrency(item.unit_cost)}</td>
            <td class="font-medium">${formatCurrency(item.total_value)}</td>
            <td>
                ${isLow ? '<span class="badge badge-error">Low Stock</span>' :
                    item.status === 'active' ? '<span class="badge badge-success">In Stock</span>' :
                        '<span class="badge badge-secondary">' + item.status + '</span>'}
            </td>
            <td>
                <button class="btn btn-icon btn-sm btn-secondary" onclick="openAdjust(${item.id})">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                </button>
            </td>
        </tr>
    `}).join('');
    }

    function openAdjust(itemId) {
        document.getElementById('adjust-item-id').value = itemId;
        Modal.open('adjust-modal');
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(amount || 0);
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

    .font-mono {
        font-family: monospace;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>