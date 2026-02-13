<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Your workspace is being created">
    <title>Setting Up Your Workspace - Buildflow</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.05) 0%, rgba(255, 152, 0, 0.05) 100%);
        }

        .checkout-container {
            max-width: 500px;
            margin: 0 auto;
            padding: var(--space-8);
            text-align: center;
        }

        .checkout-card {
            background: var(--bg-primary);
            border-radius: var(--radius-2xl);
            padding: var(--space-10);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-color);
        }

        .checkout-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-6);
        }

        .checkout-icon.loading {
            background: var(--primary-100);
            color: var(--primary-600);
        }

        .checkout-icon.success {
            background: var(--success-100);
            color: var(--success-600);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .checkout-title {
            font-size: var(--text-2xl);
            font-weight: 700;
            margin-bottom: var(--space-3);
        }

        .checkout-subtitle {
            color: var(--text-secondary);
            font-size: var(--text-base);
            margin-bottom: var(--space-6);
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--primary-100);
            border-top-color: var(--primary-500);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .progress-steps {
            text-align: left;
            margin: var(--space-6) 0;
        }

        .progress-step {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-3) 0;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .progress-step.active {
            color: var(--text-primary);
        }

        .progress-step.complete {
            color: var(--success-600);
        }

        .step-indicator {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid currentColor;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .progress-step.complete .step-indicator {
            background: var(--success-600);
            color: white;
            border-color: var(--success-600);
        }

        .progress-step.active .step-indicator {
            background: var(--primary-500);
            color: white;
            border-color: var(--primary-500);
        }

        .login-link {
            margin-top: var(--space-6);
        }

        .login-link a {
            color: var(--primary-500);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .tenant-info {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            margin-top: var(--space-6);
            text-align: left;
        }

        .tenant-info-label {
            font-size: var(--text-sm);
            color: var(--text-muted);
            margin-bottom: var(--space-1);
        }

        .tenant-info-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .error-message {
            background: var(--error-100);
            color: var(--error-700);
            padding: var(--space-4);
            border-radius: var(--radius-md);
            margin-top: var(--space-6);
        }
    </style>
</head>

<body>
    <div class="checkout-container">
        <div class="checkout-card">
            <!-- Loading State -->
            <div id="loading-state">
                <div class="checkout-icon loading">
                    <div class="spinner"></div>
                </div>
                <h1 class="checkout-title">Setting Up Your Workspace</h1>
                <p class="checkout-subtitle">Please wait while we configure your account...</p>

                <div class="progress-steps">
                    <div class="progress-step active" id="step-1">
                        <span class="step-indicator">1</span>
                        <span>Verifying payment...</span>
                    </div>
                    <div class="progress-step" id="step-2">
                        <span class="step-indicator">2</span>
                        <span>Creating workspace...</span>
                    </div>
                    <div class="progress-step" id="step-3">
                        <span class="step-indicator">3</span>
                        <span>Configuring your account...</span>
                    </div>
                </div>
            </div>

            <!-- Success State -->
            <div id="success-state" style="display: none;">
                <div class="checkout-icon success">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h1 class="checkout-title">Welcome to Buildflow! 🎉</h1>
                <p class="checkout-subtitle">Your workspace has been created successfully.</p>

                <div class="tenant-info" id="tenant-info" style="display: none;">
                    <div class="tenant-info-label">Your Workspace URL</div>
                    <div class="tenant-info-value" id="workspace-url"></div>
                </div>

                <p class="login-link">
                    Check your email for login credentials, or
                    <a href="/login" id="login-link">sign in now</a>
                </p>
            </div>

            <!-- Error State -->
            <div id="error-state" style="display: none;">
                <div class="checkout-icon loading">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h1 class="checkout-title">Something Went Wrong</h1>
                <p class="checkout-subtitle">We couldn't complete your setup.</p>
                <div class="error-message" id="error-message"></div>
                <p class="login-link">
                    <a href="/">Return to homepage</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        const sessionId = new URLSearchParams(window.location.search).get('session_id');

        async function checkStatus() {
            if (!sessionId) {
                showError('No session ID provided');
                return;
            }

            try {
                // Step 1: Verifying payment
                updateStep(1, 'active');

                const response = await fetch(`/api/checkout/verify?session_id=${encodeURIComponent(sessionId)}`);
                const result = await response.json();

                if (!result.success) {
                    showError(result.error || 'Failed to verify checkout');
                    return;
                }

                // Step 1 complete
                updateStep(1, 'complete');
                updateStep(2, 'active');

                // If tenant already exists, go to success
                if (result.data.tenant) {
                    updateStep(2, 'complete');
                    updateStep(3, 'active');

                    setTimeout(() => {
                        updateStep(3, 'complete');
                        showSuccess(result.data.tenant);
                    }, 1000);
                    return;
                }

                // Otherwise, poll for tenant creation
                pollForTenant(0);

            } catch (error) {
                showError(error.message);
            }
        }

        async function pollForTenant(attempt) {
            if (attempt > 10) {
                showError('Setup is taking longer than expected. Please check your email for login details.');
                return;
            }

            try {
                const response = await fetch(`/api/checkout/verify?session_id=${encodeURIComponent(sessionId)}`);
                const result = await response.json();

                if (result.data && result.data.tenant) {
                    updateStep(2, 'complete');
                    updateStep(3, 'active');

                    setTimeout(() => {
                        updateStep(3, 'complete');
                        showSuccess(result.data.tenant);
                    }, 1000);
                    return;
                }

                // Wait and try again
                setTimeout(() => pollForTenant(attempt + 1), 2000);

            } catch (error) {
                setTimeout(() => pollForTenant(attempt + 1), 2000);
            }
        }

        function updateStep(stepNum, state) {
            const step = document.getElementById(`step-${stepNum}`);
            step.classList.remove('active', 'complete');
            step.classList.add(state);

            if (state === 'complete') {
                step.querySelector('.step-indicator').innerHTML = '✓';
            }
        }

        function showSuccess(tenant) {
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('success-state').style.display = 'block';

            if (tenant && tenant.subdomain) {
                document.getElementById('tenant-info').style.display = 'block';
                const baseUrl = window.location.origin;
                document.getElementById('workspace-url').textContent = `${baseUrl}/t/${tenant.subdomain}/`;
            }
        }

        function showError(message) {
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('error-state').style.display = 'block';
            document.getElementById('error-message').textContent = message;
        }

        // Start checking
        checkStatus();
    </script>
</body>

</html>