<?php
$title = 'Projects';
$page = 'projects';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Projects</h1>
        <p class="text-muted text-sm">Manage construction projects and track progress</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('project-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        New Project
    </button>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4 items-center">
        <div class="flex-1">
            <input type="text" class="form-input" id="search-input" placeholder="Search projects...">
        </div>
        <select class="form-select" id="status-filter" style="width: 150px;">
            <option value="">All Status</option>
            <option value="planning">Planning</option>
            <option value="in_progress">In Progress</option>
            <option value="on_hold">On Hold</option>
            <option value="completed">Completed</option>
        </select>
        <select class="form-select" id="client-filter" style="width: 180px;">
            <option value="">All Clients</option>
        </select>
        <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
    </div>
</div>

<!-- Projects Grid -->
<div class="grid grid-cols-3" id="projects-grid">
    <div class="card">
        <div class="card-body text-center text-muted">
            <div class="animate-pulse">Loading projects...</div>
        </div>
    </div>
</div>

<!-- Pagination -->
<div class="flex justify-between items-center mt-6" id="pagination">
    <div class="text-sm text-muted" id="pagination-info">Showing 0 of 0 projects</div>
    <div class="flex gap-2">
        <button class="btn btn-secondary btn-sm" id="prev-btn" disabled>Previous</button>
        <button class="btn btn-secondary btn-sm" id="next-btn" disabled>Next</button>
    </div>
</div>

<!-- New Project Modal -->
<div class="modal" id="project-modal">
    <div class="modal-header">
        <h3 class="modal-title">Create New Project</h3>
        <button class="modal-close" onclick="Modal.close('project-modal')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>
    <form id="project-form">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Project Name</label>
                <input type="text" class="form-input" name="name" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label required">Client</label>
                    <select class="form-select" name="client_id" id="client-select" required>
                        <option value="">Select Client</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Project Code</label>
                    <div class="flex gap-2">
                        <input type="text" class="form-input" name="code" id="project-code" placeholder="e.g., PRJ-001">
                        <button type="button" class="btn btn-secondary" onclick="generateProjectCode()"
                            title="Generate Random Code">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                                </path>
                                <polyline points="7.5 4.21 12 6.81 16.5 4.21"></polyline>
                                <polyline points="7.5 19.79 7.5 14.6 3 12"></polyline>
                                <polyline points="21 12 16.5 14.6 16.5 19.79"></polyline>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" class="form-input" name="address">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-input" name="start_date">
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-input" name="end_date">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Estimated Hours</label>
                    <input type="number" class="form-input" name="estimated_hours" min="0">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('project-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Project</button>
        </div>
    </form>
</div>

<script>
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};

    document.addEventListener('DOMContentLoaded', function () {
        loadProjects();
        loadClients();

        // Search with debounce
        let searchTimeout;
        document.getElementById('search-input').addEventListener('input', function (e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentFilters.search = e.target.value;
                currentPage = 1;
                loadProjects();
            }, 300);
        });

        // Status filter
        document.getElementById('status-filter').addEventListener('change', function (e) {
            currentFilters.status = e.target.value;
            currentPage = 1;
            loadProjects();
        });

        // Client filter
        document.getElementById('client-filter').addEventListener('change', function (e) {
            currentFilters.client_id = e.target.value;
            currentPage = 1;
            loadProjects();
        });

        // Pagination
        document.getElementById('prev-btn').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                loadProjects();
            }
        });

        document.getElementById('next-btn').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                loadProjects();
            }
        });

        // Form submit
        document.getElementById('project-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);

            try {
                await ERP.api.post('/projects', data);
                ERP.toast.success('Project created successfully');
                Modal.close('project-modal');
                this.reset();
                loadProjects();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });
    });

    async function loadProjects() {
        const params = new URLSearchParams({
            page: currentPage,
            per_page: 9,
            ...currentFilters
        });

        try {
            const response = await ERP.api.get('/projects?' + params);
            if (response.success) {
                renderProjects(response.data);
                totalPages = response.meta.total_pages;
                updatePagination(response.meta);
            }
        } catch (error) {
            ERP.toast.error('Failed to load projects');
        }
    }

    async function loadClients() {
        try {
            const response = await ERP.api.get('/clients?per_page=100');
            if (response.success) {
                const options = response.data.map(c =>
                    `<option value="${c.id}">${c.name}</option>`
                ).join('');

                document.getElementById('client-select').innerHTML =
                    '<option value="">Select Client</option>' + options;
                document.getElementById('client-filter').innerHTML =
                    '<option value="">All Clients</option>' + options;
            }
        } catch (error) {
            console.error('Failed to load clients:', error);
        }
    }

    function renderProjects(projects) {
        const grid = document.getElementById('projects-grid');

        if (projects.length === 0) {
            grid.innerHTML = `
            <div class="card" style="grid-column: span 3;">
                <div class="card-body text-center text-muted">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem; opacity: 0.5;">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                    <p>No projects found</p>
                    <button class="btn btn-primary mt-4" onclick="Modal.open('project-modal')">Create First Project</button>
                </div>
            </div>
        `;
            return;
        }

        grid.innerHTML = projects.map(project => `
        <div class="card project-card" onclick="window.location.href=window.location.pathname + '/${project.id}'">
            <div class="card-body">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="font-medium text-lg">${project.name}</h3>
                        ${project.code ? `<span class="text-xs text-muted">${project.code}</span>` : ''}
                    </div>
                    <span class="badge badge-${getStatusBadge(project.status)}">
                        ${formatStatus(project.status)}
                    </span>
                </div>
                
                <p class="text-sm text-muted mb-4">${project.client_name || 'No client'}</p>
                
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span>Progress</span>
                        <span class="font-medium">${project.progress || 0}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar ${getProgressColor(project.progress)}" 
                             style="width: ${project.progress || 0}%"></div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-muted">Budget:</span>
                        <span class="font-medium">${formatCurrency(project.total_budget || 0)}</span>
                    </div>
                    <div>
                        <span class="text-muted">Spent:</span>
                        <span class="font-medium ${project.total_spent > project.total_budget ? 'text-error' : ''}">
                            ${formatCurrency(project.total_spent || 0)}
                        </span>
                    </div>
                </div>
                
                <!-- Financial Summary -->
                <div class="mt-3 pt-3 border-t">
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex items-center gap-2">
                            <span class="health-dot health-${project.health || 'green'}">●</span>
                            <span class="text-muted">Health</span>
                        </div>
                        <div class="text-right">
                            <span class="font-medium ${project.profit >= 0 ? 'text-success' : 'text-error'}">
                                ${project.profit >= 0 ? '+' : ''}${formatCurrency(project.profit || 0)}
                            </span>
                            <span class="text-xs text-muted ml-1">profit</span>
                        </div>
                    </div>
                    ${project.total_income > 0 ? `
                    <div class="flex justify-between text-xs mt-2">
                        <span class="text-muted">Income: ${formatCurrency(project.total_income)}</span>
                        <span class="text-muted">Expenses: ${formatCurrency(project.total_expenses || 0)}</span>
                    </div>
                    ` : ''}
                </div>
                
                ${project.end_date ? `
                <div class="mt-3 pt-3 border-t text-sm text-muted flex items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Due: ${formatDate(project.end_date)}
                </div>
                ` : ''}
            </div>
        </div>
    `).join('');
    }

    function updatePagination(meta) {
        document.getElementById('pagination-info').textContent =
            `Showing ${meta.from || 0}-${meta.to || 0} of ${meta.total || 0} projects`;
        document.getElementById('prev-btn').disabled = currentPage <= 1;
        document.getElementById('next-btn').disabled = currentPage >= totalPages;
    }

    function clearFilters() {
        currentFilters = {};
        currentPage = 1;
        document.getElementById('search-input').value = '';
        document.getElementById('status-filter').value = '';
        document.getElementById('client-filter').value = '';
        loadProjects();
    }

    function getStatusBadge(status) {
        const map = {
            'planning': 'primary',
            'in_progress': 'success',
            'on_hold': 'warning',
            'completed': 'secondary',
            'cancelled': 'error'
        };
        return map[status] || 'secondary';
    }

    function formatStatus(status) {
        return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    function getProgressColor(progress) {
        if (progress >= 100) return 'success';
        if (progress >= 50) return '';
        if (progress >= 25) return 'warning';
        return 'error';
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0
        }).format(amount);
    }

    function formatDate(date) {
        return new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    function generateProjectCode() {
        const prefix = 'PRJ';
        const random = Math.floor(1000 + Math.random() * 9000); // 4 digit random number
        const code = `${prefix}-${random}`;
        document.getElementById('project-code').value = code;
    }
</script>

<style>
    .project-card {
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .project-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .text-error {
        color: var(--error-500);
    }

    .text-success {
        color: var(--success-500);
    }

    .border-t {
        border-top: 1px solid var(--border-color);
    }

    .health-dot {
        font-size: 1.25rem;
        line-height: 1;
    }

    .health-green {
        color: #22c55e;
    }

    .health-yellow {
        color: #eab308;
    }

    .health-red {
        color: #ef4444;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
