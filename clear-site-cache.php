<?php
/**
 * Construction ERP - Cache Management Tool
 * Run this script to clear OPCache and suggest LiteSpeed flush.
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>ERP Environment Diagnostic v1.1.4</h1>";
echo "<hr>";

// 1. Clear OPCache
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color: green;'>✅ OPCache Reset: Successful</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ OPCache Reset: Not needed or already clear</p>";
    }
}

// 2. Fix Logging Permissions
$logDir = __DIR__ . '/storage/logs';
if (!is_dir($logDir)) {
    if (mkdir($logDir, 0777, true)) {
        echo "<p style='color: green;'>✅ Log directory created: $logDir</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create log directory: $logDir</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Log directory exists.</p>";
    if (chmod($logDir, 0777)) {
        echo "<p style='color: green;'>✅ Permissions updated to 0777 on log directory.</p>";
    }
}

// 3. Check specific files
$filesToCheck = ['views/layouts/main.php', 'assets/js/app.js', 'routes/api.php'];
echo "<h3>File Verification:</h3><ul>";
foreach ($filesToCheck as $f) {
    if (file_exists(__DIR__ . '/' . $f)) {
        $mtime = date("Y-m-d H:i:s", filemtime(__DIR__ . '/' . $f));
        $size = filesize(__DIR__ . '/' . $f);
        echo "<li><b>$f</b>: Modified at $mtime (Size: $size bytes)</li>";
    } else {
        echo "<li style='color: red;'>$f NOT FOUND</li>";
    }
}
echo "</ul>";

// 4. LiteSpeed Headers
header('X-LiteSpeed-Purge: *');
echo "<p style='color: blue;'>ℹ️ LiteSpeed Purge Header: Sent (*)</p>";

// 4. Instructions
echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li>Please go back to the <a href='/dashboard'>Dashboard</a>.</li>";
echo "<li>Press <b>Ctrl + F5</b> (Hard Refresh) to clear browser cache.</li>";
echo "<li>Look for <b>BUILD_VERSION: 1.1.3</b> in the page source to confirm update.</li>";
echo "</ul>";

echo "<p><small>Build time: " . date('Y-m-d H:i:s') . "</small></p>";
