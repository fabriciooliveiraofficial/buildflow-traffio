<?php
/**
 * 500 Error View (PHP version)
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - Construction ERP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .container {
            text-align: center;
            padding: 2rem;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #f64f59 0%, #c471ed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .error-title {
            font-size: 1.5rem;
            margin: 1rem 0;
            opacity: 0.9;
        }

        .error-message {
            color: #aaa;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
        }

        .debug {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 2rem;
            text-align: left;
            max-width: 600px;
            font-family: monospace;
            font-size: 0.8rem;
            color: #ff6b6b;
            word-break: break-all;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="error-code">500</div>
        <h1 class="error-title">Something Went Wrong</h1>
        <p class="error-message">We're experiencing technical difficulties. Please try again later.</p>
        <a href="/" class="btn">Go Home</a>

        <?php if (getenv('APP_DEBUG') === 'true' && isset($e)): ?>
            <div class="debug">
                <strong>Debug Info:</strong><br>
                <?= htmlspecialchars($e->getMessage()) ?><br>
                File: <?= $e->getFile() ?>:<?= $e->getLine() ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
