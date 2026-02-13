<?php
/**
 * Debug script to check Authorization header
 */

header('Content-Type: application/json');

// Get all possible header sources
$debug = [
    'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
    'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
    'getallheaders' => function_exists('getallheaders') ? getallheaders() : 'not available',
    'all_server_vars' => [],
];

// Find any Authorization-related server vars
foreach ($_SERVER as $key => $value) {
    if (stripos($key, 'auth') !== false || stripos($key, 'bearer') !== false) {
        $debug['all_server_vars'][$key] = $value;
    }
}

// Also check HTTP_ prefixed vars
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $debug['http_headers'][$key] = substr($value, 0, 50); // First 50 chars
    }
}

echo json_encode($debug, JSON_PRETTY_PRINT);
