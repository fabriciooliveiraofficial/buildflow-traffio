<?php
/**
 * Subscription Settings Page
 * Allows tenants to view and manage their subscription
 */
?>
<?php require_once VIEWS_PATH . '/layouts/header.php'; ?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">Subscription</h1>
        <p class="page-subtitle">Manage your plan and billing</p>
    </div>
</div>

<div class="content-wrapper">
    <!-- Loading State -->
    <div id="loading-state" class="text-center py-8">
        <div class="spinner-lg"></div>
        <p class="text-muted mt-4">Loading subscription details...</p>
    </div>

    <!-- Main Content (hidden until loaded) -->
    <div id="subscription-content" style="display: none;">

        <!-- Current Plan Card -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        class="mr-2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    Current Plan
                </h3>
            </div>
            <div class="card-body">
                <div class="subscription-overview">
                    <div class="plan-info">
                        <div class="plan-badge" id="plan-badge">
                            <span class="plan-name" id="plan-name">Loading...</span>
                        </div>
                        <div class="plan-price">
                            <span class="price-amount" id="plan-price">$0</span>
                            <span class="price-period">/month</span>
                        </div>
                    </div>
                    <div class="plan-status">
                        <span class="status-badge" id="status-badge">Active</span>
                        <p class="status-text" id="status-text"></p>
                    </div>
                </div>

                <!-- Trial Notice -->
                <div class="alert alert-info" id="trial-alert" style="display: none;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <div>
                        <strong>Free Trial</strong>
                        <p id="trial-text">Your trial ends in X days.</p>
                    </div>
                </div>

                <!-- Cancellation Notice -->
                <div class="alert alert-warning" id="cancel-alert" style="display: none;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                        </path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <div>
                        <strong>Subscription Canceled</strong>
                        <p id="cancel-text">Your subscription will end on X.</p>
                    </div>
                </div>

                <!-- Past Due Notice -->
                <div class="alert alert-danger" id="pastdue-alert" style="display: none;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <div>
                        <strong>Payment Failed</strong>
                        <p>Please update your payment method to avoid service interruption.</p>
                        <button class="btn btn-sm btn-danger mt-2" onclick="openBillingPortal()">Update Payment
                            Method</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Card -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        class="mr-2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    User Seats
                </h3>
            </div>
            <div class="card-body">
                <div class="usage-stats">
                    <div class="usage-circle">
                        <svg viewBox="0 0 36 36" class="circular-chart">
                            <path class="circle-bg"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831">
                            </path>
                            <path class="circle-progress" id="usage-circle-progress" stroke-dasharray="0, 100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831">
                            </path>
                            <text x="18" y="20.35" class="percentage" id="usage-percentage">0%</text>
                        </svg>
                    </div>
                    <div class="usage-details">
                        <div class="usage-numbers">
                            <span class="usage-used" id="usage-used">0</span>
                            <span class="usage-separator">/</span>
                            <span class="usage-limit" id="usage-limit">0</span>
                            <span class="usage-label">users</span>
                        </div>
                        <p class="usage-text" id="usage-text">You have X seats available.</p>
                        <a href="/users" class="btn btn-sm btn-outline mt-3">Manage Users</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upgrade Options -->
        <div class="card mb-6" id="upgrade-section">
            <div class="card-header">
                <h3 class="card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        class="mr-2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                    Upgrade Your Plan
                </h3>
            </div>
            <div class="card-body">
                <div class="upgrade-grid" id="upgrade-grid">
                    <!-- Upgrade options loaded dynamically -->
                </div>
            </div>
        </div>

        <!-- Billing Actions -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        class="mr-2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    Billing & Invoices
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Manage your payment method, view invoices, and update billing details through
                    the Stripe Customer Portal.</p>
                <button class="btn btn-primary" onclick="openBillingPortal()" id="portal-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        class="mr-2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    Open Billing Portal
                </button>
            </div>
        </div>

        <!-- Cancel Subscription -->
        <div class="card" id="cancel-section">
            <div class="card-header">
                <h3 class="card-title text-danger">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        class="mr-2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Cancel Subscription
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">If you cancel, you'll still have access until the end of your billing period.
                    Your data will be retained for 30 days after cancellation.</p>
                <button class="btn btn-outline-danger" onclick="showCancelModal()">
                    Cancel Subscription
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal-overlay" id="cancel-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Cancel Subscription?</h3>
            <button class="modal-close" onclick="closeCancelModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to cancel your subscription?</p>
            <ul class="cancel-info-list">
                <li>✓ You'll keep access until the end of your billing period</li>
                <li>✓ Your data will be saved for 30 days</li>
                <li>✓ You can reactivate anytime</li>
            </ul>
            <div class="form-group mt-4">
                <label class="form-label">Why are you canceling? (optional)</label>
                <select class="form-control" id="cancel-reason">
                    <option value="">Select a reason...</option>
                    <option value="too_expensive">Too expensive</option>
                    <option value="missing_features">Missing features I need</option>
                    <option value="switching">Switching to another solution</option>
                    <option value="not_using">Not using it enough</option>
                    <option value="temporary">Temporary pause</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCancelModal()">Keep Subscription</button>
            <button class="btn btn-danger" onclick="confirmCancel()" id="confirm-cancel-btn">Cancel
                Subscription</button>
        </div>
    </div>
