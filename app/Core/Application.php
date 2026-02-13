<?php
/**
 * Application Bootstrap
 * 
 * Initializes core services and returns the application instance.
 */

namespace App\Core;

class Application
{
    private static ?Application $instance = null;
    private Router $router;
    private ?Database $database = null;
    private ?Tenant $tenant = null;
    private array $services = [];

    private function __construct()
    {
        $this->initialize();
    }

    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initialize(): void
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_lifetime' => SESSION_LIFETIME * 60,
                'cookie_secure' => SESSION_SECURE,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }

        // Initialize database
        $this->database = Database::getInstance();

        // Initialize router
        $this->router = new Router();

        // Load routes
        require ROOT_PATH . '/routes/web.php';
        require ROOT_PATH . '/routes/api.php';
    }

    public function run(): void
    {
        try {
            // Resolve tenant from request
            $this->tenant = Tenant::resolve();

            // Dispatch the request
            $response = $this->router->dispatch(
                $_SERVER['REQUEST_METHOD'],
                $_SERVER['REQUEST_URI']
            );

            // Send response
            if (is_array($response) || is_object($response)) {
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                echo $response;
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function handleException(\Exception $e): void
    {
        $code = $e->getCode() ?: 500;
        http_response_code($code);

        if (APP_DEBUG) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => 'An error occurred. Please try again later.'
            ]);
        }
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function register(string $name, $service): void
    {
        $this->services[$name] = $service;
    }

    public function get(string $name)
    {
        return $this->services[$name] ?? null;
    }
}

// Return application instance
return Application::getInstance();
