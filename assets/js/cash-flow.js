/**
 * Cash Flow (Fluxo de Caixa) Module
 */

let trendChart = null;
let categoryChart = null;
let debounceTimer = null;

const state = {
    filters: {
        project_id: '',
        start_date: '', // Will be set to 1st of current month
        end_date: '',   // Will be set to last day of current month
        search: '',
        page: 1,
        per_page: 50
    },
    summary: null,
    transactions: []
};

document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    loadDashboard();
    loadProjects(); // For the filter dropdown
});

/**
 * Initialize filter values
 */
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
 * Load all dashboard data (stats + charts + ledger)
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

/**
 * Fetch summary stats and chart data
 */
async function fetchSummary() {
    try {
        const query = new URLSearchParams({
            start_date: state.filters.start_date,
            end_date: state.filters.end_date,
            project_id: state.filters.project_id
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

/**
 * Fetch unified transaction ledger
 */
async function fetchTransactions() {
    try {
        const query = new URLSearchParams({
            ...state.filters
        });

        const response = await ERP.api.get(`/cash-flow/transactions?${query}`);
        if (response.success) {
            state.transactions = response.data.transactions;
            renderLedger(response.data.pagination);
        }
    } catch (error) {
        ERP.ui.notify('error', 'Falha ao carregar transações: ' + error.message);
    }
}

/**
 * Render KPI Cards
 */
function renderKPIs() {
    const s = state.summary.stats;
    document.getElementById('kpi-net-flow').textContent = formatCurrency(s.net_flow);
    document.getElementById('kpi-cash-in').textContent = formatCurrency(s.cash_in);
    document.getElementById('kpi-cash-out').textContent = formatCurrency(s.cash_out);
    document.getElementById('kpi-savings-rate').textContent = `${s.savings_rate}% de margem`;
    
    // Total balance (running balance total from latest transaction)
    if (state.transactions.length > 0) {
        const latest = state.transactions[0];
        document.getElementById('kpi-available-balance').textContent = formatCurrency(latest.running_balance);
    }
}

/**
 * Render/Update Charts
 */
function renderCharts() {
    // 1. Trend Chart
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
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: (value) => '$' + value } }
            }
        }
    });

    // 2. Category Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    const catData = state.summary.charts.categories;
    
    if (categoryChart) categoryChart.destroy();
    
    categoryChart = new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: catData.map(d => d.category),
            datasets: [{
                data: catData.map(d => d.total),
                backgroundColor: [
                    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', 
                    '#ec4899', '#06b6d4', '#475569'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

/**
 * Render Ledger Table
 */
function renderLedger(pagination) {
    const body = document.getElementById('ledger-body');
    body.innerHTML = '';

    if (state.transactions.length === 0) {
        body.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-muted italic">Nenhum lançamento encontrado para os filtros selecionados.</td></tr>';
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
            <td class="px-4 py-3 text-sm text-right font-mono text-gray-500">
                ${formatCurrency(t.running_balance)}
            </td>
        `;
        body.appendChild(row);
    });

    // Pagination info
    document.getElementById('pagination-info').textContent = 
        `Mostrando ${state.transactions.length} de ${pagination.total} lançamentos (Página ${pagination.current_page} de ${pagination.total_pages})`;
}

/**
 * Update state from UI filters
 */
function updateFilters() {
    state.filters.project_id = document.getElementById('filter-project').value;
    state.filters.start_date = document.getElementById('filter-start').value;
    state.filters.end_date = document.getElementById('filter-end').value;
    state.filters.search = document.getElementById('ledger-search').value;
}

/**
 * Project list for dropdown
 */
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

function debounceLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadTransactions();
    }, 500);
}

function loadTransactions() {
    updateFilters();
    fetchTransactions();
}

/**
 * Format Currency (BRL/USD based on preference, defaulting to ERP standard)
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-AU', { 
        style: 'currency', 
        currency: 'AUD' // Traffio usually uses AUD/USD, adapting to layout
    }).format(amount);
}

function showLoading() {
    // Logic for loading overlays if needed
}

function hideLoading() {
    // Logic for hiding loading overlays if needed
}

function openQuickEntryModal() {
    ERP.ui.notify('info', 'Funcionalidade de lançamento rápido em desenvolvimento.');
}

function exportToExcel() {
    const query = new URLSearchParams(state.filters);
    window.location.href = `/api/reports/export?type=cash-flow&format=csv&${query}`;
}
