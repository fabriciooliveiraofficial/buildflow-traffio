<?php
$title = 'Client Details';
$page = 'clients';

$clientId = $GLOBALS['params']['id'] ?? null;

ob_start();
?>

<div class="mb-6">
    <a href="../clients" class="text-muted text-sm flex items-center gap-1 mb-2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6" />
        </svg>
        Back to Clients
    </a>
</div>

<div class="grid grid-cols-3 gap-6">
    <!-- Main Content -->
    <div style="grid-column: span 2;">
        <!-- Client Header -->
        <div class="card mb-6">
            <div class="card-body flex items-start gap-6">
                <div class="client-avatar" id="client-avatar">C</div>
                <div class="flex-1">
                    <div class="flex justify-between">
                        <div>
                            <h1 class="text-2xl font-bold" id="client-name">Loading...</h1>
                            <p class="text-muted" id="client-type">Company</p>
                        </div>
                        <button class="btn btn-outline" onclick="Modal.open('edit-modal')">Edit</button>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mt-4">
                        <div>
                            <span class="text-muted text-sm">Contact</span>
                            <p id="contact-person">-</p>
                        </div>
                        <div>
                            <span class="text-muted text-sm">Email</span>
                            <p id="client-email">-</p>
                        </div>
                        <div>
                            <span class="text-muted text-sm">Phone</span>
                            <p id="client-phone">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 mb-4">
            <button class="btn btn-secondary active" id="tab-projects" onclick="switchTab('projects')">Projects</button>
            <button class="btn btn-secondary" id="tab-invoices" onclick="switchTab('invoices')">Invoices</button>
        </div>

        <!-- Projects Tab -->
        <div id="projects-content">
            <div class="card">
                <div class="card-header flex justify-between">
                    <h3 class="card-title">Projects</h3>
                    <a href="../projects/new?client_id=<?= $clientId ?>" class="btn btn-primary btn-sm">New Project</a>
                </div>
                <div class="table-container">
                    <table class="table" id="projects-table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Budget</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Invoices Tab -->
        <div id="invoices-content" style="display: none;">
            <div class="card">
                <div class="card-header flex justify-between">
                    <h3 class="card-title">Invoices</h3>
                    <a href="../invoices/new?client_id=<?= $clientId ?>" class="btn btn-primary btn-sm">New Invoice</a>
                </div>
                <div class="table-container">
                    <table class="table" id="invoices-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Financial Summary -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Financial Summary</h3>
            </div>
            <div class="card-body">
                <div class="flex justify-between py-2 border-b">
                    <span class="text-muted">Total Revenue</span>
                    <span class="font-medium text-success" id="total-revenue">$0</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-muted">Total Invoiced</span>
                    <span class="font-medium" id="total-invoiced">$0</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-muted">Paid</span>
                    <span class="font-medium" id="total-paid">$0</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-muted">Outstanding</span>
                    <span class="font-bold text-warning" id="outstanding">$0</span>
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Address</h3>
            </div>
            <div class="card-body">
                <p id="address-line1">-</p>
                <p id="address-line2" class="text-muted"></p>
            </div>
        </div>
    </div>
</div>

