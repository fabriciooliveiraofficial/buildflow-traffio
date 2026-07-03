/**
 * Update Service - User-Controlled Update System
 * 
 * Checks for new versions and displays update modal notifications.
 * Tenant-aware: Each tenant independently tracks their update state.
 */

class UpdateService {
    constructor() {
        this.currentVersion = null;
        this.latestVersion = null;
        this.versionData = null;
        this.checkInterval = null;
        this.dismissedUntil = null;

        // Get tenant slug from URL path for multi-tenant support
        this.tenantSlug = this.getTenantSlug();

        // Tenant-specific localStorage keys
        this.storageKey = `erp_update_dismissed_${this.tenantSlug}`;
        this.versionKey = `erp_app_version_${this.tenantSlug}`;

        // Check interval: every 2 minutes
        this.CHECK_INTERVAL = 2 * 60 * 1000;
        // Remind after 4 hours if dismissed
        this.REMIND_DELAY = 4 * 60 * 60 * 1000;
    }

    /**
     * Extract tenant slug from URL path
     * e.g., /tenant-abc/dashboard -> tenant-abc
     */
    getTenantSlug() {
        const pathParts = window.location.pathname.split('/').filter(p => p);
        // First path segment is the tenant slug
        return pathParts[0] || 'default';
    }

    /**
     * Initialize the update service
     */
    async init() {
        // Load current version from storage
        this.currentVersion = localStorage.getItem(this.versionKey);
        this.dismissedUntil = localStorage.getItem(this.storageKey);

        // Check for updates immediately
        await this.checkForUpdates();

        // Set up periodic checks
        this.checkInterval = setInterval(() => this.checkForUpdates(), this.CHECK_INTERVAL);

        console.log('[UpdateService] Initialized for tenant:', this.tenantSlug, '- current version:', this.currentVersion);
    }

    /**
     * Check for available updates
     */
    async checkForUpdates() {
        try {
            // Fetch version from API endpoint with cache-busting
            const response = await fetch(`/api/version?t=${Date.now()}`);
            if (!response.ok) {
                console.warn('[UpdateService] Failed to fetch version manifest');
                return;
            }

            const result = await response.json();
            if (!result.success || !result.data) {
                console.warn('[UpdateService] Invalid version response');
                return;
            }

            this.versionData = result.data;
            this.latestVersion = this.versionData.version;

            // First time - just store the version
            if (!this.currentVersion) {
                this.currentVersion = this.latestVersion;
                localStorage.setItem(this.versionKey, this.currentVersion);
                console.log('[UpdateService] First run, setting version:', this.currentVersion);
                return;
            }

            // Check if update is available
            if (this.isUpdateAvailable()) {
                // If it is a forced update, show it regardless of dismissal
                if (this.versionData.forceUpdate) {
                    this.showForceUpdateModal();
                    return;
                }

                // Check if user dismissed the notification
                if (this.isDismissed()) {
                    console.log('[UpdateService] Update available but dismissed');
                    return;
                }

                // Show central update modal
                this.showUpdateModal();
            }
        } catch (error) {
            console.error('[UpdateService] Error checking for updates:', error);
        }
    }

    /**
     * Compare versions to determine if update is available (by SemVer or Build Hash)
     */
    isUpdateAvailable() {
        if (!this.versionData) return false;

        // 1. Prioritize build hash mismatch (automatic git push detection)
        const serverHash = this.versionData.buildHash;
        const clientHash = window.APP_BUILD_HASH || 'unknown';

        if (serverHash && clientHash && serverHash !== 'unknown' && clientHash !== 'unknown') {
            if (serverHash !== clientHash) {
                console.log('[UpdateService] Update detected via buildHash mismatch. Client:', clientHash, 'Server:', serverHash);
                return true;
            }
        }

        // 2. Fallback to semver version comparison
        if (this.currentVersion && this.latestVersion) {
            return this.compareVersions(this.latestVersion, this.currentVersion) > 0;
        }

        return false;
    }

