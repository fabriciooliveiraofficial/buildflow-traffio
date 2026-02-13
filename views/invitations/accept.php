<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Accept your invitation to join the team">
    <title>Accept Invitation | Construction ERP</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/main.css">

    <style>
        .invite-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-900) 100%);
            padding: var(--space-4);
        }

        .invite-card {
            width: 100%;
            max-width: 480px;
            background: var(--bg-primary);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
        }

        .invite-header {
            background: linear-gradient(135deg, var(--primary-500), var(--secondary-500));
            padding: var(--space-8) var(--space-6);
            text-align: center;
            color: white;
        }

        .invite-logo {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-4);
            font-size: var(--text-2xl);
            font-weight: 700;
        }

        .invite-title {
            font-size: var(--text-2xl);
            font-weight: 700;
            margin-bottom: var(--space-2);
        }

        .invite-subtitle {
            opacity: 0.9;
            font-size: var(--text-sm);
        }

        .invite-body {
            padding: var(--space-6);
        }

        .invite-info {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            margin-bottom: var(--space-5);
        }

        .invite-info-row {
            display: flex;
            justify-content: space-between;
            padding: var(--space-2) 0;
            font-size: var(--text-sm);
            border-bottom: 1px solid var(--border-color);
        }

        .invite-info-row:last-child {
            border-bottom: none;
        }

        .invite-info-label {
            color: var(--text-tertiary);
        }

        .invite-info-value {
            font-weight: 500;
            color: var(--text-primary);
        }

        .invite-form .form-group {
            margin-bottom: var(--space-4);
        }

        .invite-form .form-group-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
        }

        .invite-form .btn {
            width: 100%;
            padding: var(--space-3);
            margin-top: var(--space-2);
        }

        .password-strength {
            margin-top: var(--space-2);
            font-size: var(--text-xs);
        }

        .password-strength-bar {
            height: 4px;
            background: var(--border-color);
            border-radius: var(--radius-full);
            margin-top: var(--space-1);
            overflow: hidden;
        }

        .password-strength-fill {
            height: 100%;
            border-radius: var(--radius-full);
            transition: width 0.3s, background 0.3s;
        }

        .strength-weak {
            background: var(--error-500);
            width: 33%;
        }

        .strength-medium {
            background: var(--warning-500);
            width: 66%;
        }

        .strength-strong {
            background: var(--success-500);
            width: 100%;
        }

        .invite-footer {
            text-align: center;
            padding: var(--space-4) var(--space-6);
            border-top: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .invite-footer a {
            color: var(--primary-600);
            font-weight: 500;
        }

        .alert {
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-4);
            font-size: var(--text-sm);
        }

        .alert-error {
            background: var(--error-50);
            color: var(--error-700);
            border: 1px solid var(--error-500);
        }

        .alert-success {
            background: var(--success-50);
            color: var(--success-700);
            border: 1px solid var(--success-500);
        }

        .loading-state {
            text-align: center;
            padding: var(--space-8);
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-color);
            border-top-color: var(--primary-500);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto var(--space-4);
        }

        .error-state {
            text-align: center;
            padding: var(--space-8);
        }

        .error-icon {
            width: 64px;
            height: 64px;
            background: var(--error-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-4);
            color: var(--error-600);
            font-size: var(--text-2xl);
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="invite-page">
        <div class="invite-card">
            <div class="invite-header">
                <div class="invite-logo">🎉</div>
                <h1 class="invite-title" id="header-title">You're Invited!</h1>
                <p class="invite-subtitle" id="header-subtitle">Complete your account setup</p>
            </div>

            <!-- Loading State -->
            <div id="loading-state" class="invite-body">
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Validating your invitation...</p>
                </div>
            </div>

            <!-- Error State -->
            <div id="error-state" class="invite-body hidden">
                <div class="error-state">
                    <div class="error-icon">✕</div>
                    <h3 id="error-title">Invalid Invitation</h3>
                    <p id="error-message" style="color: var(--text-tertiary); margin: var(--space-4) 0;">
                        This invitation link is no longer valid.
                    </p>
                    <a href="/login" class="btn btn-primary">Go to Login</a>
                </div>
            </div>

            <!-- Success State (Form) -->
            <div id="form-state" class="invite-body hidden">
                <div class="invite-info">
                    <div class="invite-info-row">
                        <span class="invite-info-label">Organization</span>
                        <span class="invite-info-value" id="info-tenant">-</span>
                    </div>
                    <div class="invite-info-row">
                        <span class="invite-info-label">Email</span>
                        <span class="invite-info-value" id="info-email">-</span>
                    </div>
                    <div class="invite-info-row">
                        <span class="invite-info-label">Role</span>
                        <span class="invite-info-value" id="info-role">-</span>
                    </div>
                </div>

                <div id="form-alert" class="alert alert-error hidden"></div>

                <form id="accept-form" class="invite-form">
                    <div class="form-group-row">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name</label>
                            <input type="text" class="form-input" id="first_name" name="first_name" placeholder="John"
                                required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input type="text" class="form-input" id="last_name" name="last_name" placeholder="Doe"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Create Password</label>
                        <input type="password" class="form-input" id="password" name="password"
                            placeholder="Minimum 8 characters" required minlength="8">
                        <div class="password-strength">
                            <span id="password-strength-text">Password strength</span>
                            <div class="password-strength-bar">
                                <div class="password-strength-fill" id="password-strength-fill"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirm">Confirm Password</label>
                        <input type="password" class="form-input" id="password_confirm" name="password_confirm"
                            placeholder="Re-enter your password" required minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                        Create My Account
                    </button>
                </form>
            </div>

            <!-- Accepted State -->
            <div id="success-state" class="invite-body hidden">
                <div class="loading-state">
                    <div class="error-icon" style="background: var(--success-100); color: var(--success-600);">✓</div>
                    <h3>Account Created!</h3>
                    <p style="color: var(--text-tertiary); margin: var(--space-4) 0;">
                        Your account has been set up successfully. Redirecting to login...
                    </p>
                    <a href="/login" class="btn btn-primary">Go to Login</a>
                </div>
            </div>

            <div class="invite-footer">
                Already have an account? <a href="/login">Sign in</a>
            </div>
        </div>
    </div>

    <script>
        const token = '<?= htmlspecialchars($GLOBALS['invitation_token'] ?? '') ?>';

        // State elements
        const loadingState = document.getElementById('loading-state');
        const errorState = document.getElementById('error-state');
        const formState = document.getElementById('form-state');
        const successState = document.getElementById('success-state');

        // Info elements
        const infoTenant = document.getElementById('info-tenant');
        const infoEmail = document.getElementById('info-email');
        const infoRole = document.getElementById('info-role');

        // Error elements
        const errorTitle = document.getElementById('error-title');
        const errorMessage = document.getElementById('error-message');

        // Form elements
        const form = document.getElementById('accept-form');
        const firstNameInput = document.getElementById('first_name');
        const lastNameInput = document.getElementById('last_name');
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('password_confirm');
        const formAlert = document.getElementById('form-alert');
        const submitBtn = document.getElementById('submit-btn');
        const passwordStrengthFill = document.getElementById('password-strength-fill');
        const passwordStrengthText = document.getElementById('password-strength-text');

        // Show state helper
        function showState(state) {
            loadingState.classList.add('hidden');
            errorState.classList.add('hidden');
            formState.classList.add('hidden');
            successState.classList.add('hidden');
            state.classList.remove('hidden');
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            return strength;
        }

        passwordInput.addEventListener('input', function () {
            const strength = checkPasswordStrength(this.value);
            passwordStrengthFill.className = 'password-strength-fill';

            if (this.value.length === 0) {
                passwordStrengthText.textContent = 'Password strength';
                passwordStrengthFill.style.width = '0';
            } else if (strength <= 1) {
                passwordStrengthFill.classList.add('strength-weak');
                passwordStrengthText.textContent = 'Weak password';
            } else if (strength <= 2) {
                passwordStrengthFill.classList.add('strength-medium');
                passwordStrengthText.textContent = 'Medium strength';
            } else {
                passwordStrengthFill.classList.add('strength-strong');
                passwordStrengthText.textContent = 'Strong password';
            }
        });

        // Validate invitation on page load
        async function validateInvitation() {
            if (!token) {
                errorTitle.textContent = 'Invalid Link';
                errorMessage.textContent = 'No invitation token provided.';
                showState(errorState);
                return;
            }

            try {
                const response = await fetch(`/api/invitations/validate/${token}`);
                const data = await response.json();

                if (data.success) {
                    // Populate form with invitation data
                    infoTenant.textContent = data.data.tenant_name || 'Organization';
                    infoEmail.textContent = data.data.email;
                    infoRole.textContent = data.data.role_name || 'Team Member';

                    if (data.data.first_name) {
                        firstNameInput.value = data.data.first_name;
                    }
                    if (data.data.last_name) {
                        lastNameInput.value = data.data.last_name;
                    }

                    showState(formState);
                } else {
                    errorTitle.textContent = 'Invalid Invitation';
                    errorMessage.textContent = data.error || data.message || 'This invitation is no longer valid.';
                    showState(errorState);
                }
            } catch (error) {
                console.error('Validation error:', error);
                errorTitle.textContent = 'Error';
                errorMessage.textContent = 'Unable to validate invitation. Please try again.';
                showState(errorState);
            }
        }

        // Handle form submission
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            formAlert.classList.add('hidden');

            const password = passwordInput.value;
            const passwordConfirm = passwordConfirmInput.value;

            // Validate passwords match
            if (password !== passwordConfirm) {
                formAlert.textContent = 'Passwords do not match.';
                formAlert.classList.remove('hidden');
                return;
            }

            // Validate password strength
            if (password.length < 8) {
                formAlert.textContent = 'Password must be at least 8 characters.';
                formAlert.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">↻</span> Creating account...';

            try {
                const response = await fetch(`/api/invitations/accept/${token}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        first_name: firstNameInput.value,
                        last_name: lastNameInput.value,
                        password: password
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showState(successState);

                    // Redirect to login after 2 seconds
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                } else {
                    formAlert.textContent = data.error || data.message || 'Failed to create account.';
                    formAlert.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Accept error:', error);
                formAlert.textContent = 'An error occurred. Please try again.';
                formAlert.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create My Account';
            }
        });

        // Start validation on page load
        validateInvitation();
    </script>
</body>

</html>