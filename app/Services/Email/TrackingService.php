<?php
/**
 * Email Tracking Service
 * 
 * Handles open tracking (pixel) and click tracking (link rewriting)
 */

namespace App\Services\Email;

use App\Core\Database;

class TrackingService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Generate tracking pixel URL for email opens
     */
    public function generateTrackingPixel(int $logId): string
    {
        $token = $this->generateToken($logId, 'open');
        $baseUrl = $this->getBaseUrl();
        return "{$baseUrl}/email/track/open/{$token}";
    }

    /**
     * Generate tracked click URL
     */
    public function generateClickUrl(int $logId, string $originalUrl): string
    {
        $token = $this->generateToken($logId, 'click');
        $encodedUrl = base64_encode($originalUrl);
        $baseUrl = $this->getBaseUrl();
        return "{$baseUrl}/email/track/click/{$token}?url={$encodedUrl}";
    }

    /**
     * Process HTML body to add tracking
     */
    public function addTrackingToHtml(string $html, int $logId): string
    {
        // Add tracking pixel before </body> or at end
        $pixelUrl = $this->generateTrackingPixel($logId);
        $trackingPixel = '<img src="' . $pixelUrl . '" width="1" height="1" style="display:none" alt="" />';

        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $trackingPixel . '</body>', $html);
        } else {
            $html .= $trackingPixel;
        }

        // Rewrite links for click tracking
        $html = $this->rewriteLinks($html, $logId);

        return $html;
    }

    /**
     * Rewrite links in HTML to tracked URLs
     */
    private function rewriteLinks(string $html, int $logId): string
    {
        // Match href attributes
        $pattern = '/href=["\']([^"\']+)["\']/i';

        return preg_replace_callback($pattern, function ($matches) use ($logId) {
            $originalUrl = $matches[1];

            // Skip mailto, tel, and anchor links
            if (preg_match('/^(mailto:|tel:|#|javascript:)/i', $originalUrl)) {
                return $matches[0];
            }

            // Skip unsubscribe links (preserve for compliance)
            if (stripos($originalUrl, 'unsubscribe') !== false) {
                return $matches[0];
            }

            $trackedUrl = $this->generateClickUrl($logId, $originalUrl);
            return 'href="' . $trackedUrl . '"';
        }, $html);
    }

    /**
     * Record email open
     */
    public function recordOpen(string $token): bool
    {
        $data = $this->decodeToken($token);
        if (!$data || $data['type'] !== 'open') {
            return false;
        }

        $logId = $data['log_id'];

        // Update log
        $this->db->query(
            "UPDATE email_logs 
             SET status = 'opened',
                 opened_at = COALESCE(opened_at, NOW()),
                 opened_count = opened_count + 1,
                 ip_address = ?,
                 user_agent = ?
             WHERE id = ?",
            [
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $logId
            ]
        );

        return true;
    }

    /**
     * Record link click and return original URL
     */
    public function recordClick(string $token, string $encodedUrl): ?string
    {
        $data = $this->decodeToken($token);
        if (!$data || $data['type'] !== 'click') {
            return null;
        }

        $logId = $data['log_id'];
        $originalUrl = base64_decode($encodedUrl);

        if (!$originalUrl) {
            return null;
        }

        // Update log
        $this->db->query(
            "UPDATE email_logs 
             SET status = 'clicked',
                 clicked_at = COALESCE(clicked_at, NOW()),
                 clicked_count = clicked_count + 1,
                 ip_address = ?,
                 user_agent = ?
             WHERE id = ?",
            [
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $logId
            ]
        );

        return $originalUrl;
    }

    /**
     * Get email analytics for tenant
     */
    public function getAnalytics(int $days = 30): array
    {
        $tenantId = $this->db->getTenantId();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        // Daily stats
        $dailyStats = $this->db->fetchAll(
            "SELECT 
                DATE(sent_at) as date,
                COUNT(*) as sent,
                SUM(CASE WHEN status IN ('opened', 'clicked') THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced
             FROM email_logs
             WHERE tenant_id = ? AND sent_at >= ?
             GROUP BY DATE(sent_at)
             ORDER BY date DESC",
            [$tenantId, $startDate]
        );

        // Overall stats
        $totals = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_sent,
                SUM(CASE WHEN status IN ('opened', 'clicked') THEN 1 ELSE 0 END) as total_opened,
                SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as total_clicked,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as total_bounced
             FROM email_logs
             WHERE tenant_id = ? AND sent_at >= ?",
            [$tenantId, $startDate]
        );

        // Calculate rates
        $totalSent = (int) ($totals['total_sent'] ?? 0);
        $openRate = $totalSent > 0 ? round(($totals['total_opened'] / $totalSent) * 100, 1) : 0;
        $clickRate = $totalSent > 0 ? round(($totals['total_clicked'] / $totalSent) * 100, 1) : 0;
        $bounceRate = $totalSent > 0 ? round(($totals['total_bounced'] / $totalSent) * 100, 1) : 0;

        // Top performing emails (by open rate)
        $topEmails = $this->db->fetchAll(
            "SELECT subject, 
                    COUNT(*) as sent,
                    SUM(CASE WHEN status IN ('opened', 'clicked') THEN 1 ELSE 0 END) as opened,
                    SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked
             FROM email_logs
             WHERE tenant_id = ? AND sent_at >= ?
             GROUP BY subject
             HAVING sent >= 5
             ORDER BY (opened / sent) DESC
             LIMIT 5",
            [$tenantId, $startDate]
        );

        return [
            'period' => [
                'days' => $days,
                'start_date' => $startDate,
                'end_date' => date('Y-m-d'),
            ],
            'totals' => [
                'sent' => $totalSent,
                'opened' => (int) ($totals['total_opened'] ?? 0),
                'clicked' => (int) ($totals['total_clicked'] ?? 0),
                'failed' => (int) ($totals['total_failed'] ?? 0),
                'bounced' => (int) ($totals['total_bounced'] ?? 0),
            ],
            'rates' => [
                'open_rate' => $openRate,
                'click_rate' => $clickRate,
                'bounce_rate' => $bounceRate,
            ],
            'daily' => $dailyStats,
            'top_emails' => $topEmails,
        ];
    }

    /**
     * Generate secure token for tracking
     */
    private function generateToken(int $logId, string $type): string
    {
        $data = json_encode(['log_id' => $logId, 'type' => $type, 'ts' => time()]);
        $key = $_ENV['APP_KEY'] ?? 'default-email-tracking-key';

        // Simple encrypt with base64
        $iv = substr(md5($key), 0, 16);
        $encrypted = openssl_encrypt($data, 'AES-128-CBC', $key, 0, $iv);

        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    /**
     * Decode tracking token
     */
    private function decodeToken(string $token): ?array
    {
        try {
            $key = $_ENV['APP_KEY'] ?? 'default-email-tracking-key';
            $iv = substr(md5($key), 0, 16);

            // URL-safe base64 decode
            $encrypted = base64_decode(strtr($token, '-_', '+/'));
            $decrypted = openssl_decrypt($encrypted, 'AES-128-CBC', $key, 0, $iv);

            if (!$decrypted) {
                return null;
            }

            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get base URL for tracking endpoints
     */
    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$protocol}://{$host}";
    }
}
