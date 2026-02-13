<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to Construction ERP">
    <title>Login | Construction ERP</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%232196f3' width='100' height='100' rx='15'/><text x='50' y='65' text-anchor='middle' fill='white' font-size='50' font-weight='bold'>C</text></svg>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/main.css">

    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-900) 100%);
            padding: var(--space-4);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-primary);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-500), var(--secondary-500));
            padding: var(--space-8) var(--space-6);
            text-align: center;
            color: white;
        }

        .login-logo {
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

        .login-title {
            font-size: var(--text-2xl);
            font-weight: 700;
            margin-bottom: var(--space-2);
        }

        .login-subtitle {
            opacity: 0.9;
            font-size: var(--text-sm);
        }

        .login-body {
            padding: var(--space-6);
        }

        .login-form .form-group {
            margin-bottom: var(--space-5);
        }

        .login-form .btn {
            width: 100%;
            padding: var(--space-3);
            margin-top: var(--space-2);
        }

        .login-footer {
            text-align: center;
            padding: var(--space-4) var(--space-6);
            border-top: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .login-footer a {
            color: var(--primary-600);
            font-weight: 500;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            margin: var(--space-5) 0;
            color: var(--text-tertiary);
            font-size: var(--text-sm);
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-4);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            font-size: var(--text-sm);
            color: var(--text-secondary);
            cursor: pointer;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            accent-color: var(--primary-600);
        }

        .forgot-link {
            font-size: var(--text-sm);
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
    </style>
</head>

<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">C</div>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to your Construction ERP account</p>
            </div>

            <div class="login-body">
                <div id="login-alert" class="alert alert-error hidden"></div>

                <?php if (isset($_GET['expired'])): ?>
                    <div class="alert"
                        style="background: var(--warning-50); color: var(--warning-700); border: 1px solid var(--warning-500); margin-bottom: var(--space-4);">
                        <?= isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'Your session has expired. Please log in again.' ?>
                    </div>
                <?php endif; ?>

                <form id="login-form" class="login-form">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" class="form-input" id="email" name="email" placeholder="you@company.com"
                            required autofocus autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-input" id="password" name="password" placeholder="••••••••"
                            required minlength="6" autocomplete="current-password">
                    </div>

                    <div class="remember-row">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            Remember me
                        </label>
                        <a href="/forgot-password" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" id="login-btn">
                        Sign In
                    </button>
                </form>

                <div class="divider">or</div>

                <a href="/register" class="btn btn-outline btn-lg" style="width: 100%;">
                    Create New Account
                </a>
            </div>

            <div class="login-footer">
                Need help? <a href="mailto:support@constructerp.com">Contact Support</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = document.getElementById('login-btn');
            const alertEl = document.getElementById('login-alert');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            btn.disabled = true;
            btn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">↻</span> Signing in...';
            alertEl.classList.add('hidden');

            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();
                console.log('Login response:', data);

                if (data.success) {
                    if (data.data.requires_2fa) {
                        window.location.href = '/auth/verify-2fa?user_id=' + data.data.user_id;
                    } else {
                        // Save token and user data
                        localStorage.setItem('erp_token', data.data.token);
                        localStorage.setItem('erp_user', JSON.stringify(data.data.user));

                        // Get tenant slug from response
                        const slug = data.data.tenant?.subdomain || data.data.user?.tenant_subdomain;

                        if (slug) {
                            window.location.href = '/t/' + slug + '/dashboard';
                        } else {
                            // Fallback: fetch user info to get tenant
                            alertEl.textContent = 'Login successful! Redirecting...';
                            alertEl.classList.remove('hidden', 'alert-error');
                            alertEl.classList.add('alert-success');
                            window.location.href = '/';
                        }
                    }
                } else {
                    throw new Error(data.error || data.message || 'Login failed');
                }
            } catch (error) {
                console.error('Login error:', error);
                alertEl.textContent = error.message || 'Invalid credentials. Please try again.';
                alertEl.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Sign In';
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