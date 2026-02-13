<?php
/**
 * Router
 * 
 * Handles HTTP routing with support for RESTful routes and middleware.
 */

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $prefix = '';
    private array $groupMiddleware = [];

    /**
     * Register a GET route
     */
    public function get(string $uri, $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $uri, $handler, $middleware);
    }

    /**
     * Register a POST route
     */
    public function post(string $uri, $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $uri, $handler, $middleware);
    }

    /**
     * Register a PUT route
     */
    public function put(string $uri, $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $uri, $handler, $middleware);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $uri, $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $uri, $handler, $middleware);
    }

    /**
     * Register a PATCH route
     */
    public function patch(string $uri, $handler, array $middleware = []): self
    {
        return $this->addRoute('PATCH', $uri, $handler, $middleware);
    }

    /**
     * Group routes with common prefix and middleware
     */
    public function group(array $options, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        if (isset($options['prefix'])) {
            $this->prefix .= '/' . trim($options['prefix'], '/');
        }

        if (isset($options['middleware'])) {
            $this->groupMiddleware = array_merge($this->groupMiddleware, (array) $options['middleware']);
        }

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /**
     * Add a route to the collection
     */
    private function addRoute(string $method, string $uri, $handler, array $middleware = []): self
    {
        $uri = $this->prefix . '/' . trim($uri, '/');
        $uri = '/' . trim($uri, '/');

        // Convert route parameters {param} to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $uri);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[$method][$pattern] = [
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
            'uri' => $uri,
        ];

        return $this;
    }

    /**
     * Dispatch the request to the appropriate handler
     */
    public function dispatch(string $method, string $uri)
    {
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');

        // Handle OPTIONS for CORS
        if ($method === 'OPTIONS') {
            return $this->handleCors();
        }

        // Find matching route
        if (!isset($this->routes[$method])) {
            throw new \Exception('Method not allowed', 405);
        }

        foreach ($this->routes[$method] as $pattern => $route) {
            if (preg_match($pattern, $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $middleware) {
                    $this->runMiddleware($middleware);
                }

                // Call handler
                return $this->callHandler($route['handler'], $params);
            }
        }

        throw new \Exception('Route not found', 404);
    }

    /**
     * Run middleware
     */
    private function runMiddleware(string $middleware): void
    {
        $class = "App\\Middleware\\{$middleware}";

        if (!class_exists($class)) {
            throw new \Exception("Middleware {$middleware} not found");
        }

        $instance = new $class();
        $instance->handle();
    }

    /**
     * Call the route handler
     */
    private function callHandler($handler, array $params = [])
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler);
            $class = "App\\Controllers\\{$controller}";

            if (!class_exists($class)) {
                throw new \Exception("Controller {$controller} not found");
            }

            $instance = new $class();

            if (!method_exists($instance, $method)) {
                throw new \Exception("Method {$method} not found in {$controller}");
            }

            return call_user_func_array([$instance, $method], $params);
        }

        throw new \Exception('Invalid route handler');
    }

    /**
     * Handle CORS preflight
     */
    private function handleCors(): string
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        return '';
    }
}
