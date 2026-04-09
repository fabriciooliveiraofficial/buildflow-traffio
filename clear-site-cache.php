<?php
/**
 * Construction ERP - Cache Management Tool
 * Run this script to clear OPCache and suggest LiteSpeed flush.
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>ERP Cache Management v1.1.3</h1>";
echo "<hr>";

// 1. Clear OPCache
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color: green;'>✅ OPCache Reset: Successful</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ OPCache Reset: Failed or not needed</p>";
    }
} else {
    echo "<p style='color: red;'>❌ OPCache: Function not available</p>";
}

// 2. Clear APCu
if (function_exists('apcu_clear_cache')) {
    if (apcu_clear_cache()) {
        echo "<p style='color: green;'>✅ APCu Cache: Cleared</p>";
    }
}

// 3. LiteSpeed Headers
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