</div>

<!-- Upgrade Confirmation Modal -->
<div class="modal-overlay" id="upgrade-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Upgrade Plan</h3>
            <button class="modal-close" onclick="closeUpgradeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="upgrade-comparison">
                <div class="upgrade-from">
                    <span class="label">Current Plan</span>
                    <span class="plan" id="upgrade-from-plan">Team</span>
                    <span class="price" id="upgrade-from-price">$60/mo</span>
                </div>
                <div class="upgrade-arrow">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </div>
                <div class="upgrade-to">
                    <span class="label">New Plan</span>
                    <span class="plan" id="upgrade-to-plan">Business</span>
                    <span class="price" id="upgrade-to-price">$90/mo</span>
                </div>
            </div>
            <div class="upgrade-details mt-4">
                <p class="text-muted">You'll be charged the prorated difference for this billing cycle.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeUpgradeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="confirmUpgrade()" id="confirm-upgrade-btn">
                Confirm Upgrade
            </button>
        </div>
    </div>
</div>

<style>
    .subscription-overview {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--space-4);
    }

    .plan-info {
        display: flex;
        align-items: center;
        gap: var(--space-6);
    }

    .plan-badge {
        background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
        color: white;
        padding: var(--space-2) var(--space-4);
        border-radius: var(--radius-lg);
        font-weight: 600;
    }

    .plan-badge.business {
        background: linear-gradient(135deg, #7C3AED, #5B21B6);
    }

    .plan-badge.professional {
        background: linear-gradient(135deg, #F59E0B, #D97706);
    }

    .plan-price {
        font-size: var(--text-2xl);
    }

    .price-amount {
        font-weight: 700;
        color: var(--text-primary);
    }

    .price-period {
        color: var(--text-muted);
        font-size: var(--text-base);
    }

    .plan-status {
        text-align: right;
    }

    .status-badge {
        display: inline-block;
        padding: var(--space-1) var(--space-3);
        border-radius: var(--radius-full);
        font-size: var(--text-sm);
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
        background: var(--warning-100);
        color: var(--warning-700);
    }

    .status-text {
        color: var(--text-muted);
        font-size: var(--text-sm);
        margin-top: var(--space-1);
    }

    .alert {
        display: flex;
        gap: var(--space-3);
        padding: var(--space-4);
        border-radius: var(--radius-lg);
        margin-top: var(--space-4);
    }

    .alert svg {
        flex-shrink: 0;
        margin-top: 2px;
    }

    .alert-info {
        background: var(--info-50);
        border: 1px solid var(--info-200);
        color: var(--info-800);
    }

    .alert-warning {
        background: var(--warning-50);
        border: 1px solid var(--warning-200);
        color: var(--warning-800);
    }

    .alert-danger {
        background: var(--error-50);
        border: 1px solid var(--error-200);
        color: var(--error-800);
    }

    .alert strong {
        display: block;
        margin-bottom: var(--space-1);
    }

    .alert p {
        margin: 0;
        font-size: var(--text-sm);
    }

    /* Usage Stats */
    .usage-stats {
        display: flex;
        align-items: center;
        gap: var(--space-8);
    }

    .usage-circle {
        width: 120px;
        height: 120px;
        flex-shrink: 0;
    }

    .circular-chart {
        width: 100%;
        height: 100%;
    }

    .circle-bg {
        fill: none;
        stroke: var(--border-color);
        stroke-width: 2;
    }

    .circle-progress {
        fill: none;
        stroke: var(--primary-500);
        stroke-width: 2;
        stroke-linecap: round;
        transform: rotate(-90deg);
        transform-origin: center;
        transition: stroke-dasharray 0.5s ease;
    }

    .percentage {
        fill: var(--text-primary);
        font-size: 0.5em;
        font-weight: 600;
        text-anchor: middle;
    }

    .usage-details {
        flex: 1;
    }

    .usage-numbers {
        font-size: var(--text-2xl);
        font-weight: 700;
        margin-bottom: var(--space-2);
    }

    .usage-used {
        color: var(--primary-600);
    }

    .usage-separator {
        color: var(--text-muted);
        margin: 0 var(--space-1);
    }

    .usage-limit {
        color: var(--text-secondary);
    }

    .usage-label {
        font-size: var(--text-base);
        font-weight: 400;
        color: var(--text-muted);
        margin-left: var(--space-2);
    }

    .usage-text {
        color: var(--text-secondary);
        margin: 0;
    }

    /* Upgrade Grid */
    .upgrade-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: var(--space-4);
    }

    .upgrade-card {
        border: 2px solid var(--border-color);
        border-radius: var(--radius-xl);
        padding: var(--space-6);
        transition: all 0.2s;
    }

    .upgrade-card:hover {
        border-color: var(--primary-300);
        box-shadow: var(--shadow-md);
    }

    .upgrade-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: var(--space-4);
    }

    .upgrade-card-name {
        font-size: var(--text-lg);
        font-weight: 600;
    }

    .upgrade-card-price {
        text-align: right;
    }

    .upgrade-card-price .amount {
        font-size: var(--text-2xl);
        font-weight: 700;
    }

    .upgrade-card-price .period {
        font-size: var(--text-sm);
        color: var(--text-muted);
    }

    .upgrade-card-features {
        list-style: none;
        padding: 0;
        margin: 0 0 var(--space-4) 0;
    }

    .upgrade-card-features li {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-2) 0;
        font-size: var(--text-sm);
        color: var(--text-secondary);
    }

    .upgrade-card-features li svg {
        color: var(--success-500);
        flex-shrink: 0;
    }

    .upgrade-card .btn {
        width: 100%;
    }

    /* Modal Styles */
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
        background: var(--bg-primary);
        border-radius: var(--radius-xl);
        max-width: 480px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-4) var(--space-6);
        border-bottom: 1px solid var(--border-color);
    }

    .modal-title {
        font-size: var(--text-lg);
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
        padding: var(--space-6);
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: var(--space-3);
        padding: var(--space-4) var(--space-6);
        border-top: 1px solid var(--border-color);
    }

    .cancel-info-list {
        list-style: none;
        padding: 0;
        margin: var(--space-4) 0;
    }

    .cancel-info-list li {
        padding: var(--space-2) 0;
        color: var(--text-secondary);
    }

    /* Upgrade Comparison */
    .upgrade-comparison {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-4);
        padding: var(--space-4);
        background: var(--bg-secondary);
        border-radius: var(--radius-lg);
    }

    .upgrade-from,
    .upgrade-to {
        text-align: center;
    }

    .upgrade-from .label,
    .upgrade-to .label {
        display: block;
        font-size: var(--text-xs);
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: var(--space-1);
    }

    .upgrade-from .plan,
    .upgrade-to .plan {
        display: block;
        font-size: var(--text-lg);
        font-weight: 600;
    }

    .upgrade-from .price,
    .upgrade-to .price {
        display: block;
        color: var(--text-secondary);
    }

    .upgrade-to .plan {
        color: var(--primary-600);
    }

    .upgrade-arrow {
        color: var(--text-muted);
    }

    .text-danger {
        color: var(--error-600) !important;
    }

    .btn-outline-danger {
        border: 1px solid var(--error-500);
        color: var(--error-600);
        background: transparent;
    }

    .btn-outline-danger:hover {
        background: var(--error-50);
    }

    .spinner-lg {
        width: 48px;
        height: 48px;
        border: 4px solid var(--border-color);
        border-top-color: var(--primary-500);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .py-8 {
        padding-top: var(--space-8);
        padding-bottom: var(--space-8);
    }

    .mb-6 {
        margin-bottom: var(--space-6);
    }

    .mt-4 {
        margin-top: var(--space-4);
    }

    .mt-3 {
        margin-top: var(--space-3);
    }

    .mt-2 {
        margin-top: var(--space-2);
    }

    .mr-2 {
        margin-right: var(--space-2);
    }

    @media (max-width: 768px) {
        .subscription-overview {
            flex-direction: column;
            align-items: flex-start;
        }

        .plan-status {
            text-align: left;
        }

        .usage-stats {
            flex-direction: column;
            text-align: center;
        }

        .usage-details {
            text-align: center;
        }
    }
</style>

<script>
    let currentSubscription = null;
    let selectedUpgradePlan = null;

    // Load subscription data
    async function loadSubscription() {
        try {
            const response = await ERP.api.get('/my-subscription');

            if (response.success) {
                currentSubscription = response.data.subscription;
                renderSubscription(currentSubscription);
                renderUpgradeOptions(response.data.upgrade_options);

                document.getElementById('loading-state').style.display = 'none';
                document.getElementById('subscription-content').style.display = 'block';
            } else {
                ERP.toast.error(response.error || 'Failed to load subscription');
            }
        } catch (error) {
            console.error('Error loading subscription:', error);
            ERP.toast.error('Failed to load subscription');
        }
    }

    function renderSubscription(sub) {
        // Plan name and badge
        document.getElementById('plan-name').textContent = sub.plan.name;
        const badge = document.getElementById('plan-badge');
        badge.className = 'plan-badge ' + sub.plan.slug;

        // Price
        document.getElementById('plan-price').textContent = '$' + sub.plan.price;

        // Status
        const statusBadge = document.getElementById('status-badge');
        const statusMap = {
            'active': { text: 'Active', class: 'active' },
            'trialing': { text: 'Trial', class: 'trialing' },
            'past_due': { text: 'Past Due', class: 'past_due' },
            'canceled': { text: 'Canceled', class: 'canceled' }
        };
        const status = statusMap[sub.status] || { text: sub.status, class: '' };
        statusBadge.textContent = status.text;
        statusBadge.className = 'status-badge ' + status.class;

        // Status text
        if (sub.started_at) {
            document.getElementById('status-text').textContent =
                'Member since ' + new Date(sub.started_at).toLocaleDateString();
        }

        // Trial alert
        if (sub.is_trialing && sub.trial_ends_at) {
            const trialEnd = new Date(sub.trial_ends_at);
            const daysLeft = Math.ceil((trialEnd - new Date()) / (1000 * 60 * 60 * 24));
            document.getElementById('trial-text').textContent =
                `Your free trial ends in ${daysLeft} day${daysLeft !== 1 ? 's' : ''}.`;
            document.getElementById('trial-alert').style.display = 'flex';
        }

        // Cancellation alert
        if (sub.status === 'canceled' && sub.ends_at) {
            const endDate = new Date(sub.ends_at).toLocaleDateString();
            document.getElementById('cancel-text').textContent =
                `Your subscription will end on ${endDate}. You can reactivate anytime before then.`;
            document.getElementById('cancel-alert').style.display = 'flex';
            document.getElementById('cancel-section').style.display = 'none';
        }

        // Past due alert
        if (sub.status === 'past_due') {
            document.getElementById('pastdue-alert').style.display = 'flex';
        }

        // Usage
        renderUsage(sub.usage);
    }

    function renderUsage(usage) {
        const percentage = usage.percentage || 0;

        document.getElementById('usage-used').textContent = usage.used;
        document.getElementById('usage-limit').textContent = usage.limit;
        document.getElementById('usage-percentage').textContent = percentage + '%';
        document.getElementById('usage-circle-progress').setAttribute('stroke-dasharray', `${percentage}, 100`);

        if (usage.available > 0) {
            document.getElementById('usage-text').textContent =
                `You have ${usage.available} seat${usage.available !== 1 ? 's' : ''} available.`;
        } else {
            document.getElementById('usage-text').textContent =
                'All seats are in use. Upgrade for more capacity.';
        }
    }

    function renderUpgradeOptions(plans) {
        const container = document.getElementById('upgrade-grid');

        if (!plans || plans.length === 0) {
            document.getElementById('upgrade-section').style.display = 'none';
            return;
        }

        const features = {
            'business': [
                'Up to 5 team members',
                'Payroll management',
                'Inventory tracking',
                'Advanced analytics'
            ],
            'professional': [
                'Up to 10 team members',
                'Advanced reporting',
                'Custom branding',
                'Priority support'
            ]
        };

        container.innerHTML = plans.map(plan => `
            <div class="upgrade-card">
                <div class="upgrade-card-header">
                    <div class="upgrade-card-name">${plan.name}</div>
                    <div class="upgrade-card-price">
                        <span class="amount">$${plan.price_monthly}</span>
                        <span class="period">/month</span>
                    </div>
                </div>
                <ul class="upgrade-card-features">
                    ${(features[plan.slug] || []).map(f => `
                        <li>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            ${f}
                        </li>
                    `).join('')}
                </ul>
                <button class="btn btn-primary" onclick="showUpgradeModal('${plan.slug}', '${plan.name}', ${plan.price_monthly})">
                    Upgrade to ${plan.name}
                </button>
            </div>
        `).join('');
    }

    // Billing Portal
    async function openBillingPortal() {
        const btn = document.getElementById('portal-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Opening...';

        try {
            const response = await ERP.api.post('/my-subscription/portal');

            if (response.success && response.data.url) {
                window.open(response.data.url, '_blank');
            } else {
                ERP.toast.error('Could not open billing portal');
            }
        } catch (error) {
            ERP.toast.error('Failed to open billing portal');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                Open Billing Portal
            `;
        }
    }

    // Upgrade Modal
    function showUpgradeModal(slug, name, price) {
        selectedUpgradePlan = slug;

        document.getElementById('upgrade-from-plan').textContent = currentSubscription.plan.name;
        document.getElementById('upgrade-from-price').textContent = '$' + currentSubscription.plan.price + '/mo';
        document.getElementById('upgrade-to-plan').textContent = name;
        document.getElementById('upgrade-to-price').textContent = '$' + price + '/mo';

        document.getElementById('upgrade-modal').classList.add('active');
    }

    function closeUpgradeModal() {
        document.getElementById('upgrade-modal').classList.remove('active');
        selectedUpgradePlan = null;
    }

    async function confirmUpgrade() {
        if (!selectedUpgradePlan) return;

        const btn = document.getElementById('confirm-upgrade-btn');
        btn.disabled = true;
        btn.textContent = 'Upgrading...';

        try {
            const response = await ERP.api.post('/my-subscription/upgrade', {
                plan_slug: selectedUpgradePlan
            });

            if (response.success) {
                ERP.toast.success('Plan upgraded successfully!');
                closeUpgradeModal();
                loadSubscription(); // Reload data
            } else {
                ERP.toast.error(response.error || 'Failed to upgrade');
            }
        } catch (error) {
            ERP.toast.error('Failed to upgrade plan');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Confirm Upgrade';
        }
    }

    // Cancel Modal
    function showCancelModal() {
        document.getElementById('cancel-modal').classList.add('active');
    }

    function closeCancelModal() {
        document.getElementById('cancel-modal').classList.remove('active');
    }

    async function confirmCancel() {
        const btn = document.getElementById('confirm-cancel-btn');
        btn.disabled = true;
        btn.textContent = 'Canceling...';

        try {
            const response = await ERP.api.post('/my-subscription/cancel', {
                reason: document.getElementById('cancel-reason').value
            });

            if (response.success) {
                ERP.toast.success(response.message || 'Subscription canceled');
                closeCancelModal();
                loadSubscription(); // Reload data
            } else {
                ERP.toast.error(response.error || 'Failed to cancel');
            }
        } catch (error) {
            ERP.toast.error('Failed to cancel subscription');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Cancel Subscription';
        }
    }

    // Close modals on overlay click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // Load on page ready
    document.addEventListener('DOMContentLoaded', loadSubscription);
</script>

<?php require_once VIEWS_PATH . '/layouts/footer.php'; ?>
