<?php
/**
 * Main Application Layout
 * BUILD_VERSION: 1.1.4
 * QuickBooks-style sidebar navigation with responsive design
 */

// Get tenant slug from URL path
$tenantSlug = $GLOBALS['tenant_slug'] ?? \App\Core\Tenant::getSlugFromPath() ?? '';
$basePath = $tenantSlug ? "/t/{$tenantSlug}" : '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description"
        content="Construction ERP - Project Management, Time Tracking, Payroll, and Financial Management">
    <title><?= $title ?? 'Dashboard' ?> | BuildFlow ERP</title>

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2196f3">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="BuildFlow">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="BuildFlow ERP">
    <meta name="msapplication-TileColor" content="#2196f3">
    <meta name="msapplication-tap-highlight" content="no">

    <!-- PWA Icons - Using inline SVG -->
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%232196f3' width='100' height='100' rx='15'/><text x='50' y='65' text-anchor='middle' fill='white' font-size='50' font-weight='bold'>B</text></svg>">
    <link rel="apple-touch-icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 180 180'><rect fill='%232196f3' width='180' height='180' rx='27'/><text x='90' y='118' text-anchor='middle' fill='white' font-size='98' font-weight='bold'>B</text></svg>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/main.css?v=<?= time() ?>">

    <!-- Critical inline styles (cache-proof) -->
    <style>
        /* Prevent any horizontal overflow */
        html,
        body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
        }

        .app-container {
            overflow-x: hidden !important;
        }

        /* Mobile menu - MUST be hidden on desktop */
        .mobile-menu-btn {
            display: none !important;
        }

        /* Mobile-first sidebar styles */
        @media (max-width: 768px) {

            /* Show hamburger on mobile */
            .mobile-menu-btn {
                display: flex !important;
                width: 40px;
                height: 40px;
                align-items: center;
                justify-content: center;
                background: transparent;
                border: none;
                color: #64748b;
                cursor: pointer;
            }

            /* Sidebar MUST be off-screen by default on mobile */
            .sidebar {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                height: 100vh !important;
                width: 280px !important;
                transform: translateX(-100%) !important;
                z-index: 1040 !important;
                transition: transform 0.3s ease !important;
            }

            /* Sidebar visible when mobile-open class is added */
            .sidebar.mobile-open {
                transform: translateX(0) !important;
            }

            /* Main content takes full width on mobile */
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }

            /* Overlay visible when active */
            .sidebar-overlay.active {
                display: block !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: rgba(0, 0, 0, 0.5) !important;
                z-index: 1035 !important;
            }
        }

        /* User dropdown - MUST be hidden by default */
        .user-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            min-width: 220px;
            background: var(--bg-primary, #fff);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            margin-top: 8px;
        }

        .user-dropdown.show {
            display: block;
        }

        .user-menu {
            position: relative;
        }
    </style>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%232196f3' width='100' height='100' rx='15'/><text x='50' y='65' text-anchor='middle' fill='white' font-size='50' font-weight='bold'>C</text></svg>">
</head>

