/**
 * Construction ERP - Main Application JavaScript
 */

// =====================================================
// API Client
// =====================================================

class API {
    constructor() {
        this.baseUrl = '/api';
        this.token = localStorage.getItem('erp_token');
    }

    setToken(token) {
        this.token = token;
        localStorage.setItem('erp_token', token);
    }

    clearToken() {
        this.token = null;
        localStorage.removeItem('erp_token');
    }

    async request(endpoint, options = {}) {
        const url = this.baseUrl + endpoint;
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers,
        };

        // Always get fresh token from localStorage
        const token = localStorage.getItem('erp_token');
        console.log('API Request:', endpoint); // Debug
        console.log('Token found:', token ? token.substring(0, 10) + '...' : 'NONE'); // Debug

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
            console.log('Auth Header set:', headers['Authorization'].substring(0, 20) + '...'); // Debug
        } else {
            console.warn('No token found in localStorage!'); // Debug
        }

        try {
            const response = await fetch(url, {
                ...options,
                headers,
            });

            const data = await response.json();

            // Handle 401 Unauthorized - redirect to login
            if (response.status === 401) {
                console.warn('Token expired or invalid');
                this.clearToken();
                localStorage.removeItem('erp_user');
                localStorage.removeItem('erp_tenant');
                // Redirect to login page (but not for developer console which has its own auth)
                const currentPath = window.location.pathname;
                if (!currentPath.includes('/login') && !currentPath.startsWith('/dev/')) {
                    window.location.href = '/login?expired=1';
                }
                throw new Error('Session expired. Please log in again.');
            }

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
}

const api = new API();

// =====================================================
// Session Manager - Auto logout before token expires
// =====================================================

class SessionManager {
    constructor() {
        this.warningShown = false;
        this.warningMinutes = 5; // Warn 5 minutes before expiration
        this.checkInterval = null;
        this.warningModal = null;
        this.countdownInterval = null;
    }

    init() {
        // Check session every 30 seconds
        this.checkInterval = setInterval(() => this.checkSession(), 30000);
        // Also check immediately
        this.checkSession();
    }

    getTokenExpiration() {
        const token = localStorage.getItem('erp_token');
        if (!token) return null;

        try {
            // Decode JWT payload (middle part)
            const parts = token.split('.');
            if (parts.length !== 3) return null;

            const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
            return payload.exp ? payload.exp * 1000 : null; // Convert to milliseconds
        } catch (e) {
            console.error('Failed to decode token:', e);
            return null;
        }
    }

    checkSession() {
        const expiration = this.getTokenExpiration();
        if (!expiration) return;

        const now = Date.now();
        const timeLeft = expiration - now;
        const minutesLeft = Math.floor(timeLeft / 60000);

        // Already expired
        if (timeLeft <= 0) {
            this.logout('Your session has expired.');
            return;
        }

        // Warning threshold (5 minutes)
        if (minutesLeft <= this.warningMinutes && !this.warningShown) {
            this.showWarning(timeLeft);
        }
    }

