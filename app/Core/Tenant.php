<?php
/**
 * Tenant Resolver
 * 
 * Handles multi-tenancy by detecting the tenant from URL path.
 * URL Format: /t/{tenant_slug}/...
 */

namespace App\Core;

class Tenant
{
    private int $id;
    private string $name;
    private string $subdomain; // Used as slug now
    private string $email;
    private array $settings = [];
    private static ?Tenant $current = null;
    private static ?string $currentSlug = null;

    // Path prefix for tenant routes
    public const PATH_PREFIX = '/t/';

    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->name = $data['name'];
        $this->subdomain = $data['subdomain']; // This is the slug
        $this->email = $data['email'] ?? '';
        $this->settings = json_decode($data['settings'] ?? '{}', true) ?: [];
    }

    /**
     * Get tenant slug from current request path
     * URL format: /t/{slug}/...
     */
    public static function getSlugFromPath(): ?string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);

        // Check if path starts with /t/
        if (strpos($path, self::PATH_PREFIX) === 0) {
            $remaining = substr($path, strlen(self::PATH_PREFIX));
            $parts = explode('/', $remaining);
            if (!empty($parts[0])) {
                return $parts[0];
            }
        }

        return null;
    }

    /**
     * Get the path after the tenant prefix
     * /t/{slug}/dashboard -> /dashboard
     */
    public static function getPathWithoutTenant(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);

        $slug = self::getSlugFromPath();
        if ($slug) {
            // Remove /t/{slug} from the beginning
            $prefix = self::PATH_PREFIX . $slug;
            if (strpos($path, $prefix) === 0) {
                return substr($path, strlen($prefix)) ?: '/';
            }
        }

        return $path;
    }

    /**
     * Check if current request is a tenant-scoped path
     */
    public static function isTenantPath(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);
        return strpos($path, self::PATH_PREFIX) === 0;
    }

    /**
     * Resolve tenant from current request path
     */
    public static function resolve(): ?Tenant
    {
        if (self::$current !== null) {
            return self::$current;
        }

        $slug = self::getSlugFromPath();
        self::$currentSlug = $slug;

        if (empty($slug)) {
            return null; // Public route, no tenant
        }

        return self::findBySlug($slug);
    }

    /**
     * Find tenant by slug (subdomain field)
     */
    public static function findBySlug(string $slug): ?Tenant
    {
        $db = Database::getInstance();
        $tenant = $db->fetch(
            "SELECT * FROM tenants WHERE subdomain = ? AND status = 'active' LIMIT 1",
            [$slug]
        );

        if (!$tenant) {
            return null;
        }

        self::$current = new self($tenant);
        $db->setTenantId(self::$current->getId());

        return self::$current;
    }

    /**
     * Find tenant by ID
     */
    public static function findById(int $id): ?Tenant
    {
        $db = Database::getInstance();
        $tenant = $db->fetch(
            "SELECT * FROM tenants WHERE id = ? AND status = 'active' LIMIT 1",
            [$id]
        );

        if (!$tenant) {
            return null;
        }

        return new self($tenant);
    }

    /**
     * Set current tenant (from JWT token, etc.)
     */
    public static function setCurrent(?Tenant $tenant): void
    {
        self::$current = $tenant;
        if ($tenant) {
            self::$currentSlug = $tenant->getSlug();
            Database::getInstance()->setTenantId($tenant->getId());
        }
    }

    /**
     * Set current tenant by ID
     */
    public static function setCurrentById(int $id): ?Tenant
    {
        $tenant = self::findById($id);
        if ($tenant) {
            self::setCurrent($tenant);
        }
        return $tenant;
    }

    /**
     * Get current tenant
     */
    public static function current(): ?Tenant
    {
        return self::$current;
    }

    /**
     * Get current tenant slug
     */
    public static function currentSlug(): ?string
    {
        return self::$currentSlug ?? (self::$current ? self::$current->getSlug() : null);
    }

    /**
     * Generate URL for tenant path
     */
    public function url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        return self::PATH_PREFIX . $this->subdomain . $path;
    }

    /**
     * Static method to generate tenant URL
     */
    public static function tenantUrl(string $slug, string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        return self::PATH_PREFIX . $slug . $path;
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->subdomain;
    }

    public function getSubdomain(): string
    {
        return $this->subdomain;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