<body>
    <div class="app-container">
        <!-- Diagnostic Probe (v1.1.4) -->
        <script>
        (function() {
            window.addEventListener('load', function() {
                const sidebar = document.getElementById('sidebar');
                const state = {
                    found: !!sidebar,
                    cashFlowItem: null,
                    html: sidebar ? sidebar.innerHTML.substring(0, 1000) : 'not found',
                    location: window.location.href,
                    version: '1.1.4'
                };

                if (sidebar) {
                    const cfLink = sidebar.querySelector('a[href*="/cash-flow"]');
                    if (cfLink) {
                        state.cashFlowItem = {
                            text: cfLink.textContent.trim(),
                            parentSection: cfLink.closest('.nav-section')?.querySelector('.nav-section-title')?.textContent.trim()
                        };
                    }
                }

                fetch('/api/debug/log', {
                    method: 'POST',
                    body: JSON.stringify({
                        level: 'DEBUG',
                        message: 'UI State Probe',
                        context: state
                    })
                }).catch(e => console.error('Probe failed', e));
            });
        })();
        </script>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">C</div>
                <span class="sidebar-brand">ConstructERP</span>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">OVERVIEW</div>
                    <a href="<?= $basePath ?>/dashboard"
                        class="nav-item <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" />
                            <rect x="14" y="3" width="7" height="7" />
                            <rect x="14" y="14" width="7" height="7" />
                            <rect x="3" y="14" width="7" height="7" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= $basePath ?>/cash-flow"
                        class="nav-item <?= ($page ?? '') === 'cash-flow' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                        </svg>
                        <span>Cash Flow</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">MANAGEMENT</div>
                    <a href="<?= $basePath ?>/projects"
                        class="nav-item <?= ($page ?? '') === 'projects' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                        </svg>
                        <span>Projects</span>
                    </a>
                    <a href="<?= $basePath ?>/clients"
                        class="nav-item <?= ($page ?? '') === 'clients' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        <span>Clients</span>
                    </a>
                    <a href="<?= $basePath ?>/inventory"
                        class="nav-item <?= ($page ?? '') === 'inventory' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                            <line x1="12" y1="22.08" x2="12" y2="12" />
                        </svg>
                        <span>Inventory</span>
                    </a>
                    <a href="<?= $basePath ?>/employees"
                        class="nav-item <?= ($page ?? '') === 'employees' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span>Employees</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">FINANCE</div>
                    <a href="<?= $basePath ?>/accounts"
                        class="nav-item <?= ($page ?? '') === 'accounts' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z" />
                            <path d="M8 8h8" />
                            <path d="M8 12h8" />
                            <path d="M8 16h5" />
                        </svg>
                        <span>Chart of Accounts</span>
                    </a>
                    <a href="<?= $basePath ?>/journal-entries"
                        class="nav-item <?= ($page ?? '') === 'journal-entries' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                        </svg>
                        <span>Journal Entries</span>
                    </a>
                    <a href="<?= $basePath ?>/financial-reports"
                        class="nav-item <?= ($page ?? '') === 'financial-reports' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10" />
                            <line x1="12" y1="20" x2="12" y2="4" />
                            <line x1="6" y1="20" x2="6" y2="14" />
                        </svg>
                        <span>Financial Reports</span>
                    </a>
                    <a href="<?= $basePath ?>/invoices"
                        class="nav-item <?= ($page ?? '') === 'invoices' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                        </svg>
                        <span>Invoices</span>
                    </a>
                    <a href="<?= $basePath ?>/estimates"
                        class="nav-item <?= ($page ?? '') === 'estimates' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <path d="M9 13h2l1.5 3 2.5-6 1.5 3h2" />
                        </svg>
                        <span>Estimates</span>
                    </a>
                    <a href="<?= $basePath ?>/purchase-orders"
                        class="nav-item <?= ($page ?? '') === 'purchase-orders' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                            <line x1="3" y1="6" x2="21" y2="6" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                        <span>Purchase Orders</span>
                    </a>
                    <a href="<?= $basePath ?>/vendors"
                        class="nav-item <?= ($page ?? '') === 'vendors' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span>Vendors</span>
                    </a>
                    <a href="<?= $basePath ?>/equipment"
                        class="nav-item <?= ($page ?? '') === 'equipment' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                        </svg>
                        <span>Equipment</span>
                    </a>
                    <a href="<?= $basePath ?>/tasks" class="nav-item <?= ($page ?? '') === 'tasks' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4" />
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                        </svg>
                        <span>Tasks</span>
                    </a>
                    <a href="<?= $basePath ?>/expenses"
                        class="nav-item <?= ($page ?? '') === 'expenses' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                            <line x1="1" y1="10" x2="23" y2="10" />
                        </svg>
                        <span>Expenses</span>
                    </a>
                    <a href="<?= $basePath ?>/time-tracking"
                        class="nav-item <?= ($page ?? '') === 'time-tracking' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                        <span>Time Tracking</span>
                    </a>
                    <a href="<?= $basePath ?>/time-clock"
                        class="nav-item <?= ($page ?? '') === 'time-clock' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14" />
                            <path d="M12 5l7 7-7 7" />
                        </svg>
                        <span>Time Clock</span>
                    </a>
                    <a href="<?= $basePath ?>/payroll"
                        class="nav-item <?= ($page ?? '') === 'payroll' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23" />
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                        </svg>
                        <span>Payroll</span>
                    </a>
                    <a href="<?= $basePath ?>/documents"
                        class="nav-item <?= ($page ?? '') === 'documents' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                        </svg>
                        <span>Documents</span>
                    </a>
                    <a href="<?= $basePath ?>/scheduling"
                        class="nav-item <?= ($page ?? '') === 'scheduling' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                        <span>Scheduling</span>
                    </a>
                    <a href="<?= $basePath ?>/reports"
                        class="nav-item <?= ($page ?? '') === 'reports' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10" />
                            <line x1="12" y1="20" x2="12" y2="4" />
                            <line x1="6" y1="20" x2="6" y2="14" />
                        </svg>
                        <span>Reports</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">SYSTEM</div>
                    <a href="<?= $basePath ?>/support"
                        class="nav-item <?= ($page ?? '') === 'support' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                        <span>Support</span>
                    </a>
                    <a href="<?= $basePath ?>/email/compose"
                        class="nav-item <?= ($page ?? '') === 'email' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                            <polyline points="22,6 12,13 2,6" />
                        </svg>
                        <span>Email</span>
                    </a>
                    <a href="<?= $basePath ?>/settings"
                        class="nav-item <?= ($page ?? '') === 'settings' ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3" />
                            <path
                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <button class="sidebar-toggle-btn" id="sidebarToggle" title="Collapse sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="11 17 6 12 11 7" />
                        <polyline points="18 17 13 12 18 7" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Sidebar Overlay (Mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn" title="Open menu">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12" />
                            <line x1="3" y1="6" x2="21" y2="6" />
                            <line x1="3" y1="18" x2="21" y2="18" />
                        </svg>
                    </button>
                    <h1 class="header-title"><?= $title ?? 'Dashboard' ?></h1>
                </div>

                <div class="header-right">
                    <div class="header-search">
                        <svg class="header-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input type="text" placeholder="Search projects, clients..." id="global-search">
                    </div>

                    <button class="header-icon-btn" id="themeToggle" title="Toggle theme">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                        </svg>
                    </button>

                    <button class="header-icon-btn" id="notificationsBtn" title="Notifications">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                        <span class="notification-badge"></span>
                    </button>

                    <!-- User Menu -->
                    <div class="user-menu" id="userMenu">
                        <div class="user-avatar"><?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?></div>
                        <span class="user-name"><?= ($user['first_name'] ?? 'User') ?></span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>

                        <!-- User Dropdown -->
                        <div class="user-dropdown" id="userDropdown">
                            <div class="dropdown-header">
                                <strong><?= ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '') ?></strong>
                                <span><?= $user['email'] ?? '' ?></span>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="<?= $basePath ?>/settings" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                My Profile
                            </a>
                            <a href="<?= $basePath ?>/settings" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <circle cx="12" cy="12" r="3" />
                                    <path
                                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                </svg>
                                Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <button class="dropdown-item logout-btn" onclick="logout()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <?= $content ?? '' ?>
            </div>
        </main>
    </div>

    <!-- Modal Backdrop -->
    <div class="modal-backdrop"></div>

    <!-- Scripts -->
    <script src="<?= $basePath ?>/assets/js/app_v114.js?v=1.1.4"></script>
    <script src="/assets/js/notifications.js?v=<?= time() ?>"></script>
    <script src="/assets/js/update-service.js?v=<?= time() ?>"></script>
    <script>
        (function () {
            // Elements
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const userMenu = document.getElementById('userMenu');
            const userDropdown = document.getElementById('userDropdown');
            const themeToggle = document.getElementById('themeToggle');

            // Sidebar toggle (desktop collapse)
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
                });
            }

            // Mobile menu toggle
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function () {
                    sidebar.classList.add('mobile-open');
                    sidebarOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }

            // Close mobile sidebar
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function () {
                    closeMobileSidebar();
                });
            }

            function closeMobileSidebar() {
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            // User menu dropdown
            if (userMenu) {
                userMenu.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function (e) {
                if (userDropdown && !userMenu.contains(e.target)) {
                    userDropdown.classList.remove('show');
                }
            });

            // Theme toggle
            if (themeToggle) {
                themeToggle.addEventListener('click', function () {
                    const html = document.documentElement;
                    const currentTheme = html.getAttribute('data-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    html.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                });
            }

            // Restore sidebar state
            if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth > 1024) {
                sidebar.classList.add('collapsed');
            }

            // Restore theme
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }

            // Close mobile sidebar on nav click
            document.querySelectorAll('.sidebar .nav-item').forEach(function (item) {
                item.addEventListener('click', function () {
                    if (window.innerWidth <= 768) {
                        closeMobileSidebar();
                    }
                });
            });

            // Handle resize
            window.addEventListener('resize', function () {
                if (window.innerWidth > 768) {
                    closeMobileSidebar();
                }
            });
        })();

        // Logout function
        function logout() {
            localStorage.removeItem('erp_token');
            localStorage.removeItem('erp_tenant');
            window.location.href = '/login';
        }
    </script>

    <!-- PWA Service Worker & Install Prompt -->
    <script>
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.register('/sw.js', {
                        scope: '/'
                    });
                    console.log('[PWA] Service Worker registered:', registration.scope);

                    // Check for updates
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        console.log('[PWA] New service worker installing...');

                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('[PWA] New version available');
                                // The update service will handle showing the update toast
                            }
                        });
                    });
                } catch (error) {
                    console.error('[PWA] Service Worker registration failed:', error);
                }
            });
        }

        // PWA Install Prompt - Cross-browser support
        let deferredPrompt = null;
        const PWA_INSTALL_KEY = 'pwa_install_dismissed';
        const PWA_INSTALL_COUNT_KEY = 'pwa_visit_count';
        const PWA_INSTALLED_KEY = 'pwa_installed';

        // Detect if already installed as PWA
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;

        if (isStandalone) {
            localStorage.setItem(PWA_INSTALLED_KEY, 'true');
        }

        // Check if iOS Safari
        function isIOSSafari() {
            const ua = window.navigator.userAgent;
            const iOS = !!ua.match(/iPad/i) || !!ua.match(/iPhone/i);
            const webkit = !!ua.match(/WebKit/i);
            const isSafari = !ua.match(/CriOS/i) && !ua.match(/FxiOS/i);
            return iOS && webkit && isSafari;
        }

        // Chrome/Edge/Android - native install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            console.log('[PWA] Install prompt captured');

            // Track visits
            let visitCount = parseInt(localStorage.getItem(PWA_INSTALL_COUNT_KEY) || '0');
            visitCount++;
            localStorage.setItem(PWA_INSTALL_COUNT_KEY, visitCount.toString());

            // Check if dismissed recently or already installed
            const dismissedUntil = localStorage.getItem(PWA_INSTALL_KEY);
            const isInstalled = localStorage.getItem(PWA_INSTALLED_KEY) === 'true';

            if (isInstalled) return;
            if (dismissedUntil && Date.now() < parseInt(dismissedUntil)) return;

            // Show install prompt after 2 visits
            if (visitCount >= 2) {
                setTimeout(() => showInstallToast(), 2000);
            }
        });

        function showInstallToast() {
            if (typeof ERP !== 'undefined' && ERP.toast) {
                // Create custom toast with action buttons
                const toastHtml = `
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="flex: 1;">
                            <strong>📱 Install BuildFlow App</strong><br>
                            <span style="font-size: 13px; opacity: 0.9;">Get faster access & work offline</span>
                        </div>
                        <button onclick="installPWA()" class="btn btn-sm btn-primary" style="white-space: nowrap;">Install</button>
                        <button onclick="dismissInstallToast()" class="btn btn-sm btn-secondary">Later</button>
                    </div>
                `;

                // Use a persistent toast-like element
                showPWAToast(toastHtml);
            }
        }

        function showPWAToast(html) {
            // Remove existing
            const existing = document.getElementById('pwa-toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.id = 'pwa-toast';
            toast.innerHTML = html;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%) translateY(100px);
                background: var(--bg-primary, #1e293b);
                color: var(--text-primary, white);
                padding: 16px 20px;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                z-index: 10000;
                max-width: 400px;
                min-width: 320px;
                transition: transform 0.3s ease;
            `;
            document.body.appendChild(toast);

            // Animate in
            requestAnimationFrame(() => {
                toast.style.transform = 'translateX(-50%) translateY(0)';
            });
        }

        function hidePWAToast() {
            const toast = document.getElementById('pwa-toast');
            if (toast) {
                toast.style.transform = 'translateX(-50%) translateY(100px)';
                setTimeout(() => toast.remove(), 300);
            }
        }

        async function installPWA() {
            hidePWAToast();

            if (deferredPrompt) {
                // Native install for Chrome/Edge/Android
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log('[PWA] Install outcome:', outcome);
                deferredPrompt = null;

                if (outcome === 'accepted') {
                    localStorage.setItem(PWA_INSTALLED_KEY, 'true');
                    if (typeof ERP !== 'undefined' && ERP.toast) {
                        ERP.toast.success('✅ App installed successfully!');
                    }
                }
            } else if (isIOSSafari()) {
                // iOS Safari - show instructions
                showIOSInstallInstructions();
            }
        }

        function dismissInstallToast() {
            hidePWAToast();
            // Don't ask again for 7 days
            const dismissUntil = Date.now() + (7 * 24 * 60 * 60 * 1000);
            localStorage.setItem(PWA_INSTALL_KEY, dismissUntil.toString());
        }

        function showIOSInstallInstructions() {
            const html = `
                <div style="text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 8px;">📲</div>
                    <strong>Install on iPhone/iPad</strong>
                    <div style="margin-top: 12px; font-size: 14px; line-height: 1.6;">
                        1. Tap the <strong>Share</strong> button <span style="font-size: 18px;">⬆️</span><br>
                        2. Scroll down and tap <strong>"Add to Home Screen"</strong><br>
                        3. Tap <strong>"Add"</strong> in the top right
                    </div>
                    <button onclick="hidePWAToast()" class="btn btn-sm btn-primary" style="margin-top: 16px;">Got it!</button>
                </div>
            `;
            showPWAToast(html);
        }

        // iOS Safari - show install prompt on first visit (no beforeinstallprompt event)
        if (isIOSSafari() && !isStandalone) {
            let visitCount = parseInt(localStorage.getItem(PWA_INSTALL_COUNT_KEY) || '0');
            visitCount++;
            localStorage.setItem(PWA_INSTALL_COUNT_KEY, visitCount.toString());

            const dismissedUntil = localStorage.getItem(PWA_INSTALL_KEY);
            const isInstalled = localStorage.getItem(PWA_INSTALLED_KEY) === 'true';

            if (!isInstalled && visitCount >= 2 && (!dismissedUntil || Date.now() >= parseInt(dismissedUntil))) {
                setTimeout(() => {
                    if (typeof ERP !== 'undefined' && ERP.toast) {
                        showPWAToast(`
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="flex: 1;">
                                    <strong>📱 Add to Home Screen</strong><br>
                                    <span style="font-size: 13px; opacity: 0.9;">Install BuildFlow for quick access</span>
                                </div>
                                <button onclick="showIOSInstallInstructions()" class="btn btn-sm btn-primary">How?</button>
                                <button onclick="dismissInstallToast()" class="btn btn-sm btn-secondary">Later</button>
                            </div>
                        `);
                    }
                }, 3000);
            }
        }

        // Network Status Indicator
        function updateNetworkStatus() {
            const isOnline = navigator.onLine;
            const indicator = document.getElementById('network-status');

            if (!isOnline && !indicator) {
                const status = document.createElement('div');
                status.id = 'network-status';
                status.className = 'network-status offline';
                status.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="1" y1="1" x2="23" y2="23"/>
                        <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/>
                        <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
                        <path d="M10.71 5.05A16 16 0 0 1 22.58 9"/>
                        <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/>
                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                        <line x1="12" y1="20" x2="12.01" y2="20"/>
                    </svg>
                    <span>You're offline</span>
                `;
                document.body.appendChild(status);
                requestAnimationFrame(() => status.classList.add('visible'));
            } else if (isOnline && indicator) {
                indicator.classList.remove('visible');
                setTimeout(() => indicator.remove(), 300);
            }
        }

        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        updateNetworkStatus();
    </script>
</body>

</html>