    showWarning(timeLeft) {
        this.warningShown = true;

        // Create warning modal if it doesn't exist
        if (!document.getElementById('session-warning-modal')) {
            const modalHtml = `
                <div class="modal-backdrop" id="session-warning-backdrop" style="z-index: 9999;"></div>
                <div class="modal active" id="session-warning-modal" style="z-index: 10000; max-width: 400px;">
                    <div class="modal-header" style="background: linear-gradient(135deg, var(--warning-500), var(--warning-600)); color: white;">
                        <h3 class="modal-title">⚠️ Session Expiring</h3>
                    </div>
                    <div class="modal-body text-center">
                        <p style="font-size: var(--text-lg); margin-bottom: var(--space-4);">
                            Your session will expire in
                        </p>
                        <div id="session-countdown" style="font-size: var(--text-3xl); font-weight: 700; color: var(--warning-600); margin-bottom: var(--space-4);">
                            --:--
                        </div>
                        <p class="text-muted">
                            Would you like to extend your session?
                        </p>
                    </div>
                    <div class="modal-footer" style="justify-content: center; gap: var(--space-3);">
                        <button type="button" class="btn btn-secondary" onclick="sessionManager.logout('You have logged out.')">
                            Log Out Now
                        </button>
                        <button type="button" class="btn btn-primary" onclick="sessionManager.extendSession()">
                            Extend Session
                        </button>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            document.getElementById('session-warning-backdrop').classList.add('active');
        }

        // Start countdown
        this.startCountdown(timeLeft);
    }

    startCountdown(timeLeft) {
        const countdownEl = document.getElementById('session-countdown');

        const updateCountdown = () => {
            const now = Date.now();
            const expiration = this.getTokenExpiration();
            if (!expiration) return;

            const remaining = expiration - now;

            if (remaining <= 0) {
                this.logout('Your session has expired.');
                return;
            }

            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            countdownEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            // Change color when under 1 minute
            if (remaining < 60000) {
                countdownEl.style.color = 'var(--error-600)';
            }
        };

        updateCountdown();
        this.countdownInterval = setInterval(updateCountdown, 1000);
    }

    async extendSession() {
        try {
            const response = await api.post('/auth/refresh', {});

            if (response.success && response.data.token) {
                localStorage.setItem('erp_token', response.data.token);
                this.hideWarning();
                ERP.toast.success('Session extended successfully');
            }
        } catch (e) {
            console.error('Failed to extend session:', e);
            ERP.toast.error('Failed to extend session. Please log in again.');
            this.logout('Failed to extend session.');
        }
    }

    hideWarning() {
        this.warningShown = false;
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }

        const modal = document.getElementById('session-warning-modal');
        const backdrop = document.getElementById('session-warning-backdrop');
        if (modal) modal.remove();
        if (backdrop) backdrop.remove();
    }

    logout(message) {
        // Clear intervals
        if (this.checkInterval) clearInterval(this.checkInterval);
        if (this.countdownInterval) clearInterval(this.countdownInterval);

        // Clear storage
        localStorage.removeItem('erp_token');
        localStorage.removeItem('erp_user');
        localStorage.removeItem('erp_tenant');

        // Encode message for URL
        const encodedMsg = encodeURIComponent(message || 'You have been logged out.');

        // Redirect to login
        window.location.href = '/login?expired=1&msg=' + encodedMsg;
    }

    stop() {
        if (this.checkInterval) clearInterval(this.checkInterval);
        if (this.countdownInterval) clearInterval(this.countdownInterval);
    }
}

const sessionManager = new SessionManager();

// =====================================================
// Toast Notifications
// =====================================================

class Toast {
    constructor() {
        this.container = this.createContainer();
    }

    createContainer() {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    show(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        const icons = {
            success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
            error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
            warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        };

        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                <div class="toast-message">${message}</div>
            </div>
            <button class="modal-close" onclick="this.parentElement.remove()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        `;

        this.container.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => toast.remove(), duration);
        }

        return toast;
    }

    success(message) { return this.show(message, 'success'); }
    error(message) { return this.show(message, 'error'); }
    warning(message) { return this.show(message, 'warning'); }
    info(message) { return this.show(message, 'info'); }
}

const toast = new Toast();

// =====================================================
// Modal Manager
// =====================================================

class Modal {
    static open(modalId) {
        const backdrop = document.querySelector('.modal-backdrop');
        const modal = document.getElementById(modalId);

        if (backdrop) backdrop.classList.add('active');
        if (modal) modal.classList.add('active');

        document.body.style.overflow = 'hidden';
    }

    static close(modalId) {
        const backdrop = document.querySelector('.modal-backdrop');
        const modal = document.getElementById(modalId);

        if (backdrop) backdrop.classList.remove('active');
        if (modal) modal.classList.remove('active');

        document.body.style.overflow = '';
    }

    static closeAll() {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.querySelectorAll('.modal-backdrop.active').forEach(backdrop => {
            backdrop.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
}

// =====================================================
// Theme Manager
// =====================================================

class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.apply();
    }

    toggle() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        this.apply();
        localStorage.setItem('theme', this.theme);
    }

    apply() {
        document.documentElement.setAttribute('data-theme', this.theme);
    }

    get current() {
        return this.theme;
    }
}

const themeManager = new ThemeManager();

// =====================================================
// Time Tracking Timer
// =====================================================

class Timer {
    constructor() {
        this.isRunning = false;
        this.startTime = null;
        this.elapsed = 0;
        this.interval = null;
        this.displayElement = null;
    }

    start() {
        if (this.isRunning) return;

        this.isRunning = true;
        this.startTime = Date.now() - this.elapsed;

        this.interval = setInterval(() => {
            this.elapsed = Date.now() - this.startTime;
            this.updateDisplay();
        }, 1000);
    }

    stop() {
        if (!this.isRunning) return;

        this.isRunning = false;
        clearInterval(this.interval);
        return this.elapsed;
    }

    reset() {
        this.stop();
        this.elapsed = 0;
        this.updateDisplay();
    }

