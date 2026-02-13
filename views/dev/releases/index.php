<?php
/**
 * Version Releases Management Page
 * Admin panel for managing system versions and releases
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check dev auth
if (!isset($_SESSION['dev_user'])) {
    header('Location: /dev/login');
    exit;
}

$title = 'Version Releases';
$page = 'releases';
$devUser = $_SESSION['dev_user'] ?? null;

ob_start();
?>

<!-- Stats Dashboard -->
<div class="grid grid-cols-4 mb-6" id="stats-row">
    <div class="stat-card">
        <div class="stat-value" id="stat-current">-</div>
        <div class="stat-label">Current Version</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="stat-total">-</div>
        <div class="stat-label">Total Releases</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="stat-published" style="color: #4ade80;">-</div>
        <div class="stat-label">Published</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="stat-drafts" style="color: #fbbf24;">-</div>
        <div class="stat-label">Drafts</div>
    </div>
</div>

<!-- Actions Bar -->
<div class="card mb-4">
    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="margin: 0; font-size: 18px;">Version Releases</h3>
            <p style="margin: 4px 0 0; color: var(--dev-muted); font-size: 14px;">
                Manage system versions and publish updates to users
            </p>
        </div>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-primary" onclick="quickRelease()"
                style="background: linear-gradient(135deg, #22c55e, #16a34a); border-color: #22c55e;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 2L11 13" />
                    <path d="M22 2L15 22l-4-9-9-4 20-7z" />
                </svg>
                🚀 Quick Release
            </button>
            <button class="btn btn-secondary" onclick="showCreateModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Custom Release
            </button>
        </div>
    </div>
</div>

<!-- Releases Table -->
<div class="card">
    <div class="table-container" style="overflow-x: auto;">
        <table class="table" id="releases-table">
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Changes</th>
                    <th>Created</th>
                    <th>Published</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="releases-body">
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--dev-muted);">Loading
                        releases...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Release Modal -->
<div class="modal-backdrop" id="release-modal" style="display: none;">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">Create New Release</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="release-form" onsubmit="saveRelease(event)">
            <div class="modal-body">
                <input type="hidden" id="release-id">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Version *</label>
                        <input type="text" class="form-control" id="release-version" placeholder="e.g. 1.1.0" required>
                        <small style="color: var(--dev-muted);">Use semantic versioning (major.minor.patch)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Build Number</label>
                        <input type="text" class="form-control" id="release-build" placeholder="e.g. 20241212">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Release Name</label>
                    <input type="text" class="form-control" id="release-name" placeholder="e.g. Winter Update">
                </div>

                <div class="form-group">
                    <label class="form-label">Release Notes</label>
                    <textarea class="form-control" id="release-notes" rows="2"
                        placeholder="Brief summary of this release..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">🎨 New Features (one per line)</label>
                    <textarea class="form-control" id="release-features" rows="3"
                        placeholder="New dashboard design&#10;Export to PDF&#10;Dark mode support"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">🐛 Bug Fixes (one per line)</label>
                    <textarea class="form-control" id="release-fixes" rows="3"
                        placeholder="Fixed login timeout&#10;Resolved invoice calculation error"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">⚡ Improvements (one per line)</label>
                    <textarea class="form-control" id="release-improvements" rows="3"
                        placeholder="Faster page loading&#10;Improved mobile responsiveness"></textarea>
                </div>

                <div class="form-group"
                    style="border-top: 1px solid var(--dev-border); padding-top: 16px; margin-top: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="release-force-update">
                        <span>Force Update (require all users to update)</span>
                    </label>
                </div>

                <div class="form-group" id="force-message-group" style="display: none;">
                    <label class="form-label">Force Update Message</label>
                    <input type="text" class="form-control" id="release-force-message"
                        placeholder="A critical security update is required...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="save-btn">Save Draft</button>
            </div>
        </form>
    </div>
</div>

<style>
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-badge.published {
        background: rgba(74, 222, 128, 0.2);
        color: #4ade80;
    }

    .status-badge.draft {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
    }

    .changes-count {
        display: flex;
        gap: 12px;
        font-size: 13px;
    }

    .changes-count span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal {
        background: var(--dev-surface);
        border-radius: 12px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--dev-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-footer {
        padding: 16px 20px;
        border-top: 1px solid var(--dev-border);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        color: var(--dev-muted);
        cursor: pointer;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        background: var(--dev-bg);
        border: 1px solid var(--dev-border);
        border-radius: 6px;
        color: var(--dev-text);
        font-size: 14px;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--dev-primary);
    }
</style>

<script>
    let releases = [];

    document.addEventListener('DOMContentLoaded', function () {
        loadReleases();

        // Toggle force update message field
        document.getElementById('release-force-update').addEventListener('change', function () {
            document.getElementById('force-message-group').style.display = this.checked ? 'block' : 'none';
        });
    });

    async function loadReleases() {
        try {
            const response = await ERP.api.get('/dev/releases');
            if (response.success) {
                releases = response.data.releases || [];
                const stats = response.data.stats || {};

                // Update stats
                document.getElementById('stat-current').textContent = stats.current_version || '-';
                document.getElementById('stat-total').textContent = stats.total || 0;
                document.getElementById('stat-published').textContent = stats.published || 0;
                document.getElementById('stat-drafts').textContent = stats.drafts || 0;

                renderReleases(releases);
            }
        } catch (error) {
            console.error('Failed to load releases:', error);
            document.getElementById('releases-body').innerHTML =
                '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #f87171;">Failed to load releases</td></tr>';
        }
    }

    function renderReleases(releases) {
        const tbody = document.getElementById('releases-body');

        if (!releases || releases.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 60px; color: var(--dev-muted);">No releases yet. Create your first release!</td></tr>';
            return;
        }

        tbody.innerHTML = releases.map(r => {
            const features = r.features?.length || 0;
            const fixes = r.fixes?.length || 0;
            const improvements = r.improvements?.length || 0;

            return `
                <tr data-id="${r.id}">
                    <td>
                        <strong style="color: #a78bfa;">v${escapeHtml(r.version)}</strong>
                        ${r.force_update ? '<span style="color: #f87171; margin-left: 8px;" title="Force Update">⚠️</span>' : ''}
                    </td>
                    <td>${escapeHtml(r.name || '-')}</td>
                    <td>
                        <span class="status-badge ${r.is_published ? 'published' : 'draft'}">
                            ${r.is_published ? '✓ Published' : 'Draft'}
                        </span>
                    </td>
                    <td>
                        <div class="changes-count">
                            <span title="Features">🎨 ${features}</span>
                            <span title="Fixes">🐛 ${fixes}</span>
                            <span title="Improvements">⚡ ${improvements}</span>
                        </div>
                    </td>
                    <td style="color: var(--dev-muted); font-size: 13px;">${formatDate(r.created_at)}</td>
                    <td style="color: var(--dev-muted); font-size: 13px;">${r.published_at ? formatDate(r.published_at) : '-'}</td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            ${!r.is_published ? `
                                <button class="btn btn-sm btn-primary" onclick="publishRelease(${r.id}, '${r.version}')">Publish</button>
                                <button class="btn btn-sm btn-secondary" onclick="editRelease(${r.id})">Edit</button>
                                <button class="btn btn-sm btn-secondary" onclick="deleteRelease(${r.id})" style="color: #f87171;">Delete</button>
                            ` : `
                                <button class="btn btn-sm btn-secondary" onclick="viewRelease(${r.id})">View</button>
                            `}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function showCreateModal() {
        document.getElementById('modal-title').textContent = 'Create New Release';
        document.getElementById('save-btn').textContent = 'Save Draft';
        document.getElementById('release-form').reset();
        document.getElementById('release-id').value = '';
        document.getElementById('release-build').value = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        document.getElementById('force-message-group').style.display = 'none';
        document.getElementById('release-modal').style.display = 'flex';
    }

    function editRelease(id) {
        const release = releases.find(r => r.id == id);
        if (!release) return;

        document.getElementById('modal-title').textContent = 'Edit Release';
        document.getElementById('save-btn').textContent = 'Save Changes';
        document.getElementById('release-id').value = release.id;
        document.getElementById('release-version').value = release.version;
        document.getElementById('release-build').value = release.build || '';
        document.getElementById('release-name').value = release.name || '';
        document.getElementById('release-notes').value = release.release_notes || '';
        document.getElementById('release-features').value = (release.features || []).join('\n');
        document.getElementById('release-fixes').value = (release.fixes || []).join('\n');
        document.getElementById('release-improvements').value = (release.improvements || []).join('\n');
        document.getElementById('release-force-update').checked = release.force_update;
        document.getElementById('release-force-message').value = release.force_update_message || '';
        document.getElementById('force-message-group').style.display = release.force_update ? 'block' : 'none';

        document.getElementById('release-modal').style.display = 'flex';
    }

    function viewRelease(id) {
        const release = releases.find(r => r.id == id);
        if (!release) return;

        // For published releases, show in read-only mode
        editRelease(id);
        document.getElementById('modal-title').textContent = 'View Release (Published)';
        document.getElementById('save-btn').style.display = 'none';
    }

    function closeModal() {
        document.getElementById('release-modal').style.display = 'none';
        document.getElementById('save-btn').style.display = 'block';
    }

    async function saveRelease(e) {
        e.preventDefault();

        const id = document.getElementById('release-id').value;
        const data = {
            version: document.getElementById('release-version').value,
            build: document.getElementById('release-build').value,
            name: document.getElementById('release-name').value,
            release_notes: document.getElementById('release-notes').value,
            features: textToArray(document.getElementById('release-features').value),
            fixes: textToArray(document.getElementById('release-fixes').value),
            improvements: textToArray(document.getElementById('release-improvements').value),
            force_update: document.getElementById('release-force-update').checked,
            force_update_message: document.getElementById('release-force-message').value
        };

        try {
            let response;
            if (id) {
                response = await ERP.api.put(`/dev/releases/${id}`, data);
            } else {
                response = await ERP.api.post('/dev/releases', data);
            }

            if (response.success) {
                ERP.toast.success(response.message || 'Release saved');
                closeModal();
                loadReleases();
            } else {
                ERP.toast.error(response.error || 'Failed to save');
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to save release');
        }
    }

    async function publishRelease(id, version) {
        if (!confirm(`Publish version ${version}?\n\nAll users will be notified to update on their next page load.`)) {
            return;
        }

        try {
            const response = await ERP.api.post(`/dev/releases/${id}/publish`);
            if (response.success) {
                ERP.toast.success(response.message || `Version ${version} published!`);
                loadReleases();
            } else {
                ERP.toast.error(response.error || 'Failed to publish');
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to publish release');
        }
    }

    async function deleteRelease(id) {
        if (!confirm('Delete this release draft?')) {
            return;
        }

        try {
            const response = await ERP.api.delete(`/dev/releases/${id}`);
            if (response.success) {
                ERP.toast.success('Release deleted');
                loadReleases();
            } else {
                ERP.toast.error(response.error || 'Failed to delete');
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to delete release');
        }
    }

    // Helpers
    function textToArray(text) {
        return text.split('\n').map(s => s.trim()).filter(s => s.length > 0);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Quick Release - Auto-increment version and publish immediately
    async function quickRelease() {
        console.log('[QuickRelease] Button clicked');

        // Calculate next version
        const currentVersion = document.getElementById('stat-current').textContent || '1.0.0';
        const nextVersion = incrementVersion(currentVersion);
        console.log('[QuickRelease] Current:', currentVersion, '-> Next:', nextVersion);
        console.log('[QuickRelease] Calling API...');

        try {
            // Use fetch directly
            const response = await fetch('/api/dev/releases/quick', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ next_version: nextVersion })
            });

            const data = await response.json();
            console.log('[QuickRelease] Response:', data);

            if (data.success) {
                console.log('[QuickRelease] SUCCESS! Version', data.data?.version, 'released');
                alert('🚀 Version ' + (data.data?.version || nextVersion) + ' released!\n\nUsers will be notified on their next page load.');
                loadReleases();
            } else {
                console.error('[QuickRelease] API Error:', data.error);
                alert('Error: ' + (data.error || 'Failed to create quick release'));
            }
        } catch (error) {
            console.error('[QuickRelease] Fetch Error:', error);
            alert('Error: ' + (error.message || 'Failed to create quick release'));
        }
    }

    function incrementVersion(version) {
        const parts = version.split('.').map(Number);
        // Increment patch version (last number)
        if (parts.length >= 3) {
            parts[2]++;
        } else if (parts.length === 2) {
            parts.push(1);
        } else {
            parts.push(0, 1);
        }
        return parts.join('.');
    }
</script>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/dev.php';
?>