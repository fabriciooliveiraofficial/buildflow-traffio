<?php
/**
 * Developer Login Page
 * Separate login for developers to access the Support Console
 */
$error = $GLOBALS['login_error'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Login | BuildFlow Support</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(71, 85, 105, 0.5);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.5);
        }

        .login-header h1 {
            color: #f1f5f9;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #94a3b8;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #f1f5f9;
            font-size: 15px;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
        }

        .form-control::placeholder {
            color: #475569;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #334155;
        }

        .login-footer a {
            color: #94a3b8;
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-footer a:hover {
            color: #a78bfa;
        }

        .security-note {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            color: #64748b;
            font-size: 12px;
            margin-top: 20px;
        }

        /* Background decoration */
        .bg-decoration {
            position: fixed;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.15;
            pointer-events: none;
        }

        .bg-decoration-1 {
            background: #7c3aed;
            top: -100px;
            right: -100px;
        }

        .bg-decoration-2 {
            background: #2563eb;
            bottom: -100px;
            left: -100px;
        }
    </style>
</head>

<body>
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                        <path d="m19.4 15 1.8 3.2c.1.2.1.5-.1.6l-2.5 1.4c-.2.1-.5.1-.6-.1L16.2 17" />
                        <path d="m4.6 15-1.8 3.2c-.1.2-.1.5.1.6l2.5 1.4c.2.1.5.1.6-.1L7.8 17" />
                        <path d="M12 5V2" />
                        <path d="M5.2 7.5 3 5.7" />
                        <path d="M18.8 7.5 21 5.7" />
                    </svg>
                </div>
                <h1>Developer Console</h1>
                <p>Sign in to access the Support Console</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/dev/login">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required
                        autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-login">
                    Sign In
                </button>
            </form>

            <div class="security-note">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                This is a restricted area. Authorized personnel only.
            </div>
        </div>

        <div class="login-footer">
            <a href="/">← Back to main site</a>
        </div>
    </div>
</body>

</html>