    updateDisplay() {
        if (!this.displayElement) return;

        const hours = Math.floor(this.elapsed / 3600000);
        const minutes = Math.floor((this.elapsed % 3600000) / 60000);
        const seconds = Math.floor((this.elapsed % 60000) / 1000);

        this.displayElement.textContent =
            `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    setDisplay(element) {
        this.displayElement = element;
    }

    getHours() {
        return this.elapsed / 3600000;
    }
}

// =====================================================
// Dashboard Charts
// =====================================================

class Dashboard {
    constructor() {
        this.charts = {};
    }

    async loadStats() {
        try {
            const response = await api.get('/dashboard/stats');
            if (response.success) {
                this.renderStats(response.data);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    async loadCharts() {
        try {
            const response = await api.get('/dashboard/charts');
            if (response.success) {
                this.renderCharts(response.data);
            }
        } catch (error) {
            console.error('Failed to load charts:', error);
        }
    }

    renderStats(data) {
        // Update stat cards
        this.updateStatCard('projects-active', data.projects?.active || 0);
        this.updateStatCard('outstanding-amount', this.formatCurrency(data.financials?.outstanding || 0));
        this.updateStatCard('monthly-expenses', this.formatCurrency(data.monthly_expenses || 0));
        this.updateStatCard('weekly-hours', (data.weekly_hours || 0).toFixed(1));
    }

    updateStatCard(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }

    renderCharts(data) {
        // Revenue Chart
        if (data.revenue_by_month && window.Chart) {
            this.createRevenueChart(data.revenue_by_month);
        }

        // Expenses by Category
        if (data.expenses_by_category && window.Chart) {
            this.createExpensesChart(data.expenses_by_category);
        }

        // Projects by Status
        if (data.projects_by_status && window.Chart) {
            this.createProjectsChart(data.projects_by_status);
        }

        // Budget vs Actual
        if (data.budget_vs_actual && window.Chart) {
            this.createBudgetChart(data.budget_vs_actual);
        }
    }

    createRevenueChart(data) {
        const ctx = document.getElementById('revenue-chart');
        if (!ctx) return;

        if (this.charts.revenue) {
            this.charts.revenue.destroy();
        }

        this.charts.revenue = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.month),
                datasets: [{
                    label: 'Revenue',
                    data: data.map(d => d.total),
                    borderColor: '#2196f3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    fill: true,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '$' + value.toLocaleString()
                        }
                    }
                }
            }
        });
    }

    createExpensesChart(data) {
        const ctx = document.getElementById('expenses-chart');
        if (!ctx) return;

        if (this.charts.expenses) {
            this.charts.expenses.destroy();
        }

        const colors = [
            '#2196f3', '#ff9800', '#4caf50', '#f44336',
            '#9c27b0', '#00bcd4', '#795548', '#607d8b'
        ];

        this.charts.expenses = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.category),
                datasets: [{
                    data: data.map(d => d.total),
                    backgroundColor: colors.slice(0, data.length),
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    }

    createProjectsChart(data) {
        const ctx = document.getElementById('projects-chart');
        if (!ctx) return;

        if (this.charts.projects) {
            this.charts.projects.destroy();
        }

        const statusColors = {
            planning: '#64b5f6',
            in_progress: '#4caf50',
            on_hold: '#ffc107',
            completed: '#2196f3',
            cancelled: '#f44336'
        };

        this.charts.projects = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.map(d => d.status.replace('_', ' ').toUpperCase()),
                datasets: [{
                    data: data.map(d => d.count),
                    backgroundColor: data.map(d => statusColors[d.status] || '#9e9e9e'),
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    }

    createBudgetChart(data) {
        const ctx = document.getElementById('budget-chart');
        if (!ctx) return;

        if (this.charts.budget) {
            this.charts.budget.destroy();
        }

        this.charts.budget = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.project_name),
                datasets: [
                    {
                        label: 'Budget',
                        data: data.map(d => d.budgeted),
                        backgroundColor: 'rgba(33, 150, 243, 0.8)',
                    },
                    {
                        label: 'Spent',
                        data: data.map(d => d.spent),
                        backgroundColor: 'rgba(255, 152, 0, 0.8)',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '$' + value.toLocaleString()
                        }
                    }
                }
            }
        });
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    }
}

// =====================================================
// Form Utilities
// =====================================================

class FormUtils {
    static serialize(form) {
        const formData = new FormData(form);
        const data = {};

        for (let [key, value] of formData.entries()) {
            // Handle nested keys like 'address[city]'
            if (key.includes('[')) {
                const matches = key.match(/^([^\[]+)\[([^\]]+)\]$/);
                if (matches) {
                    if (!data[matches[1]]) data[matches[1]] = {};
                    data[matches[1]][matches[2]] = value;
                    continue;
                }
            }
            data[key] = value;
        }

        return data;
    }

    static validate(form) {
        const inputs = form.querySelectorAll('[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('error');
            } else {
                input.classList.remove('error');
            }
        });

        return isValid;
    }

    static reset(form) {
        form.reset();
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        form.querySelectorAll('.form-error').forEach(el => el.remove());
    }

    static showErrors(form, errors) {
        // Clear existing errors
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

        // Show new errors
        Object.entries(errors).forEach(([field, message]) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('error');
                const errorEl = document.createElement('div');
                errorEl.className = 'form-error';
                errorEl.textContent = message;
                input.parentNode.appendChild(errorEl);
            }
        });
    }
}

// =====================================================
// Data Table
// =====================================================

class DataTable {
    constructor(tableId, options = {}) {
        this.table = document.getElementById(tableId);
        this.options = {
            pageSize: 15,
            ...options
        };
        this.currentPage = 1;
        this.totalPages = 1;
        this.data = [];
    }

    async load(endpoint, params = {}) {
        const queryParams = new URLSearchParams({
            page: this.currentPage,
            per_page: this.options.pageSize,
            ...params
        });

        try {
            const response = await api.get(`${endpoint}?${queryParams}`);
            if (response.success) {
                this.data = response.data;
                this.totalPages = response.meta?.total_pages || 1;
                this.render();
            }
        } catch (error) {
            console.error('Failed to load data:', error);
            toast.error('Failed to load data');
        }
    }

    render() {
        // Override this method in subclass
    }

    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.load();
        }
    }

    prevPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.load();
        }
    }

    goToPage(page) {
        this.currentPage = Math.max(1, Math.min(page, this.totalPages));
        this.load();
    }
}

// =====================================================
// Sidebar Navigation
// =====================================================

class Sidebar {
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.toggleBtn = document.querySelector('.sidebar-toggle');
        this.init();
    }

    init() {
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => this.toggle());
        }

        // Close on click outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 &&
                this.sidebar.classList.contains('open') &&
                !this.sidebar.contains(e.target) &&
                !this.toggleBtn?.contains(e.target)) {
                this.close();
            }
        });
    }

    toggle() {
        this.sidebar.classList.toggle('open');
    }

    open() {
        this.sidebar.classList.add('open');
    }

    close() {
        this.sidebar.classList.remove('open');
    }
}

// =====================================================
// App Initialization
// =====================================================

document.addEventListener('DOMContentLoaded', () => {
    // Initialize sidebar
    new Sidebar();

    // Initialize session manager (only if user is logged in)
    if (localStorage.getItem('erp_token')) {
        sessionManager.init();
    }

    // Initialize dashboard if on dashboard page
    // Skip if the page has its own inline dashboard script (avoids duplicate chart creation)
    const dashboardContainer = document.getElementById('dashboard');
    if (dashboardContainer && typeof window.loadStats === 'undefined') {
        const dashboard = new Dashboard();
        dashboard.loadStats();
        dashboard.loadCharts();
    }

    // Theme toggle
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            themeManager.toggle();
            toast.info(`Switched to ${themeManager.current} mode`);
        });
    }

    // Close modals on backdrop click
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', () => Modal.closeAll());
    });

    // Close modals on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            Modal.closeAll();
        }
    });

    // Auto-refresh dashboard every 5 minutes
    // Skip if page has its own refresh logic
    if (dashboardContainer && typeof window.loadStats === 'undefined') {
        setInterval(() => {
            const dashboard = new Dashboard();
            dashboard.loadStats();
        }, 300000);
    }
});

// Export for use in other scripts
window.ERP = {
    api,
    toast,
    Modal,
    themeManager,
    Timer,
    Dashboard,
    FormUtils,
    DataTable,
    sessionManager,
};

// =====================================================
// Sidebar UI Patcher (Self-Healing)
// =====================================================

class SidebarPatcher {
    static init() {
        // Run once on load and again after a short delay to catch dynamic injections
        this.patch();
        setTimeout(() => this.patch(), 500);
        setTimeout(() => this.patch(), 2000);
    }

    static patch() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        // 1. Correct "Fluxo de Caixa" -> "Cash Flow"
        const links = sidebar.querySelectorAll('a[href*="/cash-flow"]');
        links.forEach(link => {
            const span = link.querySelector('span');
            if (span && (span.textContent.trim() === 'Fluxo de Caixa' || span.textContent.trim() === 'Fluxo')) {
                span.textContent = 'Cash Flow';
                console.log('SidebarPatcher: Corrected name to Cash Flow');
            }

            // 2. Ensure it is in the "OVERVIEW" section
            const overviewSection = Array.from(sidebar.querySelectorAll('.nav-section')).find(s => 
                s.querySelector('.nav-section-title')?.textContent.trim() === 'OVERVIEW'
            );
            
            if (overviewSection && !overviewSection.contains(link)) {
                overviewSection.appendChild(link);
                console.log('SidebarPatcher: Moved link to OVERVIEW');
            }
        });
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    SidebarPatcher.init();
});
