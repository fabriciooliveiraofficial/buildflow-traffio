<?php
/**
 * Construction ERP - Log Viewer Tool
 * Diagnostic tool for monitoring application events.
 */

// Define ROOT_PATH if not defined (standalone run)
if (!defined('ROOT_PATH')) define('ROOT_PATH', __DIR__);

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>ERP Debug Logs v1.1.4</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; line-height: 1.5; }
        .log-entry { margin-bottom: 5px; border-bottom: 1px solid #333; padding-bottom: 5px; }
        .level-DEBUG { color: #888; }
        .level-INFO { color: #569cd6; }
        .level-ERROR { color: #f44747; background: #4e1414; }
        .context { color: #ce9178; display: block; margin-left: 20px; font-size: 0.9em; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #569cd6; padding-bottom: 10px; margin-bottom: 20px; }
        .btn { background: #569cd6; color: white; border: none; padding: 5px 15px; cursor: pointer; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Diagnostic Logs v1.1.4</h1>
        <div>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="test_write">
                <button type="submit" class="btn" style="background: #6a9955;">Test Write</button>
            </form>
            <a href="/admin-logs.php" class="btn">Refresh</a>
            <a href="/clear-site-cache.php" class="btn" style="background: #ce9178;">Clear Cache</a>
            <a href="/dashboard" class="btn" style="background: #569cd6;">Dashboard</a>
        </div>
    </div>

    <div id="log-container">
        <?php
        $logFile = ROOT_PATH . '/storage/logs/app.log';
        
        // Handle Test Write
        if (isset($_POST['action']) && $_POST['action'] === 'test_write') {
            $testMsg = "[" . date('Y-m-d H:i:s') . "] [INFO] Manual Test Write from Admin-Logs\n";
            $res = file_put_contents($logFile, $testMsg, FILE_APPEND);
            if ($res === false) {
                echo "<p style='color: red; padding: 10px; border: 1px solid red;'>❌ WRITE FAILED! Check permissions of " . dirname($logFile) . "</p>";
            } else {
                echo "<p style='color: green; padding: 10px; border: 1px solid green;'>✅ WRITE SUCCESSFUL! $res bytes written.</p>";
            }
        }

        if (file_exists($logFile)) {
            $lines = file($logFile);
            $lines = array_reverse($lines); // Latest first
            $lines = array_slice($lines, 0, 100);

            foreach ($lines as $line) {
                // Parse: [date] [level] message {context}
                if (preg_match('/\[(.*?)\] \[(.*?)\] (.*?) (\{.*\})?/', $line, $matches)) {
                    $date = $matches[1];
                    $level = $matches[2];
                    $msg = $matches[3];
                    $context = isset($matches[4]) ? json_decode($matches[4], true) : null;

                    echo "<div class='log-entry'>";
                    echo "<span>[$date]</span> ";
                    echo "<strong class='level-$level'>[$level]</strong> ";
                    echo "<span>" . htmlspecialchars($msg) . "</span>";
                    if ($context) {
                        echo "<pre class='context'>" . htmlspecialchars(json_encode($context, JSON_PRETTY_PRINT)) . "</pre>";
                    }
                    echo "</div>";
                } else {
                    echo "<div class='log-entry'>" . htmlspecialchars($line) . "</div>";
                }
            }
        } else {
            echo "<p>No logs found at $logFile</p>";
        }
        ?>
    </div>
</body>
</html>
