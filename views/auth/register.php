<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register for Construction ERP">
    <title>Register | Construction ERP</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/main.css">

    <style>
        .register-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-900) 100%);
            padding: var(--space-4);
        }

        .register-card {
            width: 100%;
            max-width: 480px;
            background: var(--bg-primary);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, var(--secondary-500), var(--primary-500));
            padding: var(--space-8) var(--space-6);
            text-align: center;
            color: white;
        }

        .register-logo {
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

        .register-title {
            font-size: var(--text-2xl);
            font-weight: 700;
            margin-bottom: var(--space-2);
        }

        .register-subtitle {
            opacity: 0.9;
            font-size: var(--text-sm);
        }

        .register-body {
            padding: var(--space-6);
        }

        .register-form .form-group {
            margin-bottom: var(--space-4);
        }

        .register-form .btn {
            width: 100%;
            padding: var(--space-3);
            margin-top: var(--space-2);
        }

        .register-footer {
            text-align: center;
            padding: var(--space-4) var(--space-6);
            border-top: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .register-footer a {
            color: var(--primary-600);
            font-weight: 500;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
        }

        .subdomain-input {
            display: flex;
            align-items: center;
            gap: 0;
        }

        .subdomain-input input {
            border-radius: var(--radius-lg) 0 0 var(--radius-lg);
            border-right: none;
        }

        .subdomain-suffix {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-left: none;
            padding: var(--space-2) var(--space-3);
            border-radius: 0 var(--radius-lg) var(--radius-lg) 0;
            color: var(--text-secondary);
            font-size: var(--text-sm);
            white-space: nowrap;
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

        .hidden {
            display: none !important;
        }
    </style>
</head>

<body>
    <div class="register-page">
        <div class="register-card">
            <div class="register-header">
                <div class="register-logo">C</div>
                <h1 class="register-title">Create Your Account</h1>
                <p class="register-subtitle">Start managing your construction projects today</p>
            </div>

            <div class="register-body">
                <div id="register-alert" class="alert hidden"></div>

                <form id="register-form" class="register-form">
                    <div class="form-group">
                        <label class="form-label" for="company_name">Company Name</label>
                        <input type="text" class="form-input" id="company_name" name="company_name"
                            placeholder="Acme Construction Co." required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="subdomain">Company ID (URL Slug)</label>
                        <input type="text" class="form-input" id="subdomain" name="subdomain" placeholder="acme"
                            required pattern="[a-z0-9]+" minlength="3" maxlength="30">
                        <small style="color: var(--text-tertiary); font-size: 12px;">
                            Only lowercase letters and numbers. Your URL will be: /t/<strong
                                id="slug-preview">acme</strong>/dashboard
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name</label>
                            <input type="text" class="form-input" id="first_name" name="first_name" placeholder="John"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input type="text" class="form-input" id="last_name" name="last_name" placeholder="Smith"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" class="form-input" id="email" name="email" placeholder="john@company.com"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-input" id="password" name="password" placeholder="••••••••"
                            required minlength="8">
                        <small style="color: var(--text-tertiary); font-size: 12px;">
                            Minimum 8 characters
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirm">Confirm Password</label>
                        <input type="password" class="form-input" id="password_confirm" name="password_confirm"
                            placeholder="••••••••" required minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" id="register-btn">
                        Create Account
                    </button>
                </form>
            </div>

            <div class="register-footer">
                Already have an account? <a href="/login">Sign in</a>
            </div>
        </div>
    </div>

    <script>
        // Update slug preview as user types
        document.getElementById('subdomain').addEventListener('input', function (e) {
            this.value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '');
            const preview = document.getElementById('slug-preview');
            if (preview) {
                preview.textContent = this.value || 'acme';
            }
        });

        // Form submission
        document.getElementById('register-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            console.log('Form submitted'); // Debug log

            const btn = document.getElementById('register-btn');
            const alertEl = document.getElementById('register-alert');

            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;

            // Validate passwords match
            if (password !== passwordConfirm) {
                alertEl.textContent = 'Passwords do not match.';
                alertEl.classList.remove('hidden', 'alert-success');
                alertEl.classList.add('alert-error');
                return;
            }

            const formData = {
                company_name: document.getElementById('company_name').value,
                subdomain: document.getElementById('subdomain').value,
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                email: document.getElementById('email').value,
                password: password
            };

            console.log('Form data:', formData); // Debug log

            btn.disabled = true;
            btn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">↻</span> Creating account...';
            alertEl.classList.add('hidden');

            try {
                const response = await fetch('/api/auth/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                console.log('Response:', data); // Debug log

                if (data.success) {
                    // Show success message
                    alertEl.textContent = 'Account created! Redirecting to your dashboard...';
                    alertEl.classList.remove('hidden', 'alert-error');
                    alertEl.classList.add('alert-success');

                    // Save token
                    if (data.data && data.data.token) {
                        localStorage.setItem('erp_token', data.data.token);
                    }

                    // Redirect to tenant dashboard (path-based URL)
                    const slug = data.data.tenant.subdomain;

                    setTimeout(() => {
                        window.location.href = '/t/' + slug + '/dashboard';
                    }, 1500);
                } else {
                    throw new Error(data.error || data.message || 'Registration failed');
                }
            } catch (error) {
                console.error('Error:', error); // Debug log
                alertEl.textContent = error.message || 'Registration failed. Please try again.';
                alertEl.classList.remove('hidden', 'alert-success');
                alertEl.classList.add('alert-error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Create Account';
            }
        });
    </script>
    <style>
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
</body>

</html>