<script>
    const clientId = <?= json_encode($clientId) ?>;
    let client = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadClient();
    });

    async function loadClient() {
        try {
            const response = await ERP.api.get('/clients/' + clientId);
            if (response.success) {
                client = response.data;
                renderClient();
                loadProjects();
                loadInvoices();
                loadFinancials();
            }
        } catch (error) {
            ERP.toast.error('Failed to load client');
        }
    }

    function renderClient() {
        document.getElementById('client-name').textContent = client.name;
        document.getElementById('client-type').textContent = client.type || 'Company';
        document.getElementById('client-avatar').textContent = client.name.charAt(0).toUpperCase();
        document.getElementById('contact-person').textContent = client.contact_person || '-';
        document.getElementById('client-email').textContent = client.email || '-';
        document.getElementById('client-phone').textContent = client.phone || '-';

        document.getElementById('address-line1').textContent = client.address || '-';
        document.getElementById('address-line2').textContent = [
            client.city, client.state, client.zip_code
        ].filter(Boolean).join(', ');
    }

    async function loadProjects() {
        try {
            const response = await ERP.api.get(`/clients/${clientId}/projects`);
            if (response.success) {
                renderProjects(response.data);
            }
        } catch (error) {
            console.error('Failed to load projects');
        }
    }

    function renderProjects(projects) {
        const tbody = document.querySelector('#projects-table tbody');
        if (!projects || projects.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No projects</td></tr>';
            return;
        }

        tbody.innerHTML = projects.map(p => `
        <tr>
            <td><a href="../projects/${p.id}" class="font-medium">${p.name}</a></td>
            <td><span class="badge badge-${getStatusColor(p.status)}">${p.status}</span></td>
            <td>${formatCurrency(p.total_budget || 0)}</td>
            <td>
                <div class="flex items-center gap-2">
                    <div class="progress" style="width: 60px;">
                        <div class="progress-bar" style="width: ${p.progress || 0}%"></div>
                    </div>
                    <span class="text-sm">${p.progress || 0}%</span>
                </div>
            </td>
        </tr>
    `).join('');
    }

    async function loadInvoices() {
        try {
            const response = await ERP.api.get(`/invoices?client_id=${clientId}`);
            if (response.success) {
                renderInvoices(response.data);
            }
        } catch (error) {
            console.error('Failed to load invoices');
        }
    }

    function renderInvoices(invoices) {
        const tbody = document.querySelector('#invoices-table tbody');
        if (!invoices || invoices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No invoices</td></tr>';
            return;
        }

        tbody.innerHTML = invoices.map(inv => `
        <tr>
            <td><a href="../invoices/${inv.id}" class="font-medium">${inv.invoice_number}</a></td>
            <td>${formatDate(inv.issue_date)}</td>
            <td>${formatCurrency(inv.total_amount)}</td>
            <td>${formatCurrency(inv.paid_amount || 0)}</td>
            <td><span class="badge badge-${getInvoiceStatusColor(inv.status)}">${inv.status}</span></td>
        </tr>
    `).join('');
    }

    async function loadFinancials() {
        try {
            const response = await ERP.api.get(`/clients/${clientId}/financials`);
            if (response.success) {
                const f = response.data;
                document.getElementById('total-revenue').textContent = formatCurrency(f.total_paid || 0);
                document.getElementById('total-invoiced').textContent = formatCurrency(f.total_invoiced || 0);
                document.getElementById('total-paid').textContent = formatCurrency(f.total_paid || 0);
                document.getElementById('outstanding').textContent = formatCurrency(f.outstanding || 0);
            }
        } catch (error) {
            console.error('Failed to load financials');
        }
    }

    function switchTab(tab) {
        document.querySelectorAll('[id^="tab-"]').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('[id$="-content"]').forEach(el => el.style.display = 'none');
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById(tab + '-content').style.display = 'block';
    }

    function getStatusColor(s) {
        return { planning: 'secondary', in_progress: 'success', on_hold: 'warning', completed: 'primary' }[s] || 'secondary';
    }

    function getInvoiceStatusColor(s) {
        return { draft: 'secondary', sent: 'primary', partial: 'warning', paid: 'success', overdue: 'error' }[s] || 'secondary';
    }

    function formatCurrency(a) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(a);
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
</script>

<style>
    .client-avatar {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary-500), var(--secondary-500));
        border-radius: var(--radius-xl);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        font-weight: 700;
    }

    .flex-1 {
        flex: 1;
    }

    .border-b {
        border-bottom: 1px solid var(--border-color);
    }

    .text-success {
        color: var(--success-500);
    }

    .text-warning {
        color: var(--warning-500);
    }

    .btn.active {
        background: var(--primary-500);
        color: white;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
