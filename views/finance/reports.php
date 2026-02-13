<?php
$title = 'Financial Reports';
$page = 'financial-reports';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Financial Reports</h1>
        <p class="text-muted text-sm">View your financial statements</p>
    </div>
</div>

<!-- Report Tabs -->
<div class="card mb-6">
    <div class="card-body">
        <div class="flex gap-2 mb-4">
            <button class="btn btn-primary" id="tab-trial" onclick="switchTab('trial')">Trial Balance</button>
            <button class="btn btn-secondary" id="tab-pl" onclick="switchTab('pl')">Income Statement</button>
            <button class="btn btn-secondary" id="tab-bs" onclick="switchTab('bs')">Balance Sheet</button>
        </div>

        <!-- Date Filters -->
        <div class="flex gap-4 items-end" id="date-filters">
            <div class="form-group mb-0" id="single-date-group">
                <label class="form-label">As of Date</label>
                <input type="date" class="form-input" id="as-of-date">
            </div>
            <div class="form-group mb-0" id="start-date-group" style="display: none;">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-input" id="start-date">
            </div>
            <div class="form-group mb-0" id="end-date-group" style="display: none;">
                <label class="form-label">End Date</label>
                <input type="date" class="form-input" id="end-date">
            </div>
            <button class="btn btn-primary" onclick="loadReport()">Generate</button>
        </div>
    </div>
</div>

<!-- Report Content -->
<div class="card">
    <div class="card-body" id="report-content">
        <p class="text-center text-muted">Select a report and click Generate</p>
    </div>
</div>

