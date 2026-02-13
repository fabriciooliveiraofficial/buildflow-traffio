<?php
/**
 * Email Unsubscribe Service
 * 
 * Manages email unsubscribe links and preferences
 */

namespace App\Services\Email;

use App\Core\Database;

class UnsubscribeService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Generate unsubscribe link for an email
     */
    public function generateUnsubscribeLink(string $email, int $tenantId): string
    {
        $token = $this->generateToken($email, $tenantId);
        $baseUrl = $this->getBaseUrl();
        return "{$baseUrl}/email/unsubscribe/{$token}";
    }

    /**
     * Add unsubscribe link to email HTML
     */
    public function addUnsubscribeLinkToHtml(string $html, string $email, int $tenantId): string
    {
        $unsubscribeLink = $this->generateUnsubscribeLink($email, $tenantId);

        $footer = '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 12px; color: #999;">'
            . '<p>You received this email because you are associated with our services.</p>'
            . '<p><a href="' . $unsubscribeLink . '" style="color: #666;">Unsubscribe from these emails</a></p>'
            . '</div>';

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $footer . '</body>', $html);
        }

        return $html . $footer;
    }

    /**
     * Process unsubscribe request
     */
    public function processUnsubscribe(string $token): array
    {
        $data = $this->decodeToken($token);
        if (!$data) {
            return ['success' => false, 'error' => 'Invalid or expired link'];
        }

        $email = $data['email'];
        $tenantId = $data['tenant_id'];

        // Check if already unsubscribed
        $existing = $this->db->fetch(
            "SELECT id FROM email_unsubscribes WHERE email = ? AND tenant_id = ?",
            [$email, $tenantId]
        );

        if ($existing) {
            return ['success' => true, 'message' => 'You are already unsubscribed'];
        }

        // Add to unsubscribe list
        $this->db->insert('email_unsubscribes', [
            'tenant_id' => $tenantId,
            'email' => $email,
            'unsubscribed_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        return ['success' => true, 'message' => 'You have been unsubscribed successfully'];
    }

    /**
     * Check if email is unsubscribed
     */
    public function isUnsubscribed(string $email): bool
    {
        $tenantId = $this->db->getTenantId();

        $result = $this->db->fetch(
            "SELECT id FROM email_unsubscribes WHERE email = ? AND tenant_id = ?",
            [$email, $tenantId]
        );

        return $result !== null;
    }

    /**
     * Resubscribe an email
     */
    public function resubscribe(string $email): bool
    {
        $tenantId = $this->db->getTenantId();

        $this->db->delete('email_unsubscribes', [
            'email' => $email,
            'tenant_id' => $tenantId,
        ]);

        return true;
    }

    /**
     * Get all unsubscribed emails for tenant
     */
    public function getUnsubscribedList(): array
    {
        $tenantId = $this->db->getTenantId();

        return $this->db->fetchAll(
            "SELECT email, unsubscribed_at FROM email_unsubscribes WHERE tenant_id = ? ORDER BY unsubscribed_at DESC",
            [$tenantId]
        );
    }

    /**
     * Generate secure token
     */
    private function generateToken(string $email, int $tenantId): string
    {
        $data = json_encode([
            'email' => $email,
            'tenant_id' => $tenantId,
            'ts' => time(),
        ]);

        $key = $_ENV['APP_KEY'] ?? 'default-unsubscribe-key';
        $iv = substr(md5($key), 0, 16);
        $encrypted = openssl_encrypt($data, 'AES-128-CBC', $key, 0, $iv);

        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    /**
     * Decode token
     */
    private function decodeToken(string $token): ?array
    {
        try {
            $key = $_ENV['APP_KEY'] ?? 'default-unsubscribe-key';
            $iv = substr(md5($key), 0, 16);

            $encrypted = base64_decode(strtr($token, '-_', '+/'));
            $decrypted = openssl_decrypt($encrypted, 'AES-128-CBC', $key, 0, $iv);

            if (!$decrypted) {
                return null;
            }

            $data = json_decode($decrypted, true);

            // Check if token is expired (30 days)
            if (isset($data['ts']) && (time() - $data['ts']) > 2592000) {
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get base URL
     */
    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$protocol}://{$host}";
    }
}
