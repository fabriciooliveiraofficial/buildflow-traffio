<?php
/**
 * Email Unsubscribe Confirmation Page
 * Public page - no auth required
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Unsubscribe</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            color: #1a1a1a;
            font-size: 24px;
            margin-bottom: 15px;
        }

        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .success {
            color: #16a34a;
        }

        .error {
            color: #dc2626;
        }

        .info {
            color: #2563eb;
        }

        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5a67d8;
        }
    </style>
</head>

<body>
    <div class="card">
        <?php if (isset($result) && $result['success']): ?>
            <div class="icon">✅</div>
            <h1 class="success">Successfully Unsubscribed</h1>
            <p><?= htmlspecialchars($result['message']) ?></p>
            <p>You will no longer receive marketing emails from us.</p>
        <?php else: ?>
            <div class="icon">⚠️</div>
            <h1 class="error">Unable to Unsubscribe</h1>
            <p><?= htmlspecialchars($result['error'] ?? 'The unsubscribe link may be invalid or expired.') ?></p>
            <p>Please contact support if you continue to receive unwanted emails.</p>
        <?php endif; ?>
        <a href="/" class="btn">Return to Homepage</a>
    </div>
</body>

</html>