    /**
     * Compare semantic versions
     * Returns: 1 if a > b, -1 if a < b, 0 if equal
     */
    compareVersions(a, b) {
        const partsA = a.split('.').map(Number);
        const partsB = b.split('.').map(Number);

        for (let i = 0; i < Math.max(partsA.length, partsB.length); i++) {
            const numA = partsA[i] || 0;
            const numB = partsB[i] || 0;
            if (numA > numB) return 1;
            if (numA < numB) return -1;
        }
        return 0;
    }

    /**
     * Check if user has dismissed the update notification
     */
    isDismissed() {
        const dismissedUntil = localStorage.getItem(this.storageKey);
        if (!dismissedUntil) return false;
        return Date.now() < parseInt(dismissedUntil, 10);
    }

    /**
     * Show central update modal
     */
    showUpdateModal() {
        // Remove existing modal if any
        const existing = document.getElementById('update-check-modal');
        if (existing) existing.remove();

        const changelog = this.getLatestChangelog();

        const modalBackdrop = document.createElement('div');
        modalBackdrop.id = 'update-check-modal';
        modalBackdrop.className = 'modal-backdrop active';
        modalBackdrop.innerHTML = `
            <div class="modal active update-modal" style="max-width: 520px; border: 1px solid var(--border-color);">
                <div class="modal-header update-modal-header" style="background: linear-gradient(135deg, var(--primary-500), #4f46e5); color: white; display: flex; align-items: center; justify-content: space-between; padding: var(--space-4) var(--space-5);">
                    <h3 class="modal-title" style="color: white; margin: 0; font-size: 1.15rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        🚀 Atualização Disponível
                    </h3>
                    <button class="modal-close" onclick="updateService.dismissModal()" style="color: rgba(255,255,255,0.8); background: none; border: none; cursor: pointer; display: flex; align-items: center; padding: 4px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body update-modal-body" style="padding: var(--space-5);">
                    <p class="update-modal-intro" style="margin-bottom: var(--space-4); color: var(--text-secondary); line-height: 1.5; font-size: 0.95rem;">
                        Uma nova atualização do BuildFlow está disponível com novos recursos, correções de bugs e melhorias de performance.
                    </p>
                    
                    ${changelog ? `
                        <div class="update-changelog-container" style="background: var(--bg-secondary); border-radius: var(--radius-lg); padding: var(--space-4); border: 1px solid var(--border-color); max-height: 200px; overflow-y: auto; text-align: left;">
                            <h4 class="changelog-release-title" style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-primary); letter-spacing: 0.05em; margin-bottom: var(--space-3);">
                                Versão v${this.latestVersion}
                            </h4>
                            ${this.renderChangelog(changelog)}
                        </div>
                    ` : `
                        <div class="update-changelog-container" style="background: var(--bg-secondary); border-radius: var(--radius-lg); padding: var(--space-4); border: 1px solid var(--border-color); text-align: center;">
                            <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">Correções e otimizações gerais de sistema.</p>
                        </div>
                    `}
                </div>
                <div class="modal-footer update-modal-footer" style="padding: var(--space-4) var(--space-5); display: flex; justify-content: flex-end; gap: var(--space-3); border-top: 1px solid var(--border-color);">
                    <button class="btn btn-secondary" onclick="updateService.remindLater()">
                        Talvez mais tarde
                    </button>
                    <button class="btn btn-primary" onclick="updateService.applyUpdate()" style="background-color: var(--primary-500); border-color: var(--primary-500); color: white;">
                        Atualizar
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modalBackdrop);
        
        // Show update badge in header
        this.showUpdateBadge();
    }

    /**
     * Show force update modal (non-dismissable, no cancel button)
     */
    showForceUpdateModal() {
        // Remove existing modal if any
        const existing = document.getElementById('update-check-modal');
        if (existing) existing.remove();

        const changelog = this.getLatestChangelog();

        const modalBackdrop = document.createElement('div');
        modalBackdrop.id = 'update-check-modal';
        modalBackdrop.className = 'modal-backdrop active';
        // Prevent dismissal on backdrop click
        modalBackdrop.style.pointerEvents = 'all';
        
        modalBackdrop.innerHTML = `
            <div class="modal active update-modal" style="max-width: 520px; border: 1px solid var(--border-color);">
                <div class="modal-header update-modal-header" style="background: linear-gradient(135deg, var(--error-500), #dc2626); color: white; display: flex; align-items: center; padding: var(--space-4) var(--space-5);">
                    <h3 class="modal-title" style="color: white; margin: 0; font-size: 1.15rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        ⚠️ Atualização Obrigatória
                    </h3>
                </div>
                <div class="modal-body update-modal-body" style="padding: var(--space-5);">
                    <p class="update-modal-intro" style="margin-bottom: var(--space-4); color: var(--text-secondary); line-height: 1.5; font-size: 0.95rem;">
                        Uma nova versão crítica foi lançada. Você precisa atualizar o aplicativo para continuar utilizando a plataforma com segurança.
                    </p>
                    
