<?php
$title = 'Vendors';
$page = 'vendors';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Vendors & Suppliers</h1>
        <p class="text-muted text-sm">Manage your vendor relationships</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('vendor-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Add Vendor
    </button>
</div>

<!-- Search -->
<div class="card mb-6">
    <div class="card-body flex gap-4 items-center">
        <div class="flex-1">
            <input type="text" class="form-input" id="search-input"
                placeholder="Search vendors by name, email, or company...">
        </div>
        <button class="btn btn-secondary" onclick="loadVendors()">Search</button>
    </div>
</div>

<!-- Vendors Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="vendors-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Category</th>
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

<!-- Pagination -->
<div class="flex justify-between items-center mt-6">
    <div class="text-sm text-muted" id="pagination-info">Showing 0 of 0</div>
    <div class="flex gap-2">
        <button class="btn btn-secondary btn-sm" id="prev-btn" disabled>Previous</button>
        <button class="btn btn-secondary btn-sm" id="next-btn" disabled>Next</button>
    </div>
</div>

<!-- Create/Edit Vendor Modal -->
<div class="modal" id="vendor-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Add Vendor</h3>
        <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <form id="vendor-form">
        <input type="hidden" name="id" id="vendor-id">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Contact Name</label>
                    <input type="text" class="form-input" name="name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Company</label>
                    <input type="text" class="form-input" name="company">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" name="email">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-input" name="phone">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" class="form-input" name="address">
            </div>
            <div class="grid grid-cols-4 gap-4">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" class="form-input" name="city">
                </div>
                <div class="form-group">
                    <label class="form-label">State</label>
                    <input type="text" class="form-input" name="state">
                </div>
                <div class="form-group">
                    <label class="form-label">ZIP</label>
                    <input type="text" class="form-input" name="zip">
                </div>
                <div class="form-group">
                    <label class="form-label">Country</label>
                    <input type="text" class="form-input" name="country" value="USA">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">Select Category</option>
                        <option value="materials">Materials</option>
                        <option value="equipment">Equipment</option>
                        <option value="subcontractor">Subcontractor</option>
                        <option value="services">Services</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Terms</label>
                    <select class="form-select" name="payment_terms">
                        <option value="Net 30">Net 30</option>
                        <option value="Net 15">Net 15</option>
                        <option value="Net 60">Net 60</option>
                        <option value="Due on Receipt">Due on Receipt</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-input" name="notes" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Vendor</button>
        </div>
    </form>
</div>

<script>
    let currentPage = 1;
    let totalPages = 1;
    let editingId = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadVendors();

        document.getElementById('vendor-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                if (editingId) {
                    await ERP.api.put('/vendors/' + editingId, data);
                    ERP.toast.success('Vendor updated');
                } else {
                    await ERP.api.post('/vendors', data);
                    ERP.toast.success('Vendor created');
                }
                closeModal();
                this.reset();
                loadVendors();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        document.getElementById('search-input').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') { loadVendors(); }
        });

        document.getElementById('prev-btn').addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; loadVendors(); }
        });
        document.getElementById('next-btn').addEventListener('click', () => {
            if (currentPage < totalPages) { currentPage++; loadVendors(); }
        });
    });

    async function loadVendors() {
        const search = document.getElementById('search-input').value;
        const params = new URLSearchParams({ page: currentPage, per_page: 15 });
        if (search) params.set('search', search);

        try {
            const response = await ERP.api.get('/vendors?' + params);
            if (response.success) {
                renderVendors(response.data);
                totalPages = response.meta.total_pages || 1;
                updatePagination(response.meta);
            }
        } catch (error) {
            ERP.toast.error('Failed to load vendors');
        }
    }

    function renderVendors(vendors) {
        const tbody = document.querySelector('#vendors-table tbody');

        if (!vendors || vendors.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No vendors found</td></tr>';
            return;
        }

        tbody.innerHTML = vendors.map(v => `
            <tr>
                <td><strong>${v.name}</strong></td>
                <td>${v.company || '-'}</td>
                <td>${v.email ? `<a href="mailto:${v.email}">${v.email}</a>` : '-'}</td>
                <td>${v.phone || '-'}</td>
                <td>${v.category ? `<span class="badge badge-secondary">${v.category}</span>` : '-'}</td>
                <td><span class="badge badge-${v.status === 'active' ? 'success' : 'secondary'}">${v.status}</span></td>
                <td>
                    <div class="flex gap-1">
                        <button class="btn btn-sm btn-secondary" onclick="editVendor(${v.id})" title="Edit">✎</button>
                        <button class="btn btn-sm btn-secondary" onclick="deleteVendor(${v.id})" title="Delete">🗑</button>
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

    async function editVendor(id) {
        try {
            const response = await ERP.api.get('/vendors/' + id);
            if (response.success) {
                const v = response.data;
                editingId = id;
                document.getElementById('modal-title').textContent = 'Edit Vendor';

                const form = document.getElementById('vendor-form');
                form.querySelector('[name="name"]').value = v.name || '';
                form.querySelector('[name="company"]').value = v.company || '';
                form.querySelector('[name="email"]').value = v.email || '';
                form.querySelector('[name="phone"]').value = v.phone || '';
                form.querySelector('[name="address"]').value = v.address || '';
                form.querySelector('[name="city"]').value = v.city || '';
                form.querySelector('[name="state"]').value = v.state || '';
                form.querySelector('[name="zip"]').value = v.zip || '';
                form.querySelector('[name="country"]').value = v.country || 'USA';
                form.querySelector('[name="category"]').value = v.category || '';
                form.querySelector('[name="payment_terms"]').value = v.payment_terms || 'Net 30';
                form.querySelector('[name="notes"]').value = v.notes || '';

                Modal.open('vendor-modal');
            }
        } catch (error) { ERP.toast.error(error.message); }
    }

    async function deleteVendor(id) {
        if (!confirm('Delete this vendor?')) return;
        try {
            await ERP.api.delete('/vendors/' + id);
            ERP.toast.success('Vendor deleted');
            loadVendors();
        } catch (error) { ERP.toast.error(error.message); }
    }

    function closeModal() {
        Modal.close('vendor-modal');
        editingId = null;
        document.getElementById('modal-title').textContent = 'Add Vendor';
        document.getElementById('vendor-form').reset();
    }
</script>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
