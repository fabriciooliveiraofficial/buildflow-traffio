<?php
/**
 * Helper Functions
 */

/**
 * Get environment variable with default
 */
function env(string $key, $default = null)
{
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

/**
 * Dump and die
 */
function dd(...$vars): void
{
    foreach ($vars as $var) {
        var_dump($var);
    }
    die();
}

/**
 * Format currency
 */
function formatCurrency(float $amount, string $currency = 'USD'): string
{
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
    ];
    $symbol = $symbols[$currency] ?? '$';
    return $symbol . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate(?string $date, string $format = 'M j, Y'): string
{
    if (!$date)
        return '';
    return date($format, strtotime($date));
}

/**
 * Generate a random string
 */
function randomString(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Sanitize filename
 */
function sanitizeFilename(string $filename): string
{
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
}

/**
 * Get file extension
 */
function getFileExtension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if request is AJAX
 */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get client IP
 */
function getClientIP(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Flash message helper
 */
function flash(string $key, $value = null)
{
    if ($value === null) {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    $_SESSION['flash'][$key] = $value;
}

/**
 * Redirect helper
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Asset URL helper
 */
function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

/**
 * URL helper
 */
function url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}
