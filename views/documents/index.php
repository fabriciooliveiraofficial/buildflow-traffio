<?php
$title = 'Documents';
$page = 'documents';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Documents</h1>
        <p class="text-muted text-sm">Manage files and documents across projects</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('upload-modal')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="17 8 12 3 7 8" />
            <line x1="12" y1="3" x2="12" y2="15" />
        </svg>
        Upload Document
    </button>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body flex gap-4 items-center">
        <div class="flex-1">
            <input type="text" class="form-input" id="search-input" placeholder="Search documents...">
        </div>
        <select class="form-select" id="project-filter" style="width: 180px;">
            <option value="">All Projects</option>
        </select>
        <select class="form-select" id="category-filter" style="width: 150px;">
            <option value="">All Categories</option>
            <option value="contract">Contracts</option>
            <option value="permit">Permits</option>
            <option value="plan">Plans/Blueprints</option>
            <option value="invoice">Invoices</option>
            <option value="report">Reports</option>
            <option value="other">Other</option>
        </select>
        <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
    </div>
</div>

<!-- Documents Grid -->
<div class="grid grid-cols-4 gap-4" id="documents-grid">
    <div class="text-center text-muted p-8">Loading documents...</div>
</div>

<!-- Pagination -->
<div class="flex justify-between items-center mt-6">
    <div class="text-sm text-muted" id="pagination-info">Showing 0 of 0</div>
    <div class="flex gap-2">
        <button class="btn btn-secondary btn-sm" id="prev-btn" disabled>Previous</button>
        <button class="btn btn-secondary btn-sm" id="next-btn" disabled>Next</button>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal" id="upload-modal" style="max-width: 500px;">
    <div class="modal-header">
        <h3 class="modal-title">Upload Document</h3>
        <button class="modal-close" onclick="Modal.close('upload-modal')">×</button>
    </div>
    <form id="upload-form" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">File</label>
                <input type="file" class="form-input" name="file" id="file-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Title (optional)</label>
                <input type="text" class="form-input" name="title" placeholder="Auto-detected from filename">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Project</label>
                    <select class="form-select" name="project_id" id="project-select">
                        <option value="">No Project</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="other">Other</option>
                        <option value="contract">Contract</option>
                        <option value="permit">Permit</option>
                        <option value="plan">Plan/Blueprint</option>
                        <option value="invoice">Invoice</option>
                        <option value="report">Report</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-input" name="notes" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('upload-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>
    </form>
</div>

<script>
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};

    document.addEventListener('DOMContentLoaded', function () {
        loadDocuments();
        loadProjects();

        document.getElementById('upload-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch(ERP.api.baseUrl + '/documents', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + token },
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    ERP.toast.success('Document uploaded');
                    Modal.close('upload-modal');
                    this.reset();
                    loadDocuments();
                } else {
                    ERP.toast.error(result.message || 'Upload failed');
                }
            } catch (error) {
                ERP.toast.error('Upload failed');
            }
        });

        document.getElementById('search-input').addEventListener('input', debounce(applyFilters, 300));
        document.getElementById('project-filter').addEventListener('change', applyFilters);
        document.getElementById('category-filter').addEventListener('change', applyFilters);

        document.getElementById('prev-btn').addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; loadDocuments(); }
        });
        document.getElementById('next-btn').addEventListener('click', () => {
            if (currentPage < totalPages) { currentPage++; loadDocuments(); }
        });
    });

    async function loadDocuments() {
        const params = new URLSearchParams({ page: currentPage, per_page: 12, ...currentFilters });

        try {
            const response = await ERP.api.get('/documents?' + params);
            if (response.success) {
                renderDocuments(response.data);
                totalPages = response.meta.total_pages || 1;
                updatePagination(response.meta);
            }
        } catch (error) {
            ERP.toast.error('Failed to load documents');
        }
    }

    async function loadProjects() {
        try {
            const response = await ERP.api.get('/projects?per_page=100');
            if (response.success) {
                const options = response.data.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
                document.getElementById('project-select').innerHTML = '<option value="">No Project</option>' + options;
                document.getElementById('project-filter').innerHTML = '<option value="">All Projects</option>' + options;
            }
        } catch (error) { console.error(error); }
    }

    function renderDocuments(docs) {
        const grid = document.getElementById('documents-grid');

        if (!docs || docs.length === 0) {
            grid.innerHTML = '<div class="text-center text-muted p-8 col-span-4">No documents found. Upload your first document.</div>';
            return;
        }

        grid.innerHTML = docs.map(d => `
            <div class="card doc-card">
                <div class="card-body">
                    <div class="doc-icon">${getFileIcon(d.file_type || d.filename)}</div>
                    <h4 class="doc-title">${d.title || d.filename || 'Untitled'}</h4>
                    <div class="text-sm text-muted mb-2">${d.project_name || 'No Project'}</div>
                    ${d.category ? `<span class="badge badge-secondary mb-2">${d.category}</span>` : ''}
                    <div class="text-xs text-muted">${formatDate(d.created_at)}</div>
                    <div class="flex gap-2 mt-3">
                        <a href="${ERP.api.baseUrl}/documents/${d.id}/download" class="btn btn-sm btn-primary flex-1" target="_blank">Download</a>
                        <button class="btn btn-sm btn-error" onclick="deleteDocument(${d.id})">🗑</button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function updatePagination(meta) {
        document.getElementById('pagination-info').textContent =
            `Showing ${meta.from || 0}-${meta.to || 0} of ${meta.total || 0}`;
        document.getElementById('prev-btn').disabled = currentPage <= 1;
        document.getElementById('next-btn').disabled = currentPage >= totalPages;
    }

    function applyFilters() {
        currentFilters = {};
        const search = document.getElementById('search-input').value;
        const project = document.getElementById('project-filter').value;
        const category = document.getElementById('category-filter').value;

        if (search) currentFilters.search = search;
        if (project) currentFilters.project_id = project;
        if (category) currentFilters.category = category;

        currentPage = 1;
        loadDocuments();
    }

    function clearFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('project-filter').value = '';
        document.getElementById('category-filter').value = '';
        currentFilters = {};
        currentPage = 1;
        loadDocuments();
    }

    async function deleteDocument(id) {
        if (!confirm('Delete this document?')) return;
        try {
            await ERP.api.delete('/documents/' + id);
            ERP.toast.success('Document deleted');
            loadDocuments();
        } catch (error) { ERP.toast.error(error.message); }
    }

    function getFileIcon(filename) {
        if (!filename) return '📄';
        const ext = filename.split('.').pop()?.toLowerCase();
        const icons = {
            pdf: '📕', doc: '📘', docx: '📘', xls: '📗', xlsx: '📗',
            jpg: '🖼️', jpeg: '🖼️', png: '🖼️', gif: '🖼️',
            zip: '📦', rar: '📦', txt: '📝', csv: '📊'
        };
        return icons[ext] || '📄';
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function debounce(fn, delay) {
        let timer;
        return function (...args) { clearTimeout(timer); timer = setTimeout(() => fn.apply(this, args), delay); };
    }
</script>

<style>
    .doc-card {
        text-align: center;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .doc-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .doc-icon {
        font-size: 3rem;
        margin-bottom: var(--space-2);
    }

    .doc-title {
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: var(--space-1);
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>