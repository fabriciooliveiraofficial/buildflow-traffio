<?php
$title = 'Chart of Accounts';
$page = 'accounts';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Chart of Accounts</h1>
        <p class="text-muted text-sm">Manage your financial accounts</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('account-modal'); resetForm();">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Add Account
    </button>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4">
        <select class="form-select" id="type-filter" style="width: 180px;">
            <option value="">All Types</option>
            <option value="asset">Assets</option>
            <option value="liability">Liabilities</option>
            <option value="equity">Equity</option>
            <option value="income">Income</option>
            <option value="expense">Expenses</option>
        </select>
    </div>
</div>

<!-- Accounts Table -->
<div class="card">
    <div class="table-container">
        <table class="table" id="accounts-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Subtype</th>
                    <th>System</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="text-center text-muted">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Account Modal -->
<div class="modal" id="account-modal">
    <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Add Account</h3>
        <button class="modal-close" onclick="Modal.close('account-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="account-form">
        <input type="hidden" name="id" id="account-id">
        <div class="modal-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Account Type</label>
                    <select class="form-select" name="type" id="account-type" required>
                        <option value="">Select Type</option>
                        <option value="asset">Asset (1xxx)</option>
                        <option value="liability">Liability (2xxx)</option>
                        <option value="equity">Equity (3xxx)</option>
                        <option value="income">Income (4xxx)</option>
                        <option value="expense">Expense (5xxx)</option>
                    </select>
                    <p class="form-help">Account code will be auto-generated based on type</p>
                </div>
                <div class="form-group">
                    <label class="form-label required">Account Name</label>
                    <input type="text" class="form-input" name="name" id="account-name" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Subtype</label>
                <select class="form-select" name="subtype" id="account-subtype">
                    <option value="">None</option>
                    <option value="current_asset">Current Asset</option>
                    <option value="fixed_asset">Fixed Asset</option>
                    <option value="current_liability">Current Liability</option>
                    <option value="long_term_liability">Long-term Liability</option>
                    <option value="equity">Equity</option>
                    <option value="operating_revenue">Operating Revenue</option>
                    <option value="other_income">Other Income</option>
                    <option value="cost_of_goods_sold">Cost of Goods Sold</option>
                    <option value="operating_expense">Operating Expense</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" id="account-description" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('account-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary" id="save-btn">Save Account</button>
        </div>
    </form>
</div>

<script>
    let isEditing = false;

    document.addEventListener('DOMContentLoaded', function () {
        loadAccounts();

        document.getElementById('type-filter').addEventListener('change', loadAccounts);

        document.getElementById('account-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);
            const id = document.getElementById('account-id').value;

            try {
                if (isEditing && id) {
                    await ERP.api.put('/accounts/' + id, data);
                    ERP.toast.success('Account updated');
                } else {
                    await ERP.api.post('/accounts', data);
                    ERP.toast.success('Account created');
                }
                Modal.close('account-modal');
                loadAccounts();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadAccounts() {
        const type = document.getElementById('type-filter').value;
        const params = type ? '?type=' + type : '';

        try {
            const response = await ERP.api.get('/accounts' + params);
            if (response.success) {
                renderAccounts(response.data);
            }
        } catch (error) {
            ERP.toast.error('Failed to load accounts');
        }
    }

    function renderAccounts(accounts) {
        const tbody = document.querySelector('#accounts-table tbody');

        if (accounts.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No accounts found</td></tr>';
            return;
        }

        tbody.innerHTML = accounts.map(a => `
        <tr>
            <td><span class="font-mono font-medium">${a.code}</span></td>
            <td>${a.name}</td>
            <td><span class="badge badge-${getTypeBadge(a.type)}">${capitalize(a.type)}</span></td>
            <td>${a.subtype ? formatSubtype(a.subtype) : '-'}</td>
            <td>${a.is_system ? '<span class="badge badge-secondary">System</span>' : ''}</td>
            <td>
                <div class="flex gap-1">
                    ${!a.is_system ? `
                    <button class="btn btn-icon btn-sm btn-secondary" onclick="editAccount(${a.id})" title="Edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button class="btn btn-icon btn-sm btn-error" onclick="deleteAccount(${a.id}, '${a.name}')" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
    }

    function resetForm() {
        isEditing = false;
        document.getElementById('account-form').reset();
        document.getElementById('account-id').value = '';
        document.getElementById('modal-title').textContent = 'Add Account';
        document.getElementById('account-type').disabled = false;
    }

    async function editAccount(id) {
        try {
            const response = await ERP.api.get('/accounts/' + id);
            if (response.success) {
                const a = response.data;
                isEditing = true;
                document.getElementById('account-id').value = a.id;
                document.getElementById('account-name').value = a.name;
                document.getElementById('account-type').value = a.type;
                document.getElementById('account-type').disabled = true; // Can't change type (code is based on it)
                document.getElementById('account-subtype').value = a.subtype || '';
                document.getElementById('account-description').value = a.description || '';
                document.getElementById('modal-title').textContent = 'Edit Account: ' + a.code;
                Modal.open('account-modal');
            }
        } catch (error) {
            ERP.toast.error('Failed to load account');
        }
    }

    async function deleteAccount(id, name) {
        if (!confirm(`Delete account "${name}"? This cannot be undone.`)) return;

        try {
            await ERP.api.delete('/accounts/' + id);
            ERP.toast.success('Account deleted');
            loadAccounts();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    function getTypeBadge(type) {
        const map = {
            'asset': 'primary',
            'liability': 'warning',
            'equity': 'secondary',
            'income': 'success',
            'expense': 'error'
        };
        return map[type] || 'secondary';
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function formatSubtype(subtype) {
        return subtype.split('_').map(capitalize).join(' ');
    }
</script>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>