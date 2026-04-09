/**
 * Cash Flow Module
 */

let trendChart = null;
let categoryChart = null;
let debounceTimer = null;

const state = {
    filters: {
        project_id: '',
        start_date: '',
        end_date: '',
        search: '',
        page: 1,
        per_page: 50
    },
    summary: null,
    transactions: [],
    categories: {
        income: ['Serviços', 'Materiais', 'Consultoria', 'Outros'],
        expense: ['Material de Construção', 'Mão de Obra', 'Logística', 'Equipamento', 'Administrativo', 'Impostos']
    }
};

document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    loadDashboard();
    loadProjects();
});

function initFilters() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    state.filters.start_date = firstDay.toISOString().split('T')[0];
    state.filters.end_date = lastDay.toISOString().split('T')[0];

    document.getElementById('filter-start').value = state.filters.start_date;
    document.getElementById('filter-end').value = state.filters.end_date;
}

/**
 * Load all dashboard data (Stats + Charts + Transactions)
 */
async function loadDashboard() {
    updateFilters();
    showLoading();
    
    await Promise.all([
        fetchSummary(),
        fetchTransactions()
    ]);
    
    hideLoading();
}

async function fetchSummary() {
    try {
        const query = new URLSearchParams({
            start_date: state.filters.start_date,
            end_date: state.filters.end_date,
            project_id: state.filters.project_id,
            search: state.filters.search
        });

        const response = await ERP.api.get(`/cash-flow/summary?${query}`);
        if (response.success) {
            state.summary = response.data;
            renderKPIs();
            renderCharts();
        }
    } catch (error) {
        ERP.ui.notify('error', 'Falha ao carregar resumo: ' + error.message);
    }
}

async function fetchTransactions() {
    try {
        const query = new URLSearchParams({
            ...state.filters
        });

        const response = await ERP.api.get(`/cash-flow/transactions?${query}`);
        if (response.success) {
            state.transactions = response.data.transactions;
            renderLedger(response.data.pagination);
            
            // Re-render KPIs to update Available Balance based on ledger results
            renderKPIs();
        }
    } catch (error) {
        ERP.ui.notify('error', 'Falha ao carregar transações: ' + error.message);
    }
}

function renderKPIs() {
    if (!state.summary) return;
    
    const s = state.summary.stats;
    document.getElementById('kpi-net-flow').textContent = formatCurrency(s.net_flow);
    document.getElementById('kpi-cash-in').textContent = formatCurrency(s.cash_in);
    document.getElementById('kpi-cash-out').textContent = formatCurrency(s.cash_out);
    document.getElementById('kpi-savings-rate').textContent = `${s.savings_rate}% de margem`;
    
    // Total balance (running balance total from latest transaction)
    if (state.transactions.length > 0) {
        // Find the absolute latest transaction (first in sorted list)
        const latest = state.transactions[0];
        document.getElementById('kpi-available-balance').textContent = formatCurrency(latest.running_balance);
        document.getElementById('kpi-available-balance').classList.remove('text-warning');
        document.getElementById('kpi-available-balance').classList.add(latest.running_balance >= 0 ? 'text-success' : 'text-error');
    }
}

