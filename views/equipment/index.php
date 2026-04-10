<?php
$title = 'Equipment';
$page = 'equipment';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Equipment & Assets</h1>
        <p class="text-muted text-sm">Track equipment, maintenance, and usage</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('equipment-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Add Equipment
    </button>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4 items-center">
        <select class="form-select" id="status-filter" style="width: 150px;">
            <option value="">All Status</option>
            <option value="available">Available</option>
            <option value="in_use">In Use</option>
            <option value="maintenance">Maintenance</option>
            <option value="retired">Retired</option>
        </select>
        <select class="form-select" id="category-filter" style="width: 150px;">
            <option value="">All Categories</option>
            <option value="tools">Tools</option>
            <option value="vehicles">Vehicles</option>
            <option value="machinery">Machinery</option>
            <option value="safety">Safety Equipment</option>
            <option value="other">Other</option>
        </select>
        <button class="btn btn-secondary" onclick="clearFilters()">Clear Filters</button>
    </div>
</div>

<!-- Equipment Grid -->
<div class="grid grid-cols-3 gap-4" id="equipment-grid">
    <div class="text-center text-muted p-8">Loading equipment...</div>
</div>

<!-- Pagination -->
<div class="flex justify-between items-center mt-6">
    <div class="text-sm text-muted" id="pagination-info">Showing 0 of 0</div>
    <div class="flex gap-2">
        <button class="btn btn-secondary btn-sm" id="prev-btn" disabled>Previous</button>
        <button class="btn btn-secondary btn-sm" id="next-btn" disabled>Next</button>
    </div>
</div>

<!-- Add/Edit Equipment Modal -->
<div class="modal" id="equipment-modal" style="max-width: 600px;">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Add Equipment</h3>
        <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <form id="equipment-form">
        <input type="hidden" name="id" id="equipment-id">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Name</label>
                    <input type="text" class="form-input" name="name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">Select</option>
                        <option value="tools">Tools</option>
                        <option value="vehicles">Vehicles</option>
                        <option value="machinery">Machinery</option>
                        <option value="safety">Safety Equipment</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="2"></textarea>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="form-group">
                    <label class="form-label">Serial Number</label>
                    <input type="text" class="form-input" name="serial_number">
                </div>
                <div class="form-group">
                    <label class="form-label">Model</label>
                    <input type="text" class="form-input" name="model">
                </div>
                <div class="form-group">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" class="form-input" name="manufacturer">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="form-group">
                    <label class="form-label">Purchase Date</label>
                    <input type="date" class="form-input" name="purchase_date">
                </div>
                <div class="form-group">
                    <label class="form-label">Purchase Price</label>
                    <input type="number" step="0.01" class="form-input" name="purchase_price">
                </div>
                <div class="form-group">
                    <label class="form-label">Current Value</label>
                    <input type="number" step="0.01" class="form-input" name="current_value">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" class="form-input" name="location" placeholder="e.g. Warehouse A, Truck #3">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<!-- Maintenance Modal -->
<div class="modal" id="maintenance-modal" style="max-width: 500px;">
    <div class="modal-header">
        <h3 class="modal-title">Add Maintenance Record</h3>
        <button class="modal-close" onclick="Modal.close('maintenance-modal')">×</button>
    </div>
    <form id="maintenance-form">
        <input type="hidden" name="equipment_id" id="maint-equipment-id">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Maintenance Type</label>
                    <select class="form-select" name="maintenance_type" required>
                        <option value="routine">Routine</option>
                        <option value="repair">Repair</option>
                        <option value="inspection">Inspection</option>
                        <option value="calibration">Calibration</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-input" name="maintenance_date" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="2"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Cost</label>
                    <input type="number" step="0.01" class="form-input" name="cost" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Next Maintenance</label>
                    <input type="date" class="form-input" name="next_maintenance_date">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('maintenance-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<script>
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};
    let editingId = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadEquipment();

        document.getElementById('equipment-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                if (editingId) {
                    await ERP.api.put('/equipment/' + editingId, data);
                    ERP.toast.success('Equipment updated');
                } else {
                    await ERP.api.post('/equipment', data);
                    ERP.toast.success('Equipment added');
                }
                closeModal();
                this.reset();
                loadEquipment();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        document.getElementById('maintenance-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const equipId = document.getElementById('maint-equipment-id').value;
            const data = ERP.FormUtils.serialize(this);

            try {
                await ERP.api.post('/equipment/' + equipId + '/maintenance', data);
                ERP.toast.success('Maintenance record added');
                Modal.close('maintenance-modal');
                this.reset();
                loadEquipment();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        document.getElementById('status-filter').addEventListener('change', applyFilters);
        document.getElementById('category-filter').addEventListener('change', applyFilters);

        document.getElementById('prev-btn').addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; loadEquipment(); }
        });
        document.getElementById('next-btn').addEventListener('click', () => {
            if (currentPage < totalPages) { currentPage++; loadEquipment(); }
        });
    });

    async function loadEquipment() {
        const params = new URLSearchParams({ page: currentPage, per_page: 12, ...currentFilters });

        try {
            const response = await ERP.api.get('/equipment?' + params);
            if (response.success) {
                renderEquipment(response.data);
                totalPages = response.meta.total_pages || 1;
                updatePagination(response.meta);
            }
        } catch (error) {
            ERP.toast.error('Failed to load equipment');
        }
    }

    function renderEquipment(items) {
        const grid = document.getElementById('equipment-grid');

        if (!items || items.length === 0) {
            grid.innerHTML = '<div class="text-center text-muted p-8">No equipment found. Add your first item.</div>';
            return;
        }

        grid.innerHTML = items.map(e => `
            <div class="card equipment-card">
                <div class="card-body">
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="font-bold">${e.name}</h4>
                        <span class="badge badge-${getStatusColor(e.status)}">${e.status}</span>
                    </div>
                    ${e.category ? `<div class="text-sm text-muted mb-2">📦 ${e.category}</div>` : ''}
                    ${e.location ? `<div class="text-sm text-muted mb-2">📍 ${e.location}</div>` : ''}
                    ${e.serial_number ? `<div class="text-sm text-muted mb-2">🔢 ${e.serial_number}</div>` : ''}
                    <div class="flex gap-2 mt-4">
                        <button class="btn btn-sm btn-secondary flex-1" onclick="editEquipment(${e.id})">Edit</button>
                        <button class="btn btn-sm btn-warning" onclick="addMaintenance(${e.id})" title="Add Maintenance">🔧</button>
                        <button class="btn btn-sm btn-error" onclick="deleteEquipment(${e.id})" title="Delete">🗑</button>
                    </div>
                </div>
            </div>
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
        const status = document.getElementById('status-filter').value;
        const category = document.getElementById('category-filter').value;
        if (status) currentFilters.status = status;
        if (category) currentFilters.category = category;
        currentPage = 1;
        loadEquipment();
    }

    function clearFilters() {
        document.getElementById('status-filter').value = '';
        document.getElementById('category-filter').value = '';
        currentFilters = {};
        currentPage = 1;
        loadEquipment();
    }

    async function editEquipment(id) {
        try {
            const response = await ERP.api.get('/equipment/' + id);
            if (response.success) {
                const e = response.data;
                editingId = id;
                document.getElementById('modal-title').textContent = 'Edit Equipment';

                const form = document.getElementById('equipment-form');
                form.querySelector('[name="name"]').value = e.name || '';
                form.querySelector('[name="category"]').value = e.category || '';
                form.querySelector('[name="description"]').value = e.description || '';
                form.querySelector('[name="serial_number"]').value = e.serial_number || '';
                form.querySelector('[name="model"]').value = e.model || '';
                form.querySelector('[name="manufacturer"]').value = e.manufacturer || '';
                form.querySelector('[name="purchase_date"]').value = e.purchase_date || '';
                form.querySelector('[name="purchase_price"]').value = e.purchase_price || '';
                form.querySelector('[name="current_value"]').value = e.current_value || '';
                form.querySelector('[name="location"]').value = e.location || '';

                Modal.open('equipment-modal');
            }
        } catch (error) { ERP.toast.error(error.message); }
    }

    function addMaintenance(id) {
        document.getElementById('maint-equipment-id').value = id;
        document.getElementById('maintenance-form').reset();
        document.getElementById('maintenance-form').querySelector('[name="maintenance_date"]').value = new Date().toISOString().split('T')[0];
        Modal.open('maintenance-modal');
    }

    async function deleteEquipment(id) {
        if (!confirm('Delete this equipment?')) return;
        try {
            await ERP.api.delete('/equipment/' + id);
            ERP.toast.success('Equipment deleted');
            loadEquipment();
        } catch (error) { ERP.toast.error(error.message); }
    }

    function closeModal() {
        Modal.close('equipment-modal');
        editingId = null;
        document.getElementById('modal-title').textContent = 'Add Equipment';
        document.getElementById('equipment-form').reset();
    }

    function getStatusColor(status) {
        return { available: 'success', in_use: 'primary', maintenance: 'warning', retired: 'secondary' }[status] || 'secondary';
    }
</script>

<style>
    .equipment-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .equipment-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
