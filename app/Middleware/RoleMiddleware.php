<?php
/**
 * Role Middleware
 * 
 * Restricts access based on user roles.
 */

namespace App\Middleware;

class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles = [])
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(): void
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            $this->forbidden('Authentication required');
        }

        $userRole = $user['role_name'] ?? $user['role'] ?? '';

        // Admin always has access
        if ($userRole === 'admin') {
            return;
        }

        // Check if user role is in allowed roles
        if (!empty($this->allowedRoles) && !in_array($userRole, $this->allowedRoles)) {
            $this->forbidden('Insufficient permissions');
        }
    }

    private function forbidden(string $message): void
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
        ]);
        exit;
    }
}
