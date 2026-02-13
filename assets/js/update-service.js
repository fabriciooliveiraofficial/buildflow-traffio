/**
 * Update Service - User-Controlled Update System
 * 
 * Checks for new versions and displays non-intrusive update notifications.
 * Inspired by Slack, Figma, VS Code update patterns.
 * 
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

        // Check interval: every 5 minutes
        this.CHECK_INTERVAL = 5 * 60 * 1000;
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
        // Load current version from storage or manifest
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
                // Check if user dismissed the notification
                if (this.isDismissed()) {
                    console.log('[UpdateService] Update available but dismissed');
                    return;
                }

                // Check for forced updates
                if (this.versionData.forceUpdate) {
                    this.showForceUpdateModal();
                } else {
                    this.showUpdateToast();
                }
            }
        } catch (error) {
            console.error('[UpdateService] Error checking for updates:', error);
        }
    }

    /**
     * Compare versions to determine if update is available
     */
    isUpdateAvailable() {
        if (!this.currentVersion || !this.latestVersion) return false;
        return this.compareVersions(this.latestVersion, this.currentVersion) > 0;
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
     * Show the update toast notification
     */
    showUpdateToast() {
        // Remove existing toast if any
        const existing = document.getElementById('update-toast');
        if (existing) existing.remove();

        const changelog = this.getLatestChangelog();
        const featurePreview = changelog?.features?.[0] || 'New improvements available';

        const toast = document.createElement('div');
        toast.id = 'update-toast';
        toast.className = 'update-toast';
        toast.innerHTML = `
            <div class="update-toast-header">
                <div class="update-toast-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </div>
                <div class="update-toast-title-wrap">
                    <div class="update-toast-title">New version available</div>
                    <span class="update-version-badge">v${this.latestVersion}</span>
                </div>
                <button class="update-toast-close" onclick="updateService.dismissToast()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="update-toast-message">${featurePreview}</div>
            <div class="update-toast-actions">
                <button class="btn btn-sm btn-secondary" onclick="updateService.remindLater()">
                    Later
                </button>
                <button class="btn btn-sm btn-outline" onclick="updateService.showWhatsNew()">
                    What's New
                </button>
                <button class="btn btn-sm btn-primary" onclick="updateService.applyUpdate()">
                    Update Now
                </button>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('visible');
        });

        // Show update badge in header
        this.showUpdateBadge();
    }

    /**
     * Show force update modal (non-dismissable)
     */
    showForceUpdateModal() {
        const existing = document.getElementById('force-update-modal');
        if (existing) return;

        const modal = document.createElement('div');
        modal.id = 'force-update-modal';
        modal.className = 'force-update-overlay';
        modal.innerHTML = `
            <div class="force-update-modal">
                <div class="force-update-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <h2>Critical Update Required</h2>
                <p>${this.versionData.forceUpdateMessage || 'A critical update is required to continue using the application.'}</p>
                <button class="btn btn-primary btn-lg" onclick="updateService.applyUpdate()">
                    Update Now
                </button>
            </div>
        `;

        document.body.appendChild(modal);
        requestAnimationFrame(() => modal.classList.add('visible'));
    }

    /**
     * Show What's New modal
     */
    showWhatsNew() {
        this.dismissToast();

        const existing = document.getElementById('whats-new-modal');
        if (existing) existing.remove();

        const changelog = this.getLatestChangelog();

        const modal = document.createElement('div');
        modal.id = 'whats-new-modal';
        modal.className = 'modal-backdrop active';
        modal.innerHTML = `
            <div class="modal active whats-new-modal">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-500), var(--secondary-500)); color: white;">
                    <h3 class="modal-title">
                        ✨ What's New in v${this.latestVersion}
                    </h3>
                    <button class="modal-close" onclick="updateService.closeWhatsNew()" style="color: white;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    ${this.renderChangelog(changelog)}
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="updateService.closeWhatsNew()">
                        Maybe Later
                    </button>
                    <button class="btn btn-primary" onclick="updateService.applyUpdate()">
                        Update Now
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    }

    /**
     * Render changelog content
     */
    renderChangelog(changelog) {
        if (!changelog) return '<p class="text-muted">No changelog available.</p>';

        let html = '';

        if (changelog.features?.length) {
            html += `
                <div class="changelog-section">
                    <h4 class="changelog-section-title">
                        <span class="changelog-icon">🎨</span> New Features
                    </h4>
                    <ul class="changelog-list">
                        ${changelog.features.map(f => `<li>${f}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        if (changelog.fixes?.length) {
            html += `
                <div class="changelog-section">
                    <h4 class="changelog-section-title">
                        <span class="changelog-icon">🐛</span> Bug Fixes
                    </h4>
                    <ul class="changelog-list">
                        ${changelog.fixes.map(f => `<li>${f}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        if (changelog.improvements?.length) {
            html += `
                <div class="changelog-section">
                    <h4 class="changelog-section-title">
                        <span class="changelog-icon">⚡</span> Improvements
                    </h4>
                    <ul class="changelog-list">
                        ${changelog.improvements.map(i => `<li>${i}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        return html || '<p class="text-muted">No changes documented for this version.</p>';
    }

    /**
     * Get the latest changelog entry
     */
    getLatestChangelog() {
        if (!this.versionData?.changelog?.length) return null;
        return this.versionData.changelog[0];
    }

    /**
     * Close What's New modal
     */
    closeWhatsNew() {
        const modal = document.getElementById('whats-new-modal');
        if (modal) modal.remove();
    }

    /**
     * Dismiss the toast for 4 hours
     */
    remindLater() {
        const dismissUntil = Date.now() + this.REMIND_DELAY;
        localStorage.setItem(this.storageKey, dismissUntil.toString());
        this.dismissToast();

        if (typeof ERP !== 'undefined' && ERP.toast) {
            ERP.toast.info('We\'ll remind you about this update later');
        }
    }

    /**
     * Dismiss the toast (temporary, until next page load)
     */
    dismissToast() {
        const toast = document.getElementById('update-toast');
        if (toast) {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }
    }

    /**
     * Apply the update (clear cache and refresh)
     */
    async applyUpdate() {
        console.log('[UpdateService] applyUpdate called');
        console.log('[UpdateService] Setting version to:', this.latestVersion);

        // Store the new version FIRST before any async operations
        localStorage.setItem(this.versionKey, this.latestVersion);
        // Clear dismissal
        localStorage.removeItem(this.storageKey);
        // Store flag to show "Updated successfully" message
        localStorage.setItem('erp_just_updated', 'true');

        // Verify localStorage was set
        const storedVersion = localStorage.getItem(this.versionKey);
        console.log('[UpdateService] Stored version verification:', storedVersion);

        // Show loading state
        if (typeof ERP !== 'undefined' && ERP.toast) {
            ERP.toast.info('Updating... Please wait');
        }

        try {
            // Tell service worker to clear all caches
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                console.log('[UpdateService] Clearing SW cache...');
                navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_CACHE' });
                await new Promise(resolve => setTimeout(resolve, 500));
            }

            // Also clear from window side  
            if ('caches' in window) {
                console.log('[UpdateService] Clearing window caches...');
                const cacheNames = await caches.keys();
                await Promise.all(cacheNames.map(name => caches.delete(name)));
            }

            // Unregister SW to force fresh load
            if ('serviceWorker' in navigator) {
                console.log('[UpdateService] Unregistering service workers...');
                const registrations = await navigator.serviceWorker.getRegistrations();
                for (let registration of registrations) {
                    await registration.unregister();
                }
            }
        } catch (error) {
            console.error('[UpdateService] Error during cache clear:', error);
        }

        console.log('[UpdateService] Reloading page...');
        // Force reload from server (bypass cache)
        window.location.reload(true);
    }

    /**
     * Show update badge in header
     */
    showUpdateBadge() {
        const settingsBtn = document.querySelector('.header-icon-btn[title="Settings"]');
        if (settingsBtn && !settingsBtn.querySelector('.update-badge')) {
            const badge = document.createElement('span');
            badge.className = 'update-badge';
            badge.title = 'Update available';
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

            // Show success toast
            setTimeout(() => {
                if (typeof ERP !== 'undefined' && ERP.toast) {
                    ERP.toast.success(`Updated to v${this.currentVersion}`);
                }

                // Optionally show What's New for this version
                const showChangelog = localStorage.getItem('erp_show_changelog_on_update') !== 'false';
                if (showChangelog && this.versionData) {
                    this.showWhatsNewAfterUpdate();
                }
            }, 500);
        }
    }

    /**
     * Show What's New modal after update
     */
    showWhatsNewAfterUpdate() {
        const changelog = this.versionData?.changelog?.find(c => c.version === this.currentVersion);
        if (!changelog) return;

        // Only show if there are meaningful changes
        const hasContent = changelog.features?.length || changelog.fixes?.length || changelog.improvements?.length;
        if (!hasContent) return;

        // Small delay for better UX
        setTimeout(() => {
            this.latestVersion = this.currentVersion; // For the modal display
            this.showWhatsNew();
        }, 1000);
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
