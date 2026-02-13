<?php
$title = 'Invoice Details';
$page = 'invoices';

$invoiceId = $GLOBALS['params']['id'] ?? null;

ob_start();
?>

<div class="mb-6">
    <a href="../invoices" class="text-muted text-sm flex items-center gap-1 mb-2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6" />
        </svg>
        Back to Invoices
    </a>
</div>

<div class="grid grid-cols-3 gap-6">
    <!-- Invoice Card -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold" id="invoice-number">Loading...</h1>
                <span class="badge" id="invoice-status"></span>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-outline" onclick="printInvoice()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9" />
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                        <rect x="6" y="14" width="12" height="8" />
                    </svg>
                    Print
                </button>
                <button class="btn btn-primary" id="send-btn" onclick="sendInvoice()" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13" />
                        <polygon points="22 2 15 22 11 13 2 9 22 2" />
                    </svg>
                    Send to Client
                </button>
            </div>
        </div>

        <div class="card-body">
            <!-- Header Info -->
            <div class="grid grid-cols-2 gap-6 mb-6 pb-6 border-b">
                <div>
                    <h3 class="text-sm text-muted mb-2">Bill To</h3>
                    <p class="font-medium" id="client-name">-</p>
                    <p class="text-sm text-muted" id="client-email">-</p>
                    <p class="text-sm text-muted" id="client-address">-</p>
                </div>
                <div class="text-right">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <span class="text-muted">Issue Date:</span>
                        <span id="issue-date">-</span>
                        <span class="text-muted">Due Date:</span>
                        <span id="due-date" class="font-medium">-</span>
                        <span class="text-muted">Project:</span>
                        <span id="project-name">-</span>
                    </div>
                </div>
            </div>

            <!-- Line Items -->
            <table class="table mb-6">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody id="items-tbody">
                    <tr>
                        <td colspan="4" class="text-center text-muted">Loading...</td>
                    </tr>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="flex justify-end">
                <div class="w-64">
                    <div class="flex justify-between py-2">
                        <span class="text-muted">Subtotal:</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-muted">Tax (<span id="tax-rate">0</span>%):</span>
                        <span id="tax-amount">$0.00</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-muted">Discount:</span>
                        <span id="discount">-$0.00</span>
                    </div>
                    <div class="flex justify-between py-2 border-t font-bold text-lg">
                        <span>Total:</span>
                        <span id="total-amount">$0.00</span>
                    </div>
                    <div class="flex justify-between py-2 text-success">
                        <span>Paid:</span>
                        <span id="paid-amount">$0.00</span>
                    </div>
                    <div class="flex justify-between py-2 border-t font-bold">
                        <span>Balance Due:</span>
                        <span id="balance-due">$0.00</span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="mt-6 pt-6 border-t" id="notes-section" style="display: none;">
                <h4 class="text-sm text-muted mb-2">Notes</h4>
                <p id="invoice-notes" class="text-sm"></p>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Payment Summary -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Payment</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="text-3xl font-bold" id="balance-display">$0</div>
                    <div class="text-muted text-sm">Balance Due</div>
                </div>

                <button class="btn btn-primary w-full mb-3" id="record-payment-btn"
                    onclick="Modal.open('payment-modal')">
                    Record Payment
                </button>

                <button class="btn btn-outline w-full" id="payment-link-btn" onclick="createPaymentLink()">
                    Get Payment Link
                </button>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payment History</h3>
            </div>
            <div class="card-body" id="payments-list">
                <p class="text-muted text-center text-sm">No payments yet</p>
            </div>
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal" id="payment-modal" style="max-width: 400px;">
    <div class="modal-header">
        <h3 class="modal-title">Record Payment</h3>
        <button class="modal-close" onclick="Modal.close('payment-modal')">×</button>
    </div>
    <form id="payment-form">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Amount</label>
                <input type="number" class="form-input" name="amount" id="payment-amount" step="0.01" required>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Date</label>
                <input type="date" class="form-input" name="payment_date">
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method</label>
                <select class="form-select" name="payment_method">
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="stripe">Stripe</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Reference</label>
                <input type="text" class="form-input" name="reference" placeholder="Check #, Transaction ID, etc.">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('payment-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Record Payment</button>
        </div>
    </form>
</div>

<script>
    const invoiceId = <?= json_encode($invoiceId) ?>;
    let invoice = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadInvoice();

        document.querySelector('[name="payment_date"]').value = new Date().toISOString().split('T')[0];

        document.getElementById('payment-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                await ERP.api.post(`/invoices/${invoiceId}/payments`, data);
                ERP.toast.success('Payment recorded');
                Modal.close('payment-modal');
                loadInvoice();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadInvoice() {
        try {
            const response = await ERP.api.get('/invoices/' + invoiceId);
            if (response.success) {
                invoice = response.data;
                renderInvoice();
            }
        } catch (error) {
            ERP.toast.error('Failed to load invoice');
        }
    }

    function renderInvoice() {
        document.getElementById('invoice-number').textContent = invoice.invoice_number;

        const statusEl = document.getElementById('invoice-status');
        statusEl.textContent = invoice.status;
        statusEl.className = 'badge badge-' + getStatusColor(invoice.status);

        // Show/hide buttons based on status
        document.getElementById('send-btn').style.display = invoice.status === 'draft' ? '' : 'none';
        document.getElementById('record-payment-btn').disabled = invoice.status === 'paid';

        // Client info
        document.getElementById('client-name').textContent = invoice.client_name || '-';
        document.getElementById('client-email').textContent = invoice.client_email || '';
        document.getElementById('client-address').textContent = [
            invoice.client_address,
            invoice.client_city,
            invoice.client_state
        ].filter(Boolean).join(', ') || '';

        // Dates
        document.getElementById('issue-date').textContent = formatDate(invoice.issue_date);
        document.getElementById('due-date').textContent = formatDate(invoice.due_date);
        document.getElementById('project-name').textContent = invoice.project_name || '-';

        // Line items
        const tbody = document.getElementById('items-tbody');
        if (invoice.items && invoice.items.length > 0) {
            tbody.innerHTML = invoice.items.map(item => `
            <tr>
                <td>${item.description}</td>
                <td class="text-right">${item.quantity}</td>
                <td class="text-right">${formatCurrency(item.unit_price)}</td>
                <td class="text-right">${formatCurrency(item.quantity * item.unit_price)}</td>
            </tr>
        `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted">No items</td></tr>';
        }

        // Totals
        document.getElementById('subtotal').textContent = formatCurrency(invoice.subtotal || 0);
        document.getElementById('tax-rate').textContent = invoice.tax_rate || 0;
        document.getElementById('tax-amount').textContent = formatCurrency(invoice.tax_amount || 0);
        document.getElementById('discount').textContent = '-' + formatCurrency(invoice.discount_amount || 0);
        document.getElementById('total-amount').textContent = formatCurrency(invoice.total_amount);
        document.getElementById('paid-amount').textContent = formatCurrency(invoice.paid_amount || 0);

        const balance = invoice.total_amount - (invoice.paid_amount || 0);
        document.getElementById('balance-due').textContent = formatCurrency(balance);
        document.getElementById('balance-display').textContent = formatCurrency(balance);
        document.getElementById('payment-amount').value = balance.toFixed(2);

        // Notes
        if (invoice.notes) {
            document.getElementById('notes-section').style.display = '';
            document.getElementById('invoice-notes').textContent = invoice.notes;
        }

        // Payment history
        renderPayments(invoice.payments || []);
    }

    function renderPayments(payments) {
        const container = document.getElementById('payments-list');
        if (!payments || payments.length === 0) {
            container.innerHTML = '<p class="text-muted text-center text-sm">No payments yet</p>';
            return;
        }

        container.innerHTML = payments.map(p => `
        <div class="flex justify-between items-center py-2 border-b last:border-0">
            <div>
                <div class="font-medium">${formatCurrency(p.amount)}</div>
                <div class="text-xs text-muted">${formatDate(p.payment_date)} • ${p.payment_method}</div>
            </div>
            <span class="badge badge-success text-xs">Paid</span>
        </div>
    `).join('');
    }

    async function sendInvoice() {
        if (!confirm('Send this invoice to the client?')) return;

        try {
            await ERP.api.post(`/invoices/${invoiceId}/send`, {});
            ERP.toast.success('Invoice sent to client');
            loadInvoice();
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    async function createPaymentLink() {
        try {
            const response = await ERP.api.post(`/invoices/${invoiceId}/payment-link`, {});
            if (response.success) {
                const link = response.data.url;
                navigator.clipboard.writeText(link);
                ERP.toast.success('Payment link copied to clipboard');
            }
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    function printInvoice() {
        window.print();
    }

    function getStatusColor(status) {
        return { draft: 'secondary', sent: 'primary', partial: 'warning', paid: 'success', overdue: 'error', cancelled: 'error' }[status] || 'secondary';
    }

    function formatCurrency(a) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(a || 0);
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
</script>

<style>
    .w-64 {
        width: 16rem;
    }

    .w-full {
        width: 100%;
    }

    .border-b {
        border-bottom: 1px solid var(--border-color);
    }

    .border-t {
        border-top: 1px solid var(--border-color);
    }

    .text-success {
        color: var(--success-500);
    }

    .text-right {
        text-align: right;
    }

    @media print {

        .sidebar,
        .btn,
        .card-header .btn,
        nav,
        header {
            display: none !important;
        }

        .card {
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>