<?php
/**
 * Authentication API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;
use App\Models\Tenant;

class AuthController extends Controller
{
    private Auth $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new Auth();
    }

    /**
     * Login with email and password
     */
    public function login(): array
    {
        $data = $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $result = $this->auth->attempt($data['email'], $data['password']);

        if (!$result) {
            $this->error('Invalid credentials', 401);
        }

        if (isset($result['requires_2fa']) && $result['requires_2fa']) {
            return $this->success([
                'requires_2fa' => true,
                'user_id' => $result['user_id'],
            ], 'Two-factor authentication required');
        }

        return $this->success($result, 'Login successful');
    }

    /**
     * Register a new tenant and admin user
     */
    public function register(): array
    {
        $data = $this->validate([
            'company_name' => 'required|min:2',
            'subdomain' => 'required|min:3',
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        // Check if subdomain exists
        $existingTenant = $this->db->fetch(
            "SELECT id FROM tenants WHERE subdomain = ?",
            [$data['subdomain']]
        );

        if ($existingTenant) {
            $this->error('Subdomain is already taken', 422);
        }

        // Check if email exists
        $existingUser = $this->db->fetch(
            "SELECT id FROM users WHERE email = ?",
            [$data['email']]
        );

        if ($existingUser) {
            $this->error('Email is already registered', 422);
        }

        try {
            $this->db->beginTransaction();

            // Create tenant
            $tenantId = $this->db->insert('tenants', [
                'name' => $data['company_name'],
                'subdomain' => strtolower($data['subdomain']),
                'email' => $data['email'],
                'status' => 'active',
            ]);

            // Get admin role
            $adminRole = $this->db->fetch(
                "SELECT id FROM roles WHERE name = 'admin' AND tenant_id IS NULL"
            );

            // Create admin user
            $userId = $this->db->insert('users', [
                'tenant_id' => $tenantId,
                'role_id' => $adminRole['id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $this->auth->hashPassword($data['password']),
                'status' => 'active',
            ]);

            // Create default settings for tenant
            $defaultSettings = [
                'language' => 'en',
                'theme' => 'light',
                'timezone' => 'America/New_York',
                'date_format' => 'Y-m-d',
                'currency' => 'USD',
            ];

            foreach ($defaultSettings as $key => $value) {
                $this->db->insert('settings', [
                    'tenant_id' => $tenantId,
                    'key' => $key,
                    'value' => $value,
                ]);
            }

            $this->db->commit();

            // Get user with role
            $user = $this->db->fetch(
                "SELECT u.*, r.name as role_name 
                 FROM users u 
                 LEFT JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = ?",
                [$userId]
            );

            // Generate token
            $token = $this->auth->generateToken($user);

            return $this->success([
                'tenant' => [
                    'id' => $tenantId,
                    'name' => $data['company_name'],
                    'subdomain' => $data['subdomain'],
                ],
                'user' => [
                    'id' => $userId,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'role' => 'admin',
                ],
                'token' => $token,
            ], 'Registration successful', 201);

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Verify 2FA code
     */
    public function verify2FA(): array
    {
        $data = $this->validate([
            'user_id' => 'required|numeric',
            'code' => 'required',
        ]);

        $result = $this->auth->verify2FA((int) $data['user_id'], $data['code']);

        if (!$result) {
            $this->error('Invalid verification code', 401);
        }

        return $this->success($result, 'Verification successful');
    }

    /**
     * Get current user profile
     */
    public function me(): array
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            $this->error('Not authenticated', 401);
        }

        unset($user['password']);
        unset($user['two_factor_secret']);

        // Get tenant info
        $tenant = $this->db->fetch(
            "SELECT id, name, subdomain, logo FROM tenants WHERE id = ?",
            [$user['tenant_id']]
        );

        return $this->success([
            'user' => $user,
            'tenant' => $tenant,
        ]);
    }

    /**
     * Logout (invalidate token)
     */
    public function logout(): array
    {
        // In a real implementation, you would blacklist the token
        session_destroy();
        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Refresh token - generates a new token for the authenticated user
     */
    public function refresh(): array
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            $this->error('Not authenticated', 401);
        }

        // Get fresh user data
        $userData = $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.permissions 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             WHERE u.id = ? AND u.status = 'active'",
            [$user['id']]
        );

        if (!$userData) {
            $this->error('User not found or inactive', 401);
        }

        // Generate new token
        $token = $this->auth->generateToken($userData);

        return $this->success([
            'token' => $token,
            'expires_in' => JWT_EXPIRY,
        ], 'Token refreshed successfully');
    }

    /**
     * Enable 2FA
     */
    public function enable2FA(): array
    {
        $user = $_SESSION['user'];

        $secret = $this->auth->generate2FASecret();

        $this->db->update('users', [
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
        ], ['id' => $user['id']]);

        return $this->success([
            'secret' => $secret,
            'qr_url' => $this->generateQRUrl($user['email'], $secret),
        ], '2FA enabled');
    }

    /**
     * Update user profile
     */
    public function updateProfile(): array
    {
        $user = $_SESSION['user'];

        $data = $this->getJsonInput();

        $updateData = [];

        if (isset($data['first_name'])) {
            $updateData['first_name'] = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $updateData['last_name'] = $data['last_name'];
        }
        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }
        if (isset($data['preferences'])) {
            $updateData['preferences'] = json_encode($data['preferences']);
        }

        if (!empty($updateData)) {
            $this->db->update('users', $updateData, ['id' => $user['id']]);
        }

        return $this->success(null, 'Profile updated');
    }

    /**
     * Generate QR URL for 2FA
     */
    private function generateQRUrl(string $email, string $secret): string
    {
        $issuer = urlencode(APP_NAME);
        $label = urlencode($email);
        return "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}";
    }
}
