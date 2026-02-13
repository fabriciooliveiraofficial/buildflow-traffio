<?php
$title = 'Settings';
$page = 'settings';

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold">Settings</h1>
    <p class="text-muted text-sm">Manage your account and system preferences</p>
</div>

<div class="grid grid-cols-4 gap-6">
    <!-- Settings Navigation -->
    <div class="card">
        <div class="card-body p-0">
            <nav class="settings-nav">
                <a href="#general" class="settings-nav-item active" onclick="showSection('general')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                    General
                </a>
                <a href="#profile" class="settings-nav-item" onclick="showSection('profile')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    Profile
                </a>
                <a href="#security" class="settings-nav-item" onclick="showSection('security')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    Security
                </a>
                <a href="#categories" class="settings-nav-item" onclick="showSection('categories')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6" />
                        <line x1="8" y1="12" x2="21" y2="12" />
                        <line x1="8" y1="18" x2="21" y2="18" />
                        <line x1="3" y1="6" x2="3.01" y2="6" />
                        <line x1="3" y1="12" x2="3.01" y2="12" />
                        <line x1="3" y1="18" x2="3.01" y2="18" />
                    </svg>
                    Categories
                </a>
                <a href="#roles" class="settings-nav-item" onclick="showSection('roles')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    Roles & Permissions
                </a>
                <a href="#team" class="settings-nav-item" onclick="showSection('team')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Team & Invitations
                </a>
                <a href="#billing" class="settings-nav-item" onclick="showSection('billing')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    Billing & Payments
                </a>
                <a href="settings/subscription" class="settings-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                    Subscription
                    <span class="nav-badge">Plan</span>
                </a>
                <a href="settings/email" class="settings-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        <polyline points="22,6 12,13 2,6" />
                    </svg>
                    Email Settings
                </a>
                <a href="settings/email-templates" class="settings-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                    </svg>
                    Email Templates
                </a>
                <a href="settings/email-automations" class="settings-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        <polyline points="9 12 12 15 16 10"></polyline>
                    </svg>
                    Email Automations
                </a>
                <a href="settings/email-signature" class="settings-nav-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                        <line x1="16" y1="11" x2="22" y2="11"></line>
                    </svg>
                    Email Signature
                </a>
            </nav>
        </div>
    </div>

    <!-- Settings Content -->
    <div style="grid-column: span 3;">
        <!-- General Settings -->
        <div id="general-section" class="settings-section">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">General Settings</h3>
                </div>
                <form id="general-form">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-input" name="company_name" id="company_name">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Language</label>
                                <select class="form-select" name="language" id="language">
                                    <option value="en">English</option>
                                    <option value="es">Spanish</option>
                                    <option value="fr">French</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Timezone</label>
                                <select class="form-select" name="timezone" id="timezone">
                                    <option value="America/New_York">Eastern Time</option>
                                    <option value="America/Chicago">Central Time</option>
                                    <option value="America/Denver">Mountain Time</option>
                                    <option value="America/Los_Angeles">Pacific Time</option>
                                    <option value="UTC">UTC</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Date Format</label>
                                <select class="form-select" name="date_format" id="date_format">
                                    <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                    <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                    <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Currency</label>
                                <select class="form-select" name="currency" id="currency">
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Theme</label>
                            <div class="flex gap-4">
                                <label class="radio-label">
                                    <input type="radio" name="theme" value="light" checked> Light
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="theme" value="dark"> Dark
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="theme" value="auto"> Auto
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Profile Settings -->
        <div id="profile-section" class="settings-section" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profile Settings</h3>
                </div>
                <form id="profile-form">
                    <div class="card-body">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-input" name="first_name" id="first_name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-input" name="last_name" id="last_name">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" name="email" id="email">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-input" name="phone" id="phone">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Settings -->
        <div id="security-section" class="settings-section" style="display: none;">
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Change Password</h3>
                </div>
                <form id="password-form">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-input" name="current_password" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-input" name="new_password" required minlength="8">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-input" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Two-Factor Authentication</h3>
                </div>
                <div class="card-body">
                    <p class="mb-4">Add an extra layer of security to your account.</p>
                    <button class="btn btn-outline" id="2fa-btn" onclick="setup2FA()">Enable 2FA</button>
                </div>
            </div>
        </div>

        <!-- Categories Settings -->
        <div id="categories-section" class="settings-section" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Custom Categories</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label">Category Type</label>
                        <select class="form-select" id="category-type" style="width: 200px;">
                            <option value="projects">Projects</option>
                            <option value="expenses">Expenses</option>
                            <option value="tasks">Tasks</option>
                            <option value="inventory">Inventory</option>
                        </select>
                    </div>

                    <div id="categories-list" class="mb-4"></div>

                    <div class="flex gap-2">
                        <input type="text" class="form-input flex-1" id="new-category" placeholder="New category name">
                        <button class="btn btn-primary" onclick="addCategory()">Add</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles & Permissions Settings -->
        <div id="roles-section" class="settings-section" style="display: none;">
            <div class="card mb-6">
                <div class="card-header flex justify-between">
                    <h3 class="card-title">Roles</h3>
                    <button class="btn btn-primary btn-sm" onclick="openRoleModal()">+ New Role</button>
                </div>
                <div class="card-body p-0">
                    <div id="roles-list"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">User Role Assignment</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Current Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-list"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Team & Invitations Section -->
        <div id="team-section" class="settings-section" style="display: none;">
            <div class="card mb-6">
                <div class="card-header flex justify-between">
                    <h3 class="card-title">Team Invitations</h3>
                    <button class="btn btn-primary btn-sm" onclick="openInviteModal()">+ Invite User</button>
                </div>
                <div class="card-body p-0">
                    <div id="invitations-loading" class="p-4 text-center text-muted">Loading...</div>
                    <table class="table" id="invitations-table" style="display: none;">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Invited</th>
                                <th>Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="invitations-list"></tbody>
                    </table>
                    <div id="invitations-empty" class="p-4 text-center text-muted" style="display: none;">
                        <p>No invitations yet. Click "Invite User" to invite someone to your team.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Billing & Payments Section -->
    <div id="billing-section" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Billing & Payments Integration</h3>
            </div>
            <div class="card-body" id="stripe-content">
                <!-- Loading state -->
                <div id="stripe-loading" class="text-center p-8">
                    <div class="spinner"></div>
                    <p class="text-muted mt-2">Loading Stripe status...</p>
                </div>

                <!-- Not connected state -->
                <div id="stripe-not-connected" style="display: none;">
                    <div class="text-center p-8">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)"
                            stroke-width="1.5" style="margin: 0 auto 1rem;">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        <h4 class="mb-2">Connect with Stripe</h4>
                        <p class="text-muted mb-6" style="max-width: 400px; margin: 0 auto;">Accept payments, manage
                            subscriptions, and track transactions directly through your ERP.</p>

                        <div class="stripe-options mb-6" style="max-width: 400px; margin: 0 auto;">
                            <label class="stripe-option">
                                <input type="radio" name="stripe_mode" value="connect_new" checked>
                                <span class="stripe-option-content">
                                    <strong>I need a new Stripe account</strong>
                                    <small>We'll guide you through creating one</small>
                                </span>
                            </label>
                            <label class="stripe-option">
                                <input type="radio" name="stripe_mode" value="connect_existing">
                                <span class="stripe-option-content">
                                    <strong>I already have a Stripe account</strong>
                                    <small>Connect your existing account</small>
                                </span>
                            </label>
                            <label class="stripe-option">
                                <input type="radio" name="stripe_mode" value="manual">
                                <span class="stripe-option-content">
                                    <strong>Enter API keys manually</strong>
                                    <small>For advanced users</small>
                                </span>
                            </label>
                        </div>

                        <button type="button" class="btn btn-primary btn-lg" onclick="connectStripe()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" style="margin-right: 8px;">
                                <path
                                    d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" />
                                <path d="M8 12H16" />
                                <path d="M12 8V16" />
                            </svg>
                            Connect with Stripe
                        </button>
                    </div>

                    <!-- Manual Keys Form (hidden by default) -->
                    <div id="manual-keys-form" style="display: none;" class="mt-6">
                        <hr class="my-6">
                        <h4 class="mb-4">Enter Stripe API Keys</h4>
                        <form id="stripe-manual-form">
                            <div class="form-group">
                                <label class="form-label required">Publishable Key</label>
                                <input type="text" class="form-input" name="publishable_key" placeholder="pk_live_..."
                                    required>
                                <p class="text-sm text-muted mt-1">Starts with pk_live_ or pk_test_</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Secret Key</label>
                                <input type="password" class="form-input" name="secret_key" placeholder="sk_live_..."
                                    required>
                                <p class="text-sm text-muted mt-1">Starts with sk_live_ or sk_test_</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Webhook Signing Secret</label>
                                <input type="password" class="form-input" name="webhook_secret" placeholder="whsec_...">
                                <p class="text-sm text-muted mt-1">Optional. Required for receiving webhook events.</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="btn btn-primary">Save API Keys</button>
                                <button type="button" class="btn btn-secondary"
                                    onclick="cancelManualKeys()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Connected state -->
                <div id="stripe-connected" style="display: none;">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="stripe-status-icon success">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                            <div>
                                <h4 class="mb-0">Stripe Connected</h4>
                                <p class="text-muted text-sm mb-0" id="stripe-business-name"></p>
                            </div>
                        </div>
                        <span class="badge" id="stripe-mode-badge">Live</span>
                    </div>

                    <!-- Status indicators -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="stripe-status-card">
                            <div class="stripe-status-indicator" id="charges-status"></div>
                            <span>Charges</span>
                        </div>
                        <div class="stripe-status-card">
                            <div class="stripe-status-indicator" id="payouts-status"></div>
                            <span>Payouts</span>
                        </div>
                        <div class="stripe-status-card">
                            <div class="stripe-status-indicator" id="details-status"></div>
                            <span>Details</span>
                        </div>
                    </div>

                    <!-- Quick stats -->
                    <div class="grid grid-cols-3 gap-4 mb-6" id="stripe-stats" style="display: none;">
                        <div class="stat-card">
                            <div class="stat-value" id="stripe-total-amount">$0</div>
                            <div class="stat-label">This Month</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="stripe-transactions">0</div>
                            <div class="stat-label">Transactions</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="stripe-success-rate">0%</div>
                            <div class="stat-label">Success Rate</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-secondary" onclick="syncStripe()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                            </svg>
                            Sync Now
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="viewStripeDashboard()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                            Stripe Dashboard
                        </button>
                        <button type="button" class="btn btn-error" onclick="disconnectStripe()">
                            Disconnect
                        </button>
                    </div>

                    <!-- Pending onboarding -->
                    <div id="stripe-pending" style="display: none;" class="mt-6">
                        <div class="alert alert-warning">
                            <strong>Complete your Stripe setup</strong>
                            <p class="mb-2">Your account setup is incomplete. Complete the onboarding to start accepting
                                payments.</p>
                            <button type="button" class="btn btn-sm btn-warning" onclick="resumeOnboarding()">Complete
                                Setup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Role Modal -->
