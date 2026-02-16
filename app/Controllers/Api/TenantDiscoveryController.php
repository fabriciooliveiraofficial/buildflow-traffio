<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;

class TenantDiscoveryController extends Controller
{
    /**
     * Find tenants associated with an email
     */
    public function discover(Request $request): array
    {
        $email = $request->get('email');
        if (!$email) {
            $this->error('Email is required', 400);
        }

        // Search for employees/users with this email across all tenants
        $results = $this->db->fetchAll(
            "SELECT 
                t.id as tenant_id,
                t.name as tenant_name,
                t.subdomain,
                t.logo,
                e.user_id
            FROM employees e
            JOIN tenants t ON e.tenant_id = t.id
            WHERE e.email = ? AND e.status = 'active'",
            [$email]
        );

        if (empty($results)) {
            return $this->success([], 'No organizations found for this email');
        }

        // Enhance results with portal URLs
        $discovery = array_map(function($row) use ($email) {
            $baseUrl = getenv('APP_URL') ?: 'https://buildflow-traffio.com';
            // If using subdomains: company.traffio.com/portal
            $portalUrl = str_replace('://', '://' . ($row['subdomain'] ? $row['subdomain'] . '.' : ''), $baseUrl) . '/portal/';
            
            // If they need to set up a password, we append a query param
            if (!$row['user_id']) {
                $portalUrl .= '?action=setup&email=' . urlencode($email);
            }

            return [
                'name' => $row['tenant_name'],
                'logo' => $row['logo'] ?: '/assets/img/default-logo.png',
                'url' => $portalUrl,
                'has_account' => !empty($row['user_id'])
            ];
        }, $results);

        return $this->success($discovery);
    }
}
