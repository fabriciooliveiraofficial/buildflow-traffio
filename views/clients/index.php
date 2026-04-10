<?php
$title = 'Clients';
$page = 'clients';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Clients</h1>
        <p class="text-muted text-sm">Manage client information and projects</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('client-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Add Client
    </button>
</div>

<!-- Search -->
<div class="card mb-6">
    <div class="card-body flex gap-4">
        <input type="text" class="form-input flex-1" id="search-input" placeholder="Search clients...">
        <select class="form-select" id="status-filter" style="width: 150px;">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
</div>

<!-- Clients Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="clients-table">
            <thead>
                <tr>
                    <th class="sortable" data-sort="name" onclick="sortClients('name')" style="cursor: pointer;">
                        Client <span class="sort-icon" id="sort-icon-name"></span>
                    </th>
                    <th class="sortable" data-sort="contact" onclick="sortClients('contact')" style="cursor: pointer;">
                        Contact <span class="sort-icon" id="sort-icon-contact"></span>
                    </th>
                    <th class="sortable" data-sort="email" onclick="sortClients('email')" style="cursor: pointer;">
                        Email <span class="sort-icon" id="sort-icon-email"></span>
                    </th>
                    <th class="sortable" data-sort="phone" onclick="sortClients('phone')" style="cursor: pointer;">
                        Phone <span class="sort-icon" id="sort-icon-phone"></span>
                    </th>
                    <th class="sortable" data-sort="projects" onclick="sortClients('projects')"
                        style="cursor: pointer;">
                        Projects <span class="sort-icon" id="sort-icon-projects"></span>
                    </th>
                    <th class="sortable" data-sort="revenue" onclick="sortClients('revenue')" style="cursor: pointer;">
                        Total Revenue <span class="sort-icon" id="sort-icon-revenue"></span>
                    </th>
                    <th class="sortable" data-sort="outstanding" onclick="sortClients('outstanding')"
                        style="cursor: pointer;">
                        Outstanding <span class="sort-icon" id="sort-icon-outstanding"></span>
                    </th>
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

<!-- Client Modal -->
<div class="modal" id="client-modal">
    <div class="modal-header">
        <h3 class="modal-title">Add Client</h3>
        <button class="modal-close" onclick="Modal.close('client-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="client-form">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Company Name</label>
                    <input type="text" class="form-input" name="name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Client Type</label>
                    <select class="form-select" name="type">
                        <option value="company">Company</option>
                        <option value="individual">Individual</option>
                        <option value="government">Government</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" class="form-input" name="contact_person">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" name="email">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-input" name="phone">
                </div>
                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" class="form-input" name="website">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" class="form-input" name="address">
            </div>
            <div class="grid grid-cols-3 gap-4">
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
                    <input type="text" class="form-input" name="zip_code">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-input" name="notes" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('client-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Client</button>
        </div>
    </form>
</div>

<script>
    // Sorting state
    let clientsData = [];
    let clientSortColumn = 'name';
    let clientSortDirection = 'asc';
    let editingClientId = null;  // Track if we're editing an existing client

    document.addEventListener('DOMContentLoaded', function () {
        loadClients();

        let searchTimeout;
        document.getElementById('search-input').addEventListener('input', function (e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadClients, 300);
        });

        document.getElementById('status-filter').addEventListener('change', loadClients);

        document.getElementById('client-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                if (editingClientId) {
                    // Update existing client
                    await ERP.api.put(`/clients/${editingClientId}`, data);
                    ERP.toast.success('Client updated');
                    editingClientId = null;
                } else {
                    // Create new client
                    await ERP.api.post('/clients', data);
                    ERP.toast.success('Client added');
                }
                Modal.close('client-modal');
                this.reset();
                document.querySelector('#client-modal .modal-title').textContent = 'Add Client';
                loadClients();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadClients() {
        const params = new URLSearchParams({
            search: document.getElementById('search-input').value,
            status: document.getElementById('status-filter').value,
            per_page: 100,  // Increased for client-side sorting
        });

        try {
            const response = await ERP.api.get('/clients?' + params);
            if (response.success) {
                clientsData = response.data;  // Store for sorting
                renderClients(clientsData);
            }
        } catch (error) {
            ERP.toast.error('Failed to load clients');
        }
    }

    function sortClients(column) {
        if (!clientsData || clientsData.length === 0) return;

        // Toggle direction if same column, otherwise default to ascending
        if (clientSortColumn === column) {
            clientSortDirection = clientSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            clientSortColumn = column;
            clientSortDirection = 'asc';
        }

        // Sort the data
        clientsData.sort((a, b) => {
            let valA, valB;

            switch (column) {
                case 'name':
                    valA = (a.name || '').toLowerCase();
                    valB = (b.name || '').toLowerCase();
                    break;
                case 'contact':
                    valA = (a.contact_person || '').toLowerCase();
                    valB = (b.contact_person || '').toLowerCase();
                    break;
                case 'email':
                    valA = (a.email || '').toLowerCase();
                    valB = (b.email || '').toLowerCase();
                    break;
                case 'phone':
                    valA = (a.phone || '').toLowerCase();
                    valB = (b.phone || '').toLowerCase();
                    break;
                case 'projects':
                    valA = parseInt(a.project_count || 0);
                    valB = parseInt(b.project_count || 0);
                    break;
                case 'revenue':
                    valA = parseFloat(a.total_revenue || 0);
                    valB = parseFloat(b.total_revenue || 0);
                    break;
                case 'outstanding':
                    valA = parseFloat(a.outstanding || 0);
                    valB = parseFloat(b.outstanding || 0);
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

            return clientSortDirection === 'asc' ? comparison : -comparison;
        });

        updateClientSortIcons(column);
        renderClients(clientsData);
    }

    function updateClientSortIcons(activeColumn) {
        const columns = ['name', 'contact', 'email', 'phone', 'projects', 'revenue', 'outstanding'];
        columns.forEach(col => {
            const icon = document.getElementById(`sort-icon-${col}`);
            if (icon) {
                if (col === activeColumn) {
                    icon.textContent = clientSortDirection === 'asc' ? ' ↑' : ' ↓';
                } else {
                    icon.textContent = '';
                }
            }
        });
    }

    function renderClients(clients) {
        const tbody = document.querySelector('#clients-table tbody');

        if (clients.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No clients found</td></tr>';
            return;
        }

        tbody.innerHTML = clients.map(c => `
        <tr>
            <td>
                <a href="/clients/${c.id}" class="font-medium">${c.name}</a>
                <div class="text-xs text-muted">${c.type || 'Company'}</div>
            </td>
            <td>${c.contact_person || '-'}</td>
            <td>${c.email || '-'}</td>
            <td>${c.phone || '-'}</td>
            <td>${c.project_count || 0}</td>
            <td class="font-medium">${formatCurrency(c.total_revenue || 0)}</td>
            <td class="${c.outstanding > 0 ? 'text-warning' : ''}">${formatCurrency(c.outstanding || 0)}</td>
            <td>
                <div class="flex gap-1">
                    <button class="btn btn-icon btn-sm btn-secondary" onclick="window.location.href='/clients/${c.id}'" title="View">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                    <button class="btn btn-icon btn-sm btn-secondary" onclick="editClient(${c.id})" title="Edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="btn btn-icon btn-sm btn-danger" onclick="deleteClient(${c.id}, '${c.name.replace(/'/g, "\\'")}')" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(amount);
    }

    // Edit client - opens modal with pre-filled data
    async function editClient(clientId) {
        try {
            const response = await ERP.api.get(`/clients/${clientId}`);
            if (response.success) {
                const client = response.data;
                editingClientId = clientId;

                // Fill form with client data
                const form = document.getElementById('client-form');
                form.querySelector('[name="name"]').value = client.name || '';
                form.querySelector('[name="type"]').value = client.type || 'company';
                form.querySelector('[name="contact_person"]').value = client.contact_person || '';
                form.querySelector('[name="email"]').value = client.email || '';
                form.querySelector('[name="phone"]').value = client.phone || '';
                form.querySelector('[name="website"]').value = client.website || '';
                form.querySelector('[name="address"]').value = client.address || '';
                form.querySelector('[name="city"]').value = client.city || '';
                form.querySelector('[name="state"]').value = client.state || '';
                form.querySelector('[name="zip_code"]').value = client.zip_code || '';
                form.querySelector('[name="notes"]').value = client.notes || '';

                // Update modal title
                document.querySelector('#client-modal .modal-title').textContent = 'Edit Client';

                Modal.open('client-modal');
            }
        } catch (error) {
            ERP.toast.error('Failed to load client data');
        }
    }

    // Delete client with confirmation
    async function deleteClient(clientId, clientName) {
        if (!confirm(`Are you sure you want to delete "${clientName}"? This action cannot be undone.`)) {
            return;
        }

        try {
            await ERP.api.delete(`/clients/${clientId}`);
            ERP.toast.success('Client deleted successfully');
            loadClients();
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to delete client');
        }
    }

    // Reset modal when closed
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('client-modal');
        if (modalEl) {
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (!modalEl.classList.contains('open') && editingClientId) {
                        editingClientId = null;
                        document.getElementById('client-form').reset();
                        document.querySelector('#client-modal .modal-title').textContent = 'Add Client';
                    }
                });
            });
            observer.observe(modalEl, { attributes: true, attributeFilter: ['class'] });
        }
    });
</script>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
