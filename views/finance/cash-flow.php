<?php
$title = 'Fluxo de Caixa (Cash Flow)';
$page = 'cash-flow';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Fluxo de Caixa</h1>
        <p class="text-muted text-sm">Visão geral unificada de entradas e saídas financeiras</p>
    </div>
    <div class="flex gap-2">
        <button class="btn btn-outline-primary" onclick="exportToExcel()">
            <i class="fas fa-file-excel mr-2"></i> Exportar Excel
        </button>
        <button class="btn btn-primary" onclick="openQuickEntryModal()">
            <i class="fas fa-plus mr-2"></i> Novo Lançamento
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="card bg-primary-light border-none shadow-sm overflow-hidden">
        <div class="card-body relative p-5">
            <div class="absolute right-[-10px] top-[-10px] opacity-10">
                <i class="fas fa-wallet text-6xl"></i>
            </div>
            <p class="text-xs uppercase font-bold tracking-wider text-primary mb-1">Saldo Líquido (Mês)</p>
            <h3 class="text-2xl font-black text-primary" id="kpi-net-flow">$0.00</h3>
            <p class="text-xs mt-2 text-primary-dark" id="kpi-savings-rate">0% de margem</p>
        </div>
    </div>

    <div class="card bg-success-light border-none shadow-sm overflow-hidden">
        <div class="card-body relative p-5">
            <div class="absolute right-[-10px] top-[-10px] opacity-10">
                <i class="fas fa-arrow-down text-6xl"></i>
            </div>
            <p class="text-xs uppercase font-bold tracking-wider text-success mb-1">Entradas</p>
            <h3 class="text-2xl font-black text-success" id="kpi-cash-in">$0.00</h3>
            <p class="text-xs mt-2 text-success-dark">Recebimentos confirmados</p>
        </div>
    </div>

    <div class="card bg-error-light border-none shadow-sm overflow-hidden">
        <div class="card-body relative p-5">
            <div class="absolute right-[-10px] top-[-10px] opacity-10">
                <i class="fas fa-arrow-up text-6xl"></i>
            </div>
            <p class="text-xs uppercase font-bold tracking-wider text-error mb-1">Saídas</p>
            <h3 class="text-2xl font-black text-error" id="kpi-cash-out">$0.00</h3>
            <p class="text-xs mt-2 text-error-dark">Despesas e Folha pagas</p>
        </div>
    </div>

    <div class="card bg-warning-light border-none shadow-sm overflow-hidden">
        <div class="card-body relative p-5">
            <div class="absolute right-[-10px] top-[-10px] opacity-10">
                <i class="fas fa-calendar-alt text-6xl"></i>
            </div>
            <p class="text-xs uppercase font-bold tracking-wider text-warning mb-1">Saldo Disponível</p>
            <h3 class="text-2xl font-black text-warning" id="kpi-available-balance">Calculando...</h3>
            <p class="text-xs mt-2 text-warning-dark">Total acumulado</p>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 card">
        <div class="card-header flex justify-between items-center bg-white border-b p-4">
            <h5 class="card-title font-bold m-0">Tendência Mensal (Entradas vs Saídas)</h5>
        </div>
        <div class="card-body p-4">
            <div style="height: 300px;">
                <canvas id="cashTrendChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-white border-b p-4">
            <h5 class="card-title font-bold m-0">Distribuição de Gastos</h5>
        </div>
        <div class="card-body p-4">
            <div style="height: 300px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Unified Ledger -->
<div class="card shadow-sm border-none">
    <div class="card-header bg-white border-b p-4 flex flex-wrap justify-between items-center gap-4">
        <h5 class="card-title font-bold m-0">Extrato Unificado (Ledger)</h5>
        
        <div class="flex flex-wrap gap-2 items-center">
            <div class="form-group mb-0">
                <select id="filter-project" class="form-select text-sm p-2" onchange="loadTransactions()">
                    <option value="">Todos os Projetos</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <input type="date" id="filter-start" class="form-input text-sm p-2" onchange="loadTransactions()">
            </div>
            <div class="form-group mb-0">
                <input type="date" id="filter-end" class="form-input text-sm p-2" onchange="loadTransactions()">
            </div>
            <div class="form-group mb-0">
                <input type="text" id="ledger-search" class="form-input text-sm p-2" placeholder="Buscar..." onkeyup="debounceLoad()">
            </div>
        </div>
    </div>
    
    <div class="card-body p-0 overflow-x-auto">
        <table class="table table-hover mb-0" id="cash-flow-table">
            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3 border-b text-left">Data</th>
                    <th class="px-4 py-3 border-b text-left">Tipo</th>
                    <th class="px-4 py-3 border-b text-left">Descrição</th>
                    <th class="px-4 py-3 border-b text-left">Projeto</th>
                    <th class="px-4 py-3 border-b text-right">Valor</th>
                    <th class="px-4 py-3 border-b text-right">Saldo</th>
                </tr>
            </thead>
            <tbody id="ledger-body">
                <!-- Rendered dynamically -->
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-t p-4 flex justify-between items-center">
        <span class="text-sm text-muted" id="pagination-info">Mostrando 0 de 0 lançamentos</span>
        <div class="flex gap-1" id="pagination-controls">
            <!-- Rendered dynamically -->
        </div>
    </div>
</div>

<!-- Chart.js CDN (Checking if already included in layouts/main.php typically happens via global scripts, but adding here just in case or for explicit dependency) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/assets/js/cash-flow.js"></script>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>
