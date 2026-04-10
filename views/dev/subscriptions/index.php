<?php
/**
 * Admin Subscriptions Dashboard
 * Platform owner view for managing all tenant subscriptions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check dev auth
if (!isset($_SESSION['dev_user'])) {
    header('Location: /dev/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions - Admin Console</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">

    <style>
        :root {
            --sidebar-width: 240px;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg-secondary);
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: #0F172A;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #2563EB, #7C3AED);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .sidebar-title {
            font-weight: 600;
            font-size: 1.125rem;
        }

        .sidebar-subtitle {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section-title {
            padding: 0.75rem 1.5rem 0.5rem;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255, 255, 255, 0.4);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .nav-item.active {
            background: rgba(37, 99, 235, 0.2);
            color: #60A5FA;
            border-right: 3px solid #2563EB;
        }

        .nav-item svg {
            opacity: 0.7;
        }

        .nav-item.active svg {
            opacity: 1;
        }

        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .page-subtitle {
            color: var(--text-muted);
            margin: 0.25rem 0 0;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .stat-change {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-change.positive {
            color: var(--success-600);
        }

        .stat-change.negative {
            color: var(--error-600);
        }

        /* Cards */
        .card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Filters */
        .filters-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 0.75rem center;
        }

        .filter-select {
            padding: 0.625rem 2rem 0.625rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            background: white;
            cursor: pointer;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
        }

        .data-table tr:hover {
            background: var(--bg-secondary);
        }

        .tenant-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .tenant-avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .tenant-name {
            font-weight: 500;
        }

        .tenant-email {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge.active {
            background: var(--success-100);
            color: var(--success-700);
        }

        .status-badge.trialing {
            background: var(--info-100);
            color: var(--info-700);
        }

        .status-badge.past_due {
            background: var(--error-100);
            color: var(--error-700);
        }

        .status-badge.canceled {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .status-badge.suspended {
            background: var(--warning-100);
            color: var(--warning-700);
        }

        .plan-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 500;
            background: var(--primary-100);
            color: var(--primary-700);
        }

        .plan-badge.business {
            background: #EDE9FE;
            color: #6D28D9;
        }

        .plan-badge.professional {
            background: #FEF3C7;
            color: #D97706;
        }

        .usage-bar {
            width: 60px;
            height: 6px;
            background: var(--gray-200);
            border-radius: 3px;
            overflow: hidden;
        }

        .usage-fill {
            height: 100%;
            background: var(--primary-500);
            border-radius: 3px;
        }

        .usage-text {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .actions-cell {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.2s;
        }

        .btn-icon:hover {
            border-color: var(--primary-500);
            color: var(--primary-600);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .pagination-info {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .pagination-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: white;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .pagination-btn:hover:not(:disabled) {
            border-color: var(--primary-500);
            color: var(--primary-600);
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* MRR Chart Placeholder */
        .chart-placeholder {
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
        }

        /* Activity Feed */
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-icon.created {
            background: var(--success-100);
            color: var(--success-600);
        }

        .activity-icon.upgraded {
            background: var(--primary-100);
            color: var(--primary-600);
        }

        .activity-icon.canceled {
            background: var(--error-100);
            color: var(--error-600);
        }

        .activity-icon.payment_failed {
            background: var(--warning-100);
            color: var(--warning-600);
        }

        .activity-text {
            flex: 1;
        }

        .activity-title {
            font-size: 0.875rem;
        }

        .activity-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-dialog {
            background: white;
            border-radius: var(--radius-xl);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .loading-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-color);
            border-top-color: var(--primary-500);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                display: none;
            }

            .admin-main {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon">B</div>
                    <div>
                        <div class="sidebar-title">Buildflow</div>
                        <div class="sidebar-subtitle">Admin Console</div>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section-title">Overview</div>
                <a href="/dev/support" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    Support Tickets
                </a>
                <a href="/dev/subscriptions" class="nav-item active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                    Subscriptions
                </a>
                <a href="/dev/releases" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Releases
                </a>
                <div class="nav-section-title">Management</div>
                <a href="#" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Tenants
                </a>
                <a href="#" class="nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                        </path>
                    </svg>
                    Settings
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Subscriptions</h1>
                    <p class="page-subtitle">Manage tenant subscriptions and billing</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="exportData()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Export
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid" id="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #2563EB, #1D4ED8);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" id="stat-mrr">$0</div>
                    <div class="stat-label">Monthly Recurring Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #22C55E, #16A34A);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" id="stat-active">0</div>
                    <div class="stat-label">Active Subscriptions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" id="stat-trials">0</div>
                    <div class="stat-label">Trials Ending Soon</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #EF4444, #DC2626);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                                </path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value" id="stat-pastdue">0</div>
                    <div class="stat-label">Past Due</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                <!-- Subscriptions Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                            All Subscriptions
                        </h3>
                    </div>
                    <div class="card-body" style="padding: 1rem 1.5rem;">
                        <div class="filters-row">
                            <input type="text" class="search-input" id="search-input"
                                placeholder="Search by name, email, or subdomain...">
                            <select class="filter-select" id="status-filter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="trialing">Trialing</option>
                                <option value="past_due">Past Due</option>
                                <option value="canceled">Canceled</option>
                            </select>
                            <select class="filter-select" id="plan-filter">
                                <option value="">All Plans</option>
                                <option value="team">Team</option>
                                <option value="business">Business</option>
                                <option value="professional">Professional</option>
                            </select>
                        </div>
                    </div>
                    <div id="table-container">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                            Recent Activity
                        </h3>
                    </div>
                    <div class="card-body" id="activity-container">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Tenant Detail Modal -->
    <div class="modal-overlay" id="detail-modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-tenant-name">Tenant Details</h3>
                <button class="modal-close" onclick="closeModal('detail-modal')">&times;</button>
            </div>
            <div class="modal-body" id="modal-content">
                <!-- Content loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('detail-modal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Change Plan Modal -->
    <div class="modal-overlay" id="plan-modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Change Plan</h3>
                <button class="modal-close" onclick="closeModal('plan-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="plan-tenant-id">
                <div class="form-group">
                    <label class="form-label">Select New Plan</label>
                    <select class="form-control" id="new-plan-select">
                        <option value="team">Team ($60/month)</option>
                        <option value="business">Business ($90/month)</option>
                        <option value="professional">Professional ($160/month)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason (optional)</label>
                    <textarea class="form-control" id="plan-change-reason" rows="2"
                        placeholder="Admin override reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('plan-modal')">Cancel</button>
                <button class="btn btn-primary" onclick="confirmPlanChange()" id="confirm-plan-btn">Update Plan</button>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let debounceTimer;

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function () {
            loadStats();
            loadSubscriptions();
            loadActivity();

            // Setup filters
            document.getElementById('search-input').addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentPage = 1;
                    loadSubscriptions();
                }, 300);
            });

            document.getElementById('status-filter').addEventListener('change', function () {
                currentPage = 1;
                loadSubscriptions();
            });

            document.getElementById('plan-filter').addEventListener('change', function () {
                currentPage = 1;
                loadSubscriptions();
            });
        });

        async function loadStats() {
            try {
                const response = await fetch('/api/admin/subscriptions/stats');
                const result = await response.json();

                if (result.success) {
                    const data = result.data;
                    document.getElementById('stat-mrr').textContent = '$' + data.mrr.toLocaleString();
                    document.getElementById('stat-active').textContent = data.active_subscriptions;
                    document.getElementById('stat-trials').textContent = data.trials_ending_soon;
                    document.getElementById('stat-pastdue').textContent = data.past_due;
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        }

        async function loadSubscriptions() {
            const container = document.getElementById('table-container');

            const search = document.getElementById('search-input').value;
            const status = document.getElementById('status-filter').value;
            const plan = document.getElementById('plan-filter').value;

            const params = new URLSearchParams({
                page: currentPage,
                limit: 15,
            });

            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (plan) params.append('plan', plan);

            try {
                const response = await fetch('/api/admin/subscriptions?' + params.toString());
                const result = await response.json();

                if (result.success) {
                    renderTable(result.data, result.meta);
                    totalPages = result.meta.total_pages;
                }
            } catch (error) {
                console.error('Failed to load subscriptions:', error);
                container.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-muted);">Failed to load subscriptions</div>';
            }
        }

        function renderTable(subscriptions, meta) {
            const container = document.getElementById('table-container');

            if (subscriptions.length === 0) {
                container.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-muted);">No subscriptions found</div>';
                return;
            }

            const rows = subscriptions.map(s => {
                const initial = s.name ? s.name.charAt(0).toUpperCase() : '?';
                const userPercent = s.user_limit ? Math.round((s.user_count / s.user_limit) * 100) : 0;

                return `
                    <tr>
                        <td>
                            <div class="tenant-cell">
                                <div class="tenant-avatar">${initial}</div>
                                <div>
                                    <div class="tenant-name">${s.name || 'Unnamed'}</div>
                                    <div class="tenant-email">${s.email || '-'}</div>
                                </div>
                            </div>
                        </td>
                        <td><code style="font-size: 0.75rem;">${s.subdomain || '-'}</code></td>
                        <td>
                            <span class="plan-badge ${s.plan_slug || ''}">${s.plan_name || 'None'}</span>
                        </td>
                        <td>
                            <span class="status-badge ${s.subscription_status || ''}">${formatStatus(s.subscription_status)}</span>
                        </td>
                        <td>
                            <div class="usage-bar"><div class="usage-fill" style="width: ${userPercent}%"></div></div>
                            <div class="usage-text">${s.user_count}/${s.user_limit} users</div>
                        </td>
                        <td>${s.price_monthly ? '$' + s.price_monthly : '-'}</td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn-icon" onclick="viewDetails(${s.id})" title="View Details">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <button class="btn-icon" onclick="showPlanModal(${s.id}, '${s.plan_slug}')" title="Change Plan">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            container.innerHTML = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Subdomain</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Usage</th>
                            <th>MRR</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
                <div class="pagination">
                    <div class="pagination-info">
                        Showing ${(meta.page - 1) * meta.limit + 1} to ${Math.min(meta.page * meta.limit, meta.total)} of ${meta.total}
                    </div>
                    <div class="pagination-buttons">
                        <button class="pagination-btn" onclick="prevPage()" ${meta.page <= 1 ? 'disabled' : ''}>Previous</button>
                        <button class="pagination-btn" onclick="nextPage()" ${meta.page >= meta.total_pages ? 'disabled' : ''}>Next</button>
                    </div>
                </div>
            `;
        }

        function formatStatus(status) {
            const map = {
                'active': 'Active',
                'trialing': 'Trial',
                'past_due': 'Past Due',
                'canceled': 'Canceled',
                'suspended': 'Suspended'
            };
            return map[status] || status || 'None';
        }

        async function loadActivity() {
            const container = document.getElementById('activity-container');

            try {
                const response = await fetch('/api/admin/subscriptions/recent-activity');
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    const items = result.data.slice(0, 10).map(a => {
                        const icon = getActivityIcon(a.event_type);
                        const text = formatActivityText(a);
                        const time = new Date(a.created_at).toLocaleDateString();

                        return `
                            <li class="activity-item">
                                <div class="activity-icon ${a.event_type}">${icon}</div>
                                <div class="activity-text">
                                    <div class="activity-title">${text}</div>
                                    <div class="activity-time">${time}</div>
                                </div>
                            </li>
                        `;
                    }).join('');

                    container.innerHTML = `<ul class="activity-list">${items}</ul>`;
                } else {
                    container.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 2rem;">No recent activity</div>';
                }
            } catch (error) {
                console.error('Failed to load activity:', error);
            }
        }

        function getActivityIcon(type) {
            const icons = {
                'created': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
                'upgraded': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>',
                'canceled': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
                'payment_failed': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>',
            };
            return icons[type] || icons['created'];
        }

        function formatActivityText(a) {
            const name = a.tenant_name || 'Unknown';
            switch (a.event_type) {
                case 'created': return `<strong>${name}</strong> signed up`;
                case 'upgraded': return `<strong>${name}</strong> upgraded to ${a.to_plan_name || 'new plan'}`;
                case 'downgraded': return `<strong>${name}</strong> downgraded to ${a.to_plan_name || 'new plan'}`;
                case 'canceled': return `<strong>${name}</strong> canceled subscription`;
                case 'payment_failed': return `<strong>${name}</strong> payment failed`;
                case 'payment_succeeded': return `<strong>${name}</strong> payment succeeded`;
                default: return `<strong>${name}</strong> - ${a.event_type}`;
            }
        }

        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                loadSubscriptions();
            }
        }

        function nextPage() {
            if (currentPage < totalPages) {
                currentPage++;
                loadSubscriptions();
            }
        }

        async function viewDetails(id) {
            const modal = document.getElementById('detail-modal');
            const content = document.getElementById('modal-content');

            content.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
            modal.classList.add('active');

            try {
                const response = await fetch(`/api/admin/subscriptions/${id}`);
                const result = await response.json();

                if (result.success) {
                    const t = result.data;
                    document.getElementById('modal-tenant-name').textContent = t.name || 'Tenant Details';

                    content.innerHTML = `
                        <div style="margin-bottom: 1.5rem;">
                            <h4 style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">Subscription Info</h4>
                            <table style="width: 100%; font-size: 0.875rem;">
                                <tr><td style="padding: 0.25rem 0; color: var(--text-muted);">Plan</td><td style="padding: 0.25rem 0;"><span class="plan-badge ${t.plan_slug}">${t.plan_name || 'None'}</span></td></tr>
                                <tr><td style="padding: 0.25rem 0; color: var(--text-muted);">Status</td><td style="padding: 0.25rem 0;"><span class="status-badge ${t.subscription_status}">${formatStatus(t.subscription_status)}</span></td></tr>
                                <tr><td style="padding: 0.25rem 0; color: var(--text-muted);">Users</td><td style="padding: 0.25rem 0;">${t.user_count} / ${t.user_limit}</td></tr>
                                <tr><td style="padding: 0.25rem 0; color: var(--text-muted);">Started</td><td style="padding: 0.25rem 0;">${t.subscription_started_at ? new Date(t.subscription_started_at).toLocaleDateString() : '-'}</td></tr>
                                <tr><td style="padding: 0.25rem 0; color: var(--text-muted);">Stripe Customer</td><td style="padding: 0.25rem 0;"><code style="font-size: 0.75rem;">${t.stripe_customer_id || '-'}</code></td></tr>
                            </table>
                        </div>
                        <div>
                            <h4 style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">Contact</h4>
                            <table style="width: 100%; font-size: 0.875rem;">
                                <tr><td style="padding: 0.25rem 0; color: var(--text-muted);">Email</td><td style="padding: 0.25rem 0;">${t.email || '-'}</td></tr>
                                <tr><td style="padding: 0.25rem 0; color: var(--text-muted);">Subdomain</td><td style="padding: 0.25rem 0;"><a href="/t/${t.subdomain}/dashboard" target="_blank">${t.subdomain}</a></td></tr>
                            </table>
                        </div>
                    `;
                }
            } catch (error) {
                content.innerHTML = '<div style="color: var(--error-600);">Failed to load details</div>';
            }
        }

        function showPlanModal(id, currentPlan) {
            document.getElementById('plan-tenant-id').value = id;
            document.getElementById('new-plan-select').value = currentPlan || 'team';
            document.getElementById('plan-modal').classList.add('active');
        }

        async function confirmPlanChange() {
            const id = document.getElementById('plan-tenant-id').value;
            const plan = document.getElementById('new-plan-select').value;
            const reason = document.getElementById('plan-change-reason').value;

            const btn = document.getElementById('confirm-plan-btn');
            btn.disabled = true;
            btn.textContent = 'Updating...';

            try {
                const response = await fetch(`/api/admin/subscriptions/${id}/plan`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ plan_slug: plan, reason: reason })
                });

                const result = await response.json();

                if (result.success) {
                    closeModal('plan-modal');
                    loadSubscriptions();
                    loadStats();
                    alert('Plan updated successfully');
                } else {
                    alert(result.error || 'Failed to update plan');
                }
            } catch (error) {
                alert('Error updating plan');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Update Plan';
            }
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function exportData() {
            alert('Export feature coming soon!');
        }

        // Close modals on overlay click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>

</html>
