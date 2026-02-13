<?php
/**
 * Authentication Middleware
 * 
 * Validates JWT token on protected routes and sets tenant context.
 */

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Tenant;

class AuthMiddleware
{
    public function handle(): void
    {
        $token = $this->getToken();

        if (!$token) {
            $this->unauthorized('No token provided');
        }

        $auth = new Auth();
        $user = $auth->validateToken($token);

        if (!$user) {
            $this->unauthorized('Invalid or expired token');
        }

        // Store user in session for access in controllers
        $_SESSION['user'] = $user;

        // Set tenant context from user's tenant_id
        if (!empty($user['tenant_id'])) {
            Tenant::setCurrentById((int) $user['tenant_id']);
        }
    }

    private function getToken(): ?string
    {
        // Check Authorization header (standard)
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Fallback: Check REDIRECT_HTTP_AUTHORIZATION (set by .htaccess on shared hosting)
        if (empty($header)) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        }

        // Fallback: Try getallheaders() function
        if (empty($header) && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return $matches[1];
        }

        // Check query parameter (for file downloads etc.)
        if (isset($_GET['token'])) {
            return $_GET['token'];
        }

        return null;
    }

    private function unauthorized(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
        ]);
        exit;
    }
}

