<?php
/**
 * Authentication Service
 * Updated: 2024-12-10 19:12 - Added developer token fallback
 * Handles JWT tokens, password hashing, and 2FA.
 */

namespace App\Core;

class Auth
{
    private Database $db;
    private static ?array $user = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Attempt login with email and password
     */
    public function attempt(string $email, string $password): ?array
    {
        $user = $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.permissions 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             WHERE u.email = ? AND u.status = 'active'",
            [$email]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return null;
        }

        // Check if 2FA is required
        if ($user['two_factor_enabled']) {
            return [
                'requires_2fa' => true,
                'user_id' => $user['id'],
            ];
        }

        // Generate token
        $token = $this->generateToken($user);

        // Update last login
        $this->db->update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
        ], ['id' => $user['id']]);

        // Get tenant info
        $tenant = null;
        if (!empty($user['tenant_id'])) {
            $tenant = $this->db->fetch(
                "SELECT id, name, subdomain FROM tenants WHERE id = ?",
                [$user['tenant_id']]
            );
        }

        return [
            'user' => $this->sanitizeUser($user),
            'token' => $token,
            'expires_in' => JWT_EXPIRY,
            'tenant' => $tenant,
        ];
    }

    /**
     * Verify 2FA code
     */
    public function verify2FA(int $userId, string $code): ?array
    {
        $user = $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.permissions 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             WHERE u.id = ?",
            [$userId]
        );

        if (!$user) {
            return null;
        }

        // Verify TOTP code (simplified - in production use a proper TOTP library)
        if (!$this->verifyTOTP($user['two_factor_secret'], $code)) {
            return null;
        }

        $token = $this->generateToken($user);

        return [
            'user' => $this->sanitizeUser($user),
            'token' => $token,
            'expires_in' => JWT_EXPIRY,
        ];
    }

    /**
     * Generate JWT token
     */
    public function generateToken(array $user): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'sub' => $user['id'],
            'tenant_id' => $user['tenant_id'],
            'role' => $user['role_name'] ?? $user['role'],
            'email' => $user['email'],
            'iat' => time(),
            'exp' => time() + JWT_EXPIRY,
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", JWT_SECRET, true)
        );

        return "{$header}.{$payload}.{$signature}";
    }

    /**
     * Validate JWT token and return user data
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", JWT_SECRET, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // Decode payload
        $data = json_decode($this->base64UrlDecode($payload), true);

        if (!$data) {
            return null;
        }

        // Check expiration
        if ($data['exp'] < time()) {
            return null;
        }

        // Check if this is a developer token (skip database lookup)
        if (!empty($data['is_dev_token'])) {
            $user = [
                'id' => $data['sub'],
                'tenant_id' => $data['tenant_id'] ?? null,
                'email' => $data['email'] ?? '',
                'role' => $data['role'] ?? 'super_admin',
                'role_name' => $data['role'] ?? 'super_admin',
                'is_developer' => true
            ];
            self::$user = $user;
            return $user;
        }

        // Get full user data from database
        $user = $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.permissions 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             WHERE u.id = ? AND u.status = 'active'",
            [$data['sub']]
        );

        // If user not found in database, use JWT payload data (for developers)
        if (!$user) {
            // JWT contains valid signature, so trust the payload
            $user = [
                'id' => $data['sub'],
                'tenant_id' => $data['tenant_id'] ?? null,
                'email' => $data['email'] ?? '',
                'role' => $data['role'] ?? 'user',
                'role_name' => $data['role'] ?? 'user',
                'is_developer' => true // Flag that this is a developer token
            ];
        }

        self::$user = $user;
        return $user;
    }

    /**
     * Get current authenticated user
     */
    public static function user(): ?array
    {
        return self::$user;
    }

    /**
     * Hash a password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Generate 2FA secret
     */
    public function generate2FASecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Verify TOTP code (simplified implementation)
     */
    private function verifyTOTP(string $secret, string $code): bool
    {
        // In production, use a proper TOTP library like spomky-labs/otphp
        $timeSlice = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            $expectedCode = $this->generateTOTP($secret, $timeSlice + $i);
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate TOTP code
     */
    private function generateTOTP(string $secret, int $timeSlice): string
    {
        $binary = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $binary, $this->base32Decode($secret), true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode base32 string
     */
    private function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper($input);
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $buffer = ($buffer << 5) | strpos($map, $input[$i]);
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }

        return $result;
    }

    /**
     * Remove sensitive data from user array
     */
    private function sanitizeUser(array $user): array
    {
        unset($user['password']);
        unset($user['two_factor_secret']);
        return $user;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
