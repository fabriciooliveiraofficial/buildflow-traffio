<?php
// Simple test to verify subdomain reaches public_html
echo "<h1>Subdomain Test</h1>";
echo "<p>If you see this, the subdomain reaches public_html!</p>";
echo "<p><strong>Host:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p><strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
