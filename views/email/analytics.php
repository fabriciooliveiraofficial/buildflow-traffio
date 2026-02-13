<?php
$pageTitle = 'Email Analytics';
$activeNav = 'email';
include APP_PATH . '/../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1>Email Analytics</h1>
        <p class="text-muted">Track email performance and engagement</p>
    </div>
    <div>
        <select class="form-select" id="period-select" onchange="loadAnalytics()">
            <option value="7">Last 7 Days</option>
            <option value="30" selected>Last 30 Days</option>
            <option value="90">Last 90 Days</option>
        </select>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-5 gap-4 mb-6">
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-primary" id="stat-sent">0</div>
            <div class="text-sm text-muted">Sent</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-success" id="stat-opened">0</div>
            <div class="text-sm text-muted">Opened</div>
            <div class="text-lg font-bold text-success" id="stat-open-rate">0%</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-info" id="stat-clicked">0</div>
            <div class="text-sm text-muted">Clicked</div>
            <div class="text-lg font-bold text-info" id="stat-click-rate">0%</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-error" id="stat-failed">0</div>
            <div class="text-sm text-muted">Failed</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-3xl font-bold text-warning" id="stat-bounced">0</div>
            <div class="text-sm text-muted">Bounced</div>
            <div class="text-lg font-bold text-warning" id="stat-bounce-rate">0%</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6">
    <!-- Daily Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daily Activity</h3>
        </div>
        <div class="card-body">
            <canvas id="daily-chart" height="250"></canvas>
        </div>
    </div>

    <!-- Top Performing Emails -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Top Performing Emails</h3>
        </div>
        <div class="card-body p-0">
            <table class="table" id="top-emails-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th class="text-center">Sent</th>
                        <th class="text-center">Opened</th>
                        <th class="text-center">Clicked</th>
                    </tr>
                </thead>
                <tbody id="top-emails-body">
                    <tr>
                        <td colspan="4" class="text-center text-muted">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="grid grid-cols-3 gap-4 mt-6">
    <a href="/email/compose" class="card hover:shadow-lg transition-shadow">
        <div class="card-body text-center">
            <div class="text-3xl mb-2">✉️</div>
            <div class="font-bold">Compose Email</div>
        </div>
    </a>
    <a href="/email/logs" class="card hover:shadow-lg transition-shadow">
        <div class="card-body text-center">
            <div class="text-3xl mb-2">📋</div>
            <div class="font-bold">Sent Emails</div>
        </div>
    </a>
    <a href="/settings/email" class="card hover:shadow-lg transition-shadow">
        <div class="card-body text-center">
            <div class="text-3xl mb-2">⚙️</div>
            <div class="font-bold">Email Settings</div>
        </div>
    </a>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let dailyChart = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadAnalytics();
    });

    async function loadAnalytics() {
        const days = document.getElementById('period-select').value;

        try {
            const response = await ERP.api.get('/email/analytics?days=' + days);
            if (response.success) {
                updateStats(response.data);
                updateChart(response.data.daily);
                updateTopEmails(response.data.top_emails);
            }
        } catch (error) {
            console.error('Failed to load analytics:', error);
        }
    }

    function updateStats(data) {
        document.getElementById('stat-sent').textContent = data.totals.sent.toLocaleString();
        document.getElementById('stat-opened').textContent = data.totals.opened.toLocaleString();
        document.getElementById('stat-clicked').textContent = data.totals.clicked.toLocaleString();
        document.getElementById('stat-failed').textContent = data.totals.failed.toLocaleString();
        document.getElementById('stat-bounced').textContent = data.totals.bounced.toLocaleString();

        document.getElementById('stat-open-rate').textContent = data.rates.open_rate + '%';
        document.getElementById('stat-click-rate').textContent = data.rates.click_rate + '%';
        document.getElementById('stat-bounce-rate').textContent = data.rates.bounce_rate + '%';
    }

    function updateChart(dailyData) {
        const ctx = document.getElementById('daily-chart').getContext('2d');

        // Reverse to show oldest first
        const data = [...dailyData].reverse();

        const labels = data.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });

        const sentData = data.map(d => d.sent);
        const openedData = data.map(d => d.opened);
        const clickedData = data.map(d => d.clicked);

        if (dailyChart) {
            dailyChart.destroy();
        }

        dailyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sent',
                        data: sentData,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Opened',
                        data: openedData,
                        borderColor: '#27ae60',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0.3
                    },
                    {
                        label: 'Clicked',
                        data: clickedData,
                        borderColor: '#9b59b6',
                        backgroundColor: 'transparent',
                        borderDash: [2, 2],
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function updateTopEmails(emails) {
        const tbody = document.getElementById('top-emails-body');

        if (!emails || emails.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Not enough data yet</td></tr>';
            return;
        }

        tbody.innerHTML = emails.map(email => {
            const openRate = email.sent > 0 ? Math.round((email.opened / email.sent) * 100) : 0;
            const clickRate = email.sent > 0 ? Math.round((email.clicked / email.sent) * 100) : 0;

            return '<tr>' +
                '<td class="truncate" style="max-width: 200px;">' + email.subject + '</td>' +
                '<td class="text-center">' + email.sent + '</td>' +
                '<td class="text-center">' + email.opened + ' <span class="text-muted text-sm">(' + openRate + '%)</span></td>' +
                '<td class="text-center">' + email.clicked + ' <span class="text-muted text-sm">(' + clickRate + '%)</span></td>' +
                '</tr>';
        }).join('');
    }
</script>

<?php include APP_PATH . '/../views/layouts/footer.php'; ?>