<?php
/**
 * Developer Layout - Minimal layout for developer console
 * Separate from tenant ERP, focused on support management
 */

if (session_status() === PHP_SESSION_NONE)
    session_start();

$devUser = $_SESSION['dev_user'] ?? null;
$devApiToken = $_SESSION['dev_api_token'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Developer Console' ?> | BuildFlow Support</title>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?= time() ?>">
    <style>
        :root {
            --dev-primary: #7c3aed;
            --dev-primary-hover: #6d28d9;
            --dev-bg: #0f172a;
            --dev-surface: #1e293b;
            --dev-border: #334155;
            --dev-text: #f1f5f9;
            --dev-muted: #94a3b8;
        }

        body {
            background: var(--dev-bg);
            color: var(--dev-text);
            min-height: 100vh;
        }

        .dev-header {
            background: var(--dev-surface);
            border-bottom: 1px solid var(--dev-border);
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .dev-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 18px;
        }

        .dev-brand-icon {
            width: 36px;
            height: 36px;
            background: var(--dev-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dev-nav {
            display: flex;
            gap: 24px;
        }

        .dev-nav-link {
            color: var(--dev-muted);
            text-decoration: none;
            padding: 8px 0;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .dev-nav-link:hover,
        .dev-nav-link.active {
            color: var(--dev-text);
            border-color: var(--dev-primary);
        }

        .dev-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dev-user-name {
            font-size: 14px;
        }

        .dev-user-role {
            font-size: 12px;
            color: var(--dev-muted);
        }

        .dev-avatar {
            width: 36px;
            height: 36px;
            background: var(--dev-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .dev-main {
            padding: 24px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Override card styles for dark theme */
        .card {
            background: var(--dev-surface);
            border: 1px solid var(--dev-border);
        }

        .card-header {
            border-bottom-color: var(--dev-border);
        }

        .table thead th {
            background: rgba(0, 0, 0, 0.2);
            color: var(--dev-muted);
        }

        .table tbody tr {
            border-color: var(--dev-border);
        }

        .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .form-control,
        .form-select {
            background: var(--dev-bg);
            border-color: var(--dev-border);
            color: var(--dev-text);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--dev-primary);
        }

        .btn-primary {
            background: var(--dev-primary);
            border-color: var(--dev-primary);
        }

        .btn-primary:hover {
            background: var(--dev-primary-hover);
        }

        .stat-card {
            background: var(--dev-surface);
            border: 1px solid var(--dev-border);
            padding: 20px;
            border-radius: 12px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dev-text);
        }

        .stat-label {
            font-size: 13px;
            color: var(--dev-muted);
            margin-top: 4px;
        }

        .tenant-badge {
            background: rgba(124, 58, 237, 0.2);
            color: #a78bfa;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <header class="dev-header">
        <div class="dev-brand">
            <div class="dev-brand-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            </div>
            Developer Support Console
        </div>

        <nav class="dev-nav">
            <a href="/dev/support" class="dev-nav-link <?= ($page ?? '') === 'support-list' ? 'active' : '' ?>">All
                Tickets</a>
            <a href="/dev/support?status=new" class="dev-nav-link">New</a>
            <a href="/dev/support?status=open" class="dev-nav-link">Open</a>
            <a href="/dev/support?priority=urgent" class="dev-nav-link">Urgent</a>
            <span style="color: var(--dev-border); margin: 0 8px;">|</span>
            <a href="/dev/subscriptions"
                class="dev-nav-link <?= ($page ?? '') === 'subscriptions' ? 'active' : '' ?>">Subscriptions</a>
            <a href="/dev/releases"
                class="dev-nav-link <?= ($page ?? '') === 'releases' ? 'active' : '' ?>">Releases</a>
        </nav>

        <div class="dev-user">
            <div>
                <div class="dev-user-name"><?= htmlspecialchars($devUser['name'] ?? 'Developer') ?></div>
                <div class="dev-user-role"><?= htmlspecialchars(ucfirst($devUser['role'] ?? 'Developer')) ?></div>
            </div>
            <div class="dev-avatar">
                <?= strtoupper(substr($devUser['name'] ?? 'D', 0, 1)) ?>
            </div>
            <a href="/dev/logout"
                style="margin-left: 12px; color: var(--dev-muted); text-decoration: none; font-size: 13px;"
                title="Logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
            </a>
        </div>
    </header>

    <main class="dev-main">
        <?= $content ?? '' ?>
    </main>

    <script>
        // Developer console token setup
        <?php if ($devApiToken): ?>
            localStorage.setItem('erp_token', <?= json_encode($devApiToken) ?>);
            console.log('[DEV] Token set in localStorage');
        <?php else: ?>
            console.warn('[DEV] No API token found in session. You may need to re-login.');
        <?php endif; ?>
    </script>
    <script src="/assets/js/app.js?v=<?= time() ?>"></script>
</body>

</html>
