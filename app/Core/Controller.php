<?php
/**
 * Base Controller
 * 
 * Provides common functionality for all controllers.
 */

namespace App\Core;

abstract class Controller
{
    protected Database $db;
    protected ?Tenant $tenant;
    protected ?array $user = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenant = Tenant::current();
    }

    /**
     * Get JSON input from request body
     */
    protected function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * Get query parameters
     */
    protected function getQueryParams(): array
    {
        return $_GET;
    }

    /**
     * Get a specific input value
     */
    protected function input(string $key, $default = null)
    {
        $input = $this->getJsonInput();
        return $input[$key] ?? $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Validate required fields
     */
    protected function validate(array $rules): array
    {
        $input = $this->getJsonInput();
        $errors = [];

        foreach ($rules as $field => $rule) {
            $ruleList = explode('|', $rule);
            $value = $input[$field] ?? null;

            foreach ($ruleList as $r) {
                if ($r === 'required' && empty($value)) {
                    $errors[$field] = "{$field} is required";
                }
                if (strpos($r, 'min:') === 0 && strlen($value) < (int) substr($r, 4)) {
                    $errors[$field] = "{$field} must be at least " . substr($r, 4) . " characters";
                }
                if (strpos($r, 'max:') === 0 && strlen($value) > (int) substr($r, 4)) {
                    $errors[$field] = "{$field} must not exceed " . substr($r, 4) . " characters";
                }
                if ($r === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "{$field} must be a valid email";
                }
                if ($r === 'numeric' && !is_numeric($value)) {
                    $errors[$field] = "{$field} must be a number";
                }
            }
        }

        if (!empty($errors)) {
            $this->error($errors, 422);
        }

        return $input;
    }

    /**
     * Send JSON success response
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): array
    {
        http_response_code($code);
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * Send JSON error response
     */
    protected function error($message, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
        ]);
        exit;
    }

    /**
     * Send paginated response
     */
    protected function paginate(array $data, int $total, int $page, int $perPage): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Get authenticated user
     */
    protected function getUser(): ?array
    {
        // Return stored user if set via setUser()
        if ($this->user !== null) {
            return $this->user;
        }

        // Fallback to session user (set by AuthMiddleware)
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        // Fallback to Auth::user() static method
        return Auth::user();
    }

    /**
     * Set authenticated user (called by auth middleware)
     */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    /**
     * Check if user has permission
     */
    protected function authorize(string $permission): bool
    {
        if (!$this->user) {
            $this->error('Unauthorized', 401);
        }

        // Admin has all permissions
        if ($this->user['role'] === 'admin') {
            return true;
        }

        // Check specific permission
        $userPermissions = $this->user['permissions'] ?? [];
        if (!in_array($permission, $userPermissions)) {
            $this->error('Forbidden', 403);
        }

        return true;
    }
}