                    ${this.versionData.forceUpdateMessage ? `
                        <div class="force-message-box" style="background: rgba(239, 68, 68, 0.05); border-left: 4px solid var(--error-500); padding: var(--space-3) var(--space-4); border-radius: var(--radius-md); margin-bottom: var(--space-4); font-size: 0.9rem; color: #b91c1c; line-height: 1.5;">
                            ${this.versionData.forceUpdateMessage}
                        </div>
                    ` : ''}
                    
                    ${changelog ? `
                        <div class="update-changelog-container" style="background: var(--bg-secondary); border-radius: var(--radius-lg); padding: var(--space-4); border: 1px solid var(--border-color); max-height: 160px; overflow-y: auto; text-align: left;">
                            <h4 class="changelog-release-title" style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-primary); letter-spacing: 0.05em; margin-bottom: var(--space-3);">
                                Novidades da v${this.latestVersion}
                            </h4>
                            ${this.renderChangelog(changelog)}
                        </div>
                    ` : ''}
                </div>
                <div class="modal-footer update-modal-footer" style="padding: var(--space-4) var(--space-5); display: flex; justify-content: stretch; border-top: 1px solid var(--border-color);">
                    <button class="btn btn-primary" onclick="updateService.applyUpdate()" style="width: 100%; background-color: var(--error-500); border-color: var(--error-500); color: white;">
                        Atualizar Agora
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modalBackdrop);
    }

    /**
     * Show loading overlay while applying update
     */
    showLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'update-loading-overlay';
        overlay.innerHTML = `
            <div class="update-loader"></div>
            <div class="update-loader-text">Atualizando plataforma, por favor aguarde...</div>
        `;
        document.body.appendChild(overlay);
        
        // Trigger reflow then add class for fade-in transition
        overlay.offsetHeight;
        overlay.classList.add('visible');
    }

    /**
     * Render changelog lists
     */
    renderChangelog(changelog) {
        if (!changelog) return '<p class="text-muted" style="font-size: 0.9rem; margin: 0;">Nenhuma nota de versão disponível.</p>';

        let html = '';

        if (changelog.features?.length) {
            html += `
                <div class="changelog-section" style="margin-bottom: var(--space-3);">
                    <h5 class="changelog-section-title" style="font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; margin: 0 0 4px 0;">
                        <span>🎨</span> Recursos
                    </h5>
                    <ul class="changelog-list" style="margin: 0; padding-left: 18px; font-size: 0.85rem; color: var(--text-secondary);">
                        ${changelog.features.map(f => `<li>${f}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        if (changelog.fixes?.length) {
            html += `
                <div class="changelog-section" style="margin-bottom: var(--space-3);">
                    <h5 class="changelog-section-title" style="font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; margin: 0 0 4px 0;">
                        <span>🐛</span> Correções
                    </h5>
                    <ul class="changelog-list" style="margin: 0; padding-left: 18px; font-size: 0.85rem; color: var(--text-secondary);">
                        ${changelog.fixes.map(f => `<li>${f}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        if (changelog.improvements?.length) {
            html += `
                <div class="changelog-section">
                    <h5 class="changelog-section-title" style="font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; margin: 0 0 4px 0;">
                        <span>⚡</span> Melhorias
                    </h5>
                    <ul class="changelog-list" style="margin: 0; padding-left: 18px; font-size: 0.85rem; color: var(--text-secondary);">
                        ${changelog.improvements.map(i => `<li>${i}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        return html || '<p class="text-muted" style="font-size: 0.85rem; margin: 0;">Correções e otimizações gerais.</p>';
    }

    /**
     * Get the latest changelog entry
     */
    getLatestChangelog() {
        if (!this.versionData?.changelog?.length) return null;
        return this.versionData.changelog[0];
    }

    /**
     * Dismiss the modal
     */
    dismissModal() {
        const modal = document.getElementById('update-check-modal');
        if (modal) modal.remove();
    }

    /**
     * Dismiss the update for 4 hours
     */
    remindLater() {
        const dismissUntil = Date.now() + this.REMIND_DELAY;
        localStorage.setItem(this.storageKey, dismissUntil.toString());
        this.dismissModal();

        if (typeof ERP !== 'undefined' && ERP.toast) {
            ERP.toast.info('Lembraremos você sobre esta atualização mais tarde');
        }
    }

    /**
     * Apply the update (purge cache and reload)
     */
    async applyUpdate() {
        console.log('[UpdateService] applyUpdate called');
        this.dismissModal();
        this.showLoadingOverlay();

        // Save new version to local storage
        localStorage.setItem(this.versionKey, this.latestVersion);
        localStorage.removeItem(this.storageKey);
        localStorage.setItem('erp_just_updated', 'true');

        try {
            // 1. Clear Service Worker cache
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                console.log('[UpdateService] Clearing SW Cache...');
                navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_CACHE' });
                await new Promise(resolve => setTimeout(resolve, 500));
            }

            // 2. Clear Window Cache Storage
            if ('caches' in window) {
                console.log('[UpdateService] Clearing Caches keys...');
                const cacheNames = await caches.keys();
                await Promise.all(cacheNames.map(name => caches.delete(name)));
            }

            // 3. Unregister all service workers
            if ('serviceWorker' in navigator) {
                console.log('[UpdateService] Unregistering Service Workers...');
                const registrations = await navigator.serviceWorker.getRegistrations();
                for (let r of registrations) {
                    await r.unregister();
                }
            }
        } catch (error) {
            console.error('[UpdateService] Error clearing cache:', error);
        }

        // 4. Force reload from server with timestamp query string to bypass server/intermediate caches
        console.log('[UpdateService] Reloading page...');
        const url = new URL(window.location.href);
        url.searchParams.set('_update', Date.now().toString());
        window.location.href = url.toString();
    }

    /**
     * Show update badge in header on Settings button
     */
    showUpdateBadge() {
        const settingsBtn = document.querySelector('.header-icon-btn[title="Settings"]');
        if (settingsBtn && !settingsBtn.querySelector('.update-badge')) {
            const badge = document.createElement('span');
            badge.className = 'update-badge';
            badge.title = 'Atualização disponível';
            settingsBtn.style.position = 'relative';
            settingsBtn.appendChild(badge);
        }
    }

    /**
     * Check if app was just updated and show success message
     */
    checkJustUpdated() {
        if (localStorage.getItem('erp_just_updated') === 'true') {
            localStorage.removeItem('erp_just_updated');

            setTimeout(() => {
                if (typeof ERP !== 'undefined' && ERP.toast) {
                    ERP.toast.success(`Plataforma atualizada para v${this.currentVersion}!`);
                }
            }, 500);
        }
    }

    /**
     * Stop the update service
     */
    destroy() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
    }
}

// Create global instance
const updateService = new UpdateService();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    updateService.init().then(() => {
        updateService.checkJustUpdated();
    });
});