<script>
    let currentTab = 'trial';

    document.addEventListener('DOMContentLoaded', function () {
        // Set default dates
        const today = new Date();
        document.getElementById('as-of-date').value = today.toISOString().split('T')[0];
        document.getElementById('end-date').value = today.toISOString().split('T')[0];
        document.getElementById('start-date').value = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];

        loadReport();
    });

    function switchTab(tab) {
        currentTab = tab;

        // Update button states
        document.querySelectorAll('[id^="tab-"]').forEach(btn => btn.classList.remove('btn-primary'));
        document.querySelectorAll('[id^="tab-"]').forEach(btn => btn.classList.add('btn-secondary'));
        document.getElementById('tab-' + tab).classList.remove('btn-secondary');
        document.getElementById('tab-' + tab).classList.add('btn-primary');

        // Show/hide date fields
        if (tab === 'pl') {
            document.getElementById('single-date-group').style.display = 'none';
            document.getElementById('start-date-group').style.display = '';
            document.getElementById('end-date-group').style.display = '';
        } else {
            document.getElementById('single-date-group').style.display = '';
            document.getElementById('start-date-group').style.display = 'none';
            document.getElementById('end-date-group').style.display = 'none';
        }

        loadReport();
    }

    async function loadReport() {
        const content = document.getElementById('report-content');
        content.innerHTML = '<p class="text-center text-muted">Loading...</p>';

        try {
            let response;
            if (currentTab === 'trial') {
                const asOf = document.getElementById('as-of-date').value;
                response = await ERP.api.get('/reports/trial-balance?as_of=' + asOf);
                if (response.success) renderTrialBalance(response.data);
            } else if (currentTab === 'pl') {
                const start = document.getElementById('start-date').value;
                const end = document.getElementById('end-date').value;
                response = await ERP.api.get('/reports/income-statement?start_date=' + start + '&end_date=' + end);
                if (response.success) renderIncomeStatement(response.data);
            } else if (currentTab === 'bs') {
                const asOf = document.getElementById('as-of-date').value;
                response = await ERP.api.get('/reports/balance-sheet?as_of=' + asOf);
                if (response.success) renderBalanceSheet(response.data);
            }
        } catch (error) {
            content.innerHTML = '<p class="text-center text-error">Failed to load report: ' + error.message + '</p>';
        }
    }

    function renderTrialBalance(data) {
        let html = `
            <h3 class="mb-4">Trial Balance as of ${formatDate(data.as_of)}</h3>
            <table class="table">
                <thead>
                    <tr><th>Code</th><th>Account</th><th>Type</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr>
                </thead>
                <tbody>
                    ${data.accounts.filter(a => a.total_debit > 0 || a.total_credit > 0).map(a => `
                        <tr>
                            <td class="font-mono">${a.code}</td>
                            <td>${a.name}</td>
                            <td><span class="badge badge-${getTypeBadge(a.type)}">${capitalize(a.type)}</span></td>
                            <td class="text-right">${a.total_debit > 0 ? formatCurrency(a.total_debit) : ''}</td>
                            <td class="text-right">${a.total_credit > 0 ? formatCurrency(a.total_credit) : ''}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td colspan="3" class="text-right">Totals:</td>
                        <td class="text-right">${formatCurrency(data.total_debit)}</td>
                        <td class="text-right">${formatCurrency(data.total_credit)}</td>
                    </tr>
                </tfoot>
            </table>
            <p class="mt-4 ${data.is_balanced ? 'text-success' : 'text-error'}">
                ${data.is_balanced ? '✓ Books are balanced' : '✗ Books are NOT balanced!'}
            </p>
        `;
        document.getElementById('report-content').innerHTML = html;
    }

    function renderIncomeStatement(data) {
        let html = `
            <h3 class="mb-4">Income Statement: ${formatDate(data.period.start)} to ${formatDate(data.period.end)}</h3>
            
            <h4 class="mt-4 mb-2 font-semibold text-success">Revenue</h4>
            <table class="table mb-4">
                <tbody>
                    ${data.income.map(a => `
                        <tr><td class="font-mono">${a.code}</td><td>${a.name}</td><td class="text-right">${formatCurrency(a.amount)}</td></tr>
                    `).join('') || '<tr><td colspan="3" class="text-muted">No income recorded</td></tr>'}
                </tbody>
                <tfoot>
                    <tr class="font-bold border-t"><td colspan="2">Total Revenue</td><td class="text-right">${formatCurrency(data.total_income)}</td></tr>
                </tfoot>
            </table>
            
            <h4 class="mt-4 mb-2 font-semibold text-error">Expenses</h4>
            <table class="table mb-4">
                <tbody>
                    ${data.expenses.map(a => `
                        <tr><td class="font-mono">${a.code}</td><td>${a.name}</td><td class="text-right">${formatCurrency(a.amount)}</td></tr>
                    `).join('') || '<tr><td colspan="3" class="text-muted">No expenses recorded</td></tr>'}
                </tbody>
                <tfoot>
                    <tr class="font-bold border-t"><td colspan="2">Total Expenses</td><td class="text-right">${formatCurrency(data.total_expenses)}</td></tr>
                </tfoot>
            </table>
            
            <div class="p-4 rounded ${data.net_income >= 0 ? 'bg-success-light' : 'bg-error-light'}">
                <h4 class="text-lg font-bold">Net Income: <span class="${data.net_income >= 0 ? 'text-success' : 'text-error'}">${formatCurrency(data.net_income)}</span></h4>
            </div>
        `;
        document.getElementById('report-content').innerHTML = html;
    }

    function renderBalanceSheet(data) {
        let html = `
            <h3 class="mb-4">Balance Sheet as of ${formatDate(data.as_of)}</h3>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h4 class="mb-2 font-semibold">Assets</h4>
                    <table class="table mb-4">
                        <tbody>
                            ${data.assets.map(a => `
                                <tr><td class="font-mono">${a.code}</td><td>${a.name}</td><td class="text-right">${formatCurrency(a.amount)}</td></tr>
                            `).join('') || '<tr><td colspan="3" class="text-muted">No assets</td></tr>'}
                        </tbody>
                        <tfoot>
                            <tr class="font-bold border-t"><td colspan="2">Total Assets</td><td class="text-right">${formatCurrency(data.total_assets)}</td></tr>
                        </tfoot>
                    </table>
                </div>
                
                <div>
                    <h4 class="mb-2 font-semibold">Liabilities</h4>
                    <table class="table mb-4">
                        <tbody>
                            ${data.liabilities.map(a => `
                                <tr><td class="font-mono">${a.code}</td><td>${a.name}</td><td class="text-right">${formatCurrency(a.amount)}</td></tr>
                            `).join('') || '<tr><td colspan="3" class="text-muted">No liabilities</td></tr>'}
                        </tbody>
                        <tfoot>
                            <tr class="font-bold border-t"><td colspan="2">Total Liabilities</td><td class="text-right">${formatCurrency(data.total_liabilities)}</td></tr>
                        </tfoot>
                    </table>
                    
                    <h4 class="mb-2 font-semibold">Equity</h4>
                    <table class="table">
                        <tbody>
                            ${data.equity.map(a => `
                                <tr><td class="font-mono">${a.code}</td><td>${a.name}</td><td class="text-right">${formatCurrency(a.amount)}</td></tr>
                            `).join('')}
                            <tr><td></td><td>Retained Earnings</td><td class="text-right">${formatCurrency(data.retained_earnings)}</td></tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-bold border-t"><td colspan="2">Total Equity</td><td class="text-right">${formatCurrency(data.total_equity)}</td></tr>
                            <tr class="font-bold"><td colspan="2">Total Liabilities + Equity</td><td class="text-right">${formatCurrency(data.total_liabilities + data.total_equity)}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <p class="mt-4 ${data.is_balanced ? 'text-success' : 'text-error'}">
                ${data.is_balanced ? '✓ Balance sheet is balanced' : '✗ Balance sheet is NOT balanced!'}
            </p>
        `;
        document.getElementById('report-content').innerHTML = html;
    }

    function getTypeBadge(type) {
        return { 'asset': 'primary', 'liability': 'warning', 'equity': 'secondary', 'income': 'success', 'expense': 'error' }[type] || 'secondary';
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
    }

    function formatDate(dateStr) {
        return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
</script>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>