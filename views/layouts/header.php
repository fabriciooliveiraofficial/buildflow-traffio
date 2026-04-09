<?php
/**
 * Header Layout Fragment
 * 
 * This file outputs the opening part of main.php layout, 
 * allowing views to include header and footer separately.
 * Use with: include VIEWS_PATH . '/layouts/footer.php'; at end of view
 */

// Get tenant slug from URL path
$tenantSlug = $GLOBALS['tenant_slug'] ?? \App\Core\Tenant::getSlugFromPath() ?? '';
$basePath = $tenantSlug ? "/t/{$tenantSlug}" : '';

// Get user from session
$user = $_SESSION['user'] ?? [];
$page = $activeNav ?? '';
$title = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description"
        content="Construction ERP - Project Management, Time Tracking, Payroll, and Financial Management">
    <title><?= $title ?> | BuildFlow ERP</title>

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2196f3">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="BuildFlow">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- Icons -->
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%232196f3' width='100' height='100' rx='15'/><text x='50' y='65' text-anchor='middle' fill='white' font-size='50' font-weight='bold'>B</text></svg>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/main.css?v=<?= time() ?>">
</head>

<body>
    <script src="<?= $basePath ?>/assets/js/app.js?v=1.1.4"></script>
    <script>
    (function() {
        window.addEventListener('load', function() {
            const sidebar = document.getElementById('sidebar');
            const state = {
                found: !!sidebar,
                source: 'header.php',
                html: sidebar ? sidebar.innerHTML.substring(0, 1000) : 'not found',
                version: '1.1.4'
            };
            fetch('/api/debug/log', { method: 'POST', body: JSON.stringify({ level: 'DEBUG', message: 'UI State Probe (Header)', context: state }) }).catch(e => {});
        });
    })();
    </script>
        <!-- Sidebar Overlay (Mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Include the sidebar from main -->
        <?php 
        // Extract sidebar HTML
        ob_start();
        ?>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?= $basePath ?>/dashboard" class="sidebar-logo">
                    <div class="logo-icon">B</div>
                    <span class="logo-text">BuildFlow</span>
                </a>
                <button class="sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6" />
                    </svg>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">OVERVIEW</div>
                    <a href="<?= $basePath ?>/dashboard"
                        class="nav-item <?= ($page === 'dashboard') ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" />
                            <rect x="14" y="3" width="7" height="7" />
                            <rect x="14" y="14" width="7" height="7" />
                            <rect x="3" y="14" width="7" height="7" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= $basePath ?>/cash-flow"
                        class="nav-item <?= ($page === 'cash-flow') ? 'active' : '' ?>">
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
                        class="nav-item <?= ($page === 'projects') ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                        </svg>
                        <span>Projects</span>
                    </a>
                    <a href="<?= $basePath ?>/clients"
                        class="nav-item <?= ($page === 'clients') ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        <span>Clients</span>
                    </a>
                    <a href="<?= $basePath ?>/employees"
                        class="nav-item <?= ($page === 'employees') ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span>Employees</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">FINANCE</div>
                    <a href="<?= $basePath ?>/invoices"
                        class="nav-item <?= ($page === 'invoices') ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                        </svg>
                        <span>Invoices</span>
                    </a>
                    <a href="<?= $basePath ?>/estimates"
                        class="nav-item <?= ($page === 'estimates') ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <path d="M9 13h2l1.5 3 2.5-6 1.5 3h2" />
                        </svg>
                        <span>Estimates</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">WORKFORCE</div>
                    <a href="<?= $basePath ?>/timesheets"
                        class="nav-item <?= ($page === 'timesheets') ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                        <span>Timesheets</span>
                    </a>
                    <a href="<?= $basePath ?>/payroll"
                        class="nav-item <?= ($page === 'payroll') ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23" />
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                        </svg>
                        <span>Payroll</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">SYSTEM</div>
                    <a href="<?= $basePath ?>/email/compose"
                        class="nav-item <?= ($page === 'email') ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                            <polyline points="22,6 12,13 2,6" />
                        </svg>
                        <span>Email</span>
                    </a>
                    <a href="<?= $basePath ?>/settings"
                        class="nav-item <?= ($page === 'settings') ? 'active' : '' ?>">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3" />
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>
        </aside>
        <?php echo ob_get_clean(); ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn" title="Open menu">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12" />
                            <line x1="3" y1="6" x2="21" y2="6" />
                            <line x1="3" y1="18" x2="21" y2="18" />
                        </svg>
                    </button>
                    <h1 class="header-title"><?= $title ?></h1>
                </div>

                <div class="header-right">
                    <div class="header-search">
                        <svg class="header-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input type="text" placeholder="Search..." id="global-search">
                    </div>

                    <button class="header-icon-btn" id="themeToggle" title="Toggle theme">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                        </svg>
                    </button>

                    <!-- User Menu -->
                    <div class="user-menu" id="userMenu">
                        <div class="user-avatar"><?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?></div>
                        <span class="user-name"><?= ($user['first_name'] ?? 'User') ?></span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>

                        <!-- User Dropdown -->
                        <div class="user-dropdown" id="userDropdown">
                            <div class="dropdown-header">
                                <strong><?= ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '') ?></strong>
                                <span><?= $user['email'] ?? '' ?></span>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="<?= $basePath ?>/settings" class="dropdown-item">Settings</a>
                            <button class="dropdown-item logout-btn" onclick="logout()">Logout</button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
