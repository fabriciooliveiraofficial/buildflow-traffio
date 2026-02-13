<?php
/**
 * Tenant Middleware
 * 
 * Ensures a valid tenant is set for tenant-scoped routes.
 * Tenant can be set via URL path (/t/{slug}/...) OR via JWT token (from AuthMiddleware).
 */

namespace App\Middleware;

use App\Core\Tenant;

class TenantMiddleware
{
    public function handle(): void
    {
        // If tenant already set by AuthMiddleware (from JWT), we're good
        if (Tenant::current() !== null) {
            return;
        }

        // Try to resolve from URL path (/t/{slug}/...)
        $tenant = Tenant::resolve();

        // If we're on a tenant path but tenant not found, error
        if (Tenant::isTenantPath() && !$tenant) {
            $slug = Tenant::getSlugFromPath();
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => "Company '{$slug}' not found or inactive.",
            ]);
            exit;
        }

        // If not on a tenant path, that's OK for public routes
    }
}