function renderCharts() {
    const trendCtx = document.getElementById('cashTrendChart').getContext('2d');
    const trendData = state.summary.charts.trend;
    
    if (trendChart) trendChart.destroy();
    trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.month),
            datasets: [
                {
                    label: 'Entradas',
                    data: trendData.map(d => d.income),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Saídas',
                    data: trendData.map(d => d.expense),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    const catCtx = document.getElementById('categoryChart').getContext('2d');
    const catData = state.summary.charts.categories;
    
    if (categoryChart) categoryChart.destroy();
    categoryChart = new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: catData.map(d => d.category),
            datasets: [{
                data: catData.map(d => d.total),
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function renderLedger(pagination) {
    const body = document.getElementById('ledger-body');
    body.innerHTML = '';

    if (state.transactions.length === 0) {
        body.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-muted italic">Nenhum lançamento encontrado.</td></tr>';
        return;
    }

    state.transactions.forEach(t => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-4 py-3 text-sm">${t.date}</td>
            <td class="px-4 py-3">
                <span class="badge badge-${t.type === 'income' ? 'success' : 'error'} text-xs">
                    <i class="fas fa-arrow-${t.type === 'income' ? 'down' : 'up'} mr-1"></i>
                    ${t.type === 'income' ? 'Entrada' : 'Saída'}
                </span>
            </td>
            <td class="px-4 py-3 text-sm">
                <div class="font-medium">${t.description}</div>
                <div class="text-xs text-muted">${t.person || 'N/A'} • ${t.category}</div>
            </td>
            <td class="px-4 py-3 text-sm text-muted">${t.project_name || '-'}</td>
            <td class="px-4 py-3 text-sm text-right font-bold ${t.type === 'income' ? 'text-success' : 'text-error'}">
                ${t.type === 'income' ? '+' : '-'}${formatCurrency(Math.abs(t.amount))}
            </td>
            <td class="px-4 py-3 text-sm text-right font-mono text-gray-400">
                ${formatCurrency(t.running_balance)}
            </td>
        `;
        body.appendChild(row);
    });

    document.getElementById('pagination-info').textContent = 
        `Mostrando ${state.transactions.length} de ${pagination.total} lançamentos`;
}

function updateFilters() {
    state.filters.project_id = document.getElementById('filter-project').value;
    state.filters.start_date = document.getElementById('filter-start').value;
    state.filters.end_date = document.getElementById('filter-end').value;
    state.filters.search = document.getElementById('ledger-search').value;
}

/**
 * UI Actions
 */
function debounceLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadDashboard(); // Full reload to sync charts and KPIs with search
    }, 500);
}

function loadTransactions() {
    loadDashboard();
}

function openQuickEntryModal() {
    const modal = document.getElementById('quick-entry-modal');
    modal.classList.add('show');
    
    // Pre-populate project dropdown in modal
    const modalProject = document.getElementById('modal-project');
    const mainProject = document.getElementById('filter-project');
    modalProject.innerHTML = mainProject.innerHTML;
    
    updateCategoryOptions();
}

function closeQuickEntryModal() {
    document.getElementById('quick-entry-modal').classList.remove('show');
    document.getElementById('quick-entry-form').reset();
}

function updateCategoryOptions() {
    const type = document.querySelector('[name="type"]').value;
    const select = document.getElementById('modal-category');
    select.innerHTML = '';
    
    state.categories[type].forEach(cat => {
        const opt = document.createElement('option');
        opt.value = cat;
        opt.textContent = cat;
        select.appendChild(opt);
    });
}

async function saveQuickEntry(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await ERP.api.post('/cash-flow/summary', data); // We'll map POST /cash-flow/summary to CashFlowController@store
        if (response.success) {
            ERP.ui.notify('success', response.message);
            closeQuickEntryModal();
            loadDashboard();
        }
    } catch (e) {
        ERP.ui.notify('error', 'Falha ao salvar: ' + e.message);
    }
}

function exportToExcel() {
    updateFilters();
    const query = new URLSearchParams(state.filters);
    ERP.ui.notify('info', 'Gerando arquivo de exportação...');
    
    // Call the export API which returns a download_url
    ERP.api.get(`/reports/export?type=cash-flow&format=csv&${query}`)
        .then(res => {
            if (res.success && res.data.download_url) {
                window.location.href = res.data.download_url;
            }
        });
}

async function loadProjects() {
    try {
        const response = await ERP.api.get('/projects?per_page=100');
        if (response.success) {
            const select = document.getElementById('filter-project');
            response.data.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name;
                select.appendChild(opt);
            });
        }
    } catch (e) {}
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD' }).format(amount);
}

function showLoading() {
    document.getElementById('ledger-body').style.opacity = '0.5';
}

function hideLoading() {
    document.getElementById('ledger-body').style.opacity = '1';
}
