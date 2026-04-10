<?php
$pageTitle = 'Email Settings';
$activeNav = 'settings';
include APP_PATH . '/../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1>Email Settings</h1>
        <p class="text-muted">Configure your SMTP server to send emails from the platform</p>
    </div>
</div>

<div class="grid grid-cols-3 gap-6">
    <!-- Main Settings Form -->
    <div class="col-span-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">SMTP Configuration</h3>
            </div>
            <form id="email-settings-form">
                <div class="card-body">
                    <!-- Connection Status -->
                    <div class="alert alert-info mb-4" id="connection-status" style="display: none;">
                        <span id="status-icon">⏳</span>
                        <span id="status-text">Checking connection...</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label required">SMTP Host</label>
                            <input type="text" class="form-input" id="smtp_host" name="smtp_host"
                                placeholder="smtp.gmail.com" required>
                            <p class="form-help">Your email server hostname</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">SMTP Port</label>
                            <input type="number" class="form-input" id="smtp_port" name="smtp_port" value="587"
                                required>
                            <p class="form-help">Common: 587 (TLS), 465 (SSL), 25 (Plain)</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Encryption</label>
                        <select class="form-select" id="encryption" name="encryption">
                            <option value="tls">TLS (Recommended)</option>
                            <option value="ssl">SSL</option>
                            <option value="starttls">STARTTLS</option>
                            <option value="none">None (Not Recommended)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label required">Username</label>
                            <input type="text" class="form-input" id="username" name="username"
                                placeholder="your-email@domain.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" id="password-label">Password</label>
                            <input type="password" class="form-input" id="password" name="password"
                                placeholder="Enter password or App Password">
                            <p class="form-help">For Gmail, use an App Password</p>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h4 class="mb-4">Sender Information</h4>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Sender Name</label>
                            <input type="text" class="form-input" id="sender_name" name="sender_name"
                                placeholder="Your Company Name">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Sender Email</label>
                            <input type="email" class="form-input" id="sender_email" name="sender_email"
                                placeholder="noreply@yourcompany.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reply-To Email</label>
                        <input type="email" class="form-input" id="reply_to_email" name="reply_to_email"
                            placeholder="support@yourcompany.com">
                        <p class="form-help">Where replies should go (optional)</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Daily Limit</label>
                        <input type="number" class="form-input" id="daily_limit" name="daily_limit" value="100" min="1"
                            max="1000">
                        <p class="form-help">Maximum emails per day (prevents spam abuse)</p>
                    </div>
                </div>
                <div class="card-footer flex justify-between">
                    <button type="button" class="btn btn-secondary" onclick="testConnection()">
                        Test Connection
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Stats Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Email Stats</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-sm text-muted">Today</div>
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold" id="stat-today-sent">0</span>
                        <span class="text-sm text-muted">/ <span id="stat-today-limit">100</span></span>
                    </div>
                    <div class="progress mt-1" style="height: 4px;">
                        <div class="progress-bar" id="stat-today-progress" style="width: 0%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div>
                        <div class="text-sm text-muted">This Week</div>
                        <div class="text-lg font-bold" id="stat-week">0</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted">This Month</div>
                        <div class="text-lg font-bold" id="stat-month">0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Email Features</h3>
            </div>
            <div class="card-body p-0">
                <a href="/settings/email-templates" class="block px-4 py-3 border-b hover:bg-gray-50">
                    <div class="flex justify-between items-center">
                        <span>Email Templates</span>
                        <span class="text-muted">→</span>
                    </div>
                </a>
                <a href="/email/logs" class="block px-4 py-3 border-b hover:bg-gray-50">
                    <div class="flex justify-between items-center">
                        <span>Sent Emails</span>
                        <span class="text-muted">→</span>
                    </div>
                </a>
                <a href="/email/compose" class="block px-4 py-3 hover:bg-gray-50">
                    <div class="flex justify-between items-center">
                        <span>Compose Email</span>
                        <span class="text-muted">→</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Help Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Setup Guide</h3>
            </div>
            <div class="card-body text-sm">
                <details class="mb-3">
                    <summary class="cursor-pointer font-bold">Gmail Setup</summary>
                    <div class="mt-2 text-muted">
                        <ol class="list-decimal list-inside">
                            <li>Enable 2-Step Verification</li>
                            <li>Generate an App Password</li>
                            <li>Host: smtp.gmail.com</li>
                            <li>Port: 587, Encryption: TLS</li>
                        </ol>
                    </div>
                </details>
                <details class="mb-3">
                    <summary class="cursor-pointer font-bold">Outlook/Microsoft 365</summary>
                    <div class="mt-2 text-muted">
                        <ol class="list-decimal list-inside">
                            <li>Host: smtp.office365.com</li>
                            <li>Port: 587, Encryption: STARTTLS</li>
                            <li>Use your full email as username</li>
                        </ol>
                    </div>
                </details>
                <details>
                    <summary class="cursor-pointer font-bold">Custom Domain</summary>
                    <div class="mt-2 text-muted">
                        Contact your hosting provider for SMTP details.
                        Common format: mail.yourdomain.com
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        loadSettings();
        loadStats();
    });

    async function loadSettings() {
        try {
            const response = await ERP.api.get('/email/settings');
            if (response.success && response.data) {
                const s = response.data;
                if (s.configured) {
                    document.getElementById('smtp_host').value = s.smtp_host || '';
                    document.getElementById('smtp_port').value = s.smtp_port || 587;
                    document.getElementById('encryption').value = s.encryption || 'tls';
                    document.getElementById('username').value = s.username || '';
                    document.getElementById('sender_name').value = s.sender_name || '';
                    document.getElementById('sender_email').value = s.sender_email || '';
                    document.getElementById('reply_to_email').value = s.reply_to_email || '';
                    document.getElementById('daily_limit').value = s.daily_limit || 100;

                    if (s.has_password) {
                        document.getElementById('password').placeholder = '••••••••';
                        document.getElementById('password-label').textContent = 'Password (saved)';
                    }

                    if (s.is_verified) {
                        showStatus('success', 'SMTP connection verified');
                    }
                }
            }
        } catch (error) {
            console.error('Failed to load settings:', error);
        }
    }

    async function loadStats() {
        try {
            const response = await ERP.api.get('/email/settings/stats');
            if (response.success && response.data) {
                const stats = response.data;
                document.getElementById('stat-today-sent').textContent = stats.today.sent;
                document.getElementById('stat-today-limit').textContent = stats.today.limit;
                document.getElementById('stat-week').textContent = stats.this_week;
                document.getElementById('stat-month').textContent = stats.this_month;

                const percent = (stats.today.used / stats.today.limit) * 100;
                document.getElementById('stat-today-progress').style.width = percent + '%';
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    document.getElementById('email-settings-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = {
            smtp_host: document.getElementById('smtp_host').value,
            smtp_port: parseInt(document.getElementById('smtp_port').value),
            encryption: document.getElementById('encryption').value,
            username: document.getElementById('username').value,
            sender_name: document.getElementById('sender_name').value,
            sender_email: document.getElementById('sender_email').value,
            reply_to_email: document.getElementById('reply_to_email').value,
            daily_limit: parseInt(document.getElementById('daily_limit').value),
        };

        const password = document.getElementById('password').value;
        if (password) {
            formData.password = password;
        }

        try {
            const response = await ERP.api.put('/email/settings', formData);
            if (response.success) {
                ERP.toast.success('Settings saved successfully');
                document.getElementById('password').value = '';
                document.getElementById('password').placeholder = '••••••••';
                document.getElementById('password-label').textContent = 'Password (saved)';
            } else {
                ERP.toast.error(response.message || 'Failed to save settings');
            }
        } catch (error) {
            ERP.toast.error('Failed to save settings');
        }
    });

    async function testConnection() {
        showStatus('info', 'Testing connection...');

        try {
            const response = await ERP.api.post('/email/settings/test', {});
            if (response.success) {
                showStatus('success', 'Connection successful! SMTP is working.');
            } else {
                showStatus('error', response.message || 'Connection failed');
            }
        } catch (error) {
            showStatus('error', 'Connection test failed: ' + (error.message || 'Unknown error'));
        }
    }

    function showStatus(type, message) {
        const container = document.getElementById('connection-status');
        const icon = document.getElementById('status-icon');
        const text = document.getElementById('status-text');

        container.style.display = 'block';
        container.className = 'alert mb-4';

        switch (type) {
            case 'success':
                container.classList.add('alert-success');
                icon.textContent = '✓';
                break;
            case 'error':
                container.classList.add('alert-error');
                icon.textContent = '✕';
                break;
            case 'info':
                container.classList.add('alert-info');
                icon.textContent = '⏳';
                break;
        }

        text.textContent = message;
    }
</script>

<?php include APP_PATH . '/../views/layouts/footer.php'; ?>