<div class="modal" id="role-modal" style="max-width: 600px;">
    <div class="modal-header">
        <h3 class="modal-title" id="role-modal-title">New Role</h3>
        <button class="modal-close" onclick="Modal.close('role-modal')">×</button>
    </div>
    <form id="role-form">
        <input type="hidden" name="role_id" id="role-id">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Role Name</label>
                <input type="text" class="form-input" name="name" id="role-name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" class="form-input" name="description" id="role-description">
            </div>
            <div class="form-group">
                <label class="form-label">Permissions</label>
                <div id="permissions-list" class="permissions-grid"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('role-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Role</button>
        </div>
    </form>
</div>

<!-- Invite User Modal -->
<div class="modal" id="invite-modal" style="max-width: 500px;">
    <div class="modal-header">
        <h3 class="modal-title">Invite Team Member</h3>
        <button class="modal-close" onclick="Modal.close('invite-modal')">×</button>
    </div>
    <form id="invite-form">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Email Address</label>
                <input type="email" class="form-input" name="email" id="invite-email"
                    placeholder="colleague@example.com" required>
            </div>
            <div class="form-group">
                <label class="form-label required">Role</label>
                <select class="form-select" name="role_id" id="invite-role" required>
                    <option value="">Select a role...</option>
                </select>
                <p class="text-sm text-muted mt-1">The user will have the permissions of this role</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-input" name="first_name" id="invite-first-name"
                        placeholder="Optional">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-input" name="last_name" id="invite-last-name" placeholder="Optional">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Personal Message</label>
                <textarea class="form-input" name="message" id="invite-message" rows="2"
                    placeholder="Optional message to include in the invitation email"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="Modal.close('invite-modal')">Cancel</button>
            <button type="submit" class="btn btn-primary" id="invite-submit-btn">Send Invitation</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        loadSettings();
        loadProfile();
        loadCategories();

        document.getElementById('general-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);
            try {
                await ERP.api.put('/settings', data);
                ERP.toast.success('Settings saved');
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        document.getElementById('profile-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);
            try {
                await ERP.api.put('/auth/profile', data);
                ERP.toast.success('Profile updated');
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        document.getElementById('password-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = ERP.FormUtils.serialize(this);
            if (data.new_password !== data.confirm_password) {
                ERP.toast.error('Passwords do not match');
                return;
            }
            try {
                await ERP.api.post('/auth/change-password', data);
                ERP.toast.success('Password changed');
                this.reset();
            } catch (error) {
                ERP.toast.error(error.message);
            }
        });

        document.getElementById('category-type').addEventListener('change', loadCategories);
    });

    function showSection(section) {
        document.querySelectorAll('.settings-section').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.settings-nav-item').forEach(el => el.classList.remove('active'));

        document.getElementById(section + '-section').style.display = 'block';
        document.querySelector(`[onclick="showSection('${section}')"]`).classList.add('active');
    }

    async function loadSettings() {
        try {
            const response = await ERP.api.get('/settings');
            if (response.success) {
                const s = response.data;
                if (s.company_name) document.getElementById('company_name').value = s.company_name;
                if (s.language) document.getElementById('language').value = s.language;
                if (s.timezone) document.getElementById('timezone').value = s.timezone;
                if (s.date_format) document.getElementById('date_format').value = s.date_format;
                if (s.currency) document.getElementById('currency').value = s.currency;
            }
        } catch (error) {
            console.error('Failed to load settings');
        }
    }

    async function loadProfile() {
        try {
            const response = await ERP.api.get('/auth/profile');
            if (response.success) {
                const p = response.data;
                document.getElementById('first_name').value = p.first_name || '';
                document.getElementById('last_name').value = p.last_name || '';
                document.getElementById('email').value = p.email || '';
                document.getElementById('phone').value = p.phone || '';
            }
        } catch (error) {
            console.error('Failed to load profile');
        }
    }

    async function loadCategories() {
        const type = document.getElementById('category-type').value;
        try {
            const response = await ERP.api.get(`/settings/categories/${type}`);
            if (response.success) {
                renderCategories(response.data);
            }
        } catch (error) {
            document.getElementById('categories-list').innerHTML = '<p class="text-muted">No categories found</p>';
        }
    }

    function renderCategories(categories) {
        const list = document.getElementById('categories-list');
        if (categories.length === 0) {
            list.innerHTML = '<p class="text-muted">No categories found</p>';
            return;
        }
        list.innerHTML = categories.map(c => `
        <div class="flex justify-between items-center p-2 mb-1 bg-secondary rounded">
            <span>${c.name || c}</span>
            <button class="btn btn-icon btn-sm btn-secondary" onclick="deleteCategory('${c.id || c}')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    `).join('');
    }

    async function addCategory() {
        const type = document.getElementById('category-type').value;
        const name = document.getElementById('new-category').value.trim();
        if (!name) return;

        try {
            await ERP.api.post(`/settings/categories/${type}`, { name });
            document.getElementById('new-category').value = '';
            loadCategories();
            ERP.toast.success('Category added');
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    async function deleteCategory(id) {
        const type = document.getElementById('category-type').value;
        try {
            await ERP.api.delete(`/settings/categories/${type}/${id}`);
            loadCategories();
            ERP.toast.success('Category deleted');
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    async function setup2FA() {
        try {
            const response = await ERP.api.post('/auth/2fa/setup', {});
            if (response.success) {
                alert('2FA QR code: ' + response.data.qr_code);
            }
        } catch (error) {
            ERP.toast.error(error.message);
        }
    }

    // ===== ROLES & PERMISSIONS =====
    let roles = [];
    let availablePermissions = {};
    let allUsers = [];

    async function loadRoles() {
        try {
            const [rolesResp, permsResp, usersResp] = await Promise.all([
                ERP.api.get('/roles'),
                ERP.api.get('/roles/permissions'),
                ERP.api.get('/roles/users/list')
            ]);

            if (rolesResp.success) {
                roles = rolesResp.data;
                renderRoles();
            }
            if (permsResp.success) {
                availablePermissions = permsResp.data;
            }
            if (usersResp.success) {
                allUsers = usersResp.data;
                renderUsers();
            }
        } catch (error) {
            console.error('Failed to load roles');
        }
    }

    function renderRoles() {
        const container = document.getElementById('roles-list');
        if (!roles.length) {
            container.innerHTML = '<p class="p-4 text-muted">No roles found</p>';
            return;
        }

        container.innerHTML = roles.map(role => `
            <div class="flex justify-between items-center p-4 border-b">
                <div>
                    <div class="font-medium">${role.name}</div>
                    <div class="text-sm text-muted">${role.description || ''} · ${role.user_count || 0} users</div>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-sm btn-secondary" onclick="editRole(${role.id})">Edit</button>
                    ${!role.is_system ? `<button class="btn btn-sm btn-error" onclick="deleteRole(${role.id})">Delete</button>` : ''}
                </div>
            </div>
        `).join('');
    }

    function renderUsers() {
        const tbody = document.getElementById('users-list');
        if (!allUsers.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No users found</td></tr>';
            return;
        }

        tbody.innerHTML = allUsers.map(user => `
            <tr>
                <td>${user.first_name} ${user.last_name}</td>
                <td>${user.email}</td>
                <td><span class="badge badge-secondary">${user.role_name || 'None'}</span></td>
                <td>
                    <select class="form-select form-select-sm" style="width: 150px;" onchange="assignRole(${user.id}, this.value)">
                        <option value="">Select Role</option>
                        ${roles.map(r => `<option value="${r.id}" ${user.role_id == r.id ? 'selected' : ''}>${r.name}</option>`).join('')}
                    </select>
                </td>
            </tr>
        `).join('');
    }

    function openRoleModal(roleId = null) {
        document.getElementById('role-id').value = roleId || '';
        document.getElementById('role-name').value = '';
        document.getElementById('role-description').value = '';
        document.getElementById('role-modal-title').textContent = roleId ? 'Edit Role' : 'New Role';

        renderPermissionsCheckboxes([]);
        Modal.open('role-modal');
    }

    function renderPermissionsCheckboxes(selectedPerms) {
        const container = document.getElementById('permissions-list');
        let html = '';

        for (const [module, perms] of Object.entries(availablePermissions)) {
            html += `<div class="permission-module">
                <div class="permission-module-title">${module.charAt(0).toUpperCase() + module.slice(1)}</div>`;

            for (const [key, label] of Object.entries(perms)) {
                const checked = selectedPerms.includes(key) ? 'checked' : '';
                html += `<label class="permission-item">
                    <input type="checkbox" name="permissions[]" value="${key}" ${checked}>
                    ${label}
                </label>`;
            }

            html += '</div>';
        }

        container.innerHTML = html;
    }

    async function editRole(id) {
        try {
            const response = await ERP.api.get('/roles/' + id);
            if (response.success) {
                const role = response.data;
                document.getElementById('role-id').value = role.id;
                document.getElementById('role-name').value = role.name;
                document.getElementById('role-description').value = role.description || '';
                document.getElementById('role-modal-title').textContent = 'Edit Role';

                renderPermissionsCheckboxes(role.permissions || []);
                Modal.open('role-modal');
            }
        } catch (error) {
            ERP.toast.error('Failed to load role');
        }
    }

    async function deleteRole(id) {
        if (!confirm('Delete this role?')) return;
        try {
            await ERP.api.delete('/roles/' + id);
            ERP.toast.success('Role deleted');
            loadRoles();
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to delete role');
        }
    }

    async function assignRole(userId, roleId) {
        if (!roleId) return;
        try {
            await ERP.api.put('/users/' + userId + '/role', { role_id: parseInt(roleId) });
            ERP.toast.success('Role assigned');
            loadRoles();
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to assign role');
        }
    }

    // Role form submit
    document.getElementById('role-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const roleId = document.getElementById('role-id').value;
        const name = document.getElementById('role-name').value;
        const description = document.getElementById('role-description').value;

        const checkboxes = document.querySelectorAll('#permissions-list input[type="checkbox"]:checked');
        const permissions = Array.from(checkboxes).map(cb => cb.value);

        try {
            if (roleId) {
                await ERP.api.put('/roles/' + roleId, { name, description, permissions });
                ERP.toast.success('Role updated');
            } else {
                await ERP.api.post('/roles', { name, description, permissions });
                ERP.toast.success('Role created');
            }
            Modal.close('role-modal');
            loadRoles();
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to save role');
        }
    });

    // Load roles when switching to roles section
    document.querySelector('[onclick="showSection(\'roles\')"]').addEventListener('click', function () {
        loadRoles();
    });

    // ===== TEAM & INVITATIONS =====
    let invitations = [];

    async function loadInvitations() {
        try {
            document.getElementById('invitations-loading').style.display = 'block';
            document.getElementById('invitations-table').style.display = 'none';
            document.getElementById('invitations-empty').style.display = 'none';

            const response = await ERP.api.get('/invitations');

            if (response.success) {
                invitations = response.data || [];
                renderInvitations();
            }
        } catch (error) {
            console.error('Failed to load invitations:', error);
            document.getElementById('invitations-loading').textContent = 'Failed to load invitations';
        }
    }

    function renderInvitations() {
        const loading = document.getElementById('invitations-loading');
        const table = document.getElementById('invitations-table');
        const empty = document.getElementById('invitations-empty');
        const tbody = document.getElementById('invitations-list');

        loading.style.display = 'none';

        if (!invitations.length) {
            empty.style.display = 'block';
            table.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        table.style.display = 'table';

        const statusBadgeClass = {
            'pending': 'badge-warning',
            'accepted': 'badge-success',
            'expired': 'badge-secondary',
            'cancelled': 'badge-secondary'
        };

        tbody.innerHTML = invitations.map(inv => {
            const createdAt = new Date(inv.created_at).toLocaleDateString();
            const expiresAt = inv.expires_at ? new Date(inv.expires_at).toLocaleDateString() : '-';
            const isExpired = inv.expires_at && new Date(inv.expires_at) < new Date();
            const statusClass = isExpired && inv.status === 'pending' ? 'badge-secondary' : (statusBadgeClass[inv.status] || 'badge-secondary');
            const displayStatus = isExpired && inv.status === 'pending' ? 'Expired' : inv.status.charAt(0).toUpperCase() + inv.status.slice(1);

            let actions = '';
            if (inv.status === 'pending') {
                actions = `
                    <button class="btn btn-sm btn-secondary" onclick="resendInvitation(${inv.id})" title="Resend">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="m21 10-3.5-3.5A9 9 0 1 0 5.64 18.36"></path>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-error" onclick="cancelInvitation(${inv.id})" title="Cancel">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                `;
            }

            return `
                <tr>
                    <td>
                        <div>${inv.email}</div>
                        ${inv.first_name ? `<div class="text-sm text-muted">${inv.first_name} ${inv.last_name || ''}</div>` : ''}
                    </td>
                    <td><span class="badge badge-secondary">${inv.role_name || 'Unknown'}</span></td>
                    <td><span class="badge ${statusClass}">${displayStatus}</span></td>
                    <td class="text-sm text-muted">${createdAt}</td>
                    <td class="text-sm text-muted">${inv.status === 'pending' ? expiresAt : '-'}</td>
                    <td>
                        <div class="flex gap-1">${actions}</div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function openInviteModal() {
        document.getElementById('invite-form').reset();

        // Populate roles dropdown
        const roleSelect = document.getElementById('invite-role');
        roleSelect.innerHTML = '<option value="">Select a role...</option>';

        if (roles.length) {
            roles.forEach(role => {
                const option = document.createElement('option');
                option.value = role.id;
                option.textContent = role.name || role.display_name;
                roleSelect.appendChild(option);
            });
        }

        Modal.open('invite-modal');
    }

    async function resendInvitation(id) {
        if (!confirm('Resend this invitation?')) return;

        try {
            const response = await ERP.api.post(`/invitations/${id}/resend`);
            if (response.success) {
                ERP.toast.success(response.data.message || 'Invitation resent!');
                loadInvitations();
            } else {
                throw new Error(response.error || 'Failed to resend');
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to resend invitation');
        }
    }

    async function cancelInvitation(id) {
        if (!confirm('Cancel this invitation? The invitee will no longer be able to join.')) return;

        try {
            await ERP.api.delete(`/invitations/${id}`);
            ERP.toast.success('Invitation cancelled');
            loadInvitations();
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to cancel invitation');
        }
    }

    // Invite form submit
    document.getElementById('invite-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = document.getElementById('invite-submit-btn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        const data = {
            email: document.getElementById('invite-email').value,
            role_id: parseInt(document.getElementById('invite-role').value),
            first_name: document.getElementById('invite-first-name').value || null,
            last_name: document.getElementById('invite-last-name').value || null,
            message: document.getElementById('invite-message').value || null
        };

        try {
            const response = await ERP.api.post('/invitations', data);
            if (response.success) {
                ERP.toast.success(response.data.message || 'Invitation sent!');
                Modal.close('invite-modal');
                loadInvitations();
            } else {
                throw new Error(response.error || 'Failed to send invitation');
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to send invitation');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Invitation';
        }
    });

    // Load invitations when switching to team section
    document.querySelector('[onclick="showSection(\'team\')"]').addEventListener('click', function () {
        // Also load roles if not already loaded
        if (!roles.length) {
            loadRoles();
        }
        loadInvitations();
    });

    // ==========================================
    // STRIPE INTEGRATION
    // ==========================================

    // Load Stripe status when billing section is shown
    document.querySelector('[onclick="showSection(\'billing\')"]')?.addEventListener('click', function () {
        loadStripeStatus();
    });

    // Check URL for Stripe callback
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('stripe') === 'success') {
        showSection('billing');
        loadStripeStatus();
        ERP.toast.success('Stripe connected successfully!');
        // Clean URL
        window.history.replaceState({}, document.title, '/settings#billing');
    } else if (urlParams.get('stripe') === 'error') {
        showSection('billing');
        loadStripeStatus();
        ERP.toast.error(urlParams.get('message') || 'Stripe connection failed');
        window.history.replaceState({}, document.title, '/settings#billing');
    } else if (urlParams.get('stripe') === 'refresh') {
        showSection('billing');
        resumeOnboarding();
    }

    async function loadStripeStatus() {
        document.getElementById('stripe-loading').style.display = 'block';
        document.getElementById('stripe-not-connected').style.display = 'none';
        document.getElementById('stripe-connected').style.display = 'none';

        try {
            const response = await ERP.api.get('/stripe/status');

            document.getElementById('stripe-loading').style.display = 'none';

            if (response.success && response.data.connected) {
                showStripeConnected(response.data);
            } else {
                document.getElementById('stripe-not-connected').style.display = 'block';
            }
        } catch (error) {
            document.getElementById('stripe-loading').style.display = 'none';
            document.getElementById('stripe-not-connected').style.display = 'block';
        }
    }

    function showStripeConnected(data) {
        document.getElementById('stripe-connected').style.display = 'block';

        // Business name
        document.getElementById('stripe-business-name').textContent =
            data.business_name || data.stripe_account_id || 'Connected Account';

        // Mode badge
        const modeBadge = document.getElementById('stripe-mode-badge');
        modeBadge.textContent = data.livemode ? 'Live' : 'Test';
        modeBadge.className = data.livemode ? 'badge badge-success' : 'badge badge-warning';

        // Status indicators
        setStatusIndicator('charges-status', data.charges_enabled);
        setStatusIndicator('payouts-status', data.payouts_enabled);
        setStatusIndicator('details-status', data.details_submitted);

        // Show pending alert if not fully set up
        const pendingDiv = document.getElementById('stripe-pending');
        if (!data.charges_enabled || !data.details_submitted) {
            pendingDiv.style.display = 'block';
        } else {
            pendingDiv.style.display = 'none';
            // Load stats if fully connected
            loadStripeStats();
        }
    }

    function setStatusIndicator(elementId, enabled) {
        const el = document.getElementById(elementId);
        el.className = 'stripe-status-indicator ' + (enabled ? 'enabled' : 'disabled');
        el.title = enabled ? 'Enabled' : 'Disabled';
    }

    async function loadStripeStats() {
        try {
            const response = await ERP.api.get('/stripe/dashboard');
            if (response.success) {
                document.getElementById('stripe-stats').style.display = 'grid';
                document.getElementById('stripe-total-amount').textContent =
                    '$' + (response.data.this_month.total || 0).toLocaleString();
                document.getElementById('stripe-transactions').textContent =
                    response.data.this_month.transactions || 0;
                document.getElementById('stripe-success-rate').textContent =
                    (response.data.this_month.success_rate || 0) + '%';
            }
        } catch (error) {
            // Stats failed to load, not critical
        }
    }

    async function connectStripe() {
        const mode = document.querySelector('input[name="stripe_mode"]:checked')?.value || 'connect_new';

        if (mode === 'manual') {
            document.getElementById('manual-keys-form').style.display = 'block';
            return;
        }

        try {
            ERP.toast.info('Redirecting to Stripe...');

            if (mode === 'connect_new') {
                // Create new Connect account
                const response = await ERP.api.post('/stripe/connect/onboarding');
                if (response.success && response.data.url) {
                    window.location.href = response.data.url;
                } else {
                    throw new Error(response.error || 'Failed to start onboarding');
                }
            } else if (mode === 'connect_existing') {
                // OAuth for existing accounts
                const response = await ERP.api.get('/stripe/oauth/url');
                if (response.success && response.data.url) {
                    window.location.href = response.data.url;
                } else {
                    throw new Error(response.error || 'Failed to get OAuth URL');
                }
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to connect Stripe');
        }
    }

    function cancelManualKeys() {
        document.getElementById('manual-keys-form').style.display = 'none';
        document.getElementById('stripe-manual-form').reset();
    }

    // Manual keys form submit
    document.getElementById('stripe-manual-form')?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const data = ERP.FormUtils.serialize(this);

        try {
            const response = await ERP.api.post('/stripe/manual-keys', data);
            if (response.success) {
                ERP.toast.success('Stripe API keys saved!');
                loadStripeStatus();
            } else {
                throw new Error(response.error || 'Failed to save keys');
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to save Stripe keys');
        }
    });

    async function syncStripe() {
        try {
            ERP.toast.info('Syncing...');
            const response = await ERP.api.post('/stripe/sync');
            if (response.success) {
                ERP.toast.success('Synced successfully');
                loadStripeStatus();
            } else {
                throw new Error(response.error || 'Sync failed');
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to sync');
        }
    }

    function viewStripeDashboard() {
        window.open('https://dashboard.stripe.com', '_blank');
    }

    async function disconnectStripe() {
        if (!confirm('Are you sure you want to disconnect Stripe? You will no longer be able to accept payments until you reconnect.')) {
            return;
        }

        try {
            const response = await ERP.api.post('/stripe/disconnect');
            if (response.success) {
                ERP.toast.success('Stripe disconnected');
                loadStripeStatus();
            } else {
                throw new Error(response.error || 'Failed to disconnect');
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to disconnect Stripe');
        }
    }

    async function resumeOnboarding() {
        try {
            ERP.toast.info('Redirecting to complete setup...');
            const response = await ERP.api.get('/stripe/connect/refresh-link');
            if (response.success && response.data.url) {
                window.location.href = response.data.url;
            } else {
                throw new Error(response.error || 'Failed to get onboarding link');
            }
        } catch (error) {
            ERP.toast.error(error.message || 'Failed to resume onboarding');
        }
    }
</script>

<style>
    .settings-nav {
        display: flex;
        flex-direction: column;
    }

    .settings-nav-item {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        padding: var(--space-3) var(--space-4);
        color: var(--text-secondary);
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: all var(--transition-fast);
    }

    .settings-nav-item:hover {
        background: var(--bg-secondary);
    }

    .settings-nav-item.active {
        color: var(--primary-600);
        background: var(--primary-50);
        border-left-color: var(--primary-500);
    }

    .radio-label {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        cursor: pointer;
    }

    .card-footer {
        padding: var(--space-4) var(--space-6);
        border-top: 1px solid var(--border-color);
    }

    .bg-secondary {
        background: var(--bg-secondary);
    }

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: var(--space-4);
        max-height: 400px;
        overflow-y: auto;
    }

    .permission-module {
        background: var(--bg-secondary);
        padding: var(--space-3);
        border-radius: var(--radius-md);
    }

    .permission-module-title {
        font-weight: 600;
        margin-bottom: var(--space-2);
        color: var(--text-primary);
    }

    .permission-item {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-1) 0;
        font-size: 0.875rem;
        cursor: pointer;
    }

    .permission-item:hover {
        color: var(--primary-600);
    }

    /* Stripe Integration Styles */
    .stripe-options {
        display: flex;
        flex-direction: column;
        gap: var(--space-3);
        text-align: left;
    }

    .stripe-option {
        display: flex;
        align-items: flex-start;
        gap: var(--space-3);
        padding: var(--space-4);
        border: 2px solid var(--border-color);
        border-radius: var(--radius-lg);
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .stripe-option:hover {
        border-color: var(--primary-400);
        background: var(--bg-secondary);
    }

    .stripe-option input[type="radio"] {
        margin-top: 3px;
    }

    .stripe-option input[type="radio"]:checked+.stripe-option-content {
        color: var(--primary-600);
    }

    .stripe-option:has(input:checked) {
        border-color: var(--primary-500);
        background: var(--primary-50);
    }

    .stripe-option-content {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .stripe-option-content small {
        color: var(--text-muted);
    }

    .stripe-status-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stripe-status-icon.success {
        background: var(--success-100);
        color: var(--success-600);
    }

    .stripe-status-card {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-3);
        background: var(--bg-secondary);
        border-radius: var(--radius-md);
    }

    .stripe-status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    .stripe-status-indicator.enabled {
        background: var(--success-500);
        box-shadow: 0 0 0 3px var(--success-100);
    }

    .stripe-status-indicator.disabled {
        background: var(--gray-400);
    }

    .stat-card {
        background: var(--bg-secondary);
        padding: var(--space-4);
        border-radius: var(--radius-md);
        text-align: center;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-top: var(--space-1);
    }

    .alert-warning {
        background: var(--warning-50);
        border: 1px solid var(--warning-200);
        border-radius: var(--radius-md);
        padding: var(--space-4);
        color: var(--warning-800);
    }

    .btn-warning {
        background: var(--warning-500);
        color: white;
    }

    .btn-warning:hover {
        background: var(--warning-600);
    }

    .nav-badge {
        font-size: 0.65rem;
        background: var(--primary-100);
        color: var(--primary-700);
        padding: 2px 6px;
        border-radius: var(--radius-full);
        margin-left: auto;
        font-weight: 600;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
?>